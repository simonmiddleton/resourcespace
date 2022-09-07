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
$size=getval("size","letter");
$color=getval("color","yellow");
$previewpage=getval("previewpage",1,true);
$cleartmp=getval("cleartmp","");

if ($cleartmp!="")
    {
    echo getval("annotateid","");
    clear_annotate_temp($ref,getval("annotateid",""),$previewpage);
    exit("cleared");
    }

if(getval("preview","")!="")
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







