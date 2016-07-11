<?php
$php_sapi_name = php_sapi_name();

if('cli' != $php_sapi_name)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied');
    }

include dirname(__FILE__) . '/../../../include/db.php';
include_once dirname(__FILE__) . '/../../../include/general.php';
include_once dirname(__FILE__) . '/../../../include/resource_functions.php';
include_once dirname(__FILE__) . '/../include/emu_functions.php';
include_once dirname(__FILE__) . '/../include/emu_api.php';


// Init
ob_end_clean();
set_time_limit(24 * 60 * 60);

$debug_log = false;

if('' != $emu_email_notify)
    {
    $email_notify = $emu_email_notify;
    }

// Check when this script was last run - do it now in case of permanent process locks
$emu_script_last_ran = '';
if(!check_script_last_ran($emu_script_last_ran))
    {
    $emu_script_failed_subject = ($emu_test_mode ? 'TESTING MODE: ' : '') . 'EMu Import script - WARNING';
    send_mail($email_notify, $emu_script_failed_subject, "WARNING: The EMu Import Script has not completed since '{$emu_script_last_ran}'.\r\n\r\nYou can safely ignore this warning only if you subsequently received notification of a successful script completion.", $email_from);
    }

if('cli' == $php_sapi_name && 2 == $argc)
    {
    if(in_array($argv[1], array('--help', '-help', '-h', '-?')))
        {
        echo 'To clear the lock after a failed run, pass in "--clearlock", "-clearlock", "-c" or "--c"' . PHP_EOL;
        exit('Bye!');
        }
    else if(in_array($argv[1], array('--clearlock', '-clearlock', '-c', '--c')))
        {
        if(is_process_lock('emu_import'))
            {
            clear_process_lock('emu_import');
            }
        }
    else
        {
        exit("Unknown argv: {$argv[1]}" . PHP_EOL);
        }
    }

# Check for a process lock
if(is_process_lock('emu_import')) 
    {
    echo 'EMu script lock is in place. Deferring.' . PHP_EOL . 'To clear the lock after a failed run use --clearlock flag.' . PHP_EOL;

    $emu_script_failed_subject = ($emu_test_mode ? 'TESTING MODE: ' : '') . 'EMu Import script - FAILED';
    send_mail($email_notify, $emu_script_failed_subject, "The EMu script failed to run because a process lock was in place. This indicates that the previous run did not complete.\r\n\r\nIf you need to clear the lock after a failed run, run the script as follows:\r\n\r\nphp emu_script.php --clearlock\r\n", $email_from);
    exit();
    }
set_process_lock('emu_import');









echo PHP_EOL . 'END OF SCRIPT';