<?php
function simplesaml_get_lib_path()
    {
    global $simplesaml_lib_path;

    $lib_path = dirname(__FILE__) . '/../lib';

    if('' == $simplesaml_lib_path)
        {
        return $lib_path;
        }

    $lib_path2 = $simplesaml_lib_path;

    if('/' == substr($lib_path2, -1))
        {
        $lib_path2 = rtrim($lib_path2, '/');
        }

    if(file_exists($lib_path2))
        {
        $lib_path = $lib_path2;
        }

    return $lib_path;
    }

function simplesaml_authenticate()
	{
	global $as,$simplesaml_sp;
    if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML\Auth\Simple($simplesaml_sp);
		}
	$as->requireAuth();
	return true;
	}
	
function simplesaml_getattributes()
	{
	global $as,$simplesaml_sp;
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML\Auth\Simple($simplesaml_sp);
		}
	$as->requireAuth();
	$attributes = $as->getAttributes();
	return $attributes;
	}
	

function simplesaml_signout()
	{
	global $baseurl, $as, $simplesaml_sp;
	if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML\Auth\Simple($simplesaml_sp);	
		}
	if($as->isAuthenticated())
		{
		$as->logout($baseurl . "/login.php"); 
		}
	
	}
	
function simplesaml_is_authenticated()
	{
	global $as,$simplesaml_sp;
	if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
    if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML\Auth\Simple($simplesaml_sp);
		}
	if(isset($as) && $as->isAuthenticated())
		{
		return true;
		}
	return false;	
	}

function simplesaml_getauthdata($value)
	{
    if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
	global $as,$simplesaml_sp;
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML\Auth\Simple($simplesaml_sp);
		}
	$as->requireAuth();
	$authdata = $as->getAuthData($value);
	return $authdata;
	}

function simplesaml_duplicate_notify($username, $group, $email, $email_matches, $newuserid = 0)
    {
    global $lang, $baseurl, $baseurl_short, $simplesaml_multiple_email_notify, $user_pref_user_management_notifications, $email_user_notifications, $applicationname;
    debug("simplesaml - user authenticated with matching email for existing users: " . $email);
    $message = $lang['simplesaml_multiple_email_match_text'] . " " . $email . "<br /><br />";
    $messageurl = "";
    if($newuserid > 0)
        {
        $messageurl = $baseurl . "/?u=" . $newuserid;   
        }
    
    $message .= "</a><table class=\"InfoTable\" style=\"width:100%\"border=1>";
    $message.="<tr><th>" . $lang["property-name"] . "</th><th>" . $lang["property-reference"] . "</th><th>" . $lang["username"] . "</th></tr>";
    foreach($email_matches as $email_match)
        {
        $message.="<tr><td><a href=\"" . $baseurl . "/?u=" . $email_match["ref"] .  "\" target=\"_blank\">" . $email_match["fullname"] . "</a></td><td><a href=\"" . $baseurl . "/?u=" . $email_match["ref"] .  "\" target=\"_blank\">" . $email_match["ref"] . "</a></td><td><a href=\"" . $baseurl . "/?u=" . $email_match["ref"] .  "\" target=\"_blank\">" . $email_match["username"] . "</a></td></tr>\n";
        }
    
    $message.="</table><a>";
    $emailmessage = $message;
    if($messageurl != "")
        {
        $emailmessage .= $lang["simplesaml_usercreated"] . ": <a href=\"" . $messageurl . "\">" . $username . "</a><br />";   
        }
    
    $notify_users = sql_query("SELECT ref, email FROM user WHERE email='" . escape_check($simplesaml_multiple_email_notify) . "'");				
    $message_users=array();
    foreach($notify_users as $notify_user)
        {
        get_config_option($notify_user['ref'],'user_pref_user_management_notifications', $send_message, $user_pref_user_management_notifications);
        if(!$send_message)
            {
            continue;
            }
            
        get_config_option($notify_user['ref'],'email_user_notifications', $send_email, $email_user_notifications);    
        if($send_email && filter_var($notify_user["email"], FILTER_VALIDATE_EMAIL))
            {
            send_mail($notify_user["email"],$applicationname . ": " . $lang["simplesaml_multiple_email_match_subject"],$emailmessage);
            }        
        else
            {
            $message_users[]=$notify_user["ref"];
            }
        }
     if (count($message_users)>0)
        {
        // Send a message with long timeout (30 days)
        message_add($message_users,str_replace($baseurl . "/", $baseurl_short, $message), $messageurl);
        }    
    }
