<?php
include_once dirname(__FILE__) . "/../../include/db.php";
command_line_only();


set_time_limit(0);

$offline_job_short_options = 'chm:j:';

$offline_job_cli_long_options  = array(
    'clear-lock',
    'job:',
    'max-jobs:',
    'help',
);
$clear_lock = false;
$jobs = array();
$max_jobs = 10;

$help_text = "NAME
    offline_jobs.php - Process offline jobs

SYNOPSIS
    php /path/to/pages/tools/tools/offline_jobs.php [OPTIONS...]

DESCRIPTION
    Used to run ResourceSpace offline jobs. Normally trigggered by a cron entry/scheduled task.

OPTIONS SUMMARY

    -h, --help          Display this help text and exit
    -j, --job           Specify the job ID to run
    -c, --clear-lock    Clear any existing process locks for jobs
    -m, --max-jobs      Maximum number of concurrent jobs that can be running (integer)

EXAMPLES
    php offline_jobs.php --clear-lock --job 256 
    php offline_jobs.php --max-jobs 5
";

// CLI options check
$options = getopt($offline_job_short_options, $offline_job_cli_long_options);
//exit(print_r($options));
foreach($options as $option_name => $option_value)
    {
    if(in_array($option_name, ['h', 'help']))
        {
        fwrite(STDOUT, $help_text . PHP_EOL);
        exit(0);
        }

    if(in_array($option_name, ['c', 'clear-lock']))
        {
        $clear_lock = true;
        }

    // IMPORTANT: job can be an integer when option is used once or an array when options is used multiple times
    // (e.g. php offline_jobs.php --clear-lock --job=2 --job=10 )
    if(in_array($option_name, ['j', 'job']))
        {
        if(!is_array($option_value))
            {
            $jobs[] = (int)$option_value;
            continue;
            }

        $jobs = $option_value;
        }

    if(in_array($option_name, ['m', 'max-jobs']))
        {
        $max_jobs =  (int)$option_value;
        }
    }

if($offline_job_queue)
    {
    // Mark any jobs that are still marked as in progress but have an old process lock as failed
    $runningjobs=job_queue_get_jobs("",STATUS_INPROGRESS,"","","ref", "ASC");
    $jobcount = count($runningjobs);
    foreach($runningjobs as $runningjob)
        {
        $runningjob_data = json_decode($runningjob["job_data"],true);
        if(!is_process_lock("job_" . $runningjob["ref"]))
            {
            // No current lock in place. Check for presence of an old lock and mark as failed
            $saved_process_lock_max_seconds = $process_locks_max_seconds;
            $process_locks_max_seconds = 9999999;
            if(is_process_lock("job_" . $runningjob["ref"]))
                {
                echo "Job is in progress (ID: " . $runningjob["ref"] . ") but has exceeded maximum lock time - marking as failed\n";
                job_queue_update($runningjob["ref"],$runningjob_data,STATUS_ERROR);
                clear_process_lock("job_" . $runningjob["ref"]);
                }
            $process_locks_max_seconds = $saved_process_lock_max_seconds;
            $jobcount--;
            }
        }
    
    if($jobcount >= $max_jobs)
        {
        exit("There are currently " . $jobcount . " jobs in progress. Exiting\n");
        }

    $offlinejobs=job_queue_get_jobs("", STATUS_ACTIVE, "","","priority,ref", "ASC");
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
