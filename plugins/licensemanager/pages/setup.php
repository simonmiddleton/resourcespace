<?php
include "../../../include/db.php";

include "../../../include/authenticate.php"; if (!checkperm("a")) {exit ("Permission denied.");}
$plugin_page_heading = 'License Manager';
$plugin_name = 'licensemanager';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}

$page_def[] = config_add_text_list_input(
    'license_usage_mediums',
    $lang['license_manager_mediums']
);

// Do the page generation ritual -- don't change this section.
config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, null, $plugin_page_heading);
include '../../../include/footer.php';