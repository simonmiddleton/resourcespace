<?php

// Do the include and authorization checking ritual
include '../../../include/boot.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}

$plugin_name="clip";
$page_heading = $lang['clip-configuration'];
$page_intro = "";

$page_def[] = config_add_text_input("clip_service_url",$lang["clip_service_url"]);

// Build configuration variable descriptions
$page_def[] = config_add_section_header($lang["clip-natural-language-search"]);
$page_def[] = config_add_percent_range("clip_search_cutoff",$lang["clip_search_cutoff"]);
$page_def[] = config_add_text_input("clip_results_limit_search",$lang["clip_results_limit_search"]);
//$page_def[] = config_add_multi_ftype_select("clip_text_search_fields", $lang["clip_text_search_fields"],410,10,0);

$page_def[] = config_add_section_header($lang["clip-visually-similar-images"]);
$page_def[] = config_add_percent_range("clip_similar_cutoff",$lang["clip_similar_cutoff"]);
$page_def[] = config_add_text_input("clip_results_limit_similar",$lang["clip_results_limit_similar"]);


$page_def[] = config_add_section_header($lang["clip-duplicate-images"]);
$page_def[] = config_add_percent_range("clip_duplicate_cutoff",$lang["clip_duplicate_cutoff"]);



// Do the page generation ritual
config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, null, $page_heading, $page_intro);
include '../../../include/footer.php';
