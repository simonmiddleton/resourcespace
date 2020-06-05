<?php

if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// Test derestrict filters 

// Save current settings
$saved_search_filter_nodes = $search_filter_nodes;
$saved_edit_filter = $usereditfilter;
$saved_user = $userref;

function test_derestrict_filter_text_update($user,$group,$filtertext)
    {
    global $userdata,$udata_cache;
    $udata_cache = array();
    sql_query("UPDATE usergroup SET derestrict_filter='" . $filtertext . "', derestrict_filter_id=NULL WHERE ref='" . $group . "'");
    $userdata = get_user($user);
    setup_user($userdata);
    }

function test_derestrict_filter_id_update($user,$group,$filterid)
    {
    global $userdata,$udata_cache;
    $udata_cache = array();
    sql_query("UPDATE usergroup SET derestrict_filter_id='" . $filterid . "' WHERE ref='" . $group . "'");
    $userdata = get_user($user);
    setup_user($userdata);
    }

// Set permissions to restrict access to all resources
$derestrictuser = new_user("derestricted");
sql_query("INSERT INTO usergroup (name,permissions,edit_filter,derestrict_filter,edit_filter_id,derestrict_filter_id) SELECT 'testeditgroup','s,e0,f*','','',NULL,NULL FROM usergroup WHERE ref='3';");
$testderestrictgroup = sql_insert_id();
user_set_usergroup($derestrictuser, $testderestrictgroup);


// create 5 new resources
$resourcea  = create_resource(1,0);
$resourceb  = create_resource(1,0);
$resourcec  = create_resource(2,0);
$resourced  = create_resource(2,0);
$resourcee  = create_resource(2,0);
$resourcef  = create_resource(2,0);

$regionfield = create_resource_type_field("Region",0,FIELD_TYPE_CHECK_BOX_LIST,"region");
$classificationfield = create_resource_type_field("Classification",0,FIELD_TYPE_DROP_DOWN_LIST,"classification");

// Add new nodes to fields
$emeanode       = set_node(NULL, $regionfield, "EMEA",'',1000);
$apacnode       = set_node(NULL, $regionfield, "APAC",'',1000);
$americasnode   = set_node(NULL, $regionfield, "Americas",'',1000);
$sensitivenode  = set_node(NULL, $classificationfield, "Sensitive",'',1000);
$opennode       = set_node(NULL, $classificationfield, "Open",'',1000);
$topsecretnode  = set_node(NULL, $classificationfield, "Top Secret",'',1000);

add_resource_nodes($resourcea,array($emeanode, $sensitivenode));
add_resource_nodes($resourceb,array($emeanode, $opennode));
add_resource_nodes($resourcec,array($emeanode, $topsecretnode));
add_resource_nodes($resourced,array($apacnode, $sensitivenode));
add_resource_nodes($resourcee,array($apacnode,$opennode));
add_resource_nodes($resourcef,array($americasnode,$topsecretnode));

// SUBTEST A: old style derestrict filter
$search_filter_nodes = false;
$userderestrictfilter = "classification=Open;region=EMEA";
test_derestrict_filter_text_update($derestrictuser,$testderestrictgroup,$userderestrictfilter);

$openaccessa = get_resource_access($resourcea) == 0;
$openaccessb = get_resource_access($resourceb) == 0;
$openaccessc = get_resource_access($resourcec) == 0;
$openaccessd = get_resource_access($resourced) == 0;
$openaccesse = get_resource_access($resourcee) == 0;
$openaccessf = get_resource_access($resourcef) == 0;

if($openaccessa || !$openaccessb || $openaccessc || $openaccessd || $openaccesse || $openaccessf)
	{
    echo "SUBTEST A";
    return false;
    }

// SUBTEST B: old style derestrict filter migrated
$search_filter_nodes = true;
$migrateresult = migrate_filter($userderestrictfilter);
test_derestrict_filter_id_update($derestrictuser,$testderestrictgroup,$migrateresult);

$openaccessa = get_resource_access($resourcea) == 0;
$openaccessb = get_resource_access($resourceb) == 0;
$openaccessc = get_resource_access($resourcec) == 0;
$openaccessd = get_resource_access($resourced) == 0;
$openaccesse = get_resource_access($resourcee) == 0;
$openaccessf = get_resource_access($resourcef) == 0;
if($openaccessa || !$openaccessb || $openaccessc || $openaccessd || $openaccesse || $openaccessf)
	{
    echo "SUBTEST B";
    return false;
    }

// Reset saved settings
$search_filter_nodes = $saved_search_filter_nodes;
$userpermissions = $savedpermissions;

return true;