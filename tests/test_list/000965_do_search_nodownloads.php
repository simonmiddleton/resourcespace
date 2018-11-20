<?php

include_once(__DIR__ . '/../../include/search_functions.php');
if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// Check searching resources with no downloads


$resourcez = create_resource(1,0);
$totalresources = do_search("");
foreach($totalresources as $resource)
    {
    daily_stat("Resource download",$resource["ref"]);    
    }

$resourcea = create_resource(1,0);
$resourceb = create_resource(1,0);

// SUBTEST A
// Do standard !nodownloads search 
$results = do_search("!nodownloads");
if(count($results) != 2 || !isset($results[0]['ref']) || !isset($results[1]['ref'])
	||
    !match_values(array_column($results,'ref'),array($resourcea, $resourceb))
	)
	{
    echo "SUBTEST A - ";
    return false;
    }

// SUBTEST B
// Do !nodownloads search with extra field search
$resourcec = create_resource(1,0);
$new_country = set_node(NULL, 3, 'Atlantis', '', '');

add_resource_nodes($resourceb, array($new_country));
add_resource_nodes($resourcec, array($new_country));

$results = do_search("!nodownloads " . NODE_TOKEN_PREFIX . $new_country);
if(count($results) != 2 || !isset($results[0]['ref']) || !isset($results[1]['ref'])
	||
    !match_values(array_column($results,'ref'),array($resourceb, $resourcec))
	)
	{
    echo "SUBTEST B - ";
    return false;
    }

return true;
