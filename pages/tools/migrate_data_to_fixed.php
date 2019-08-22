<?php
if(isset($_POST["submit"]))
    {
    $suppress_headers=true;
    $nocache = true;
    }

include "../../include/db.php";
include_once "../../include/general.php";
include_once "../../include/resource_functions.php";
include_once "../../include/search_functions.php";
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

$backurl=getvalescaped("backurl","");
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
    
    $valid_fields = sql_array("SELECT ref value FROM resource_type_field WHERE type IN ('" . implode("','", $FIXED_LIST_FIELD_TYPES) . "')");
    $messages = array();
    
    if($showprogress)
        {
        header('Content-Type: text/event-stream');
        }
        
    if(!in_array($migrate_field,$valid_fields))
        {
        $messages[] = "Invalid field specified. Only fixed type field types can be specified";
        }
    
    // Get all existing nodes
    $existing_nodes = get_nodes($migrate_field, NULL, TRUE);
	if($dryrun)
		{
		// Set start value for dummy node refs 
		$newnoderef = (count($existing_nodes) > 0) ? max(array_column($existing_nodes,"ref")) + 1: 0;
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
    $lower=0;
    $upper = $lower + $chunksize;
    $total = sql_value("SELECT count(*) value FROM resource_data   WHERE resource_type_field = '{$migrate_field}'",0);

    while($migrated < $total && ($maxrows == 0 || $migrated < $maxrows))
        {
        $resdata = sql_query(
            "SELECT resource,
                    `value` 
                FROM resource_data 
                WHERE resource_type_field = '{$migrate_field}'
                LIMIT " . $lower . "," . $upper
            );

        // Process each data row
        foreach($resdata as $resdata_row)
            {
            // No need to process any further if no data is found set for this resource
            if(trim($resdata_row['value']) == '' || ($maxrows != 0 && $migrated >= $maxrows))
                {
                continue;
                }

            $logtext = "";
            $nodes_to_add = array();
            $resource = $resdata_row["resource"];
            $logtext .= ($dryrun?"TESTING: ":"") . "Checking data for resource id #" . $resource . ". Value: '" . $resdata_row["value"] . "'" . PHP_EOL;

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
        
                $nodeidx = array_search($data_value,array_column($existing_nodes,"name"));

                if($nodeidx !== false)
                    {
                    $logtext .= ($dryrun?"TESTING: ":"") . " - Found matching field node option. ref:" . $existing_nodes[$nodeidx]["ref"] . PHP_EOL;
                    $nodes_to_add[] = $existing_nodes[$nodeidx]["ref"];      
                    }
                else
                    {
                    if(!$dryrun)
                        {
                        $newnode = set_node(NULL, $migrate_field, escape_check($data_value), NULL, '',true);
                        $logtext .= " - New option added for '" . htmlspecialchars($data_value) . "' - ref: " . $newnode . PHP_EOL;
                        $nodes_to_add[] = $newnode;
                        $newnodecounter = count($existing_nodes);
                        $existing_nodes[$newnodecounter]["ref"] = $newnode;
                        $existing_nodes[$newnodecounter]["name"] = $data_value;
                        }
                    else 
                        {
                        $newnode = $newnoderef;
                        $logtext .= ($dryrun?"TESTING: ":"") . " - New option added for '" . htmlspecialchars($data_value) . "' - ref: " . $newnoderef . PHP_EOL;
                        $newnodecounter = count($existing_nodes);
                        $existing_nodes[$newnodecounter]["ref"] = $newnoderef;
                        $existing_nodes[$newnodecounter]["name"] = $data_value;
                        $newnoderef++;							
                        }
                    }
                }           
            
            if(count($nodes_to_add) > 0)
                {
                $logtext .= ($dryrun?"TESTING: ":"") . "Adding nodes to resource ID #" . $resource . ": " . implode(",", $nodes_to_add) . PHP_EOL;

                if(!$dryrun)
                    {
                    add_resource_nodes($resource,$nodes_to_add);
                    }
                }
               
            $migrated++;
            
            $completion = ($maxrows == 0) ? floor($migrated/$total*100) : floor($migrated/$maxrows*100);  
            if($showprogress && $lastcompletion != $completion)
                {               
                send_event_update("Resource " . $migrated . "/" . $total . PHP_EOL, $completion,$logurl);
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

        if($deletedata && !$dryrun)
            {
            $logtext = ($dryrun?"TESTING: ":"") . "Deleting existing data for " . $chunksize . " resources " . PHP_EOL;
            $fp = fopen($logfile, 'a');
            fwrite($fp, $logtext);
            fclose($fp);
            sql_query("delete from resource_data where resource_type_field='" . $migrate_field . "' AND resource IN ('" . implode("','",array_column($resdata, "resource")) . "')");
            sql_query("delete from resource_keyword where resource_type_field='" . $migrate_field . "' AND resource IN ('" . implode("','",array_column($resdata, "resource")) . "')");
            
            $lower = 0;
            $upper = $chunksize;
            }
        else
            {
            $lower = $upper + 1;
            $upper = $upper + $chunksize;
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

	<form method="post" class="FormWide" action="<?php echo $baseurl_short ?>pages/tools/migrate_data_to_fixed.php" onsubmit="start_task(this);return false;">
     <?php generateFormToken("migrate_data_to_fixed"); ?>    
	<div class="Question" >
		<label for="field" ><?php echo $lang["field"] ?></label>
        <?php if($migrate_field == 0)
            {?>
            <input class="medwidth" type="number" name="field" value="<?php echo $migrate_field ?>">
            <?php }
        else
            {?>
            <input type="hidden" name="field" value="<?php echo htmlspecialchars($migrate_field); ?>">
            <div class="Fixed" id="field" ><?php echo htmlspecialchars($field_info["title"]) . " (" . htmlspecialchars($migrate_field) . ")"; ?></div>
            <?php } ?>
        <div class="clearerleft"> </div>
	</div>
	<div class="Question" >
		<label for="splitchar" ><?php echo $lang["admin_resource_type_field_migrate_separator"] ?></label>
		<input class="medwidth" type="text" name="splitchar" value=",">
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


