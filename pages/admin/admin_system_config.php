<?php
include '../../include/db.php';
include '../../include/authenticate.php'; if(!checkperm('a')) { exit('Permission denied.'); }
include_once '../../include/config_functions.php';
include_once '../../include/ajax_functions.php';

$ajax = getval('ajax', '') === 'true';

// Search functionality
$searching = (((getval("find", "") != "" && getval("clear_search", "") == "") || getval("only_modified", "no") == "yes") ? true : false);
$find = getval("find", "");
$only_modified = (getval('only_modified', 'no') == 'yes');
if (!$searching) {$find = "";}

// Common config fields' options
$enable_disable_options = array($lang['userpreference_disable_option'], $lang['userpreference_enable_option']);
$yes_no_options         = array($lang['no'], $lang['yes']);



// System section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead">' . $lang['systemsetup'] . '</h3><div id="SystemConfigSystemSection" class="CollapsibleSection">');
$page_def[] = config_add_text_input('applicationname', $lang['setup-applicationname'], false, 420, false, '', true);
$page_def[] = config_add_text_input('email_from', $lang['setup-emailfrom'], false, 420, false, '', true);
$page_def[] = config_add_text_input('email_notify', $lang['setup-emailnotify'], false, 420, false, '', true);
$page_def[] = config_add_single_select(
    'user_local_timezone',
    $lang['systemconfig_user_local_timezone'],
    timezone_identifiers_list(),
    false,
    420,
    '',
    true);
$page_def[] = config_add_text_input(
    'download_filename_format',
    $lang['setup-download_filename_format'] . render_help_link('resourceadmin/download_filename_format', true),
    false,
    420,
    false,
    '',
    true
);
$page_def[] = config_add_html('</div>');


// Debug section
$page_def[] = config_add_html(
    '<h3 class="CollapsibleSectionHead collapsed">' . htmlspecialchars($lang['systemconfig_debug']) . '</h3>'
    . '<div id="SystemConfigDebugSection" class="CollapsibleSection">'
);

// Determine the time left on debug log override
$debug_log_default_duration = 300;
$time_left = get_sysvar('debug_override_expires', time()) - time();
if ($time_left > 0)
    {
    $debug_log_override_time_left = $time_left;
    $system_config_debug_log_duration_question_class = '';
    $debug_log_override_timer_active = true;
    }
else
    {
    // reset 
    remove_config_option(null, 'system_config_debug_log_interim');
    $system_config_debug_log_duration_question_class = 'DisplayNone';
    $debug_log_override_timer_active = false;
    }
$debug_log_override_time_left ??= $debug_log_default_duration;

// "Faking" a config option so that we can apply some logic before deciding to override debug_log
$system_config_debug_log_interim = $lang['off'];
$debug_log_options = [
    $lang['systemconsoleonallusers'],
    $lang['systemconfig_debug_log_on_specific_user'],
    $lang['off'],
];
if ($debug_log)
    {
    $debug_log_options = [$lang['systemconsoleonpermallusers']];
    $system_config_debug_log_interim = $lang['systemconsoleonpermallusers'];
    }
get_config_option(null, 'system_config_debug_log_interim', $system_config_debug_log_interim);

$page_def[] = config_add_single_select(
    'system_config_debug_log_interim',
    $lang['systemconsoledebuglog'],
    $debug_log_options,
    false,
    420,
    '',
    true,
    'debug_log_selector_onchange(this);'
);
ob_clean();
$autocomplete_user_scope = 'SystemConfigDebugLogSpecificUser_';
$debug_override_user = (int) get_sysvar('debug_override_user', -1);
$single_user_select_field_id = 'debug_override_user';
$single_user_select_field_value = $debug_override_user;
$single_user_select_field_onchange = 'create_debug_log_override();';
$SystemConfigDebugForUser_class = $system_config_debug_log_interim === $lang['systemconfig_debug_log_on_specific_user']
    ? ''
    : 'DisplayNone';
?>
<div id="SystemConfigDebugForUser" class="Question <?php echo escape($SystemConfigDebugForUser_class); ?>">
    <label></label>
    <?php include dirname(__DIR__, 2) . "/include/user_select.php"; ?> 
    <div class="clearerleft"></div>
</div>
<?php
render_text_question(
    "{$lang['systemconsoleturnoffafter']} X {$lang['seconds']}",
    'system_config_debug_log_duration',
    sprintf(
        '<span class="MarginLeft1rem"><span id="DebugLogOverrideTimerText">%s</span>s %s</span>',
        $debug_log_override_time_left,
        htmlspecialchars($lang['remaining'])
    ),
    true,
    ' onchange="create_debug_log_override(undefined, this.value);"',
    $debug_log_default_duration,
    ['div_class' => [$system_config_debug_log_duration_question_class]]
);
$user_select_html = ob_get_contents();
ob_clean();
$page_def[] = config_add_html($user_select_html);
$page_def[] = config_add_html('</div>');
// End of Debug section


// User interface section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['userpreference_user_interface'] . '</h3><div id="SystemConfigUserInterfaceSection" class="CollapsibleSection">');


// Font selection
$fontsdir=scandir(dirname(__FILE__) . "/../../css/fonts/");
$fonts=array();
foreach ($fontsdir as $f)
    {
    if (strpos($f,".css")!==false) // Valid font CSS definition
        {
        $fn=substr($f,0,strlen($f)-4);
        $fonts[$fn]=$fn;
        }
    }
$page_def[] = config_add_single_select('global_font', $lang['font'], $fonts, true, 420, '', true,"jQuery('#global_font_link').attr('href','" .  $baseurl . "/css/fonts/' + this.value + '.css');");

$page_def[] = config_add_file_input(
    'custom_font',
    $lang['systemconfig_customfont_label'],
    $baseurl . '/pages/admin/admin_system_config.php',
    316,
    array('woff2', 'woff', 'ttf', 'otf')
);

$page_def[] = config_add_file_input(
    'linkedheaderimgsrc',
    $lang['systemconfig_linkedheaderimgsrc_label'],
    $baseurl . '/pages/admin/admin_system_config.php',
    316
);
$page_def[] = config_add_file_input(
    'header_favicon',
    $lang['systemconfig_header_favicon_label'],
    $baseurl . '/pages/admin/admin_system_config.php',
    316
);

$page_def[] = config_add_single_select(
    'header_size',
    $lang['userpreference_headersize'],
    array(
        'HeaderSmall' => $lang['headersmall'],
        'HeaderMid'   => $lang['headermid'],
        'HeaderLarge' => $lang['headerlarge']
    ),
    true,
    420,
    '',
    true,"jQuery('#Header').removeClass('HeaderSmall');jQuery('#Header').removeClass('HeaderMid');jQuery('#Header').removeClass('HeaderLarge');jQuery('#Header').addClass(this.value);myLayout._sizePane('north');"
);
$page_def[] = config_add_colouroverride_input(
    'header_colour_style_override',
    $lang["setup-headercolourstyleoverride"],
    '',
    null,
    true,
    "jQuery('#Header').css('background',value);"
);
$page_def[] = config_add_colouroverride_input(
    'header_link_style_override',
    $lang["setup-headerlinkstyleoverride"],
    '',
    null,
    true,
    "jQuery('#HeaderNav1 li a').css('color',value);jQuery('#HeaderNav1 li.UploadButton a').css('color','white');jQuery('#HeaderNav2 a').css('color',value);jQuery('#HeaderNav2 li').css('border-color', value);"
);
$page_def[] = config_add_colouroverride_input(
    'home_colour_style_override',
    $lang["setup-homecolourstyleoverride"],
    '',
    null,
    true,
    "jQuery('#SearchBox').css('background',value); jQuery('#HomeSiteText.dashtext').css('background',value); jQuery('.HomePanelIN').css('background',value); jQuery('#BrowseBar').css('background',value); jQuery('.SearchBarTab.SearchBarTabSelected').css('background', value);"
);
$page_def[] = config_add_colouroverride_input(
    'collection_bar_background_override',
    $lang["setup-collectionbarbackground"],
    '',
    null,
    true,
    "jQuery('.CollectBack').css('background',value);"
);
$page_def[] = config_add_colouroverride_input(
    'collection_bar_foreground_override',
    $lang["setup-collectionbarforeground"],
    '',
    null,
    true,
    "jQuery('.CollectionPanelShell').css('background-color',value);jQuery('#CollectionDiv select').css('background-color',value); jQuery('.ui-layout-resizer').css('background',value);"
);
$page_def[] = config_add_colouroverride_input(
    'button_colour_override',
    $lang["setup-buttoncolouroverride"],
    '',
    null,
    true,
    "jQuery('button:not(.search-icon),input[type=submit],input[type=button],.RecordPanel .RecordDownloadSpace .DownloadDBlend a,.UploadButton a').css('background-color',value);"
);
$page_def[] = config_add_single_select('thumbs_default', $lang['userpreference_thumbs_default_label'], array('show' => $lang['showthumbnails'], 'hide' => $lang['hidethumbnails']), true, 420, '', true);
$page_def[] = config_add_boolean_select('resource_view_modal', $lang['userpreference_resource_view_modal_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('modal_default', $lang['systemconfig_modal_default'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('basic_simple_search', $lang['userpreference_basic_simple_search_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('comments_resource_enable', $lang['systemconfig_comments'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_single_select('upload_then_edit', $lang['default_upload_sequence'], array(true => $lang['upload_first_then_set_metadata'], false => $lang['set_metadata_then_upload']), true, 420, '', true);
$page_def[] = config_add_boolean_select('byte_prefix_mode_decimal', $lang['byte_prefix_mode_decimal'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('tilenav', $lang['userpreference_tilenavdefault'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select(
    'use_native_input_for_date_field',
    $lang['systemconfig_use_native_input_for_date_field'],
    $enable_disable_options,
    420,
    '',
    true,
    null,
    false,
    $lang['systemconfig_native_date_input_no_partials_supported'],
);
$page_def[] = config_add_html('</div>');

// Watermark section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['watermark_header'] . '</h3><div id="SystemConfigWatermarkSection" class="CollapsibleSection">');
$page_def[] = config_add_file_input(
    'watermark',
    $lang['watermark_label'],
    $baseurl . '/pages/admin/admin_system_config.php',
    316,
    array('png'),
    true
);
$page_def[] = config_add_html('</div>');

// Multilingual section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_multilingual'] . '</h3><div id="SystemConfigMultilingualSection" class="CollapsibleSection">');
$page_def[] = config_add_single_select('defaultlanguage', $lang['systemconfig_default_language_label'], $languages, true, 420, '', true);
$page_def[] = config_add_boolean_select('disable_languages', $lang['disable_languages'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('browser_language', $lang['systemconfig_browser_language_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_html('</div>');


// Search section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['searchcapability'] . '</h3><div id="SystemConfigSearchSection" class="CollapsibleSection">');

$sort_order_fields = array('relevance' => $lang['relevance']);
if($random_sort)
    {
    $sort_order_fields['random'] = $lang['random'];
    }

if($popularity_sort)
    {
    $sort_order_fields['popularity'] = $lang['popularity'];
    }

if($orderbyrating)
    {
    $sort_order_fields['rating'] = $lang['rating'];
    }

if($date_column)
    {
    $sort_order_fields['date'] = $lang['date'];
    }

if($colour_sort)
    {
    $sort_order_fields['colour'] = $lang['colour'];
    }

if($order_by_resource_id)
    {
    $sort_order_fields['resourceid'] = $lang['resourceid'];
    }

if($order_by_resource_type)
    {
    $sort_order_fields['resourcetype'] = $lang['type'];
    }
$page_def[] = config_add_single_select(
    'default_sort',
    $lang['userpreference_default_sort_label'],
    $sort_order_fields,
    true,
    420,
    '',
    true
);
$default_display_array = array();
$default_display_array['thumbs'] = $lang['largethumbstitle'];
if($xlthumbs || $GLOBALS['default_display'] == 'xlthumbs')
    {
    $default_display_array['xlthumbs'] = $lang['xlthumbstitle'];
    }
if($searchlist || $GLOBALS['default_display'] == 'list')
    {
    $default_display_array['list'] = $lang['listtitle'];
    }
$default_display_array['strip']  = $lang['striptitle'];

$page_def[] = config_add_single_select('default_perpage', $lang['userpreference_default_perpage_label'], $results_display_array, false, 420, '', true);
$page_def[] = config_add_single_select(
    'default_display',
    $lang['userpreference_default_display_label'],
    $default_display_array,
    true,
    420,
    '',
    true
);
$page_def[] = config_add_boolean_select('archive_search', $lang['stat-archivesearch'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('display_resource_id_in_thumbnail', $lang['systemconfig_display_resource_id_in_thumbnail_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('advanced_search_contributed_by', $lang['systemconfig_advanced_search_contributed_by_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('advanced_search_media_section', $lang['systemconfig_advanced_search_media_section_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_html('</div>');


// Navigation section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_navigation'] . '</h3><div id="SystemConfigNavigationSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('help_link', $lang['systemconfig_help_link_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('recent_link', $lang['systemconfig_recent_link_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('mycollections_link', $lang['systemconfig_mycollections_link_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('myrequests_link', $lang['systemconfig_myrequests_link_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('research_link', $lang['systemconfig_research_link_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('themes_navlink', $lang['systemconfig_themes_navlink_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('use_theme_as_home', $lang['systemconfig_use_theme_as_home_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('use_recent_as_home', $lang['systemconfig_use_recent_as_home_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_html('</div>');

// Browse Bar section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_browse_bar_section'] . '</h3><div id="SystemConfigFeaturedCollectionSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('browse_bar', $lang['systemconfig_browse_bar_enable'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('browse_bar_workflow', $lang['systemconfig_browse_bar_workflow'], $yes_no_options, 420, '', true);
$page_def[] = config_add_html('</div>');

// Collection section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['collections'] . '</h3><div id="SystemConfigCollectionSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('show_collection_name', $lang['systemconfig_show_collection_name'], $yes_no_options, 420, '', true);
$page_def[] = config_add_html('</div>');

// Featured Collection section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_featured_collections'] . '</h3><div id="SystemConfigFeaturedCollectionSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('enable_themes', $lang['systemconfig_enable_themes'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('themes_simple_view', $lang['systemconfig_themes_simple_view'], $yes_no_options, 420, '', true);
$page_def[] = config_add_html('</div>');

// Workflow section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_workflow'] . '</h3><div id="SystemConfigWorkflowSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('research_request', $lang['researchrequest'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_html('</div>');


// Actions section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['actions'] . '</h3><div id="SystemConfigActionsSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('actions_enable', $lang['actions-enable'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('actions_resource_requests', $lang['actions_resource_requests_default'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('actions_account_requests', $lang['actions_account_requests_default'], $enable_disable_options, 420, '', true);
	
$page_def[] = config_add_html('</div>');

// Metadata section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['metadata'] . '</h3><div id="SystemConfigMetadataSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('metadata_report', $lang['metadata-report'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('metadata_read_default', $lang['embedded_metadata'], array($lang['embedded_metadata_donot_extract_option'], $lang['embedded_metadata_extract_option']), 420, '', true);
$page_def[] = config_add_html('</div>');


// User accounts section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_user_accounts'] . '</h3><div id="SystemConfigUserAccountsSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('allow_account_request', $lang['systemconfig_allow_account_request_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('terms_download', $lang['systemconfig_terms_download_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('terms_login', $lang['systemconfig_terms_login_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('terms_upload', $lang['systemconfig_terms_upload_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('user_rating', $lang['systemconfig_user_rating_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_html('</div>');


// Security section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_security'] . '</h3><div id="SystemConfigSecuritySection" class="CollapsibleSection">');
$page_def[] = config_add_single_select(
    'password_min_length',
    $lang['systemconfig_password_min_length_label'],
    range(0, 30),
    false,
    420,
    '',
    true
);
$page_def[] = config_add_single_select(
    'password_min_alpha',
    $lang['systemconfig_password_min_alpha_label'],
    range(0, 30),
    false,
    420,
    '',
    true
);
$page_def[] = config_add_single_select(
    'password_min_numeric',
    $lang['systemconfig_password_min_numeric_label'],
    range(0, 30),
    false,
    420,
    '',
    true
);
$page_def[] = config_add_single_select(
    'password_min_uppercase',
    $lang['systemconfig_password_min_uppercase_label'],
    range(0, 30),
    false,
    420,
    '',
    true
);
$page_def[] = config_add_single_select(
    'password_min_special',
    $lang['systemconfig_password_min_special_label'],
    range(0, 30),
    false,
    420,
    '',
    true
);  
$page_def[] = config_add_single_select(
    'password_expiry',
    $lang['systemconfig_password_expiry_label'],
    array_merge(array(0 => $lang['never']), range(1, 90)),
    true,
    420,
    '',
    true
);
$page_def[] = config_add_single_select(
    'max_login_attempts_per_ip',
    $lang['systemconfig_max_login_attempts_per_ip_label'],
    range(10, 50),
    false,
    420,
    '',
    true
);
$page_def[] = config_add_single_select(
    'max_login_attempts_per_username',
    $lang['systemconfig_max_login_attempts_per_username_label'],
    range(0, 30),
    false,
    420,
    '',
    true
);
$page_def[] = config_add_single_select(
    'max_login_attempts_wait_minutes',
    $lang['systemconfig_max_login_attempts_wait_minutes_label'],
    range(0, 30),
    false,
    420,
    '',
    true
);
$page_def[] = config_add_single_select(
    'password_brute_force_delay',
    $lang['systemconfig_password_brute_force_delay_label'],
    range(0, 30),
    false,
    420,
    '',
    true
);
$page_def[] = config_add_html('</div>');

// API section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_api'] . '</h3><div id="SystemConfigAPISection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('iiif_enabled', $lang['iiif_enable_option'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_html('</div>');

// Search engines section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['system_config_search_engines'] . '</h3><div id="SystemConfigSearchEngineSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('search_engine_noindex', $lang['search_engine_noindex'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('search_engine_noindex_external_shares', $lang['search_engine_noindex_external_shares'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_html('</div>');








// Let plugins hook onto page definition and add their own configs if needed
// or manipulate the list
$plugin_specific_definition = hook('add_system_config_page_def', '', array($page_def));
if(is_array($plugin_specific_definition) && !empty($plugin_specific_definition))
    {
    $page_def = $plugin_specific_definition;
    }

// Strip out any configs that are blocked from being edited in the UI.
if (count($system_config_hide)>0)
    {
    $new_page_def=array();
    for($n=0;$n<count($page_def);$n++)
        {
        if (!in_array($page_def[$n][1],$system_config_hide)) {$new_page_def[]=$page_def[$n];} // Add if not blocked
        }
    $page_def=$new_page_def;
    }




// Process autosaving requests
// Note: $page_def must be defined by now in order to make sure we only save options that we've defined
if('true' === getval('ajax', '') && 'true' === getval('autosave', ''))
    {
    $response['success'] = true;
    $response['message'] = '';

    $autosave_option_name  = getval('autosave_option_name', '');
    $autosave_option_value = getval('autosave_option_value', '');

    // Search for the option name within our defined (allowed) options
    // if it is not there, error and don't allow saving it
    $page_def_option_index = array_search($autosave_option_name, array_column($page_def, 1));
    if(false === $page_def_option_index)
        {
        $response['success'] = false;
        $response['message'] = $lang['systemconfig_option_not_allowed_error'];

        echo json_encode($response);
        exit();
        }

    if(!set_config_option(null, $autosave_option_name, $autosave_option_value))
        {
        $response['success'] = false;
        }

    echo json_encode($response);
    exit();
    }

if($ajax && getval('action', '') === 'create_debug_log_override' && enforcePostRequest($ajax))
    {
    $debug_user = getval('debug_override_user', '');
    $debug_expires = getval('debug_override_expires', '');
    if ($debug_user !== '' && $debug_expires !== '')
        {
        create_debug_log_override($debug_user, $debug_expires);
        unset($GLOBALS['debug_log_override']);
        ajax_send_response(200, ajax_response_ok_no_data());
        }
    ajax_send_response(400, ajax_response_fail(ajax_build_message($lang['error_invalid_input'])));
    }

config_process_file_input($page_def, 'system/config', $baseurl . '/pages/admin/admin_system_config.php');

# $lang is not a config option! 
unset($system_wide_config_options['lang']);
foreach ($system_wide_config_options as $key => $value)
    {
    $GLOBALS[$key] = $value;
    }

# Get user ref for use in header.php when loading profile image.
if (!isset($userref))
    {
    $userref = $userdata[0]['ref'];
    }

if ($searching)
    {
    // Check for search phrase in config. description.
    if ($find !== '')
        {
        $search_matches = array();
        foreach ($page_def as $config_to_check)
            {
            if (isset($config_to_check[2]) && stripos($config_to_check[2], $find) !== false)
                {
                $search_matches[] = $config_to_check;
                }
            }
        $page_def = $search_matches;
        }
    // Filter results to only config which has been changed previously i.e. exists in user_preferences with null in user column.
    if ($only_modified)
        {
        $search_matches = array();
        $returned_options = array();
        get_config_options(null, $returned_options);
        $returned_options = array_column($returned_options, 'parameter');
        foreach ($page_def as $config_to_check)
            {
            if (isset($config_to_check[1]) && in_array($config_to_check[1], $returned_options))
                {
                $search_matches[] = $config_to_check;
                }
            }
        $page_def = $search_matches;
        }
    }

include '../../include/header.php';
?>
<div class="BasicsBox">
    <h1 class="inline_config_search"><?php echo htmlspecialchars($lang["systemconfig"]); ?></h1>

    <form id="SearchSystemPages" class="inline_config_search" method="post" onSubmit="return CentralSpacePost(this);">
        <?php generateFormToken("system_config_search"); ?>
        <div>
        <input type="text" name="find" id="configsearch" value="<?php echo escape($find); ?>">
        <input type="submit" name="searching" value="<?php echo escape($lang["searchbutton"]); ?>">
        <?php
        if($searching)
            {
            ?>
            <input type="button" name="clear_search" value="<?php echo escape($lang["clearbutton"]); ?>" onClick="jQuery('#configsearch').val(''); jQuery('#only_modified').prop('checked', false); CentralSpacePost(document.getElementById('SearchSystemPages'));">
            <?php
            }
        ?>
        </div>
        <div>
        <input type="checkbox" name="only_modified" id="only_modified" value="yes" <?php  echo $only_modified ? 'checked="checked"' : ''; ?>>
        <label for="only_modified"><?php echo htmlspecialchars($lang["systemconfig_only_show_modified"]); ?></label>
        </div>
    </form>

    <?php
	$links_trail = array(
	    array(
	        'title' => $lang["systemsetup"],
	        'href'  => $baseurl_short . "pages/admin/admin_home.php",
            'menu' =>  true
	    ),
	    array(
	        'title' => $lang["systemconfig"],
	    )
	);

	renderBreadcrumbs($links_trail);
	?>

    <p><?php echo $lang['systemconfig_description']; ?></p>
    <div class="CollapsibleSections">
    <?php
    config_generate_html($page_def);
    ?>
    </div>
    <script>registerCollapsibleSections(false);</script>
    <?php 
    if ($custom_font != "") 
        {
        ?><script>document.getElementById("question_global_font").hidden = true;</script>
    <?php
        } 
    config_generate_AutoSaveConfigOption_function($baseurl . '/pages/admin/admin_system_config.php'); 
    ?>
    <script>
    function debug_log_selector_onchange(el)
        {
        let value = jQuery(el).val();
        let options_to_show_duration = <?php echo json_encode([
            escape($lang['systemconsoleonallusers']),
            escape($lang['systemconfig_debug_log_on_specific_user']),
        ]);?>;

        // Display the user selection (if applicable)
        if (value === '<?php echo escape($lang['systemconfig_debug_log_on_specific_user']); ?>') {
            jQuery('#SystemConfigDebugForUser').removeClass('DisplayNone');
        } else {
            jQuery('#SystemConfigDebugForUser').addClass('DisplayNone');
        }

        // Display the timer
        if (options_to_show_duration.includes(value)) {
            jQuery('#question_system_config_debug_log_duration').removeClass('DisplayNone');
            create_debug_log_override();
        } else {
            jQuery('#question_system_config_debug_log_duration').addClass('DisplayNone');
        }

        if (value === '<?php echo escape($lang['off']); ?>') {
            create_debug_log_override(-1, -1);
        }
        return;
        }

    function create_debug_log_override(user_id, duration)
        {
        user_id = Number(typeof user_id !== 'undefined' ? user_id : jQuery('#debug_override_user').val());
        duration = Number(typeof duration !== 'undefined' ? duration : jQuery('#system_config_debug_log_duration_input').val());

        // Clearing the user is the same as having this enabled for all users.
        if (user_id === 0) {
            user_id = -1;
        }
        console.debug('create_debug_log_override(user_id = %o, duration = %o)', user_id, duration);

        jQuery.post(
            baseurl + '/pages/admin/admin_system_config.php',
            {
                ajax: true,
                action: 'create_debug_log_override',
                debug_override_user: user_id,
                debug_override_expires: duration,
                <?php echo generateAjaxToken('create_debug_log_override'); ?>
            },
            null,
            'json'
        )
            .done(function(data) {
                let system_config_debug_log_interim = jQuery('#system_config_debug_log_interim');
                if (system_config_debug_log_interim.data('timer_started')) {
                    system_config_debug_log_interim.data('reset_expiry', duration);
                } else {
                    debug_log_override_timer(duration, 'DebugLogOverrideTimerText')
                        .then(debug_log_override_timer_done);
                    system_config_debug_log_interim.data('timer_started', true);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown)
                {
                let response = typeof jqXHR.responseJSON.data.message !== 'undefined'
                    ? jqXHR.responseJSON.data.message
                    : textStatus;
                console.error("create_debug_log_override: %s - %s", errorThrown, response);
                });

        return;
        }

    function debug_log_override_timer(time_left, update_el)
        {
        console.debug('debug_log_override_timer(time_left = %o, update_el = %o)', time_left, update_el);
        return new Promise((resolve, reject) => {
            var debug_log_override_timer = setInterval(() => {
                let system_config_debug_log_interim = jQuery('#system_config_debug_log_interim');
                let reset_expiry = system_config_debug_log_interim.data('reset_expiry');

                // Reset the time left if the user changed settings while still running
                if (typeof reset_expiry !== 'undefined') {
                    time_left = Number(reset_expiry);
                    system_config_debug_log_interim.removeData('reset_expiry');
                }

                --time_left;

                document.getElementById(update_el).textContent = time_left;
                console.log('debug_log_override_timer: tick');

                if (time_left <= 0) {
                    document.getElementById(update_el).textContent = 0;
                    clearInterval(debug_log_override_timer);
                    resolve(true);
                }
            },
            1000);
        });
        }

    function debug_log_override_timer_done()
        {
        console.debug('debug_log_override_timer_done');
        let option_off = '<?php echo escape($lang['off']); ?>';
        let system_config_debug_log_interim = jQuery('#system_config_debug_log_interim');

        system_config_debug_log_interim.removeData('timer_started');
        
        if (system_config_debug_log_interim.val() !== option_off) {
            system_config_debug_log_interim.val(option_off).change();
        }
        }

<?php
if ($debug_log_override_timer_active)
    {
    ?>
    jQuery(function()
        {
        let system_config_debug_log_interim = jQuery('#system_config_debug_log_interim');
        debug_log_override_timer(<?php echo (int) $debug_log_override_time_left; ?>, 'DebugLogOverrideTimerText')
            .then(debug_log_override_timer_done);
        system_config_debug_log_interim.data('timer_started', true);
        });
    <?php
    }
?>
    </script>
</div>
<?php
include '../../include/footer.php';
