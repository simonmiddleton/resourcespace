<?php
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit($lang['error-permissiondenied']);
    }

$plugin_name = 'antivirus';
if(!in_array($plugin_name, $plugins))
    {
    plugin_activate_for_setup($plugin_name);
    }

$archive_states = array();
foreach(sql_query('SELECT `code`, `name` FROM archive_states') as $state)
    {
    $archive_states[$state['code']] = $state['name'];
    }


$page_def[] = config_add_text_input('antivirus_path', $lang['antivirus_av_path_label']);
$page_def[] = config_add_text_input('antivirus_silent_options', $lang['antivirus_av_silent_options_label']);
$page_def[] = config_add_single_select(
    'antivirus_action',
    $lang['antivirus_action_label'],
    array(
        ANTIVIRUS_ACTION_DELETE     => $lang['antivirus_action_delete'],
        ANTIVIRUS_ACTION_QUARANTINE => $lang['antivirus_action_quarantine']
    )
);
$page_def[] = config_add_single_select(
    'antivirus_quarantine_state',
    $lang['antivirus_quarantine_status_label'],
    $archive_states
);


// Render setup page ritual
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
if(isset($error))
    {
    echo "<div class=\"PageInformal\">{$error}</div>";
    }
config_gen_setup_html($page_def, $plugin_name, $upload_status, $lang['antivirus_configuration']);
include '../../../include/footer.php';