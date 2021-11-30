<?php
if('cli' !== php_sapi_name())
    {
    exit('This utility is command line only.');
    }

$saved_userref      = $userref;
$saved_usergroup    = $usergroup;
$savedpermissions   = $userpermissions;  

// Add a new fixed list field 
$riverfield = create_resource_type_field("River",0,FIELD_TYPE_CHECK_BOX_LIST,"river");
$nilenode = set_node(NULL, $riverfield, "Nile",'',1000);
$amazonnode = set_node(NULL, $riverfield, "Amazon",'',1000);

// Add a new free text field
$boatnamefield = create_resource_type_field("Boat name",0,FIELD_TYPE_TEXT_BOX_SINGLE_LINE,"boatname");

// Set group defaults
$setoptions = array(
    "name" => "Group with defaults",
    "resource_defaults" => "river=Nile;boatname=Sea Monster",
    "permissions" => "c,e-2,s",
    );
$groupref = save_usergroup(0,$setoptions);

// Add a new usergroup with defaults set
$defuser        = new_user("DeeFawlts",$groupref);
$defuserdata    = validate_user("u.ref='$defuser'");
setup_user($defuserdata[0]);

// Create  a resource, defaults should be set
$defresource = create_resource(1);
set_resource_defaults($defresource);

$data = get_resource_field_data($defresource,false,false);
$databyname = array_column($data,"value","name");

if(!isset($databyname["river"]) || $databyname["river"] != "Nile")
    {
    echo "Failed to set defaults for checkbox field - ";
    return false;
    }
if(!isset($databyname["boatname"]) || $databyname["boatname"] != "Sea Monster")
    {
    echo "Failed to set defaults for single line text field - ";
    return false;
    }

// Metadata should have been logged
$resource_log = get_resource_log($defresource);


$userref            = $saved_userref;
$usergroup          = $saved_usergroup;
$userpermissions    = $savedpermissions;

return true;