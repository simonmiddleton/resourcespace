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

function execute_api_call($query)
    {
    // Execute the specified API function.
    $params=array();parse_str($query,$params);        
    if (!array_key_exists("function",$params)) {return false;}
    $function=$params["function"];
    if (!function_exists("api_" . $function)) {return false;}
    
    $eval="return api_" . $function . "(";
    $n=1;while (true)
        {
        if (array_key_exists("param" . $n,$params))
            {
            if ($n>1) {$eval.=",";}
            $eval.="\"" . str_replace("\"","\\\"",$params["param" . $n]) . "\"";
            $n++;
            }
        else
            {
            break;
            }
        }
    $eval.=");";
    return json_encode(eval($eval));
    }
    

function iiif_get_canvases($identifier, $iiif_results,$sequencekeys=false)
    {
    global $rooturl,$rootimageurl,$iiif_sequence_field;	
	
	/*
	{
	// Metadata about this canvas
	"@context": "http://iiif.io/api/presentation/2/context.json",
	"@id": "http://example.org/iiif/book1/canvas/p1",
	"@type": "sc:Canvas",
	"label": "p. 1",
	"height": 1000,
	"width": 750,
	"thumbnail" : {
	  "@id" : "http://example.org/iiif/book1/canvas/p1/thumb.jpg",
	  "@type": "dctypes:Image",
	  "height": 200,
	  "width": 150
	},
	"images": [
	  {
		"@type": "oa:Annotation"
		// Link from Image to canvas should be included here, as below
	  }
	],
	"otherContent": [
	  {
		// Reference to list of other Content resources, _not included directly_
		"@id": "http://example.org/iiif/book1/list/p1",
		"@type": "sc:AnnotationList"
	  }
	]
  
	}
	*/
		
    $canvases = array();
    $position=0;
    foreach ($iiif_results as $iiif_result)
        {
        if(isset($iiif_sequence_field))
            {
            if(isset($iiif_result["field" . $iiif_sequence_field]))
                {
                $position = $iiif_result["field" . $iiif_sequence_field];
                }
            else
                {
                $position = get_data_by_field($iiif_result["ref"],$iiif_sequence_field);
                }
			$position_field=get_resource_type_field($iiif_sequence_field);
			$position_prefix = $position_field["name"] . " ";
            }
        else
            {
            $position++;
            }
        debug("iiif position" . $position);
        $canvases[$position]["@id"] = $rooturl . $identifier . "/canvas/" . $position;
        $canvases[$position]["@type"] = "sc:Canvas";
        //$canvases[$position]["label"] = "Default order";
        $canvases[$position]["label"] = (isset($position_prefix)?$position_prefix:'') . $position;
        
        // Get the size of the images
        $img_path = get_resource_path($iiif_result["ref"],true,'',false);
        $image_size = get_original_imagesize($iiif_result["ref"],$img_path);
        $canvases[$position]["height"] = intval($image_size[1]);
        $canvases[$position]["width"] = intval($image_size[2]);
        
        // Add images
        $canvases[$position]["images"] = array();
        $canvases[$position]["images"][0]["@id"] = $rooturl . $iiif_result["ref"] . "/annotation/" . $position;
        $canvases[$position]["images"][0]["@type"] = "oa:Annotation";
        $canvases[$position]["images"][0]["motivation"] = "sc:painting";
        
        $canvases[$position]["images"][0]["resource"] = array();
        $canvases[$position]["images"][0]["resource"]["@id"] = $rootimageurl . $iiif_result["ref"] . "/full/max/0/default.jpg";
        $canvases[$position]["images"][0]["resource"]["@type"] = "dctypes:Image";
        $canvases[$position]["images"][0]["resource"]["format"] = "image/jpeg";
        $canvases[$position]["images"][0]["resource"]["service"] =array();
        $canvases[$position]["images"][0]["resource"]["service"]["@context"] = "http://iiif.io/api/image/2/context.json";
        $canvases[$position]["images"][0]["resource"]["service"]["@id"] = $rooturl . "image/";
        $canvases[$position]["images"][0]["resource"]["service"]["profile"] = "http://iiif.io/api/image/2/level1.json";
        $canvases[$position]["images"][0]["on"] = $rooturl . $iiif_result["ref"] . "/canvas/" . $position;
        $canvases[$position]["images"][0]["resource"]["height"] = intval($image_size[1]);
        $canvases[$position]["images"][0]["resource"]["width"] = intval($image_size[2]);       
        
        }
    
	//print_r($canvases);
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

function iiif_get_thumbnail($identifier, $iiif_results)
    {
	global $rooturl;
	$thumbnail = array();
	$thumbnail["@id"] = $rooturl . $identifier . "/full/thm/0/default.jpg";
	$thumbnail["@type"] = "dctypes:Image";
	
	 // Get the size of the images
	$img_path = get_resource_path($iiif_results[0]["ref"],true,'thm',false);
	$image_size = get_original_imagesize($iiif_results[0]["ref"],$img_path);
	$thumbnail["height"] = intval($image_size[1]);
	$thumbnail["width"] = intval($image_size[2]);
	$thumbnail["format"] = "image/jpeg";
	
	$thumbnail["service"] =array();
	$thumbnail["service"]["@context"] = "http://iiif.io/api/image/2/context.json";
	$thumbnail["service"]["@id"] = $rooturl . "image/";
	$thumbnail["service"]["profile"] = "http://iiif.io/api/image/2/level1.json";
	return $thumbnail;
	}
	
	