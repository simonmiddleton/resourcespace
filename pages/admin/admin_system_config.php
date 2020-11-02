<?php
include '../../include/db.php';
include '../../include/authenticate.php'; if(!checkperm('a')) { exit('Permission denied.'); }
include_once '../../include/config_functions.php';


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
$page_def[] = config_add_html('</div>');



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
    'linkedheaderimgsrc',
    $lang['systemconfig_linkedheaderimgsrc_label'],
    $baseurl . '/pages/admin/admin_system_config.php',
    420
);
$page_def[] = config_add_file_input(
    'header_favicon',
    $lang['systemconfig_header_favicon_label'],
    $baseurl . '/pages/admin/admin_system_config.php',
    420
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
    "jQuery('#SearchBox').css('background',value); jQuery('#HomeSiteText.dashtext').css('background',value); jQuery('.HomePanelIN').css('background',value); jQuery('#BrowseBar').css('background',value); jQuery('#BrowseBarTab').css('background',value);"
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
$page_def[] = config_add_single_select('thumbs_default', $lang['userpreference_thumbs_default_label'], array('show' => $lang['showthumbnails'], 'hide' => $lang['hidethumbnails']), true, 420, '', true);
$page_def[] = config_add_boolean_select('resource_view_modal', $lang['userpreference_resource_view_modal_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('modal_default', $lang['systemconfig_modal_default'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('basic_simple_search', $lang['userpreference_basic_simple_search_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('comments_resource_enable', $lang['systemconfig_comments'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_single_select('upload_then_edit', $lang['default_upload_sequence'], array(true => $lang['upload_first_then_set_metadata'], false => $lang['set_metadata_then_upload']), true, 420, '', true);
$page_def[] = config_add_boolean_select('byte_prefix_mode_decimal', $lang['byte_prefix_mode_decimal'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('tilenav', $lang['userpreference_tilenavdefault'], $enable_disable_options, 420, '', true);
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
$page_def[] = config_add_single_select('default_perpage', $lang['userpreference_default_perpage_label'], array(24, 48, 72, 120, 240), false, 420, '', true);
$page_def[] = config_add_single_select(
    'default_display',
    $lang['userpreference_default_display_label'],
    array(
        'thumbs'      => $lang['largethumbstitle'],
        'xlthumbs'    => $lang['xlthumbstitle'],
        'list'        => $lang['listtitle']
    ),
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
$page_def[] = config_add_html('</div>');

// Workflow section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_workflow'] . '</h3><div id="SystemConfigWorkflowSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('research_request', $lang['researchrequest'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_html('</div>');


// Actions section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['actions'] . '</h3><div id="SystemConfigActionsSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('actions_enable', $lang['actions-enable'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('actions_resource_requests', $lang['actions_resource_requests_default'], $enable_disable_options, 300, '', true);
$page_def[] = config_add_boolean_select('actions_resource_review', $lang['actions_resource_review_default'], $enable_disable_options, 300, '', true);
$page_def[] = config_add_boolean_select('actions_account_requests', $lang['actions_account_requests_default'], $enable_disable_options, 300, '', true);
	
$page_def[] = config_add_html('</div>');

// Metadata section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['metadata'] . '</h3><div id="SystemConfigMetadataSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('metadata_report', $lang['metadata-report'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('metadata_read_default', $lang['embedded_metadata'], array($lang['embedded_metadata_donot_extract_option'], $lang['embedded_metadata_extract_option']), 420, '', true);
$page_def[] = config_add_boolean_select('speedtagging', $lang['speedtagging'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_single_ftype_select('speedtaggingfield', $lang['speedtaggingfield'], 420, false, $TEXT_FIELD_TYPES,true);
$page_def[] = config_add_html('</div>');


// User accounts section
$page_def[] = config_add_html('<h3 class="CollapsibleSectionHead collapsed">' . $lang['systemconfig_user_accounts'] . '</h3><div id="SystemConfigUserAccountsSection" class="CollapsibleSection">');
$page_def[] = config_add_boolean_select('allow_account_request', $lang['systemconfig_allow_account_request_label'], $yes_no_options, 420, '', true);
$page_def[] = config_add_boolean_select('terms_download', $lang['systemconfig_terms_download_label'], $enable_disable_options, 420, '', true);
$page_def[] = config_add_boolean_select('terms_login', $lang['systemconfig_terms_login_label'], $enable_disable_options, 420, '', true);
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

    $autosave_option_name  = getvalescaped('autosave_option_name', '');
    $autosave_option_value = getvalescaped('autosave_option_value', '');

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


config_process_file_input($page_def, 'system/config', $baseurl . '/pages/admin/admin_system_config.php');
$GLOBALS = $system_wide_config_options;

include '../../include/header.php';
?>
<div class="BasicsBox">
    <h1><?php echo $lang['systemconfig']; ?></h1>
    <p><?php echo $lang['systemconfig_description']; ?></p>
    <div class="CollapsibleSections">
    <?php
    config_generate_html($page_def);
    ?>
    </div>
    <script>registerCollapsibleSections(false);</script>
    <?php config_generate_AutoSaveConfigOption_function($baseurl . '/pages/admin/admin_system_config.php'); ?>
</div>
<?php
include '../../include/footer.php';
