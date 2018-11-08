<?php
include_once __DIR__ . '/../image_processing.php';
include_once __DIR__ .  "/../resource_functions.php";
# $job_data["resource"]
# $job_data["extract"]
# $job_data["revert"]
# $job_data["autorotate"]
# $job_data["archive"] -> optional based on $upload_then_process_holding_state

$resource=get_resource_data($job_data["resource"]);
$status=false;

if($resource!==false)
	{
	$status=upload_file($job_data["resource"], $job_data["extract"], $job_data["revert"], $job_data["autorotate"] ,"", true);
	
	# update the archive status
	if(isset($job_data['archive']) && $job_data['archive'] !== '')
		{
		update_archive_status($job_data["resource"], $job_data["archive"]);
		}
	}

global $baseurl, $offline_job_delete_completed;

$url = isset($job_data['resource']) ? $baseurl . "/?r=" . $job_data['resource']: '';

if($status===false)
    {
    # fail
    message_add($job['user'], $job_failure_text, $url, 0);
    
    job_queue_update($jobref , $job_data , STATUS_ERROR);
    }
else
    {
    # success
    message_add($job['user'], $job_success_text, $url, 0);
    
    # only delete the job if completed successfully;
    if($offline_job_delete_completed)
        {
        job_queue_delete($jobref);
        }
    else
        {
        job_queue_update($jobref,$job_data,STATUS_COMPLETE);
        }
    }
