<?php
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit($lang['error-permissiondenied']);
    }
include_once '../include/emu_functions.php';


$plugin_name = 'emu';
if(!in_array($plugin_name, $plugins))
    {
    plugin_activate_for_setup($plugin_name);
    }

$emu_rs_mappings               = unserialize(base64_decode($emu_rs_saved_mappings));
$emu_script_last_ran           = '';
$emu_config_modified_timestamp = time();

check_script_last_ran('last_emu_import', $emu_script_failure_notify_days, $emu_script_last_ran);


// Save module - column - rs_field mappings
if('' != getval('submit', '') || '' != getval('save', ''))
    {
    $emu_module          = getvalescaped('emu_module', array());
    $emu_column          = getvalescaped('emu_column', array());
    $rs_field            = getvalescaped('rs_field', array());
    $emu_rs_mappings_new = array();

    // There should always be the same number of values in each array
    for($i = 0; $i < count($emu_module); $i++)
        {
        if('' == trim($emu_module[$i]))
            {
            continue;
            }

        if('' == trim($emu_column[$i]))
            {
            continue;
            }

        // Do not allow empty RS fields to be saved. We require a full map
        if('' == $rs_field[$i])
            {
            continue;
            }

        // User selected to remove this field from the map
        if('delete' == $rs_field[$i])
            {
            continue;
            }

        $emu_rs_mappings_new[$emu_module[$i]][$emu_column[$i]] = $rs_field[$i];
        }

    $emu_rs_mappings       = $emu_rs_mappings_new;
    $emu_rs_saved_mappings = base64_encode(serialize($emu_rs_mappings_new));
    }

// Add test script functionality
// For now, only for sync mode
if(EMU_SCRIPT_MODE_SYNC == $emu_script_mode)
    {
    $scripts_test_functionality = '<button type="button" onclick="testScript(document.getElementById(\'emu_script_mode\').value);" style="font-size: 1em;">Test script</button>';
    }
$script_last_ran_content = str_replace('%script_last_ran%', $emu_script_last_ran, $lang['emu_last_run_date']);
$script_last_ran_content = str_replace('%scripts_test_functionality%', (isset($scripts_test_functionality) ? $scripts_test_functionality : ''), $script_last_ran_content);



// API server settings
$page_def[] = config_add_section_header($lang['emu_api_settings']);
$page_def[] = config_add_text_input('emu_api_server', $lang['emu_api_server']);
$page_def[] = config_add_text_input('emu_api_server_port', $lang['emu_api_server_port']);

// EMUu script
$page_def[] = config_add_section_header($lang['emu_script_header']);
$page_def[] = config_add_html($script_last_ran_content);
$page_def[] = config_add_single_select('emu_script_mode',
    $lang['emu_script_mode'], array(
        EMU_SCRIPT_MODE_IMPORT => $lang['emu_script_mode_option_1'],
        EMU_SCRIPT_MODE_SYNC   => $lang['emu_script_mode_option_2']
    )
);
$page_def[] = config_add_boolean_select('emu_enable_script', $lang['emu_enable_script']);
$page_def[] = config_add_boolean_select('emu_test_mode', $lang['emu_test_mode']);
$page_def[] = config_add_text_input('emu_interval_run', $lang['emu_interval_run']);
$page_def[] = config_add_text_input('emu_email_notify', $lang['emu_email_notify']);
$page_def[] = config_add_text_input('emu_script_failure_notify_days', $lang['emu_script_failure_notify_days']);
$page_def[] = config_add_text_input('emu_log_directory', $lang['emu_log_directory']);
$page_def[] = config_add_single_ftype_select('emu_created_by_script_field', $lang['emu_created_by_script_field']);

// EMu settings
$page_def[] = config_add_section_header($lang['emu_settings_header']);
$page_def[] = config_add_single_ftype_select('emu_irn_field', $lang['emu_irn_field']);
$page_def[] = config_add_multi_rtype_select('emu_resource_types', $lang['emu_resource_types']);
if(EMU_SCRIPT_MODE_SYNC == $emu_script_mode)
    {
    $page_def[] = config_add_text_input('emu_search_criteria', $lang['emu_search_criteria']);
    }

// EMu - ResourceSpace mappings
$page_def[] = config_add_section_header($lang['emu_rs_mappings_header']);
$emu_rs_mappings_html = "
<div class='Question'>
    <table id='emuRsMappingTable'>
        <tr>
            <th><strong>{$lang['emu_module']}</strong></th>
            <th><strong>{$lang['emu_column_name']}</strong></th>
            <th><strong>{$lang['emu_rs_field']}</strong></th>
        </tr>";

$metadata_fields = get_resource_type_fields('', 'title, name');

foreach($emu_rs_mappings as $emu_module => $emu_module_columns)
    {
    foreach($emu_module_columns as $emu_module_column => $emu_rs_field)
        {
        $row_id = 'row_' . htmlspecialchars("{$emu_module}_{$emu_module_column}");

        $emu_rs_mappings_html .= "
        <tr id ='{$row_id}'>
            <td><input type='text' name='emu_module[]' value='{$emu_module}'></td>
            <td><input type='text' name='emu_column[]' value='{$emu_module_column}'></td>
            <td>
                <select name='rs_field[]' style='width: 300px'>
                    <option value='' " . (0 == $emu_rs_field ? ' selected' : '') . "></option>
                    <option value='delete'>--- {$lang['action-delete']} ---</option>";
        foreach($metadata_fields as $metadata_field)
            {
            $emu_rs_mappings_html .= "<option value='{$metadata_field['ref']}' " . ($emu_rs_field == $metadata_field['ref'] ? 'selected' : '') . ">" . lang_or_i18n_get_translated($metadata_field['title'], 'fieldtitle-') . "</option>";
            }
        $emu_rs_mappings_html .= '</select></td></tr>';
        }
    }

$emu_rs_mappings_html .= '
<tr id ="newrow">
    <td><input type="text" name="emu_module[]" value=""></td>
    <td><input type="text" name="emu_column[]" value=""></td>
    <td>
        <select name="rs_field[]" style="width: 300px">
            <option value="" selected></option>';
foreach($metadata_fields as $metadata_field)
    {
    $emu_rs_mappings_html .= "<option value='{$metadata_field['ref']}'>" . lang_or_i18n_get_translated($metadata_field['title'], 'fieldtitle-') . "</option>";
    }
$emu_rs_mappings_html .= "
        </select> 
    </td>
</tr>
</table>

<a onclick='addEmuRsMappingRow();'>{$lang['emu_add_mapping']}</a>
</div>
<!-- end of Question -->";
$page_def[] = config_add_html($emu_rs_mappings_html);
$page_def[] = config_add_hidden('emu_rs_saved_mappings');
$page_def[] = config_add_hidden('emu_config_modified_timestamp');


if(!isset($php_path) || '' == $php_path)
    {
    $error = '$php_path config option MUST be set in order for testing scripts functionality to work!';
    }

$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
if(isset($error))
    {
    echo "<div class=\"PageInformal\">{$error}</div>";
    }
config_gen_setup_html($page_def, $plugin_name, $upload_status, $lang['emu_configuration']);
?>
<script>
function addEmuRsMappingRow()
    {
    var table    = document.getElementById('emuRsMappingTable');
    var rowCount = table.rows.length;
    var row      = table.insertRow(rowCount);

    row.innerHTML = document.getElementById('newrow').innerHTML;
    }

function testScript(script)
    {
    if(script <= 0)
        {
        return false;
        }

    ModalLoad('<?php echo $baseurl; ?>/plugins/emu/pages/emu_test_script.php?script=' + script);

    return true;
    }
</script>
<?php
include '../../../include/footer.php';