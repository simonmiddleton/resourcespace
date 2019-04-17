<?php

include_once __DIR__ . '/definitions.php';		// includes log code definitions for resource_log() callers.

function log_activity($note=null, $log_code=LOG_CODE_UNSPECIFIED, $value_new=null, $remote_table=null, $remote_column=null, $remote_ref=null, $ref_column_override=null, $value_old=null, $user=null, $generate_diff=false)
	{

	if (is_null($log_code))
		{
		$log_code == LOG_CODE_UNSPECIFIED;
		}

	if(!function_exists('log_diff'))
		{
		include_once(__DIR__ . '/resource_functions.php');
		}

	if (is_null($user))
		{
		global $userref;
		$user = isset($userref) && !is_null($userref) ? (int) $userref : 0;
		}

	if (is_null($value_old) && !is_null($remote_table) && !is_null($remote_column) && !is_null($remote_ref))	// only try and get the old value if not explicitly set and we have table details
		{
		$row = sql_query("SELECT * FROM `{$remote_table}` WHERE `" . (is_null($ref_column_override) ? 'ref' : escape_check($ref_column_override)) . "`='{$remote_ref}'");
		if (isset($row[0][$remote_column]))
			{
			$value_old = $row[0][$remote_column];
			$log_code = $log_code==LOG_CODE_UNSPECIFIED ? LOG_CODE_EDITED : $log_code;
			}
		else
			{
			$log_code = $log_code==LOG_CODE_UNSPECIFIED ? LOG_CODE_CREATED : $log_code;
			}
		}

	if ($value_old == $value_new && ($log_code == LOG_CODE_EDITED || $log_code == LOG_CODE_COPIED))	// return if the value has not changed
		{
		return;
		}

	sql_query("INSERT INTO `activity_log` (`logged`,`user`,`log_code`,`note`,`value_old`,`value_new`,`value_diff`,`remote_table`,`remote_column`,`remote_ref`) VALUES (" .
		"NOW()," .
		"'{$user}'," .
		"'" . (!LOG_CODE_validate($log_code) ? LOG_CODE_UNSPECIFIED : $log_code) . "'," .
		"'" . (is_null($note) ? '' : escape_check($note)) . "'," .
		"'" . (is_null($value_old) ? '' : escape_check($value_old)) . "'," .
		"'" . (is_null($value_new) ? '' : escape_check($value_new)) . "'," .
		"'" . (!is_null($value_old) && !is_null($value_new) && $generate_diff ? escape_check(log_diff($value_old,$value_new)) : '') . "'," .
		"'" . (is_null($remote_table) ? '' : escape_check($remote_table)) . "'," .
		"'" . (is_null($remote_column) ? '' : escape_check($remote_column)) . "'," .
		"'" . (is_null($remote_ref) ? '' : escape_check($remote_ref)) . "'" .
		")");
	}


/**
* Log script messages on screen and optionally in a file. If debug_log is enabled, it will also write the message in the 
* debug log file.
* 
* @uses debug()
* 
* @param string   $message
* @param resource $file
* 
* @return void
*/
function logScript($message, $file = null)
    {
    $date_time = date('Y-m-d H:i:s');

    if(PHP_SAPI == 'cli')
        {
        echo "{$date_time} {$message}" . PHP_EOL;
        }

    // Log in debug as well, with extended information to show the backtrace
    global $debug_extended_info;
    $orig_debug_extended_info = $debug_extended_info;
    $debug_extended_info = true;
    debug($message);
    $debug_extended_info = $orig_debug_extended_info;

    // If a file resource has been passed, then write to that file as well
    if(!is_null($file) && (is_resource($file) && 'file' == get_resource_type($file) || 'stream' == get_resource_type($file)))
        {
        fwrite($file, "{$date_time} {$message}" . PHP_EOL);
        }

    return;
    }
 
/**
* Retrieve entries from resource log based on date or references
* 
* @param integer   $minref      (Optional) Minimum ref of resource log entry to return (default 0)
* @param integer   $days       (Optional) Number of days to return. e.g 3 = all results for today, yesterday and the day before. Default = 7 (ignored if minref supplied)
* @param integer   $maxrecords  (Optional) Maximum number of records to return. Default = all rows (0)
* 
* @return array
*/   
 function resource_log_last_rows($minref = 0, $days = 7, $maxrecords = 0)
    {
    if(!checkperm('v'))
        {
        return array();
        }
        
    $sql = "SELECT date, ref, resource, type, resource_type_field AS field, user, notes, diff, usageoption FROM resource_log WHERE type not in ('l', 't')";
    if($minref > 0)
        {
        $sql .= " AND ref>=" . (int)$minref;
        }
    else
        {
        $sql .= " AND datediff(now(),date)<'" . (int)$days . "'";
        }
        
    if($maxrecords > 0)
        {
        $sql .= " LIMIT " . (int)$maxrecords;
        }
        
    $results = sql_query($sql);
    return $results;
    }
 
/**
* Get activity log entries from log tables (e.g activity_log, resource_log and collection_log)
* 
* @uses sql_query()
* 
* @param  string  $search  Search text to filter down results using fuzzy searching
* @param  integer $offset  Specifies the offset of the first row to return
* @param  integer $rows  Specifies the maximum number of rows to return
* @param  array   $where_statements  Where statements for log tables
*                                    Example of where statements:
*                                    $where_statements = array(
*                                        'activity_log' => "`activity_log`.`user`='{$actasuser}' AND ",
*                                        'resource_log' => "`resource_log`.`user`='{$actasuser}' AND ",
*                                        'collection_log' => "`collection_log`.`user`='{$actasuser}' AND ",
*                                    );
* @param  string $table  Table name (e.g resource_type_field, user, resource)
* @param  integer $table_reference  ID of the record in the referred table
* @param  boolean $count  Switch for if the result should be a single count or the result set
* 
* @return array
*/
function get_activity_log($search, $offset, $rows, array $where_statements, $table, $table_reference, $count = false)
    {
    foreach($where_statements as $ws_table => $where_statement)
        {
        $where_var = "where_{$ws_table}_statement";

        $$where_var = $where_statement;
        }

    $log_codes = array_values(LOG_CODE_get_all());
    $when_statements  = "";
    foreach($log_codes as $log_code)
        {
        $log_code_escaped = escape_check($log_code);
        $log_code_description = "";

        if(!isset($GLOBALS['lang']["log_code_{$log_code}"]))
            {
            if(!isset($GLOBALS['lang']["collectionlog-{$log_code}"]))
                {
                continue;
                }

            $log_code_description = escape_check($GLOBALS['lang']["collectionlog-{$log_code}"]);

            $when_statements .= " WHEN ASCII('{$log_code_escaped}') THEN '{$log_code_description}'";

            continue;
            }

        $log_code_description = escape_check($GLOBALS['lang']["log_code_{$log_code}"]);

        $when_statements .= " WHEN ASCII('{$log_code_escaped}') THEN '{$log_code_description}'";
        }

    $count_statement_start = "";
    $count_statement_end = "";

    if($count)
        {
        $count_statement_start = "SELECT COUNT(*) AS value FROM (";
        $count_statement_end = ") AS count_select";
        }

    $sql_query = "
                {$count_statement_start}
                 SELECT
                        `activity_log`.`logged` AS 'datetime',
                        `user`.`username` AS 'user',
                        CASE ASCII(`activity_log`.`log_code`) {$when_statements} ELSE `activity_log`.`log_code` END AS 'operation',
                        `activity_log`.`note` AS 'notes',
                        NULL AS 'resource_field',
                        `activity_log`.`value_old` AS 'old_value',
                        `activity_log`.`value_new` AS 'new_value',
                        if(`activity_log`.`value_diff`='','',concat('<pre>',`activity_log`.`value_diff`,'</pre>')) AS 'difference',
                        `activity_log`.`remote_table`AS 'table',
                        `activity_log`.`remote_column` AS 'column',
                        `activity_log`.`remote_ref` AS 'table_reference'
                   FROM `activity_log`
        LEFT OUTER JOIN `user` ON `activity_log`.`user`=`user`.`ref`
                  WHERE
                        {$where_activity_log_statement}
                        (
                            `activity_log`.`ref` LIKE '%{$search}%'
                            OR `activity_log`.`logged` LIKE '%{$search}%'
                            OR `user`.`username` LIKE '%{$search}%'
                            OR `activity_log`.`note` LIKE '%{$search}%'
                            OR `activity_log`.`value_old` LIKE '%{$search}%'
                            OR `activity_log`.`value_new` LIKE '%{$search}%'
                            OR `activity_log`.`value_diff` LIKE '%{$search}%'
                            OR `activity_log`.`remote_table` LIKE '%{$search}%'
                            OR `activity_log`.`remote_column` LIKE '%{$search}%'
                            OR `activity_log`.`remote_ref` LIKE '%{$search}%'
                            OR (CASE ASCII(`activity_log`.`log_code`) {$when_statements} ELSE `activity_log`.`log_code` END) LIKE '%{$search}%'
                        )

                  UNION

                 SELECT
                        `resource_log`.`date` AS 'datetime',
                        `user`.`username` AS 'user',
                        CASE ASCII(`resource_log`.`type`) {$when_statements} ELSE `resource_log`.`type` END AS 'operation',
                        `resource_log`.`notes` AS 'notes',
                        `resource_type_field`.`title` AS 'resource_field',
                        `resource_log`.`previous_value` AS 'old_value',
                        '' AS 'new_value',
                        if(`resource_log`.`diff`='','',concat('<pre>',`resource_log`.`diff`,'</pre>')) AS 'difference',
                        'resource' AS 'table',
                        'ref' AS 'column',
                        `resource_log`.`resource` AS 'table_reference'
                   FROM `resource_log`
        LEFT OUTER JOIN `user` ON `resource_log`.`user`=`user`.`ref`
        LEFT OUTER JOIN `resource_type_field` ON `resource_log`.`resource_type_field`=`resource_type_field`.`ref`
                  WHERE
                        {$where_resource_log_statement}
                        (
                            `resource_log`.`ref` LIKE '%{$search}%'
                            OR `resource_log`.`date` LIKE '%{$search}%'
                            OR `user`.`username` LIKE '%{$search}%'
                            OR `resource_log`.`notes` LIKE '%{$search}%'
                            OR `resource_log`.`previous_value` LIKE '%{$search}%'
                            OR 'resource' LIKE '%{$search}%'
                            OR 'ref' LIKE '%{$search}%'
                            OR `resource_log`.`resource` LIKE '%{$search}%'
                            OR (CASE ASCII(`resource_log`.`type`) {$when_statements} ELSE `resource_log`.`type` END) LIKE '%{$search}%'
                        )

                  UNION

                 SELECT
                        `collection_log`.`date` AS 'datetime',
                        `user`.`username` AS 'user',
                        CASE ASCII(`collection_log`.`type`) $when_statements ELSE `collection_log`.`type` END AS 'operation',
                        `collection_log`.`notes` AS 'notes',
                        NULL AS 'resource_field',
                        '' AS 'old_value',
                        '' AS 'new_value',
                        '' AS 'difference',
                        if(`collection_log`.`resource` IS NULL,'collection','resource') AS 'table',
                        'ref' AS 'column',
                        if(`collection_log`.`resource` IS NULL,`collection_log`.`collection`,`collection_log`.`resource`) AS 'table_reference'
                   FROM `collection_log`
        LEFT OUTER JOIN `user` ON `collection_log`.`user`=`user`.`ref`
        LEFT OUTER JOIN `collection` ON `collection_log`.`collection`=`collection`.`ref`
                  WHERE
                        {$where_collection_log_statement}
                        (
                            `collection_log`.`collection` LIKE '%{$search}%'
                            OR `collection_log`.`date` LIKE '%{$search}%'
                            OR `collection_log`.`notes` LIKE '%{$search}%'
                            OR `collection_log`.`resource` LIKE '%{$search}%'
                            OR `collection`.`name` LIKE '%{$search}%'
                            OR `user`.`username` LIKE '%{$search}%'
                            OR (CASE ASCII(`collection_log`.`type`) {$when_statements} ELSE `collection_log`.`type` END) LIKE '%{$search}%'
                        )

        ORDER BY `datetime` DESC
        {$count_statement_end}
    ";

    if(trim($table) !== '')
        {
        $table = escape_check($table);
        $outer_sql_query = "SELECT * FROM ({$sql_query}) AS `logs` WHERE `logs`.`table` = '{$table}' ";

        if(is_numeric($table_reference) && $table_reference > 0)
            {
            $outer_sql_query .= "AND `logs`.`table_reference` = '{$table_reference}'";
            }

        $sql_query = $outer_sql_query;
        }

    $limit = sql_limit($offset, $rows);

    if($count)
        {
        return sql_value($sql_query, 0);
        }
    else
        {
        return sql_query("{$sql_query} {$limit}");
        }
    }

