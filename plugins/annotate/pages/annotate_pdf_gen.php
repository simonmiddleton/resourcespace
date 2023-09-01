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

$ref=getval("ref",0);
$size=getval("size","letter");
$color=getval("color","yellow");
$previewpage=getval("previewpage",1,true);
$cleartmp=getval("cleartmp","");

if ($cleartmp!="")
    {
    echo htmlspecialchars(getval("annotateid",""));
    clear_annotate_temp($ref,getval("annotateid",""));
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

$is_collection=false;
if(substr($ref,0,1)=="C")
    {
    $ref=substr($ref,1); 
    $is_collection=true;
    }
$result=create_annotated_pdf((int)$ref,$is_collection,$size,true,$preview);





