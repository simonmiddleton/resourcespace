<?php
# Search functions
# Functions to perform searches (read only)
#  - For resource indexing / keyword creation, see resource_functions.php

include_once 'node_functions.php';

if (!function_exists("do_search"))
    {
    include_once 'search_do.php';       // be consistent with filename
    }

function resolve_soundex($keyword)
    {
    # returns the most commonly used keyword that sounds like $keyword, or failing a soundex match,
    # the most commonly used keyword that starts with the same few letters.
    global $soundex_suggest_limit;
    $soundex=sql_value("select keyword value from keyword where soundex='".soundex($keyword)."' and keyword not like '% %' and hit_count>'" . $soundex_suggest_limit . "' order by hit_count desc limit 1",false);
    if (($soundex===false) && (strlen($keyword)>=4))
        {
        # No soundex match, suggest words that start with the same first few letters.
        return sql_value("select keyword value from keyword where keyword like '" . substr($keyword,0,4) . "%' and keyword not like '% %' order by hit_count desc limit 1",false);
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
    $refine=sql_query("select k.keyword,count(*) c from resource_keyword r join keyword k on r.keyword=k.ref and r.resource in ($in) and length(k.keyword)>=3 and length(k.keyword)<=15 and k.keyword not like '%0%' and k.keyword not like '%1%' and k.keyword not like '%2%' and k.keyword not like '%3%' and k.keyword not like '%4%' and k.keyword not like '%5%' and k.keyword not like '%6%' and k.keyword not like '%7%' and k.keyword not like '%8%' and k.keyword not like '%9%' group by k.keyword order by c desc limit 5");
    for ($n=0;$n<count($refine);$n++)
        {
        if (strpos($search,$refine[$n]["keyword"])===false)
            {
            $suggest[]=$search . " " . $refine[$n]["keyword"];
            }
        }
    return $suggest;
    }

if (!function_exists("get_advanced_search_fields")) {
function get_advanced_search_fields($archive=false, $hiddenfields="")
    {
    global $FIXED_LIST_FIELD_TYPES;
    # Returns a list of fields suitable for advanced searching. 
    $return=array();

    $hiddenfields=explode(",",$hiddenfields);

    $fields=sql_query("select *, ref, name, title, type ,order_by, keywords_index, partial_index, resource_type, resource_column, display_field, use_for_similar, iptc_equiv, display_template, tab_name, required, smart_theme_name, exiftool_field, advanced_search, simple_search, help_text, tooltip_text, display_as_dropdown, display_condition, field_constraint from resource_type_field where advanced_search=1 and ((keywords_index=1 and length(name)>0) or type in (" . implode(",",$FIXED_LIST_FIELD_TYPES) . ")) " . (($archive)?"":"and resource_type<>999") . " order by resource_type,order_by");
    # Apply field permissions and check for fields hidden in advanced search
    for ($n=0;$n<count($fields);$n++)
        {
        if ((checkperm("f*") || checkperm("f" . $fields[$n]["ref"]))
            && !checkperm("f-" . $fields[$n]["ref"]) && !checkperm("T" . $fields[$n]["resource_type"]) && !in_array($fields[$n]["ref"], $hiddenfields))
            {$return[]=$fields[$n];}
        }

    return $return;
    }
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
    if (getval("year","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.="year:" . getval("year","");   
        }
    if (getval("month","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.="month:" . getval("month",""); 
        }
    if (getval("day","")!="")
        {
        if ($search!="") {$search.=", ";}
        $search.="day:" . getval("day",""); 
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
						$p=str_replace(";",", {$fields[$n]["name"]}:",$p);	// this will force each and condition into a separate union in do_search (which will AND)
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
                    continue;
                    }
                $rangedates = explode("/",$date_edtf);
                $rangestart=str_pad($rangedates[0],  10, "-00");
                $rangeendparts=explode("-",$rangedates[1]);
                $rangeend=$rangeendparts[0] . "-" . (isset($rangeendparts[1])?$rangeendparts[1]:"12") . "-" . (isset($rangeendparts[2])?$rangeendparts[2]:"99");
                $datepart = "start" . $rangestart . "end" . $rangeend;
                }
            else
                {
                #Date range search -  start date
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
                    
                #Date range search -  end date  
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
            // Allow a single special search to be prepended to the search string.  For example, !contributions<user id>
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

if (!function_exists("refine_searchstring")){
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
    $dynamic_keyword_fields=sql_array("select name value from resource_type_field where type=9");
    
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
}

function compile_search_actions($top_actions)
    {
    $options = array();
	$o=0;

    global $baseurl,$baseurl_short, $lang, $k, $search, $restypes, $order_by, $archive, $sort, $daylimit, $home_dash, $url,
           $allow_smart_collections, $resources_count, $show_searchitemsdiskusage, $offset, $allow_save_search,
           $collection, $usercollection, $internal_share_access;

	if(!isset($internal_share_access)){$internal_share_access=false;}
	

    // globals that could also be passed as a reference
    global $starsearch;

    if(!checkperm('b') && ($k == '' || $internal_share_access)) 
        {
        if($top_actions && $allow_save_search && $usercollection != $collection)
            {
            $extra_tag_attributes = sprintf('
                    data-url="%spages/collections.php?addsearch=%s&restypes=%s&archive=%s&daylimit=%s"
                ',
                $baseurl_short,
                urlencode($search),
                urlencode($restypes),
                urlencode($archive),
                urlencode($daylimit)
            );

            $options[$o]['value']='save_search_to_collection';
			$options[$o]['label']=$lang['savethissearchtocollection'];
			$options[$o]['data_attr']=array();
			$options[$o]['extra_tag_attributes']=$extra_tag_attributes;
			$o++;
            }

        #Home_dash is on, AND NOT Anonymous use, AND (Dash tile user (NOT with a managed dash) || Dash Tile Admin)
        if($top_actions && $home_dash && checkPermission_dashcreate())
            {
            $option_name = 'save_search_to_dash';
            $data_attribute = array(
                'url'  => $baseurl_short . 'pages/dash_tile.php?create=true&tltype=srch&freetext=true',
                'link' => str_replace($baseurl,'',$url)
            );

            if(substr($search, 0, 11) == '!collection')
                {
                $option_name = 'save_collection_to_dash';
                $data_attribute['url'] = sprintf('
                    %spages/dash_tile.php?create=true&tltype=srch&promoted_resource=true&freetext=true&all_users=1&link=/pages/search.php?search=!collection%s&order_by=%s&sort=%s
                    ',
                    $baseurl_short,
                    urlencode($collection),
                    urlencode($order_by),
                    urlencode($sort)
                );
                }

            $options[$o]['value']=$option_name;
			$options[$o]['label']=$lang['savethissearchtodash'];
			$options[$o]['data_attr']=$data_attribute;
			$o++;
            }
        // Save search as Smart Collections
        if($allow_smart_collections && substr($search, 0, 11) != '!collection')
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

        if($resources_count != 0)
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
				$o++;
                }                
            }
        }

    if($top_actions && ($k == '' || $internal_share_access))
        {
        $options[$o]['value']            = 'csv_export_results_metadata';
		$options[$o]['label']            = $lang['csvExportResultsMetadata'];
		$options[$o]['data_attr']['url'] = sprintf('%spages/csv_export_results_metadata.php?search=%s&restype=%s&order_by=%s&archive=%s&sort=%s&starsearch=%s',
            $baseurl_short,
            urlencode($search),
            urlencode($restypes),
            urlencode($order_by),
            urlencode($archive),
            urlencode($sort),
            urlencode($starsearch)
        );

		$o++;
        
        $options[$o]['value']            = 'csv_export_results_metadata_personal';
		$options[$o]['label']            = $lang['csvExportResultsMetadataPersonal'];
		$options[$o]['data_attr']['url'] = sprintf('%spages/csv_export_results_metadata.php?search=%s&restype=%s&order_by=%s&archive=%s&sort=%s&starsearch=%s&personal=true',
            $baseurl_short,
            urlencode($search),
            urlencode($restypes),
            urlencode($order_by),
            urlencode($archive),
            urlencode($sort),
            urlencode($starsearch)
        );

		$o++;
        }

    // Add extra search actions or modify existing options through plugins
    $modified_options = hook('render_search_actions_add_option','',array($options));
	if($top_actions && !empty($modified_options))
		{
        $options=$modified_options;
		}

    return $options;
    }

function search_filter($search,$archive,$restypes,$starsearch,$recent_search_daylimit,$access_override,$return_disk_usage,$editable_only=false)
	{
	global $userref,$userpermissions,$resource_created_by_filter,$uploader_view_override,$edit_access_for_contributor,$additional_archive_states,$heightmin,
	$heightmax,$widthmin,$widthmax,$filesizemin,$filesizemax,$fileextension,$haspreviewimage,$geo_search_restrict,$pending_review_visible_to_all,
	$search_all_workflow_states,$pending_submission_searchable_to_all,$collections_omit_archived,$k,$collection_allow_not_approved_share,$archive_standard;
	
	# Convert the provided search parameters into appropriate SQL, ready for inclusion in the do_search() search query.
	if(!is_array($archive)){$archive=explode(",",$archive);}
	# Start with an empty string = an open query.
	$sql_filter="";
		
	# Apply resource types
	if (($restypes!="")&&(substr($restypes,0,6)!="Global") && substr($search, 0, 11) != '!collection')
	    {
	    if ($sql_filter!="") {$sql_filter.=" and ";}
	    $restypes_x=explode(",",$restypes);
	    $sql_filter.="resource_type in ('" . join("','",$restypes_x) . "')";
	    }
	
	# Apply star search
	if ($starsearch!="" && $starsearch!=0 && $starsearch!=-1)
	    {
	    if ($sql_filter!="") {$sql_filter.=" and ";}
	    $sql_filter.="user_rating >= '$starsearch'";
	    }   
	if ($starsearch==-1)
	    {
	    if ($sql_filter!="") {$sql_filter.=" and ";}
	    $sql_filter.="user_rating = '-1'";
	    }
	
	# Apply day limit
	if('' != $recent_search_daylimit && is_numeric($recent_search_daylimit))
        {
        if('' != $sql_filter)
            {
            $sql_filter .= ' and ';
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
		if ($created_filter!="") {$created_filter.=" or ";} 
		$created_filter.= "created_by = '" . $filter_user . "'";
		}    
	    if ($created_filter!="")
		{
		if ($sql_filter!="") {$sql_filter.=" and ";}            
		$sql_filter.="(" . $created_filter . ")";
		}
	    }
	
	
	# Geo zone exclusion
	# A list of upper/lower long/lat bounds, defining areas that will be excluded from geo search results.
	# Areas are defined as southwest lat, southwest long, northeast lat, northeast long
	if (count($geo_search_restrict)>0 && substr($search,0,4)=="!geo")
	    {
	    foreach ($geo_search_restrict   as $zone)
		{
		if ($sql_filter!="") {$sql_filter.=" and ";}
		$sql_filter.= "(geo_lat is null or geo_long is null or not(geo_lat >= '" . $zone[0] . "' and geo_lat<= '" . $zone[2] . "'";
		$sql_filter.= "and geo_long >= '" . $zone[1] . "' and geo_long<= '" . $zone[3] . "'))";
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
	    if ($sql_filter!="") {$sql_filter.=" and ";}
	    $sql_filter.="resource_type not in (" . join(",",$rtfilter) . ")";
	    }
	
	# append "use" access rights, do not show confidential resources unless admin
	if (!checkperm("v")&&!$access_override)
	    {
	    if ($sql_filter!="") {$sql_filter.=" and ";}
	    # Check both the resource access, but if confidential is returned, also look at the joined user-specific or group-specific custom access for rows.
	    $sql_filter.="(r.access<>'2' or (r.access=2 and ((rca.access is not null and rca.access<>2) or (rca2.access is not null and rca2.access<>2))))";
	    }
	    
	# append archive searching. Updated Jan 2016 to apply to collections as resources in a pending state that are in a shared collection could bypass approval process
	if (!$access_override)
	    {
	    if(substr($search,0,11)=="!collection" || substr($search,0,5)=="!list")
			{
			# Resources in a collection or list may be in any archive state
			if(substr($search,0,11)=="!collection" && $collections_omit_archived && !checkperm("e2"))
				{
				$sql_filter.= (($sql_filter!="")?" and ":"") . "archive<>2";
				}			
			}
		elseif ($search_all_workflow_states)
			{hook("search_all_workflow_states_filter");}   
		elseif ($archive_standard && $pending_review_visible_to_all)
            {
            # If resources pending review are visible to all, when performing a default search with no archive specified 
            # that normally returns only active resources, include pending review (-1) resources too.
            if ($sql_filter!="") {$sql_filter.=" and ";}
            $sql_filter.="archive in('0','-1')";
            } 
		else
            {
            # Append normal filtering - extended as advanced search now allows searching by archive state
            if($sql_filter!="")
                {
                $sql_filter.=" and ";
                }

            if('' == implode(',', $archive))
                {
                $archive = array(0);
                }

            $sql_filter.="archive in (" . implode(",",$archive) . ")";
            }
        if (!checkperm("v") && !(substr($search,0,11)=="!collection" && $k!='' && $collection_allow_not_approved_share)) 
            {
            # Append standard filtering to hide resources in a pending state, whatever the search
            if (!$pending_submission_searchable_to_all) {$sql_filter.= (($sql_filter!="")?" and ":"") . "(r.archive<>-2 or r.created_by='" . $userref . "')";}
            if (!$pending_review_visible_to_all){$sql_filter.=(($sql_filter!="")?" and ":"") . "(r.archive<>-1 or r.created_by='" . $userref . "')";}
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
			if ($sql_filter!="") {$sql_filter.=" and ";}
			$sql_filter.="(archive not in ('$filterblockstates') or created_by='" . $userref . "')";
			}
	    else
			{
			if ($sql_filter!="") {$sql_filter.=" and ";}
			$sql_filter.="archive not in ('$filterblockstates')";
			}
	    }
	
	# Append media restrictions
	
	if ($heightmin!='')
		{		
		if ($sql_filter!="") {$sql_filter.=" and ";}
		$sql_filter.= "dim.height>='$heightmin'";
		}
		
		
	# append ref filter - never return the batch upload template (negative refs)
	if ($sql_filter!="") {$sql_filter.=" and ";}
	$sql_filter.="r.ref>0";
	
	
	// Append filter if only searching for editable resources
	// ($status<0 && !(checkperm("t") || $resourcedata['created_by'] == $userref) && !checkperm("ert" . $resourcedata['resource_type']))
	if($editable_only)
		{		
		$editable_filter = "";
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
			$editable_filter.="(archive not in (-2,-1) or (created_by='" . $userref . "' ";
			if(count($rtexclusions)>0)
				{
				$editable_filter .= " or resource_type in (" . implode(",",$rtexclusions) . ")";				
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
				if ($blockeditoverride!="") {$blockeditoverride.=" and ";}
				$blockeditoverride .= " resource_type in (" . implode(",",$rtexclusions) . ")";				
				}
			if ($editable_filter!="") {$editable_filter.=" and ";}
			$editable_filter.="(archive not in ('" . implode("','",$blockeditstates) . "')" . (($blockeditoverride!="")?" or " . $blockeditoverride:"") . ")";
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
    global $FIXED_LIST_FIELD_TYPES;
	
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
        
        if (!is_numeric($last)) {$last=1000;$search="!last1000";} # 'Last' must be a number. SQL injection filter.
        
        # Fix the order by for this query (special case due to inner query)
        $order_by=str_replace("r.rating","rating",$order_by);
        
        $sql=$sql_prefix . "select distinct *,r2.total_hit_count score from (select $select from resource r $sql_join where $sql_filter order by ref desc limit $last ) r2 order by $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
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
            if ($collection_filter!="(") {$collection_filter.=" or ";}
            $collection_filter.=" (c.public=1 and (length(c.theme)>0))";
            }
	
	 if (strpos($flags,"P")!==false) # Include public collections
            {
            if ($collection_filter!="(") {$collection_filter.=" or ";}
            $collection_filter.=" (c.public=1 and (length(c.theme)=0 or c.theme is null))";
            }
        
        if (strpos($flags,"U")!==false) # Include the user's own collections
            {
            if ($collection_filter!="(") {$collection_filter.=" or ";}
            global $userref;
            $collection_filter.=" (c.public=0 and c.user='$userref')";
            }
        $collection_filter.=")";
        
        # Formulate SQL
        $sql="select distinct c.*, sum(r.hit_count) score, sum(r.hit_count) total_hit_count from collection c join resource r $sql_join join collection_resource cr on cr.resource=r.ref and cr.collection=c.ref where $sql_filter and $collection_filter group by c.ref order by $order_by ";#echo $search . " " . $sql;
        return $returnsql?$sql:sql_query($sql);
        }
    
    # View Resources With No Downloads
    if (substr($search,0,12)=="!nodownloads") 
        {
        if ($orig_order=="relevance") {$order_by="ref desc";}
        $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where $sql_filter and ref not in (select distinct object_ref from daily_stat where activity_type='Resource download') order by $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }
    
    # Duplicate Resources (based on file_checksum)
    if (substr($search,0,11)=="!duplicates") 
        {
        # find duplicates of a given resource
        
        # Extract the resource ID
        $ref=explode(" ",$search);
        $ref=str_replace("!duplicates","",$ref[0]);
        $ref=explode(",",$ref);// just get the number
        $ref=escape_check($ref[0]);

        if ($ref!="") 
            {
            $sql="select distinct r.hit_count score, $select from resource r $sql_join  where $sql_filter and file_checksum= (select file_checksum from (select file_checksum from resource where archive = 0 and ref=$ref and file_checksum is not null)r2) order by file_checksum";    
            if($returnsql) {return $sql;}
            $results=sql_query($sql,false,$fetchrows);
            $count=count($results);
            if ($count>1) 
                {
                return $results;
                }
            else 
                {
                return false;
                }
            }
        else
            {
            $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where $sql_filter and file_checksum in (select file_checksum from (select file_checksum from resource where archive = 0 and file_checksum <> '' and file_checksum is not null group by file_checksum having count(file_checksum)>1)r2) order by file_checksum" . $sql_suffix;
            return $returnsql?$sql:sql_query($sql,false,$fetchrows);
            }
        }
    
    # View Collection
    if (substr($search, 0, 11) == '!collection')
        {
        if($orig_order == 'relevance')
            {
            $order_by = 'c.sortorder ASC, c.date_added DESC, r.ref DESC';
            }

        $colcustperm   = $sql_join;
        $colcustfilter = $sql_filter; // to avoid allowing this sql_filter to be modified by the $access_override search in the smart collection update below!!!
        
              
        # Special case if a key has been provided.
        if(getval('k', '') != '')
            {
            $sql_filter = 'r.ref > 0';
            }

        # Extract the collection number
        $collection = explode(' ', $search);
        $collection = str_replace('!collection', '', $collection[0]);
        $collection = explode(',', $collection); // just get the number
        $collection = (int)$collection[0];

        # Check access
        if(!collection_readable($collection))
            {
            return array();
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
        $searchsql = $sql_prefix . "select distinct c.date_added,c.comment,c.purchase_size,c.purchase_complete,r.hit_count score,length(c.comment) commentset, $select from resource r  join collection_resource c on r.ref=c.resource $colcustperm  where c.collection='" . $collection . "' and $colcustfilter group by r.ref order by $order_by" . $sql_suffix;
        $collectionsearchsql=hook('modifycollectionsearchsql','',array($searchsql));

        if($collectionsearchsql)
            {
            $searchsql=$collectionsearchsql;
            }
        
        if($returnsql){return $searchsql;}
        
        if($return_refs_only)
            {
            // note that we actually include archive and created_by columns too as often used to work out permission to edit collection
            $result = sql_query($searchsql,false,$fetchrows,true,2,true,array('ref','archive','created_by'));
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
        $order_by=str_replace("r.","",$order_by); # UNION below doesn't like table aliases in the order by.
        
        $sql=$sql_prefix . "select distinct r.hit_count score,rt.name resource_type_name, $select from resource r join resource_type rt on r.resource_type=rt.ref and rt.push_metadata=1 join resource_related t on (t.related=r.ref and t.resource='" . $resource . "') $sql_join  where 1=1 and $sql_filter group by r.ref 
        UNION
        select distinct r.hit_count score, rt.name resource_type_name, $select from resource r join resource_type rt on r.resource_type=rt.ref and rt.push_metadata=1 join resource_related t on (t.resource=r.ref and t.related='" . $resource . "') $sql_join  where 1=1 and $sql_filter group by r.ref 
        order by $order_by" . $sql_suffix;
        
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }
        
    # View Related
    if (substr($search,0,8)=="!related")
        {
        # Extract the resource number
        $resource=explode(" ",$search);$resource=str_replace("!related","",$resource[0]);
        $order_by=str_replace("r.","",$order_by); # UNION below doesn't like table aliases in the order by.
        
        global $pagename, $related_search_show_self;
        $sql_self = '';
        if ($related_search_show_self && $pagename == 'search') 
            {
            $sql_self = " select distinct r.hit_count score, $select from resource r $sql_join where r.ref=$resource and $sql_filter group by r.ref UNION ";
            }
        $sql=$sql_prefix . $sql_self . "select distinct r.hit_count score, $select from resource r join resource_related t on (t.related=r.ref and t.resource='" . $resource . "') $sql_join  where 1=1 and $sql_filter group by r.ref 
        UNION
        select distinct r.hit_count score, $select from resource r join resource_related t on (t.resource=r.ref and t.related='" . $resource . "') $sql_join  where 1=1 and $sql_filter group by r.ref 
        order by $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }
        

        
    # Geographic search
    if (substr($search,0,4)=="!geo")
        {
        $geo=explode("t",str_replace(array("m","p"),array("-","."),substr($search,4))); # Specially encoded string to avoid keyword splitting
        $bl=explode("b",$geo[0]);
        $tr=explode("b",$geo[1]);   
        $sql="select r.hit_count score, $select from resource r $sql_join where 

                    geo_lat > '" . escape_check($bl[0]) . "'
              and   geo_lat < '" . escape_check($tr[0]) . "'        
              and   geo_long > '" . escape_check($bl[1]) . "'       
              and   geo_long < '" . escape_check($tr[1]) . "'       
                          
         and $sql_filter group by r.ref order by $order_by";
        $searchsql=$sql_prefix . $sql . $sql_suffix;
        return $returnsql?$searchsql:sql_query($searchsql,false,$fetchrows);
        }

    # Colour search
    if (substr($search,0,7)=="!colour")
        {
        $colour=explode(" ",$search);$colour=str_replace("!colour","",$colour[0]);

        $sql="select r.hit_count score, $select from resource r $sql_join
                where 
                    colour_key like '" . escape_check($colour) . "%'
                or  colour_key like '_" . escape_check($colour) . "%'
                          
         and $sql_filter group by r.ref order by $order_by";
        
        $searchsql=$sql_prefix . $sql . $sql_suffix;
        return $returnsql?$searchsql:sql_query($searchsql,false,$fetchrows);
        }       
        
    # Similar to a colour
    if (substr($search,0,4)=="!rgb")
        {
        $rgb=explode(":",$search);$rgb=explode(",",$rgb[1]);
        
        $searchsql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where has_image=1 and $sql_filter group by r.ref order by (abs(image_red-" . $rgb[0] . ")+abs(image_green-" . $rgb[1] . ")+abs(image_blue-" . $rgb[2] . ")) asc limit 500" . $sql_suffix;
        return $returnsql?$searchsql:sql_query($searchsql,false,$fetchrows);
        }
        
    # Has no preview image
    if (substr($search,0,10)=="!nopreview")
        {
        $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where has_image=0 and $sql_filter group by r.ref" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }       
        
    # Similar to a colour by key
    if (substr($search,0,10)=="!colourkey")
        {
        # Extract the colour key
        $colourkey=explode(" ",$search);$colourkey=str_replace("!colourkey","",$colourkey[0]);
        
        $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where has_image=1 and left(colour_key,4)='" . $colourkey . "' and $sql_filter group by r.ref" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }
    
    global $config_search_for_number;
    if (($config_search_for_number && is_numeric($search)) || substr($search,0,9)=="!resource")
        {
        $theref = escape_check($search);
        $theref = preg_replace("/[^0-9]/","",$theref);
        $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where r.ref='$theref' and $sql_filter group by r.ref" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }

    # Searching for pending archive
    if (substr($search,0,15)=="!archivepending")
        {
        $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where archive=1 and ref>0 group by r.ref order by $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }
    
    if (substr($search,0,12)=="!userpending")
        {
        if ($orig_order=="rating") {$order_by="request_count desc," . $order_by;}
        $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where archive=-1 and ref>0 group by r.ref order by $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }
        
    # View Contributions
    if (substr($search,0,14)=="!contributions")
        {
        global $userref;
        
        # Extract the user ref
        $cuser=explode(" ",$search);$cuser=str_replace("!contributions","",$cuser[0]);
        
        // Don't filter if user is searching for their own resources and $open_access_for_contributor=true;
		global $open_access_for_contributor;
		if($open_access_for_contributor && $userref==$cuser){$sql_filter="archive in(" . implode(",",$archive) . ")";$sql_join="";}
        
        $select=str_replace(",rca.access group_access,rca2.access user_access ",",null group_access, null user_access ",$select);
        $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where created_by='" . $cuser . "' and r.ref > 0 and $sql_filter group by r.ref order by $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }
    
    # Search for resources with images
    if ($search=="!images") 
        {
        $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where has_image=1 group by r.ref order by $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }

    # Search for resources not used in Collections
    if (substr($search,0,7)=="!unused") 
        {
        $sql=$sql_prefix . "SELECT distinct $select FROM resource r $sql_join  where r.ref>0 and r.ref not in (select c.resource from collection_resource c) and $sql_filter" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
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
    
        $sql=$sql_prefix . "SELECT distinct r.hit_count score, $select FROM resource r $sql_join $resources and $sql_filter order by $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }   

    # View resources that have data in the specified field reference - useful if deleting unused fields
    if (substr($search,0,8)=="!hasdata") 
        {       
        $fieldref=intval(trim(substr($search,8)));
        $hasdatafieldtype = sql_value("SELECT  `type` value FROM resource_type_field WHERE ref = '{$fieldref}'", 0);	
										
        if(in_array($hasdatafieldtype,$FIXED_LIST_FIELD_TYPES))
            {   
            // Check that nodes are empty
            /*$union = "select ref as resource, [bit_or_condition] 1 as score from resource r[union_index] where r[union_index].ref not in 
                    (
                    select rn.resource from  
                    node n 
                    right join resource_node rn on rn.node=n.ref  
                    where  n.resource_type_field='" . $nodatafield . "'
                    group by rn.resource
                    )";
                    
            */
            $sql_join.=" RIGHT JOIN resource_node rn ON r.ref=rn.resource JOIN node n ON n.ref=rn.node where  n.resource_type_field='" . $fieldref . "'";
            $sql = $sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join and r.ref > 0 and $sql_filter group by r.ref order by $order_by" . $sql_suffix;
            return $returnsql?$sql:sql_query($sql,false,$fetchrows);        
            
            }
        else
            {
            $sql_join.=" join resource_data on r.ref=resource_data.resource and resource_data.resource_type_field=$fieldref and resource_data.value<>'' ";
            $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join and r.ref > 0 and $sql_filter group by r.ref order by $order_by" . $sql_suffix;
            return $returnsql?$sql:sql_query($sql,false,$fetchrows);
            }
        }
        
    # Search for resource properties
    if (substr($search,0,11)=="!properties")
        {
        // Note: in order to combine special searches with normal searches, these are separated by space (" ")
        $searches_array = explode(' ', $search);
        $properties     = explode(';', substr($searches_array[0], 11));
        $sql_join.=" left join resource_dimensions rdim on r.ref=rdim.resource";
        
        foreach ($properties as $property)
            {
            $propertycheck=explode(":",$property);
            if(count($propertycheck)==2)
                {
                $propertyname=$propertycheck[0];
                $propertyval=escape_check($propertycheck[1]);
                if($sql_filter==""){$sql_filter .= " where ";}else{$sql_filter .= " and ";}
                switch($propertyname)
                    {
                    case "hmin":
                        $sql_filter.=" rdim.height>='".  intval($propertyval) . "'";
                    break;
                    case "hmax":
                        $sql_filter.=" rdim.height<='".  intval($propertyval) . "'";
                    break;
                    case "wmin":
                        $sql_filter.=" rdim.width>='".  intval($propertyval) . "'";
                    break;
                    case "wmax":
                        $sql_filter.=" rdim.width<='".  intval($propertyval) . "'";
                    break;
                    case "fmin":
                        // Need to convert MB value to bytes
                        $sql_filter.=" r.file_size>='".  (floatval($propertyval) * 1024 * 1024) . "'";
                    break;
                    case "fmax":
                        // Need to convert MB value to bytes
                        $sql_filter.=" r.file_size<='". (floatval($propertyval) * 1024 * 1024) . "'";
                    break;
                    case "fext":
                        $propertyval=str_replace("*","%",$propertyval);
                        $sql_filter.=" r.file_extension ";
                        if(substr($propertyval,0,1)=="-")
                            {
                            $propertyval = substr($propertyval,1);
                            $sql_filter.=" not ";                            
                            }
                        if(substr($propertyval,0,1)==".")
                            {
                            $propertyval = substr($propertyval,1);
                            }
                        $sql_filter.=" like '". escape_check($propertyval) . "'";
                    break;
                    case "pi":
                        $sql_filter.=" r.has_image='".  intval($propertyval) . "'";
                    break;
                    case "cu":
                        $sql_filter.=" r.created_by='".  intval($propertyval) . "'";
                    break;
                    }
                }
            }
            
        $sql=$sql_prefix . "select distinct r.hit_count score, $select from resource r $sql_join  where r.ref > 0 and $sql_filter group by r.ref order by $order_by" . $sql_suffix;
        return $returnsql?$sql:sql_query($sql,false,$fetchrows);
        }

    # Within this hook implementation, set the value of the global $sql variable:
    # Since there will only be one special search executed at a time, only one of the
    # hook implementations will set the value.  So, you know that the value set
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

    $field_shortname = sql_value("SELECT name AS `value` FROM resource_type_field where ref = '{$node['resource_type_field']}'", "field{$node['resource_type_field']}");

    // Note: at the moment there is no need to return a specific field search by multiple options
    // Example: country:keyword1;keyword2
    return ((strpos($node['name']," ")===false)?$field_shortname . ":" . i18n_get_translated($node['name']):"\"" . $field_shortname . ":" . i18n_get_translated($node['name']) . "\"");
    }

    
function search_get_previews($search,$restypes="",$order_by="relevance",$archive=0,$fetchrows=-1,$sort="desc",$access_override=false,$starsearch=0,$ignore_filters=false,$return_disk_usage=false,$recent_search_daylimit="", $go=false, $stats_logging=true, $return_refs_only=false, $editable_only=false,$returnsql=false,$getsizes=array(),$previewextension="jpg")
   {
   # Search capability.
   # Note the subset of the available parameters. We definitely don't want to allow override of permissions or filters.
   $results= do_search($search,$restypes,$order_by,$archive,$fetchrows,$sort,$access_override,$starsearch,$ignore_filters,$return_disk_usage,$recent_search_daylimit,$go,$stats_logging,$return_refs_only,$editable_only,$returnsql);
   if(is_string($getsizes)){$getsizes=explode(",",$getsizes);}
   if(is_array($getsizes) && count($getsizes)>0)
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
