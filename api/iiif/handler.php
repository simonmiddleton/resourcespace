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
$rooturl = $baseurl . "/api/iiif/";
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
		$response["@id"] = $rooturl . $identifier . "/manifest";
		$response["@type"] = "sc:Manifest";
		
		
		// Descriptive metadata about the object/work
		// The manifest data should be the same for all resources that are returned.
		// This is the default when using the tms_link plugin for TMS integration. 
		// Therefore we use the data from the first returned result.
		$iiif_data=get_resource_field_data($iiif_results[0]["ref"]);
		//$iiif_data_rows=count($iiif_data);
		
		//$response["label"] = $iiif_data["field" . $view_title_field];
		$response["label"] = get_data_by_field($iiif_results[0]["ref"], $view_title_field);
		
		$response["metadata"] = array();
		$n=0;
		//print_r($iiif_data);
		foreach($iiif_data as $iiif_data_row)
			{
			$response["metadata"][$n]=array();		
			$response["metadata"][$n]["label"]=$iiif_data[$n]["title"];
			if(in_array($iiif_data[$n]["type"],$FIXED_LIST_FIELD_TYPES))
				{
				// Don't use the data as this has already concatentated the translations, add an entry for each node translation by building up a new array
				$resnodes=get_resource_nodes($iiif_results[0]["ref"],$iiif_data[$n]["resource_type_field"],true);
				//$langidx=0;
				//print_r($resnodes);
				$langentries=array();
				foreach($resnodes as $resnode)
					{
					$node_langs=i18n_get_translations($resnode["name"]);
					$transcount=0;
					$defaulttrans="";
					foreach($node_langs as $nlang=>$nltext)
						{
						if(!isset($langentries[$nlang]))
							{
							// This is the first translated node entry for this language. If we already have translations copy the default language array to make sure no nodes with missing translations are lost
							echo "adding a new lang entry for " . $nlang  . "\n";
							$langentries[$nlang]=isset($def_lang)?$def_lang:array();
							echo "NEw array is :  " . implode(",",$langentries[$nlang])  . "\n";
							}
						// Add the node text to the array for this language
						echo "adding text  entry for " . $nltext . " to " . $nlang  . "\n";
							$langentries[$nlang][]=$nltext;
						
						// Set default text for any translations
						if($nlang==$defaultlanguage || $defaulttrans==""){$defaulttrans=$nltext;}
						
						$transcount++;						
						}

					// There may not be translations for all nodes, fill the arrays with the untranslated versions
					foreach($langentries as $mdlang=>$mdtrans)
						{
						if(count($mdtrans)!=$transcount)
							{
							$langentries[$mdlang][]=  $defaulttrans;
							//if(!isset($node_langs[$mdlang])){$langentries[$mdlang][]=$node_langs[$defaultlanguage];}
							}
						}	
					//$response["metadata"][$n]["value"]["@value"]=$mdtrans;
					//$response["metadata"][$n]["value"]["@language"]=$mdlang;
					//$langidx++;
					
					// To ensure that no nodes are lost due to missing translations,  
					// Save the default language array to make sure we include any untranslated nodes that may be missing when/if we find new languages for the next node
					if(!isset($def_lang))
						{
						// Default language is the ideal, but if no default language entries for this node have been found copy the first language we have
						reset($langentries);
						$def_lang = isset($langentries[$defaultlanguage])?$langentries[$defaultlanguage]:$langentries[key($langentries)];
						}
					}
				
								
				
				$response["metadata"][$n]["value"]=array();
				//$iiif_langs=i18n_get_translations($iiif_data[$n]["value"]);
				//print_r($langentries);
				$o=0;
				foreach($langentries as $mdlang=>$mdtrans)
					{
					$response["metadata"][$n]["value"][$o]["@value"]=array();
					$response["metadata"][$n]["value"][$o]["@value"][]=$mdtrans;
					$response["metadata"][$n]["value"][$o]["@language"]=$mdlang;
					$o++;
					}
				}
			else
				{
				$response["metadata"][$n]["value"]=$iiif_data[$n]["value"];
				}
			$n++;
			}
		print_r($response);
		
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
	
