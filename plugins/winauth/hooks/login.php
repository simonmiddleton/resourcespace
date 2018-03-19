<?php

        
function HookWinauthLoginLoginformlink()
    {
    // Add a link to login.php, which is still used if $wordpress_sso_allow_standard_login is set to true
    global $baseurl_short, $lang, $winauth_enable, $default_home_page;
    if($winauth_enable)
        {
        $url = getval("url","");
        $redirpath = ($url != "") ? $baseurl_short . "?url=" . urlencode(urldecode($url)) . "&winauth_login=true" : $baseurl_short .  "pages/" . $default_home_page . "?winauth_login=true";
        
        echo "<br/><a href='" . $redirpath . "' >" . LINK_CARET . $lang["winauth_use_win_login"] . "</a>";
        }
    }
