<?php
// vimeo_publish setup page
include '../../../include/db.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    exit($lang['error-permissiondenied']);
    }
    
// Specify the name of this plugin and the heading to display for the page.
$plugin_name         = 'vimeo_publish';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}
$plugin_page_heading = $lang['vimeo_publish_configuration'];

if(getval("vimeo_publish_reset_user","") != "")
    {
    // Initialize VIMEO
    init_vimeo_api($vimeo_publish_client_id, $vimeo_publish_client_secret, $vimeo_callback_url);
    $vimeo_publish_system_token = get_access_token($vimeo_publish_client_id, $vimeo_publish_client_secret, $vimeo_callback_url);
    $_POST["vimeo_publish_system_token"] = $vimeo_publish_system_token;
    $_POST["vimeo_publish_system_state"] = "";
    }

// Build Insructions from language strings:
$vimeo_api_instructions = '<div class="Question"><ul>';
$vimeo_api_instruction_conditions = 1;
while(isset($lang['vimeo_api_instructions_condition_' . $vimeo_api_instruction_conditions]))
    {
    $vimeo_api_instructions .= '<li>' . $lang['vimeo_api_instructions_condition_' . $vimeo_api_instruction_conditions] . '</li>';
    $vimeo_api_instruction_conditions++;
    }
$vimeo_api_instructions .= '</ul><div class="clearerleft"></div></div>';



$page_def[] = config_add_html("<p><strong>{$lang['vimeo_publish_base']}:</strong> {$baseurl}<br>");
$page_def[] = config_add_html("<strong>{$lang['vimeo_publish_callback_url']}:</strong> {$vimeo_callback_url}</p>");

if(1 < $vimeo_api_instruction_conditions)
    {
    $page_def[] = config_add_section_header($lang['vimeo_publish_vimeo_instructions']);
    $page_def[] = config_add_html($vimeo_api_instructions);
    }

// OAuth 2.0 - Authentication credentials
$page_def[] = config_add_section_header($lang['vimeo_publish_authentication']);
$page_def[] = config_add_text_input('vimeo_publish_client_id', $lang['vimeo_publish_oauth2_client_id']);
$page_def[] = config_add_text_input('vimeo_publish_client_secret', $lang['vimeo_publish_oauth2_client_secret']);

$page_def[] = config_add_section_header($lang['vimeo_publish_account_options']);
$page_def[] = config_add_boolean_select("vimeo_publish_allow_user_accounts",$lang["vimeo_publish_allow_user_accounts"]);

$hiddeninputs = "<input type='hidden' id='vimeo_publish_system_token' name='vimeo_publish_system_token' value='" . $vimeo_publish_system_token . "' />";
$hiddeninputs .= "<input type='hidden' id='vimeo_publish_system_state' name='vimeo_publish_system_state' value='" . $vimeo_publish_system_state . "' />";
$hiddeninputs = "<input type='hidden' id='vimeo_publish_reset_user' name='vimeo_publish_reset_user' value='' />";

$page_def[] = config_add_html($hiddeninputs);

if($vimeo_publish_system_token != "" && get_vimeo_user($vimeo_publish_client_id, $vimeo_publish_client_secret, $vimeo_publish_system_token, $vimeo_user_data))
    {
    $usertext = $vimeo_user_data['name'] . "(" . ucfirst($vimeo_user_data['account']) . " account - " . formatfilesize($vimeo_user_data['upload_quota_free']) . " free)";
    $usertext .= "<a href='#' onclick='jQuery(\"#vimeo_publish_system_token\").val();jQuery(\"#vimeo_publish_system_state\").val();return CentralSpacePost(document.getElementById(\"form1\"), false);'>" . $lang['vimeo_publish_delete_token'] . "</a>"; 
    }
else
    {
    $usertext = "<a href='#' onclick='jQuery(\"#vimeo_publish_reset_user\").val(\"true\");return CentralSpacePost(document.getElementById(\"form1\"), false);'>" . $lang['vimeo_publish_set_account'] . "</a>"; 
    }
$userquestion = "<div class='Question'>
        <label>" . $lang['vimeo_publish_publish_as_user'] . "</label>
        <div class='Fixed'>" . $usertext . "</div>
        <div class='clearerleft'></div>
    </div>'";
$page_def[] = config_add_html($userquestion);

// ResourceSpace - metadata mappings
$page_def[] = config_add_section_header($lang['vimeo_publish_rs_field_mappings']);
$page_def[] = config_add_single_ftype_select('vimeo_publish_vimeo_link_field', $lang['vimeo_publish_vimeo_link']);
$page_def[] = config_add_single_ftype_select('vimeo_publish_video_title_field', $lang['vimeo_publish_video_title']);
$page_def[] = config_add_single_ftype_select('vimeo_publish_video_description_field', $lang['vimeo_publish_video_description']);
$page_def[] = config_add_multi_rtype_select('vimeo_publish_restypes', $lang['vimeo_publish_resource_types_to_include']);




// Do the page generation ritual -- don't change this section.
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, $upload_status, $plugin_page_heading);
include '../../../include/footer.php';
