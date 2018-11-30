<?php
include '../../include/db.php';
include_once '../../include/general.php';
include '../../include/authenticate.php';
include_once "../../include/render_functions.php";
include_once "../../include/collections_functions.php";
include_once "../../include/search_functions.php";
include_once "../../include/resource_functions.php";

// generate JSON data to populate bar
// type
// parent
// Text
// link
// Expand link?

$id = getvalescaped('id', '');
$newlevel=getvalescaped('level', 0,true) + 1;
//$parent=getvalescaped('parent','');

// Use id to work out search string for link and data requested
$target_search = array();
$browse_elements = explode("-", $id);
$bcount = count($browse_elements);
$n=0;
for($n=0;$n<$bcount;$n++)
    {
    $browseparts =explode(":", $browse_elements[$n]);
    $type = $browseparts[0];
    $browseid = isset($browseparts[1]) ? $browseparts[1] : 0;
    switch ($type)
        {
        case "RT":
            {
            $target_search["restypes"] =  $browseid;
            }
        }
        
    if($n == $bcount-1)
        {
        // Last id - this decided what is requested
        $returntype =  $type;
        $returnid   = $browseid;
        }
    }

//exit('<pre>' . print_r($parentparts) . '</pre>');


// Generate data to return
$return_items  = array();
$n=0;
switch ($returntype)
    {
    case "R":
        $restypes = get_resource_types();
        
        foreach($restypes as $restype)
            {
            //exit("level: " . $newlevel);
            // Create link based on parent and current restype
            $return_items[$n] = array();
            //$return_items[$n]["type"] = "RT";
            $return_items[$n]["id"] = $id . "-RT:" . $restype["ref"];
            //$return_items[$n]["parent"] = $parent;
            $return_items[$n]["name"] = i18n_get_translated($restype["name"]);
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa far fa-folder";
            $return_items[$n]["expandaction"] = "fa far fa-folder";
            $return_items[$n]["expandable"] = "true";
            
            $tgtparams = array();
            $tgtparams["restypes"]  = $restype["ref"];
            $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams);
            $return_items[$n]["link"] = $tgturl;

            //$bb_html .= generate_browse_bar_item($newlevel, "restype", $restype["ref"], $restype["name"], $tgturl, "test", $classes=array());
            $n++;
            }
        //echo  $bb_html;

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
        echo json_encode($return_data);
        exit();
    break;
    
    case "RT":
        $gettypes = array("0",(int)$returnid);
        $allfields = get_resource_type_fields($gettypes,"order_by",'asc','',$FIXED_LIST_FIELD_TYPES);
        
        foreach($allfields as $field)
            {
            //exit("level: " . $newlevel);
            // Create link based on parent and current restype
            $return_items[$n] = array();
            //$return_items[$n]["type"] = "F";
            $return_items[$n]["id"] = $id . "-F:" . $field["ref"];
            //$return_items[$n]["parent"] = $parent;
            $return_items[$n]["name"] = i18n_get_translated($field["title"]);
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa far fa-folder";
            $return_items[$n]["expandaction"] = "fa far fa-folder";
            $return_items[$n]["expandable"] = "true";
            $return_items[$n]["link"] = "";

            //$bb_html .= generate_browse_bar_item($newlevel, "restype", $restype["ref"], $restype["name"], $tgturl, "test", $classes=array());
            $n++;
            }
        //echo  $bb_html;

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
        echo json_encode($return_data);
        exit();
    break;
    
    case "F":
        $allnodes = get_nodes($returnid, null, true);
        
        foreach($allnodes as $node)
            {
            //exit("level: " . $newlevel);
            // Create link based on parent and current restype
            $return_items[$n] = array();
            //$return_items[$n]["type"] = "N";
            $return_items[$n]["id"] = $id . "-N:" .$node["ref"];
            //$return_items[$n]["parent"] = $parent;
            $return_items[$n]["name"] = i18n_get_translated($node["name"]);
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa far fa-tag";
            $return_items[$n]["expandaction"] = "fa far fa-tag";
            $return_items[$n]["expandable"] = "false";
            
            $tgtparams = array();
            $tgtparams["search"]  = NODE_TOKEN_PREFIX . $node["ref"];
            $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams, $target_search);
            $return_items[$n]["link"] = $tgturl;

            //$bb_html .= generate_browse_bar_item($newlevel, "restype", $restype["ref"], $restype["name"], $tgturl, "test", $classes=array());
            $n++;
            }
        //echo  $bb_html;

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
        echo json_encode($return_data);
        exit();
    break;
    }