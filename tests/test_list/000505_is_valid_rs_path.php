<?php

command_line_only();

// --- Set up
$run_id = test_generate_random_ID(5);
$test_tmp_dir = get_temp_dir(false);
$resource_a = create_resource(1, 0);
$resource_a_original_path = get_resource_path($resource_a, true, '', true, 'jpg');

$syncdir = sys_get_temp_dir() . "/staticsync/test_{$run_id}";
mkdir($syncdir, 0777, true);
$static_sync_test_path = "{$syncdir}/test_static_sync.jpg";
copy(dirname(__DIR__, 2) . '/gfx/homeanim/1.jpg', $static_sync_test_path);
// --- End of Set up

$use_cases = [
    [
        'name' => 'Allow resource files',
        'setup' => fn() => file_put_contents(get_resource_path($resource_a, true, '', true, 'jpg'), ''),
        'input' => [get_resource_path($resource_a, true, '', true, 'jpg')],
        'expected' => true,
    ],
    [
        'name' => 'Allow temp dir files',
        'setup' => fn() => file_put_contents("{$test_tmp_dir}/test_{$run_id}.txt", ''),
        'input' => ["{$test_tmp_dir}/test_{$run_id}.txt"],
        'expected' => true,
    ],
    [
        'name' => 'Allow static sync path',
        'input' => [$static_sync_test_path],
        'expected' => true,
    ],
    [
        'name' => 'Block files outside ResourceSpace',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . "/test_{$run_id}.txt", ''),
        'input' => [sys_get_temp_dir() . "/test_{$run_id}.txt"],
        'expected' => false,
    ],
    [
        'name' => 'Block path traversals outside filestore',
        'input' => ["{$test_tmp_dir}/../../../include/config.php"],
        'expected' => false,
    ],
    [
        'name' => 'Allow non-existent files within filestore',
        'input' => [get_resource_path($resource_a, true, 'not_real_size', true, 'jpg')],
        'expected' => true,
    ],
    [
        'name' => 'Block non-existent files with path traversal (outside filestore)',
        'input' => ["{$test_tmp_dir}/../../../someFile.ext"],
        'expected' => false,
    ],
    [
        'name' => 'Block path injection via the basename',
        'input' => [
            str_replace(
                pathinfo($resource_a_original_path, PATHINFO_BASENAME),
                "some'bad_file.jpg",
                $resource_a_original_path
            )
        ],
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

    $result = is_valid_rs_path(...$uc['input']);
    if($uc['expected'] !== $result)
        {
        echo "Use case: {$uc['name']} - ";
        test_log("Run ID - {$run_id}");
        test_log("test_tmp_dir - {$test_tmp_dir}");
        test_log("path - {$uc['input'][0]}");
        test_log('--- ');
        return false;
        }
    }

// Tear down
unset($run_id, $test_tmp_dir, $resource_a, $use_cases, $result);

return true;
