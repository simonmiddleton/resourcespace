<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php"; if (!checkperm("u")) {exit ("Permission denied.");}

$plugin_name = 'embedvideo';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}
	
$page_heading = $lang['embed_video_configuration'];

$page_def[]= config_add_single_rtype_select("embedvideo_resourcetype",$lang["video_resourcetype"]);

// Do the page generation ritual
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, $upload_status, $page_heading);
include '../../../include/footer.php';

