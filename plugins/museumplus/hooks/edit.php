<?php
function HookMuseumplusAllAdditionalvalcheck($fields, $fields_item)
    {
    global $lang, $ref, $resource, $museumplus_host, $museumplus_application, $museumplus_api_user, $museumplus_api_pass,
           $museumplus_mpid_field, $museumplus_resource_types, $museumplus_rs_saved_mappings, $museumplus_search_mpid_field;

    if(!in_array($resource['resource_type'], $museumplus_resource_types))
        {
        return false;
        }

    if($museumplus_mpid_field != $fields_item['ref'])
        {
        return false;
        }

    // Other plugins can modify the field (e.g when MpID field is the original filename without the extension) in which case,
    // code needs to be able to handle this so it will attempt to retrieve it from the database instead.
    $mpid = getvalescaped("field_{$museumplus_mpid_field}", get_data_by_field($ref, $museumplus_mpid_field)); # CAN BE ALPHANUMERIC
    if(trim($mpid) === '')
        {
        return false;
        }

    $museumplus_rs_mappings = unserialize(base64_decode($museumplus_rs_saved_mappings));

    $conn_data = mplus_generate_connection_data($museumplus_host, $museumplus_application, $museumplus_api_user, $museumplus_api_pass);
    if(empty($conn_data))
        {
        return $lang['museumplus_error_bad_conn_data'];
        }

    $mplus_data = mplus_search($conn_data, $museumplus_rs_mappings, 'Object', $mpid, $museumplus_search_mpid_field);

    update_field($ref, $museumplus_mpid_field, escape_check($mpid));

    if(empty($mplus_data))
        {
        return str_replace('%mpid', $mpid, $lang['museumplus_error_no_data_found']);
        }

    foreach($mplus_data as $mplus_field => $field_value)
        {
        if(!array_key_exists($mplus_field, $museumplus_rs_mappings))
            {
            continue;
            }

        $rs_field = $museumplus_rs_mappings[$mplus_field];

        update_field($ref, $rs_field, escape_check($field_value));
        }

    return false;
    }