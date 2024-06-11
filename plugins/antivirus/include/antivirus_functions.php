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
    global $lang, $antivirus_path, $antivirus_silent_options;

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

    debug("Antivirus plugin scan result for path: $file_path : $cmd_output");

    $clamdscan = substr($antivirus_path, -9) == 'clamdscan';
    $clamdscan_result = false; # Default is unsafe
    if ($clamdscan)
        {
        $scan_result_error =  stripos($cmd_output, 'error') !== false; # Clamdscan may return errors. Always report file is unsafe if scanning results in error.
        $scan_result_is_infected = substr($cmd_output, strpos($cmd_output, 'Infected files:') + 16, 1);
        if (!$scan_result_error && $scan_result_is_infected === '0')
            {
            # File is safe
            $clamdscan_result = true;
            }
        }

    if($clamdscan_result || '' == $cmd_output)
        {
        return 'SAFE';
        }

    return 'UNSAFE';
    }