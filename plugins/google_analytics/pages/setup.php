<?php
#
# Google Analytics Key setup page, requires System Setup permission
#

// Do the include and authorization checking ritual.
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}

// Specify the name of this plugin, the heading to display for the page.
$plugin_name = 'google_analytics';
$page_heading = 'Google Analytics Key Configuration';

// Build the $page_def array of descriptions of each configuration variable the plugin uses.
$page_def[] = config_add_text_list_input('google_analytics_key','Google Analytics Key');


// Do the page generation ritual
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, $upload_status, $page_heading);
include '../../../include/footer.php';



