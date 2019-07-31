<?php

function HookResourceconnectLogUserdisplay($log)
    {
    // Better rendering for ResourceConnect rows in log.
    if (strpos($log["access_key"],"-")===false) {return false;}
    $s=explode("-",$log["access_key"]);

    echo "'" . $s[0] . "' remotely accessing via " . $log["fullname"];

    return true;
    }