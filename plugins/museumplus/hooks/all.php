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
        $old_config = $mplus_config;
        // Remove sensitive information
        unset($old_config['museumplus_host'], $old_config['museumplus_application'], $old_config['museumplus_api_user'], $old_config['museumplus_api_pass']);
        mplus_log_event('Migrating old MuseumPlus plugin configuration', array('old_mplus_plugin_config' => $old_config));

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


/**
* MuseumPlus plugin attaching to the 'aftersaveresourcedata' hook
* IMPORTANT: aftersaveresourcedata hook is called from both save_resource_data() and save_resource_data_multi()!
* 
* @param int|array $R Generic type for resource ID(s). It will be a resource ref when hook is called from 
*                     save_resource_data() -or- a list of resource IDs when called from save_resource_data_multi().
* @param array $added_nodes   List of nodes added. When called from save_resource_data_multi() it is a list of all added nodes.
* @param array $removed_nodes List of nodes removed. When called from save_resource_data_multi() it is a list of all removed nodes.
* 
* @return boolean|array Returns false to show hook didn't run or list of errors. See hook 'aftersaveresourcedata' in resource_functions.php for more info
*/
function HookMuseumplusAllAftersaveresourcedata($R, $added_nodes, $removed_nodes)
    {
    mplus_log_event(
        'Called HookMuseumplusAllAftersaveresourcedata()',
        array(
            'args' => array(
                'R' => $R,
                'added_nodes' => $added_nodes,
                'removed_nodes' => $removed_nodes,
            ),
        ),
        'debug'
    );

    if(!(is_numeric($R) || is_array($R)))
        {
        return false;
        }
    $refs = (is_array($R) ? $R : array($R));

    $batch_resource_data = get_resource_data_batch($refs);
    // if resources are not in the "Active" state, then no further processing is required
    $resources = array_keys(array_filter($batch_resource_data, function($r) { return $r['archive'] == 0; }));
    // resources will get mutated after this call. From this point, resource ID is in the key and the value is the associated module config.
    // Note: resources for which a module config wasn't found have been dropped from the list as no further processing is needed.
    $resources = mplus_get_associated_module_conf($resources, true);
    // if resources have a type not valid for the associated module configuration then no further processing is required
    $resources = array_filter(
        $resources,
        function($cfg, $r) use ($batch_resource_data)
            {
            return (isset($batch_resource_data[$r]['resource_type']) && in_array($batch_resource_data[$r]['resource_type'], $cfg['applicable_resource_types']));
            },
        ARRAY_FILTER_USE_BOTH);
    if(empty($resources))
        {
        return false;
        }

    mplus_log_event(
        'Running MuseumPlus process (i.e. validating "module name - MpID" combination and syncing data...',
        array('resources' => array_keys($resources))
    );

    $errors = array();
    global $lang;


    // STEP 1: Clear (if configured) metadata fields mapped to MuseumPlus fields
    mplus_resource_clear_metadata(array_keys($resources));

    // STEP 2: validate the MpID for the associated module
    $resources_with_valid_ids = mplus_validate_association($resources, false);
    if(isset($resources_with_valid_ids['errors']))
        {
        // $errors = array_merge($errors, $resources_with_valid_ids['errors']);
        $validation_errors = $resources_with_valid_ids['errors'];
        unset($resources_with_valid_ids['errors']);
        }

    if(empty($resources_with_valid_ids))
        {
        return $errors;
        }

    // STEP 3: Sync data from MuseumPlus (if resource has been re-associated with a different module record item)




    if(!empty($errors))
        {
        return $errors;
        }

    return false;
    }
