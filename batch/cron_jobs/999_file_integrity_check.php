<?php
include_once __DIR__ . "/../../include/db.php";


if (is_process_lock("file_integrity_check")) 
    {
    echo " - File integrity process lock is in place.Skipping.\n";
    return;
    }

set_process_lock("file_integrity_check");

// Get resources and checksums to validate
$resources      = sql_query("SELECT ref, archive, file_extension, file_checksum, last_verified, integrity_fail FROM resource WHERE ref>0 AND (datediff(now(),last_verified)>1 OR last_verified IS NULL) " . ((count($file_integrity_ignore_states) > 0) ? " AND archive NOT IN ('" . implode("','",$file_integrity_ignore_states) . "')" : "") . " ORDER BY last_verified ASC");
$checkfailed    = array();
$validtime      = true;

if(count($resources) == 0)
    {
    echo " - No resources require integrity checks" . PHP_EOL;
    }

foreach($resources as $resource)
    {
    // Check we are in a valid time period
    $curhour = date('H');
    if($file_integrity_verify_window[0] <= $file_integrity_verify_window[1])
        {
        // Second time is later than first or times are the same (off). Ensure time is not before the first or later than the second
        if($curhour < $file_integrity_verify_window[0] || $curhour >= $file_integrity_verify_window[1])
            {
            $validtime = false;
            }
        }
    else
        {
        // First time is later than second (running through midnight). Ensure time is not before the first and after the second
        if($curhour < $file_integrity_verify_window[0] && $curhour >= $file_integrity_verify_window[1])
            {
            $validtime = false;
            }
        }

    if(!$validtime)
        {
        if('cli' == PHP_SAPI)
            {
            echo " - Outside of valid time period. Set times are between " . $file_integrity_verify_window[0] . ":00 and " . $file_integrity_verify_window[1] . ":00 hours. Current hour: " . $curhour . ":00" . $LINE_END;
            }
        break;
        }

    $path=get_resource_path($resource['ref'],true,"",false,$resource['file_extension']);
    if(!hook('file_integrity_check','',array($resource)))
        {             
        if (is_readable($path))
            {
            if($file_checksums && !$file_checksums_50k)
                {
                // Need full file checksums to check integrity
                $checksum = get_checksum($path);
                if(trim($resource['file_checksum']) != '' && $checksum == $resource['file_checksum'])
                    {
                    sql_query("UPDATE resource SET integrity_fail=0, last_verified=now() WHERE ref='" . $resource['ref'] . "'");
                    }
                elseif(!in_array($resource["ref"],$file_integrity_ignore_states))
                    {
                    if('cli' == PHP_SAPI)
                        {
                        echo " - Checksum mismatch for resource " . $resource['ref'] . ".  Current: " . $resource['file_checksum'] . ". Stored: " . $checksum . $LINE_END;
                        }
                    // Checksum mismatch - add to array of failed files
                    $checkfailed[] = $resource["ref"];
                    }
                }
            else
                {
                // No checksum functionality but file is present - just ensure file updated                
                sql_query("UPDATE resource SET integrity_fail = 0, last_verified=now() WHERE ref='" . $resource['ref'] . "'");
                }
            }
        else
            {
            // File is missing or not readable, record this and update the resource table
            if(!in_array($resource['archive'],$file_integrity_ignore_states))
                {
                if('cli' == PHP_SAPI)
                    {
                    echo " - Missing or unreadable resource file for resource " . $resource['ref'] . ".  Expected location: " . $path . $LINE_END;
                    }
                $checkfailed[] = $resource['ref'];
                }
            }
        }
    }
    
if(count($checkfailed) > 0)
    {
    sql_query("UPDATE resource SET integrity_fail = 1 WHERE ref in('" . implode("','",$checkfailed) . "')");

    $last_integrity_check_notify = get_sysvar('last_integrity_check_notify', '1970-01-01');

    # Skip if run within last 24 hours
    if (time()-strtotime($last_integrity_check_notify) < 24*60*60)
        {
        if('cli' == PHP_SAPI)
            {
            echo " - Skipping sending of integrity failure notifications - last sent: " . $last_integrity_check_notify . $LINE_END;
            }
        }
    else
        {
        if('cli' == PHP_SAPI)
            {
            echo " - Sending summary messages to administrators" . $LINE_END;
            }
            
        # Send notifications if not sent in last 24 hours
        $subject = $applicationname . ": " . $lang['file_integrity_summary'];
        $message = $lang['file_integrity_summary_failed'];
        $notification_message = $message; 

        $message   .= "\r\n" . $baseurl . "pages/search.php?search=!integrityfail"; 
        $url        = $baseurl_short . "pages/search.php?search=!integrityfail";
        $templatevars['message']    = $message;
        $templatevars['url']        = $baseurl . "/pages/search.php?search=!integrityfail";
        $admin_notify_emails        = array();
        $admin_notify_users         = array();
        $notify_users               = get_notification_users("SYSTEM_ADMIN");
        foreach($notify_users as $notify_user)
            {
            get_config_option($notify_user['ref'],'user_pref_system_management_notifications', $send_message);		  
            if($send_message==false)
                {
                $continue;
                }
            get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
            if($send_email && $notify_user["email"]!="")
                {
                $admin_notify_emails[] = $notify_user['email'];				
                }        
            else
                {
                $admin_notify_users[]=$notify_user["ref"];
                }
            }

        foreach($admin_notify_emails as $admin_notify_email)
            {
            send_mail($admin_notify_email,$applicationname . ": " . $lang['file_integrity_summary'],$message,"","","file_integrity_fail_email",$templatevars);    
            }
                
        if (count($admin_notify_users)>0)
            {
            message_add($admin_notify_users,$notification_message,$url,0);
            }
        set_sysvar("last_integrity_check_notify",date("Y-m-d H:i:s"));
        }
    }

clear_process_lock("file_integrity_check");
