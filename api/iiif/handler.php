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
$iiif->identifier_field = (int)$iiif_identifier_field;
$iiif->description_field = (int)$iiif_description_field;
$iiif->sequence_field = (int)$iiif_sequence_field ?? 0;
$iiif->license_field = (int)$iiif_license_field ?? 0;
$iiif->title_field = $view_title_field;
$iiif->max_width = (int)$iiif_max_width ?? 1024;
$iiif->max_height = (int)$iiif_max_height ?? 1024;
$iiif->custom_sizes = (bool)$iiif_custom_sizes ?? true;
$iiif->preview_tiles = (bool)$preview_tiles ?? true;
$iiif->preview_tile_size = (int)$preview_tile_size ?? 1024;
$iiif->preview_tile_scale_factors = $preview_tile_scale_factors ?? [1,2,4];
$iiif->download_chunk_size = $download_chunk_size;

$iiif->response=[];
$iiif->validrequest = false;
$iiif->headers = [];
$iiif->errors=[];

// Extract request details
iiif_parse_url($iiif, ($_SERVER["REQUEST_URI"] ?? ""));

if ($iiif->request["api"] == "root")
	{
	# Root level request - send information file only
	$iiif->response["@context"] = "http://iiif.io/api/presentation/2/context.json";
  	$iiif->response["id"] = $iiif->rooturl;
  	$iiif->response["type"] = "sc:Manifest";
    $arr_langdefault = i18n_get_all_translations("iiif");
    foreach($arr_langdefault as $langcode=>$langdefault)
        {
        $iiif->response["label"][$langcode] = [$langdefault];
        }
	$iiif->response["width"] = 6000;
	$iiif->response["height"] = 4000;
    $iiif->response["tiles"] = array();
    $iiif->response["tiles"][] = array("width" => $preview_tile_size, "height" => $preview_tile_size, "scaleFactors" => $preview_tile_scale_factors);
    $iiif->response["profile"] = array("http://iiif.io/api/image/3/level0.json");
    $iiif->validrequest = true;
    }
elseif($iiif->request["api"] == "image")
    {
    iiif_process_image_request($iiif);
    } 
elseif($iiif->request["api"] == "presentation")
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
        iiif_process_presentation_request($iiif);
        }
    }

// Send the response 
if($iiif->validrequest)
    {
    if(function_exists("http_response_code"))
        {
        http_response_code(200); # Send OK
        }
    header("Access-Control-Allow-Origin: *");
    if(isset($iiif->response_image) && file_exists($iiif->response_image))
        {
        iiif_render_image($iiif);
        }
    else
        {
        header('Content-Type: application/ld+json;profile="http://iiif.io/api/image/3/context.json"');
        foreach($iiif->headers as $iiif_header)
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

		
	
