<?php

if (!function_exists("rse_workflow_get_actions")){
    function rse_workflow_get_actions($status="",$ref="")
            {
            # Check if we are searching for actions specific to a status
            $condition="";
            if($status!="" && is_int($status)){$condition=" where wa.statusfrom='status' ";}
            if($ref!=""){$condition=" where wa.ref='$ref' ";}
            $actions=sql_query("select wa.ref, wa.text, wa.name, wa.buttontext, wa.statusfrom, wa.statusto,a.notify_group,a.name as statusto_name,a.more_notes_flag,a.notify_user_flag, a.email_from, a.bcc_admin from workflow_actions wa left outer join archive_states a on wa.statusto=a.code $condition group by wa.ref order by wa.ref,wa.statusfrom,wa.statusto asc");
            return $actions;
            }
    }

if (!function_exists("rse_workflow_save_action")){
    function rse_workflow_save_action($ref="")
            {
            if($ref==""){$ref=getvalescaped("ref","");};
            $fromstate=getvalescaped("actionfrom","");
            $tostate=getvalescaped("actionto","");
            $name=getvalescaped("actionname","");
            $text=getvalescaped("actiontext","");
            $buttontext=getvalescaped("actionbuttontext","");
            
            # Check if we are searching for actions specific to a status
            sql_query("update workflow_actions set name='$name', text='$text', buttontext='' statusfrom='$fromstate', statusto='$tostate' where ref='$ref'");
            return true;
            }
    }

if (!function_exists("rse_workflow_delete_action")){
    function rse_workflow_delete_action($action)
        {
        sql_query("delete from workflow_actions where ref='$action'");
        return true;  
        }
    }   

if (!function_exists("rse_workflow_get_archive_states")){
    function rse_workflow_get_archive_states()
            {
            $rawstates=sql_query("
                    SELECT code,
                           name,
                           notify_group,
                           more_notes_flag,
                           notify_user_flag,
                           email_from,
                           bcc_admin,
                           simple_search_flag
                      FROM archive_states
                  ORDER BY code ASC","workflow");

            global $additional_archive_states, $lang;
            $states=array();
            foreach($rawstates as $rawstate)
                {
                // Reformat into new array
                $states[$rawstate['code']]['name']=$rawstate['name'];
                $states[$rawstate['code']]['notify_group']=$rawstate['notify_group'];
                $states[$rawstate['code']]['more_notes_flag']=$rawstate['more_notes_flag'];
                $states[$rawstate['code']]['notify_user_flag']=$rawstate['notify_user_flag'];
                $states[$rawstate['code']]['rse_workflow_email_from']=$rawstate['email_from'];
                $states[$rawstate['code']]['rse_workflow_bcc_admin']=$rawstate['bcc_admin'];
                $states[$rawstate['code']]['simple_search_flag'] = $rawstate['simple_search_flag'];
                // Identify states that are set in config.php and cannot be deleted from plugin                
                if(in_array($rawstate['code'],$additional_archive_states))
                    {
                    $states[$rawstate['code']]['fixed']=true;
                    }
                else
                    {
                    $states[$rawstate['code']]['fixed']=false; 
                    }
                }
            
            //Add $additional_archive_states from config.php to table so can be managed by plugin if deleted from config                    
            foreach($additional_archive_states as $additional_archive_state)
                {
                if (!isset($states[$additional_archive_state]))
                    {
                    // Set name of archive state (will just be the ref if not set in lang file)
                    $statename= $additional_archive_state; 
                    if(isset($lang['status' . $additional_archive_state]))
                        {
                        $statename=$lang['status' . $additional_archive_state];
                        }
                    sql_query("insert into archive_states set code='" . escape_check($additional_archive_state) . "', name='" . escape_check($statename) . "'");
                    clear_query_cache("workflow");
                    $states[$additional_archive_state]['name']=$lang['status' . $additional_archive_state];
                    $states[$additional_archive_state]['fixed']=true;
                    }
                }

            // Add default system states
            for($workflow_state = -2; $workflow_state <= 3; $workflow_state++)
                {
                $workflow_state_name = $lang["status{$workflow_state}"];

                if (!isset($states[$workflow_state]))
                    {
                    $simple_search_flag = ($workflow_state == 0 ? 1 : 0);

                    sql_query("
                        INSERT INTO archive_states
                                SET code = '" . escape_check($workflow_state) . "',
                                    name = '" . escape_check($workflow_state_name) . "',
                                    simple_search_flag = '{$simple_search_flag}'");
                    clear_query_cache("workflow");
                    }

                $states[$workflow_state]['name'] = $workflow_state_name;
                $states[$workflow_state]['fixed'] = true;
                }

            return $states;
            }
    }
    
if (!function_exists("rse_workflow_delete_state")){
    function rse_workflow_delete_state($state,$newstate)
        {		
        sql_query("update resource set archive='" . escape_check($newstate) . "' where archive='" . escape_check($state) . "'");
        sql_query("delete from archive_states where code='" . escape_check($state) . "'");
        clear_query_cache("workflow");
        return true;  
        }
    } 

/**
* Validate list of actions for a resource or a batch of resources. For a batch of 
* resources, an action is valid only if using the 'wf' permission is set for that action.
* 
* @param array $actions         List of workflow actions (@see rse_workflow_get_actions())
* @param bool  $use_perms_only  Validate actions using edit access (e perm) on the destination state -OR- 'wf' permissions
* 
* @return array
*/
function rse_workflow_get_valid_actions(array $actions, $use_perms_only)
    {
    /** $resource, $edit_access are used on the view page to determine valid actions for a resource where we run a 
    * proper action validation (@see rse_workflow_validate_action())
    */
    global $resource, $edit_access;

    if($use_perms_only)
        {
        return array_filter($actions, function($action)
            {
            return checkperm("e{$action['statusto']}") || checkperm("wf{$action['ref']}");
            });
        }

    return array_filter($actions, function($action) use ($resource, $edit_access)
        {
        $resource["edit_access"] = $edit_access;
        return rse_workflow_validate_action($action, $resource);
        });
    }

/**
* Validate a workflow action for a particular resource
* 
* @param array $action   Workflow action structure (@see rse_workflow_get_actions())
* @param array $resource Resource structure (@see get_resource_data() or do_search())
* 
* @return bool
*/
function rse_workflow_validate_action(array $action, array $resource)
    {
    if(empty($action) || empty($resource))
        {
        return false;
        }

    $resource_in_valid_state = in_array($resource['archive'], explode(',', $action['statusfrom']));

    // resource[edit_access] can be added by outside context if this information is already available to increase performance
    // if action is validated for a list of resources (3k+) that we had to iterate over
    if(!isset($resource["edit_access"]))
        {
        $edit_access = ($resource["access"] == 0 && get_edit_access($resource["ref"], $resource["archive"], false, $resource));
        }
    else
        {
        $edit_access = (is_bool($resource["edit_access"]) ? $resource["edit_access"] : false);
        }

    $check_edit_access = ($edit_access && checkperm("e{$action['statusto']}"));

    // Provide workflow action option if user has access to it without having edit access to resource
    // Use case: a particular user group doesn't have access to the archive state but still needs to be
    // able to move the resource to a different state.
    $checkperm_wf = checkperm("wf{$action['ref']}");

    return ($resource_in_valid_state && ($check_edit_access || $checkperm_wf));
    }


/**
* Compile workflow actions for the unified dropdown actions. This will validate actions using only 'wf' permissions (@see rse_workflow_get_valid_actions)
* 
* @param array $url_params Inject any url params if needed. Useful to pass along search params.
* 
* @return array
*/
function rse_workflow_compile_actions(array $url_params)
    {
    // Validate actions without going through all resources to not impact performance on huge sets
    $valid_actions = rse_workflow_get_valid_actions(rse_workflow_get_actions(), true);
    if(empty($valid_actions))
        {
        return array();
        }

    global $baseurl;

    $wf_actions = array();
    foreach($valid_actions as $action)
        {
        $option = array(
            "value" => "rse_workflow_move_to_workflow",
            "label" => i18n_get_translated($action["buttontext"]),
            "data_attr" => array(
                "url" => generateURL(
                    "{$baseurl}/plugins/rse_workflow/pages/batch_action.php",
                    $url_params,
                    array(
                        "action" => $action["ref"],
                    )),
            ),
            "category" => ACTIONGROUP_EDIT
        );

        $wf_actions[] = $option;
        }

    return $wf_actions;
    }