<?php
if('cli' != PHP_SAPI)
    {
    http_response_code(401);
    exit('Access denied - Command line only!');
    }

$webroot = dirname(dirname(__DIR__));
include_once "{$webroot}/include/db.php";
include_once "{$webroot}/include/general.php";
include_once "{$webroot}/include/log_functions.php";
include_once "{$webroot}/include/resource_functions.php";

// Script options (if required)
$cli_short_options = '';
$cli_long_options  = array(
    'manage-method:',
    'delete-permanently',
);

/* managed-method option allows administrators to decide how the script will deal with the duplicates removal:
 - FIFO (First In, First Out) - will remove old resources and keep the last one found
 - LIFO (Last In, First Out) - will remove new duplicate resources and keep the first one found
*/
$manage_method = 'LIFO';
$order_by = 'r.ref ASC';
$delete_permanently = false;

foreach(getopt($cli_short_options, $cli_long_options) as $option_name => $option_value)
    {
    if($option_name == 'manage-method' && !is_array($option_value) && in_array($option_value, array('FIFO', 'LIFO')))
        {
        $manage_method = $option_value;

        if($option_value == 'FIFO')
            {
            $order_by = 'r.ref DESC';
            }

        continue;
        }

    if($option_name == 'delete-permanently')
        {
        $delete_permanently = true;
        }
    }

logScript("Purging duplicates using '{$manage_method}' method!");

$duplicates = sql_query("
        SELECT r.ref, r.file_extension, r.file_checksum
          FROM resource AS r
         WHERE length(r.file_extension) > 0
           AND (r.file_checksum IS NOT NULL AND r.file_checksum <> '')
           AND file_checksum IN (
                    SELECT file_checksum
                      FROM (
                                SELECT file_checksum
                                  FROM resource
                                 WHERE (file_checksum IS NOT NULL AND file_checksum <> '')
                              GROUP BY file_checksum HAVING count(file_checksum) > 1
                            ) AS rfc2
               )
      ORDER BY {$order_by} 
");
logScript('Found #' . count($duplicates) . ' duplicates');
logScript("");

$saved_duplicates = array(); # Key is the resource ID and value is the file checksum

foreach($duplicates as $duplicate)
    {
    logScript("Processing resource #{$duplicate['ref']} having checksum '{$duplicate['file_checksum']}'");

    // We keep one resource based on the method chosen to manage the purge (ie. FIFO/ LIFO)
    if(!in_array($duplicate['file_checksum'], $saved_duplicates))
        {
        logScript("Resource #{$duplicate['ref']} kept");
        $saved_duplicates[$duplicate['ref']] = $duplicate['file_checksum'];
        continue;
        }

    if($delete_permanently)
        {
        logScript("Deleting permanently resource #{$duplicate['ref']}");

        unset($resource_deletion_state);
        delete_resource($duplicate['ref']);
        }
    else
        {
        logScript("Moving duplicate resource #{$duplicate['ref']} to archive state #{$resource_deletion_state}");
        update_archive_status($duplicate['ref'], $resource_deletion_state);
        }
    }

logScript("Script completed!");