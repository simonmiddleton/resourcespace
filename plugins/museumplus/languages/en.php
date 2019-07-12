<?php
$lang['museumplus_configuration'] = 'MuseumPlus Configuration';

$lang['museumplus_api_settings_header'] = 'API details';
$lang['museumplus_host'] = 'Host';
$lang['museumplus_application'] = 'Application name';
$lang['museumplus_api_user'] = $lang['user'];
$lang['museumplus_api_pass'] = $lang['password'];

$lang['museumplus_RS_settings_header'] = 'ResourceSpace settings';
$lang['museumplus_mpid_field'] = 'Metadata field used to store the MuseumPlus identifier (MpID)';
$lang['museumplus_resource_types'] = 'Select resource types which can be synchronised with MuseumPlus';
$lang['museumplus_object_details_title'] = 'MuseumPlus details';
$lang['museumplus_search_match_field'] = 'The field that stores the MpID on MuseumPlus side';
$lang['museumplus_script_header'] = 'Script settings';
$lang['museumplus_last_run_date'] = '
<div class="Question">
    <label>
        <strong>Script last run</strong>
    </label>
    <input name="script_last_ran" type="text" value="%script_last_ran" disabled style="width: 420px;">
</div>
<div class="clearerleft"></div>';
$lang['museumplus_enable_script'] = 'Enable MuseumPlus script';
$lang['museumplus_interval_run'] = 'Run script at the following interval (e.g. +1 day, +2 weeks, fortnight). Leave blank and it will run everytime cron_copy_hitcount.php runs)';
$lang['museumplus_log_directory'] = 'Directory to store script logs in. If this is left blank or is invalid then no logging will occur.';
$lang['museumplus_integrity_check_field'] = 'Integrity check field';
$lang['museumplus_rs_mappings_header'] = 'MuseumPlus - ResourceSpace mappings';
$lang['museumplus_add_mapping'] = 'Add mapping';
$lang['museumplus_mplus_field_name'] = 'MuseumPlus field name';
$lang['museumplus_rs_field'] = 'ResourceSpace field';
$lang['museumplus_'] = '';
$lang['museumplus_'] = '';

// Errors/ warnings
$lang['museumplus_error_bad_conn_data'] = 'MuseumPlus Connection Data invalid';
$lang['museumplus_error_unexpected_response'] = 'Unexpected MuseumPlus response code received - %code';
$lang['museumplus_error_no_data_found'] = 'No data found in MuseumPlus for this MpID - %mpid';
$lang['museumplus_warning_script_not_completed'] = "WARNING: The MuseumPlus script has not completed since '%script_last_ran'.\r\nYou can safely ignore this warning only if you subsequently received notification of a successful script completion.";
$lang['museumplus_error_script_failed'] = "The MuseumPlus script failed to run because a process lock was in place. This indicates that the previous run did not complete.\r\nIf you need to clear the lock after a failed run, run the script as follows:\r\nphp museumplus_script.php --clear-lock";
$lang['museumplus_php_utility_not_found'] = '$php_path configuration option MUST be set in order for cron functionality to successfully run!';
$lang['museumplus_'] = '';