<?php

declare(strict_types=1);

command_line_only();

// --- Set up
$initial_baseurl = $baseurl;
$baseurl = 'http://test.localhost';
// --- End of Set up

$use_cases = [
    [
        'name' => 'Incorrect type',
        'input' => [],
        'expected' => false,
    ],
    [
        'name' => 'Simple',
        'input' => 'http://test.localhost/pages/search.php',
        'expected' => true,
    ],
    [
        'name' => 'Simple (not ours)',
        'input' => 'http://unknown.localhost/pages/search.php',
        'expected' => false,
    ],
    [
        'name' => 'Simple with query string params',
        'input' => 'http://test.localhost/pages/search.php?foo=1&bar=2',
        'expected' => true,
    ],
    [
        'name' => 'URL found later',
        'input' => 'http://unknown.localhost/pages/search.php?foo=1&bar=http://test.localhost/pages/search.php',
        'expected' => false,
    ],
];
foreach($use_cases as $uc)
    {
    $result = is_resourcespace_url($uc['input']);
    if($uc['expected'] !== $result)
        {
        echo "Use case: {$uc['name']} - ";
        test_log("result = {$result}");
        return false;
        }
    }

// Tear down
$baseurl = $initial_baseurl;
unset($use_cases, $result, $initial_baseurl);

return true;