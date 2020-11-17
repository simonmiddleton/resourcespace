<?php
include '../../../include/db.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    exit(error_alert($lang["error-permissiondenied"], true, 403));
    }

$museumplus_modules_config = plugin_decode_complex_configs($museumplus_modules_saved_config);

$plugin_name = 'museumplus';
$plugin_yaml_path = get_plugin_path($plugin_name) . "/{$plugin_name}.yaml";
$plugin_yaml = get_plugin_yaml($plugin_yaml_path, false);
$breadcrumbs = array(
    array(
        'title' => $lang["systemsetup"],
        'href'  => "{$baseurl_short}pages/admin/admin_home.php"
    ),
    array(
        'title' => $lang["pluginmanager"],
        'href'  => "{$baseurl_short}pages/team/team_plugins.php"
    ),
    array(
        'title' => $lang['museumplus_configuration'],
        'href'  => $baseurl_short . $plugin_yaml['config_url']
    ),
    array(
        'title' => $lang['museumplus_module_setup'],
    ),
);

$id = getval('id', 0, true);
$action = getval('action', '');


// TODO: save/new/delete actions


$museumplus_module_name = '';
$museumplus_mplus_id_field = '';
$museumplus_rs_uid_field = null;
$museumplus_applicable_resource_types = array();
$museumplus_media_sync = false;
$museumplus_media_sync_df_field = null; # must be a checkbox type with only one option as all we'll check is if the resource will have this field set (e.g a field like 'sync with CMS' : yes)
$museumplus_field_mappings = array();
if(isset($museumplus_modules_config[$id]))
    {
    $record = $museumplus_modules_config[$id];

    $museumplus_module_name = $record['module_name'];
    $museumplus_mplus_id_field = $record['mplus_id_field'];
    $museumplus_rs_uid_field = $record['rs_uid_field'];
    $museumplus_applicable_resource_types = $record['applicable_resource_types'];
    $museumplus_media_sync = $record['media_sync'];
    $museumplus_media_sync_df_field = $record['media_sync_df_field'];
    $museumplus_field_mappings = $record['field_mappings'];
    }

$form_action = generateURL(
    "{$baseurl}/plugins/{$plugin_name}/pages/setup_module.php",
    array(
        'id' => $id,
    )
);
$rtfs = sql_query('SELECT * FROM resource_type_field ORDER BY title, name', 'schema');

include '../../../include/header.php';
?>
<div class="BasicsBox">
<?php
renderBreadcrumbs($breadcrumbs);
if(isset($error))
    {
    render_top_page_error_style($error);
    }
?>
    <form id="MplusModuleConfigForm" method="post" action="<?php echo $form_action; ?>">
    <?php generateFormToken("mplus_module_config"); ?>
    <div class="Question">
        <label><?php echo $lang["museumplus_module_name"]; ?></label>
        <input name="museumplus_module_name" type="text" class="stdwidth" value="<?php echo htmlspecialchars($museumplus_module_name); ?>">
        <div class="clearerleft"></div>
    </div>
    <div class="Question">
        <label><?php echo $lang["museumplus_mplus_id_field"]; ?></label>
        <input name="museumplus_mplus_id_field" type="text" class="stdwidth" value="<?php echo htmlspecialchars($museumplus_mplus_id_field); ?>">
        <div class="clearerleft"></div>
    </div>
    <?php
    render_field_selector_question(
        $lang["museumplus_rs_uid_field"],
        "museumplus_rs_uid_field",
        array(FIELD_TYPE_TEXT_BOX_SINGLE_LINE),
        "stdwidth",
        false,
        $museumplus_rs_uid_field);
    config_multi_rtype_select(
        "museumplus_applicable_resource_types",
        $lang["museumplus_applicable_resource_types"],
        $museumplus_applicable_resource_types,
        420);
    config_boolean_select('museumplus_media_sync', $lang['museumplus_media_sync'], $museumplus_media_sync);
    render_field_selector_question(
        $lang["museumplus_media_sync_df_field"],
        "museumplus_media_sync_df_field",
        array(FIELD_TYPE_CHECK_BOX_LIST),
        "stdwidth",
        false,
        $museumplus_media_sync_df_field);
    ?>
        <div class="Question">
            <label for="buttons"><?php echo $lang["museumplus_field_mappings"]; ?></label>
            <table id="MplusModuleFieldsMappingTable">
                <tbody>
                    <tr>
                        <th><strong><?php echo $lang["museumplus_mplus_field_name"]; ?></strong></th>
                        <th><strong><?php echo $lang["museumplus_rs_field"]; ?></strong></th>
                        <th><strong><!-- actions --></strong></th>
                    </tr>
                <?php
                foreach($museumplus_field_mappings as $mapping_index => $mapping)
                    {
                    ?>
                    <tr>
                        <td>
                            <input class="medwidth"
                                   type="text"
                                   name="museumplus_field_mappings[<?php echo $mapping_index; ?>][field_name]"
                                   value="<?php echo htmlspecialchars($mapping['field_name']); ?>">
                        </td>
                        <td>
                            <select class="medwidth" name="museumplus_field_mappings[<?php echo $mapping_index; ?>][rs_field]">
                                <option value=""><?php echo $lang['select']; ?></option>
                            <?php
                            foreach($rtfs as $rtf)
                                {
                                $selected = ($mapping['rs_field'] == $rtf['ref'] ? ' selected' : '');
                                $option_text = htmlspecialchars(lang_or_i18n_get_translated($rtf['title'], 'fieldtitle-'));
                                ?>
                                <option value="<?php echo $rtf['ref']; ?>" <?php echo $selected; ?>><?php echo $option_text; ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <button type="button" onclick="museumplus_delete_field_mapping(this);"><?php echo $lang['action-delete']; ?></button>
                        </td>
                    </tr>
                    <?php
                    }
                    ?>
                    <tr>
                        <td colspan="4">
                            <button type="button" onclick="museumplus_add_new_field_mapping(this);"><?php echo $lang['museumplus_add_mapping']; ?></button>
                        </td>
                    </tr>
                </tbody>
            </table><!-- end of MplusModuleFieldsMappingTable -->
            <div class="clearerleft"></div>
        </div>
        <div class="QuestionSubmit">
            <label></label>
            <input type="submit" name="save" value="<?php echo $lang["save"]; ?>">
        </div>
    </form>  <!-- end of MplusModuleConfigForm -->
</div> <!-- end of BasicBox -->
<script>
function museumplus_add_new_field_mapping(element)
    {
    var button = jQuery(element);
    var row_index = document.getElementById('MplusModuleFieldsMappingTable').rows.length - 2;
    var new_row_html = '';

    new_row_html += '<tr>';
    new_row_html += '<td><input class="medwidth" type="text" name="museumplus_field_mappings[' + row_index + '][field_name]" value=""></td>';
    new_row_html += '<td><select class="medwidth" name="museumplus_field_mappings[' + row_index + '][rs_field]">';
    new_row_html += '<option value=""><?php echo $lang['select']; ?></option>';
    <?php
    foreach($rtfs as $rtf)
        {
        $option_text = htmlspecialchars(lang_or_i18n_get_translated($rtf['title'], 'fieldtitle-'));
        ?>
        new_row_html += '<option value="<?php echo $rtf['ref']; ?>"><?php echo $option_text; ?></option>';
        <?php
        }
    ?>
    new_row_html += '</select></td>';
    new_row_html += '<td><button type="button" onclick="museumplus_delete_field_mapping(this);"><?php echo $lang['action-delete']; ?></button></td>';
    new_row_html += '</tr>';
    jQuery(new_row_html).insertBefore(jQuery(button).closest('tr'));

    museumplus_reindex_table();
    }

function museumplus_delete_field_mapping(element)
    {
    var button = jQuery(element);
    var record = jQuery(button).closest('tr');
    record.remove();
    museumplus_reindex_table();
    }

// Re-index the 'name' attribute when adding/deleting mapping records
function museumplus_reindex_table()
    {
    jQuery('#MplusModuleFieldsMappingTable tr').not(':first').not(':last').each(function(i) 
        {
        var field_name = "museumplus_field_mappings[" + i + "][field_name]";
        var rs_field = "museumplus_field_mappings[" + i + "][rs_field]";

        // Change name of each input/select to use the correct index
        jQuery(this).find('td').eq(0).find('input').attr('name', field_name);
        jQuery(this).find('td').eq(1).find('select').attr('name', rs_field);
        });
    }
</script><!-- end of museumplus setup_module script -->
<?php
include '../../../include/footer.php';