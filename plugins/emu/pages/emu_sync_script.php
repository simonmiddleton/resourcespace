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
    }

// emu_script_log($message, $emu_log_file);



$emu_rs_mappings       = unserialize(base64_decode($emu_rs_saved_mappings));
$emu_script_start_time = microtime(true);


$emu_api = new EMuAPI($emu_api_server, $emu_api_server_port);

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

    emu_script_log("Found '{$emu_records_found}' records that match your search criteria in module '{$emu_module}'", $emu_log_file);

    // foreach($objects_data as $object_data)
    //     {
    //     foreach($columns_list as $column)
    //         {
    //         if(!array_key_exists($column, $object_data))
    //             {
    //             continue;
    //             }

    //         $return[$object_data['irn']][$column] = $object_data[$column];
    //         }
    //     }
    }


// echo '<pre>';print_r($objects_data);echo '</pre>';
// die('<br>You died in ' . __FILE__ . ' @' . __LINE__);






// sql_query('DELETE FROM sysvars WHERE name = "last_emu_import"');
// sql_query('INSERT INTO sysvars VALUES ("last_emu_import", NOW())');