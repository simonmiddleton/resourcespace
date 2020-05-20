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
* resources, an action is valid if 
* 
* @param array $actions List of workflow actions (@see rse_workflow_get_actions())
* 
* @return array
*/
function rse_workflow_get_valid_actions(array $actions, array $resources)
    {
    // $resource, $edit_access are used only on the view page to determine valid actions for a resource
    global $resource, $edit_access;

    /*valid actions use cases:
    done - per resource (how it works on the view page) -> valid_states, edit_access, checkperm_wf
    - for list of resources (to display in the unified dropdown actions) -> edit_access?, checkperm_wf
    - for list of resources (to inform users how many resources will be affected) -> valid_states, edit_access, checkperm_wf @ for each resource*/
    $filter_list_generically = function($action) use ($resources)
        {
        return checkperm("wf{$action['ref']}");
        };

    $filter_for_resource = function($action) use ($resources)
        {
        return false;
        };

    if(empty($resources))
        {
        return array_filter($actions, $filter_list_generically);
        }

    return array_filter($actions, function($action) use ($resource, $edit_access)
        {
        $valid_states = explode(',', $action['statusfrom']); 

        // todo: cater for batch of resources (we don't check in that case)
        // @todo: establish how we handle the UX:
        // 1. provide options even if action will affect just a subset? Also display how many it will affect
        // 2. show options only when all criteria match for all resources (e.g allow_multi_edit and all resources in Active state)
        $resource_in_valid_state = in_array($resource['archive'], $valid_states);
        $check_edit_access = ($edit_access && checkperm("e{$action['statusto']}"));

        // if checking valid actions for batch
        // $check_valid_states = ;
        // $check_edit_access = false; # 

        // Provide workflow action option if user has access to it without having edit access to resource
        // Use case: a particular user group doesn't have access to the archive state but still needs to be
        // able to move the resource to a different state.
        $checkperm_wf = checkperm("wf{$action['ref']}");

        return ($resource_in_valid_state && ($check_edit_access || $checkperm_wf));
        });
    }


function rse_workflow_validate_action(array $action, array $resource)
    {
    if(empty($action) || empty($resource))
        {
        return false;
        }

    $resource_in_valid_state = in_array($resource['archive'], explode(',', $action['statusfrom']));

    $edit_access = false;
    // resource[edit_access] can be added by outside context if this information is already available to increase performance
    // if action is validated for a list of resources (3k+) that we had to iterate over
    if(!isset($resource["edit_access"]))
        {
        // $edit_access = ($access==0 && get_edit_access($resource_ref, $resource["archive"], false, $resource));
        $edit_access = ($resource["access"] == 0 && get_edit_access($resource["ref"], $resource["archive"], false, $resource));
        }

    $check_edit_access = ($edit_access && checkperm("e{$action['statusto']}"));

    // Provide workflow action option if user has access to it without having edit access to resource
    // Use case: a particular user group doesn't have access to the archive state but still needs to be
    // able to move the resource to a different state.
    $checkperm_wf = checkperm("wf{$action['ref']}");

    return ($resource_in_valid_state && ($check_edit_access || $checkperm_wf));
    }