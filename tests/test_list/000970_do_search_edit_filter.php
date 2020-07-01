<?php
if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// Test to ensure that searching for editable resources (using parameter foredit=true) returns all valid resources and no invalid resources

// Setup test
$original_user_data = $userdata;

// create 5 new resources 2 of type 1, 2 of type 2 and 2 of type 3
$resourcea=create_resource(1,0);
$resourceb=create_resource(1,0);
$resourcec=create_resource(2,0);
$resourced=create_resource(2,0);
$resourcee=create_resource(3,0);

debug("Resource A: " . $resourcea);
debug("Resource B: " . $resourceb);
debug("Resource C: " . $resourcec);
debug("Resource D: " . $resourced);
debug("Resource E: " . $resourcee);

// Add text to free text to fields
update_field($resourcea,'title','Sales Document');
update_field($resourceb,'title','Marketing Image');
update_field($resourcec,'title','Engineering Specification');
update_field($resourced,'title','Facilities Invoice');
update_field($resourcee,'title','Finance Spreadsheet');

// Add new nodes to field
$customera_node = set_node(NULL, 73, "customera",'',1000);
$customerb_node = set_node(NULL, 73, "customerb",'',1000);
$customerc_node = set_node(NULL, 73, "customerc",'',1000);
$productone_node = set_node(NULL, 73, "productone",'',1000);
$producttwo_node = set_node(NULL, 73, "producttwo",'',1000);
debug("customera node: " . $customera_node . "\n");
debug("customerb node: " . $customerb_node . "\n");
debug("customerc node: " . $customerc_node . "\n");
debug("productone node: " . $productone_node . "\n");
debug("producttwo node: " . $producttwo_node . "\n");

// Add nodes to resource a
add_resource_nodes($resourcea,array($customera_node, $productone_node));
// Add node to resource b
add_resource_nodes($resourceb,array($customera_node, $producttwo_node));
// Add nodes to resource c
add_resource_nodes($resourcec,array($customerb_node, $producttwo_node));
// Add nodes to resource d
add_resource_nodes($resourced,array($customerb_node, $productone_node, $producttwo_node));
// Add node to resource e
add_resource_nodes($resourcee,array($customerc_node));

$userdata = array();
$userdata[0]["edit_filter"] = "";
$userdata[0]["edit_filter_id"] = "";

// SUBTEST A
// ----- Equals (=)(Equals Character) -----
$usersearchfilter='';
$usereditfilter='subject=producttwo';
$userdata[0]["edit_filter"] = $usereditfilter;
$results=do_search('','','',0,-1,"desc",false,0,false,false,'',false,false,false,true);  // this should return 3 assets:  b, c and d
if(count($results)!=3 || !isset($results[0]['ref']) || !isset($results[1]['ref']) || !isset($results[2]['ref'])
	||
    !match_values(array_column($results,'ref'),array($resourceb, $resourcec, $resourced))
	)
	{
    echo "ERROR - SUBTEST A ";
    return false;
    }


// SUBTEST B
// ----- Or (|)(Pipe character) -----
$usereditfilter='subject=productone|producttwo';
$userdata[0]["edit_filter"] = $usereditfilter;
$results=do_search('','','',0,-1,"desc",false,0,false,false,'',false,false,false,true);  // this should return 4 assets:  a,b, c and d
if(count($results)!=4 || !isset($results[0]['ref']) || !isset($results[1]['ref']) || !isset($results[2]['ref']) || !isset($results[3]['ref'])
	||
    !match_values(array_column($results,'ref'),array($resourcea,$resourceb, $resourcec, $resourced))
	)
	{
    echo "ERROR - SUBTEST B ";
    return false;
    }
    
// SUBTEST C
// ----- Not (!=)(Exclamation Mark and Equals Characters combined) -----
$usereditfilter='subject!=producttwo';
$userdata[0]["edit_filter"] = $usereditfilter;
$results=do_search('@@' . $productone_node,'','',0,-1,"desc",false,0,false,false,'',false,false,false,true);  // this should return 1 asset:  a

if (count($results)!=1 || !in_array($resourcea,array_column($results,'ref')))
    {
    echo "ERROR - SUBTEST C ";
    return false;
    }

// Revert changes
$userdata = $original_user_data;
setup_user($original_user_data);

return true;