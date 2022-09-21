<?php
command_line_only();

// --- Set up
// Metadata fields
$rtf_text = create_resource_type_field("Test #412 text", 1, FIELD_TYPE_TEXT_BOX_SINGLE_LINE, "test_412_text", false);

$rtf_dropdown = create_resource_type_field("Test #412 dropdown", 1, FIELD_TYPE_DROP_DOWN_LIST, "test_412_drop", true);
$dd_opt_a = set_node(null, $rtf_dropdown, 'Option A', null, 10);
$dd_opt_b = set_node(null, $rtf_dropdown, 'Option B', null, 20);

$rtf_cat_tree = create_resource_type_field("Test #412 category tree", 1, FIELD_TYPE_CATEGORY_TREE, "test_412_tree", true);
$ct_opt_a = set_node(null, $rtf_cat_tree, 'A', null, '');
$ct_opt_b = set_node(null, $rtf_cat_tree, 'B', null, '');
$ct_opt_a_a1 = set_node(null, $rtf_cat_tree, 'a.1', $ct_opt_a, '');
$ct_opt_a_a2 = set_node(null, $rtf_cat_tree, 'a.2', $ct_opt_a, '');
$ct_opt_a_a1_a11 = set_node(null, $rtf_cat_tree, 'a.1.1', $ct_opt_a_a1, '');

// Create resources and tag them
$resource_a = create_resource(1, 0);
$update_errors = [];
update_field($resource_a, $rtf_text, 'test value for a text field (test #412)', $update_errors);
add_resource_nodes($resource_a, [$dd_opt_a]);

$resource_b = create_resource(1, 0);
add_resource_nodes($resource_b, [$dd_opt_b, $ct_opt_a, $ct_opt_a_a1, $ct_opt_a_a1_a11]);

$resource_c = create_resource(1, 0);
add_resource_nodes($resource_c, [$dd_opt_b, $ct_opt_b]);

$t412_get_node_name = function(int $ref)
    {
    $node = [];
    get_node($ref, $node);
    return $node;
    };
// --- End of Set up



$use_cases = [
    [
        'name' => 'Get text field data for resource A',
        'resource' => $resource_a,
        'rtf' => $rtf_text,
        'expected' => 'test value for a text field (test #412)',
    ],
    [
        'name' => 'Get text field data (raw) for resource A',
        'resource' => $resource_a,
        'rtf' => $rtf_text,
        'flatten' => false,
        'expected' => get_resource_nodes($resource_a, $rtf_text, true),
    ],
    [
        'name' => 'Get dropdown field data for resource A',
        'resource' => $resource_a,
        'rtf' => $rtf_dropdown,
        'expected' => $t412_get_node_name($dd_opt_a)['name'],
    ],
    [
        'name' => 'Get category tree field data for resource B',
        'resource' => $resource_b,
        'rtf' => $rtf_cat_tree,
        'expected' => implode(', ', [$t412_get_node_name($ct_opt_a)['name'], $t412_get_node_name($ct_opt_a_a1)['name'], $t412_get_node_name($ct_opt_a_a1_a11)['name']]),
    ],
    [
        'name' => 'Get category tree field data for resource C',
        'resource' => $resource_c,
        'rtf' => $rtf_cat_tree,
        'expected' => implode(', ', [$t412_get_node_name($ct_opt_b)['name']]),
    ],
    [
        'name' => 'Get category tree field data (raw) for resource C',
        'resource' => $resource_c,
        'rtf' => $rtf_cat_tree,
        'flatten' => false,
        'expected' => get_cattree_nodes_ordered($rtf_cat_tree, $resource_c, false),
    ],
    [
        'name' => 'Get dropdown field data (raw) for resource C',
        'resource' => $resource_c,
        'rtf' => $rtf_dropdown,
        'flatten' => false,
        'expected' => get_resource_nodes($resource_c, $rtf_dropdown, true),
    ],
    [
        'name' => 'Get text field data for all resources',
        'resource' => null,
        'rtf' => $rtf_text,
        'expected' => iterator_to_array(get_resources_nodes_by_rtf($rtf_text)),
    ],
    [
        'name' => 'Get fixed list field data for all resources (unsupported)',
        'resource' => null,
        'rtf' => $rtf_dropdown,
        'expected' => '',
    ],
];
foreach($use_cases as $use_case)
    {
    $result = get_data_by_field($use_case['resource'], $use_case['rtf'], $use_case['flatten'] ?? true);
    if($result instanceof Generator)
        {
        $result = iterator_to_array($result);
        }

    if($use_case['expected'] !== $result)
        {
        echo "Use case: {$use_case['name']} - ";
        return false;
        }
    }



// Tear down
unset(
    $rtf_text, $rtf_dropdown, $rtf_cat_tree, $dd_opt_a, $dd_opt_b, $ct_opt_a, $ct_opt_b, $ct_opt_a_a1, $ct_opt_a_a2, $ct_opt_a_a1_a11,
    $resource_a, $resource_b, $resource_c,
    $update_errors, $t412_get_node_name, $use_cases, $result
);

return true;