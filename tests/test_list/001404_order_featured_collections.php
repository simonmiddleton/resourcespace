<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

// Set up
$fc_cat_1 = create_collection($userref, "FC first");
$fc_cat_2 = create_collection($userref, "FC second");
$fc_cat_3 = create_collection($userref, "FC third");
$fc_cat_1_stars = create_collection($userref, "*FC with 1 asterisks");
$fc_cat_2_stars = create_collection($userref, "**FC with 2 asterisks");
$fcs_list = [
    $fc_cat_1,
    $fc_cat_2,
    $fc_cat_3,
    $fc_cat_1_stars,
    $fc_cat_2_stars,
];
foreach($fcs_list as $idx => $fc_ref)
    {
    save_collection($fc_ref, ["featured_collections_changes" => ["update_parent" => 0, "force_featured_collection_type" => true]]);
    $fc_data = get_collection($fc_ref);
    if($fc_data === false)
        {
        echo "Setting up FC #{$fc_ref} - ";
        return false;
        }
    $fc_data['has_resources'] = 0;

    $fcs_list[$idx] = $fc_data;
    }


usort($fcs_list, "order_featured_collections");
$expected_order = [
    $fc_cat_2_stars,
    $fc_cat_1_stars,
    $fc_cat_1,
    $fc_cat_2,
    $fc_cat_3
];
if($expected_order !== array_map('intval', array_column($fcs_list, 'ref')))
    {
    return false;
    }


// Tear down
unset($fc_cat_1, $fc_cat_2, $fc_cat_3, $fc_cat_1_stars, $fc_cat_2_stars, $fcs_list);

return true;