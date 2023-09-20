<?php
/*
 * API v2 functions
 *
 * Montala Limited, July 2016
 *
 * For documentation please see: http://www.resourcespace.com/knowledge-base/api/
 *
 */


/**
 * Return a private scramble key for this user.
 *
 * @param  integer $user The user ID
 * @return string|false
 */
function get_api_key($user)
    {
    global $api_scramble_key;
    return hash("sha256", $user . $api_scramble_key);
    }    

/**
 * Check a query is signed correctly.
 *
 * @param  string $username The username of the calling user
 * @param  string $querystring The query being passed to the API
 * @param  string $sign The signature to check
 * @param  string $authmode The type of key being provided (user key or session key)
 */
function check_api_key($username,$querystring,$sign,$authmode="userkey"): bool
    {
    // Fetch user ID and API key
    $user=get_user_by_username($username); if ($user===false) {return false;}
    $aj = strpos($querystring,"&ajax=");
    if($aj != false)
        {
        $querystring = substr($querystring,0,$aj);
        }

    if($authmode == "sessionkey")
        {
        $userkey=get_session_api_key($user);
        }
    else
        {        
        $userkey=get_api_key($user);
        }

    # Calculate the expected signature and check it matches
    $expected=hash("sha256",$userkey . $querystring);
    if ($expected === $sign)
	{
	return true;
	}
    # Also try matching against the username - allows remote API use without knowing the user ID, e.g. in the event of managing multiple systems each with a common username but different ID.
    if (hash("sha256",get_api_key($username) . $querystring) === $sign)
	{
	return true;
	} 
    return false;
    }
    
/**
 * Execute the specified API function.
 *
 * @param  string $query The query string passed to the API
 * @param  boolean $pretty Should the JSON encoded result be 'pretty' i.e. formatted for reading?
 * @return bool|string
 */
function execute_api_call($query,$pretty=false)
    {
    $params=array();parse_str($query,$params);
    if (!array_key_exists("function",$params)) {return false;}
    $function=$params["function"];
    if (!function_exists("api_" . $function)) {return false;}

    global $lang;

    // Construct an array of the real params, setting default values as necessary
    $setparams = array();
    $n = 0;    
    $fct = new ReflectionFunction("api_" . $function);
    foreach ($fct->getParameters() as $fparam)
        {
        $paramkey = $n + 1;
        $param_name = $fparam->getName();
        debug("API: Checking for parameter " . $param_name . " (param" . $paramkey . ")");
        if (array_key_exists("param" . $paramkey,$params))
            {
            debug ("API: " . $param_name . " -   value has been passed : '" . $params["param" . $paramkey] . "'");
            $setparams[$n] = $params["param" . $paramkey];
            }
        else if(array_key_exists($param_name, $params))
            {
            debug("API: {$param_name} - value has been passed (by name): '" . json_encode($params[$param_name]) . "'");

            // Check if array;
            $type = $fparam->getType();
            if(gettype($type) == "object")
                {
                // type is an object 
                $type = $type->getName();
                }
            if($fparam->hasType() && gettype($type) == "string" && $type == "array")
                {
                // Decode as must be json encoded if array
                $GLOBALS["use_error_exception"] = true;
                try
                    {
                    $decoded = json_decode($params[$param_name],JSON_OBJECT_AS_ARRAY);
                    }
                catch (Exception $e)
                    {
                    $error = str_replace(
                        array("%arg", "%expected-type", "%type"),
                        array($param_name, "array (json encoded)",$lang['unknown']),
                        $lang["error-type-mismatch"]);
                    return json_encode($error);
                    }
                unset($GLOBALS["use_error_exception"]);
                // Check passed data type after decode
                if(gettype($decoded) != "array")
                    {
                    $error = str_replace(
                        array("%arg", "%expected-type", "%type"),
                        array($param_name, "array (json encoded)", $lang['unknown']),
                        $lang["error-type-mismatch"]);
                    return json_encode($error);
                    }
                $params[$param_name] = $decoded;
                }

            $setparams[$n] = $params[$param_name];
            }
        elseif ($fparam->isOptional())
            {
            // Set default value if nothing passed e.g. from API test tool
            debug ("API: " . $param_name . " -  setting default value = '" . $fparam->getDefaultValue() . "'");
            $setparams[$n] = $fparam->getDefaultValue();
            }
        else
            {
             // Set as empty
            debug ("API: {$param_name} -  setting empty value");
            $setparams[$n] = "";    
            }
        $n++;
        }
    
    debug("API: calling api_" . $function);
    $result = call_user_func_array("api_" . $function, $setparams);

    if($pretty)
        {
            debug("API: json_encode() using JSON_PRETTY_PRINT");
            $json_encoded_result = json_encode($result,(defined('JSON_PRETTY_PRINT')?JSON_PRETTY_PRINT:0));
        }
    else
        {
            debug("API: json_encode()");
            $json_encoded_result = json_encode($result);
        }
    if(json_last_error() !== JSON_ERROR_NONE)
        {
        debug("API: JSON error: " . json_last_error_msg());
        debug("API: JSON error when \$result = " . print_r($result, true));

        $json_encoded_result = json_encode($result,JSON_UNESCAPED_UNICODE);
        }

    return $json_encoded_result;

    }
    
/**
* Get an array of all the canvases for the identifier ready for JSON encoding
* 
* @uses get_data_by_field()
* @uses get_original_imagesize()
* @uses get_resource_type_field()
* @uses get_resource_path()
* @uses iiif_get_thumbnail()
* @uses iiif_get_image()
* 
* @param object $iiif		IIIF object
* @param boolean $sequencekeys		Get the array with each key matching the value set in the metadata field $iiif_sequence_field. By default the array will be sorted but have a 0 based index
* 
* @return array
*/
function iiif_get_canvases($iiif, $sequencekeys=false)
    {			
    $canvases = array();
    foreach ($iiif->searchresults as $iiif_result)
        {
		$size = (strtolower($iiif_result["file_extension"]) != "jpg") ? "hpr" : "";
        $img_path = get_resource_path($iiif_result["ref"],true,$size,false);
        $position_prefix="";
        
        if(!file_exists($img_path))
            {
            continue;
            }
			
		$position = $iiif_result["iiif_position"];
        $canvases[$position]["@id"] = $iiif->rooturl . $iiif->request["id"] . "/canvas/" . $position;
        $canvases[$position]["@type"] = "sc:Canvas";
        $canvases[$position]["label"] = (isset($position_prefix)?$position_prefix:'') . $position;
        
        // Get the size of the images
        $image_size = get_original_imagesize($iiif_result["ref"],$img_path);
        $canvases[$position]["height"] = intval($image_size[2]);
        $canvases[$position]["width"] = intval($image_size[1]);
				
		// "If the largest image�s dimensions are less than 1200 pixels on either edge, then the canvas�s dimensions should be double those of the image." - From http://iiif.io/api/presentation/2.1/#canvas
		if($image_size[1] < 1200 || $image_size[2] < 1200)
			{
			$image_size[1] = $image_size[1] * 2;
			$image_size[2] = $image_size[2] * 2;
			}
        
        $canvases[$position]["thumbnail"] = iiif_get_thumbnail($iiif, $iiif_result["ref"]);
        
        // Add image (only 1 per canvas currently supported)
		$canvases[$position]["images"] = array();
        $size_info = array(
            'identifier' => $size,
            'return_height_width' => false,
        );
        $canvases[$position]["images"][] = iiif_get_image($iiif, $iiif_result["ref"], $position, $size_info);
        }
    
	if($sequencekeys)
		{
		// keep the sequence identifiers as keys so a required canvas can be accessed by sequence id
		return $canvases;
		}
	
    ksort($canvases);	
    $return=array();
    foreach($canvases as $canvas)
        {
        $return[] = $canvas;
        }
    return $return;
    }

/**
* Get  thumbnail information for the specified resource id ready for IIIF JSON encoding
* 
* @uses get_resource_path()
* @uses getimagesize()
* 
* @param object $iiif		IIIF object

* @return array
*/
function iiif_get_thumbnail($iiif, $resourceid)
    {	
	$img_path = get_resource_path($resourceid,true,'thm',false);
	if(!file_exists($img_path))
            {
		    return false;
            }
			
	$thumbnail = array();
	$thumbnail["@id"] = $iiif->rootimageurl . $resourceid . "/full/thm/0/default.jpg";
	$thumbnail["@type"] = "dctypes:Image";
	
	 // Get the size of the images
    if ((list($tw,$th) = @getimagesize($img_path))!==false)
        {
        $thumbnail["height"] = (int) $th;
        $thumbnail["width"] = (int) $tw;   
        }
    else
        {
        // Use defaults
        $thumbnail["height"] = 150;
        $thumbnail["width"] = 150;    
        }
            
	$thumbnail["format"] = "image/jpeg";
	
	$thumbnail["service"] =array();
	$thumbnail["service"]["@context"] = "http://iiif.io/api/image/2/context.json";
	$thumbnail["service"]["@id"] = $iiif->rootimageurl . $resourceid;
	$thumbnail["service"]["profile"] = "http://iiif.io/api/image/2/level1.json";
	return $thumbnail;
	}
	
/**
* Get the image for the specified identifier canvas and resource id
* 
* @uses get_original_imagesize()
* @uses get_resource_path()
* 
* @param object $iiif		  IIIF request object
* @param integer $resourceid  Resource ID
* @param string $position     The canvas identifier, i.e position in the sequence. If $iiif_sequence_field is defined
* @param array $size          ResourceSpace size information. Required information: identifier and whether it 
*                             requires to return height & width back (e.g annotations don't require it). 
*                             Please note for the identifier - we use 'hpr' if the original file is not a JPG file it 
*                             will be the value of this metadata field for the given resource
*                             Example:
*                             $size_info = array(
*                               'identifier'          => 'hpr',
*                               'return_height_width' => true
*                             );
* 
* @return array
*/	
function iiif_get_image($iiif,$resourceid,$position, array $size_info)
    {
    // Quick validation of the size_info param
    if(empty($size_info) || (!isset($size_info['identifier']) && !isset($size_info['return_height_width'])))
        {
        return false;
        }

    $size = $size_info['identifier'];
    $return_height_width = $size_info['return_height_width'];

	$img_path = get_resource_path($resourceid,true,$size,false);
	if(!file_exists($img_path))
            {
		    return false;
            }

    $image_size = get_original_imagesize($resourceid, $img_path);
			
	$images = array();
	$images["@context"] = "http://iiif.io/api/presentation/2/context.json";
	$images["@id"] = $iiif->rooturl . $iiif->request["id"] . "/annotation/" . $position;
	$images["@type"] = "oa:Annotation";
	$images["motivation"] = "sc:painting";
	
	$images["resource"] = array();
	$images["resource"]["@id"] = $iiif->rootimageurl . $resourceid . "/full/max/0/default.jpg";
	$images["resource"]["@type"] = "dctypes:Image";
	$images["resource"]["format"] = "image/jpeg";

    $images["resource"]["height"] = intval($image_size[2]);
    $images["resource"]["width"] = intval($image_size[1]);

	$images["resource"]["service"] =array();
	$images["resource"]["service"]["@context"] = "http://iiif.io/api/image/2/context.json";
	$images["resource"]["service"]["@id"] = $iiif->rootimageurl . $resourceid;
	$images["resource"]["service"]["profile"] = "http://iiif.io/api/image/2/level1.json";
	$images["on"] = $iiif->rooturl . $iiif->request["id"] . "/canvas/" . $position;

    if($return_height_width)
        {
        $images["height"] = intval($image_size[2]);
        $images["width"] = intval($image_size[1]);
        }

    return $images;  
	}

/**
 * Handle a IIIF error.
 *
 * @param  integer $errorcode The error code
 * @param  array $errors An array of errors
 * @return void
 */
function iiif_error($errorcode = 404, $errors = array())
    {
    if(function_exists("http_response_code"))
        {
        http_response_code($errorcode); # Send error status
        }
    echo json_encode($errors);	 
    exit();
    }

/**
 * Return the session specific key for the given user.
 *
 * @param  integer $user The user ID
 * @return string
 */
function get_session_api_key($user)
    {
    global $scramble_key;
    $private_key = get_api_key($user);
    $usersession = ps_value("SELECT session value FROM user where ref = ?", array("i",$user), "");
    return hash_hmac("sha256", "{$usersession}{$private_key}", $scramble_key);
    }

/**
* API login function

* 
 * @param  string $username         Username
 * @param  string $password         Password to validate
 * @return string|false             FALSE if invalid, session API key if valid
*/
function api_login($username,$password)
    {
    global $session_hash, $scramble_key;
    $user=get_user_by_username($username); if ($user===false) {return false;}
    $result = perform_login($username,$password);
    $private_key = get_api_key($user);
    if ((bool)$result['valid'])
        {
        return hash_hmac("sha256", "{$session_hash}{$private_key}", $scramble_key);
        }

    return false;
    }

/**
 * Validate URL supplied in APIs create resource or upload by URL. Requires the URL hostname to be added in config $api_upload_urls
 *
 * @param   string   $url   The full URL.
 * 
 * @return  bool   Returns true if a valid URL is found.
 */
function api_validate_upload_url($url)
    {
    $url = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
    if ($url === false)
        {
        return false;
        }
    
    $url_parts = parse_url($url);

    if (in_array($url_parts['scheme'], BLOCKED_STREAM_WRAPPERS))
        {
        return false;
        }

    global $api_upload_urls;
    if (!isset($api_upload_urls))
        {
        return true; // For systems prior to this config.
        }

    if (in_array($url_parts['host'], $api_upload_urls))
        {
        return true;
        }

    return false;
    }

/**
 * Assert API request is using POST method.
 *
 * @param bool $force Force the assertion
 *
 * @return array Returns JSend data back {@see ajax_functions.php} if not POST method
 */
function assert_post_request(bool $force): array
    {
    // Legacy use cases we don't want to break backwards compatibility for (e.g JS api() makes only POST requests but
    // other clients might only use GET because it was allowed if not authenticating with native mode)
    if (!$force)
        {
        return [];
        }
    else if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
        return [];
        }
    else
        {
        http_response_code(405);
        return ajax_response_fail(ajax_build_message($GLOBALS['lang']['error-method-not_allowed']));
        }
    }

/**
 * Assert API sent the expected content type.
 *
 * @param string $expected MIME type
 * @param string $received_raw MIME type
 *
 * @return array Returns JSend data back {@see ajax_functions.php} if received Content-Type is unexpected
 */
function assert_content_type(string $expected, string $received_raw): array
    {
    $expected = trim($expected);
    if ($expected === '')
        {
        trigger_error('Expected MIME type MUST not be a blank string', E_USER_ERROR);
        }

    $encoding = 'UTF-8';
    $received = mb_strcut($received_raw, 0, mb_strlen($expected, $encoding), $encoding);
    if ($expected === $received)
        {
        return [];
        }

    http_response_code(415);
    header("Accept: {$expected}");
    return ajax_response_fail([]);
    }


function iiif_parse_url(&$iiif)
    {
    // Extract IIIF request details from the URL path
    // Root level request 
    // type - root manifest , image or presentation API
   
    $iiif->request = [];

    $request_url=strtok($_SERVER["REQUEST_URI"],'?');
    $path=substr($request_url,strpos($request_url,$iiif->rootlevel) + strlen($iiif->rootlevel));
    $xpath = explode("/",$path);

    // Set API type
    if(strtolower($xpath[0]) == "image")
        {
        $iiif->request["api"] = "image";
        }
    elseif(count($xpath) > 1 ||  $xpath[0] != "")
        {
        $iiif->request["api"] = "presentation";
        }
    else
        {
        $iiif->request["api"]  = "root";
        return;
        }

    if($iiif->request["api"] == "image")
        {
        // For image need to extract: -
        // - Resource ID
        // - type (manifest)
        // - region
        // - size
        // - rotation
        // - quality
        // - format
        $iiif->request["id"] = trim($xpath[1] ?? '');
        $iiif->request["region"] = trim($xpath[2] ?? '');
        $iiif->request["size"] = trim($xpath[3] ?? '');
        $iiif->request["rotation"] = trim($xpath[4] ?? '');
        $iiif->request["filename"] = trim($xpath[5] ?? '');
        
        if($iiif->request["id"]  === '')
            {
            iiif_error(400, ['Missing identifier']);
            }

        if($iiif->request["region"] == "")
            {
            // Redirect to image information document
            $redirurl = $iiif->rootimageurl . $iiif->request["id"] . '/info.json';
            if(function_exists("http_response_code"))
                {
                http_response_code(303);
                }
            header ("Location: " . $redirurl);
            exit();
            }
        // Check the request parameters
        elseif($iiif->request["region"] != "info.json")
            {
            if(($iiif->request["size"] == "" 
                    || 
                    !is_int_loose($iiif->request["rotation"])
                    ||
                    $iiif->request["filename"] != "default.jpg"
                    )
                )
                {
                // Not request for image information document and no sizes specified
                $errors = ["Invalid image request format."];
                iiif_error(400,$errors);
                }
            
            $formatparts = explode(".",$iiif->request["filename"]);
            if(count($formatparts) != 2)
                {
                // Format. As we only support IIIF Image level 0 a value of 'jpg' is required 
                $errors = ["Invalid quality or format requested. Try using 'default.jpg'"];
                iiif_error(400,$errors);
                }
            else
                {
                $iiif->request["quality"] = $formatparts[0];
                $iiif->request["format"] = $formatparts[1];
                }
            }     
        }
    elseif($iiif->request["api"] == "presentation")
        {
        // Presentation -  need
        // - identifier
        // - type (manifest/canvas/sequence/annotation

        $iiif->request["id"] = trim($xpath[0] ?? '');
        $iiif->request["type"] = trim($xpath[1] ?? '');
        $iiif->request["typeid"] = trim($xpath[2] ?? '');
        }
    return;
    }

function iiif_generate_manifest(&$iiif)
    {
    global $lang, $baseurl;
    if($iiif->request["id"] != "" && $iiif->request["type"] == "")
        {
        // Redirect to image information document
        $redirurl = $_SERVER["REQUEST_URI"] . ($iiif->request["id"] != "" ? "/" : "") . "manifest";
        if(function_exists("http_response_code"))
            {
            http_response_code(303); # Send error status
            }
        header ("Location: " . $redirurl);
        exit();
        }

    $iiif_field = get_resource_type_field($iiif->identifier_field);
    $iiif_search = $iiif_field["name"] . ":" . $iiif->request["id"];
    $iiif->searchresults = do_search($iiif_search);
    
    if(is_array($iiif->searchresults) && count($iiif->searchresults)>0)
        {
        if($iiif->request["type"] == "")
            {
            $iiif->errorcode=404;
            $iiif->errors[] = "Bad request. Valid options are 'manifest', 'sequence' or 'canvas' e.g. ";
            $iiif->errors[] = "For the manifest: " . $iiif->rooturl . $iiif->request["id"] . "/manifest";
            $iiif->errors[] = "For a sequence : " . $iiif->rooturl . $iiif->request["id"] . "/sequence";
            $iiif->errors[] = "For a canvas : " . $iiif->rooturl . $iiif->request["id"] . "/canvas/<identifier>";
            }
        else
            {
            // Add sequence position information
            $resultcount = count($iiif->searchresults);
            $iiif_results_with_position = array();
            $iiif_results_without_position = array();
            for ($n=0;$n<$resultcount;$n++)
                {
                if($iiif->sequence_field != 0)
                    {
                    if(isset($iiif->searchresults[$n]["field" . $iiif->sequence_field]))
                        {
                        $position = $iiif->searchresults[$n]["field" . $iiif->sequence_field];
                        }
                    else
                        {
                        $position = get_data_by_field($iiif->searchresults[$n]["ref"],$iiif->sequence_field);
                        }
                    $position_field=get_resource_type_field($iiif->sequence_field);
                    // $position_prefix = $position_field["name"] . " ";

                    if(!isset($position) || trim($position) == "")
                        {
                        // Processing resources without a sequence position separately
                        debug("iiif position empty for resource ref " . $iiif->searchresults[$n]["ref"]);
                        $iiif_results_without_position[] = $iiif->searchresults[$n];
                        continue;
                        }

                    debug("iiif position $position found in resource ref " . $iiif->searchresults[$n]["ref"]);
                    $iiif->searchresults[$n]["iiif_position"] = $position;
                    $iiif_results_with_position[] = $iiif->searchresults[$n];
                    }
                else
                    {
                    $position = $n;
                    debug("iiif position $position assigned to resource ref " . $iiif->searchresults[$n]["ref"]);
                    $iiif->searchresults[$n]["iiif_position"] = $position;
                    }
                }

            // Sort by user supplied position (handle blanks and duplicates)
            if ($iiif->sequence_field != 0)
                {
                # First sort by ref. Any duplicate positions will then be sorted oldest resource first.
                usort($iiif_results_with_position, function($a, $b) { return $a['ref'] - $b['ref']; });
                # Sort resources with user supplied position.
                usort($iiif_results_with_position, function($a, $b)
                    {
                    if(is_int_loose($a['iiif_position']) && is_int_loose($b['iiif_position']))
                        {
                        return $a['iiif_position'] - $b['iiif_position'];
                        }
                    return strcmp($a['iiif_position'],$b['iiif_position']);
                    });

                if (count($iiif_results_without_position) > 0 && count($iiif_results_with_position) > 0)
                    {
                    # Sort resources without a user supplied position by resource reference.
                    # These will appear at the end of the sequence after those with a user supplied position.
                    # Only applies if some resources have a sequence position else return in search results order per earlier behaviour.
                    usort($iiif_results_without_position, function($a, $b) { return $a['ref'] - $b['ref']; });
                    }

                $iiif->searchresults = array_merge($iiif_results_with_position, $iiif_results_without_position);
                foreach ($iiif->searchresults as $result_key => $result_val)
                    {
                    # Update iiif_position after sorting using unique array key, removing potential user entered duplicates in sequence field.
                    # iiif_get_canvases() requires unique iiif_position values.
                    $iiif->searchresults[$result_key]['iiif_position'] = $result_key;
                    debug("final iiif position $result_key given for resource ref " . $iiif->searchresults[$result_key]["ref"]);
                    }
                }

            if($iiif->request["type"] == "manifest" || $iiif->request["type"] == "")
                {
                /* MANIFEST REQUEST - see http://iiif.io/api/presentation/2.1/#manifest */
                $iiif->response["@context"] = "http://iiif.io/api/presentation/2/context.json";
                $iiif->response["@id"] = $iiif->rooturl . $iiif->request["id"] . "/manifest";
                $iiif->response["@type"] = "sc:Manifest";		

                // Descriptive metadata about the object/work
                // The manifest data should be the same for all resources that are returned.
                // This is the default when using the tms_link plugin for TMS integration.
                // Therefore we use the data from the first returned result.
                $iiif->data = get_resource_field_data($iiif->searchresults[0]["ref"]);

                // Label property
                foreach($iiif->searchresults as $iiif_result)
                    {
                    // Keep on until we find a label
                    $iiif_label = get_data_by_field($iiif->searchresults[0]["ref"], $iiif->title_field);
                    if(trim($iiif_label) != "")
                        {
                        $iiif->response["label"] = $iiif_label;
                        break;
                        }
                    }

                if(!$iiif_label)
                    {
                    $iiif->response["label"] = $lang["notavailableshort"];
                    }

                $iiif->response["description"] = get_data_by_field($iiif->searchresults[0]["ref"], $iiif->description_field);
                
                // Construct metadata array from resource field data 
                iiif_generate_metadata($iiif);
                
                $iiif->response["description"] = get_data_by_field($iiif->searchresults[0]["ref"], $iiif->description_field);
                if($iiif->license_field != 0)
                    {
                    $iiif->response["license"] = get_data_by_field($iiif->searchresults[0]["ref"], $iiif->license_field);
                    }

                // Thumbnail property
                foreach($iiif->searchresults as $iiif_result)
                    {
                    // Keep on until we find an image
                    $iiif_thumb = iiif_get_thumbnail($iiif, $iiif->searchresults[0]["ref"]);
                    if($iiif_thumb)
                        {
                        $iiif->response["thumbnail"] = $iiif_thumb;
                        break;
                        }
                    }

                if(!$iiif_thumb)
                    {
                    $iiif->response["thumbnail"] = $baseurl . "/gfx/" . get_nopreview_icon($iiif->searchresults[0]["resource_type"],"jpg",false);
                    }
                    
                // Sequences
                $iiif->response["sequences"] = array();
                $iiif->response["sequences"][0]["@id"] = $iiif->rooturl . $iiif->request["id"] . "/sequence/normal";
                $iiif->response["sequences"][0]["@type"] = "sc:Sequence";
                $iiif->response["sequences"][0]["label"] = "Default order";
                    
                                        
                $iiif->response["sequences"][0]["canvases"]  = iiif_get_canvases($iiif,false);
                $iiif->validrequest = true;	
                /* MANIFEST REQUEST END */
                }
            elseif($iiif->request["type"] == "canvas")
                {
                iiif_generate_canvas($iiif);
                }
            elseif($iiif->request["type"] == "sequence")
                {
                iiif_generate_sequence($iiif);
                }
            elseif($iiif->request["type"] == "annotation")
                {
                iiif_generate_annotation($iiif);
                }
                    
            }
        } // End of valid $identifier check based on search results
    else
        {
        $iiif->errorcode=404;
        $iiif->errors[] = "Invalid identifier: " . $iiif->request["id"];
        }
    return;
    }

function iiif_generate_sequence(&$iiif)
    {
    if($iiif->request["typeid"]=="normal")
        {
        $iiif->response["@context"] = "http://iiif.io/api/presentation/2/context.json";
        $iiif->response["@id"] = $iiif->rooturl . $iiif->request["id"] . "/sequence/normal";
        $iiif->response["@type"] = "sc:Sequence";
        $iiif->response["label"] = "Default order";
        $iiif->response["canvases"] = iiif_get_canvases($iiif->request["id"]);
        $iiif->validrequest = true;
        }
    return;
    }

function iiif_generate_canvas(&$iiif)
    {
    // This is essentially a resource
    // {scheme}://{host}/{prefix}/{identifier}/canvas/{name}
    $canvasid = $iiif->request["typeid"];
    $allcanvases = iiif_get_canvases($iiif->request["id"],true);
    $iiif->response["@context"] =  "http://iiif.io/api/presentation/2/context.json";
    $iiif->response = array_merge($iiif->response,$allcanvases[$canvasid]);
    $iiif->validrequest = true;
    }

function iiif_generate_annotation(&$iiif)
    {
    // See http://iiif.io/api/presentation/2.1/#image-resources                    
    // Need to find the resourceid the annotation is linked to
    $resourceid=0;
    $size_info=[];
    foreach($iiif->searchresults as $iiif_result)
        {
        if($iiif_result["iiif_position"] == $iiif->request["typeid"])
            {
            $resourceid = $iiif_result["ref"];
            $size_info = array(
                'identifier' => (strtolower($iiif_result['file_extension']) != 'jpg') ? 'hpr' : '',
                'return_height_width' => false,
            );
            $iiif->validrequest = true;
            break;
            }
        }
    if($iiif->validrequest)
        {
        $iiif->response["@context"] = "http://iiif.io/api/presentation/2/context.json";
        $iiif->response["@id"] = $iiif->rooturl . $iiif->request["id"] . "/annotation/" . $iiif->request["typeid"];
        $iiif->response["@type"] = "oa:Annotation";
        $iiif->response["motivation"] = "sc:painting";
        $iiif->response["resource"] = iiif_get_image($iiif, $resourceid, $iiif->request["typeid"], $size_info);
        $iiif->response["on"] = $iiif->rooturl . $iiif->request["id"] . "/canvas/" . $iiif->request["typeid"];
        }
    else
        {
        $iiif->errorcode=404;
        $iiif->errors[] = "Invalid annotation identifier: " . $iiif->request["id"];
        }
    }

function iiif_generate_metadata(&$iiif)
    {
    global $FIXED_LIST_FIELD_TYPES, $defaultlanguage;
    $iiif->response["metadata"] = [];
    $n=0;
    foreach($iiif->data as $iiif_data_row)
        {
        if(in_array($iiif_data_row["type"],$FIXED_LIST_FIELD_TYPES))
            {
            // Don't use the data as this has already concatentated the translations, add an entry for each node translation by building up a new array
            $resnodes = get_resource_nodes($iiif->searchresults[0]["ref"],$iiif_data_row["resource_type_field"],true);
            if(count($resnodes) == 0)
                {
                continue;
                }
            $langentries = array();
            $nodecount = 0;
            unset($def_lang);
            foreach($resnodes as $resnode)
                {
                debug("iiif: translating " . $resnode["name"] . " from field '" . $iiif_data_row["title"] . "'");
                $node_langs = i18n_get_translations($resnode["name"]);
                $transcount=0;
                $defaulttrans = "";
                foreach($node_langs as $nlang => $nltext)
                    {
                    if(!isset($langentries[$nlang]))
                        {
                        // This is the first translated node entry for this language. If we already have translations copy the default language array to make sure no nodes with missing translations are lost
                        debug("iiif: Adding a new translation entry for language '" . $nlang . "', field '" . $iiif_data_row["title"] . "'");
                        $langentries[$nlang] = isset($def_lang)?$def_lang:array();
                        }
                    // Add the node text to the array for this language;
                    debug("iiif: Adding node translation for language '" . $nlang . "', field '" . $iiif_data_row["title"] . "': " . $nltext);
                    $langentries[$nlang][] = $nltext;
                    
                    // Set default text for any translations
                    if($nlang == $defaultlanguage || $defaulttrans == ""){$defaulttrans = $nltext;}
                    $transcount++;
                    }
                $nodecount++;

                // There may not be translations for all nodes, fill any arrays that don't have an entry with the untranslated versions
                foreach($langentries as $mdlang => $mdtrans)
                    {
                    debug("iiif: enry count for " . $mdlang . ":" . count($mdtrans));
                    debug("iiif: node count: " . $nodecount);
                    if(count($mdtrans) != $nodecount)
                        {
                        debug("iiif: No translation found for " . $mdlang . ". Adding default translation to language array for field '" . $iiif_data_row["title"] . "': " . $mdlang . ": " . $defaulttrans);
                        $langentries[$mdlang][] =  $defaulttrans;
                        }
                    }

                // To ensure that no nodes are lost due to missing translations,  
                // Save the default language array to make sure we include any untranslated nodes that may be missing when/if we find new languages for the next node
                
                debug("iiif: Saving default language array for field '" . $iiif_data_row["title"] . "': " . implode(",",$langentries[$defaultlanguage]));
                // Default language is the ideal, but if no default language entries for this node have been found copy the first language we have
                reset($langentries);
                $def_lang = isset($langentries[$defaultlanguage])?$langentries[$defaultlanguage]:$langentries[key($langentries)];
                }		

            
            $iiif->response["metadata"][$n] = array();
            $iiif->response["metadata"][$n]["label"] = $iiif_data_row["title"];
            $iiif->response["metadata"][$n]["value"] = array();
            
            // Add each tag
            $o=0;
            foreach($langentries as $mdlang => $mdtrans)
                {
                debug("iiif: adding to metadata language array: " . $mdlang . ": " . implode(",",$mdtrans));
                $iiif->response["metadata"][$n]["value"][$o]["@value"] = implode(",",array_values($mdtrans));
                $iiif->response["metadata"][$n]["value"][$o]["@language"] = $mdlang;
                $o++;
                }
            $n++;
            }
        elseif(trim((string) $iiif_data_row["value"]) !== "")
            {
            $iiif->response["metadata"][$n] = array();
            $iiif->response["metadata"][$n]["label"] = $iiif_data_row["title"];
            $iiif->response["metadata"][$n]["value"] = $iiif_data_row["value"];
            $n++;
            }
        }
    }