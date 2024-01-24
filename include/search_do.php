<?php
/**
* Takes a search string $search, as provided by the user, and returns a results set of matching resources. If there are
* no matches, instead returns an array of suggested searches
*
* @uses debug()
* @uses hook()
* @uses ps_value()
* @uses ps_query()
* @uses split_keywords()
* @uses add_verbatim_keywords()
* @uses search_filter()
* @uses search_special()
*
* @param string      $search                  Search string
* @param string      $restypes                Optionally used to specify which resource types to search for
* @param string      $order_by
* @param string      $archive                 Allows searching in more than one archive state
* @param int|array   $fetchrows               - If passing an integer, retrieve the specified number of rows (a limit with no offset).
*                                             The returned array of resources will be padded with '0' elements up to the total without the limit.
*                                             - If passing an array, the first element must be the offset (int) and the second the limit (int)
*                                             (the number of rows to return). See setup_search_chunks() for more detail.
*                                             IMPORTANT: When passing an array, the returned array will be in structured form - as returned by 
*                                             sql_limit_with_total_count() i.e. the array will have 'total' (the count) and 'data' (the resources) 
*                                             named array elements, and the data will not be padded.
* @param string      $sort
* @param boolean     $access_override         Used by smart collections, so that all all applicable resources can be judged
*                                             regardless of the final access-based results
* @param integer     $starsearch              DEPRECATED_STARSEARCH passed in for backwards compatibility
* @param boolean     $ignore_filters
* @param boolean     $return_disk_usage
* @param string      $recent_search_daylimit
* @param string|bool $go                      Paging direction (prev|next)
* @param boolean     $stats_logging           Log keyword usage
* @param boolean     $return_refs_only
* @param boolean     $editable_only
* @param boolean     $returnsql               Returns the query as a PreparedStatementQuery instance
* @param integer     $access                  Search for resources with this access
*
* @return null|string|array|PreparedStatementQuery
*/
function do_search(
    $search,
    $restypes = '',
    $order_by = 'relevance',
    $archive = '0',
    $fetchrows = -1,
    $sort = 'desc',
    $access_override = false,
    $starsearch = DEPRECATED_STARSEARCH,     # Parameter retained for backwards compatibility
    $ignore_filters = false,
    $return_disk_usage = false,
    $recent_search_daylimit = '',
    $go = false,
    $stats_logging = true,
    $return_refs_only = false,
    $editable_only = false,
    $returnsql = false,
    $access = null,
    $smartsearch = false
)
    {
    debug_function_call("do_search", func_get_args());

    global $sql, $order, $select, $sql_join, $sql_filter, $orig_order, $usergroup,
        $userref,$k, $DATE_FIELD_TYPES,$stemming, $usersearchfilter, $userpermissions, $usereditfilter, $userdata,
        $lang, $baseurl, $internal_share_access, $config_separators, $date_field, $noadd, $wildcard_always_applied,
        $index_contributed_by, $max_results, $config_search_for_number,
        $category_tree_search_use_and_logic, $date_field, $FIXED_LIST_FIELD_TYPES;

    if($editable_only && !$returnsql && trim((string) $k) != "" && !$internal_share_access)
        {
        return array();
        }

    $alternativeresults = hook("alternativeresults", "", array($go));
    if ($alternativeresults)
        {
        return $alternativeresults;
        }

    if(!is_not_wildcard_only($search))
        {
        $search = '';
        debug('do_search(): User searched only for "*". Converting this into an empty search instead.');
        }

    $modifyfetchrows = hook("modifyfetchrows", "", array($fetchrows));
    if ($modifyfetchrows)
        {
        $fetchrows=$modifyfetchrows;
        }

    if(strtolower($sort)!=='desc')      // default to ascending if not a valid "desc"
        {
        $sort='asc';
        }

    // Used for collection sort order as sortorder is ASC, date is DESC
    $revsort = (strtolower($sort) == 'desc') ? "asc" : " desc";

    $orig_order=$order_by;

    $order_by_date_sql_comma = ",";
    $order_by_date = "r.ref $sort";
    if(metadata_field_view_access($date_field))
        {
        $order_by_date_sql = "field{$date_field} {$sort}";
        $order_by_date_sql_comma = ", {$order_by_date_sql}, ";
        $order_by_date = "{$order_by_date_sql}, r.ref {$sort}";
        }

    # Check if order_by is empty string as this avoids 'relevance' default
    if ($order_by === "") {$order_by="relevance";}

    $order = array(
        "relevance"       => "score $sort, user_rating $sort, total_hit_count $sort {$order_by_date_sql_comma} r.ref $sort",
        "popularity"      => "user_rating $sort, total_hit_count $sort {$order_by_date_sql_comma} r.ref $sort",
        "rating"          => "r.rating $sort, user_rating $sort, score $sort, r.ref $sort",
        "date"            => "$order_by_date, r.ref $sort",
        "colour"          => "has_image $sort, image_blue $sort, image_green $sort, image_red $sort {$order_by_date_sql_comma} r.ref $sort",
        "country"         => "country $sort, r.ref $sort",
        "title"           => "title $sort, r.ref $sort",
        "file_path"       => "file_path $sort, r.ref $sort",
        "resourceid"      => "r.ref $sort",
        "resourcetype"    => "order_by $sort, resource_type $sort, r.ref $sort",
        "extension"       => "file_extension $sort, r.ref $sort",
        "titleandcountry" => "title $sort, country $sort, r.ref $sort",
        "random"          => "RAND()",
        "status"          => "archive $sort, r.ref $sort",
        "modified"        => "modified $sort, r.ref $sort"
    );

    // Add collection sort option only if searching a collection
    if(substr($search, 0, 11) == '!collection')
        {
        $order["collection"] = "c.sortorder $sort,c.date_added $revsort,r.ref $sort";
        }

    # Check if date_field is being used as this will be needed in the inner select to be used in ordering
    $include_fieldx=false;
    if (isset($order_by_date_sql) && array_key_exists($order_by,$order) && strpos($order[$order_by],$order_by_date_sql)!==false)
        {
        $include_fieldx=true;
        }

    # Append order by field to the above array if absent and if named "fieldn" (where n is one or more digits)
    if (!in_array($order_by,$order)&&(substr($order_by,0,5)=="field"))
        {
        if (!is_numeric(str_replace("field","",$order_by)))
            {
            exit("Order field incorrect.");
            }
        # If fieldx is being used this will be needed in the inner select to be used in ordering
        $include_fieldx=true;
        # Check for field type
        $field_order_check=ps_value("SELECT field_constraint value FROM resource_type_field WHERE ref = ?",["i",str_replace("field","",$order_by)],"", "schema");
        # Establish sort order (numeric or otherwise)
        # Attach ref as a final key to foster stable result sets which should eliminate resequencing when moving <- and -> through resources (in view.php)
        if ($field_order_check==1)
            {
            $order[$order_by]="$order_by +0 $sort,r.ref $sort";
            }
        else
            {
            $order[$order_by]="$order_by $sort,r.ref $sort";
            }
        }

	$archive=explode(",",$archive); // Allows for searching in more than one archive state

    hook("modifyorderarray");

    // ********************************************************************************

    // IMPORTANT!
    // add to this array in the format [AND group]=array(<list of nodes> to OR)
    $node_bucket=array();

    // add to this normal array to exclude nodes from entire search
    $node_bucket_not=array();

    // Take the current search URL and extract any nodes (putting into buckets) removing terms from $search
    resolve_given_nodes($search,$node_bucket,$node_bucket_not);

    $order_by=(isset($order[$order_by]) ? $order[$order_by] : (substr($search, 0, 11) == '!collection' ? $order['collection'] : $order['relevance']));       // fail safe by falling back to default if not found

    $searchidmatch = false;
    // Used to check if ok to skip keyword due to a match with resource type/resource ID
    if(is_int_loose($search))
        {
        // Resource ID is no longer indexed, if search is just for a single integer then include this
        $searchidmatch = ps_value("SELECT COUNT(*) AS value FROM resource WHERE ref = ?",["i",$search],0) != 0;
        }
  
    // Resource type is no longer indexed
    $restypenames = get_resource_types();

    # Extract search parameters and split to keywords.
    $search_params=$search;
    if (substr($search,0,1)=="!" && substr($search,0,6)!="!empty")
        {
        # Special search, discard the special search identifier when splitting keywords and extract the search parameters
        $s=strpos($search," ");
        if ($s===false)
            {
            $search_params=""; # No search specified
            }
        else
            {
            $search_params=substr($search,$s+1); # Extract search params
            }
        }

    if($search_params!="")
        {
        $keywords=split_keywords($search_params,false,false,false,false,true);
        }
    else
        {
        $keywords = array();
        }

    $search=trim($search);
    $keywords = array_values(array_filter(array_unique($keywords), 'is_not_wildcard_only'));

    $modified_keywords=hook('dosearchmodifykeywords', '', array($keywords, $search));
    if ($modified_keywords)
        {
        $keywords=$modified_keywords;
        }

    # -- Build up filter SQL that will be used for all queries
    $sql_filter = new PreparedStatementQuery();
    $sql_filter = search_filter($search,$archive,$restypes,$recent_search_daylimit,$access_override,$return_disk_usage, $editable_only, $access, $smartsearch);
    debug("do_search(): \$sql_filter = '" . $sql_filter->sql . "', parameters = ['" . implode("','",$sql_filter->parameters) . "']");

    # Initialise variables.
    $sql="";
    $sql_keyword_union              = array(); // An array of all the unions - at least one for each keyword
    //$sql_keyword_union_params       = array();
    $sql_keyword_union_aggregation  = array(); // This is added to the SELECT statement. Normally 'BIT_OR(`keyword_[union_index]_found`) AS `keyword_[union_index]_found`', where '[union_index]' will be replaced
    $sql_keyword_union_criteria     = array(); // Criteria for the union to be true - normally '`h`.`keyword_[union_index]_found`', where '[union_index]' will be replaced

    // For each union sql_keyword_union_or must be set
    // This will normally will be false to ensure that all keywords are found
    // Needs to be set to false when keywords are expanded and an extra $sql_keyword_union element is added (e.g. for wildcards) so that a match on any is ok
    $sql_keyword_union_or           = array();

    $sql_join = new PreparedStatementQuery();
    $sql_join->sql                  =   "";
    $sql_join->parameters           = [];

    # If returning disk used by the resources in the search results ($return_disk_usage=true) then wrap the returned SQL in an outer query that sums disk usage.
    $sql_prefix="";
    $sql_suffix="";
    if ($return_disk_usage)
        {
        $sql_prefix="SELECT sum(disk_usage) total_disk_usage,count(*) total_resources, resourcelist.ref, resourcelist.score, resourcelist.user_rating, resourcelist.total_hit_count FROM (";
        $sql_suffix=") resourcelist";
        }

    # ------ Advanced 'custom' permissions, need to join to access table.
    if ((!checkperm("v")) && !$access_override)
        {
        # one extra join (rca2) is required for user specific permissions (enabling more intelligent watermarks in search view)
        # the original join is used to gather group access into the search query as well.
        $sql_join->sql   = " LEFT OUTER JOIN resource_custom_access rca2 ON r.ref=rca2.resource AND rca2.user = ? AND (rca2.user_expires IS null or rca2.user_expires>now()) AND rca2.access<>2  ";
        array_push($sql_join->parameters,"i",$userref);
        $sql_join->sql  .= " LEFT OUTER JOIN resource_custom_access rca ON r.ref=rca.resource AND rca.usergroup = ? AND rca.access<>2 ";
        array_push($sql_join->parameters,"i",$usergroup);

        if ($sql_filter->sql != "") {$sql_filter->sql .= " AND ";}
        # If rca.resource is null, then no matching custom access record was found
        # If r.access is also 3 (custom) then the user is not allowed access to this resource.
        # Note that it's normal for null to be returned if this is a resource with non custom permissions (r.access<>3).
        $sql_filter->sql.=" NOT (rca.resource IS null AND r.access=3)";
        }

    # Join thumbs_display_fields to resource table
    $select="r.ref, r.resource_type, r.has_image, r.is_transcoding, r.creation_date, r.rating, r.user_rating, r.user_rating_count, r.user_rating_total, r.file_extension, r.preview_extension, r.image_red, r.image_green, r.image_blue, r.thumb_width, r.thumb_height, r.archive, r.access, r.colour_key, r.created_by, r.file_modified, r.file_checksum, r.request_count, r.new_hit_count, r.expiry_notification_sent, r.preview_tweaks, r.file_path, r.modified, r.file_size ";
    $sql_hitcount_select="r.hit_count";

    $modified_select=hook('modifyselect');
    $select.=$modified_select ? $modified_select : '';      // modify select hook 1

    $modified_select2=hook('modifyselect2');
    $select.=$modified_select2 ? $modified_select2 : '';    // modify select hook 2

    $select.=$return_disk_usage ? ',r.disk_usage' : '';      // disk usage

    # select group and user access rights if available, otherwise select null values so columns can still be used regardless
    # this makes group and user specific access available in the basic search query, which can then be passed through access functions
    # in order to eliminate many single queries.
    if (!checkperm("v") && !$access_override)
        {
        $select.=",rca.access group_access,rca2.access user_access ";
        }
    else
        {
        $select.=",null group_access, null user_access ";
        }

    # add 'joins' to select (only add fields if not returning the refs only)
    $joins=$return_refs_only===false||$include_fieldx===true ? get_resource_table_joins() : array();
    foreach($joins as $datajoin)
        {
        if(metadata_field_view_access($datajoin) || $datajoin == $GLOBALS["view_title_field"])
            {
            $select .= ", r.field{$datajoin} ";
            }
        }

    # Prepare SQL to add join table for all provided keywords

    $suggested=$keywords; # a suggested search
    $fullmatch=true;
    $c=0;
    $t = new PreparedStatementQuery();
    $t2 = new PreparedStatementQuery();
    $score="";
    $skipped_last=false;

    # Do not process if a numeric search is provided (resource ID)
    $keysearch=!($config_search_for_number && is_numeric($search));

    # Fetch a list of fields that are not available to the user - these must be omitted from the search.
    $hidden_indexed_fields=get_hidden_indexed_fields();

    // *******************************************************************************
    //
    //                                                                  START keywords
    //
    // *******************************************************************************
    $keywords_used = [];
    if ($keysearch)
        {
        for ($n=0;$n<count($keywords);$n++)
            {
            $canskip = false;
            $search_field_restrict="";
            $keyword=$keywords[$n];
            debug("do_search(): \$keyword = {$keyword}");
            $quoted_string=(substr($keyword,0,1)=="\""  || substr($keyword,0,2)=="-\"" ) && substr($keyword,-1,1)=="\"";
            $quoted_field_match=false;
            $field_short_name_specified=false;

            // Extra sql to search non-field data that used to be stored in resource_keyword e.g. resource type/resource contributor
            $non_field_keyword_sql = new PreparedStatementQuery();

            if($quoted_string && substr($keyword,1,strlen(FULLTEXT_SEARCH_PREFIX))==FULLTEXT_SEARCH_PREFIX)
                {
                // Full text search
                $fulltext_string = str_replace(FULLTEXT_SEARCH_QUOTES_PLACEHOLDER,"\"",substr($keyword,strlen(FULLTEXT_SEARCH_PREFIX)+2,-1));
                if(strpos($fulltext_string,"@") !== false) 
                    {
                    // There's an @ character in the fulltext search which InnoDB does not permit, so quote-wrap the search string 
                    $fulltext_string = "'\"".$fulltext_string."\"'";
                    }

                $freetextunion = new PreparedStatementQuery();
                $freetextunion->sql = " SELECT resource, [bit_or_condition] 1 AS score FROM resource_node rn LEFT JOIN node n ON n.ref=rn.node WHERE MATCH(name) AGAINST (? IN BOOLEAN MODE)";
                $freetextunion->parameters = ["s",$fulltext_string];
                if (count($hidden_indexed_fields) > 0)
                    {
                    $freetextunion->sql .= " AND n.resource_type_field NOT IN (" .  ps_param_insert(count($hidden_indexed_fields)) . ")";
                    $freetextunion->parameters = array_merge($freetextunion->parameters,ps_param_fill($hidden_indexed_fields,"i"));
                    }

                $sql_keyword_union[] = $freetextunion;
                $sql_keyword_union_aggregation[] = "BIT_OR(`keyword_[union_index]_found`) AS `keyword_[union_index]_found`";
                $sql_keyword_union_or[]=FALSE;
                $sql_keyword_union_criteria[] = "`h`.`keyword_[union_index]_found`";
                continue;
                }

            if($keyword == $search && is_int_loose($keyword) && $searchidmatch)
                {
                // Resource ID is no longer indexed, if search is just for a single integer then include this
                $non_field_keyword_sql->sql .= " UNION (SELECT " . (int)$keyword . " AS resource, [bit_or_condition] 1 AS score)";
                $canskip = true;
                }
            elseif (in_array(mb_strtolower($keyword),array_map("mb_strtolower",array_column($restypenames,"name"))))
                {
                // Resource type is no longer actually indexed but this will still honour the config by including in search
                $non_field_keyword_sql->sql .= " UNION (SELECT r.ref AS resource, [bit_or_condition] 1 AS score FROM resource r LEFT JOIN resource_type rt ON r.resource_type=rt.ref WHERE r.ref > 0 AND rt.name LIKE ?)";
                array_push($non_field_keyword_sql->parameters,"s",$keyword);
                $canskip = true;
                }
            if($index_contributed_by)
                {
                // Resource type is no longer actually indexed but this will still honour the config by including in search
                $matchusers = get_users(0,$keyword,"u.username",true);
                if(count($matchusers) > 0)
                    {
                    $non_field_keyword_sql->sql .= " UNION (SELECT r.ref AS resource, [bit_or_condition] 1 AS score FROM resource r WHERE r.created_by IN (" .  ps_param_insert(count($matchusers)). ") AND r.ref >0)";
                    $userparams = ps_param_fill(array_column($matchusers,"ref"),"i");
                    $non_field_keyword_sql->parameters = array_merge($non_field_keyword_sql->parameters,$userparams);
                    $canskip = true;
                    }
                }

            if(!$quoted_string || ($quoted_string && strpos($keyword,":")!==false)) // If quoted string with a field specified we first need to try and resolve it to a node instead of working out keyword positions etc.
                {
                if (substr($keyword,0,1)!="!" || substr($keyword,0,6)=="!empty")
                    {
                    $field=0;
                    $keywordprocessed=false;

                    if (strpos($keyword,":")!==false)
                        {
                        $field_short_name_specified=true;
                        $kw=explode(":",($quoted_string?substr($keyword,1,-1):$keyword),2);
                        # Fetch field info
                        global $fieldinfo_cache;
                        $fieldname=$kw[0];
                        $keystring=$kw[1];
                        debug("do_search(): \$fieldname = {$fieldname}");
                        debug("do_search(): \$keystring = {$keystring}");
                        if (isset($fieldinfo_cache[$fieldname]))
                            {
                            $fieldinfo=$fieldinfo_cache[$fieldname];
                            }
                        else
                            {
                            $fieldinfo = ps_query("SELECT ref, `type` FROM resource_type_field WHERE name = ?",["s",$fieldname],"schema");

                            // Checking for date from Simple Search will result with a fieldname like 'year' which obviously does not exist
                            if(0 === count($fieldinfo) && ('basicyear' == $kw[0] || 'basicmonth' == $kw[0] || 'basicday' == $kw[0]))
                                {
                                $fieldinfo = ps_query("SELECT ref, `type` FROM resource_type_field WHERE ref = ?",["i",$date_field], "schema");
                                }
                            if(0 === count($fieldinfo))
                                {
                                // Search may just happen to include a colon - treat the colon as a space and add to $keywords array to process separately
                                $addedkeywords = explode(":",$keyword);
                                $keywords = array_merge($keywords,$addedkeywords);
                                continue;
                                }
                            else
                                {
                                $fieldinfo=$fieldinfo[0];
                                $fieldinfo_cache[$fieldname]=$fieldinfo;
                                }
                            }
                        if(!metadata_field_view_access($fieldinfo['ref']))
                            {
                            // User can't search against a metadata field they don't have access to
                            return false;
                            }
                        }

                    //First try and process special keyword types
                    if ($field_short_name_specified && !$quoted_string && !$ignore_filters && isset($fieldinfo['type']) && in_array($fieldinfo['type'],$DATE_FIELD_TYPES))
                        {
                        // ********************************************************************************
                        // Date field keyword
                        // ********************************************************************************

                        global $datefieldinfo_cache;
                        if (isset($datefieldinfo_cache[$fieldname]))
                            {
                            $datefieldinfo=$datefieldinfo_cache[$fieldname];
                            }
                        else
                            {
                            $datefieldinfo=ps_query("SELECT ref FROM resource_type_field WHERE name = ? AND type IN (" . FIELD_TYPE_DATE_AND_OPTIONAL_TIME . "," . FIELD_TYPE_EXPIRY_DATE . "," . FIELD_TYPE_DATE . "," . FIELD_TYPE_DATE_RANGE . ")",["s",$fieldname], "schema");
                            $datefieldinfo_cache[$fieldname]=$datefieldinfo;
                            }

						if (count($datefieldinfo) && substr($keystring,0,5)!="range")
                            {
                            $c++;
                            $datefieldinfo=$datefieldinfo[0];
                            $datefield=$datefieldinfo["ref"];

                            $val=str_replace("n","_", $keystring);
                            $val=str_replace("|","-", $val);

                            if($fieldinfo['type']==FIELD_TYPE_DATE_RANGE)
                                {
                                // Find where the searched value is between the range values
                                $sql_join->sql .=" JOIN resource_node drrn" . $c . "s ON drrn" . $c . "s.resource=r.ref JOIN node drn" . $c . "s ON drn" . $c . "s.ref=drrn" . $c . "s.node AND drn" . $c . "s.resource_type_field = ? AND drn" . $c . "s.name >= ? JOIN resource_node drrn" . $c . "e ON drrn" . $c . "e.resource=r.ref JOIN node drn" . $c . "e ON drn" . $c . "e.ref=drrn" . $c . "e.node AND drn" . $c . "e.resource_type_field = ? AND DATE(drn" . $c . "e.name) <= ?";
                                array_push($sql_join->parameters,"i",$datefield,"i",$datefield,"s",$val,"s",$val);
                                }
                            else
                                {
                                $sql_join->sql .=" JOIN resource_node rnd" . $c . " ON rnd" . $c . ".resource=r.ref JOIN node dn" . $c . " ON dn" . $c . ".ref=rnd" . $c . ".node AND dn" . $c . ".resource_type_field = ?";
                                array_push($sql_join->parameters,"i",$datefield);

                                $sql_filter->sql .= ($sql_filter->sql != "" ? " AND " : "") . "dn" . $c . ".name like ?";
                                array_push($sql_filter->parameters,"s",$val . "%");
                                }


                            // Find where the searched value is LIKE the range values

                            }
                        elseif(in_array($kw[0],array("basicday","basicmonth","basicyear")))
                            {
                            $c++;
                            if(!isset($datefieldjoin))
                                {
                                // We only want to join once to the date_field
                                $sql_join->sql .=" JOIN resource_node rdnf" . $c . " ON rdnf" . $c . ".resource=r.ref JOIN node rdn" . $c . " ON rdnf" . $c  . ".node=rdn" . $c . ".ref AND rdn" . $c . ".resource_type_field = ?";
                                $datefieldjoin = $c;
                                array_push($sql_join->parameters,"i",$date_field);
                                }

                            if('basicday' == $kw[0])
                                {
                                $sql_filter->sql .= ($sql_filter->sql != "" ? " AND " : "") . "rdn" . $datefieldjoin . ".name like ? ";
                                array_push($sql_filter->parameters,"s","____-__-" . $keystring . "%");
                                $c++;
                                }
                            else if('basicmonth' == $kw[0])
                                {
                                $sql_filter->sql .= ($sql_filter->sql != "" ? " AND " : "") . "rdn" . $datefieldjoin . ".name like ? ";
                                array_push($sql_filter->parameters,"s","____-" . $keystring . "%");
                                $c++;
                                }
                            elseif('basicyear' == $kw[0])
                                {
                                $sql_filter->sql .= ($sql_filter->sql != "" ? " AND " : "") . "rdn" . $datefieldjoin . ".name like ? ";
                                array_push($sql_filter->parameters,"s",$keystring . "%");
                                $c++;
                                }
                            }
                        # Additional date range filtering
                        elseif (count($datefieldinfo) && substr($keystring,0,5)=="range")
                            {
                            $c++;
                            $rangefield=$datefieldinfo[0]["ref"];
                            $rangestring=substr($keystring,5);
                            if (strpos($rangestring,"start")!==FALSE )
                                {
                                $rangestartpos=strpos($rangestring,"start")+5;
                                $rangestart=str_replace(" ","-",substr($rangestring,$rangestartpos,strpos($rangestring,"end")?strpos($rangestring,"end")-$rangestartpos:10));
                                }
                            if (strpos($keystring,"end")!==FALSE )
                                {
                                $rangeend=str_replace(" ","-",$rangestring);
                                $rangeend=substr($rangeend,strpos($rangeend,"end")+3,10) . " 23:59:59";
                                }

                            // Find where the start value or the end value  is between the range values
                            if(isset($rangestart))
                                {
                                // Need to check for a date greater than the start date
                                $sql_join->sql .= " JOIN resource_node drrn" . $c . "s ON drrn" . $c . "s.resource=r.ref JOIN node drn" . $c . "s ON drn" . $c . "s.ref=drrn" . $c . "s.node AND drn" . $c . "s.resource_type_field = ? AND drn" . $c . "s.name>= ? ";
                                array_push($sql_join->parameters,"i",$fieldinfo['ref'],"s",$rangestart);
                                }
                            if(isset($rangeend))
                                {
                                // Need to check for a date earlier than the end date
                                $sql_join->sql .= " JOIN resource_node drrn" . $c . "e ON drrn" . $c . "e.resource=r.ref JOIN node drn" . $c . "e ON drn" . $c . "e.ref=drrn" . $c . "e.node AND drn" . $c . "e.resource_type_field = ? AND drn" . $c . "e.name <= ? ";
                                array_push($sql_join->parameters,"i",$fieldinfo['ref'],"s",$rangeend);
                                }
                            }
                        $keywordprocessed=true;
                        }
                    elseif ($field_short_name_specified && substr($keystring,0,8)=="numrange" && !$quoted_string && !$ignore_filters && isset($fieldinfo['type']) && $fieldinfo['type']==0)
                        {
                        // Text field numrange search ie mynumberfield:numrange1|1234 indicates that mynumberfield needs a numrange search for 1 to 1234.
                        $c++;
                        $rangefield=$fieldname;
                        $rangefieldinfo=ps_query("SELECT ref FROM resource_type_field WHERE name = ? AND type IN (0)", ["s",$fieldname], "schema");
                        $rangefieldinfo=$rangefieldinfo[0];
                        $rangefield=$rangefieldinfo["ref"];
                        $rangestring=substr($keystring,8);
                        $minmax=explode("|",$rangestring);$min=str_replace("neg","-",$minmax[0]);
                        if (isset($minmax[1]))
                            {
                            $max=str_replace("neg","-",$minmax[1]);
                            }
                        else
                            {
                            $max='';
                            }
                        if ($max!='' || $min !='')
                            {
                            // At least the min or max should be set
                            if ($max=='' || $min=='')
                                {
                                // if only one number is entered, do a direct search
                                if ($sql_filter->sql!="") {$sql_filter->sql .= " AND ";}
                                $sql_filter->sql .= "rnn" . $c . ".name = ? ";
                                array_push($sql_filter->parameters,"d",max($min,$max));
                                }
                            else
                                {
                                // else use min and max values as a range search
                                if ($sql_filter->sql!="") {$sql_filter->sql.=" AND ";}
                                $sql_filter->sql.="rnn" . $c . ".name >= ? ";
                                array_push($sql_filter->parameters,"d",$min);
                                if ($sql_filter->sql!="") {$sql_filter->sql.=" AND ";}
                                $sql_filter->sql.="rnn" . $c . ".name <= ? ";
                                array_push($sql_filter->parameters,"d",$max);
                                }
                            }

                        $sql_join->sql .=" JOIN resource_node rrn" . $c . " ON rrn" . $c . ".resource=r.ref LEFT JOIN node rnn" . $c . " ON rnn" . $c . ".ref=rrn" . $c . ".node AND rnn" . $c . ".resource_type_field = ?";

                        array_push($sql_join->parameters,"i",$rangefield);
						$keywordprocessed=true;
                        }
                    // Convert legacy fixed list field search to new format for nodes (@@NodeID)
                    else if($field_short_name_specified && !$ignore_filters && isset($fieldinfo['type']) && in_array($fieldinfo['type'], $FIXED_LIST_FIELD_TYPES))
                        {
                        // We've searched using a legacy format (ie. fieldShortName:keyword), try and convert it to @@NodeID
                        $field_nodes      = get_nodes($fieldinfo['ref'], null, false, true);

                        // Check if multiple nodes have been specified for an OR search
                        $keywords_expanded=explode(';',$keystring);
                        $nodeorcount = count($node_bucket);
                        foreach($keywords_expanded as $keyword_expanded)
                            {
                            debug("keyword_expanded:  " . $keyword_expanded);
                            $field_node_index = array_search(mb_strtolower(i18n_get_translated($keyword_expanded)), array_map('i18n_get_translated',array_map('mb_strtolower',array_column($field_nodes, 'name'))));
                            // Take the ref of the node and add it to the node_bucket as an OR
                            if(false !== $field_node_index)
                                {
                                $node_bucket[$nodeorcount][] = $field_nodes[$field_node_index]['ref'];
                                $quoted_field_match=true; // String has been resolved to a node so even if it is quoted we don't need to process it as a quoted string now
                                $keywordprocessed=true;
                                }
                            }
                        }

                     if($field_short_name_specified) // Need this also for string matching in a named text field
                        {
                        $keyword=$keystring;
                        $search_field_restrict=$fieldinfo['ref'];
                        }

                    if(!$quoted_string && !$keywordprocessed && !($field_short_name_specified && hook('customsearchkeywordfilter', null, array($kw)))) // Need this also for string matching in a named text field
                        {
                        // Normal keyword
                        //
                        // Searches all fields that the user has access to
                        // If ignoring field specifications then remove them.
                        $keywords_expanded=explode(';',$keyword);
                        $keywords_expanded_or=count($keywords_expanded) > 1;
                        # Omit resources containing this keyword?
                        $omit = false;
                        if (substr($keyword, 0, 1) == "-")
                            {
                            $omit = true;
                            $keyword = substr($keyword, 1);
                            }

                        # Search for resources with an empty field, ex: !empty18  or  !emptycaption
                        $empty = false;
                        if (substr($keyword, 0, 6) == "!empty")
                            {
                            $nodatafield = str_replace("!empty", "", $keyword);

                            if (!is_numeric($nodatafield))
                                {
                                $nodatafield = ps_value("SELECT ref value FROM resource_type_field WHERE name = ?", ["i",$nodatafield], "", "schema");
                                }

                            if ($nodatafield == "" || !is_numeric($nodatafield))
                                {
                                exit('invalid !empty search');
                                }
                            $empty = true;
                            }

                        if (in_array($keyword, $noadd)) # skip common words that are excluded from indexing
                            {
                            $skipped_last = true;
                            debug("do_search(): skipped common word: {$keyword}");
                            }
                        else
                            {
                            // ********************************************************************************
                            //                                                                 Handle wildcards
                            // ********************************************************************************

                            # Handle wildcards
                            $wildcards = array();
                            if (strpos($keyword, "*") !== false || $wildcard_always_applied)
                                {
                                if ($wildcard_always_applied && strpos($keyword, "*") === false)
                                    {
                                    # Suffix asterisk if none supplied and using $wildcard_always_applied mode.
                                    $keyword = $keyword . "*";
                                    }

                                # Keyword contains a wildcard. Expand.
                                global $wildcard_expand_limit;
                                $wildcards = ps_array("SELECT ref value FROM keyword WHERE keyword like ? ORDER BY hit_count DESC LIMIT " . (int)$wildcard_expand_limit,["s", str_replace("*", "%", $keyword)]);
                                }

                            $keyref = resolve_keyword(str_replace('*', '', $keyword),false,true,!$quoted_string); # Resolve keyword. Ignore any wildcards when resolving. We need wildcards to be present later but not here.
                            if ($keyref === false)
                                {
                                if($stemming)
                                    {
                                    // Attempt to find match for original keyword
                                    $keyref = resolve_keyword(str_replace('*', '', $keyword), false, true, false);
                                    }

                                if ($keyref === false)
                                    {
                                    if($keywords_expanded_or)
                                        {
                                        $alternative_keywords = array();
                                        foreach($keywords_expanded as $keyword_expanded)
                                            {
                                            $alternative_keyword_keyref = resolve_keyword($keyword_expanded, false, true, true);    
                                            if($alternative_keyword_keyref === false)
                                                {
                                                continue;
                                                }

                                            $alternative_keywords[] = $alternative_keyword_keyref;
                                            }

                                        if(count($alternative_keywords) > 0)
                                            {           
                                            // Multiple alternative keywords
                                            $alternative_keywords_sql = new PreparedStatementQuery();
                                            $alternative_keywords_sql->sql = " OR nk[union_index].keyword IN (" . ps_param_insert(count($alternative_keywords)) .")";
                                            $alternative_keywords_sql->parameters = ps_param_fill($alternative_keywords,"i");
                                            debug("do_search(): \$alternative_keywords_sql = {$alternative_keywords_sql->sql}, parameters = " . implode(",",$alternative_keywords_sql->parameters));
                                            }
                                        }
                                    else
                                        {
                                        // Check keyword for defined separators and if found each part of the value is added as a keyword for checking.
                                        $contains_separators = false;
                                        foreach ($config_separators as $separator)
                                            {
                                            if (strpos($keyword, $separator) !== false)
                                                {
                                                $contains_separators = true;
                                                }
                                            }
                                        if ($contains_separators === true)
                                            {
                                            $keyword_split = split_keywords($keyword);

                                            if($field_short_name_specified)
                                                {
                                                $keyword_split = array_map(prefix_value($fieldname.":"),$keyword_split);
                                                }
                                            $keywords = array_merge($keywords,$keyword_split);
                                            continue;
                                            }
                                        }
                                    }
                                }

                            if ($keyref === false && !$omit && !$empty && count($wildcards) == 0 && !$field_short_name_specified && !$canskip)
                                {
                                // ********************************************************************************
                                //                                                                     No wildcards
                                // ********************************************************************************

                                $fullmatch = false;
                                $soundex = resolve_soundex($keyword);
                                if ($soundex === false)
                                    {
                                    # No keyword match, and no keywords sound like this word. Suggest dropping this word.
                                    $suggested[$n] = "";
                                    } else
                                    {
                                    # No keyword match, but there's a word that sounds like this word. Suggest this word instead.
                                    $suggested[$n] = "<i>" . $soundex . "</i>";
                                    }
                                }
                            else
                                {
                                // ********************************************************************************
                                //                                                                  Found wildcards
                                // ********************************************************************************

                                // Multiple alternative keywords
                                $alternative_keywords_sql = new PreparedStatementQuery();
                                $alternative_keywords = array();
                                if($keywords_expanded_or)
                                    {
                                    foreach($keywords_expanded as $keyword_expanded)
                                        {
                                        $alternative_keyword_keyref = resolve_keyword($keyword_expanded, false, true, true);

                                        if($alternative_keyword_keyref === false)
                                            {
                                            continue;
                                            }

                                        $alternative_keywords[] = $alternative_keyword_keyref;
                                        }

                                    if(count($alternative_keywords) > 0)
                                        {
                                        $alternative_keywords_sql->sql = " OR nk[union_index].keyword IN (" . ps_param_insert(count($alternative_keywords)) .")";
                                        $alternative_keywords_sql->parameters = ps_param_fill($alternative_keywords,"i");
                                        debug("do_search(): \$alternative_keywords_sql = {$alternative_keywords_sql->sql}, parameters = " . implode(",",$alternative_keywords_sql->parameters));
                                        }
                                    }

                                if ($keyref === false)
                                    {
                                    # make a new keyword
                                    $keyref = resolve_keyword(str_replace('*', '', $keyword), true,true,false);
                                    }
                                # Key match, add to query.
                                $c++;

                                $relatedsql = new PreparedStatementQuery();

                                # Add related keywords
                                $related = get_related_keywords($keyref);
                                if($stemming)
                                    {
                                    # Need to ensure we include related keywords for original string
                                    $original_keyref = resolve_keyword(str_replace('*', '', $keyword), false, true, false);
                                    if($original_keyref && $original_keyref !== $keyref)
                                        {
                                        $original_related = get_related_keywords($original_keyref);
                                        if(count($original_related)>0)
                                            {
                                            $original_related_kws = ps_array("SELECT keyword AS `value` FROM keyword WHERE ref IN (" . ps_param_insert(count($original_related)) . ")",ps_param_fill($original_related,"i"));

                                            $extra_related = array();
                                            foreach($original_related_kws as $orig_related_kw)
                                                {
                                                $extrakeyword = GetStem(trim($orig_related_kw));
                                                $extra_related[] = resolve_keyword($extrakeyword, true, false, false);
                                                }
                                            $related = array_merge($related, $extra_related);
                                            }
                                        }
                                    }

                                # Merge wildcard expansion with related keywords
                                $related = array_merge($related, $wildcards);
                                if (count($related) > 0)
                                    {
                                    $relatedsql->sql = " OR (nk[union_index].keyword IN (" . ps_param_insert(count($related)) . ")";
                                    $relatedsql->parameters = ps_param_fill($related,"i");

                                    if ($field_short_name_specified && isset($fieldinfo['ref']))
                                        {
                                        $relatedsql->sql .= " AND nk[union_index].node IN (SELECT ref FROM node WHERE resource_type_field = ? )";
                                        $relatedsql->parameters[] = "i";
                                        $relatedsql->parameters[] = $fieldinfo['ref'];
                                        }
                                    $relatedsql->sql .= ")";
                                    }

                                # Form join
                                $sql_exclude_fields = hook("excludefieldsfromkeywordsearch");

                                if ($omit)
                                    {
                                    # Exclude matching resources from query (omit feature)
                                    if ($sql_filter->sql != "")
                                        {
                                        $sql_filter->sql .= " AND ";
                                        }

                                    // ----- check that keyword does not exist via resource_node->node_keyword relationship -----

                                    $sql_filter->sql .= "`r`.`ref` NOT IN (SELECT `resource` FROM `resource_node` JOIN `node_keyword` ON `resource_node`.`node`=`node_keyword`.`node`" .
                                        " WHERE `resource_node`.`resource`=`r`.`ref` AND `node_keyword`.`keyword` = ?)";
                                    array_push($sql_filter->parameters,"i",$keyref);
                                    }
                                else
                                    # Include in query
                                    {
                                    // --------------------------------------------------------------------------------
                                    // Start of normal union for resource keywords
                                    // --------------------------------------------------------------------------------

                                    // // these restrictions apply to both !empty searches as well as normal keyword searches (i.e. both branches of next if statement)
                                    $union_restriction_clause = new PreparedStatementQuery();
                                    $skipfields = array();

                                    if (!empty($sql_exclude_fields))
                                        {
                                        $union_restriction_clause->sql .= " AND nk[union_index].node NOT IN (SELECT ref FROM node WHERE resource_type_field IN (" . ps_param_insert(count($sql_exclude_fields)) .  "))";
                                        $union_restriction_clause->parameters = array_merge($union_restriction_clause->parameters,ps_param_fill($sql_exclude_fields,"i"));

                                        $skipfields = explode(",",str_replace(array("'","\""),"",$sql_exclude_fields));
                                        }

                                    if (count($hidden_indexed_fields) > 0)
                                        {
                                        $union_restriction_clause->sql .= " AND nk[union_index].node NOT IN (SELECT ref FROM node WHERE node.resource_type_field IN (" .  ps_param_insert(count($hidden_indexed_fields)) . "))";
                                        $union_restriction_clause->parameters = array_merge($union_restriction_clause->parameters,ps_param_fill($hidden_indexed_fields,"i"));
                                        $skipfields = array_merge($skipfields,$hidden_indexed_fields);
                                        }
                                    if (isset($search_field_restrict) && $search_field_restrict!="")
                                        {
                                        // Search is looking for a keyword in a specified field
                                        $union_restriction_clause->sql .= " AND nk[union_index].node IN (SELECT ref FROM node WHERE node.resource_type_field = ?)";
                                        $union_restriction_clause->parameters = array_merge($union_restriction_clause->parameters,["i",$search_field_restrict]);
                                        }
                                    if ($empty)  // we are dealing with a special search checking if a field is empty
                                        {
                                        // First check user can see this field
                                        if(in_array($nodatafield,$skipfields))
                                            {
                                            // Not permitted to check this field, return false
                                            return false;
                                            }

                                        $restypesql = new PreparedStatementQuery();
                                        $nodatafieldinfo = get_resource_type_field($nodatafield);
                                        if ($nodatafieldinfo["global"] != 1)
                                            {
                                            $nodatarestypes = explode(",",(string)$nodatafieldinfo["resource_types"]);
                                            $restypesql->sql = " AND r[union_index].resource_type IN (" . ps_param_insert(count($nodatarestypes)) . ") ";
                                            $restypesql->parameters = ps_param_fill($nodatarestypes,"i");
                                            }

                                        // Check that nodes are empty
                                        $union = new PreparedStatementQuery();
                                        $union->sql = "SELECT ref AS resource, [bit_or_condition] 1 AS score FROM resource r[union_index] WHERE r[union_index].ref NOT IN
                                        (
                                        SELECT rn.resource FROM
                                        node n
                                        RIGHT JOIN resource_node rn ON rn.node=n.ref
                                        WHERE  n.resource_type_field = ? $restypesql->sql
                                        GROUP BY rn.resource
                                        )";
                                        $union->parameters = array_merge(["i",$nodatafield],$restypesql->parameters);
                                        $sql_keyword_union[] = $union;
                                        $sql_keyword_union_criteria[] = "`h`.`keyword_[union_index]_found`";
                                        $sql_keyword_union_aggregation[] = "BIT_OR(`keyword_[union_index]_found`) AS `keyword_[union_index]_found`";
                                        $sql_keyword_union_or[]= FALSE;
                                        }
                                    else  // we are dealing with a standard keyword match
                                        {
                                        // ----- resource_node -> node_keyword sub query -----
                                        $union = new PreparedStatementQuery();

                                        $union->sql = " SELECT resource, [bit_or_condition] hit_count AS score
                                                          FROM resource_node rn[union_index]
                                                         WHERE rn[union_index].node IN
                                                               (SELECT node
                                                                  FROM `node_keyword` nk[union_index]
                                                                 WHERE ((nk[union_index].keyword = ? " . $relatedsql->sql .") ".  $union_restriction_clause->sql . ")" .
                                                                 ($alternative_keywords_sql->sql != "" ? ($alternative_keywords_sql->sql . $union_restriction_clause->sql) : "" ) .
                                                    ") GROUP BY resource " .
                                                       ($non_field_keyword_sql->sql != "" ? $non_field_keyword_sql->sql : "") ;

                                        $union->parameters = array_merge(["i",$keyref],$relatedsql->parameters,$union_restriction_clause->parameters);
                                        if($alternative_keywords_sql->sql != "")
                                            {
                                            $union->parameters = array_merge($union->parameters,$alternative_keywords_sql->parameters,$union_restriction_clause->parameters);
                                            }
                                        if($non_field_keyword_sql->sql != "")
                                            {
                                            $union->parameters = array_merge($union->parameters,$non_field_keyword_sql->parameters);
                                            }


                                        $sql_keyword_union[] = $union;

                                        // ---- end of resource_node -> node_keyword sub query -----
                                        $sql_keyword_union_criteria[] = "`h`.`keyword_[union_index]_found`";
                                        $sql_keyword_union_aggregation[] = "BIT_OR(`keyword_[union_index]_found`) AS `keyword_[union_index]_found`";

                                        $sql_keyword_union_or[]=$keywords_expanded_or;

                                        // Log this
                                        if($stats_logging && !$go)
                                            {
                                            $keywords_used[] = $keyref;
                                            }
                                        } // End of standard keyword match
                                    } // end if not omit
                                } // end found wildcards
                            $skipped_last = false;
                            } // end handle wildcards
                        } // end normal keyword
                    } // end of check if special search
                } // End of if not quoted string
            if ($quoted_string && !$quoted_field_match)
                {
                $quotedfieldid="";
				// This keyword is a quoted string, split into keywords but don't preserve quotes this time
                 if ($field_short_name_specified && isset($fieldinfo['ref']))
                    {
                    // We have already parsed the keyword when looking for a node, get string and then filter on this field
                    $quotedkeywords=split_keywords($keystring);
                    $quotedfieldid= $fieldinfo['ref'];
                    }
                else
                    {
                    $quotedkeywords=split_keywords(substr($keyword,1,-1));
                    }
				$omit = false;
                if (substr($keyword, 0, 1) == "-")
					{
					$omit = true;
					$keyword = substr($keyword, 1);
					}

                $qk=1; // Set the counter to the first keyword
                $last_key_offset=1;
                $fixedunion = new PreparedStatementQuery();
                $fixedunioncondition = new PreparedStatementQuery();
				foreach($quotedkeywords as $quotedkeyword)
					{
					global $noadd, $wildcard_always_applied;
					if (in_array($quotedkeyword, $noadd)) # skip common words that are excluded from indexing
						{
						# Support skipped keywords - if the last keyword was skipped (listed in $noadd), increase the allowed position from the previous keyword. Useful for quoted searches that contain $noadd words, e.g. "black and white" where "and" is a skipped keyword.
						++$last_key_offset;
						}
					else
						{
						$keyref = resolve_keyword($quotedkeyword, false,true,false); # Resolve keyword.
                        if ($keyref === false)
                            {
                            # make a new keyword
                            $keyref = resolve_keyword($quotedkeyword, true,true,false);
                            }

                        $union_restriction_clause = new PreparedStatementQuery();

                        if (!empty($sql_exclude_fields))
                            {
                            $union_restriction_clause->sql .= " AND nk_[union_index]_" . $qk . ".node NOT IN (SELECT ref FROM node WHERE resource_type_field IN (" . ps_param_insert(count($sql_exclude_fields)) . "))";
                            $union_restriction_clause->parameters = array_merge($union_restriction_clause->parameters,ps_param_fill($sql_exclude_fields,"i"));
                            }

                        if (count($hidden_indexed_fields) > 0)
                            {
                            $union_restriction_clause->sql .= " AND nk_[union_index]_" . $qk . ".node NOT IN (SELECT ref FROM node WHERE resource_type_field IN (" .  ps_param_insert(count($hidden_indexed_fields)) . "))";
                            $union_restriction_clause->parameters = array_merge($union_restriction_clause->parameters,ps_param_fill($hidden_indexed_fields,"i"));
                            }

                        if ($quotedfieldid != "")
                            {
                            $union_restriction_clause->sql .= " AND nk_[union_index]_" . $qk . ".node IN (SELECT ref FROM node WHERE resource_type_field = ? )";
                            $union_restriction_clause->parameters = array_merge($union_restriction_clause->parameters,["i",$quotedfieldid]);
                            }

                        if ($qk==1)
                            {
                            // Add code to find matching nodes in resource_node
                            $fixedunion->sql = " SELECT rn_[union_index]_" . $qk . ".resource, [bit_or_condition] rn_[union_index]_" . $qk . ".hit_count AS score FROM resource_node rn_[union_index]_" . $qk .
                                " LEFT OUTER JOIN `node_keyword` nk_[union_index]_" . $qk . " ON rn_[union_index]_" . $qk . ".node=nk_[union_index]_" . $qk . ".node AND (nk_[union_index]_" . $qk . ".keyword = ? " .  ")";
                            $fixedunion->parameters = array_merge($fixedunion->parameters,["i",$keyref]);

                            $fixedunioncondition->sql = "nk_[union_index]_" . $qk . ".keyword = ? " . $union_restriction_clause->sql;
                            $fixedunioncondition->parameters = array_merge(["i",$keyref], $union_restriction_clause->parameters);
                            }
                        else
                            {
                            # For keywords other than the first one, check the position is next to the previous keyword.
                            # Also check these occurances are within the same field.
                            $fixedunion->sql .=" JOIN `node_keyword` nk_[union_index]_" . $qk . " ON nk_[union_index]_" . $qk . ".node = nk_[union_index]_" . ($qk-1) . ".node AND nk_[union_index]_" . $qk . ".keyword = ? AND  nk_[union_index]_" . $qk . ".position=nk_[union_index]_" . ($qk-1) . ".position+" . $last_key_offset ;
                            array_push($fixedunion->parameters,"i",$keyref);
                            }

                        $last_key_offset=1;
                        $qk++;
                        } // End of if keyword not excluded (not in $noadd array)
                    } // End of each keyword in quoted string

                if(trim($fixedunioncondition->sql) != "")
                    {
                    if($omit)# Exclude matching resources from query (omit feature)
                        {
                        if ($sql_filter->sql != "")
                            {
                            $sql_filter->sql .= " AND ";
                            }

                        $sql_filter->sql .= str_replace("[bit_or_condition]",""," r.ref NOT IN (SELECT resource FROM (" . $fixedunion->sql . " WHERE " . $fixedunioncondition->sql . ") qfilter[union_index]) "); # Instead of adding to the union, filter out resources that do contain the quoted string.


                        $sql_filter->parameters = array_merge($sql_filter->parameters,$fixedunion->parameters,$fixedunioncondition->parameters);
                        }
                    elseif (is_a($fixedunion,"PreparedStatementQuery"))
                        {
                        $addunion = new PreparedStatementQuery();
                        $addunion->sql = $fixedunion->sql . " WHERE " . $fixedunioncondition->sql . " GROUP BY resource ";
                        $addunion->parameters = array_merge($fixedunion->parameters,$fixedunioncondition->parameters);
                        $sql_keyword_union[] = $addunion;
                        $sql_keyword_union_aggregation[] = "BIT_OR(`keyword_[union_index]_found`) AS `keyword_[union_index]_found` ";
                        $sql_keyword_union_or[]=FALSE;
                        $sql_keyword_union_criteria[] = "`h`.`keyword_[union_index]_found`";
                        }
                    }
                $c++;
                }
            } // end keywords expanded loop
        } // end keysearch if

    // *******************************************************************************
    //
    //                                                                    END keywords
    //
    // *******************************************************************************


    // *******************************************************************************
    //                                                      order by RESOURCE TYPE
    // *******************************************************************************

    $sql_join->sql .= " JOIN resource_type AS rty ON r.resource_type = rty.ref ";

    $select .= ", rty.order_by ";

    // *******************************************************************************
    //                                                       START add node conditions
    // *******************************************************************************

    $node_bucket_sql="";
    $rn=0;
    $node_hitcount="";
    foreach($node_bucket as $node_bucket_or)
        {
        if($category_tree_search_use_and_logic)
            {
            foreach($node_bucket_or as $node_bucket_and)
                {
                $sql_join->sql .= ' JOIN `resource_node` rn' . $rn . ' ON r.`ref`=rn' . $rn . '.`resource` AND rn' . $rn . '.`node` = ?';
                array_push($sql_join->parameters,"i",$node_bucket_and);
                $node_hitcount .= (($node_hitcount != "") ? " +" : "") . "rn" . $rn . ".hit_count";
                $rn++;
                }
            }
        else
            {
            $sql_join->sql .= ' JOIN `resource_node` rn' . $rn . ' ON r.`ref`=rn' . $rn . '.`resource` AND rn' . $rn . '.`node` IN (' . ps_param_insert(count($node_bucket_or)) . ')';
            $sql_join->parameters = array_merge($sql_join->parameters,ps_param_fill($node_bucket_or,"i"));
            $node_hitcount .= (($node_hitcount!="")?" +":"") . "rn" . $rn . ".hit_count";
            $rn++;
            }
        }
    if ($node_hitcount!="")
        {
        $sql_hitcount_select = "(SUM(" . $sql_hitcount_select . ") + SUM(" . $node_hitcount . ")) ";
        }


    $select .= ", " . $sql_hitcount_select . " total_hit_count";

    $sql_filter->sql  = $node_bucket_sql . $sql_filter->sql;

    if(count($node_bucket_not)>0)
        {
        $sql_filter->sql = 'NOT EXISTS (SELECT `resource`, node FROM `resource_node` WHERE r.ref=`resource` AND `node` IN (' .
        ps_param_insert(count($node_bucket_not)) . ')) AND ' . $sql_filter->sql;
        $sql_filter->parameters = array_merge(ps_param_fill($node_bucket_not,"i"),$sql_filter->parameters);
        }

    // *******************************************************************************
    //                                                         END add node conditions
    // *******************************************************************************

    # Could not match on provided keywords? Attempt to return some suggestions.
    if ($fullmatch==false)
        {
        if ($suggested==$keywords)
            {
            # Nothing different to suggest.
            debug("No alternative keywords to suggest.");
            return "";
            }
        else
            {
            # Suggest alternative spellings/sound-a-likes
            $suggest="";
            if (strpos($search,",")===false)
                {
                $suggestjoin=" ";
                }
            else
                {
                $suggestjoin=", ";
                }

            foreach ($suggested as $suggestion)
                {
                if ($suggestion != "")
                    {
                    if ($suggest!="")
                        {
                        $suggest.=$suggestjoin;
                        }
                    $suggest.=$suggestion;
                    }
                }
            debug ("Suggesting $suggest");
            return $suggest;
            }
        }

    hook("additionalsqlfilter");
    hook("parametricsqlfilter", '', array($search));

    // *******************************************************************************
    //
    //                                                                 START filtering
    //
    // *******************************************************************************

    if(strlen(trim((string) $usersearchfilter)) > 0
        && !is_numeric($usersearchfilter)
        && (
            (trim($userdata[0]["search_filter_override"]) != "" && $userdata[0]["search_filter_o_id"] != -1)
            ||
            (trim($userdata[0]["search_filter"]) != "" && $userdata[0]["search_filter_id"] != -1)
            )
        )
        {
        // Migrate old style filter unless previously failed attempt
        $migrateresult = migrate_filter($usersearchfilter);
        $notification_users = get_notification_users();
        if(is_numeric($migrateresult))
            {
            message_add(array_column($notification_users,"ref"), $lang["filter_migrate_success"] . ": '" . $usersearchfilter . "'",generateURL($baseurl . "/pages/admin/admin_group_management_edit.php",array("ref"=>$usergroup)));

            // Successfully migrated - now use the new filter
            if(isset($userdata[0]["search_filter_override"]) && $userdata[0]["search_filter_override"]!='')
                {
                // This was a user override filter - update the user record
                ps_query("UPDATE user SET search_filter_o_id = ? WHERE ref = ?",["i",$migrateresult,"i",$userref]);
                }
            else
                {
                ps_query("UPDATE usergroup SET search_filter_id = ? WHERE ref = ?",["i",$migrateresult,"i",$usergroup]);
                }
            $usersearchfilter = $migrateresult;
            debug("FILTER MIGRATION: Migrated filter - new filter id#" . $usersearchfilter);
            }
        elseif(is_array($migrateresult))
            {
            debug("FILTER MIGRATION: Error migrating filter: '" . $usersearchfilter . "' - " . implode('\n' ,$migrateresult));
            // Error - set flag so as not to reattempt migration and notify admins of failure
            if(isset($userdata[0]["search_filter_override"]) && $userdata[0]["search_filter_override"]!='')
                {
                ps_query("UPDATE user SET search_filter_o_id='-1' WHERE ref = ?",["i",$userref]);
                }
            else
                {
                ps_query("UPDATE usergroup SET search_filter_id='-1' WHERE ref = ?",["i",$usergroup]);
                }

            message_add(array_column($notification_users,"ref"), $lang["filter_migration"] . " - " . $lang["filter_migrate_error"] . ": <br />" . implode('\n' ,$migrateresult),generateURL($baseurl . "/pages/admin/admin_group_management_edit.php",array("ref"=>$usergroup)));
            }
        }
    // Old text filters are no longer supported
    if (is_int_loose($usersearchfilter) && $usersearchfilter > 0)
        {
        $search_filter_sql = get_filter_sql($usersearchfilter);
        if (!$search_filter_sql)
            {
            exit($lang["error_search_filter_invalid"]);
            }
        if (is_a($search_filter_sql,"PreparedStatementQuery"))
            {
            if ($sql_filter->sql != "")
                {$sql_filter->sql .= " AND ";}
            $sql_filter->sql .=  $search_filter_sql->sql;
            $sql_filter->parameters = array_merge($sql_filter->parameters,$search_filter_sql->parameters);
            }
        }

    if ($editable_only)
		{
        if(strlen(trim($usereditfilter??"")) > 0
            && !is_numeric($usereditfilter)
            && trim($userdata[0]["edit_filter"]) != ""
            && $userdata[0]["edit_filter_id"] != -1
        )
            {
            // Migrate unless marked not to due to failure
            $usereditfilter = edit_filter_to_restype_permission($usereditfilter, $usergroup, $userpermissions);
            if(trim($usereditfilter) !== "")
                {
                $migrateresult = migrate_filter($usereditfilter);
                }
            else
                {
                $migrateresult = 0; // filter was only for resource type, hasn't failed but no need to migrate again
                ps_query("UPDATE usergroup SET edit_filter='' WHERE ref = ?",["i",$usergroup]);
                }
            if(is_numeric($migrateresult))
                {
                debug("Migrated . " . $migrateresult);
                // Successfully migrated - now use the new filter
                ps_query("UPDATE usergroup SET edit_filter_id = ? WHERE ref = ?",["i",$migrateresult,"i",$usergroup]);
                debug("FILTER MIGRATION: Migrated edit filter - '" . $usereditfilter . "' filter id#" . $migrateresult);
                $usereditfilter = $migrateresult;
                }
            elseif(is_array($migrateresult))
                {
                debug("FILTER MIGRATION: Error migrating filter: '" . $usersearchfilter . "' - " . implode('\n' ,$migrateresult));
                // Error - set flag so as not to reattempt migration and notify admins of failure
                ps_query("UPDATE usergroup SET edit_filter_id='-1' WHERE ref=?",["i",$usergroup]);
                $notification_users = get_notification_users();
                message_add(array_column($notification_users,"ref"), $lang["filter_migration"] . " - " . $lang["filter_migrate_error"] . ": <br />" . implode('\n' ,$migrateresult),generateURL($baseurl . "/pages/admin/admin_group_management_edit.php",array("ref"=>$usergroup)));
                }
            }

        if (is_numeric($usereditfilter) && $usereditfilter > 0)
            {
            $edit_filter_sql = get_filter_sql($usereditfilter);
            if (is_a($edit_filter_sql,"PreparedStatementQuery"))
                {
                if ($sql_filter->sql != "")
                    {
                    $sql_filter->sql .= " AND ";
                    }
                $sql_filter->sql .=  $edit_filter_sql->sql;
                $sql_filter->parameters = array_merge($sql_filter->parameters,$edit_filter_sql->parameters);
                }
            }
        }

    $userownfilter=hook("userownfilter");
    if ($userownfilter)
        {
        $sql_join->sql = $userownfilter;
        }

    // *******************************************************************************
    //
    //                                                                   END filtering
    //
    // *******************************************************************************

    # Handle numeric searches when $config_search_for_number=false, i.e. perform a normal search but include matches for resource ID first
    global $config_search_for_number;
    if (!$config_search_for_number && is_int_loose($search))
        {
        # Always show exact resource matches first.
        $order_by="(r.ref='" . $search . "') desc," . $order_by;
        }

    # ---------------------------------------------------------------
    # Keyword union assembly.
    # Use UNIONs for keyword matching instead of the older JOIN technique - much faster
    # Assemble the new join from the stored unions
    # ---------------------------------------------------------------
    if (count($sql_keyword_union)>0)
        {
        $union_sql_arr = [];
        $union_sql_params = [];

        for($i=1; $i<=count($sql_keyword_union); $i++)
            {
            $bit_or_condition="";
            for ($y=1; $y<=count($sql_keyword_union); $y++)
                {
                if ($i==$y)
                    {
                    $bit_or_condition .= " TRUE AS `keyword_{$y}_found`, ";
                    }
                else
                    {
                    $bit_or_condition .= " FALSE AS `keyword_{$y}_found`,";
                    }
                }
            $sql_keyword_union[($i-1)]->sql=str_replace('[bit_or_condition]',$bit_or_condition,$sql_keyword_union[($i-1)]->sql);
            $sql_keyword_union[($i-1)]->sql=str_replace('[union_index]',$i,$sql_keyword_union[($i-1)]->sql);
            $sql_keyword_union[($i-1)]->sql=str_replace('[union_index_minus_one]',($i-1),$sql_keyword_union[($i-1)]->sql);

            $union_sql_arr[] = $sql_keyword_union[$i-1]->sql;
            $union_sql_params = array_merge($union_sql_params,$sql_keyword_union[$i-1]->parameters);
            }

        for($i=1; $i<=count($sql_keyword_union_criteria); $i++)
            {
            $sql_keyword_union_criteria[($i-1)]=str_replace('[union_index]',$i,$sql_keyword_union_criteria[($i-1)]);
            $sql_keyword_union_criteria[($i-1)]=str_replace('[union_index_minus_one]',($i-1),$sql_keyword_union_criteria[($i-1)]);
            }

        for($i=1; $i<=count($sql_keyword_union_aggregation); $i++)
            {
            $sql_keyword_union_aggregation[($i-1)]=str_replace('[union_index]',$i,$sql_keyword_union_aggregation[($i-1)]);
            }

        $sql_join->sql .= " JOIN (
            SELECT resource,sum(score) AS score,
            " . join(", ", $sql_keyword_union_aggregation) . " from
            (" . join(" union ", $union_sql_arr) . ") AS hits GROUP BY resource) AS h ON h.resource=r.ref ";

        $sql_join->parameters = array_merge($sql_join->parameters,$union_sql_params);
        if ($sql_filter->sql != "")
            {
            $sql_filter->sql .= " AND ";
            }

        if(count($sql_keyword_union_or)!=count($sql_keyword_union_criteria))
            {
            debug("Search error - union criteria mismatch");
            return "ERROR";
            }

        $sql_filter->sql.="(";

        for($i=0; $i<count($sql_keyword_union_or); $i++)
            {
            // Builds up the string of conditions that will be added when joining to the $sql_keyword_union_criteria
            if($i==0)
                {
                $sql_filter->sql.=$sql_keyword_union_criteria[$i];
                continue;
                }

            if($sql_keyword_union_or[$i]!=$sql_keyword_union_or[$i-1])
                {
                $sql_filter->sql.=') AND (' . $sql_keyword_union_criteria[$i];
                continue;
                }

            if($sql_keyword_union_or[$i])
                {
                $sql_filter->sql.=' OR ';
                }
            else
                {
                $sql_filter->sql.=' AND ';
                }

            $sql_filter->sql.=$sql_keyword_union_criteria[$i];
            }

        $sql_filter->sql.=")";

        # Use amalgamated resource_keyword hitcounts for scoring (relevance matching based on previous user activity)
        $score="h.score";
        }

    # Can only search for resources that belong to featured collections. Doesn't apply to user's special upload collection to allow for upload then edit mode.
    $upload_collection = '!collection' . (0 - $userref);
    if(checkperm("J") && $search != $upload_collection)
        {
        $collection_join = " JOIN collection_resource AS jcr ON jcr.resource = r.ref JOIN collection AS jc ON jcr.collection = jc.ref";
        $collection_join .= featured_collections_permissions_filter_sql("AND", "jc.ref",true);

        $sql_join->sql = $collection_join . $sql_join->sql;
        }

    # --------------------------------------------------------------------------------
    # Special Searches (start with an exclamation mark)
    # --------------------------------------------------------------------------------

    $special_results=search_special($search,$sql_join,$fetchrows,$sql_prefix,$sql_suffix,$order_by,$orig_order,$select,$sql_filter,$archive,$return_disk_usage,$return_refs_only, $returnsql);
    if ($special_results!==false)
        {
        log_keyword_usage($keywords_used, $special_results);
        return $special_results;
        }

    # -------------------------------------------------------------------------------------
    # Standard Searches
    # -------------------------------------------------------------------------------------

    # We've reached this far without returning.
    # This must be a standard (non-special) search.

    # Construct and perform the standard search query.
    $sql = new PreparedStatementQuery();
    if ($sql_filter->sql!="")
        {
        if ($sql->sql!="")
            {
            $sql->sql .= " AND ";
            }
        $sql->sql .= $sql_filter->sql;
        $sql->parameters = array_merge($sql->parameters, $sql_filter->parameters);
        }

    # Append custom permissions
    $t->sql .= $sql_join->sql;
    $t->parameters = array_merge($t->parameters, $sql_join->parameters);

    if ($score=="")
        {
        $score=$sql_hitcount_select;
        } # In case score hasn't been set (i.e. empty search)

    if (($t2->sql != "") && ($sql->sql != ""))
        {
        $sql->sql = " AND " . $sql->sql;
        }

    # Compile final SQL
    $results_sql = new PreparedStatementQuery();
    $results_sql->sql = $sql_prefix . "SELECT distinct $score score, $select FROM resource r" . $t->sql . " WHERE " . $t2->sql . $sql->sql . " GROUP BY r.ref, user_access, group_access ORDER BY " . $order_by . $sql_suffix;
    $results_sql->parameters = array_merge($t->parameters,$t2->parameters,$sql->parameters);

    # Debug
    debug('$results_sql=' . $results_sql->sql . ", parameters: " . implode(",",$results_sql->parameters));

    setup_search_chunks($fetchrows, $chunk_offset, $search_chunk_size);

    if($return_refs_only)
        {
        # Execute query but only ask for ref columns back from ps_query();
        # We force verbatim query mode on (and restore it afterwards) as there is no point trying to strip slashes etc. just for a ref column
        global $mysql_verbatim_queries;
        $mysql_vq=$mysql_verbatim_queries;
        $mysql_verbatim_queries=true;

        if($returnsql)
            {
            return $results_sql;
            }
        $count_sql = clone($results_sql);
        $count_sql->sql = str_replace("ORDER BY " . $order_by,"",$count_sql->sql);
        $result = sql_limit_with_total_count($results_sql, $search_chunk_size, $chunk_offset, true, $count_sql);
        
        if(is_array($fetchrows))
            {
            // Return without converting into the legacy padded array
            log_keyword_usage($keywords_used, $result);
            return $result;
            }
        
        $resultcount = $result["total"]  ?? 0;
        if ($resultcount>0 & count($result["data"]) > 0)
            {
            $result = array_map(function($val){return(["ref"=>$val["ref"]]);}, $result["data"]);
            }
        $mysql_verbatim_queries=$mysql_vq;
        log_keyword_usage($keywords_used, $result);
        return $result;
        }
    else
        {
        # Execute query as normal
        if($returnsql)
            {
            return $results_sql;
            }
        $count_sql = clone($results_sql);
        $count_sql->sql = str_replace("ORDER BY " . $order_by,"",$count_sql->sql);
        $result = sql_limit_with_total_count($results_sql, $search_chunk_size, $chunk_offset, true, $count_sql);
        }

    if(is_array($fetchrows))
        {
        // Return without converting into the legacy padded array
        log_keyword_usage($keywords_used, $result);
        return $result;
        }
    $resultcount = $result["total"]  ?? 0;
    if ($resultcount>0 & count($result["data"]) > 0)
        {
        $result = $result['data'];
        if($search_chunk_size !== -1)
            {
            // Only perform legacy padding of results if not all rows have been requested or total may be incorrect
            $diff = $resultcount - count($result);
            while($diff > 0)
                {
                $result = array_merge($result, array_fill(0,($diff<1000000?$diff:1000000),0));
                $diff-=1000000;
                }
            }
        hook("beforereturnresults","",array($result, $archive));
        log_keyword_usage($keywords_used, $result);
        return $result;
        }
    else
        {
        $result =[];
        }

    hook('zero_search_results');

    // No suggestions for field-specific searching
    if (strpos($search,":")!==false)
        {
        return "";
        }

    # All keywords resolved OK, but there were no matches
    # Remove keywords, least used first, until we get results.
    $lsql = new PreparedStatementQuery();
    $omitmatch=false;
    $params=array();

    for ($n=0;$n<count($keywords);$n++)
        {
        if (substr($keywords[$n],0,1)=="-")
            {
            $omitmatch=true;
            $omit=$keywords[$n];
            }
        if ($lsql->sql != "")
            {
            $lsql->sql .= " OR ";
            }
        $lsql->sql .= "keyword = ?";
        array_push($lsql->parameters,"i",$keywords[$n]);
        }

    if ($omitmatch)
        {
        return trim_spaces(str_replace(" " . $omit . " "," "," " . join(" ",$keywords) . " "));
        }
    if ($lsql->sql != "")
        {
        $least=ps_value("SELECT keyword value FROM keyword WHERE " . $lsql->sql . " ORDER BY hit_count ASC LIMIT 1",$lsql->parameters,"");
        return trim_spaces(str_replace(" " . $least . " "," "," " . join(" ",$keywords) . " "));
        }
    else
        {
        return array();
        }
    }

// Take the current search URL and extract any nodes (putting into buckets) removing terms from $search
//
// Currently supports:
// @@!<node id> (NOT)
// @@<node id>@@<node id> (OR)
function resolve_given_nodes(&$search, &$node_bucket, &$node_bucket_not)
    {

    // extract all of the words, a word being a bunch of tokens with optional NOTs
    if (preg_match_all('/(' . NODE_TOKEN_PREFIX . NODE_TOKEN_NOT . '*\d+)+/',$search,$words)===false || count($words[0])==0)
        {
        return;
        }

    // spin through each of the words and process tokens
    foreach ($words[0] as $word)
        {
        $search=str_replace($word,'',$search);        // remove the entire word from the search string

        preg_match_all('/' . NODE_TOKEN_PREFIX . '(' . NODE_TOKEN_NOT . '*)(\d+)/',$word,$tokens);

        if(count($tokens[1])==1 && $tokens[1][0]==NODE_TOKEN_NOT)      // you are currently only allowed NOT condition for a single token within a single word
            {
            $node_bucket_not[]=$tokens[2][0];       // add the node number to the node_bucket_not
            continue;
            }

        $node_bucket[]=$tokens[2];
        }
    }

