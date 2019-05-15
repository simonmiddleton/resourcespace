<?php
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    http_response_code(401);
    exit($lang['error-permissiondenied']);
    }
include_once '../include/museumplus_functions.php';


$plugin_name = 'museumplus';
if(!in_array($plugin_name, $plugins))
    {
    plugin_activate_for_setup($plugin_name);
    }

$museumplus_rs_mappings = unserialize(base64_decode($museumplus_rs_saved_mappings));



// API settings
$page_def[] = config_add_section_header($lang['museumplus_api_settings_header']);
$page_def[] = config_add_text_input('museumplus_host', $lang['museumplus_host']);
$page_def[] = config_add_text_input('museumplus_application', $lang['museumplus_application']);
$page_def[] = config_add_text_input('museumplus_api_user', $lang['museumplus_api_user']);
$page_def[] = config_add_text_input('museumplus_api_pass', $lang['museumplus_api_pass'], true);
$page_def[] = config_add_text_input('museumplus_search_mpid_field', $lang['museumplus_search_match_field']);

// ResourceSpace settings
$page_def[] = config_add_section_header($lang['museumplus_RS_settings_header']);
$page_def[] = config_add_single_ftype_select('museumplus_mpid_field', $lang['museumplus_mpid_field'], 420);
$page_def[] = config_add_multi_rtype_select('museumplus_resource_types', $lang['museumplus_resource_types'], 420);

// Script settings
$page_def[] = config_add_section_header($lang['museumplus_script_header']);
$museumplus_script_last_ran = '';
check_script_last_ran('last_museumplus_import', $museumplus_script_failure_notify_days, $museumplus_script_last_ran);
$script_last_ran_content = str_replace('%script_last_ran', $museumplus_script_last_ran, $lang['museumplus_last_run_date']);
$page_def[] = config_add_html($script_last_ran_content);










$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
if(isset($error))
    {
    $error = htmlspecialchars($error);
    echo "<div class=\"PageInformal\">{$error}</div>";
    }
config_gen_setup_html($page_def, $plugin_name, $upload_status, $lang['museumplus_configuration']);
include '../../../include/footer.php';