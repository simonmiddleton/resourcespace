<?php
include_once dirname(__FILE__) . "/../include/db.php";

include_once dirname(__FILE__) . "/../include/reporting_functions.php";
include_once dirname(__FILE__) . "/../include/action_functions.php";
include_once dirname(__FILE__) . "/../include/request_functions.php";

$LINE_END = ('cli' == PHP_SAPI) ? PHP_EOL : "<br>";
set_time_limit($cron_job_time_limit);
ob_end_flush();
ob_implicit_flush();
ob_start();
echo "Starting cron process..." . $LINE_END;

# Get last cron date
$lastcron       = get_sysvar('last_cron', '1970-01-01');
$lastcrontime   = strtotime($lastcron);
$sincelast      = time() - $lastcrontime;

// grab a list of files to run as part of the upgrade process
$new_system_version_files=array();
$files=scandir(__DIR__ .  '/cron_jobs');
$total_upgrade_files=0;
for($i=0; $i<=999; $i++)
    {
    foreach($files as $file)
        {
        if(preg_match('/^' . str_pad($i,3,'0',STR_PAD_LEFT) . '_.*\.php/', $file))
            {
            echo "Executing job: " . $file  . $LINE_END;flush();ob_flush();
            include __DIR__ .  '/cron_jobs/' . $file;
            }
        }
    }

    
# Allow plugins to add their own cron jobs.
hook("cron");

echo PHP_EOL . "All tasks complete" .  $LINE_END;

# Update last cron date
set_sysvar("last_cron",date("Y-m-d H:i:s"));






