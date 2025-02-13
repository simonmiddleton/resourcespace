<?php

command_line_only();

// --- Set up
$run_id = test_generate_random_ID(5);
$dest = new SplFileInfo(get_temp_dir(false) . "/test_508_{$run_id}.bin");
$expect_fail_cond = static function (ProcessFileUploadErrorCondition $V): callable {
    return static fn ($R): bool => (is_array($R) && !$R['success'] && isset($R['error']) && $R['error'] === $V);
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
        'expected' => $expect_fail_cond(ProcessFileUploadErrorCondition::InvalidUploadPath),
    ],
    [
        'name' => 'Special files are forbidden',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . '/.htaccess', 'x'),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . '/.htaccess'),
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => $expect_fail_cond(ProcessFileUploadErrorCondition::SpecialFile),
    ],
    [
        'name' => 'Banned extension',
        'setup' => fn() => file_put_contents(sys_get_temp_dir() . "/test_508_{$run_id}.php", '<?php echo 508;'),
        'input' => [
            'source' => new SplFileInfo(sys_get_temp_dir() . "/test_508_{$run_id}.php"),
            'destination' => $dest,
            'processor' => [],
        ],
        'expected' => $expect_fail_cond(ProcessFileUploadErrorCondition::InvalidExtension),
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
        'expected' => $expect_fail_cond(ProcessFileUploadErrorCondition::MimeTypeMismatch),
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
        'expected' => null,
        'should_throw' => true,
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

// Use case: HTTP file uploaded
// (integration test so has to be handled separately to the above scenarios)
$test_signature = generateSecureKey(32);
$test_endpoint_filename = 'test_508_endpoint.php';
$test_endpoint_content = <<<EOT
<?php

include dirname(__DIR__, 3) . '/include/boot.php';

if (getval('sign', '') !== '{$test_signature}') {
    exit;
}

echo json_encode(process_file_upload(\$_FILES['file'], new SplFileInfo('{$dest}'), []));

EOT;
$test_source_file = get_temp_dir(false) . '/test_508.txt';
if (!(
    file_put_contents(get_temp_dir(false) . "/{$test_endpoint_filename}", $test_endpoint_content)
    && file_put_contents($test_source_file, "Source for {$run_id}")
    && chmod($dest, 0777)
)) {
    echo "[ENV] Set up 'HTTP file uploaded' use case - ";
    return false;
}
// - make request and get (JSON encoded) response
$ch = curl_init(get_temp_dir(true) . "/{$test_endpoint_filename}");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['sign' => $test_signature, 'file' => new CURLFile($test_source_file)]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
$result_decoded = json_decode($result, true);
curl_close($ch);
// - assert expectation
if (!(is_array($result_decoded) && isset($result_decoded['success']) && $result_decoded['success'])) {
    echo "Use case: HTTP file uploaded - ";
    return false;
}

// Tear down
unset($run_id, $use_cases, $result, $dest, $expect_fail_cond);
array_map('unlink', array_merge(glob(sys_get_temp_dir() . '/test_508*'), glob(get_temp_dir() . '/test_508*')));

return true;
