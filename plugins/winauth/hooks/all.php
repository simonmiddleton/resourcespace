<?php

include_once dirname(__FILE__) . '/../include/winauth_functions.php';

function HookWinauthAllPreheaderoutput()
    {
    global $winauth_enable,$allow_password_change, $delete_requires_password;
   
	if ($winauth_enable && isset($_COOKIE["winauth_user"]))
        {
        // Disable password change and requirement to re-enter password
        $allow_password_change=false;
        $delete_requires_password=false;
        return true;
        }
        
    return true;
    }
        
        
function HookWinauthAllProvideusercredentials()
        {
        global $winauth_enable, $winauth_prefer_normal, $session_hash, $username, $user_select_sql, $baseurl_short, $winauth_domains, $lang, $user_preferences,$pagename,$allow_password_change, $delete_requires_password;
        debug("winauth - Provideusercredentials hook: Enabled=" . ($winauth_enable ? "TRUE" : "FALSE") . ", Page=" . $pagename . ", user cookie=" . (isset($_COOKIE["user"]) ? "TRUE" : "FALSE") . ", winauth_prefer_normal=" . ($winauth_prefer_normal ? "TRUE" : "FALSE") . ", winauth requested=" . (getval("winauth_login","") == "" ? "FALSE" : "TRUE"));
                  
         
        // If not enabled OR has a user cookie and not specified to login with Windows
        if ((!$winauth_enable)
            ||
           $pagename == "login"
            ||
           isset($_COOKIE["user"])
            ||
          ($pagename != "login" && $winauth_prefer_normal && getval("winauth_login","") == ""))
            {
            return false;
            }
        WinauthAuthenticate();
        
        return false;
        }


