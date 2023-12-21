<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }


$use_cases = [
    'Simple value' => 'Foo bar',
    'Value with double quotes' => 'Foo "bar"',
    'Value with single quotes' => "Foo 'bar'",
    'Value beginning with a double quote' => '"Foo',
    'Value beginning with a single quote' => "'Bar",
];
foreach($use_cases as $use_case_name => $input)
    {
    $output_double_quotes = sprintf('test="%s"', escape($input));
    $output_single_quotes = sprintf("test='%s'", escape($input));

    if(mb_strpos($output_double_quotes, '""') !== false || mb_strpos($output_single_quotes, "''") !== false)
        {
        echo "Use case: {$use_case_name} - ";
        return false;
        }
    }

// We want to show invalid characters to be able to detect encoding issues!
if (escape("invalid -\x80- char") !== "invalid -\u{FFFD}- char")
    {
    echo "Use case: Invalid character (\x80) - ";
    return false;
    }

return true;