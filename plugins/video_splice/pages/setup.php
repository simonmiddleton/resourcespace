<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php"; if (!checkperm("u")) {exit ("Permission denied.");}

// Specify the name of this plugin, the heading to display for the page.
$plugin_name = 'video_splice';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}
$page_heading = $lang['videospliceconfiguration'];


$page_def[]= config_add_single_rtype_select("videosplice_resourcetype",$lang["video_resource_type"]);
$page_def[]= config_add_single_ftype_select("videosplice_parent_field",$lang["parent_resource_field"]);


// Do the page generation ritual
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, $upload_status, $page_heading);
include '../../../include/footer.php';

