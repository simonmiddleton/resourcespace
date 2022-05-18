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

