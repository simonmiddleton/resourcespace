<?php
function HookAntivirusAllAfterpluploadfile($resource_id, $file_extension)
    {
    global $lang, $resource_deletion_state, $antivirus_action, $antivirus_quarantine_state;

    // This will show both as a styledalert and as a message in the log
    $plupload_json_response = array(
        'jsonrpc' => '2.0',
        'error'   => array(
            'code'    => $lang['antivirus_label'],
            'message' => ''
        ),
        'id'      => 'id'
    );

    $resource_path = get_resource_path($resource_id, true, '', false, $file_extension);
    $scan_output   = antivirus_scan($resource_path);

    if('UNSAFE' === $scan_output)
        {
        switch($antivirus_action)
            {
            case ANTIVIRUS_ACTION_DELETE:
                // Delete permanently
                delete_resource($resource_id);
                delete_resource($resource_id);

                $plupload_json_response['error']['message'] = str_replace(
                    '[%resource_id%]',
                    $resource_id,
                    $lang['antivirus_deleting_file']
                );
                break;

            case ANTIVIRUS_ACTION_QUARANTINE:
            default:
                $to_state      = $antivirus_quarantine_state;
                $resource_data = get_resource_data($resource_id);

                update_archive_status($resource_id, $to_state, $resource_data['archive']);

                $plupload_json_response['error']['message'] = str_replace(
                    array('[%resource_id%]', '[%archive_state%]'),
                    array($resource_id, $to_state),
                    $lang['antivirus_moving_file']
                );
                break;
            }

        header('Content-Type: application/json');
        die(json_encode($plupload_json_response));
        }

    return true;
    }