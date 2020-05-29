<?php

if(!(php_sapi_name() == 'cli')){exit ("Access denied"); }

include dirname(__FILE__) . "/../../../include/db.php";


include_once dirname(__FILE__) . "/../include/tms_link_functions.php";

$debug_log=false;

ob_end_clean();
set_time_limit($cron_job_time_limit);

if(!in_array("tms_link",$plugins))
    {
    exit("TMS link plugin is not enabled - exiting.". PHP_EOL);    
    }
    
if($tms_link_email_notify!=""){$email_notify=$tms_link_email_notify;}

// Check when this script was last run - do it now in case of permanent process locks
$scriptlastran=sql_value("select value from sysvars where name='last_tms_import'","");

$tms_link_script_failure_notify_seconds=intval($tms_link_script_failure_notify_days)*60*60*24;

if($scriptlastran=="" || time()>=(strtotime($scriptlastran)+$tms_link_script_failure_notify_seconds))
	{
	$tmsfailedsubject=(($tms_link_test_mode)?"TESTING MODE ":"") . "TMS Import script - WARNING";
	send_mail($email_notify,$tmsfailedsubject,"WARNING: The TMS Import Script has not completed since "  . (($scriptlastran!="")?date("l F jS Y @ H:i:s",strtotime($scriptlastran)):$lang["status-never"]) . PHP_EOL . " You can safely ignore this warning only if you subsequently receive notification of a successful script completion.",$email_from);
	}


if ($argc == 2)
	{
	if ( in_array($argv[1], array('--help', '-help', '-h', '-?')) )
		{
		echo "To clear the lock after a failed run, ";
  		echo "pass in '--clearlock', '-clearlock', '-c' or '--c'." . PHP_EOL;
  		exit("Bye!");
  		}
	else if ( in_array($argv[1], array('--clearlock', '-clearlock', '-c', '--c')) )
		{
		if ( is_process_lock("tms_link") )
			{
			clear_process_lock("tms_link");
			}
		}
	else
		{
		exit("Unknown argv: " . $argv[1]);
		}
	} 

# Check for a process lock
if (is_process_lock("tms_link")) 
	{
	echo 'TMS script lock is in place. Deferring.' . PHP_EOL;
	echo 'To clear the lock after a failed run use --clearlock flag.' . PHP_EOL;
	$tmsfailedsubject=(($tms_link_test_mode)?"TESTING MODE ":"") . "TMS Import script - FAILED";
	send_mail($email_notify,$tmsfailedsubject,"The TMS script failed to run because a process lock was in place. This indicates that the previous run did not complete. If you need to clear the lock after a failed run, run the script as follows:-" . PHP_EOL . PHP_EOL . " php tms_update_script.php --clearlock" . PHP_EOL ,$email_from);
	exit();
	}
set_process_lock("tms_link");

// Record the start time
$tms_script_start_time = microtime(true);

$tms_log = false;
if(trim($tms_link_log_directory)!="")
    {
    if (!is_dir($tms_link_log_directory))
        {
        @mkdir($tms_link_log_directory, 0755, true);
        if (!is_dir($tms_link_log_directory))
            {
            echo 'Unable to create log directory: ' . htmlspecialchars($tms_link_log_directory) . PHP_EOL;
            }
        }
    else
        {
        // Valid log directory  
        
        // clean up old files
        $iterator = new DirectoryIterator($tms_link_log_directory);
        $expirytime = $tms_script_start_time - (intval($tms_link_log_expiry) * 24 * 60 * 60) ;
        foreach ($iterator as $fileinfo)
            {
            if ($fileinfo->isFile()) 
                {
                $filename = $fileinfo->getFilename();           
                if (substr($filename,0,15)=="tms_import_log_" && $fileinfo->getMTime() < $expirytime)
                    {
                    // Attempt to delete file - it is a TMS log and is older than the log expiration period
                    @unlink($fileinfo->getPathName());
                    }
                }
            }
        
        $logfile=fopen($tms_link_log_directory . DIRECTORY_SEPARATOR . "tms_import_log_" . date("Y_m_d_H_i") . ".log","ab");
        $tms_log = true;
        }
    }

$logmessage = "Script started at " . date("Y-m-d H:i",time()) . PHP_EOL;
echo $logmessage;
if($tms_log)
    {
    fwrite($logfile, $logmessage);
    }

$tmscount = 0;
$tms_updated_array = array();
$tmserrors = array();

foreach(tms_link_get_modules_mappings() as $module)
    {
    $tms_resources = tms_link_get_tms_resources($module);

    if(empty($tms_resources))
        {
        continue;
        }

    $current_tms_count = count($tms_resources);
    $tmscount += $current_tms_count;

    $tmspointer = 0;
    
    if(!$tms_link_test_mode || !is_numeric($tms_link_test_count))
        {
        $tms_link_test_count = 999999999;
        }

    while($tmspointer < $current_tms_count && $tmspointer < $tms_link_test_count)
        {
        $tms_query_ids = array();

        for($t = $tmspointer; $t < ($tmspointer + $tms_link_query_chunk_size) && (($tmspointer + $t) < $tms_link_test_count) && $t < $current_tms_count; $t++)
            {
            if($tms_resources[$t]["objectid"] != "" && is_numeric($tms_resources[$t]["objectid"]) && strpos($tms_resources[$t]["objectid"], ".") === false)
                {
                $tms_query_ids[] = $tms_resources[$t]["objectid"];
                }
            else
                {
                $logmessage = "Invalid TMS data stored in ResourceSpace: '" . $tms_resources[$t]["objectid"] . "'";
                $tmserrors[$tms_resources[$t]["resource"]] = $logmessage;
                if($tms_log)
                    {
                    fwrite($logfile, $logmessage . PHP_EOL);
                    }
                }
            }

        $logmessage = "Retrieving data from TMS system" . PHP_EOL;
        echo $logmessage;
        if($tms_log)
            {
            fwrite($logfile, $logmessage);
            }

        $tmsresults = tms_link_get_tms_data("", $tms_query_ids);

        if(!is_array($tmsresults) || count($tmsresults) == 0 || !array_key_exists($module['module_name'], $tmsresults))
            {
            echo "No TMS data received, continuing" . PHP_EOL;
            $tmspointer = $tmspointer + $tms_link_query_chunk_size;
            continue;
            }

        $tmsresults = $tmsresults[$module['module_name']];

        // Go through this set of resources and update db/show data/do something else
        for($ri = $tmspointer; $ri < ($tmspointer + $tms_link_query_chunk_size) && (($tmspointer + $ri) < $tms_link_test_count) && $ri < $tmscount; $ri++)
            {
            $tms_data_found = false;

            foreach($tmsresults as $tmsresult)
                {
                if($tms_resources[$ri]["objectid"] != $tmsresult[$module['tms_uid_field']])
                    {
                    continue;
                    }

                $tms_data_found = true;

                debug("TMS_LINK - Checking resource: "  . $tms_resources[$ri]["resource"]  . ". Object ID: " . $tms_resources[$ri]["objectid"]);
                $logmessage= "Checking resource: "  . $tms_resources[$ri]["resource"]  . ". Object ID: " . $tms_resources[$ri]["objectid"] . PHP_EOL;
                echo $logmessage;
                if($tms_log)
                    {
                    fwrite($logfile, $logmessage);
                    }

                // Check checksum
                if(isset($tmsresult["RowChecksum"]) && $tms_resources[$ri]["checksum"] == $tmsresult["RowChecksum"])
                    {
                    debug("TMS_LINK ---- Checksum matches for resource #" .  $tms_resources[$ri]["resource"] . ". Skipping..." . PHP_EOL);
                    $logmessage = "-- Checksum matches. Skipping...". PHP_EOL;
                    echo $logmessage;
                    if($tms_log)
                        {
                        fwrite($logfile,$logmessage);
                        }

                    continue;
                    }

                debug("TMS_LINK ---- UPDATE! Checksum differs for resource #" .  $tms_resources[$ri]["resource"] . PHP_EOL);
                $logmessage = "-- Checksum differs.(CURRENT: " . $tms_resources[$ri]["checksum"] . " vs NEW: " . (isset($tmsresult["RowChecksum"])?$tmsresult["RowChecksum"]:"EMPTY") . ") Updating." . PHP_EOL;
                echo $logmessage;
                if($tms_log)
                    {
                    fwrite($logfile, $logmessage);
                    }
                
                $updatedok = false;

                // Update fields if necessary
                foreach($module['tms_rs_mappings'] as $tms_rs_mapping)
                    {
                    $tms_link_column_name = $tms_rs_mapping['tms_column'];
                    $tms_link_field_id = $tms_rs_mapping['rs_field'];

                    if($tms_link_field_id!="" && $tms_link_field_id!=0 && isset($tmsresult[$tms_link_column_name]))
                        {   
                        $existingval = get_data_by_field($tms_resources[$ri]["resource"], $tms_link_field_id);                                                                     
                        $newval     =$tmsresult[$tms_link_column_name];
                        $resource_type_field_data = get_resource_type_field($tms_link_field_id);
                        if($resource_type_field_data!==false && $resource_type_field_data['type'] == FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR)
                            {
                            $newval = strip_tags($tmsresult[$tms_link_column_name]);
                            $newval = str_replace('&nbsp;', ' ', $newval);
                            }
                            
                        if ($existingval!== $newval)
                            {
                            if(!$tms_link_test_mode)
                                {
                                $logmessage = "---- Updating RS field " . $tms_link_field_id . " from column " . $tms_link_column_name . ". VALUE: " . $tmsresult[$tms_link_column_name] . PHP_EOL;
                                echo $logmessage;
                                if($tms_log)
                                    {
                                    fwrite($logfile,$logmessage);
                                    }

                                update_field($tms_resources[$ri]["resource"], $tms_link_field_id, escape_check($tmsresult[$tms_link_column_name]));
                                }

                            if($tms_link_field_id != $tms_link_checksum_field)
                                {
                                $updatedok = true;
                                } // Don't record as successful - if it is only the checksum that has changed then this has not really been worth processing 
                            }
                        }
                    }

                if($updatedok)
                    {
                    $tms_updated_array[$tms_resources[$ri]["resource"]] = $tms_resources[$ri]["objectid"];

                    if($tms_log)
                        {
                        fwrite($logfile,"Resource " . $tms_resources[$ri]["resource"] . " : Updated successfully" . PHP_EOL);
                        }
                    }
                else
                    {
                    $logmessage="Checksum differs but no changes were found when comparing ResourceSpace data with TMS data.";
                    $tmserrors[$tms_resources[$ri]["resource"]]=$logmessage;
                    echo $logmessage;
                    if($tms_log)
                        {
                        fwrite($logfile,"Resource " . $tms_resources[$ri]["resource"] . " : " . $logmessage . PHP_EOL);
                        }
                    }

                } # end of $tmsresults loop

            if(!$tms_data_found && !isset($tmserrors[$tms_resources[$ri]["resource"]]))
                {
                $tmserrors[$tms_resources[$ri]["resource"]] = "No TMS data found for resource - ObjectID : " . $tms_resources[$ri]["objectid"];
                }

            }

        // Update pointer and go onto next set of resources
        $tmspointer = $tmspointer+$tms_link_query_chunk_size;

        } # end of while loop
    } # end of tms_link_get_modules_mappings()

$logtext  = "";
$logtext .= PHP_EOL . sprintf("TMS Script completed in %01.2f seconds.\n", microtime(true) - $tms_script_start_time) . PHP_EOL;

if($tmscount==0)
	{
	$tmsstatustext="Completed with errors";
	if($tms_log)
        {
        fwrite($logfile,$tmsstatustext);
        }

	$logtext.="No Resources found with TMS IDs. Please check the tms_link plugin configuration.";	
	if($tms_log)
        {
        fwrite($logfile,"No Resources found with TMS IDs. Please check the tms_link plugin configuration.");
        }
	}
else
	{
	$logtext.="Processed " . $tmscount .  " resource(s) with TMS Object IDs." . PHP_EOL . PHP_EOL;
	$tmsupdated = count($tms_updated_array);
	$logtext.="Successfully updated " . $tmsupdated .  " resource(s)." . PHP_EOL . PHP_EOL;
	if($tmsupdated>0)
		{
        $logtext .= " Resource ID : TMS ObjectID" . PHP_EOL;
        }

	foreach($tms_updated_array as $success_ref=>$success_tmsid)
		{
		$logtext .=  " " . str_pad($success_ref,12) . ": " . $success_tmsid . PHP_EOL;
		}

	if(count($tmserrors)!=0)
		{
		$tmsstatustext= PHP_EOL . "Completed with errors" . PHP_EOL;
		$logtext.= PHP_EOL . "Failed to update " . count($tmserrors) .  " resource(s)" . PHP_EOL;
		
		$logtext .= PHP_EOL . "Error summary: -" . PHP_EOL; 
		$logtext .= " Resource ID : Error" . PHP_EOL;

        foreach($tmserrors as $errorresource=>$tmserror)
			{
			$logtext .= " " . str_pad($errorresource,12) . ": " . $tmserror . PHP_EOL;		
			}
		}	
	else
		{
		$tmsstatustext="Success!";
		}
	
	if($tms_log)
        {
        fwrite($logfile,$tmsstatustext);
        }
	}

$tmssubject = ($tms_link_test_mode ? "TESTING MODE - " : "") . "TMS Import script - " . $tmsstatustext;
send_mail($email_notify, $tmssubject, $logtext, $email_from);

echo $logtext;
$endmessage = PHP_EOL . "Script completed at " . date("Y-m-d H:i",time()) . PHP_EOL;

if($tms_log)
    {
    fwrite($logfile,$logtext);
    fwrite($logfile,$endmessage);
    fclose($logfile);
    }

clear_process_lock("tms_link");
sql_query("delete from sysvars where name='last_tms_import'");
sql_query("insert into sysvars values('last_tms_import', now())");