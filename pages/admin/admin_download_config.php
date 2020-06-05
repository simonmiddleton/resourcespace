<?php
include '../../include/db.php';
include '../../include/authenticate.php'; if(!(checkperm('a') && checkperm("v"))) { exit('Permission denied.');}
include_once '../../include/config_functions.php';

if(!extension_loaded("zip"))
    {
    $error = str_replace("%%MODULE%%","php-zip",$lang["error_server_missing_module"]);
    }
elseif(!$offline_job_queue)
    {
    $error = str_replace("%%CONFIG_OPTION%%","\$offline_job_queue",$lang["error_check_config"]);
    }
elseif(!isset($mysql_bin_path))
    {
    $error = str_replace("%%CONFIG_OPTION%%","\$mysql_bin_path",$lang["error_check_config"]);
    }
elseif(!$system_download_config)
    {
    $error = str_replace("%%CONFIG_OPTION%%","\$system_download_config",$lang["error_check_config"]);
    }

$export = getval("export","") != "";
$exportcollection = getval("exportcollection",0,true);
$obfuscate = ($system_download_config_force_obfuscation || getval("obfuscate","") !== "");
$separatesql = getval("separatesql","") !== "";

if (!isset($error) && $export!="" && enforcePostRequest(false))
	{
    $exporttables = get_export_tables($exportcollection);

    // Create offline job
    $job_data=array();
    $job_data["exporttables"]   = $exporttables;
    $job_data["obfuscate"]      = $obfuscate;
    $job_data["userref"]        = $userref;
    $job_data["separatesql"]    = $separatesql;
    
    $job_code = "system_export_" . md5($userref . $exportcollection . ($obfuscate ? "1" : "0") . ($separatesql ? "1" : "0")); // unique code for this job, used to prevent duplicate job creation.
    $jobadded=job_queue_add("config_export",$job_data,$userref,'',$lang["exportcomplete"],$lang["exportfailed"],$job_code);
    if($jobadded!==true)
        {
        $message = $lang["oj-creation-failure-text"] . " : " . $jobadded;  
        }
    else
        {
        $message = $lang["oj-creation-success"];
        }
    }


// This page will create an offline job that creates a zip file containing system configuration information and data

include '../../include/header.php';

?>
<div class="BasicsBox">
    <p>
        <a href="<?php echo $baseurl_short; ?>pages/admin/admin_home.php" onClick="return CentralSpaceLoad(this, true);"><?php echo LINK_CARET_BACK ?><?php echo $lang['back']; ?></a>
    </p>
    <h1><?php echo $lang['exportdata']; ?></h1>
    <?php
    if (isset($error))
        {
        echo "<div class=\"FormError\">" . $lang["error"] . ":&nbsp;" . htmlspecialchars($error) . "</div>";
        }

    elseif (isset($message))
        {
        echo "<div class=\"PageInformal\">" . htmlspecialchars($message) . "</div>";
        }
    ?>
    <p><?php echo $lang['exportdata-instructions']; render_help_link("admin/download-config");?></p>
    
    <form method="post" action="<?php echo $baseurl_short?>pages/admin/admin_download_config.php" onSubmit="return CentralSpacePost(this,true);">
        <input type="hidden" name="export" value="true" />

        <?php
        if(!$system_download_config_force_obfuscation)
            {
            ?>
            <div class="Question">
            <label><?php echo $lang['exportobfuscate']; ?></label>
            <input type="checkbox" name="obfuscate" value="1"  <?php echo $obfuscate? "checked" : "";?> />
            <div class="clearerleft"> </div>
            </div>
            <?php
            }?>        

        <div class="Question">
            <label><?php echo $lang['exportcollection']; ?></label>
            <input type="number" name="exportcollection" value="<?php echo (int)$exportcollection; ?>"></input>
            <div class="clearerleft"> </div>
        </div>

        <div class="Question">
            <label><?php echo $lang['export_separate_sql']; ?></label>
            <input type="checkbox" name="separatesql" value="1" <?php echo $separatesql? "checked" : "";?> />
            <div class="clearerleft"> </div>
        </div>


        <div class="Question" <?php if(isset($error)){echo "style=\"display: none;\"";}?>>
            <label for="export"></label>
            <input type="button" name="export" value="<?php echo $lang["export"]; ?>" onClick="jQuery(this.form).submit();" >
            <div class="clearerleft"> </div>
        </div>


        <?php generateFormToken("download_config"); ?>
    </form>
    
</div>
<?php


include '../../include/footer.php';
