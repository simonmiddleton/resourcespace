<?php
$suppress_headers = true;
include "../../include/db.php";
include "../../include/image_processing.php";

if(!$iiif_enabled || !isset($iiif_identifier_field) || !is_numeric($iiif_identifier_field) || !isset($iiif_userid) || !is_numeric($iiif_userid) || !isset($iiif_description_field))
    {
    exit($lang["iiif_disabled"]);
    }

include_once "../../include/general.php";
include_once "../../include/resource_functions.php";
include_once "../../include/search_functions.php";
include_once "../../include/api_functions.php";
$iiif_debug = getval("debug","")!="";

$iiif_user = get_user($iiif_userid);
setup_user($iiif_user);

$rootlevel = $baseurl_short . "iiif/";
$rooturl = $baseurl . "/iiif/";
$rootimageurl = $baseurl . "/iiif/image/";
$request_url=strtok($_SERVER["REQUEST_URI"],'?');
$path=substr($request_url,strpos($request_url,$rootlevel) + strlen($rootlevel));
$xpath = explode("/",$path);

$validrequest = false;
$iiif_headers = array();
$errors=array();
if (count($xpath) == 1 && $xpath[0] == "")
	{
	# Root level request - send information file only
	$response["@context"] = "http://iiif.io/api/presentation/2/context.json";
  	$response["@id"] = $rooturl;
  	$response["@type"] = "sc:Manifest";
  	$response["@label"] = "";
	$response["width"] = 6000;
	$response["height"] = 4000;

    $response["tiles"] = array();
    $response["tiles"][] = array("width" => $preview_tile_size, "height" => $preview_tile_size, "scaleFactors" => $preview_tile_scale_factors);
	$response["profile"] = array("http://iiif.io/api/image/2/level0.json");

	$validrequest = true;
    }
else
	{
	if(strtolower($xpath[0]) == "image")
        {
		// IMAGE REQUEST (http://iiif.io/api/image/2.1/)
        // The IIIF Image API URI for requesting an image must conform to the following URI Template:
        // {scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
        if(!isset($xpath[2]) || $xpath[2] == "")
            {
            // Redirect to image information document
            $redirurl = $_SERVER["REQUEST_URI"] . (!isset($xpath[2]) ? "/" : "") . "info.json";
            if(function_exists("http_response_code"))
                {
                http_response_code(303); # Send error status
                }
            header ("Location: " . $redirurl);
            exit();
            }

        $resourceid = $xpath[1];
        if (is_numeric($resourceid))
			{
			$resource =  get_resource_data($resourceid);
			$resource_access =  get_resource_access($resourceid);
			}
		else
			{
			$resource_access = 2;	
			}
            
		if($resource_access==0)
            {
            // Check resource actually exists and is active
            $img_path = get_resource_path($resourceid,true,'',false);
            $image_size = get_original_imagesize($resourceid,$img_path);
            $imageWidth = (int) $image_size[1];
            $imageHeight = (int) $image_size[2];
            $portrait = ($imageHeight >= $imageWidth) ? TRUE : FALSE;
            
            // Get all available sizes
            $sizes = get_image_sizes($resourceid,true,"jpg",false);
            $availsizes = array();
            foreach($sizes as $size)
                {
                // Compute actual pixel size - use same calculations as when generating previews
                if ($portrait)
                    {
                    // portrait or square
                    $preheight = $size['height'];
                    $prewidth = round(($imageWidth * $preheight + $imageHeight - 1) / $imageHeight);
                    }
                else
                    {                    
                    $prewidth = $size['width'];
                    $preheight = round(($imageHeight * $prewidth + $imageWidth - 1) / $imageWidth);
                    }
                if($prewidth > 0 && $preheight > 0 && $prewidth <= $iiif_max_width && $preheight <= $iiif_max_height)
                    {
                    $availsizes[] = array("id"=>$size['id'],"width" => $prewidth, "height" => $preheight);
                    }
                }

            if($xpath[2] == "info.json")
				{
				// Image information request. Only fullsize available in this initial version
				$response["@context"] = "http://iiif.io/api/image/2/context.json";
				$response["@id"] = $rootimageurl . $resourceid;
                				
				$response["height"] = $imageHeight;
				$response["width"]  = $imageWidth;
                
                $response["profile"] = array();
				$response["profile"][] = "http://iiif.io/api/image/2/level0.json";
				if($iiif_custom_sizes)
                    {
                    $response["profile"][] = array(
                        "formats" => array("jpg"),
                        "qualities" => array("default"),
                        "maxWidth" => $iiif_max_width,
                        "maxHeight" => $iiif_max_height,
                        "supports" => array("sizeByH","sizeByW")
                        );
                    }
                else
                    {
                    $response["profile"][] = array(
                        "formats" => array("jpg"),
                        "qualities" => array("default"),
                        "maxWidth" => $iiif_max_width,
                        "maxHeight" => $iiif_max_height
                        );
                    }

				$response["protocol"] = "http://iiif.io/api/image";
				$response["sizes"] = $availsizes;
                if($preview_tiles)
                    {
                    $response["tiles"] = array();
                    $response["tiles"][] = array("height" => $preview_tile_size, "width" => $preview_tile_size, "scaleFactors" => $preview_tile_scale_factors);
                    }
				$iiif_headers[] = 'Link: <http://iiif.io/api/image/2/level0.json>;rel="profile"';
				$validrequest = true;
				}
            elseif(!isset($xpath[3]) || !isset($xpath[4]) || !isset($xpath[5]) || !isset($xpath[5]) || $xpath[5] != "default.jpg")
				{
                // Not request for image infomation document and no sizes specified
				$errors[] = "Invalid image request format.";
				iiif_error(400,$errors);
				}
            else
				{
				// Check the request parameters
				$region = $xpath[2];
				$size = $xpath[3];
				$rotation = $xpath[4];
				$formatparts = explode(".",$xpath[5]);
				if(count($formatparts) != 2)
					{
					// Format. As we only support IIIF Image level 0 a value of 'jpg' is required 
					$errors[] = "Invalid quality or format requested. Try using 'default.jpg'";
                    iiif_error(400,$errors);
					}
				else
					{
					$quality = $formatparts[0];
					$format = $formatparts[1];
					}

                // Process requested region
                if(!isset($errorcode) && $region != "full" && $region != "max" && $preview_tiles)
					{
                    // If the request specifies a region which extends beyond the dimensions reported in the image information document,
                    // then the service should return an image cropped at the image’s edge, rather than adding empty space.
                    // If the requested region’s height or width is zero, or if the region is entirely outside the bounds
                    // of the reported dimensions, then the server should return a 400 status code.

                    $regioninfo = explode(",",$region);
                    $region_filtered = array_filter($regioninfo, 'is_numeric');
                    if(count($region_filtered) != 4)
                        {
                        // Invalid region
                        $errors[]  = "Invalid region requested. Use 'full' or 'x,y,w,h'";
                        iiif_error(400,$errors);
                        }
                    else
                        {
                        $regionx = (int)$region_filtered[0];
                        $regiony = (int)$region_filtered[1];
                        $regionw = (int)$region_filtered[2];
                        $regionh = (int)$region_filtered[3];
                        debug("IIIF region requested: x:" . $regionx . ", y:" . $regiony . ", w:" .  $regionw . ", h:" . $regionh);
                        if(fmod($regionx,$preview_tile_size) != 0 || fmod($regiony,$preview_tile_size) != 0)
                            {
                            // Invalid region
                            $errors[]  = "Invalid region requested. Supported tiles are " . $preview_tile_size . "x" . $preview_tile_size . " at scale factors " . implode($preview_tile_scale_factors,",") . ".";
                            iiif_error(400,$errors);
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
                if(strpos($size,",") !== false)
                    {
                    // Currently support 'w,' and ',h' syntax requests
                    $getdims    = explode(",",$size);
                    $getwidth   = (int)$getdims[0];
                    $getheight  = (int)$getdims[1];
                    //    
                    //echo ("regionx" . $regionx . "regiony" . $regiony) . "<br />";
                    //echo ("regionw" . $regionw . "regionh" . $regionh) . "<br />";
                    //echo("getwidth" . $getwidth . "getheight" . $getheight). "<br />";
                    //
                    if($tile_request)
                        {
                        if(($regionx + $regionw) >= $imageWidth || ($regiony + $regionh) >= $imageHeight)
                            {
                            // Size specified is not the standard tile width, may be right or bottom edge of image
                            $validtileh = false;
                            $validtilew = false;
                            
                            if($getwidth > 0 && $getheight == 0)
                                {
                                $scale = ceil($regionw / $getwidth);
                                }
                            elseif($getheight > 0 && $getwidth == 0)
                                {
                                $scale = ceil($regionh / $getheight);
                                }
                            else
                                {
                                $errors[] = "Invalid tile size requested";
                                iiif_error(501,$errors);
                                }
                                                        
                            if(!in_array($scale,$preview_tile_scale_factors))
                                {
                                $errors[] = "Invalid tile size requested";
                                iiif_error(501,$errors); 
                                }
                            }
                        elseif(($getwidth == $preview_tile_size && $getheight == 0) ||
                               ($getheight == $preview_tile_size && $getwidth == 0) ||
                               ($getheight == $preview_tile_size && $getwidth == $preview_tile_size))
                            {
                            $valid_tile = true;
                            }
                        else
                            {
                            $errors[] = "Invalid tile size requested";
                            iiif_error(400,$errors);                             
                            }
                            
                        $getsize = "tile_" . $regionx . "_" . $regiony . "_". $regionw . "_". $regionh;
                        $getext = "jpg";
                            
                        debug("IIIF" . $regionx . "_" . $regiony . "_". $regionw . "_". $regionh);
                        }
                    else
                        {
                        if($getheight == 0)
                            {
                            $getheight = floor($getwidth * ($imageHeight/$imageWidth));
                            }
                        elseif($getwidth == 0)
                            {
                            $getwidth = floor($getheight * ($imageWidth/$imageHeight));
                            }
                        // Establish which preview size this request relates to
                        foreach($availsizes  as $availsize)
                            {
                            debug("IIIF - checking available size for resource " . $resource["ref"]  . ". Size '" . $availsize["id"] . "': " . $availsize["width"] . "x" . $availsize["height"] . ". Requested size: " . $getwidth . "x" . $getheight);
                            if($availsize["width"] == $getwidth && $availsize["height"] == $getheight)
                                {
                                $getsize = $availsize["id"];
                                }
                            }
                        if(!isset($getsize))
                            {
                            if(!$iiif_custom_sizes || $getwidth > $iiif_max_width || $getheight > $iiif_max_height)
                                {
                                // Invalid size requested
                                $errors[] = "Invalid size requested";
                                iiif_error(400,$errors);                   
                                }
                            else
                                {
                                $getsize = "resized_" . $getwidth . "_". $getheight;
                                $getext = "jpg";
                                }
                            }   
                        }
                    
                    }
                elseif($size == "full"  || $size == "max" || $size == "thm")
                    {
                    if($tile_request)
                        {
                        if($size == "full"  || $size == "max")
                            {
                            $getsize = "tile_" . $regionx . "_" . $regiony . "_". $regionw . "_". $regionh;
                            $getext = "jpg";
                            }
                        else
                            {
                            $errors[] = "Invalid tile size requested";
                            iiif_error(501,$errors);    
                            }
                        }
                    else
                        {
                        // Full/max image region requested
                        if($iiif_max_width >= $imageWidth && $iiif_max_height >= $imageHeight)
                            {
                            $isjpeg = in_array(strtolower($resource["file_extension"]),array("jpg","jpeg"));
                            $getext = strtolower($resource["file_extension"]) == "jpeg" ? "jpeg" : "jpg";
                            $getsize = $isjpeg ? "" : "hpr";
                            }
                        else
                            {
                            $getext = "jpg";
                            $getsize = count($availsizes) > 0 ? $availsizes[0]["id"] : "thm";
                            }
                        }                   
                        
                    }
                else
                    {
                    $errors[] = "Invalid size requested";
                    iiif_error(400,$errors);  
                    }
                
				if($rotation!=0)
					{
					// Rotation. As we only support IIIF Image level 0 only a rotation value of 0 is accepted 
					$errors[] = "Invalid rotation requested. Only '0' is permitted.";
                    iiif_error(404,$errors);  
					}
				 if(isset($quality) && $quality != "default" && $quality != "color")
					{
					// Quality. As we only support IIIF Image level 0 only a quality value of 'default' or 'color' is accepted 
					$errors[] = "Invalid quality requested. Only 'default' is permitted";
                    iiif_error(404,$errors);  
					}
				 if(isset($format) && strtolower($format) != "jpg")
					{
					// Format. As we only support IIIF Image level 0 only a value of 'jpg' is accepted 
					$errors[] = "Invalid format requested. Only 'jpg' is permitted."; 
                    iiif_error(404,$errors);    
					}

                if(!isset($errorcode))
                    {
                    // Request is supported, send the image
                    $imgpath = get_resource_path($resourceid,true,$getsize,false,$getext);
                    debug ("IIIF: image path: " . $imgpath);
					if(file_exists($imgpath))
						{
                        $imgfound = true;
						}
					else
						{
						if($region != "full" && $region != "max")
							{
                            // Tiles have not yet been created
                            if(is_process_lock('create_previews_' . $resource["ref"] . "_tiles"))
                                {
                                $errors[] = "Requested image is not currently available"; 
                                iiif_error(503,$errors);
                                }
                            set_process_lock('create_previews_' . $resource["ref"] . "_tiles");
                            $imgfound = @create_previews($resourceid,false,"jpg",false,false,-1,true,false,false,array("tiles"));
                            clear_process_lock('create_previews_' . $resource["ref"] . "_tiles");
							}
                        else
                            {
                            if(is_process_lock('create_previews_' . $resource["ref"] . "_" . $getsize))
                                {
                                $errors[] = "Requested image is not currently available"; 
                                iiif_error(503,$errors);
                                }
                            $imgfound = @create_previews($resourceid,false,"jpg",false,false,-1,true,false,false,array($getsize));
                            clear_process_lock('create_previews_' . $resource["ref"] . "_" . $getsize);
                            }
						}
					if($imgfound)
						{
                        $validrequest = true;
						$response_image=$imgpath; 
						}
                    else
                        {
						$errorcode = "404";
						$errors[] = "No image available for this identifier";
                        }
					}
				}
            /* IMAGE REQUEST END */
            }
        else
            {
            //$errorcode=404;
            $errors[] = "Missing or invalid identifier";
            //$errors[]  = "Invalid region requested. Supported tiles are " . $preview_tile_size . "x" . $preview_tile_size . " at scale factors " . implode($preview_tile_scale_factors,",") . ".";
            iiif_error(404,$errors);
            }
        } // End of image API
	else
        {
		// Presentation API
		$identifier = $xpath[0];
        if($identifier != "" && !isset($xpath[1]))
            {
            // Redirect to image information document
            $redirurl = $_SERVER["REQUEST_URI"] . (!isset($xpath[2]) ? "/" : "") . "manifest";
            if(function_exists("http_response_code"))
                {
                http_response_code(303); # Send error status
                }
            header ("Location: " . $redirurl);
            exit();
            }

		$iiif_field = get_resource_type_field($iiif_identifier_field);
		$iiif_search = $iiif_field["name"] . ":" . $identifier;
		$iiif_results = do_search($iiif_search);
		
        if(is_array($iiif_results) && count($iiif_results)>0)
			{
            if(!isset($xpath[1]))
				{
				$errorcode=404;
				$errors[] = "Bad request. Valid options are 'manifest', 'sequence' or 'canvas' e.g. ";
				$errors[] = "For the manifest: " . $rooturl . $xpath[0] . "/manifest";
				$errors[] = "For a sequence : " . $rooturl . $xpath[0] . "/sequence";
				$errors[] = "For a canvas : " . $rooturl . $xpath[0] . "/canvas/<identifier>";
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
					// Add sequence position information
					$resultcount = count($iiif_results);
				    for ($n=0;$n<$resultcount;$n++)
						{
						if(isset($iiif_sequence_field))
							{
							if(isset($iiif_results[$n]["field" . $iiif_sequence_field]))
								{
								$position = $iiif_results[$n]["field" . $iiif_sequence_field];
								}
							else
								{
								$position = get_data_by_field($iiif_results[$n]["ref"],$iiif_sequence_field);
								}
							$position_field=get_resource_type_field($iiif_sequence_field);
							$position_prefix = $position_field["name"] . " ";
							}
						if(!isset($position) || trim($position) == "")
							{
							$position = $n;
							}
						debug("iiif position" . $position);
						$iiif_results[$n]["iiif_position"] = $position;
						}

					// Sort by position
					usort($iiif_results, function($a, $b)
						{
						return $a['iiif_position'] - $b['iiif_position'];
						});

					if($xpath[1] == "manifest" || $xpath[1] == "")
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

						// Label property
						foreach($iiif_results as $iiif_result)
							{
							// Keep on until we find a label
							$iiif_label = get_data_by_field($iiif_results[0]["ref"], $view_title_field);
							if(trim($iiif_label) != "")
								{
								$response["label"] = $iiif_label;
								break;
								}
							}

						if(!$iiif_label)
							{
							$response["label"] = $lang["notavailableshort"];
							}

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
								$nodecount = 0;
								unset($def_lang);
								foreach($resnodes as $resnode)
									{
									debug("iiif: translating " . $resnode["name"] . " from field '" . $iiif_data[$n]["title"] . "'");
									$node_langs = i18n_get_translations($resnode["name"]);
									$transcount=0;
									$defaulttrans = "";
									foreach($node_langs as $nlang => $nltext)
										{
										if(!isset($langentries[$nlang]))
											{
											// This is the first translated node entry for this language. If we already have translations copy the default language array to make sure no nodes with missing translations are lost
											debug("iiif: Adding a new translation entry for language '" . $nlang . "', field '" . $iiif_data[$n]["title"] . "'");
											$langentries[$nlang] = isset($def_lang)?$def_lang:array();
											}
										// Add the node text to the array for this language;
										debug("iiif: Adding node translation for language '" . $nlang . "', field '" . $iiif_data[$n]["title"] . "': " . $nltext);
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
											debug("iiif: No translation found for " . $mdlang . ". Adding default translation to language array for field '" . $iiif_data[$n]["title"] . "': " . $mdlang . ": " . $defaulttrans);
											$langentries[$mdlang][] =  $defaulttrans;
											}
										}

									// To ensure that no nodes are lost due to missing translations,  
									// Save the default language array to make sure we include any untranslated nodes that may be missing when/if we find new languages for the next node
								   
									debug("iiif: Saving default language array for field '" . $iiif_data[$n]["title"] . "': " . implode(",",$langentries[$defaultlanguage]));
									// Default language is the ideal, but if no default language entries for this node have been found copy the first language we have
									reset($langentries);
									$def_lang = isset($langentries[$defaultlanguage])?$langentries[$defaultlanguage]:$langentries[key($langentries)];
									}		

								$response["metadata"][$n]["value"] = array();
								$o=0;
								foreach($langentries as $mdlang => $mdtrans)
									{
									debug("iiif: adding to metadata language array: " . $mdlang . ": " . implode(",",$mdtrans));
									//$response["metadata"][$n]["value"][$o]["@value"] = array();
									//$response["metadata"][$n]["value"][$o]["@value"][] = $mdtrans;
									//$response["metadata"][$n]["value"][$o]["@language"] = $mdlang;
									$response["metadata"][$n]["value"][$o]["@value"] = implode(",",array_values($mdtrans));
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
						foreach($iiif_results as $iiif_result)
							{
							// Keep on until we find an image
							$iiif_thumb = iiif_get_thumbnail($iiif_results[0]["ref"]);
							if($iiif_thumb)
								{
								$response["thumbnail"] = $iiif_thumb;
								break;
								}
							}

						if(!$iiif_thumb)
							{
							$response["thumbnail"] = $baseurl . "/gfx/" . get_nopreview_icon($iiif_results[0]["resource_type"],"jpg",false);
							}
                            
						// Sequences
						$response["sequences"] = array();
						$response["sequences"][0]["@id"] = $rooturl . $identifier . "/sequence/normal";
						$response["sequences"][0]["@type"] = "sc:Sequence";
						$response["sequences"][0]["label"] = "Default order";
						   
												
						$response["sequences"][0]["canvases"]  = iiif_get_canvases($identifier,$iiif_results,false);
						$validrequest = true;	
						/* MANIFEST REQUEST END */
						}
					elseif($xpath[1] == "canvas")
						{                       
						// This is essentially a resource
						// {scheme}://{host}/{prefix}/{identifier}/canvas/{name}
						$canvasid = $xpath[2];
						$allcanvases = iiif_get_canvases($identifier,$iiif_results,true);
						$response["@context"] =  "http://iiif.io/api/presentation/2/context.json";
						$response = array_merge($response,$allcanvases[$canvasid]);
						$validrequest = true;
					}
					elseif($xpath[1] == "sequence")
						{
						if(isset($xpath[2]) && $xpath[2]=="normal")
							{
							$response["@context"] = "http://iiif.io/api/presentation/2/context.json";
							$response["@id"] = $rooturl . $identifier . "/sequence/normal";
							$response["@type"] = "sc:Sequence";
							$response["label"] = "Default order";
							$response["canvases"] = iiif_get_canvases($identifier,$iiif_results);
							$validrequest = true;
							}
						}
                    elseif($xpath[1] == "annotation")
						{
						// See http://iiif.io/api/presentation/2.1/#image-resources
						$annotationid = $xpath[2]; 
						
						// Need to find the resourceid the annotation is linked to
						foreach($iiif_results as $iiif_result)
							{
							if($iiif_result["iiif_position"] == $annotationid)
								{
								$resourceid = $iiif_result["ref"];
                                $size_info = array(
                                    'identifier' => (strtolower($iiif_result['file_extension']) != 'jpg') ? 'hpr' : '',
                                    'return_height_width' => false,
                                );
								$validrequest = true;
								break;
								}
							}
						if($validrequest)
							{
							$response["@context"] = "http://iiif.io/api/presentation/2/context.json";
							$response["@id"] = $rooturl . $identifier . "/annotation/" . $annotationid;
							$response["@type"] = "oa:Annotation";
							$response["motivation"] = "sc:painting";
                            $response["resource"] = iiif_get_image($identifier, $resourceid, $annotationid, $size_info);
                            $response["on"] = $rooturl . $identifier . "/canvas/" . $annotationid;
							}
						else
							{
							$errorcode=404;
							$errors[] = "Invalid annotation identifier: " . $identifier;
							}
						}
					}
				}
			} // End of valid $identifier check based on search results
		else
			{
			$errorcode=404;
			$errors[] = "Invalid identifier: " . $identifier;
			}
		}

    }
    // Send the data 
	if($validrequest)
		{
		if(function_exists("http_response_code"))
		    {
			http_response_code(200); # Send OK
			}
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
            header("Content-Type: application/ld+json");
            foreach($iiif_headers as $iiif_header)
                {
                header($iiif_header);
                }
            if(defined('JSON_PRETTY_PRINT'))
                {
                echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            else
                {
                echo json_encode($response);
                }
            }
		exit();
		}
		
	
