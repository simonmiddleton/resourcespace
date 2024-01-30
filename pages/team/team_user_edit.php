<?php
/**
 * User edit form display page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";
include "../../include/authenticate.php"; 
include "../../include/api_functions.php"; 

$backurl=getval("backurl","");
$modal=(getval("modal","")=="true");
$url=$baseurl_short."pages/team/team_user_edit.php?ref=" .getval("ref","",true) . "&backurl=" . urlencode($backurl);
if (!checkperm("u"))
    {
    error_alert($lang["error-permissiondenied"],true);
    exit();
    }

$ref=getval("ref","",true);
$approval_state_text = array(0 => $lang["notapproved"],1 => $lang["approved"], 2 => $lang["disabled"]);


if (getval("unlock","")!="" && enforcePostRequest(getval("ajax", false)))
	{
	# reset user lock
	ps_query("update user set login_tries='0' where ref= ?", ['i', $ref]);
	}
elseif(getval("suggest","")!="")
	{
	echo make_password();
	exit();
	}
elseif (getval("save","")!="" && enforcePostRequest(getval("ajax", false)))
	{
	# Save user data
	$result=save_user($ref);
    # Result can be === True which means save_user was successful and (if requested) the email password reset link was successful
    # Otherwise it will be a string with an error message describing the reason for failure to save/email
    if ($result!==true)
		{
		$error=$result;
		}
	else
		{
		hook('aftersaveuser');
		if (getval("save","")!="" && !$modal)
			{
			redirect ($backurl!=""?$backurl:$baseurl_short ."pages/team/team_user.php?nc=" . time());
			exit();
			}
		if (getval("save","")!="" && $modal)
			{
			# close Modal and return to action list
			echo "<script>ModalClose()</script>";
			exit();
			}	
		
		}
	}

# Fetch user data
$user=get_user($ref);
if ($user===false)
    {
    $error = $lang['accountdoesnotexist'];
    if(getval("ajax","") != "")
        {
        error_alert($error, false);
        }
    else
        {
        include __DIR__ . "/../../include/header.php";
        $onload_message = array("title" => $lang["error"],"text" => $error);
        include __DIR__ . "/../../include/footer.php";
        }
    exit();
    }
    
if (($user["usergroup"]==3) && ($usergroup!=3)) 
    {
    error_alert($lang["error-permissiondenied"],false);
    exit();
    }

if (!checkperm_user_edit($user))
	{
    error_alert($lang["error-permissiondenied"],true);
    exit();
    }

// Block this from running if we are logging in as a user because running this here will block db.php from setting headers
if(getval('loginas', '') === '')
    {
    include "../../include/header.php";
    }


// Log in as this user. A user key must be generated to enable login using the MD5 hash as the password.
if(getval('loginas', '') != '')
    {
    // Log user switch in the activity log for both sides (the user we moved from and the one we moved to)
    $log_activity_note = str_replace(
        array('%USERNAME_FROM', '%USERNAME_TO'),
        array($username, $user['username']),
        $lang['activity_log_admin_log_in_as']
    );
    log_activity($log_activity_note, LOG_CODE_LOGGED_IN, null, 'user', null, null, null, null, $userref, false);
    log_activity($log_activity_note, LOG_CODE_LOGGED_IN, null, 'user', null, null, null, null, $user['ref'], false);

    global $CSRF_token_identifier, $usersession;

    //userkey and CSRF tokens still need to be placed in post array as preform_login() references these directly
    $_POST = [];
    $_POST['username'] = $user['username'];
    $_POST['password'] = $user['password'];
    $_POST['userkey'] = md5($user["username"] . $scramble_key);
    $_POST[$CSRF_token_identifier] = generateCSRFToken($usersession, 'autologin');

    include '../../login.php';
    exit();
    }
?>
<div class="BasicsBox">

<div class="RecordHeader">

<h1><?php echo htmlspecialchars($lang["edituser"]); ?></h1>

<?php
// Breadcrumbs links
global $display_useredit_ref;
$links_trail = array(
    array(
        'title' => $lang["teamcentre"],
        'href'  => $baseurl_short . "pages/team/team_home.php",
		'menu' =>  true
    ),
    array(
        'title' => $lang["manageusers"],
        'href'  => $baseurl_short . "pages/team/team_user.php"
    ),
    array(
        'title' => $lang["edituser"] . ($display_useredit_ref ? " " . $ref : ""),
        'help' => 'systemadmin/creating-users'
    )
);

renderBreadcrumbs($links_trail);
?>

</div>
<?php if (isset($error)) { ?><div class="FormError">!! <?php echo $error?> !!</div><?php } ?>
<?php if (isset($message)) { ?><div class="PageInfoMessage"><?php echo $message?></div><?php } ?>

<form method=post action="<?php echo $baseurl_short?>pages/team/team_user_edit.php" onsubmit="return <?php echo ($modal?"Modal":"CentralSpace") ?>Post(this,true);">
<?php 
if($modal)
	{
	?>
	<input type=hidden name="modal" value="true">
	<?php
	}

generateFormToken("team_user_edit");
?>
<input type=hidden name=ref value="<?php echo urlencode($ref) ?>">
<input type=hidden name=backurl value="<?php echo escape(getval("backurl", $baseurl_short . "pages/team/team_user.php?nc=" . time()))?>">
<input type=hidden name="save" value="save" /><!-- to capture default action -->


<?php
if (($user["login_tries"]>=$max_login_attempts_per_username) && (strtotime($user["login_last_try"]) > (time() - ($max_login_attempts_wait_minutes * 60))))
 {?>
	<div class="Question"><label><strong><?php echo htmlspecialchars($lang["accountlockedstatus"])?></strong></label>
		<input class="medcomplementwidth" type=submit name="unlock" value="<?php echo escape($lang["accountunlock"])?>" onclick="jQuery('#unlockuser').val('true');"/>
		<input id="unlockuser" type=hidden name="unlock" value="" />
	</div>

	<div class="clearerleft"> </div>
<?php } ?>

<div class="Question" ><label><?php echo htmlspecialchars($lang["username"])?></label><input id="user_edit_username" name="username" type="text" class="stdwidth" value="<?php echo form_value_display($user,"username") ?>"><div class="clearerleft"> </div></div>

<?php if (!hook("password", "", array($user))) { ?>
<div class="Question"><label><?php echo htmlspecialchars($lang["password"])?></label><input name="password" id="password" type="text" class="medwidth" value="<?php echo escape($lang["hidden"]); ?>" autocomplete="new-password">&nbsp;<input class="medcomplementwidth" type=submit name="suggest" value="<?php echo escape($lang["suggest"])?>" onclick="jQuery.get(this.form.action + '?suggest=true', function(result) {jQuery('#password').val(result);});return false;" /><div class="clearerleft"> </div></div>
<?php } else { ?>
<div><input name="password" id="password" type="hidden" value="<?php echo escape($lang["hidden"]);?>" /></div>
<?php } ?>

<?php if (!hook("replacefullname")){?>
<div class="Question"><label><?php echo htmlspecialchars($lang["fullname"])?></label><input name="fullname" id="user_edit_fullname" type="text" class="stdwidth" value="<?php echo form_value_display($user,"fullname") ?>"><div class="clearerleft"> </div></div>
<?php } ?>

<div class="Question"><label><?php echo htmlspecialchars($lang["group"])?></label>
<?php if (!hook("replaceusergroups")) { ?>
<select class="stdwidth" name="usergroup">
<?php
	$groups=get_usergroups(true);
	for ($n=0;$n<count($groups);$n++)
		{
		if (($groups[$n]["ref"]==3) && ($usergroup!=3))
			{
			#Do not show
			}
		else
			{
			?>
			<option value="<?php echo $groups[$n]["ref"]?>" <?php if (getval("usergroup",$user["usergroup"])==$groups[$n]["ref"]) {?>selected<?php } ?>><?php echo $groups[$n]["name"]?></option>	
			<?php
			}
		}
?>
</select>
<?php } ?>
<div class="clearerleft"> </div></div>
<?php hook("additionalusergroupfields"); ?>

<div class="Question">
    <label><?php echo htmlspecialchars($lang["emailaddress"])?></label>
    <input 
        name="email" 
        id="user_edit_email" 
        type="text" 
        class="stdwidth<?php if($user["email_invalid"]??false){echo " emailinvalid";}?>" 
        value="<?php echo form_value_display($user,"email") ?>"
        <?php if($user["email_invalid"]??false)
            {
            echo "title='" . escape($lang["emailmarkedinvalid"]) . "'";
            }
        ?>>
    <div class="clearerleft"> </div>
</div>

<div class="Question"><label><?php echo htmlspecialchars($lang["accountexpiresoptional"])?><br/><?php echo htmlspecialchars($lang["format"]) . ": " . $lang["yyyy-mm-dd"]?></label><input name="account_expires" id="user_edit_expires" type="text" class="stdwidth" value="<?php echo form_value_display($user,"account_expires")?>"><div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo htmlspecialchars($lang["ipaddressrestriction"])?><br/><?php echo htmlspecialchars($lang["wildcardpermittedeg"])?> 194.128.*</label><input name="ip_restrict" type="text" class="stdwidth" value="<?php echo form_value_display($user,"ip_restrict_user") ?>"><div class="clearerleft"> </div></div>

<?php
if (is_numeric($user['search_filter_o_id']) && $user['search_filter_o_id'] > 0)
    {
    //Filter is set and migrated
    $search_filter_migrated = true;
    $search_filter_set      = true;
    }
else if ($user['search_filter_override'] != "" && ($user['search_filter_o_id'] == 0 || $user['search_filter_o_id'] == NULL))
    {
    // Filter requires migration
    $search_filter_migrated = false;
    $search_filter_set      = true;

    // Attempt to migrate filter
    $migrateresult = migrate_filter($user['search_filter_override']);
    $notification_users = get_notification_users();
    if(is_numeric($migrateresult))
        {
        message_add(array_column($notification_users,"ref"), $lang["filter_migrate_success"] . ": '" . $user['search_filter_override'] . "'",generateURL($baseurl . "/pages/team/team_user_edit.php",array("ref"=>$user['ref'])));
        
        // Successfully migrated - now use the new filter
        ps_query("UPDATE user SET search_filter_o_id= ? WHERE ref= ?", ['i', $migrateresult, 'i', $user['ref']]);
        
        $search_filter_migrated = true;
        $user['search_filter_o_id'] = $migrateresult;
        debug("FILTER MIGRATION: Migrated filter - new filter id#" . $usersearchfilter);
        }
    }
else if ($user['search_filter_override'] == "" && $user['search_filter_o_id'] == 0)
    {
    // Filter is not set (migrated by convention)
    $search_filter_migrated = true;
    $search_filter_set      = false;
    }

// Show filter selector if already migrated or no filter has been set
$search_filters = get_filters("name","ASC");
$filters[] = array("ref" => -1, "name" => $lang["disabled"]);
?>
<div class="Question">
    <label for="search_filter_o_id"><?php echo htmlspecialchars($lang["searchfilteroverride"]); ?></label>
    <select id="user_edit_search_filter" name="search_filter_o_id" class="stdwidth">
        <?php
        echo "<option value='0' >" . $lang["filter_none"] . "</option>";
        foreach	($search_filters as $search_filter)
            {
            echo "<option value='" . $search_filter['ref'] . "' " . ($user['search_filter_o_id'] == $search_filter['ref'] ? " selected " : "") . ">" . i18n_get_translated($search_filter['name']) . "</option>";
            }?>
    </select>
    <div class="clearerleft"></div>
</div>
<?php
           
hook("additionaluserfields");
if (!hook("replacecomments"))
    { ?>
    <div class="Question"><label><?php echo htmlspecialchars($lang["comments"])?></label><textarea id="user_edit_comments" name="comments" class="stdwidth" rows=5 cols=50><?php echo form_value_display($user,"comments")?></textarea><div class="clearerleft"> </div></div>
    <?php
    } ?>
<div class="Question"><label><?php echo htmlspecialchars($lang["created"])?></label>
<div class="Fixed"><?php echo nicedate($user["created"],true,true,true) ?></div>
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo htmlspecialchars($lang["origin"]); ?></label>
<div class="Fixed"><?php echo (($user["origin"]!="")?(isset($lang["origin_" . $user["origin"]])?$lang["origin_" . $user["origin"]]:$user["origin"]):$applicationname) ?></div>
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo htmlspecialchars($lang["lastactive"])?></label>
<div class="Fixed"><?php echo nicedate($user["last_active"],true,true,true) ?></div>
<div class="clearerleft"> </div></div>


<div class="Question"><label><?php echo htmlspecialchars($lang["lastbrowser"])?></label>
<div class="Fixed"><?php echo resolve_user_agent($user["last_browser"])?></div>
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo htmlspecialchars($lang["profile_image"])?></label>
<?php
$profile_image = get_profile_image($ref);
if ($profile_image != "")
    {
    ?> <div class="Fixed"> <img src="<?php echo $profile_image ?>" alt="Current profile image"></div> <?php   
    }
else
    {
    ?> <div class="Fixed"><?php echo htmlspecialchars($lang["no_profile_image"]) ?></div> <?php
    }
?>
<div class="clearerleft"> </div></div>

<?php if ($enable_remote_apis) { ?>
<div class="Question"><label><?php echo htmlspecialchars($lang["private-api-key"]) ?></label>
<div class="Fixed"><?php echo get_api_key($user["ref"]) ?></div>
<div class="clearerleft"> </div></div>
<?php }

if(!hook('ticktoemailpassword')) 
    {
    ?>
    <div class="Question"><label><?php echo htmlspecialchars($lang["ticktoemaillink"])?></label>
    <input name="emailresetlink" type="checkbox" value="yes" <?php if ($user["approved"]==0 || getval("emailresetlink","") != "") { ?>checked<?php } ?>>
    <div class="clearerleft"> </div></div>
    <?php
    }
    ?> 

<div class="Question"><label><?php echo htmlspecialchars($lang["status"])?></label>
<select name="approved" >
    <?php
    for($n=0;$n<=2;$n++)
        {
        echo "<option value=" . $n . " " . ($user["approved"] == $n ? " selected" : "") . " >" . $approval_state_text[$n] . "</option>";
        }
    ?>
</select>
<?php if ($user["approved"]!=1) { ?><div class="FormError">!! <?php echo htmlspecialchars($lang["ticktoapproveuser"])?> !!</div><?php } ?>
<div class="clearerleft"> </div></div>

<div class="Question">
    <label><?php echo htmlspecialchars($lang['ticktodelete']); ?></label>
    <input type="checkbox" name="deleteme" value="yes" onclick="return confirm_delete_user(this);">
    <div class="clearerleft"></div>
</div>
<?php hook("additionaluserlinks");?>

<div class="Question">
<label><?php echo htmlspecialchars($lang["team_user_contributions"])?></label>
<div class="Fixed"><a href="<?php echo $baseurl_short?>pages/search.php?search=!contributions<?php echo $ref?>"><?php echo LINK_CARET ?><?php echo htmlspecialchars($lang["team_user_view_contributions"]) ?></a></div>
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo htmlspecialchars($lang["log"])?></label>
<div class="Fixed"><a href="<?php echo $baseurl_short ?>pages/admin/admin_system_log.php?actasuser=<?php echo $ref ?>&backurl=<?php echo urlencode($url) ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo htmlspecialchars($lang["clicktoviewlog"])?></a></div>
<div class="clearerleft"> </div></div>

<?php
if($userref != $ref)
    {
    // Add message link
    ?>
    <div class="Question"><label><?php echo htmlspecialchars($lang["new_message"])?></label>
    <div class="Fixed"><a href="<?php echo $baseurl_short ?>pages/user/user_message.php?msgto=<?php echo $ref ?>&backurl=<?php echo urlencode($url) ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo htmlspecialchars($lang["message"])?></a></div>
    <div class="clearerleft"> </div></div>
<?php
    }  
hook("usertool")?>

<?php 
if ($user["approved"]==1 && !hook("loginasuser"))
    {
    if  (trim((string)$user["origin"]) != "" || 
            ( ($user['account_expires'] == "" || strtotime((string)$user['account_expires']) > time()) 
            && ($password_expiry == 0 || ($password_expiry > 0 && strtotime((string)$user['password_last_change']) != "" 
            && (time()-strtotime((string)$user['password_last_change'])) < $password_expiry*60*60*24))
            )
        )
        {
        ?>
        <div class="Question"><label><?php echo htmlspecialchars($lang["login"])?></label>
        <div class="Fixed"><a href="<?php echo $baseurl_short?>pages/team/team_user_edit.php?ref=<?php echo $ref?>&loginas=true"><?php echo LINK_CARET ?><?php echo htmlspecialchars($lang["clicktologinasthisuser"])?></a></div>
        <div class="clearerleft"> </div></div>
        <?php
        }
    else
        {
        ?>
        <div class="Question"><label><?php echo htmlspecialchars($lang["login"])?></label>
        <div class="Fixed"><?php echo htmlspecialchars($lang["accountorpasswordexpired"])?></div>
        <div class="clearerleft"> </div></div>
        <?php
        }
    }
?>



<div class="QuestionSubmit">			
<input name="save" type="submit" id="user_edit_save" value="&nbsp;&nbsp;<?php echo htmlspecialchars($lang["save"])?>&nbsp;&nbsp;" />
</div>
</form>
</div>
<script>
function confirm_delete_user(el)
    {
    if(jQuery(el).is(':checked') === false)
        {
        return true;
        }

    return confirm('<?php echo escape($lang['team_user__confirm-deletion']); ?>');
    }
</script>
<?php		
include "../../include/footer.php";