<?php
include "../../include/db.php";

if(!$iiif_enabled || !isset($iiif_identifier_field) || !is_numeric($iiif_identifier_field) || !isset($iiif_userid) || !is_numeric($iiif_userid)){exit($lang["iiif_disabled"]);}

include_once "../../include/general.php";
include_once "../../include/resource_functions.php";
include_once "../../include/search_functions.php";
$debug = getval("debug","")!="";
   
$iiif_user = get_user($iiif_userid);
setup_user($iiif_user);

$rootlevel = $baseurl_short . "iiif/";
$rooturl = $baseurl . "/iiif/";
$request_url=strtok($_SERVER["REQUEST_URI"],'?');
$path=substr($request_url,strpos($request_url,$rootlevel) + strlen($rootlevel));
$xpath = explode("/",$path);

$validrequest = false;
$iiif_headers = array();
$errors=array();

if (count($xpath) == 1)
	{
	# Root level request - send information file only		   	
	$response["@context"] = "http://iiif.io/api/presentation/2/context.json";
  	$response["@id"] = $rooturl;
  	$response["@type"] = "sc:Manifest";
  	$response["@label"] = "";
	$response["width"] = 6000;
	$response["height"] = 4000;
			  
	$response["sizes"] = array();
	$response["sizes"][] = array("width" => 150, "height" => 100);
	$response["sizes"][] = array("width" => 600, "height" => 400);
	$response["sizes"][] = array("width" => 3000, "height" => 2000);
  	$response["tiles"] = array("width" => 512, "scaleFactors" => array(1,2,4,8,16));
	$response["profile"] = array("http://iiif.io/api/image/2/level2.json");
	
	$validrequest = true;	
	//echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);	
	//exit();
	}
else
	{
	$identifier = $xpath[0];
	if(is_numeric($identifier))
        {	
        $iiif_field = get_resource_type_field($iiif_identifier_field);
        $iiif_search = $iiif_field["name"] . ":" . $identifier;
        //$iiif_results = do_search($iiif_search,"","field" . $iiif_sequence_field,0,-1,"asc");
        $iiif_results = do_search($iiif_search);
        //print_r($iiif_results);
        
        if(!isset($xpath[1]))
            {
            $errorcode=404;
            $errors[] = "Bad request. Valid options are 'manifest', 'sequence' or 'full' e.g. ";
            $errors[] = "For the manifest: " . $rooturl . $xpath[0] . "/manifest";
            $errors[] = "For a sequence : " . $rooturl . $xpath[0] . "/sequence";
            }
        else
            {
            if(!is_array($iiif_results) || count($iiif_results) == 0)
                {
                $errorcode=404;
                $errors[] = "Invalid identifier: " . $identifier;
                }
            else
                {
                if($xpath[1] == "manifest")
                    {
                    /* MANIFEST REQUEST - see http://iiif.io/api/presentation/2.1/#manifest */
                    
                    $response["@context"] = "http://iiif.io/api/presentation/2/context.json";
                    $response["@id"] = $rooturl . $identifier . "/manifest";
                    $response["@type"] = "sc:Manifest";		
                    
                    // Descriptive metadata about the object/work
                    // The manifest data should be the same for all resources that are returned.
                    // This is the default when using the tms_link plugin for TMS integration. 
                    // Therefore we use the data from the first returned result.
                    $iiif_data = get_resource_field_data($iiif_results[0]["ref"]);
                    
                    $response["label"] = get_data_by_field($iiif_results[0]["ref"], $view_title_field);
                    
                    $response["description"] = get_data_by_field($iiif_results[0]["ref"], $iiif_description_field);
                    
                    $response["metadata"] = array();
                    $n=0;
                    foreach($iiif_data as $iiif_data_row)
                        {
                        $response["metadata"][$n] = array();		
                        $response["metadata"][$n]["label"] = $iiif_data[$n]["title"];
                        if(in_array($iiif_data[$n]["type"],$FIXED_LIST_FIELD_TYPES))
                            {
                            // Don't use the data as this has already concatentated the translations, add an entry for each node translation by building up a new array
                            $resnodes = get_resource_nodes($iiif_results[0]["ref"],$iiif_data[$n]["resource_type_field"],true);
                            $langentries = array();
                            foreach($resnodes as $resnode)
                                {
                                $node_langs = i18n_get_translations($resnode["name"]);
                                $transcount = 0;
                                $defaulttrans = "";
                                foreach($node_langs as $nlang => $nltext)
                                    {
                                    if(!isset($langentries[$nlang]))
                                        {
                                        // This is the first translated node entry for this language. If we already have translations copy the default language array to make sure no nodes with missing translations are lost
                                        $langentries[$nlang] = isset($def_lang)?$def_lang:array();
                                        }
                                    // Add the node text to the array for this language;
                                    $langentries[$nlang][] = $nltext;
                                    
                                    // Set default text for any translations
                                    if($nlang == $defaultlanguage || $defaulttrans == ""){$defaulttrans = $nltext;}
                                    
                                    $transcount++;						
                                    }
            
                                // There may not be translations for all nodes, fill the arrays with the untranslated versions
                                foreach($langentries as $mdlang => $mdtrans)
                                    {
                                    if(count($mdtrans) != $transcount)
                                        {
                                        $langentries[$mdlang][] =  $defaulttrans;
                                        }
                                    }						
                                // To ensure that no nodes are lost due to missing translations,  
                                // Save the default language array to make sure we include any untranslated nodes that may be missing when/if we find new languages for the next node
                                if(!isset($def_lang))
                                    {
                                    // Default language is the ideal, but if no default language entries for this node have been found copy the first language we have
                                    reset($langentries);
                                    $def_lang = isset($langentries[$defaultlanguage])?$langentries[$defaultlanguage]:$langentries[key($langentries)];
                                    }
                                }		
                                            
                            
                            $response["metadata"][$n]["value"] = array();
                            $o=0;
                            foreach($langentries as $mdlang => $mdtrans)
                                {
                                $response["metadata"][$n]["value"][$o]["@value"] = array();
                                $response["metadata"][$n]["value"][$o]["@value"][] = $mdtrans;
                                $response["metadata"][$n]["value"][$o]["@language"] = $mdlang;
                                $o++;
                                }
                            }
                        else
                            {
                            $response["metadata"][$n]["value"] = $iiif_data[$n]["value"];
                            }
                        $n++;
                        }
                        
                    $response["description"] = get_data_by_field($iiif_results[0]["ref"], $iiif_description_field);
                    if(isset($iiif_license_field))
                        {
                        $response["license"] = get_data_by_field($iiif_results[0]["ref"], $iiif_license_field);
                        }
                                        
                    // Thumbnail property
                    $response["thumbnail"] = array();
                    $response["thumbnail"]["@id"] = "http://example.org/images/book1-page1/full/80,100/0/default.jpg";
                    $response["thumbnail"]["@type"] = "dctypes:Image";
                    
                     // Get the size of the images
                    $img_path = get_resource_path($iiif_results[0]["ref"],true,'thm',false);
                    $image_size = get_original_imagesize($iiif_results[0]["ref"],$img_path);
                    $response["thumbnail"]["height"] = intval($image_size[1]);
                    $response["thumbnail"]["width"] = intval($image_size[2]);
                    $response["thumbnail"]["format"] = "image/jpeg";
                    
                    $response["thumbnail"]["service"] =array();
                    $response["thumbnail"]["service"]["@context"] = "http://iiif.io/api/image/2/context.json";
                    $response["thumbnail"]["service"]["@id"] = $rooturl . $identifier . "/full/thm/0/default.jpg";
                    $response["thumbnail"]["service"]["profile"] = "http://iiif.io/api/image/2/level1.json";
                    
                    
                    // Sequences
                    $response["sequences"] = array();                    
                    $response["sequences"][0]["@id"] = $rooturl . $identifier . "/sequence/normal";
                    $response["sequences"][0]["@type"] = "sc:Sequence";
                    $response["sequences"][0]["label"] = "Default order";
                       
                        
                    $canvases = array();
                    //$position=0;
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
                            }
                        else
                            {
                            $position++;
                            }
                        
                        $canvases[$position]["@id"] = $rooturl . $identifier . "/canvas/" . $position;
                        $canvases[$position]["@type"] = "sc:Canvas";
                        $canvases[$position]["label"] = "Default order";
                        
                        // Get the size of the images
                        $img_path = get_resource_path($iiif_result["ref"],true,'',false);
                        $image_size = get_original_imagesize($iiif_result["ref"],$img_path);
                        $canvases[$position]["height"] = intval($image_size[1]);
                        $canvases[$position]["width"] = intval($image_size[2]);
                        }
                    
                    ksort($canvases);
                    $response["sequences"][0]["canvases"]=array();
                    foreach($canvases as $canvas)
                        {
                        $response["sequences"][0]["canvases"][] = $canvas;
                        }
                    $validrequest = true;	
                    /* MANIFEST REQUEST END */
                    }
                elseif($xpath[1] == "canvas")
                    {
                    // {scheme}://{host}/{prefix}/{identifier}/canvas/{name}
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
                    
                    }
                elseif($xpath[1] == "sequence")
                    {
                    // {scheme}://{host}/{prefix}/{identifier}/sequence/{name}
                    /*
                    {
                      // Metadata about this sequence
                      "@context": "http://iiif.io/api/presentation/2/context.json",
                      "@id": "http://example.org/iiif/book1/sequence/normal",
                      "@type": "sc:Sequence",
                      "label": "Current Page Order",
            
                      "viewingDirection": "left-to-right",
                      "viewingHint": "paged",
                      "startCanvas": "http://example.org/iiif/book1/canvas/p2",
            
                      // The order of the canvases
                      "canvases": [
                        {
                          "@id": "http://example.org/iiif/book1/canvas/p1",
                          "@type": "sc:Canvas",
                          "label": "p. 1"
                          // ...
                        },
                        {
                          "@id": "http://example.org/iiif/book1/canvas/p2",
                          "@type": "sc:Canvas",
                          "label": "p. 2"
                          // ...
                        },
                        {
                          "@id": "http://example.org/iiif/book1/canvas/p3",
                          "@type": "sc:Canvas",
                          "label": "p. 3"
                          // ...
                        }
                      ]
                    }
                    */
                    if(isset($xpath[2]) && $xpath[2]=="normal")
                        {
                        $response["@context"] = "http://iiif.io/api/presentation/2/context.json";
                        $response["@id"] = $rooturl . $identifier . "/sequence/normal";
                        $response["type"] = "sc:Sequence";
                        $response["label"] = "Default order";
                        
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
                                }
                            else
                                {
                                $position++;
                                }
                            
                            $canvases[$position]["@id"] = $rooturl . $identifier . "/canvas/" . $position;
                            $canvases[$position]["type"] = "sc:Sequence";
                            $canvases[$position]["label"] = "Default order";
                            }
                        $response["canvases"] = array_values($canvases);
                        $validrequest = true;
                        }
                    }
                else
                    {
                    // IMAGE REQUEST (http://iiif.io/api/image/2.1/)
                    if($xpath[1] == "info.json")
                        {			
                        // Image information request. Only fullsize available in this initial version
                        $response["@context"] = "http://iiif.io/api/image/2/context.json";
                        $response["@id"] = $rooturl . $identifier . "/info.json";
                        $response["protocol"] = "http://iiif.io/api/image";			
                        
                        $img_path = get_resource_path($iiif_results[0]["ref"],true,'',false);
                        $image_size = get_original_imagesize($iiif_results[0]["ref"],$img_path);
                        //print_r($image_size);
                        $response["width"] = $image_size[1];
                        $response["height"] = $image_size[2];
                        
                        $response["sizes"] = array();
                        $response["sizes"][0]["width"] = $image_size[1];
                        $response["sizes"][0]["height"] = $image_size[2];
                        
                        $response["profile"] = array();
                        $response["profile"][] = "http://iiif.io/api/image/2/level0.json";
                        $response["profile"][] = array(
                            "formats" => array("jpg"),
                            "qualities" => array("color"),
                            "supports" => array("baseUriRedirect")				
                            );
                        
                        $iiif_headers[] = 'Link: <http://iiif.io/api/image/2/level0.json>;rel="profile"';
                        $validrequest = true;
                        }
                        
                    // The IIIF Image API URI for requesting an image must conform to the following URI Template:
                    // {scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
                    // Initial version only supports full image
                    elseif(!isset($xpath[2]) || !isset($xpath[3]) || !isset($xpath[4]) || !isset($xpath[4]) || strpos(".",$xpath[4] === false))
                        {
                        $errorcode= 400;
                        $errors[] = "Invalid image request format.";
                        }
                    else
                        {
                        // Request is ok so far, check the request parameters
                        $region = $xpath[1];
                        $size = $xpath[2];
                        $rotation = $xpath[3];
                        $formatparts =explode(".",$xpath[4]);
                        if(count($formatparts) != 2)
                            {
                            // Format. As we only support IIIF Image level 0 only a value of 'jpg' is accepted 
                            $errorcode=501;
                            $errors[] = "Invalid quality or format requested. Try using 'default.jpg'";   
                            }
                        else
                            {
                            $quality = $formatparts[0];
                            $format = $formatparts[1];
                            }
                        
                        if($region != "full")
                            {
                            // Invalid region, only 'full' specified at present
                            $errorcode=501;
                            $errors[] = "Invalid region requested. Only 'full' is permitted";   
                            }
                        if($size != "full"  && $size != "max" && $size != "thm")
                            {
                            // Need full size image, only max resolution is available
                            $errorcode=501;
                            $errors[] = "Invalid size requested. Only 'max', 'full' or 'thm' is permitted";   
                            }
                        if($rotation!=0)
                            {
                            // Rotation. As we only support IIIF Image level 0 only a rotation value of 0 is accepted 
                            $errorcode=501;
                            $errors[] = "Invalid rotation requested. Only '0' is permitted";   
                            }
                         if(isset($quality) && $quality != "default" && $quality != "color")
                            {
                            // Quality. As we only support IIIF Image level 0 only a quality value of 'default' or 'color' is accepted 
                            $errorcode=501;
                            $errors[] = "Invalid quality requested. Only 'default' is permitted";   
                            }
                         if(isset($format) && strtolower($format) != "jpg")
                            {
                            // Format. As we only support IIIF Image level 0 only a value of 'jpg' is accepted 
                            $errorcode=501;
                            $errors[] = "Invalid format requested. Only 'jpg' is permitted";   
                            }
                            
                        if(!isset($errorcode))
                            {                                           
                            // Request is supported, send the image
                            $validrequest = true;
                            $imgfound = false;
                            foreach($iiif_results as $iiif_result)
                                {
                                $imgpath = get_resource_path($iiif_result["ref"],true,($size == "thm"?'thm':''),false,"jpg");
                                if(file_exists($imgpath))
                                    {
                                    //$imgurl = get_resource_path($iiif_result["ref"],false,'',false,"jpg");
                                    $response_image=$imgpath;
                                    $imgfound = true;
                                    break;
                                    }
                                }
                            if(!$imgfound)
                                {
                                $errorcode = "404";
                                $errors[] = "No image available for this identifier";
                                }
                            }
                        else
                            {
                            // Invalid request format
                            $errorcode=400;
                            $errors[] = "Bad request. Please check the format of your request.";
                            $errors[] = "For the full image use " . $rooturl . $xpath[0] . "/full/max/0/default.jpg";
                            }
                        }
                    /* IMAGE REQUEST END */
                    }
                }
            }
        } // End of !is_numeric($identifier)
    else
        {
        $errorcode=404;
        $errors[] = "Invalid identifier: " . $identifier;
        }
    }
    
    // Send the data 
	if($validrequest)
		{
		http_response_code(200); # Send OK
        header("Access-Control-Allow-Origin: *");
		if(isset($response_image))
            {
            // Send the image
            $file_size   = filesize_unlimited($response_image);
            $file_handle = fopen($response_image, 'rb');
            header("Access-Control-Allow-Origin: *");
            header('Content-Disposition: inline;');
            header('Content-Transfer-Encoding: binary');
            $mime = get_mime_type($response_image);
            header("Content-Type: {$mime}");
            $sent = 0;
            while($sent < $file_size)
                {
                echo fread($file_handle, $download_chunk_size);
        
                ob_flush();
                flush();
        
                $sent += $download_chunk_size;
        
                if(0 != connection_status()) 
                    {
                    break;
                    }
                }
        
            fclose($file_handle);
            }
        else
            {
            header("Content-type: application/json");
            foreach($iiif_headers as $iiif_header)
                {
                header($iiif_header);
                }
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
		exit();
		}
	else
		{
		http_response_code($errorcode); # Send error status
        if($debug)
            {
            echo implode("<br />",$errors);	 
            }
		else
            {
            echo implode("\n",$errors);
            }
		}
	
