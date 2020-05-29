<?php
#
# winauth login page - logs the user in if Windows authentication is enabled
#
include dirname(__FILE__) . '/../../../../include/db.php';
include_once dirname(__FILE__) . '/../../include/winauth_functions.php';
   
$session_hash="";
$url = urldecode(getval("url",""));
$redirecturl = (trim($url) != "/" && trim($url) != "") ? $url : "pages/" . $default_home_page;
$winuser = WinauthGetUser();

if(count($winauth_domains) > 0 && !in_array($winuser['domain'], $winauth_domains))
    {
    // Invalid domain
    return false;
    }

if($winuser['user'] != "")
    {
    $username = $winuser['user'];
    }

# Allow login
$userref = sql_value("select ref value from user where username='" . escape_check($username) . "' and approved=1",0);
if($userref != 0)
    {
    include_once dirname(__FILE__) . '/../../../../include/login_functions.php';
    $ip=get_ip();
    
    # Generate a new session hash.
    $session_hash = generate_session_hash('sha256', md5("RS" . $username . "WINAUTH"));
    
    # Update the user record.
    sql_query("update user set session='" . escape_check($session_hash) . "', last_active = NOW() where ref='$userref'"); 

    # Log this
    daily_stat("User session",$userref);
    
    log_activity(null,LOG_CODE_LOGGED_IN,$ip,"user","ref",$userref,null,'',$userref);

    # Blank the IP address lockout counter for this IP
    sql_query("delete from ip_lockout where ip='" . escape_check($ip) . "'");
    
    set_login_cookies($userref, $session_hash, "", $user_preferences, "/");
    
    // Set cookie to disable password change and requirement to re-enter password
    rs_setcookie("winauth_user", "true", 0, "", "", substr($baseurl,0,5)=="https", true);
    
    $redirecturl = $baseurl_short . urldecode($url);
    $redirecturl = str_replace("winauth_login=true","",$redirecturl);
    redirect($redirecturl);   
    exit();
    }
else
    {
    $userinit = getval("winauth_login","") != ""; 
    $redirecturl = generateURL($baseurl_short. "login.php", array("url"=>$url,"winauth_login"=>"true", "error"=> ($userinit ? "winauth_nouser" : "")));    
    redirect($redirecturl);
    exit();
    }   

