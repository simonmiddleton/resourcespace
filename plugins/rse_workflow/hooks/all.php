<?php
function HookRse_workflowAllInitialise()
     {
	 include_once dirname(__FILE__)."/../include/rse_workflow_functions.php";
	 include_once dirname(__FILE__)."/../../../include/language_functions.php";
     # Deny access to specific pages if RSE_KEY is not enabled and a valid key is not found.
     global $pagename, $additional_archive_states, $fixed_archive_states, $wfstates, $searchstates;
    
    # Update $archive_states and associated $lang variables with entries from database
    $searchstates = array();
    $wfstates=rse_workflow_get_archive_states();
    
	global $lang;
	foreach($wfstates as $wfstateref=>$wfstate)
		{
		if (!$wfstate['fixed'])
			{
			$additional_archive_states[]=$wfstateref;
            }
        else
            {
            // Save for later so we know which are editable
            $fixed_archive_states[] = $wfstateref;
            }
        if((isset($wfstate['simple_search_flag']) && $wfstate['simple_search_flag'] != 0) || $wfstateref == 0) // Always include active state
            {
            $searchstates[] = $wfstateref;
            }
        $lang["status" . $wfstateref] =  i18n_get_translated($wfstate["name"]);
		}
    natsort($additional_archive_states);		 
    }
    
function HookRse_workflowAllAfter_update_archive_status($resource, $archive, $existingstates)
    {
    global  $baseurl, $lang, $userref, $wfstates, $applicationname, $use_phpmailer;
    
    $rse_workflow_from="";
    if (isset($wfstates[$archive]["rse_workflow_email_from"]) && $wfstates[$archive]["rse_workflow_email_from"]!="")
        {
        $rse_workflow_from=$wfstates[$archive]["rse_workflow_email_from"];
        }

    $workflowaction = getval('workflowaction','');

    // Set message text and URL to link to resources
    // The field 'more_workflow_action' is a hidden field which carries input text on the action specific form
    // A textarea named 'more_for_workflow_action' is effectively bound to and copies any keyboard input to 'more_workflow_action' 
    $message = $lang["rse_workflow_state_notify_message"] . $lang["status" . $archive];
    
    if(getval('more_workflow_action_' . $workflowaction,'') != '')
        {
            $message .= "\n\n" . $lang["rse_workflow_more_notes_title"];
            $message .= "\n\n" . getval('more_workflow_action_' . $workflowaction, '');
        }
        
    if(count($resource) > 200)
        {
        // Too many resources to link to directly
        $linkurl = $baseurl . "/pages/search.php?search=archive" . $archive;
        }
     else
        {
        $linkurl = $baseurl . "/pages/search.php?search=!list" . implode(":",$resource);;
        }
    
    $maillinkurl = (($use_phpmailer) ? "<a href=\"$linkurl\">$linkurl</a>" : $linkurl); // Convert to anchor link if using html mails
      
    /***** NOTIFY GROUP SUPPORT *****/
    if(isset($wfstates[$archive]['notify_group']) && $wfstates[$archive]['notify_group'] != '')
        {   
        $archive_notify = sql_query("
            SELECT ref, email
              FROM user
             WHERE approved = 1
               AND usergroup = '" . escape_check($wfstates[$archive]['notify_group']) . "'
        ");

        // Send notifications to members of usergroup
        foreach($archive_notify as $archive_notify_user)
            {
            debug("processing notification for notify user " . $archive_notify_user['ref']);
            get_config_option($archive_notify_user['ref'],'user_pref_resource_notifications', $send_message);          
            if($send_message==false)
                {
                continue;
                }
                
            // Does this user want an email or notification?
            get_config_option($archive_notify_user['ref'],'email_user_notifications', $send_email); 
            if($send_email && filter_var($archive_notify_user["email"], FILTER_VALIDATE_EMAIL))
                {
                debug("sending email notification to user " . $archive_notify_user['ref']);
                send_mail($archive_notify_user["email"],$applicationname . ": " . $lang["status" . $archive],$message . "\n\n" . $maillinkurl);
                }
            else
                {
                global $userref;
                debug("sending system notification to user " . $archive_notify_user['ref']);
                message_add($archive_notify_user['ref'],$message,$linkurl);
                }
            }
        }
    /***** END OF NOTIFY GROUP SUPPORT *****/

    /*****NOTIFY CONTRIBUTOR*****/
    if(isset($wfstates[$archive]['notify_user_flag']) && $wfstates[$archive]['notify_user_flag'] == 1)
        {
        $cntrb_arr = array();
        foreach($resource as $resourceref)
            { 
            $resdata = get_resource_data($resourceref);
            if(isset($resdata['created_by']) && is_numeric($resdata['created_by']))
                {
                $contuser = sql_query('SELECT ref, email FROM user WHERE ref = ' . $resdata['created_by'] . ';', '');
                if(count($contuser) == 0)
                    {
                    // No contributor listed
                    debug("No contributor listed for resource " . $resourceref);
                    continue;
                    }
                    
                if(!isset($cntrb_arr[$contuser[0]["ref"]]))
                    {
                    // This contributor needs to be added to the array of users to notify
                    $cntrb_arr[$contuser[0]["ref"]] = array();
                    $cntrb_arr[$contuser[0]["ref"]]["resources"] = array();
                    $cntrb_arr[$contuser[0]["ref"]]["email"] = $contuser[0]["email"];
                    }
                $cntrb_arr[$contuser[0]["ref"]]["resources"][] = $resourceref;
                }
            }
        // Construct messages for each user    
        foreach($cntrb_arr as $cntrb_user => $cntrb_detail)
            {
            debug("processing notification for contributing user " . $cntrb_user);
            // Does this user want to receive any notifications?
            get_config_option($cntrb_user,'user_pref_resource_notifications', $send_message);          
            if($send_message==false)
                {
                continue;
                }
            
            $message = $lang["userresources_status_change"] . $lang["status" . $archive];
            if(getval('more_workflow_action_' . $workflowaction,'') != '')
                {
                    $message .= "\n\n" . $lang["rse_workflow_more_notes_title"];
                    $message .= "\n\n" . getval('more_workflow_action_' . $workflowaction, '');
                }
        
            if(count($cntrb_detail["resources"]) > 200)
                {
                // Too many resources to link to directly
                $linkurl = $baseurl . "/pages/search.php?search=archive" . $archive;
                }
             else
                {
                $linkurl = $baseurl . "/pages/search.php?search=!list" . implode(":",$cntrb_detail["resources"]);
                }
            
            $maillinkurl = (($use_phpmailer) ? "<br /><br /><a href=\"$linkurl\">$linkurl</a>" : "\n\n" . $linkurl); // Convert to anchor link if using html mails
              

            // Does this user want an email or system message?
            get_config_option($cntrb_user,'email_user_notifications', $send_email);
            if($send_email && filter_var($cntrb_detail["email"], FILTER_VALIDATE_EMAIL))
                {
                debug("sending email notification to contributing user " . $cntrb_user);
                send_mail($cntrb_detail["email"],$applicationname . ": " . $lang["status" . $archive],$message . "\n\n" . $maillinkurl, $rse_workflow_from,$rse_workflow_from);
                if($wfstates[$archive]["rse_workflow_bcc_admin"]==1)
                    {
                    $bccadmin_users = get_notification_users("SYSTEM_ADMIN");
                    foreach($bccadmin_users as $bccadmin_user)
                        {
                        debug("processing bcc notification for contributing user " . $bccadmin_user["ref"]);
                        // Does this admin user want to receive any notifications?
                        get_config_option($bccadmin_user["ref"],'user_pref_resource_notifications', $send_message);          
                        if($send_message==false)
                            {
                            continue;
                            }
                        
                        // Does this admin user want an email or system message?
                        get_config_option($bccadmin_user["ref"],'email_user_notifications', $send_email); 
                        if($send_email && filter_var($bccadmin_user["email"], FILTER_VALIDATE_EMAIL)) 
                            {
                            send_mail($bccadmin_user["email"], $applicationname . ': ' . $lang['status' . $archive], $message . $maillinkurl, $rse_workflow_from,$rse_workflow_from);
                            }
                        else
                            {
                            message_add($bccadmin_user['ref'],$message,$linkurl);
                            }
                        }
                    }					
                }
            else
                {
                debug("sending system notification to contributing user " . $cntrb_user);
                message_add($cntrb_user,$message,$linkurl);
                }
            }
        }
    /*****END OF NOTIFY CONTRIBUTOR*****/    
    }


function HookRse_workflowAllRender_actions_add_collection_option($top_actions, array $options, $collection_data, $urlparams)
    {
    global $baseurl_short, $lang, $pagename, $count_result;

    // On special search !collection the actions will be added from HookRse_workflowSearchRender_search_actions_add_option
    if($pagename != "collections" || $count_result == 0)
        {
        return false;
        }

    $wf_actions_options = rse_workflow_compile_actions($urlparams);

    if(isset($GLOBALS["hook_return_value"]) && is_array($GLOBALS["hook_return_value"]))
        {
        // @see hook() for an explanation about the hook_return_value global
        $options = $GLOBALS["hook_return_value"];
        }

    return array_merge($options, $wf_actions_options);
    }

function HookRse_workflowAllRender_actions_add_option_js_case($action_selection_id)
    {
    ?>
    case 'rse_workflow_move_to_workflow':
        var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
        ModalLoad(option_url, true, true);
        break;
    <?php
    return;
    }