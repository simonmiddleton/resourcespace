<?php
#
# Reindex.php
#
#
# Reindexes the resource metadata. This should be unnecessary unless the resource_keyword table has been corrupted.
#

include "../../include/db.php";
if (!(PHP_SAPI == 'cli')) {include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}}
include_once "../../include/general.php";
include "../../include/resource_functions.php";
include "../../include/image_processing.php";

// Disable sql_logging
$mysql_log_transactions=false;

$sql = '';
if('' != getval('ref', ''))
    {
    $sql = "WHERE r.ref = '" . getvalescaped('ref', '', true) . "'";
    }

set_time_limit(0);
echo "<pre>";


$start = getvalescaped('start', '');
if(is_numeric($start))
    {
    $sql= "where r.ref>=" . $start;
	$end = getvalescaped('end', '');
	if(is_numeric($end))
		{
		$sql.= " and r.ref<=" . $end;
		}
    }
	
$start = getvalescaped('start', '');
if(is_numeric($start))
    {
    $sql= "where r.ref>=" . $start;
	$end = getvalescaped('end', '');
	if(is_numeric($end))
		{
		$sql.= " and r.ref<=" . $end;
		}
    }
	
$resources = sql_query("SELECT r.ref, u.username, u.fullname FROM resource AS r LEFT OUTER JOIN user AS u ON r.created_by = u.ref {$sql} ORDER BY ref");


$time_start = microtime(true);

for($n = 0; $n < count($resources); $n++)
    {
    $ref = $resources[$n]['ref'];

    reindex_resource($ref);

    $words = sql_value("SELECT count(*) `value` FROM resource_keyword WHERE resource = '{$ref}'", 0);

    echo "Done {$ref} ({$n}/" . count($resources) . ") - $words words<br />\n";


    @flush();
    @ob_flush();
    }

    
// Reindex nodes
$nodes=sql_query("select n.ref, n.name, n.resource_type_field, f.partial_index from node n join resource_type_field f on n.resource_type_field=f.ref order by resource_type_field;");
$count=count($nodes);
for($n=0;$n<$count;$n++)
		{
		// Populate node_keyword table
        remove_all_node_keyword_mappings($nodes[$n]['ref']);
        add_node_keyword_mappings($nodes[$n], $nodes[$n]["partial_index"]);
        }


$time_end = microtime(true);
$time     = $time_end - $time_start;

echo "Reindex took $time seconds\n";
