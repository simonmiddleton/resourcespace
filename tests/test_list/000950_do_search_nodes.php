<?php

include_once(__DIR__ . '/../../include/search_functions.php');

if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}


// --------------------------------------------------------------------------------

function resource_node_keyword_associated($resource,$resource_type_field,$keyword,$position)
    {
    sql_query("INSERT INTO `node`(`resource_type_field`,`name`) VALUES ({$resource_type_field},'{$keyword}')");
    $node_ref=sql_insert_id();
    sql_query("INSERT INTO resource_node(`resource`,`node`) VALUES ({$resource},{$node_ref})");
    sql_query("INSERT INTO `node_keyword`(`node`,`keyword`,`position`) SELECT {$node_ref},ref,{$position} FROM `keyword` WHERE `keyword`='{$keyword}'");
    }

// --------------------------------------------------------------------------------

// -------------- Both old resource_keyword and node_keyword lookups ------------

sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (950,'Node test one with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");
sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (951,'Node test two with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");

update_field(950,'title','Small goat');
update_field(951,'title','Central library containing many cartoons');

reindex_resource(950);
reindex_resource(951);

resource_node_keyword_associated(950,8,'small',0);
resource_node_keyword_associated(950,8,'goat',1);

resource_node_keyword_associated(951,8,'central',0);
resource_node_keyword_associated(951,8,'library',1);
resource_node_keyword_associated(951,8,'containing',2);
resource_node_keyword_associated(951,8,'many',3);
resource_node_keyword_associated(951,8,'books',4);

// -------------- Old resource_keyword lookup only ------------

sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (952,'Node test one with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");
sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (953,'Node test two with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");

update_field(952,'title','Billy goat');
update_field(953,'title','Mobile library containing many manuscripts');

reindex_resource(952);
reindex_resource(953);

sql_query("DELETE FROM `node_keyword` WHERE `keyword` IN (SELECT `ref` FROM `keyword` WHERE `keyword` IN ('billy','mobile','manuscripts'))");

// -------------- New node_keyword lookup only ------------

sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (954,'Node test one with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");
sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (955,'Node test two with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");

update_field(954,'title','Goat style beard');
update_field(955,'title','District library containing many documents');

resource_node_keyword_associated(954,8,'goat',0);
resource_node_keyword_associated(954,8,'style',1);
resource_node_keyword_associated(954,8,'beard',2);

resource_node_keyword_associated(955,8,'district',0);
resource_node_keyword_associated(955,8,'library',1);
resource_node_keyword_associated(955,8,'containing',2);
resource_node_keyword_associated(955,8,'many',3);
resource_node_keyword_associated(955,8,'documents',4);

sql_query("DELETE FROM `resource_keyword` WHERE `resource`=954 AND `keyword` IN (SELECT `ref` FROM `keyword` WHERE `keyword` IN ('goat','style','beard'))");
sql_query("DELETE FROM `resource_keyword` WHERE `resource`=955 AND `keyword` IN (SELECT `ref` FROM `keyword` WHERE `keyword` IN ('district','documents'))");

// -------------------- Useful SQL: ---------------

// new style keyword lookup:
// select * from resource_node left outer join node_keyword on resource_node.node=node_keyword.node left outer join keyword on node_keyword.keyword=keyword.ref where resource >= 950 and resource <=955

// traditional keyword lookup:
// select * from resource_keyword left outer join keyword on resource_keyword.keyword=keyword.ref where resource >= 950 and resource <=955


// search for 'goat' which will produce 3 results (both from keywords and nodes)
$results=do_search('goat');
if(count($results)!=3 || !isset($results[0]['ref']) || !isset($results[1]['ref']) || !isset($results[2]['ref']) ||
    (
    ($results[0]['ref']!=950 && $results[1]['ref']!=952 && $results[2]['ref']!=954) &&
    ($results[0]['ref']!=950 && $results[1]['ref']!=954 && $results[2]['ref']!=952) &&
    ($results[0]['ref']!=952 && $results[1]['ref']!=954 && $results[2]['ref']!=950) &&
    ($results[0]['ref']!=952 && $results[1]['ref']!=950 && $results[2]['ref']!=954) &&
    ($results[0]['ref']!=954 && $results[1]['ref']!=950 && $results[2]['ref']!=952) &&
    ($results[0]['ref']!=954 && $results[1]['ref']!=952 && $results[2]['ref']!=950)
    )
) return false;

// search for 'billy' which will produce 1 result (via resource_keyword)
$results=do_search('billy');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=952) return false;

// search for 'beard' which will produce 1 result (via resource_node->node_keyword)
$results=do_search('beard');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=954) return false;

// search for goat without 'billy' which will produce 2 results (omit via resource_keyword)
$results=do_search('goat -billy');
if(count($results)!=2 || !isset($results[0]['ref']) || !isset($results[1]['ref']) ||
    (
        ($results[0]['ref']!=950 && $results[1]['ref']!=954) &&
        ($results[0]['ref']!=954 && $results[1]['ref']!=950)
    )
) return false;

// search for goat without 'beard' which will produce 2 results (omit via resource_node->node_keyword)
$results=do_search('goat -beard');
if(count($results)!=2 || !isset($results[0]['ref']) || !isset($results[1]['ref']) ||
    (
        ($results[0]['ref']!=950 && $results[1]['ref']!=952) &&
        ($results[0]['ref']!=952 && $results[1]['ref']!=950)
    )
) return false;


// quoted search (via resource_keyword only)
$results=do_search('"containing many cartoons"');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=951) return false;

// quoted search (via resource_node->node_keyword only)
$results=do_search('"containing many documents"');
if(count($results)!=1 || !isset($results[0]['ref']) || $results[0]['ref']!=955) return false;

// quoted search (via both resource_keyword and resource_node->node_keyword)
$results=do_search('"containing many"');
if(count($results)!=3 || !isset($results[0]['ref']) || !isset($results[1]['ref']) || !isset($results[2]['ref']) ||
    (
        ($results[0]['ref']!=951 && $results[1]['ref']!=953 && $results[2]['ref']!=955) &&
        ($results[0]['ref']!=951 && $results[1]['ref']!=955 && $results[2]['ref']!=953) &&
        ($results[0]['ref']!=953 && $results[1]['ref']!=955 && $results[2]['ref']!=951) &&
        ($results[0]['ref']!=953 && $results[1]['ref']!=951 && $results[2]['ref']!=955) &&
        ($results[0]['ref']!=955 && $results[1]['ref']!=951 && $results[2]['ref']!=953) &&
        ($results[0]['ref']!=955 && $results[1]['ref']!=953 && $results[2]['ref']!=951)
    )
) return false;


return true;
