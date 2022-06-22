<?php
#
# Unindex_field.php
#
#
# Removes Indexes for a field
#

include "../../include/db.php";

include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}
include "../../include/image_processing.php";

set_time_limit(0);

# Unindex a single field
$field=getval("field","");
if ($field=="") {exit("Specify field with ?field=");}

# Unindex only resources in specified collection
$collectionid=getval("col", "");

# Fetch field info
$fieldinfo=ps_query("select * from resource_type_field where ref=?",array("i",$field));
$fieldinfo=$fieldinfo[0];

if (getval("submit","")!="" && enforcePostRequest(false))
	{
	echo "<pre>";
	
	$joinkeyword="";
	$joindata="";
	$conditionand = "";
	$parameters=array("i",$field);
	if ($collectionid != "")
			{
			$joinkeyword=" inner join collection_resource on collection_resource.resource=resource_keyword.resource "; 
			$joindata=" inner join collection_resource on collection_resource.resource=resource_data.resource "; 
			$conditionand = "and collection_resource.collection = ? ";
			$parameters=array_merge($parameters,array("i",$collectionid));
			}
	
	
	# Delete existing keywords index for this field
	ps_query("delete resource_keyword.* from resource_keyword $joinkeyword where resource_type_field=? $conditionand",$parameters);
	echo "Complete";
	}
else
	{
	$extratext="";
	if ($collectionid != "")
		{
		$collectionname=ps_value("select name as value from collection where ref=?",array("i",$collectionid),'');
		$extratext=" for collection '" . $collectionname .  "'";
		}
	?>
	<form method="post" action="unindex_field.php">
        <?php generateFormToken("Unindex_field"); ?>
	<input type="hidden" name="field" value="<?php echo $field ?>">
	<input type="hidden" name="col" value="<?php echo $collectionid ?>">
	<input type="submit" name="submit" value="Un-Index field '<?php echo $fieldinfo["title"] . "'" . $extratext ?>">
	</form>
	<?php
	}	
?>
