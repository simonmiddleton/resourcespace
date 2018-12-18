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

global $lang, $baseurl, $offline_job_delete_completed;

// Defaults for create_previews
$resource          = 0;
$thumbonly         = false;
$extension         = 'jpg';
$previewonly       = false;
$previewbased      = false;
$alternative       = -1;
$ignoremaxsize     = true;
$ingested          = false;
$checksum_required = true;

// For messages
$url = isset($job_data['resource']) ? "{$baseurl}/?r={$job_data['resource']}": '';

// Overwrite defaults
foreach($job_data as $arg => $value)
    {
    $$arg = $value;
    }

if($resource > 0 && create_previews($resource, $thumbonly, $extension, $previewonly, $previewbased, $alternative, $ignoremaxsize, $ingested, $checksum_required))
    {
    // success
    $create_previews_job_success_text = str_replace('%RESOURCE', $resource, $lang['jq_create_previews_success_text']);
    $message = $job_success_text != '' ? $job_success_text : $create_previews_job_success_text;

    message_add($job['user'], $message, $url, 0);
    }
else
    {
    // fail
    job_queue_update($jobref, $job_data, STATUS_ERROR);
    $create_previews_job_failure_text = str_replace('%RESOURCE', $resource, $lang['jq_create_previews_failure_text']);
    $message = $job_failure_text != '' ? $job_failure_text : $create_previews_job_failure_text;

    message_add($job['user'], $message, $url, 0);
    }

// Clean after job handler
foreach($job_data as $arg => $value)
    {
    unset($$arg);
    }

if($offline_job_delete_completed)
    {
    job_queue_delete($jobref);
    }
else
    {
    job_queue_update($jobref, $job_data, STATUS_COMPLETE);
    }