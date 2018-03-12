<?php
$rse_search_notifications_plugin_root_path = dirname(__DIR__);
include_once "{$rse_search_notifications_plugin_root_path}/../../include/collections_functions.php";
include_once "{$rse_search_notifications_plugin_root_path}/include/search_notifications_functions.php";

function HookRse_search_notificationsCron_copy_hitcountAddplugincronjob()
    {
    echo "\r\n\r\nrse_search_notifications plugin: starting cron process...\r\n";

    $users = sql_query("SELECT DISTINCT owner FROM search_saved WHERE enabled = 1");

    foreach($users as $user)
        {
        $user = $user["owner"];

        setup_user(get_user($user));
        search_notification_process($user);
        }

    return true;
    }