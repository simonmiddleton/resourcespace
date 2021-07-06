<?php
include "../include/db.php";

$ref=getval("ref","",true);
$k=getvalescaped("k","");if ($k=="" || !check_access_key_collection($ref,$k)) {include "../include/authenticate.php";}

if (!checkperm('q')){exit("<br /><br /><strong>".$lang["error-permissiondenied"]."</strong>");}

include "../include/request_functions.php";

if ($k!="" && (!isset($internal_share_access) || !$internal_share_access) && $prevent_external_requests)
	{
	echo "<script>window.location = '" .  $baseurl . "/login.php?error="  . (($allow_account_request)?"signin_required_request_account":"signin_required") . "'</script>";
	exit();
	}

if($ref=="" && isset($usercollection))
  {
  $ref = $usercollection;
  }
  
$cinfo=get_collection($ref);
$error=false;

# Determine the minimum access across all of the resources in the collection being requested
$collection_request_min_access=collection_min_access($ref);

# Prevent "request all" resources in a collection if the user has access to all of its resources
if ($collection_request_min_access == 0)
	{
	exit("<br /><br /><strong>".$lang["error-cant-request-all-are-open"]."</strong>");
	}

if (getval("save","")!="" && enforcePostRequest(false))
	{
	if ($k!="" || $userrequestmode==0)
		{
		if ($k!="" && (getval("fullname","")=="" || getvalescaped("email","")==""))
			{
			$result=false; # Required fields not completed.
			}
		else
			{
			# Request mode 0 : Simply e-mail the request.
			$result=email_collection_request($ref,getvalescaped("request",""),getvalescaped("email",""));
			}
		}
	else
		{
		# Request mode 1 : "Managed" mode via Manage Requests / Orders
		$result=managed_collection_request($ref,getvalescaped("request",""));
		}
	if ($result===false)
		{
		$error=$lang["requiredfields-general"];
		}
	else
		{
		?>
		<script>
		CentralSpaceLoad("<?php echo $baseurl_short ?>pages/done.php?text=resource_request&k=<?php echo htmlspecialchars($k); ?>",true);
		</script>
		<?php
		}
	}
include "../include/header.php";
?>

<div class="BasicsBox">
  <?php 
  $backlink=getvalescaped("backlink","");
  if($backlink!="")
	{
	?><p>
	  <a href='<?php echo rawurldecode($backlink); ?>'><?php echo LINK_CARET_BACK ?><?php echo $lang['back']; ?></a>
	</p>
	<?php
	}?>
		
		
  <h1><?php echo $lang["requestcollection"];render_help_link("resourceadmin/user-resource-requests");?></h1>
  <p><?php echo text("introtext")?></p>
  
	<form method="post" onsubmit="return CentralSpacePost(this,true);" action="<?php echo $baseurl_short?>pages/collection_request.php">  
	<?php generateFormToken("collection_request"); ?>
    <input type=hidden name=ref value="<?php echo htmlspecialchars($ref) ?>">
	<input type=hidden name="k" value="<?php echo htmlspecialchars($k) ?>">
	
	<div class="Question">
	<label><?php echo $lang["collectionname"]?></label>
	<div class="Fixed"><?php echo htmlspecialchars(i18n_get_collection_name($cinfo)); ?></div>
	<div class="clearerleft"> </div>
	</div>

	<?php 
	hook('collectionrequestdetail','',array($cinfo['ref']));
	
	# Only ask for user details if this is an external share. Otherwise this is already known from the user record.
	if ($k!="") { ?>
	<div class="Question">
	<label><?php echo $lang["fullname"]?></label>
	<input type="hidden" name="fullname_label" value="<?php echo $lang["fullname"]?>">
	<input name="fullname" class="stdwidth" value="<?php echo htmlspecialchars(getval("fullname","")) ?>">
	<div class="clearerleft"> </div>
	</div>
	
	<div class="Question">
	<label><?php echo $lang["emailaddress"]?></label>
	<input type="hidden" name="email_label" value="<?php echo $lang["emailaddress"]?>">
	<input name="email" class="stdwidth" value="<?php echo htmlspecialchars(getval("email","")) ?>">
	<div class="clearerleft"> </div>
	</div>

	<div class="Question">
	<label><?php echo $lang["contacttelephone"]?></label>
	<input name="contact" class="stdwidth" value="<?php echo htmlspecialchars(getval("contact","")) ?>">
	<input type="hidden" name="contact_label" value="<?php echo $lang["contacttelephone"]?>">
	<div class="clearerleft"> </div>
	</div>
	<?php } ?>
	
	<div class="Question">
	<label for="requestreason"><?php echo $lang["requestreason"]?> <?php if ($resource_request_reason_required) { ?><sup>*</sup><?php } ?></label>
	<textarea class="stdwidth" name="request" id="request" rows=5 cols=50><?php echo htmlspecialchars(getval("request","")) ?></textarea>
	<div class="clearerleft"> </div>
	</div>


<?php # Add custom fields 
if (isset($custom_request_fields))
	{
	$custom=explode(",",$custom_request_fields);
	$required=explode(",",$custom_request_required);
	
	for ($n=0;$n<count($custom);$n++)
		{
		$type=1;
		
		# Support different question types for the custom fields.
		if (isset($custom_request_types[$custom[$n]])) {$type=$custom_request_types[$custom[$n]];}
		
		if ($type==4)
			{
			# HTML type - just output the HTML.
			echo $custom_request_html[$custom[$n]];
			}
		else
			{
			?>
			<div class="Question">
			<label for="custom<?php echo $n?>"><?php echo htmlspecialchars(i18n_get_translated($custom[$n]))?>
			<?php if (in_array($custom[$n],$required)) { ?><sup>*</sup><?php } ?>
			</label>
			
			<?php if ($type==1) {  # Normal text box
			?>
			<input type=text name="custom<?php echo $n?>" id="custom<?php echo $n?>" class="stdwidth" value="<?php echo htmlspecialchars(getvalescaped("custom" . $n,""))?>">
			<?php } ?>

			<?php if ($type==2) { # Large text box 
			?>
			<textarea name="custom<?php echo $n?>" id="custom<?php echo $n?>" class="stdwidth" rows="5"><?php echo htmlspecialchars(getvalescaped("custom" . $n,""))?></textarea>
			<?php } ?>

			<?php if ($type==3) { # Drop down box
			?>
			<select name="custom<?php echo $n?>" id="custom<?php echo $n?>" class="stdwidth">
			<?php foreach ($custom_request_options[$custom[$n]] as $option)
				{
				$val=i18n_get_translated($option);
				?>
				<option <?php if (getval("custom" . $n,"")==$val) { ?>selected<?php } ?>><?php echo htmlspecialchars(i18n_get_translated($option));?></option>
				<?php
				}
			?>
			</select>
			<?php } ?>
			
			<div class="clearerleft"> </div>
			</div>
			<?php
			}
		}
	}
?>


	<div class="QuestionSubmit">
	<?php if ($error) { ?><div class="FormError">!! <?php echo $error ?> !!</div><?php } ?>
	<label for="buttons"> </label>			
	<input name="cancel" type="button" value="&nbsp;&nbsp;<?php echo $lang["cancel"]?>&nbsp;&nbsp;" onclick="document.location='<?php echo $baseurl_short?>pages/search.php?search=!collection<?php echo urlencode($ref) ?>';"/>&nbsp;
	<input name="save" value="true" type="hidden" />
	<input type="submit" value="&nbsp;&nbsp;<?php echo $lang["requestcollection"]?>&nbsp;&nbsp;" />
	</div>
	</form>
	
</div>

<?php
include "../include/footer.php";
?>
