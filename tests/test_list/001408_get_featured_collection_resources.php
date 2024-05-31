<?php
command_line_only();

// Setup
$collection_allow_not_approved_share_cache = $GLOBALS["collection_allow_not_approved_share"];

$original_user_data = $userdata;
$user_admin = new_user("test_001401_admin", 1);
if($user_admin === false)
    {
    $user_admin = ps_value("SELECT ref AS `value` FROM user WHERE username = 'test_001408_admin'", array(), 0);
    }
if($user_admin === 0)
    {
    echo "Setup test: users - ";
    return false;
    }
$user_admin_data = get_user($user_admin);
$orig_userpermissions = $userpermissions;
$userpermissions = ["s","j*","f*","e0","e3","c","D","h"];

$resources["active"][]  = create_resource(1, 0);
$resources["active"][]  = create_resource(1, 0);
$resources["deleted"][] = create_resource(1, 3);
$resources["deleted"][] = create_resource(1, 3);

$parent                 = create_collection($user_admin,"test_001408_parent");
$collections["all"]     = create_collection($user_admin,"test_001408_all");
$collections["active"]  = create_collection($user_admin,"test_001408_active");
$collections["deleted"] = create_collection($user_admin,"test_001408_deleted");

collection_add_resources($collections["all"],array_merge($resources["active"],$resources["deleted"]));
collection_add_resources($collections["active"],$resources["active"]);
collection_add_resources($collections["deleted"],$resources["deleted"]);

$coldata = array(
    "featured_collections_changes" => array(
        "update_parent" => $parent,
        "force_featured_collection_type" => true,
        "thumbnail_selection_method" => $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"],
    ),
);
foreach ($collections as $collection) {
    save_collection($collection,$coldata);
}

$use_cases = [
    [
        "name" => "01 - Only approved from all",
        "collection" => $collections["all"],
        "result"  => $resources["active"],
        "collection_allow_not_approved_share" => false,
    ],
    [
        "name" => "02 - All from all",
        "collection" => $collections["all"],
        "result"  => array_merge($resources["active"],$resources["deleted"]),
        "collection_allow_not_approved_share" => true,
    ],
    [
        "name" => "03 - Only approved from active",
        "collection" => $collections["active"],
        "result"  => $resources["active"],
        "collection_allow_not_approved_share" => false,
    ],
    [
        "name" => "04 - All from active",
        "collection" => $collections["active"],
        "result"  => $resources["active"],
        "collection_allow_not_approved_share" => true,
    ],
    [
        "name" => "05 - Only approved from deleted",
        "collection" => $collections["deleted"],
        "result"  => [],
        "collection_allow_not_approved_share" => false,
    ],
    [
        "name" => "06 - Only approved from all",
        "collection" => $collections["deleted"],
        "result"  => $resources["deleted"],
        "collection_allow_not_approved_share" => true,
    ],
    [
        "name" => "07 - Only approved from parent",
        "collection" => $parent,
        "result" => $resources["active"],
        "collection_allow_not_approved_share" => false,
    ],
    [
        "name" => "07 - All from parent",
        "collection" => $parent,
        "result" => array_merge($resources["active"],$resources["deleted"]),
        "collection_allow_not_approved_share" => true,
    ],
];


// Test
foreach ($use_cases as $use_case) {
    $GLOBALS["collection_allow_not_approved_share"] = $use_case["collection_allow_not_approved_share"];
    $c = get_collection($use_case["collection"]);
    $result = get_featured_collection_resources($c,[]);
    if (sort($resources)!=sort($use_case["result"])) {
        echo "Use case: {$use_case['name']} - ";
        return false;
    }
}

// Tear Down
$GLOBALS["collection_allow_not_approved_share"] = $collection_allow_not_approved_share_cache;
$user_data = $original_user_data;
unset($original_user_data);
$userpermissions = $orig_userpermissions;
unset($orig_userpermissions, $use_cases);

return true;