<?php
include "../../include/db.php";
if (PHP_SAPI != 'cli')
    {
    exit('Access denied - Command line only!');
    }

// php populate_nodes_pre_v10.php [maximum time to run for in minutes]
// e.g. to run script for 6 hours
// php populate_nodes_pre_v10.php 360
set_time_limit(0);

if(isset($argv[1]))
    {
    // Set script to end after this number of minutes
    $endtime = time() + ((int)$argv[1]*60);
    }

$debug_log=false; // This would slow things down too much
$global_start_time = microtime(true);
$tomigrate = array_diff(array_keys($field_types),array_merge($FIXED_LIST_FIELD_TYPES,[FIELD_TYPE_DATE_RANGE]));

$resource_type_fields=ps_query('SELECT * FROM `resource_type_field` WHERE `type` IN (' . ps_param_insert(count($tomigrate)) . ') ORDER BY `ref`',ps_param_fill($tomigrate,"i"));

// Number of resource_data rows to migrate in each batch to avoid out of memory errors
$chunksize = 10000;
foreach($resource_type_fields as $resource_type_field)
    {
    $fref = $resource_type_field['ref'];
    $fname = $resource_type_field['name'];
    
    $status = "Migrating resource_data for field #" . $fref . " (" . $fname . ")";
    
    // get_nodes() can cause memory errors for non-fixed list fields so will get hash (a single md5 is too susceptible to collisions for large datasets) and compare on that
    $nodeinfo =  ps_query("SELECT ref, concat(MD5(name),MD5(CONCAT('!',name))) hash FROM node WHERE resource_type_field = ?" , ["i", $fref]);
    $allfieldnodes= array_column($nodeinfo,"ref","hash");

    $totalrows = ps_value("SELECT count(*) AS value FROM `resource_data` WHERE resource_type_field = ?",["i",$fref],0);

    $out = " (" . $totalrows . " rows found)";
    logScript(str_pad($status . $out,100,' '));
    ob_flush();
    $chunkstart = 0;
    $processed = 0;
    while($chunkstart < $totalrows)
        {
        $rows = ps_query("SELECT `resource`,`value` FROM `resource_data` WHERE resource_type_field = ? ORDER BY resource ASC LIMIT " . $chunkstart . ", " . $chunksize . "",["i",$fref]);

        // Process in smaller chunks for inserts
        $rowbatches = array_chunk($rows, 2000);
        
        // Get current nodes for this batch of resources
        $batchresources = array_column($rows,"resource");
        $max = max($batchresources);
        $min = min($batchresources);
        $resnodes = ps_query("SELECT rn.resource, rn.node FROM resource_node rn LEFT JOIN node n ON n.ref=rn.node WHERE rn.resource >= '" . $min . "' AND rn.resource <= '" . $max . "' AND n.resource_type_field = ?",["i",$fref]);
        $resnodearr = [];
        foreach($resnodes as $resnode)
            {
            $resnodearr[$resnode["resource"]][] = $resnode["node"];
            }

        for($n=0;$n<count($rowbatches);$n++)
            {
            if(isset($endtime) && time() > $endtime)
                {
                logScript("Time limit reached, exiting\n");
                break 3;
                }
            db_begin_transaction("populate_nodes_from_data");
            foreach($rowbatches[$n] as $rowdata)
                {
                if(trim($rowdata["value"]) != "")
                    {
                    if(isset($allfieldnodes[md5($rowdata["value"]) . md5('!'. $rowdata["value"])]))
                        {
                        $newnode = $allfieldnodes[md5($rowdata["value"]) . md5('!'. $rowdata["value"])];
                        }
                    else
                        {
                        // Not using set_node() here as that will reindex node. 
                        // The existing data from resource_keyword can be used instead to speed things up
                        $addnodequery = "INSERT INTO `node` (`resource_type_field`, `name`, `parent`, `order_by`) VALUES (?, ?, NULL, 0)";
                        $parameters=array  
                            (
                            "i",$fref,
                            "s",$rowdata["value"],
                            );
                        ps_query($addnodequery,$parameters);
                        $newnode = sql_insert_id();
                        $copykeywordquery = "INSERT INTO node_keyword (node, keyword, position) SELECT ?, keyword, position FROM resource_keyword WHERE resource = ? AND resource_type_field = ?";
                        $copykeywordparams = ["i",$newnode,"i",$rowdata["resource"],"i", $fref];
                        ps_query($copykeywordquery,$copykeywordparams);
                        $allfieldnodes[md5($rowdata["value"]) . md5('!'. $rowdata["value"])] = $newnode;
                        }
                    if(!isset($resnodearr[$rowdata["resource"]]) || !in_array($newnode,$resnodearr[$rowdata["resource"]]))
                        {
                        logScript("Updating resource " . $rowdata["resource"] . ", field #" . $fref . " (" . $fname . ") with node " . $newnode . " (" . str_replace("\n"," ",mb_strcut($rowdata["value"],0,30)) . "...)");

                        // Not using add_resource_nodes() here to speed things up - action doesn't need to be logged
                        ps_query("INSERT INTO resource_node(resource, node) VALUES (?,?)", ["i",$rowdata["resource"],"i",$newnode]);
                        $resnodearr[$rowdata["resource"]][] = $newnode;
                        }
                    else
                        {
                        logScript("Skipping, correct node already set for resource - " . $rowdata["resource"] . ", field #" . $fref . " (" . $fname . ") with node " . $newnode . " (" . str_replace("\n"," ",mb_strcut($rowdata["value"],0,30)) . "...)");
                        }
                    }
                
                $processed++;
                }
            db_end_transaction("populate_nodes_from_data");
            $out = " - processed $processed / $totalrows records for field # ". $fref;
            logScript(str_pad($out,100,' '));
            ob_flush();
            }
        $chunkstart = $chunkstart + $chunksize;
        }
    $out = sprintf(" - Completed $processed records in %01.2f seconds.\n", microtime(true) - $global_start_time);
    logScript(str_pad($out,100,' '));
    }
echo "Finished\n\n";
