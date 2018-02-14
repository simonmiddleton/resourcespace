<?php

include_once dirname(__FILE__) . '/../include/winauth_functions.php';

function HookWinauthAllInitialize()
    {
    global $winauth_enable,$allow_password_change, $delete_requires_password;
    
    // If not enabled OR has a user cookie and not specified to login with Windows
	if (!$winauth_enable || (isset($_COOKIE["user"]) && getval("winauth_login","")==""))
        {
        return false;
        }
    
    if(WinauthIsAuthenticated())
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
        global $winauth_enable, $winauth_prefer_normal, $session_hash, $username, $user_select_sql, $baseurl_short, $winauth_domains, $lang, $user_preferences;

        // If not enabled OR has a user cookie and not specified to login with Windows
        if (!$winauth_enable || (isset($_COOKIE["user"]) && getval("winauth_login","") == ""))
            {
            return false;
            }
            
        if($winauth_prefer_normal && getval("winauth_login","") == "")
            {
            return false;
            }
        
        WinauthAuthenticate();
        
        return false;
        }


