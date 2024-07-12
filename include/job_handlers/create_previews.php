<?php
/*
Job handler for creating previews for a resource/ alternative

Requires the following job data:-
$job_data['resource'] - Resource ID
$job_data['thumbonly'] - Optional
$job_data['extension'] - Optional
$job_data['previewonly'] - Optional
$job_data['previewbased'] - Optional
$job_data['alternative'] - Optional
$job_data['ignoremaxsize'] - Optional
$job_data['ingested'] - Optional
$job_data['checksum_required'] - Optional 
*/
include_once __DIR__ . '/../image_processing.php';

global $lang, $baseurl, $offline_job_delete_completed, $baseurl_short;

$resource          = $job_data["resource"]??0;
$thumbonly         = $job_data["thumbonly"]??false;
$extension         = $job_data["extension"]??'jpg';
$previewonly       = $job_data["previewonly"]??false;
$previewbased      = $job_data["previewbased"]??false;
$alternative       = $job_data["alternative"]??-1;
$ignoremaxsize     = $job_data["ignoremaxsize"]??true;
$ingested          = $job_data["ingested"]??false;
$checksum_required = $job_data["checksum_required"]??true;

// For messages
$url = isset($job_data['resource']) ? "{$baseurl_short}?r={$job_data['resource']}": '';

if($resource > 0 && create_previews($resource, $thumbonly, $extension, $previewonly, $previewbased, $alternative, $ignoremaxsize, $ingested, $checksum_required))
    {
    // Success - no message required
    # Update disk usage
    update_disk_usage($resource);
    }
else
    {
    // fail
    job_queue_update($jobref, $job_data, STATUS_ERROR);
    $create_previews_job_failure_text = str_replace('%RESOURCE', $resource, $lang['jq_create_previews_failure_text']);
    $message = $job_failure_text != '' ? $job_failure_text : $create_previews_job_failure_text;

    message_add($job['user'], $message, $url, 0);
    }

unset(
    $resource,
    $thumbonly,
    $extension,
    $previewonly,
    $previewbased,
    $alternative,
    $ignoremaxsize,
    $ingested,
    $checksum_required
);

if($offline_job_delete_completed)
    {
    job_queue_delete($jobref);
    }
else
    {
    job_queue_update($jobref, $job_data, STATUS_COMPLETE);
    }