<?php
include(dirname(__FILE__) . "/../include/db.php");
include_once(dirname(__FILE__) . "/../include/image_processing.php");

$expired_resources = sql_query('select r.ref,r.field8 as title from resource r join resource_data rd on r.ref=rd.resource join resource_type_field rtf on rd.resource_type_field=rtf.ref and rtf.type = ' . FIELD_TYPE_EXPIRY_DATE . ' where r.expiry_notification_sent<>1 and rd.value<>"" and rd.value<=now()');
if (count($expired_resources)>0)
	{
	# Send notifications
	$refs=array();
	$body=$lang["resourceexpirymail"] . "\n";
	foreach ($expired_resources as $resource)
		{
		$refs[]=$resource["ref"];
		echo "<br>Sending expiry notification for: " . $resource["ref"] . " - " . $resource["title"];
		
		$body.="\n" . $resource["ref"] . " - " . $resource["title"];
		$body.="\n" . $baseurl . "/?r=" . $resource["ref"] . "\n";
		}
	
	$url = $baseurl . "/pages/search.php?search=!list" . implode(":",$refs);
	
	$admin_notify_emails = array();
	$admin_notify_users = array();
	if (isset($expiry_notification_mail))
		{
		$admin_notify_emails[] = $expiry_notification_mail;	
		}
	else
		{
		$notify_users=get_notification_users("RESOURCE_ADMIN");
		foreach($notify_users as $notify_user)
			{
			get_config_option($notify_user['ref'],'user_pref_resource_notifications', $send_message);		  
			if(!$send_message)
                {
                continue;
                }

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
		}
		
	foreach($admin_notify_emails as $admin_notify_email)
			{
			# Send mail
			send_mail($admin_notify_email,$lang["resourceexpiry"],$body);
			}
			
	if (count($admin_notify_users)>0)
		{
		echo "Sending notification to user refs: " . implode(",",$admin_notify_users) . "\r\n";
		message_add($admin_notify_users,$lang["resourceexpirymail"],$url,0);
		}	

	# Update notification flag so an expiry is not sent again until the expiry field(s) is edited.
	sql_query("update resource set expiry_notification_sent=1 where ref in (" . join(",",$refs) . ")");
	}


// Send a notification X days prior to expiry to all users who have ever downloaded the resources
if(isset($notify_on_resource_expiry_days))
    {
    echo "<br>Sending a notification {$notify_on_resource_expiry_days} day(s) prior to expiry to all users who have ever downloaded these resources.";
    $data = sql_query(sprintf(
         'SELECT rl.`user`,
                 rte.ref AS `resource`,
                 u.email
            FROM resource_log AS rl
            JOIN (
                     SELECT r.ref
                       FROM resource AS r
                       JOIN resource_data AS rd ON r.ref = rd.resource
                       JOIN resource_type_field AS rtf ON rd.resource_type_field = rtf.ref AND rtf.type = %s
                      WHERE rd.`value` <> ""
                        AND DATE(rd.`value`) = DATE(DATE_ADD(NOW(), INTERVAL %s DAY))
                 ) AS rte ON rte.ref = rl.resource
            JOIN user AS u ON u.ref = rl.user
           WHERE rl.`type` = "%s"
        ORDER BY rte.ref ASC',

        FIELD_TYPE_EXPIRY_DATE,
        (int) $notify_on_resource_expiry_days,
        LOG_CODE_DOWNLOADED
    ));

    $msg = str_replace('%X', $notify_on_resource_expiry_days, $lang['resource_expiry_x_days']);

    $matched_resources = array_unique(array_column($data, 'resource'));
    foreach($matched_resources as $resource_ref)
        {
        $url = "{$baseurl}/?r={$resource_ref}";
        $email_body = "{$msg}<br><br><a href=\"{$url}\">{$url}</a>";
        $admin_notify_users = [];

        $users_who_dld = array_filter($data, function($v) use ($resource_ref) { return $v['resource'] === $resource_ref; });
        foreach($users_who_dld as $dld_record)
            {
            get_config_option($dld_record['user'], 'email_user_notifications', $send_email);
            if($send_email && $dld_record['email'] !== '')
                {
                send_mail($dld_record['email'], "{$applicationname}: {$lang['resourceexpiry']}", $email_body);
                }
            else
                {
                $admin_notify_users[] = $dld_record['user'];
                }
            }

        message_add($admin_notify_users, $msg, $url);
        }
    }