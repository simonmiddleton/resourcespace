<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }


$use_cases = [
    'testnovalfound' => [
        'validation_value' => null,
    ],
    'testval_int' => [
        'value' => '234',
        'validation_value' => '234',
    ],
    'testval_float' => [
        'value' => '234.23',
        'validation_value' => null,
    ],
    'testval_string' => [
        'value' => 'abcd',
        'validation_value' => null,
    ],
    // Shouldn't be a common use case but it has been used like this before
    'testval_array' => [
        'value' => ['A', 'B'],
        'validation_value' => null,
    ],
];


foreach($use_cases as $qsn => $case)
    {
    $case_msg = "Use case '{$qsn}':";

    // simulate a query string param value
    if(isset($case['value']))
        {
        $_GET[$qsn] = $case['value'];
        }

    $test_no_validation = getval($qsn, null);
    $test_w_validation = getval($qsn, null, true);

    if(!isset($case['value']) && !is_null($test_no_validation))
        {
        echo "{$case_msg} return default value (null) for missing QS param - ";
        return false;
        }
    else if(isset($case['value']) && $case['value'] !== $test_no_validation)
        {
        echo "{$case_msg} return QS param value (no validation applied) - ";
        return false;
        }
    else if($case['validation_value'] !== $test_w_validation)
        {
        echo "{$case_msg} return a valid integer from QS param value - ";
        return false;
        }
    }


return true;