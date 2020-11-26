<?php
function HookMuseumplusAllInitialise()
    {
    $mplus_config = get_plugin_config('museumplus');

    if(
        isset($mplus_config['museumplus_search_mpid_field'])
        && isset($mplus_config['museumplus_mpid_field'])
        && isset($mplus_config['museumplus_resource_types'])
        && isset($mplus_config['museumplus_rs_saved_mappings'])
        && isset($mplus_config['museumplus_cms_url_form_part'])
    )
        {
        $field_mappings = array();
        $museumplus_rs_saved_mappings = plugin_decode_complex_configs($mplus_config['museumplus_rs_saved_mappings']);
        foreach($museumplus_rs_saved_mappings as $field_name => $rs_field)
            {
            $field_mappings[] = array(
                'field_name' => $field_name,
                'rs_field' => $rs_field);
            }

        $module_configs = array(
            1 => array(
                'module_name' => 'Object',
                'mplus_id_field' => $mplus_config['museumplus_search_mpid_field'],
                'rs_uid_field' => $mplus_config['museumplus_mpid_field'],
                'applicable_resource_types' => $mplus_config['museumplus_resource_types'],
                'media_sync' => false,
                'media_sync_df_field' => 0,
                'field_mappings' => $field_mappings,
            )
        );

        $mplus_config['museumplus_modules_saved_config'] = plugin_encode_complex_configs($module_configs);
        unset($mplus_config['museumplus_search_mpid_field']);
        unset($mplus_config['museumplus_mpid_field']);
        unset($mplus_config['museumplus_resource_types']);
        unset($mplus_config['museumplus_rs_saved_mappings']);
        unset($mplus_config['museumplus_cms_url_form_part']); # not migrated but no longer needed

        set_plugin_config('museumplus', $mplus_config);
        }

    return;
    }

function HookMuseumplusAllUpdate_field($resource, $field, $value, $existing)
    {
    global $lang, $museumplus_mpid_field, $museumplus_resource_types, $museumplus_host, $museumplus_application,
           $museumplus_api_user, $museumplus_api_pass, $museumplus_rs_saved_mappings, $museumplus_search_mpid_field;

   $resource_data = get_resource_data($resource);

   if(!in_array($resource_data['resource_type'], $museumplus_resource_types))
        {
        return;
        }

    if($museumplus_mpid_field != $field)
        {
        return;
        }

    $mpid = $value; # CAN BE ALPHANUMERIC
    if(trim($mpid) === '')
        {
        return;
        }

    $conn_data = mplus_generate_connection_data($museumplus_host, $museumplus_application, $museumplus_api_user, $museumplus_api_pass);
    if(empty($conn_data))
        {
        debug("MUSEUMPLUS - update_field: {$lang['museumplus_error_bad_conn_data']}");
        return;
        }

    $museumplus_rs_mappings = plugin_decode_complex_configs($museumplus_rs_saved_mappings);
    $mplus_data = mplus_search($conn_data, $museumplus_rs_mappings, 'Object', $mpid, $museumplus_search_mpid_field);

    foreach($mplus_data as $mplus_field => $field_value)
        {
        if(!array_key_exists($mplus_field, $museumplus_rs_mappings))
            {
            continue;
            }

        $rs_field = $museumplus_rs_mappings[$mplus_field];

        update_field($resource, $rs_field, escape_check($field_value));
        }

    return;
    }


function HookMuseumplusAllAdditionalvalcheck($fields, $fields_item)
    {
    global $resource, $museumplus_module_name_field;

    $associated_module_cfg = mplus_get_associated_module_conf($resource['ref']);
    if(
        empty($associated_module_cfg)
        || !in_array($resource['resource_type'], $associated_module_cfg['applicable_resource_types'])
        // Changing any other field other than the module name or the M+ ID for a resource
        || !in_array($fields_item['ref'], array($museumplus_module_name_field, $associated_module_cfg['rs_uid_field']))
    )
        {
        return false;
        }

    $GLOBALS['museumplus_trigger_id_validation'] = true;

    return false;
    }


function HookMuseumplusAllAftersaveresourcedata($ref)
    {
    debug("TEST.f: HookMuseumplusAllAftersaveresourcedata(resource_ref = {$ref});");
    // The global 'museumplus_trigger_id_validation' is set in Additionalvalcheck if either the module name OR the rs_uid_field
    // have changed to trigger this process
    if(!isset($GLOBALS['museumplus_trigger_id_validation']))
        {
        return false;
        }

    $resource = get_resource_data($ref);
    if($resource === false)
        {
        return false;
        }

    $associated_module_cfg = mplus_get_associated_module_conf($resource['ref']);
    if(
        empty($associated_module_cfg)
        || !in_array($resource['resource_type'], $associated_module_cfg['applicable_resource_types'])
    )
        {
        return false;
        }

    $module_name = $associated_module_cfg['module_name'];
    $rs_uid_field = $associated_module_cfg['rs_uid_field'];
    $resource_assoc_module_cfg_values = mplus_get_resource_module_conf_values($resource['ref'], $associated_module_cfg);

    $errors = array();
    if(isset($GLOBALS['hook_return_value']) && is_array($GLOBALS['hook_return_value']) && !empty($GLOBALS['hook_return_value']))
        {
        // @see hook() for an explanation about the hook_return_value global
        $errors = $GLOBALS['hook_return_value'];
        }

    global $lang;

    $mpid = trim($resource_assoc_module_cfg_values['rs_uid_field']); # CAN BE ALPHANUMERIC (technical IDs are integers, virtual IDs are strings)

    // STEP 1: validate the record ID for the linked module
    $valid_id = mplus_validate_id($module_name, $mpid);
    if($mpid != '' && $valid_id === false)
        {
        $errors['museumplus_invalid_id'] = $lang['museumplus_error_invalid_id'];
        }



    if(!empty($errors))
        {
        return $errors;
        }

    return false;
    }