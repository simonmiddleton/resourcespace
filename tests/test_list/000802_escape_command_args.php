<?php

use Montala\ResourceSpace\CommandPlaceholderArg;

command_line_only();

$use_cases = [
    [
        'name' => 'Simple placeholder replacement',
        'cmd' => "'/usr/bin/madeupcmd' -d dateFormat -o output_file",
        'args' => [
            'dateFormat' => new CommandPlaceholderArg(
                '%Y-%m-%d %H:%M:%S',
                fn($val): bool => preg_match('/[%Y\-mdH:MS[:space:]]/', $val)
            ),
            'output_file' => new CommandPlaceholderArg(
                '/path/to/file.ext',
                fn($val): bool => is_valid_rs_path($val, ['/path/to/'])
            ),
        ],
        'expected' => "'/usr/bin/madeupcmd' -d '%Y-%m-%d %H:%M:%S' -o '/path/to/file.ext'",
    ],
    [
        'name' => 'Block by default metacharacters (throws exception)',
        'cmd' => "ls --ignore=%arg /tmp",
        'args' => ['%arg' => "sth' -l"],
        'expected' => 'ValueError',
    ],
];
$GLOBALS['use_error_exception'] = true;
foreach($use_cases as $uc)
    {
    try {
        $result = escape_command_args($uc['cmd'], $uc['args']);
    } catch (Throwable $t) {
        $result = $t::class;
    }

    if($uc['expected'] !== $result)
        {
        echo "Use case: {$uc['name']} - ";
        test_log("CMD: " . $result);
        test_log("Expected = " . $uc['expected']);
        test_log('--- ');
        return false;
        }
    }

// Tear down
unset($use_cases, $result);
$GLOBALS['use_error_exception'] = false;

return true;
