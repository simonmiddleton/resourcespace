<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

include_once(__DIR__ . '/../../include/search_functions.php');
include_once(__DIR__ . '/../../include/search_do.php');

$per_page = 3;

$resources = array();
for($i = 0; $i < 20; $i++)
    {
    $resource_ref = create_resource(1, 0);

    $update_field_errors = array();
    $resource_title = "Resource #000922-{$resource_ref}";
    update_field($resource_ref, 8, $resource_title, $update_field_errors);

    if(!empty($update_field_errors))
        {
        echo "Update failed: {$update_field_errors[0]} - ";
        return false;
        }

    $resources[$i] = array(
        'ref'   => $resource_ref,
        'title' => $resource_title);
    }


################
#### LEGACY ####
################
$fetchrows = 0 + $per_page;

// Use case: getting total number of resources matching search
$resources_000922 = do_search('Resource #000922', 1, 'resourceid', '0', $fetchrows, 'asc');
if(20 != count($resources_000922))
    {
    echo "[Legacy format] total number of resources - ";
    return false;
    }

// Use case: getting the first 3 resources on the first page
if(
    $resources_000922[0]['ref'] != $resources[0]['ref']
    || $resources_000922[1]['ref'] != $resources[1]['ref']
    || $resources_000922[2]['ref'] != $resources[2]['ref']
)
    {
    echo "[Legacy format] displayed resources on first page - ";
    return false;
    }

// PAGING - getting second page
$fetchrows = 3 + $per_page;

$page2_resources_000922 = do_search('Resource #000922', 1, 'resourceid', '0', $fetchrows, 'asc');
if(20 != count($page2_resources_000922))
    {
    echo "[Legacy format][page 2] total number of resources - ";
    return false;
    }

// Use case: getting the next 3 resources on the second page
if(
    $resources_000922[0]['ref'] != $resources[0]['ref']
    || $resources_000922[1]['ref'] != $resources[1]['ref']
    || $resources_000922[2]['ref'] != $resources[2]['ref']
    || $page2_resources_000922[3]['ref'] != $resources[3]['ref']
    || $page2_resources_000922[4]['ref'] != $resources[4]['ref']
    || $page2_resources_000922[5]['ref'] != $resources[5]['ref']
)
    {
    echo "[Legacy format][page 2] displayed resources - ";
    return false;
    }


#####################
##### SQL LIMIT #####
#####################
$fetchrows = array(
    'offset' => 0,
    'rows'   => $per_page
);

// Use case: getting total number of resources matching search
$resources_000922 = do_search('Resource #000922', 1, 'resourceid', '0', $fetchrows, 'asc');
if(20 != count($resources_000922))
    {
    echo "[SQL Limit format] total number of resources - ";
    return false;
    }

// Extract the first page resources
$resources_000922 = array_slice($resources_000922, 0, $per_page);

// Use case: getting the first 3 resources on the first page
if(
    $resources_000922[0]['ref'] != $resources[0]['ref']
    || $resources_000922[1]['ref'] != $resources[1]['ref']
    || $resources_000922[2]['ref'] != $resources[2]['ref']
)
    {
    echo "[SQL Limit format] displayed resources on first page- ";
    return false;
    }

// PAGING - getting second page
$fetchrows = array(
    'offset' => 3,
    'rows'   => $per_page
);

$page2_resources_000922 = do_search('Resource #000922', 1, 'resourceid', '0', $fetchrows, 'asc');
if(20 != count($page2_resources_000922))
    {
    echo "[SQL Limit format][page 2] total number of resources - ";
    return false;
    }

// Extract the second page resources
$page2_resources_000922 = array_slice($page2_resources_000922, 3, $per_page);

// Use case: getting the next 3 resources on the second page
if(
    $page2_resources_000922[0]['ref'] != $resources[3]['ref']
    || $page2_resources_000922[1]['ref'] != $resources[4]['ref']
    || $page2_resources_000922[2]['ref'] != $resources[5]['ref']
)
    {
    echo "[SQL Limit format][page 2] displayed resources - ";
    return false;
    }

return true;