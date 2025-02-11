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
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . '/.htaccess', 'x'),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . '/.htaccess'),
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => $expect_fail_msg('"Special" file'),
    ],
    [
        'name' => 'Banned extension',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . "/test_508_{$run_id}.php", '<?php echo 508;'),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . "/test_508_{$run_id}.php"),
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => $expect_fail_msg('Invalid extension'),
    ],
    [
        'name' => 'Allow specific extension',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . "/test_508_{$run_id}.csv", 'x,y'),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . "/test_508_{$run_id}.csv"),
            'destination' => $dest,
            'processor' => ['allow_extensions' => ['csv']],
        ],
        'expected' => ['success' => true],
    ],
    [
        'name' => 'MIME type check',
        'setup' => function () use ($run_id) {
            $img = create_random_image(['text' => "Run ID {$run_id}"]);
            if (!isset($img['path'])) {
                return false;
            }
            return rename($img['path'], sys_get_temp_dir() . "/test_508_mime_check_{$run_id}.txt");
        },
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . "/test_508_mime_check_{$run_id}.txt"),
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => $expect_fail_msg('MIME type mismatch'),
    ],
    [
        'name' => 'Check destination is not a directory',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . '/test_508.txt', 'x'),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . '/test_508.txt'),
            'destination' => new SplFileInfo(get_temp_dir(false)),
            'processor' => [],
        ],
        'expected' => null,
        'should_throw' => true,
    ],
    [
        'name' => 'Check destination path is allowed',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . '/test_508.txt', 'x'),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . '/test_508.txt'),
            'destination' => new SplFileInfo(sys_get_temp_dir() . '/test_508_copy.txt'),
            'processor' => [],
        ],
        'expected' => $expect_fail_msg('Destination path'),
    ],
    [
        'name' => 'Check file move processor is allowed',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . '/test_508.txt', 'x'),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . '/test_508.txt'),
            'destination' => $dest,
            'processor' => ['file_move' => 'unknown_value'],
        ],
        'expected' => null,
        'should_throw' => true,
    ],
    [
        'name' => 'Moving file from source to its destination',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . '/test_508.txt', 'x'),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . '/test_508.txt'),
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => ['success' => true],
    ],
];
foreach ($use_cases as $uc) {
    if (isset($uc['setup']) && !$uc['setup']()) {
        echo "[ENV] Set up '{$uc['name']}' use case - ";
        return false;
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
array_map('unlink', glob(sys_get_temp_dir() . '/test_508*'));

return true;
