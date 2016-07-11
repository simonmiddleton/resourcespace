<?php
function HookEmuCron_copy_hitcountAddplugincronjob()
    {
    global $php_path, $config_windows, $emu_enable_script;

    if(!$emu_enable_script)
        {
        return false;
        }

    if(isset($php_path) && (file_exists("{$php_path}/php") || ($config_windows && file_exists("{$php_path}/php.exe"))))
        {
        $command = "\"{$php_path}" . ($config_windows ? '/php.exe" ' : '/php" ') . dirname(__FILE__) . '/../pages/emu_script.php';

        echo PHP_EOL . 'Running EMu script...' . PHP_EOL . 'COMMAND: "{$command}' . PHP_EOL;

        run_command($command);

        echo PHP_EOL . 'EMu script started, please check setup page to ensure script has completed.' . PHP_EOL;
        }
    else
        {
        echo 'EMu script failed - $php_path variable must be set in config.php' . PHP_EOL;
        }

    return;
    }