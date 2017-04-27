<?php

// Note: It is safe to run this script at any time as it will work on differential data if migration interrupted

include_once __DIR__ . "/../../include/db.php";
include_once __DIR__ . "/../../include/general.php";
include_once __DIR__ . "/../../include/resource_functions.php";

// ---------------------------------------------------------------------------------------------------------------------
// Step 1.  Convert any missing fixed field type options to nodes (where not already deprecated)
// ---------------------------------------------------------------------------------------------------------------------

// IMPORTANT! - Uncomment this line if you want to force migration of fixed field values
// sql_query("update resource_type_field set options=replace(options,'!deprecated,','')");

$check_options_column=sql_query('SHOW COLUMNS FROM `resource_type_field` LIKE \'OPTIONS\'');
if(count($check_options_column)==0) {return true;}
                                
$resource_type_fields=sql_query('SELECT * FROM `resource_type_field` WHERE `type` IN (' .
    implode(',',$FIXED_LIST_FIELD_TYPES) .
    ") AND NOT `options` LIKE '!deprecated%' ORDER BY `ref`");

foreach($resource_type_fields as $resource_type_field)
    {
    echo "Migrating resource_type_field {$resource_type_field['ref']}:{$resource_type_field['name']}" . PHP_EOL;
    ob_flush();
    migrate_resource_type_field_check($resource_type_field);
    }

// ---------------------------------------------------------------------------------------------------------------------
// Step 2.  Migrate any missing resource_data fixed fields only when not already existing in resource_node table
// ---------------------------------------------------------------------------------------------------------------------

foreach($resource_type_fields as $resource_type_field)
    {
    $out="Migrating resource_data {$resource_type_field['ref']}:{$resource_type_field['name']}";
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT,$out);
    $resource_data_entries=sql_query("SELECT `resource`,`value` FROM `resource_data` WHERE  resource_type_field={$resource_type_field['ref']}");
    $out.=' (' . count($resource_data_entries) . ' rows found)';
    echo str_pad($out,100,' ');
    ob_flush();
    $sql="INSERT INTO `resource_node`(`resource`,`node`,`hit_count`,`new_hit_count`)
      SELECT
        `resource_data`.`resource`,
        `node`.`ref`,
        max(`resource_keyword`.`hit_count`),
        max(`resource_keyword`.`new_hit_count`)
      FROM
        `resource_data`
      JOIN
        `node`
      ON
        NOT EXISTS(SELECT * FROM `resource_node` WHERE `resource`=`resource_data`.`resource` AND `node`=`node`.`ref`) AND
        `node`.`resource_type_field`={$resource_type_field['ref']} AND
        `resource_data`.`value` REGEXP CONCAT('[\\\^\\\|\\\;,]+', `node`.`name`, '[\\\$\\\|\\\;,]*')
      LEFT OUTER JOIN
        `keyword`
      ON
        `keyword`.`keyword`=`node`.`name`
      LEFT OUTER JOIN
        `resource_keyword`
      ON
        `resource_keyword`.`keyword`=`keyword`.`ref` AND `resource_keyword`.`resource`=`resource_data`.`resource`
      WHERE
        `resource_data`.`resource_type_field`={$resource_type_field['ref']}
      GROUP BY
        `resource_data`.`resource`,
        `node`.`ref`";
    sql_query($sql);
    echo mysqli_affected_rows($db);
    echo " rows inserted." . PHP_EOL;
    ob_flush();
    }

