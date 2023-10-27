<?php
#
# search_tiles setup page
#

include '../../../include/db.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}
	
// Specify the name of this plugin and the heading to display for the page.
$plugin_name = 'search_tiles';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}
$plugin_page_heading = $lang['search_tiles_title'];

$page_def[] = config_add_boolean_select('search_tiles_text_shadow', $lang['search_tiles_text_shadow'],array(0=>$lang["no"],1=>$lang["yes"]));
$page_def[] = config_add_boolean_select('search_tiles_collection_count', $lang['search_tiles_collection_count'], array(0=>$lang["no"],1=>$lang["yes"]));

// Do the page generation ritual -- don't change this section.
config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, null, $plugin_page_heading);
include '../../../include/footer.php';
