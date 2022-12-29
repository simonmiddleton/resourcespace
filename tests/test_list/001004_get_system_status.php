<?php
command_line_only();



$system_status = get_system_status();
if(isset($system_status['results']['required_php_modules']) && $system_status['results']['required_php_modules']['status'] === 'FAIL')
    {
    echo "INFO: {$system_status['results']['required_php_modules']['info']}; - ";
    }


$mysql_log_transactions = true;
$mysql_log_location = '/var/some_incorrect_location';
$system_status = get_system_status();
unset($mysql_log_transactions, $mysql_log_location);
if(!isset($system_status['results']['mysql_log_location']) && $system_status['status'] === 'OK')
    {
    echo 'Bad mysql_log_location - ';
    return false;
    }


$debug_log_location = '/var/some_incorrect_location';
$system_status = get_system_status();
if(isset($system_status['results']['debug_log_location']) && $system_status['results']['debug_log_location']['status'] !== 'WARNING')
    {
    echo 'Bad debug_log_location as a WARN message - ';
    return false;
    }
$debug_log = true;
$system_status = get_system_status();
unset($debug_log, $debug_log_location);
if(isset($system_status['results']['debug_log_location']) && $system_status['results']['debug_log_location']['status'] !== 'FAIL')
    {
    echo 'Bad debug_log_location as a FAIL error - ';
    return false;
    }


set_sysvar('last_cron', '2021-01-01');
$system_status = get_system_status();
set_sysvar('last_cron', date('Y-m-d'));
if(!isset($system_status['results']['cron_process']) && $system_status['status'] === 'OK')
    {
    echo 'Cron not executing - ';
    return false;
    }


return true;