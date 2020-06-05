<?php
include_once __DIR__ . '/../image_processing.php';
# $job_data["resource"]
# $job_data["extract"]
# $job_data["revert"]
# $job_data["autorotate"]
# $job_data["archive"] -> optional based on $upload_then_process_holding_state
# $job_data["upload_file_by_url"] -> optional. If NOT empty, means upload_file_by_url should be used instead
# $job_data["alternative"] -> optional. If alternative ID is passed, then process upload for the alternative
# $job_data["extension"] -> optional. Used for alternative uploads.
# $job_data["file_path"] -> optional. Used for alternative uploads.

$upload_file_by_url = isset($job_data["upload_file_by_url"]) && is_string($job_data["upload_file_by_url"]) ? trim($job_data["upload_file_by_url"]) : "";
$alternative = isset($job_data["alternative"]) && is_int($job_data["alternative"]) ? $job_data["alternative"] : null;
$extension = isset($job_data["extension"]) && is_string($job_data["extension"]) ? trim($job_data["extension"]) : null;
$file_path = isset($job_data["file_path"]) && is_string($job_data["file_path"]) ? trim($job_data["file_path"]) : null;

// Set up the user who triggered this event - the upload should be done as them
$user_data = validate_user("AND u.ref = '" . escape_check($job['user']) . "'", true);
if(!is_array($user_data) || count($user_data) == 0)
    {
    job_queue_update($jobref, $job_data, STATUS_ERROR);
    return;
    }
setup_user($user_data[0]);

$resource=get_resource_data($job_data["resource"]);
$status=false;

// Process a resource upload
if($resource!==false && is_null($alternative))
	{
    if($upload_file_by_url != "")
        {
        $status = upload_file_by_url(
            $job_data["resource"],
            $job_data["extract"],
            $job_data["revert"],
            $job_data["autorotate"],
            $job_data["upload_file_by_url"]);
        }
    else
        {
        $status=upload_file($job_data["resource"], $job_data["extract"], $job_data["revert"], $job_data["autorotate"] ,"", true);
        }
	
	# update the archive status
	if(isset($job_data['archive']) && $job_data['archive'] !== '')
		{
		update_archive_status($job_data["resource"], $job_data["archive"]);
		}
	}
// Process a resource alternative upload
else if($resource !== false && !is_null($alternative) && $alternative > 0 && $extension != "")
    {
    $alt_path = get_resource_path($job_data["resource"], true, "", true, $extension, -1, 1, false, "", $alternative);

    if(is_null($file_path) && $upload_file_by_url != "")
        {
        copy($upload_file_by_url, $alt_path);;
        }
    else if(!is_null($file_path) && $file_path != "")
        {
        $result = rename($file_path, $alt_path);
        if($result === false)
            {
            job_queue_update($jobref , $job_data , STATUS_ERROR);
            }
        }
    else
        {
        job_queue_update($jobref , $job_data , STATUS_ERROR);
        }
    
    chmod($alt_path, 0777);

    global $alternative_file_previews;
    if($alternative_file_previews)
        {
        create_previews($job_data["resource"], false, $extension, false, false, $alternative);
        }

    update_disk_usage($job_data["resource"]);

    $status = true;
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
