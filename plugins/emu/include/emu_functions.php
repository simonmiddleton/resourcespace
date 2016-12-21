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
* Format date for EMu use (similar to the ISO8601 date format except
* the time zone designator is not included)
* 
* @param string  $format    PHP's date() valid format
* @param integer $timestamp
* 
* @return string
*/
function emu_format_date($timestamp, $format = 'c')
    {
    if(!is_string($format) || !is_integer($timestamp))
        {
        trigger_error('Wrong arguments passed to emu_format_date()');
        }

    $result = date($format, $timestamp);

    if('c' == $format)
        {
        $result = substr($result, 0, -6);
        }

    return $result;
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
    $return  = array();
    $emu_api = new EMuAPI($emu_api_server, $emu_api_server_port);

    foreach($emu_rs_mappings as $emu_module => $emu_module_columns)
        {
        $columns_list = array_keys($emu_module_columns);

        $emu_api->setModule($emu_module);
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
function emu_script_log($message, $log_file_pointer = null)
    {
    $message .= PHP_EOL;

    echo $message;

    if(!is_null($log_file_pointer) && (is_resource($log_file_pointer) && 'file' == get_resource_type($log_file_pointer) || 'stream' == get_resource_type($log_file_pointer)))
        {
        fwrite($log_file_pointer, $message);
        }

    return;
    }


/**
* Utility function to figure out emu plugin configuration changed.
* 
* For example, can be used in cases when we need to check all records again because of
* new mappings have been added.
* 
* @return boolean
*/
function check_config_changed()
    {
    global $emu_config_modified_timestamp;

    $script_last_ran = sql_value('SELECT `value` FROM sysvars WHERE name = "last_emu_import"', '');

    if('' == $script_last_ran)
        {
        return true;
        }

    $emu_script_last_ran = strtotime($script_last_ran);

    if($emu_config_modified_timestamp > $emu_script_last_ran)
        {
        return true;
        }

    return false;
    }


/**
* Add search criteria to any existing IMuTerms object before doing a search on a module
* Note: uses $emu_search_criteria which value is basic TexQL (currently only AND and OR
* are supported, without support for nesting)
* 
* @param IMuTerms $imu_terms Any IMuTerms object on which we want to add new search terms
* 
* @return IMuTerms
*/
function add_search_criteria(IMuTerms $imu_terms)
    {
    global $emu_search_criteria;

    $kind            = $imu_terms->getKind();
    $search_criteria = trim($emu_search_criteria);

    if(!is_string($search_criteria) && '' == $search_criteria)
        {
        return $imu_terms;
        }

    // One condition, add it and return IMuTerms object
    if(false === strpos($search_criteria, ' AND ')
        && false === strpos($search_criteria, ' OR ')
        && false !== strpos($search_criteria, '='))
        {
        $condition = explode('=', $search_criteria);

        if('' != $condition[0] && '' != $condition[1])
            {
            $imu_terms->add($condition[0], $condition[1]);
            }

        return $imu_terms;
        }

    /* Example
    [
        [and] = [
            0 => [column, val]
            1 => [column, val]
        ]
        [or]  = [
            0 => [column, val]
            1 => [column, val]
        ]
    ]
    */
    $conditions = array();

    if(false !== strpos($search_criteria, ' AND '))
        {
        $and_search_criterias = explode(' AND ', $search_criteria);

        foreach($and_search_criterias as $and_search_criteria)
            {
            // AND condition
            if(false === strpos($and_search_criteria, ' OR ') && false !== strpos($and_search_criteria, '='))
                {
                $condition = explode('=', $and_search_criteria);
                $column    = trim($condition[0]);
                $value     = trim($condition[1]);

                if('' != $column && '' != $value)
                    {
                    $conditions['and'][] = array($column, $value);
                    $search_criteria     = str_replace("{$and_search_criteria} AND ", '', $search_criteria);
                    $search_criteria     = str_replace("{$and_search_criteria}", '', $search_criteria);
                    }
                }
            }
        }

    if(false !== strpos($search_criteria, ' OR '))
        {
        $and_search_criterias = explode(' OR ', $search_criteria);

        foreach($and_search_criterias as $and_search_criteria)
            {
            // OR condition
            if(false === strpos($and_search_criteria, ' OR ') && false !== strpos($and_search_criteria, '='))
                {
                $condition = explode('=', $and_search_criteria);
                $column    = trim($condition[0]);
                $value     = trim($condition[1]);

                if('' != $column && '' != $value)
                    {
                    $conditions['or'][] = array($column, $value);
                    $search_criteria    = str_replace("{$and_search_criteria} OR ", '', $search_criteria);
                    $search_criteria    = str_replace("{$and_search_criteria}", '', $search_criteria);
                    }
                }
            }
        }

    // Adding to the actual IMuTerms object based on what kind the original
    // object was in order to end up with a correctly configured IMuTerms object
    foreach($conditions as $condition => $available_criteria)
        {
        $use_separate_terms = false;

        if('and' == $condition && 'and' != $kind)
            {
            $separate_terms     = $imu_terms->addAnd();
            $use_separate_terms = true;
            }

        if('or' == $condition && 'or' != $kind)
            {
            $separate_terms     = $imu_terms->addOr();
            $use_separate_terms = true;
            }

        foreach($available_criteria as $criteria)
            {
            if($use_separate_terms)
                {
                $separate_terms->add($criteria[0], $criteria[1]);

                continue;
                }

            $imu_terms->add($criteria[0], $criteria[1]);
            }
        }

    return $imu_terms;
    }