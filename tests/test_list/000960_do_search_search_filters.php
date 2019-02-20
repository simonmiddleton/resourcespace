<?php

// Save current settings
$saved_search_filter_nodes = $search_filter_nodes;
$search_filter_nodes = false;

include_once(__DIR__ . '/../../include/search_functions.php');

if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// create 5 new resources
$resourcea=create_resource(1,0);
$resourceb=create_resource(1,0);
$resourcec=create_resource(2,0);
$resourced=create_resource(2,0);
$resourcee=create_resource(2,0);

debug("Resource A: " . $resourcea);
debug("Resource B: " . $resourceb);
debug("Resource C: " . $resourcec);
debug("Resource D: " . $resourced);
debug("Resource E: " . $resourcee);


// Add text to free text to fields
update_field($resourcea,'title','Building');
update_field($resourceb,'title','Building');
update_field($resourcec,'title','Car');
update_field($resourced,'title','Boat');
update_field($resourcee,'title','Actor');

// Add new nodes to field
$buildingnode = set_node(NULL, 73, "building",'',1000);
$landscapenode = set_node(NULL, 73, "landscape",'',1000);
$vehiclenode = set_node(NULL, 73, "vehicle",'',1000);
$personnode = set_node(NULL, 73, "person",'',1000);
debug("buildingnode: " . $buildingnode . "\n");
debug("landscapenode: " . $landscapenode . "\n");
debug("vehiclenode: " . $vehiclenode . "\n");
debug("personnode: " . $personnode . "\n");

// Add nodes to resource a
add_resource_nodes($resourcea,array($buildingnode, $landscapenode, 232));
// Add node to resource b
add_resource_nodes($resourceb,array($buildingnode, 232));
// Add nodes to resource c
add_resource_nodes($resourcec,array($vehiclenode, $buildingnode));
// Add nodes to resource d
add_resource_nodes($resourced,array($vehiclenode, $personnode));
// Add node to resource e
add_resource_nodes($resourcee,array($personnode));

// ----- Equals (=)(Equals Character) -----

$usersearchfilter='subject=building';

$results=do_search('');  // this should return 3 assets:  a, b and c
if(count($results)!=3 || !isset($results[0]['ref']) || !isset($results[1]['ref']) || !isset($results[2]['ref'])
	||
    !match_values(array_column($results,'ref'),array($resourcea, $resourceb, $resourcec))
	)
	{ return false; }

// ----- Or (|)(Pipe character) -----

// mobile and billy are indexed within resource_keyword

$usersearchfilter='title=actor|car';

$results=do_search('');  // this should return all the library assets
if(count($results)!=2 || !isset($results[0]['ref']) || !isset($results[1]['ref']) ||
    (
    !match_values(array_column($results,'ref'),array($resourcec, $resourcee))
    )
) return false;
// ----- Not (!=)(Exclamation Mark and Equals Characters combined) -----
$usersearchfilter='title!=car';

$results=do_search('');  // this should return all the library assets

if (in_array($resourcec,array_column($results,'ref'))) return false;
// ----- And Or Combination ------

$usersearchfilter='subject=building;country=france|united kingdom';

$results=do_search('');

if(!match_values(array_column($results,'ref'),array($resourcea, $resourceb))) return false;

// ----- Or Multiple Fields -----
$usersearchfilter='subject|title=vehicle|car;title!=building;title!=boat';
$results=do_search('');
$resultrefs=array_column($results,'ref');      // resourcea and resourceb are omitted as they contain "building" and resourced is omitted as it contains "boat"
if(
	in_array($resourcea,$resultrefs)
	||
	in_array($resourceb,$resultrefs)
	||
	in_array($resourced,$resultrefs)
	||
	!in_array($resourcec,$resultrefs)
	) return false;


// clean up after test
$usersearchfilter = '';
$search_filter_nodes = $saved_search_filter_nodes;

return true;
