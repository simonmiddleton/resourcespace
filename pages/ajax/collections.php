<?php
$rsroot = dirname(dirname(dirname(__FILE__)));
include "{$rsroot}/include/db.php";
include_once "{$rsroot}/include/general.php";
include "{$rsroot}/include/authenticate.php";
include_once "{$rsroot}/include/ajax_functions.php";
include_once "{$rsroot}/include/collections_functions.php";
include_once "{$rsroot}/include/render_functions.php";
// include_once "{$rsroot}/include/resource_functions.php";

if(checkperm("b"))
    {
    ajax_unauthorized();
    }

$return = array();
$action = trim(getval("action", ""));
$allowed_actions = array(
    "clear_selection_collection_resources",
    "get_selected_resources_counter",
    "render_selected_resources_counter",
    "render_edit_selected_btn",
    "render_clear_selected_btn",
    "remove_selected_from_collection",
);

if($action == "" || !in_array($action, $allowed_actions))
    {
    $fail_msg = str_replace("%key", "action", $lang["error-request-missing-key"]);
    ajax_send_response(400, ajax_response_fail(ajax_build_message($fail_msg)));
    }

// todo: move actions from pages/collections.php here (break them apart if needed to cover single responsibilities)

if($action == "clear_selection_collection_resources")
    {
    remove_all_resources_from_collection($USER_SELECTION_COLLECTION);
    ajax_send_response(200, ajax_response_ok_no_data());
    }

if($action == "get_selected_resources_counter")
    {
    $counter = count(get_collection_resources($USER_SELECTION_COLLECTION));
    ajax_send_response(200, ajax_response_ok(array("selected" => $counter)));
    }

if($action == "render_selected_resources_counter")
    {
    $counter = count(get_collection_resources($USER_SELECTION_COLLECTION));
    ajax_send_text_response(200, render_selected_resources_counter($counter));
    }

if($action == "render_clear_selected_btn")
    {
    ajax_send_text_response(200, render_clear_selected_btn());
    }

if($action == "render_edit_selected_btn")
    {
    include_once "{$rsroot}/include/search_do.php";
    include_once "{$rsroot}/include/search_functions.php";

    $restypes = getval("restypes", "");
    $archive = getval("archive", "");
    ajax_send_text_response(200, render_edit_selected_btn());
    }

if($action == "remove_selected_from_collection")
    {
    if(!collection_readable($usercollection))
        {
        $fail_msg = str_replace("%ref", $usercollection, $lang["error-collection-unreadable"]);
        ajax_send_response(400, ajax_response_fail(ajax_build_message($fail_msg)));
        }

    $selected_resources       = get_collection_resources($USER_SELECTION_COLLECTION);
    $usercollection_resources = get_collection_resources($usercollection);
    
    $refs_to_remove = array_intersect($selected_resources, $usercollection_resources);
    foreach(array_intersect($selected_resources, $usercollection_resources) as $ref)
        {
        remove_resource_from_collection($ref, $usercollection);
        }

    ajax_send_response(200, ajax_response_ok_no_data());
    }