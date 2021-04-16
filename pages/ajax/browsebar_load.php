<?php
include '../../include/db.php';
include '../../include/authenticate.php';

// generate JSON data to populate bar

$id = getvalescaped('id', '');

// Use id to work out search string for link and path to data requested e.g. to get field id for node expansion
$target_search = array();
$fc_parent = 0;
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
                $fc_parent = $browseid;
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
        // Resource types can be configured to not have global fields in which case we only present the user fields valid for
        // this resource type
        $inherit_global_fields = (bool) sql_value("SELECT inherit_global_fields AS `value` FROM resource_type WHERE ref = '{$returnid}'", true, "schema");
        $gettypes = ($inherit_global_fields == true) ? array(0) : array(); // determine whether to display global fields
        $gettypes[] = (int)$returnid; // add selected resource type fields
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
                break;
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
                break;
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
    
    // Featured collection
    case "FC":
        $fc_parent = validate_collection_parent(array("parent" => $fc_parent));

        // Add 'create new' option
        if($collection_allow_creation && checkperm("h"))
            {
            $item = array(
                "id" => "{$id}-FC:new",
                "name" => htmlspecialchars($lang["create"]),
                "class" => "New",
                "expandable" => "false",
                "link" => generateURL(
                    "{$baseurl_short}pages/collections_featured.php",
                    array(
                        "new" => "true",
                        "cta" => "true",
                        "parent" => $fc_parent,
                    )
                ),
                "modal" => true,
            );

            $return_items[$n] = $item;
            $n++;
            }

        $featured_collections = get_featured_collections($fc_parent, array());
        usort($featured_collections, "order_featured_collections");
        foreach($featured_collections as $fc)
            {
            $is_featured_collection_category = is_featured_collection_category($fc);
            $id_part = ($is_featured_collection_category ? "FC" : "C");
            $link = generateURL("{$baseurl_short}pages/search.php", array("search" => "!collection{$fc["ref"]}"));
            if($is_featured_collection_category)
                {
                $link = generateURL("{$baseurl_short}pages/collections_featured.php", array("parent" => $fc["ref"]));
                }

            $item = array(
                "id" => "{$id}-{$id_part}:{$fc["ref"]}",
                "name" => htmlspecialchars(strip_prefix_chars(i18n_get_translated($fc["name"]),"*")),
                "class" => ($is_featured_collection_category ? "Featured" : "Col"),
                "expandable" => ($is_featured_collection_category ? "true" : "false"), # lib/js/browsebar_js.php requires this to be a string.
                "link" => $link,
                "modal" => false,
                "drop" => !$is_featured_collection_category,
            );

            $return_items[$n] = $item;
            $n++;
            }

        $return_data["success"] = true;
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
            $return_items[$n]["name"] = strip_tags_and_attributes(i18n_get_collection_name($mycol["name"]));
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
            
            // Set an icon 
            switch($showstate)
                {
                case -2: $icon="file-import"; break;
                case -1: $icon="eye"; break;
                case 0: $icon="check"; break;
                case 1: $icon="clock"; break;
                case 2: $icon="archive"; break;
                case 3: $icon="trash"; break;
                default: $icon="cogs"; # All additional workflow states show gears icon to indicate workflow
                }
            $return_items[$n]["icon"] = "<i class='fa fa-fw fa-" . $icon  . "'></i>";
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