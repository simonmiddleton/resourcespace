<?php
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    http_response_code(401);
    exit($lang["error-permissiondenied"]);
    }
include_once '../../../include/render_functions.php';


$id = getval('id', '');


$tms_link_modules_mappings = unserialize(base64_decode($tms_link_modules_saved_mappings));

// TODO: remove once save functionality works
$tms_link_modules_mappings['5be059413088d'] = array(
    'module_name'   => 'exhibition_data',
    'tms_uid_field' => 'ExhibitionID',
    'rs_uid_field'  => 'RS ExhibitionID',
    'applicable_resource_types' => array(1),
    'tms_rs_mappings' => array(
        array(
            'tms_column' => 'ObjectID',
            'rs_field' => 73,
            'encoding' => 'UTF-8'
        ),
        array(
            'tms_column' => 'ObjectNumber',
            'rs_field' => 75,
            'encoding' => 'UTF-8'
        ),
        array(
            'tms_column' => 'Department',
            'rs_field' => 74,
            'encoding' => 'UTF-16'
        ),
    ),
);

// for posted data this will come from getval() otherwise it should come from existing plugin config OR default to these values
$tms_link_rs_uid_field = getval('tms_link_rs_uid_field', 0, true);
$tms_link_applicable_resource_types = getval('tms_link_applicable_resource_types', array());
$tms_link_tms_rs_mappings = getval('tms_rs_mappings', array());
// echo "<pre>";print_r($tms_link_tms_rs_mappings);echo "</pre>";die("You died in file " . __FILE__ . " at line " . __LINE__);

$tms_link_config = get_plugin_config('tms_link');
if(!is_null($tms_link_config))
    {
    // TODO: Look up information needed to display page
    }

if(getval('save', '') !== '' && enforcePostRequest(false))
    {
    // TODO: process form and save to plugin config
    // generate a UID using uniqid()
    // set_plugin_config($plugin_name, $config);
    }

// Existing record
if($id !== '' && array_key_exists($id, $tms_link_modules_mappings))
    {
    $record = $tms_link_modules_mappings[$id];

    $tms_link_module_name = $record['module_name'];
    $tms_link_tms_uid_field = $record['tms_uid_field'];
    $tms_link_rs_uid_field = $record['rs_uid_field'];
    $tms_link_applicable_resource_types = $record['applicable_resource_types'];
    $tms_link_tms_rs_mappings = $record['tms_rs_mappings'];
    }

include '../../../include/header.php';
?>
<div class="BasicsBox">
    <h1><?php echo $lang["tms_link_tms_module_configuration"]; ?></h1>
    <?php
    if(isset($error))
        {
        echo "<div class=\"PageInformal\">{$error}</div>";
        }
    ?>
    <form id="TmsModuleConfigForm" method="post" action="#TODO:ChangeToRealAction">
        <?php generateFormToken("tms_module_config"); ?>
        <div class="Question">
            <label><?php echo $lang["tms_link_tms_module_name"]; ?></label>
            <input name="tms_link_module_name" type="text" class="stdwidth" value="<?php echo htmlspecialchars($tms_link_module_name); ?>">
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label><?php echo $lang["tms_link_tms_uid_field"]; ?></label>
            <input name="tms_link_tms_uid_field" type="text" class="stdwidth" value="<?php echo htmlspecialchars($tms_link_tms_uid_field); ?>">
            <div class="clearerleft"></div>
        </div>
        <?php
        render_field_selector_question(
            $lang["tms_link_rs_uid_field"],
            "tms_link_rs_uid_field",
            array(),
            "stdwidth",
            false,
            $tms_link_rs_uid_field);
        config_multi_rtype_select(
            "tms_link_applicable_resource_types",
            $lang["tms_link_applicable_rt"],
            $tms_link_applicable_resource_types,
            420);
        ?>
        <div class="Question">
            <label for="buttons"><?php echo $lang["tms_link_field_mappings"]; ?></label>
            <table id="tmsModulesMappingTable">
                <tbody>
                    <tr>
                        <th><strong><?php echo $lang["tms_link_column_name"]; ?></strong></th>
                        <th><strong><?php echo $lang["tms_link_resourcespace_field"]; ?></strong></th>
                        <th><strong><?php echo "{$lang["tms_link_column_name"]} {$lang["tms_link_encoding"]}"; ?></strong></th>
                        <th><strong></strong></th>
                    </tr>
                <?php
                foreach($tms_link_tms_rs_mappings as $tms_rs_mapping_index => $tms_rs_mapping)
                    {
                    ?>
                    <tr>
                        <td>
                            <input class="medwidth"
                                   type="text"
                                   name="tms_rs_mappings[<?php echo $tms_rs_mapping_index; ?>][tms_column]"
                                   value="<?php echo htmlspecialchars($tms_rs_mapping['tms_column']); ?>">
                        </td>
                        <td>
                            <select class="medwidth" name="tms_rs_mappings[<?php echo $tms_rs_mapping_index; ?>][rs_field]">
                                <option value=""><?php echo $lang['select']; ?></option>
                        <?php
                        $fields = sql_query('SELECT * FROM resource_type_field ORDER BY title, name');
                        foreach($fields as $field)
                            {
                            $selected = ($tms_rs_mapping['rs_field'] == $field['ref'] ? ' selected' : '');
                            $option_text = lang_or_i18n_get_translated($field['title'], 'fieldtitle-');
                            ?>
                            <option value="<?php echo $field['ref']; ?>" <?php echo $selected; ?>><?php echo $option_text; ?></option>
                            <?php
                            }
                        ?>
                            </select>
                        </td>
                        <td>
                            <input class="srtwidth"
                                   type="text"
                                   name="tms_rs_mappings[<?php echo $tms_rs_mapping_index; ?>][encoding]"
                                   value="<?php echo htmlspecialchars($tms_rs_mapping['encoding']); ?>">
                        </td>
                        <td>
                            <button type="button" onclick="delete_tms_field_mapping(this);"><?php echo $lang['action-delete']; ?></button>
                        </td>
                    </tr>
                    <?php
                    }
                    ?>
                    <tr>
                        <td colspan="4">
                            <button type="button" onclick="add_new_tms_field_mapping(this);"><?php echo $lang['tms_link_add_mapping']; ?></button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <script>
            function add_new_tms_field_mapping(element)
                {
                var button = jQuery(element);
                var row_index = document.getElementById('tmsModulesMappingTable').rows.length;
                var new_row_html = '';

                new_row_html += '<tr>';
                new_row_html += '<td><input class="medwidth" type="text" name="tms_rs_mappings[' + row_index + '][tms_column]" value=""></td>';
                new_row_html += '<td><select class="medwidth" name="tms_rs_mappings[' + row_index + '][rs_field]">';
                new_row_html += '<option value=""><?php echo $lang['select']; ?></option>';
                <?php
                $fields = sql_query('SELECT * FROM resource_type_field ORDER BY title, name');
                foreach($fields as $field)
                    {
                    $option_text = lang_or_i18n_get_translated($field['title'], 'fieldtitle-');
                    ?>
                    new_row_html += '<option value="<?php echo $field['ref']; ?>"><?php echo $option_text; ?></option>';
                    <?php
                    }
                ?>
                new_row_html += '</select>';
                new_row_html += '</td>';
                new_row_html += '<td><input class="srtwidth" type="text" name="tms_rs_mappings[' + row_index + '][encoding]" value=""></td>';
                new_row_html += '<td><button type="button" onclick="delete_tms_field_mapping(this);"><?php echo $lang['action-delete']; ?></button></td>';
                new_row_html += '</tr>';

                jQuery(new_row_html).insertBefore(jQuery(button).closest('tr'));
                }

            function delete_tms_field_mapping(element)
                {
                var button = jQuery(element);
                var record = jQuery(button).closest('tr');

                record.remove();
                }
            </script>
        </div>
        <div class="QuestionSubmit">
            <label for="buttons"></label>
            <input name="save" type="submit" value="<?php echo $lang["save"]; ?>">
        </div>
    </form>
</div>
<?php
include '../../../include/footer.php';