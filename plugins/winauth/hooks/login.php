<?php

        
function HookWinauthLoginLoginformlink()
    {
    // Add a link to login.php, which is still used if $wordpress_sso_allow_standard_login is set to true
    global $baseurl_short, $lang, $winauth_enable, $default_home_page;
    if($winauth_enable)
        {
        echo "<br/><a href='" . $baseurl_short .  "pages/" . $default_home_page. "?winauth_login=true' >" . LINK_CARET . $lang["winauth_use_win_login"] . "</a>";
        }
    }
