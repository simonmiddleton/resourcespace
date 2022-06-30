<?php
command_line_only();



$system_status = get_system_status();
$find_stat_idx = array_search('required_php_modules', array_column($system_status['results'], 'name'));
if($find_stat_idx !== false && $system_status['results'][$find_stat_idx]['status'] === 'FAIL')
    {
    echo "INFO: {$system_status['results'][$find_stat_idx]['info']}; - ";
    }


$mysql_log_transactions = true;
$mysql_log_location = '/var/some_incorrect_location';
$system_status = get_system_status();
unset($mysql_log_transactions, $mysql_log_location);
$find_stat_idx = array_search('mysql_log_location', array_column($system_status['results'], 'name'));
if($find_stat_idx === false && $system_status['status'] === 'OK')
    {
    echo 'Bad mysql_log_location - ';
    return false;
    }


$debug_log_location = '/var/some_incorrect_location';
$system_status = get_system_status();
$find_stat_idx = array_search('debug_log_location', array_column($system_status['results'], 'name'));
if($find_stat_idx !== false && $system_status['results'][$find_stat_idx]['status'] !== 'WARNING')
    {
    echo 'Bad debug_log_location as a WARN message - ';
    return false;
    }
$debug_log = true;
$system_status = get_system_status();
unset($debug_log, $debug_log_location);
$find_stat_idx = array_search('debug_log_location', array_column($system_status['results'], 'name'));
if($find_stat_idx !== false && $system_status['results'][$find_stat_idx]['status'] !== 'FAIL')
    {
    echo 'Bad debug_log_location as a FAIL error - ';
    return false;
    }


set_sysvar('last_cron', '2021-01-01');
$system_status = get_system_status();
set_sysvar('last_cron', date('Y-m-d'));
$find_stat_idx = array_search('cron_process', array_column($system_status['results'], 'name'));
if($find_stat_idx === false && $system_status['status'] === 'OK')
    {
    echo 'Cron not executing - ';
    return false;
    }


return true;