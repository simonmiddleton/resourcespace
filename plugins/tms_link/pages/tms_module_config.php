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


// for posted data this will come from getval() otherwise it should come from existing plugin config OR default to these values
$tms_link_rs_uid_field = getval('tms_link_rs_uid_field', 0, true);
$tms_link_applicable_resource_types = getval('tms_link_applicable_resource_types', array());


$tms_link_config = get_plugin_config('tms_link');
if(!is_null($tms_link_config))
    {
    // TODO: Look up information needed to display page
    }

if(getval('save', '') !== '' && enforcePostRequest(false))
    {
    // TODO: process form and save to plugin config
    // set_plugin_config($plugin_name, $config);
    }

include '../../../include/header.php';
?>
<div class="BasicsBox">
    <p>TODO: go to previous page (ie. tms_link setup page</p>
    <h1><?php echo $lang["tms_link_tms_module_configuration"]; ?></h1>
    <?php
    if(isset($error))
        {
        echo "<div class=\"PageInformal\">{$error}</div>";
        }
    ?>
    <form method="post" action="#TODO:ChangeToRealAction" id="TmsModuleConfigForm">
        <?php generateFormToken("tms_module_config"); ?>
        <div class="Question">
            <label><?php echo $lang["tms_link_tms_module_name"]; ?></label>
            <input name="tms_link_module_name" type="text" class="stdwidth" value="">
            <div class="clearerleft"> </div>
        </div>
        <div class="Question">
            <label><?php echo $lang["tms_link_tms_uid_field"]; ?></label>
            <input name="tms_link_tms_uid_field" type="text" class="stdwidth" value="">
            <div class="clearerleft"> </div>
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
                    <tr>
                        <td>
                            <input type="text" class="medwidth" value="">
                        </td>
                        <td>
                            <select class="medwidth" name='rs_field[]'>
                                <option value=""><?php echo $lang['select']; ?></option>
                        <?php
                        $fields = sql_query('SELECT * FROM resource_type_field ORDER BY title, name');
                        foreach($fields as $field)
                            {
                            // TODO: change 0 to maping fieldid
                            $selected = (0 == $field['ref'] ? ' selected' : '');
                            ?>
                            <option value="<?php echo $field['ref']; ?>" <?php echo $selected; ?>><?php echo lang_or_i18n_get_translated($field['title'], 'fieldtitle-'); ?></option>
                            <?php
                            }
                        ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="srtwidth" value="">
                        </td>
                        <td>
                            <button type="button" id="delete_tms_module_1" onclick="delete_tms_field_mapping(1);"><?php echo $lang['action-delete']; ?></button>
                        </td>
                    </tr>



                    <tr>
                        <td>
                            <input type="text" class="medwidth" value="">
                        </td>
                        <td>
                            <select class="medwidth" name='rs_field[]'>
                                <option value=""><?php echo $lang['select']; ?></option>
                        <?php
                        $fields = sql_query('SELECT * FROM resource_type_field ORDER BY title, name');
                        foreach($fields as $field)
                            {
                            // TODO: change 0 to maping fieldid
                            $selected = (0 == $field['ref'] ? ' selected' : '');
                            ?>
                            <option value="<?php echo $field['ref']; ?>" <?php echo $selected; ?>><?php echo lang_or_i18n_get_translated($field['title'], 'fieldtitle-'); ?></option>
                            <?php
                            }
                        ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="srtwidth" value="">
                        </td>
                        <td>
                            <button type="button" id="delete_tms_module_1" onclick="add_new_tms_field_mapping();"><?php echo $lang['tms_link_add_mapping']; ?></button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <script>
            function add_new_tms_field_mapping()
                {
                // add client side the element. once submitting the form, it will be applied
                }

            function delete_tms_field_mapping(id)
                {
                // remove client side the element. once submitting the form, it will be applied
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