<?php
include "../../include/db.php";
include_once dirname(__DIR__, 2) . '/include/login_functions.php';

$password_reset_mode=false;
$resetvalues=getvalescaped("rp","");
if($resetvalues!="")
    {
    if(substr($resetvalues,0,2) == "3D")
        {
        // Email Smay have encoded the = character
        $resetvalues = substr($resetvalues,2);
        }
        
    $rplength=strlen($resetvalues);
    $resetuserref=substr($resetvalues,0,$rplength-15);
    $resetkey=substr($resetvalues,$rplength-15);
    $valid_reset_link=false;
	$resetvaliduser=sql_query("select ref, username, email, fullname, usergroup, password, password_reset_hash, last_active from user where ref='" . escape_check($resetuserref)  . "'",""); 
	if(count($resetvaliduser)==1)
		{
		$resetuser=$resetvaliduser[0];
  		$keycheck=array();
		$keycheck[]=substr(hash('sha256', date("Ymd") .  $resetuser["password_reset_hash"] . $resetuser["username"] . $scramble_key),0,15); 
		// We also need to check the entered key to see if valid in the last number of days ($password_reset_link_expiry), so add these as possible values to the array
		for($n=1;$n<=$password_reset_link_expiry;$n++)
		    {
		    $keycheck[]=substr(hash('sha256', date("Ymd", time() - 60 * 60 * 24 * $n) .  $resetuser["password_reset_hash"] . $resetuser["username"] . $scramble_key),0,15); 
		    }
		if(in_array($resetkey, $keycheck))
			{$valid_reset_link=true;}
		}
	if($valid_reset_link)
		{		
		$userref=$resetuser["ref"];
		$username=$resetuser["username"];
		$userfullname=$resetuser["fullname"];
		$email=$resetuser["email"];
		$usergroup=$resetuser["usergroup"];
		$userpassword=$resetuser["password"];
		$last_active=$resetuser["last_active"];
		$password_reset_mode=true;
		}
    else
		{
		redirect ($baseurl . "/login.php?error=passwordlinkexpired");     
		exit();
		}
    }


if(!$password_reset_mode)
    {
    include "../../include/authenticate.php";

    if (checkperm("p") || !$allow_password_change || (isset($anonymous_login) && $anonymous_login==$username))
        {
        exit("Not allowed.");
        }

    // Reset $not_authenticated_pages (used in header) when this runs in an authenticated context. This will allow showing
    // the header and search bar as normal.
    $GLOBALS['modify_header_not_authenticated_pages'] = [];
    $GLOBALS['modify_header_omit_searchbar_pages'] = [];
    }
$is_authenticated_ctx = is_authenticated();

hook("preuserpreferencesform");

if(getval("save", "") != "" && enforcePostRequest(false))
	{
    if($case_insensitive_username)
        {
        $username=sql_value("select username value from user where lower(username)=lower('" . escape_check($username) ."')",$username);       
        $username=escape_check($username);
        }
	if (hook('saveadditionaluserpreferences'))
		{
		# The above hook may return true in order to prevent the password from being updated
		}
    else if(!$password_reset_mode && !rs_password_verify(getval('currentpassword', ''), $userpassword, ['username' => $username]))
        {
        $error3 = $lang['wrongpassword'];
        }
	else {
        if (getval("password","")!=getval("password2","")) {$error2=true;}
    	else
	    	{
		    $message=change_password(getvalescaped("password",""));
    		if ($message===true)
	    		{
				if($password_reset_mode && $last_active=="" && $email!="")
					{
					// This account has just been created, probably an auto approved account. Send the welcome email
					email_user_welcome($email,$username,$lang["hidden"],$usergroup);
					redirect($baseurl_short."pages/done.php?text=password_changed&notloggedin=true");
					exit();
					}
		    	redirect($baseurl_short."pages/" . ($use_theme_as_home?'collections_featured.php':$default_home_page));
			    exit();
				}
    		else
	    		{
		    	$error=true;
			    }
		    }
		}
	}

include "../../include/header.php";
if($is_authenticated_ctx)
    {
    ?><div class="BasicsBox"><?php
    }
else
    {
    include '../../include/login_background.php';
    ?>
    <div id="LoginHeader">
        <img src="<?php echo get_header_image(); ?>" class="LoginHeaderImg">
    </div>
    <?php
    }

    if ($userpassword=="b58d18f375f68d13587ce8a520a87919")
        {
        ?><div class="FormError" style="margin:0;"><?php echo $lang['secureyouradminaccount'];?></div><p></p><?php
        }
	
    if (!hook("replaceuserpreferencesheader")) { ?>
	<p>
        <h1><?php echo $lang["changeyourpassword"]?></h1>
        <?php } ?> <!-- End hook("replaceuserpreferencesheader") -->

        <?php 
        if($password_reset_mode && $last_active=="")
            {
            // The user is a new account setting a password for the first time
            echo text("introtext_new");
            }
        else
            {
            echo text("introtext");
            }
            ?>
	</p>

	<?php if (getval("expired","")!="") { ?><div class="FormError">!! <?php echo $lang["password_expired"]?> !!</div><?php } ?>

	<form method="post" action="<?php echo $baseurl_short?>pages/user/user_change_password.php">
	<input type="hidden" name="expired" value="<?php echo htmlspecialchars(getvalescaped("expired",""))?>">
	<?php
    generateFormToken("user_change_password");

	if(!$password_reset_mode)
	    {
	    // Additional user preferences only available in use cases other than password reset
        hook('additionaluserpreferences');

	    ?>
	    <div class="Question">
	    <label for="password"><?php echo $lang["currentpassword"]?></label>
	    <input type="password" class="stdwidth" name="currentpassword" id="currentpassword" value="<?php if ($userpassword=="b58d18f375f68d13587ce8a520a87919"){?>admin<?php } ?>"/>
	    <div class="clearerleft"> </div>
	    <?php if (isset($error3)) { ?><div class="FormError">!! <?php echo $error3?> !!</div><?php } ?>
	    </div>
	    <?php
	    }
	else
	    {?>
	    <input type="hidden" name="rp" id="resetkey" value="<?php echo htmlspecialchars($resetuserref) . htmlspecialchars($resetkey)  ?>" />    
	    <?php
	    }
	    ?>
	<div class="Question">
	<label for="password"><?php echo $lang["newpassword"]?></label>
	<input type="password" name="password" id="password" class="stdwidth">
	<?php if (isset($error)) { ?><div class="FormError">!! <?php echo $message?> !!</div><?php } ?>
	<div class="clearerleft"> </div>
	</div>

	<div class="Question">
	<label for="password2"><?php echo $lang["newpasswordretype"]?></label>
	<input type="password" name="password2" id="password2" class="stdwidth">
	<?php if (isset($error2)) { ?><div class="FormError">!! <?php echo $lang["passwordnotmatch"]?> !!</div><?php } ?>
	<div class="clearerleft"> </div>
	</div>



	<div class="QuestionSubmit">
	<label for="buttons"> </label>
	<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" /><div class="clearerleft"> </div>
	</div>
	</form>

<?php

if(!$password_reset_mode)
    {
    hook("afteruserpreferencesform");
    }

if($is_authenticated_ctx)
    {
    // Close BasicsBox container
    ?></div><?php
    }
include "../../include/footer.php";