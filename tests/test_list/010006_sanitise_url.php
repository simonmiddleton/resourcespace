<?php

command_line_only();

// --- Set up
$initial_baseurl_short = $baseurl_short;
$baseurl_short = '/test'; # required for consistency between dev environments (under web root -vs- in a sub-directory)
// --- End of Set up

$use_cases = [
    [
        'name' => 'Absolute (http) URL should be returned as is, if safe',
        'input' => 'http://test.localhost/path/?p1=v1&p2=v2#fragment',
        'expected' => 'http://test.localhost/path/?p1=v1&p2=v2#fragment',
    ],
    [
        'name' => 'Absolute (https) URL should be returned as is, if safe',
        'input' => 'https://test.localhost/path/?p1=v1&p2=v2#fragment',
        'expected' => 'https://test.localhost/path/?p1=v1&p2=v2#fragment',
    ],
    [
        'name' => 'Relative (root) URL should be returned as is, if safe',
        'input' => "{$baseurl_short}path/?p1=v1&p2=v2#fragment",
        'expected' => "{$baseurl_short}path/?p1=v1&p2=v2#fragment",
    ],
    [
        'name' => 'Relative (root) URL not matching $baseurl_short is sanitised',
        'input' => '/unknwon/path/?p1=v1&p2=v2#fragment',
        'expected' => '#',
    ],
    [
        'name' => 'Relative URL will be sanitised (1)',
        'input' => 'pages/relative_from.php',
        'expected' => '#',
    ],
    [
        'name' => 'Relative URL will be sanitised (2)',
        'input' => '../../pages/relative_from.php',
        'expected' => '#',
    ],
    [
        'name' => 'URI with non-http schema will be sanitised',
        'input' => 'javascript:alert("test")',
        'expected' => '#',
    ],
    [
        'name' => 'URL with html code (not % encoded) will be sanitised',
        'input' => 'https://test.localhost/path/?p1=v1&p2="><p>test</p>',
        'expected' => '#',
    ],
];
foreach ($use_cases as $uc) {
    $result = sanitise_url($uc['input']);
    if ($uc['expected'] !== $result) {
        echo "Use case: {$uc['name']} - ";
        test_log(" - expected >>>{$uc['expected']}<<<");
        test_log(" - result   >>>{$result}<<<");
        return false;
    }
}

// Tear down
$baseurl_short = $initial_baseurl_short;
unset($use_cases, $initial_baseurl_short);

return true;
