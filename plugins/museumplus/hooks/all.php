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