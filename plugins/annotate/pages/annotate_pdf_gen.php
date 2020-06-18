<?php

// this program creates a new PDF document with annotations
include('../../../include/db.php');
include_once "../include/annotate_functions.php";
include('../../../include/authenticate.php');

global $plugins;
if (!in_array("annotate",$plugins))
    {
    header("Status: 403 plugin not activated");
    exit($lang["error-plugin-not-activated"]);
    }

$ref=getval("ref",0,true);
$size=getvalescaped("size","letter");
$color=getvalescaped("color","yellow");
$previewpage=getval("previewpage",1,true);
$cleartmp=getvalescaped("cleartmp","");

if ($cleartmp!="")
    {
    echo getvalescaped("annotateid","");
    clear_annotate_temp($ref,getvalescaped("annotateid",""),$previewpage);
    exit("cleared");
    }

if(getvalescaped("preview","")!="")
    {
    $preview=true;
    }
else
    {
    $preview=false;
    }

if (substr($ref,0,1)=="C")
    {
	$is_collection=true;
	$ref=substr($ref,1); 
	$result=create_annotated_pdf($ref,true,$size,true,$preview);
    } 
else
    { 
	$is_collection=false;
	$result=create_annotated_pdf($ref,false,$size,true,$preview);
    }







