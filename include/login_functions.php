<?php
/**
 * Performs the login using the global $username, and $password. Since the "externalauth" hook
 * is allowed to change the credentials later on, the $password_hash needs to be global as well.
 *
 * @return array Containing the login details ('valid' determines whether or not the login succeeded).
 */
function perform_login($loginuser="",$loginpass="")
	{
    global $scramble_key, $lang, $max_login_attempts_wait_minutes, $max_login_attempts_per_ip, $max_login_attempts_per_username,
    $global_cookies, $username, $password, $password_hash, $session_hash, $usergroup;

    $result = [];
    $result['valid'] = false;

    debug(sprintf('q10529: [line=%s fct=%s] %s = %s', __LINE__, __FUNCTION__, 'password', json_encode($password)));

    if(trim($loginpass) != "")
        {
        $password = trim($loginpass); 
        debug(sprintf('q10529: [line=%s fct=%s] %s = %s', __LINE__, __FUNCTION__, 'password', json_encode($password)));
        }
    if(trim($loginuser) != "")
        {
        $username = trim($loginuser); 
        }

    // Get user record
    $user_ref = get_user_by_username($username);
    $user_data = ($user_ref !== false ? get_user($user_ref) : []);
    if(empty($user_data))
        {
        $result['error'] = $lang["loginincorrect"];
        return $result;
        }

    // Prevent hashes being entered directly while still supporting direct entry of plain text passwords (for systems that 
    // were set up prior to MD5 password encryption was added). If a special key is sent, which is the md5 hash of the 
    // username and the secret scramble key, then allow a login using the MD5 password hash as the password. This is for 
    // the 'log in as this user' feature.
    $impersonate_user = (getval('userkey', '') === md5($username . $scramble_key));
    if(!$impersonate_user && $password === $user_data['password'])
        {
        $result['error'] = $lang["loginincorrect"];
        return $result;
        }

    $rs_password_verify = rs_password_verify("RS{$username}{$password}", $user_data['password']);
    if($rs_password_verify)
        {
        $password_hash_info = get_password_hash_info();
        $algo = $password_hash_info['algo'];
        $options = $password_hash_info['options'];

        if(password_needs_rehash($user_data['password'], $algo, $options))
            {
            $password_hash = rs_password_hash("RS{$username}{$password}");
            sql_query(sprintf("UPDATE user SET `password` = '%s' WHERE ref = '%s'", escape_check($password_hash), escape_check($user_ref)));
            debug(sprintf('q10529: [line=%s fct=%s] rehashed... %s = %s', __LINE__, __FUNCTION__, 'password_hash', json_encode($password_hash)));
            }
        else
            {
            $password_hash = $user_data['password'];
            }

        $result['valid'] = true;
        }


var_dump($rs_password_verify);
var_dump($password_hash);
die("Process stopped in file " . __FILE__ . " at line " . __LINE__);

	if (strlen($password)!=64)
		{
		# Provided password is not a hash, so generate a hash.
		$password_hash=hash('sha256', md5("RS" . $username . $password));				
        debug(sprintf('q10529: [line=%s fct=%s] %s = %s', __LINE__, __FUNCTION__, 'password_hash', json_encode($password_hash)));
		}
	else
		{
		$password_hash=$password;
        debug(sprintf('q10529: [line=%s fct=%s] %s = %s', __LINE__, __FUNCTION__, 'password_hash', json_encode($password_hash)));
		}

	// ------- Automatic migration of md5 hashed or plain text passwords to SHA256 hashed passwords ------------
	// This is necessary because older systems being upgraded may still have passwords stored using md5 hashes or even possibly stored in plain text.
	// Updated March 2015 - select password_reset_hash to force dbstruct that will update password column varchar(100) if not already
	$accountstoupdate=sql_query("select username, password, password_reset_hash from user where length(password)<>64");
	foreach($accountstoupdate as $account)
		{
		$oldpassword=$account["password"];
		if(strlen($oldpassword)!=32){$oldpassword=md5("RS" . $account["username"] . $oldpassword);} // Needed if we have a really old password, or if password has been manually reset in db for some reason
		$new_password_hash=hash('sha256', $oldpassword);
        sql_query("update user set password='" . $new_password_hash . "' where username='".escape_check($account["username"]) . "'");
		}
	$ip=get_ip();

	# This may change the $username, $password, and $password_hash
        $externalresult=hook("externalauth","",array($username, $password)); #Attempt external auth if configured

	# Generate a new session hash.
	$session_hash=generate_session_hash($password_hash);

    # Check the provided credentials
	$valid=sql_query("select ref,usergroup,account_expires,approved from user where username='".escape_check($username)."' and password='".escape_check($password_hash)."'");
	if (count($valid)>=1)
		{
		# Account expiry
		$userref=$valid[0]["ref"];
		$usergroup=$valid[0]["usergroup"];
		$expires=$valid[0]["account_expires"];
        $approved=$valid[0]["approved"];

        if ($approved == 2)
            {
            $result['error']=$lang["accountdisabled"];
            return $result;
            }

		if ($expires!="" && $expires!="0000-00-00 00:00:00" && strtotime($expires)<=time())
			{
			$result['error']=$lang["accountexpired"];
			return $result;
			}

		$result['valid']=true;
		$result['session_hash']=$session_hash;
		$result['password_hash']=$password_hash;
		$result['ref']=$userref;

		# Update the user record.
		$session_hash_sql="session='".escape_check($session_hash)."',";

        $language = getvalescaped("language", "");

		sql_query("
            UPDATE user
               SET {$session_hash_sql}
                   last_active = NOW(),
                   login_tries = 0,
                   lang = '{$language}'
             WHERE ref = '{$userref}'
        ");

        // Update user local time zone (if provided)
        $get_user_local_timezone = getval('user_local_timezone', null);
        set_config_option($userref, 'user_local_timezone', $get_user_local_timezone);

		# Log this
		daily_stat("User session",$userref);
		
        log_activity(null,LOG_CODE_LOGGED_IN,$ip,"user","ref",($userref!="" ? $userref :"null"),null,'',($userref!="" ? $userref :"null"));

		# Blank the IP address lockout counter for this IP
		sql_query("delete from ip_lockout where ip='" . escape_check($ip) . "'");

		return $result;
		}

	# Invalid login
	if(isset($externalresult["error"])){$result['error']=$externalresult["error"];} // We may have been given a better error to display
        else {$result['error']=$lang["loginincorrect"];}

  hook("loginincorrect");

	# Add / increment a lockout value for this IP
	$lockouts=sql_value("select count(*) value from ip_lockout where ip='" . escape_check($ip) . "' and tries<'" . $max_login_attempts_per_ip . "'","");

	if ($lockouts>0)
		{
		# Existing row with room to move
		$tries=sql_value("select tries value from ip_lockout where ip='" . escape_check($ip) . "'",0);
		$tries++;
		if ($tries==$max_login_attempts_per_ip)
			{
			# Show locked out message.
			$result['error']=str_replace("?",$max_login_attempts_wait_minutes,$lang["max_login_attempts_exceeded"]);
			}
		# Increment
		sql_query("update ip_lockout set last_try=now(),tries=tries+1 where ip='" . escape_check($ip) . "'");
		}
	else
		{
		# New row
		sql_query("delete from ip_lockout where ip='" . escape_check($ip) . "'");
		sql_query("insert into ip_lockout (ip,tries,last_try) values ('" . escape_check($ip) . "',1,now())");
		}

	# Increment a lockout value for any matching username.
	$ulocks=sql_query("select ref,login_tries,login_last_try from user where username='".escape_check($username)."'");
	if (count($ulocks)>0)
		{
		$tries=$ulocks[0]["login_tries"];
		if ($tries=="") {$tries=1;} else {$tries++;}
		if ($tries>$max_login_attempts_per_username) {$tries=1;}
		if ($tries==$max_login_attempts_per_username)
			{
			# Show locked out message.
			$result['error']=str_replace("?",$max_login_attempts_wait_minutes,$lang["max_login_attempts_exceeded"]);
			}
		sql_query("update user set login_tries='$tries',login_last_try=now() where username='$username'");
		}

	return $result;
	}

	
function generate_session_hash($password_hash)
	{
	# Generates a unique session hash
	global $randomised_session_hash,$scramble_key;
	
	if ($randomised_session_hash)
		{
		# Completely randomised session hashes. May be more secure, but allows only one user at a time.
		while (true)
			{
			$session=md5(rand() . microtime());
			if (sql_value("select count(*) value from user where session='" . escape_check($session) . "'",0)==0) {return $session;} # Return a unique hash only.
			}	
		}
	else
		{
		# Session hash is based on the password hash and the date, so there is one new session hash each day. Allows two users to use the same login.
		$suffix="";
		while (true)
			{
			$session=md5($scramble_key . $password_hash . date("Ymd") . $suffix);
			if (sql_value("select count(*) value from user where session='" . escape_check($session) . "' and password<>'" . escape_check($password_hash) . "'",0)==0) {return $session;} # Return a unique hash only.
			$suffix.="."; # Extremely unlikely case that this was not a unique session (hash collision) - alter the string slightly and try again.
			}	
		}	
		
	}

/**
* Set login cookies
* 
* @param integer $user             User ref
* @param string  $session_hash     User session hash
* @param string  $language         Language code (e.g en)
* @param boolean $user_preferences Set colour theme from user preferences
* 
* @return void
*/
function set_login_cookies($user, $session_hash, $language = "", $user_preferences = true)
    {
    global $baseurl, $baseurl_short, $allow_keep_logged_in, $default_res_types, $language;
    $expires=0;
    if((string)(int)$user!=(string)$user || $user < 1)
        {
        debug("set_login_cookies() - invalid paramters passed : " . func_get_args());
        return false;
        }
    if ($allow_keep_logged_in && getval("remember","")!="") {$expires = 100;} # remember login for 100 days
            
    if($language != "")
        {
        # Store language cookie
        rs_setcookie("language", $language, 1000); # Only used if not global cookies
        rs_setcookie("language", $language, 1000, $baseurl_short . "pages/");
        }
        
    # Set the session cookie. Do this for all paths that nay set the cookie as otherwise we can end up with a valid cookie at e.g. pages/team or pages/ajax
    rs_setcookie("user", "", 0, $baseurl_short);
    rs_setcookie("user", "", 0, $baseurl_short . "pages");
    rs_setcookie("user", "", 0, $baseurl_short . "pages/team");
    rs_setcookie("user", "", 0, $baseurl_short . "pages/admin");
    rs_setcookie("user", "", 0, $baseurl_short . "pages/ajax");

    # Set user cookie, setting secure only flag if a HTTPS site, and also setting the HTTPOnly flag so this cookie cannot be probed by scripts (mitigating potential XSS vuln.)	
    rs_setcookie("user", $session_hash, $expires, $baseurl_short, "", substr($baseurl,0,5)=="https", true);

    # Set default resource types
    rs_setcookie('restypes', $default_res_types);

    $userpreferences = ($user_preferences) ? sql_query("SELECT user, `value` AS colour_theme FROM user_preferences WHERE user = '" . escape_check($user) . "' AND parameter = 'colour_theme';") : FALSE;
    $userpreferences = ($userpreferences && isset($userpreferences[0])) ? $userpreferences[0]: FALSE;
    if($userpreferences && isset($userpreferences["colour_theme"]) && $userpreferences["colour_theme"]!="" && (!isset($_COOKIE["colour_theme"]) || $userpreferences["colour_theme"]!=$_COOKIE["colour_theme"]))
        {
        rs_setcookie("colour_theme", $userpreferences["colour_theme"],100, "/", "", substr($baseurl,0,5)=="https", true);
        }
    }

/**
* ResourceSpace password hashing
* 
* @uses password_hash - @see https://www.php.net/manual/en/function.password-hash.php
* 
* @param string $password Password
* 
* @return string Password hash
*/
function rs_password_hash(string $password)
    {
    $phi = get_password_hash_info();
    $algo = $phi['algo'];
    $options = $phi['options'];
    return password_hash($password, $algo, $options);
    }

/**
* ResourceSpace verify password
* 
* @param string $password Password
* @param string $hash     Password hash
* 
* @return boolean
*/
function rs_password_verify(string $password, string $hash)
    {
    $hash_v1 = md5($password);
    $hash_v2 = hash('sha256', $hash_v1);

    // Most common case: hash is at version 3 (ie. hash generated using password_hash from PHP)
    if(password_verify($password, $hash))
        {
        return true;
        }
    else if($hash_v2 === $hash)
        {
        return true;
        }
    else if($hash_v1 === $hash)
        {
        return true;
        }
    // Plain text password hash - when passwords were not hashed at all (very old code - should really not be the case anymore)
    else if($password === $hash)
        {
        return true;
        }

    return false;
    }

/**
* ResourceSpace check if password hash needs rehashing. For old password hashes (e.g plain text or MD5 or SHA256 of MD5,
* this means generating a new hash with rs_password_hash(). For password hashes that already use it, this checks the password
* hash configuration (algorithm and hash options) and updates the hash based on new configuration, if required.
* 
* @uses password_needs_rehash - @see https://www.php.net/manual/en/function.password-needs-rehash.php
* 
* @param string $password Password
* @param string $hash     Password hash
* 
* @return string Password hash
*/
function rs_password_needs_rehash(string $password, string $hash)
    {
    $phi = get_password_hash_info();
    $algo = $phi['algo'];
    $options = $phi['options'];

    if(password_needs_rehash($hash, $algo, $options))
        {
        return rs_password_hash($password);
        }

    return $hash;
    }

/**
* Helper function to get the password hash information (algorithm and options) from the global scope.
* 
* @return array
*/
function get_password_hash_info()
    {
    return [
        'algo' => ($GLOBALS['password_hash_info']['algo'] ?? PASSWORD_BCRYPT),
        'options' => ($GLOBALS['password_hash_info']['options'] ?? ['cost' => 12])
    ];
    }
