<?php

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
    global $baseurl_short, $pagename, $winauth_prefer_normal;
    
    $userinit = getval("winauth_login","") != "";
    
    if (!isset($_SERVER["AUTH_USER"]) || $_SERVER["AUTH_USER"] == "")
        {
        if($userinit)
            {
            $redirecturl = generateURL($baseurl_short. "login.php", array("error"=> ($userinit ? "winauth_nouser" : "")));    
            redirect($redirecturl);
            exit();
            }
        return false;
        }
    $url = urldecode(getval("url",""));
    
    if(trim($url) == "/" || trim($url) == "")
        {
        // Not at login page, get current URL
        $url = $_SERVER["REQUEST_URI"];
        $url=str_replace("ajax","ajax_disabled",$url);
        }
    $redirecturl = generateURL($baseurl_short . "plugins/winauth/pages/secure/winauth.php", array("url"=>$url,"winauth_login"=> ($userinit ? "true" : "")));
    debug("winauth: redirect to : " . $redirecturl);
    redirect($redirecturl);
    return false;
    }

