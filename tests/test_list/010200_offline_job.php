<?php

// Validate that an offline extract_text job functions
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

$testresource=create_resource(2,0);
$settext = "This is test text from an offline job";

$resource_path = get_resource_path($testresource, true, '',true,"txt");
file_put_contents($resource_path,"This is test text from an offline job");

// Create a new offline job
$extract_text_job_data = array(
    'ref'       => $testresource,
    'extension' => "txt",
);

$offlinejob = job_queue_add('extract_text', $extract_text_job_data);

// Run the job
$alljobs = job_queue_get_jobs("extract_text",STATUS_ACTIVE,$userref);

ob_start();
job_queue_run_job($alljobs[0], true);
ob_clean();

$gettext = get_data_by_field($testresource,$extracted_text_field);

if($gettext != $settext)
    {  
    return false;
    }

return true;
