<?php
if(PHP_SAPI != 'cli') { exit('Access denied'); }
$museumplus_rs_root = dirname(__FILE__) . '/../../../';
include $museumplus_rs_root . 'include/db.php';
include_once $museumplus_rs_root . 'include/resource_functions.php';
include_once $museumplus_rs_root . 'include/log_functions.php';

$mplus_script_start_time = microtime(true);
set_time_limit($cron_job_time_limit);

// Log in the specified directory (new files get created each time)
$mplus_log_file = null;
if('' != trim($museumplus_log_directory))
    {
    $museumplus_script_log_path = sprintf('%s%smplus_script_log_%s.log', $museumplus_log_directory, DIRECTORY_SEPARATOR, date('Y_m_d-H_i'));
    $museumplus_enable_script_log = false;

    if(is_dir($museumplus_log_directory))
        {
        $museumplus_enable_script_log = true;
        }
    else if(mkdir($museumplus_log_directory, 0755, true))
        {
        $museumplus_enable_script_log = true;
        logScript("[museumplus] Created log directory: '{$museumplus_log_directory}'");
        }
    else
        {
        logScript("[museumplus][warn] Unable to create log directory: '{$museumplus_log_directory}'");
        }

    if($museumplus_enable_script_log)
        {
        $mplus_log_file = fopen($museumplus_script_log_path, 'ab');
        if($mplus_log_file === false)
            {
            logScript("[museumplus][warn] Unable to create log file: '{$museumplus_script_log_path}'");
            }
        else
            {
            logScript("[museumplus] Logging script events in file: '{$museumplus_script_log_path}'");
            }
        }
    // Cleaning up old log files is done by the cron_copy_hitcount hook!
    }

// Script options @see https://www.php.net/manual/en/function.getopt.php
$mplus_short_options = 'hc';
$mplus_long_options  = array(
    'help',
    'clear-lock',
);
foreach(getopt($mplus_short_options, $mplus_long_options) as $option_name => $option_value)
    {
    if(in_array($option_name, array('h', 'help')))
        {
        logScript('[museumplus] To clear the lock after a failed run, pass either the "-c" flag -or- "--clear-lock" option.', $mplus_log_file);
        exit();
        }

    if(in_array($option_name, array('c', 'clear-lock')))
        {
        if(is_process_lock(MPLUS_LOCK) && clear_process_lock(MPLUS_LOCK))
            {
            logScript('[museumplus] Process lock removed!', $mplus_log_file);
            }
        else
            {
            logScript('[museumplus][error] Unable to clear process lock!', $mplus_log_file);
            }
        }
    }

// Prepare list of users to send notifications/emails when needed
$notify_users = get_notification_users('SYSTEM_ADMIN');
$message_users = array();
foreach($notify_users as $notify_user)
    {
    get_config_option($notify_user['ref'], 'user_pref_show_notifications', $show_notifications);
    get_config_option($notify_user['ref'], 'user_pref_system_management_notifications', $sys_mgmt_notifications);
    if(!$show_notifications || !$sys_mgmt_notifications) { continue; }
    $message_users[] = $notify_user['ref'];
    }

// Check when this script was last run
$museumplus_script_last_ran = '';
if(!check_script_last_ran(MPLUS_LAST_IMPORT, $museumplus_script_failure_notify_days, $museumplus_script_last_ran))
    {
    logScript("[museumplus][info] Script last ran: {$museumplus_script_last_ran}", $mplus_log_file);
    mplus_notify($message_users, str_replace('%script_last_ran', $museumplus_script_last_ran, $lang['museumplus_warning_script_not_completed']));
    }

// Check for a process lock
if(is_process_lock(MPLUS_LOCK)) 
    {
    logScript('[museumplus] Script lock is in place. Deferring...', $mplus_log_file);
    logScript('[museumplus] To clear the lock after a failed run, pass either the "-c" flag -or- "--clear-lock" option.', $mplus_log_file);
    mplus_notify($message_users, $lang['museumplus_error_script_failed']);
    exit(1);
    }
set_process_lock(MPLUS_LOCK);



$museumplus_modules_config = plugin_decode_complex_configs($museumplus_modules_saved_config);
$mplus_resources           = mplus_resource_get_association_data([]);
echo 'mplus_resources = ' . print_r($mplus_resources, true) . PHP_EOL;


















die(PHP_EOL . "Process stopped in file " . __FILE__ . " at line " . __LINE__ . PHP_EOL);
# OLD script below
/*
NOT required anymore! TODO: make sure any lang or functions are removed from the code
$connection_data = mplus_generate_connection_data($museumplus_host, $museumplus_application, $museumplus_api_user, $museumplus_api_pass);
if(empty($connection_data))
    {
    mplus_notify($message_users, $lang['museumplus_error_bad_conn_data']);
    exit(1);
    }*/

// // Check when this script was last run - do it now in case of permanent process locks
// $museumplus_script_last_ran = '';
// if(!check_script_last_ran(MPLUS_LAST_IMPORT, $museumplus_script_failure_notify_days, $museumplus_script_last_ran))
//     {
//     mplus_notify(
//         $message_users,
//         str_replace('%script_last_ran', $museumplus_script_last_ran, $lang['museumplus_warning_script_not_completed']));
//     }

// // Check for a process lock
// if(is_process_lock(MPLUS_LOCK)) 
//     {
//     logScript('[museumplus] Script lock is in place. Deferring...', $mplus_log_file);
//     logScript('[museumplus] To clear the lock after a failed run, pass either the "-c" flag -or- "--clear-lock" option.', $mplus_log_file);

//     mplus_notify($message_users, $lang['museumplus_error_script_failed']);

//     exit(1);
//     }
// set_process_lock(MPLUS_LOCK);

// So far all checks are ok, proceed...
// $mplus_script_start_time = microtime(true);
// $mplus_resources         = get_museumplus_resources();

// $museumplus_rs_mappings = plugin_decode_complex_configs($museumplus_rs_saved_mappings);

logScript('[museumplus] Retrieving data from MuseumPlus...', $mplus_log_file);
foreach($mplus_resources as $mplus_resource)
    {
    if(trim($mplus_resource['mpid']) === '')
        {
        continue;
        }

    logScript("[museumplus] ", $mplus_log_file);
    logScript("[museumplus] Checking resource #{$mplus_resource['resource']} with MpID '{$mplus_resource['mpid']}'", $mplus_log_file);

    $mplus_data = mplus_search($connection_data, $museumplus_rs_mappings, 'Object', $mplus_resource['mpid'], $museumplus_search_mpid_field);
    if(empty($mplus_data))
        {
        logScript('[museumplus] No data found! Skipped.', $mplus_log_file);
        continue;
        }

    $existing_resource_data = array_values(
        array_filter(
            get_resource_field_data($mplus_resource['resource'], false, false),
            function($value) use ($museumplus_rs_mappings)
                {
                return in_array($value['ref'], array_unique(array_values($museumplus_rs_mappings)));
                }));

    foreach($museumplus_rs_mappings as $mplus_field => $rs_field)
        {
        if(!array_key_exists($mplus_field, $mplus_data))
            {
            continue;
            }


        $existing_field_value = null;
        $existing_field_index = array_search($rs_field, array_column($existing_resource_data, 'ref'));
        if($existing_field_index !== false)
            {
            $existing_field_value = $existing_resource_data[$existing_field_index]['value'];
            }

        if(!is_null($existing_field_value) && $existing_field_value == $mplus_data[$mplus_field])
                {
                continue;
                }

        $update_errors = array();
        if(!update_field($mplus_resource['resource'], $rs_field, $mplus_data[$mplus_field], $update_errors))
            {
            logScript("[museumplus] Failed to update field #{$rs_field} with the following value '{$mplus_data[$mplus_field]}'", $mplus_log_file);
            logScript("[museumplus] Reason(s): " . implode(', ', $update_errors), $mplus_log_file);
            continue;
            }

        logScript("[museumplus] Successfully updated field #{$rs_field} with '{$mplus_data[$mplus_field]}'", $mplus_log_file);
        }
    }

logScript("[museumplus] ", $mplus_log_file);
logScript(sprintf("[museumplus] Script completed in %01.2f seconds.", microtime(true) - $mplus_script_start_time), $mplus_log_file);
sql_query("UPDATE sysvars SET `value` = NOW() WHERE `name` = '" . MPLUS_LAST_IMPORT . "'");
if($mplus_log_file !== null) { fclose($mplus_log_file); }
clear_process_lock(MPLUS_LOCK);