<?php 
command_line_only();

// --- Set up
$initial_field_column_string_separator = $field_column_string_separator;

// Metadata fields
$rtf_checkbox = create_resource_type_field("Test #402 checkbox", 1, FIELD_TYPE_CHECK_BOX_LIST, "test_402_checkbox", true);
$ckb_opt_a = set_node(null, $rtf_checkbox, '~en:Scanned negative~fr:Négatif scanné', null, 10);
$ckb_opt_b = set_node(null, $rtf_checkbox, '~en:Digital camera~fr:Appareil photo numérique', null, 20);

$rtf_cat_tree = create_resource_type_field('Test #402 category tree', 1, FIELD_TYPE_CATEGORY_TREE, 'test_402_tree', true);
$ct_opt_colors = set_node(null, $rtf_cat_tree, '~en:Colors~fr:Couleurs', null, 10);
$ct_opt_numbers = set_node(null, $rtf_cat_tree, '~en:Numbers~fr:Nombres', null, 20);
$ct_opt_colors_red = set_node(null, $rtf_cat_tree, '~en:red~fr:rouge', $ct_opt_colors, 20);
$ct_opt_colors_black = set_node(null, $rtf_cat_tree, '~en:black~fr:noire', $ct_opt_colors, 10);
$ct_opt_colors_blue = set_node(null, $rtf_cat_tree, '~en:blue~fr:bleue', $ct_opt_colors, 30);

// Resources
$resource_a = create_resource(1, 0);
$resource_b = create_resource(1, 0);

// Collections
$collection_ref = create_collection($userref, 'test_402', 1);
add_resource_to_collection($resource_a, $collection_ref);
add_resource_to_collection($resource_b, $collection_ref);
$resources_list = [$resource_a, $resource_b];

// Assert helpers
$assert_same_all_resources_fieldx = function($resources, $rtf)
    {
    $data = [];
    foreach($resources as $resource)
        {
        $data[$resource] = get_resource_data($resource, false)["field{$rtf}"];
        }
    
    $fieldx_value = reset($data);

    if(
        count($data) > 1
        && count(array_unique($data)) === 1
        && $fieldx_value !== false
        && mb_strpos($fieldx_value, $GLOBALS['field_column_string_separator']) !== false
    )
        {
        return true;
        }

    return false;
    };
// --- End of Set up



// Check field_column_string_separator is applied for fixed list fields
$field_column_string_separator = '|:|';

// - Check box
$data_joins[] = $rtf_checkbox;
$_POST['nodes'][$rtf_checkbox] = [$ckb_opt_a, $ckb_opt_b];
$_POST["editthis_field_{$rtf_checkbox}"] = 'yes';
$_POST["modeselect_{$rtf_checkbox}"] = 'RT';
save_resource_data_multi($collection_ref);
if(!$assert_same_all_resources_fieldx($resources_list, $rtf_checkbox))
    {
    echo "Use case (RT mode): use separator for storing multiple node values in the resource table (column fieldX) - ";
    return false;
    }


// - Category tree
// -- Replace all text/options
$data_joins[] = $rtf_cat_tree;
$_POST['nodes'][$rtf_cat_tree] = [$ct_opt_colors, $ct_opt_colors_red, $ct_opt_colors_black];
$_POST["editthis_field_{$rtf_cat_tree}"] = 'yes';
$_POST["modeselect_{$rtf_cat_tree}"] = 'RT';
save_resource_data_multi($collection_ref);
if(!$assert_same_all_resources_fieldx($resources_list, $rtf_cat_tree))
    {
    echo 'Use case (RT mode): use separator for storing multiple node paths for category tree in column fieldX - ';
    return false;
    }


$cat_tree_fieldx_value = get_resource_data($resource_a, false)["field{$rtf_cat_tree}"];
$cat_tree_fieldX_values = array_intersect_key(
    array_column(get_nodes($rtf_cat_tree, null, true), 'name', 'ref'),
    array_flip([$ct_opt_colors, $ct_opt_colors_black, $ct_opt_colors_red])
);
$expected_cat_tree_fieldx_value = implode(
    $field_column_string_separator,
    array_map(
        function(array $v): string { return implode('/', $v); },
        [
            [$cat_tree_fieldX_values[$ct_opt_colors]],
            [$cat_tree_fieldX_values[$ct_opt_colors], $cat_tree_fieldX_values[$ct_opt_colors_black]],
            [$cat_tree_fieldX_values[$ct_opt_colors], $cat_tree_fieldX_values[$ct_opt_colors_red]],
        ]
    )
);
if($expected_cat_tree_fieldx_value !== $cat_tree_fieldx_value
)
    {
    echo 'Use case (RT mode): column fieldX (category tree) having nodes resolved according to their order_by - ';
    return false;
    }


// -- Append all text/options
$data_joins[] = $rtf_cat_tree;
$_POST['nodes'][$rtf_cat_tree] = [$ct_opt_numbers, $ct_opt_colors, $ct_opt_colors_red, $ct_opt_colors_black];
$_POST["editthis_field_{$rtf_cat_tree}"] = 'yes';
$_POST["modeselect_{$rtf_cat_tree}"] = 'AP';
save_resource_data_multi($collection_ref);
if(!$assert_same_all_resources_fieldx($resources_list, $rtf_cat_tree))
    {
    echo 'Use case (AP mode): use separator for storing multiple node paths for category tree in column fieldX - ';
    return false;
    }

$cat_tree_fieldx_value = get_resource_data($resource_a, false)["field{$rtf_cat_tree}"];
$cat_tree_fieldX_values = array_intersect_key(
    array_column(get_nodes($rtf_cat_tree, null, true), 'name', 'ref'),
    array_flip([$ct_opt_colors, $ct_opt_colors_black, $ct_opt_colors_red, $ct_opt_numbers])
);
$expected_cat_tree_fieldx_value = implode(
    $field_column_string_separator,
    array_map(
        function(array $v): string { return implode('/', $v); },
        [
            [$cat_tree_fieldX_values[$ct_opt_colors]],
            [$cat_tree_fieldX_values[$ct_opt_colors], $cat_tree_fieldX_values[$ct_opt_colors_black]],
            [$cat_tree_fieldX_values[$ct_opt_colors], $cat_tree_fieldX_values[$ct_opt_colors_red]],
            [$cat_tree_fieldX_values[$ct_opt_numbers]],
        ]
    )
);
if($expected_cat_tree_fieldx_value !== $cat_tree_fieldx_value
)
    {
    echo 'Use case (AP mode): column fieldX (category tree) having nodes resolved according to their order_by - ';
    return false;
    }



// Tear down
$field_column_string_separator = $initial_field_column_string_separator;
$data_joins = $_POST = [];
unset(
    $rtf_checkbox, $ckb_opt_a, $ckb_opt_b, $resource_a, $resource_b, $collection_ref, $resources_list,
    $assert_same_all_resources_fieldx, $cat_tree_fieldx_value, $cat_tree_fieldX_values, $expected_cat_tree_fieldx_value
);
 
return true;