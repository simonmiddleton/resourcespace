<?php
/**
* Scan file using current setup AV
* 
* @uses debug() 
* 
* @param string  $file_path Physical path of the file to be scanned
* 
* @return string Returns SAFE for safe files or UNSAFE otherwise
*/
function antivirus_scan($file_path)
    {
    global $antivirus_path, $antivirus_silent_options;

    if(!isset($antivirus_path) || trim($antivirus_path) == '')
        {
        trigger_error($lang['antivirus_av_not_setup_error']);
        }

    $file_path = escapeshellarg($file_path);
    $av_path   = escapeshellarg($antivirus_path);

    $av_options         = explode(' ', $antivirus_silent_options);
    $escaped_av_options = array();
    foreach($av_options as $av_option)
        {
        $escaped_av_options[] = escapeshellarg($av_option);
        }
    $av_options = implode(' ', $escaped_av_options);

    $cmd = "{$av_path} {$av_options} {$file_path}";

    debug("ANTIVIRUS: Running command {$cmd}");

    $cmd_output = run_command($cmd);

    if('' == $cmd_output)
        {
        return 'SAFE';
        }

    return 'UNSAFE';
    }