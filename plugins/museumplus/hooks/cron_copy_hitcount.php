<?php
// TODO: remove the error control operator. PHP 8 compatibility
function HookMuseumplusCron_copy_hitcountAddplugincronjob()
    {
    global $lang, $museumplus_enable_script;

    if(!$museumplus_enable_script)
        {
        return false;
        }

    // Make sure we run this at an interval specified by Admins, otherwise run this every time cron jobs run
    global $museumplus_interval_run, $museumplus_script_failure_notify_days;
    if('' != $museumplus_interval_run)
        {
        $museumplus_script_last_ran = '';
        $check_script_last_ran = check_script_last_ran(MPLUS_LAST_IMPORT, $museumplus_script_failure_notify_days, $museumplus_script_last_ran);
        $mplus_script_future_run_date = new DateTime();

        // Use last date the script was run, if available
        if($check_script_last_ran || (!$check_script_last_ran && $lang['status-never'] != $museumplus_script_last_ran))
            {
            $mplus_script_future_run_date = DateTime::createFromFormat('l F jS Y @ H:m:s', $museumplus_script_last_ran);
            }

        $mplus_script_future_run_date->modify($museumplus_interval_run);

        // Calculate difference between dates and establish whether it should run or not
        $date_diff = $mplus_script_future_run_date->diff(new DateTime());

        if(0 < $date_diff->days)
            {
            return false;
            }
        }

    $cli = (PHP_SAPI == 'cli');
    echo nl2br(PHP_EOL, !$cli); # Adding an extra new line to separate cron_copy_hitcount items from the plugin item

    $php_fullpath = get_utility_path("php");
    if($php_fullpath === false)
        {
        echo nl2br(PHP_EOL . 'MuseumPlus script failed - $php_fullpath variable must be set in config.php', !$cli);
        return false;
        }

    // Deal with log directory now so that scripts can just use it if they need to
    global $museumplus_log_directory;
    if('' != trim($museumplus_log_directory))
        {
        if(!is_dir($museumplus_log_directory))
            {
            @mkdir($museumplus_log_directory, 0755, true);

            if(!is_dir($museumplus_log_directory))
                {
                echo nl2br(PHP_EOL . 'MuseumPlus: Unable to create log directory: "' . htmlspecialchars($museumplus_log_directory) . '"', !$cli);
                return false;
                }
            }

        // Clean up old files
        $dir_iterator    = new DirectoryIterator($museumplus_log_directory);
        // @todo: potentially we can have the expiry time multiplier as a variable
        $log_expiry_time = microtime(true) - ((5 * intval($museumplus_script_failure_notify_days)) * 24 * 60 * 60) ;

        foreach($dir_iterator as $file_info)
            {
            if(!$file_info->isFile())
                {
                continue;
                }

            $filename = $file_info->getFilename();

            // Delete log file if it is older than its expiration time
            if('mplus_script_log' == substr($filename, 0, 16) && $file_info->getMTime() < $log_expiry_time)
                {
                @unlink($file_info->getPathName());
                }
            }
        }

    $script_file = dirname(__FILE__) . '/../pages/museumplus_script.php';
    if(!file_exists($script_file))
        {
        echo nl2br(PHP_EOL . "MuseumPlus: script '{$script_file}' not found!", !$cli);
        return false;
        }

    $command = "{$php_fullpath} {$script_file}";

    echo nl2br(PHP_EOL . 'Running MuseumPlus script...' . PHP_EOL . "COMMAND: '{$command}'", !$cli);
    run_command($command);
    echo nl2br(PHP_EOL . 'MuseumPlus script started, please check setup page to ensure script has completed.', !$cli);

    return true;
    }