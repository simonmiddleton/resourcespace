<?php
include_once __DIR__ . "/../../include/db.php";
include_once __DIR__ . "/../../include/general.php";


$columns_to_be_removed = array();

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Checking resource table for fieldX columns");

foreach(sql_query('DESCRIBE resource;') as $resource_table_column)
    {
    if(isset($resource_table_column['Field']) && substr($resource_table_column['Field'], 0, 5) !== 'field')
        {
        continue;
        }

    $columns_to_be_removed[] = $resource_table_column['Field'];

    $log = PHP_EOL . "Marking column '{$resource_table_column['Field']}' to be dropped." . PHP_EOL;
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, $log);
    echo ($cli ? $log : nl2br(str_pad($log, 4096)));
    ob_flush();
    flush();
    }

if(!empty($columns_to_be_removed))
    {
    $columns_list_sql = implode(', DROP COLUMN ', $columns_to_be_removed);
    $alter_table_sql = "ALTER TABLE resource DROP COLUMN {$columns_list_sql}";

    $log = PHP_EOL . "Running SQL: {$alter_table_sql}" . PHP_EOL;
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, $log);
    echo ($cli ? $log : nl2br(str_pad($log, 4096)));
    ob_flush();
    flush();

    sql_query($alter_table_sql);
    }