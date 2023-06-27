<?php
if(isset($_POST["submit"]))
    {
    $suppress_headers=true;
    $nocache = true;
    }

include "../../include/db.php";
include_once "../../include/authenticate.php";
if(!checkperm("a")){exit("Access denied");}

set_time_limit(0);

// Set flag to indicate whether we can show progress using server side events (SSE)
$showprogress   = strpos(strtoupper($_SERVER['HTTP_USER_AGENT']),"TRIDENT") === false && strpos(strtoupper($_SERVER['HTTP_USER_AGENT']),"MSIE") === false;
$migrate_field  = getval("field",0,true);
$field_info     = get_resource_type_field($migrate_field);
$splitvalue     = getval("splitchar","");
$maxrows        = getval("maxrows",0, true);
$modal          = (getval("modal","")=="true");
$dryrun         = getval("dryrun","") != "";
$deletedata     = getval("deletedata","")=="true";
$backurl=getval("backurl","");
if($backurl=="")
    {
    $backurl=$baseurl . "/pages/admin/admin_resource_type_field_edit.php?ref=" . $migrate_field;
    }

function send_event_update($message,$progress,$url="")
    {
    $output = array('message' => $message, 'progress' => $progress);
    $output['url'] = $url;
    echo "id: " . json_encode(time()) . PHP_EOL;
    echo "data: " . json_encode($output) . PHP_EOL;
    echo PHP_EOL;
    // Added to force flush as finding a way to do this seems to have varied results
    echo str_pad('',4096).PHP_EOL;
    ob_flush();
    flush();
    }
       
if(getval("submit","") != "")
    {
    ob_start();
    
    $valid_fields = ps_array("SELECT ref value FROM resource_type_field WHERE type IN (". ps_param_insert(count($FIXED_LIST_FIELD_TYPES)) .")", ps_param_fill($FIXED_LIST_FIELD_TYPES,'i'));
    $messages = array();
    
    if($showprogress)
        {
        header('Content-Type: text/event-stream');
        }
        
    if(!in_array($migrate_field,$valid_fields))
        {
        $messages[] = "Invalid field specified. Only fixed type field types can be specified";
        }

 
	$migrated = 0;
    $lastcompletion = 0;
    $completion = 0;
    $now = date(time());
    // Set up logging
    $logfile = get_temp_dir(false,'') . "/migrate-data_" . $userref . "_" . md5($username . $now . $scramble_key) . ".txt";
    $logurl = $baseurl . "/pages/download.php?tempfile=migrate-data_" . $userref . "_" . $now . ".txt";
    $fp = fopen($logfile, 'a');
    fwrite($fp, "<pre>Script started at " . date("Y-m-d H:i",time()) . PHP_EOL);
    fwrite($fp, "Migrating data from text field '" . $field_info["title"] . "' ID #" . $migrate_field . PHP_EOL);
    fclose($fp);
    

    $chunksize = 1000;    
    $nodeinfo = ps_query("SELECT MAX(ref) maxref, MIN(ref) minref, count(*) count FROM node WHERE resource_type_field = ?",array("i",$migrate_field), 0);

	$total = $nodeinfo[0]["count"];
	$minref = $nodeinfo[0]["minref"];
	$maxref = $nodeinfo[0]["maxref"];
    $newnoderef = $maxref+1;
    $deletenodes = [];

    // Get existing nodes
    $existing_nodes = get_nodes($migrate_field, NULL, TRUE);

    while($migrated < $total && ($maxrows == 0 || $migrated < $maxrows))
        {
        $nodedata = ps_query(
            "SELECT n.ref, n.`name`,
                GROUP_CONCAT(rn.resource)  AS resources
                FROM node n 
                LEFT JOIN resource_node  rn ON n.ref=rn.node
                WHERE resource_type_field = ?
                AND ref >= ?
                GROUP BY n.ref
                ORDER BY n.ref ASC
                LIMIT ?", ['i', $migrate_field, 'i', $minref, 'i', $chunksize]
            );

        // Process each data row
        foreach($nodedata as $node)
            {
            $deletenodes[] = $node["ref"];
            if(trim($node['name']) == '' || strpos($node['name'],$splitvalue) === false || ($maxrows != 0 && $migrated >= $maxrows))
                {
                $minref = $node["ref"];
                $migrated++;
                continue;
                }

            $logtext = "";
            $nodes_to_add = [];
            $resources = explode(",",$node["resources"]);
            $nodename = $node["name"];
            $logtext .= ($dryrun?"TESTING: ":"") . "Checking data for node id #" . $node["ref"] . ". Value: '" . $nodename . "'" . PHP_EOL;

            $arr_newvals = explode($splitvalue,$nodename);
            
            foreach($arr_newvals as $newvalue)
                {
                // Skip if this value is empty (e.g if users left a separator at the end of the value by mistake)
                $newvalue = trim($newvalue);
                if($newvalue == '')
                    {
                    continue;
                    }
                $nodeidx = array_search($newvalue,array_column($existing_nodes,"name"));

                if($nodeidx !== false)
                    {
                    $logtext .= ($dryrun?"TESTING: ":"") . " - Found matching field node option. ref:" . $existing_nodes[$nodeidx]["ref"] . PHP_EOL;
                    $nodes_to_add[] = $existing_nodes[$nodeidx]["ref"];      
                    }
                else
                    {
                    if(!$dryrun)
                        {
                        $newnode = set_node(NULL, $migrate_field, $newvalue, NULL, '');
                        $newnodecounter = count($existing_nodes);
                        $logtext .= " - New option added for '" . htmlspecialchars($newvalue) . "' - ref: " . $newnode . PHP_EOL;
                        $nodes_to_add[] = $newnode;
                        $existing_nodes[$newnodecounter]["ref"] = $newnode;
                        $existing_nodes[$newnodecounter]["name"] = $newvalue;
                        }
                    else
                        {
                        $newnode = $newnoderef;
                        $logtext .= " - Added node for '" . htmlspecialchars($newvalue) . "' - ref: " . $newnode . PHP_EOL;
                        $newnodecounter = count($existing_nodes);
                        $nodes_to_add[] = $newnode;
                        $existing_nodes[$newnodecounter]["ref"] = $newnoderef;
                        $existing_nodes[$newnodecounter]["name"] = $newvalue;
                        $newnoderef++;
                        }
                    }
                }
            
            if(count($nodes_to_add) > 0)
                {
                $logtext .= ($dryrun?"TESTING: ":"") . "Adding nodes to resource IDs " . $node["resources"] . ": (" . implode(",", $nodes_to_add) . ")" . PHP_EOL;
                if(!$dryrun)
                    {
                    add_resource_nodes_multi($resources,$nodes_to_add);
                    delete_resource_nodes_multi($resources,[$node["ref"]]);
                    }
                }
            
            if($deletedata)
                {
                $logtext = ($dryrun ? "TESTING: " : "") . "Deleting unused node# " . $node["ref"] . PHP_EOL;
                if(!$dryrun)
                    {
                    delete_node($node["ref"]);
                    }
                }
               
            $migrated++;
            $minref = $node["ref"];

            $completion = ($maxrows == 0) ? floor($migrated/$total*100) : floor($migrated/$maxrows*100);  
            if($showprogress && $lastcompletion != $completion)
                {               
                send_event_update("Node " . $migrated . "/" . $total . PHP_EOL, $completion,$logurl);
                $lastcompletion = $completion;
                }

            // Update log
            $fp = fopen($logfile, 'a');
            fwrite($fp, $logtext);
            fclose($fp);

            if (connection_aborted() != 0)
                {
                $logtext = ($dryrun?"TESTING: ":"") . " Connection aborted" . PHP_EOL;
                $fp = fopen($logfile, 'a');
                fwrite($fp, $logtext);
                fclose($fp);
                exit();
                }
            }
        
        if (connection_aborted () != 0)
            {
            $logtext = ($dryrun?"TESTING: ":"") . " Connection aborted" . PHP_EOL;
            $fp = fopen($logfile, 'a');
            fwrite($fp, $logtext);
            fclose($fp);
            exit();
            }
        }
   

    $logtext = "Completed at " . date("Y-m-d H:i",time()) . ". " . $total . " rows migrated" . PHP_EOL;
    // Update log
    $fp = fopen($logfile, 'a');
    fwrite($fp, $logtext);
    fclose($fp);
    
    $completemessage = ($dryrun ? "TESTING: " : "") . "Completed at " . date("Y-m-d H:i",time()) . ". " . $migrated . " rows migrated out of " . $total . "</pre>";
    
    // Send a message to the user
    message_add($userref,$lang["admin_resource_type_field_migrate_data"] . ": " . $completemessage , $logurl);
    
    // Always send the completion event
    if($showprogress)
        {
        send_event_update($completemessage . PHP_EOL, "100",$logurl);
        }
    else
        {
        echo json_encode(array("message"=>$completemessage,"url"=>$logurl));
        }
    exit();
    }
    
include_once "../../include/header.php";


?>
<div class="BasicsBox">
	<p>    
	<a href="<?php echo $backurl ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"]?></a>
	</p>
	<h1><?php echo $lang["admin_resource_type_field_migrate_data"] ?></h1>

	<form method="post" class="FormWide" action="<?php echo $baseurl_short ?>pages/tools/migrate_data_to_fixed.php" onsubmit="if(jQuery('#splitchar').val()==''){styledalert('<?php echo htmlspecialchars($lang["admin_resource_type_field_no_action"]); ?>');return false;};start_task(this);return false;">
     <?php generateFormToken("migrate_data_to_fixed"); ?>
    <?php
    render_field_selector_question($lang["field"],"field",[],"medwidth",false,$migrate_field);
    ?>
	<div class="Question" >
		<label for="splitchar" ><?php echo $lang["admin_resource_type_field_migrate_separator"] ?></label>
		<input class="medwidth" type="text" id="splitchar" name="splitchar" value=",">
        <div class="clearerleft"> </div>
	</div>
    <div class="Question" >
		<label for="maxrows" ><?php echo $lang["max"] . " " . $lang["resources"]?></label>
		<input class="medwidth" type="text" name="maxrows" value="">
        <div class="clearerleft"> </div>
	</div>
	<div class="Question" >
		<label for="dryrun" ><?php echo $lang["admin_resource_type_field_migrate_dry_run"] ?></label>
		<input class="medwidth" type="checkbox" name="dryrun" value="true">
        <div class="clearerleft"> </div>
	</div>
	<div class="Question" >
		<label for="deletedata" ><?php echo $lang["admin_resource_type_field_migrate_delete_data"] ?></label>
		<input class="medwidth" type="checkbox" name="deletedata" value="true">
        <div class="clearerleft"> </div>
	</div>
    <div class="Question" >
		<label for="progress"><?php echo $lang["progress"] ?></label>
		<div class="Fixed" id="progress" >0%</div>
        <div class="clearerleft"> </div>
	</div>
    <div class="Question" >
		<label for="progress_log"><?php echo $lang["status"] ?></label>
        <div class="Fixed medwidth" id="progress_log" ></div>
        <div class="clearerleft"> </div>
	</div>
    <div class="Question" >
		<label for="view_log"><?php echo $lang["log"] ?></label>
        <div class="Fixed medwidth" id="view_log" ><a style="display:none;" id="log_url" href="#" target="_blank"><?php echo $lang["action-log"] ?></a></div>
        <div class="clearerleft"> </div>
	</div>
    <div class="Question" >
		<input type="hidden" id="submitinput" name="submit" value="">
		<input type="submit" name="submit" value="<?php echo $lang["action-submit-button-label"] ?>"" onclick="document.getElementById('submitinput').value='true';">
        <div class="clearerleft"> </div>
	</div>
    
	<div class="clearerleft"> </div>
    
	</form>
    <script>
        function start_task(form)
        <?php        
        if($showprogress)
            {?>
            {
            source = new EventSource(form.action + '?' + jQuery(form).serialize());
            jQuery('#progress_log').html('Running...\n');
            source.addEventListener('message' , function(e) 
                {
                var result = JSON.parse( e.data );
                add_log(result.message);                
                jQuery('#progress').html(result.progress + '%');
                jQuery('#log_url').attr('href',result.url);
                jQuery('#log_url').show();
                    
                if(e.data.search('Completed') != -1)
                    {               
                    add_log(result.message);
                    source.close();
                    }
                
                });
                 
            source.addEventListener('error' , function(e)
                {
                jQuery('#progress_log').append('<?php echo $lang["error"]; ?> ' . result.message);
                source.close();
                });
                
            function add_log(message)
                {
                jQuery('#progress_log').html(message);
                jQuery('#progress_log').scrollTop(jQuery('#progress_log').prop('scrollHeight'));
                } 
            }
            <?php
            }
        else
            {
            ?>
            {
            jQuery('#progress_log').html('Running. Please do not leave this page. You will be notified when the migration has completed.\n');
            formdata = jQuery(form).serialize();
            jQuery.ajax({
                url: form.action + '?' + formdata,
                dataType: "json"
                }).done(function(data)
                    {
                    jQuery('#progress_log').html(data.message);
                    jQuery('#progress').html("100%");
                    jQuery('#log_url').attr('href',data.url);
                    jQuery('#log_url').show();
                    });
                  
            return false;
            }           
            <?php
            } ?>       
    </script>
        
</div>
<?php


include_once "../../include/footer.php";


