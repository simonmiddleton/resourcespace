<?php
include_once __DIR__ . '/../image_processing.php';
include_once __DIR__ .  "/../resource_functions.php";
# $job_data["resource"]
# $job_data["extract"]
# $job_data["autorotate"]
# $job_data["archive"]

$resource=get_resource_data($job_data["resource"]);
if($resource!==false)
	{
	$status=upload_file($job_data["resource"],$job_data["extract"],$revert=false,$job_data["autorotate"],"",true);
	echo "status:" . ($status ? 'true' : 'false') . "<br/>";
	# update the archive status
	if($job_data["archive"]!=='')
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
	
/*else
	{
	# Job failed, update job queue
	job_queue_update($jobref,$job_data,STATUS_ERROR);
    //$message=$job_failure_text!=""?$job_failure_text:$lang["download_file_creation_failed"]  . ": " . str_replace(array('%ref','%title'),array($job_data['resource'],$resource['field' . $view_title_field]),$lang["ref-title"]) . "(" . $job_data["alt_name"] . "," . $job_data["alt_description"] . ")";
   	// $url=$baseurl . "/?r=" . $job_data["resource"];
    //message_add($job["user"],$message,$url,0);
	}
	*/