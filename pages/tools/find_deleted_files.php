<?php
/**
* Utility script to check which resources don't have an original file.
* 
* Note: the script does not care about previews since these can be recreated
*/
if('cli' != PHP_SAPI)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied - Command line only!');
    }

include __DIR__ . '/../../include/db.php';
include_once __DIR__ . '/../../include/general.php';

ob_end_clean();
restore_error_handler();

$resources = sql_query("SELECT ref, file_extension FROM resource WHERE ref > 0 AND archive != {$resource_deletion_state}");

foreach($resources as $resource)
    {
    $file_path = get_resource_path($resource['ref'], true, '', false, $resource['file_extension']);

    if(file_exists($file_path))
        {
        continue;
        }

    echo $file_path . PHP_EOL;
    }