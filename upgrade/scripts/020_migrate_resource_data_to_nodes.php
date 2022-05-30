<?php

// Script to migrate all non-fixed list data to nodes

$tomigrate = array_diff(array_keys($field_types),array_merge($FIXED_LIST_FIELD_TYPES,[FIELD_TYPE_DATE_RANGE]));
$startfield = get_sysvar("node_migrated_data_field",0);

$resource_type_fields=ps_query('SELECT * FROM `resource_type_field` WHERE `type` IN (' . ps_param_insert(count($tomigrate)) . ') AND ref > ? ORDER BY `ref`',array_merge(ps_param_fill($tomigrate,"i"),["i",$startfield]));

// Number of resource_data rows to migrate in each batch to avoid out of memory errors
$chunksize = 5000;
foreach($resource_type_fields as $resource_type_field)
    {
    $fref = $resource_type_field['ref'];
    $fname = $resource_type_field['name'];
    $status = "Migrating resource_data for field #" . $fref . " (" . $fname . ")";
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT,$status);
    $nodecache = [];
    $totalrows = ps_value("SELECT count(*) AS value FROM `resource_data` WHERE resource_type_field = ?",["i",$fref],0);
    $out = " (" . $totalrows . " rows found)";
    logScript(str_pad($status . $out,100,' '));
    ob_flush();
    
    $chunkstart = 0;
    $processed = 0;
    while($chunkstart < $totalrows)
        {
        $rows = ps_query("SELECT `resource`,`value` FROM `resource_data` WHERE resource_type_field = ? ORDER BY resource ASC LIMIT " . $chunkstart . ", " . ($chunksize) . "",["i",$fref]);

        // Get current nodes for this batch of resources
        $batchresources = array_column($rows,"resource");
        $resnodes = ps_query("SELECT rn.resource, rn.node FROM resource_node rn LEFT JOIN node n ON n.ref=rn.node WHERE rn.resource IN (" . ps_param_insert(count($batchresources)). ") AND n.resource_type_field = ?",array_merge(ps_param_fill($batchresources,"i"),["i",$fref]));
        
        $resnodearr = [];
        foreach($resnodes as $resnode)
            {
            $resnodearr[$resnode["resource"]][] = $resnode["node"];
            }

        foreach($rows as $rowdata)
            {
            if(trim($rowdata["value"]) != "")
                {
                if(isset($nodecache[$rowdata["value"]]))
                    {
                    $newnode = $nodecache[$rowdata["value"]];
                    }
                else
                    {
                    $newnode = set_node(NULL,$fref,$rowdata["value"],NULL,NULL);
                    $nodecache[$rowdata["value"]] = $newnode;
                    }
                if(!isset($resnodearr[$rowdata["resource"]]) || !in_array($newnode,$resnodearr[$rowdata["resource"]]))
                    {
                    logScript("Updating resource " . $rowdata["resource"] . ", field #" . $fref . " (" . $fname . ") with node " . $newnode . " (" . mb_strcut($rowdata["value"],0,30) . "...)");
                    add_resource_nodes($rowdata["resource"],[$newnode]);
                    }
                else
                    {
                    logScript("Skipping, correct node already set for resource - " . $rowdata["resource"] . ", field #" . $fref . " (" . $fname . ") with node " . $newnode . " (" . mb_strcut($rowdata["value"],0,30) . "...)");
                    }
                // Remove any extra nodes that may be present
                if(isset($resnodearr[$rowdata["resource"]]))
                    {
                    $nodestoremove = array_diff($resnodearr[$rowdata["resource"]],[$newnode]);
                    delete_resource_nodes($rowdata["resource"],$nodestoremove);
                    }
                }
            $processed++;        
            }

        $chunkstart = $chunkstart + $chunksize;
        $out = " - processed $processed / $totalrows records";
        logScript(str_pad($out,100,' '));
        set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT,$status . $out);
        ob_flush();
        }
    $out = " - Completed $totalrows records";
    logScript(str_pad($out,100,' '));
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT,$status . $out);
    set_sysvar("node_migrated_data_field",$fref);
    ob_flush();
    }


// Migrate any annotations (plugin) as these are not field linked
// The plugin may only be enabled for some usergroups so can't just check $plugins array
$alltables = ps_query("SHOW TABLES");
$annotate_enabled = in_array("annotate_notes",array_column($alltables,"Tables_in_" . $mysql_db));
if($annotate_enabled)
    {
    logScript("Annotate plugin enabled, migrating to use new nodes");
    $count = 0;

    $annotate_config = get_plugin_config("annotate");

    logScript( "Checking if metadata field set: " . ($annotate_config["annotate_resource_type_field"] ?? "Not set"));
    if(!isset($annotate_config["annotate_resource_type_field"]) || $annotate_config["annotate_resource_type_field"] === 0 )
        {
        // Create a new field to hold annotations
        $annotate_field = create_resource_type_field("Annotations plugin",0,FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,"annotateplugin",true);
        ps_query("UPDATE resource_type_field SET display_field=0, advanced_search=0,hide_when_uploading=1 WHERE ref = ?",["i",$annotate_field]);
        // Set plugin to use this field
        $annotate_config["annotate_resource_type_field"] = $annotate_field;
        set_plugin_config("annotate",$annotate_config);
        logScript("Set new annotation field " . $annotate_field);
        }
    else
        {
        $annotate_field = $annotate_config["annotate_resource_type_field"];
        }
    // Get existing annotations
    $current_annotations = ps_query("SELECT ref, note, note_id, node FROM annotate_notes");
    foreach($current_annotations as $annotation)
        {
        logScript("Found annotation for resource  " . $annotation["ref"] . ", node: " . $annotation["node"]);
        if((int)$annotation["node"] == 0)
            {
            // No node set, create a new one
            logScript("Migrating annotation for resource  " . $annotation["ref"] . ", note: " . $annotation["note"]);
            $node = set_node(NULL,$annotate_field,$annotation["note"],NULL,10);
            ps_query("UPDATE annotate_notes SET node = ? WHERE ref= ?",["i",$node,"i",$annotation["ref"]]);
            // Add nodes so will be searchable
            add_resource_nodes($ref,[$node], true,true);
            $count++;
            }
        }
    logScript("Completed " . $count . " annotations");
    }
echo "Finished<br/>";
