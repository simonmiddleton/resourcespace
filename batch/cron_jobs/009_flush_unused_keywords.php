<?php
include_once __DIR__ . "/../../include/db.php";

// After migrating to nodes it is prefereable to delete resource_keyword rows for fixed list data
// as this is now stored in normalised form in resource_node and node_keyword

$count_query="select count(*) value from resource_keyword where resource_type_field in (select ref from resource_type_field where type in ('" . join("','",$FIXED_LIST_FIELD_TYPES) . "')) ";
$c=sql_value($count_query,0);

while ($c>0)
	{
	sql_query("delete from resource_keyword where resource_type_field in (select ref from resource_type_field where type in ('" . join("','",$FIXED_LIST_FIELD_TYPES) . "')) limit 1000");
	$c=sql_value($count_query,0);
	echo ($c . " rows remaining to delete");
	}
