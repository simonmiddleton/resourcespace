<?php

define('PROCESS_LOCK_UPGRADE_IN_PROGRESS','process_lock_upgrade_in_progress');
define('SYSVAR_UPGRADE_PROGRESS_OVERALL','upgrade_progress_overall');
define('SYSVAR_UPGRADE_PROGRESS_SCRIPT','upgrade_progress_script');
define('SYSVAR_CURRENT_UPGRADE_LEVEL','upgrade_system_level');

$cli=PHP_SAPI=='cli';

// if running from the command line or called somewhere within RS check to see if we need to include db.php
if ($cli || !in_array(realpath(__DIR__ . '/../include/db.php'), get_included_files()))
    {
    include_once __DIR__ . '/../include/db.php';
    }

// need to perform a get_included_files() again to guard against db.php bringing in general.php in the future
if ($cli || !in_array(realpath(__DIR__ . '/../include/general.php'), get_included_files()))
    {
    include_once __DIR__ . '/../include/general.php';
    }

// try and grab the current system upgrade level from sysvars
$current_system_upgrade_level=get_sysvar(SYSVAR_CURRENT_UPGRADE_LEVEL);

// if not set, then set to zero which will force execution of the upgrade scripts
if ($current_system_upgrade_level===false)
    {
    set_sysvar(SYSVAR_CURRENT_UPGRADE_LEVEL,0);
    $current_system_upgrade_level=0;
    }

// if the current system upgrade level is the same as that found in version.php then simply return as there is nothing to do
if ($current_system_upgrade_level>=SYSTEM_UPGRADE_LEVEL)
    {
    if ($cli)
        {
        echo "The system is up-to-date and does not require upgrading." . PHP_EOL;
        }
    return;
    }

set_time_limit(60 * 60 * 4);
$process_locks_max_seconds=60 * 60;     // allow 1 hour for the upgrade of a single script
if (is_process_lock(PROCESS_LOCK_UPGRADE_IN_PROGRESS))
    {
    $upgrade_progress_overall=get_sysvar(SYSVAR_UPGRADE_PROGRESS_OVERALL);
    $upgrade_progress_script=get_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT);
    $message="This system is currently being upgraded by another process." . PHP_EOL;
    $message.=($upgrade_progress_overall===false ? '' : $upgrade_progress_overall . PHP_EOL);
    $message.=($upgrade_progress_script===false ? '' : 'Script status: ' . $upgrade_progress_script . PHP_EOL);
    if($cli)
        {
        echo $message;
        }
    else
        {
        echo nl2br($message);
        }
    exit;
    }

// only kick of migration if on a whitelist of pages to avoid short-lived ajax callbacks from starting upgrade (and also setup.php)
if(!$cli && !in_array(basename($_SERVER['PHP_SELF']),array('home.php')))
    {
    return;
    }

// set a process lock straight away even before running any upgrade scripts to reduce chance of concurrent upgrades
set_process_lock(PROCESS_LOCK_UPGRADE_IN_PROGRESS);

// grab a list of files to run as part of the upgrade process
$new_system_version_files=array();
$files=scandir(__DIR__ .  '/../upgrade/scripts');
$total_upgrade_files=0;
for($i=$current_system_upgrade_level+1; $i<=SYSTEM_UPGRADE_LEVEL; $i++)
    {
    foreach($files as $file)
        {
        if(preg_match('/^' . str_pad($i,3,'0',STR_PAD_LEFT) . '_.*\.php/', $file))
            {
            if (!isset($new_system_version_files[$i]))
                {
                $new_system_version_files[$i]=array();
                }
            array_push($new_system_version_files[$i],$file);
            $total_upgrade_files++;
            }
        }
    }

if(count($new_system_version_files)==0)
    {
    if($cli)
        {
        echo 'No upgrade required - the system is up-to-date' . PHP_EOL;
        }
    clear_process_lock(PROCESS_LOCK_UPGRADE_IN_PROGRESS);
    return;
    }

ignore_user_abort(true);

$notification_users = get_notification_users();
$total_processed=1;
$out = "The system has been upgraded. Please wait whilst the necessary upgrade tasks are completed." . PHP_EOL;
if ($cli)
	{
	echo $out;
	}
else
	{
	echo nl2br(str_pad($out,4096));
	}
ob_flush();flush();

foreach($new_system_version_files as $new_system_version=>$files)
    {
    $out="Performing upgrade tasks for system version: {$new_system_version}" . PHP_EOL;
    if ($cli)
        {
	echo $out;
        }
    else
	{
	echo nl2br(str_pad($out,4096));
	}
    ob_flush();flush();

    foreach ($files as $file)
        {
        set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Started');
        $upgrade_progress_overall="Running {$total_processed} out of {$total_upgrade_files} (" .
        round (($total_processed / $total_upgrade_files) * 100,2) . "%) {$file}";
        set_sysvar(SYSVAR_UPGRADE_PROGRESS_OVERALL,$upgrade_progress_overall);
        if ($cli)
		{
		echo $upgrade_progress_overall;
		}
	else
		{
		echo nl2br(str_pad($upgrade_progress_overall,4096));
		}
        ob_flush();flush();

        if(!is_process_lock(PROCESS_LOCK_UPGRADE_IN_PROGRESS))
            {
            set_process_lock(PROCESS_LOCK_UPGRADE_IN_PROGRESS);
            }
        try
            {
            include_once(__DIR__ . '/scripts/' . $file);
            $message = 'version ' . $new_system_version . ' upgrade script: ' . $file . ' completed OK.';
            log_activity($message, LOG_CODE_SYSTEM, $new_system_version, 'sysvars', 'version', null, null, null, null, false);
            }
        catch (Exception $e)
            {
            if ($cli)
                {
                echo "Upgrade script failed." . PHP_EOL;
                echo $e->getMessage() . PHP_EOL;
                }

            $message = 'version ' . $new_system_version . ' upgrade script: ' . $file . ' failed.';
            log_activity($message, LOG_CODE_SYSTEM, $current_system_upgrade_level, 'sysvars', 'version', null, null, null, null, false);
            exit;
            }

        set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Completed');
        $total_processed++;
        clear_process_lock(PROCESS_LOCK_UPGRADE_IN_PROGRESS);
        }
    set_sysvar(SYSVAR_CURRENT_UPGRADE_LEVEL, $new_system_version);
    }

set_sysvar(SYSVAR_UPGRADE_PROGRESS_OVERALL,'Completed');

$message = 'Successfully upgraded to system version: ' . $new_system_version . '.';
log_activity($message, LOG_CODE_SYSTEM, $new_system_version, 'sysvars', 'version', null, null, $current_system_upgrade_level, null, false);
            
if($cli)
    	{
    	echo "Upgrade complete" . PHP_EOL;
    	}
else
	{
	echo PHP_EOL . "Upgrade complete. Please wait for redirect<br />
	<script src=\"" . $baseurl . "/lib/js/jquery-3.3.1.min.js?css_reload_key=" . $css_reload_key . "\"></script>
	<script>
	jQuery(document).ready(function () {
		setTimeout(function () {
			window.location.href = '" . $baseurl . "'
			}, 5000);
		});
	</script>";
	exit();
	}