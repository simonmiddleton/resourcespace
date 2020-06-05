<?php

if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// Check search for field specific free text search e.g. '"title:launch party"'

$resourcea=create_resource(1,0);
$resourceb=create_resource(1,0);
$resourcec=create_resource(1,0);

// Add plain text to resources
update_field($resourcea,8,"Launch party");
update_field($resourceb,8,"Book signing");
update_field($resourcec,8,"Ship launch");


// Do field specific search for 'launch party' (should return resource a)
$results=do_search('"title:launch party"');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=$resourcea) return false;


// Do field specific search for 'launch' (should return resources a and c)
$results=do_search('"title:launch"');
if(count($results)!=2 || !isset($results[0]['ref']) || !isset($results[1]['ref']) ||
    ($results[0]['ref']!=$resourcea && $results[1]['ref']!=$resourcea) ||
    ($results[0]['ref']!=$resourcec && $results[1]['ref']!=$resourcec)
) return false;


// Create and add a node with same name to resource b 
$launchnode = set_node(NULL, 74, "launch",'',1000);
add_resource_nodes($resourceb,array($launchnode));

// This shouldn't be return resource b
$results=do_search('"title:launch"');
if(count($results)!=2 || !isset($results[0]['ref']) || !isset($results[1]['ref']) ||
    ($results[0]['ref']!=$resourcea && $results[1]['ref']!=$resourcea) ||
    ($results[0]['ref']!=$resourcec && $results[1]['ref']!=$resourcec)
) return false;

// Alternative keywords search:
$multi_keywords_results = do_search("title:Book;Ship");
if(
    !is_array($multi_keywords_results)
    || count($multi_keywords_results) < 2
    || $multi_keywords_results[1]["ref"] != $resourceb
    || $multi_keywords_results[0]["ref"] != $resourcec
)
    {
    return false;
    }

return true;