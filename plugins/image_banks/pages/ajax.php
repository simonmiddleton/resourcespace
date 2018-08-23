<?php
$rs_root = dirname(dirname(dirname(__DIR__)));
include_once "{$rs_root}/include/db.php";
include_once "{$rs_root}/include/general.php";
include_once "{$rs_root}/include/authenticate.php";
include_once "{$rs_root}/include/resource_functions.php";
include_once "{$rs_root}/include/image_processing.php";

if(!(checkperm("c") || checkperm("d")))
    {
    http_response_code(401);
    $return["error"] = array(
        "title"  => "Unauthorized",
        "detail" => $lang["error-permissiondenied"]);

    echo json_encode($return);
    exit();
    }

$return = array();

$original_file_url = getval("original_file_url", "");

if(!\ImageBanks\validFileSource($original_file_url, $image_banks_loaded_providers))
    {
    $log_activity_note = str_replace("%FILE", $original_file_url, $lang["image_banks_bad_file_create_attempt"]);
    log_activity($log_activity_note, LOG_CODE_SYSTEM, null, 'user', null, null, null, null, $userref, false);

    $original_file_url = "";
    }

if($original_file_url != "")
    {
    // Clear the user template and then copy resource from user template. This should deal with archive state permissions
    // and put the resource in active state if user has access to it
    clear_resource_data(0 - $userref);
    $new_resource_ref = copy_resource(0 - $userref, $default_resource_type);
    if($new_resource_ref === false)
        {
        $new_resource_ref = create_resource($default_resource_type, 999, $userref);
        }

    if(!$new_resource_ref)
        {
        http_response_code(500);
        $return["error"] = array(
            "title"  => $lang["image_banks_create_new_resource"],
            "detail" => $lang["image_banks_unable_to_create_resource"]
        );

        echo json_encode($return);
        exit();
        }

    // We intentionally want to extract embedded metadata from external Image Bank Provider
    if(!upload_file_by_url($new_resource_ref, false, false, false, $original_file_url))
        {
        http_response_code(500);
        $return["error"] = array(
            "title"  => $lang["image_banks_create_new_resource"],
            "detail" => str_replace("%RESOURCE", $new_resource_ref, $lang["image_banks_unable_to_upload_file"])
        );

        echo json_encode($return);
        exit();
        }

    http_response_code(200);
    $return["data"] = array(
        "new_resource_ref" => $new_resource_ref,
    );
    echo json_encode($return);
    exit();
    }


// If by this point we still don't have a response for the request, create one now telling client this is a bad request
if(0 === count($return))
    {
    http_response_code(400);
    $return["error"] = array(
        "title"  => $lang["image_banks_bad_request_title"],
        "detail" => str_replace("%FILE", __FILE__, $lang["image_banks_bad_request_detail"]));
    }

echo json_encode($return);
exit();