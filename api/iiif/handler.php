<?php
include "../../include/db.php";

if(!$iiif_enabled || !isset($iiif_identifier_field) || !is_numeric($iiif_identifier_field) || !isset($iiif_userid) || !is_numeric($iiif_userid)){exit($lang["iiif_disabled"]);}
include_once "../../include/general.php";
include_once "../../include/resource_functions.php";
include_once "../../include/search_functions.php";


$debug=getval("debug","")!="";
if($debug)
	{
	include "../../include/header.php";
	}

$iiif_user=get_user($iiif_userid);
setup_user($iiif_user);

$rootlevel = $baseurl_short . "api/iiif/";
$path=substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],$rootlevel) + strlen($rootlevel));
$xpath=explode("/",$path);

if (count($xpath)==1)
	{
	# Root level request - send information file only		   	
	$response["@context"] = "http://iiif.io/api/image/2/context.json";
  	$response["@id"] = $rootlevel;
	$response["protocol"] = "http://iiif.io/api/image";
	$response["width"] = 6000;
	$response["height"] = 4000;
			  
	$response["sizes"] = array();
	$response["sizes"][]=array("width" => 150, "height" => 100);
	$response["sizes"][]=array("width" => 600, "height" => 400);
	$response["sizes"][]=array("width" => 3000, "height" => 2000);
  	$response["tiles"]= array("width" => 512, "scaleFactors" => array(1,2,4,8,16));
	$response["profile"] = array("http://iiif.io/api/image/2/level2.json");
		
	echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);	
	exit();
	}
else
	{
	$identifier =$xpath[0];
	if(!is_numeric($identifier)){exit("Invalid identifier: " . $identifier);}
	
	if($debug)
		{
		echo "Identifier: " . $identfier;
		}
	else
		{	
		http_response_code(200); # Send OK
		header("Content-type: application/json");
		}
		
	$iiif_field=get_resource_type_field($iiif_identifier_field);
	$iiif_search=$iiif_field["name"] . ":" . $identifier;
	$iiif_results=do_search($iiif_search);
	
	if(!is_array($iiif_results) || count($iiif_results)==0){exit("Invalid identifier: " . $identifier);}
	
	if($xpath[1]=="manifest")
		{
		$response["@context"] = "http://iiif.io/api/presentation/2/context.json";
		$response["@id"] = $rootlevel . "/" . $identifier . "/manifest";
		$response["@type"] = "sc:Manifest";
		
		
		// Descriptive metadata about the object/work
		// The manifest data should be the same for all resources that are returned.
		// This is the default when using the tms_link plugin for TMS integration. 
		// Therefore we use the data from the first returned result.
		
		//$response["label"] = $iiif_results[""];
		/*
		"label": "Book 1",
		  "metadata": [
			{"label": "Author", "value": "Anne Author"},
			{"label": "Published", "value": [
				{"@value": "Paris, circa 1400", "@language": "en"},
				{"@value": "Paris, environ 1400", "@language": "fr"}
			  ]
			},
			{"label": "Notes", "value": ["Text of note 1", "Text of note 2"]},
			{"label": "Source",
			 "value": "<span>From: <a href=\"http://example.org/db/1.html\">Some Collection</a></span>"}
		  ],
		  "description": "A longer description of this example book. It should give some real information.",
		  "thumbnail": {
			"@id": "http://example.org/images/book1-page1/full/80,100/0/default.jpg",
			"service": {
			  "@context": "http://iiif.io/api/image/2/context.json",
			  "@id": "http://example.org/images/book1-page1",
			  "profile": "http://iiif.io/api/image/2/level1.json"
			}
		  },
		*/  
			
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);		
		}
	else
		{
		exit("Not supported");
		}
	
	
	exit();
	}
	
