<?php
$php_sapi_name = php_sapi_name();

if('cli' != $php_sapi_name)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied');
    }

include dirname(__FILE__) . '/../../../include/db.php';
include_once dirname(__FILE__) . '/../../../include/general.php';
include_once dirname(__FILE__) . '/../../../include/resource_functions.php';
include_once dirname(__FILE__) . '/../include/emu_functions.php';
include_once dirname(__FILE__) . '/../include/emu_api.php';


// Init
ob_end_clean();
set_time_limit($cron_job_time_limit);
define('EMU_SCRIPT_SYNC_LOCK', 'emu_sync_mode_lock');

// Use log file if set
// IMPORTANT: running this script directly (without going via cron_copy_hitcount) may
// not guarantee log directory is set properly, with required permissions in place
$emu_log_file = null;
if('' != $emu_log_directory)
    {
    $emu_log_file = fopen($emu_log_directory . DIRECTORY_SEPARATOR . 'emu_script_log_sync_' . date('d_m_Y__H_i') . '.log', 'ab');
    }

// Last chance to make sure this is what you want
if(EMU_SCRIPT_MODE_SYNC != $emu_script_mode)
    {
    emu_script_log('ALERT - Trying to run script with wrong script mode set.', $emu_log_file);

    exit();
    }

// TODO uncomment
/*// Check if we need to clear locks or need help using the script
if('cli' == $php_sapi_name && 2 == $argc)
    {
    if(in_array($argv[1], array('--help', '-help', '-h', '-?')))
        {
        emu_script_log('To clear the lock after a failed run, pass in "--clearlock", "-clearlock", "-c" or "--c"', $emu_log_file);
        exit();
        }
    else if(in_array($argv[1], array('--clearlock', '-clearlock', '-c', '--c')))
        {
        if(is_process_lock(EMU_SCRIPT_SYNC_LOCK))
            {
            clear_process_lock(EMU_SCRIPT_SYNC_LOCK);
            }
        }
    else
        {
        emu_script_log("Unknown argv: {$argv[1]}", $emu_log_file);
        exit();
        }
    }

// Check for a process lock
if(is_process_lock(EMU_SCRIPT_SYNC_LOCK)) 
    {
    echo 'EMu script lock is in place. Deferring.' . PHP_EOL . 'To clear the lock after a failed run use --clearlock flag.' . PHP_EOL;

    $emu_script_failed_subject = ($emu_test_mode ? 'TESTING MODE: ' : '') . 'EMu Import script - FAILED';
    send_mail($email_notify, $emu_script_failed_subject, "The EMu script failed to run because a process lock was in place. This indicates that the previous run did not complete.\r\n\r\nIf you need to clear the lock after a failed run, run the script as follows:\r\n\r\nphp emu_script.php --clearlock\r\n", $email_from);
    exit();
    }
set_process_lock(EMU_SCRIPT_SYNC_LOCK);*/




// emu_script_log($message, $emu_log_file);



$emu_rs_mappings       = unserialize(base64_decode($emu_rs_saved_mappings));
$emu_script_start_time = microtime(true);
$emu_records_limit     = 100;
$emu_records_data      = array();

emu_script_log('Starting...', $emu_log_file);

$emu_api = new EMuAPI($emu_api_server, $emu_api_server_port);

// Step 1 - Build the EMu Objects Data array
emu_script_log('Step 1 - building EMu found records data array', $emu_log_file);
foreach($emu_rs_mappings as $emu_module => $emu_module_columns)
    {
    $emu_api->setModule($emu_module);

    $columns_list = array_keys($emu_module_columns);

    $emu_api->setColumns($columns_list);

    // Build where clause
    $search_terms = new IMuTerms();
    if(!check_config_changed())
        {
        $script_last_ran = sql_value('SELECT `value` FROM sysvars WHERE name = "last_emu_import"', '');

        if('' != $script_last_ran)
            {
            $search_terms->add('modifiedTimeStamp', emu_format_date(strtotime($script_last_ran)), '>');
            }
        }
    $search_terms = add_search_criteria($search_terms);
    $emu_api->setTerms($search_terms);

    $emu_records_found = $emu_api->runSearch();

    // Skip modules that did not return any records back
    if(0 >= $emu_records_found)
        {
        emu_script_log("Skip module '{$emu_module}' as it didn't return any records back for search criteria provided", $emu_log_file);
        continue;
        }

    emu_script_log("Found '{$emu_records_found}' records that match your search criteria in '{$emu_module}' module", $emu_log_file);

    $emu_api->setColumns(array('multimedia'));
    $columns_list[] = 'multimedia';

    $offset = 0;

    while($offset < $emu_records_found)
        {
        // Get objects data in batches otherwise you get End of Stream Exception
        $objects_data = $emu_api->getSearchResults($offset, $emu_records_limit);

        foreach($objects_data as $object_data)
            {
            foreach($object_data as $object_data_column => $object_data_column_value)
                {
                if(!in_array($object_data_column, $columns_list))
                    {
                    continue;
                    }

                $emu_records_data[$object_data['irn']][$object_data_column] = $object_data_column_value;
                }
            }

        // Ready to go to the next batch
        $offset += $emu_records_limit;
        }
    }

// Step 2 - Get existing ResourceSpace resources created by "script" that have an IRN set
emu_script_log(PHP_EOL . 'Step 2 - finding how many ResourceSpace resources created by "script" have an IRN set', $emu_log_file);
/*
Example:
Array
(
    [0] => Array
        (
            [resource] => 1
            [object_irn] => 74766
        )

    [1] => Array
        (
            [resource] => 5
            [object_irn] => 904207
        )
)

*/
// TODO: add a filter based on $emu_created_by_script_field for get_emu_resources()
$rs_resources_with_irn = get_emu_resources();

// emu_script_log(print_r($rs_resources_with_irn, true), $emu_log_file);

// Step 3 - Add new resources (also add the original file (master))

// Step 4 - Add as alternative file the EMu multimedia file if its checksum is different than the one we have in ResourceSpace for this resource



fclose($emu_log_file);

emu_script_log(PHP_EOL . sprintf("EMu Script completed in %01.2f seconds.", microtime(true) - $emu_script_start_time), $emu_log_file);

// TODO uncomment
// clear_process_lock(EMU_SCRIPT_SYNC_LOCK);

// sql_query('DELETE FROM sysvars WHERE name = "last_emu_import"');
// sql_query('INSERT INTO sysvars VALUES ("last_emu_import", NOW())');