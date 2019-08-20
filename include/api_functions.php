<?php
/*
 * API v2 functions
 *
 * Montala Limited, July 2016
 *
 * For documentation please see: http://www.resourcespace.com/knowledge-base/api/
 *
 */

function get_api_key($user)
    {
    // Return a private scramble key for this user.
    global $api_scramble_key;
    return hash("sha256", $user . $api_scramble_key);
    }

function check_api_key($username,$querystring,$sign)
    {
    // Check a query is signed correctly.
    
    // Fetch user ID and API key
    $user=get_user_by_username($username); if ($user===false) {return false;}
    $private_key=get_api_key($user);
        
    $aj = strpos($querystring,"&ajax=");
    if($aj != false)
        {
        $querystring = substr($querystring,0,$aj);
        }

    # Sign the querystring ourselves and check it matches.
    # First remove the sign parameter as this would not have been present when signed on the client.
    $s=strpos($querystring,"&sign=");

    if ($s===false || $s+6+strlen($sign)!==strlen($querystring)) {return false;}
    $querystring=substr($querystring,0,$s);

    # Calculate the expected signature.
    $expected=hash("sha256",$private_key . $querystring);
    
    # Was it what we expected?
    return $expected==$sign;
    }

function execute_api_call($query,$pretty=false)
    {
    // Execute the specified API function.
    $params=array();parse_str($query,$params);
    if (!array_key_exists("function",$params)) {return false;}
    $function=$params["function"];
    if (!function_exists("api_" . $function)) {return false;}
    
    // Construct an array of the real params, setting default values as necessary
    $setparams = array();
    $n = 0;    
    $fct = new ReflectionFunction("api_" . $function);
    foreach ($fct->getParameters() as $fparam)
        {
        $paramkey = $n + 1;
        debug ("API Checking for parameter " . $fparam->getName() . " (param" . $paramkey . ")");
        if (array_key_exists("param" . $paramkey,$params) && $params["param" . $paramkey] != "")
            {
            debug ("API " . $fparam->getName() . " -   value has been passed : '" . $params["param" . $paramkey] . "'");
            $setparams[$n] = $params["param" . $paramkey];
            }
        
        elseif ($fparam->isOptional())
            {
            // Set default value if nothing passed e.g. from API test tool
            debug ("API " . $fparam->getName() . " -  setting default value = '" . $fparam->getDefaultValue() . "'");
            $setparams[$n] = $fparam->getDefaultValue();
            }
        else
            {
             // Set as empty
            debug ("API " . $fparam->getName() . " -  setting null value = '" . $fparam->getDefaultValue() . "'");
            $setparams[$n] = "";    
            }
        $n++;
        }
    
    debug("API - calling api_" . $function);
    $result = call_user_func_array("api_" . $function, $setparams);
    if($pretty)
        {
            debug("API: json_encode() using JSON_PRETTY_PRINT");
            return json_encode($result,(defined('JSON_PRETTY_PRINT')?JSON_PRETTY_PRINT:0));
        }
    else
        {
            debug("API: json_encode()");
            $json_encoded_result = json_encode($result);

            if(json_last_error() !== JSON_ERROR_NONE)
                {
                debug("API: JSON error: " . json_last_error_msg());
                debug("API: JSON error when \$result = " . print_r($result, true));
                }

            return $json_encoded_result;
        }
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
* @param integer $identifier		IIIF identifier (this associates resources via the metadata field set as $iiif_identifier_field
* @param array $iiif_results		Array of ResourceSpace search results that match the $identifier, sorted 
* @param boolean $sequencekeys		Get the array with each key matching the value set in the metadata field $iiif_sequence_field. By default the array will be sorted but have a 0 based index
* 
* @return array
*/
function iiif_get_canvases($identifier, $iiif_results,$sequencekeys=false)
    {
    global $rooturl,$rootimageurl;	
			
    $canvases = array();
    foreach ($iiif_results as $iiif_result)
        {
		$size = (strtolower($iiif_result["file_extension"]) != "jpg") ? "hpr" : "";
        $img_path = get_resource_path($iiif_result["ref"],true,$size,false);

        if(!file_exists($img_path))
            {
            continue;
            }
			
		$position = $iiif_result["iiif_position"];
        $canvases[$position]["@id"] = $rooturl . $identifier . "/canvas/" . $position;
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
        
        $canvases[$position]["thumbnail"] = iiif_get_thumbnail($iiif_result["ref"]);
        
        // Add image (only 1 per canvas currently supported)
		$canvases[$position]["images"] = array();
        $size_info = array(
            'identifier' => $size,
            'return_height_width' => false,
        );
        $canvases[$position]["images"][] = iiif_get_image($identifier, $iiif_result["ref"], $position, $size_info);
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
* @param integer $resourceid		Resource ID
*
* @return array
*/
function iiif_get_thumbnail($resourceid)
    {
	global $rootimageurl;
	
	$img_path = get_resource_path($resourceid,true,'thm',false);
	if(!file_exists($img_path))
            {
		    return false;
            }
			
	$thumbnail = array();
	$thumbnail["@id"] = $rootimageurl . $resourceid . "/full/thm/0/default.jpg";
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
	$thumbnail["service"]["@id"] = $rootimageurl . $resourceid;
	$thumbnail["service"]["profile"] = "http://iiif.io/api/image/2/level1.json";
	return $thumbnail;
	}
	
/**
* Get the image for the specified identifier canvas and resource id
* 
* @uses get_original_imagesize()
* @uses get_resource_path()
* 
* @param integer $identifier  IIIF identifier (this associates resources via the metadata field set as $iiif_identifier_field
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
function iiif_get_image($identifier,$resourceid,$position, array $size_info)
    {
    global $rooturl,$rootimageurl;

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
	$images["@id"] = $rooturl . $identifier . "/annotation/" . $position;
	$images["@type"] = "oa:Annotation";
	$images["motivation"] = "sc:painting";
	
	$images["resource"] = array();
	$images["resource"]["@id"] = $rootimageurl . $resourceid . "/full/max/0/default.jpg";
	$images["resource"]["@type"] = "dctypes:Image";
	$images["resource"]["format"] = "image/jpeg";

    $images["resource"]["height"] = intval($image_size[2]);
    $images["resource"]["width"] = intval($image_size[1]);

	$images["resource"]["service"] =array();
	$images["resource"]["service"]["@context"] = "http://iiif.io/api/image/2/context.json";
	$images["resource"]["service"]["@id"] = $rootimageurl . $resourceid;
	$images["resource"]["service"]["profile"] = "http://iiif.io/api/image/2/level1.json";
	$images["on"] = $rooturl . $identifier . "/canvas/" . $position;

    if($return_height_width)
        {
        $images["height"] = intval($image_size[2]);
        $images["width"] = intval($image_size[1]);
        }

    return $images;  
	}

function iiif_error($errorcode = 404, $errors = array())
    {
    global $iiif_debug;
    if(function_exists("http_response_code"))
        {
        http_response_code($errorcode); # Send error status
        }
    if($iiif_debug)
        {
        echo implode("<br />",$errors);	 
        }
    else
        {
        echo implode("<br />",$errors);
        }
    exit();
    }