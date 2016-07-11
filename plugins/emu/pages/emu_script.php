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

$debug_log           = false;
$emu_log_text        = '';
$emu_updated_records = array();
$emu_errors          = array();

if('' != $emu_email_notify)
    {
    $email_notify = $emu_email_notify;
    }

$emu_query_offset = 100;
$emu_test_count   = 999999999;
if($emu_test_mode)
    {
    $emu_test_count = 500;
    }


// Check if we need to clear locks or need help using the script
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

// Check when this script was last run - do it now in case of permanent process locks
$emu_script_last_ran = '';
if(!check_script_last_ran($emu_script_last_ran))
    {
    $emu_script_failed_subject = ($emu_test_mode ? 'TESTING MODE: ' : '') . 'EMu Import script - WARNING';
    // send_mail($email_notify, $emu_script_failed_subject, "WARNING: The EMu Import Script has not completed since '{$emu_script_last_ran}'.\r\n\r\nYou can safely ignore this warning only if you subsequently received notification of a successful script completion.", $email_from);
    }

// Check for a process lock
if(is_process_lock('emu_import')) 
    {
    echo 'EMu script lock is in place. Deferring.' . PHP_EOL . 'To clear the lock after a failed run use --clearlock flag.' . PHP_EOL;

    $emu_script_failed_subject = ($emu_test_mode ? 'TESTING MODE: ' : '') . 'EMu Import script - FAILED';
    send_mail($email_notify, $emu_script_failed_subject, "The EMu script failed to run because a process lock was in place. This indicates that the previous run did not complete.\r\n\r\nIf you need to clear the lock after a failed run, run the script as follows:\r\n\r\nphp emu_script.php --clearlock\r\n", $email_from);
    exit();
    }
// set_process_lock('emu_import');



$emu_script_start_time = microtime(true);
$emu_resources         = get_emu_resources();
$count_emu_resources   = count($emu_resources);
$emu_rs_mappings       = unserialize(base64_decode($emu_rs_saved_mappings));
$emu_pointer           = 0;


// Init script logging (if set)
if('' != trim($emu_log_directory))
    {
    if(!is_dir($emu_log_directory))
        {
        @mkdir($emu_log_directory, 0755, true);

        if(!is_dir($emu_log_directory))
            {
            echo 'Unable to create log directory: "' . htmlspecialchars($emu_log_directory) . '"' . PHP_EOL;
            }
        }
    else
        {
        // Valid log directory

        // Clean up old files
        $iterator        = new DirectoryIterator($emu_log_directory);
        $log_expiry_time = $emu_script_start_time - ((5 * intval($emu_script_failure_notify_days)) * 24 * 60 * 60) ;

        foreach($iterator as $file_info)
            {
            if(!$file_info->isFile())
                {
                continue;
                }

            $filename = $file_info->getFilename();

            // Delete log file if it is older than its expiration time
            if('emu_script_log' == substr($filename, 0, 14) && $file_info->getMTime() < $log_expiry_time)
                {
                @unlink($file_info->getPathName());
                }
            }

        // New log file
        $emu_log_file = fopen($emu_log_directory . DIRECTORY_SEPARATOR . 'emu_script_log_' . date('Y_m_d-H_i') . '.log', 'ab');
        }
    }


$emu_query_ids = array();

while($emu_pointer < $count_emu_resources && $emu_pointer < $emu_test_count)
    {
    unset($emu_query_ids);
    $emu_query_ids = array();

    for($t = $emu_pointer; $t < ($emu_pointer + $emu_query_offset) && (($emu_pointer + $t) < $emu_test_count) && $t < $count_emu_resources; $t++)
        {
        if('' != $emu_resources[$t]['object_irn'] && is_numeric($emu_resources[$t]['object_irn']) && false === strpos($emu_resources[$t]['object_irn'], '.'))
            {
            $emu_query_ids[] = $emu_resources[$t]['object_irn'];
            }
        else
            {
            // Invalid IRN
            $emu_log_message = "Invalid EMu data stored in ResourceSpace: {$emu_resources[$t]['object_irn']}";
            $emu_errors[$emu_resources[$t]['resource']] = $emu_log_message;

            fwrite($emu_log_file, $emu_log_message);
            }
        }

    fwrite($emu_log_file, "Retrieving data from EMu database\r\n");
    fwrite($emu_log_file, "EMu query IRNs:\r\n" . print_r($emu_query_ids, true));

    $emu_records = get_emu_data($emu_api_server, $emu_api_server_port, $emu_query_ids, $emu_rs_mappings);
    echo '<pre>';print_r($emu_records);echo '</pre>';die('<br>You died in ' . __FILE__ . ' @' . __LINE__);
        

    if(!is_array($emu_records) || 0 === count($emu_records))
        {
        $emu_log_message =  'No EMu data received, continuing...';

        echo $emu_log_message . PHP_EOL;
        fwrite($emu_log_file, "{$emu_log_message}\r\n");

        $emu_pointer = $emu_pointer + $emu_query_offset;
        
        continue;
        }

 
    // Update pointer and go onto next set of resources
    $emu_pointer = $emu_pointer + $emu_query_offset;

    }

$emu_log_text .= sprintf("EMu Script completed in %01.2f seconds.\r\n", microtime(true) - $emu_script_start_time) . "\r\n";




echo PHP_EOL . 'END OF SCRIPT';