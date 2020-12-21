<?php
/**
* Build the search XML for MuseumPlus API request body
* {@see http://docs.zetcom.com/ws/module/search/search_1_4.xsd}
* 
* @param string $fp   Field path used to identify the "linked" modules. This is essentially either the technical ID field (ie. __id)
*                     or another virtual field (e.g ObjObjectNumberVrt)
* @param array  $vals Module record IDs to search for in MuseumPlus
* @param array  $sfs  MuseumPlus modules' fields to return back
* 
* @return DOMDocument Returns XML document with the search criteria
*/
function mplus_xml_search_by_fieldpath(string $fp, array $vals, array $sfs)
    {
    mplus_log_event('Called mplus_xml_search_by_fieldpath()',
        array(
            'expert_search_fieldPath' => $fp,
            'resource_mpid' => $vals,
        ),
        'debug');

    $xml = new DOMDocument('1.0', 'UTF-8');
    $search = $xml->createElement('search');
    $search->setAttribute('limit', count($vals));
    $search->setAttribute('offset', 0);
    $search = $xml->appendChild($search);

    // Select specific fields from the module
    $select = $xml->createElement('select');
    $select = $search->appendChild($select);
    $select_fields = array_merge($sfs, array('__lastModified')); # always select the systems' field "__lastModified"
    foreach($select_fields as $field_name)
        {
        $field = $xml->createElement('field');
        $field->setAttribute('fieldPath', $field_name);
        $field = $select->appendChild($field);
        }

    // Search criteria
    $expert = $xml->createElement('expert');
    $expert = $search->appendChild($expert);
    if(count($vals) > 1)
        {
        $expert = $expert->appendChild($xml->createElement('or'));
        }
    foreach($vals as $value)
        {
        $equalsField = $xml->createElement('equalsField');
        $equalsField->setAttribute('fieldPath', $fp);
        $equalsField->setAttribute('operand', $value);
        $equalsField = $expert->appendChild($equalsField);
        }

    return $xml;
    }


/**
* Run an ad-hoc module search using the provided expert search expression
* {@see http://docs.zetcom.com/ws/#Perform_an_ad-hoc_search_for_modules_items}
* 
* @param string      $module_name Module name
* @param DOMDocument $search      Expert search criteria. {@see mplus_xml_search_by_fieldpath()}
* 
* @return array
*/
function mplus_search(string $module_name, DOMDocument $search)
    {
    mplus_log_event('Called mplus_search()',
        array(
            'module_name' => $module_name,
            'search' => $search->saveXML(),
        ),
        'debug');

    $conn_data = mplus_get_connection_data();
    $search_node = $search->getElementsByTagName("search")->item(0);

    if(empty($conn_data) || trim($module_name) === '' || is_null($search_node))
        {
        mplus_log_event('mplus_search(): Bad arguments.', array(), 'warn');
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
    $search = $xml->importNode($search_node, true);
    $search = $module->appendChild($search);

    $request_xml = $xml->saveXML();
    if($request_xml === false)
        {
        mplus_log_event('mplus_search(): Failed to save XML', array(), 'warn');
        return array();
        }

    $result = do_http_request($url, $basic_auth, "application/xml", "POST", $request_xml);

    if($result['status_code'] != 200)
        {
        mplus_log_event('mplus_search(): Request failed! Response status code NOT 200.', array('status_code' => $result['status_code']), 'warn');
        return array();
        }

    return $result;
    }


/**
* Get XML response body received from MuseumPlus search request
* {@see http://docs.zetcom.com/ws/module/module_1_4.xsd}
* 
* @param array $result Search results as returned by {@see mplus_search()}
* 
* @return DOMDocument
*/
function mplus_get_response_xml(array $result)
    {
    // $xml = new DOMDocument();
    $xml = new DOMDocument('1.0', 'UTF-8');

    if($result['headers']['content-type'][0] == 'application/xml')
        {
        $xml->loadXML($result['result']);
        }

    return $xml;
    }

#### legacy code from mplus_search(). Might be useful later
/*
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
*/