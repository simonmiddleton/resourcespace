<?php

        
function HookWinauthLoginLoginformlink()
    {
    // Add a link to login.php, which is still used if $wordpress_sso_allow_standard_login is set to true
    global $baseurl_short, $lang, $winauth_enable, $default_home_page;
    if($winauth_enable)
        {
        $url = urldecode(getval("url",""));
        $redirecturl = (trim($url) != "/" && trim($url) != "") ? $url : "pages/" . $default_home_page;
        $winauthurl = generateURL($baseurl_short, array("url" => $redirecturl,"winauth_login" => "true"));
        echo "<br/><a href='" . $winauthurl . "' >" . LINK_CARET . $lang["winauth_use_win_login"] . "</a>";
        }
    }

function HookWinauthLoginPostlogout()
    {
    global $baseurl;
    // Clear the cookie that prevents password changing
    rs_setcookie("winauth_user", "", time() - 3600, "", "", substr($baseurl,0,5)=="https", true);   
    }