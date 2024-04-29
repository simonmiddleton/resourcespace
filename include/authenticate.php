<?php
include_once __DIR__ . '/login_functions.php';

debug("[authenticate.php] Reached authenticate page...");
# authenticate user based on cookie

$valid=true;
$autologgedout=false;
$nocookies=false;
$is_authenticated=false;

if (array_key_exists("user",$_COOKIE) || array_key_exists("user",$_GET) || isset($anonymous_login) || hook('provideusercredentials'))
    {
    debug("[authenticate.php] Attempting to resolve user session...");

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
        $session_hash=$_GET["user"];
        }
    elseif (array_key_exists("user",$_COOKIE))
        {
        $session_hash=$_COOKIE["user"];
        }
    elseif (isset($anonymous_login))
        {
        $username=$anonymous_login;
        $session_hash="";
        $rs_session=get_rs_session_id(true);
        }  

    // Automatic anonymous login, do not require session hash.
    $user_select_sql = new PreparedStatementQuery();
    if(isset($anonymous_login) && $username == $anonymous_login)
        {
        $user_select_sql->sql = "u.username = ? AND usergroup IN (SELECT ref FROM usergroup)";
        $user_select_sql->parameters = ["s",$username];
        }
    else
        {
        $user_select_sql->sql = "u.session=?";
        $user_select_sql->parameters = ["s",$session_hash];            
        }

    hook('provideusercredentials');

    $userdata = validate_user($user_select_sql, true); // validate user and get user details 

    if(count($userdata) > 0)
        {
        debug("[authenticate.php] User valid!");

        $valid = true;
        setup_user($userdata[0]);

        if ($password_expiry>0 && !checkperm("p") && $allow_password_change && in_array($pagename, array("user_change_password","index","collections")) === false && strlen(trim((string) $userdata[0]["password_last_change"]))>0 && getval("modal","")=="")
            {
            # Redirect the user to the password change page if their password has expired.
            $last_password_change=time()-strtotime((string) $userdata[0]["password_last_change"]);
        if ($last_password_change>($password_expiry*60*60*24))
            {
            debug("[authenticate.php] Redirecting user to change password...");

            ?>
            <script>
            top.location.href="<?php echo $baseurl_short?>pages/user/user_change_password.php?expired=true";
            </script>
            <?php
            }
            }
        
        if (
            !isset($system_login) 
            && strlen(trim((string)$userdata[0]["last_active"])) > 0
            && $userdata[0]["idle_seconds"]>($session_length*60)
            ) {
                debug("[authenticate.php] Session length expired!");
                # Last active more than $session_length mins ago?
                $al="";if (isset($anonymous_login)) {$al=$anonymous_login;}
                
                if ($session_autologout && $username!=$al) # If auto logout enabled, but this is not the anonymous user, log them out.
                    {
                    debug("[authenticate.php] Autologging out user.");
                    # Reached the end of valid session time, auto log out the user.
                    
                    # Remove session
                    ps_query("update user set logged_in = 0, session = '' where ref= ?",array("i",$userref));
                    hook("removeuseridcookie");
                    # Blank cookie / var
                    rs_setcookie("user", "", -1, "", "", substr($baseurl,0,5)=="https", true);                  
                    rs_setcookie("user", "", -1, "/pages", "", substr($baseurl,0,5)=="https", true);
                    unset($username);
        
                    if (isset($anonymous_login))
                        {
                        # If the system is set up with anonymous access, redirect to the home page after logging out.
                        redirect("pages/home.php");
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
    $email=trim(getval("email","")) ;
    $username="anonymous" . ps_value("select max(ref)+1 value from user", array(), 0); # Make up a username.
    $password=make_password();
    $password_hash = rs_password_hash("RS{$username}{$password}");

    # Create the user
    ps_query("insert into user (username,password,fullname,email,usergroup,approved) values (?, ?, ?, '', ?, 1)",array("s",$username,"s",$password_hash,"s",$username,"i",$anonymous_autouser_group));
    $new = sql_insert_id();
   
    $login_data = perform_login();
    rs_setcookie("user", $session_hash, 100, "", "", substr($baseurl,0,5)=="https", true);

    // Setup the user
    $login_session_hash = (isset($login_data['session_hash']) ? $login_data['session_hash'] : '');
    $user_select_sql = new PreparedStatementQuery();
    $user_select_sql->sql = "u.session=?";
    $user_select_sql->parameters = ["s",$login_session_hash];
    $user_data = validate_user($user_select_sql, true);

    $valid = false;
    if(0 < count($user_data))
        {
        $valid = true;
        setup_user($user_data[0]);
        }
    }
    
if (!$valid && !isset($system_login))
    {
    debug("[authenticate.php] User not valid!");
    $_SERVER['REQUEST_URI'] = ( isset($_SERVER['REQUEST_URI']) ?
    $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'] . ( isset($_SERVER
    ['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    $path = $_SERVER["REQUEST_URI"];
    debug("[authenticate.php] path = $path");
    
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
        debug("[authenticate.php] Redirecting to $url");
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
if($terms_login && 0 == $useracceptedterms && in_array($pagename, array("reload_links","browsebar_js","css_override","category_tree_lazy_load","message","terms","collections","login","user_change_password")) === false )
    {
    redirect('pages/terms.php?noredir=true&url=' . urlencode("pages/home.php"));
    }

if (isset($_SERVER["HTTP_USER_AGENT"]))
    {
    $last_browser=substr($_SERVER["HTTP_USER_AGENT"],0,250);
    }
else
    { 
    $last_browser="unknown";
    }

// don't update this table if the System is doing its own operations
if (!isset($system_login)){
    ps_query("update user set lang = ?, last_active = now(), logged_in = 1, last_ip = ? , last_browser = ? where ref = ?", array("s",$language,"s",get_ip(),"s",$last_browser,"s",$userref), false, -1, true, 0);
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
        $site_text_query = "
				SELECT `name`,
				       `text`,
				       `page` 
				  FROM site_text 
				 WHERE language = ?
				   AND specific_to_group = ?
			";
        $parameters=array
            (
            "s",$language,
            "i",$usergroup
            );

        if ($pagename!="admin_content") // Load all content on the admin_content page to allow management.
            {
            $site_text_query.="AND (page = ? OR page = 'all' OR page = '' " .  (($pagename=="dash_tile")?" OR page = 'home'":"") . ")";
            $parameters[]="s";$parameters[]=$pagename;
            }

        $results = ps_query($site_text_query,$parameters,"sitetext",-1,true,0);

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
    }   /* end replacesitetextloader */
    
    $plugins=[];
    register_group_access_plugins($plugins,$usergroup);



// Load user config options
process_config_options($userref);

// Once system wide/user preferences and user group config overrides have loaded, any config based dependencies should be checked and loaded.
if(!$disable_geocoding)
    {
    include_once __DIR__ . '/map_functions.php';
    }

hook('handleuserref','',array($userref));

// Set a trace ID which can be used to correlate events within this request (requires $debug_extended_info)
$trace_id_components = [
    getmypid(),
    $_SERVER['REQUEST_TIME_FLOAT'],
    $GLOBALS['pagename'], # already set in boot.php
    http_build_query($_GET),
    $GLOBALS['userref'],
];
$GLOBALS['debug_trace_id'] = generate_trace_id($trace_id_components);
debug(sprintf(
    'User %s (ID %s) set its debug_trace_id to "%s" (components: %s)',
    $GLOBALS['username'],
    $GLOBALS['userref'],
    $GLOBALS['debug_trace_id'],
    json_encode($trace_id_components)
));

$is_authenticated=true;

// Check CSRF Token
$csrf_token = getval($CSRF_token_identifier, "");
if(
    $_SERVER["REQUEST_METHOD"] === "POST"
    && !isValidCSRFToken($csrf_token, $usersession)
    && !(isset($anonymous_login) && $username == $anonymous_login)
    && !defined("API_CALL")
)
    {
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
elseif (defined('API_CALL') && $_SERVER['REQUEST_METHOD'] === 'POST' && !isValidCSRFToken($csrf_token, $usersession))
    {
    ajax_send_response(
        400,
        ajax_response_fail(ajax_build_message("{$lang['error-csrf-verification']}: {$lang['error_invalid_input']}"))
    );
    }
