<?php
include_once __DIR__ . '/../image_processing.php';
include_once __DIR__ .  "/../resource_functions.php";
# $job_data["resource"]
# $job_data["extract"]
# $job_data["autorotate"]
# $job_data["archive"] -> optional based on $upload_then_process_holding_state

$resource=get_resource_data($job_data["resource"]);
if($resource!==false)
	{
	$status=upload_file($job_data["resource"],$job_data["extract"],$revert=false,$job_data["autorotate"],"",true);
	echo "status:" . ($status ? 'true' : 'false') . "<br/>";
	# update the archive status
	if(isset($job_data['archive']) && $job_data['archive'] !== '')
		{
		update_archive_status($job_data["resource"], $job_data["archive"]);
		}
	}

global $offline_job_delete_completed;

if($offline_job_delete_completed)
	{
	job_queue_delete($jobref);
	}
else
	{
	job_queue_update($jobref,$job_data,STATUS_COMPLETE);
	}