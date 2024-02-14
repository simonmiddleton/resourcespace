<?php

command_line_only();

// --- Set up
$run_id = test_generate_random_ID(5);
test_log("Run ID - {$run_id}");
$rt_title_prefix = sprintf('Test #%s-%s', test_get_file_id(__FILE__), $run_id);
$rt_name_prefix = sprintf('test_%s-%s', test_get_file_id(__FILE__), $run_id);

// Checkbox field
$rtf_checkbox = create_resource_type_field("{$rt_title_prefix} checkbox", 1, FIELD_TYPE_CHECK_BOX_LIST, "{$rt_name_prefix}_checkbox", true);
$ckb_opt_a = set_node(null, $rtf_checkbox, "Option A ({$rt_name_prefix})", null, 10);
$ckb_opt_b = set_node(null, $rtf_checkbox, "Option B ({$rt_name_prefix})", null, 20);
$ckb_opt_c = set_node(null, $rtf_checkbox, "Option C ({$rt_name_prefix})", null, 30);

// Text field and a resource with a value (now using nodes)
$rtf_text = create_resource_type_field("{$rt_title_prefix} text", 1, FIELD_TYPE_TEXT_BOX_SINGLE_LINE, "{$rt_name_prefix}_txt", false);
$resource_a = create_resource(1, 0);
update_field($resource_a, $rtf_text, "Lorem ipsum dolor sit amet. ({$rt_name_prefix})");
$rtf_text_node = get_resource_nodes($resource_a, $rtf_text)[0];

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
    [
        'name' => 'Active state toggles only fixed list fields',
        'input' => ['refs' => [$rtf_text_node]],
        'valid' => $use_case_expect([$rtf_text_node => 1]),
    ],
    [
        'name' => 'Toggle fixed list fields (e.g checkbox) active state',
        'input' => ['refs' => [$ckb_opt_b]],
        'valid' => $use_case_expect([$ckb_opt_b => 0]),
    ],
];
foreach ($use_cases as $uc) {
    // Set up the use case environment
    if (isset($uc['setup'])) {
        $uc['setup']();
    }

    toggle_active_state_for_nodes($uc['input']['refs']);
    if (!$uc['valid']($uc['input']['refs'])) {
        test_log('');
        echo "Use case: {$uc['name']} - ";
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
