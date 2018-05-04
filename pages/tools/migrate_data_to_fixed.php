<?php
include "../../include/db.php";
include_once "../../include/general.php";
include_once "../../include/resource_functions.php";
include_once "../../include/search_functions.php";
include_once "../../include/authenticate.php";
if(!checkperm("a")){exit("Access denied");}

$migrate_field = getvalescaped("field",0,true);
if($migrate_field == 0){$errortext=("No field specified");}
$splitvalue = getvalescaped("splitchar","");
$modal = (getval("modal","")=="true");
$dryrun = getval("dryrun","")!="";
$deletedata = getval("deletedata","")=="true";

$backurl=getvalescaped("backurl","");
if($backurl=="")
    {
    $backurl=$baseurl . "/pages/admin/admin_resource_type_field_edit.php?ref=" . $migrate_field;
    }

include_once "../../include/header.php";

if (isset($error_text)) { ?><div class="PageInformal"><?php echo $error_text?></div><?php }

?>
<div class="BasicsBox">
	<p>    
	<a href="<?php echo $backurl ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"]?></a>
	</p>
	<h1><?php echo $lang["admin_resource_type_field_migrate_data"] ?></h1>

	<form method="post" action="<?php echo $baseurl_short ?>pages/tools/migrate_data_to_fixed.php" >
        <?php generateFormToken("migrate_data_to_fixed"); ?>
	<div class="Question" >
		<label for="field" ><?php echo $lang["field"] ?></label>
		<input type="number" name="field" value="<?php echo $migrate_field ?>">
	</div>
	<div class="Question" >
		<label for="splitchar" ><?php echo $lang["admin_resource_type_field_migrate_separator"] ?></label>
		<input type="text" name="splitchar" value=",">
	</div>
	<div class="Question" >
		<label for="dryrun" ><?php echo $lang["admin_resource_type_field_migrate_dry_run"] ?></label>
		<input type="checkbox" name="dryrun" value="true">
	</div>
	<div class="Question" >
		<label for="deletedata" ><?php echo $lang["admin_resource_type_field_migrate_delete_data"] ?></label>
		<input type="checkbox" name="deletedata" value="true">
	</div>
	<div class="Question" >
		<input type="hidden" name="submit" value="true">
		<input type="submit" name="submit" value="<?php echo $lang["action-submit-button-label"] ?>">
	</div>
	</form>
	<textarea id="migration_log" rows=20 cols=100 style="width: 100%; border: solid 1px;" ></textarea>
        
</div>
<?php


include_once "../../include/footer.php";

if(getval("submit","") != "" && enforcePostRequest(false))
    {
    
    $valid_fields = sql_array("SELECT ref value FROM resource_type_field WHERE type IN ('" . implode("','", $FIXED_LIST_FIELD_TYPES) . "')");
    
    if(!in_array($migrate_field,$valid_fields))
        {
        ?>
		<script>
		jQuery('#migration_log').append('Invalid field specified. Only fixed type field types can be specified');
		</script>
		<?php
		exit();
        }
    
	?>
	<script>
	<?php
    $resdata = sql_query(
        "SELECT resource,
                `value` 
           FROM resource_data 
          WHERE resource_type_field = '{$migrate_field}'
            AND `value` IS NOT NULL
            AND `value` != ''"
    );
    
    // Get all existing nodes
    $existing_nodes = get_nodes($migrate_field, NULL, TRUE);
	if($dryrun)
		{
		// Set start value for dummy node refs 
		$newnoderef = (count($existing_nodes) > 0) ? max(array_column($existing_nodes,"ref")) + 1: 0;
		}
		
    foreach($resdata as $resdata_row)
        {
        // No need to process any further if no data is found set for this resource
        if(trim($resdata_row['value']) == '')
            {
            continue;
            }

        $nodes_to_add = array();
        $resource = $resdata_row["resource"];
		$log = array();
        $log[] = "Checking data for resource id #" . $resource . "";

        if($splitvalue != "")
            {
            $data_values = explode($splitvalue,$resdata_row["value"]);
            }
        else
            {
            $data_values = array($resdata_row["value"]);   
            }
            
        foreach($data_values as $data_value)
            {
            // Skip if this value is empty (e.g if users left a separator at the end of the value by mistake)
            if(trim($data_value) == '')
                {
                continue;
                }

            $log[] = "- value: " . $data_value . "";
       
            $nodeidx = array_search($data_value,array_column($existing_nodes,"name"));

            if($nodeidx !== false)
                {
                $log[] = " - found matching node. ref:" . $existing_nodes[$nodeidx]["ref"] ;
                $nodes_to_add[] = $existing_nodes[$nodeidx]["ref"];      
                }
            else
                {
                if(!$dryrun)
					{
					$newnode = set_node(NULL, $migrate_field, escape_check($data_value), NULL, '',true);
			        $log[] = " - Created new node. ref:" . $newnode ;
					$nodes_to_add[] = $newnode;
					$newnodecounter = count($existing_nodes);
					$existing_nodes[$newnodecounter]["ref"] = $newnode;
					$existing_nodes[$newnodecounter]["name"] = $data_value;
					}
				else 
					{
					$newnode = $newnoderef;
					$newnodecounter = count($existing_nodes);
					$existing_nodes[$newnodecounter]["ref"] = $newnoderef;
					$existing_nodes[$newnodecounter]["name"] = $data_value;
					$newnoderef++;							
					}
                }
            }            
        
        if(count($nodes_to_add) > 0)
            {
            $log[] = "Adding nodes: " . implode(",", $nodes_to_add) . "";
			if(!$dryrun)
				{
				add_resource_nodes($resource,$nodes_to_add);
				}
            }
			                                            
		foreach ($log as $logtext)
			{
			?>
			jQuery('#migration_log').append("<?php echo ($dryrun?"TESTING: ":"") . str_pad($logtext,2048); ?>");
			<?php
			}
			?>
		<?php
		ob_flush();flush();
        }
	
	if($deletedata)
		{?>
		jQuery('#migration_log').append("\n<?php echo ($dryrun?"TESTING: ":"") ?>DELETING EXISTING DATA\n");
		<?php
		if(!$dryrun)
			{
			sql_query("delete from resource_data where resource_type_field='" . $migrate_field . "'");
			sql_query("delete from resource_keyword where where resource_type_field='" . $migrate_field . "'");
			}
		}

	?>
	jQuery('#migration_log').append('\nCOMPLETED');
	</script>
	</div>
	<?php
    }
