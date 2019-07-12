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
        trigger_error(str_replace('$code', $result['status_code'], $lang['museumplus_error_unexpected_response']));
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