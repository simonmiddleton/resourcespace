<?php
include_once '../../../include/db.php';
include_once '../../../include/general.php';
include_once '../../../include/authenticate.php'; if(!checkperm('a')) { exit($lang['error-permissiondenied']); }
include_once '../../../include/search_functions.php';
include_once '../../../include/resource_functions.php';
include_once '../include/rse_workflow_functions.php';

$modal = (getval("modal", "") == "true");
$form_action_extra = array();

$action = getval("action", null, true);
if(is_null($action))
    {
    trigger_error($lang["rse_workflow_err_invalid_action"]);
    }
$action = rse_workflow_get_actions("", $action);
if(!is_array($action) || empty($action))
    {
    trigger_error($lang["rse_workflow_err_invalid_action"]);
    }

$wf_states = rse_workflow_get_archive_states();
if(!array_key_exists($action[0]["statusto"], $wf_states))
    {
    trigger_error($lang["rse_workflow_err_missing_wfstate"]);
    }
$to_wf_state = $wf_states[$action[0]["statusto"]];

$collection = getval("collection", null, true);
if(!is_null($collection) && checkperm("b"))
    {
    exit($lang["error-permissiondenied"]);
    }

// Determine resources affected (effectively runs a search to determine if action is valid for each resource)
$search = getvalescaped("search", "");
$restypes = getvalescaped("restypes", "");
$order_by = getvalescaped("order_by", "relevance");
$archive = getvalescaped("archive", "0");
$per_page = getvalescaped("per_page", null, true);
$offset = getvalescaped("offset", null, true);
$fetchrows = (!is_null($per_page) || !is_null($offset) ? (int) $per_page + (int) $offset : -1);
$sort = getvalescaped("sort", "desc");
$starsearch = getvalescaped("starsearch", 0, true);
$recent_search_daylimit = getvalescaped("recent_search_daylimit", "");
$go = getvalescaped("go", "");

// Override if needed
if(!is_null($collection))
    {
    $search = "!collection{$collection}";
    $order_by = $default_collection_sort;
    $form_action_extra["collection"] = $collection;
    }

$result = do_search(
    $search,
    $restypes,
    $order_by,
    $archive,
    $fetchrows,
    $sort,
    false, # $access_override
    $starsearch,
    false, # $ignore_filters 
    false, # $return_disk_usage
    $recent_search_daylimit,
    $go,
    true, # $stats_logging
    false, # $return_refs_only
    true # $editable_only
);

$resources = array();
if(is_array($result) && count($result) > 0)
    {
    $resources = $result;
    }

$affected_resources = array_reduce($resources, function($carry, $resource) use ($action)
    {
    $action = $action[0];
    return (rse_workflow_validate_action($action, $resource) ? ++$carry : $carry);
    }, 0);

$form_action = generateURL("{$baseurl}/pages/edit.php",
    array(
        "search" => $search,
        "restypes" => $restypes,
        "order_by" => $order_by,
        "archive" => $archive,
        "per_page" => $per_page,
        "offset" => $offset,
        "sort" => $sort,
        "starsearch" => $starsearch,
        "recent_search_daylimit" => $recent_search_daylimit,
        "go" => $go,
    ),
    $form_action_extra
);
include_once '../../../include/header.php';
?>
<div class="BasicsBox">
    <div class="RecordHeader">
        <div class="BackToResultsContainer">
            <div class="backtoresults">
            <?php
            if($modal)
                {
                ?>
                <a href="#" class="closeLink fa fa-times" onclick="ModalClose();"></a>
                <?php
                }
                ?>
            </div>
        </div>
        <h1><?php echo $lang["rse_workflow_confirm_batch_wf_change"]; ?></h1>
    </div>

    <p><?php echo str_replace("%wf_ref", $to_wf_state["name"], $lang["rse_workflow_confirm_to_state"]); ?></p>
    <p><?php echo str_replace("%count", $affected_resources, $lang["rse_workflow_affected_resources"]); ?></p>
    <form id="rse_workflow_process_batch_action"
          name="rse_workflow_process_batch_action"
          class="modalform"
          method="POST"
          action="<?php echo $form_action; ?>"
          onsubmit="return CentralSpacePost(this, true);">
        <?php generateFormToken("rse_workflow_process_batch_action"); ?>
        <input type="hidden" name="editsearchresults" value="true">
        <div class="QuestionSubmit">
            <label></label>
            <input type="button" name="cancel" value="<?php echo $lang["cancel"]; ?>" onclick="ModalClose();"></input>
            <input type="submit" name="ok" value="<?php echo $lang["ok"]; ?>"></input>
            <div class="clearleft"></div>
        </div>
    </form>
</div>
<?php
include "../../../include/footer.php";