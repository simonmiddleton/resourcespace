<?php
include_once dirname(__FILE__) . '/../include/simplesaml_functions.php';
function HookSimplesamlAllPreheaderoutput()
    {      
    if(!simplesaml_php_check() || get_sysvar(SYSVAR_CURRENT_UPGRADE_LEVEL) != SYSTEM_UPGRADE_LEVEL)
        {
        // If a new version then allow upgrade scripts to run first
        return false;
        }

	global $simplesaml_site_block, $simplesaml_allow_public_shares, $simplesaml_allowedpaths, $simplesaml_login, $simplesaml_allow_standard_login,
    $anonymous_login, $pagename, $baseurl;

    if($simplesaml_login && simplesaml_is_authenticated())
        {
        // Prevent password change if SAML authenticated and signed in to RS with SAML
        // Also ensure we don't ask the user to type in a password, since we don't have it!
        global $allow_password_change, $delete_requires_password;
        $delete_requires_password=false;
        $allow_password_change=false;
        $session_autologout = false;
        return true;
        }

    if($pagename == "login" && !$simplesaml_allow_standard_login && ($simplesaml_login || trim($anonymous_login) !== ''))
        {
        // Shouldn't be able to see the login page, unless misconfigured in which case show to avoid a redirect loop and allow user to log in and recover
        debug("simplesaml: blocking access to login page");
        redirect($baseurl);
        exit();
        }

    // If normal user is logged in and allowing standard logins do nothing and return
    if ($simplesaml_allow_standard_login && isset($_COOKIE["user"]))
        {
        $session_hash = $_COOKIE["user"];

        $user_select_sql = new PreparedStatementQuery();
        $user_select_sql->sql = "u.session = ?";
        $user_select_sql->parameters = ["s",$session_hash];
        if (validate_user($user_select_sql, false) === false)
            {
            debug("simplesaml: standard user login - invalid user session");
            rs_setcookie('user', '', 0);
            }

        debug("simplesaml: standard user login - no action required");
        return true;
        }

    if(!$simplesaml_allow_standard_login)
        {
        global $show_anonymous_login_panel;
        $show_anonymous_login_panel = false;
        }

	// If not blocking site completely and allowing standard logins but not on login page, do nothing and return
	if (!$simplesaml_site_block && $simplesaml_allow_standard_login)
        {
        debug("simplesaml: standard user login - no action required");
        return true;
        }

	// Check for exclusions
    $k = getval('k', '');
    if(
        $simplesaml_allow_public_shares &&
        '' != $k &&
        (
            // Hard to determine at this stage what we consider a collection/ resource ID so we
            // use the most general ones
            check_access_key_collection(str_replace('!collection', '', getval('search', '')), $k) ||
            check_access_key(getval('ref', ''), $k)
        )
    )
        {
        return true;
        }

    $url=str_replace("\\","/", $_SERVER["PHP_SELF"]);
    if ($simplesaml_allow_public_shares)
        {
        // Allow redirect for password protected external shares
        $simplesaml_allowedpaths[] = '/pages/share_access.php';
        }

    foreach ($simplesaml_allowedpaths as $simplesaml_allowedpath)
        {
        if('' == trim($simplesaml_allowedpath))
            {
            continue;
            }

		$samlexempturl=strpos($url,$simplesaml_allowedpath);
		if ($samlexempturl!==false && $samlexempturl==0)
			{
			return true;
			}
		}

	$as=simplesaml_authenticate();
	return true;
	}


function HookSimplesamlAllProvideusercredentials()
        {       
        if(simplesaml_php_check() == false)
            {
            return false;
            }

		global $pagename, $simplesaml_allow_standard_login, $simplesaml_prefer_standard_login, $baseurl, $path, $default_res_types, $scramble_key,
        $simplesaml_username_suffix, $simplesaml_username_attribute, $simplesaml_fullname_attribute, $simplesaml_email_attribute, $simplesaml_group_attribute,
        $simplesaml_fallback_group, $simplesaml_groupmap, $user_select_sql, $session_hash,$simplesaml_fullname_separator,$simplesaml_username_separator,
        $simplesaml_custom_attributes,$lang,$simplesaml_login, $simplesaml_site_block, $anonymous_login,$allow_password_change, $simplesaml_create_new_match_email,
        $simplesaml_allow_duplicate_email, $simplesaml_multiple_email_notify, $simplesaml_authorisation_claim_name, 
        $simplesaml_authorisation_claim_value, $usercredentialsprovided;

        // Don't authenticate if this hook has already been handled by another higher priority plugin
        if(isset($usercredentialsprovided) && $usercredentialsprovided)
            {
            return false;    
            }

        // Allow anonymous logins outside SSO if simplesaml is not configured to block access to site.
        // NOTE: if anonymous_login is set to an invalid user, then use SSO otherwise it goes in an indefinite loop
        if(!$simplesaml_site_block && isset($anonymous_login) && trim($anonymous_login) !== '' && getval("usesso","")=="")
            {
            debug("simplesaml: checking for anonymous user");
            $anonymous_login_found   = ps_value("SELECT username AS `value` FROM user WHERE username = ?", array("s",$anonymous_login), '');

            // If anonymous_login is not set to a real username then use SSO to authenticate
            if($anonymous_login_found == '')
                {
                simplesaml_authenticate();
                }

            if(!simplesaml_is_authenticated())
                {
                return true;
                }
            elseif(!$simplesaml_login)
                {
                global $show_anonymous_login_panel;
                $show_anonymous_login_panel = false;    
                }
            }

        // If user is logged in or if SAML is not being used to login to ResourceSpace (just as a simple barrier, 
        // usually with anonymous access configured) then use standard authentication if available
        if($simplesaml_site_block && !simplesaml_is_authenticated())
            {
            debug("simplesaml: site block enabled, performing SAML authentication");
            simplesaml_authenticate();
            }

        if((isset($_COOKIE['user']) && $simplesaml_allow_standard_login) || (!$simplesaml_login && simplesaml_is_authenticated() ))
            {
            return true;
            }

		// Return false if not already authenticated and local login option is preferred
		if(!simplesaml_is_authenticated() && $simplesaml_allow_standard_login  && $simplesaml_prefer_standard_login && getval("usesso","")=="" )
			{
            return false;
			}

        if(!simplesaml_is_authenticated())
            {
            if($pagename == "done" && !isset($_COOKIE["SimpleSAMLAuthToken"]))
                {
                // Don't attempt to authenticate when on done.php if user is not already authenticated
                return false;
                }
            elseif(getval("ajax","") != "")
                {
                // Ajax loads can't be redirected. Need a full reload if session has timed out
                $url_alt = isset($_SERVER["HTTP_HOST"]) ?  $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"] : $baseurl;

                $reload_url = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : $url_alt;
                debug("simplesaml: ajax request - reloading page " . $reload_url);
                ?>
                <script>
                top.location.href="<?php echo str_replace(array("modal=true","ajax=true"),"",$reload_url); ?>";
                </script>	
                <?php    
                exit();
                }
            debug("simplesaml: authenticating");
            simplesaml_authenticate();
            }

		$attributes = simplesaml_getattributes();

        // Construct username        
        $username = "";
        if(strlen($simplesaml_username_attribute) > 0)
            {
            $username_attributes=explode(",",$simplesaml_username_attribute);
            $username_parts = [];
            foreach ($username_attributes as $username_attribute)
                {
                if(isset($attributes[$username_attribute][0]))
                    {
                    if(is_object($attributes[$username_attribute][0]))
                        {
                        $username_parts[] = $attributes[$username_attribute][0]->getValue();
                        }
                    elseif(is_string($attributes[$username_attribute][0]))
                        {
                        $username_parts[] = $attributes[$username_attribute][0];
                        }
                    }
                }
            if(count($username_parts)>0)
                {
                $username = implode($simplesaml_username_separator,$username_parts);
                }
            }
        if($username == '')
            {
            debug("simplesaml: WARNING: no username found, attempting to use NameID");
            // Attempt to fall back to NameID, truncated as necessary
            $username = simplesaml_getauthdata("saml:sp:NameID");
            }

        if($username == '')
            {
            // no username, can't continue
            debug("simplesaml: WARNING: no username found, aborting");
            return false;
            }

        // truncate if necessary
        if(strlen($username) > 50)
            {
            $username = mb_substr($username,0,15) . "_" . md5($username);
            }

        $username= $username . $simplesaml_username_suffix;

        // If local authorisation based on assertion/ claim is needed, check now and make sure we don't process any further!
        if(
            (trim($simplesaml_authorisation_claim_name) != '' && trim($simplesaml_authorisation_claim_value) != '')
            && (
                !array_key_exists($simplesaml_authorisation_claim_name, $attributes)
                || !in_array($simplesaml_authorisation_claim_value, $attributes[$simplesaml_authorisation_claim_name])
            )
        )
            {
            debug("simplesaml: WARNING: Unauthorised login attempt recorded for username '{$username}'!");
            ?>
            <script>
            top.location.href = "<?php echo generateURL("{$baseurl}/login.php", array('error' => 'simplesaml_authorisation_login_error')); ?>";
            </script>
            <?php
            return false;
            }

        if(strpos($simplesaml_fullname_attribute,",")!==false) // Do we have to join two fields together?
		    {
		    $fullname_attributes=explode(",",$simplesaml_fullname_attribute);
		    }
		else // Previous version used semi-colons
		    {
	        $fullname_attributes=explode(";",$simplesaml_fullname_attribute);
		    }

        $displayname ="";
        foreach ($fullname_attributes as $fullname_attribute)
            {
            if($displayname!=""){$displayname.=$simplesaml_fullname_separator;}
            if(!isset($attributes[$fullname_attribute][0]))
                {
                debug("simplesaml: error - invalid fullname attribute: " . $fullname_attribute . ". Please check your configuration");                      
                return false;
                }
            debug("simplesaml: constructing fullname FROM attribute " . $fullname_attribute . ": "  . $attributes[$fullname_attribute][0]);
            $displayname.=  $attributes[$fullname_attribute][0];
            }

		$displayname=trim($displayname);
		debug("simplesaml: constructed fullname : "  . $displayname);

		if(isset($attributes[$simplesaml_email_attribute][0]))
            {
            $email = $attributes[$simplesaml_email_attribute][0];
            }

        $groups = array();
        if(trim($simplesaml_group_attribute) != '' && isset($attributes[$simplesaml_group_attribute]))
            {
            $groups = $attributes[$simplesaml_group_attribute];
            }

        $userid = 0;
        $update_hash = false; // Only update password hash if necessary as computationally intensive
        $currentuser = ps_query("SELECT ref, usergroup, last_active FROM user WHERE username=?",array("s",$username));
        $legacy_username_used = false;

        // Attempt one more time with ".sso" suffix. Legacy way of distinguishing between SSO accounts and normal accounts
        if(is_array($currentuser) && count($currentuser) == 0)
            {
            $legacy_username = "{$username}.sso";
            $currentuser = ps_query("SELECT ref, usergroup, last_active FROM user WHERE username = ?",array("s",$legacy_username));
            $legacy_username_used = true;
            }

        if(count($currentuser) > 0)
            {
            $userid = $currentuser[0]["ref"];

            if($legacy_username_used)
                {
                ps_query("UPDATE user SET username = ? WHERE ref = ?",array("s",$username,"i",$userid));
                }

            // Update hash if not logged on in last day
            $lastactive = strtotime((string)$currentuser[0]["last_active"]);
            if($lastactive < date(time() - (60*60*24)))
                {
                $update_hash = true;
                }
            }

		debug ("simplesaml - got user details username=" . $username . ", email: " . (isset($email)?$email:"(not received)"));

        if(!isset($email))
            {
            // No email - may be a test account?
            $email = "";
            }

		// figure out group
		$group = $simplesaml_fallback_group;
		$currentpriority=0;
		if (count($simplesaml_groupmap)>0)
            {
			for ($i = 0; $i < count($simplesaml_groupmap); $i++)
				{
				for($g = 0; $g < count($groups); $g++)
					{
					if (($groups[$g] == $simplesaml_groupmap[$i]['samlgroup']) && is_numeric($simplesaml_groupmap[$i]['rsgroup']) && $simplesaml_groupmap[$i]['priority']>$currentpriority)
						{
						$group = $simplesaml_groupmap[$i]['rsgroup'];
						$currentpriority=$simplesaml_groupmap[$i]['priority'];
                        debug("simplesaml  - found mapping for SAML group: " . $groups[$g] . ", group #" . $simplesaml_groupmap[$i]['rsgroup'] . ". priority :"  . $simplesaml_groupmap[$i]['priority']);
						}
					}
				}
			}
        debug("simplesaml  - using RS group #" . $group);

        // If custom attributes need to be recorded against a user record, do it now
        $custom_attributes = array();
        if('' != $simplesaml_custom_attributes)
            {
            $search_custom_attributes = explode(',', $simplesaml_custom_attributes);
 
            foreach($attributes as $attribute => $attribute_value)
                {
                if(!in_array($attribute, $search_custom_attributes))
                    {
                    continue;
                    }
 
                // For now, we only allow one value per attribute
                $custom_attributes[$attribute] = $attribute_value[0];
                }
            }

        if ($userid <= 0)
			{
            // User authenticated, but does not exist
            // First see if there is a matching account
            $email_matches=ps_query("SELECT ref, username, fullname, origin FROM user WHERE email=?",array("s",$email));				

            if(count($email_matches)>0 && trim($email) != "")
				{
				if(count($email_matches)==1 && $simplesaml_create_new_match_email)
					{
                    // We want adopt this matching account - update the username and details to match the new login credentials
                    debug("simplesaml - user authenticated with matching email for existing user . " . $email . ", updating user account '" . $email_matches[0]["username"] . "' (id #" .$email_matches[0]["ref"] . ") to new username " . $username);
                    $userid = $email_matches[0]["ref"];
                    $origin = $email_matches[0]["origin"];
					$comment = $lang["simplesaml_usermatchcomment"]; 
                    $update_hash = true;
                    }
				else
                    {
                    if(!$simplesaml_allow_duplicate_email)
                        {
                        if (filter_var($simplesaml_multiple_email_notify, FILTER_VALIDATE_EMAIL) && getval("usesso","") != "")
                            {
                            // Already account(s) with this email address, notify the administrator (provided it is an actual attempt to pevent unnecessary duplicates)
                            simplesaml_duplicate_notify($username,$group,$email,$email_matches,$email,$userid);
                            }
                        // We are blocking accounts with the same email
                        if($simplesaml_allow_standard_login)
                            {
                            ?>
                            <script>
                            top.location.href="<?php echo $baseurl; ?>/login.php?error=simplesaml_duplicate_email_error";
                            </script>
                            <?php
                            exit();
                            }
                        else
                            {
                            return false;
                            }
                        }
                    else
                        {
                        // Create the user
                        $userid=new_user($username,$group);
                        if (!$userid)
                            {
                            debug("simplesaml - unable to create user: " . $userid);
                            return false;
                            }
                        if (filter_var($simplesaml_multiple_email_notify, FILTER_VALIDATE_EMAIL) && getval("usesso","") != "")
                            {
                            // Already account(s) with this email address, notify the administrator (provided it is an actual attempt to pevent unnecessary duplicates)
                            simplesaml_duplicate_notify($username,$group,$email,$email_matches,$userid);
                            }
                        include_once __DIR__ . '/../../../include/dash_functions.php';
                        build_usergroup_dash($group, $userid);
                        $update_hash = true;
                        }
                    }
                }
            else
                {
                // Create the user
                $userid=new_user($username,$group);
                include_once __DIR__ . '/../../../include/dash_functions.php';
                build_usergroup_dash($group, $userid);
                $update_hash = true;
                }
            }
            
        if ($userid > 0)
			{
			// Update user info
			global $simplesaml_update_group, $session_autologout;
            $hash_update = "";
            $sql = "UPDATE user SET origin='simplesaml', username=?,";
            $params=array("s",$username);

            if($update_hash)
                {
                $password_hash = rs_password_hash('RSSAML' . generateSecureKey(64) . $username);
                $sql .= "password = ?, ";
                $params[]="s";$params[]=$password_hash;
                }

            $sql.=" fullname=?";
            $params[]="s";$params[]=$displayname;

            if(isset($email) && $email != "")
                {
                // Only set email if provided. Allows accounts without an email address to have one set by the admin without it getting overwritten
                $sql .= ", email=?";
                $params[]="s";$params[]=$email;
                }
            if(isset($comment))
                {
                $sql .= ",comments=concat(comments,?)";
                $params[]="s";$params[]="\n" . date("Y-m-d") . " " . $comment;

                log_activity($comment, LOG_CODE_UNSPECIFIED, 'simplesaml', 'user', 'origin', $userid, null, (isset($origin) ? $origin : null), $userid);
                }
			if($simplesaml_update_group || (isset($currentuser[0]["usergroup"]) && $currentuser[0]["usergroup"] == ""))
				{
				$sql .= ", usergroup = ?";
                $params[]="i";$params[]=$group;
				}
            if(0 < count($custom_attributes))
                {
                $custom_attributes = json_encode($custom_attributes);
                $sql .=",simplesaml_custom_attributes = ?";
                $params[]="s";$params[]=$custom_attributes;
                }

			$sql .= " WHERE ref = ?";
            $params[]="i";$params[]=$userid;
			ps_query($sql,$params);

            $user_select_sql = new PreparedStatementQuery();
            $user_select_sql->sql = "u.username = ?";
            $user_select_sql->parameters = ["s",$username];
            
            $allow_password_change = false;
            $session_autologout = false;
			return true;
			}
		return false;
        }

function HookSimplesamlAllLoginformlink()
        {
        if(!simplesaml_php_check())
            {
            return false;
            }

        // Include in redirect any resource or collection parameter if present so we load to that page rather than home.
        $parameters = array('usesso' => 'true');
        $url_params = explode('?', getval("url",""));
        parse_str(str_replace('&amp;', '&', ($url_params[1] ?? "")), $url_params);

        if (isset($url_params['c']))
            {
            $parameters['c'] = $url_params['c'];
            }
            
        if (isset($url_params['r']))
            {
            $parameters['r'] = $url_params['r'];
            }

        // Add a link to login.php, as this page may still be seen if $simplesaml_allow_standard_login is set to true
        global $baseurl, $lang, $simplesaml_login;

        // Don't show link to use SSO to login if this has been disabled
        if(!$simplesaml_login)
            {
            return false;
            }
        ?>
        <a href="<?php echo generateURL($baseurl, $parameters); ?>"><i class="fas fa-fw fa-key"></i>&nbsp;<?php echo htmlspecialchars($lang['simplesaml_use_sso']); ?></a><br/>
        <?php
        }

function HookSimplesamlLoginPostlogout()
        {        
        if(!simplesaml_php_check())
            {
            return false;
            }
		global $simplesaml_login;

		if($simplesaml_login && simplesaml_is_authenticated())
			{
            simplesaml_signout();
            }
        }

function HookSimplesamlLoginPostlogout2()
        {
        if(!simplesaml_php_check())
            {
            return false;
            }
		global $baseurl,$simplesaml_login;
		if (getval("logout","")!="" && $simplesaml_login && simplesaml_is_authenticated())
			{
			simplesaml_signout();
			header('Location: '.$baseurl);
			}
        }


function HookSimplesamlAllCheckuserloggedin()
    {
    return simplesaml_is_authenticated();
    }


/**
* Render header navigation links in anonymous mode based on simplasaml configuration
* 
* 
*/
function HookSimplesamlAllReplaceheadernav1anon()
    {
    if(!simplesaml_php_check())
        {
        return false;
        }

    global $baseurl, $lang, $contact_link, $simplesaml_prefer_standard_login, $simplesaml_site_block, $simplesaml_allow_standard_login, $simplesaml_login;

    // Don't show any link if signed in via SAML already and standard logins have been disabled
    if(!$simplesaml_allow_standard_login && !$simplesaml_login && simplesaml_is_authenticated())
        {
        return true;
        }
        
    if($simplesaml_prefer_standard_login || $simplesaml_site_block)
        {
        return false;
        }

        ?>
    <ul>
        <li>
            <a href="<?php echo $baseurl; ?>/?usesso=true"><?php echo $lang['login']; ?></a>
        </li>
    <?php
    if($contact_link)
        {
        ?>
        <li>
            <a href="<?php echo $baseurl?>/pages/contact.php" onClick="return CentralSpaceLoad(this, true);"><?php echo $lang['contactus']; ?></a>
        </li>
        <?php
        }
        ?>
    </ul>
    <?php

    return true;
    }

function HookSimplesamlCollection_emailReplacecollectionemailredirect()
    {
    if(!simplesaml_php_check())
        {
        return false;
        }
    global $baseurl_short, $userref;

    redirect($baseurl_short . "pages/done.php?text=collection_email");
    }

function HookSimplesamlResource_emailReplaceresourceemailredirect()
    {
    if(!simplesaml_php_check())
        {
        return false;
        }
    global $baseurl_short, $userref, $ref, $search, $offset, $order_by, $sort, $archive;
    
    redirect($baseurl_short . "pages/done.php?text=resource_email&resource=" . urlencode($ref) . "&search=" . urlencode($search) . "&offset=" . urlencode($offset) . "&order_by=" . urlencode($order_by) . "&sort=" . urlencode($sort) . "&archive=" . urlencode($archive));
    }

function HookSimplesamlAllCheck_access_key()
    {
    if(!simplesaml_php_check())
        {
        return false;
        }
    global $external_share_view_as_internal, $simplesaml_login;

    /*
    Handle "$external_share_view_as_internal = true;" case. This require us to set the user up as authenticate.php is not called
    at this stage on search.php page so we need to validate user and set it up in order to set $internal_share_access.
    */
    if($external_share_view_as_internal && $simplesaml_login && simplesaml_is_authenticated())
        {
        global $is_authenticated, $user_select_sql;

        HookSimplesamlAllProvideusercredentials();

        $validate_user = validate_user($user_select_sql);

        if(is_array($validate_user[0]) && !empty($validate_user[0]))
            {
            setup_user($validate_user[0]);
            $is_authenticated = true;
            }
        }

    // return false because check_access_key() returns true without doing any checks on the key if hook returns TRUE
    return false;
    }

function HookSimplesamlAllExtra_fail_checks()
    {
    // Check if incompatible with PHP version
    $simplesaml_fail = [
        'name' => 'simplesaml',
        'info' => $GLOBALS['lang']['simplesaml_healthcheck_error'],
    ];

    $GLOBALS['use_error_exception'] = true;
    try
        {
        $samlok = simplesaml_php_check();
        }
    catch (Exception $e)
        {
        return $simplesaml_fail;
        }
    unset($GLOBALS['use_error_exception']);

    return $samlok ? false : $simplesaml_fail;
    }

function HookSimplesamlAllExtra_warn_checks()
    {
    // Check if SAML library needs updating (if pre-9.7 SP not using ResourceSpace config)
    $simplesaml_warn = [
        'name' => 'simplesaml',
        'info' => $GLOBALS['lang']['simplesaml_healthcheck_error'],
    ];

    $GLOBALS['use_error_exception'] = true;
    try
        {
        $samlok = simplesaml_config_check();
        }
    catch (Exception $e)
        {
        return array($simplesaml_warn);
        }
    unset($GLOBALS['use_error_exception']);

    return $samlok ? false : array($simplesaml_warn);
    }