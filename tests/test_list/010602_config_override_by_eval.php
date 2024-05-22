<?php
command_line_only();
# Test overriding variables in the global scope by eval - replicating config override.
# Testing with config who's value is changed from the default in config.default.php

global $metadata_report;
$original_value = $metadata_report;
$metadata_report = false;
$starting_value = $metadata_report;

$config_to_override = '$metadata_report = true;';
$config_to_override_signed = "//SIG" . sign_code($config_to_override) . "\n" . $config_to_override;

override_rs_variables_by_eval($GLOBALS, $config_to_override_signed);

if ($starting_value == $GLOBALS['metadata_report'])
    {
    echo '1. Override was not applied. '; # false didn't become true
    return false;
    }


$config_to_override_signed = '';

override_rs_variables_by_eval($GLOBALS, $config_to_override_signed);

if ($starting_value != $GLOBALS['metadata_report'])
    {
    echo '2. Override was not applied. '; # false was changed
    return false;
    }

$metadata_report = true;
$config_to_override = '$metadata_report = false;';
$config_to_override_signed = "//SIG" . sign_code($config_to_override) . "\n" . $config_to_override;

override_rs_variables_by_eval($GLOBALS, $config_to_override_signed);

if ($starting_value != $GLOBALS['metadata_report'])
    {
    echo '3. Override was not applied. ';  # true didn't become false
    return false;
    }


# Testing with config that isn't defined in config.default.php

if (isset($resource_created_by_filter))
    {
    unset($resource_created_by_filter);
    }

$config_to_override = '$resource_created_by_filter = array(-1);';
$config_to_override_signed = "//SIG" . sign_code($config_to_override) . "\n" . $config_to_override;

override_rs_variables_by_eval($GLOBALS, $config_to_override_signed);

if (!isset($GLOBALS['resource_created_by_filter']) && count($GLOBALS['resource_created_by_filter']) != 0)
    {
    echo '4. Override was not applied. '; # previously undefined variable was not set
    return false;
    }

$config_to_override_signed = '';

override_rs_variables_by_eval($GLOBALS, $config_to_override_signed);

if (isset($GLOBALS['resource_created_by_filter']))
    {
    echo '5. Override was not applied. Variable was not unset. '; # previously defined variable was not unset
    return false;
    }

unset($resource_created_by_filter);
$metadata_report = $original_value;
return true;