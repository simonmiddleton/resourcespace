<?php
if('cli' != PHP_SAPI)
    {
    http_response_code(401);
    exit('Access denied');
    }

include dirname(__FILE__) . '/../../../include/db.php';
include_once dirname(__FILE__) . '/../../../include/general.php';
include_once dirname(__FILE__) . '/../../../include/resource_functions.php';
include_once dirname(__FILE__) . '/../../../include/log_functions.php';

set_time_limit($cron_job_time_limit);

// Init script logging (if set)
global $museumplus_log_directory;
$mplus_log_file = '';
if('' != trim($museumplus_log_directory))
    {
    if(!is_dir($museumplus_log_directory))
        {
        @mkdir($museumplus_log_directory, 0755, true);

        if(!is_dir($museumplus_log_directory))
            {
            logScript("MuseumPlus: Unable to create log directory: '{$museumplus_log_directory}'");
            return false;
            }
        }

    // Cleaning up old files is up to the cron_copy_hitcount hook to do

    // New log file
    $mplus_log_file = fopen($museumplus_log_directory . DIRECTORY_SEPARATOR . 'mplus_script_log_' . date('Y_m_d-H_i') . '.log', 'ab');
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
        logScript('To clear the lock after a failed run, pass in "-c" or "--clear-lock"', $mplus_log_file);
        exit();
        }

    if(in_array($option_name, array('c', 'clear-lock')))
        {
        if(is_process_lock(MPLUS_LOCK))
            {
            logScript('Clearing lock...', $mplus_log_file);
            clear_process_lock(MPLUS_LOCK);
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

    if(!$show_notifications || !$sys_mgmt_notifications)
        {
        continue;
        }

    $message_users[] = $notify_user['ref'];
    }

$connection_data = mplus_generate_connection_data($museumplus_host, $museumplus_application, $museumplus_api_user, $museumplus_api_pass);
if(empty($connection_data))
    {
    mplus_notify($message_users, $lang['museumplus_error_bad_conn_data']);
    exit(1);
    }

// Check when this script was last run - do it now in case of permanent process locks
$museumplus_script_last_ran = '';
if(!check_script_last_ran(MPLUS_LAST_IMPORT, $museumplus_script_failure_notify_days, $museumplus_script_last_ran))
    {
    mplus_notify(
        $message_users,
        str_replace('%script_last_ran', $museumplus_script_last_ran, $lang['museumplus_warning_script_not_completed']));
    }

// Check for a process lock
if(is_process_lock(MPLUS_LOCK)) 
    {
    logScript('MuseumPlus script lock is in place. Deferring...', $mplus_log_file);
    logScript('To clear the lock after a failed run use --clear-lock flag.', $mplus_log_file);

    mplus_notify($message_users, $lang['museumplus_error_script_failed']);

    exit(1);
    }
set_process_lock(MPLUS_LOCK);

// So far all checks are ok, proceed...
$mplus_script_start_time = microtime(true);
$mplus_resources         = get_museumplus_resources();

$museumplus_rs_mappings = unserialize(base64_decode($museumplus_rs_saved_mappings));

logScript('Retrieving data from MuseumPlus...', $mplus_log_file);
foreach($mplus_resources as $mplus_resource)
    {
    if(trim($mplus_resource['mpid']) === '')
        {
        continue;
        }

    logScript("", $mplus_log_file);
    logScript("Checking resource #{$mplus_resource['resource']} with MpID '{$mplus_resource['mpid']}'", $mplus_log_file);

    $mplus_data = mplus_search($connection_data, $museumplus_rs_mappings, 'Object', $mplus_resource['mpid'], $museumplus_search_mpid_field);
    if(empty($mplus_data))
        {
        logScript('No data found! Skipped.', $mplus_log_file);
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
            logScript("Failed to update field #{$rs_field} with the following value '{$mplus_data[$mplus_field]}'", $mplus_log_file);
            logScript("Reason(s): " . implode(', ', $update_errors), $mplus_log_file);
            continue;
            }

        logScript("Successfully updated field #{$rs_field} with '{$mplus_data[$mplus_field]}'", $mplus_log_file);
        }
    }

logScript("", $mplus_log_file);
logScript(sprintf("MuseumPlus script completed in %01.2f seconds.", microtime(true) - $mplus_script_start_time), $mplus_log_file);
fclose($mplus_log_file);
sql_query("UPDATE sysvars SET `value` = NOW() WHERE `name` = '" . MPLUS_LAST_IMPORT . "'");
clear_process_lock(MPLUS_LOCK);