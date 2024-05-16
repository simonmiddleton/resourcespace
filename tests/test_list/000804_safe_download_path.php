<?php

if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

$errors = array();

$test_file_location1 = get_temp_dir(false, 'automated_tests');

$test_path = $test_file_location1 . '/test.txt';
if (!validate_temp_path($test_path, 'automated_tests'))
    {
    $errors[] = 'Safe path considered unsafe. [1] ';
    }

$test_path = $test_file_location1 . '/../test.txt';
if (validate_temp_path($test_path, 'automated_tests'))
    {
    $errors[] = 'Unsafe path considered safe. [2] ';
    }

$test_file_location2 = get_temp_dir(false);

$test_path = $test_file_location2 . '/test.txt';
if (!validate_temp_path($test_path))
    {
    $errors[] = 'Safe path considered unsafe. [3] ';
    }

$test_path = $test_file_location2 . '/../test.txt';
if (validate_temp_path($test_path))
    {
    $errors[] = 'Unsafe path considered safe. [4] ';
    }

rcRmdir($test_file_location1);

if (count($errors) !== 0)
    {
    echo implode(' ', $errors);
    return false;
    }

return true;