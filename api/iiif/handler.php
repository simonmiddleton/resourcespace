<?php
$suppress_headers = true;
include "../../include/db.php";
include_once "../../include/image_processing.php";

if(!$iiif_enabled || !isset($iiif_identifier_field) || !is_numeric($iiif_identifier_field) || !isset($iiif_userid) || !is_numeric($iiif_userid) || !isset($iiif_description_field))
    {
    exit($lang["iiif_disabled"]);
    }

include_once "../../include/api_functions.php";
$iiif_debug = getval("debug","")!="";

$iiif_user = get_user($iiif_userid);
if($iiif_user === false)
    {
    iiif_error(500, ['Invalid $iiif_userid.']);
    }
// Creating $userdata for use in do_search()
$userdata[0] = $iiif_user;
setup_user($iiif_user);

// Set up request object
$iiif = new stdClass();
$iiif->rootlevel = $baseurl_short . "iiif/";
$iiif->rooturl = $baseurl . "/iiif/";
$iiif->rootimageurl = $baseurl . "/iiif/image/";
$iiif->identifier_field = $iiif_identifier_field;
$iiif->description_field = $iiif_description_field;
$iiif->sequence_field = $iiif_sequence_field ?? 0;
$iiif->license_field = $iiif_license_field ?? 0;
$iiif->title_field = $view_title_field;

// Extract request details
iiif_parse_url($iiif);

$getext="";

// print_r($request);
// exit();


$iiif->response=[];
$iiif->validrequest = false;
$iiif_headers = [];
$iiif->errors=[];
if ($iiif->request["api"] == "root")
	{
	# Root level request - send information file only
	$iiif->response["@context"] = "http://iiif.io/api/presentation/2/context.json";
  	$iiif->response["@id"] = $iiif->rooturl;
  	$iiif->response["@type"] = "sc:Manifest";
  	$iiif->response["@label"] = "";
	$iiif->response["width"] = 6000;
	$iiif->response["height"] = 4000;

    $iiif->response["tiles"] = array();
    $iiif->response["tiles"][] = array("width" => $preview_tile_size, "height" => $preview_tile_size, "scaleFactors" => $preview_tile_scale_factors);
	$iiif->response["profile"] = array("http://iiif.io/api/image/2/level0.json");

	$iiif->validrequest = true;
    }
elseif($iiif->request["api"] == "image")
    {
    // IMAGE REQUEST (http://iiif.io/api/image/2.1/)
    // The IIIF Image API URI for requesting an image must conform to the following URI Template:
    // {scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
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
        $imageWidth = (int) $image_size[1];
        $imageHeight = (int) $image_size[2];
        $portrait = ($imageHeight >= $imageWidth) ? TRUE : FALSE;

        // Get all available sizes
        $sizes = get_image_sizes($iiif->request["id"],true,"jpg",false);
        $availsizes = array();
        if ($imageWidth > 0 && $imageHeight > 0)
            {
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
            }

        if($iiif->request["region"] == "info.json")
            {
            // Image information request. Only fullsize available in this initial version
            $iiif->response["@context"] = "http://iiif.io/api/image/2/context.json";
            $iiif->response["@id"] = $iiif->rootimageurl . $iiif->request["id"];
                            
            $iiif->response["height"] = $imageHeight;
            $iiif->response["width"]  = $imageWidth;
            
            $iiif->response["profile"] = array();
            $iiif->response["profile"][] = "http://iiif.io/api/image/2/level0.json";
            if($iiif_custom_sizes)
                {
                $iiif->response["profile"][] = array(
                    "formats" => array("jpg"),
                    "qualities" => array("default"),
                    "maxWidth" => $iiif_max_width,
                    "maxHeight" => $iiif_max_height,
                    "supports" => array("sizeByH","sizeByW")
                    );
                }
            else
                {
                $iiif->response["profile"][] = array(
                    "formats" => array("jpg"),
                    "qualities" => array("default"),
                    "maxWidth" => $iiif_max_width,
                    "maxHeight" => $iiif_max_height
                    );
                }

            $iiif->response["protocol"] = "http://iiif.io/api/image";
            $iiif->response["sizes"] = $availsizes;
            if($preview_tiles)
                {
                $iiif->response["tiles"] = array();
                $iiif->response["tiles"][] = array("height" => $preview_tile_size, "width" => $preview_tile_size, "scaleFactors" => $preview_tile_scale_factors);
                }
            $iiif_headers[] = 'Link: <http://iiif.io/api/image/2/level0.json>;rel="profile"';
            $iiif->validrequest = true;
            }
        else
            {
            // Process requested region
            if(!isset($iiif->errorcode) && $iiif->request["region"] != "full" && $iiif->request["region"] != "max" && $preview_tiles)
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
                    $regionx = (int)$region_filtered[0];
                    $regiony = (int)$region_filtered[1];
                    $regionw = (int)$region_filtered[2];
                    $regionh = (int)$region_filtered[3];
                    debug("IIIF region requested: x:" . $regionx . ", y:" . $regiony . ", w:" .  $regionw . ", h:" . $regionh);
                    if(fmod($regionx,$preview_tile_size) != 0 || fmod($regiony,$preview_tile_size) != 0)
                        {
                        // Invalid region
                        $iiif->errors[]  = "Invalid region requested. Supported tiles are " . $preview_tile_size . "x" . $preview_tile_size . " at scale factors " . implode(",",$preview_tile_scale_factors) . ".";
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
                $getwidth   = (int)$getdims[0];
                $getheight  = (int)$getdims[1];
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
                            $iiif->errors[] = "Invalid tile size requested";
                            iiif_error(501,$iiif->errors);
                            }
                                                    
                        if(!in_array($scale,$preview_tile_scale_factors))
                            {
                            $iiif->errors[] = "Invalid tile size requested";
                            iiif_error(501,$iiif->errors); 
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
                        $iiif->errors[] = "Invalid tile size requested";
                        iiif_error(400,$iiif->errors);                             
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
                            $iiif->errors[] = "Invalid size requested";
                            iiif_error(400,$iiif->errors);                   
                            }
                        else
                            {
                            $getsize = "resized_" . $getwidth . "_". $getheight;
                            $getext = "jpg";
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
                        $getsize = "tile_" . $regionx . "_" . $regiony . "_". $regionw . "_". $regionh;
                        $getext = "jpg";
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
                $imgpath = get_resource_path($iiif->request["id"],true,$getsize,false,$getext);
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
                        if(is_process_lock('create_previews_' . $resource["ref"] . "_tiles"))
                            {
                            $iiif->errors[] = "Requested image is not currently available"; 
                            iiif_error(503,$iiif->errors);
                            }
                        set_process_lock('create_previews_' . $resource["ref"] . "_tiles");
                        $imgfound = @create_previews($iiif->request["id"],false,"jpg",false,true,-1,true,false,false,array("tiles"));
                        clear_process_lock('create_previews_' . $resource["ref"] . "_tiles");
                        }
                    else
                        {
                        if(is_process_lock('create_previews_' . $resource["ref"] . "_" . $getsize))
                            {
                            $iiif->errors[] = "Requested image is not currently available"; 
                            iiif_error(503,$iiif->errors);
                            }
                        $imgfound = @create_previews($iiif->request["id"],false,"jpg",false,true,-1,true,false,false,array($getsize));
                        clear_process_lock('create_previews_' . $resource["ref"] . "_" . $getsize);
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
    } // End of image API
elseif($iiif->request["api"] == "presentation")
    {
    // Presentation API
    iiif_generate_manifest($iiif);
    }

// Send the data 
if($iiif->validrequest)
    {
    if(function_exists("http_response_code"))
        {
        http_response_code(200); # Send OK
        }
    header("Access-Control-Allow-Origin: *");
    if(isset($iiif->response_image) && file_exists($iiif->response_image))
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
            echo json_encode($iiif->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        else
            {
            echo json_encode($iiif->response);
            }
        }
    }
elseif(count($iiif->errors) > 0)
    {
    iiif_error($iiif->errorcode ?? 400,$iiif->errors);
    }

		
	
