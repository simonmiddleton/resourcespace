<?php

function HookResourceconnectLogUserdisplay($log)
    {
    // Better rendering for ResourceConnect rows in log.
    if (strpos($log["access_key"],"-")===false) {return false;}
    $s=explode("-",$log["access_key"]);

    echo "<strong>" . htmlspecialchars($s[0]) . "</strong> remotely accessing via " . htmlspecialchars($log["fullname"]);

    return true;
    }
