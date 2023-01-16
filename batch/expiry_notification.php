<?php
include(dirname(__FILE__) . "/../include/db.php");
command_line_only();
include_once(dirname(__FILE__) . "/../include/image_processing.php");

$expired_resources = ps_query('SELECT r.ref, r.field8 AS title 
                                 FROM resource r 
                                 JOIN resource_node AS rn ON r.ref = rn.resource
                                 JOIN node n ON n.ref=rn.node
                                 JOIN resource_type_field AS rtf ON n.resource_type_field = rtf.ref AND rtf.type = ?
                                WHERE r.expiry_notification_sent<>1 AND n.name <> "" AND n.name <= NOW()',
                                array("i", FIELD_TYPE_EXPIRY_DATE));

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
	ps_query("UPDATE resource SET expiry_notification_sent = 1 WHERE ref IN (" . ps_param_insert(count($refs)) . ")", ps_param_fill($refs, "i"));
	}


// Send a notification X days prior to expiry to all users who have ever downloaded the resources
if(isset($notify_on_resource_expiry_days))
    {
    echo "<br>Sending a notification {$notify_on_resource_expiry_days} day(s) prior to expiry to all users who have ever downloaded these resources.\n";
    $data = ps_query(
         'SELECT rl.`user`,
                 rte.ref AS `resource`,
                 u.email
            FROM resource_log AS rl
            JOIN (
                     SELECT r.ref
                       FROM resource AS r
                  LEFT JOIN resource_node AS rn ON r.ref = rn.resource
                  LEFT JOIN node n ON n.ref=rn.node
                  LEFT JOIN resource_type_field AS rtf ON n.resource_type_field = rtf.ref AND rtf.type = ?
                      WHERE n.`name` <> ""
                        AND DATE(n.`name`) = DATE(DATE_ADD(NOW(), INTERVAL ? DAY))
                 ) AS rte ON rte.ref = rl.resource
            JOIN user AS u ON u.ref = rl.user
           WHERE rl.`type` = ?
           GROUP BY resource, rl.user
        ORDER BY rte.ref ASC',
        array("i", FIELD_TYPE_EXPIRY_DATE, "i", (int)$notify_on_resource_expiry_days, "s", LOG_CODE_DOWNLOADED));

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