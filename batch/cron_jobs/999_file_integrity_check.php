<?php

use Google\Service\Resource;

include_once __DIR__ . "/../../include/boot.php";

if (is_process_lock("file_integrity_check")) 
    {
    echo " - File integrity process lock is in place.Skipping.\n";
    return;
    }

set_process_lock("file_integrity_check");


// Check we are in a valid time period
$validtime = true;
$curhour = date('H');
if($file_integrity_verify_window[0] <= $file_integrity_verify_window[1])
    {
    // Second time is later than first or times are the same (off). Ensure time is not before the first or later than the second
    if($curhour < $file_integrity_verify_window[0] || $curhour >= $file_integrity_verify_window[1])
        {
        $validtime = false;
        }
    }
else
    {
    // First time is later than second (running through midnight). Ensure time is not before the first and after the second
    if($curhour < $file_integrity_verify_window[0] && $curhour >= $file_integrity_verify_window[1])
        {
        $validtime = false;
        }
    }

if(!$validtime)
    {
    if('cli' == PHP_SAPI)
        {
        echo " - Outside of valid time period. Set times are between " . $file_integrity_verify_window[0] . ":00 and " . $file_integrity_verify_window[1] . ":00 hours. Current hour: " . $curhour . ":00" . $LINE_END;
        }
    return;
    }

$resources = get_resources_to_validate(1);
$failures = check_resources($resources);

if(count($failures) > 0) {
    $last_integrity_check_notify = get_sysvar('last_integrity_check_notify', '1970-01-01');
    if (time()-strtotime($last_integrity_check_notify) < 24*60*60) {
        if('cli' == PHP_SAPI) {
            echo " - Skipping sending of integrity failure notifications - last sent: " . $last_integrity_check_notify . PHP_EOL;
        }
    } else {
        if('cli' == PHP_SAPI) {
            echo " - Sending summary messages to administrators" . PHP_EOL;
        }
            
    # Send notification
    $message = new ResourceSpaceUserNotification();
    $message->set_subject("lang_file_integrity_summary");
    $message->set_text("lang_file_integrity_summary_failed"); 
    $message->append_text("<br><br>");
    if(count($failures) < 1000) {
        // Show links to the resources that have failed this time.
        // If too many have failed it will still include a link to the general search for all failed resources.
        $message->append_text("<table class='ListviewStyle'");
        $n = 1;
        foreach (array_chunk($failures,200) as $arr_failed) {
            $message->append_text("<tr><td class='SingleLine'><a href='");
            $message->append_text($baseurl . "/pages/search.php?search=!list" . implode(":", $arr_failed));
            $message->append_text("'>");
            $message->append_text("lang_file_integrity_fail_search");
            $message->append_text(" ({$n})");
            $message->append_text("</a></td></tr>");
            $n++;
            }
        $message->append_text("</table>");
    }

    $message->url = $baseurl . "/pages/search.php?search=!integrityfail";

    $notify_users = get_notification_users("SYSTEM_ADMIN");
    send_user_notification($notify_users,$message);
    set_sysvar("last_integrity_check_notify",date("Y-m-d H:i:s"));
    }
}

clear_process_lock("file_integrity_check");
