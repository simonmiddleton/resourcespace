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
    $params=[];parse_str($query,$params);
    if (!array_key_exists("function",$params)) {return false;}
    $function=$params["function"];
    if (!function_exists("api_" . $function)) {return false;}

    global $lang;

    // Construct an array of the real params, setting default values as necessary
    $setparams = [];
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
* Find all the resources to generate an array of all the canvases for the identifier ready for JSON encoding
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
    $canvases = [];
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
        // $canvases[$position]["id"] = $iiif->rooturl . $iiif->request["id"] . "/canvas/" . $position;
        // $canvases[$position]["type"] = "Canvas";
        // $canvases[$position]["label"]["none"] = [];
        // $canvases[$position]["label"]["none"][] = (isset($position_prefix)?$position_prefix:'') . $position;
        // $canvases[$position]["items"] = [];
        $canvases[$position] = iiif_generate_canvas($iiif,$position);
        }

	if($sequencekeys)
		{
		// keep the sequence identifiers as keys so a required canvas can be accessed by sequence id
		return $canvases;
		}

    ksort($canvases);
    $return=[];
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
    $thumbnail = [];
    $thumbnail["id"] = $iiif->rootimageurl . $resourceid . "/full/thm/0/default.jpg";
    $thumbnail["type"] = "Image";
	$thumbnail["format"] = "image/jpeg";

    $img_path = get_resource_path($resourceid,true,'thm',false);
	if(!file_exists($img_path))
        {
        $iiif->response["thumbnail"] = $GLOBALS["baseurl"] . "/gfx/" . get_nopreview_icon($iiif->searchresults[0]["resource_type"],"jpg",false);
        }
    else
        {
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
        }
	$thumbnail["service"] = [generate_iiif_image_service($iiif,$resourceid)];
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
function iiif_get_image($iiif,$resource, array $size_info)
    {
    // Quick validation of the size_info param
    if(empty($size_info) || (!isset($size_info['identifier']) && !isset($size_info['return_height_width'])))
        {
        return false;
        }

    $size = $size_info['identifier'];
    $return_height_width = $size_info['return_height_width'];

	$img_path = get_resource_path($resource,true,$size,false);
	if(!file_exists($img_path))
            {
		    return false;
            }

    $image_size = get_original_imagesize($resource, $img_path);

	$images = [];
	$images = [];
	$images["id"] = $iiif->rootimageurl . $resource . "/full/max/0/default.jpg";
	$images["type"] = "Image";
	$images["format"] = "image/jpeg";
    $images["height"] = intval($image_size[2]);
    $images["width"] = intval($image_size[1]);

	$images["service"] = [generate_iiif_image_service($iiif,$resource)];

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
function iiif_error($errorcode = 404, $errors = [])
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


/**
 * Extract IIIF request details from the URL path
 *
 * @param object    $iiif   The current IIIF request object generated in api/iiif/handler.php
 *
 * @return void
 *
 */
function iiif_parse_url(&$iiif) : void
    {
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

/**
 * Generate the top level manifest
 * @param object    $iiif   The current IIIF request object generated in api/iiif/handler.php
 *
 * @return void
 *
 */
function iiif_generate_manifest(&$iiif)
    {
    global $lang, $baseurl, $defaultlanguage;
    if($iiif->request["id"] != "" && $iiif->request["type"] == "")
        {
        // Redirect to manifest
        $redirurl = $iiif->rooturl . $iiif->request["id"] . "/manifest";
        if(function_exists("http_response_code"))
            {
            http_response_code(303); # Send error status
            }
        header ("Location: " . $redirurl);
        exit();
        }

    iiif_get_resources($iiif);

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
            $iiif_results_with_position = [];
            $iiif_results_without_position = [];
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
                /* MANIFEST REQUEST - see http://iiif.io/api/presentation/3.0/#manifest */
                $iiif->response["@context"] = "http://iiif.io/api/presentation/3/context.json";
                $iiif->response["id"] = $iiif->rooturl . $iiif->request["id"] . "/manifest";
                $iiif->response["type"] = "Manifest";
                $iiif->response["behavior"] = "paged";

                // Descriptive metadata about the object/work
                // The manifest data should be the same for all resources that are returned.
                // This is the default when using the tms_link plugin for TMS integration.
                // Therefore we use the data from the first returned result.
                $iiif->data = get_resource_field_data($iiif->searchresults[0]["ref"]);

                // Label property
                foreach($iiif->searchresults as $iiif_result)
                    {
                    // Keep on until we find a label
                    $iiif_label = get_data_by_field($iiif_result["ref"], $iiif->title_field);
                    if(trim($iiif_label) != "")
                        {
                        $i18n_values = i18n_get_translations($iiif_label);
                        foreach($i18n_values as $langcode=>$langstring)
                            {
                            $iiif->response["label"][$langcode] =[$langstring];
                            }
                        break;
                        }
                    }
                if(!$iiif_label)
                    {
                    $iiif->response["label"][$defaultlanguage] = $lang["notavailableshort"];
                    }

                foreach($iiif->searchresults as $iiif_result)
                    {
                    $description = get_data_by_field($iiif_result["ref"], $iiif->description_field);
                    if(trim($description) != "")
                        {
                        $i18n_values = i18n_get_translations($description);
                        foreach($i18n_values as $langcode=>$langstring)
                            {
                            $iiif->response["summary"][$langcode] =[$langstring];
                            }
                        break;
                        }
                    }
                // Construct metadata array from resource field data
                $iiif->response["metadata"] = iiif_generate_metadata($iiif);
                if($iiif->license_field != 0)
                    {
                    $iiif->response["rights"] = get_data_by_field($iiif->searchresults[0]["ref"], $iiif->license_field);
                    }
                    $iiif->response["rights"] = "http://creativecommons.org/publicdomain/mark/1.0/";


                // Thumbnail property
                $iiif->response["thumbnail"] =[];
                foreach($iiif->searchresults as $iiif_result)
                    {
                    // Keep on until we find an image
                    $iiif_thumb = iiif_get_thumbnail($iiif, $iiif->searchresults[0]["ref"]);
                    if($iiif_thumb)
                        {
                        $iiif->response["thumbnail"][] = $iiif_thumb;
                        break;
                        }
                    }

                // Sequences
                $iiif->response["items"] = iiif_get_canvases($iiif,false);

                // Add as sequences for Universal Viewer support (images not working with a v3.0 manifest at time of writing)
                $iiif->response["sequences"] = iiif_generate_sequence($iiif);

                $iiif->validrequest = true;
                /* MANIFEST REQUEST END */
                }
            elseif($iiif->request["type"] == "canvas")
                {
                iiif_get_resource_from_position($iiif,$iiif->request["typeid"]);

                $iiif->response = iiif_generate_canvas($iiif,$iiif->request["typeid"]);;
                $iiif->validrequest = true;
                }
            elseif($iiif->request["type"] == "sequence")
                {
                $iiif->response = iiif_generate_sequence($iiif);
                $iiif->validrequest = true;
                }
            elseif($iiif->request["type"] == "annotationpage")
                {
                iiif_get_resource_from_position($iiif,$iiif->request["typeid"]);
                $iiif->response = iiif_generate_annotation_page($iiif,$iiif->request["typeid"]);
                $iiif->validrequest = true;
                }
            elseif($iiif->request["type"] == "annotation")
                {
                iiif_get_resource_from_position($iiif,$iiif->request["typeid"]);
                $iiif->response = iiif_generate_annotation($iiif,$iiif->request["typeid"]);
                $iiif->validrequest = true;
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

/**
 * Generate the sequence - TODO - update this for IIIF API v3.0
 * @param object    $iiif   The current IIIF request object generated in api/iiif/handler.php
 *
 * @return void
 *
 */
function iiif_generate_sequence(&$iiif)
    {
    if($iiif->request["typeid"]=="normal")
        {
        $iiif->response["@context"] = "http://iiif.io/api/presentation/3/context.json";
        $iiif->response["id"] = $iiif->rooturl . $iiif->request["id"] . "/sequence/normal";
        $iiif->response["type"] = "sc:Sequence";
        $arr_langdefault = i18n_get_all_translations("default");
        foreach($arr_langdefault as $langcode=>$langdefault)
            {
            $iiif->response["label"][$langcode] = $langdefault;
            }
        $iiif->response["canvases"] = iiif_get_canvases($iiif);
        $iiif->validrequest = true;
        }
    return;
    }

/**
 * Generate a canvas
 *
 * @param object    $iiif       The current IIIF request object generated in api/iiif/handler.php
 * @param int       $position   The canvas identifier
 *
 * @return $canvas              Canvas data for presentation API response
 *
 */
function iiif_generate_canvas(object &$iiif, $position)
    {
    // This is essentially a resource
    // {scheme}://{host}/{prefix}/{identifier}/canvas/{name}
    $canvas = [];
    $canvasidx = array_search($position,array_column($iiif->searchresults,"iiif_position"));
    $resource = $iiif->searchresults[$canvasidx];

    $size = (strtolower($resource["file_extension"]) != "jpg") ? "hpr" : "";
    $img_path = get_resource_path($resource["ref"],true,$size,false);

    if(!file_exists($img_path))
        {
        $iiif->errors[] = "Invalid canvas requested";
        iiif_error(404,$iiif->errors);
        }
    $position_prefix = "";
    $position_field=get_resource_type_field($iiif->sequence_field);
    if($position_field !== false)
        {
        $position_prefix = $position_field["name"] . " ";
        }

    $position = $resource["iiif_position"];
    $position_val = $resource["field" . $iiif->sequence_field] ?? get_data_by_field($resource["ref"], $iiif->sequence_field);
    //$canvas["@context"] = "http://iiif.io/api/presentation/3/context.json";
    $canvas["id"] = $iiif->rooturl . $iiif->request["id"] . "/canvas/" . $position;
    $canvas["type"] = "Canvas";
    $canvas["label"]["none"] = [$position_prefix . $position_val];


    // Get the size of the images
    $image_size = get_original_imagesize($resource["ref"],$img_path);
    $canvas["height"] = intval($image_size[2]);
    $canvas["width"] = intval($image_size[1]);

    // "If the largest images dimensions are less than 1200 pixels on either edge, then the canvases dimensions should be double those of the image." - From http://iiif.io/api/presentation/2.1/#canvas
    if($image_size[1] < 1200 || $image_size[2] < 1200)
        {
        $image_size[1] = $image_size[1] * 2;
        $image_size[2] = $image_size[2] * 2;
        }

    //$canvases[$position]["thumbnail"] = iiif_get_thumbnail($iiif, $resource["ref"]);

    // Add image (only 1 per canvas currently supported)
    iiif_get_resource_from_position($iiif,$position);
    $canvas["items"][] = iiif_generate_annotation_page($iiif, $position);

    return $canvas;
    }



/**
 * Generate the AnnotationPage elements
 *
 * @param object    $iiif       The current IIIF request object generated in api/iiif/handler.php
 * @param int       $position   The annotation position
 *
 * @return array    Array of annotation pages
 *
 */
function iiif_generate_annotation_page(object &$iiif, int $position=0) : array
    {
    $annotationpages=[];
    $annotationpages["id"] = $iiif->rooturl . $iiif->request["id"] . "/annotationpage/" . $position;
    $annotationpages["type"] = "AnnotationPage";
    $annotationpages["items"] = [];
    $annotationpages["items"][]=iiif_generate_annotation($iiif, $position);
    return $annotationpages;
    }

/**
 * Generate the Annotation elements
 *
 *
 * @param object    $iiif       The current IIIF request object generated in api/iiif/handler.php
 * @param int       $position   The annotation position
 *
 * @return array    Array of annotations
 */
function iiif_generate_annotation(object &$iiif, int $position=0) : array
    {
    $annotation["id"] = $iiif->rooturl . $iiif->request["id"] . "/annotation/" . $position;
    $annotation["type"] = "Annotation";
    $annotation["motivation"] = "Painting";
    $annotation["body"] = iiif_get_image($iiif, $iiif->processing["resource"], $iiif->processing["size_info"]);
    $annotation["target"] = $iiif->rooturl . $iiif->request["id"] . "/canvas/" . $position;
    return $annotation;
    }

/**
 * Generates the IIIF response for the current IIIF object (presentation API)
 *
 * @param object    $iiif       The current IIIF request object generated in api/iiif/handler.php
 * 
 * @return $metadata            Array with all object metadata
 * 
 */
function iiif_generate_metadata(object &$iiif) : array
    {
   $metadata = [];
    $n=0;
    foreach($iiif->data as $iiif_data_row)
        {
        if(in_array($iiif_data_row["type"],$GLOBALS["FIXED_LIST_FIELD_TYPES"]))
            {
            // Don't use the data as this has already concatentated the translations, add an entry for each node translation by building up a new array
            $resnodes = get_resource_nodes($iiif->searchresults[0]["ref"],$iiif_data_row["resource_type_field"],true);
            if(count($resnodes) == 0)
                {
                continue;
                }
            // Add all translated field names
           $metadata[$n] = [];
           $metadata[$n]["label"] = [];
            $i18n_titles = i18n_get_translations($iiif_data_row["title"]);
            foreach($i18n_titles as $langcode=>$langstring)
                {
               $metadata[$n]["label"][$langcode] =[$langstring];
                }

            // Add all translated node names
            $arr_showlangs = [];
            $arr_alllangstrings = [];
            $arr_lang_default = [];
            foreach($resnodes as $resnode)
                {
                $node_langs_avail = [];
                $i18n_names = i18n_get_translations($resnode["name"]);
                // Set default in case no translation available for any languages
                $defaultnodename = $i18n_names[$GLOBALS["defaultlanguage"]];
                $arr_lang_default[] =  $defaultnodename;

                foreach($i18n_names as $langcode=>$langstring)
                    {
                    $node_langs_avail[] = $langcode;
                    if(!isset($arr_alllangstrings[$langcode]))
                        {
                        // This is the first time this language has been found for this field
                        // Initialise the language by copying the default array of values found so far
                        $arr_alllangstrings[$langcode] = $arr_lang_default;
                        }
                    // Add to array
                    $arr_alllangs[$langcode][] =$langstring;
                    $arr_showlangs[] = $langcode;
                    }

                // Check that this node string has been added for all translations found so far
                foreach($arr_alllangstrings as $langcode=>$strings)
                    {
                    if(!in_array($langcode,$node_langs_avail))
                        {
                        $arr_alllangstrings[$langcode][] = $defaultnodename;
                        }
                    }
                }
           $metadata[$n]["value"] = [];
            foreach($arr_alllangstrings as $langcode=>$strings)
                {
               $metadata[$n]["value"][$langcode] = implode(NODE_NAME_STRING_SEPARATOR,$strings);
                }
            }
        elseif(trim((string) $iiif_data_row["value"]) !== "")
            {
           $metadata[$n] = [];
           $metadata[$n]["label"] = [];
            $i18n_titles = i18n_get_translations($iiif_data_row["title"]);
            foreach($i18n_titles as $langcode=>$langstring)
                {
               $metadata[$n]["label"][$langcode] =[$langstring];
                }
           $metadata[$n]["value"]=[];
            $i18n_titles = i18n_get_translations($iiif_data_row["value"]);
            foreach($i18n_titles as $langcode=>$langstring)
                {
                $metadata[$n]["value"][$langcode] =[$langstring];
                }
            $n++;
            }
        }
    return $metadata;
    }

/**
 * Process the IIIF Image API request - TODO tidy this up
 *
 * @param object    $iiif       The current IIIF request object generated in api/iiif/handler.php
 * 
 * @return void
 * 
 */
function iiif_process_image_request(&$iiif) : void
    {
    $iiif->request["getext"] = "jpg";
    if($iiif->request["id"] === '')
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

    if (is_numeric($iiif->request["id"]))
        {
        $resource =  get_resource_data($iiif->request["id"]);
        $resource_access =  get_resource_access($iiif->request["id"]);
        }
    else
        {
        $resource_access = 2;
        }
    if($resource_access==0 && !in_array($resource["file_extension"], config_merge_non_image_types()))
        {
        // Check resource actually exists and is active
        $fulljpgsize = strtolower($resource["file_extension"]) != "jpg" ? "hpr" : "";
        $img_path = get_resource_path($iiif->request["id"],true,$fulljpgsize,false, "jpg");
        if(!file_exists($img_path))
            {
            // Missing file
            $iiif->errors[] = "No image available for this identifier";
            iiif_error(404,$iiif->errors);
            }
        $image_size = get_original_imagesize($iiif->request["id"],$img_path, "jpg");
        $iiif->imagewidth = (int) $image_size[1];
        $iiif->imageheight = (int) $image_size[2];
        $portrait = ($iiif->imageheight >= $iiif->imagewidth) ? TRUE : FALSE;

        // Get all available sizes
        $sizes = get_image_sizes($iiif->request["id"],true,"jpg",false);
        $availsizes = [];
        if ($iiif->imagewidth > 0 && $iiif->imageheight > 0)
            {
            foreach($sizes as $size)
                {
                // Compute actual pixel size - use same calculations as when generating previews
                if ($portrait)
                    {
                    // portrait or square
                    $preheight = $size['height'];
                    $prewidth = round(($iiif->imagewidth * $preheight + $iiif->imageheight - 1) / $iiif->imageheight);
                    }
                else
                    {
                    $prewidth = $size['width'];
                    $preheight = round(($iiif->imageheight * $prewidth + $iiif->imagewidth - 1) / $iiif->imagewidth);
                    }
                if($prewidth > 0 && $preheight > 0 && $prewidth <= $iiif->max_width && $preheight <= $iiif->max_height)
                    {
                    $availsizes[] = array("id"=>$size['id'],"width" => $prewidth, "height" => $preheight);
                    }
                }
            }

        if($iiif->request["region"] == "info.json")
            {
            // Image information request. Only fullsize available in this initial version
            $iiif->response["@context"] = "http://iiif.io/api/image/3/context.json";
            $iiif->response["extraFormats"] = [
                    "jpg",
                ];
            $iiif->response["extraQualities"] = [
                "default",
                ];
            $iiif->response["id"] = $iiif->rootimageurl . $iiif->request["id"];

            $iiif->response["height"] = $iiif->imageheight;
            $iiif->response["width"]  = $iiif->imagewidth;

            $iiif->response["type"] = "ImageService3";
            $iiif->response["profile"] = "level0";
            // if($iiif->custom_sizes)
            //     {
            //     $iiif->response["profile"][] = array(
            //         "formats" => array("jpg"),
            //         "qualities" => array("default"),
            //         "maxWidth" => $iiif->max_width,
            //         "maxHeight" => $iiif->max_height,
            //         "supports" => array("sizeByH","sizeByW")
            //         );
            //     }
            // else
            //     {
            //     $iiif->response["profile"][] = array(
            //         "formats" => array("jpg"),
            //         "qualities" => array("default"),
            //         "maxWidth" => $iiif->max_width,
            //         "maxHeight" => $iiif->max_height
            //         );
            //     }

            $iiif->response["protocol"] = "http://iiif.io/api/image";
            //$iiif->response["sizes"] = $availsizes;
            if($iiif->preview_tiles)
                {
                $iiif->response["tiles"] = [];
                $iiif->response["tiles"][] = array("height" => $iiif->preview_tile_size, "width" => $iiif->preview_tile_size, "scaleFactors" => $iiif->preview_tile_scale_factors);
                }
            $iiif->headers[] = 'Link: <http://iiif.io/api/image/3/level0.json>;rel="profile"';
            $iiif->validrequest = true;
            }
        else
            {
            // Process requested region
            if(!isset($iiif->errorcode) && $iiif->request["region"] != "full" && $iiif->request["region"] != "max" && $iiif->preview_tiles)
                {
                // If the request specifies a region which extends beyond the dimensions reported in the image information document,
                // then the service should return an image cropped at the image’s edge, rather than adding empty space.
                // If the requested region’s height or width is zero, or if the region is entirely outside the bounds
                // of the reported dimensions, then the server should return a 400 status code.

                $regioninfo = explode(",",$iiif->request["region"]);
                $region_filtered = array_filter($regioninfo, 'is_numeric');
                if(count($region_filtered) != 4)
                    {
                    // Invalid region
                    $iiif->errors[]  = "Invalid region requested. Use 'full' or 'x,y,w,h'";
                    iiif_error(400,$iiif->errors);
                    }
                else
                    {
                    $iiif->regionx = (int)$region_filtered[0];
                    $iiif->regiony = (int)$region_filtered[1];
                    $iiif->regionw = (int)$region_filtered[2];
                    $iiif->regionh = (int)$region_filtered[3];
                    debug("IIIF region requested: x:" . $iiif->regionx . ", y:" . $iiif->regiony . ", w:" .  $iiif->regionw . ", h:" . $iiif->regionh);
                    if(fmod($iiif->regionx,$iiif->preview_tile_size) != 0 || fmod($iiif->regiony,$iiif->preview_tile_size) != 0)
                        {
                        // Invalid region
                        $iiif->errors[]  = "Invalid region requested. Supported tiles are " . $iiif->preview_tile_size . "x" . $iiif->preview_tile_size . " at scale factors " . implode(",",$iiif->preview_tile_scale_factors) . ".";
                        iiif_error(400,$iiif->errors);
                        }
                    else
                        {
                        $tile_request = true;
                        }
                    }
                }
            else
                {
                // Full image requested
                $tile_request = false;
                }

            // Process size
            if(strpos($iiif->request["size"],",") !== false)
                {
                // Currently support 'w,' and ',h' syntax requests
                $getdims    = explode(",",$iiif->request["size"]);
                $iiif->getwidth   = (int)$getdims[0];
                $iiif->getheight  = (int)$getdims[1];
                if($tile_request)
                    {
                    if(!is_valid_tile_request($iiif))
                        {
                        $iiif->errors[] = "Invalid tile size requested";
                        iiif_error(400,$iiif->errors);
                        }

                    $iiif->request["getsize"] = "tile_" . $iiif->regionx . "_" . $iiif->regiony . "_". $iiif->regionw . "_". $iiif->regionh;
                    debug("IIIF" . $iiif->regionx . "_" . $iiif->regiony . "_". $iiif->regionw . "_". $iiif->regionh);
                    }
                else
                    {
                    if($iiif->getheight == 0)
                        {
                        $iiif->getheight = floor($iiif->getwidth * ($iiif->imageheight/$iiif->imagewidth));
                        }
                    elseif($iiif->getwidth == 0)
                        {
                        $iiif->getwidth = floor($iiif->getheight * ($iiif->imagewidth/$iiif->imageheight));
                        }
                    // Establish which preview size this request relates to
                    foreach($availsizes  as $availsize)
                        {
                        debug("IIIF - checking available size for resource " . $resource["ref"]  . ". Size '" . $availsize["id"] . "': " . $availsize["width"] . "x" . $availsize["height"] . ". Requested size: " . $iiif->getwidth . "x" . $iiif->getheight);
                        if($availsize["width"] == $iiif->getwidth && $availsize["height"] == $iiif->getheight)
                            {
                            $iiif->request["getsize"] = $availsize["id"];
                            }
                        }
                    if(!isset($iiif->request["getsize"]))
                        {
                        if(!$iiif->custom_sizes || $iiif->getwidth > $iiif->max_width || $iiif->getheight > $iiif->max_height)
                            {
                            // Invalid size requested
                            $iiif->errors[] = "Invalid size requested";
                            iiif_error(400,$iiif->errors);
                            }
                        else
                            {
                            $iiif->request["getsize"] = "resized_" . $iiif->getwidth . "_". $iiif->getheight;
                            }
                        }
                    }

                }
            elseif($iiif->request["size"] == "full"  || $iiif->request["size"] == "max" || $iiif->request["size"] == "thm")
                {
                if($tile_request)
                    {
                    if($iiif->request["size"] == "full"  || $iiif->request["size"] == "max")
                        {
                        $iiif->request["getsize"] = "tile_" . $iiif->regionx . "_" . $iiif->regiony . "_". $iiif->regionw . "_". $iiif->regionh;
                        $iiif->request["getext"] = "jpg";
                        }
                    else
                        {
                        $iiif->errors[] = "Invalid tile size requested";
                        iiif_error(501,$iiif->errors);
                        }
                    }
                else
                    {
                    // Full/max image region requested
                    if($iiif->max_width >= $iiif->imagewidth && $iiif->max_height >= $iiif->imageheight)
                        {
                        $isjpeg = in_array(strtolower($resource["file_extension"]),array("jpg","jpeg"));
                        $iiif->request["getext"] = strtolower($resource["file_extension"]) == "jpeg" ? "jpeg" : "jpg";
                        $iiif->request["getsize"] = $isjpeg ? "" : "hpr";
                        }
                    else
                        {
                        $iiif->request["getext"] = "jpg";
                        $iiif->request["getsize"] = count($availsizes) > 0 ? $availsizes[0]["id"] : "thm";
                        }
                    }
                }
            else
                {
                $iiif->errors[] = "Invalid size requested";
                iiif_error(400,$iiif->errors);
                }

            if($iiif->request["rotation"]!=0)
                {
                // Rotation. As we only support IIIF Image level 0 only a rotation value of 0 is accepted
                $iiif->errors[] = "Invalid rotation requested. Only '0' is permitted.";
                iiif_error(404,$iiif->errors);
                }
                if(isset($quality) && $quality != "default" && $quality != "color")
                {
                // Quality. As we only support IIIF Image level 0 only a quality value of 'default' or 'color' is accepted
                $iiif->errors[] = "Invalid quality requested. Only 'default' is permitted";
                iiif_error(404,$iiif->errors);
                }
                if(isset($format) && strtolower($format) != "jpg")
                {
                // Format. As we only support IIIF Image level 0 only a value of 'jpg' is accepted
                $iiif->errors[] = "Invalid format requested. Only 'jpg' is permitted.";
                iiif_error(404,$iiif->errors);
                }

            if(!isset($iiif->errorcode))
                {
                // Request is supported, send the image
                $imgpath = get_resource_path($iiif->request["id"],true,$iiif->request["getsize"],false,$iiif->request["getext"]);
                debug ("IIIF: image path: " . $imgpath);
                if(file_exists($imgpath))
                    {
                    $imgfound = true;
                    }
                else
                    {
                    if($iiif->request["region"] != "full" && $iiif->request["region"] != "max")
                        {
                        // Tiles have not yet been created
                        debug("IIIF: no preview tiles found for resource ". $resource["ref"]);
                        $iiif->errors[] = "Requested image is not currently available";
                        iiif_error(503,$iiif->errors);
                        }
                    else
                        {
                        if(is_process_lock('create_previews_' . $resource["ref"] . "_" . $iiif->request["getsize"]))
                            {
                            $iiif->errors[] = "Requested image is not currently available";
                            iiif_error(503,$iiif->errors);
                            }
                        $imgfound = @create_previews($iiif->request["id"],false,"jpg",false,true,-1,true,false,false,array($iiif->request["getsize"]));
                        clear_process_lock('create_previews_' . $resource["ref"] . "_" . $iiif->request["getsize"]);
                        }
                    }
                if($imgfound)
                    {
                    $iiif->validrequest = true;
                    $iiif->response_image=$imgpath;
                    }
                else
                    {
                    $iiif->errorcode = "404";
                    $iiif->errors[] = "No image available for this identifier";
                    }
                }
            }
        /* IMAGE REQUEST END */
        }
    else
        {
        $iiif->errors[] = "Missing or invalid identifier";
        iiif_error(404,$iiif->errors);
        }
    }


/**
 * Send the requested image to the IIIF client
 *
 * @param object    $iiif       The current IIIF request object generated in api/iiif/handler.php
 * 
 * @return void
 * 
 */
function iiif_render_image(object &$iiif) : void
    {
    // Send the image
    $file_size   = filesize_unlimited($iiif->response_image);
    $file_handle = fopen($iiif->response_image, 'rb');
    header("Access-Control-Allow-Origin: *");
    header('Content-Disposition: inline;');
    header('Content-Transfer-Encoding: binary');
    $mime = get_mime_type($iiif->response_image);
    header("Content-Type: {$mime}");
    $sent = 0;
    while($sent < $file_size)
        {
        echo fread($file_handle, $iiif->download_chunk_size);
        ob_flush();
        flush();
        $sent += $iiif->download_chunk_size;
        if(0 != connection_status())
            {
            break;
            }
        }

    fclose($file_handle);
    }

/**
 * Find all resources associated with the given identifier and adds to the $iiif object
 *
 * @param object    $iiif       The current IIIF request object generated in api/iiif/handler.php
 *
 * @return void
 *
 */
function iiif_get_resources(&$iiif) : void
    {
    $iiif_field = get_resource_type_field($iiif->identifier_field);
    $iiif_search = $iiif_field["name"] . ":" . $iiif->request["id"];
    $iiif->searchresults = do_search($iiif_search);
    }


/**
 * Update the $iiif object with the current resource at the given canvas position
 *
 * @param object    $iiif       The current IIIF request object generated in api/iiif/handler.php
 * @param int       $position   The annotation position
 * 
 * @return void
 * 
 */
function iiif_get_resource_from_position(&$iiif, $position) : void
    {
    $iiif->processing = [];
    foreach($iiif->searchresults as $iiif_result)
        {
        if($iiif_result["iiif_position"] == $position)
            {
            $iiif->processing["resource"] = $iiif_result["ref"];
            $iiif->processing["size_info"] = [
                'identifier' => (strtolower($iiif_result['file_extension']) != 'jpg') ? 'hpr' : '',
                'return_height_width' => false,
                ];
            break;
            }
        }
    }

/**
 * Generate the image API data
 *
 * @param object    $iiif           The current IIIF request object generated in api/iiif/handler.php     
 * @param int       $resourceid     Resource ID
 * 
 * @return array
 * 
 */
function generate_iiif_image_service(object $iiif, int $resourceid) : array 
    {
    $service = [];
    // $thumbnail["service"]["@context"] = "http://iiif.io/api/image/2/context.json";
	$service["id"] = $iiif->rootimageurl . $resourceid;
	$service["type"] = "ImageService3";
	$service["profile"] = "level0";
    return $service;
    }


function is_valid_tile_request(object &$iiif)
    {
    // echo "\nregionx $iiif->regionx";
    // echo "\nregionw $iiif->regionw";
    // echo "\nregiony $iiif->regiony";
    // echo "\nregionh $iiif->regionh";
    // echo "\ngetheight $iiif->getheight";
    // echo "\ngetwidth $iiif->getwidth";
    // echo "\nimagewidth $iiif->imagewidth";
    // echo "\nimageheight $iiif->imageheight";
    // echo "\nregionx + regionw " . $iiif->regionx + $iiif->regionw;
    // echo "\nregiony + regionh " . $iiif->regiony + $iiif->regionh;
    // echo "\nfmod regionw/getwidth " . fmod($iiif->regionw,$iiif->getwidth);
    // echo "\nfmod regionh/getheight " . fmod($iiif->regionh,$iiif->getheight);

    if(($iiif->getwidth == $iiif->preview_tile_size && $iiif->getheight == 0) // "w," 
        || ($iiif->getheight == $iiif->preview_tile_size && $iiif->getwidth == 0) // ",h" 
        || ($iiif->getheight == $iiif->preview_tile_size && $iiif->getwidth == $iiif->preview_tile_size)) // "w,h"
        {
        // Standard tile widths
        return true;
        }
    elseif(($iiif->regionx + $iiif->regionw) === ($iiif->imagewidth)
        || ((int)$iiif->regiony + (int)$iiif->regionh) === ((int)$iiif->imageheight)
        )
        {
        // Size specified is not the standard tile width - only valid for right side or bottom edge of image
        if(fmod($iiif->regionw,$iiif->getwidth) == 0
            && fmod($iiif->regionh,$iiif->getheight) == 0
            )
            {
            // return true;
            $hscale = ceil($iiif->regionw / $iiif->getwidth);
            $vscale = ceil($iiif->regionh / $iiif->getheight);
            if($hscale == $vscale && count(array_diff([$hscale,$vscale],$iiif->preview_tile_scale_factors)) == 0)
                {
                return true;
                }
            }
        }
    return false;
    }