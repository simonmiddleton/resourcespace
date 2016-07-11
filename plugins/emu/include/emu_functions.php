<?php
/**
* Check if script last ran more than the failure notification days
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

    if('' != $script_last_ran && time() >= (strtotime($script_last_ran) + $emu_script_failure_notify_seconds))
        {
        $emu_script_last_ran = date('l F jS Y @ H:m:s', strtotime($script_last_ran));

        return true;
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