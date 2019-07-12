<?php
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

    $museumplus_rs_mappings = unserialize(base64_decode($museumplus_rs_saved_mappings));
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