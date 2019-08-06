<?php
function HookAction_datesCronCron()
	{
	global $lang, $action_dates_restrictfield,$action_dates_deletefield, $resource_deletion_state,
           $action_dates_reallydelete, $action_dates_email_admin_days, $email_notify, $email_from,
           $applicationname, $action_dates_new_state, $action_dates_remove_from_collection,
           $action_dates_extra_config, $DATE_FIELD_TYPES;
	
	echo "action_dates: running cron tasks" . PHP_EOL;
    
    # Reset any residual userref from earlier cron tasks
    global $userref;
    $userref=0;

	$allowable_fields=sql_array("select ref as value from resource_type_field where type in (4,6,10)");
	
	# Check that this is a valid date field to use
	if(in_array($action_dates_restrictfield, $allowable_fields))
		{
        echo "action_dates: Checking field " . $action_dates_restrictfield . PHP_EOL;
        $restrict_resources=sql_query("select rd.resource, rd.value from resource_data rd left join resource r on r.ref=rd.resource where r.ref > 0 and r.access=0 and rd.resource_type_field = '$action_dates_restrictfield' and rd.value <>'' and rd.value is not null");
		$emailrefs=array();
		foreach ($restrict_resources as $resource)
			{
			$ref=$resource["resource"];
			if($action_dates_email_admin_days!="") # Set up email notification to admin of expiring resources
				{
				$action_dates_email_admin_seconds=intval($action_dates_email_admin_days)*60*60*24;	
				if ((time()>=(strtotime($resource["value"])-$action_dates_email_admin_seconds)) && (time()<=(strtotime($resource["value"])+$action_dates_email_admin_seconds)))		
					{  			
					$emailrefs[]=$ref;		
					}
				}
			
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
			}
			
		if(count($emailrefs)>0)
			{
			global $baseurl,$baseurl_short;
			# Send email as the date is within the specified number of days
			
			$subject=$lang['action_dates_email_subject'];
			$message=str_replace("%%DAYS",$action_dates_email_admin_days,$lang['action_dates_email_text']) . "\r\n";
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
						send_mail($admin_notify_email,$applicationname . ": " . $lang['action_dates_notification_subject'],$message,"","","emailproposedchanges",$templatevars);    
						}
					
					if (count($admin_notify_users)>0)
						{
						echo "Sending notification to user refs: " . implode(",",$admin_notify_users) . "\r\n";
						message_add($admin_notify_users,$notification_message,$url,0);
						}
			}
        }
    
    # Process resources whose deletion date has been reached
	if(in_array($action_dates_deletefield, $allowable_fields))
        {
        $change_archive_state = false;
        
        $fieldinfo = get_resource_type_field($action_dates_deletefield);
        
        echo "action_dates: Checking dates in field " . $fieldinfo["title"] . PHP_EOL;
        if($action_dates_reallydelete)
            {
            $delete_resources = sql_query("SELECT resource, value FROM resource_data WHERE resource > 0 AND resource_type_field = '{$action_dates_deletefield}' AND value <> '' AND value IS NOT NULL");
            }
        else
            {
            if(!isset($resource_deletion_state))
                {
                $resource_deletion_state = 3;
                }

            // The new state should be by default 3 - Deleted state
            // If this is different, it means we only want to move 
            // resources to that state
            if($action_dates_new_state != $resource_deletion_state)
                {
                $resource_deletion_state = $action_dates_new_state;
                $change_archive_state    = true;
                }

            $delete_resources = sql_query("SELECT rd.resource, rd.value FROM resource r LEFT JOIN resource_data rd ON r.ref = rd.resource AND r.archive != '{$resource_deletion_state}' WHERE r.ref > 0 AND rd.resource_type_field = '{$action_dates_deletefield}' AND value <> '' AND rd.value IS NOT NULL");
            }

        foreach($delete_resources as $resource)
            {
            $ref = $resource['resource'];

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
                    delete_resource($ref);
                    }
                else
                    {
                    sql_query("UPDATE resource SET archive = '{$resource_deletion_state}' WHERE ref = '{$ref}'");
                    
                    if($action_dates_remove_from_collection)
                        {
                        // Remove the resource from any collections
                        sql_query("delete from collection_resource where resource='$ref'");
                        }
                
                    }


                resource_log($ref,'x','',$lang['action_dates_delete_logtext']);
                }
            }
        }
        
        
    // Perform additional actions based on fields
    foreach($action_dates_extra_config as $action_dates_extra_config)
        {
        $datefield = get_resource_type_field($action_dates_extra_config["field"]);
        $field = $datefield["ref"];
        $newstatus = $action_dates_extra_config["status"];
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
