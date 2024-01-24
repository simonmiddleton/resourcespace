<?php
include "../include/db.php";

include "../include/authenticate.php"; 

$ref=getval("ref","",true);
// Fetch resource data
$resource=get_resource_data($ref);if ($resource===false) {exit($lang['resourcenotfound']);}

// fetch the current search 
$search=getval("search","");
$order_by=getval("order_by","relevance");
$offset=getval("offset",0,true);
$restypes=getval("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}
$archive=getval("archive",0,true);
$modal=(getval("modal", "") == "true");

$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);

$minaccess=get_resource_access($ref);

// Check if sharing permitted
if (!can_share_resource($ref,$minaccess)) {exit($lang["error-permissiondenied"]);}

$user_select_internal=checkperm("noex") ||  intval($user_dl_limit) > 0;

$errors="";
if (getval("save","")!="" && enforcePostRequest(getval("ajax", false)))
	{
	// Build a new list and insert
	$users=getval("users","");
	$message=getval("message","");
	$access=getval("access","");
	$add_internal_access=(getval("grant_internal_access","")!="");
	if (hook("modifyresourceaccess")){$access=hook("modifyresourceaccess");}
	$expires=getval("expires","");
	$group=getval("usergroup","");
    $sharepwd = getval('sharepassword', '');
	$list_recipients=getval("list_recipients",""); if ($list_recipients=="") {$list_recipients=false;} else {$list_recipients=true;}

	$use_user_email=getval("use_user_email",false);
	if ($use_user_email){$user_email=$useremail;} else {$user_email="";} // if use_user_email, set reply-to address
	if (!$use_user_email){$from_name=$applicationname;} else {$from_name=$userfullname;} // make sure from_name matches system name

	if (getval("ccme",false)){ $cc=$useremail;} else {$cc="";}

    // Email single resource
    $errors=email_resource($ref,i18n_get_translated($resource["field".$view_title_field]),$userfullname,$users,$message,$access,$expires,$user_email,$from_name,$cc,$list_recipients,$add_internal_access,$minaccess,$group);
    if ($errors=="")
        {
        // Log this			
        daily_stat("E-mailed resource",$ref);
        if (!hook("replaceresourceemailredirect"))
            {
            $params = get_search_params();
            $params["text"]     = "resource_email";
            $params["resource"] = $ref;
            redirect(generateURL($baseurl_short . "pages/done.php",$params));
            }
        }
	}

include "../include/header.php";
?>
<div class="BasicsBox">
<p><a onClick="return <?php echo ($modal?'ModalLoad':'CentralSpaceLoad');?>(this,true);" href="<?php echo $baseurl_short; ?>pages/resource_share.php?ref=<?php echo urlencode($ref) ?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>"><?php echo LINK_CARET_BACK ?><?php echo htmlspecialchars($lang["backtoshareresource"]); ?></a></p>

<h1><?php echo htmlspecialchars($lang["emailresourcetitle"])?></h1>

<p><?php echo text("introtext");render_help_link("user/sharing-resources");?></p>

<form method=post id="resourceform" action="<?php echo $baseurl_short?>pages/resource_email.php?search=<?php echo urlencode($search)?>&offset=<?php echo $offset?>&order_by=<?php echo $order_by?>&sort=<?php echo $sort?>&archive=<?php echo $archive?>">
<input type=hidden name=ref value="<?php echo escape($ref)?>">
<?php generateFormToken("resourceform"); ?>
<div class="Question">
<label><?php echo htmlspecialchars($lang["resourcetitle"])?></label><div class="Fixed"><?php echo htmlspecialchars(i18n_get_translated($resource["field".$view_title_field]))?></div>
<div class="clearerleft"> </div>
</div>

<div class="Question">
<label><?php echo htmlspecialchars($lang["resourceid"])?></label><div class="Fixed"><?php echo $resource["ref"]?></div>
<div class="clearerleft"> </div>
</div>
<?php
hook("resemailmoreinfo"); ?>
<div class="Question">
<label for="message"><?php echo htmlspecialchars($lang["message"])?></label><textarea class="stdwidth" rows=6 cols=50 name="message" id="message"></textarea>
<div class="clearerleft"> </div>
</div>

<?php if(!hook("replaceemailtousers")){?>
<div class="Question">
	<label for="users">
	<?php echo ($user_select_internal)?$lang["emailtousers_internal"]:$lang["emailtousers"]; ?>
	</label>

	<?php

include "../include/user_select.php"; ?>
<div class="clearerleft"> </div>
<?php if ($errors!="") { ?><div class="FormError">!! <?php echo htmlspecialchars($errors)?> !!</div><?php } ?>
</div>
<?php } ?>

<?php if ($list_recipients){?>
<div class="Question">
<label for="list_recipients"><?php echo htmlspecialchars($lang["list-recipients-label"]); ?></label><input type=checkbox id="list_recipients" name="list_recipients">
<div class="clearerleft"> </div>
</div>
<?php } ?>

<?php if($minaccess==0 && !hook("replaceemailopenaccess"))
	{
	$resourcedata=get_resource_data($ref,true);
	if(get_edit_access($ref,$resource['archive'],$resource))
		{?>
		<div class="Question">
		<label for="grant_internal_access"><?php echo htmlspecialchars($lang["internal_share_grant_access"]) ?></label>
		<input type=checkbox id="grant_internal_access" name="grant_internal_access" onClick="if(this.checked){jQuery('#question_internal_access').slideDown();}else{jQuery('#question_internal_access').slideUp()};">
		<div class="clearerleft"> </div>
		</div>
		<?php
		}
	}?>


<?php 

if(!$user_select_internal)
	{
    ?>
    <h2 class="CollapsibleSectionHead collapsed"><?php echo htmlspecialchars($lang['external_shares_options']);?></h2>
    <div class="CollapsibleSection" id="ExternalShareOptionsSection">
        <p><?php echo htmlspecialchars($lang['email_shares_options_summary']);?></p>
    <?php
    render_share_options();
    ?></div><?php    
    }
	?>

<?php hook("resourceemailafterexternal");?>

<?php if ($email_from_user) {?>
<?php if ($useremail!="") { // Only allow this option if there is an email address available for the user.
?>
<div class="Question">
<label for="use_user_email"><?php echo htmlspecialchars($lang["emailfromuser"]).$useremail.". ".$lang["emailfromsystem"].$email_from ?></label><input type=checkbox checked id="use_user_email" name="use_user_email">
<div class="clearerleft"> </div>
</div>
<?php } ?>
<?php } ?>

<?php if ($cc_me && $useremail!=""){?>
<div class="Question">
<label for="ccme"><?php echo str_replace("%emailaddress", $useremail, $lang["cc-emailaddress"]); ?></label><input type=checkbox checked id="ccme" name="ccme">
<div class="clearerleft"> </div>
</div>
<?php } ?>

<?php hook("additionalemailfield");?>

<?php if(!hook("replaceemailsubmitbutton")){?>
<div class="QuestionSubmit">		
<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo escape($lang["emailresourcetitle"])?>&nbsp;&nbsp;" />
</div>
<?php } // end replaceemailsubmitbutton ?>

</form>
</div>
<script>
jQuery('document').ready(function()
    {
    registerCollapsibleSections(true)
    });
</script>
<?php		
include "../include/footer.php";
?>
