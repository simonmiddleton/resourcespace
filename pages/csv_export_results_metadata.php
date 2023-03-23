<?php
include_once '../include/db.php';
include_once '../include/authenticate.php';
include_once '../include/csv_export_functions.php';

$search     = getval('search', '');
$restypes   = getval('restypes', '');
$order_by   = getval('order_by', '');
$archive    = getval('archive', '');
$access     = getval('access', null, true);
$sort       = getval('sort', '');
$offline    = getval("process_offline","") != "";
$submitted  = getval("submit","") != "";
$personaldata   = (getval('personaldata', '') != '');
$allavailable    = (getval('allavailable', '') != '');

$resources_found = count(do_search($search, $restypes, $order_by, $archive, 1, $sort, false, DEPRECATED_STARSEARCH, false, false, '', false, false, false, false, false, $access));

$resources_to_process = array();
$search_chunk_size = 100000;
$chunk_offset = 0; // Return data in batches. Required for particularly large csv export where there is a risk of PHP memory_limit being exceeded by search returning too many results.

while ($chunk_offset < $resources_found)
    {
    $search_results = do_search($search, $restypes, $order_by, $archive, array($chunk_offset, $search_chunk_size), $sort, false, DEPRECATED_STARSEARCH, false, false, '', false, false, true, false, false, $access, null);
    $resources_to_process = array_merge($resources_to_process, array_column($search_results['data'], "ref"));
    $chunk_offset = $chunk_offset + $search_chunk_size;
    }

$resultcount = count($resources_to_process);
if($resultcount == 0)
    {
    $error = $lang["noresourcesfound"]; 
    }

if($submitted && $resultcount > 0)
    {

    $findstrings = array("%%SEARCH%%","%%TIME%%");
    $replacestrings = array(safe_file_name($search),date("Ymd-H:i",time()));
    $csv_filename = str_replace($findstrings, $replacestrings, $lang["csv_export_filename"]);

    $csv_filename_noext = strip_extension($csv_filename);
   
    if($offline || (($resultcount > $metadata_export_offline_limit) && $offline_job_queue))
        {
        // Generate offline job 
        $job_data=array();
        $job_data["personaldata"]   = $personaldata;
        $job_data["allavailable"]   = $allavailable;
        $job_data["exportresources"]= $resources_to_process;
        $job_data["search"]         = $search;
        $job_data["restypes"]       = $restypes;
        $job_data["archive"]        = $archive;
        $job_data["access"]         = $access;
        $job_data["sort"]           = $sort;

        $job_code = "csv_metadata_export_" . md5($userref . json_encode($job_data)); // unique code for this job, used to prevent duplicate job creation.
        $jobadded = job_queue_add("csv_metadata_export",$job_data,$userref,'',$lang["csv_export_file_ready"] . " : " . $csv_filename ,$lang["download_file_creation_failed"],$job_code);
        if((string)(int)$jobadded !== (string)$jobadded)
            {
            $message = $lang["oj-creation-failure-text"];
            }
        else
            {
            $message = str_replace('%%JOBNUMBER%%', $jobadded, $lang['oj-creation-success']);
            }
        }
    else
        {
        log_activity($lang['csvExportResultsMetadata'],LOG_CODE_DOWNLOADED,$search . ($restypes == '' ? '' : ' (' . $restypes . ')'));
    
        if (!hook('csvreplaceheader'))
            {
            header("Content-type: application/octet-stream");
            header("Content-disposition: attachment; filename=" . $csv_filename_noext  . ".csv");
            }
        
        generateResourcesMetadataCSV($resources_to_process, $personaldata, $allavailable);
        exit();   
        } 
    }

include "../include/header.php";
if (isset($error))
    {
    echo "<div class=\"FormError\">" . $lang["error"] . ":&nbsp;" . htmlspecialchars($error) . "</div>";
    }

elseif (isset($message))
    {
    echo "<div class=\"PageInformal\">" . htmlspecialchars($message) . "</div>";
    }
?>
<div class="BasicsBox">
    <!-- Below is intentionally not an AJAX POST -->
    <form method="post" action="<?php echo $baseurl_short?>pages/csv_export_results_metadata.php" >
        <?php
        generateFormToken("csv_export_results");
        ?>

        <input type="hidden" name="search" value="<?php echo htmlspecialchars((string)$search) ?>" />
        <input type="hidden" name="restypes" value="<?php echo htmlspecialchars((string)$restypes) ?>" />
        <input type="hidden" name="order_by" value="<?php echo htmlspecialchars((string)$order_by) ?>" />
        <input type="hidden" name="archive" value="<?php echo htmlspecialchars((string)$archive) ?>" />
        <input type="hidden" name="access" value="<?php echo htmlspecialchars((string)$access) ?>" />
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars((string)$sort) ?>" />
        
        <h1><?php echo $lang["csvExportResultsMetadata"];render_help_link("user/csv_export");?></h1>


        <div class="Question" id="question_personal">
            <label for="personaldata"><?php echo htmlspecialchars($lang['csvExportResultsMetadataPersonal']) ?></label>
            <input name="personaldata" id="personaldata" type="checkbox" value="true" style="margin-top:7px;" <?php if($personaldata){echo " checked ";} ?>> 
            <div class="clearerleft"> </div>
        </div>
        
        <div class="Question" id="question_personal">
            <label for="allavailable"><?php echo htmlspecialchars($lang['csvExportResultsMetadataAll']) ?></label>
            <input name="allavailable" id="allavailable" type="checkbox" value="true" style="margin-top:7px;" <?php if($allavailable){echo " checked ";} ?>> 
            <div class="clearerleft"> </div>
        </div>

        <div class="Question" >
            <label for="process_offline"><?php echo $lang["csv_export_offline_option"] ?></label>
            <?php 
            if($offline_job_queue)
                {
                echo "<input type='checkbox' id='process_offline' name='process_offline' value='1' " . ($resultcount > $metadata_export_offline_limit ? "onclick='styledalert(\"" .  $lang["csvExportResultsMetadata"]  . "\",\"" . str_replace("%%RESOURCE_COUNT%%",$metadata_export_offline_limit,$lang['csv_export_offline_only']) . "\");return false;' checked" : ($submitted && !$offline ? "" : " checked ")) . ">";
                }
            else
                {
                echo "<div class='Fixed'>" . $lang["offline_processing_disabled"] . "</div>";
                }?>
            <div class="clearerleft"> </div>
        </div>


        <div class="QuestionSubmit">
            <label for="buttons"> </label>        
            <input type="hidden" name="submit" value="true" />  
            <input name="submit" type="submit" id="submit" value="&nbsp;&nbsp;<?php echo $lang["action-download"]?>&nbsp;&nbsp;" />
        </div>

    </form>
</div>
<?php
include "../include/footer.php";



