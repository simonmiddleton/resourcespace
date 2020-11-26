<?php
/**
* Execute a cURL request
* 
* @param string $url            URL
* @param string $basic_auth     Username and password used for basic authentication.
*                               MUST follow the "username:password" syntax to work!
* @param string $content_type   Content type header value (e.g application/xml)
* @param string $request_method HTTP request methods (e.g GET, POST, PUT, DELETE) 
* @param string $data           Posted data (e.g XML)
* 
* @return array Response information such status code (e.g 200), headers and actual body
*/
function do_http_request($url, $basic_auth, $content_type, $request_method, $data)
    {
    $curl_handle = curl_init();

    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_HEADER, false);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);

    if(strpos($basic_auth, ':') !== false)
        {
        list($username, $password) = explode(':', $basic_auth);

        curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl_handle, CURLOPT_USERPWD, "{$username}:{$password}");
        }

    // Set HTTP headers
    $request_http_headers = array();

    if(trim($content_type) != '')
        {
        $request_http_headers[] = "Content-Type: {$content_type}";
        }

    if(!empty($request_http_headers))
        {
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $request_http_headers);
        }
    // End of setting HTTP headers

    // Set request method and posted data
    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, $request_method);
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);

    // Get a list of response headers in order to have the ability to react to them (e.g can be useful to avoid reaching 
    // rate limits or knowing content type)
    $curl_response_headers = array();
    curl_setopt(
        $curl_handle,
        CURLOPT_HEADERFUNCTION,
        function($curl, $header) use (&$curl_response_headers)
            {
            $length = strlen($header);
            $header = explode(':', $header, 2);

            // Invalid header
            if(count($header) < 2)
                {
                return $length;
                }

            $name = strtolower(trim($header[0]));

            if(!array_key_exists($name, $curl_response_headers))
                {
                $curl_response_headers[$name] = array(trim($header[1]));
                }
            else
                {
                $curl_response_headers[$name][] = trim($header[1]);
                }

            return $length;
            }
    );

    $result = curl_exec($curl_handle);
    $response_status_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    curl_close($curl_handle);

    $response = array(
        'status_code' => $response_status_code,
        'headers'     => $curl_response_headers,
        'result'      => $result,
    );

    return $response;
    }


/**
* Helper function to ensure required connection data was provided
* 
* @see http://docs.zetcom.com/ws/
* 
* @param string $host
* @param string $application
* @param string $user
* @param string $pass
* 
* @return array
*/
function mplus_generate_connection_data($host, $application, $user, $pass)
    {
    if(trim($host) == '' || trim($application) == '' || trim($user) == '' || trim($pass) == '')
        {
        return array();
        }

    $result = array(
        'host' => $host,
        'application' => $application,
        'username' => $user,
        'password' => $pass,
    );

    return $result;
    }


/**
* Run a module search in the search service using an expert search expression
* 
* @uses do_http_request()
* 
* @param array  $conn_data        Connection data. @see mplus_generate_connection_data()
* @param array  $mappings         MuseumPlus - ResourceSpace mappings
* @param string $module_name      Module name
* @param string $mpid             MuseumPlus ID
* @param string $mplus_mpid_field MuseumPlus field name that stores the MpID in the searched module
* 
* @return array
*/
function mplus_search(array $conn_data, array $mappings, $module_name, $mpid, $mplus_mpid_field)
    {
    global $lang;
    if(
        empty($conn_data)
        || empty($mappings)
        || trim($module_name) === ''
        || trim($mpid) === ''
        || trim($mplus_mpid_field) === '')
        {
        return array();
        }

    $basic_auth = "{$conn_data['username']}:{$conn_data['password']}";
    $url = "{$conn_data['host']}/{$conn_data['application']}/ria-ws/application/module/{$module_name}/search/";

    $xml = new DOMDocument('1.0', 'UTF-8');
    $application = $xml->createElement('application');
    $application->setAttribute('xmlns', 'http://www.zetcom.com/ria/ws/module/search');
    $application->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $application->setAttribute('xsi:schemaLocation', 'http://www.zetcom.com/ria/ws/module/search http://www.zetcom.com/ria/ws/module/search/search_1_1.xsd');
    $application = $xml->appendChild($application);

    $modules = $xml->createElement('modules');
    $modules = $application->appendChild($modules);

    $module = $xml->createElement('module');
    $module->setAttribute('name', $module_name);
    $module = $modules->appendChild($module);

    $search = $xml->createElement('search');
    $search->setAttribute('limit', 1);
    $search->setAttribute('offset', 0);
    $search = $module->appendChild($search);

    $select = $xml->createElement('select');
    $select = $search->appendChild($select);

    // Always select the systems' field "__lastModified"
    $field = $xml->createElement('field');
    $field->setAttribute('fieldPath', '__lastModified');
    $field = $select->appendChild($field);

    // Fields to select
    foreach($mappings as $mplus_field => $rs_field)
        {
        $field = $xml->createElement('field');
        $field->setAttribute('fieldPath', $mplus_field);
        $field = $select->appendChild($field);
        }

    // Search criteria
    $expert = $xml->createElement('expert');
    $expert = $search->appendChild($expert);
    $equalsField = $xml->createElement('equalsField');
    $equalsField->setAttribute('fieldPath', $mplus_mpid_field);
    $equalsField->setAttribute('operand', $mpid);
    $equalsField = $expert->appendChild($equalsField);

    $request_xml = $xml->saveXML();

    $result = do_http_request($url, $basic_auth, "application/xml", "POST", $request_xml);

    if($result['status_code'] != 200)
        {
        return array();
        }

    if($result['headers']['content-type'][0] == 'application/xml')
        {
        $xml = new DOMDocument();
        $xml->loadXML($result['result']);
        }

    $result = array();
    foreach($xml->getElementsByTagName('systemField') as $system_field)
        {
        foreach($system_field->attributes as $attr)
            {
            if($attr->nodeName != 'name' || $attr->nodeValue != '__lastModified')
                {
                continue;
                }

            $value = $system_field->getElementsByTagName('value');
            $result[$attr->nodeValue] = $value[0]->nodeValue;
            }
        }
    foreach($xml->getElementsByTagName('virtualField') as $virtual_field)
        {
        foreach($virtual_field->attributes as $attr)
            {
            if($attr->nodeName != 'name')
                {
                continue;
                }

            $result[$attr->nodeValue] = $virtual_field->nodeValue;
            }
        }

    return $result;
    }


/**
* Send notifications to users regarding MuseumPlus events (e.g script failed)
* 
* @uses message_add()
* 
* @param array   $users    List of users' IDs
* @param string  $message  Notification message
* 
* @return boolean TRUE on success FALSE on failure
*/
function mplus_notify(array $users, $message)
    {
    if(count($users) < 0 || trim($message) === '')
        {
        return false;
        }

    message_add($users, $message);

    return true;
    }


/**
* Function to retrieve all resources that have their MpID field set to a value
* and that are within the allowed resource types for an update
* 
* @return array
*/
function get_museumplus_resources()
    {
    global $museumplus_mpid_field, $museumplus_resource_types, $museumplus_integrity_check_field;

    $resource_types_list = implode(
        ', ',
        array_map(
            function($resource_type)
                {
                return "'" . escape_check($resource_type) . "'";
                },
                $museumplus_resource_types));

    $museumplus_mpid_field_escaped = escape_check($museumplus_mpid_field);
    $museumplus_integrity_check_field_escaped = escape_check($museumplus_integrity_check_field);

    $found_resources = sql_query("
            SELECT r.ref AS resource,
                   rd.value AS mpid,
                   ic.value AS 'integrity_check'
              FROM resource_data AS rd
        RIGHT JOIN resource AS r ON rd.resource = r.ref AND r.resource_type IN ({$resource_types_list})
         LEFT JOIN resource_data AS ic ON ic.resource = r.ref AND ic.resource_type_field = '{$museumplus_integrity_check_field_escaped}'
             WHERE rd.resource > 0
               AND rd.resource_type_field = '{$museumplus_mpid_field_escaped}'
               AND rd.value <> ''
          ORDER BY r.ref;
    ");

    return $found_resources;
    }

/**
* Generate a MuseumPlus URL for any module records without having to know the form name
* 
* @param string  $module Module name
* @param integer $id     Module record technical (internal) ID
* 
* @return string Returns the generated URL or empty string on failure
*/
function mplus_generate_module_record_url(string $module, int $id)
    {
    global $museumplus_host, $museumplus_application;

    $host = trim($museumplus_host);
    $application = trim($museumplus_application);
    $module = trim($module);
    $id = ($id > 0 ? $id : '');

    if($host == '' || $application == '' || $module == '' || $id == '')
        {
        return '';
        }

    return sprintf('%s/%s/v#!m/%s/%s',
        $host,
        $application,
        htmlspecialchars($module),
        htmlspecialchars($id));
    }


/**
* Save MuseumPlus modules configuration
* 
* @param array $cf Modules configuration to save
* 
* @return void
*/
function mplus_save_module_config(array $cf)
    {
    $mplus_config = get_plugin_config('museumplus');
    if(is_null($mplus_config))
        {
        $mplus_config = array();
        }

    $mplus_config['museumplus_modules_saved_config'] = plugin_encode_complex_configs($cf);

    if(is_plugin_activated('museumplus'))
        {
        set_plugin_config('museumplus', $mplus_config);
        }

    return;
    }

/**
* For a resource, obtain the associated modules' configuration
* 
* @param integer $resource_ref
* 
* @return array The associated modules' configuration
*/
function mplus_get_associated_module_conf(int $resource_ref)
    {
    global $museumplus_modules_saved_config, $museumplus_module_name_field;

    if($resource_ref <= 0 || is_null($museumplus_modules_saved_config) || $museumplus_modules_saved_config === '')
        {
        return array();
        }

    $museumplus_modules_config = plugin_decode_complex_configs($museumplus_modules_saved_config);

    // A resource can only be linked to one module (for syncing purposes). If module name field is not defined, fallback to 'Object'
    // Note: museumplus_module_name_field should already be constrained to fixed list fields that support only one value (ie. dropdown and radio)
    $resource_module_name = get_resource_nodes($resource_ref, $museumplus_module_name_field, true);
    $resource_module_name = (!empty($resource_module_name) ? $resource_module_name[0]['name'] : 'Object');

    $found_index = array_search($resource_module_name, array_column($museumplus_modules_config, 'module_name'));
    if($found_index === false)
        {
        return array();
        }
    // museumplus_modules_config index starts from one as these are used as end user records' IDs. See the setup_module.php
    // array_search() doesn't honour the multidimensional array index and returns the found index counting from zero.
    $found_index = ++$found_index;

    return $museumplus_modules_config[$found_index];
    }


/**
* For a resource associated modules' configuration, obtain all the relevant values from ResourceSpace metadata fields
* (e.g the actual module record ID based on the 'rs_uid_field' module configuration).
* 
* IMPORTANT: this is a transformation from the RS field IDs to their values. Structure of the return array will be similar 
* (e.g. for 'rs_uid_field' instead of the ID of the field it will be the value for that field)
* 
* @param integer $ref         Resource ref
* @param array   $module_conf The resource associated modules' configuration structure. {@see mplus_get_associated_module_conf()}
* 
* @return array The associated modules' configuration
*/
function mplus_get_resource_module_conf_values(int $ref, array $module_conf)
    {
    // Get the module record ID associated with the resource
    if(isset($module_conf['rs_uid_field']) && $module_conf['rs_uid_field'] > 0)
        {
        $module_conf['rs_uid_field'] = get_data_by_field($ref, $module_conf['rs_uid_field']);
        }

    // Get the decision factor value - if multimedia (ie selected preview images) should be pushed to MuseumPlus system
    // Note: for our purpose, all we care is if that field has one node (from media_sync_df_field) associated with the resource.
    // We do not care what value this is. These fields are meant to be something like "Sync with CMS?" where the only option is "Yes".
    if(isset($module_conf['media_sync_df_field']) && $module_conf['media_sync_df_field'] > 0)
        {
        $media_sync_df_nodes = get_resource_nodes($ref, $module_conf['media_sync_df_field'], false);
        $module_conf['media_sync_df_field'] = (count($media_sync_df_nodes) === 1);
        }

    if(isset($module_conf['field_mappings']) && count($module_conf['field_mappings']) > 0)
        {
        $use_permissions = false;
        $resource_fields_data = get_resource_field_data($ref, false, $use_permissions);

        foreach($module_conf['field_mappings'] as $i => $mapped_field)
            {
            $found_index = array_search($mapped_field['rs_field'], array_column($resource_fields_data, 'ref'));
            if($found_index === false)
                {
                continue;
                }

            $module_conf['field_mappings'][$i]['rs_field'] = $resource_fields_data[$found_index]['value'];
            }
        }

    return $module_conf;
    }


/**
* Validate a modules' record ID (technical or virtual)
* 
* @param string     $module The module name (e.g Object)
* @param string|int $id     The modules' record ID. IMPORTANT: technical IDs are integers, virtual IDs are strings.
* 
* @return integer|boolean Returns the valid MuseumPlus module record technical ID, FALSE otherwise
*/
function mplus_validate_id($module, $id)
    {
    if($id === '')
        {
        return false;
        }

    // - for validation, always try the virtual ID (if one was configured) first, then check the technical ID.
    // - for validation, always error if a virtual ID finds more than a record
    return false;
    }





/*
#####
SYNCING data from M+ ---- old code that was in HookMuseumplusAllAdditionalvalcheck()
USE AS REFERENCE ONLY! (if even needed since the structure has changed)
#####


// Other plugins can modify the field (e.g when MpID field is the original filename without the extension) in which case,
// code needs to be able to handle this so it will attempt to retrieve it from the database instead.
$mpid = getvalescaped("field_{$museumplus_mpid_field}", get_data_by_field($ref, $museumplus_mpid_field)); # CAN BE ALPHANUMERIC
if(trim($mpid) === '')
    {
    return false;
    }

$museumplus_rs_mappings = plugin_decode_complex_configs($museumplus_rs_saved_mappings);

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
*/