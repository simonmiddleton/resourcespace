<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

$use_cases = array(
    array(
        'offset'          => NULL,
        'rows'            => NULL,
        'expected_output' => ''
    ),
    array(
        'offset'          => NULL,
        'rows'            => 15,
        'expected_output' => 'LIMIT 15'
    ),
    // You cannot offset without limiting number of rows
    array(
        'offset'          => 15,
        'rows'            => NULL,
        'expected_output' => ''
    ),
    array(
        'offset'          => 15,
        'rows'            => 15,
        'expected_output' => 'LIMIT 15,15'
    ),
);

$use_case_number = 1;
foreach($use_cases as $use_case)
    {
    $limit = sql_limit($use_case['offset'], $use_case['rows']);

    if($limit !== $use_case['expected_output'])
        {
        echo "Use case {$use_case_number} - ";
        return false;
        }

    $use_case_number++;
    }

return true;