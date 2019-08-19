<?php
include_once '../include/db.php';
include_once '../include/general.php';
# External access support (authenticate only if no key was provided)
if(getvalescaped('k', '') == '')
    {
    include_once '../include/authenticate.php';
    }
include_once '../include/search_functions.php';
include_once '../include/collections_functions.php';
include_once '../include/csv_export_functions.php';

$search     = getvalescaped('search', '');
$restypes   = getvalescaped('restypes', '');
$order_by   = getvalescaped('order_by', '');
$archive    = getvalescaped('archive', '');
$sort       = getvalescaped('sort', '');
$starsearch = getvalescaped('starsearch', '');

if(getval("submit","") != "")
    {
    $personaldata   = (getvalescaped('personaldata', '') != '');
    $allavailable    = (getvalescaped('allavailable', '') != '');
    // Do the search again to get the results back
    $search_results = do_search($search, $restypes, $order_by, $archive, -1, $sort, false, $starsearch);
    
    log_activity($lang['csvExportResultsMetadata'],LOG_CODE_DOWNLOADED,$search . ($restypes == '' ? '' : ' (' . $restypes . ')'));
    
    if (!hook('csvreplaceheader'))
        {
        header("Content-type: application/octet-stream");
        header("Content-disposition: attachment; filename=search_results_metadata.csv");
        }
    
    echo generateResourcesMetadataCSV($search_results,$personaldata, $allavailable);
    exit();    
    }
else
    {
    include "../include/header.php";
    ?>
    <div class="BasicsBox">
        <!-- Below is intentionally not an AJAX POST -->
        <form method="post" action="<?php echo $baseurl_short?>pages/csv_export_results_metadata.php" >
            <?php
            generateFormToken("csv_export_results");
            ?>
    
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search) ?>" />
            <input type="hidden" name="restypes" value="<?php echo htmlspecialchars($restypes) ?>" />
            <input type="hidden" name="order_by" value="<?php echo htmlspecialchars($order_by) ?>" />
            <input type="hidden" name="archive" value="<?php echo htmlspecialchars($archive) ?>" />
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort) ?>" />
            <input type="hidden" name="starsearch" value="<?php echo htmlspecialchars($starsearch) ?>" />
            
            <h1><?php echo $lang["csvExportResultsMetadata"];render_help_link("user/csv_export");?></h1>
   
   
            <div class="Question" id="question_personal">
                <label for="personaldata"><?php echo htmlspecialchars($lang['csvExportResultsMetadataPersonal']) ?></label>
                <input name="personaldata" id="personaldata" type="checkbox" value="true" style="margin-top:7px;"> 
                <div class="clearerleft"> </div>
            </div>
            
            <div class="Question" id="question_personal">
                <label for="allavailable"><?php echo htmlspecialchars($lang['csvExportResultsMetadataAll']) ?></label>
                <input name="allavailable" id="allavailable" type="checkbox" value="true" style="margin-top:7px;"> 
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
    }



