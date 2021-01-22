<?php
include_once dirname(__FILE__) . "/../../include/db.php";
include_once dirname(__FILE__) . "/../../include/reporting_functions.php";

// This MUST only be done by having access to the server
if(PHP_SAPI != 'cli')
    {
    exit("This script cannot be run from a browser");
    }

set_time_limit(0);
$offline_job_cli_long_options  = array(
    'clear-lock',
    'job:'
);
$clear_lock = false;
$jobs = array();

// CLI options check
foreach(getopt('', $offline_job_cli_long_options) as $option_name => $option_value)
    {
    if($option_name == 'clear-lock')
        {
        $clear_lock = true;
        }

    // IMPORTANT: job can be an integer when option is used once or an array when options is used multiple times
    // (e.g. php offline_jobs.php --clear-lock --job=2 --job=10)
    if($option_name == 'job')
        {
        if(!is_array($option_value))
            {
            $jobs[] = $option_value;

            continue;
            }

        $jobs = $option_value;
        }
    }

if($offline_job_queue)
    {
    // Mark any jobs that are still marked as in progress but have an old process lock as failed
    $runningjobs=job_queue_get_jobs("",STATUS_INPROGRESS,"","","ref", "ASC");
    foreach($runningjobs as $runningjob)
        {
        $runningjob_data = json_decode($runningjob["job_data"],true);
        job_queue_update($runningjob["ref"],$runningjob_data,STATUS_ERROR);
        if(!is_process_lock("job_" . $runningjob["ref"]))
            {
            // No current lock in place. Check for presence of an old lock and mark as failed
            $saved_process_lock_max_seconds = $process_locks_max_seconds;
            $process_locks_max_seconds = 9999999;
            if(is_process_lock("job_" . $runningjob["ref"]))
                {
                echo "Job is in progress but has exceeded maximum lock time - marking as failed\n";
                job_queue_update($runningjob["ref"],$runningjob_data,STATUS_ERROR);
                }
            $process_locks_max_seconds = $saved_process_lock_max_seconds;
            }
        }

    $offlinejobs=job_queue_get_jobs("", STATUS_ACTIVE, "","","ref", "ASC");

    foreach($offlinejobs as $offlinejob)
        {
        if(!empty($jobs) && !in_array($offlinejob["ref"], $jobs))
            {
            continue;
            }

        $readonly_jobs = array(
            "collection_download",
            "create_download_file",
            "csv_metadata_export",
            );

        // Only run essential and non-data affecting jobs in read only mode
        if($system_read_only && !in_array($offlinejob["type"],$readonly_jobs))
            {
            continue;
            }

        $clear_job_process_lock = false;
        if(PHP_SAPI == 'cli' && $clear_lock && in_array($offlinejob['ref'], $jobs))
            {
            $clear_job_process_lock = true;
            }
        if($offlinejob["start_date"] > date('Y-m-d H:i:s',time()))
            {
            continue;
            }
        job_queue_run_job($offlinejob, $clear_job_process_lock);	
        }
    }
