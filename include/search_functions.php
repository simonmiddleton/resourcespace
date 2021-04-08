<?php
# Search functions
# Functions to perform searches (read only)
# - For resource indexing / keyword creation, see resource_functions.php

function resolve_soundex($keyword)
    {
    # returns the most commonly used keyword that sounds like $keyword, or failing a soundex match,
    # the most commonly used keyword that starts with the same few letters.

    global $soundex_suggest_limit;
    $soundex=sql_value("SELECT keyword value FROM keyword WHERE soundex='". escape_check(soundex($keyword))."' AND keyword NOT LIKE '% %' AND hit_count>'" . $soundex_suggest_limit . "' ORDER BY hit_count DESC LIMIT 1",false);
    if (($soundex===false) && (strlen($keyword)>=4))
        {
        # No soundex match, suggest words that start with the same first few letters.
        return sql_value("SELECT keyword value FROM keyword WHERE keyword LIKE '" . escape_check(substr($keyword,0,4)) . "%' AND keyword NOT LIKE '% %' ORDER BY hit_count DESC LIMIT 1",false);
        }
    return $soundex;
    }
    
function suggest_refinement($refs,$search)
    {
    # Given an array of resource references ($refs) and the original
    # search query ($search), produce a list of suggested search refinements to 
    # reduce the result set intelligently.
    $in=join(",",$refs);
    $suggest=array();
    # find common keywords
    $refine=sql_query("SELECT k.keyword,count(*) c FROM resource_keyword r join keyword k on r.keyword=k.ref AND r.resource IN ($in) AND length(k.keyword)>=3 AND length(k.keyword)<=15 AND k.keyword NOT LIKE '%0%' AND k.keyword NOT LIKE '%1%' AND k.keyword NOT LIKE '%2%' AND k.keyword NOT LIKE '%3%' AND k.keyword NOT LIKE '%4%' AND k.keyword NOT LIKE '%5%' AND k.keyword NOT LIKE '%6%' AND k.keyword NOT LIKE '%7%' AND k.keyword NOT LIKE '%8%' AND k.keyword NOT LIKE '%9%' GROUP BY k.keyword ORDER BY c DESC LIMIT 5");
    for ($n=0;$n<count($refine);$n++)
        {
        if (strpos($search,$refine[$n]["keyword"])===false)
            {
            $suggest[]=$search . " " . $refine[$n]["keyword"];
            }
        }
    return $suggest;
    }

function get_advanced_search_fields($archive=false, $hiddenfields="")
    {
    global $FIXED_LIST_FIELD_TYPES, $date_field, $daterange_search;
    # Returns a list of fields suitable for advanced searching. 
    $return=array();

    $date_field_already_present=false; # Date field not present in searchable fields array
    $date_field_data=null; # If set then this is the date field to be added to searchable fields array

    $hiddenfields=explode(",",$hiddenfields);

    $fields=sql_query("SELECT *, ref, name, title, type ,order_by, keywords_index, partial_index, resource_type, resource_column, display_field, use_for_similar, iptc_equiv, display_template, tab_name, required, smart_theme_name, exiftool_field, advanced_search, simple_search, help_text, tooltip_text, display_as_dropdown, display_condition, field_constraint, active FROM resource_type_field WHERE advanced_search=1 AND active=1 AND ((keywords_index=1 AND length(name)>0) OR type IN (" . implode(",",$FIXED_LIST_FIELD_TYPES) . ")) " . (($archive)?"":"and resource_type<>999") . " ORDER BY resource_type,order_by", "schema");
    # Apply field permissions and check for fields hidden in advanced search
    for ($n=0;$n<count($fields);$n++)
        {
        if (metadata_field_view_access($fields[$n]["ref"]) && !checkperm("T" . $fields[$n]["resource_type"]) && !in_array($fields[$n]["ref"], $hiddenfields))
            {
            $return[]=$fields[$n];
            if($fields[$n]["ref"]==$date_field)
                {
                $date_field_already_present=true;
                }
            }
        }

    # If not already in the list of advanced search metadata fields, insert the field which is the designated searchable date ($date_field)
    if(!$date_field_already_present 
        && $daterange_search 
        && metadata_field_view_access($date_field) 
        && !in_array($date_field, $hiddenfields))
        {
        $date_field_data = get_resource_type_field($date_field);
        # Insert searchable date field so that it appears as the first array entry for a given resource type
        $return1=array();
        for ($n=0;$n<count($return);$n++)
            {
            if (isset($date_field_data))
                {
                if ($return[$n]["resource_type"] == $date_field_data['resource_type']) 
                    {
                    $return1[]=$date_field_data;
                    $date_field_data=null; # Only insert it once
                    }
                }
            $return1[]=$return[$n];
            }
        # If not yet added because it's resource type differs from everything in the list then add it to the end of the list
        if (isset($date_field_data))
            {
            $return1[]=$date_field_data;
            $date_field_data=null; # Keep things tidy
        }
        return $return1;
        }
 
    # Designated searchable date_field is already present in the lost of advanced search metadata fields        }
    return $return;
    }


function get_advanced_search_collection_fields($archive=false, $hiddenfields="")
    {
    # Returns a list of fields suitable for advanced searching. 
    $return=array();

    $hiddenfields=explode(",",$hiddenfields);

    $fields[]=Array ("ref" => "collection_title", "name" => "collectiontitle", "display_condition" => "", "tooltip_text" => "", "title"=>"Title", "type" => 0);
    $fields[]=Array ("ref" => "collection_keywords", "name" => "collectionkeywords", "display_condition" => "", "tooltip_text" => "", "title"=>"Keywords", "type" => 0);
    $fields[]=Array ("ref" => "collection_owner", "name" => "collectionowner", "display_condition" => "", "tooltip_text" => "", "title"=>"Owner", "type" => 0);
    # Apply field permissions and check for fields hidden in advanced search
    for ($n=0;$n<count($fields);$n++)
        {

        if (!in_array($fields[$n]["ref"], $hiddenfields))
        {$return[]=$fields[$n];}
        }

    return $return;
    }


function search_form_to_search_query($fields,$fromsearchbar=false)
    {
    # Take the data in the the posted search form that contained $fields, and assemble
    # a search query string that can be used for a standard search.
    #
    # This is used to take the advanced search form and assemble it into a search query.
    
    global $auto_order_checkbox,$checkbox_and,$dynamic_keyword_and;
    $search="";
    if (getval("basicyear","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.="basicyear:" . getval("basicyear",""); 
        }
    if (getval("basicmonth","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.="basicmonth:" . getval("basicmonth",""); 
        }
    if (getval("basicday","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.="basicday:" . getval("basicday",""); 
        }
    if (getval("startdate","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.="startdate:" . getval("startdate","");
        }
    if (getval("enddate","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.="enddate:" . getval("enddate",""); 
        }
    if (getval("start_year","")!="")
        {       
        if ($search!="") {$search.=", ";}
        $search.="startdate:" . getval("start_year","");
        if (getval("start_month","")!="")
            {
            $search.="-" . getval("start_month","");
            if (getval("start_day","")!="")
                {
                $search.="-" . getval("start_day","");
                }
            else
                {
                $search.="-01";
                }
            }
        else
            {
            $search.="-01-01";
            }
        }   
    if (getval("end_year","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.="enddate:" . getval("end_year","");
        if (getval("end_month","")!="")
            {
            $search.="-" . getval("end_month","");
            if (getval("end_day","")!="")
                {
                $search.="-" . getval("end_day","");
                }
            else
                {
                $search.="-31";
                }
            }
        else
            {
            $search.="-12-31";
            }
        }
    if (getval("allfields","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.=join(", ",explode(" ",getvalescaped("allfields",""))); # prepend 'all fields' option
        }
    if (getval("resourceids","")!="")
        {
        $listsql="!list" . join(":",trim_array(split_keywords(getvalescaped("resourceids",""))));
        $search=$listsql . " " . $search;
        }

    // Disabled as was killing search
    //$tmp = hook("richeditsearchquery", "", array($search, $fields, $n)); if($tmp) $search .= $tmp;
    
    for ($n=0;$n<count($fields);$n++)
        {
        switch ($fields[$n]["type"])
            {
            case FIELD_TYPE_TEXT_BOX_MULTI_LINE:
            case FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE:
            case FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR:
            $name="field_" . $fields[$n]["ref"];
            $value=getvalescaped($name,"");
            if ($value!="")
                {
                $vs=split_keywords($value, false, false, false, false, true);
                for ($m=0;$m<count($vs);$m++)
                    {
                    if ($search!="") {$search.=", ";}
                    $search.= ((strpos($vs[$m],"\"")===false)?$fields[$n]["name"] . ":" . $vs[$m]:"\"" . $fields[$n]["name"] . ":" . substr($vs[$m],1,-1) . "\""); // Move any quotes around whole field:value element so that they are kept together
                    }
                }
            break;
            
            case FIELD_TYPE_DROP_DOWN_LIST: # -------- Dropdowns / check lists
            case FIELD_TYPE_CHECK_BOX_LIST:
            if ($fields[$n]["display_as_dropdown"])
                {
                # Process dropdown box
                $name="field_" . $fields[$n]["ref"];
                $value=getvalescaped($name,"");
                if ($value!=="")
                    {
                    /*
                    $vs=split_keywords($value);
                    for ($m=0;$m<count($vs);$m++)
                        {
                        if ($search!="") {$search.=", ";}
                        $search.=$fields[$n]["name"] . ":" . strtolower($vs[$m]);
                        }
                    */
                    if ($search!="") {$search.=", ";}
                    $search.= ((strpos($value," ")===false)?$fields[$n]["name"] . ":" . $value:"\"" . $fields[$n]["name"] . ":" .substr($value,1,-1) . "\"");
                    }
                }
            else
                {
                # Process checkbox list
                //$options=trim_array(explode(",",$fields[$n]["options"]));
                $options=array();
                node_field_options_override($options,$fields[$n]['ref']);
                $p="";
                $c=0;
                for ($m=0;$m<count($options);$m++)
                    {
                    $name=$fields[$n]["ref"] . "_" . md5($options[$m]);
                    $value=getvalescaped($name,"");
                    if ($value=="yes")
                        {
                        $c++;
                        if ($p!="") {$p.=";";}
                        $p.=mb_strtolower(i18n_get_translated($options[$m]), 'UTF-8');
                        }
                    }

                if (($c==count($options) && !$checkbox_and) && (count($options)>1))
                    {
                    # all options ticked - omit from the search (unless using AND matching, or there is only one option intended as a boolean selection)
                    $p="";
                    }
                if ($p!="")
                    {
                    if ($search!="") {$search.=", ";}
                    if($checkbox_and)
                        {
                        $p=str_replace(";",", {$fields[$n]["name"]}:",$p); // this will force each and condition into a separate union in do_search (which will AND)
                        if ($search!="") {$search.=", ";}
                        }
                    $search.=$fields[$n]["name"] . ":" . $p;
                    }
                }
            break;

            case FIELD_TYPE_DATE_AND_OPTIONAL_TIME: 
            case FIELD_TYPE_EXPIRY_DATE: 
            case FIELD_TYPE_DATE:
            case FIELD_TYPE_DATE_RANGE:
            $name="field_" . $fields[$n]["ref"];
            $datepart="";
            $value="";
            if (strpos($search, $name.":")===false) 
                {
                $key_year=$name."_year";
                $value_year=getvalescaped($key_year,"");
                if ($value_year!="") $value=$value_year;
                else $value="nnnn";
                
                $key_month=$name."_month";
                $value_month=getvalescaped($key_month,"");
                if ($value_month=="") $value_month.="nn";
                
                $key_day=$name."_day";
                $value_day=getvalescaped($key_day,"");
                if ($value_day!="") $value.="|" . $value_month . "|" . $value_day;
                elseif ($value_month!="nn") $value.="|" . $value_month;
                
                if (($value!=="nnnn|nn|nn")&&($value!=="nnnn")) 
                    {
                    if ($search!="") {$search.=", ";}
                    $search.=$fields[$n]["name"] . ":" . $value;
                    }
                }

            if(($date_edtf=getvalescaped("field_" . $fields[$n]["ref"] . "_edtf",""))!=="")
                {
                // We have been passed the range in EDTF format, check it is in the correct format
                $rangeregex="/^(\d{4})(-\d{2})?(-\d{2})?\/(\d{4})(-\d{2})?(-\d{2})?/";
                if(!preg_match($rangeregex,$date_edtf,$matches))
                    {
                    //ignore this string as it is not a valid EDTF string
                    continue 2;
                    }
                $rangedates = explode("/",$date_edtf);
                $rangestart=str_pad($rangedates[0], 10, "-00");
                $rangeendparts=explode("-",$rangedates[1]);
                $rangeend=$rangeendparts[0] . "-" . (isset($rangeendparts[1])?$rangeendparts[1]:"12") . "-" . (isset($rangeendparts[2])?$rangeendparts[2]:"99");
                $datepart = "start" . $rangestart . "end" . $rangeend;
                }
            else
                {
                #Date range search - start date
                if (getval($name . "_start_year","")!="")
                    {
                    $datepart.= "start" . getval($name . "_start_year","");
                    if (getval($name . "_start_month","")!="")
                        {
                        $datepart.="-" . getval($name . "_start_month","");
                        if (getval($name . "_start_day","")!="")
                            {
                            $datepart.="-" . getval($name . "_start_day","");
                            }
                        else
                            {
                            $datepart.="";
                            }
                        }
                    else
                        {
                        $datepart.="";
                        }
                    }
                    
                #Date range search - end date
                if (getval($name . "_end_year","")!="")
                    {
                    $datepart.= "end" . getval($name . "_end_year","");
                    if (getval($name . "_end_month","")!="")
                        {
                        $datepart.="-" . getval($name . "_end_month","");
                        if (getval($name . "_end_day","")!="")
                            {
                            $datepart.="-" . getval($name . "_end_day","");
                            }
                        else
                            {
                            $datepart.="-31";
                            }
                        }
                    else
                        {
                        $datepart.="-12-31";
                        }
                    }   
                }
            if ($datepart!="")
                {
                if ($search!="") {$search.=", ";}
                $search.=$fields[$n]["name"] . ":range" . $datepart;
                }

            break;
   
            case FIELD_TYPE_TEXT_BOX_SINGLE_LINE: # -------- Text boxes 
            default: 
                $value=getvalescaped('field_'.$fields[$n]["ref"],'');
                if ($value!="")
                    {
                    $valueparts=split_keywords($value, false, false, false, false, true);
                    foreach($valueparts as $valuepart)
                        {
                        if ($search!="") {$search.=", ";}
                        // Move any quotes around whole field:value element so that they are kept together
                        $search.= (strpos($valuepart,"\"")===false)?($fields[$n]["name"] . ":" . $valuepart):("\"" . $fields[$n]["name"] . ":" .substr($valuepart,1,-1) . "\"");
                        }
                    }
            break;
            }
        }

        ##### NODES #####
        // Fixed lists will be handled separately as we don't care about the field
        // they belong to (except when $checkbox_and and $dynamic_keyword_and)
        // we know exactly what we are searching for.
        $node_ref = '';

        foreach(getval('nodes_searched', array()) as $searchedfield => $searched_field_nodes)
            {
            // Fields that are displayed as a dropdown will only pass one node ID
            if(!is_array($searched_field_nodes) && '' == $searched_field_nodes)
                {
                continue;
                }
            else if(!is_array($searched_field_nodes))
                {
                $node_ref .= ', ' . NODE_TOKEN_PREFIX . escape_check($searched_field_nodes);
                continue;
                }

            $fieldinfo = get_resource_type_field($searchedfield);
            
            // For fields that are displayed as checkboxes
            $node_ref .= ', ';

            foreach($searched_field_nodes as $searched_node_ref)
                {
                if(($fieldinfo["type"] == FIELD_TYPE_CHECK_BOX_LIST && $checkbox_and) || ($fieldinfo["type"] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && $dynamic_keyword_and))
                    {
                    // Split into an additional search element to force a join since this is a separate condition
                    $node_ref .= ', ';
                    }
                $node_ref .= NODE_TOKEN_PREFIX . escape_check($searched_node_ref);
                }
            }

        $search = ('' == $search ? '' : join(', ', split_keywords($search,false,false,false,false,true))) . $node_ref;
        ##### END OF NODES #####

        $propertysearchcodes=array();
        global $advanced_search_properties;
        foreach($advanced_search_properties as $advanced_search_property=>$code)
            {
            $propval=getvalescaped($advanced_search_property,"");
            if($propval!="")
                {$propertysearchcodes[] =$code . ":" . $propval;}
            }
        if(count($propertysearchcodes)>0)
            {
            $search = '!properties' . implode(';', $propertysearchcodes) . ' ,' . $search;
            }
        else
            {
            // Allow a single special search to be prepended to the search string. For example, !contributions<user id>
            foreach ($_POST as $key=>$value)
                {
                if ($key[0]=='!' && strlen($value) > 0)
                    {
                    $search=$key . $value . ',' . $search;
                    //break;
                    }
                }
            }
        return $search;
    }

function refine_searchstring($search)
    {
    # This function solves several issues related to searching.
    # it eliminates duplicate terms, helps the field content to carry values over into advanced search correctly, fixes a searchbar bug where separators (such as in a pasted filename) cause an initial search to fail, separates terms for searchcrumbs.
    
    global $use_refine_searchstring, $dynamic_keyword_and;
    
    if (!$use_refine_searchstring){return $search;}
    
    if (substr($search,0,1)=="\"" && substr($search,-1,1)=="\"") {return $search;} // preserve string search functionality.
    
    global $noadd;
    $search=str_replace(",-",", -",$search);
    $search=str_replace ("\xe2\x80\x8b","",$search);// remove any zero width spaces.
    
    $keywords=split_keywords($search, false, false, false, false, true);

    $orfields=get_OR_fields(); // leave checkbox type fields alone
    $dynamic_keyword_fields=sql_array("SELECT name value FROM resource_type_field where type=9", "schema");
    
    $fixedkeywords=array();
    foreach ($keywords as $keyword)
        {
        if (strpos($keyword,"startdate")!==false || strpos($keyword,"enddate")!==false)
            {
            $keyword=str_replace(" ","-",$keyword);
            }
             
        if(strpos($keyword,"!collection") === 0)
            {
            $collection=intval(substr($search,11));
            $keyword = "!collection" . $collection;
            }
    
        if (strpos($keyword,":")>0)
            {
            $keywordar=explode(":",$keyword,2);
            $keyname=$keywordar[0];
            if (substr($keyname,0,1)!="!")
                {
                if(substr($keywordar[1],0,5)=="range"){$keywordar[1]=str_replace(" ","-",$keywordar[1]);}
                if (!in_array($keyname,$orfields) && (!$dynamic_keyword_and || ($dynamic_keyword_and && !in_array($keyname, $dynamic_keyword_fields))))
                    {
                    $keyvalues=explode(" ",str_replace($keywordar[0].":","",$keywordar[1]));
                    }
                else
                    {
                    $keyvalues=array($keywordar[1]);
                    }
                foreach ($keyvalues as $keyvalue)
                    {
                    if (!in_array($keyvalue,$noadd))
                        { 
                        $fixedkeywords[]=$keyname.":".$keyvalue;
                        }
                    }
                }
            else if (!in_array($keyword,$noadd))
                {
                $keywords=explode(" ",$keyword);
                $fixedkeywords[]=$keywords[0];
                } // for searches such as !list
            }
        else
            {
            if (!in_array($keyword,$noadd))
                { 
                $fixedkeywords[]=$keyword;
                }
            }
        }
    $keywords=$fixedkeywords;
    $keywords=array_unique($keywords);
    $search=implode(", ",$keywords);
    $search=str_replace(",-"," -",$search); // support the omission search
    return $search;
    }


function compile_search_actions($top_actions)
    {
    $options = array();
    $o=0;

    global $baseurl,$baseurl_short, $lang, $k, $search, $restypes, $order_by, $archive, $sort, $daylimit, $home_dash, $url,
           $allow_smart_collections, $resources_count, $show_searchitemsdiskusage, $offset, $allow_save_search,
           $collection, $usercollection, $internal_share_access, $show_edit_all_link, $system_read_only;

    if(!isset($internal_share_access)){$internal_share_access=false;}
    

    // globals that could also be passed as a reference
    global $starsearch;
    $urlparams = array(
        "search"        =>  $search,
        "collection"    =>  $collection,
        "restypes"      =>  $restypes,
        "starsearch"    =>  $starsearch,
        "order_by"      =>  $order_by,
        "archive"       =>  $archive,
        "sort"          =>  $sort,
        "daylimit"      =>  $daylimit,
        "offset"        =>  $offset,
        "k"             =>  $k
        );

    $omit_edit_all = false;

    #This is to stop duplicate "Edit all resources" caused on a collection search
    if(isset($search) && substr($search, 0, 11) == '!collection' && ($k == '' || $internal_share_access))
        { 
        $omit_edit_all = true;
        }
                   
    if(!checkperm('b') && ($k == '' || $internal_share_access)) 
        {
        if($top_actions && $allow_save_search && $usercollection != $collection)
            {
            $options[$o]['value']='save_search_to_collection';
            $options[$o]['label']=$lang['savethissearchtocollection'];
            $data_attribute['url'] = generateURL($baseurl_short . "pages/collections.php", $urlparams, array("addsearch" => $search));
            $options[$o]['data_attr']=$data_attribute;
            $options[$o]['category']  = ACTIONGROUP_ADVANCED;
            $options[$o]['order_by']  = 70;
            $o++;
            }

        #Home_dash is on, AND NOT Anonymous use, AND (Dash tile user (NOT with a managed dash) || Dash Tile Admin)
        if($top_actions && $home_dash && checkPermission_dashcreate())
            {
            $option_name = 'save_search_to_dash';
            $extraparams = array();
            $extraparams["create"] = "true";
            $extraparams["tltype"] = "srch";
            $extraparams["freetext"] = "true";
            
            $data_attribute = array(
                'url'  => generateURL($baseurl_short . "pages/dash_tile.php", $urlparams, $extraparams),
                'link' => str_replace($baseurl,'',$url)
            );

            if(substr($search, 0, 11) == '!collection')
                {
                $option_name = 'save_collection_to_dash';
                $extraparams["promoted_resource"] = "true";
                $extraparams["all_users"] = "1";
                $extraparams["link"] = $baseurl_short . "pages/search.php?search=!collection" . $collection;
                $data_attribute['url'] = generateURL($baseurl_short . "pages/dash_tile.php", $urlparams, $extraparams);
                }

            $options[$o]['value'] = $option_name;
            $options[$o]['label'] = $lang['savethissearchtodash'];
            $options[$o]['data_attr'] = $data_attribute;
            $options[$o]['category']  = ACTIONGROUP_SHARE;
            $options[$o]['order_by']  = 170;
            $o++;
            }
            
        // Save search as Smart Collections
        if($top_actions && $allow_smart_collections && substr($search, 0, 11) != '!collection')
            {
            $extra_tag_attributes = sprintf('
                    data-url="%spages/collections.php?addsmartcollection=%s&restypes=%s&archive=%s&starsearch=%s"
                ',
                $baseurl_short,
                urlencode($search),
                urlencode($restypes),
                urlencode($archive),
                urlencode($starsearch)
            );

            $options[$o]['value']='save_search_smart_collection';
            $options[$o]['label']=$lang['savesearchassmartcollection'];
            $options[$o]['data_attr']=array();
            $options[$o]['extra_tag_attributes']=$extra_tag_attributes;
            $options[$o]['category']  = ACTIONGROUP_COLLECTION;
            $options[$o]['order_by']  = 170;
            $o++;
            }

        /*// Wasn't able to see this working even in the old code
        // so I left it here for reference. Just uncomment it and it should work
        global $smartsearch;
        if($allow_smart_collections && substr($search, 0, 11) == '!collection' && (is_array($smartsearch[0]) && !empty($smartsearch[0])))
            {
            $smartsearch = $smartsearch[0];

            $extra_tag_attributes = sprintf('
                    data-url="%spages/search.php?search=%s&restypes=%s&archive=%s&starsearch=%s&daylimit=%s"
                ',
                $baseurl_short,
                urlencode($smartsearch['search']),
                urlencode($smartsearch['restypes']),
                urlencode($smartsearch['archive']),
                urlencode($smartsearch['starsearch']),
                urlencode($daylimit)
            );

            $options[$o]['value']='do_saved_search';
            $options[$o]['label']=$lang['dosavedsearch'];
            $options[$o]['data_attr']=array();
            $options[$o]['extra_tag_attributes']=$extra_tag_attributes;
            $o++;
            }*/

        if($resources_count != 0 && !$system_read_only)
            {
                $extra_tag_attributes = sprintf('
                        data-url="%spages/collections.php?addsearch=%s&restypes=%s&order_by=%s&sort=%s&archive=%s&mode=resources&daylimit=%s&starsearch=%s"
                    ',
                    $baseurl_short,
                    urlencode($search),
                    urlencode($restypes),
                    urlencode($order_by),
                    urlencode($sort),
                    urlencode($archive),
                    urlencode($daylimit),
                     urlencode($starsearch)
                );

                $options[$o]['value']='save_search_items_to_collection';
                $options[$o]['label']=$lang['savesearchitemstocollection'];
                $options[$o]['data_attr']=array();
                $options[$o]['extra_tag_attributes']=$extra_tag_attributes;
                $options[$o]['category']  = ACTIONGROUP_COLLECTION;
                $options[$o]['order_by']  = 170;
                $o++;
                

            if(0 != $resources_count && $show_searchitemsdiskusage) 
                {
                $extra_tag_attributes = sprintf('
                        data-url="%spages/search_disk_usage.php?search=%s&restypes=%s&offset=%s&order_by=%s&sort=%s&archive=%s&daylimit=%s&k=%s"
                    ',
                    $baseurl_short,
                    urlencode($search),
                    urlencode($restypes),
                    urlencode($offset),
                    urlencode($order_by),
                    urlencode($sort),
                    urlencode($archive),
                    urlencode($daylimit),
                    urlencode($k)
                );

                $options[$o]['value']='search_items_disk_usage';
                $options[$o]['label']=$lang['searchitemsdiskusage'];
                $options[$o]['data_attr']=array();
                $options[$o]['extra_tag_attributes']=$extra_tag_attributes;
                $options[$o]['category']  = ACTIONGROUP_ADVANCED;
                $options[$o]['order_by']  = 300;
                $o++;
                }
            }
        }

    // If all resources are editable, display an edit all link
    if($top_actions && $show_edit_all_link && !$omit_edit_all)
        {
        $editable_resources = do_search($search,$restypes,'resourceid',$archive,-1,'',false,0,false,false,$daylimit,false,false, true, true);

        if (is_array($editable_resources) && $resources_count == count($editable_resources))
            {
            $data_attribute['url'] = generateURL($baseurl_short . "pages/edit.php",$urlparams,array("editsearchresults" => "true"));
            $options[$o]['value']='editsearchresults';
            $options[$o]['label']=$lang['edit_all_resources'];
            $options[$o]['data_attr']=$data_attribute;
            $options[$o]['category'] = ACTIONGROUP_EDIT;
            $options[$o]['order_by']  = 130;
            $o++;
            }
        }
        
    if($top_actions && ($k == '' || $internal_share_access))
        {
        $options[$o]['value']            = 'csv_export_results_metadata';
        $options[$o]['label']            = $lang['csvExportResultsMetadata'];
        $options[$o]['data_attr']['url'] = sprintf('%spages/csv_export_results_metadata.php?search=%s&restypes=%s&order_by=%s&archive=%s&sort=%s&starsearch=%s',
            $baseurl_short,
            urlencode($search),
            urlencode($restypes),
            urlencode($order_by),
            urlencode($archive),
            urlencode($sort),
            urlencode($starsearch)
        );
        $options[$o]['category'] = ACTIONGROUP_ADVANCED;
        $options[$o]['order_by']  = 290;
        $o++;
        }

    // Add extra search actions or modify existing options through plugins
    $modified_options = hook('render_search_actions_add_option','',array($options, $urlparams));
    if($top_actions && !empty($modified_options))
        {
        $options=$modified_options;
        }

    return $options;
    }

function search_filter($search,$archive,$restypes,$starsearch,$recent_search_daylimit,$access_override,$return_disk_usage,$editable_only=false, $access = null, $smartsearch = false)
    {
    debug_function_call("search_filter", func_get_args());

    global $userref,$userpermissions,$resource_created_by_filter,$uploader_view_override,$edit_access_for_contributor,$additional_archive_states,$heightmin,
    $heightmax,$widthmin,$widthmax,$filesizemin,$filesizemax,$fileextension,$haspreviewimage,$geo_search_restrict,$pending_review_visible_to_all,
    $search_all_workflow_states,$pending_submission_searchable_to_all,$collections_omit_archived,$k,$collection_allow_not_approved_share,$archive_standard,
    $open_access_for_contributor, $searchstates;
    
    if (hook("modifyuserpermissions")){$userpermissions=hook("modifyuserpermissions");}
    $userpermissions = (isset($userpermissions)) ? $userpermissions : array();
    
    # Convert the provided search parameters into appropriate SQL, ready for inclusion in the do_search() search query.
    if(!is_array($archive)){$archive=explode(",",$archive);}
    $archive = array_filter($archive,function($state){return (string)(int)$state==(string)$state;}); // remove non-numeric values

    # Start with an empty string = an open query.
    $sql_filter="";

    # Apply resource types
    if (($restypes!="")&&(substr($restypes,0,6)!="Global") && substr($search, 0, 11) != '!collection')
        {
        if ($sql_filter!="") {$sql_filter.=" AND ";}
        $restypes_x=explode(",",$restypes);
        $sql_filter.="resource_type IN ('" . join("','", escape_check_array_values($restypes_x)) . "')";
        }

    # Apply star search
    if ($starsearch!="" && $starsearch!=0 && $starsearch!=-1)
        {
        if ($sql_filter!="") {$sql_filter.=" AND ";}
        $sql_filter.="user_rating >= '$starsearch'";
        }   
    if ($starsearch==-1)
        {
        if ($sql_filter!="") {$sql_filter.=" AND ";}
        $sql_filter.="user_rating = '-1'";
        }

    # Apply day limit
    if('' != $recent_search_daylimit && is_numeric($recent_search_daylimit))
        {
        if('' != $sql_filter)
            {
            $sql_filter .= ' AND ';
            }

        $sql_filter.= "creation_date > (curdate() - interval '" . escape_check($recent_search_daylimit) . "' DAY)";
        }

    # The ability to restrict access by the user that created the resource.
    if (isset($resource_created_by_filter) && count($resource_created_by_filter)>0)
        {
        $created_filter="";
        foreach ($resource_created_by_filter as $filter_user)
        {
        if ($filter_user==-1) {$filter_user=$userref;} # '-1' can be used as an alias to the current user. I.e. they can only see their own resources in search results.
        if ($created_filter!="") {$created_filter.=" OR ";} 
        $created_filter.= "created_by = '" . $filter_user . "'";
        }
        if ($created_filter!="")
        {
        if ($sql_filter!="") {$sql_filter.=" AND ";}
        $sql_filter.="(" . $created_filter . ")";
        }
        }


    # Geo zone exclusion
    # A list of upper/lower long/lat bounds, defining areas that will be excluded from geo search results.
    # Areas are defined as southwest lat, southwest long, northeast lat, northeast long
    if (count($geo_search_restrict)>0 && substr($search,0,4)=="!geo")
        {
        foreach ($geo_search_restrict as $zone)
        {
        if ($sql_filter!="") {$sql_filter.=" AND ";}
        $sql_filter.= "(geo_lat IS null OR geo_long IS null OR not(geo_lat >= '" . $zone[0] . "' AND geo_lat<= '" . $zone[2] . "'";
        $sql_filter.= " AND geo_long >= '" . $zone[1] . "' AND geo_long<= '" . $zone[3] . "'))";
        }
        }

    # append resource type restrictions based on 'T' permission 
    # look for all 'T' permissions and append to the SQL filter.
    $rtfilter=array();
    
    for ($n=0;$n<count($userpermissions);$n++)
        {
        if (substr($userpermissions[$n],0,1)=="T")
            {
            $rt=substr($userpermissions[$n],1);
            if (is_numeric($rt)&&!$access_override) {$rtfilter[]=$rt;}
            }
        }
    if (count($rtfilter)>0)
        {
        if ($sql_filter!="") {$sql_filter.=" AND ";}
        $sql_filter.="resource_type NOT IN (" . join(",",$rtfilter) . ")";
        }

    # append "use" access rights, do not show confidential resources unless admin
    if (!checkperm("v")&&!$access_override)
        {
        if ($sql_filter!="") {$sql_filter.=" AND ";}
        # Check both the resource access, but if confidential is returned, also look at the joined user-specific or group-specific custom access for rows.
        $sql_filter.="(r.access<>'2' OR (r.access=2 AND ((rca.access IS NOT null AND rca.access<>2) OR (rca2.access IS NOT null AND rca2.access<>2))))";
        }
        
    # append standard archive searching criteria. Updated Jan 2016 to apply to collections as resources in a pending state that are in a shared collection could bypass approval process
    if (!$access_override)
        {
        if(substr($search,0,11)=="!collection" || substr($search,0,5)=="!list" || substr($search,0,15)=="!archivepending" || substr($search,0,12)=="!userpending")
            {
            # Resources in a collection or list may be in any archive state
            # Other special searches define the archive state in search_special()
            if(substr($search,0,11)=="!collection" && $collections_omit_archived && !checkperm("e2"))
                {
                $sql_filter.= (($sql_filter!="")?" AND ":"") . "archive<>2";
                }
            }
        elseif ($search_all_workflow_states || substr($search,0,8)=="!related" || substr($search,0,8)=="!hasdata")
            {hook("search_all_workflow_states_filter");}   
        elseif (count($archive) == 0 || $archive_standard && !$smartsearch)
            {
            # If no archive specified add in default archive states (set by config options or as set in rse_workflow plugin)
            # Defaults are not used if searching smartsearch collection, actual values will be used instead
            if ($sql_filter!="") {$sql_filter.=" AND ";}
            $defaultsearchstates = get_default_search_states();
            if(count($defaultsearchstates) == 0)
                {
                // Make sure we have at least one state - system has been misconfigured
                $defaultsearchstates[] = 0;
                }
            $sql_filter.="archive IN (" . implode(",",$defaultsearchstates) . ")";
            }
        else
            {
            # Append normal filtering - extended as advanced search now allows searching by archive state
            if($sql_filter!="")
                {
                $sql_filter.=" AND ";
                }

            $sql_filter.="archive IN (" . implode(",",$archive) . ")";
            }
        if (!checkperm("v") && !(substr($search,0,11)=="!collection" && $k!='' && $collection_allow_not_approved_share)) 
            {
            $pending_states_visible_to_all_sql = "";
            # Append standard filtering to hide resources in a pending state, whatever the search
            if (!$pending_submission_searchable_to_all) {$pending_states_visible_to_all_sql.= "(r.archive<>-2 OR r.created_by='" . $userref . "')";}
            if (!$pending_review_visible_to_all){$pending_states_visible_to_all_sql.=(($pending_states_visible_to_all_sql!="")?" AND ":"") . "(r.archive<>-1 OR r.created_by='" . $userref . "')";}

            if ($pending_states_visible_to_all_sql != "")
                {
                    #Except when the resource is type that the user has ert permission for
                    $rtexclusions = "";
                    for ($n=0;$n<count($userpermissions);$n++)
                        {
                        if (substr($userpermissions[$n],0,3)=="ert")
                            {
                            $rt=substr($userpermissions[$n],3);
                            if (is_numeric($rt)) {$rtexclusions .= " OR (resource_type=" . $rt . ")";}
                            }
                        }
                    $sql_filter .= " AND ((" . $pending_states_visible_to_all_sql . ") " . $rtexclusions . ")";
                    unset($rtexclusions);
                }
            }
        }
        
    # Add code to filter out resoures in archive states that the user does not have access to due to a 'z' permission
    $filterblockstates="";
    for ($n=-2;$n<=3;$n++)
        {
        if(checkperm("z" . $n) && !$access_override)
            {
            if ($filterblockstates!="") {$filterblockstates.="','";}
            $filterblockstates .= $n;
            }
        }

    foreach ($additional_archive_states as $additional_archive_state)
        {
        if(checkperm("z" . $additional_archive_state))
            {
            if ($filterblockstates!="") {$filterblockstates.="','";}
            $filterblockstates .= $additional_archive_state;
            }
        }
    if ($filterblockstates!=""&&!$access_override)
        {
        if ($uploader_view_override)
            {
            if ($sql_filter!="") {$sql_filter.=" AND ";}
            $sql_filter.="(archive NOT IN ('$filterblockstates') OR created_by='" . $userref . "')";
            }
        else
            {
            if ($sql_filter!="") {$sql_filter.=" AND ";}
            $sql_filter.="archive NOT IN ('$filterblockstates')";
            }
        }
    
    # Append media restrictions
    
    if ($heightmin!='')
        {
        if ($sql_filter!="") {$sql_filter.=" AND ";}
        $sql_filter.= "dim.height>='$heightmin'";
        }

    # append ref filter - never return the batch upload template (negative refs)
    if ($sql_filter!="") {$sql_filter.=" AND ";}
    $sql_filter.="r.ref>0";

    // Only users with v perm can search for resources with a specific access
    if(checkperm("v") && !is_null($access) && is_numeric($access))
        {
        $sql_filter .= (trim($sql_filter) != "" ? " AND " : "");
        $sql_filter .= "r.access = {$access}";
        }

    // Append filter if only searching for editable resources
    // ($status<0 && !(checkperm("t") || $resourcedata['created_by'] == $userref) && !checkperm("ert" . $resourcedata['resource_type']))
    if($editable_only)
        {       
        $editable_filter = "";

        if(!checkperm("v") && !$access_override)
            {
            // following condition added 2020-03-02 so that resources without an entry in the resource_custom_access table are included in the search results - "OR (rca.access IS NULL AND rca2.access IS NULL)"    
            $editable_filter .= "(r.access <> 1 OR (r.access = 1 AND ((rca.access IS NOT null AND rca.access <> 1) OR (rca2.access IS NOT null AND rca2.access <> 1) OR (rca.access IS NULL AND rca2.access IS NULL)))) ";
            }

        # Construct resource type exclusion based on 'ert' permission 
        # look for all 'ert' permissions and append to the exclusion array.
        $rtexclusions=array();
        for ($n=0;$n<count($userpermissions);$n++)
            {
            if (substr($userpermissions[$n],0,3)=="ert")
                {
                $rt=substr($userpermissions[$n],3);
                if (is_numeric($rt)) {$rtexclusions[]=$rt;}
                }
            }   
            
        $blockeditstates=array();
        for ($n=-2;$n<=3;$n++)
            {
            if(!checkperm("e" . $n))
                {
                $blockeditstates[] = $n;
                }
            }
    
        foreach ($additional_archive_states as $additional_archive_state)
            {
            if(!checkperm("e" . $n))
                {
                $blockeditstates[] = $n;
                }
            }

        // Add code to hide resources in archive<0 unless has 't' permission, resource has been contributed by user or has ert permission
        if(!checkperm("t"))
            {
            if ($editable_filter!="") {$editable_filter .= " AND ";}
            $editable_filter.="(archive NOT IN (-2,-1) OR (created_by='" . $userref . "' ";
            if(count($rtexclusions)>0)
                {
                $editable_filter .= " OR resource_type IN (" . implode(",",$rtexclusions) . ")";
                }
            $editable_filter .= "))";
            }

        if (count($blockeditstates) > 0)
            {
            $blockeditoverride="";
            global $userref;
            if ($edit_access_for_contributor)
                {
                $blockeditoverride .= " created_by='" . $userref . "'";
                }
            if(count($rtexclusions)>0)
                {
                if ($blockeditoverride!="") {$blockeditoverride.=" AND ";}
                $blockeditoverride .= " resource_type IN (" . implode(",",$rtexclusions) . ")";
                }
            if ($editable_filter!="") {$editable_filter.=" AND ";}
            $editable_filter.="(archive NOT IN ('" . implode("','",$blockeditstates) . "')" . (($blockeditoverride!="")?" OR " . $blockeditoverride:"") . ")";
            }

        
        // Check for blocked/allowed resource types
        $allrestypes = get_resource_types();
        $blockedrestypes = array();
        foreach($allrestypes as $restype)
            {
            if(checkperm("XE" . $restype["ref"]))
                {
                $blockedrestypes[] = $restype["ref"]; 
                }
            }        
        if(checkperm("XE"))
            {
            $okrestypes = array();
            $okrestypesor = "";
            foreach($allrestypes as $restype)
                {
                if(checkperm("XE-" . $restype["ref"]))
                    {
                    $okrestypes[] = $restype["ref"]; 
                    }
                }
            if(count($okrestypes) > 0)
                {
                if ($editable_filter != "")
                    {
                    $editable_filter .= " AND ";
                    }
    
                if ($edit_access_for_contributor)
                    {
                    $okrestypesor .= " created_by='" . $userref . "'";
                    }
    
                $editable_filter.="(resource_type IN ('" . implode("','",$okrestypes) . "')" . (($okrestypesor != "") ? " OR " . $okrestypesor : "") . ")";
                }
            else
                {
                if ($editable_filter != "")
                    {
                    $editable_filter .= " AND ";
                    }
                $editable_filter .= " 0=1";
                }
            }


        $updated_editable_filter = hook("modifysearcheditable","",array($editable_filter,$userref));
        if($updated_editable_filter !== false)
            {
            $editable_filter = $updated_editable_filter;
            }

         if($editable_filter != "")
            {
            if ($sql_filter != "")
                {
                $sql_filter .= " AND ";
                }
            $sql_filter .= $editable_filter;
            }
        }

    return $sql_filter;
    }

function search_special($search,$sql_join,$fetchrows,$sql_prefix,$sql_suffix,$order_by,$orig_order,$select,$sql_filter,$archive,$return_disk_usage,$return_refs_only=false, $returnsql=false)
    {
    # Process special searches. These return early with results.
    global $FIXED_LIST_FIELD_TYPES, $lang, $k, $USER_SELECTION_COLLECTION;
    
    # View Last
    if (substr($search,0,5)=="!last") 
        {
        # Replace r2.ref with r.ref for the alternative query used here.

        $order_by=str_replace("r.ref","r2.ref",$order_by);
        if ($orig_order=="relevance")
            {
            # Special case for ordering by relevance for this query.
            $direction=((strpos($order_by,"DESC")===false)?"ASC":"DESC");
            $order_by="r2.ref " . $direction;
            }
       
        
        # Extract the number of records to produce
        $last=explode(",",$search);
        $last=str_replace("!last","",$last[0]);

        # !Last must be followed by an integer. SQL injection filter.
        if (ctype_digit($last))
            {
            $last=(int)$last;
            } 
            else
            {
            $last=1000;
            $search="!last1000";
            }
        
        # Fix the ORDER BY for this query (special case due to inner query)
        $order_by=str_replace("r.rating","rating",$order_by);
        $sql = $sql_prefix . "SELECT DISTINCT *,r2.total_hit_count score FROM (SELECT $select FROM resource r $sql_join WHERE $sql_filter ORDER BY ref DESC LIMIT $last ) r2 ORDER BY $order_by" . $sql_suffix;
        return $returnsql ? $sql : sql_query($sql,false,$fetchrows);
        }
    
     # Collections containing resources
     # NOTE - this returns collections not resources! Not intended for use in user searches.
     # This is used when the $collection_search_includes_resource_metadata option is enabled and searches collections based on the contents of the collections.
    if (substr($search,0,19)=="!contentscollection")
        {
        $flags=substr($search,19,((strpos($search," ")!==false)?strpos($search," "):strlen($search)) -19); # Extract User/Public/Theme flags from the beginning of the search parameter.
        
        if ($flags=="") {$flags="TP";} # Sensible default

        # Add collections based on the provided collection type flags.
        $collection_filter="(";
        if (strpos($flags,"T")!==false) # Include themes
            {
            if ($collection_filter!="(") {$collection_filter.=" OR ";}
            $collection_filter .= sprintf(" c.`type` = %s", COLLECTION_TYPE_FEATURED);
            }
    
     if (strpos($flags,"P")!==false) # Include public collections
            {
            if ($collection_filter!="(") {$collection_filter.=" OR ";}
            $collection_filter .= sprintf(" c.`type` = %s", COLLECTION_TYPE_PUBLIC);
            }
        
        if (strpos($flags,"U")!==false) # Include the user's own collections
            {
            if ($collection_filter!="(") {$collection_filter.=" OR ";}
            global $userref;
            $collection_filter .= sprintf(" (c.`type` = %s AND c.user = '%s')", COLLECTION_TYPE_STANDARD, escape_check($userref));
            }
        $collection_filter.=")";
        
        # Formulate SQL
        $sql="SELECT DISTINCT c.*, sum(r.hit_count) score, sum(r.hit_count) total_hit_count FROM collection c join resource r $sql_join join collection_resource cr on cr.resource=r.ref AND cr.collection=c.ref WHERE $sql_filter AND $collection_filter GROUP BY c.ref ORDER BY $order_by ";
        return $returnsql ? $sql : sql_query($sql);
        }
    
    # View Resources With No Downloads
    if (substr($search,0,12)=="!nodownloads") 
        {
        if ($orig_order=="relevance") {$order_by="ref DESC";}
        $sql=$sql_prefix . "SELECT r.hit_count score, $select FROM resource r $sql_join WHERE $sql_filter AND r.ref NOT IN (SELECT DISTINCT object_ref FROM daily_stat WHERE activity_type='Resource download') GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }
    
    # Duplicate Resources (based on file_checksum)
    if (substr($search,0,11)=="!duplicates") 
        {
        # Extract the resource ID
        $ref=explode(" ",$search);
        $ref=str_replace("!duplicates","",$ref[0]);
        $ref=explode(",",$ref);// just get the number
        $ref=escape_check($ref[0]);

        if ($ref!="") 
            {
            # Find duplicates of a given resource
            if (ctype_digit($ref)) 
                {
                $sql="SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join 
                    WHERE $sql_filter AND file_checksum <> '' AND file_checksum IS NOT NULL 
                                      AND file_checksum = (SELECT file_checksum FROM resource WHERE ref=$ref AND (file_checksum <> '' AND file_checksum IS NOT NULL) ) 
                    ORDER BY file_checksum, ref";    
                if($returnsql) {return $sql;}
                $results=sql_query($sql,false,$fetchrows);
                $count=count($results);
                if ($count>1) 
                    {
                    return $results;
                    }
                else 
                    {
                    return array();
                    }
                }
            else
                {
                # Given resource is not a valid identifier
                return array();
                }
            }
        else
            {
            # Find all duplicate resources
            $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE $sql_filter AND file_checksum IN (SELECT file_checksum FROM (SELECT file_checksum FROM resource WHERE file_checksum <> '' AND file_checksum IS NOT null GROUP BY file_checksum having count(file_checksum)>1)r2) ORDER BY file_checksum, ref" . $sql_suffix;
            return $returnsql?$sql:sql_query($sql,false,$fetchrows);
            }
        }
    
    # View Collection
    if (substr($search, 0, 11) == '!collection')
        {
        global $userref;

        $colcustperm = $sql_join;
        $colcustfilter = $sql_filter; // to avoid allowing this sql_filter to be modified by the $access_override search in the smart collection update below!!!
             
        # Special case if a key has been provided.
        if($k != '')
            {
            $sql_filter = 'r.ref > 0';
            }

        # Extract the collection number
        $collection = explode(' ', $search);
        $collection = str_replace('!collection', '', $collection[0]);
        $collection = explode(',', $collection); // just get the number
        $collection = (int)$collection[0];

        # Check access
        $validcollections = [];
        if(upload_share_active() !== false)
            {
            $validcollections = get_session_collections(get_rs_session_id(), $userref);
            }
        else
            {
            $user_collections = array_column(get_user_collections($userref,"","name","ASC",-1,false), "ref");
            $public_collections = array_column(search_public_collections('', 'name', 'ASC', true, false), 'ref');
            # include collections of requested resources
            $request_collections = array();
            if (checkperm("R"))
                {
                include_once('request_functions.php');
                $request_collections = array_column(get_requests(), 'collection');
                }
            # include collections of research resources
            $research_collections = array();
            if (checkperm("r"))
                {
                include_once('research_functions.php');
                $research_collections = array_column(get_research_requests(), 'collection');
                }
            $validcollections = array_unique(array_merge($user_collections, array($USER_SELECTION_COLLECTION), $public_collections, $request_collections, $research_collections));
            }

        if(in_array($collection, $validcollections) || featured_collection_check_access_control($collection))
            {
            if(!collection_readable($collection))
                {
                return array();
                }
            }
        elseif($k == "" || upload_share_active() !== false)
            {
            return [];
            }
        
        # Smart collections update
        global $allow_smart_collections, $smart_collections_async;
        if($allow_smart_collections)
            {
            global $smartsearch_ref_cache;
            if(isset($smartsearch_ref_cache[$collection]))
                {
                $smartsearch_ref = $smartsearch_ref_cache[$collection]; // this value is pretty much constant
                }
            else
                {
                $smartsearch_ref = sql_value('SELECT savedsearch value FROM collection WHERE ref="' . $collection . '"', '');
                $smartsearch_ref_cache[$collection] = $smartsearch_ref;
                }

            global $php_path;
            if($smartsearch_ref != '' && !$return_disk_usage)
                {
                if($smart_collections_async && isset($php_path) && file_exists($php_path . '/php'))
                    {
                    exec($php_path . '/php ' . dirname(__FILE__) . '/../pages/ajax/update_smart_collection.php ' . escapeshellarg($collection) . ' ' . '> /dev/null 2>&1 &');
                    }
                else 
                    {
                    include (dirname(__FILE__) . '/../pages/ajax/update_smart_collection.php');
                    }
                }   
            }   
        $searchsql = $sql_prefix . "SELECT DISTINCT c.date_added,c.comment,c.purchase_size,c.purchase_complete,r.hit_count score,length(c.comment) commentset, $select FROM resource r  join collection_resource c on r.ref=c.resource $colcustperm  WHERE c.collection='" . $collection . "' AND ($colcustfilter) GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
        $collectionsearchsql=hook('modifycollectionsearchsql','',array($searchsql));

        if($collectionsearchsql)
            {
            $searchsql=$collectionsearchsql;
            }    

        if($returnsql){return $searchsql;}
        
        if($return_refs_only)
            {
            // note that we actually include archive and created_by columns too as often used to work out permission to edit collection
            $result = sql_query($searchsql,false,$fetchrows,true,2,true,array('ref','resource_type','archive','created_by','access'));
            }
        else
            {
            $result = sql_query($searchsql,false,$fetchrows);
            }

        hook('beforereturnresults', '', array($result, $archive));

        return $result;
        }

    # View Related - Pushed Metadata (for the view page)
    if (substr($search,0,14)=="!relatedpushed")
        {
        # Extract the resource number
        $resource=explode(" ",$search);$resource=str_replace("!relatedpushed","",$resource[0]);
        $order_by=str_replace("r.","",$order_by); # UNION below doesn't like table aliases in the ORDER BY.
        
        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score,rt.name resource_type_name, $select FROM resource r join resource_type rt on r.resource_type=rt.ref AND rt.push_metadata=1 join resource_related t on (t.related=r.ref AND t.resource='" . $resource . "') $sql_join  WHERE 1=1 AND $sql_filter GROUP BY r.ref 
        UNION
        SELECT DISTINCT r.hit_count score, rt.name resource_type_name, $select FROM resource r join resource_type rt on r.resource_type=rt.ref AND rt.push_metadata=1 join resource_related t on (t.resource=r.ref AND t.related='" . $resource . "') $sql_join  WHERE 1=1 AND $sql_filter GROUP BY r.ref 
        ORDER BY $order_by" . $sql_suffix;
        
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }
        
    # View Related
    if (substr($search,0,8)=="!related")
        {
        # Extract the resource number
        $resource=explode(" ",$search);$resource=str_replace("!related","",$resource[0]);
        $order_by=str_replace("r.","",$order_by); # UNION below doesn't like table aliases in the ORDER BY.
        
        global $pagename, $related_search_show_self;
        $sql_self = '';
        if ($related_search_show_self && $pagename == 'search')
            {
            $sql_self = " SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE r.ref=$resource AND $sql_filter GROUP BY r.ref UNION ";
            }
        $sql=$sql_prefix . $sql_self . "SELECT DISTINCT r.hit_count score, $select FROM resource r join resource_related t on (t.related=r.ref AND t.resource='" . $resource . "') $sql_join  WHERE $sql_filter GROUP BY r.ref 
        UNION
        SELECT DISTINCT r.hit_count score, $select FROM resource r join resource_related t on (t.resource=r.ref AND t.related='" . $resource . "') $sql_join WHERE $sql_filter GROUP BY r.ref 
        ORDER BY $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }

    # Geographic search
    if (substr($search,0,4)=="!geo")
        {
        $geo=explode("t",str_replace(array("m","p"),array("-","."),substr($search,4))); # Specially encoded string to avoid keyword splitting
        if(!isset($geo[0]) || empty($geo[0]) || !isset($geo[1]) || empty($geo[1]))
        {
            exit($lang["geographicsearchmissing"]);
        }
        $bl=explode("b",$geo[0]);
        $tr=explode("b",$geo[1]);
        $sql="SELECT r.hit_count score, $select FROM resource r $sql_join WHERE 

                   geo_lat > '" . escape_check($bl[0]) . "'
              AND geo_lat < '" . escape_check($tr[0]) . "'
              AND geo_long > '" . escape_check($bl[1]) . "'
              AND geo_long < '" . escape_check($tr[1]) . "'

         AND $sql_filter GROUP BY r.ref ORDER BY $order_by";
        $searchsql=$sql_prefix . $sql . $sql_suffix;
        return $returnsql ? $searchsql : sql_query($searchsql,false,$fetchrows);
        }

    # Colour search
    if (substr($search,0,7)=="!colour")
        {
        $colour=explode(" ",$search);$colour=str_replace("!colour","",$colour[0]);

        $sql="SELECT r.hit_count score, $select FROM resource r $sql_join
                WHERE 
                    colour_key LIKE '" . escape_check($colour) . "%'
                OR  colour_key LIKE '_" . escape_check($colour) . "%'

         AND $sql_filter GROUP BY r.ref ORDER BY $order_by";
        
        $searchsql=$sql_prefix . $sql . $sql_suffix;
        return $returnsql ? $searchsql : sql_query($searchsql,false,$fetchrows);
        }

    # Similar to a colour
    if (substr($search,0,4)=="!rgb")
        {
        $rgb=explode(":",$search);$rgb=explode(",",$rgb[1]);

        $searchsql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE has_image=1 AND $sql_filter GROUP BY r.ref ORDER BY (abs(image_red-" . $rgb[0] . ")+abs(image_green-" . $rgb[1] . ")+abs(image_blue-" . $rgb[2] . ")) ASC LIMIT 500" . $sql_suffix;
        return $returnsql ? $searchsql : sql_query($searchsql,false,$fetchrows);
        }

    # Has no preview image
    if (substr($search,0,10)=="!nopreview")
        {
        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE has_image=0 AND $sql_filter GROUP BY r.ref" . $sql_suffix;
        return $returnsql ? $sql : sql_query($sql,false,$fetchrows);
        }

    # Similar to a colour by key
    if (substr($search,0,10)=="!colourkey")
        {
        # Extract the colour key
        $colourkey=explode(" ",$search);$colourkey=str_replace("!colourkey","",$colourkey[0]);

        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE has_image=1 AND left(colour_key,4)='" . $colourkey . "' and $sql_filter GROUP BY r.ref" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }

    global $config_search_for_number;
    if (($config_search_for_number && is_numeric($search)) || substr($search,0,9)=="!resource")
        {
        $theref = escape_check($search);
        $theref = preg_replace("/[^0-9]/","",$theref);
        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE r.ref='$theref' AND $sql_filter GROUP BY r.ref" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }

    # Searching for pending archive
    if (substr($search,0,15)=="!archivepending")
        {
        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE r.archive=1 AND r.ref>0 AND $sql_filter GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
        return $returnsql ? $sql : sql_query($sql,false,$fetchrows);
        }

    if (substr($search,0,12)=="!userpending")
        {
        if ($orig_order=="rating") {$order_by="request_count DESC," . $order_by;}
        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE r.archive=-1 AND r.ref>0 AND $sql_filter GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
        return $returnsql ? $sql : sql_query($sql,false,$fetchrows);
        }
        
    # View Contributions
    if (substr($search,0,14)=="!contributions")
        {
        global $userref;

        # Extract the user ref
        $cuser=explode(" ",$search);$cuser=str_replace("!contributions","",$cuser[0]);

        // Don't filter if user is searching for their own resources and $open_access_for_contributor=true;
        global $open_access_for_contributor;
        if($open_access_for_contributor && $userref == $cuser)
            {
            $sql_filter="archive IN (" . implode(",",$archive) . ")";
            $sql_join = " JOIN resource_type AS rty ON r.resource_type = rty.ref ";
            }

        $select=str_replace(",rca.access group_access,rca2.access user_access ",",null group_access, null user_access ",$select);
        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE created_by='" . $cuser . "' AND r.ref > 0 AND $sql_filter GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
        return $returnsql ? $sql : sql_query($sql,false,$fetchrows);
        }

    # Search for resources with images
    if ($search=="!images") 
        {
        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE has_image=1 AND $sql_filter GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
        return $returnsql ? $sql : sql_query($sql,false,$fetchrows);
        }

    # Search for resources not used in Collections
    if (substr($search,0,7)=="!unused")
        {
        $sql=$sql_prefix . "SELECT DISTINCT $select FROM resource r $sql_join WHERE r.ref>0 AND r.ref NOT IN (select c.resource FROM collection_resource c) AND $sql_filter" . $sql_suffix;
        return $returnsql ? $sql : sql_query($sql,false,$fetchrows);
        }

    # Search for a list of resources
    # !listall = archive state is not applied as a filter to the list of resources.
    if (substr($search,0,5)=="!list")
        {  
        $resources=explode(" ",$search);
        if (substr($search,0,8)=="!listall")
            {
            $resources=str_replace("!listall","",$resources[0]);
            } 
        else 
            {
            $resources=str_replace("!list","",$resources[0]);
            }
        $resources=explode(",",$resources);// separate out any additional keywords
        $resources=escape_check($resources[0]);
        if (strlen(trim($resources))==0)
            {
            $resources="where r.ref IS NULL";
            }
        else 
            {
            $resources="where (r.ref='".str_replace(":","' OR r.ref='",$resources) . "')";
            }

        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join $resources AND $sql_filter ORDER BY $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }

    # View resources that have data in the specified field reference - useful if deleting unused fields
    if (substr($search,0,8)=="!hasdata") 
        {
        $fieldref=intval(trim(substr($search,8)));
        $hasdatafieldtype = sql_value("SELECT `type` value FROM resource_type_field WHERE ref = '{$fieldref}'", 0, "schema");

        if(in_array($hasdatafieldtype,$FIXED_LIST_FIELD_TYPES))
            {
            $sql_join.=" RIGHT JOIN resource_node rn ON r.ref=rn.resource JOIN node n ON n.ref=rn.node WHERE n.resource_type_field='" . $fieldref . "'";
            $sql = $sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join AND r.ref > 0 AND $sql_filter GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
            return $returnsql?$sql:sql_query($sql,false,$fetchrows);
            
            }
        else
            {
            $sql_join.=" join resource_data on r.ref=resource_data.resource AND resource_data.resource_type_field=$fieldref AND resource_data.value<>'' ";
            $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join AND r.ref > 0 AND $sql_filter GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
            return $returnsql?$sql:sql_query($sql,false,$fetchrows);
            }
        }
        
    # Search for resource properties
    if (substr($search,0,11)=="!properties")
        {
        // Note: in order to combine special searches with normal searches, these are separated by space (" ")
        $searches_array = explode(' ', $search);
        $properties     = explode(';', substr($searches_array[0], 11));

        // Use a new variable to ensure nothing changes $sql_filter unless this is a valid property search 
        $sql_filter_properties = "";        
        foreach ($properties as $property)
            {
            $propertycheck=explode(":",$property);
            if(count($propertycheck)==2)
                {
                $propertyname=$propertycheck[0];
                $propertyval=escape_check($propertycheck[1]);
                $sql_filter_properties_and = $sql_filter_properties != "" ? " AND "  : ""; 
                switch($propertyname)
                    {
                    case "hmin":
                        $sql_filter_properties.= $sql_filter_properties_and . " rdim.height>='" . intval($propertyval) . "'";
                    break;
                    case "hmax":
                        $sql_filter_properties.= $sql_filter_properties_and . " rdim.height<='" . intval($propertyval) . "'";
                    break;
                    case "wmin":
                        $sql_filter_properties.= $sql_filter_properties_and . " rdim.width>='" . intval($propertyval) . "'";
                    break;
                    case "wmax":
                        $sql_filter_properties.= $sql_filter_properties_and . " rdim.width<='" . intval($propertyval) . "'";
                    break;
                    case "fmin":
                        // Need to convert MB value to bytes
                        $sql_filter_properties.= $sql_filter_properties_and . " r.file_size>='" . (floatval($propertyval) * 1024 * 1024) . "'";
                    break;
                    case "fmax":
                        // Need to convert MB value to bytes
                        $sql_filter_properties.= $sql_filter_properties_and . " r.file_size<='" . (floatval($propertyval) * 1024 * 1024) . "'";
                    break;
                    case "fext":
                        $propertyval=str_replace("*","%",$propertyval);
                        $sql_filter_properties.= $sql_filter_properties_and . " r.file_extension ";
                        if(substr($propertyval,0,1)=="-")
                            {
                            $propertyval = substr($propertyval,1);
                            $sql_filter_properties.=" NOT ";
                            }
                        if(substr($propertyval,0,1)==".")
                            {
                            $propertyval = substr($propertyval,1);
                            }
                            $sql_filter_properties.=" LIKE '". escape_check($propertyval) . "'";
                    break;
                    case "pi":
                        $sql_filter_properties.= $sql_filter_properties_and . " r.has_image='". intval($propertyval) . "'";
                    break;
                    case "cu":
                        $sql_filter_properties.= $sql_filter_properties_and . " r.created_by='". intval($propertyval) . "'";
                    break;

                    case "orientation":
                        $orientation_filters = array(
                            "portrait"  => "COALESCE(rdim.height, 0) > COALESCE(rdim.width, 0)",
                            "landscape" => "COALESCE(rdim.height, 0) < COALESCE(rdim.width, 0)",
                            "square"    => "COALESCE(rdim.height, 0) = COALESCE(rdim.width, 0)",
                        );

                        if(!in_array($propertyval, array_keys($orientation_filters)))
                            {
                            break;
                            }

                        $sql_filter_properties .= $sql_filter_properties_and .  $orientation_filters[$propertyval];
                    break;
                    }
                }
            }
        if($sql_filter_properties != "")
        {
        if(strpos($sql_join,"JOIN resource_dimensions rdim on r.ref=rdim.resource") === false)
            {
            $sql_join.=" JOIN resource_dimensions rdim on r.ref=rdim.resource";
            }
        if ($sql_filter == "")
            {
            $sql_filter .= " WHERE " . $sql_filter_properties;
            }
        else
            {
            $sql_filter .= " AND " . $sql_filter_properties;
            }
        }

        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE r.ref > 0 AND $sql_filter GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;

      

        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }

    # Search for resources where the file integrity has been marked as problematic or the file is missing
    if ($search=="!integrityfail") 
        {
        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE integrity_fail=1 AND $sql_filter GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
        return $returnsql ? $sql : sql_query($sql,false,$fetchrows);
        }
    
    # Search for locked resources 
    if ($search=="!locked") 
        {
        $sql=$sql_prefix . "SELECT DISTINCT r.hit_count score, $select FROM resource r $sql_join WHERE lock_user<>0 AND $sql_filter GROUP BY r.ref ORDER BY $order_by" . $sql_suffix;
        return $returnsql ? $sql : sql_query($sql,false,$fetchrows);
        }

    # Within this hook implementation, set the value of the global $sql variable:
    # Since there will only be one special search executed at a time, only one of the
    # hook implementations will set the value. So, you know that the value set
    # will always be the correct one (unless two plugins use the same !<type> value).
    $sql=hook("addspecialsearch", "", array($search));
    
    if($sql != "")
        {
        debug("Addspecialsearch hook returned useful results.");
        $searchsql=$sql_prefix . $sql . $sql_suffix;
        return $returnsql?$searchsql:sql_query($searchsql,false,$fetchrows);
        }

     # Arrived here? There were no special searches. Return false.
     return false;
     }


/**
* Function used to create a list of nodes found in a search string
* 
* IMPORTANT: use resolve_given_nodes() if you need to detect nodes based on
* search string format (ie. @@253@@255 and/ or !@@260)
* 
* @param string $string
* 
* @return array
*/
function resolve_nodes_from_string($string)
    {
    if(!is_string($string))
        {
        return array();
        }

    $node_bucket     = array();
    $node_bucket_not = array();
    $return          = array();

    resolve_given_nodes($string, $node_bucket, $node_bucket_not);

    $merged_nodes = array_merge($node_bucket, $node_bucket_not);

    foreach($merged_nodes as $nodes)
        {
        foreach($nodes as $node)
            {
            $return[] = $node;
            }
        }

    return $return;
    }


/**
* Utility function which helps rebuilding a specific field search string
* from a node element
* 
* @param array $node A node element as returned by get_node() or get_nodes()
* 
* @return string
*/
function rebuild_specific_field_search_from_node(array $node)
    {
    if(0 == count($node))
        {
        return '';
        }

    $field_shortname = sql_value("SELECT name AS `value` FROM resource_type_field WHERE ref = '{$node['resource_type_field']}'", "field{$node['resource_type_field']}", "schema");

    // Note: at the moment there is no need to return a specific field search by multiple options
    // Example: country:keyword1;keyword2
    return ((strpos($node['name']," ")===false)?$field_shortname . ":" . i18n_get_translated($node['name']):"\"" . $field_shortname . ":" . i18n_get_translated($node['name']) . "\"");
    }


function search_get_previews($search,$restypes="",$order_by="relevance",$archive=0,$fetchrows=-1,$sort="DESC",$access_override=false,$starsearch=0,$ignore_filters=false,$return_disk_usage=false,$recent_search_daylimit="", $go=false, $stats_logging=true, $return_refs_only=false, $editable_only=false,$returnsql=false,$getsizes=array(),$previewextension="jpg")
   {
   # Search capability.
   # Note the subset of the available parameters. We definitely don't want to allow override of permissions or filters.
   $results= do_search($search,$restypes,$order_by,$archive,$fetchrows,$sort,$access_override,$starsearch,$ignore_filters,$return_disk_usage,$recent_search_daylimit,$go,$stats_logging,$return_refs_only,$editable_only,$returnsql);
   if(is_string($getsizes)){$getsizes=explode(",",$getsizes);}
   if(is_array($results) && is_array($getsizes) && count($getsizes)>0)
        {
        $resultcount=count($results);
        for($n=0;$n<$resultcount;$n++)
            {
            global $access;
            $access=get_resource_access($results[$n]);
            $use_watermark=check_use_watermark();

            if($results[$n]["access"]==2){continue;} // No images for confidential resources
            $available=get_all_image_sizes(true,($access==1));
            foreach ($getsizes as $getsize)
                {
                if(!(in_array($getsize,array_column($available,"id")))){continue;}
                $resfile=get_resource_path($results[$n]["ref"],true,$getsize,false,$previewextension,-1,1,$use_watermark);
                if(file_exists($resfile))
                    {
                    $results[$n]["url_" . $getsize]=get_resource_path($results[$n]["ref"],false,$getsize,false,$previewextension,-1,1,$use_watermark);
                    }
                }

            }
        }
   return $results;
   }


function get_upload_here_selected_nodes($search, array $nodes)
    {
    $upload_here_nodes = resolve_nodes_from_string($search);

    if(empty($upload_here_nodes))
        {
        return $nodes;
        }

    return array_merge($nodes, $upload_here_nodes);
    }

/**
* get the default archive states to search
*  
* @return array
*/
function get_default_search_states()
    {
    global $searchstates, $pending_submission_searchable_to_all, $pending_review_visible_to_all;

    $defaultsearchstates = isset($searchstates) ? $searchstates : array(0); // May be set by rse_workflow plugin

    if($pending_submission_searchable_to_all)
        {
        $defaultsearchstates[] = -2;
        }
    if($pending_review_visible_to_all)
        {
        $defaultsearchstates[] = -1;
        }

    $modifiedstates = hook("modify_default_search_states","",array($defaultsearchstates));
    if(is_array($modifiedstates))
        {
        return $modifiedstates;
        }
    return $defaultsearchstates;
    }

/**
* Get the required search filter sql for the given filter for use in do_search()
*  
* @return array
*/
function get_filter_sql($filterid)
    {
    global $userref, $access_override, $custom_access_overrides_search_filter, $open_access_for_contributor;

    $filter         = get_filter($filterid);
    $filterrules    = get_filter_rules($filterid);

    $modfilterrules=hook("modifysearchfilterrules");
    if ($modfilterrules)
        {
        $filterrules = $modfilterrules;
        }
        
    $filtercondition = $filter["filter_condition"];
    $filters = array();
    $filter_ors = array(); // Allow filters to be overridden in certain cases
        
    foreach($filterrules as $filterrule)
        {
        $filtersql = "";
        if(count($filterrule["nodes_on"]) > 0)
            {
            $filtersql .= "r.ref " . ($filtercondition == RS_FILTER_NONE ? " NOT " : "") . " IN (SELECT rn.resource FROM resource_node rn WHERE rn.node IN ('" . implode("','",$filterrule["nodes_on"]) . "')) ";
            }
        if(count($filterrule["nodes_off"]) > 0)
            {
            if($filtersql != "") {$filtersql .= " OR ";}
            $filtersql .= "r.ref " . ($filtercondition == RS_FILTER_NONE ? "" : " NOT") . " IN (SELECT rn.resource FROM resource_node rn WHERE rn.node IN ('" . implode("','",$filterrule["nodes_off"]) . "')) ";
            }
            
        $filters[] = "(" . $filtersql . ")";
        }
    
    if (count($filters) > 0)
        {   
        if($filtercondition == RS_FILTER_ALL || $filtercondition == RS_FILTER_NONE)
            {
            $glue = " AND ";
            }
        else 
            {
            // This is an OR filter
            $glue = " OR ";
            }
        
        // Bracket the filters to ensure that there is no hanging OR to create an unintentional disjunct
        $filter_add = "( " . implode($glue, $filters) . " )";
        
        # If custom access has been granted for the user or group, nullify the search filter, effectively selecting "true".
        if (!checkperm("v") && !$access_override && $custom_access_overrides_search_filter) # only for those without 'v' (which grants access to all resources)
            {
            $filter_ors[] = "(rca.access IS NOT null AND rca.access<>2) OR (rca2.access IS NOT null AND rca2.access<>2)";
            }

        if($open_access_for_contributor)
            {
            $filter_ors[] = "(r.created_by='$userref')";
            }
        
        if(count($filter_ors) > 0)
            {
            $filter_add = "((" . $filter_add . ") OR (" . implode(") OR (",$filter_ors) . "))";
            }
        return $filter_add;
        }
    }


function split_keywords($search,$index=false,$partial_index=false,$is_date=false,$is_html=false, $keepquotes=false)
    {
    # Takes $search and returns an array of individual keywords.
    global $config_trimchars,$permitted_html_tags, $permitted_html_attributes;

    if ($index && $is_date)
        {
        # Date handling... index a little differently to support various levels of date matching (Year, Year+Month, Year+Month+Day).
        $s=explode("-",$search);
        if (count($s)>=3)
            {
            return (array($s[0],$s[0] . "-" . $s[1],$search));
            }
        else if (is_array($search))
            {
            return $search;
            }
        else
            {
            return array($search);
            }
        }
        
    # Remove any real / unescaped lf/cr
    $search=str_replace("\r"," ",$search);
    $search=str_replace("\n"," ",$search);
    $search=str_replace("\\r"," ",$search);
    $search=str_replace("\\n"," ",$search);
    
    if($is_html || (substr($search,0,1) == "<" && substr($search,-1,1) == ">"))
        {
        // String can't be in encoded format at this point or string won't be indexed correctly.
        $search=html_entity_decode($search);
        if($index)
            {
            // Clean up html for indexing
            // Allow indexing of anchor text
            $allowed_tags = array_merge(array("a"),$permitted_html_tags);
            $allowed_attributes = array_merge(array("href"),$permitted_html_attributes);
            $search=strip_tags_and_attributes($search,$allowed_tags,$allowed_attributes);
            
            // Get rid of the actual html tags and attribute ids to prevent indexing these
            foreach ($allowed_tags as $allowed_tag)
                {
                $search=str_replace(array("<" . $allowed_tag . ">","<" . $allowed_tag,"</" . $allowed_tag)," ",$search);
                }
            foreach ($allowed_attributes as $allowed_attribute)
                {
                $search=str_replace($allowed_attribute . "="," ",$search);
                }
            // Remove any left over tag parts
            $search=str_replace(array(">", "<","="), " ",$search);
            }
        }

    $ns=trim_spaces($search);

    if ($index==false && strpos($ns,":")!==false) # special 'constructed' query type
        {   
        if($keepquotes)
            {
            preg_match_all('/("|-")(?:\\\\.|[^\\\\"])*"|\S+/', $ns, $matches);
            $return=trim_array($matches[0],$config_trimchars . ",");
            }
        elseif (strpos($ns,"startdate") !== false || strpos($ns,"enddate") !== false)
            {
            $return=explode(",",$ns);
            }
        else
            {
            $ns=cleanse_string($ns,false,!$index,$is_html);
            $return=explode(" ",$ns);
            }
        // If we are not breaking quotes we may end up a with commas in the array of keywords which need to be removed
        return trim_array($return,$config_trimchars . ($keepquotes?",":""));
        }
    else
        {
        # split using spaces and similar chars (according to configured whitespace characters)
        if(!$index && $keepquotes && strpos($ns,"\"")!==false)
            {
            preg_match_all('/("|-")(?:\\\\.|[^\\\\"])*"|\S+/', $ns, $matches);
            
            $splits=$matches[0];
            $ns=array();
            foreach ($splits as $split)
                {
                if(!(substr($split,0,1)=="\"" && substr($split,-1,1)=="\"") && strpos($split,",")!==false)
                    {
                    $split=explode(",",$split);
                    $ns = array_merge($ns,$split);
                    }
                else
                    {
                    $ns[] = $split;   
                    }
                }
            
        
            }
        else
            { 
            # split using spaces and similar chars (according to configured whitespace characters)
            $ns=explode(" ",cleanse_string($ns,false,!$index,$is_html));
            }
        
        
        $ns=trim_array($ns,$config_trimchars . ($keepquotes?",":""));
        
//print_r($ns) . "<br /><br />";
        if ($index && $partial_index) {
            return add_partial_index($ns);
        }
        return $ns;
        }

    }

function cleanse_string($string,$preserve_separators,$preserve_hyphen=false,$is_html=false)
    {
    # Removes characters from a string prior to keyword splitting, for example full stops
    # Also makes the string lower case ready for indexing.
    global $config_separators;
    $separators=$config_separators;

    // Replace some HTML entities with empty space
    // Most of them should already be in $config_separators
    // but others, like &shy; don't have an actual character that we can copy and paste
    // to $config_separators
    $string = htmlentities($string, null, 'UTF-8');
    $string = str_replace('&nbsp;', ' ', $string);
    $string = str_replace('&shy;', ' ', $string);
    $string = str_replace('&lsquo;', ' ', $string);
    $string = str_replace('&rsquo;', ' ', $string);
    $string = str_replace('&ldquo;', ' ', $string);
    $string = str_replace('&rdquo;', ' ', $string);
    $string = str_replace('&ndash;', ' ', $string);

    // Revert the htmlentities as otherwise we lose ability to identify certain text e.g. diacritics
    $string= html_entity_decode($string,ENT_QUOTES,'UTF-8');
    
    if ($preserve_hyphen)
        {
        # Preserve hyphen - used when NOT indexing so we know which keywords to omit from the search.
        if ((substr($string,0,1)=="-" /*support minus as first character for simple NOT searches */ || strpos($string," -")!==false) && strpos($string," - ")==false)
            {
                $separators=array_diff($separators,array("-")); # Remove hyphen from separator array.
            }
        }
    if (substr($string,0,1)=="!" && strpos(substr($string,1),"!")===false) 
            {
            // If we have the exclamation mark configured as a config separator but we are doing a special search we don't want to remove it
            $separators=array_diff($separators,array("!")); 
            }
            
    if ($preserve_separators)
            {
            return mb_strtolower(trim_spaces(str_replace($separators," ",$string)),'UTF-8');
            }
    else
            {
            # Also strip out the separators used when specifying multiple field/keyword pairs (comma and colon)
            $s=$separators;
            $s[]=",";
            $s[]=":";
            return mb_strtolower(trim_spaces(str_replace($s," ",$string)),'UTF-8');
            }
    }


function resolve_keyword($keyword,$create=false,$normalize=true,$stem=true)
    {
    debug_function_call("resolve_keyword", func_get_args());

    global $quoted_string, $stemming;
    $keyword=mb_strcut($keyword,0,100); # Trim keywords to 100 chars for indexing, as this is the length of the keywords column.
            
    if(!$quoted_string && $normalize)
        {
        $keyword=normalize_keyword($keyword);       
        debug("resolving normalized keyword " . $keyword  . ".");
        }
    
    # Stemming support. If enabled and a stemmer is available for the current language, index the stem of the keyword not the keyword itself.
    # This means plural/singular (and other) forms of a word are treated as equivalents.
    
    if ($stem && $stemming && function_exists("GetStem"))
        {
        $keyword=GetStem($keyword);
        }

    # Returns the keyword reference for $keyword, or false if no such keyword exists.
    $return=sql_value("select ref value from keyword where keyword='" . trim(escape_check($keyword)) . "'",false);
    if ($return===false && $create)
        {
        # Create a new keyword.
        debug("resolve_keyword: Creating new keyword for " . $keyword);
        sql_query("insert into keyword (keyword,soundex,hit_count) values ('" . escape_check($keyword) . "',left('".soundex(escape_check($keyword))."',10),0)");
        $return=sql_insert_id();
        }
    return $return;
    }


function add_partial_index($keywords)
    {
    # For each keywords in the supplied keywords list add all possible infixes and return the combined array.
    # This therefore returns all keywords that need indexing for the given string.
    # Only for fields with 'partial_index' enabled.
    $return=array();
    $position=0;
    $x=0;
    for ($n=0;$n<count($keywords);$n++)
        {
        $keyword=trim($keywords[$n]);
        $return[$x]['keyword']=$keyword;
        $return[$x]['position']=$position;
        $x++;
        if (strpos($keyword," ")===false) # Do not do this for keywords containing spaces as these have already been broken to individual words using the code above.
            {
            global $partial_index_min_word_length;
            # For each appropriate infix length
            for ($m=$partial_index_min_word_length;$m<strlen($keyword);$m++)
                {
                # For each position an infix of this length can exist in the string
                for ($o=0;$o<=strlen($keyword)-$m;$o++)
                    {
                    $infix=mb_substr($keyword,$o,$m);
                    $return[$x]['keyword']=$infix;
                    $return[$x]['position']=$position; // infix has same position as root
                    $x++;
                    }
                }
            } # End of no-spaces condition
        $position++; // end of root keyword
        } # End of partial indexing keywords loop
    return $return;
    }


function highlightkeywords($text,$search,$partial_index=false,$field_name="",$keywords_index=1, $str_highlight_options = STR_HIGHLIGHT_SIMPLE)
    {
    # do not highlight if the field is not indexed, so it is clearer where results came from.   
    if ($keywords_index!=1){return $text;}

    # Highlight searched keywords in $text
    # Optional - depends on $highlightkeywords being set in config.php.
    global $highlightkeywords;
    # Situations where we do not need to do this.
    if (!isset($highlightkeywords) || ($highlightkeywords==false) || ($search=="") || ($text=="")) {return $text;}


        # Generate the cache of search keywords (no longer global so it can test against particular fields.
        # a search is a small array so I don't think there is much to lose by processing it.
        $hlkeycache=array();
        $wildcards_found=false;
        $s=split_keywords($search);
        for ($n=0;$n<count($s);$n++)
                {
                if (strpos($s[$n],":")!==false) {
                        $c=explode(":",$s[$n]);
                        # only add field specific keywords
                        if($field_name!="" && $c[0]==$field_name){
                                $hlkeycache[]=$c[1];            
                        }   
                }
                # else add general keywords
                else {
                        $keyword=$s[$n];
            
                        global $stemming;
                        if ($stemming && function_exists("GetStem")) // Stemming enabled. Highlight any words matching the stem.
                            {
                            $keyword=GetStem($keyword);
                            }
                        
                        if (strpos($keyword,"*")!==false) {$wildcards_found=true;$keyword=str_replace("*","",$keyword);}
                        $hlkeycache[]=$keyword;
                }   
                }
        
    # Parse and replace.
    return str_highlight($text, $hlkeycache, $str_highlight_options);
    }
 

/**
 * Highlight the relevant text in a string
 *
 * @param  string $text         Text to search
 * @param  string $needle       Text to highlight
 * @param  int  $options        String highlight options - See include/definitions.php
 * @param  string $highlight    Optional custom highlight code
 * @return string
 */
function str_highlight($text, $needle, $options = null, $highlight = null)
    {

    /*
    this function requires that needle array does not contain any of the following characters: "(" ")"
    */
    $remove_from_needle = array("(", ")");
    $needle = str_replace($remove_from_needle, "", $needle);
    /*
    Sometimes the text can contain HTML entities and can break the highlighting feature
    Example: searching for "q&a" in a string like "q&amp;a" will highlight the wrong string
    */
    $htmltext = htmlspecialchars_decode($text);
    // If text contains HTML tags then ignore them
    if ($htmltext != strip_tags($htmltext))
        {
        $options = $options & STR_HIGHLIGHT_STRIPLINKS;
        }

    # Thanks to Aidan Lister <aidan@php.net>
    # Sourced from http://aidanlister.com/repos/v/function.str_highlight.php on 2007-10-09
    # As of 2020-09-07 code is now at https://github.com/aidanlister/code/blob/master/function.str_highlight.php 
    # The GitHub code repository README states: "The code resides entirely in the public domain."
    # https://github.com/aidanlister/code

    $text=str_replace("_","♠",$text);// underscores are considered part of words, so temporarily replace them for better \b search.
    $text=str_replace("#zwspace;","♣",$text);
    
    // Default highlighting. This used to use '<' and '>' characters as placeholders but now changed as they were being removed by strip_tags
    if ($highlight === null) {
        $highlight = '\(\1\)';
    }
    
    // Select pattern to use
    if ($options & STR_HIGHLIGHT_SIMPLE) {
        $pattern = '#(%s)#';
        $sl_pattern = '#(%s)#';
    } else {
        $pattern = '#(?!<.*?)(%s)(?![^<>]*?>)#';
        $sl_pattern = '#<a\s(?:.*?)>(%s)</a>#';
    }
    
    // Case sensitivity
    if (!($options & STR_HIGHLIGHT_CASESENS)) {
        $pattern .= 'i';
        $sl_pattern .= 'i';
    }
    
    $needle = (array) $needle;

    usort($needle, "sorthighlights");

    foreach ($needle as $needle_s) {
        if (strlen($needle_s) > 0) {
            $needle_s = preg_quote($needle_s, "#");
        
            // Escape needle with optional whole word check
            if ($options & STR_HIGHLIGHT_WHOLEWD) {
                $needle_s = '\b' . $needle_s . '\b';
            }
        
            // Strip links
            if ($options & STR_HIGHLIGHT_STRIPLINKS) {
                $sl_regex = sprintf($sl_pattern, $needle_s);
                $text = preg_replace($sl_regex, '\1', $text);
            }
        
            $regex = sprintf($pattern, $needle_s);
            $text = preg_replace($regex, $highlight, $text);
        }
    }
    $text=str_replace("♠","_",$text);
    $text=str_replace("♣","#zwspace;",$text);    

    # Fix - do the final replace at the end - fixes a glitch whereby the highlight HTML itself gets highlighted if it matches search terms, and you get nested HTML.
    $text=str_replace("\(",'<span class="highlight">',$text);
    $text=str_replace("\)",'</span>',$text);
    return $text;
    }
        
function sorthighlights($a, $b)
    {
    # fixes an odd problem for str_highlight related to the order of keywords
    if (strlen($a) < strlen($b)) {
        return 0;
        }
    return ($a < $b) ? -1 : 1;
    }


function get_suggested_keywords($search,$ref="")
    {
    # For the given partial word, suggest complete existing keywords.
    global $autocomplete_search_items,$autocomplete_search_min_hitcount;
    
    # Fetch a list of fields that are not available to the user - these must be omitted from the search.
    $hidden_indexed_fields=get_hidden_indexed_fields();
    
    $restriction_clause_free = "";
    $restriction_clause_node = ""; 
    
    if (count($hidden_indexed_fields) > 0)
        {
        $restriction_clause_free .= " AND rk.resource_type_field NOT IN ('" . join("','", $hidden_indexed_fields) . "')";
        $restriction_clause_node .= " AND n.resource_type_field NOT IN ('" . join("','", $hidden_indexed_fields) . "')";                                 
        }
    
    if ((string)(int)$ref == $ref)
        {
        $restriction_clause_free .= " AND rk.resource_type_field = '" . $ref . "'";
        $restriction_clause_node .= " AND n.resource_type_field = '" . $ref . "'";                                        
        }    
    
    return sql_array("SELECT ak.keyword value
        FROM
            (
            SELECT k.keyword, k.hit_count
            FROM keyword k
            JOIN resource_keyword rk ON rk.keyword=k.ref
            WHERE k.keyword LIKE '" . escape_check($search) . "%'" . $restriction_clause_free . "
            AND k.hit_count >= '$autocomplete_search_min_hitcount'
         
            UNION
         
            SELECT k.keyword, k.hit_count
            FROM keyword k
            JOIN node_keyword nk ON nk.keyword=k.ref
            JOIN node n ON n.ref=nk.node
            WHERE k.keyword LIKE '" . escape_check($search) . "%'" . $restriction_clause_node . "
            ) ak
        GROUP BY ak.keyword, ak.hit_count 
        ORDER BY ak.hit_count DESC LIMIT " . $autocomplete_search_items
        );
    }


function get_related_keywords($keyref)
    {
    debug_function_call("get_related_keywords", func_get_args());

    # For a given keyword reference returns the related keywords
    # Also reverses the process, returning keywords for matching related words
    # and for matching related words, also returns other words related to the same keyword.
    global $keyword_relationships_one_way;
    global $related_keywords_cache;
    if (isset($related_keywords_cache[$keyref])){
        return $related_keywords_cache[$keyref];
    } else {
        if ($keyword_relationships_one_way){
            $related_keywords_cache[$keyref]=sql_array("select related value from keyword_related where keyword='$keyref'");
            return $related_keywords_cache[$keyref];
            }
        else {
            $related_keywords_cache[$keyref]=sql_array("select keyword value from keyword_related where related='$keyref' union select related value from keyword_related where (keyword='$keyref' or keyword in (select keyword value from keyword_related where related='$keyref')) and related<>'$keyref'");
            return $related_keywords_cache[$keyref];
            }
        }
    }

    
    
function get_grouped_related_keywords($find="",$specific="")
    {
    debug_function_call("get_grouped_related_keywords", func_get_args());

    # Returns each keyword and the related keywords grouped, along with the resolved keywords strings.
    $sql="";
    if ($find!="") {$sql="where k1.keyword='" . escape_check($find) . "' or k2.keyword='" . escape_check($find) . "'";}
    if ($specific!="") {$sql="where k1.keyword='" . escape_check($specific) . "'";}
    
    return sql_query("
        select k1.keyword,group_concat(k2.keyword order by k2.keyword separator ', ') related from keyword_related kr
            join keyword k1 on kr.keyword=k1.ref
            join keyword k2 on kr.related=k2.ref
        $sql
        group by k1.keyword order by k1.keyword
        ");
    }

function save_related_keywords($keyword,$related)
    {
    debug_function_call("save_related_keywords", func_get_args());

    $keyref = resolve_keyword($keyword, true, false, false);
    $s=trim_array(explode(",",$related));

    sql_query("DELETE FROM keyword_related WHERE keyword = '$keyref'");
    if (trim($related)!="")
        {
        for ($n=0;$n<count($s);$n++)
            {
            sql_query("insert into keyword_related (keyword,related) values ('$keyref','" . resolve_keyword($s[$n],true,false,false) . "')");
            }
        }
    return true;
    }


function get_simple_search_fields()
    {
    global $FIXED_LIST_FIELD_TYPES, $country_search;
    # Returns a list of fields suitable for the simple search box.
    # Standard field titles are translated using $lang.  Custom field titles are i18n translated.
   
    # First get all the fields
    $allfields=get_resource_type_fields("","resource_type,order_by");
    
    # Applies field permissions and translates field titles in the newly created array.
    $return = array();
    for ($n = 0;$n<count($allfields);$n++)
        {
        if (
            # Check if for simple_search
            # Also include the country field even if not selected
            # This is to provide compatibility for older systems on which the simple search box was not configurable
            # and had a simpler 'country search' option.
            ($allfields[$n]["simple_search"] == 1 || (isset($country_search) && $country_search && $allfields[$n]["ref"] == 3))         
        &&
            # Must be either indexed or a fixed list type
            ($allfields[$n]["keywords_index"] == 1 || in_array($allfields[$n]["type"],$FIXED_LIST_FIELD_TYPES))
        &&    
            metadata_field_view_access($allfields[$n]["ref"]) && !checkperm("T" . $allfields[$n]["resource_type"] ))
            {
            $allfields[$n]["title"] = lang_or_i18n_get_translated($allfields[$n]["title"], "fieldtitle-");            
            $return[] = $allfields[$n];
            }
        }
    return $return;
    }


function get_fields_for_search_display($field_refs)
    {
    # Returns a list of fields/properties with refs matching the supplied field refs, for search display setup
    # This returns fewer columns and doesn't require that the fields be indexed, as in this case it's only used to judge whether the field should be highlighted.
    # Standard field titles are translated using $lang.  Custom field titles are i18n translated.

    if (!is_array($field_refs)) {
        print_r($field_refs);
        exit(" passed to getfields() is not an array. ");
    }

    # Executes query.
    $fields = sql_query("select *, ref, name, type, title, keywords_index, partial_index, value_filter from resource_type_field where ref in ('" . join("','",$field_refs) . "')","schema");

    # Applies field permissions and translates field titles in the newly created array.
    $return = array();
    for ($n = 0;$n<count($fields);$n++)
        {
        if (metadata_field_view_access($fields[$n]["ref"]))
            {
            $fields[$n]["title"] = lang_or_i18n_get_translated($fields[$n]["title"], "fieldtitle-");
            $return[] = $fields[$n];
            }
        }
    return $return;
    }


/**
* Get all defined filters (currently only used for search)
* 
* @param string $order  column to order by  
* @param string $sort   sort order ("ASC" or "DESC")
* @param string $find   text to search for in filter
* 
* @return array
*/        
function get_filters($order = "ref", $sort = "ASC", $find = "")
    {
    $validorder = array("ref","name");
    if(!in_array($order,$validorder))
        {
        $order = "ref";
        }
        
    if($sort != "ASC")
        {
        $sort = "DESC";
        }
        
    $condition = "";
    $join = "";
    
    if(trim($find) != "")
        {
        $join = " LEFT JOIN filter_rule_node fn ON fn.filter=f.ref LEFT JOIN node n ON n.ref = fn.node LEFT JOIN resource_type_field rtf ON rtf.ref=n.resource_type_field";
        $condition = " WHERE f.name LIKE '%" . escape_check($find) . "%' OR n.name LIKE '%" . escape_check($find) . "%' OR rtf.name LIKE '" . escape_check($find) . "' OR rtf.title LIKE '" . escape_check($find) . "'";
        }
        
    $sql = "SELECT f.ref, f.name FROM filter f {$join}{$condition} GROUP BY f.ref ORDER BY f.{$order} {$sort}";
    $filters = sql_query($sql);
    return $filters;
    }


/**
* Get filter summary details
* 
* @param int $filterid  ID of filter (from usergroup search_filter_id or user search_filter_oid)
* 
* @return array
*/           
function get_filter($filterid)
    {
    // Codes for filter 'condition' column
    // 1 = ALL must apply
    // 2 = NONE must apply
    // 3 = ANY can apply
    
    if(!is_numeric($filterid) || $filterid < 1)
            {
            return false;    
            }
            
    $filter  = sql_query("SELECT ref, name, filter_condition FROM filter f WHERE ref={$filterid}"); 
    
    if(count($filter) > 0)
        {
        return $filter[0];
        }
        
    return false;
    }

/**
* Get filter rules for use in search
* 
* @param int $filterid  ID of filter (from usergroup search_filter_id or user search_filter_oid)
* 
* @return array
*/       
function get_filter_rules($filterid)
    {
    $filter_rule_nodes  = sql_query("SELECT fr.ref as rule, frn.node_condition, frn.node FROM filter_rule fr LEFT JOIN filter_rule_node frn ON frn.filter_rule=fr.ref WHERE fr.filter='" . escape_check($filterid) . "'"); 
        
    // Convert results into useful array    
    $rules = array();
    foreach($filter_rule_nodes as $filter_rule_node)
        {
        $rule = $filter_rule_node["rule"];
        if(!isset($rules[$filter_rule_node["rule"]]))
            {
            $rules[$rule] = array();
            $rules[$rule]["nodes_on"] = array();
            $rules[$rule]["nodes_off"] = array();
            }
        if($filter_rule_node["node_condition"] == 1)
            {
            $rules[$rule]["nodes_on"][] = $filter_rule_node["node"];
            }
        else
            {
            $rules[$rule]["nodes_off"][] = $filter_rule_node["node"];
            }
        }
        
    return $rules;
    }
    
/**
* Get filter rule
* 
* @param int $ruleid  - ID of filter rule
* 
* @return array
*/       
function get_filter_rule($ruleid)
    {    
    $rule_data = sql_query("SELECT fr.ref, frn.node_condition, group_concat(frn.node) AS nodes, n.resource_type_field FROM filter_rule fr JOIN filter_rule_node frn ON frn.filter_rule=fr.ref join node n on frn.node=n.ref WHERE fr.ref='" . escape_check($ruleid) . "' GROUP BY n.resource_type_field,frn.node_condition"); 
    if(count($rule_data) > 0)
        {
        return $rule_data;
        }
    return false;
    }


/**
* Save filter, will return existing filter ID if text matches already migrated
* 
* @param int $filter            - ID of filter 
* @param int $filter_name       - Name of filter 
* @param int $filter_condition  - One of RS_FILTER_ALL,RS_FILTER_NONE,RS_FILTER_ANY
* 
* @return boolean | integer     - false, or ID of filter
*/        
function save_filter($filter,$filter_name,$filter_condition)
    {
    if(!in_array($filter_condition, array(RS_FILTER_ALL,RS_FILTER_NONE,RS_FILTER_ANY)))
        {
        return false;
        }
        
    if($filter != 0)
        {    
        if(!is_numeric($filter))
            {
            return false;    
            }
        sql_query("UPDATE filter SET name='" . escape_check($filter_name). "', filter_condition='{$filter_condition}' WHERE ref = '" . escape_check($filter)  . "'");
        }
    else
        {
        $newfilter = sql_query("INSERT INTO filter (name, filter_condition) VALUES ('" . escape_check($filter_name). "','{$filter_condition}')");
        $newfilter = sql_insert_id();
        return $newfilter;
        }

    return $filter;
    }
    
/**
* Save filter rule, will return existing rule ID if text matches already migrated
* 
* @param int $filter_rule       - ID of filter_rule
* @param int $filterid          - ID of associated filter 
* @param array|string $ruledata   - Details of associated rule nodes  (as JSON if submitted from rule edit page)
* 
* @return boolean | integer     - false, or ID of filter_rule
*/     
function save_filter_rule($filter_rule, $filterid, $rule_data)
    {
    if(!is_array($rule_data))
        {
        $rule_data = json_decode($rule_data);
        }
        
    if($filter_rule != "new" && (string)(int)$filter_rule == (string)$filter_rule && $filter_rule > 0)
        {
        sql_query("DELETE FROM filter_rule_node WHERE filter_rule = '{$filter_rule}'");
        }
    else
        {
        sql_query("INSERT INTO filter_rule (filter) VALUES ('{$filterid}')");
        $filter_rule = sql_insert_id();
        }    
        
    if(count($rule_data) > 0)
        {
        $nodeinsert = array();
        for($n=0;$n<count($rule_data);$n++)
            {
            $condition = $rule_data[$n][0];
            for($rd=0;$rd<count($rule_data[$n][1]);$rd++)
                {
                $nodeid = $rule_data[$n][1][$rd];
                $nodeinsert[] = "('" . $filter_rule . "','" . $nodeid . "','" . $condition . "')";
                }
            }
        $sql = "INSERT INTO filter_rule_node (filter_rule,node,node_condition) VALUES " . implode(',',$nodeinsert);
        sql_query($sql);
        }
    return $filter_rule;
    }

/**
* Delete specified filter
* 
* @param int $filter       - ID of filter
* 
* @return boolean | array of users/groups using filter
*/       
function delete_filter($filter)
    {
    if(!is_numeric($filter))
            {
            return false;    
            }
            
    // Check for existing use of filter
    $checkgroups = sql_array("SELECT ref value FROM usergroup WHERE search_filter_id='" . $filter . "'","");
    $checkusers  = sql_array("SELECT ref value FROM user WHERE search_filter_o_id='" . $filter . "'","");
    
    if(count($checkgroups)>0 || count($checkusers)>0)
        {
        return array("groups"=>$checkgroups, "users"=>$checkusers);
        }
    
    // Delete and cleanup any unused 
    sql_query("DELETE FROM filter WHERE ref='$filter'"); 
    sql_query("DELETE FROM filter_rule WHERE filter NOT IN (SELECT ref FROM filter)");
    sql_query("DELETE FROM filter_rule_node WHERE filter_rule NOT IN (SELECT ref FROM filter_rule)");
    sql_query("DELETE FROM filter_rule WHERE ref NOT IN (SELECT DISTINCT filter_rule FROM filter_rule_node)"); 
        
    return true;
    }

/**
* Delete specified filter_rule
* 
* @param int $filter       - ID of filter_rule
* 
* @return boolean | integer     - false, or ID of filter_rule
*/  
function delete_filter_rule($filter_rule)
    {
    if(!is_numeric($filter_rule))
            {
            return false;    
            }
            
    // Delete and cleanup any unused nodes
    sql_query("DELETE FROM filter_rule WHERE ref='$filter_rule'");  
    sql_query("DELETE FROM filter_rule_node WHERE filter_rule NOT IN (SELECT ref FROM filter_rule)");
    sql_query("DELETE FROM filter_rule WHERE ref NOT IN (SELECT DISTINCT filter_rule FROM filter_rule_node)"); 
        
    return true;
    }

/**
* Copy specified filter_rule
* 
* @param int $filter            - ID of filter_rule to copy
* 
* @return boolean | integer     - false, or ID of new filter
*/ 
function copy_filter($filter)
    {
    if(!is_numeric($filter))
            {
            return false;    
            }
            
    sql_query("INSERT INTO filter (name, filter_condition) SELECT name, filter_condition FROM filter WHERE ref={$filter}"); 
    $newfilter = sql_insert_id();
    $rules = sql_array("SELECT ref value from filter_rule  WHERE filter={$filter}"); 
    foreach($rules as $rule)
        {
        sql_query("INSERT INTO filter_rule (filter) VALUES ({$newfilter})");
        $newrule = sql_insert_id();
        sql_query("INSERT INTO filter_rule_node (filter_rule, node_condition, node) SELECT '{$newrule}', node_condition, node FROM filter_rule_node WHERE filter_rule='{$rule}'");
        }

    return $newfilter;
    }

/**
* Add POST/GET parameters into search string. Moved from pages/search.php
* 
* @param string $search        Existing search string without params added
* 
* @return string               Updated string with params added
*/ 
function update_search_from_request($search)
    {
    global $config_separators;
    reset ($_POST);reset($_GET);

    foreach (array_merge($_GET, $_POST) as $key=>$value)
        {
        if(is_string($value))
          {
          $value = trim($value);
          }

        if ($value!="" && substr($key,0,6)=="field_")
            {
            if ((strpos($key,"_year")!==false)||(strpos($key,"_month")!==false)||(strpos($key,"_day")!==false))
                {
                # Date field
                
                # Construct the date from the supplied dropdown values
                $key_part=substr($key,0, strrpos($key, "_"));
                $field=substr($key_part,6);
                $value="";
                if (strpos($search, $field.":")===false) 
                    {
                    $key_year=$key_part."_year";
                    $value_year=getvalescaped($key_year,"");
                    if ($value_year!="") $value=$value_year;
                    else $value="nnnn";
                    
                    $key_month=$key_part."_month";
                    $value_month=getvalescaped($key_month,"");
                    if ($value_month=="") $value_month.="nn";
                    
                    $key_day=$key_part."_day";
                    $value_day=getvalescaped($key_day,"");
                    if ($value_day!="") $value.="|" . $value_month . "|" . $value_day;
                    elseif ($value_month!="nn") $value.="|" . $value_month;
                    $search=(($search=="")?"":join(", ",split_keywords($search)) . ", ") . $field . ":" . $value;
                    }
                                
                }
            elseif (strpos($key,"_drop_")!==false)
                {
                # Dropdown field
                # Add keyword exactly as it is as the full value is indexed as a single keyword for dropdown boxes.
                $search=(($search=="")?"":join(", ",split_keywords($search, false, false, false, false, true)) . ", ") . substr($key,11) . ":" . $value;
                }       
            elseif (strpos($key,"_cat_")!==false)
                {
                # Category tree field
                # Add keyword exactly as it is as the full value is indexed as a single keyword for dropdown boxes.
                $value=str_replace(",",";",$value);
                if (substr($value,0,1)==";") {$value=substr($value,1);}
                
                $search=(($search=="")?"":join(", ",split_keywords($search, false, false, false, false, true)) . ", ") . substr($key,10) . ":" . $value;
                }
            else
                {
                # Standard field
                $values =  explode(' ', mb_strtolower(trim_spaces(str_replace($config_separators, ' ', $value)), 'UTF-8'));
                foreach ($values as $value)
                    {
                    # Standard field
                    $search=(($search=="")?"":join(", ",split_keywords($search, false, false, false, false, true)) . ", ") . substr($key,6) . ":" . $value;
                    }
                }
            }
        // Nodes can be searched directly when displayed on simple search bar
        // Note: intially they come grouped by field as we need to know whether if
        // there is a OR case involved (ie. @@101@@102)
        else if('' != $value && substr($key, 0, 14) == 'nodes_searched')
            {
            $node_ref = '';

            foreach($value as $searched_field_nodes)
                {
                // Fields that are displayed as a dropdown will only pass one node ID
                if(!is_array($searched_field_nodes) && '' == $searched_field_nodes)
                    {
                    continue;
                    }
                else if(!is_array($searched_field_nodes))
                    {
                    $node_ref .= ', ' . NODE_TOKEN_PREFIX . escape_check($searched_field_nodes);

                    continue;
                    }

                // For fields that can pass multiple node IDs at a time
                $node_ref .= ', ';

                foreach($searched_field_nodes as $searched_node_ref)
                    {
                    $node_ref .= NODE_TOKEN_PREFIX . escape_check($searched_node_ref);
                    }
                }
            $search = ('' == $search ? '' : join(', ', split_keywords($search,false,false,false,false,true))) . $node_ref;
            }
        }

    $year=getvalescaped("basicyear","");
    if ($year!="")
        {
        $search=(($search=="")?"":join(", ",split_keywords($search,false,false,false,false,true)) . ", ") . "basicyear:" . $year;
        }
    $month=getvalescaped("basicmonth","");
    if ($month!="")
        {
        $search=(($search=="")?"":join(", ",split_keywords($search,false,false,false,false,true)) . ", ") . "basicmonth:" . $month;
        }
    $day=getvalescaped("basicday","");
    if ($day!="")
        {
        $search=(($search=="")?"":join(", ",split_keywords($search,false,false,false,false,true)) . ", ") . "basicday:" . $day;
        }
    
    return $search;
    }

function get_search_default_restypes()
	{
	global $search_includes_resources, $collection_search_includes_resource_metadata;
	$defaultrestypes=array();
	if($search_includes_resources)
		{
		$defaultrestypes[] = "Global";
		}
	  else
		{
		$defaultrestypes[] = "Collections";
		if($search_includes_user_collections){$defaultrestypes[] = "mycol";}
		if($search_includes_public_collections){$defaultrestypes[] = "pubcol";}
		if($search_includes_themes){$defaultrestypes[] = "themes";}
		}	
	return $defaultrestypes;
	}
	
function get_selectedtypes()
    {
    global $search_includes_resources, $collection_search_includes_resource_metadata;

	# The restypes cookie is populated with $default_res_type at login and maintained thereafter
	# The advanced_search_section cookie is for the advanced search page and is not referenced elsewhere
	$restypes=getvalescaped("restypes","");
    $advanced_search_section = getvalescaped("advanced_search_section", "");
	
	# If advanced_search_section is absent then load it from restypes
	if (getval("submitted","")=="") 
		{
		if (!isset($advanced_search_section))
			{
			$advanced_search_section = $restypes;
			}
		}

	# If clearbutton pressed then the selected types are reset based on configuration settings
    if(getval('resetform', '') != '')
        {
        if (isset($default_advanced_search_mode)) 
            {
            $selectedtypes = explode(',',$default_advanced_search_mode);
            }
        else
            {
            if($search_includes_resources)
                {
                $selectedtypes = array('Global', 'Media');
                }
            else
                {
                $selectedtypes = array('Collections');
                }
            }
        }
    else # Not clearing, so get the currently selected types
        {
        $selectedtypes = explode(',', $advanced_search_section);
        }

    return $selectedtypes;
    }

function render_advanced_search_buttons() 
    {
    global $lang, $swap_clear_and_search_buttons, $baseurl_short;
 
    $button_search = "<input name=\"dosearch\" class=\"dosearch\" type=\"submit\" value=\"" . $lang["action-viewmatchingresults"] . "\" />";
    $button_reset = "<input name=\"resetform\" class=\"resetform\" type=\"submit\" onClick=\"unsetCookie('search_form_submit','" . $baseurl_short . "')\" value=\"". $lang["clearbutton"] . "\" />";
        
    $html= '
            <div class="QuestionSubmit QuestionSticky">
            <label for="buttons"> </label>
            {button1}
            &nbsp;
            {button2}
            </div>';
        
    if ($swap_clear_and_search_buttons)
            {
            $content_replace = array("{button1}" => $button_search, "{button2}" => $button_reset);
            } else 
            {	
            $content_replace = array("{button1}" => $button_reset, "{button2}" => $button_search);
            }
        
    echo strtr($html, $content_replace);
    }
    
/**
* If a "fieldX" order_by is used, check it's a valid value
* 
* @param string         string of order by
*/
function check_order_by_in_table_joins($order_by)
    {
    global $lang;

    if (substr($order_by,0,5)=="field" && !in_array(substr($order_by,5),get_resource_table_joins()))
        {
        exit($lang['error_invalid_input'] . ":- <pre>order_by : " . htmlspecialchars($order_by) . "</pre>");
        }
    }


/**
* Get collection total resource count for a list of collections
* 
* @param array $refs List of collection IDs
* 
* @return array Returns table of collections and their total resource count (taking into account access controls). Please
*               note that the returned array might NOT contain keys for all the input IDs (e.g validation failed).
*/
function get_collections_resource_count(array $refs)
    {
    $return = [];

    foreach($refs as $ref)
        {
        if(!(is_int_loose($ref) && $ref > 0))
            {
            continue;
            }

        $sql = do_search("!collection{$ref}", '', 'relevance', '0', -1, 'desc', false, 0, false, false, '', false, false, true, false, true, null, false);
        if(!(is_string($sql) && trim($sql) !== ''))
            {
            continue;
            }

        $resources = sql_query($sql, 'col_total_ref_count_w_perm', -1, true, 2, true, ['ref']);
        $return[$ref] = count($resources);
        }

    return $return;
    }

/**
 * Get all search request parameters. Note that this does not escape the
 * parameters which must be sanitised using escape_check() before using in SQL
 * or e.g. htmlspecialchars() or urlencode() before rendering on page
 *
 * @return array()
 */
function get_search_params()
    {
    $searchparams = array(
        "search"        =>"",
        "restypes"      =>"",
        "archive"       =>"",
        "order_by"      =>"",
        "sort"          =>"",
        "offset"        =>"",
        "k"             =>"",
        "access"        =>"",
        "foredit"       =>"",
        "recentdaylimit"=>"",
        );
    $requestparams = array();
    foreach($searchparams as $searchparam => $default)
        {
        $requestparams[$searchparam] = getval($searchparam,$default);
        }
    return $requestparams;
    }