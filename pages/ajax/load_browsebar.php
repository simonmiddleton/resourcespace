<?php
include '../../include/db.php';
include_once '../../include/general.php';
include '../../include/authenticate.php';
include_once "../../include/render_functions.php";
include_once "../../include/collections_functions.php";
include_once "../../include/search_functions.php";
include_once "../../include/resource_functions.php";

enforcePostRequest("browse_load");

$type=getvalescaped('type', '');
$id=getvalescaped('id', '',true);
$newlevel=getvalescaped('level', 0,true) + 1;

$bb_html = "";
switch ($type)
    {
    case "tags":
        $restypes = get_resource_types();
        
        foreach($restypes as $restype)
            {
            //exit("level: " . $newlevel);
            $bb_html .= generate_browse_bar_item($newlevel, "restype", $restype["ref"], $restype["name"], "test", "test", $classes=array());
            }
        echo  $bb_html;
        exit();
    break;
    
    case "resource":
    break;
    }