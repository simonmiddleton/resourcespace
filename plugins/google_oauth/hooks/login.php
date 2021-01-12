<?php
function HookGoogle_oauthLoginLoginformlink()
    {
    global $baseurl, $lang;
    ?>
    <br/>
    <a href="<?php echo GOOGLE_OAUTH_REDIRECT_URI; ?>"><i class="fab fa-fw fa-google"></i>&nbsp;<?php echo $lang['google_oauth_log_in_with_google']; ?></a>
    <?php
    return;
    }


function HookGoogle_oauthLoginPostlogout()
    {
    google_oauth_signout();

    return;
    }


function HookGoogle_oauthLoginPostlogout2()
    {
    global $baseurl;

    if('' != getval('logout', '') && google_oauth_is_authenticated())
        {
        google_oauth_signout();
        
        // NOTE: to keep it consistent with other authentication plugins (i.e simplesaml - SSO) we are signing out
        // the user completely from their Google account.
        header("Location: https://accounts.google.com/Logout");
        exit();
        }

    return;
    }
