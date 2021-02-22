<?php
include '../../include/db.php';

$k = getvalescaped('k','');
$upload_collection = getval('upload_share_active',''); 
if ($k=="" || (!check_access_key_collection($upload_collection,$k)))
    {
    include "../../include/authenticate.php";
    if (! (checkperm("c") || checkperm("d")))
        {
        exit ("Permission denied.");
        }
    }

// Initialise
$ajax           = ('' != getval('ajax', '') ? true : false);
$node_ref       = getvalescaped('node_ref', null, true);
$field          = (int) getvalescaped('field', '', true);
$selected_nodes = getvalescaped('selected_nodes', array());
$opened_nodes   = array();
$js_tree_data   = array();

$nodes = get_nodes($field, $node_ref);

// Find the root nodes for any of the searched nodes
// Most of the nodes will most likely be a tree leaf. 
// This allows us to know which tree nodes we need to 
// expand from the begining
foreach($selected_nodes as $selected_node)
    {
    $tree_level = get_tree_node_level($selected_node);

    if(0 === $tree_level)
        {
        continue;
        }

    $found_root_node = get_root_node_by_leaf($selected_node, $tree_level);
    if($found_root_node)
        {
        $opened_nodes[] = $found_root_node;
        }
    }

foreach($nodes as $node)
    {
    $node_opened = false;

    if(in_array($node['ref'], $opened_nodes))
        {
        $node_opened = true;
        }

    $js_tree_data[] = array(
            'id'     => $node['ref'],
            'parent' => ('' == $node['parent'] ? '#' : $node['parent']),
            'text'   => htmlspecialchars(i18n_get_translated($node['name'])),
            'li_attr'=> array(
                'title' => htmlspecialchars(i18n_get_translated($node['name'])),
                'class' => 'show_tooltip'
            ),
            'state'  => array(
                'opened'   => $node_opened,
                'selected' => in_array($node['ref'], $selected_nodes)
            ),
            'children' => is_parent_node($node['ref'])
        );
    }

header('Content-Type: application/json');
echo json_encode($js_tree_data);
