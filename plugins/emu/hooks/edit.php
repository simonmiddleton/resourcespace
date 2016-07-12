<?php
include_once dirname(__FILE__) . '/../include/emu_api.php';
include_once dirname(__FILE__) . '/../include/emu_functions.php';


function HookEmuEditEdithidefield($field)
    {
    global $ref, $resource, $emu_irn_field, $emu_resource_types;

    if($emu_irn_field == $field['ref'] && 0 > $ref && in_array($resource['resource_type'], $emu_resource_types))
        {
        return true;
        }

    return false;
    }


function HookEmuEditEditbeforesectionhead()
    {
    global $lang, $baseurl, $ref, $resource, $emu_irn_field, $emu_confirm_upload, $emu_resource_types;

    if(0 > $ref && in_array($resource['resource_type'], $emu_resource_types))
        {
        $value = htmlspecialchars(sql_value("SELECT `value` FROM resource_data WHERE resource = '{$ref}' AND resource_type_field = '{$emu_irn_field}'", ''));
        ?>
        <div id="question_emu" class="Question">
            <label for="question_emu"><?php echo $lang['emu_upload_emu_field_label']; ?></label>
            <input id="field_<?php echo $emu_irn_field; ?>" type="text" name="field_<?php echo $emu_irn_field; ?>" value="<?php echo $value; ?>">
            <div class="clearerleft"></div>
        </div>
        <?php
        if(isset($emu_confirm_upload) && $emu_confirm_upload)
            {
            ?>
            <div id="emu_confirm_upload" class="Question FieldSaveError">
                <label for="emu_confirm_upload"><?php echo $lang['emu_confirm_upload_nodata']; ?></label>
                <input id="emu_confirm_upload" type="checkbox" name="emu_confirm_upload" value="true">
                <div class="clearerleft"></div>
            </div>
            <?php
            }
        }
    }


function HookEmuAllAdditionalvalcheck($fields, $fields_item)
    {
    global $lang, $ref, $val, $resource, $emu_api_server, $emu_api_server_port, $emu_irn_field, $emu_resource_types, $emu_rs_saved_mappings;

    if(!in_array($resource['resource_type'], $emu_resource_types))
        {
        return false;
        }

    if($emu_irn_field == $fields_item['ref'])
        {
        $emu_form_post_id = getvalescaped("field_{$emu_irn_field}", '', true);
        $emu_irn          = intval($emu_form_post_id);

        global $emu_data;

        $emu_rs_mappings = unserialize(base64_decode($emu_rs_saved_mappings));
        $emu_data        = get_emu_data($emu_api_server, $emu_api_server_port, array($emu_irn), $emu_rs_mappings);

        // Make sure we actually do save this data, even if we return an error
        update_field($ref, $emu_irn_field, escape_check($emu_form_post_id));

        if(!is_array($emu_data) && 0 > $ref)
            {
            // We can't get any data from EMu for this new resource. Need to show warning if user has not already accepted this
            if('' == getval('emu_confirm_upload', ''))
                {
                global $emu_confirm_upload;

                $emu_confirm_upload = true;

                $error = "{$lang['emu_upload_nodata']} {$emu_form_post_id} {$lang['emu_confirm_upload_nodata']}";

                return $error;
                }
            }
        else
            {
            global $emu_import;

            $emu_import = true;

            return false;
            }
        }

    return false;
    }