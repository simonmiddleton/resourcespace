<?php
include_once __DIR__ . '/../../../include/collections_functions.php';
include_once dirname(__FILE__) . '/../include/simplesaml_functions.php';

function HookSimplesamlAllPreheaderoutput()
    {
    if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        {
        debug("simplesaml: plugin not configured.");
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
        $session_hash = escape_check($_COOKIE["user"]);
        if (validate_user("u.session='{$session_hash}'", false) === false)
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
    $k = getvalescaped('k', '');
    if(
        $simplesaml_allow_public_shares &&
        '' != $k &&
        (
            // Hard to determine at this stage what we consider a collection/ resource ID so we
            // use the most general ones
            check_access_key_collection(str_replace('!collection', '', getvalescaped('search', '')), $k) || 
            check_access_key(getvalescaped('ref', ''), $k)
        )
    )
        {
        return true;
        }

	$url=str_replace("\\","/", $_SERVER["PHP_SELF"]);

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
    	if(!file_exists(simplesaml_get_lib_path() . '/config/config.php'))
            {
            debug("simplesaml: plugin not configured.");
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
            $anonymous_login_escaped = escape_check($anonymous_login);
            $anonymous_login_found   = sql_value("SELECT username AS `value` FROM user WHERE username = '{$anonymous_login_escaped}'", '');

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
                $reload_url = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : $_SERVER["REQUEST_URL"];
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
        
        if(strpos($simplesaml_username_attribute,",")!==false) // Do we have to join two fields together?
		    {
		    $username_attributes=explode(",",$simplesaml_username_attribute);
		    $username ="";
		    foreach ($username_attributes as $username_attribute)
                {
                if($username!=""){$username.=$simplesaml_username_separator;}   
                $username.=  $attributes[$username_attribute][0]; 				
                }
		    $username= $username . $simplesaml_username_suffix;
		    }
		else
		    {
            if(!isset($attributes[$simplesaml_username_attribute][0]) )
                {
                $samlusername = simplesaml_getauthdata("saml:sp:NameID");
                debug("simplesaml: username attribute not found. Setting to default user id " . $samlusername);
                $username= $samlusername . $simplesaml_username_suffix;
                }
            else
                {
                $username=$attributes[$simplesaml_username_attribute][0] . $simplesaml_username_suffix;
                }
		    }

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

		$password_hash= md5("RSSAML" . $scramble_key . $username);

        $userid = 0;
        $currentuser = sql_query("SELECT ref, usergroup FROM user WHERE username='" . escape_check($username) . "'");
        $legacy_username_used = false;

        // Attempt one more time with ".sso" suffix. Legacy way of distinguishing between SSO accounts and normal accounts
        if(is_array($currentuser) && count($currentuser) == 0)
            {
            $legacy_username_escaped = escape_check("{$username}.sso");
            $currentuser = sql_query("SELECT ref, usergroup FROM user WHERE username = '{$legacy_username_escaped}'");
            $legacy_username_used = true;
            }

        if(count($currentuser) > 0)
            {
            $userid = $currentuser[0]["ref"];

            if($legacy_username_used)
                {
                $username_escaped = escape_check($username);
                $userid_escaped = escape_check($userid);
                sql_query("UPDATE user SET username = '{$username_escaped}' WHERE ref = '{$userid_escaped}'");
                }
            }

		debug ("simplesaml - got user details username=" . $username . ", email: " . (isset($email)?$email:""));

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
            $email_matches=sql_query("SELECT ref, username, fullname, origin FROM user WHERE email='" . escape_check($email) . "'");				
			
            if(count($email_matches)>0)
				{			
				if(count($email_matches)==1 && $simplesaml_create_new_match_email)
					{
                    // We want adopt this matching account - update the username and details to match the new login credentials
                    debug("simplesaml - user authenticated with matching email for existing user . " . $email . ", updating user account '" . $email_matches[0]["username"] . "' (id #" .$email_matches[0]["ref"] . ") to new username " . $username);
                    $userid = $email_matches[0]["ref"];
                    $origin = $email_matches[0]["origin"];
					$comment = $lang["simplesaml_usermatchcomment"]; 
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
                        }
                    }
                }
            else
                {
                // Create the user
                $userid=new_user($username,$group);    
                }
            }
            
        if ($userid > 0)
			{
			// Update user info
			global $simplesaml_update_group, $session_autologout;
            $sql = "UPDATE user SET origin='simplesaml', username='" . escape_check($username) . "', password = '$password_hash', fullname='" . escape_check($displayname) . "'";
            
            if(isset($email) && $email != "")
                {
                // Only set email if provided. Allows accounts without an email address to have one set by the admin without it getting overwritten
                $sql .= ", email='" . escape_check($email) . "'";
                }
            if(isset($comment))
                {
                $sql .= ",comments=concat(comments,'\n" . date("Y-m-d") . " " . escape_check($comment) . "')";
                log_activity($comment, LOG_CODE_UNSPECIFIED, 'simplesaml', 'user', 'origin', $userid, null, (isset($origin) ? $origin : null), $userid);
                //log_activity($note=null, $log_code=LOG_CODE_UNSPECIFIED, $value_new=null, $remote_table=null, $remote_column=null, $remote_ref=null, $ref_column_override=null, $value_old=null, $user=null, $generate_diff=false)
                }
			if($simplesaml_update_group || (isset($currentuser[0]["usergroup"]) && $currentuser[0]["usergroup"] == ""))
				{
				$sql .= ", usergroup = '$group'";
				}
            if(0 < count($custom_attributes))
                {
                $custom_attributes = json_encode($custom_attributes);
                $sql .=",simplesaml_custom_attributes = '" . escape_check($custom_attributes) . "'";
                }

			$sql .= " WHERE ref = '$userid'";
			sql_query($sql);			
           
			$user_select_sql="and u.username='" . escape_check($username) . " '";
            $allow_password_change = false;
            $session_autologout = false;
			return true;
			}
		return false;
        }

function HookSimplesamlAllLoginformlink()
        {
        if(!file_exists(simplesaml_get_lib_path() . '/config/config.php'))
            {
            debug("simplesaml: plugin not configured.");
            return false;
            }
			
		// Add a link to login.php, as this page may still be seen if $simplesaml_allow_standard_login is set to true
		global $baseurl, $lang, $simplesaml_login;
		
		// Don't show link to use SSO to login if this has been disabled
		if(!$simplesaml_login) {return false;}
		
        ?>
		<br/><a href="<?php echo $baseurl; ?>/?usesso=true"><?php echo  LINK_CARET . $lang['simplesaml_use_sso']; ?></a>
		<?php
        }



function HookSimplesamlLoginPostlogout()
        {
		global $simplesaml_login;
		
		if($simplesaml_login && simplesaml_is_authenticated()) 
			{simplesaml_signout();}
        }

function HookSimplesamlLoginPostlogout2()
        {
		global $baseurl,$simplesaml_login;
		if (getval("logout","")!="" && $simplesaml_login && simplesaml_is_authenticated())
			{
			simplesaml_signout();
			header( 'Location: '.$baseurl ) ;
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
    global $baseurl, $lang, $anon_login_modal, $contact_link, $simplesaml_prefer_standard_login, $simplesaml_site_block, $simplesaml_allow_standard_login, $simplesaml_login;

    // Don't show any link if signed in via SAML already and standard logins have been disabled
    if(!$simplesaml_allow_standard_login && !$simplesaml_login && simplesaml_is_authenticated())
        {
        return true;
        }
        
    if($simplesaml_prefer_standard_login || $simplesaml_site_block)
        {
        return false;
        }

    $onClick = '';

    if($anon_login_modal)
        {
        $onClick = 'onClick="return ModalLoad(this, true);"';
        }
        ?>
    <ul>
        <li>
            <a href="<?php echo $baseurl; ?>/?usesso=true"<?php echo $onClick; ?>><?php echo $lang['login']; ?></a>
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
    global $baseurl_short, $userref;
    
    redirect($baseurl_short . "pages/done.php?text=collection_email");
    }

function HookSimplesamlResource_emailReplaceresourceemailredirect()
    {
    global $baseurl_short, $userref, $ref, $search, $offset, $order_by, $sort, $archive;
    
  redirect($baseurl_short . "pages/done.php?text=resource_email&resource=" . urlencode($ref) . "&search=" . urlencode($search) . "&offset=" . urlencode($offset) . "&order_by=" . urlencode($order_by) . "&sort=" . urlencode($sort) . "&archive=" . urlencode($archive));
    }

function HookSimplesamlAllCheck_access_key()
    {
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
