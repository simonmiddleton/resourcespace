<?php 
command_line_only();

// --- Set up
$original_state = $GLOBALS;
$setup_global_env = function() use ($original_state)
    {
    $GLOBALS['use_case_expected_logs'] = [];
    $GLOBALS['userpermissions'] = $original_state['userpermissions'];
    };

$rtf_text = create_resource_type_field("Test #413 text", 1, FIELD_TYPE_TEXT_BOX_SINGLE_LINE, "test_413_text", false);
$resource_a = create_resource(1, 0);
$resource_b = create_resource(1, 0);
$resource_c = create_resource(1, 0);

$test413_resource_log = function(array $info)
    {
    ps_query(
        'INSERT INTO `resource_log`(`date`, `resource`, `type`, `resource_type_field`, notes) VALUES (?, ?, ?, ?, ?)',
        [
        's', $info['date'] ?? date('Y-m-d H:i:s'),
        'i', $info['resource'] ?? 413_000,
        's', $info['type'] ?? LOG_CODE_VIEWED,
        'i', $info['rtf'] ?? null,
        's', $info['notes'] ?? null,
        ]
    );
    return sql_insert_id();
    };

$last_log_ref = ps_value('SELECT ref AS `value` FROM resource_log ORDER BY ref DESC LIMIT 1', [], 0);
// --- End of Set up



$use_cases = [
    [
        'name' => 'Filtering by minref returns the specified minimum ID',
        'setup' => function() use ($test413_resource_log)
            {
            $GLOBALS['use_case_expected_logs'][] = $test413_resource_log(['notes' => 'UC#1']);
            },
        'input' => ['minref' => ++$last_log_ref, 'days' => 7, 'maxrecords' => 0, 'field' => 0, 'log_code' => ''],
    ],
    [
        'name' => 'Get logs in the last 3 days',
        'setup' => function() use ($test413_resource_log)
            {
            ps_query('TRUNCATE resource_log');
            $test413_resource_log([
                'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'notes' => 'UC#2: 3d'
            ]);
            $GLOBALS['use_case_expected_logs'][] = $test413_resource_log([
                'date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'notes' => 'UC#2: 2d'
            ]);
            },
        'input' => ['minref' => 0, 'days' => 3, 'maxrecords' => 0, 'field' => 0, 'log_code' => ''],
    ],
    [
        'name' => 'Return only a certain number of records back',
        'setup' => function() use ($test413_resource_log)
            {
            $GLOBALS['use_case_expected_logs'][] = $test413_resource_log(['notes' => 'UC#3']);
            $GLOBALS['use_case_expected_logs'][] = $test413_resource_log(['notes' => 'UC#3']);
            $GLOBALS['use_case_expected_logs'][] = $test413_resource_log(['notes' => 'UC#3']);
            },
        'input' => ['minref' => 0, 'days' => 2, 'maxrecords' => 3, 'field' => 0, 'log_code' => ''],
    ],
    [
        'name' => 'Filter by metadata field',
        'setup' => function() use ($rtf_text, $test413_resource_log)
            {
            $GLOBALS['use_case_expected_logs'][] = $test413_resource_log(['rtf' => $rtf_text, 'notes' => 'UC#4']);
            },
        'input' => ['minref' => 0, 'days' => 2, 'maxrecords' => 0, 'field' => $rtf_text, 'log_code' => ''],
    ],
    [
        'name' => 'Filter by log code',
        'setup' => function() use ($test413_resource_log)
            {
            $test413_resource_log(['type' => LOG_CODE_COPIED, 'notes' => 'UC#5']);
            $GLOBALS['use_case_expected_logs'][] = $test413_resource_log(['type' => LOG_CODE_DELETED, 'notes' => 'UC#5']);
            },
        'input' => ['minref' => 0, 'days' => 2, 'maxrecords' => 0, 'field' => 0, 'log_code' => LOG_CODE_DELETED],
    ],
    [
        'name' => 'Enforce access control',
        'setup' => function() use ($test413_resource_log)
            {
            $test413_resource_log(['notes' => 'UC#6']);
            $GLOBALS['userpermissions'] = array_diff($GLOBALS['userpermissions'], ['v']);
            },
        'input' => [],
    ],
];
foreach($use_cases as $i => $use_case)
    {
    $setup_global_env();
    if(isset($use_case['setup']))
        {
        $use_case['setup']();
        }

    // We use the notes just for debugging the use cases
    $result = array_column(resource_log_last_rows(...$use_case['input']), 'notes', 'ref');
    if(array_keys($result) !== $GLOBALS['use_case_expected_logs'])
        {
        echo "Use case: {$use_case['name']} - ";
        printf(PHP_EOL.'$result: %s = %s' . PHP_EOL, gettype($result), print_r($result, true));
        printf(PHP_EOL.'use_case_expected_log = %s' . PHP_EOL, print_r($GLOBALS['use_case_expected_logs'], true));
        return false;
        }
    }



// Tear down
unset(
    $original_state,
    $setup_global_env,
    $rtf_text,
    $resource_a,
    $resource_b,
    $resource_c,
    $test413_resource_log,
    $last_log_ref,
    $use_cases,
    $result
);

return true;