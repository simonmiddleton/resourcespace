<?php
include '../../../include/db.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    exit(error_alert($lang["error-permissiondenied"], true, 403));
    }

$plugin_name = 'museumplus';
if(!in_array($plugin_name, $plugins))
    {
    plugin_activate_for_setup($plugin_name);
    }

// This is processed in config_gen_setup_post() but it's losing the museumplus_modules_saved_config information. We 
// need it in order to be able to generate the HTML for it ($page_def).
if(getval('upload','') !== '')
    {
    $error = handle_rsc_upload($plugin_name);
    }

// Validate modules config (this is configurable on a different page - setup_module.php)
$museumplus_modules_config = plugin_decode_complex_configs(getval('museumplus_modules_saved_config', $museumplus_modules_saved_config));
if(!is_array($museumplus_modules_config))
    {
    $error = $lang['museumplus_error_unknown_type_saved_config'];
    $museumplus_modules_config = array();
    $museumplus_modules_saved_config = '';
    }



// API settings
$page_def[] = config_add_section_header($lang['museumplus_api_settings_header']);
$page_def[] = config_add_text_input('museumplus_host', $lang['museumplus_host']);
$page_def[] = config_add_text_input('museumplus_application', $lang['museumplus_application']);
$page_def[] = config_add_text_input('museumplus_api_user', $lang['museumplus_api_user']);
$page_def[] = config_add_text_input('museumplus_api_pass', $lang['museumplus_api_pass'], true);

// ResourceSpace settings
$page_def[] = config_add_section_header($lang['museumplus_RS_settings_header']);
$page_def[] = config_add_single_ftype_select(
    'museumplus_module_name_field',
    $lang['museumplus_module_name_field'],
    420,
    false,
    array(
        FIELD_TYPE_DROP_DOWN_LIST,
        FIELD_TYPE_RADIO_BUTTONS,
    )
);
$page_def[] = config_add_single_ftype_select(
    'museumplus_secondary_links_field',
    $lang['museumplus_secondary_links_field'],
    420,
    false,
    array(
        FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
        FIELD_TYPE_TEXT_BOX_MULTI_LINE,
        FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE,
    )
);

// Script settings
$page_def[] = config_add_section_header($lang['museumplus_script_header']);
$museumplus_script_last_ran = '';
check_script_last_ran(MPLUS_LAST_IMPORT, $museumplus_script_failure_notify_days, $museumplus_script_last_ran);
$script_last_ran_content = str_replace('%script_last_ran', $museumplus_script_last_ran, $lang['museumplus_last_run_date']);
$page_def[] = config_add_html($script_last_ran_content);
$page_def[] = config_add_boolean_select('museumplus_enable_script', $lang['museumplus_enable_script']);
$page_def[] = config_add_text_input('museumplus_interval_run', $lang['museumplus_interval_run']);
$page_def[] = config_add_text_input('museumplus_log_directory', $lang['museumplus_log_directory']);
// $page_def[] = config_add_single_ftype_select('museumplus_integrity_check_field', $lang['museumplus_integrity_check_field'], 420); # not in use until we can reliably get integrity checks of the data from M+

// MuseumPlus - modules configuration
$page_def[] = config_add_section_header($lang['museumplus_modules_configuration_header']);
$museumplus_modules_conf_html = "<div class=\"Question\">
    <table id=\"MplusModulesTable\">
        <tr>
            <th><strong>{$lang['museumplus_module']}</strong></th>
            <th><strong>{$lang['museumplus_mplus_id_field']}</strong></th>
            <th><strong>{$lang['museumplus_rs_uid_field']}</strong></th>
            <th><strong>{$lang['museumplus_applicable_resource_types']}</strong></th>
            <th><strong>{$lang['tools']}</strong></th>
        </tr>";
foreach($museumplus_modules_config as $module_conf_index => $module_conf)
    {
    $rs_uid_field = get_resource_type_field($module_conf['rs_uid_field']);
    $rs_uid_field = ($rs_uid_field !== false ? $rs_uid_field['title'] : '');

    $applicable_resource_types = $module_conf['applicable_resource_types'];
    if(!empty($applicable_resource_types))
        {
        $applicable_resource_types = get_resource_types(join(',', $applicable_resource_types));
        $applicable_resource_types = array_column($applicable_resource_types, 'name');
        }

    $museumplus_modules_conf_html .= sprintf(
        '<tr>
            <td><input type="text" class="shortwidth" value="%1$s" disabled></td>
            <td><input type="text" class="medwidth" value="%2$s" disabled></td>
            <td><input type="text" class="medwidth" value="%3$s" disabled></td>
            <td><input type="text" class="medwidth" value="%4$s" disabled></td>
            <td>
                <button type="button" onclick="museumplus_edit_module_conf(%5$s);">%6$s</button>
                <button type="button" onclick="museumplus_delete_module_conf(this, %5$s);">%7$s</button>
            </td>
        </tr>',
        htmlspecialchars($module_conf['module_name']),
        htmlspecialchars($module_conf['mplus_id_field']),
        htmlspecialchars($rs_uid_field),
        htmlspecialchars(join(', ', $applicable_resource_types)),
        $module_conf_index,
        $lang['action-edit'],
        $lang['action-delete']);
    }
$museumplus_modules_conf_html .= "</table>
    <a href=\"{$baseurl}/plugins/museumplus/pages/setup_module.php\" onclick=\"return CentralSpaceLoad(this, true);\">{$lang['museumplus_add_new_module']}</a>
";
$page_def[] = config_add_html($museumplus_modules_conf_html);
if(trim($museumplus_modules_saved_config) !== '')
    {
    $page_def[] = config_add_hidden_input('museumplus_modules_saved_config');
    }


if(get_utility_path("php") === false)
    {
    $error = $lang['museumplus_php_utility_not_found'];
    }

$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
if(isset($error))
    {
    render_top_page_error_style($error);
    }
config_gen_setup_html($page_def, $plugin_name, $upload_status, $lang['museumplus_configuration']);
?>
<script>
function museumplus_edit_module_conf(id)
    {
    var setup_module_url = '<?php echo $baseurl; ?>/plugins/museumplus/pages/setup_module.php?id=' + id;
    CentralSpaceLoad(setup_module_url, true);
    }

function museumplus_delete_module_conf(element, id)
    {
    if(confirm('<?php echo $lang["museumplus_confirm_delete_module_config"]; ?>') == false)
        {
        return;
        }

    CentralSpaceShowLoading();
    jQuery.ajax(
        {
        type: 'POST',
        url: '<?php echo $baseurl; ?>/plugins/museumplus/pages/setup_module.php',
        data: {
            id: id,
            action: 'delete',
            <?php echo generateAjaxToken('MplusModuleConfigForm'); ?>
        },
        dataType: "json"
        })
        .done(function(response, textStatus, jqXHR)
            {
            // Update the saved configs hidden input
            jQuery('input:hidden[name=museumplus_modules_saved_config]').val(response.data.modules_saved_configs)

            // Remove config record
            var button = jQuery(element);
            var record = jQuery(button).closest('tr');
            record.remove();
            })
        .fail(function(data, textStatus, jqXHR)
            {
            if(typeof data.responseJSON === 'undefined')
                {
                return;
                }

            var response = data.responseJSON;
            styledalert(response.data.title, response.data.message);
            })
        .always(function()
            {
            CentralSpaceHideLoading();
            });

    return;
    }
</script>
<?php
include '../../../include/footer.php';