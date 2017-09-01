<?php
function HookAntivirusAllAfterpluploadfile($resource_id, $file_extension)
    {
    global $lang, $resource_deletion_state, $antivirus_action, $antivirus_silent_options, $antivirus_quarantine_state;

    $resource_path = get_resource_path($resource_id, true, '', false, $file_extension);

    echo PHP_EOL . $lang['antivirus_scanning_file'];

    $scan_output = antivirus_scan($resource_path, $antivirus_silent_options);

    echo PHP_EOL . $scan_output . PHP_EOL;

    if('UNSAFE' === $scan_output)
        {
        switch($antivirus_action)
            {
            case ANTIVIRUS_ACTION_DELETE:
                // Delete permanently
                delete_resource($resource_id);
                delete_resource($resource_id);
                echo $lang['antivirus_deleting_file'] . PHP_EOL;
                break;

            case ANTIVIRUS_ACTION_QUARANTINE:
            default:
                $to_state      = $antivirus_quarantine_state;
                $resource_data = get_resource_data($resource_id);

                update_archive_status($resource_id, $to_state, $resource_data['archive']);
                echo "{$lang['antivirus_moving_file']} {$to_state}" . PHP_EOL;
                break;
            }

        exit();
        }

    return true;
    }