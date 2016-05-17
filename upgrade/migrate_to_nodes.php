<?php

// Note: It is safe to run this script at any time as it will work on differential data if migration interrupted

include __DIR__ . "/../include/db.php";
include __DIR__ . "/../include/general.php";
include __DIR__ . "/../include/resource_functions.php";

// ---------------------------------------------------------------------------------------------------------------------
// Step 1.  Convert any missing fixed field type options to nodes (where not already deprecated)
// ---------------------------------------------------------------------------------------------------------------------

// IMPORTANT! - Uncomment this line if you want to force migration of fixed field values
// sql_query("update resource_type_field set options=replace(options,'!deprecated,','')");

$resource_type_fields=sql_query('SELECT * FROM `resource_type_field` WHERE `type` IN (' .
    implode(',',$FIXED_LIST_FIELD_TYPES) .
    ") AND NOT `options` REGEXP '^!deprecated' ORDER BY `ref`");

foreach($resource_type_fields as $resource_type_field)
    {
    echo "Migrating resource_type_field {$resource_type_field['ref']}:{$resource_type_field['name']}" . PHP_EOL;
    ob_flush();
    migrate_resource_type_field_check($resource_type_field);
    }

// ---------------------------------------------------------------------------------------------------------------------
// Step 2.  Migrate any missing resource data only when not already existing in resource_node table
// ---------------------------------------------------------------------------------------------------------------------

$resource_type_fields=sql_query('SELECT `ref`,`name` FROM `resource_type_field` WHERE `type` IN (' .
    implode(',',$FIXED_LIST_FIELD_TYPES) . ')');

foreach($resource_type_fields as $resource_type_field)
    {
    $out="Migrating resource_data {$resource_type_field['ref']}:{$resource_type_field['name']}";
    $resource_data_entries=sql_query("SELECT `resource`,`value` FROM `resource_data` WHERE  resource_type_field={$resource_type_field['ref']}");
    $out.=' (' . count($resource_data_entries) . ' rows found)';
    echo str_pad($out,100,' ');
    ob_flush();
    $sql="INSERT INTO `resource_node`(`resource`,`node`)
      SELECT
        `resource_data`.`resource`,
        `node`.`ref`
      FROM
        `resource_data`
      JOIN
        `node`
      ON
        NOT EXISTS(SELECT * FROM `resource_node` WHERE `resource`=`resource_data`.`resource` AND `node`=`node`.`ref`) AND
        `node`.`resource_type_field`={$resource_type_field['ref']} AND
        `resource_data`.`value` REGEXP CONCAT('[\\\^\\\|\\\;,]+', `node`.`name`, '[\\\$\\\|\\\;,]*')
      WHERE
        `resource_data`.`resource_type_field`={$resource_type_field['ref']}";
    sql_query($sql);
    echo mysqli_affected_rows($db);
    echo " rows inserted." . PHP_EOL;
    ob_flush();
    }

