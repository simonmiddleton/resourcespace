<?php

command_line_only();

// --- Set up
$run_id = test_generate_random_ID(5);
$dest = new SplFileInfo(get_temp_dir(false) . "/test_508_{$run_id}.bin");
$expect_fail_msg = static function (string $V): callable {
    return static fn ($R): bool => (
        is_array($R)
        && !$R['success']
        && isset($R['error'])
        && mb_strpos($R['error'], $V) !== false
    );
};
// --- End of Set up

$use_cases = [
    [
        'name' => 'Faking an HTTP POST uploaded file will trigger error for developers',
        'input' => [
            'source' => [
                'name' => 'test_fake.jpg',
                'full_path' => 'test_fake.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpt9Vnyy',
                'error' => 0,
                'size' => 4715,
            ],
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => null,
        'should_throw' => true,
    ],
    [
        'name' => 'Invalid upload path should fail',
        'input' => [
            'source' => new SplFileInfo(__FILE__),
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => $expect_fail_msg('Invalid upload path'),
    ],
    [
        'name' => 'Special files are forbidden',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . '/.htaccess', ''),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . '/.htaccess'),
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => $expect_fail_msg('"Special" file'),
    ],
    [
        'name' => 'Banned extension',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . "/test_508_{$run_id}.php", ''),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . "/test_508_{$run_id}.php"),
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => $expect_fail_msg('Invalid extension'),
    ],
    [
        'name' => 'Allow specific extension',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . "/test_508_{$run_id}.csv", ''),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . "/test_508_{$run_id}.csv"),
            'destination' => $dest,
            'processor' => ['allow_extensions' => ['csv']],
        ],
        'expected' => ['success' => true],
    ],
    // [
    //     'name' => 'TEST',
    //     // 'setup' => fn() => $GLOBALS['testXXX'] = true,
    //     'input' => [
    //         'source' => new SplFileInfo(__FILE__),
    //         'destination' => $dest,
    //         'processor' => [],
    //     ],
    //     'expected' => ['success' => false],
    // ],
];
foreach ($use_cases as $uc) {
    // Set up the use case environment
    if (isset($uc['setup'])) {
        $uc['setup']();
    }

    $GLOBALS['use_error_exception'] = true;
    try {
        $result = process_file_upload(...$uc['input']);
    } catch (Throwable $t) {
        $result = isset($uc['should_throw']) ? null : [];
    }
    unset($GLOBALS['use_error_exception']);

    $assertion_fails = is_callable($uc['expected']) ? !$uc['expected']($result) : $uc['expected'] !== $result;
    if ($assertion_fails) {
        echo "Use case: {$uc['name']} - ";
        return false;
    }
}

// Tear down
unset($run_id, $use_cases, $result, $dest, $expect_fail_msg);

return true;
