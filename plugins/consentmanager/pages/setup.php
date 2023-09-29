<?php
include "../../../include/db.php";

include "../../../include/authenticate.php"; if (!checkperm("a")) {exit ("Permission denied.");}
$plugin_page_heading = 'Consent Manager';
$plugin_name = 'consentmanager';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}

$page_def[] = config_add_text_list_input(
    'consent_usage_mediums',
    $lang['consent_manager_mediums']
);

// Do the page generation ritual -- don't change this section.
config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, null, $plugin_page_heading);
include '../../../include/footer.php';