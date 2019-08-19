<?php

// enumerated types of message.  Note the base two offset for binary combination.
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN",1);
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL",2);
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_RESERVED_1",4);
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_RESERVED_2",8);
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_RESERVED_3",16);

DEFINE ("MESSAGE_DEFAULT_TTL_SECONDS",60 * 60 * 24 * 7);		// 7 days

// ------------------------------------------------------------------------------------------------------------------------

// gets messages for a given user (return true if there are messages, if not false)
// note that messages are passed by reference.
function message_get(&$messages,$user,$get_all=false,$sort_desc=false)
	{
	$messages=sql_query("SELECT user_message.ref, user.username AS owner, user_message.seen, message.created, message.expires, message.message, message.url " .
		"FROM `user_message`
		INNER JOIN `message` ON user_message.message=message.ref " .
		"LEFT OUTER JOIN `user` ON message.owner=user.ref " .
		"WHERE user_message.user='{$user}'" .
		($get_all ? " " : " AND message.expires > NOW()") .
		($get_all ? " " : " AND user_message.seen='0'") .
		" ORDER BY user_message.ref " . ($sort_desc ? "DESC" : "ASC"));
	return(count($messages) > 0);
	}

// ------------------------------------------------------------------------------------------------------------------------

// add a message.
function message_add($users,$text,$url="",$owner=null,$notification_type=MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,$ttl_seconds=MESSAGE_DEFAULT_TTL_SECONDS, $related_activity=0, $related_ref=0)
	{
	global $userref,$applicationname,$lang, $baseurl, $baseurl_short;
	
	if(!is_int($notification_type))
		{
		$notification_type=intval($notification_type); // make sure this in an integer
		}
	
	$orig_text=$text;
	$text = escape_check($text);
	$url = escape_check($url);

	if (!is_array($users))
		{
		$users=array($users);
		}

	if(is_null($owner))
		{
		$owner=$userref;
		}

	if (is_null($owner))
		{
		$owner_escaped = 'NULL';
		}
	else
		{
		$owner_escaped = "'" . escape_check($owner) . "'";
		}

	sql_query("INSERT INTO `message` (`owner`, `created`, `expires`, `message`, `url`, `related_activity`, `related_ref`) VALUES ({$owner_escaped}, NOW(), DATE_ADD(NOW(), INTERVAL {$ttl_seconds} SECOND), '{$text}', '{$url}', '{$related_activity}', '{$related_ref}' )");
	$message_ref = sql_insert_id();

	foreach($users as $user)
		{
		sql_query("INSERT INTO `user_message` (`user`, `message`) VALUES ($user,$message_ref)");
		
		// send an email if the user has notifications and emails setting and the message hasn't already been sent via email
		if(~$notification_type & MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL)
			{
			get_config_option($user,'email_and_user_notifications', $notifications_always_email);
			if($notifications_always_email)
				{
				$email_to=sql_value("select email value from user where ref={$user}","");
				if($email_to!=='')
					{
                    if(strpos($url,$baseurl) === false)
                        {
                        // If a relative link is provided make sure we add the full URL when emailing
                        $url = $baseurl . $url;
                        }
					$message_text=nl2br($orig_text);
					send_mail($email_to,$applicationname . ": " . $lang['notification_email_subject'],$message_text . "<br/><br/><a href='" . $url . "' >" . $url . "</a>");
					}
				}
			}
		}

	}

// ------------------------------------------------------------------------------------------------------------------------

// remove a message from message table and associated user_messages
function message_remove($message)
	{
    $message = escape_check($message);

	sql_query("DELETE FROM user_message WHERE message='{$message}'");
	sql_query("DELETE FROM message WHERE ref='{$message}'");	
	}

// ------------------------------------------------------------------------------------------------------------------------

function message_seen($message,$seen_type=MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN)
	{
    $seen_type = escape_check($seen_type);
    $message   = escape_check($message);

	sql_query("UPDATE `user_message` SET seen=seen | {$seen_type} WHERE `ref`='{$message}'");
	}
    
// ------------------------------------------------------------------------------------------------------------------------

function message_unseen($message)
	{
    $message = escape_check($message);

	sql_query("UPDATE `user_message` SET seen='0' WHERE `ref`='{$message}'");
	}

// ------------------------------------------------------------------------------------------------------------------------

// flags all non-read messages as read for given user and seen type
function message_seen_all($user,$seen_type=MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN)
	{
	$messages = array();
	if (message_get($messages,$user,true))
		{
		foreach($messages as $message)
			{             
			message_seen($message['ref']);
			}
		}
	}

// ------------------------------------------------------------------------------------------------------------------------

// remove all messages from message and user_message tables that have expired (regardless of read).  This will be called
// from a cron job.
function message_purge()
	{
	sql_query("DELETE FROM user_message WHERE message IN (SELECT ref FROM message where expires < NOW())");
	sql_query("DELETE FROM message where expires < NOW()");
	}

// ------------------------------------------------------------------------------------------------------------------------

// Send a summary of all unread notifications as an email
// from the standard cron_copy_hitcount

function message_send_unread_emails()
	{
	global $lang, $applicationname,$actions_enable,$baseurl,$list_search_results_title_trim;
    global $user_pref_daily_digest,$applicationname,$actions_on, $inactive_message_auto_digest_period;
    
    $lastrun = get_sysvar('daily_digest', '1970-01-01');
    
    # Don't run if already run in last 24 hours.
    if (time()-strtotime($lastrun) < 24*60*60)
        {
        echo " - Skipping message_send_unread_emails (daily_digest) - last run: " . $lastrun . "<br />\n";
        return false;
        }
    
	$sendall = array();
    
	// Get all the users who have chosen to receive the digest email (or the ones that have opted out if set globally)
	if($user_pref_daily_digest)
		{
		$allusers=get_users("","","u.username","",-1,1);		
		$nodigestusers = get_config_option_users('user_pref_daily_digest',0);
		$digestusers=array_diff(array_column($allusers,"ref"),$nodigestusers);
		}
	else
		{
		$digestusers=get_config_option_users('user_pref_daily_digest',1);
		}
    
    if($inactive_message_auto_digest_period > 0 && is_numeric($inactive_message_auto_digest_period))
        {
        // Add any users who have not logged on to the array
        $allusers = get_users(0,"","u.ref",false,-1,1,false,"u.ref, u.username, u.last_active");
        foreach($allusers as $user)
            {
            if(!in_array($user["ref"],$digestusers) && strtotime($user["last_active"]) < date(time() - $inactive_message_auto_digest_period *  60 * 60 *24))
                {
                debug("message_send_unread_emails: Processing unread messages for inactive user: " . $user["username"]);
                $digestusers[] = $user["ref"];
                $sendall[] = $user["ref"];
                }
            }
        }
        
	# Get all unread notifications created since last run, or all mesages sent to inactive users. 
	$unreadmessages=sql_query("SELECT u.ref AS userref, u.email, m.ref AS messageref, m.message, m.created, m.url FROM user_message um JOIN user u ON u.ref=um.user JOIN message m ON m.ref=um.message WHERE um.seen=0 AND u.ref IN ('" . implode("','",$digestusers) . "') AND u.email<>'' AND (m.created>'" . $lastrun . "'" . (count($sendall) > 0 ? " OR u.ref IN ('" . implode("','",$sendall) . "')" : "") . ") ORDER BY m.created DESC");
	
    $inactive_message_auto_digest_period_saved = $inactive_message_auto_digest_period;
	foreach($digestusers as $digestuser)
		{
        // Reset config before setting up user so that any user groups processed later are not affected by the override
        $inactive_message_auto_digest_period = $inactive_message_auto_digest_period_saved;
        $messageuser=get_user($digestuser);
        if(!$messageuser)
            {
            // Invalid user
            continue;
            }
		setup_user($messageuser);
        get_config_option($digestuser,'user_pref_inactive_digest', $user_auto_digest_option);
        
        if($inactive_message_auto_digest_period == 0 || !$user_auto_digest_option) // This may be set differently as a group configuration overerride or disabled by user
            {
            debug("Skipping email digest for user ref " . $digestuser . " as user or group preference disabled");
            continue;
            }
        
        $usermail = $messageuser["email"];
        if(!filter_var($usermail, FILTER_VALIDATE_EMAIL))
            {
            debug("Skipping email digest for user ref " . $digestuser . " due to invalid email:  " . $usermail);
            continue;
            }
         
		$messageflag=false;
		$actionflag=false;
		// Set up an array of message to delete for this user if they have chosen to purge the messages
		$messagerefs=array();
		
		// Start the new email
        if(in_array($digestuser,$sendall))
            {
            $message = $lang['email_auto_digest_inactive'] . "<br /><br />";
            }
        else
            {
            $message = $lang['email_daily_digest_text'] . "<br /><br />";
            }
		$message .= "<style>.InfoTable td {padding:5px; margin: 0px;border: 1px solid #000;}</style><table class='InfoTable'>";
		$message .= "<tr><th>" . $lang["columnheader-date_and_time"] . "</th><th>" . $lang["message"] . "</th><th></th></tr>";
		
		foreach($unreadmessages as $unreadmessage)
			{
			if($unreadmessage["userref"] == $digestuser)
				{
				// Message applies to this user
				$messageflag=true;
				$usermail = $unreadmessage["email"];
				$message .= "<tr><td>" . nicedate($unreadmessage["created"], true) . "</td><td>" . $unreadmessage["message"] . "</td><td><a href='" . $unreadmessage["url"] . "'>" . $lang["link"] . "</a></td></tr>";
				$messagerefs[]=$unreadmessage["messageref"];
				}
			}
		if($actions_on)
			{
			//debug("Checking actions for user " . $unreadmessage["userref"]);
            if(!$actions_on){break;}
			$user_actions = get_user_actions(false);
			if (count($user_actions) > 0)		
				{
				$actionflag=true;
				debug("Adding actions to message for user " . $usermail);
				if($messageflag)
					{
					$message .= "</table><br /><br />";
					}
				$message .= $lang['email_daily_digest_actions'] . "<br /><br />". $lang["actions_introtext"] . "<br />";
				$message .= "<style>.InfoTable td {padding:5px; margin: 0px;border: 1px solid #000;}</style><table class='InfoTable'>";
				$message .= "<tr><th>" . $lang["date"] . "</th>";
				$message .= "<th>" . $lang["property-reference"] . "</th>";
				$message .= "<th>" . $lang["description"] . "</th>";
				$message .= "<th>" . $lang["type"] . "</th></tr>";
				
				
				foreach($user_actions as $user_action)
					{
					$actionlinks=hook("actioneditlink",'',array($user_action));
					if($actionlinks)
					  {
					  $actioneditlink=$actionlinks["editlink"];
					  $actionviewlink=$actionlinks["viewlink"];
					  }
					else
					  {
					  $actioneditlink = '';
					  $actionviewlink = '';  
					  }
					
					if($user_action["type"]=="resourcereview")
					  {
					  $actioneditlink = $baseurl . "/pages/edit.php";
					  $actionviewlink = $baseurl . "/pages/view.php";
					  }
					elseif($user_action["type"]=="resourcerequest")
					  {
					  $actioneditlink = $baseurl . "/pages/edit.php";
					  }
					elseif($user_action["type"]=="userrequest")
					  {
					  $actioneditlink = $baseurl . "/pages/team/team_user_edit.php";
					  } 
					
					$linkparams["ref"] = $user_action["ref"];                            
					$editlink=($actioneditlink=='')?'':generateURL($actioneditlink,$linkparams);
					$viewlink=($actionviewlink=='')?'':generateURL($actionviewlink,$linkparams);
					$message .= "<tr>";
					$message .= "<td>" . nicedate($user_action["date"],true) . "</td>";
					$message .= "<td><a href=\"" . $editlink . "\" >" . $user_action["ref"] . "</a></td>";
					$message .= "<td>" . tidy_trim(TidyList($user_action["description"]),$list_search_results_title_trim) . "</td>";
					$message .= "<td>" . $lang["actions_type_" . $user_action["type"]] . "</td>";
					$message .= "<td><div class=\"ListTools\">";
					if($editlink!=""){$message .= "<a href=\"" . $editlink . "\" >&nbsp;&nbsp;" . $lang["action-edit"] . "</a>";}
					if($viewlink!=""){$message .= "<a href=\"" . $viewlink . "\" >&nbsp;&nbsp;" . $lang["view"] . "</a>";}
					$message .= "</div>";
					$message .= "</td></tr>";
					} // End of each $user_actions loop
				}
			}
			
		// Send the email			
		debug("Sending summary to user ref " . $digestuser . ", email " . $usermail);
		$message .= "</table>";
        
        $userprefurl = $baseurl . "/pages/user/user_preferences.php#UserPreferenceEmailSection";
        $message .= "<br /><br />" . $lang["email_digest_disable"] . "<br /><a href='" . $userprefurl  . "'>" . $userprefurl . "</a>";
        
		if($messageflag || $actionflag)
			{
			// Send mail
			send_mail($usermail,$applicationname . ": " . $lang["email_daily_digest_subject"],$message); 
			}

		get_config_option($digestuser,'user_pref_daily_digest_mark_read', $mark_read);
		if($mark_read)
			{
			sql_query("update user_message set seen='" . MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL . "' where message in ('" . implode("','",$messagerefs) . "') and user = '" . $digestuser . "'");
			}
		}

    set_sysvar("daily_digest",date("Y-m-d H:i:s")); 
	}



// ------------------------------------------------------------------------------------------------------------------------
// Remove all messages related to a certain activity (e.g. resource request or resource submission) matching the given ref(s)
function message_remove_related($remote_activity=0,$remote_refs=array())
	{
	if($remote_activity==0 || $remote_refs==0 || (is_array($remote_refs) && count($remote_refs)==0) ){return false;}
	if(!is_array($remote_refs)){$remote_refs=array($remote_refs);}
    $relatedmessages = sql_array("select ref value from message where related_activity='$remote_activity' and related_ref in (" . implode(',',$remote_refs) . ");","");
    if(count($relatedmessages)>0)
        {            
        sql_query("DELETE FROM message WHERE ref in (" . implode(',',$relatedmessages) . ");");
        sql_query("DELETE FROM user_message WHERE message in (" . implode(',',$relatedmessages) . ");");
        }
	}

// Remove an instance of a message from user_message table 
function message_user_remove($usermessage)
    {
    global $userref;

    $userref     = escape_check($userref);
    $usermessage = escape_check($usermessage);

    sql_query("DELETE FROM user_message WHERE user = {$userref} AND ref = '{$usermessage}'");
    }
