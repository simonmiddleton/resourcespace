<?php
if('cli' != php_sapi_name())
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
    $emu_script_failed_subject = ($tms_link_test_mode ? 'TESTING MODE ' : '') . 'EMu Import script - WARNING';
    send_mail($email_notify, $emu_script_failed_subject, "WARNING: The EMu Import Script has not completed since '{$emu_script_last_ran}'.\r\n\r\nYou can safely ignore this warning only if you subsequently received notification of a successful script completion.", $email_from);
    }



echo PHP_EOL . 'END OF SCRIPT';