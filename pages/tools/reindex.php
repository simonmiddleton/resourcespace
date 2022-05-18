<?php
#
# Reindex.php
#
# Reindexes all nodes. This should be unnecessary unless the node_keyword table has been corrupted.
#

include "../../include/db.php";

if (!(PHP_SAPI == 'cli'))
	{
	include "../../include/authenticate.php";
	if (!checkperm("a")) {exit("Permission denied");}
	$field = getvalescaped('field', 0, true);
	}
elseif(isset($argv[1]) && is_int_loose($argv[1]))
	{
    $field= $argv[1];
	}
	
include_once "../../include/image_processing.php";

// Disable sql_logging
$mysql_log_transactions=false;

$sql = '';

set_time_limit(0);
echo "<pre>" . PHP_EOL;
$time_start = microtime(true);

// Reindex nodes
// node query
$query = "SELECT n.ref, n.name, n.resource_type_field, f.partial_index FROM node n JOIN resource_type_field f ON n.resource_type_field=f.ref";
$params = [];
if(isset($field))
    {
    $query .= " WHERE n.resource_type_field = ?";
    $params[] = "i";
    $params[] = $field;
    }	
$query .= " ORDER BY resource_type_field;";

$nodes=ps_query($query,$params);
$count=count($nodes);
for($n=0;$n<$count;$n++)
    {
    // Populate node_keyword table
    remove_all_node_keyword_mappings($nodes[$n]['ref']);
    add_node_keyword_mappings($nodes[$n], $nodes[$n]["partial_index"]);
    }

$time_end = microtime(true);
$time     = $time_end - $time_start;

echo "Reindex took $time seconds" . PHP_EOL;
