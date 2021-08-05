<?php
include "../include/db.php";

include "../include/authenticate.php";
include_once "../include/image_processing.php";

$ref=getvalescaped("ref","",true);

$search=getvalescaped("search","");
$offset=getvalescaped("offset",0,true);
$order_by=getvalescaped("order_by","");
$archive=getvalescaped("archive","",true);
$restypes=getvalescaped("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}
$modal = (getval("modal", "") == "true");

$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);

$resource=getvalescaped("resource","",true);

# Fetch resource data.
$resourcedata=get_resource_data($resource);

if($resourcedata["lock_user"] > 0 && $resourcedata["lock_user"] != $userref)
    {
    $error = get_resource_lock_message($resourcedata["lock_user"]);
    http_response_code(403);
    exit($error);
    }

# Load the configuration for the selected resource type. Allows for alternative notification addresses, etc.
resource_type_config_override($resourcedata["resource_type"]);

# Not allowed to edit this resource?
if ((!get_edit_access($resource, $resourcedata["archive"],false,$resourcedata) || checkperm('A')) && $resource>0) {exit ("Permission denied.");}

hook("pageevaluation");

# Fetch alternative file data
$file=get_alternative_file($resource,$ref);if ($file===false) {exit("Alternative file not found.");}

# Tweak images
if (getval("tweak","")!="" && enforcePostRequest(false))
   {
   $tweak=getval("tweak","");
   switch($tweak)
      {
      case "rotateclock":
         $wait=tweak_preview_images($resource,270,0,"jpg",$ref);
         break;
      case "rotateanti":
         $wait=tweak_preview_images($resource,90,0,"jpg",$ref);
         break;
      case "restore":
         if ($enable_thumbnail_creation_on_upload)
            {
            $wait=create_previews($resource,false,"jpg",false,false,$ref);
            }
         break;
      }
   }



if (getval("name","")!="" && getval("tweak","")=="" && enforcePostRequest(false)) // do not do this during a tweak operation! 
	{
	hook("markmanualupload");
	# Save file data
	save_alternative_file($resource,$ref);
	// Check to see if we need to notify users of this change							
	if($notify_on_resource_change_days!=0)
		{								
		notify_resource_change($resource);
		}
	hook ("savealternatefiledata");
    $url_modal_part = "&modal=" . ($modal ? "true" : "");
	if (getval("tweak","")!=''){
		redirect ($baseurl_short."pages/alternative_file.php?resource=$resource&ref=$ref&search=".urlencode($search)."&offset=$offset&order_by$order_by&sort=$sort&archive=$archive" . $url_modal_part);
	} else {
		redirect ($baseurl_short."pages/alternative_files.php?ref=$resource&search=".urlencode($search)."&offset=$offset&order_by=$order_by&sort=$sort&archive=$archive" . $url_modal_part);
		}
	}

	
include "../include/header.php";
?>
<div class="BasicsBox">
<p>
<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/alternative_files.php?ref=<?php echo $resource?>&search=<?php echo urlencode($search)?>&offset=<?php echo $offset?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtomanagealternativefiles"]?></a>
</p>

<h1><?php echo $lang["editalternativefile"]; render_help_link('user/alternative-files');?></h1>

<form method="post" class="form" id="fileform" onsubmit="return <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Post(this, true);" action="<?php echo $baseurl_short?>pages/alternative_file.php?search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>">
<?php
if($modal)
    {
    ?>
    <input type="hidden" name="modal" value="true">
    <?php
    }
generateFormToken('fileform'); ?>
<input type=hidden name=ref value="<?php echo htmlspecialchars($ref) ?>">
<input type=hidden name=resource value="<?php echo htmlspecialchars($resource) ?>">

<?php //display preview if exists
$previews_exist = false;

if(file_exists(get_resource_path($resource , true, 'thm', true, 'jpg', true, 1, false, '', $ref, true)))
    {
    $previews_exist=true;
    $fileurl = get_resource_path($resource, false, 'thm', true, 'jpg', true, 1, false, date('Y-m-d H:i:s'), $ref);
    ?>
    <div class="Question" style="border:0px;"><img id="preview" align="top" src="<?php echo $fileurl; ?>" class="ImageBorder" style="margin-right:10px;"/><br /><br /><div class="clearerleft"> </div></div>
    <?php 
    } 
    ?>
<div class="Question">
<label><?php echo $lang["resourceid"]?></label><div class="Fixed"><?php echo htmlspecialchars($resource) ?></div>
<div class="clearerleft"> </div>
</div>

<div class="Question">
<label for="name"><?php echo $lang["name"]?></label><input type=text class="stdwidth" name="name" id="name" value="<?php echo htmlspecialchars($file["name"]) ?>" maxlength="100">
<div class="clearerleft"> </div>
</div>

<div class="Question">
<label for="name"><?php echo $lang["description"]?></label><input type=text class="stdwidth" name="description" id="description" value="<?php echo htmlspecialchars($file["description"]) ?>" maxlength="200">
<div class="clearerleft"> </div>
</div>
<?php hook('alternative_file_question', ''); ?>

<?php
	// if the system is configured to support a type selector for alt files, show it
	if (isset($alt_types) && count($alt_types) > 1){
		echo "<div class='Question'>\n<label for='alt_type'>".$lang["alternatetype"]."</label><select name='alt_type' id='alt_type'>";
		foreach($alt_types as $thealttype){
			//echo "thealttype:$thealttype: / filealttype:" . $file['alt_type'].":";
			if ($thealttype == $file['alt_type']){$alt_type_selected = " selected='selected'"; } else { $alt_type_selected = ''; }
			$thealttype = htmlspecialchars($thealttype,ENT_QUOTES);
			echo "\n   <option value='$thealttype' $alt_type_selected >$thealttype</option>";
		}
		echo "\n</select>\n<div class='clearerleft'> </div>\n</div>";
	}
?>
<?php if ($previews_exist){?>
<div class="Question" id="question_imagecorrection">
   <label><?php echo $lang["imagecorrection"]?><br/><?php echo $lang["previewthumbonly"]?></label>
   <select name="tweak" id="tweak" onChange="form.submit()">
   <option value=""><?php echo $lang["select"]?></option>
   <?php
	# On some PHP installations, the imagerotate() function is wrong and images are turned incorrectly.
	# A local configuration setting allows this to be rectified
   if (!$image_rotate_reverse_options)
   {
     ?>
     <option value="rotateclock"><?php echo $lang["rotateclockwise"]?></option>
     <option value="rotateanti"><?php echo $lang["rotateanticlockwise"]?></option>
     <?php
   }
  else
  {
     ?>
     <option value="rotateanti"><?php echo $lang["rotateclockwise"]?></option>
     <option value="rotateclock"><?php echo $lang["rotateanticlockwise"]?></option>
     <?php
  }
  ?>
  <option value="restore"><?php echo $lang["recreatepreviews"]?></option>
  
  <?php /*hook("moretweakingopt");*/?>
</select>
<div class="clearerleft"> </div>
</div>
<?php } ?>

<div class="QuestionSubmit">
<label for="buttons"> </label>			
<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
</div>
</form>
</div>

<?php		
include "../include/footer.php";
?>
