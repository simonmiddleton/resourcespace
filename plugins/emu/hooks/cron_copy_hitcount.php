<?php
include_once dirname(__FILE__) . '/../include/emu_functions.php';

function HookEmuCron_copy_hitcountAddplugincronjob()
    {
    global $lang, $php_path, $config_windows, $emu_enable_script, $emu_interval_run, $emu_script_mode;

    if(!$emu_enable_script)
        {
        return false;
        }

    // Make sure we run this at an interval specified by Admins, otherwise run this every time cron jobs run
    if('' != $emu_interval_run)
        {
        $emu_script_last_ran        = '';
        $check_script_last_ran      = check_script_last_ran($emu_script_last_ran);
        $emu_script_future_run_date = new DateTime();

        // Use last date the script was run, if available
        if($check_script_last_ran || (!$check_script_last_ran && $lang['status-never'] != $emu_script_last_ran))
            {
            $emu_script_future_run_date = DateTime::createFromFormat('l F jS Y @ H:m:s', $emu_script_last_ran);
            }

        $emu_script_future_run_date->modify($emu_interval_run);

        // Calculate difference between dates and establish whether it should run or not
        $date_diff = $emu_script_future_run_date->diff(new DateTime());

        if(0 < $date_diff->days)
            {
            return false;
            }
        }

    if(!(isset($php_path) && (file_exists("{$php_path}/php") || ($config_windows && file_exists("{$php_path}/php.exe")))))
        {
        echo 'EMu script failed - $php_path variable must be set in config.php' . PHP_EOL;
        return false;
        }

    if(EMU_SCRIPT_MODE_IMPORT == $emu_script_mode)
        {
        $script_file = dirname(__FILE__) . '/../pages/emu_script.php';

        if(!file_exists($script_file))
            {
            echo PHP_EOL . 'EMu script "' . $script_file . '" not found!' . PHP_EOL;
            return false;
            }

        $command = "\"{$php_path}" . ($config_windows ? '/php.exe" ' : '/php" ') . $script_file;

        echo PHP_EOL . 'Running EMu script...' . PHP_EOL . "COMMAND: '{$command}'" . PHP_EOL;

        run_command($command);

        echo PHP_EOL . 'EMu script started, please check setup page to ensure script has completed.' . PHP_EOL;
        }

    if(EMU_SCRIPT_MODE_SYNC == $emu_script_mode)
        {
        $script_file = dirname(__FILE__) . '/../pages/emu_sync_script.php';

        if(!file_exists($script_file))
            {
            echo PHP_EOL . 'EMu script "' . $script_file . '" not found!' . PHP_EOL;
            return false;
            }

        $command = "\"{$php_path}" . ($config_windows ? '/php.exe" ' : '/php" ') . $script_file;

        echo PHP_EOL . 'Running EMu script...' . PHP_EOL . "COMMAND: '{$command}'";

        run_command($command);

        echo PHP_EOL . 'EMu script started, please check setup page to ensure script has completed.';
        }

    return true;
    }