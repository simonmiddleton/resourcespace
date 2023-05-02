<?php 
command_line_only();

// --- Set up
$initial_field_column_string_separator = $field_column_string_separator;

// Metadata fields
$rtf_checkbox = create_resource_type_field('Test #401 checkbox', 1, FIELD_TYPE_CHECK_BOX_LIST, 'test_401_checkbox', true);
$ckb_opt_a = set_node(null, $rtf_checkbox, '~en:Scanned negative~fr:Négatif scanné', null, 20);
$ckb_opt_b = set_node(null, $rtf_checkbox, '~en:Digital camera~fr:Appareil photo numérique', null, 10);

$rtf_cat_tree = create_resource_type_field('Test #401 category tree', 1, FIELD_TYPE_CATEGORY_TREE, 'test_401_tree', true);
$ct_opt_colors = set_node(null, $rtf_cat_tree, '~en:Colors~fr:Couleurs', null, 10);
$ct_opt_numbers = set_node(null, $rtf_cat_tree, '~en:Numbers~fr:Nombres', null, 20);
$ct_opt_colors_red = set_node(null, $rtf_cat_tree, '~en:red~fr:rouge', $ct_opt_colors, 20);
$ct_opt_colors_black = set_node(null, $rtf_cat_tree, '~en:black~fr:noire', $ct_opt_colors, 10);
$ct_opt_colors_blue = set_node(null, $rtf_cat_tree, '~en:blue~fr:bleue', $ct_opt_colors, 30);

$resource_a = create_resource(1, 0);
// --- End of Set up



// Check field_column_string_separator is applied for fixed list fields
$field_column_string_separator = '|:|';

// - Check box
$data_joins[] = $rtf_checkbox;
$_POST['nodes'][$rtf_checkbox] = [$ckb_opt_a, $ckb_opt_b];
save_resource_data($resource_a, false, $rtf_checkbox);
$ckb_fieldx_value = get_resource_data($resource_a, false)["field{$rtf_checkbox}"];
if(mb_strpos($ckb_fieldx_value, $field_column_string_separator) === false)
    {
    echo 'Use case: use separator for storing multiple node values in the resource table (column fieldX) - ';
    return false;
    }

// - When field_column_string_separator is applied, nodes should be resolved according to their order_by
$expected_fieldX_value = implode(
    $field_column_string_separator,
    array_intersect_key(array_column(get_nodes($rtf_checkbox), 'name', 'ref'), array_flip([$ckb_opt_b, $ckb_opt_a]))
);
if($ckb_fieldx_value !== $expected_fieldX_value)
    {
    echo 'Use case: column fieldX having nodes resolved according to their order_by - ';
    return false;
    }


// - Category tree
$data_joins[] = $rtf_cat_tree;
$_POST['nodes'][$rtf_cat_tree] = [$ct_opt_colors, $ct_opt_colors_red, $ct_opt_colors_black];
save_resource_data($resource_a, false, $rtf_cat_tree);
$cat_tree_fieldx_value = get_resource_data($resource_a, false)["field{$rtf_cat_tree}"];
if(mb_strpos($cat_tree_fieldx_value, $field_column_string_separator) === false)
    {
    echo 'Use case: use separator for storing multiple node paths for category tree in column fieldX - ';
    return false;
    }

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
if($expected_cat_tree_fieldx_value !== $cat_tree_fieldx_value)
    {
    echo 'Use case: column fieldX (category tree) having nodes resolved according to their order_by - ';
    return false;
    }




// Tear down
$field_column_string_separator = $initial_field_column_string_separator;
$data_joins = $_POST = [];
unset(
    $initial_field_column_string_separator,
    $rtf_checkbox, $ckb_opt_a, $ckb_opt_b,
    $rtf_cat_tree, $ct_opt_colors, $ct_opt_colors_red, $ct_opt_colors_black, $ct_opt_colors_blue, $ct_opt_numbers,
    $resource_a
);
 
return true;