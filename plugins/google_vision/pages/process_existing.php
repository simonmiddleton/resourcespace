<?php
// Do the include and authorization checking ritual
include '../../../include/db.php';
include_once '../../../include/general.php';
include_once '../../../include/resource_functions.php';
if('cli' != PHP_SAPI)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied - Command line only!');
    }
include __DIR__ . "/../include/google_vision_functions.php";


$resources=sql_array("select ref value from resource where (google_vision_processed is null or google_vision_processed=0) and has_image=1 and resource_type in ('" . join("','",$google_vision_restypes) . "')");

foreach ($resources as $resource)
    {
    echo "\n\nProcessing resource " . $resource;
    google_visionProcess($resource,true);
    }

