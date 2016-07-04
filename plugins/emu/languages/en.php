<?php
$lang['emu_configuration'] = 'EMu Configuration';
$lang['emu_mapping_rules'] = 'Mapping rules';

$lang['emu_api_settings'] = 'API server settings';
$lang['emu_api_server'] = 'Server address (e.g. http://[server.address])';
$lang['emu_api_server_port'] = 'Server port';
$lang['emu_api_authentication_token'] = 'Authentication token (leave blank if API is not set to use one';

$lang['emu_resource_types'] = 'Select resource types linked to EMu';
$lang['emu_email_notify'] = 'E-mail address that script will send notifications to. Leave blank to default to the system notification address';

$lang['emu_script_header'] = 'Enable script that will automatically update the EMu data whenever ResourceSpace runs its scheduled task (cron_copy_hitcount.php)';
$lang['emu_last_run_date'] = '<strong>Script last run: </strong>';
$lang['emu_enable_script'] = 'Enable EMu script';
$lang['emu_test_mode'] = 'Test mode - Set to true and script will run but not update resources';
$lang['emu_interval_run'] = 'Run script at the following interval (e.g. 1 day, 2 weeks, fortnight). Leave blank and it will run everytime cron_copy_hitcount.php runs)';