<?php
/**
* Log activity in the system (e.g user deleted a user)
* 
* @param string  $note                  Notes/comments regarding this activity
* @param string  $log_code
* @param string  $value_new
* @param string  $remote_table
* @param string  $remote_column
* @param string  $remote_ref
* @param string  $ref_column_override
* @param string  $value_old
* @param string  $user                  User that ran the activity
* @param boolean $generate_diff
* 
* @return void
*/
function log_activity($note=null, $log_code=LOG_CODE_UNSPECIFIED, $value_new=null, $remote_table=null, $remote_column=null, $remote_ref=null, $ref_column_override=null, $value_old=null, $user=null, $generate_diff=false)
	{

	if (is_null($log_code))
		{
		$log_code = LOG_CODE_UNSPECIFIED;
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
		$row = ps_query("SELECT " . columns_in($remote_table) . " FROM `{$remote_table}` WHERE `" . (is_null($ref_column_override) ? 'ref' : $ref_column_override) . "` = ?",array("i",$remote_ref));
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

    $parameters=array("i",$user,"s",(!LOG_CODE_validate($log_code) ? LOG_CODE_UNSPECIFIED : $log_code));
    $parameters[]="s"; $parameters[]=(is_null($note) ? null : $note);
    $parameters[]="s"; $parameters[]=(is_null($value_old) ? null : $value_old);
    $parameters[]="s"; $parameters[]=(is_null($value_new) ? null : $value_new);
    $parameters[]="s"; $parameters[]=(!is_null($value_old) && !is_null($value_new) && $generate_diff ? log_diff($value_old,$value_new) : '');
    $parameters[]="s"; $parameters[]=(is_null($remote_table) ? null : $remote_table);
    $parameters[]="s"; $parameters[]=(is_null($remote_column) ? null : $remote_column);
    $parameters[]="s"; $parameters[]=(is_null($remote_ref) ? null : mb_strcut($remote_ref, 0, 100));

	ps_query("INSERT INTO `activity_log` (`logged`,`user`,`log_code`,`note`,`value_old`,`value_new`,`value_diff`,`remote_table`,`remote_column`,`remote_ref`) 
              VALUES (NOW()," . ps_param_insert(count($parameters)/2) . ")", $parameters);
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
    
    $parameters=array();
    $sql = "SELECT date, ref, resource, type, resource_type_field AS field, user, notes, diff, usageoption FROM resource_log WHERE type not in ('l', 't')";
    if($minref > 0)
        {
        $sql .= " AND ref >= ?";
        $parameters[]="i"; $parameters[]=(int)$minref;
        }
    else
        {
        $sql .= " AND datediff(now(),date) < ?";
        $parameters[]="i"; $parameters[]=(int)$days;
        }
        
    if($maxrecords > 0)
        {
        $sql .= " LIMIT " . (int)$maxrecords;
        }
        
    $results = ps_query($sql,$parameters);
    return $results;
    }
 
/**
* Get activity log entries from log tables (e.g activity_log, resource_log and collection_log)
* 
* @uses ps_query()
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

        # Create named where statement variable
        $$where_var = $where_statement;
        }

    $log_codes = array_values(LOG_CODE_get_all());
    $when_statements  = "";
    foreach($log_codes as $log_code)
        {
        $log_code_description = "";

        if(!isset($GLOBALS['lang']["log_code_{$log_code}"]))
            {
            if(!isset($GLOBALS['lang']["collectionlog-{$log_code}"]))
                {
                continue;
                }

            $log_code_description = $GLOBALS['lang']["collectionlog-{$log_code}"];

            $when_statements .= " WHEN BINARY('{$log_code}') THEN '{$log_code_description}'";

            continue;
            }

        $log_code_description = $GLOBALS['lang']["log_code_{$log_code}"];

        $when_statements .= " WHEN BINARY('{$log_code}') THEN '{$log_code_description}'";
        }

    $count_statement_start = "";
    $count_statement_end = "";

    $sql_query = "
                 SELECT
                        `activity_log`.`logged` AS 'datetime',
                        `user`.`username` AS 'user',
                        CASE BINARY(`activity_log`.`log_code`) {$when_statements} ELSE `activity_log`.`log_code` END AS 'operation',
                        `activity_log`.`note` AS 'notes',
                        NULL AS 'resource_field',
                        `activity_log`.`value_old` AS 'old_value',
                        `activity_log`.`value_new` AS 'new_value',
                        if(`activity_log`.`value_diff`='','',concat('<pre>',`activity_log`.`value_diff`,'</pre>')) AS 'difference',
                        '' AS 'access_key',
                        `activity_log`.`remote_table`AS 'table',
                        `activity_log`.`remote_column` AS 'column',
                        `activity_log`.`remote_ref` AS 'table_reference'
                   FROM `activity_log`
        LEFT OUTER JOIN `user` ON `activity_log`.`user`=`user`.`ref`
                  WHERE
                        {$where_activity_log_statement}
                        (
                            `activity_log`.`ref` LIKE ?
                            OR `activity_log`.`logged` LIKE ?
                            OR `user`.`username` LIKE ?
                            OR `activity_log`.`note` LIKE ?
                            OR `activity_log`.`value_old` LIKE ?
                            OR `activity_log`.`value_new` LIKE ?
                            OR `activity_log`.`value_diff` LIKE ?
                            OR `activity_log`.`remote_table` LIKE ?
                            OR `activity_log`.`remote_column` LIKE ?
                            OR `activity_log`.`remote_ref` LIKE ?
                            OR (CASE BINARY(`activity_log`.`log_code`) {$when_statements} ELSE `activity_log`.`log_code` END) LIKE ?
                        )

                  UNION

                 SELECT
                        `resource_log`.`date` AS 'datetime',
                        `user`.`username` AS 'user',
                        CASE BINARY(`resource_log`.`type`) {$when_statements} ELSE `resource_log`.`type` END AS 'operation',
                        `resource_log`.`notes` AS 'notes',
                        `resource_type_field`.`title` AS 'resource_field',
                        `resource_log`.`previous_value` AS 'old_value',
                        '' AS 'new_value',
                        if(`resource_log`.`diff`='','',concat('<pre>',`resource_log`.`diff`,'</pre>')) AS 'difference',
                        `resource_log`.`access_key` AS 'access_key',
                        'resource' AS 'table',
                        'ref' AS 'column',
                        `resource_log`.`resource` AS 'table_reference'
                   FROM `resource_log`
        LEFT OUTER JOIN `user` ON `resource_log`.`user`=`user`.`ref`
        LEFT OUTER JOIN `resource_type_field` ON `resource_log`.`resource_type_field`=`resource_type_field`.`ref`
                  WHERE
                        {$where_resource_log_statement}
                        (
                            `resource_log`.`ref` LIKE ?
                            OR `resource_log`.`date` LIKE ?
                            OR `user`.`username` LIKE ?
                            OR `resource_log`.`notes` LIKE ?
                            OR `resource_log`.`previous_value` LIKE ?
                            OR 'resource' LIKE ?
                            OR 'ref' LIKE ?
                            OR `resource_log`.`resource` LIKE ?
                            OR (CASE BINARY(`resource_log`.`type`) {$when_statements} ELSE `resource_log`.`type` END) LIKE ?
                        )

                  UNION

                 SELECT
                        `collection_log`.`date` AS 'datetime',
                        `user`.`username` AS 'user',
                        CASE BINARY(`collection_log`.`type`) $when_statements ELSE `collection_log`.`type` END AS 'operation',
                        `collection_log`.`notes` AS 'notes',
                        NULL AS 'resource_field',
                        '' AS 'old_value',
                        '' AS 'new_value',
                        '' AS 'difference',
                        '' AS 'access_key',
                        if(`collection_log`.`resource` IS NULL,'collection','resource') AS 'table',
                        'ref' AS 'column',
                        if(`collection_log`.`resource` IS NULL,`collection_log`.`collection`,`collection_log`.`resource`) AS 'table_reference'
                   FROM `collection_log`
        LEFT OUTER JOIN `user` ON `collection_log`.`user`=`user`.`ref`
        LEFT OUTER JOIN `collection` ON `collection_log`.`collection`=`collection`.`ref`
                  WHERE
                        {$where_collection_log_statement}
                        (
                            `collection_log`.`collection` LIKE ?
                            OR `collection_log`.`date` LIKE ?
                            OR `collection_log`.`notes` LIKE ?
                            OR `collection_log`.`resource` LIKE ?
                            OR `collection`.`name` LIKE ?
                            OR `user`.`username` LIKE ?
                            OR (CASE BINARY(`collection_log`.`type`) {$when_statements} ELSE `collection_log`.`type` END) LIKE ?
                        )

        ORDER BY `datetime` DESC
    ";

    $parameters=array();
    # Count the number of placeholders to parameterise
    $placeholder_count=substr_count($sql_query,"?");
    for($n=0;$n < $placeholder_count ;$n++) {
        $parameters[]="s";
        $parameters[]="%".$search."%";
    }

    # Wrap the query as a subquery within a table selection if necessary
    if(trim($table) !== '')
        {
        $outer_sql_query = "SELECT * FROM ({$sql_query}) AS `logs` WHERE `logs`.`table` = ? ";
        $parameters[]="s";$parameters[]=$table;    

        if(is_numeric($table_reference) && $table_reference > 0)
            {
            $outer_sql_query .= "AND `logs`.`table_reference` = ?";
            $parameters[]="i";$parameters[]=$table_reference;    
        }

        $sql_query = $outer_sql_query;
        }

    $limit = sql_limit($offset, $rows);

    if($count)
        {
        $count_statement_start = "SELECT COUNT(*) AS value FROM (";
        $count_statement_end = ") AS count_select";
        $sql_query = $count_statement_start . $sql_query . $count_statement_end;
        return ps_value($sql_query,$parameters,0);
        }
    else
        {
        $sql_query .= " ".$limit;
        return ps_query($sql_query,$parameters);
        }
    }

/**
* Use resource log to obtain a count of resources downloaded by the specified user in the last X days
* 
* @param integer  $userref                        User reference
* @param integer  $user_dl_days                   The number of days to check the resource log for 
* 
* @return integer  download count
*/
function get_user_downloads($userref,$user_dl_days)
    {
    $daylimit = (int)$user_dl_days != 0 ? (int)$user_dl_days : 99999;
    $parameters=array("i",(int)$userref, "i",$daylimit*60*60*24);

    $count = ps_value("SELECT COUNT(DISTINCT resource) value 
        FROM resource_log rl
        WHERE rl.type='d'
        AND rl.user = ?
        AND TIMESTAMPDIFF(SECOND,date,now()) <= ?",$parameters,0);
        
    return $count;
    }


/**
* Add detail of node changes to resource log
* 
* @param integer $resource          Resource ID
* @param array   $nodes_added       Array of node IDs that have been added
* @param array   $nodes_removed     Array of node IDs that have been removed
* @param string  $lognote           Optional note to add to log entry
* 
* @return boolean                   Success/failure
*/
function log_node_changes($resource,$nodes_added,$nodes_removed,$lognote = "")
    {
    if((string)(int)$resource !== (string)$resource)
        {
        return false;
        }
    $nodefieldchanges = array();
    foreach ($nodes_removed as $node)
        {
        $nodedata = array();
        if(get_node($node, $nodedata))
            {
            $nodefieldchanges[$nodedata["resource_type_field"]][0][] = $nodedata["name"];
            }
        }
    foreach ($nodes_added as $node)
        {
        $nodedata = array();
        if(get_node($node, $nodedata))
            {
            $nodefieldchanges[$nodedata["resource_type_field"]][1][] = $nodedata["name"];
            }
        }
    foreach ($nodefieldchanges as $key => $value)
        {
        // Log changes to each field separately
        // Prefix with a comma so that log_diff() can log each node change correctly
        $fromvalue  = isset($value[0]) ? "," . implode(",",$value[0]) : "";
        $tovalue    = isset($value[1]) ? "," . implode(",",$value[1]) : "";
        resource_log($resource,LOG_CODE_EDITED,$key,$lognote,$fromvalue,$tovalue);
        return true;
        }

    // Nothing to log
    return false;
    }

/**
* Log search events
* 
* @param string  $search         Actual search string {@see do_search()}
* @param array   $resource_types Resource types filter
* @param array   $archive_states Archive states filter
* @param integer $result_count   Search result count
* 
* @return void
*/
function log_search_event(string $search, array $resource_types, array $archive_states, int $result_count)
    {
    global $userref;

    $resource_types = array_filter($resource_types, 'is_int_loose');
    $archive_states = array_filter($archive_states, 'is_int_loose');

    $parameters=array();
    $parameters[]="s";$parameters[]=($search === '' ? NULL : $search);
    $parameters[]="s";$parameters[]=(empty($resource_types) ? NULL : implode(', ', $resource_types));
    $parameters[]="s";$parameters[]=(empty($archive_states) ? NULL : implode(', ', $archive_states));
    $parameters[]="i";$parameters[]=(is_null($userref) ? NULL : (int)$userref);
    $parameters[]="i";$parameters[]=(is_int_loose($result_count) ? (int)$result_count : 0);

    $q = "INSERT INTO search_log (search_string, resource_types, archive_states, `user`, result_count) VALUES (?,?,?,?,?)";
    return ps_query($q,$parameters);
    }