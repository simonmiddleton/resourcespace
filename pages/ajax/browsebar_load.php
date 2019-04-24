<?php
include '../../include/db.php';
include_once '../../include/general.php';
include '../../include/authenticate.php';
include_once "../../include/render_functions.php";
include_once "../../include/collections_functions.php";
include_once "../../include/search_functions.php";
include_once "../../include/resource_functions.php";

// generate JSON data to populate bar

$id = getvalescaped('id', '');

// Use id to work out search string for link and path to data requested e.g. to get field id for node expansion
$target_search = array();
$ftcolcats = array();
$parent_nodes = array();
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
            $target_search["archive"] =  "";
        break;
        
        case "F":
            $browse_field = $browseid;
        break;
        
        case "N":
            $parent_nodes[] = $browseid;
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
            $return_items[$n]["class"] = "New";
            $return_items[$n]["expandable"] = "false";
            $tgtparams = array();
            $tgtparams["type"]  = "resource_type";
            $tgturl = generateURL($baseurl_short . "pages/ajax/create_new.php", $tgtparams);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = true;
            $n++;
            }

        foreach($restypes as $restype)
            {

            if(!in_array($restype['ref'], $hide_resource_types)) 
                {
                // Create link based on parent and current restype
                $return_items[$n] = array();
                $return_items[$n]["id"] = $id . "-RT:" . $restype["ref"];
                $return_items[$n]["name"] = htmlspecialchars(i18n_get_translated($restype["name"]));
                $return_items[$n]["class"] = "Restype";
                $return_items[$n]["expandable"] = "true";            
                $tgtparams = array();
                $tgtparams["restypes"]  = $restype["ref"];
                $tgtparams["noreload"] = "true";
                $tgtparams["search"]  = "";
                $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams);
                $return_items[$n]["link"] = $tgturl;
                $return_items[$n]["modal"] = false;
                $n++;
                }
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
            $return_items[$n]["class"] = "New";
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
            if($field["browse_bar"] && metadata_field_view_access($field["ref"]) && $field["type"] != FIELD_TYPE_DYNAMIC_KEYWORDS_LIST)
                {
                // Create link based on parent and current restype
                $return_items[$n] = array();
                $return_items[$n]["id"] = $id . "-F:" . $field["ref"];
                $return_items[$n]["name"] = i18n_get_translated($field["title"]);
                $return_items[$n]["class"] = "Field";
                $return_items[$n]["expandable"] = "true";
                $return_items[$n]["link"] = "";
                $return_items[$n]["modal"] = false;

                $n++;
                }
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

        if(metadata_field_view_access($returnid))
            {
            $fielddata = get_resource_type_field($returnid);
            if(!$fielddata["browse_bar"] || !metadata_field_view_access($returnid) || !in_array($fielddata["type"],$FIXED_LIST_FIELD_TYPES) || $fielddata["type"] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST)
                {
                continue;
                }

            if(checkperm("k") || checkperm('a') || ($fielddata["type"] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && !checkperm ("bdk" . $returnid)))
                {
                // Add 'create new' option
                $return_items[$n] = array();
                $return_items[$n]["id"] = $id . "-N:new";
                $return_items[$n]["name"] = $lang["add"];
                $return_items[$n]["class"] = "New";
                $return_items[$n]["expandable"] = "false";
                $tgtparams = array();
                $tgtparams["type"]  = "node";
                $tgtparams["field"]  = $returnid;
                $tgtparams["parent"]  = $parent;
                $tgturl = generateURL($baseurl_short . "pages/ajax/create_new.php", $tgtparams);
                $return_items[$n]["link"] = $tgturl;
                $return_items[$n]["modal"] = true;
                $n++;
                }
            
            $nodes = get_nodes($returnid, $parent, false);
        
            if((bool) $fielddata['automatic_nodes_ordering'])
                {
                $nodes = reorder_nodes($nodes);
                }

            foreach($nodes as $node)
                {
                // Create link based on parent and current restype
                $return_items[$n] = array();
                $return_items[$n]["id"] = $id . "-N:" . $node["ref"];
                $return_items[$n]["name"] = htmlspecialchars(i18n_get_translated($node["name"]));
                $return_items[$n]["class"] = "Node";
                $return_items[$n]["expandable"] = (is_parent_node($node["ref"])) ? "true" : "false";
                
                $tgtparams = array();
                $tgtparams["search"]  = NODE_TOKEN_PREFIX . $node["ref"];
                $tgtparams["noreload"] = "true";
                $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams, $target_search);
                $return_items[$n]["link"] = $tgturl;
                $return_items[$n]["modal"] = false;
                $return_items[$n]["drop"] = true;
                $n++;
                }

            $return_data["success"] = TRUE;
            $return_data["items"] = $return_items;
            }
    break;
    
    case "N":
        // Get subnodes for node
        if(metadata_field_view_access($browse_field))
            {
            $fielddata = get_resource_type_field($browse_field);            
            if(!$fielddata["browse_bar"] || !metadata_field_view_access($browse_field) || !in_array($fielddata["type"],$FIXED_LIST_FIELD_TYPES) || $fielddata["type"] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST)
                {
                continue;
                }

            if(checkperm("k") || checkperm('a') || ($fielddata["type"] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && !checkperm ("bdk" . $returnid)))
                {
                // Add 'create new' option
                $return_items[$n] = array();
                $return_items[$n]["id"] = $id . "-N:new";
                $return_items[$n]["name"] = $lang["add"];
                $return_items[$n]["class"] = "New";
                $return_items[$n]["expandable"] = "false";
                $tgtparams = array();
                $tgtparams["type"]  = "node";
                $tgtparams["field"]  = $browse_field;
                $tgtparams["parent_nodes"]  = implode(",",$parent_nodes);
                $tgturl = generateURL($baseurl_short . "pages/ajax/create_new.php", $tgtparams);
                $return_items[$n]["link"] = $tgturl;
                $return_items[$n]["modal"] = true;
                $n++;
                }
        
            $nodes = get_nodes($browse_field, $returnid, false);
            
            if((bool) $fielddata['automatic_nodes_ordering'])
                {
                $nodes = reorder_nodes($nodes);
                }

            foreach($nodes as $node)
                {
                $return_items[$n] = array();
                $return_items[$n]["id"] = $id . "-N:" . $node["ref"];
                $return_items[$n]["name"] = htmlspecialchars(i18n_get_translated($node["name"]));
                $return_items[$n]["class"] = "Node";           
                $return_items[$n]["expandable"] = (is_parent_node($node["ref"])) ? "true" : "false";            
                $tgtparams = array();
                $tgtparams["search"]  = NODE_TOKEN_PREFIX . $node["ref"];
                $tgtparams["noreload"] = "true";
                $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams, $target_search);
                $return_items[$n]["link"] = $tgturl;
                $return_items[$n]["modal"] = false;
                $return_items[$n]["drop"] = true;
                $n++;
                }

            $return_data["success"] = TRUE;
            $return_data["items"] = $return_items;
            }
    break;
    
    case "FC":
        // Featured collection
        
        $ftcol_subcats = get_theme_headers($ftcolcats);
        $tgtparams = array();
        for ($x=0;$x<count($ftcolcats);$x++)
            {
            $fclevel = ($x==0) ? "" : $x+1;
            $tgtparams["theme" . $fclevel] = $ftcolcats[$x];
            }
            
         if($collection_allow_creation && checkperm("h"))
            {
            // Add 'create new' option
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-FC:new";
            $return_items[$n]["name"] = $lang["create"];
            $return_items[$n]["class"] = "New";
            $return_items[$n]["expandable"] = "false";
            $newtgtparams = $tgtparams;
            $newtgtparams["new"]  = "true";            
            
            $tgturl = generateURL($baseurl_short . "pages/themes.php", $newtgtparams);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = true;
            $n++;
            }
                
        foreach($ftcol_subcats as $subcat)
            {
            // Create link based on parent 
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-FC:" . base64_encode($subcat);
            $return_items[$n]["name"] = htmlspecialchars(i18n_get_translated($subcat));
            $return_items[$n]["class"] = "Featured";
            $return_items[$n]["expandable"] = "true";                            
            $tgturl = generateURL($baseurl_short . "pages/themes.php", $tgtparams, array("theme" . ($x+1) => $subcat));
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = false;
            $n++;
            }
        
        if(count($ftcolcats) > 0)
            {
            $fcols = get_themes($ftcolcats);
            foreach($fcols as $fcol)
                {
                // Create link based on parent 
                $return_items[$n] = array();
                $return_items[$n]["id"] = $id . "-C:" . $fcol["ref"];
                $return_items[$n]["name"] = htmlspecialchars(i18n_get_translated($fcol["name"]));
                $return_items[$n]["class"] = "Col";
                $return_items[$n]["expandable"] = "false";                
                $tgtparams = array();
                $tgtparams["search"] = "!collection" . $fcol["ref"];                            
                $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams);
                $return_items[$n]["link"] = $tgturl;
                $return_items[$n]["modal"] = false;
                $return_items[$n]["drop"] = true;
                $n++;
                }
            }        

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
    break;
    
    case "C":
        // My collections
        
        if($collection_allow_creation && !checkperm("b"))
            {
            // Add 'create new' option
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-C:new";
            $return_items[$n]["name"] = $lang["createnewcollection"];
            $return_items[$n]["class"] = "New";
            $return_items[$n]["expandable"] = "false";
            $tgtparams = array();
            $tgtparams["type"]  = "collection";
            $tgturl = generateURL($baseurl_short . "pages/ajax/create_new.php", $tgtparams);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = true;
            $n++;
            }
            
        $mycols = get_user_collections($userref);
        foreach($mycols as $mycol)
            {
            // Create link based on parent 
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-C:" . $mycol["ref"];
            $return_items[$n]["name"] = htmlspecialchars(i18n_get_collection_name($mycol["name"]));
            $return_items[$n]["class"] = "Col";
            $return_items[$n]["expandable"] = "false";
            
            $tgtparams = array();
            $tgtparams["search"] = "!collection" . $mycol["ref"];                            
            $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = false;
            $return_items[$n]["drop"] = true;
            $n++;
            }
        

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
    break;
    
    case "WF":
        // Workflow states
        $showstates = array();
        for ($s=-2;$s<=3;$s++)
            {
            if(!checkperm("z" . $s))
                {
                $showstates[] = $s;
                }
            }

        foreach ($additional_archive_states as $additional_archive_state)
            {
            if(!checkperm("z" . $additional_archive_state))
                {
                $showstates[] = $additional_archive_state;
                }
            }
            
        foreach($showstates as $showstate)
            {
            // Create link based on parent 
            $return_items[$n] = array();
            $return_items[$n]["id"] = $id . "-A:" . $showstate;
            $return_items[$n]["name"] = isset($lang["status" . $showstate]) ? $lang["status" . $showstate] : $showstate;
            $return_items[$n]["class"] = "State";
            $return_items[$n]["expandable"] = "false";
            
            $tgtparams = array();
            $tgtparams["search"] = "";  
            $tgtparams["restypes"] = "";  
            $tgtparams["archive"] = $showstate;                           
            $tgturl = generateURL($baseurl_short . "pages/search.php", $tgtparams);
            $return_items[$n]["link"] = $tgturl;
            $return_items[$n]["modal"] = false;
            $n++;
            }

        $return_data["success"] = TRUE;
        $return_data["items"] = $return_items;
    break;
    
    default:
        // TODO Return an error
        $return_data["success"] = FALSE;
        $return_data["message"] = "ERROR";
    break;
      
    }
    
    
echo json_encode($return_data);
exit();