<?php
include_once dirname(__FILE__) . "/../../include/db.php";
include_once dirname(__FILE__) . "/../../include/general.php";
include_once dirname(__FILE__) . "/../../include/reporting_functions.php";
include_once dirname(__FILE__) . "/../../include/resource_functions.php";
include_once dirname(__FILE__) . "/../../include/search_functions.php";
set_time_limit(0);

// This MUST only be done by having access to the server
if(PHP_SAPI == 'cli')
    {
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
    }

if($offline_job_queue)
    {
    # Run offline jobs (may be useful in the event a cron job hasn't yet been created for the new offline_jobs.php)
    $offlinejobs=job_queue_get_jobs("", STATUS_ACTIVE);

    foreach($offlinejobs as $offlinejob)
        {
        $clear_job_process_lock = false;
        if(PHP_SAPI == 'cli' && $clear_lock && in_array($offlinejob['ref'], $jobs))
            {
            $clear_job_process_lock = true;
            }

        job_queue_run_job($offlinejob, $clear_job_process_lock);	
        }
    }
