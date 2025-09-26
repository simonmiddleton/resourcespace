<?php

command_line_only();

// --- Set up
$initial_baseurl_short = $baseurl_short;
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
        'name' => 'Allow root-relative URL (web root)',
        'setup' => fn() => $GLOBALS['baseurl_short'] = '/',
        'input' => '/path/?p1=v1&p2=v2#fragment',
        'expected' => '/path/?p1=v1&p2=v2#fragment',
    ],
    [
        'name' => 'Allow root-relative URL (sub-directory: /test)',
        'input' => '/test/path/?p1=v1&p2=v2#fragment',
        'expected' => '/test/path/?p1=v1&p2=v2#fragment',
    ],
    [
        'name' => 'Allow relative URL path (1)',
        'input' => './pages/relative_from.php',
        'expected' => './pages/relative_from.php',
    ],
    [
        'name' => 'Allow relative URL path (2)',
        'input' => '../pages/relative_from.php',
        'expected' => '../pages/relative_from.php',
    ],
    [
        'name' => 'Allow relative URL path (3)',
        'input' => '../../pages/relative_from.php',
        'expected' => '../../pages/relative_from.php',
    ],
    [
        'name' => 'Allow relative URL path (4)',
        'input' => 'page.php',
        'expected' => 'page.php',
    ],
    [
        'name' => 'Allow relative URL path (5)',
        'input' => 'generic_page.html?p1=v1&p2=v2#fragment',
        'expected' => 'generic_page.html?p1=v1&p2=v2#fragment',
    ],
    [
        'name' => 'Unsafe relative URL path will be sanitised (1)',
        'input' => '../pages/relative_from.php?p1=v1&p2="><p>test</p>',
        'expected' => '#',
    ],
    [
        'name' => 'Unsafe relative URL path will be sanitised (2)',
        'input' => '."/pages/relative_from.php?p1=v1&p2="><p>test</p>',
        'expected' => '#',
    ],
    [
        'name' => 'Unsafe relative URL path will be sanitised (3)',
        'input' => 'page.php?p1"=v1&p2="><p>test</p>',
        'expected' => '#',
    ],
    [
        'name' => 'Relative (root) URL not matching $baseurl_short is sanitised (1)',
        'input' => '/unknown/path/?p1=v1&p2=v2#fragment',
        'expected' => '#',
    ],
    [
        'name' => 'Relative (root) URL not matching $baseurl_short is sanitised (2)',
        'setup' => fn() => $GLOBALS['baseurl_short'] = '/',
        'input' => ':path/?p1=v1&p2=v2#fragment',
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
    $baseurl_short = '/test'; # required for consistency between dev environments (under web root -vs- in a sub-directory)

    if (isset($uc['setup'])) {
        $uc['setup']();
    }

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
