<?php
#
# update_empty_field_with_default.php
#
#
# Sets the current default field option on all resources without an existing value.
#

include "../../include/db.php";

include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}

set_time_limit(0);

# update a single field
$field=getval("field","");

if ($field=="") {exit("Specify field with ?field=");}
elseif(!in_array($field,$default_to_first_node_for_fields)) {exit("This field is not set to use a default option!");}

$fieldinfo=ps_query("SELECT * from resource_type_field where ref=?",array("i",$field));
$fieldinfo=$fieldinfo[0];

if($fieldinfo['type']!=3) {exit("This field is not a dropdown so a default value cannot be set!");}

# THIS IS NOT FULLY IMPLEMENTED - update only resources in specified collection
$collectionid=getval("col", "");
if ($collectionid != "") {exit("Update by collection not implemented!");}

# Fetch node info for field
$nodes = get_nodes($field);
if(empty($nodes))
	{
	exit("This field does not have any options!");
	}

$default_node_value=$nodes[0]['name'];
	
if (getval("submit","")!="" && enforcePostRequest(false))
	{
	# we also need the node ids of all other options
	$node_refs=array();
	for($n=0;$n<count($nodes);$n++)
		{
		$node_refs[]=$nodes[$n]['ref'];
		}
	
	# get all resources without a value currently set for this field
	$parameters=ps_param_fill($node_refs, "i");
	$query="SELECT ref value from resource 
			 WHERE ref>0 
			   AND ref not in (SELECT resource from resource_node WHERE node in (" . ps_param_insert(count($node_refs)) . ") )";

	if ($fieldinfo['resource_type']!=0)
		{
		$query.=" AND resource_type=?";
		$parameters=array_merge($parameters,array("i",$fieldinfo['resource_type'])); 
		}
	
	$query.=" ORDER BY ref";

	$refs=ps_array($query,$parameters);

	$r_count=count($refs);
	echo "There are " . $r_count . " resources to update.<br/>";
	foreach($refs as $ref)
		{
		echo "Updating resource $ref...";
		update_field($ref,$field,$default_node_value);
		# Write this edit to the log (including the diff) (unescaped is safe because the diff is processed later)
		resource_log($ref,'e',$field,"",'',$default_node_value);
		echo "complete<br/>";
		flush();ob_flush();
		}
	echo "Complete";
	}
else
	{
	$extratext="";
	if ($collectionid != "")
		{
		$collectionname=ps_value("select name as value from collection where ref=?",array("i",$collectionid), '');
		$extratext=" for collection '" . $collectionname .  "'";
		}
	?>
	<form method="post" action="update_empty_field_with_default.php">
        <?php generateFormToken("update_empty_field_with_default"); ?>
	<input type="hidden" name="field" value="<?php echo $field ?>">
	<input type="hidden" name="col" value="<?php echo $collectionid ?>">
	<input type="submit" name="submit" value="Update empty values with default '<?php echo $default_node_value?>' for field '<?php echo $fieldinfo["title"] . "'" . $extratext ?>">
	</form>
	<?php
	}	
?>
