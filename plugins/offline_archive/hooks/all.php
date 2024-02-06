<?php

include_once __DIR__ . "/../include/offline_archive_functions.php";

function HookOffline_archiveAllAddplugincronjob()
    {
    $linebreak = PHP_SAPI == "cli" ? PHP_EOL : "<br/>"; // Cron may be called from browser
    echo $linebreak . "Offline_archive plugin: running jobs" . $linebreak;
    $errors = offline_archive_run_jobs(PHP_SAPI == "cli");
    if(is_array($errors) && PHP_SAPI == "cli")
        {
        echo "Errors: " . $linebreak . implode($linebreak,$errors) . $linebreak;
        }
    else
        {
        echo "Offline archive job complete" . $linebreak;
        }
    }
