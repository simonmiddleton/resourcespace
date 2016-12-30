<?php
if('cli' !== php_sapi_name())
    {
    exit('This utility is command line only.');
    }


$resource_type_field = 2000;

// Add a new node
$node_id_1 = set_node(null, $resource_type_field, 'New option added via test', null, null);
$node_id_2 = set_node(null, $resource_type_field, 'Option assigned order_by', null, 40);
$node_id_3 = set_node(null, $resource_type_field, '', null, 40);
$node_id_4 = set_node(null, $resource_type_field, null, null, null);
$node_id_5 = set_node(null, '', 'Option with resource_type_field invalid', null, null);
$node_id_6 = set_node(null, null, 'Option with resource_type_field invalid', null, null);

// Have nodes been set? No other integrity check...
$current_node = array();
if(!get_node($node_id_1, $current_node) || !get_node($node_id_2, $current_node))
    {
    return false;
    }

// Check node (expecting parent empty and order by 10)
$current_node = array();
if(get_node($node_id_1, $current_node)
    && ($resource_type_field != $current_node['resource_type_field'] || 'New option added via test' != $current_node['name'] || '' != $current_node['parent'] || 10 != $current_node['order_by'])
)
    {
    return false;
    }

// Check node (expecting parent empty and order by 40
$current_node = array();
if(get_node($node_id_2, $current_node)
   && ($resource_type_field != $current_node['resource_type_field'] || 'Option assigned order_by' != $current_node['name'] || '' != $current_node['parent'] || 40 != $current_node['order_by'])
)
    {
    return false;
    }

// Check cases when either resource_type_field or name are invalid, hence returning false
if(false !== $node_id_3 || false !== $node_id_4 || false !== $node_id_5 || false !== $node_id_6)
    {
    return false;
    }


// Creating some nodes to be used as parents or children
$resource_type_field = 2001;

$parent_node_id_1 = set_node(null, $resource_type_field, 'Parent 1', null, null);
$parent_node_id_2 = set_node(null, $resource_type_field, 'Parent 2 without children', null, null);
$parent_node_id_3 = set_node(null, $resource_type_field, 'Parent 3', null, null);

$child_node_id_11 = set_node(null, $resource_type_field, 'Child 1.1', $parent_node_id_1, null);
$child_node_id_12 = set_node(null, $resource_type_field, 'Child 1.2', $parent_node_id_1, null);
$child_node_id_13 = set_node(null, $resource_type_field, 'Child 1.3', $parent_node_id_1, null);
$child_node_id_31 = set_node(null, $resource_type_field, 'Child 3.1', $parent_node_id_3, null);
$child_node_id_32 = set_node(null, $resource_type_field, 'Child 3.2', $parent_node_id_3, null);
$child_node_id_33 = set_node(null, $resource_type_field, 'Child 3.3', $parent_node_id_3, null);

$parent_children_nodes = array(
    $parent_node_id_1,
    $parent_node_id_2,
    $parent_node_id_3,
    $child_node_id_11,
    $child_node_id_12,
    $child_node_id_13,
    $child_node_id_31,
    $child_node_id_32,
    $child_node_id_33
);

foreach(get_nodes($resource_type_field, null, true) as $nodes)
    {
    if(!in_array($nodes['ref'], $parent_children_nodes))
        {
        return false;
        }
    }

return true;