<?php
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    http_response_code(401);
    exit($lang['error-permissiondenied']);
    }
include_once '../include/museumplus_functions.php';


$plugin_name = 'museumplus';
if(!in_array($plugin_name, $plugins))
    {
    plugin_activate_for_setup($plugin_name);
    }

$museumplus_rs_mappings = unserialize(base64_decode($museumplus_rs_saved_mappings));

// Save MuseumPlus - RS mappings
if('' != getval('submit', '') || '' != getval('save', ''))
    {
    $mplus_field_name      = getvalescaped('mplus_field_name', array());
    $rs_field              = getvalescaped('rs_field', array());
    $mplus_rs_mappings_new = array();

    // There should always be the same number of values in each array
    for($i = 0; $i < count($mplus_field_name); $i++)
        {
        if('' == trim($mplus_field_name[$i]))
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

        $mplus_rs_mappings_new[$mplus_field_name[$i]] = $rs_field[$i];
        }

    $museumplus_rs_mappings = $mplus_rs_mappings_new;
    $museumplus_rs_saved_mappings  = base64_encode(serialize($mplus_rs_mappings_new));
    }



// API settings
$page_def[] = config_add_section_header($lang['museumplus_api_settings_header']);
$page_def[] = config_add_text_input('museumplus_host', $lang['museumplus_host']);
$page_def[] = config_add_text_input('museumplus_application', $lang['museumplus_application']);
$page_def[] = config_add_text_input('museumplus_api_user', $lang['museumplus_api_user']);
$page_def[] = config_add_text_input('museumplus_api_pass', $lang['museumplus_api_pass'], true);
$page_def[] = config_add_text_input('museumplus_search_mpid_field', $lang['museumplus_search_match_field']);

// ResourceSpace settings
$page_def[] = config_add_section_header($lang['museumplus_RS_settings_header']);
$page_def[] = config_add_single_ftype_select('museumplus_mpid_field', $lang['museumplus_mpid_field'], 420);
$page_def[] = config_add_multi_rtype_select('museumplus_resource_types', $lang['museumplus_resource_types'], 420);

// Script settings
$page_def[] = config_add_section_header($lang['museumplus_script_header']);
$museumplus_script_last_ran = '';
check_script_last_ran('last_museumplus_import', $museumplus_script_failure_notify_days, $museumplus_script_last_ran);
$script_last_ran_content = str_replace('%script_last_ran', $museumplus_script_last_ran, $lang['museumplus_last_run_date']);
$page_def[] = config_add_html($script_last_ran_content);
$page_def[] = config_add_boolean_select('museumplus_enable_script', $lang['museumplus_enable_script']);
$page_def[] = config_add_text_input('museumplus_interval_run', $lang['museumplus_interval_run']);
$page_def[] = config_add_text_input('museumplus_log_directory', $lang['museumplus_log_directory']);
// $page_def[] = config_add_single_ftype_select('museumplus_integrity_check_field', $lang['museumplus_mpid_field'], 420); # not in use until we can reliably get integrity checks of the data from M+

// MuseumPlus - ResourceSpace mappings
$page_def[] = config_add_section_header($lang['museumplus_rs_mappings_header']);
$museumplus_rs_mappings_html = "
<div class='Question'>
    <table id='MplusRsMappingTable'>
        <tr>
            <th><strong>{$lang['museumplus_mplus_field_name']}</strong></th>
            <th><strong>{$lang['museumplus_rs_field']}</strong></th>
        </tr>";

$metadata_fields = get_resource_type_fields('', 'title, name');

foreach($museumplus_rs_mappings as $mplus_field_name => $mplus_rs_field)
    {
    $row_id = 'row_' . htmlspecialchars("{$mplus_field_name}_{$mplus_rs_field}");

    $museumplus_rs_mappings_html .= "
    <tr id ='{$row_id}'>
        <td><input type='text' name='mplus_field_name[]' value='{$mplus_field_name}'></td>
        <td>
            <select name='rs_field[]' class='stdwidth'>
                <option value='' " . (0 == $mplus_rs_field ? ' selected' : '') . "></option>
                <option value='delete'>--- {$lang['action-delete']} ---</option>";
    foreach($metadata_fields as $metadata_field)
        {
        $museumplus_rs_mappings_html .= "<option value='{$metadata_field['ref']}' " . ($mplus_rs_field == $metadata_field['ref'] ? 'selected' : '') . ">" . lang_or_i18n_get_translated($metadata_field['title'], 'fieldtitle-') . "</option>";
        }
    $museumplus_rs_mappings_html .= '</select></td></tr>';
    }


$museumplus_rs_mappings_html .= '
<tr id ="newrow">
    <td><input type="text" name="mplus_field_name[]" value=""></td>
    <td>
        <select name="rs_field[]" class="stdwidth">
            <option value="" selected></option>';
foreach($metadata_fields as $metadata_field)
    {
    $museumplus_rs_mappings_html .= "<option value='{$metadata_field['ref']}'>" . lang_or_i18n_get_translated($metadata_field['title'], 'fieldtitle-') . "</option>";
    }
$museumplus_rs_mappings_html .= "
                </select> 
            </td>
        </tr>
    </table>

    <a onclick='addMplusRsMappingRow();'>{$lang['museumplus_add_mapping']}</a>
</div>
<!-- end of Question -->";
$page_def[] = config_add_html($museumplus_rs_mappings_html);
$page_def[] = config_add_hidden('museumplus_rs_saved_mappings');



if(get_utility_path("php") === false)
    {
    $error = $lang['museumplus_php_utility_not_found'];
    }

$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
if(isset($error))
    {
    $error = htmlspecialchars($error);
    echo "<div class=\"PageInformal\">{$error}</div>";
    }
config_gen_setup_html($page_def, $plugin_name, $upload_status, $lang['museumplus_configuration']);
?>
<script>
function addMplusRsMappingRow()
    {
    var table    = document.getElementById('MplusRsMappingTable');
    var rowCount = table.rows.length;
    var row      = table.insertRow(rowCount);

    row.innerHTML = document.getElementById('newrow').innerHTML;
    }
</script>
<?php
include '../../../include/footer.php';