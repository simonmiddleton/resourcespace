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

// Use id to work out search string for link and path to data requested e.g. to get field id for node expansion
$target_search = array();
$ftcolcats = array();
$browse_field = 0;

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
            $target_search["restypes"] =  $browseid;
        break;
        
        case "F":
            $browse_field = $browseid;
        break;
                   
        case "FC":
            if($browseid != "")
                {
                $ftcolcats[] =  base64_decode($browseid);
                }
        break;
        }
        
    if($n == $bcount-1)
        {
        // Last id - this decided what is requested
        $returntype =  $type;
        $returnid   = $browseid;
        }
    }

//exit('<pre>' . print_r($parentparts) . '</pre>');
//exit('<pre>' . print_r($ftcolcats) . '</pre>');


// Generate data to return
$return_items  = array();
$n=0;
switch ($returntype)
    {
    case "R":
        // Add resource types
        $restypes = get_resource_types();
         
        if(checkperm("a"))
            {
            // Add 'create new' option
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-RT:new";
            $return_items[$n]["name"] = $lang["resource_type_new"];
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa far fa-plus-circle";
            $return_items[$n]["expandable"] = "false";
            $tgtparams = array();
            $tgtparams["type"]  = "resource_type";
            $tgturl = generateURL($baseurl_short . "pages/ajax/create_new.php", $tgtparams);
            //$tgturl = generateURL($baseurl_short . "pages/admin/admin_resource_type_edit.php", $tgtparams);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = true;
            $n++;
            }

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
            //$return_items[$n]["expandaction"] = "fa far fa-folder";
            $return_items[$n]["expandable"] = "true";            
            $tgtparams = array();
            $tgtparams["restypes"]  = $restype["ref"];
            $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = false;
            $n++;
            }
       
        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
    break;
    
    case "RT":
        // Resource type - get all applicable fields
        
         if(checkperm("a"))
            {
            // Add 'create new' option
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-F:new";
            $return_items[$n]["name"] = $lang["resource_type_field_new"];
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa far fa-plus-circle";
            $return_items[$n]["expandable"] = "false";
            $tgtparams = array();
            $tgtparams["restypes"]  = "new";
            $tgtparams["type"]  = "resource_type_field";
            $tgturl = generateURL($baseurl_short . "pages/ajax/create_new.php", $tgtparams);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = true;
            $n++;
            }
            
        $gettypes = array("0",(int)$returnid);
        $allfields = get_resource_type_fields($gettypes,"order_by",'asc','',$FIXED_LIST_FIELD_TYPES);
        
        foreach($allfields as $field)
            {
            if(!$field["browse_bar"])
                {
                continue;
                }
            //exit("level: " . $newlevel);
            // Create link based on parent and current restype
            $return_items[$n] = array();
            //$return_items[$n]["type"] = "F";
            $return_items[$n]["id"] = $id . "-F:" . $field["ref"];
            //$return_items[$n]["parent"] = $parent;
            $return_items[$n]["name"] = i18n_get_translated($field["title"]);
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa far fa-folder";
            //$return_items[$n]["expandaction"] = "fa far fa-folder";
            $return_items[$n]["expandable"] = "true";
            $return_items[$n]["link"] = "";
            $return_items[$n]["modal"] = false;

            //$bb_html .= generate_browse_bar_item($newlevel, "restype", $restype["ref"], $restype["name"], $tgturl, "test", $classes=array());
            $n++;
            }
       
        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
    break;
    
    case "F":
        // Get nodes for field
        if(isset($parentnode) && $parentnode > 0)
            {
            $parent = $parentnode;
            }
        else
            {
            $parent = NULL;
            }
            
        $nodes = get_nodes($returnid, $parent, false);
        
        foreach($nodes as $node)
            {
            // Create link based on parent and current restype
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-N:" . $node["ref"];
            $return_items[$n]["name"] = i18n_get_translated($node["name"]);
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa far fa-tag";
            $return_items[$n]["expandable"] = (is_parent_node($node["ref"])) ? "true" : "false";
            
            $tgtparams = array();
            $tgtparams["search"]  = NODE_TOKEN_PREFIX . $node["ref"];
            $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams, $target_search);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = false;
            $n++;
            }
        //echo  $bb_html;

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
    break;
    
    case "N":
        // Get subnodes for node
        if($browse_field == 0)
            {
            // No field was found in browse_id
            exit("ERROR");
            }
        $nodes = get_nodes($browse_field, $returnid, false);
        
        foreach($nodes as $node)
            {
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-N:" . $node["ref"];
            $return_items[$n]["name"] = i18n_get_translated($node["name"]);
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa far fa-tag";
            //$return_items[$n]["expandaction"] = "fa far fa-tag";            
            $return_items[$n]["expandable"] = (is_parent_node($node["ref"])) ? "true" : "false";            
            $tgtparams = array();
            $tgtparams["search"]  = NODE_TOKEN_PREFIX . $node["ref"];
            $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams, $target_search);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = false;
            $n++;
            }
        //echo  $bb_html;

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
    break;
    
    case "FC":
        // Featured collection
        $ftcol_subcats = get_theme_headers($ftcolcats);
        //exit(print_r($fcol_cats));
        $tgtparams = array();
        for ($x=0;$x<count($ftcolcats);$x++)
            {
            $fclevel = ($x==0) ? "" : $x+1;
            $tgtparams["theme" . $fclevel] = $ftcolcats[$x];
            }
                
//exit('<pre>' . print_r($ftcolcats) . '</pre>');
        foreach($ftcol_subcats as $subcat)
            {
            //exit("level: " . $newlevel);
            // Create link based on parent 
            $return_items[$n] = array();
            //$fc_id = convert_uuencode(json_encode(array_merge($fcol_arr, $fcol)));
            //$fcoltgt = array_merge($ftcolcats,array($subcat));
            $return_items[$n]["id"] = $id . "-FC:" . base64_encode($subcat);
            //$return_items[$n]["parent"] = $parent;
            $return_items[$n]["name"] = i18n_get_translated($subcat);
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa far fa-folder";
            //$return_items[$n]["expandaction"] = "fa far fa-folder";
            $return_items[$n]["expandable"] = "true";
                            
            //$tgtparams["theme" . $fclevel] = $ftcolcats[$x];
            $tgturl = generateURL($baseurl_short . "pages/themes.php", $tgtparams, array("theme" . ($x+1) => $subcat));
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = false;

            //$bb_html .= generate_browse_bar_item($newlevel, "restype", $restype["ref"], $restype["name"], $tgturl, "test", $classes=array());
            $n++;
            }
        //echo  $bb_html;
        
        //print_r($ftcolcats);
        if(count($ftcolcats) > 0)
            {
            $fcols = get_themes($ftcolcats);
            foreach($fcols as $fcol)
                {
                //exit("level: " . $newlevel);
                // Create link based on parent 
                $return_items[$n] = array();
                $return_items[$n]["id"] = $id . "-  C:" . $fcol["ref"];
                //$return_items[$n]["parent"] = $parent;
                $return_items[$n]["name"] = i18n_get_translated($fcol["name"]);
                $return_items[$n]["level"] = $newlevel;
                $return_items[$n]["class"] = "fa fa-th-large";
                //$return_items[$n]["expandaction"] = "fa fa-th-large";
                $return_items[$n]["expandable"] = "false";
                
                $tgtparams = array();
                $tgtparams["search"] = "!collection" . $fcol["ref"];                            
                $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams);
                $return_items[$n]["link"] = $tgturl;
                $return_items[$n]["modal"] = false;

                //$bb_html .= generate_browse_bar_item($newlevel, "restype", $restype["ref"], $restype["name"], $tgturl, "test", $classes=array());
                $n++;
                }
            }
        

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
    break;
    
    case "C":
        // My collections
        $mycols = get_user_collections($userref);
        //exit(print_r($fcol_cats));
        $tgtparams = array();
        foreach($mycols as $mycol)
            {
            //exit("level: " . $newlevel);
            // Create link based on parent 
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-C:" . $mycol["ref"];
            //$return_items[$n]["parent"] = $parent;
            $return_items[$n]["name"] = i18n_get_translated($mycol["name"]);
            $return_items[$n]["level"] = $newlevel;
            $return_items[$n]["class"] = "fa fa-th-large";
            //$return_items[$n]["expandaction"] = "fa fa-th-large";
            $return_items[$n]["expandable"] = "false";
            
            $tgtparams = array();
            $tgtparams["search"] = "!collection" . $mycol["ref"];                            
            $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = false;

            //$bb_html .= generate_browse_bar_item($newlevel, "restype", $restype["ref"], $restype["name"], $tgturl, "test", $classes=array());
            $n++;
            }
        

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
        // TODO Add dew collection
    break;
    
    default:
        // TODO Return an error
        $return_data["success"] = FALSE;
        $return_data["message"] = "ERROR";
    break;
      
    }
    
    
echo json_encode($return_data);
exit();