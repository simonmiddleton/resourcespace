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
emu_script_log(PHP_EOL . 'Step 1 - building EMu found records data array', $emu_log_file);
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

    emu_script_log("Found {$emu_records_found} records that match your search criteria in '{$emu_module}' module", $emu_log_file);

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

// Step 2 - Get existing ResourceSpace resources with an IRN set
emu_script_log(PHP_EOL . 'Step 2 - finding existing ResourceSpace resources with an IRN set', $emu_log_file);
/*
Example:
Array
(
    [0] => Array
        (
            [resource] => 1
            [object_irn] => 74766
            [created_by_script_flag] => 
        )

    [1] => Array
        (
            [resource] => 5
            [object_irn] => 904207
            [created_by_script_flag] => script
        )
)

*/
$rs_emu_resources       = get_emu_resources();
$rs_emu_resources_count = count($rs_emu_resources);
emu_script_log("Found {$rs_emu_resources_count} resources in ResourceSpace with IRN set.", $emu_log_file);

/*
Using step 1 & 2 to figure out how many:
- existing resources created by SCRIPT and not found in the latest search need to be archived,
- new resources need to be added,
- multimedia files need to be added as alternatives in ResourceSpace because checksums don't match anymore
- resources simply just need their metadata updated
*/
if(0 < $rs_emu_resources_count)
    {
    emu_script_log(PHP_EOL . 'Optional step - archiving resources', $emu_log_file);

    $emu_api_expired_resources = new EMuAPI($emu_api_server, $emu_api_server_port);

    foreach($rs_emu_resources as $rs_emu_resource)
        {
        if(array_key_exists($rs_emu_resource['object_irn'], $emu_records_data))
            {
            continue;
            }

        if('script' != $rs_emu_resource['created_by_script_flag'])
            {
            continue;
            }

        foreach(array_keys($emu_rs_mappings) as $module_name)
            {
            $emu_api_expired_resources->setModule($module_name);
            $emu_api_expired_resources->setIrnColumn();

            $search_terms = new IMuTerms();
            $search_terms->add('irn', $rs_emu_resource['object_irn']);
            $search_terms = add_search_criteria($search_terms);
            $emu_api_expired_resources->setTerms($search_terms);

            // A record has been found so skip this resource
            if(0 < $emu_api_expired_resources->runSearch())
                {
                emu_script_log("Skipping resource ID {$rs_emu_resource['resource']} because a record was found when searching by IRN {$rs_emu_resource['object_irn']} and search criteria", $emu_log_file);

                continue 2;
                }
            }

        emu_script_log("Resource ID {$rs_emu_resource['resource']} is being archived because IRN {$rs_emu_resource['object_irn']} was not found in the last search result set", $emu_log_file);

        if($emu_test_mode)
            {
            emu_script_log("SQL: UPDATE resource SET archive = '2' WHERE ref = '{$rs_emu_resource['resource']}'", $emu_log_file);
            }
        else
            {
            sql_query("UPDATE resource SET archive = '2' WHERE ref = '{$rs_emu_resource['resource']}'");
            }
        }
    }


// Step 3 - Add new resources (also add the original file (master))
emu_script_log(PHP_EOL . 'Step 3 - Add new resources and their master media file as original file', $emu_log_file);
foreach($emu_records_data as $emu_record_irn => $emu_record_fields)
    {
    $irn_index_in_rs_emu_resources = array_search($emu_record_irn, array_column($rs_emu_resources, 'object_irn'));

    if(false === $irn_index_in_rs_emu_resources)
        {
        continue;
        }

    emu_script_log("IRN {$emu_record_irn} was found at index {$irn_index_in_rs_emu_resources} in rs_emu_resources, proof: "
        . PHP_EOL
        . print_r($rs_emu_resources[$irn_index_in_rs_emu_resources], true)
        . 'and will need to be updated (metadata and multimedia checksum checked)', $emu_log_file);
    }





// Step 4 - Add as alternative file the EMu multimedia file if its checksum is different than the one we have in ResourceSpace for this resource

// emu_script_log(print_r($emu_records_data, true), $emu_log_file);


emu_script_log(PHP_EOL . sprintf("EMu Script completed in %01.2f seconds.", microtime(true) - $emu_script_start_time), $emu_log_file);
fclose($emu_log_file);

// TODO uncomment
// clear_process_lock(EMU_SCRIPT_SYNC_LOCK);

// sql_query('DELETE FROM sysvars WHERE name = "last_emu_import"');
// sql_query('INSERT INTO sysvars VALUES ("last_emu_import", NOW())');