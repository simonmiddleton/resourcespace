<?php

// Do the include and authorization checking ritual
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}

// Specify the name of this plugin and the heading to display for the page.
$plugin_name = 'flickr_theme_publish';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}
$page_heading = $lang['flickr_theme_publish'];
$page_intro = '';

// Build configuration variable descriptions
$page_def[] = config_add_text_input('flickr_api_key',$lang['flickr_api_key']);
$page_def[] = config_add_text_input('flickr_api_secret',$lang['flickr_api_secret']);
$page_def[]= config_add_section_header("General","");
$page_def[]= config_add_single_ftype_select("flickr_caption_field", $lang["flickr_caption_field"]); 
$page_def[]= config_add_single_ftype_select("flickr_keywords_field", $lang["flickr_keywords_field"]);
$page_def[] = config_add_boolean_select("flickr_prefix_id_title", $lang['flickr_prefix_id_title']);
$page_def[] = config_add_text_input('flickr_default_size',$lang['flickr_default_size']);
$page_def[] = config_add_boolean_select("flickr_scale_up", $lang['flickr_scale_up']);
$page_def[] = config_add_text_list_input('flickr_alt_image_sizes',$lang['flickr_alt_image_sizes']);

$page_def[] = config_add_boolean_select("flickr_nice_progress", $lang['flickr_nice_progress']);
$page_def[] = config_add_boolean_select("flickr_nice_progress_previews", $lang['flickr_nice_progress_previews']);
$page_def[] = config_add_boolean_select("flickr_nice_progress_metadata", $lang['flickr_nice_progress_metadata']);
$page_def[] = config_add_text_input("flickr_nice_progress_min_timeout",$lang['flickr_nice_progress_min_timeout']);

// Do the page generation ritual
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, $upload_status, $page_heading, $page_intro);
include '../../../include/footer.php';
