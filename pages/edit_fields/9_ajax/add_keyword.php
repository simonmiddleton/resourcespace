<?php
include dirname(__FILE__) . '/../../../include/db.php';
include_once dirname(__FILE__) . '/../../../include/general.php';
include dirname(__FILE__) . '/../../../include/authenticate.php';
include_once dirname(__FILE__) . '/../../../include/resource_functions.php';
include_once dirname(__FILE__) . '/../../../include/node_functions.php';

$field   = getvalescaped('field', '');
$keyword = getvalescaped('keyword', '');
$result  = array();

if(checkperm('bdk' . $field))
    {
    header('HTTP/1.1 401 Unauthorized');
    die('Permission denied!');
    }

// Add node and return to jQuery autocomplete the new node ID
$new_node_id = set_node(null, $field, $keyword, null, null);

if(false !== $new_node_id && is_numeric($new_node_id))
    {
    $result['new_node_id'] = $new_node_id;
    }

echo json_encode($result);
exit();