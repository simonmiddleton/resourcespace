<?php
$rse_search_notifications_plugin_root_path = dirname(__DIR__);
include_once "{$rse_search_notifications_plugin_root_path}/include/search_notifications_functions.php";

function HookRse_search_notificationsCron_copy_hitcountAddplugincronjob()
    {
    echo "\r\n\r\nrse_search_notifications plugin: starting cron process...\r\n";

    $users = ps_query("SELECT DISTINCT owner FROM search_saved WHERE enabled = 1", array());

    foreach($users as $user)
        {
        $user = $user["owner"];
        $userdata = get_user($user);
        if(!$userdata)
            {
            debug("rse_search_notifications: no user found for search owner id: " . $user);
            continue;    
            }
        setup_user($userdata);
        $GLOBALS['userdata'][0] = $userdata;
        search_notification_process($user);
        }

    return true;
    }