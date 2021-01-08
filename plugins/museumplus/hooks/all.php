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

/* 
IMPORTANT: DO NOT USE the "update_field" hook! You can potentially end up in a processing loop.
The function is used in multiple places and won't be able to let the user know there were errors caused by validation/sync.
In addition, the "museumplus_script.php" can run every minute and pick up any remaining resources left unprocessed.
function HookMuseumplusAllUpdate_field($resource, $field, $value, $existing) {}
*/

/**
* MuseumPlus plugin attaching to the 'aftersaveresourcedata' hook
* IMPORTANT: 'aftersaveresourcedata' hook is called from both save_resource_data() and save_resource_data_multi()!
* 
* @param int|array $R Generic type for resource ID(s). It will be a resource ref when hook is called from 
*                     save_resource_data() -OR- a list of resource IDs when called from save_resource_data_multi().
* 
* @return boolean|array Returns FALSE to show hook didn't run or list of errors. See hook 'aftersaveresourcedata' in resource_functions.php for more info
*/
function HookMuseumplusAllAftersaveresourcedata($R)
    {
    mplus_log_event('Called HookMuseumplusAllAftersaveresourcedata()', ['resources' => $R], 'debug');

    if(!(is_numeric($R) || is_array($R)))
        {
        return false;
        }
    $refs = (is_array($R) ? $R : array($R));


    // STEP 1: Determine which resources should be processed
    $batch_resource_data = get_resource_data_batch($refs);
    // If resources are not in the "Active" state, then no further processing is required
    $active_resources = array_keys(array_filter($batch_resource_data, function($r) { return $r['archive'] == 0; }));
    // Note: resources for which a module config wasn't found have been dropped from the list as no further processing is needed.
    $resources = mplus_get_associated_module_conf($active_resources, true);
    // Filter resources - discard of the ones where the "module name - MpID" combination hasn't changed since resource association was last validated
    foreach(mplus_flip_struct_by_module($resources) as $module_name => $mdata)
        {
        $computed_md5s = mplus_compute_data_md5($mdata['resources'], $module_name);
        $resources_md5s = array_column(mplus_resource_get_data(array_keys($mdata['resources'])), 'museumplus_data_md5', 'ref');
        foreach(array_keys($mdata['resources']) as $r_ref)
            {
            if(isset($computed_md5s[$r_ref], $resources_md5s[$r_ref]) && $computed_md5s[$r_ref] === $resources_md5s[$r_ref])
                {
                unset($resources[$r_ref]);
                continue;
                }
            }
        }
    // If resources have a type not valid for the associated module configuration then no further processing is required
    $resources = array_filter(
        $resources,
        function($cfg, $r) use ($batch_resource_data)
            {
            return (isset($batch_resource_data[$r]['resource_type']) && in_array($batch_resource_data[$r]['resource_type'], $cfg['applicable_resource_types']));
            },
        ARRAY_FILTER_USE_BOTH);


/*
TODO; use new function - mplus_resource_get_association_data(array $filter) - and pass extra filters requied for this use case:
- search only for specific resource IDs
- get only resources that had their "module name - MpID" combination changed with this save:
    AND r.museumplus_data_md5 <> MD5(CONCAT(r.ref, \'_comb(\', n.`name`, \'-\', rd.`value`, \')\'))

then call 
$resources = mplus_get_associated_module_conf($active_resources, true);
*/ 

    if(empty($resources))
        {
        return false;
        }


    // STEP 2: Process resources with an associated module configuration
    mplus_log_event('Running MuseumPlus process (i.e. validating "module name - MpID" combination and syncing data...', ['resources' => array_keys($resources)]);
    mplus_resource_clear_metadata(array_keys($resources));
    $errors = mplus_sync(mplus_validate_association($resources, false));

    if(is_array($errors) && !empty($errors))
        {
        return $errors;
        }

    return false;
    }
