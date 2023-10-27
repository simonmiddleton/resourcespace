<?php

// Do the include and authorization checking ritual
include '../../../include/db.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}

// Specify the name of this plugin and the heading to display for the page.
$plugin_name = 'openai_gpt';
if (!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}
$page_heading = $lang['openai_gpt_title'];
$page_intro = "<p>" . $lang['openai_gpt_intro'] . "</p>";

// Can't use old model since move to chat API
if(trim($openai_gpt_model) == "text-davinci-003")
    {
    $openai_gpt_model = $openai_gpt_fallback_model;
    }

// Build configuration variable descriptions
$page_def[] = config_add_text_input("openai_gpt_api_key",$lang["openai_gpt_api_key"]);
$page_def[] = config_add_section_header($lang["plugin_category_advanced"]);
$page_def[] = config_add_html("<div class='Question'><strong>" . htmlspecialchars($lang["openai_gpt_advanced"]) . "</strong><div class='clearerleft'></div></div>");
$page_def[] = config_add_text_input("openai_gpt_model",$lang["openai_gpt_model"]);
$page_def[] = config_add_text_input("openai_gpt_system_message",$lang["openai_gpt_system_message"]);
$page_def[] = config_add_text_input("openai_gpt_temperature",$lang["openai_gpt_temperature"]);
$page_def[] = config_add_text_input("openai_gpt_max_tokens",$lang["openai_gpt_max_tokens"]);

// Do the page generation ritual
config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, null, $page_heading, $page_intro);
include '../../../include/footer.php';
