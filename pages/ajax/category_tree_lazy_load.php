<?php
include '../../include/db.php';
include_once '../../include/general.php';
include '../../include/authenticate.php';
include_once '../../include/node_functions.php';


$ajax         = ('' != getval('ajax', '') ? true : false);
$field        = (int) getvalescaped('field', '', true);
$node_ref     = getvalescaped('node_ref', null, true);
$js_tree_data = array();

if(!metadata_field_edit_access($field))
    {
    header('HTTP/1.1 401 Unauthorized');
    die('Permission denied!');
    }

$nodes = get_nodes($field, $node_ref);

foreach($nodes as $node)
    {
    $js_tree_data[] = array(
            'id'     => $node['ref'],
            'parent' => ('' == $node['parent'] ? '#' : $node['parent']),
            'text'   => $node['name'],
            'state'  => array(
                'opened'   => false,
                // 'selected' => $selected
            ),
            'children' => is_parent_node($node['ref'])
        );
    }


header('Content-Type: application/json');
echo json_encode($js_tree_data);