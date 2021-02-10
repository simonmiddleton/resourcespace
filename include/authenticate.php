<?php
# authenticate user based on cookie

$valid=true;
$autologgedout=false;
$nocookies=false;
$is_authenticated=false;

if (array_key_exists("user",$_COOKIE) || array_key_exists("user",$_GET) || isset($anonymous_login) || hook('provideusercredentials'))
    {
    $username="";
	// Resolve anonymous login user if it is configured at domain level
	if(isset($anonymous_login) && is_array($anonymous_login))
		{
		foreach($anonymous_login as $key => $val)
			{
			if($baseurl==$key){$anonymous_login=$val;}
			}
		}
	// Establish session hash
	if (array_key_exists("user",$_GET))
		{
	    $session_hash=escape_check($_GET["user"]);
		}
	elseif (array_key_exists("user",$_COOKIE))
  		{
	  	$session_hash=escape_check($_COOKIE["user"]);
	  	}
	elseif (isset($anonymous_login))
		{
		$username=$anonymous_login;
		$session_hash="";
		$rs_session=get_rs_session_id(true);
		}

    $user_select_sql = "u.session='{$session_hash}'";

    // Automatic anonymous login, do not require session hash.
    if(isset($anonymous_login) && $username == $anonymous_login)
        {
        $user_select_sql = "AND u.username = '{$username}' AND usergroup IN (SELECT ref FROM usergroup)";
        }

	hook('provideusercredentials');

    $userdata = validate_user($user_select_sql, true); // validate user and get user details 

    if(count($userdata) > 0)
        {
        $valid = true;
        setup_user($userdata[0]);

        if ($password_expiry>0 && !checkperm("p") && $allow_password_change && $pagename!="user_change_password" && $pagename!="index" && $pagename!="collections" && strlen(trim($userdata[0]["password_last_change"]))>0 && getval("modal","")=="")
        	{
        	# Redirect the user to the password change page if their password has expired.
	        $last_password_change=time()-strtotime($userdata[0]["password_last_change"]);
		if ($last_password_change>($password_expiry*60*60*24))
			{
			?>
			<script>
			top.location.href="<?php echo $baseurl_short?>pages/user/user_change_password.php?expired=true";
			</script>
			<?php
			}
        	}
        
        if (!isset($system_login) && strlen(trim($userdata[0]["last_active"]))>0)
        	{
	        if ($userdata[0]["idle_seconds"]>($session_length*60))
	        	{
          	    # Last active more than $session_length mins ago?
				$al="";if (isset($anonymous_login)) {$al=$anonymous_login;}
				
				if ($session_autologout && $username!=$al) # If auto logout enabled, but this is not the anonymous user, log them out.
					{
					# Reached the end of valid session time, auto log out the user.
					
					# Remove session
					sql_query("update user set logged_in=0,session='' where ref='$userref'");
					hook("removeuseridcookie");
					# Blank cookie / var
					rs_setcookie("user", "", time() - 3600, "", "", substr($baseurl,0,5)=="https", true);					
					rs_setcookie("user", "", time() - 3600, "/pages", "", substr($baseurl,0,5)=="https", true);
					unset($username);
		
					if (isset($anonymous_login))
						{
						# If the system is set up with anonymous access, redirect to the home page after logging out.
						redirect("pages/" . $default_home_page);
						}
					else
						{
						$valid=false;
						$autologgedout=true;
						}
					}
				else
	        		{
		        	# Session end reached, but the user may still remain logged in.
			        # This is a new 'session' for the purposes of statistics.
					daily_stat("User session",$userref);
					}
				}
			}
        }
        else {$valid=false;}
    }
else
    {
    $valid=false;
    $nocookies=true;
    
    # Set a cookie that we'll check for again on the login page after the redirection.
    # If this cookie is missing, it's assumed that cookies are switched off or blocked and a warning message is displayed.
    rs_setcookie('cookiecheck', 'true', 0, '/');
    hook("removeuseridcookie");
    }

if (!$valid && isset($anonymous_autouser_group))
    {
    # Automatically create a users for anonymous access, and place them in a user group.
    
	# Prepare to create the user.
	$email=trim(getvalescaped("email","")) ;
    $username="anonymous" . sql_value("select max(ref)+1 value from user",0); # Make up a username.
	$password=make_password();
	$password_hash = hash('sha256', md5('RS' . $username . $password));
    
    # Create the user
	sql_query("insert into user (username,password,fullname,email,usergroup,approved) values ('" . $username . "','" . $password_hash . "','" . $username . "','','" . $anonymous_autouser_group . "',1)");
	$new = sql_insert_id();
   
    include_once ("login_functions.php");
    $login_data = perform_login();
    rs_setcookie("user", $session_hash, 100, "", "", substr($baseurl,0,5)=="https", true);

    // Setup the user
    $login_session_hash = (isset($login_data['session_hash']) ? escape_check($login_data['session_hash']) : '');
    $user_data          = validate_user("u.session = '{$login_session_hash}'", true);

    $valid = false;
    if(0 < count($user_data))
        {
        $valid = true;

        setup_user($user_data[0]);
        }
    }
    
if (!$valid && !isset($system_login))
    {
	$_SERVER['REQUEST_URI'] = ( isset($_SERVER['REQUEST_URI']) ?
	$_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'] . (( isset($_SERVER
	['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')));
    $path = $_SERVER["REQUEST_URI"];
    
    if(strpos($path,"/ajax") !== false)
        {
        if(isset($_COOKIE["user"]))
            {
            http_response_code(401);
            exit($lang['error-sessionexpired']);
            }
        else
            {
            http_response_code(403);
            exit($lang['error-permissiondenied']);
            }
        }
    
    $path=str_replace("ajax=","ajax_disabled=",$path);# Disable forwarding of the AJAX parameter if this was an AJAX load, otherwise the redirected page will be missing the header/footer.
    
    $redirparams = array();

    $redirparams["url"]         = isset($anonymous_login) ? "" : $path;
    $redirparams["auto"]        = $autologgedout ? "true" : "";
    $redirparams["nocookies"]   = $nocookies ? "true" : "";
    
    if(strpos($path, "ajax") !== false || getval("ajax","") != "")
        {
        // Perform a javascript redirect as may be directly loading content directly into div.
        $url = generateURL($baseurl . "/login.php",$redirparams);
        ?>
        <script>
        top.location.href="<?php echo $url ?>";
        </script>
        <?php
        exit();
        }
    else
        {
        $url = generateURL($baseurl . "/login.php",$redirparams);
        redirect($url);
        exit();
        }
    }   

# Handle IP address restrictions
$ip=get_ip();
if (isset($ip_restrict_group)){
	$ip_restrict=$ip_restrict_group;
	if ($ip_restrict_user!="") {$ip_restrict=$ip_restrict_user;} # User IP restriction overrides the group-wide setting.
	if ($ip_restrict!="")
	{
	$allow=false;

	if (!hook('iprestrict'))
		{
		$allow=ip_matches($ip, $ip_restrict);
		}

	if (!$allow)
		{
		if ($iprestrict_friendlyerror)
			{
			exit("Sorry, but the IP address you are using to access the system (" . $ip . ") is not in the permitted list. Please contact an administrator.");
			}
		header("HTTP/1.0 403 Access Denied");
		exit("Access denied.");
		}
	}
}

#update activity table
global $pagename;

/*
Login terms have not been accepted? Redirect until user does so
Note: it is considered safe to show the collection bar because even if we enable login terms
      later on, when the user might have resources in it, they would not be able to do anything with them
      unless they accept terms
*/
if($terms_login && 0 == $useracceptedterms && 'login' != $pagename && 'terms' != $pagename && 'collections' != $pagename)
    {
    redirect('pages/terms.php?noredir=true&url=' . urlencode("pages/{$default_home_page}"));
    }

if (isset($_SERVER["HTTP_USER_AGENT"]))
	{
	$last_browser=escape_check(substr($_SERVER["HTTP_USER_AGENT"],0,250));
	}
else
	{ 
	$last_browser="unknown";
	}

// don't update this table if the System is doing its own operations
if (!isset($system_login)){
	sql_query("update user set lang='$language', last_active=now(),logged_in=1,last_ip='" . escape_check(get_ip()) . "',last_browser='" . $last_browser . "' where ref='$userref'",false,-1,true,0);
}

# Add group specific text (if any) when logged in.
if (hook("replacesitetextloader"))
	{
	# this hook expects $site_text to be modified and returned by the plugin	 
	$site_text=hook("replacesitetextloader");
	}
else
	{
	if (isset($usergroup))
		{
		// Fetch user group specific content.
		$site_text_query = sprintf("
				SELECT `name`,
				       `text`,
				       `page` 
				  FROM site_text 
				 WHERE language = '%s'
				   %s #pagefilter
				   AND specific_to_group = '%s';
			",
			escape_check($language),
			$pagefilter,
			$usergroup
		);
		$results = sql_query($site_text_query,"sitetext",-1,true,0);

		for($n = 0; $n < count($results); $n++)
			{
			if($results[$n]['page'] == '')
				{
				$lang[$results[$n]['name']] = $results[$n]['text'];
				$customsitetext[$results[$n]['name']] = $results[$n]['text'];
				} 
			else
				{
				$lang[$results[$n]['page'] . '__' . $results[$n]['name']] = $results[$n]['text'];
				}
			}
		}
	}	/* end replacesitetextloader */


# Load group specific plugins and reorder plugins list
$plugins= array();
$active_plugins = (sql_query("SELECT name,enabled_groups, config, config_json, disable_group_select FROM plugins WHERE inst_version>=0 ORDER BY priority","plugins"));


foreach($active_plugins as $plugin)
	{
	#Get Yaml
	$plugin_yaml_path = get_plugin_path($plugin["name"]) ."/".$plugin["name"].".yaml";
	$py="";
	$py = get_plugin_yaml($plugin_yaml_path, false);

	# Check group access and applicable for this user in the group, only if group access is permitted as otherwise will have been processed already
	if(!$py['disable_group_select'] && $plugin['enabled_groups'] != '')
		{
		$s=explode(",",$plugin['enabled_groups']);
		if (isset($usergroup) && in_array($usergroup,$s))
			{
			include_plugin_config($plugin['name'],$plugin['config'],$plugin['config_json']);
			register_plugin($plugin['name']);
			register_plugin_language($plugin['name']);
			$plugins[]=$plugin['name'];
			}
		}
	else
		{
		$plugins[]=$plugin['name'];
		}
	}	
foreach($plugins as $plugin)
	{
	hook("afterregisterplugin","",array($plugin));
	}

// Load user config options
process_config_options($userref);

hook('handleuserref','',array($userref));

$is_authenticated=true;

// Checks user has opted to see the full site view rather than
// the responsive version on a device
if(true == getvalescaped('ui_view_full_site', false))
    {
    $responsive_ui = false;
    }

// Check CSRF Token
$csrf_token = getval($CSRF_token_identifier, "");
if(
    $_SERVER["REQUEST_METHOD"] === "POST"
    && !isValidCSRFToken($csrf_token, $usersession)
    && !(isset($anonymous_login) && $username == $anonymous_login)
    && !defined("API_CALL")
)
    {
    debug("WARNING: CSRF verification failed!");

    http_response_code(400);

    if(filter_var(getval("ajax", false), FILTER_VALIDATE_BOOLEAN))
        {
        include_once dirname(__FILE__) . "/ajax_functions.php";
        $return['error'] = array(
            'title'  => $lang["error-csrf-verification"],
            'detail' => $lang["error-csrf-verification-failed"]);

        echo json_encode(array_merge($return, ajax_response_fail(ajax_build_message($lang["error-csrf-verification-failed"]))));
        exit();
        }

    exit($lang["error-csrf-verification-failed"]);
    }
