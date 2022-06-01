<?php

include "../../include/db.php";
if (PHP_SAPI != 'cli')
    {
    exit('Access denied - Command line only!');
    }

set_time_limit(0);
$debug_log=false; // This would slow things down too much
$global_start_time = microtime(true);
$tomigrate = array_diff(array_keys($field_types),array_merge($FIXED_LIST_FIELD_TYPES,[FIELD_TYPE_DATE_RANGE]));

$resource_type_fields=ps_query('SELECT * FROM `resource_type_field` WHERE `type` IN (' . ps_param_insert(count($tomigrate)) . ') ORDER BY `ref`',ps_param_fill($tomigrate,"i"));

// Number of resource_data rows to migrate in each batch to avoid out of memory errors
$chunksize = 2000;
foreach($resource_type_fields as $resource_type_field)
    {
    $fref = $resource_type_field['ref'];
    $fname = $resource_type_field['name'];
    $status = "Migrating resource_data for field #" . $fref . " (" . $fname . ")";
    $allfieldnodes= get_nodes($fref,NULL);
    $nodecache = [];
    $totalrows = ps_value("SELECT count(*) AS value FROM `resource_data` WHERE resource_type_field = ?",["i",$fref],0);
    $out = " (" . $totalrows . " rows found)";
    logScript(str_pad($status . $out,100,' '));
    ob_flush();
    $chunkstart = 0;
    $processed = 0;
    while($chunkstart < $totalrows)
        {
        $rows = ps_query("SELECT `resource`,`value` FROM `resource_data` WHERE resource_type_field = ? ORDER BY resource ASC LIMIT " . $chunkstart . ", " . $chunksize . "",["i",$fref]);

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
                    $exnodeidx = array_search($rowdata["value"],$allfieldnodes);
                    if($exnodeidx !== false)
                        {
                        $newnode = $allfieldnodes[$exnodeidx];
                        }
                    else
                        {
                        $newnode = set_node(NULL,$fref,$rowdata["value"],NULL,NULL);
                        }
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
                }
            $processed++;
            }
        $chunkstart = $chunkstart + $chunksize;
        $out = " - processed $processed / $totalrows records";
        logScript(str_pad($out,100,' '));
        ob_flush();
        }

    $out = sprintf(" - Completed $totalrows records in %01.2f seconds.\n", microtime(true) - $global_start_time);
    logScript(str_pad($out,100,' '));
    }
echo "Finished<br/>";
