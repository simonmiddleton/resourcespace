<?php
/**
* Check if script last ran more than the failure notification days
* Note: Never/ period longer than allowed failure should return false
* 
* @param string $emu_scrip_last_ran Datetime (string format) when script was last run
* 
* @return boolean
*/
function check_script_last_ran(&$emu_script_last_ran = '')
    {
    global $lang, $emu_script_failure_notify_days;

    $emu_script_last_ran = $lang['status-never'];

    $script_last_ran                   = sql_value('SELECT `value` FROM sysvars WHERE name = "last_emu_import"', '');
    $emu_script_failure_notify_seconds = intval($emu_script_failure_notify_days) * 24 * 60 * 60;

    if('' != $script_last_ran)
        {
        $emu_script_last_ran = date('l F jS Y @ H:m:s', strtotime($script_last_ran));

        // It's been less than Admins allow it to last run, meaning it is all good!
        if(time() < (strtotime($script_last_ran) + $emu_script_failure_notify_seconds))
            {
            return true;
            }
        }

    return false;
    }


/**
* Function to retrieve all resources that have their IRN field set to a value
* and that are within the allowed resource types for an EMu update
* 
* @return array
*/
function get_emu_resources()
    {
    global $emu_irn_field, $emu_resource_types;

    $resource_types_list = '\'' . implode('\', \'', $emu_resource_types) . '\'';

    $emu_resources = sql_query("
            SELECT rd.resource AS resource,
                   rd.value AS object_irn
              FROM resource_data AS rd
        RIGHT JOIN resource AS r ON rd.resource = r.ref AND r.resource_type IN ({$resource_types_list})
             WHERE rd.resource > 0
               AND rd.resource_type_field = '{$emu_irn_field}'
          ORDER BY rd.resource;
    ");

    return $emu_resources;
    }


/**
* Get EMu data by using an array of IRNs.
* 
* @param array $irns             Array of one/ more IRNs to get data by
* @param array $emu_rs_mappings EMu table-column -> RS field mappings
* 
* @return array $return[IRN][Column] = Column value
* 
* Example:
* [74766] => Array
*         (
*             [ObjTitle] => 
*             [ObjName] => mask
*             [ChaAspectRatio] => 
*         )
* 
* [886159] => Array
*         (
*             [ObjTitle] => 
*             [ObjName] => mask
*         )
*/
function get_emu_data($emu_api_server, $emu_api_server_port, array $irns, array $emu_rs_mappings)
    {
    $return = array();

    foreach($emu_rs_mappings as $emu_module => $emu_module_columns)
        {
        $columns_list = array_keys($emu_module_columns);

        $emu_api = new EMuAPI($emu_api_server, $emu_api_server_port, $emu_module);
        $emu_api->setColumns($columns_list);

        $objects_data = $emu_api->getObjectsByIrns($irns);

        foreach($objects_data as $object_data)
            {
            foreach($columns_list as $column)
                {
                if(!array_key_exists($column, $object_data))
                    {
                    continue;
                    }

                $return[$object_data['irn']][$column] = $object_data[$column];
                }
            }
        }

    return $return;
    }


/**
* Log EMu script history both on screen and in a file
* 
* @param string $message
* @param resource $log_file_pointer
* 
* @return void
*/
function emu_script_log($message, $log_file_pointer)
    {
    $message .= PHP_EOL;

    echo $message;

    if(is_resource($log_file_pointer) && 'file' == get_resource_type($log_file_pointer) || 'stream' == get_resource_type($log_file_pointer))
        {
        fwrite($log_file_pointer, $message);
        }

    return;
    }