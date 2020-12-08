<?php

include_once (dirname(__FILE__)."/../include/meta_functions.php");
include_once (dirname(__FILE__)."/../include/csv_functions.php");

global $userref,$username,$scramble_key,$baseurl,$csvfile,$meta,$resource_types, $messages, $csv_set_options;

$csv_set_options = $job_data["csv_set_options"];
$csvfile = $job_data["csvfile"];

// Set up the user who initiated the CSV upload as all permissions must be honoured
$user_data = validate_user("u.ref = '{$job['user']}'", true);

if(count($user_data) > 0)
    {
    setup_user($user_data[0]);
    }
else
    {
    job_queue_update($jobref, $job_data, STATUS_ERROR);
    return;
    }

$messages = array();
$meta = meta_get_map();

$restypearr = get_resource_types();
$resource_types = array();
// Sort into array with ids as keys
foreach($restypearr as $restype)
    {
    $resource_types[$restype["ref"]] = $restype;
    }
    
$logfile = get_temp_dir(false,'user_downloads') . "/" . $userref . "_" . md5($username . md5($csv_set_options["csvchecksum"]) . $scramble_key) . ".log";
$logurl = $baseurl . "/pages/download.php?userfile=" . $userref . "_" . md5($csv_set_options["csvchecksum"]) . ".log&filename=csv_upload_" . date("Ymd-H:i",time());
$csv_set_options["log_file"] = $logfile;

csv_upload_process($csvfile,$meta,$resource_types,$messages,$csv_set_options,0,true);

// Send a message to the user
job_queue_update($jobref, $job_data, STATUS_COMPLETE);
message_add($job["user"], $job["success_text"] . (isset($csv_set_options["csv_filename"]) ? (": " . $csv_set_options["csv_filename"]) : ""), $logurl);
