<?php
include '../../../include/db.php';
include_once '../../../include/general.php';
include_once '../../../include/resource_functions.php';
include_once '../../../include/collections_functions.php';

if('cli' != PHP_SAPI)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied - Command line only!');
    }
include __DIR__ . "/../include/google_vision_functions.php";

logScript("Google Vision plugin - processing_existing.php script...");

$collections = array();
$ignore_resource_type_constraint = false;

$cli_short_options = 'c:';
$cli_long_options  = array('collection:');
$cli_options = getopt($cli_short_options, $cli_long_options);
if($cli_options !== false)
    {
    foreach($cli_options as $option_name => $option_value)
        {
        if(in_array($option_name, array('c', 'collection')))
            {
            if(is_array($option_value))
                {
                $collections = $option_value;
                continue;
                }

            $collections[] = $option_value;
            }
        }
    }

if(empty($collections))
    {
    $sql_gv_restypes = join("','", $google_vision_restypes);
    $resources = sql_array("
        SELECT ref AS `value`
          FROM resource
         WHERE (google_vision_processed IS NULL OR google_vision_processed = 0)
           AND ref > 0
           AND has_image = 1
           AND resource_type IN ('{$sql_gv_restypes}')");
    }
else
    {
    $ignore_resource_type_constraint = true;
    $resources = array();

    foreach($collections as $collection)
        {
        if(!is_numeric($collection))
            {
            logScript("Warning: Collection ID '{$collection}' is not numeric!");
            continue;
            }

        $collection_resources = get_collection_resources($collection);
        $resources = array_merge($resources, $collection_resources);
        }

    $resources = array_unique($resources);
    }

foreach($resources as $resource)
    {
    logScript("Processing resource #{$resource}");

    google_visionProcess($resource, true, $ignore_resource_type_constraint);
    }