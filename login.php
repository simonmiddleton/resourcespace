<?php
include "include/db.php";
include_once "include/login_functions.php";

$url=getval("url","index.php");

if (is_array($url))
    {
    $url = 'index.php';
    }

$modifiedurl=hook("modifyloginurl","",array($url));
if ($modifiedurl){$url=$modifiedurl;}

$modal=getval("modal","");

if ($modal || getval("ajax","")!="")
	{
	# add the capslock lib because there's no header
	?>
	<script type="text/javascript" src="<?php echo $baseurl?>/lib/js/jquery.capslockstate.js"></script>
	<?php
	}

if (isset($anonymous_login) && $anon_login_modal && !$modal && getval("logout","")==false && !array_key_exists("username",$_POST))
	{
	$anon_login_extras="loginmodal=true&url=".urlencode($url)."&error=".urlencode(getval("error",""))."&auto=".urlencode(getval("auto",""))."&nocookies=".urlencode(getval("nocookies",""));
	
	$anon_login_redirect="/pages/".$default_home_page."?".$anon_login_extras;
	if ($use_theme_as_home)
		{
		$anon_login_redirect="/pages/themes.php?".$anon_login_extras;
		}
	if ($use_recent_as_home)
		{
		$anon_login_redirect="/pages/search.php?search=".urlencode('!last'.$recent_search_quantity)."&".$anon_login_extras;
		}
	# this shouldn't load as a unique page. go to the home page and display the login modal
	redirect($baseurl.$anon_login_redirect);
	}
        
# process log in
$error=getval("error","");
$error=isset($lang[$error]) ? $lang[$error] : "";

# Auto logged out? Set error message.
if (getval("auto","")!="") {$error=str_replace("30",$session_length,$lang["sessionexpired"]);}

# Display a no-cookies message
if (getval("nocookies","")!="" && getval("cookiecheck","")=="") {$error=$lang["nocookies"];}

if (!hook("replaceauth")) {
# First check that this IP address has not been locked out due to excessive attempts.
$ip=get_ip();
$lockouts=sql_value("select count(*) value from ip_lockout where ip='" . escape_check($ip) . "' and tries>='" . $max_login_attempts_per_ip . "' and date_add(last_try,interval " . $max_login_attempts_wait_minutes . " minute)>now()",0);

$username=getvalescaped("username","");
if (is_array($username))
    {
    redirect($baseurl . "/login.php");
    }

$username=trim($username);
if($case_insensitive_username)
    {
    $username=sql_value("select username value from user where lower(username)=lower('" . $username ."')",$username);       
    $username=escape_check($username);
    }
    
# Also check that the username provided has not been locked out due to excessive login attempts.
$ulockouts=sql_value("select count(*) value from user where username='" . $username . "' and login_tries>='" . $max_login_attempts_per_username . "' and date_add(login_last_try,interval " . $max_login_attempts_wait_minutes . " minute)>now()",0);

if ($lockouts>0 || $ulockouts>0)
	{
	$error=str_replace("?",$max_login_attempts_wait_minutes,$lang["max_login_attempts_exceeded"]);
    if ($ulockouts>0){$log_message='Account locked';}
    else {$log_message = 'IP address locked';}
    $userref = get_user_by_username($username);
    log_activity(
        $log_message,                       # Note
        LOG_CODE_FAILED_LOGIN_ATTEMPT,      # Log Code
        $ip,                                # Value New
        ($userref!="" ? "user"    : NULL),  # Remote Table
        ($userref!="" ? "last_ip" : NULL),  # Remote Column
        ($userref!="" ? $userref  : NULL),  # Remote Ref
        NULL,                               # Ref Column Override
        NULL,                               # Value Old
        ($userref!="" ? $userref : NULL));  # User
	}

# Process the submitted login
elseif (array_key_exists("username",$_POST) && getval("langupdate","")=="")
    {
    $password=trim(getvalescaped("password",""));
	$result=perform_login();
	if ($result['valid'])
		{
        set_login_cookies($result["ref"],$session_hash,$language, $user_preferences);

        # Set 'user_local_timezone' in cookie like 'user preferences page' does
        $login_lang = getval("user_local_timezone", "");        
        rs_setcookie('user_local_timezone', $login_lang, 365);

		# If the redirect URL is the collection frame, do not redirect to this as this will cause
		# the collection frame to appear full screen.
		if (strpos($url,"pages/collections.php")!==false) {$url="index.php";}

        $accepted = sql_value("SELECT accepted_terms value FROM user WHERE ref = '{$result['ref']}'", 0);
        if(0 == $accepted && $terms_login && !checkperm('p'))
            {
            $redirect_url='pages/terms.php?url=' . urlencode($url);
            }
        else{
            $redirect_url=$url;
            }
            
		if(!$modal)
			{
			redirect($redirect_url);
			}
		else
			{
			?>
			<script type="text/javascript">
				CentralSpaceLoad('<?php echo $baseurl."/".$redirect_url?>',true);
			</script>
			<?php
			}
        }
    else
        {
        sleep($password_brute_force_delay);
        
		$error=$result['error'];
        hook("dispcreateacct");
        }
    }
}

if(getval("logout", "") != "" && array_key_exists("user", $_COOKIE))
    {
    $session = escape_check($_COOKIE["user"]);

    // Check CSRF Token
    $csrf_token = getval($CSRF_token_identifier, "");
    if($_SERVER["REQUEST_METHOD"] === "POST" && !isValidCSRFToken($csrf_token, $session))
        {
        http_response_code(400);
        debug("WARNING: CSRF verification failed!");
        trigger_error($lang["error-csrf-verification-failed"]);
        }

    // Clear out special "COLLECTION_TYPE_SELECTION" collection
    $user_selection_collection = get_user_selection_collection(sql_value("SELECT ref AS `value` FROM user WHERE session = '{$session}'", null));
    if(!is_null($user_selection_collection) && count(get_collection_resources($user_selection_collection)) > 0)
        {
        remove_all_resources_from_collection($user_selection_collection);
        }

    sql_query("UPDATE user SET logged_in = 0, session = NULL, csrf_token = NULL WHERE session = '{$session}'");
    hook("removeuseridcookie");
    #blank cookie
    rs_setcookie('user', '', 0);

    # Also blank search related cookies
    rs_setcookie('search', '');
    rs_setcookie('search_form_submit', '');
    rs_setcookie('saved_offset', '');
    rs_setcookie('saved_archive', '');
    rs_setcookie('restypes', '');
    
    // Blank cookies under /pages as well
    rs_setcookie('search', '', 0, $baseurl_short . 'pages');
    rs_setcookie('saved_offset', '', 0, $baseurl_short . 'pages');
    rs_setcookie('saved_archive', '', 0, $baseurl_short . 'pages');
    rs_setcookie('restypes', '', 0, $baseurl_short . 'pages');

    unset($username);
	
	hook("postlogout");
    
    if (isset($anonymous_login))
    	{
    	# If the system is set up with anonymous access, redirect to the home page after logging out.
    	redirect("pages/".$default_home_page);
    	}
    }

hook("postlogout2");
    
if (getval("langupdate","")!="")
	{
	# Update language while remaining on this page.
    rs_setcookie("language", $language, 1000); # Only used if not global cookies
    rs_setcookie("language", $language, 1000, $baseurl_short . "pages/");    
	redirect("login.php?username=" . urlencode(getval("username","")));
	}

include "include/header.php";

include "include/login_background.php";
	
if (!hook("replaceloginform"))
    {
    ?>
    <form id="loginform" method="post" action="<?php echo $baseurl_short?>login.php" <?php if (!$login_autocomplete) { ?>autocomplete="off"<?php } ?><?php if($modal){?>onsubmit="return ModalPost(this,true,true);" <?php } ?>>
        <input type="hidden" name="langupdate" id="langupdate" value="">  
        <input type="hidden" name="url" value="<?php echo htmlspecialchars($url)?>">
        <input type="hidden" name="modal" value="<?php echo ($modal=="true"?"true":"") ?>">

        <?php $header_img_src = get_header_image(); ?>
        <div id="LoginHeader">
            <img src="<?php echo $header_img_src; ?>" class="LoginHeaderImg">
        </div>
        
        <h1><?php echo text("welcomelogin")?></h1>

        <p class="ExternalLoginLinks">
            <?php hook("loginformlink") ?> 
        </p>

        <div class="Question">
            <label for="username"><?php echo $lang["username"]?> </label>
            <input type="text" name="username" id="username" class="stdwidth" <?php if (!$login_autocomplete) { ?>autocomplete="off"<?php } ?> value="<?php echo htmlspecialchars(getval("username","")) ?>" />
            <div class="clearerleft"> </div>
        </div>
        
        <div class="Question">
            <label for="password"><?php echo $lang["password"]?> </label>
            <input type="password" name="password" id="password" class="stdwidth" <?php if (!$login_autocomplete) { ?>autocomplete="off"<?php } ?> />
             <div id="capswarning"><?php echo $lang["caps-lock-on"]; ?></div>
            <div class="clearerleft"> </div>
        </div>

        <?php if ($error!="") { ?><div class="FormIncorrect"><?php echo $error?></div><?php } ?>

        <?php if ($disable_languages==false) { ?>	
            <div class="Question HalfWidth">
                <label for="language"><?php echo $lang["language"]?></label>
                <select id="language" class="stdwidth" name="language" onChange="document.getElementById('langupdate').value='YES';document.getElementById('loginform').submit();">
                <?php reset ($languages); foreach ($languages as $key=>$value) { ?>
                    <option value="<?php echo $key?>" <?php if ($language==$key) { ?>selected<?php } ?>><?php echo $value?></option>
                <?php } ?>
                </select>
                <div class="clearerleft"> </div>
            </div> 
        <?php } ?>

        <div class="Question HalfWidth">
            <label for="user_local_timezone"><?php echo $lang["local_tz"]; ?></label>
            <select id="user_local_tz" class="stdwidth" name="user_local_timezone">
            <?php

            $user_local_timezone = getval('user_local_timezone', '');

            foreach(timezone_identifiers_list() as $timezone)
                {
                if($user_local_timezone == $timezone)
                    {
                    ?>
                    <option value="<?php echo $timezone; ?>" selected><?php echo $timezone; ?></option>
                    <?php
                    }
                else
                    {
                    ?>
                    <option value="<?php echo $timezone; ?>"><?php echo $timezone; ?></option>
                    <?php
                    }                
                }
            
            ?>
            </select>
            <script>
            jQuery(document).ready(function() {
                var user_local_tz = detect_local_timezone();
                <?php 

                if(!isset($user_local_timezone) || $user_local_timezone == '') 
                    {
                    ?>jQuery('#user_local_tz').val(user_local_tz);<?php
                    }

                ?>
            });
            </script>
            <div class="clearerleft"></div>
        </div>

        <?php if ($allow_keep_logged_in) { ?>
            <div class="Question KeepLoggedIn">
                <label for="remember"><?php echo $lang["keepmeloggedin"]?></label>
                <input name="remember" id="remember" type="checkbox" value="yes" <?php echo ($remember_me_checked === true) ? "checked='checked'" : "";?>>
                <div class="clearer"> </div>
            </div>
        <?php } ?>

        <div class="QuestionSubmit">
            <label for="buttons"> </label>          
            <input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["login"]?>&nbsp;&nbsp;" />
        </div>

    
        <p class="LoginLinks">
        <?php if ($allow_account_request) { ?>
            <a id="account_apply" href="<?php echo $baseurl_short?>pages/user_request.php"><i class="fas fa-fw fa-user-plus"></i>&nbsp;<?php echo $lang["nopassword"]?></a>
        <?php } ?>
        <?php if ($allow_password_reset) { ?>
            <br/><a id="account_pw_reset" href="<?php echo $baseurl_short?>pages/user_password.php"><i class="fas fa-fw fa-lock"></i>&nbsp;<?php echo $lang["forgottenpassword"]?></a>
        <?php } ?>
        </p>

	</form>

    <script type="text/javascript">
    // Default the focus to the username box
    jQuery('#username').focus();

    jQuery(document).ready(function() {
        /* 
        * Bind to capslockstate events and update display based on state 
        */
        jQuery(window).bind("capsOn", function(event) {
            if (jQuery("#password:focus").length > 0) {
                jQuery("#capswarning").show();
            }
        });
        jQuery(window).bind("capsOff capsUnknown", function(event) {
            jQuery("#capswarning").hide();
        });
        jQuery("#password").bind("focusout", function(event) {
            jQuery("#capswarning").hide();
        });
        jQuery("#password").bind("focusin", function(event) {
            if (jQuery(window).capslockstate("state") === true) {
                jQuery("#capswarning").show();
            }
        });

        /* 
        * Initialize the capslockstate plugin.
        * Monitoring is happening at the window level.
        */
        jQuery(window).capslockstate();

    });

    /* Responsive Stylesheet inclusion based upon viewing device */
    if(document.createStyleSheet)
        {
        document.createStyleSheet('<?php echo $baseurl ;?>/css/responsive/slim-style.css?rcsskey=<?php echo $css_reload_key; ?>');
        }
    else
        {
        jQuery("head").append("<link rel='stylesheet' href='<?php echo $baseurl ;?>/css/responsive/slim-style.css?rcsskey=<?php echo $css_reload_key; ?>' type='text/css' media='screen' />");
        }

    if(!is_touch_device() && jQuery(window).width() <= 1280)
        {
        if(document.createStyleSheet)
            {
            document.createStyleSheet('<?php echo $baseurl; ?>/css/responsive/slim-non-touch.css?rcsskey=<?php echo $css_reload_key; ?>');
            }
        else
            {
            jQuery("head").append("<link rel='stylesheet' href='<?php echo $baseurl; ?>/css/responsive/slim-non-touch.css?rcsskey=<?php echo $css_reload_key; ?>' type='text/css' media='screen' />");
            }
        }

    </script>

    <?php
    }

hook('afterlogin');

//include_once "./include/footer.php"; AJAX Check Ignores Footer
//Closing tags as the footer has not been included
?>
<div> <!-- end of login_box --><?php

include "include/footer.php";
