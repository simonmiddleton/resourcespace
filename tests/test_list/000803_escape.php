<?php

command_line_only();

$use_cases = [
    ['Simple text should be left alone', 'Foo bar', 'Foo bar'],
    ['Ampersand should be encoded', '&', '&amp;'],
    ['Less then sign should be encoded', '<', '&lt;'],
    ['Greater then sign should be encoded', '>', '&gt;'],
    ['Double quotes should be encoded', '"', '&quot;'],
    ['Single quotes should be encoded', "'", '&#039;'],

    // We want to show invalid characters to be able to detect encoding issues!
    ["Invalid character (\x80) should be encoded", "\x80", "\u{FFFD}"],

    [
        'URL query string should be encoded (see ampersand use case)',
        generateURL($baseurl, ['param1' => 'val1', 'param2' => 'val2']) . '#fragment',
        str_replace('&', '&amp;', generateURL($baseurl, ['param1' => 'val1', 'param2' => 'val2']) . '#fragment'),
    ],
    ['File path should be left alone', '/path/to/page.php', '/path/to/page.php'],
    [
        'Text with URI schema keywords should be left alone',
        'Text that may contain URI keywords like -- data: test ',
        'Text that may contain URI keywords like -- data: test ',
    ],
    ['Invalid URI schemes (javascript:)', "javascript:alert('XSS')", 'javascript%3Aalert(&#039;XSS&#039;)'],
    ['Invalid URI schemes (data:)', 'data:,Foo%2C%20Bar%21', 'data%3A,Foo%2C%20Bar%21'],
    ['Invalid URI scheme (embedded tab)', "jav\tascript:alert('XSS')", "jav\tascript%3Aalert(&#039;XSS&#039;)"],
    [
        'Invalid URI scheme (embedded encoded tab)',
        "jav&#x09;ascript:alert('XSS')",
        "jav&amp;#x09;ascript%3Aalert(&#039;XSS&#039;)"
    ],
    [
        'Invalid URI schemes (spaces and meta characters)',
        " &#14;  javascript:alert('XSS')",
        ' &amp;#14;  javascript%3Aalert(&#039;XSS&#039;)'
    ],
    [
        'Invalid URI schemes (inline CSS style)',
        'background-image: url(javascript:alert(\'XSS\'));',
        'background-image: url(javascript%3Aalert(&#039;XSS&#039;));',
    ],
];
foreach ($use_cases as [$use_case, $input, $expected]) {
    $result = escape($input);
    if ($expected !== $result) {
        echo "Use case: {$use_case} - ";
        test_log("expected >>>{$expected}<<<");
        test_log("result   >>>{$result}<<<");
        return false;
    }
}

// Tear down
unset($use_cases, $result);

return true;
