<?php
function HookTms_linkEditEditbeforesectionhead()
	{
	global $lang,$baseurl,$tms_link_object_id_field, $ref,$resource,$tms_confirm_upload,$tms_link_resource_types;

    if($ref > 0)
        {
        return;
        }

    $resource_type_allowed = array();

    foreach(tms_link_get_modules_mappings() as $module_uid => $module)
        {
        if(!in_array($resource['resource_type'], $module['applicable_resource_types']))
            {
            continue;
            }

        $resource_type_allowed[] = true;
        $field_label = str_replace(
            array("%module_name", "%tms_uid_field"),
            array($module['module_name'], $module['tms_uid_field']),
            $lang["tms_link_uid_field"]);
        $input_identifier = "field_{$module_uid}_{$module['rs_uid_field']}";
        ?>
		<div class="Question">
			<label for="<?php echo $input_identifier; ?>"><?php echo $field_label; ?></label>
			<input id="<?php echo $input_identifier; ?>" name="<?php echo $input_identifier; ?>" type="text" value="<?php echo htmlspecialchars(get_data_by_field($ref, $module['rs_uid_field'])); ?>">
			<div class="clearerleft"></div>
		</div>
		<?php
        }

    if(!empty($resource_type_allowed) && isset($tms_confirm_upload) && $tms_confirm_upload)
        {
        ?>
        <div class="Question FieldSaveError" id="tms_confirm_upload">
            <label for="tms_confirm_upload"><?php echo $lang["tms_link_confirm_upload_nodata"] ?></label>
            <input type="checkbox" id="tms_confirm_upload" name="tms_confirm_upload" value="true">
            <div class="clearerleft"></div>
        </div>
        <?php
        }
	}
	
function HookTMS_linkEditEdithidefield($field)
	{
	global $tms_link_object_id_field,$ref,$resource,$tms_link_resource_types;

    $field_ref_ok = false;
    $resource_type_allowed = false;

    if(tms_link_is_rs_uid_field($field['ref']) && $ref < 0)
        {
        $field_ref_ok = true;
        }

    foreach(tms_link_get_modules_mappings() as $module)
        {
        if(in_array($resource['resource_type'], $module['applicable_resource_types']))
            {
            $resource_type_allowed = true;
            break;
            }
        }

    if($field_ref_ok && $resource_type_allowed)
        {
        return true;
        }

    return false;
	}


function HookTms_linkAllAdditionalvalcheck($fields, $fieldsitem)
	{
	global $ref,$val,$tms_link_object_id_field,$resource,$tms_link_resource_types,$lang;

    if(!tms_link_is_rs_uid_field($fieldsitem['ref']))
        {
        return false;
        }

    foreach(tms_link_get_modules_mappings() as $module_uid => $module)
        {
        if(!in_array($resource['resource_type'], $module['applicable_resource_types']))
            {
            continue;
            }

        $input_identifier = ($ref < 0) ? "field_{$module_uid}_{$module['rs_uid_field']}" : "field_{$module['rs_uid_field']}";
        $tms_form_post_id = getval($input_identifier, 0, true);
		if($tms_form_post_id == 0)
			{
			continue;
			}

		$tms_object_id = intval($tms_form_post_id);
        
        global $tmsdata;
		$tmsdata = tms_link_get_tms_data('', $tms_object_id);

		// Make sure we actually do save this data, even if we return an error
		update_field($ref, $module['rs_uid_field'], $tms_object_id);
        
        if(!is_array($tmsdata) && $ref < 0)
			{
			// We can't get any data from TMS for this new resource. Need to show warning if user has not already accepted this
			if(getval("tms_confirm_upload","")=="")
				{
				global $tms_confirm_upload, $lang;
				$tms_confirm_upload=true;
				$error=$lang["tms_link_upload_nodata"] . $tms_form_post_id . " " . $lang["tms_link_confirm_upload_nodata"];

				return $error;						
				}
			}
		else
			{
			global $tms_link_import;

			$tms_link_import=true;
			}
        }

    return false;
	}
	
function HookTms_linkEditSaveextraresourcedata($list)
	{
	// Multi edit - set flag to update TMS data if necessary
    foreach(tms_link_get_modules_mappings() as $module)
        {
        $tms_object_id = getval("field_{$module['rs_uid_field']}", 0, true);

        if($tms_object_id == 0)
            {
            continue;
            }

        global $tmsdata;
        $tmsdata = tms_link_get_tms_data('', $tms_object_id);
        
        if(!is_array($tmsdata))
            {
            continue;
            }

        global $tms_link_import, $tmsupdatelist;
        $tms_link_import = true;
        $tmsupdatelist = $list;
        }

	return;		
	}	
	
function HookTms_linkEditAftersaveresourcedata()
	{
    global $tms_link_import;

    if(isset($tms_link_import) && !$tms_link_import)
        {
        return;
        }

    global $ref, $tmsdata, $tmsupdatelist;

    if(is_null($tmsdata) || !is_array($tmsdata))
        {
        return;
        }
    
    if(!is_array($tmsupdatelist))
        {
        $tmsupdatelist = array();
        $tmsupdatelist[] = $ref;
        }

    foreach($tmsupdatelist as $resourceref)
        {
        debug("tms_link: updating resource id #{$resourceref}");

        foreach(tms_link_get_modules_mappings() as $module)
            {
            if(!array_key_exists($module['module_name'], $tmsdata))
                {
                continue;
                }

            foreach($module['tms_rs_mappings'] as $tms_rs_mapping)
                {
                if($tms_rs_mapping['rs_field'] > 0 && $module['rs_uid_field'] != $tms_rs_mapping['rs_field'] && isset($tmsdata[$module['module_name']][$tms_rs_mapping['tms_column']]))
                    {
                    update_field($resourceref, $tms_rs_mapping['rs_field'], $tmsdata[$module['module_name']][$tms_rs_mapping['tms_column']]);
                    }
                else if($resourceref > 0 && getval("field_{$module['rs_uid_field']}", '') == '')
                    {
                    update_field($resourceref, $tms_rs_mapping['rs_field'], '');
                    }
                }
            }
        }

    return;
	}