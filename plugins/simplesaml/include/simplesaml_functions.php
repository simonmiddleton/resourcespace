<?php

/**
 * Get the configured path to the root of the SimpleSAML library
 * If $simplesaml_lib_path is not set this will be the [webroot]/plugins/simplesaml/lib folder
 *
 * @return string
 */
function simplesaml_get_lib_path()
    {
    global $simplesaml_lib_path, $simplesaml_rsconfig;

    $lib_path = dirname(__FILE__) . '/../lib';

    if('' == $simplesaml_lib_path || $simplesaml_rsconfig)
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

/**
 * Authenticate user, redfirecting to IdP if necessary
 *
 * @return boolean
 */
function simplesaml_authenticate()
    {
    global $as;
    if(simplesaml_is_configured() == false)
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
    if(!isset($as))
        {
        require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
        $spname = get_saml_sp_name();
        debug("simplesaml: Using SP name '{$spname}'");
        $as = new SimpleSAML\Auth\Simple($spname);
        }
    $as->requireAuth();
    return true;
    }

/**
 * Get SAML attributes
 *
 * @return array
 */
function simplesaml_getattributes()
    {
    global $as;
    if(!isset($as))
        {
        require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
        $spname = get_saml_sp_name();
        $as = new SimpleSAML\Auth\Simple($spname);
        }
    $as->requireAuth();
    $attributes = $as->getAttributes();
    return $attributes;
    }	

/**
 * Sign out of SAML SP
 *
 * @return void
 */
function simplesaml_signout()
	{
	global $baseurl, $as;
	if(simplesaml_is_configured() == false)
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');        
        $spname = get_saml_sp_name();
        $as = new SimpleSAML\Auth\Simple($spname);
		}
	if($as->isAuthenticated())
		{
		$as->logout($baseurl . "/login.php"); 
		}	
	}
	
/**
 * Check if user has been authenticated by SimpleSAMLPHP
 *
 * @return boolean
 */
function simplesaml_is_authenticated()
	{
	global $as,$simplesaml_authenticated;
	if(simplesaml_is_configured() == false)
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }

    if(isset($simplesaml_authenticated))
        {
        return $simplesaml_authenticated;
        }

    if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');     
        $spname = get_saml_sp_name();
        $as = new SimpleSAML\Auth\Simple($spname);
		}
	if(isset($as) && $as->isAuthenticated())
		{
        $simplesaml_authenticated = true;
		return true;
		}
	return false;	
	}

function simplesaml_getauthdata($value)
	{
    if(simplesaml_is_configured() == false)
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
	global $as;
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');       
        $spname = get_saml_sp_name();
        $as = new SimpleSAML\Auth\Simple($spname);
        }
	$as->requireAuth();
	$authdata = $as->getAuthData($value)->getValue();
	return $authdata;
	}

/**
 * Notify of a new SAML user with an email address that is already in use by an existing user
 *
 * @param  string   $username       Username
 * @param  int      $group          Usergroup
 * @param  string   $email          Email 
 * @param  array    $email_matches  Array of existing users with matching email
 * @param  int      $newuserid      ID of new user if created
 * @return void
 */
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
    
    $notify_users = ps_query("SELECT ref, email FROM user WHERE email=?",array("s",$simplesaml_multiple_email_notify));				
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

/**
 * Check that the SimpleSAMLphp configuration is valid
 *
 * @return boolean
 */
function simplesaml_config_check()
	{
    global $simplesaml_version, $lang;
    
	if(simplesaml_is_configured() == false)
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
    require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
	$config = \SimpleSAML\Configuration::getInstance();
    $version = $config->getVersion();

    if($version != $simplesaml_version)
        {
        if(get_sysvar("SAML_UPGRADE_REQUIRED",0) != 1)
            {
            system_notification($lang['simplesaml_authorisation_version_error'], "https://www.resourcespace.com/knowledge-base/plugins/simplesaml#saml_instructions_migrate");
            // Set flag so this is not sent multiple times
            set_sysvar("SAML_UPGRADE_REQUIRED",1);
            }
        return false;
        }

    return true;
    }

function simplesaml_php_check()
    {
    global $simplesaml_check_phpversion,$simplesaml_php_check;
    
    // Check whether PHP version will cause an error with current SAML config
    if(!isset($simplesaml_php_check))
        {
        // Check if not already checked previously
        $simplesaml_php_check = simplesaml_config_check() || version_compare(phpversion(), $simplesaml_check_phpversion, '<');
        }
    return $simplesaml_php_check;
    }


/**
 * Check that the SimpleSAMLphp has been configured.
 * This is done by either:-
 * a) Adding config, authsources and metadata files manually to the configured lib folder ($simplesaml_lib_path) or
 * b) By setting the options in the $simplesamlconfig variable and then enabling the
 * plugin option 'Use ResourceSpace configuration to set SP configuration and metadata'
 *
 * @return boolean
 */
function simplesaml_is_configured()
	{
    global $simplesamlconfig, $simplesaml_rsconfig;
    if(($simplesaml_rsconfig && !isset($simplesamlconfig))
         ||
        (!$simplesaml_rsconfig && !(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        )
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
    return true;
    }


/**
 * Generate a key/certificate pair
 * 
 * @param  array $dn    Array of certificate attributes with named indexes as below
 *                      - "countryName"
 *                      - "stateOrProvinceName"
 *                      - "localityName"
 *                      - "organizationName"
 *                      - "commonName"
 *                      - "emailAddress"
 * 
 *
 * @return array      Array containing paths to private key (.pem) and certificate (.crt) files
 */
function simplesaml_generate_keypair($dn)
	{
    global $storagedir;
    // Generate key pair
    $privkey = openssl_pkey_new(array(
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ));

    // Generate CSR and certificate
    $csr = openssl_csr_new($dn, $privkey, array('digest_alg' => 'AES-128-CBC'));
    $x509 = openssl_csr_sign($csr, null, $privkey, $days=3650, array('digest_alg' => 'AES-128-CBC'));
    
    // Save key and certificate
    $pempath = $storagedir . "/system/" . uniqid("saml_") . ".pem";
    $crtpath = $storagedir . "/system/" . uniqid("saml_") . ".crt";
    openssl_x509_export_to_file($x509, $crtpath);
    openssl_pkey_export_to_file($privkey, $pempath);
    
    return array(
        'privatekey' => $pempath,
        'certificate' => $crtpath
        );
	}

/**
 * Get the name of the saml sp to use
 *
 * @return string
 */
function get_saml_sp_name()
    {
    global $simplesaml_sp, $safe_sp, $simplesaml_rsconfig, $simplesamlconfig;
    if($safe_sp != "")
        {
        return $safe_sp;
        }

    $default_sp_name = "resourcespace-sp";
    $safe_sp = "";
    if(!$simplesaml_rsconfig || (isset($simplesamlconfig["authsources"]) && is_array($simplesamlconfig["authsources"])))
        {
        // If SAML has been configured we need to ensure that defined SP is valid
        $use_error_exception_cache = $GLOBALS["use_error_exception"] ?? false;
        $GLOBALS["use_error_exception"] = true;
        try {
            $as = new SimpleSAML\Auth\Simple($simplesaml_sp);
            $as->getAuthSource();
            }
        catch(exception $e)
            {
            // Invalid SP name, use default
            $simplesaml_sp = $default_sp_name;
            }
        $GLOBALS["use_error_exception"] = $use_error_exception_cache;
        }
    else
        {
        $simplesaml_sp = $default_sp_name;
        }
    $safe_sp = $simplesaml_sp;
    return $safe_sp;
    }