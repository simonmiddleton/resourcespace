<?php
include_once('../../include/db.php');
include_once('../../include/general.php');
include_once('../../include/authenticate.php');
include_once('../../include/resource_functions.php');
include_once('../../include/search_functions.php');
include_once('../../include/collections_functions.php');

// Browse bar action endpoint
$action = getvalescaped('action','');
$resource   = getval("resource",0,true);
$return = array();
$return['status'] = 400; // set to default 

if(enforcePostRequest("browse_action"))
    {
    switch ($action)
        {
        case 'add_node':
            $node       = getval("node",0,true);
            $nodeinfo = array();
            get_node($node,$nodeinfo);
            // TODO Check field access
            // TODO CHeck resource edit access
            add_resource_nodes($resource,$nodes=array($node));
            $return['status'] = 200;
            break;
            
        case 'collection_add':
            $collection = getval("collection",0,true);
            
            if (collection_writeable($collection) && add_resource_to_collection($resource,$collection,false))
                {
                $return['status'] = 200;
                }
            break;

        default:
            $return['message'] = $lang["error_generic"] ;
            break;
        }
    }
http_response_code($return['status']);
header('Content-type: application/json');
echo json_encode($return);
exit();

