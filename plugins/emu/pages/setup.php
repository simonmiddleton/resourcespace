<?php
include '../../../include/db.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit($lang['error-permissiondenied']);
    }
include_once '../../../include/general.php';


$plugin_name = 'emu';

// API server settings
$page_def[] = config_add_section_header($lang['emu_api_settings']);
$page_def[] = config_add_text_input('emu_api_server', $lang['emu_api_server']);
$page_def[] = config_add_text_input('emu_api_server_port', $lang['emu_api_server_port']);
$page_def[] = config_add_text_input('emu_api_authentication_token', $lang['emu_api_authentication_token']);

// EMUu script
$page_def[]      = config_add_section_header($lang['emu_script_header']);
$script_last_ran = sql_value('SELECT `value` FROM sysvars WHERE name = "last_emu_import"', '');
$page_def[]      = config_add_html($lang['emu_last_run_date'] . ('' != $script_last_ran ? date('l F jS Y @ H:i:s', strtotime($script_last_ran)) : $lang['status-never']) . '<br><br>');
$page_def[]      = config_add_boolean_select('emu_enable_script', $lang['emu_enable_script']);
$page_def[]      = config_add_boolean_select('emu_test_mode', $lang['emu_test_mode']);
$page_def[]      = config_add_text_input('emu_interval_run', $lang['emu_interval_run']);
$page_def[]      = config_add_text_input('emu_email_notify', $lang['emu_email_notify']);

// EMu settings
$page_def[] = config_add_section_header($lang['emu_settings_header']);
$page_def[] = config_add_single_ftype_select('emu_irn_field',$lang['emu_irn_field']);
$page_def[] = config_add_multi_rtype_select('emu_resource_types', $lang['emu_resource_types']);



$upload_status = config_gen_setup_post($page_def, $plugin_name);

include '../../../include/header.php';
if(isset($error))
    {
    echo "<div class=\"PageInformal\">{$error}</div>";
    }
config_gen_setup_html($page_def, $plugin_name, $upload_status, $lang['emu_configuration']);
include '../../../include/footer.php';