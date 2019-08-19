<?php
/**
 * Batch resource replace
 * 
 */
include "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php";
if (!checkperm("t"))
    {
    exit ("Permission denied.");
    }
    
$no_exif        = getval('no_exif', '');
$filename_field = getval("filename_field",0,true);
$batch_replace_min   = getval("batch_replace_min",0,true);
$batch_replace_max   = getval("batch_replace_max",0,true);
$batch_replace_col   = getval("batch_replace_col",0,true);
$mode           = getval("batch_replace_mode","upload");
$submitted      = getval("submit","") != "";

if($submitted)
    {    
    if($mode == "upload")
        {
        $upload_params = array();
        $upload_params["replace"]           = "true";
        $upload_params["filename_field"]    = $filename_field;
        $upload_params["batch_replace_col"] = $batch_replace_col;
        $upload_params["batch_replace_min"] = $batch_replace_min;
        $upload_params["batch_replace_max"] = $batch_replace_max;
        $upload_params["no_exif"]           = $no_exif;
        
        redirect(generateURL($baseurl_short . "pages/upload_plupload.php", $upload_params));
        exit();
        }
    elseif($mode == "fetch_local" && $offline_job_queue)
        {
        // Create offline job to retrieve files
        $replace_batch_local_data = array(
            'import_path'       => $batch_replace_local_folder,
            'filename_field'    => $filename_field,
            'batch_replace_col' => $batch_replace_col,
            'batch_replace_min' => $batch_replace_min,
            'batch_replace_max' => $batch_replace_max,
            'no_exif'           => $no_exif
        );
        
        job_queue_add(
            'replace_batch_local',
            $replace_batch_local_data,
            '',
            '',
            $lang["oj-batch-replace-local-success-text"],
            $lang["oj-batch-replace-local-failure-text"]);

        $info_text = $lang["replacebatch_job_created"];
        }
    }

// Get list of fields to allow selection of field containing file name to folder path
$allfields=get_resource_type_fields();
//print_r($allfields);

include "../include/header.php";

if (isset($info_text))
    {?>
    <div class="PageInformal"><?php echo $info_text?></div>
    <?php
    }

?>

<h1><?php echo $lang["replaceresourcebatch"] ?></h1>

<p><?php echo $lang["batch_replace_filename_intro"];render_help_link("resourceadmin/batch-replace");?></p>

<form action="<?php echo $baseurl_short?>pages/upload_replace_batch.php" >

<?php generateFormToken("upload_replace_batch"); ?>
<input id="batch_replace_mode" type="hidden" name="batch_replace_mode" value="<?php echo htmlspecialchars($mode); ?>" />
<input id="submit" type="hidden" name="submit" value="true" />
    
<div class="Question">
    <label for="use_resourceid"><?php echo $lang["batch_replace_use_resourceid"]?></label>
    <input type="checkbox" value="yes" <?php if ($filename_field == 0) {echo " checked ";} ?> name="use_resourceid" id="use_resourceid" onClick="if(this.checked){jQuery('#question_filename_field').slideUp();jQuery('#filename_field').prop('disabled',true);}else{jQuery('#question_filename_field').slideDown();jQuery('#filename_field').prop('disabled',false);}" />
    <div class="clearerleft"> </div>
</div>

<div class="Question" id="question_filename_field" <?php if ($filename_field == 0) {echo "style='display:none;'";}?>>
    <label for="filename_field"><?php echo $lang["batch_replace_filename_field_select"]?></label>
    <select  class="stdwidth" name="filename_field" id="filename_field">

    <option value="0" >
    <?php

    foreach ($allfields as $metadatafield)
        {
        ?>
        <option value="<?php echo $metadatafield["ref"] ?>" <?php if($metadatafield["ref"] == $filename_field){ echo " selected";} ?>>
        <?php echo i18n_get_translated($metadatafield["title"]) ?>	
        </option>    
        <?php
        }
    ?>
    </select>
    <div class="clearerleft"> </div>
</div>


<div class="Question">
    <label for="batch_replace_col"><?php echo $lang["replacebatch_collection"]?></label>
    <input type="text" class="shrtwidth" value="<?php echo ($batch_replace_col > 0) ? htmlspecialchars($batch_replace_col) : ""; ?>" name="batch_replace_col" id="batch_replace_col" />
    <div class="clearerleft"> </div>
</div>

<div class="Question">
    <label for="batch_replace_min"><?php echo $lang["replacebatch_resource_min"]?></label>
    <input type="text" class="shrtwidth" value="<?php echo ($batch_replace_min > 0) ? htmlspecialchars($batch_replace_min) : ""; ?>" name="batch_replace_min" id="batch_replace_min" />
    <div class="clearerleft"> </div>
</div>

<div class="Question">
    <label for="batch_replace_max"><?php echo $lang["replacebatch_resource_max"]?></label>
    <input type="text" class="shrtwidth" value="<?php echo ($batch_replace_max > 0) ?  htmlspecialchars($batch_replace_max) : ""; ?>" name="batch_replace_max" id="batch_replace_max" />
    <div class="clearerleft"> </div>
</div>

<div class="Question">
    <label for="no_exif"><?php echo $lang["no_exif"]?></label>
    <input type=checkbox <?php if ((!$metadata_read_default && !$submitted) || $no_exif == "yes"){ echo " checked "; } ?> id="no_exif" name="no_exif" value="yes">
    <div class="clearerleft"> </div>
</div>

<?php
if($offline_job_queue)
    {?>
    <div class="Question">
        <label for="replace_batch_local"><?php echo $lang["replacebatchlocalfolder"]?></label>
        <input type="checkbox" value="yes" <?php if($mode == "fetch_local") {echo " checked";} ?> name="replace_batch_local" id="replace_batch_local" onClick="if(this.checked){document.getElementById('batch_replace_mode').value = 'fetch_local';}else{document.getElementById('batch_replace_mode').value = 'upload'}" />
        <div class="clearerleft"> </div>
    </div>
    
   
    <?php
    }
    ?>

<div class="Question">
<input type="submit" value="<?php echo $lang["start"]; ?>" name="upload" id="upload_button" onClick="CentralSpacePost(this,true);" />
<div class="clearerleft"> </div>
</div>

</form>

<?php


include "../include/footer.php";
