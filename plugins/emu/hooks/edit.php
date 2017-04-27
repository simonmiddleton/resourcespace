<?php
include_once dirname(__FILE__) . '/../include/emu_api.php';
include_once dirname(__FILE__) . '/../include/emu_functions.php';


function HookEmuEditEdithidefield($field)
    {
    global $ref, $resource, $emu_irn_field, $emu_resource_types, $emu_created_by_script_field;

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
    global $lang, $ref, $resource, $emu_api_server, $emu_api_server_port, $emu_irn_field, $emu_resource_types, $emu_rs_saved_mappings;

    if(!in_array($resource['resource_type'], $emu_resource_types))
        {
        return false;
        }

    if($emu_irn_field == $fields_item['ref'])
        {
        global $emu_data;

        $emu_irn         = intval(getvalescaped("field_{$emu_irn_field}", '', true));
        $emu_rs_mappings = unserialize(base64_decode($emu_rs_saved_mappings));
        $emu_data        = get_emu_data($emu_api_server, $emu_api_server_port, array($emu_irn), $emu_rs_mappings);

        // Make sure we actually do save this data, even if we return an error
        update_field($ref, $emu_irn_field, escape_check($emu_irn));

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


function HookEmuEditSaveextraresourcedata($list)
    {
    global $emu_irn_field;

    $emu_irn = intval(getvalescaped("field_{$emu_irn_field}", '', true));

    if(0 == $emu_irn)
        {
        return false;
        }

    global $emu_api_server, $emu_api_server_port, $emu_rs_saved_mappings, $emu_data;

    $emu_rs_mappings = unserialize(base64_decode($emu_rs_saved_mappings));
    $emu_data        = get_emu_data($emu_api_server, $emu_api_server_port, array($emu_irn), $emu_rs_mappings);

    if(is_array($emu_data))
        {
        global $emu_import, $emu_update_list;

        $emu_import      = true;
        $emu_update_list = $list;
        }

    return false;
    }


function HookEmuEditAftersaveresourcedata()
    {
    global $emu_import;

    if(!isset($emu_import) || (isset($emu_import) && !$emu_import))
        {
        return false;
        }

    // Update Resource with EMu data
    global $ref, $emu_irn_field, $emu_rs_saved_mappings, $emu_data, $emu_update_list;

    $emu_irn         = intval(getvalescaped("field_{$emu_irn_field}", '', true));
    $emu_rs_mappings = unserialize(base64_decode($emu_rs_saved_mappings));

    // Not a batch edit, make up the $list array so we can pretend it is
    if(!is_array($emu_update_list))
        {
        $emu_update_list    = array();
        $emu_update_list[0] = $ref;
        }

    foreach($emu_update_list as $resource_ref)
        {
        debug("emu: Updating resource ID #{$resource_ref} with data from EMu database");

        foreach($emu_rs_mappings as $emu_module => $emu_module_columns)
            {
            foreach($emu_module_columns as $emu_module_column => $rs_field_id)
                {
                if(0 != intval($rs_field_id) && isset($emu_data[$emu_irn][$emu_module_column]) && $emu_irn_field != $rs_field_id)
                    {
                    update_field($resource_ref, $rs_field_id, escape_check($emu_data[$emu_irn][$emu_module_column]));
                    }
                }
            }
        }

    return;
    }