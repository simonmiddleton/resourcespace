<?php

// Note: It is safe to run this script at any time as it will work on differential data if migration interrupted


// ---------------------------------------------------------------------------------------------------------------------
// Step 1.  Convert any missing fixed field type options to nodes (where not already deprecated)
// ---------------------------------------------------------------------------------------------------------------------

// IMPORTANT! - Uncomment this line if you want to force migration of fixed field values
// sql_query("update resource_type_field set options=replace(options,'!deprecated,','')");

$tomigrate = array_diff($field_types,array_merge($FIXED_LIST_FIELD_TYPES,[FIELD_TYPE_DATE_RANGE]));
                                
$resource_type_fields=ps_query('SELECT * FROM `resource_type_field` WHERE `type` IN (' . ps_param_insert(count($tomigrate)) . ') ORDER BY `ref`',ps_param_fill($tomigrate,"i"));

// Number of resource_data rows to migrate in each batch to avoid out of memory errors
$chunksize = 5000;
foreach($resource_type_fields as $resource_type_field)
    {
    $fref = $resource_type_field['ref'];
    $fname = $resource_type_field['name'];
    $out="Migrating resource_data {$fref}:{$fname}";
    echo $out . "\n";;
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT,$out);

    $totalrows = ps_value("SELECT count(*) AS value FROM `resource_data` WHERE resource_type_field = ?",["i",$fref],0);
    $out.=' (' . $totalrows . ' rows found)';
    echo str_pad($out,100,' ') . "\n";
    ob_flush();
    
    $chunkstart = 0;
    while($chunkstart < $totalrows)
        {
        $rows = ps_query("SELECT `resource`,`value` FROM `resource_data` WHERE resource_type_field = ? ORDER BY resource ASC LIMIT " . $chunkstart . ", " . $chunksize . "",["i",$fref]);

        foreach($rows as $rowdata)
            {           
            $newnode = set_node(NULL,$fref,$rowdata["value"],NULL,NULL);
            echo "Updating resource " . $rowdata["resource"] . " with node " . $newnode;
            add_resource_nodes($rowdata["resource"],[$newnode]);
            }

        $chunkstart = $chunkstart + $chunksize +1;
        $out .= " - Completed $chunkstart / $totalrows records";
        echo str_pad($out,100,' ') . "\n";
        ob_flush();
        set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT,$out);
        }    
    ob_flush();
    }


// Migrate any annotations (plugin) as these are not field linked
// The plugin may only be enabled for some usergroups so can't just check $plugins array
$alltables = ps_query("SHOW TABLES");
$annotate_enabled = in_array("annotate_notes",array_column($alltables,"Tables_in_dev"));
if($annotate_enabled)
    {
    echo "Annotate plugin enabled, migrating to use new nodes<br/>";
    $count = 0;
    
    $annotate_config = get_plugin_config("annotate");
    echo "Checking if metadata field set: " . ($annotate_config["annotate_resource_type_field"] ?? "Not set") . "<br/>";
    if(!isset($annotate_config["annotate_resource_type_field"]) || $annotate_config["annotate_resource_type_field"] === 0 )
        {
        // Create a new field to hold annotations
        $annotate_field = create_resource_type_field("Annotations plugin",0,FIELD_TYPE_TEXT_BOX_SINGLE_LINE,"annotateplugin",true);
        ps_query("UPDATE SET display_field=0, advanced_search=0,hide_when_uploading=1 WHERE ref = ?",["i",$annotate_field]);
        // Set plugin to use this field
        $annotate_config["annotate_resource_type_field"] = $annotate_field;            
        set_plugin_config("annotate",$annotate_config);
        echo "Set new annotation field $annotate_field<br/>";
        }
    else
        {
        $annotate_field = $annotate_config["annotate_resource_type_field"];
        }
    // Get existing annotations
    $current_annotations = ps_query("SELECT ref, note, note_id, node FROM annotate_notes");
    foreach($current_annotations as $annotation)
        {
        echo "Found annotation for resource  " . $annotation["ref"] . ", node: " . $annotation["node"] . "<br/>";
        if((int)$annotation["node"] == 0)
            {
            // No node set, create a new one
            echo "Migrating annotation for resource  " . $annotation["ref"] . ", note: " . $annotation["note"] . "<br/>";
            $node = set_node(NULL,$annotate_field,$annotation["note"],NULL,10);
            ps_query("UPDATE annotate_notes SET node = ? WHERE ref= ?",["i",$node,"i",$annotation["ref"]]);
            // Add nodes so will be searchable
            add_resource_nodes($ref,[$node], true,true);
            $count++;
            }
        }
    echo "Completed " . $count . " annotations<br/>";
    }
echo "Finished<br/>";
