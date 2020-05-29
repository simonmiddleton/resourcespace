<?php

if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// Check wildcard search e.g. 'sam*' and 'super*'

$resourcea=create_resource(1,0);
$resourceb=create_resource(1,0);
$resourcec=create_resource(1,0);

// Add new nodes to field, use dummy countries in case dbstruct changes
$sambalandnode = set_node(NULL, 3, "Sambaland",'',1000);
$superlandnode = set_node(NULL, 3, "Superland",'',1000);

// Add sambalandnode node to resource a
add_resource_nodes($resourcea,array($sambalandnode));
// Add superlandnode node to resource b
add_resource_nodes($resourceb,array($superlandnode));

// Do search for 'sam*' (should return resource a)
$results=do_search('sam*');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=$resourcea) return false;

// Do search for 'super*' (should return resource b)
$results=do_search('super*');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=$resourceb) return false;

// Add plain text to caption field for resource c
update_field($resourcec,8,"Supermarine");

// Do search for 'super*' again (now should return both resources b and c)
$results=do_search('super*');
if(count($results)!=2 || !isset($results[0]['ref']) || !isset($results[1]['ref']) ||
    ($results[0]['ref']!=$resourceb && $results[1]['ref']!=$resourceb) ||
    ($results[0]['ref']!=$resourcec && $results[1]['ref']!=$resourcec)
) return false;

// Do search for 'sam* super*'(should return no resources and get a suggestion back)
$results=do_search('sam* super*');
if(is_array($results)) {return false;}

// Add text to caption field for resources a and b and a node to resource c
update_field($resourcea,8,"Supercilious");
update_field($resourceb,8,"Samuel Taylor Coleridge");
$sambucalandnode = set_node(NULL, 3, "Sambucaland",'',1000);
add_resource_nodes($resourcec,array($sambucalandnode));

// Do search for 'sam* super*' again(should return resources a, b and c)
$results=do_search('sam* super*');
if(count($results)!=3 || !isset($results[0]['ref']) || !isset($results[1]['ref']) || !isset($results[2]['ref']) ||
    ($results[0]['ref']!=$resourcea && $results[1]['ref']!=$resourcea && $results[2]['ref']!=$resourcea) ||
    ($results[0]['ref']!=$resourceb && $results[1]['ref']!=$resourceb && $results[2]['ref']!=$resourceb)||
    ($results[0]['ref']!=$resourcec && $results[1]['ref']!=$resourcec && $results[2]['ref']!=$resourcec)
) return false;


return true;
