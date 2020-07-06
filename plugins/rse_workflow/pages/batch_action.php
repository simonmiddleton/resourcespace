<?php
include_once '../../../include/db.php';
include_once '../../../include/authenticate.php';
include_once '../../../include/ajax_functions.php';
include_once '../include/rse_workflow_functions.php';

$modal = (getval("modal", "") == "true");
$form_action_extra = array();
$ajax = getval("ajax", "") == "true";
$process_action = getval("process_action", "") == "true";

$action = getval("action", null, true);
if(is_null($action))
    {
    if($ajax && $process_action)
        {
        ajax_send_response(400, ajax_response_fail(ajax_build_message($lang["rse_workflow_err_invalid_action"])));
        }
    trigger_error($lang["rse_workflow_err_invalid_action"]);
    }
$action = rse_workflow_get_actions("", $action);
if(!is_array($action) || empty($action))
    {
    if($ajax && $process_action)
        {
        ajax_send_response(400, ajax_response_fail(ajax_build_message($lang["rse_workflow_err_invalid_action"])));
        }
    trigger_error($lang["rse_workflow_err_invalid_action"]);
    }
$action = $action[0];

$wf_states = rse_workflow_get_archive_states();
if(!array_key_exists($action["statusto"], $wf_states))
    {
    if($ajax && $process_action)
        {
        ajax_send_response(400, ajax_response_fail(ajax_build_message($lang["rse_workflow_err_missing_wfstate"])));
        }
    trigger_error($lang["rse_workflow_err_missing_wfstate"]);
    }
$to_wf_state = $wf_states[$action["statusto"]];

$collection = getval("collection", null, true);
if(!is_null($collection) && checkperm("b"))
    {
    if($ajax && $process_action)
        {
        ajax_unauthorized();
        }
    exit($lang["error-permissiondenied"]);
    }

// Determine resources affected (effectively runs a search to determine if action is valid for each resource)
$search = getvalescaped("search", "");
$restypes = getvalescaped("restypes", "");
if (strpos($search,"!")!==false) {$restypes="";}
$order_by = getvalescaped("order_by", "relevance");
$archive = getvalescaped("archive", "0");
$per_page = getvalescaped("per_page", null, true);
$offset = getvalescaped("offset", null, true);
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
    -1,
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

$affected_resources = array_filter($resources, function($resource) use ($action)
    {
    return rse_workflow_validate_action($action, $resource);
    });
$affected_resources_count = count($affected_resources);

if($ajax && $process_action)
    {
    if(empty($affected_resources))
        {
        ajax_send_response(200, ajax_response_ok_no_data());
        }

    foreach($resources as $resource)
        {
        update_archive_status($resource["ref"], $action["statusto"], $resource["archive"]);
        }

    // send user a message of confirmation with link to all resources in that new wf state
    $url = generateURL(
        "{$baseurl}/pages/search.php",
        array(
            "search" => "",
            "archive" => $action["statusto"],
            "resetrestypes" => "true",
        ));
    $text = str_replace("%wf_name", $to_wf_state["name"], $lang["rse_workflow_confirm_resources_moved_to_state"]);
    message_add($userref, $text, $url);

    ajax_send_response(200, ajax_response_ok(array_column($affected_resources, "ref")));
    }

$form_action = generateURL($_SERVER['PHP_SELF'],
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

        "action" => $action["ref"],
        "collection" => $collection,
    ),
    $form_action_extra
);
$action_csrf_data  = " data-csrf-token-identifier=\"{$CSRF_token_identifier}\"";
$action_csrf_data .= " data-csrf-token=\"" . generateCSRFToken($usersession, "process_wf_action{$action["ref"]}") . "\"";
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

    <p><?php echo str_replace("%wf_name", $to_wf_state["name"], $lang["rse_workflow_confirm_to_state"]); ?></p>
    <p><?php echo str_replace("%count", $affected_resources_count, $lang["rse_workflow_affected_resources"]); ?></p>
    <div class="QuestionSubmit">
        <label></label>
        <button type="button" onclick="ModalClose();"><?php echo $lang["cancel"]; ?></button>
        <button type="button" onclick="process_wf_action(this);" <?php echo $action_csrf_data; ?>><?php echo $lang["ok"]; ?></button>
    </div>
</div>
<script>
function process_wf_action(e)
    {
    var button = jQuery(e);
    var csrf_token_identifier = button.data("csrf-token-identifier");
    var csrf_token = button.data("csrf-token");

    var default_post_data = {};
    default_post_data[csrf_token_identifier] = csrf_token;
    var post_data = Object.assign({}, default_post_data);
    post_data.ajax = true;
    post_data.process_action = true;

    CentralSpaceShowLoading();
    jQuery.ajax({
        type: 'POST',
        url: "<?php echo $form_action; ?>",
        data: post_data,
        dataType: "json"
        })
        .done(function(response)
            {
            if(typeof response.status !== "undefined" && response.status == "success")
                {
                console.debug("response.data = %o", response.data);
                }
            })
        .fail(function(data, textStatus, jqXHR)
            {
            if(typeof data.responseJSON === 'undefined')
                {
                return;
                }

            var response = data.responseJSON;
            styledalert(jqXHR, response.data.message);
            })
        .always(function()
            {
            CentralSpaceHideLoading();
            ModalClose();
            });

    return;
    }
</script>
<?php
include "../../../include/footer.php";