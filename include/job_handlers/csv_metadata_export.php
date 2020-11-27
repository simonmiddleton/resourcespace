<?php
/*
Job handler to process CSV metadata download for result set

Requires the following job data:

$job_data["personaldata"]    - (bool) Only include fields marked as likely to contains personal data?
$job_data["allavailable"]    - (bool) Include data from all fields?
$job_data["$search_results"] - (array) Result set returned by do_search()*/

include_once __DIR__ . '/../csv_export_functions.php';

global $lang, $baseurl, $offline_job_delete_completed, $scramble_key, $download_file_lifetime, $userref, $username;

foreach($job_data as $arg => $value)
    {
    $$arg = $value;
    }

// Set up the user who requested the metadata download as it needs to be processed with their access
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

$randstring = md5(json_encode($job_data));
$csvfile = get_temp_dir(false,'user_downloads') . "/" . $userref . "_" . md5($username . $randstring . $scramble_key) . ".csv";

$findstrings = array("%%SEARCH%%","%%TIME%%");
$replacestrings = array(safe_file_name("TEST"),date("Ymd-H:i",time()));
$csv_filename = str_replace($findstrings, $replacestrings, $lang["csv_export_filename"]);

$csvurl = $baseurl . "/pages/download.php?userfile=" . $userref . "_" . $randstring . ".csv&filename=" . $csv_filename . ".csv";

generateResourcesMetadataCSV($exportresources,$personaldata, $allavailable, $csvfile);

$jobsuccess = true;

message_add($job["user"], $job_success_text, $csvurl);

$delete_job_data=array();
$delete_job_data["file"]=$csvfile;
$delete_date = date('Y-m-d H:i:s',time()+(60*60*24*(int)$download_file_lifetime)); // Delete file after set number of days
$job_code=md5($csvfile);
job_queue_add("delete_file",$delete_job_data,"",$delete_date,"","",$job_code);