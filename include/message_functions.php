<?php

/**
 * Simple class to use for user notifications
 * 
 * @internal
 */
class ResourceSpaceUserNotification
    {   
    /**
     * @var array $message_parts 
     * Array of message text components and optional find/replace arrays for language strings
     */
    private $message_parts = [];

    /**
     * @var array $subject
     * Array of subject parts 
     */
    private $subject = [];   

    /**
     * @var string $url
     */
    public $url = "";

    /**
     * @var array $template
     */
    public $template;

    /**
     * @var array $templatevars
     */
    public $templatevars;

     /**
     * @var array $user_preference  Optional array of (boolean only) user preferences, with required and default values to check for when sending notification e.g.
     * ["user_pref_resource_access_notifications"=>["requiredvalue"=>true,"default"=>$admin_resource_access_notifications],"actions_resource_requests" =>["requiredvalue"=>false,"default"=>true]]
     * or
     * ["user_pref_system_management_notifications" => true]
     * 
     * All preferences must be set to the required values for the notification to be sent
     * 
     */
    public $user_preference;

    /**
     * @var array $eventdata  Optional array for linking the system message to a specific activity e.g. resource or account request so that it can be deleted once the request has been processed.
     *                  ["type"] e.g. MANAGED_REQUEST
     *                  ["ref"]  
     */
    public $eventdata = [];

    /**
     * Set the notification message
     *
     * @param  string   $text       Text or $lang string using the 'lang_' prefix
     * @param  array    $find       Array of find strings to use for str_replace() in $lang strings
     * @param  array    $replace    Array of replace strings to use for str_replace()
     * 
     * @return void
     */
    public function set_text($text,$find=[], $replace=[])
        {
        $this->message_parts = [[$text, $find, $replace]];
        }

    /**
     * Append text to the notification message
     *
      * @param  string   $text       Text or $lang string using the 'lang_' prefix
      * @param  array    $find       Array of find strings to use for str_replace() in $lang strings
      * @param  array    $replace    Array of replace strings to use for str_replace()
      * @return void
     */
    public function append_text($text,$find=[], $replace=[])
        {
        $this->message_parts[] = [$text, $find, $replace];
        }

    /**
     * Append multiple text elements to the notification message
     *
      * @param  array   $messages    Array of text components as per append_text()
      * @return void
     */
    public function append_text_multi($textarr)
        {
        $this->message_parts = array_merge( $this->message_parts,$textarr);
        }

    /**
     * Prepend text component to the notification message
     *
      * @param  string   $text       Text or $lang string using the 'lang_' prefix
      * @param  array    $find       Array of find strings to use for str_replace() in $lang strings
      * @param  array    $replace    Array of replace strings to use for str_replace()
      * @return void
     */
    public function prepend_text($text,$find=[], $replace=[])
        {
        array_unshift($this->message_parts,[$text, $find, $replace]);
        }

    /**
     * Prepend multiple text elements to the notification message
     *
      * @param  array   $messages    Array of text components as per append_text()
      * @return void
     */
    public function prepend_text_multi($textarr)
        {
        // Loop in reverse order so that the parts get ordered correctly at start
        for($n=count($textarr);$n--;$n>=0)
            {
            array_unshift($this->message_parts,$textarr[$n]);
            }
        }

    /**
     * Set the notification subject
     *
     * @param  string   $text       Text or $lang string using the 'lang_' prefix
     * @param  array    $find       Array of find strings to use for str_replace() in $lang strings
     * @param  array    $replace    Array of replace strings to use for str_replace()
     * 
     * @return void
     */
    public function set_subject($text,$find=[], $replace=[])
        {
        $this->subject = [[$text, $find, $replace]];
        }

    /**
     * Append text to the notification subject
     *
      * @param  string   $text       Text or $lang string using the 'lang_' prefix
      * @param  array    $find       Array of find strings to use for str_replace() in $lang strings
      * @param  array    $replace    Array of replace strings to use for str_replace()
      * @return void
     */
    public function append_subject($text,$find=[], $replace=[])
        {
        $this->subject[] = [$text, $find, $replace];
        }

    /**
     * Get the message text, by default this is resolved into single string with text translated and with the find/replace completed
     * Note that if not returning raw data the correct $lang must be set by  before this is called
      * @param  bool    $unresolved       Return the raw message parts to use in another message object. False by default
     *
     * @return string|array
     */
    public function get_text($unresolved=false)
        {
        global $lang;
        if($unresolved)
            {
            return $this->message_parts;
            }
        $messagetext = "";
        foreach($this->message_parts as $message_part)
            {
            $text = $message_part[0];
            if(substr($text,0,5) == "lang_")
                {
                $langkey = substr($text,5);
                $text = $lang[$langkey];
                }
            if(substr($text,0,5) == "i18n_")
                {
                $i18n_string = substr($text,5);
                $text = i18n_get_translated($i18n_string);
                }
            if(isset($message_part[1]) && isset($message_part[2]) && count($message_part[1])  == count($message_part[2]))
                {
                $text = str_replace($message_part[1],$message_part[2],$text);
                }
            $messagetext .= $text;
            }
        return $messagetext;
        }

    public function get_subject()
        {
        global $lang;
        $fullsubject = "";
        foreach($this->subject as $subjectpart)
            {
            $text = $subjectpart[0];
            if(substr($text,0,5) == "lang_")
                {
                $langkey = substr($text,5);
                $text = $lang[$langkey];
                }

            if(isset($subjectpart[1]) && isset($subjectpart[2]))
                {
                $text = str_replace($subjectpart[1],$subjectpart[2],$text);
                }
            $fullsubject .= $text;
            }
        return $fullsubject;
        }
    }

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

    $messages=ps_query("SELECT user_message.ref, message.ref AS 'message_id', user.username AS owner, user_message.seen, message.created, message.expires, message.message, message.url, message.owner as ownerid, message.type " .
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
    global $userref,$applicationname,$lang, $baseurl, $baseurl_short,$header_colour_style_override;

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
        $validusers = get_users(0,"","u.username",true);
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
                $email_to=ps_value("SELECT email value FROM user WHERE ref = ?", array("i",$user), "");
                if($email_to!=='')
                    {
                    if(substr($url,0,1) == "/")
                        {
                        // If a relative link is provided make sure we add the full URL when emailing
                        $parsed_url = parse_url($baseurl);
                        $url = $baseurl . (isset($parsed_url["path"]) ? str_replace($parsed_url["path"],"",$url) : $url);
                        }

                    $message_text=nl2br($orig_text);

                    // Add system header image to email
                    $headerimghtml = "";
                    $img_url = get_header_image(true);
                    $img_div_style = "max-height:50px;padding: 5px;";
                    $img_div_style .= "background: " . ((isset($header_colour_style_override) && $header_colour_style_override != '') ? $header_colour_style_override : "rgba(0, 0, 0, 0.6)") . ";";        
                    $headerimghtml = '<div style="' . $img_div_style . '"><img src="' . $img_url . '" style="max-height:50px;"  /></div><br /><br />';
                                
                    if($url !== '' && strpos($message_text,$url) === false)
                        {
                        // Add the URL to the message if not already present
                        $message_text = $message_text . "<br /><br /><a href='" . $url . "'>" . $url . "</a>";
                        }
                    send_mail($email_to,$applicationname . ": " . $lang['notification_email_subject'],$headerimghtml . $message_text);
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
            if(!in_array($user["ref"],$digestusers) && strtotime((string)$user["last_active"]) < date(time() - $inactive_message_auto_digest_period *  60 * 60 *24))
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

/**
 * Send system notifications to specified users, checking the user preferences first if specified
 *
 * @param  array  $users            Array of user IDs or array of user details from get_users()
 * @param  ResourceSpaceUserNotification $notifymessage    An instance of a ResourceSpaceUserNotification object holding message properties
 * @param  bool   $forcemail        Force system to send email instead of notification?
 * 
 * @return array  Array containing resulting messages - can be used for testing when emails are not being sent
 *                This will contain two arrays:-
 *                          "emails"       array of emails sent, with the following elements:-
 *                              "email"     => Email address
 *                              "subject"   => Email subject
 *                              "body"      => Body text
 * 
 *                          "messages"      Array of system messages sent with the following elements :-
 *                              "user"      => User ID
 *                              "message"   => message text
 *                              "url"       => url
 */
function send_user_notification(array $users, $notifymessage, $forcemail=false)
    {
    global $userref, $lang, $plugins, $header_colour_style_override;

    // Need to global $applicationname as it is used inside the lang files
    global $applicationname;
    $userlanguages = []; // This stores the users in their relevant language key element

    // Set up $results array
    $results = [];
    $results["messages"] = [];
    $results["emails"] = [];
    foreach($users as $notify_user)
        {
        $userdetails = $notify_user;
        if(!is_array($userdetails))
            {
            $userdetails = get_user((int)$userdetails);
            }
        elseif(!isset($userdetails["lang"]))
            {
            // Need full user info
            $userdetails = get_user($userdetails["ref"]);
            }        
        if($userdetails == false)
            {
            continue;
            }
        
        $send_message=true;
        // Check if preferences should prevent the notification from being sent
        if(isset($notifymessage->user_preference) && is_array($notifymessage->user_preference))
            {
            foreach($notifymessage->user_preference as $preference=>$vals)
                {
                if($preference != "")
                    {
                    $requiredvalue  = (bool)$vals["requiredvalue"];
                    $default        = (bool)$vals["default"];
                    get_config_option($userdetails['ref'],$preference, $check_pref,$default);
                    debug(" - Required preference: " . $preference . " = " . ($requiredvalue ? "TRUE" : "FALSE"));
                    debug(" - User preference value: " . $preference . " = " . ($check_pref ? "TRUE" : "FALSE"));
                    if($check_pref != $requiredvalue)
                        {
                        debug("Skipping notification to user #" . $userdetails['ref']);
                        $send_message=false;                        
                        }
                    }
                }
            }
        if($send_message==false)
            {
            continue;
            }
        debug("Sending notification to user #" . $userdetails["ref"]);
        get_config_option($userdetails['ref'],'email_user_notifications', $send_email);
        if(!isset($userlanguages[$userdetails['lang']]))
            {
            $userlanguages[$userdetails['lang']] = [];
            $userlanguages[$userdetails['lang']]["emails"] = [];
            $userlanguages[$userdetails['lang']]["message_users"] = [];
            }
        if(($send_email && filter_var($userdetails["email"], FILTER_VALIDATE_EMAIL)) || $forcemail)
            {
            debug("Sending email to user #" . $userdetails["ref"]);
            $userlanguages[$userdetails['lang']]["emails"][] = $userdetails["email"];
            }
        else
            {
            debug("Sending system message to user #" . $userdetails["ref"]);
            $userlanguages[$userdetails['lang']]["message_users"][]=$userdetails["ref"];
            }
        }
    $url = $notifymessage->url ?? NULL;
    $headerimghtml = "";
    if(!isset($notifymessage->template))
        {
        // Add header image to email if not using template
        $img_url = get_header_image(true);
        $img_div_style = "max-height:50px;padding: 5px;";
        $img_div_style .= "background: " . ((isset($header_colour_style_override) && $header_colour_style_override != '') ? $header_colour_style_override : "rgba(0, 0, 0, 0.6)") . ";";
        $headerimghtml = '<div style="' . $img_div_style . '"><img src="' . $img_url . '" style="max-height:50px;"  /></div><br /><br />';
        }

    foreach($userlanguages as $userlanguage=>$notifications)
        {
        debug("Processing notifications for language: '" . $userlanguage . "'");
        // Save the current lang array
        $saved_lang = $lang;
        if ($userlanguage!="en")
            {
            if (substr($userlanguage, 2, 1)=='-' && substr($userlanguage, 0, 2)!='en')
                {
                $langpath = dirname(__FILE__)."/../languages/" . safe_file_name(substr($userlanguage, 0, 2)) . ".php";
                if(file_exists($langpath))
                    {
                    include $langpath;
                    }
                }
            $langpath = dirname(__FILE__)."/../languages/" . safe_file_name($userlanguage) . ".php";
            if(file_exists($langpath))
                {
                include $langpath;
                }
            }

        # Register plugin languages in reverse order
        for ($n=count($plugins)-1;$n>=0;$n--)
            {
            if (!isset($plugins[$n]))
                {
                continue;
                }
            register_plugin_language($plugins[$n]);
            }

        // Load in the correct language strings
        lang_load_site_text($lang,"",$userlanguage);

        $subject = $notifymessage->get_subject();
        $messagetext = $notifymessage->get_text();
        if (count($notifications["message_users"])>0)
            {
            $activitytype = $notifymessage->eventdata["type"] ?? NULL;
            $relatedactivity = $notifymessage->eventdata["ref"] ?? NULL;
            foreach($notifications["message_users"] as $notifyuser)
                {
                $results["messages"][] = ["user"=>$notifyuser,"message"=>$messagetext,"url"=>$url];
                }
            message_add($notifications["message_users"],$messagetext, (string) $url,$userref,MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,MESSAGE_DEFAULT_TTL_SECONDS,$activitytype,$relatedactivity);
            }
        if (count($notifications["emails"])>0)
            {
            if(!empty($url) && !is_null($url) && strpos($messagetext,$url) === false)
                {
                // Add the URL to the message if not already present
                $messagetext = $messagetext . "<br /><br /><a href='" . $url . "'>" . $url . "</a>";
                }
            send_mail(implode(",",$notifications["emails"]),$subject,$headerimghtml . $messagetext,"","",$notifymessage->template,$notifymessage->templatevars);

            foreach($notifications["emails"] as $emailsent)
                {
                $results["emails"][] = ["email"=>$emailsent,"subject"=>$subject,"body"=>$headerimghtml . $messagetext];
                }

            foreach($notifications["message_users"] as $notifyuser)
                {
                $results[$notifyuser] = ["type"=>"messsage","body"=>$messagetext];
                }
            }
        // Restore the saved $lang array
        $lang = $saved_lang;
        }
    return $results;
    }

/**
 * Gets the user message for the given ref
 * 
 * @param  int  $ref            Message ID
 * @param  bool $checkaccess    Check if user can see the given message?
 * 
 * @return array|bool  Array with two elements: 'message' => message text,'url'=> message URL and 'owner' => message owner
 *                     False if user has no access or the requested message doesn't exist,
 */
function get_user_message(int $ref, bool $checkaccess=true)
	{
    global $userref;
    if($checkaccess)
        {
        $validmessages = ps_array("SELECT `message` value FROM user_message WHERE user = ?",["i",$userref]);
        if(!in_array($ref,$validmessages))
            {
            return false;
            }
        }
    $message = ps_query("SELECT message, url, owner FROM message WHERE ref = ?",["i",$ref]);
    
    return $message ? ["message"=>$message[0]["message"],"url"=>$message[0]["url"],"owner"=>$message[0]["owner"]] : false;    
    }