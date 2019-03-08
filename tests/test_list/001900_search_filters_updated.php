<?php

include_once(__DIR__ . '/../../include/search_functions.php');

if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// Save current settings
$saved_search_filter_nodes = $search_filter_nodes;
$search_filter_nodes = true;

$allresources = do_search('');
$pre_count = is_array($allresources) ? count($allresources) : 0;

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


// create new 'department' field
$departmentfield = create_resource_type_field("Department",0,FIELD_TYPE_CHECK_BOX_LIST,"department");

// create new 'public' field
$publicfield = create_resource_type_field("Public",0,FIELD_TYPE_CHECK_BOX_LIST,"Yes");

// create new 'sensitive' field
$sensitivefield = create_resource_type_field("Sensitive",0,FIELD_TYPE_RADIO_BUTTONS,"sensitive");

// create new 'emotion' field
$emotionfield = create_resource_type_field("Emotion",0,FIELD_TYPE_DROP_DOWN_LIST,"emotion");

// Add new nodes to fields
$eldoradonode = set_node(NULL, 3, "Eldorado",'',1000);
$atlantisnode = set_node(NULL, 3, "Atlantis",'',1000);
$marketingnode = set_node(NULL, $departmentfield, "Marketing",'',1000);
$publicnode = set_node(NULL, $publicfield, "Yes",'',1000);
$sensitivenode = set_node(NULL, $sensitivefield, "Yes",'',1000);
$happynode = set_node(NULL, $emotionfield, "Happy",'',1000);

debug("eldoradonode: " . $eldoradonode . "\n");
debug("atlantisnode: " . $atlantisnode . "\n");
debug("marketingnode: " . $marketingnode . "\n");
debug("publicnode: " . $publicnode . "\n");
debug("sensitivenode: " . $sensitivenode . "\n");;
debug("happynode: " . $happynode . "\n");

// Add nodes to resource a
add_resource_nodes($resourcea,array($eldoradonode, $atlantisnode));
// Add node to resource b
add_resource_nodes($resourceb,array($atlantisnode, $publicnode));
// Add nodes to resource c
add_resource_nodes($resourcec,array($eldoradonode, $marketingnode, $sensitivenode));
// Add nodes to resource d
add_resource_nodes($resourced,array($happynode, $marketingnode));
// Add node to resource e
add_resource_nodes($resourcee,array($sensitivenode,$atlantisnode));


// SUBTEST A: filter - All rules apply
//
// 1. (country = Eldorado OR Atlantis) OR emotion = Happy
// 2. public=Yes

$filter_name = "Public AND (Happy OR (Atlantis or Eldorado))";
$filter_condition = RS_FILTER_ALL;
$newfilter = save_filter(0,$filter_name,$filter_condition);

// Create rules
$rules = array();
$rules[] = array(RS_FILTER_NODE_IN, array($eldoradonode, $atlantisnode, $happynode));    
save_filter_rule(0, $newfilter, $rules);

$rules = array();
$rules[] = array(RS_FILTER_NODE_IN, array($publicnode));
save_filter_rule(0, $newfilter, $rules);

$usersearchfilter = $newfilter;
$results=do_search('');  // this should return 1 asset:  b
if(count($results) != 1 || !isset($results[0]['ref'])
	||
    !match_values(array_column($results,'ref'),array($resourceb))
	)
	{
    echo "SUBTEST A";
    return false;
    }

// SUBTEST B: filter - Any rules apply
//
// 1. department    = Marketing 
// 2. public        = Yes

$filter_name = "Marketing OR Public";
$filter_condition = RS_FILTER_ANY;
$newfilter = save_filter(0,$filter_name,$filter_condition);

// Create rules
$rules = array();
$rules[] = array(RS_FILTER_NODE_IN, array($marketingnode));    
save_filter_rule(0, $newfilter, $rules);

$rules = array();
$rules[] = array(RS_FILTER_NODE_IN, array($publicnode));
save_filter_rule(0, $newfilter, $rules);

$usersearchfilter = $newfilter;
$results=do_search('');  // this should return 3 assets:  b, c and d
if(count($results)!=3 || !isset($results[0]['ref']) || !isset($results[1]['ref']) || !isset($results[2]['ref'])
	||
    !match_values(array_column($results,'ref'),array($resourceb, $resourcec, $resourced))
	)
	{
    echo "SUBTEST B";
    return false;
    }
  
// SUBTEST C: filter -  No rules apply
//
// 1. country      = Atlantis
// 2. sensitive    = Yes

$filter_name = "NOT Atlantis AND NOT Sensitive";
$filter_condition = RS_FILTER_NONE;
$newfilter = save_filter(0,$filter_name,$filter_condition);

// Create rules
$rules = array();
$rules[] = array(RS_FILTER_NODE_IN, array($sensitivenode));    
save_filter_rule(0, $newfilter, $rules);

$rules = array();
$rules[] = array(RS_FILTER_NODE_IN, array($atlantisnode));
save_filter_rule(0, $newfilter, $rules);

$usersearchfilter = $newfilter;
$results=do_search('');  // this should return 1 asset:  d
if(count($results) != ($pre_count + 1) || !in_array($resourced,array_column($results,'ref')))
    {
    echo "SUBTEST C";
    return false;
    }

// Reset before next script
$usersearchfilter = '';
$search_filter_nodes = $saved_search_filter_nodes;
return true;
