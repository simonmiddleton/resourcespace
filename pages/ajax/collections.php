<?php
$rsroot = dirname(dirname(dirname(__FILE__)));
include "{$rsroot}/include/db.php";

include "{$rsroot}/include/authenticate.php";
include_once "{$rsroot}/include/ajax_functions.php";

if(checkperm("b") && !(isset($anonymous_login) && $username == $anonymous_login && $anonymous_user_session_collection))
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
    "add_resource",
    "remove_resource",
);

if($action == "" || !in_array($action, $allowed_actions))
    {
    $fail_msg = str_replace("%key", "action", $lang["error-request-missing-key"]);
    ajax_send_response(400, ajax_response_fail(ajax_build_message($fail_msg)));
    }

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

if($action == "add_resource")
    {
    $resource = getval("resource", null, true);
    $collection = getval("collection", null, true);
    $smartadd = getval("smartadd", false);
    $size = getval("size", "");
    $addtype = getval("addtype", "");

    $collection_data = get_collection($collection);
    if($collection_data["type"] == COLLECTION_TYPE_UPLOAD)
        {
        ajax_send_response(200, ajax_response_fail(ajax_build_message($lang["cantmodifycollection"])));
        }

    $allow_add = true;
    // If collection has been shared externally need to check access and permissions
    $external_keys = get_collection_external_access($collection);
    if(is_array($external_keys) && !empty($external_keys))
        {
        if(checkperm("noex"))
            {
            $allow_add = false;
            }
        else
            {
            // Not permitted if share is open and access is restricted
            if(min(array_column($external_keys, "access")) < get_resource_access($add))
                {
                $allow_add = false;
                }
            }

        if(!$allow_add)
            {
            ajax_send_response(200, ajax_response_fail(ajax_build_message($lang["sharedcollectionaddblocked"])));
            }
        }

    if($allow_add)
        {
        if(!add_resource_to_collection($resource, $collection, $smartadd, $size, $addtype))
            {
            ajax_send_response(200, ajax_response_fail(ajax_build_message($lang["cantmodifycollection"])));
            }

        daily_stat("Add resource to collection", $resource);
        }

    ajax_send_response(200, ajax_response_ok_no_data());
    }

if($action == "remove_resource")
    {
    $resource = getval("resource", null, true);
    $collection = getval("collection", null, true);
    $smartadd = getval("smartadd", false);
    $size = getval("size", "");

    if(remove_resource_from_collection($resource, $collection, $smartadd, $size))
        {
        daily_stat("Removed resource from collection", $resource);
        ajax_send_response(200, ajax_response_ok_no_data());
        }

    ajax_send_response(200, ajax_response_fail(ajax_build_message($lang["cantmodifycollection"])));
    }