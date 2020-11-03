<?php
include "../include/db.php";

include "../include/authenticate.php";
include "../include/image_processing.php";

$ref=getvalescaped("ref","",true);
$offset=getval("offset",0);
$find=getvalescaped("find","");
$col_order_by=getvalescaped("col_order_by","name");
$order_by=getvalescaped("order_by","");
$sort=getval("sort","ASC");
$backto=getval("backto","");$backto=str_replace("\"","",$backto);#Prevent injection
$done=false;

# Fetch collection data
$collection_ref=$ref; // preserve collection id because tweaking resets $ref to resource ids
$collection=get_collection($ref);
if ($collection===false)
    {
	$error=$lang['error-collectionnotfound'];
	error_alert($error);
	exit();
	}

# Check access
if (!collection_writeable($ref))
    {
    exit($lang["no_access_to_collection"]);
    }
	
$resources=do_search("!collection".$ref);
$colcount=count($resources);

if (getval("tweak","")!="" && enforcePostRequest(false))
	{
	$tweak=getval("tweak","");
	switch($tweak)
		{
		case "rotateclock":
		foreach ($resources as $resource){
			tweak_preview_images($resource['ref'],270,0,$resource["preview_extension"]);
		}
		break;
		case "rotateanti":
		foreach ($resources as $resource){
			tweak_preview_images($resource['ref'],90,0,$resource["preview_extension"]);
		}
		break;
		case "gammaplus":
		foreach ($resources as $resource){
			tweak_preview_images($resource['ref'],0,1.3,$resource["preview_extension"]);
		}
		break;
		case "gammaminus":
		foreach ($resources as $resource){
			tweak_preview_images($resource['ref'],0,0.7,$resource["preview_extension"]);
		}
		break;
        case "restore":
        
        if ($enable_thumbnail_creation_on_upload && !(isset($preview_generate_max_file_size) && $resource["file_size"] > $preview_generate_max_file_size))        
            {
            foreach ($resources as $resource)
                {
                $ref=$resource['ref'];
                delete_previews($ref);
                if(!empty($resource['file_path'])){$ingested=false;}
                else{$ingested=true;}
                create_previews($ref,false,$resource["file_extension"],false,false,-1,true);
                }            
            refresh_collection_frame();
            }
        else if(!$enable_thumbnail_creation_on_upload && $offline_job_queue)
            {
            foreach ($resources as $resource)
                {
                $create_previews_job_data = array(
                    'resource' => $resource['ref'],
                    'thumbonly' => false,
                    'extension' => $resource["file_extension"],
                    'previewonly' => false,
                    'previewbased' => false,
                    'alternative' => -1,
                    'ignoremaxsize' => true,
                );
                $create_previews_job_success_text = str_replace('%RESOURCE', $resource['ref'], $lang['jq_create_previews_success_text']);
                $create_previews_job_failure_text = str_replace('%RESOURCE', $resource['ref'], $lang['jq_create_previews_failure_text']);
                job_queue_add('create_previews', $create_previews_job_data, '', '', $create_previews_job_success_text, $create_previews_job_failure_text);
                }
            $onload_message["text"] = $lang["recreatepreviews_pending"];
            }
        else
            {
            sql_query("UPDATE resource SET preview_attempts=0, has_image=0 WHERE ref IN ('" . implode("','",array_column($resources,"ref")) . "')");
            $onload_message["text"] = $lang["recreatepreviews_pending"];
            }
            
        $ref=$collection_ref; // restore collection id because tweaking resets $ref to resource ids
        break;
		}
	refresh_collection_frame();
	$done=true;
	}

	
include "../include/header.php";
?>
<p style="margin:7px 0 7px 0;padding:0;"><a onClick="return CentralSpaceLoad(this,true);" href="<?php if ($backto!=''){echo $backto;} else { echo $baseurl_short.'pages/search';}?>.php?search=!collection<?php echo urlencode($ref)?>&order_by=<?php echo urlencode($order_by) ?>&col_order_by=<?php echo urlencode($col_order_by) ?>&sort=<?php echo urlencode($sort) ?>&k=<?php echo urlencode($k) ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresults"]?></a></p><br />
<div class="BasicsBox">
<h1><?php echo $lang["editresourcepreviews"]?></h1>
<p><?php echo text("introtext")?></p>
<form method=post id="collectionform" action="<?php echo $baseurl_short?>pages/collection_edit_previews.php">
<?php generateFormToken("collectionform"); ?>
<input type=hidden value='<?php echo urlencode($ref) ?>' name="ref" id="ref"/>

<?php if (!checkperm("F*")) { ?>
<div class="Question">
<label><?php echo $lang["imagecorrection"]?><br/><?php echo $lang["previewthumbonly"]?></label><select class="stdwidth" name="tweak" id="tweak" onChange="document.getElementById('collectionform').submit();">
<option value=""><?php echo $lang["select"]?></option>
<?php //if ($resource["has_image"]==1) { 
?>
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
<?php if ($tweak_allow_gamma){?>
<option value="gammaplus"><?php echo $lang["increasegamma"]?></option>
<option value="gammaminus"><?php echo $lang["decreasegamma"]?></option>
<?php } ?>
<option value="restore"><?php echo $lang["recreatepreviews"]?></option>

?>
</select>
<div class="clearerleft"> </div>
</div>
<?php } 
?>

</div>
<?php		
if ($done){echo $lang['done'];}
include "../include/footer.php";
?>
