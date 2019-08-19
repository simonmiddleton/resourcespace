<?php
/**
 * User edit form display page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php"; 
include "../../include/api_functions.php"; 

$backurl=getval("backurl","");
$modal=(getval("modal","")=="true");
$url=$baseurl_short."pages/team/team_user_edit.php?ref=" .getvalescaped("ref","",true) . "&backurl=" . urlencode($backurl);
if (!checkperm("u")) {redirect($baseurl_short ."login.php?error=error-permissions-login&url=".urlencode($url));}

$ref=getvalescaped("ref","",true);
$approval_state_text = array(0 => $lang["notapproved"],1 => $lang["approved"], 2 => $lang["disabled"]);


if (getval("unlock","")!="" && enforcePostRequest(getval("ajax", false)))
	{
	# reset user lock
	sql_query("update user set login_tries='0' where ref='$ref'");
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
	if ($result===false)
		{
		$error=$lang["useralreadyexists"];
		}
	elseif ($result!==true)
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
		$message=$lang["changessaved"];
		}
	}

# Fetch user data
$user=get_user($ref);
if (($user["usergroup"]==3) && ($usergroup!=3)) {redirect($baseurl_short ."login.php?error=error-permissions-login&url=".urlencode($url));}

if (!checkperm_user_edit($user))
	{
	if(!$modal){redirect($baseurl_short ."login.php?error=error-permissions-login&url=".urlencode($url));}
	else
		{echo $lang["error-permissiondenied"];}
	exit;
}

include "../../include/header.php";

if ($user == ''){
	echo $lang["accountdoesnotexist"];
	include "../../include/footer.php";
	exit();
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
    ?>
    <form method="post" action="<?php echo $baseurl_short?>login.php" id="autologin">
        <?php generateFormToken("autologin"); ?>
        <input type="hidden" name="username" value="<?php echo $user["username"]?>">
        <input type="hidden" name="password" value="<?php echo $user["password"]?>">
        <input type="hidden" name="userkey" value="<?php echo md5(escape_check($user["username"]) . $scramble_key)?>">
        <noscript><input type="submit" value="<?php echo $lang["login"]?>"></noscript>
    </form>
    <script type="text/javascript">
    document.getElementById("autologin").submit();
    </script>
    <?php
    exit();
    }
?>
<div class="BasicsBox">

<div class="RecordHeader">
<div class="backtoresults">	
<?php if($modal)
	{
	?>
	<a class="maxLink fa fa-expand" href="<?php echo $url ?>" onClick="return CentralSpaceLoad(this);"></a>
	&nbsp;
	<a href="#"  class="closeLink fa fa-times" onClick="ModalClose();"></a>
	<?php
	}
	?>
</div>

<?php
if(!$modal)
	{?>
	<p><a href="<?php echo ($backurl!=""?$backurl:$baseurl_short ."pages/team/team_user.php");?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $backurl!=""?$lang["back"]:$lang["manageusers"]; ?></a></p>
	<?php
	}
	?>
<h1><?php echo $lang["edituser"];render_help_link('systemadmin/creating-users');?> <?php global $display_useredit_ref; echo $display_useredit_ref ? $ref : ""; ?></h1>
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
<input type=hidden name=backurl value="<?php echo getval("backurl", $baseurl_short . "pages/team/team_user.php?nc=" . time())?>">
<input type=hidden name="save" value="save" /><!-- to capture default action -->


<?php
if (($user["login_tries"]>=$max_login_attempts_per_username) && (strtotime($user["login_last_try"]) > (time() - ($max_login_attempts_wait_minutes * 60))))
 {?>
	<div class="Question"><label><strong><?php echo $lang["accountlockedstatus"]?></strong></label>
		<input class="medcomplementwidth" type=submit name="unlock" value="<?php echo $lang["accountunlock"]?>" onclick="jQuery('#unlockuser').val('true');"/>
		<input id="unlockuser" type=hidden name="unlock" value="" />
	</div>

	<div class="clearerleft"> </div>
<?php } ?>

<div class="Question" ><label><?php echo $lang["username"]?></label><input name="username" type="text" class="stdwidth" value="<?php echo form_value_display($user,"username") ?>"><div class="clearerleft"> </div></div>

<?php if (!hook("password")) { ?>
<div class="Question"><label><?php echo $lang["password"]?></label><input name="password" id="password" type="text" class="medwidth" value="<?php echo (strlen($user["password"])==64 || strlen($user["password"])==32)?$lang["hidden"]:$user["password"]?>">&nbsp;<input class="medcomplementwidth" type=submit name="suggest" value="<?php echo $lang["suggest"]?>" onclick="jQuery.get(this.form.action + '?suggest=true', function(result) {jQuery('#password').val(result);});return false;" /><div class="clearerleft"> </div></div>
<?php } ?>

<?php if (!hook("replacefullname")){?>
<div class="Question"><label><?php echo $lang["fullname"]?></label><input name="fullname" type="text" class="stdwidth" value="<?php echo form_value_display($user,"fullname") ?>"><div class="clearerleft"> </div></div>
<?php } ?>

<div class="Question"><label><?php echo $lang["group"]?></label>
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

<div class="Question"><label><?php echo $lang["emailaddress"]?></label><input name="email" type="text" class="stdwidth" value="<?php echo form_value_display($user,"email") ?>"><div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo $lang["accountexpiresoptional"]?><br/><?php echo $lang["format"] . ": " . $lang["yyyy-mm-dd"]?></label><input name="account_expires" type="text" class="stdwidth" value="<?php echo form_value_display($user,"account_expires")?>"><div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo $lang["ipaddressrestriction"]?><br/><?php echo $lang["wildcardpermittedeg"]?> 194.128.*</label><input name="ip_restrict" type="text" class="stdwidth" value="<?php echo form_value_display($user,"ip_restrict") ?>"><div class="clearerleft"> </div></div>

<?php
if ($search_filter_nodes && ($user['search_filter_o_id'] == "" || (is_numeric($user['search_filter_o_id']) && $user['search_filter_o_id'] > 0)))
			{
            // Show filter selector if already migrated or no filter has been set
			$search_filters = get_filters($order = "name", $sort = "ASC");
			?>
			<div class="Question">
				<label for="search_filter_o_id"><?php echo $lang["searchfilteroverride"]; ?></label>
				<select name="search_filter_o_id" class="stdwidth">
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
			}
		else
			{
			?>
            <input type="hidden" name="search_filter_o_id" value="0" />
			<div class="Question">
				<label for="search_filter"><?php echo $lang["searchfilteroverride"]; ?></label>
				<input name="search_filter_override" type="text" class="stdwidth" value="<?php echo form_value_display($user,"search_filter_override")?>">
				<div class="clearerleft"></div>
			</div>
			<?php
			}
            
hook("additionaluserfields");
if (!hook("replacecomments"))
    { ?>
    <div class="Question"><label><?php echo $lang["comments"]?></label><textarea name="comments" class="stdwidth" rows=5 cols=50><?php echo form_value_display($user,"comments")?></textarea><div class="clearerleft"> </div></div>
    <?php
    } ?>
<div class="Question"><label><?php echo $lang["created"]?></label>
<div class="Fixed"><?php echo nicedate($user["created"],true) ?></div>
<div class="clearerleft"> </div></div>

<?php 
if ($user_edit_created_by)
	{ 
	$account_creation_data=sql_query('select u.fullname, u.email from user u left join activity_log al on u.ref=al.user where al.log_code="c" and al.remote_table="user" and al.remote_column="ref" and al.remote_ref=' . $ref);
	$account_created_by=(!empty($account_creation_data) ? $account_creation_data[0]['fullname'] . ($user_edit_created_by_email ? ' (' . $account_creation_data[0]['email'] . ')' : '') : $lang['user_autocreated']);
	?>
	<div class="Question">
		<label><?php echo $lang["user_created_by"]?></label>
		<div class="Fixed"><?php echo $account_created_by ?></div>
		<div class="clearerleft"> </div>
	</div>
	<?php
	}
?>

<div class="Question"><label><?php echo $lang["origin"]; ?></label>
<div class="Fixed"><?php echo (($user["origin"]!="")?(isset($lang["origin_" . $user["origin"]])?$lang["origin_" . $user["origin"]]:$user["origin"]):$applicationname) ?></div>
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo $lang["lastactive"]?></label>
<div class="Fixed"><?php echo nicedate($user["last_active"],true) ?></div>
<div class="clearerleft"> </div></div>


<div class="Question"><label><?php echo $lang["lastbrowser"]?></label>
<div class="Fixed"><?php echo resolve_user_agent($user["last_browser"],true)?></div>
<div class="clearerleft"> </div></div>

<?php if ($enable_remote_apis) { ?>
<div class="Question"><label><?php echo $lang["private-api-key"] ?></label>
<div class="Fixed"><?php echo get_api_key($user["ref"]) ?></div>
<div class="clearerleft"> </div></div>
<?php }

if(!hook('ticktoemailpassword')) 
    {
    ?>
    <div class="Question"><label><?php echo $lang["ticktoemaillink"]?></label>
    <input name="emailresetlink" type="checkbox" value="yes" <?php if ($user["approved"]==0 || getval("emailresetlink","") != "") { ?>checked<?php } ?>>
    <div class="clearerleft"> </div></div>
    <?php
    }
    ?> 

<div class="Question"><label><?php echo $lang["status"]?></label>
<select name="approved" >
    <?php
    for($n=0;$n<=2;$n++)
        {
        echo "<option value=" . $n . " " . ($user["approved"] == $n ? " selected" : "") . " >" . $approval_state_text[$n] . "</option>";
        }
    ?>
</select>
<?php if ($user["approved"]!=1) { ?><div class="FormError">!! <?php echo $lang["ticktoapproveuser"]?> !!</div><?php } ?>
<div class="clearerleft"> </div></div>

<?php 
if ($user_edit_approved_by && $user["approved"]==1)
	{ 
	$account_approval_data=sql_query('select u.fullname, u.email from user u left join activity_log al on u.ref=al.user where al.log_code="e" and al.remote_table="user" and al.remote_column="approved" and al.remote_ref=' . $ref);
	$account_approved_by=(!empty($account_approval_data) ? $account_approval_data[0]['fullname'] . ($user_edit_approved_by_email ? ' (' . $account_approval_data[0]['email'] . ')' : '') : $lang['user_autoapproved']);
	?>
	<div class="Question">
		<label><?php echo $lang["user_approved_by"]?></label>
		<div class="Fixed"><?php echo $account_approved_by ?></div>
		<div class="clearerleft"> </div>
	</div>
	<?php
	}
?>

<div class="Question"><label><?php echo $lang["ticktodelete"]?></label><input name="deleteme" type="checkbox"  value="yes"><div class="clearerleft"> </div></div>
<?php hook("additionaluserlinks");?>

<div class="Question">
<label><?php echo $lang["team_user_contributions"]?></label>
<div class="Fixed"><a href="<?php echo $baseurl_short?>pages/search.php?search=!contributions<?php echo $ref?>"><?php echo LINK_CARET ?><?php echo $lang["team_user_view_contributions"] ?></a></div>
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo $lang["log"]?></label>
<div class="Fixed"><a href="<?php echo $baseurl_short ?>pages/admin/admin_system_log.php?actasuser=<?php echo $ref ?>&backurl=<?php echo urlencode($url) ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["clicktoviewlog"]?></a></div>
<div class="clearerleft"> </div></div>

<?php if ($user["approved"]==1 && !hook("loginasuser")) { ?>
<div class="Question"><label><?php echo $lang["login"]?></label>
<div class="Fixed"><a href="<?php echo $baseurl_short?>pages/team/team_user_edit.php?ref=<?php echo $ref?>&loginas=true"><?php echo LINK_CARET ?><?php echo $lang["clicktologinasthisuser"]?></a></div>
<div class="clearerleft"> </div></div>
<?php } ?>



<div class="QuestionSubmit">
<label for="buttons"> </label>			
<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
</div>
</form>
</div>

<?php		
include "../../include/footer.php";
?>
