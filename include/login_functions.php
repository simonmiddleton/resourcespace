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
    $result['valid'] = $valid = false;

    if(trim($loginpass) != "")
        {
        $password = trim($loginpass); 
        }
    if(trim($loginuser) != "")
        {
        $username = trim($loginuser); 
        }

    // If a special key is sent, which is the MD5 hash of the username and the secret scramble key, then allow a login 
    // using the MD5 password hash as the password. This is for the 'log in as this user' feature.
    $impersonate_user = (getval('userkey', '') === md5($username . $scramble_key));

    // Get user record
    $user_ref = get_user_by_username($username);
    $found_user_record = ($user_ref !== false);
    if($found_user_record)
        {
        $user_data = get_user($user_ref);
        }

    // User logs in
    if($found_user_record && rs_password_verify($password, $user_data['password'], ['username' => $username]))
        {
        $password_hash_info = get_password_hash_info();
        $algo = $password_hash_info['algo'];
        $options = $password_hash_info['options'];

        if(password_needs_rehash($user_data['password'], $algo, $options))
            {
            $password_hash = rs_password_hash("RS{$username}{$password}");
            if($password_hash === false)
                {
                trigger_error('Failed to rehash password!');
                }

            sql_query(sprintf("UPDATE user SET `password` = '%s' WHERE ref = '%s'", escape_check($password_hash), escape_check($user_ref)));
            }
        else
            {
            $password_hash = $user_data['password'];
            }

        $valid = true;
        }
    // An admin logs in as this user
    else if(
        $found_user_record
        && $impersonate_user
        && rs_password_verify($password, $user_data['password'], ['username' => $username, 'impersonate_user' => true])
    )
        {
        $password_hash = $user_data['password'];
        $valid = true;
        }

    $ip = get_ip();

	# This may change the $username, $password, and $password_hash
    $externalresult=hook("externalauth","",array($username, $password)); #Attempt external auth if configured

    if($valid)
        {
        $userref = $user_data['ref'];
        $usergroup = $user_data['usergroup'];
        $expires = $user_data['account_expires'];
        $approved = $user_data['approved'];

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

        $session_hash = generate_session_hash($password_hash);

        $result['valid'] = true;
        $result['session_hash'] = $session_hash;
        $result['password_hash'] = $password_hash;
        $result['ref'] = $userref;

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
        daily_stat("User session", $userref);
        log_activity(null,LOG_CODE_LOGGED_IN,$ip,"user","ref",($userref!="" ? $userref :"null"),null,'',($userref!="" ? $userref :"null"));

        # Blank the IP address lockout counter for this IP
        sql_query("DELETE FROM ip_lockout WHERE ip = '" . escape_check($ip) . "'");

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
* @return string|false Password hash or false on failure
*/
function rs_password_hash(string $password)
    {
    $phi = get_password_hash_info();
    $algo = $phi['algo'];
    $options = $phi['options'];

    // Pepper password with a known (by the application) secret.
    $hmac = hash_hmac('sha256', $password, $GLOBALS['scramble_key']);

    return password_hash($hmac, $algo, $options);
    }

/**
* ResourceSpace verify password
* 
* @param string $password Password
* @param string $hash     Password hash
* @param array  $data     Extra data required for matching hash expectations (e.g username, impersonate_user). Key is the variable name,
*                         value is the actual value for that variable.
* 
* @return boolean
*/
function rs_password_verify(string $password, string $hash, array $data)
    {
    // Prevent hashes being entered directly while still supporting direct entry of plain text passwords (for systems that 
    // were set up prior to MD5 password encryption was added). If a special key is sent, which is the MD5 hash of the 
    // username and the secret scramble key, then allow a login using the MD5 password hash as the password. This is for 
    // the 'log in as this user' feature.
    $impersonate_user = $data['impersonate_user'] ?? false;
    $hash_info = password_get_info($hash);
    $pass_info = password_get_info($password);
    $is_like_v1_hash = (mb_strlen($password) === 32);
    $is_like_v2_hash = (mb_strlen($password) === 64);
    $is_v3_hash = ($hash_info['algo'] === $pass_info['algo'] && $hash_info['algoName'] !== 'unknown');
    if(!$impersonate_user && ($is_v3_hash || $is_like_v2_hash || $is_like_v1_hash))
        {
        return false;
        }

    $RS_madeup_pass = "RS{$data['username']}{$password}";
    $hash_v1 = md5($RS_madeup_pass);
    $hash_v2 = hash('sha256', $hash_v1);

    // Most common case: hash is at version 3 (ie. hash generated using password_hash from PHP)
    if(password_verify(hash_hmac('sha256', $RS_madeup_pass, $GLOBALS['scramble_key']), $hash))
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
    // Legacy: Plain text password - when passwords were not hashed at all (very old code - should really not be the 
    // case anymore) -or- when someone resets it manually in the database
    else if($password === $hash)
        {
        return true;
        }

    return false;
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
