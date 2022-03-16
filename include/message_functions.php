<?php

/**
 * Gets messages for a given user (returns true if there are messages, false if not)
 * Note that messages are passed by reference.
 *
 * @param  array $messages  Array that will be populated by messages. Passed by reference
 * @param  int $user        User ID
 * @param  bool $get_all    Retrieve all messages? Setting to TRUE will include all seen and expired messages
 * @param  bool $sort       Sort by message ID in ascending or descending order
 * @param  string $order_by Order of messages returned
 * @return bool             Flag to indicate if any messages exist
 */
function message_get(&$messages,$user,$get_all=false,$sort="ASC",$order_by="ref")
	{
	switch ($order_by)
        {
        case "ref":
            $sql_order_by = "user_message.ref";
            break;
        case "created":
            $sql_order_by = "message.created";
            break;
        case "from":
            $sql_order_by = "owner";
            break;
        case "fullname":
            $sql_order_by = "user.fullname";
            break;
        case "message":
            $sql_order_by = "message.message";
            break;
        case "expires":
            $sql_order_by = "message.expires";
            break;
        case "seen":
            $sql_order_by = "user_message.seen";
            break;  
        }

    // Check sort value is valid
    if (!in_array(strtolower($sort), array("asc", "desc")))
    {
    $sort = "ASC";
    }

    $messages=ps_query("SELECT user_message.ref, user.username AS owner, user_message.seen, message.created, message.expires, message.message, message.url, message.owner as ownerid, message.type " .
		"FROM `user_message`
		INNER JOIN `message` ON user_message.message=message.ref " .
		"LEFT OUTER JOIN `user` ON message.owner=user.ref " .
		"WHERE user_message.user = ?" .
		($get_all ? " " : " AND message.expires > NOW()") .
		($get_all ? " " : " AND user_message.seen='0'") .
		" ORDER BY " . $sql_order_by . " " . $sort, array("i",$user));
	return(count($messages) > 0);
	}

/**
 * Add a new resourcespace system message
 *
 * @param  mixed $users             User ID, or array of user IDs
 * @param  string $text             Message text
 * @param  string $url              URL to include as link in message
 * @param  int $owner               ID of message creator/owner
 * @param  int $notification_type   Message type e.g. MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN, MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL. See definitions.php
 * @param  int $ttl_seconds         Lifetime of message in seconds before expiry
 * @param  int $related_activity    ID of related activity type - see SYSTEM NOTIFICATION TYPES section in definitions.php
 * @param  int $related_ref         Related activity ID - used with type above to delete redundant messages e.g. once a user or resource request has been approved
 * @return void
 */
function message_add($users,$text,$url="",$owner=null,$notification_type=MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,$ttl_seconds=MESSAGE_DEFAULT_TTL_SECONDS, $related_activity=0, $related_ref=0)
	{
	global $userref,$applicationname,$lang, $baseurl, $baseurl_short;
	
	if(!is_int_loose($notification_type))
		{
		$notification_type=intval($notification_type); // make sure this in an integer
		}
	
	$orig_text=$text;

	if (!is_array($users))
		{
		$users=array($users);
		}

    if(checkperm('E'))
        {
        $validusers = get_users(0,"","u.username",true,1);
        $validuserrefs = array_column($validusers,"ref");
        $users = array_filter($users,function($user) use ($validuserrefs) {return in_array($user,$validuserrefs);});
        }

	if(is_null($owner) || (isset($userref) && $userref != $owner))
		{
        // Can't send messages from another user
		$owner=$userref;
		}

	ps_query("INSERT INTO `message` (`owner`, `created`, `expires`, `message`, `url`, `related_activity`, `related_ref`, `type`) VALUES (? , NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND), ?, ?, ?, ?, ?)", array("i",$owner,"i",$ttl_seconds,"s",$text,"s",str_replace($baseurl.'/', $baseurl_short, $url),"i",$related_activity,"i",$related_ref,"i",$notification_type));
	$message_ref = sql_insert_id();

	foreach($users as $user)
		{
		ps_query("INSERT INTO `user_message` (`user`, `message`) VALUES (?, ?)", array("i",(int)$user,"i",$message_ref));
		
		// send an email if the user has notifications and emails setting and the message hasn't already been sent via email
		if(~$notification_type & MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL)
			{
			get_config_option($user,'email_and_user_notifications', $notifications_always_email);
			if($notifications_always_email)
				{
				$email_to=ps_value("select email value from user where ref = ?", array("i",$user), "");
				if($email_to!=='')
					{
                    if(substr($url,0,1) == "/")
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

/**
 * Remove a message from message table and associated user_messages
 *
 * @param  int $message Message ID
 * @return void
 */
function message_remove($message)
	{
	ps_query("DELETE FROM user_message WHERE message = ?", array("i",$message));
	ps_query("DELETE FROM message WHERE ref = ?", array("i",$message));	
	}

/**
 * Mark a message as seen
 *
 * @param  int $message Message ID
 * @param  int $seen_type    - see definitons.php
 * @return void
 */
function message_seen($message,$seen_type=MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN)
	{
	ps_query("UPDATE `user_message` SET seen = seen | ? WHERE `ref` = ?", array("i",$seen_type,"i",$message));
	}
    
/**
 * Mark a message as unseen
 *
 * @param  int $message Message ID
 * @return void
 */
function message_unseen($message)
	{
	ps_query("UPDATE `user_message` SET seen = '0' WHERE `ref` = ?", array("i",$message));
	}

/**
 * Flags all non-read messages as read for given user and seen type
 *
 * @param  int $user    User ID
 * @param  int $seen_type    - see definitons.php
 * @return void
 */
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

/**
 * Remove all messages from message and user_message tables that have expired (regardless of read). 
 * This will be called from a cron job.
 *
 * @return void
 */
function message_purge()
	{
	ps_query("DELETE FROM user_message WHERE message IN (SELECT ref FROM message where expires < NOW())", array());
	ps_query("DELETE FROM message where expires < NOW()", array());
	}

/**
 * Delete all selected messages
 *
 * @param $messages List of message refs in JSON list format
 * @return void
 */
function message_deleteselusrmsg($messages)
    {
    global $userref;
 
    $parameters = array("i",(int)$userref);
    $messages = json_decode($messages, true);
    $parameters = array_merge($parameters, ps_param_fill($messages,"i"));
 
    ps_query("DELETE FROM user_message WHERE user = ? AND ref IN (" . ps_param_insert(count($messages)) . ")", $parameters);
    }
 
/**
 * Mark all selected messages as seen
 *
 * @param $messages List of message refs in JSON list format
 * @return void
 */
function message_selectedseen($messages)
    {
    global $userref;
 
    $parameters = array("i",(int)$userref);
    $messages = json_decode($messages, true);
    $parameters = array_merge($parameters, ps_param_fill($messages,"i"));
 
    ps_query("UPDATE user_message SET seen = '1' WHERE user = ? AND ref IN (" . ps_param_insert(count($messages)) . ")", $parameters);
    }
 
/**
 * Mark all selected messages as unseen
 *
 * @param $messages List of message refs in JSON list format
 * @return void
 */
function message_selectedunseen($messages)
    {
    global $userref;
 
    $parameters = array("i",(int)$userref);
    $messages = json_decode($messages, true);
    $parameters = array_merge($parameters, ps_param_fill($messages,"i"));
 
    ps_query("UPDATE user_message SET seen = '0' WHERE user = ? AND ref IN (" . ps_param_insert(count($messages)) . ")", $parameters);
    }
 
/**
 * Send a summary of all unread notifications as an email
 * from the standard cron_copy_hitcount
 *
 * @return boolean  Returns false if not due to run
 */
function message_send_unread_emails()
	{
	global $lang, $applicationname, $baseurl, $list_search_results_title_trim, $user_pref_daily_digest, $applicationname, $actions_on, $inactive_message_auto_digest_period, $user_pref_inactive_digest;
    
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
    # Build array of sql query parameters
    $parameters = array();
    if (count($digestusers) > 0)
        {
        $parameters = array_merge($parameters, ps_param_fill($digestusers,"i"));
        }
    $parameters = array_merge($parameters, array("s",$lastrun));
    if (count($sendall) > 0)
        {
        $parameters = array_merge($parameters, ps_param_fill($sendall,"i"));
        }
    $unreadmessages = ps_query("SELECT u.ref AS userref, u.email, m.ref AS messageref, m.message, m.created, m.url FROM user_message um JOIN user u ON u.ref = um.user JOIN message m ON m.ref = um.message WHERE um.seen = 0"
      . (count($digestusers) > 0 ? " AND u.ref IN (" . ps_param_insert(count($digestusers)) . ")" : "") . " AND u.email <> '' AND (m.created > ?" . (count($sendall) > 0 ? " OR u.ref IN (" . ps_param_insert(count($sendall)) . ")" : "") . ") ORDER BY m.created DESC", $parameters);

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
        get_config_option($digestuser,'user_pref_inactive_digest', $user_pref_inactive_digest);
        get_config_option($digestuser,'user_pref_daily_digest', $user_pref_daily_digest);
        
        if($inactive_message_auto_digest_period == 0 || (!$user_pref_inactive_digest && !$user_pref_daily_digest)) // This may be set differently as a group configuration overerride or disabled by user
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
                $msgurl = $unreadmessage["url"];
                if(substr($msgurl,0,1) == "/")
                    {
                    // If a relative link is provided make sure we add the full URL when emailing
                    $msgurl = $baseurl . $msgurl;
                    }
                $message .= "<tr><td>" . nicedate($unreadmessage["created"], true, true, true) . "</td><td>" . $unreadmessage["message"] . "</td><td><a href='" . $msgurl . "'>" . $lang["link"] . "</a></td></tr>";
                $messagerefs[]=$unreadmessage["messageref"];
                }
            }

        if(count($messagerefs) == 0)
            {
            $message .= "<tr><td colspan='3'>" . $lang["nomessages"] . "</td></tr>";
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
                      $actioneditlink = $baseurl . "/pages/team/team_request_edit.php";
					  }
					elseif($user_action["type"]=="userrequest")
					  {
					  $actioneditlink = $baseurl . "/pages/team/team_user_edit.php";
					  } 
					
					$linkparams["ref"] = $user_action["ref"];                            
					$editlink=($actioneditlink=='')?'':generateURL($actioneditlink,$linkparams);
					$viewlink=($actionviewlink=='')?'':generateURL($actionviewlink,$linkparams);
					$message .= "<tr>";
					$message .= "<td>" . nicedate($user_action["date"], true, true, true) . "</td>";
					$message .= "<td><a href=\"" . $editlink . "\" >" . $user_action["ref"] . "</a></td>";
					$message .= "<td>" . tidy_trim(TidyList($user_action["description"]),$list_search_results_title_trim) . "</td>";
					$message .= "<td>" . $lang["actions_type_" . $user_action["type"]] . "</td>";
					$message .= "<td><div class=\"ListTools\">";
					if($editlink!=""){$message .= "&nbsp;&nbsp;<a href=\"" . $editlink . "\" >" . $lang["action-edit"] . "</a>";}
					if($viewlink!=""){$message .= "&nbsp;&nbsp;<a href=\"" . $viewlink . "\" >" . $lang["view"] . "</a>";}
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
		if($mark_read && count($messagerefs) > 0)
			{
            $parameters = array("i",MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL);
            $parameters = array_merge($parameters,ps_param_fill($messagerefs,"i"));
            $parameters = array_merge($parameters, array("i",$digestuser));
            ps_query("UPDATE user_message SET seen = ? WHERE message IN (" . ps_param_insert(count($messagerefs)) . ") and user = ?", $parameters);
			}
		}

    set_sysvar("daily_digest",date("Y-m-d H:i:s"));
    return true; 
	}

/**
 * Remove all messages related to a certain activity (e.g. resource request or resource submission)
 * matching the given ref(s)

 * @param  int $remote_activity     ID of related activity type - see SYSTEM NOTIFICATION TYPES section in definitions.php
 * @param  mixed $remote_refs       Related activity ID or array of IDs
 * @return void
 */
function message_remove_related($remote_activity=0,$remote_refs=array())
	{
	if($remote_activity==0 || $remote_refs==0 || (is_array($remote_refs) && count($remote_refs)==0) ){return false;}
	if(!is_array($remote_refs)){$remote_refs=array($remote_refs);}
    $parameters = array("i", $remote_activity);
    $parameters = array_merge($parameters, ps_param_fill($remote_refs,"i"));

    $relatedmessages = ps_array("select ref value from message where related_activity = ? and related_ref in (" . ps_param_insert(count($remote_refs)) . ");", $parameters, "");
    if(count($relatedmessages)>0)
        {
        $parameters = ps_param_fill($relatedmessages,"i");
        ps_query("DELETE FROM message WHERE ref in (" . ps_param_insert(count($relatedmessages)) . ");", $parameters);
        ps_query("DELETE FROM user_message WHERE message in (" . ps_param_insert(count($relatedmessages)) . ");", $parameters);
        }
	}

/**
* Send a system notification or email to the system administrators according to preference
* 
* @param string  $message      Message text
* @param string  $url          Optional URL
* 
* @return void
*/ 
function system_notification($message, $url="")
    {
    global $lang, $applicationname;
    $admin_notify_emails = array();
    $admin_notify_users = array();
    $notify_users=get_notification_users("SYSTEM_ADMIN");
    $subject = str_replace("%%APPLICATION_NAME%%", $applicationname, $lang["system_notification"]);
    foreach($notify_users as $notify_user)
        {
        get_config_option($notify_user['ref'],'user_pref_system_management_notifications', $send_message);
        if($send_message==false)
            {
            continue;
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
        $template = "system_notification_email";
        $templatevars = array("message"=>$message,"url"=>$url);
        $messageplain = $message . "\n\n" . $url;
        send_mail($admin_notify_email,$subject,$messageplain,'','',$template,$templatevars);
        }

    if (count($admin_notify_users)>0)
        {
        message_add($admin_notify_users, $message, $url, 0);
        }
    }

/**
* Get all message refs for a given user
* 
* @param string  $user      User ID
* 
* @return void
*/ 
function message_getrefs($user)
    {
    $user_messages = ps_query("SELECT ref FROM user_message WHERE user = ?", array("i",$user));
 
    $js_array = array_values($user_messages);
 
    echo json_encode($js_array);
    }

/**
 * Get all messages between the given user IDs
 *  
 * @param  int        $users    User ID
 * @param  array      $msgusers Array of other user IDs
 * 
 * @param  array    $filteropts Array of extra options to filter and sort messages returned
 *                              "msgfind"    - (string) Text to find
 *                              "sort_desc"  - (bool) Sort by message ID in descending order? False = Ascending
 *                              "msglimit"   - (int) Maximum number of messages to return
 * 
 * @return array   Array of messages
 */
function message_get_conversation(int $user, $msgusers = array(),$filteropts = array())
	{
    array_map("is_int_loose",$msgusers);
    if(count($msgusers) == 0 || !is_int_loose($user))
        {
        return array();
        }
    $validfilterops = array(
        "msgfind",
        "sort_desc",
        "limit",
    );
    foreach($validfilterops as $validfilterop)
        {
        if(isset($filteropts[$validfilterop]))
            {
            $$validfilterop = $filteropts[$validfilterop];
            }
        else
            {
            $$validfilterop = NULL;
            }
        }

    # Build array of sql query parameters
    $parameters = ps_param_fill($msgusers,"i");
    $parameters = array_merge($parameters, array("i",$user), array("i",$user));
    $parameters = array_merge($parameters, ps_param_fill($msgusers,"i"));
    if ($msgfind != "" )
        {
        $parameters = array_merge($parameters, array("s", $msgfind));
        }
    if ($limit != "")
        {
        $parameters = array_merge($parameters, array("i", (int)$limit));
        }
    $msgquery = "SELECT message.created,
                        message.owner,
                        message.message,
                        message.url,
                        message.expires,
                        message.type,
                        user_message.user,
                        user_message.ref,
                        user_message.seen
                   FROM message
              LEFT JOIN user_message ON user_message.message=message.ref
                  WHERE ((owner IN(" . ps_param_insert(count($msgusers)) . ") AND user_message.user = ?)
                     OR (owner = ? AND user_message.user IN(" . ps_param_insert(count($msgusers)) . ")))"
           .  ($msgfind != "" ? (" AND message.message LIKE ?") : " " )
           . " AND type & '" . MESSAGE_ENUM_NOTIFICATION_TYPE_USER_MESSAGE . "'"
		   . " ORDER BY user_message.ref " . ($sort_desc ? "DESC" : "ASC")
           . ($limit != "" ? " LIMIT ?" : "");
	
    $messages = ps_query($msgquery, $parameters);
    
    return $messages;
	}

/**
 * Send a user to user(s) message
 *
 * @param  array $users     Array of user IDs or usernames/groupnames from user select
 * @param  string $text     Message text
 * @return bool|string      True if sent ok or error message
 */
function send_user_message($users,$text)
    {
    global $userref, $lang;
    $users=explode(",",resolve_userlist_groups($users));
    for($n=0;$n<count($users);$n++)
        {
        if(!is_int_loose($users[$n]))
            {
            $uref = get_user_by_username(trim($users[$n]));
            if (!$uref)
                {
                return $lang["error_invalid_user"];
                }
            $users[$n] = $uref; 
            }
        }
    message_add($users,$text,"",$userref,MESSAGE_ENUM_NOTIFICATION_TYPE_USER_MESSAGE + MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,30*24*60*60);
    return true;
    }