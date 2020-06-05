<?php

if (substr(php_sapi_name(), 0, 3) != 'cli')
	{
	exit;
	}

include_once dirname(__FILE__) . "/../../../include/db.php";

include_once dirname(__FILE__) . "/../include/search_notifications_functions.php";

define('THIS_PROCESS_LOCK','watchedsearchescron');

if (is_process_lock(THIS_PROCESS_LOCK))
	{
	echo "Process lock in place";
	return;
	}

set_process_lock(THIS_PROCESS_LOCK);

$users=sql_query("SELECT DISTINCT owner FROM search_saved WHERE enabled=1");

foreach ($users as $user)
	{
	$user=$user['owner'];
	setup_user(get_user($user));
	search_notification_process($user);
	}

clear_process_lock(THIS_PROCESS_LOCK);
