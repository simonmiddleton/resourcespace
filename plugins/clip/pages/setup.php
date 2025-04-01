<?php

// Do the include and authorization checking ritual
include '../../../include/boot.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}

$plugin_name="clip";
$page_heading = $lang['clip-configuration'];
$page_intro = "";

// Build configuration variable descriptions
$page_def[] = config_add_percent_range("clip_search_cutoff",$lang["clip_search_cutoff"]);
$page_def[] = config_add_percent_range("clip_similar_cutoff",$lang["clip_similar_cutoff"]);

// $page_def[]= config_add_single_ftype_select("google_vision_label_field", $lang["google_vision_label_field"],300,false,$FIXED_LIST_FIELD_TYPES); 
// $page_def[] = config_add_multi_rtype_select("google_vision_restypes", $lang["google_vision_restypes"]);
//$page_def[] = config_add_multi_select("google_vision_features", $lang["google_vision_features"], array("LABEL_DETECTION","LANDMARK_DETECTION","TEXT_DETECTION", "LOGO_DETECTION"), false);




// Do the page generation ritual
config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, null, $page_heading, $page_intro);
include '../../../include/footer.php';
