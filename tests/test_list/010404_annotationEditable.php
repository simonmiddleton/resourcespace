<?php

command_line_only();

include_once dirname(__FILE__, 3) . '/include/annotation_functions.php';

// --- Set up
$run_id = test_generate_random_ID(5);
test_log("Run ID - {$run_id}");
$title_prefix = sprintf('Test #%s-%s', test_get_file_id(__FILE__), $run_id);

$cache_globals = [
    'userpermissions' => $userpermissions,
    'annotate_fields' => $annotate_fields ?? [],
];
$reset_globals = function (array $cache) {
    $GLOBALS['userpermissions'] = $cache['userpermissions'];
    $GLOBALS['annotate_fields'] = $cache['annotate_fields'];
};

$fake_annotation = static function (array $with) {
    return array_merge(
        [
            'resource' => rand(10_000, 90_000),
            'resource_type_field' => 0,
            'page' => 0,
            'tags' => [],
            'shapes' => [
                ['type' => 'rect', 'geometry' => ['x' => 0.10, 'y' => 0.20, 'width' => 0.50, 'height' => 0.60]]
            ],
        ],
        $with
    );
};
$without_permission = static fn (array $codes) => array_values(array_diff($userpermissions, $codes));

$rtf_dkl = create_resource_type_field("{$title_prefix} DKL", 0, FIELD_TYPE_DYNAMIC_KEYWORDS_LIST);

$annotate_fields = [$rtf_dkl];
// --- End of Set up

/* 
todo: new use cases
- create new text anno
- edit my text anno
- edit user A text anno (me having o perm)
*/
$use_cases = [
    // Create
    [
        'name' => 'Admin can create annotation (field bound)',
        'input' => $fake_annotation(['resource_type_field' => $rtf_dkl]),
        'expected' => true,
    ],
    [
        'name' => 'Non-admin can create annotation (field bound)',
        'setup' => fn() => $GLOBALS['userpermissions'] = $without_permission(['a']),
        'input' => $fake_annotation(['resource_type_field' => $rtf_dkl]),
        'expected' => true,
    ],
    // Edit - Own records
    [
        'name' => 'Admin can edit their own annotation (field bound; w/ access)',
        'input' => $fake_annotation(['resource_type_field' => $rtf_dkl, 'user' => $userref]),
        'expected' => true,
    ],
    [
        'name' => 'Non-admin can edit their own annotation (field bound; w/ access)',
        'setup' => fn() => $GLOBALS['userpermissions'] = $without_permission(['a']),
        'input' => $fake_annotation(['resource_type_field' => $rtf_dkl, 'user' => $userref]),
        'expected' => true,
    ],
    [
        'name' => "Admin can't edit their own annotation (field bound; w/o access)",
        'setup' => fn() => $GLOBALS['userpermissions'][] = "F{$rtf_dkl}",
        'input' => $fake_annotation(['resource_type_field' => $rtf_dkl, 'user' => $userref]),
        'expected' => false,
    ],
    [
        'name' => "Non-admin can't edit their own annotation (field bound; w/o access)",
        'setup' => function () use ($rtf_dkl, $without_permission) {
            $GLOBALS['userpermissions'] = $without_permission(['a']);
            $GLOBALS['userpermissions'][] = "F{$rtf_dkl}"; # can't edit the field
        },
        'input' => $fake_annotation(['resource_type_field' => $rtf_dkl, 'user' => $userref]),
        'expected' => false,
    ],
    // Edit - Other user records
    [
        'name' => 'Admin can edit another user annotation (field bound)',
        'input' => $fake_annotation(['resource_type_field' => $rtf_dkl, 'user' => 300]),
        'expected' => true,
    ],
    [
        'name' => "Non-admin can't edit another user annotation (field bound)",
        'setup' => fn() => $GLOBALS['userpermissions'] = $without_permission(['a']),
        'input' => $fake_annotation(['resource_type_field' => $rtf_dkl, 'user' => 300]),
        'expected' => false,
    ],
];
foreach($use_cases as $uc)
    {
    // Set up the use case environment
    if(isset($uc['setup']))
        {
        $uc['setup']();
        }

    $result = annotationEditable($uc['input']);
    if($uc['expected'] !== $result)
        {
        echo "Use case: {$uc['name']} - ";
        return false;
        }

    $reset_globals($cache_globals);
    }

// Tear down
$reset_globals($cache_globals);
unset($run_id, $use_cases, $result, $cache_globals, $reset_globals, $fake_annotation, $without_permission);

return true;
