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
            $field = get_resource_type_field($nodeinfo["resource_type_field"]);

            if(!get_edit_access($resource) || !metadata_field_edit_access($field) || !in_array($field["type"],$FIXED_LIST_FIELD_TYPES) 
                {
                $return['status'] = 400;
                $return['message'] = $lang["error-permissiondenied"];
                break;
                }

            // Check valid change
            $curnodes = get_resource_nodes($resource, $field);
            $multifields = array(FIELD_TYPE_CATEGORY_TREE,FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,FIELD_TYPE_TEXT_BOX_MULTI_LINE);
            $valid = count($curnodes) == 0 || in_array($field["type"],$multifields);

            if($valid)
                {
                add_resource_nodes($resource,$nodes=array($node));
                $return['status'] = 200;
                }
            else
                {
                $return['status'] = 400;
                $return['message'] = $lang['error-invalid_browse_action'];
                }

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

