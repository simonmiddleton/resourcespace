<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

// The point of this test is not to check the escape_check() which already has its own test but to ensure an array of 
// values will be handled accordingly

$unsafe_array = array(
    'value ok',
    "'SQLi possible",
    array(
        'array - value ok',
        "array - 'SQLi possible",
    )
);

$expected_output = array(
    'value ok',
    "\'SQLi possible",
    array(
        'array - value ok',
        "array - \'SQLi possible",
    )
);

if($expected_output !== escape_check_array_values($unsafe_array))
    {
    return false;
    }

return true;