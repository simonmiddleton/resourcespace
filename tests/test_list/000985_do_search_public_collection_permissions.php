<?php


if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}
// Test to ensure that J permission blocks access to resources that are outside public collections that the user has access to.

$saved_userref = $userref;
$userref = 999; 
$savedpermissions = $userpermissions;
// create 5 new resources 


$resourcea=create_resource(1,0);
$resourceb=create_resource(1,0);
$resourcec=create_resource(1,0);
$resourced=create_resource(1,0);
$resourcee=create_resource(1,0);

debug("BANG Resource A: " . $resourcea);
debug("BANG Resource B: " . $resourceb);
debug("BANG Resource C: " . $resourcec);
debug("BANG Resource D: " . $resourced);
debug("BANG Resource E: " . $resourcee);

// Add text to free text to fields
update_field($resourcea,'title','test_000985_A');
update_field($resourceb,'title','test_000985_B');
update_field($resourcec,'title','test_000985_C');
update_field($resourced,'title','test_000985_D');
update_field($resourcee,'title','test_000985_E');

// Set dummy nodes
$dummynode = set_node(NULL,73,'test000985','',1000);
add_resource_nodes($resourcea,array($dummynode));
add_resource_nodes($resourceb,array($dummynode));
add_resource_nodes($resourcec,array($dummynode));
add_resource_nodes($resourced,array($dummynode));
add_resource_nodes($resourcee,array($dummynode));

// Create public collections
$mountains = create_collection(1,'Mountains',0,0,0,true,array('Mountains'));
$cuillin   = create_collection(1,'Cuillin',0,0,0,true,array('Mountains','Cuillin'));
$winter    = create_collection(1,'Winter',0,0,0,true,array('Seasons','Winter'));
$spring = create_collection(1,'Spring',0,0,0,true,array('Seasons','Spring'));

// Add resources to public collections
// Resource A in Mountains
add_resource_to_collection($resourcea,$mountains);
// Resource B in Mountains|Cuillin
add_resource_to_collection($resourceb,$cuillin);
// Resource C in Seasons|Winter
add_resource_to_collection($resourcec,$winter);
// Resource D in Seasons|Spring
add_resource_to_collection($resourced,$spring);
// Resource E in no themes

// SUBTEST A
// ----- Access to all themes and access to resources not in themes -----
// All resources should be shown
$userpermissions = array('s','j*');
$results = do_search('test000985');

if (!is_array($results) 
    || count($results)!=5 
    || !match_values(array_column($results,'ref'),array($resourcea,$resourceb,$resourcec,$resourced,$resourcee)))
    {
    echo "ERROR - SUBTEST A\n";
    echo "Expected Results: Resources({$resourcea},{$resourceb},{$resourcec},{$resourced},{$resourcee})\n";
    echo "Results returned: " . print_r($results,true) . "\n";
    return false;
    }

// END SUBTEST A

// SUBTEST B
// ----- Access to all themes and no access to resources not in themes -----
// Resources a,b,c,d should be shown
$userpermissions = array('s','j*','J');
$results = do_search('test000985');

if (!is_array($results) 
    || count($results)!=4 
    || !match_values(array_column($results,'ref'),array($resourcea,$resourceb,$resourcec,$resourced)))
    {
    echo "ERROR - SUBTEST B\n";
    echo "Expected Results: Resources({$resourcea},{$resourceb},{$resourcec},{$resourced})\n";
    echo "Results returned: " . print_r($results,true) . "\n";
    return false;
    }

// END SUBTEST B

// SUBTEST C
// ----- Access to Mountains themes and no access to resources not in themes -----
// Resource a,b should be shown
$userpermissions = array('s','jMountains','J');
$results = do_search('test000985');

if (!is_array($results) 
    || count($results)!=2 
    || !match_values(array_column($results,'ref'),array($resourcea,$resourceb)))
    {
    echo "ERROR - SUBTEST C\n";
    echo "Expected Results: Resources({$resourcea},{$resourceb})\n";
    echo "Results returned: " . print_r($results,true) . "\n";
    return false;
    }

// END SUBTEST C

// SUBTEST D
// ----- Access to Mountains but not Cuillin subtheme and no access to resources not in themes -----
// Resource a should be shown
$userpermissions = array('s','jMountains','j-Mountains|Cuillin','J');
$results = do_search('test000985');

if (!is_array($results) 
    || count($results)!=1 
    || !match_values(array_column($results,'ref'),array($resourcea)))
    {
    echo "ERROR - SUBTEST D\n";
    echo "Expected Results: Resources({$resourcea})\n";
    echo "Results returned: " . print_r($results,true) . "\n";
    return false;
    }

// END SUBTEST D


//End of tests

$userref = $saved_userref;
$userpermissions = $savedpermissions;
