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
update_field(951,'title','Central library');

reindex_resource(950);
reindex_resource(951);

resource_node_keyword_associated(950,8,'small',0);
resource_node_keyword_associated(950,8,'goat',1);

resource_node_keyword_associated(951,8,'central',0);
resource_node_keyword_associated(952,8,'library',1);

// -------------- Old resource_keyword lookup only ------------

sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (952,'Node test one with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");
sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (953,'Node test two with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");

update_field(952,'title','Billy goat');
update_field(953,'title','Mobile library');

reindex_resource(952);
reindex_resource(953);

sql_query("DELETE FROM `node_keyword` WHERE `keyword` IN (SELECT `ref` FROM `keyword` WHERE `keyword` IN ('billy','mobile'))");

// -------------- New node_keyword lookup only ------------

sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (954,'Node test one with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");
sql_query("INSERT INTO `resource`(`ref`,`title`,`resource_type`) values (955,'Node test two with resource_keyword and node_keyword',1) ON DUPLICATE KEY UPDATE ref=ref");

update_field(954,'title','Goat style beard');
update_field(955,'title','Academia library');

resource_node_keyword_associated(954,8,'goat',0);
resource_node_keyword_associated(954,8,'style',1);
resource_node_keyword_associated(954,8,'beard',2);

resource_node_keyword_associated(955,8,'academia',0);
resource_node_keyword_associated(955,8,'library',1);

sql_query("DELETE FROM `resource_keyword` WHERE `resource`=954 AND `keyword` IN (SELECT `ref` FROM `keyword` WHERE `keyword` IN ('goat','style','beard'))");
sql_query("DELETE FROM `resource_keyword` WHERE `resource`=955 AND `keyword` IN (SELECT `ref` FROM `keyword` WHERE `keyword` IN ('academia','library'))");

// -------------------- Useful SQL: ---------------

// new style keyword lookup:
// select * from resource_node left outer join node_keyword on resource_node.node=node_keyword.node left outer join keyword on node_keyword.keyword=keyword.ref where resource >= 950 and resource <=955

// traditional keyword lookup:
// select * from resource_keyword left outer join keyword on resource_keyword.keyword=keyword.ref where resource >= 950 and resource <=955

$results=do_search('goat');

// TODO: *** under development ***

echo "results:" . PHP_EOL;
print_r($results);




return true;

