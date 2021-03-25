<?php

function HookAction_datesAllInitialise()
    {
    global $action_dates_fieldvars;
    config_register_core_fieldvars("Action dates plugin",$action_dates_fieldvars);
    }

function HookAction_datesCronCron()
	{
	global $lang, $action_dates_restrictfield,$action_dates_deletefield, $resource_deletion_state,
           $action_dates_reallydelete, $action_dates_email_admin_days, $email_notify, $email_from,
           $applicationname, $action_dates_new_state, $action_dates_remove_from_collection,
           $action_dates_extra_config, $DATE_FIELD_TYPES, $action_dates_email_for_state, $action_dates_email_for_restrict;

    global $action_dates_eligible_states;
	
	echo "action_dates: running cron tasks" . PHP_EOL;
    
    # Reset any residual userref from earlier cron tasks
    global $userref;
    $userref=0;
            
    $eligible_states_list = implode(",",$action_dates_eligible_states);

	$allowable_fields=sql_array("select ref as value from resource_type_field where type in (4,6,10)", "schema");
    
    $email_state_refs = array();
    $email_restrict_refs=array();

    # Process resource access restriction if a restriction date has been configured
    # The restriction date will be processed if it is a valid date field
	if(in_array($action_dates_restrictfield, $allowable_fields))
		{
        echo "action_dates: Checking field " . $action_dates_restrictfield . PHP_EOL;
        $restrict_resources=sql_query("SELECT rd.resource, rd.value FROM resource_data rd LEFT JOIN resource r ON r.ref=rd.resource "
            . ($eligible_states_list == "" ? "" : "AND r.archive IN ({$eligible_states_list})")    
            . " WHERE r.ref > 0 and r.access=0 and rd.resource_type_field = '$action_dates_restrictfield' and rd.value <>'' and rd.value is not null");
		foreach ($restrict_resources as $resource)
			{
			$ref=$resource["resource"];
			
			if (time()>=strtotime($resource["value"]))		
				{		
				# Restrict access to the resource as date has been reached
				$existing_access=sql_value("select access as value from resource where ref='$ref'","");
				if($existing_access==0) # Only apply to resources that are currently open
					{
					echo " - restricting resource " . $ref ."\r\n";
					sql_query("update resource set access=1 where ref='$ref'");
					resource_log($ref,'a','',$lang['action_dates_restrict_logtext'],$existing_access,1);		
					}
				}
            else
                {
                if($action_dates_email_admin_days!="") # Set up email notification to admin of expiring resources
                    {
                    $action_dates_email_admin_seconds=intval($action_dates_email_admin_days)*60*60*24;	
                    if ((time()>=(strtotime($resource["value"])-$action_dates_email_admin_seconds)) && (time()<=(strtotime($resource["value"])+$action_dates_email_admin_seconds)))		
                        {  			
                        $email_restrict_refs[]=$ref;		
                        }
                    }
                }
			}
        }
    
    # Process resource deletion or statechange if designated date has been configured
    # The designated date will be processed if it is a valid date field
	if(in_array($action_dates_deletefield, $allowable_fields))
        {
        $change_archive_state = false;

        $fieldinfo = get_resource_type_field($action_dates_deletefield);
        
        echo "action_dates: Checking dates in field " . $fieldinfo["title"] . PHP_EOL;
        if($action_dates_reallydelete)
            {
            # FULL DELETION - Build candidate list of resources which have the deletion date field populated
            $candidate_resources = sql_query("SELECT rd.resource, rd.value FROM resource r LEFT JOIN resource_data rd ON r.ref = rd.resource " 
                                    . ($eligible_states_list == "" ? "" : " AND r.archive IN ({$eligible_states_list})")    
                                    . " WHERE r.ref > 0 AND rd.resource_type_field = '{$action_dates_deletefield}' AND value <> '' AND rd.value IS NOT NULL");
            }
        else
            {
            # NOT FULL DELETION - If not already configured, establish the default resource deletion state
            if(!isset($resource_deletion_state))
                {
                $resource_deletion_state = 3;
                }
            # NOT FULL DELETION - Build candidate list of resources which have the deletion date field populated
            #                     and which are neither in the resource deletion state nor in the action dates new state
            $candidate_resources = sql_query("SELECT rd.resource, rd.value FROM resource r LEFT JOIN resource_data rd ON r.ref = rd.resource " 
                                    . ($eligible_states_list == "" ? "" : " AND r.archive IN ({$eligible_states_list})")    
                                    . " AND r.archive NOT IN ({$resource_deletion_state},{$action_dates_new_state}) " 
                                    . " WHERE r.ref > 0 AND rd.resource_type_field = '{$action_dates_deletefield}' AND value <> '' AND rd.value IS NOT NULL");

            # NOT FULL DELETION - Resolve the target archive state to which candidate resources are to be moved
            # If the new state differs from the default resource deletion state, it means we only want to move resources to that state
            if($action_dates_new_state != $resource_deletion_state)
                {
                $resource_deletion_state = $action_dates_new_state;
                $change_archive_state    = true;
                }
            # The resource deletion state now represents the target archive state
            }

        # Process the list of candidates    
        foreach($candidate_resources as $resource)
            {
            $ref = $resource['resource'];
            
            # Candidate deletion date reached or passed 
            if (time() >= strtotime($resource['value']))
                {
                if(!$change_archive_state)
                    {
                    // Delete the resource as date has been reached
                    echo " - Deleting resource {$ref}\r\n";
                    }
                else
                    {
                    echo " - Moving resource with ID {$ref} to archive state '{$resource_deletion_state}'\r\n";
                    }
                
                if ($action_dates_reallydelete)
                    {
                    # FULL DELETION
                    delete_resource($ref);
                    }
                else
                    {
                    # NOT FULL DELETION - Update resources to the target archive state
                    sql_query("UPDATE resource SET archive = '{$resource_deletion_state}' WHERE ref = '{$ref}'");
                    
                    if($action_dates_remove_from_collection)
                        {
                        // Remove the resource from any collections
                        sql_query("delete from collection_resource where resource='$ref'");
                        }
                
                    }

                resource_log($ref,'x','',$lang['action_dates_delete_logtext']);
                }
            else
                {
                if($action_dates_email_admin_days!="") # Set up email notification to admin of resources changing state
                    {
                    $action_dates_email_admin_seconds=intval($action_dates_email_admin_days)*60*60*24;	
                    if ((time()>=(strtotime($resource["value"])-$action_dates_email_admin_seconds)) && (time()<=(strtotime($resource["value"])+$action_dates_email_admin_seconds)))		
                        {  			
                        $email_state_refs[]=$ref;		
                        }
                    }
                }
            }
        }

    // Only allow email for actions configured.
    if ($action_dates_restrictfield == "")
        {
        $action_dates_email_for_restrict = "0";
        }
    if ($action_dates_deletefield == "")
        {
        $action_dates_email_for_state = "0";
        }
    
    $emailrefs = array();
    if ($action_dates_email_for_state == "1" && $action_dates_email_for_restrict == "1")
        {
        $emailrefs = array_merge($email_state_refs,$email_restrict_refs);
        $emailrefs = array_unique($emailrefs);
        $subject = $lang['action_dates_email_subject'];
        $message=str_replace("%%DAYS",$action_dates_email_admin_days,$lang['action_dates_email_text']) . "\r\n";
        }
    elseif ($action_dates_email_for_state == "1" && $action_dates_email_for_restrict == "0")
        {
        $emailrefs = $email_state_refs;
        $subject = $lang['action_dates_email_subject_state'];
        $message=str_replace("%%DAYS",$action_dates_email_admin_days,$lang['action_dates_email_text_state']) . "\r\n";
        }
    elseif ($action_dates_email_for_state == "0" && $action_dates_email_for_restrict == "1")
        {
        $emailrefs = $email_restrict_refs;
        $subject = $lang['action_dates_email_subject_restrict'];
        $message=str_replace("%%DAYS",$action_dates_email_admin_days,$lang['action_dates_email_text_restrict']) . "\r\n";
        }

    if(count($emailrefs)>0)
        {
        global $baseurl,$baseurl_short;
        # Send email as the date is within the specified number of days
        $notification_message = $message; 
        $message.= $baseurl . "?r=" . implode("\r\n" . $baseurl . "?r=",$emailrefs) . "\r\n";
        $url = $baseurl_short . "pages/search.php?search=!list" . implode(":",$emailrefs);
        $templatevars['message']=$message;
        $admin_notify_emails = array();
        $admin_notify_users = array();
        $notify_users=get_notification_users("RESOURCE_ADMIN");
        foreach($notify_users as $notify_user)
            {
            get_config_option($notify_user['ref'],'user_pref_resource_notifications', $send_message);		  
            if($send_message==false){$continue;}		
            get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
            if($send_email && $notify_user["email"]!="")
                {
                echo "Sending email to " . $notify_user["email"] . "\r\n";
                $admin_notify_emails[] = $notify_user['email'];				
                }        
            else
                {
                $admin_notify_users[]=$notify_user["ref"];
                }
            }
        foreach($admin_notify_emails as $admin_notify_email)
                    {
                    send_mail($admin_notify_email,$applicationname . ": " . $subject,$message,"","","emailproposedchanges",$templatevars);    
                    }
                
                if (count($admin_notify_users)>0)
                    {
                    echo "Sending notification to user refs: " . implode(",",$admin_notify_users) . "\r\n";
                    message_add($admin_notify_users,$notification_message,$url,0);
                    }
        }
        
    // Perform additional actions based on fields
    foreach($action_dates_extra_config as $action_dates_extra_config_setting)
        {
        $datefield = get_resource_type_field($action_dates_extra_config_setting["field"]);
        $field = $datefield["ref"];
        $newstatus = $action_dates_extra_config_setting["status"];
        if(in_array($datefield['type'],$DATE_FIELD_TYPES))
            {
            echo "action_dates: Checking dates for field " . $datefield["title"] . PHP_EOL;
            $additional_resources=sql_query("SELECT rd.resource, rd.value FROM resource_data rd LEFT JOIN resource r ON r.ref=rd.resource WHERE r.ref > 0 AND rd.resource_type_field = '$field' AND rd.value <>'' AND rd.value IS NOT null AND r.archive<>'$resource_deletion_state' AND r.archive<>'$newstatus'");
            
            foreach ($additional_resources as $resource)
                {
                $ref=$resource["resource"];
			
                if (time()>=strtotime($resource["value"]))		
                    {		
                    echo "action_dates: moving resource " . $ref . " to  archive state " . $lang["status" . $newstatus] . "\r\n";
                    update_archive_status($ref, $newstatus);		
                    }
                }
            }
        }
    }

// This is required if cron task is run via pages/tools/cron_copy_hitcount.php
function HookAction_datesCron_copy_hitcountCron()
	{
    HookAction_datesCronCron();
    }
