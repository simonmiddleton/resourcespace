<?php
#
# update_empty_field_with_default.php
#
#
# Sets the current default field option on all resources without an existing value.
#

include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}
include "../../include/resource_functions.php";

set_time_limit(0);

# update a single field
$field=getvalescaped("field","");

if ($field=="") {exit("Specify field with ?field=");}
elseif(!in_array($field,$default_to_first_node_for_fields)) {exit("This field is not set to use a default option!");}

$fieldinfo=sql_query("select * from resource_type_field where ref='$field'");
$fieldinfo=$fieldinfo[0];
//echo "fielddata:";print_r($fieldinfo);echo"<br/>";

if($fieldinfo['type']!=3) {exit("This field is not a dropdown so a default value cannot be set!");}

# update only resources in specified collection
$collectionid=getvalescaped("col", "");

# Fetch node info for field
$nodes = get_nodes($field);
if(empty($nodes))
	{
	exit("This field does not have any options!");
	}
//echo "nodes:";print_r($nodes);echo"<br/>";
$default_node_value=$nodes[0]['name'];
	
if (getval("submit","")!="" && enforcePostRequest(false))
	{
	# we also need the node ids of all other options
	$node_refs=array();
	for($n=0;$n<count($nodes);$n++)
		{
		$node_refs[]=$nodes[$n]['ref'];
		}
	# make this a list
	$node_refs=implode(",",$node_refs);
	
	# get all resources without a value currently set for this field
	$refs=sql_array("select ref value from resource where ref>0 and ref not in (select resource from resource_node where node in (" . $node_refs . ") )" . ($fieldinfo['resource_type']!=='0' ? " and resource_type=" . $fieldinfo['resource_type'] : "" ) . " order by ref");
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
		$collectionname=sql_value("select name as value from collection where ref='$collectionid'",'');
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
