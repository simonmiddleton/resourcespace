<?php
include '../../../include/db.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit($lang['error-permissiondenied']);
    }

$plugin_name = 'google_oauth';
if(!in_array($plugin_name, $plugins))
    {
    plugin_activate_for_setup($plugin_name);
    }

if(version_compare(PHP_VERSION, '5.4.0', '<'))
    {
    $page_def[] = config_add_html("<div class=\"PageInformal\">{$lang['google_oauth_php_requirement_error']}</div>");
    }


$page_def[] = config_add_section_header($lang['google_oauth_authentication']);
$page_def[] = config_add_html('
    <div class="Question">
        <label>' . $lang['google_oauth_redirect_uri_label'] . '</label>
        <span>' . GOOGLE_OAUTH_REDIRECT_URI . '</span>
        <div class="clearerleft"></div>
    </div>
');
$page_def[] = config_add_text_input('google_oauth_client_id', $lang['google_oauth_client_id_label']);
$page_def[] = config_add_text_input('google_oauth_client_secret', $lang['google_oauth_client_secret_label']);

$page_def[] = config_add_section_header($lang['setup-generalsettings']);
$page_def[] = config_add_boolean_select('google_oauth_standard_login', $lang['google_oauth_standard_login_label']);
$page_def[] = config_add_boolean_select('google_oauth_use_standard_login_by_default', $lang['google_oauth_use_standard_login_by_default_label']);
$page_def[] = config_add_boolean_select('google_oauth_xshares_bypass_sso', $lang['google_oauth_allow_xshares_sso_bypass_label']);
$page_def[] = config_add_single_group_select('google_oauth_default_user_group', $lang['google_oauth_default_usergroup'], 420);


// Render setup page ritual
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, $upload_status, $lang['google_oauth_configuration']);
include '../../../include/footer.php';