<?php
if(PHP_SAPI != 'cli')
    {
    exit('This utility is command line only.');
    }

// Defaults arguments which we don't have to test
$order_by = 'name';
$sort = 'ASC';
$include_resources = false;
$fetchrows = -1;

// Save global scope vars that will change during this test
$original_public_collections_confine_group = $public_collections_confine_group;


// Create users in other user groups
$general_user = new_user('test_002501_general', 2);
$super_admin_user = new_user('test_002501_super_admin', 3);
if($general_user === false && $super_admin_user === false)
    {
    echo 'Test setup (new_user function) - ';
    return false;
    }

/* Create a simple featured collections tree:
Level A
    -> Sub-level A.1
        -> FC A (at level A/A.1)
Level B
    -> Sub-level B.1
        -> FC B
*/
$fc_cat_lvl_a = create_collection(0, 'Level A');
save_collection($fc_cat_lvl_a, ['featured_collections_changes' => ['update_parent' => 0, 'force_featured_collection_type' => true]]);
$fc_cat_lvl_a1 = create_collection(0, 'Sub-level A.1');
save_collection($fc_cat_lvl_a1, ['featured_collections_changes' => ['update_parent' => $fc_cat_lvl_a, 'force_featured_collection_type' => true]]);
$fc_cat_lvl_b = create_collection(0, 'Level B');
save_collection( $fc_cat_lvl_b,['featured_collections_changes' => ['update_parent' => 0, 'force_featured_collection_type' => true]]);
$fc_cat_lvl_b1 = create_collection(0, 'Sub-level B.1');
save_collection( $fc_cat_lvl_b1,['featured_collections_changes' => ['update_parent' => $fc_cat_lvl_b, 'force_featured_collection_type' => true]]);

$fc_a = create_collection($general_user, 'FC A (at level A/A.1)', 0, 0, 0, true);
save_collection($fc_a, ['featured_collections_changes' => ['update_parent' => $fc_cat_lvl_a1, 'force_featured_collection_type' => true]]);
$fc_b = create_collection($super_admin_user, 'FC B', 0, 0, 0, true);
save_collection($fc_b, ['featured_collections_changes' => ['update_parent' => $fc_cat_lvl_b1, 'force_featured_collection_type' => true]]);

$public_col = create_collection($userref, 'User public collection (at user level)', 1, 0, 0, true);
$private_col = create_collection($userref, 'Private collection');

// Create some resources and give them a title
$resource_a = create_resource(1, 0);
$resource_b = create_resource(1, 0);
update_field($resource_a, 'title', 'test_002501_A');
update_field($resource_b, 'title', 'test_002501_B');
// --- End of setup



// Search - excluding FCs and include public collections
$spc_result = search_public_collections('level', $order_by, $sort, true, false);
$found_col_refs = array_column($spc_result, 'ref');
if(!in_array($public_col, $found_col_refs))
    {
    echo 'Search in public collections only - ';
    return false;
    }


// Search - including FCs and excluding public ones
add_resource_to_collection($resource_a, $fc_a);
$spc_result = search_public_collections('level', $order_by, $sort, false, true);
$found_col_refs = array_column($spc_result, 'ref');
if(!in_array($fc_a, $found_col_refs))
    {
    echo 'Search in featured collections only - ';
    return false;
    }


// Search in both featured and public collections
$spc_result = search_public_collections('level', $order_by, $sort, false, false);
$found_col_refs = array_column($spc_result, 'ref');
if([$fc_a, $public_col] != $found_col_refs)
    {
    echo 'Search both featured & public collections - ';
    return false;
    }


// Search excluding both featured and public collections => this is essentially a search public collections (ie function 
// was called incorrectly - both featured and public collections are "public".)
$spc_result = search_public_collections('level', $order_by, $sort, true, true);
$found_col_refs = array_column($spc_result, 'ref');
if(!in_array($public_col, $found_col_refs))
    {
    echo 'Search excluding featured & public collections - ';
    return false;
    }


// Search showing resource count
add_resource_to_collection($resource_a, $public_col);
add_resource_to_collection($resource_b, $public_col);
$spc_result = search_public_collections('level', $order_by, $sort, false, false, true);
$found_col_refs = array_column($spc_result, 'count', 'ref');
if(!($found_col_refs[$fc_a] == 1 && $found_col_refs[$public_col] == 2))
    {
    echo 'Show resource count - ';
    return false;
    }


// Search for collections confined to the user group (parent, child, sibling)
$public_collections_confine_group = true;
$spc_result = search_public_collections('', $order_by, $sort, false, false);
$found_col_refs = array_flip(array_column($spc_result, 'ref'));
if(
    !(
        // Expecting Super Admins collections
        isset($found_col_refs[$fc_b], $found_col_refs[$public_col])
        // The general user collection shouldn't be returned
        && !isset($found_col_refs[$fc_a])
    )
)
    {
    echo 'Search confined by group (Super Admin) - ';
    return false;
    }


// Override group confinment
$public_collections_confine_group = false;
$spc_result_no_confinment = search_public_collections('', $order_by, $sort, false, false);
$found_col_refs_no_confinment = array_column($spc_result_no_confinment, 'ref');
$public_collections_confine_group = true;
$spc_result_override_group_restrict = search_public_collections('', $order_by, $sort, false, false, $include_resources, true);
$found_col_refs_override_group_restrict = array_column($spc_result_override_group_restrict, 'ref');
if($found_col_refs_no_confinment != $found_col_refs_override_group_restrict)
    {
    echo 'Override group confinment - ';
    return false;
    }
unset($spc_result_no_confinment, $found_col_refs_no_confinment, $spc_result_override_group_restrict, $found_col_refs_override_group_restrict);


// TODO: args $search_user_collections

// TODO test behaviour determined by the following globals:
// - $search_public_collections_ref



// Tear down
$public_collections_confine_group = $original_public_collections_confine_group;

unset($order_by, $sort, $include_resources, $fetchrows, $general_user, $super_admin_user);
unset($fc_cat_lvl_a, $fc_cat_lvl_a1, $fc_cat_lvl_b, $fc_cat_lvl_b1, $public_col, $private_col);
unset($resource_a, $resource_b);
unset($spc_result, $found_col_refs);
unset($original_public_collections_confine_group);

return true;