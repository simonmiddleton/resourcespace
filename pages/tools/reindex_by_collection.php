<?php
#
# Reindex_by_collection.php
#
#
# Reindexes the resource metadata. This should be unnecessary unless the resource_keyword table has been corrupted.
# This script allows administrators to target resources in a particular collection for reindexing.
#

include "../../include/db.php";

include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}
include "../../include/image_processing.php";

$sql="";
$collection = getval("collection","");

if (!(is_numeric($collection) and $collection > 0)){
	echo "Error: Collection not provided.";
	exit;
}


set_time_limit(60*60*5);
echo "<pre>";

$resources=ps_query("select r.ref,u.username,u.fullname from 
collection_resource left join resource r on collection_resource.resource = r.ref 
left outer join user u on r.created_by=u.ref where collection_resource.collection = ? order by ref", ['i', $collection]);

for ($n=0;$n<count($resources);$n++)
	{
	$ref=$resources[$n]["ref"];
	reindex_resource($ref);
	
	$words=ps_value("select count(*) value from resource_keyword where resource=?",array("i",$ref), 0);
	echo "Done $ref ($n/" . count($resources) . ") - $words words<br />\n";
	}
?>
