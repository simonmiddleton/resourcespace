<?php

function WinauthIsAuthenticated()
    {
    if (isset($_SERVER["AUTH_USER"]) && $_SERVER["AUTH_USER"] != "")
        {
        return true; 
        }
        
    return false;
    }

function WinauthGetUser()
    {
    if (isset($_SERVER["AUTH_USER"]) && $_SERVER["AUTH_USER"] != "")
        {
        if(strpos($_SERVER["AUTH_USER"], "\\") !== false)
            {
            $userparts = explode("\\",$_SERVER["AUTH_USER"]);
            $domain = $userparts[0];
            $user = $userparts[1];
            }
        else
            {
            $domain = '';
            $user = $_SERVER["AUTH_USER"];
            }
        }
    return array('domain' => $domain,'user' => $user);
    }
    
function WinauthAuthenticate()
    {
    global $baseurl_short;
    $userinit = getval("winauth_login","") != "";
    redirect($baseurl_short . "plugins/winauth/pages/secure/winauth.php" . ($userinit ? "?winauth_login=true" : ""));
    return false;
    }

