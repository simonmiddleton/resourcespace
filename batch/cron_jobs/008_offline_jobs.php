<?php

if ($offline_job_queue)
    {
    // May be required if there has not yet been a cron task set up for the offfline_jobs.php
    include dirname(__FILE__) . "/../../pages/tools/offline_jobs.php";
    }
