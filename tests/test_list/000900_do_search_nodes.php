<?php

include_once(__DIR__ . '/../../include/search_functions.php');

if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// create a new resource with ref of 900
sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (900,'Node test',1) ON DUPLICATE KEY UPDATE ref=ref");
sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (901,'Node test second resource',1) ON DUPLICATE KEY UPDATE ref=ref");

// attach source:
sql_query("INSERT INTO `resource_node`(`resource`,`node`) values (900,247) ON DUPLICATE KEY UPDATE resource=resource");  // resource 900: "Digital Camera"
sql_query("INSERT INTO `resource_node`(`resource`,`node`) values (901,247) ON DUPLICATE KEY UPDATE resource=resource");  // resource 901: "Digital Camera"

// attach countries:
sql_query("INSERT INTO `resource_node`(`resource`,`node`) values (900,2) ON DUPLICATE KEY UPDATE resource=resource");  // resource 900: "Aland Islands"
sql_query("INSERT INTO `resource_node`(`resource`,`node`) values (900,130) ON DUPLICATE KEY UPDATE resource=resource");  // resource 900: "Macedonia - The Former Yugoslav Republic Of"

reindex_resource(900);
reindex_resource(901);

// straight search of ref
$results=do_search(900);
if(!isset($results[0]['ref']) || $results[0]['ref']!=900) return false;

// search for 'Aland Island' (should be just one)
$results=do_search('@@2');
if(!isset($results[0]['ref']) || $results[0]['ref']!=900) return false;

// search for everything (to get the count)
$results=do_search('');
$total=count($results);

// search for everything but 'Aland Island' (should be n-1)
$results=do_search('@@!2');

// there should be a difference of 1
if(count($results)!=$total-1) return false;

// search for 'Aland Island' OR 'Macedonia - The Former Yugoslav Republic Of' (should be just one)
$results=do_search('@@2@@130');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=900) return false;

// search for 'Aland Island' OR NOT 'Macedonia - The Former Yugoslav Republic Of' (should be just one - NOT within an OR is not supported at this time)
$results=do_search('@@2@@!130');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=900) return false;

// search for 'Aland Island' AND 'Macedonia - The Former Yugoslav Republic Of' (should be just one)
$results=do_search('@@2 @@130');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=900) return false;

// search for 'Aland Island' AND NOT 'Macedonia - The Former Yugoslav Republic Of' (should return nothing)
$results=do_search('@@2 @@!130');
if(!empty($results)) return false;

// search for 'Digital Camera' (should return both resources 900 and 901)
$results=do_search('@@247');
if(count($results)!=2 || !isset($results[0]['ref']) || !isset($results[1]['ref']) ||
    ($results[0]['ref']!=900 && $results[1]['ref']!=900) ||
    ($results[0]['ref']!=901 && $results[1]['ref']!=901)
) return false;

// search for 'Digital Camera' AND 'Aland Island' (should return the one resource, 900)
$results=do_search('@@247 @@130');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=900) return false;

// search for 'Digital Camera' AND NOT 'Aland Island' (should return the one resource, 901)
$results=do_search('@@247 @@!130');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=901) return false;

return true;