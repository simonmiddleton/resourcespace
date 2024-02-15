<?php

command_line_only();

// --- Set up
$run_id = test_generate_random_ID(5);
test_log("Run ID - {$run_id}");
$rt_title_prefix = sprintf('Test #%s-%s', test_get_file_id(__FILE__), $run_id);
$rt_name_prefix = sprintf('test_%s-%s', test_get_file_id(__FILE__), $run_id);

// Checkbox field
$rtf_checkbox = create_resource_type_field("{$rt_title_prefix} checkbox", 1, FIELD_TYPE_CHECK_BOX_LIST, "{$rt_name_prefix}_checkbox", true);
$ckb_opt_a = set_node(null, $rtf_checkbox, "Option A ({$rt_name_prefix})", null, '');
$ckb_opt_b = set_node(null, $rtf_checkbox, "Option B ({$rt_name_prefix})", null, '');
$ckb_opt_c = set_node(null, $rtf_checkbox, "Option C ({$rt_name_prefix})", null, '');

// Text field and a resource with a value (now using nodes)
$rtf_text = create_resource_type_field("{$rt_title_prefix} text", 1, FIELD_TYPE_TEXT_BOX_SINGLE_LINE, "{$rt_name_prefix}_txt", false);
$resource_a = create_resource(1, 0);
update_field($resource_a, $rtf_text, "Lorem ipsum dolor sit amet. ({$rt_name_prefix})");
$rtf_text_node = get_resource_nodes($resource_a, $rtf_text)[0];

// Cateogry tree field
$rtf_cat_tree = create_resource_type_field("{$rt_title_prefix} category tree", 1, FIELD_TYPE_CATEGORY_TREE, "{$rt_name_prefix}_tree", true);
test_log("rtf_cat_tree = {$rtf_cat_tree}");
$ct_opt_a = set_node(null, $rtf_cat_tree, 'A', null, '');
$ct_opt_a_a1 = set_node(null, $rtf_cat_tree, 'a.1', $ct_opt_a, '');
$ct_opt_a_a1_a11 = set_node(null, $rtf_cat_tree, 'a.1.1', $ct_opt_a_a1, '');
$ct_opt_a_a1_a12 = set_node(null, $rtf_cat_tree, 'a.1.2', $ct_opt_a_a1, '');
$ct_opt_a_a2 = set_node(null, $rtf_cat_tree, 'a.2', $ct_opt_a, '');
$ct_opt_b = set_node(null, $rtf_cat_tree, 'B', null, '');
$ct_opt_b_b1 = set_node(null, $rtf_cat_tree, 'b.1', $ct_opt_b, '');
$ct_opt_c = set_node(null, $rtf_cat_tree, 'C', null, '');

// todo: drop the next 3 lines if I end up not needing to reset
// $nodes_UT = array_merge([$rtf_text_node], [$ckb_opt_a, $ckb_opt_b, $ckb_opt_c]);
// $nodes_UT_data = array_column(get_nodes_by_refs($nodes_UT), 'active', 'ref');
// test_log("nodes_UT_data = ". print_r($nodes_UT_data, true));

$use_case_expect = function (array $expected) {
    return function (array $ids) use ($expected): bool {
        $data = array_column(get_nodes_by_refs($ids), 'active', 'ref');
        test_log("data = " . print_r($data, true));
        test_log("expected = " . print_r($expected, true));
        return $expected === $data;
    };
};
// --- End of Set up

$use_cases = [
    /* [
        'name' => 'Active state toggles only fixed list fields',
        'input' => ['refs' => [$rtf_text_node]],
        'expected' => [$rtf_text_node => 1],
    ],
    [
        'name' => 'Toggle fixed list fields (e.g checkbox) active state',
        'input' => ['refs' => [$ckb_opt_b]],
        'expected' => [$ckb_opt_b => 0],
    ], */
    [
        'name' => 'Category tree root options',
        'input' => ['refs' => [$ct_opt_c]],
        'expected' => [$ct_opt_c => 0],
    ],
/*
A
    A.1
        A.1.1
        A.1.2
    A.2
B
    B.1
C

- For category trees, if a parent is marked as deprecated, then this automatically applies to all of its children.
    If all child options are deprecated, the parent can still be active.
- toggle_active_state_for_nodes MUST return confirmation of changes (helps for trees where a child option may not be updated)


Cases
=====

Current node: active --- Parent (any up on branch) node: active --- Toggle --- NEW current node: active
---------------------
1 --- 1 --- yes --- 0
0 --- 1 --- yes --- 1
0 --- 0 --- no  --- 0



*/
];







foreach ($use_cases as $uc) {
    // Set up the use case environment
    if (isset($uc['setup'])) {
        $uc['setup']();
    }

    $result = toggle_active_state_for_nodes($uc['input']['refs']);

    ksort($result, SORT_NUMERIC);
    ksort($uc['expected'], SORT_NUMERIC);

    if($uc['expected'] !== $result) {
        test_log('');
        echo "Use case: {$uc['name']} - ";
        test_log('$result = ' . print_r($result, true));
        test_log('expected = ' . print_r($uc['expected'], true));
        return false;
    }
}

// Tear down
unset(
    $use_cases,
    $run_id,
    $rt_title_prefix,
    $rt_name_prefix,
    $rtf_checkbox,
    $ckb_opt_a,
    $ckb_opt_b,
    $ckb_opt_c,
    $rtf_text,
    $resource_a,
    $rtf_text_node,
    $use_case_expect
);

return true;
