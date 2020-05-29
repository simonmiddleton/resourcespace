<?php
#
# Reindex_field.php
#
#
# Reindexes the resource metadata for a single field
#

include "../../include/db.php";

if (!(PHP_SAPI == 'cli')) {include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}}
include "../../include/image_processing.php";

set_time_limit(0);
$reindex_chunk_size=100; // Number of resources to reindex in each batch to prevent wiping out the index.

# Reindex a single field
$field=getvalescaped("field","");
if ($field=="") {exit("Specify field with ?field=");}

# Reindex only resources in specified collection
$collectionid=getvalescaped("col", "");

# Start reindex from a specific resource ID 
$startid=getvalescaped("startid", "");

# Fetch field info
$fieldinfo=sql_query("select * from resource_type_field where ref='$field'");$fieldinfo=$fieldinfo[0];

if (in_array($fieldinfo['type'], $FIXED_LIST_FIELD_TYPES))
    {
    // Always reindex nodes for these field types
    $nodes=sql_query("select n.ref, n.name, n.resource_type_field, f.partial_index from resource_type_field f LEFT JOIN node n on n.resource_type_field=f.ref WHERE f.ref = " . $field . ";");
    $count=count($nodes);
    for($n=0;$n<$count;$n++)
            {
            // Populate node_keyword table
            remove_all_node_keyword_mappings($nodes[$n]['ref']);
            add_node_keyword_mappings($nodes[$n], $nodes[$n]["partial_index"]);
            }
    
	exit("Reindex complete");
    }

if (!in_array($fieldinfo['type'], $FIXED_LIST_FIELD_TYPES) && !$fieldinfo["keywords_index"]) {exit("Field is not set to be indexed.");}

if (getval("submit","")!="" && enforcePostRequest(false))
	{
	echo "<pre>";
	$resourcecount = 0;
	$todo = $reindex_chunk_size;
	while($todo>0)
		{
		if ($collectionid != "")
			{
			$resources=sql_array("select resource value from collection_resource where collection_resource.collection = '" . $collectionid . "' order by resource asc limit " . $resourcecount . "," . $reindex_chunk_size);
			}
		else
			{
			$resources=sql_array("select ref value from resource where ref >='" . $startid . "' order by ref asc limit " . $resourcecount . "," . $reindex_chunk_size);
			}
		$todo=count($resources);
		if($todo>0)
			{
			# Delete existing keywords index for this field
			sql_query("delete from resource_keyword where resource in (" . implode(",",$resources) . ") and resource_type_field='$field'");
			
			# Index data
			$data=sql_query("select * from resource_data rd where resource in (" . implode(",",$resources) . ") and resource_type_field='$field' and length(rd.value)>0 and rd.value is not null order by rd.resource asc");
			$n=0;
			$total=count($data);
			
			db_begin_transaction("reindex_field");

			foreach ($data as $row)
				{
				$n++;
				$ref=$row["resource"];
				$value=$row["value"];
							
				# Date field? These need indexing differently.
				$is_date=($fieldinfo["type"]==4 || $fieldinfo["type"]==6);
				
				$is_html=($fieldinfo["type"]==8);	
				
				# function add_keyword_mappings($ref,$string,$resource_type_field,$partial_index=false,$is_date=false)		
				add_keyword_mappings($ref,i18n_get_indexable($value),$field,$fieldinfo["partial_index"],$is_date,'','',$is_html);		
			
				hook("reindexfieldtooladditional","",array($ref,$value,$fieldinfo));
				
				echo "Done $ref - " . htmlspecialchars(substr($value,0,50)) . "... ($n/$total)\n";
				
				if (($n / 20 == floor($n/20)) || $n==$total) # Scroll down every now and again, and at the end.
					{
					?><script>window.scroll(0,document.height);</script><?php
					}
				flush();
				}

			db_end_transaction("reindex_field");
			
			$resourcecount = $resourcecount + $reindex_chunk_size;
			}
	
		}

	echo "Reindex complete\n\n\n";
	}
else
	{
	$extratext="";
	if ($collectionid != "")
		{
		$collectionname=sql_value("select name as value from collection where ref='$collectionid'",'');
		$extratext=" for collection '" . $collectionname .  "'";
		}
	if ($startid != "")
		{
		$extratext=" for resource ID #" . $startid .  " and above";
		}
	?>
	<form method="post" action="reindex_field.php?field=<?php echo $field ?>">
        <?php generateFormToken("reindex_field"); ?>
	<input type="hidden" name="col" value="<?php echo $collectionid ?>">
	<input type="hidden" name="startid" value="<?php echo $startid ?>">
	<input type="submit" name="submit" value="Reindex field '<?php echo $fieldinfo["title"] . "'" . $extratext ?>">
	</form>
	<?php
	}	
?>
