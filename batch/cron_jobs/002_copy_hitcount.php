<?php

// Don't do this more than once a day
if($sincelast > 23*60*60)
    {
    copy_hitcount_to_live();    
    }
else
    {
    echo " - Skipping copy_hitcount_to_live  - cron already run<br />\n";
    }
