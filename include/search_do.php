<?php
function do_search($search,$restypes="",$order_by="relevance",$archive=0,$fetchrows=-1,$sort="desc",$access_override=false,$starsearch=0,$ignore_filters=false,$return_disk_usage=false,$recent_search_daylimit="", $go=false, $stats_logging=true, $return_refs_only=false) {

    # Takes a search string $search, as provided by the user, and returns a results set
    # of matching resources.
    # If there are no matches, instead returns an array of suggested searches.
    # $restypes is optionally used to specify which resource types to search.
    # $access_override is used by smart collections, so that all all applicable resources can be judged regardless of the final access-based results

    debug("search=$search $go $fetchrows restypes=$restypes archive=$archive daylimit=$recent_search_daylimit");

    # globals needed for hooks
    global $sql,$order,$select,$sql_join,$sql_filter,$orig_order,$collections_omit_archived,$search_sql_double_pass_mode,$usergroup,$search_filter_strict,$default_sort,$superaggregationflag,$k;

    $alternativeresults = hook("alternativeresults", "", array($go));
    if ($alternativeresults)
        {
        return $alternativeresults;
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

    # resolve $order_by to something meaningful in sql
    $orig_order=$order_by;
    global $date_field;
    $order = array(
        "relevance"       => "score $sort, user_rating $sort, hit_count $sort, field$date_field $sort,r.ref $sort",
        "popularity"      => "user_rating $sort,hit_count $sort,field$date_field $sort,r.ref $sort",
        "rating"          => "r.rating $sort, user_rating $sort, score $sort,r.ref $sort",
        "date"            => "field$date_field $sort,r.ref $sort",
        "colour"          => "has_image $sort,image_blue $sort,image_green $sort,image_red $sort,field$date_field $sort,r.ref $sort",
        "country"         => "country $sort,r.ref $sort",
        "title"           => "title $sort,r.ref $sort",
        "file_path"       => "file_path $sort,r.ref $sort",
        "resourceid"      => "r.ref $sort",
        "resourcetype"    => "resource_type $sort,r.ref $sort",
        "titleandcountry" => "title $sort,country $sort",
        "random"          => "RAND()",
        "status"          => "archive $sort"
    );

    if (!in_array($order_by,$order)&&(substr($order_by,0,5)=="field"))
        {
        if (!is_numeric(str_replace("field","",$order_by)))
            {
            exit("Order field incorrect.");
            }
        $order[$order_by]="$order_by $sort";
        }

    hook("modifyorderarray");

    // ********************************************************************************

    // IMPORTANT!
    // add to this array in the format [AND group]=array(<list of nodes> to OR)
    $node_bucket=array();

    // add to this normal array to exclude nodes from entire search
    $node_bucket_not=array();

    // Take the current search URL and extract any nodes (putting into buckets) removing terms from $search
    resolve_given_nodes($search,$node_bucket,$node_bucket_not);

    // ********************************************************************************

    # Recognise a quoted search, which is a search for an exact string
    global $quoted_string;
    $quoted_string=substr($search,0,1)=="\"" && substr($search,-1,1)=="\"";
    if ($quoted_string)
        {
        $search=substr($search,1,-1);
        }

    $order_by=isset($order[$order_by]) ? $order[$order_by] : $order['relevance'];       // fail safe by falling back to default if not found

    # Extract search parameters and split to keywords.
    $search_params=$search;
    if (substr($search,0,1)=="!" && substr($search,0,6)!="!empty")
        {
        # Special search, discard the special search identifier when splitting keywords and extract the search paramaters
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

    $keywords=split_keywords($search_params);

    //print_r($keywords);


    foreach (get_indexed_resource_type_fields() as $resource_type_field)
        {
        add_verbatim_keywords($keywords,$search,$resource_type_field,true);      // add any regex matched verbatim keywords for those indexed resource type fields
        }

    $search=trim($search);
    # Dedupe keywords (not for quoted strings as the user may be looking for the same word multiple times together in this instance)
    if (!$quoted_string)
        {
        $keywords=array_values(array_unique($keywords));
        }

    $modified_keywords=hook('dosearchmodifykeywords', '', array($keywords));
    if ($modified_keywords)
        {
        $keywords=$modified_keywords;
        }

    # -- Build up filter SQL that will be used for all queries
    $sql_filter=search_filter($search,$archive,$restypes,$starsearch,$recent_search_daylimit,$access_override,$return_disk_usage);

    # Initialise variables.
    $sql="";
    $sql_keyword_union             = array();
    $sql_keyword_union_aggregation = array();
    $sql_keyword_union_criteria    = array();
    $sql_keyword_union_or          = array();


    # If returning disk used by the resources in the search results ($return_disk_usage=true) then wrap the returned SQL in an outer query that sums disk usage.
    $sql_prefix="";$sql_suffix="";
    if ($return_disk_usage)
        {
        $sql_prefix="select sum(disk_usage) total_disk_usage,count(*) total_resources from (";
        $sql_suffix=") resourcelist";
        }

    # ------ Advanced 'custom' permissions, need to join to access table.
    $sql_join="";
    if ((!checkperm("v")) &&!$access_override)
        {
        global $usergroup;global $userref;
        # one extra join (rca2) is required for user specific permissions (enabling more intelligent watermarks in search view)
        # the original join is used to gather group access into the search query as well.
        $sql_join=" left outer join resource_custom_access rca2 on r.ref=rca2.resource and rca2.user='$userref'  and (rca2.user_expires is null or rca2.user_expires>now()) and rca2.access<>2  ";
        $sql_join.=" left outer join resource_custom_access rca on r.ref=rca.resource and rca.usergroup='$usergroup' and rca.access<>2 ";

        if ($sql_filter!="") {$sql_filter.=" and ";}
        # If rca.resource is null, then no matching custom access record was found
        # If r.access is also 3 (custom) then the user is not allowed access to this resource.
        # Note that it's normal for null to be returned if this is a resource with non custom permissions (r.access<>3).
        $sql_filter.=" not(rca.resource is null and r.access=3)";
        }

    # Join thumbs_display_fields to resource table
    $select="r.ref, r.resource_type, r.has_image, r.is_transcoding, r.hit_count, r.creation_date, r.rating, r.user_rating, r.user_rating_count, r.user_rating_total, r.file_extension, r.preview_extension, r.image_red, r.image_green, r.image_blue, r.thumb_width, r.thumb_height, r.archive, r.access, r.colour_key, r.created_by, r.file_modified, r.file_checksum, r.request_count, r.new_hit_count, r.expiry_notification_sent, r.preview_tweaks, r.file_path ";

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
    $joins=$return_refs_only===false ? get_resource_table_joins() : array();
    foreach( $joins as $datajoin)
        {
        $select.=",r.field".$datajoin." ";
        }

    # Prepare SQL to add join table for all provided keywords

    $suggested=$keywords; # a suggested search
    $fullmatch=true;
    $c=0;
    $t="";
    $t2="";
    $score="";
    $skipped_last=false;

    # Do not process if a numeric search is provided (resource ID)
    global $config_search_for_number, $category_tree_search_use_and;
    $keysearch=!($config_search_for_number && is_numeric($search));

    # Fetch a list of fields that are not available to the user - these must be omitted from the search.
    $hidden_indexed_fields=get_hidden_indexed_fields();

    // This is a performance enhancement that will discard any keyword matches for fields that are not supposed to be indexed.
    $sql_restrict_by_field_types = '';
    global $search_sql_force_field_index_check;
    if(isset($search_sql_force_field_index_check) && $search_sql_force_field_index_check && '' != $restypes)
        {
        if('Global' == substr($restypes, 0, 6))
            {
            // remove "Global," from the list
            $restypes = substr($restypes, 7);
            }

        // 0 is for global fields which need to be added here as well
        $sql_restrict_by_field_types = sql_value("SELECT group_concat(ref) AS `value` FROM resource_type_field WHERE keywords_index = 1 AND resource_type IN (0, {$restypes})", '');

        if('' != $sql_restrict_by_field_types)
            {
            // -1 needed for global search
            $sql_restrict_by_field_types = '-1,' . $sql_restrict_by_field_types;
            }
        }

    // *******************************************************************************
    //
    //                                                                  START keywords
    //
    // *******************************************************************************

    if ($keysearch)
        {
        for ($n=0;$n<count($keywords);$n++)
            {
            $keyword=$keywords[$n];
            if (substr($keyword,0,1)!="!" || substr($keyword,0,6)=="!empty")
                {
                global $date_field;
                $field=0;
                //echo "<li>$keyword<br/>";

                $field_short_name_specified=false;
                if (strpos($keyword,":")!==false)
                    {
                    $field_short_name_specified=true;
                    $kw=explode(":",$keyword,2);
                    # Fetch field info
                    global $fieldinfo_cache;
                    if (isset($fieldinfo_cache[$kw[0]]))
                        {
                        $fieldinfo=$fieldinfo_cache[$kw[0]];
                        }
                    else
                        {
                        $fieldinfo=sql_query("select ref,type from resource_type_field where name='" . escape_check($kw[0]) . "'",0);
                        $fieldinfo_cache[$kw[0]]=$fieldinfo;
                        }
                    }

                //echo "field_short_name_specified=" . $field_short_name_specified . PHP_EOL;

                if ($field_short_name_specified && !$ignore_filters && isset($fieldinfo['type']) && !in_array($fieldinfo['type'],array(FIELD_TYPE_TEXT_BOX_SINGLE_LINE, FIELD_TYPE_TEXT_BOX_MULTI_LINE)))
                    {

                    // ********************************************************************************
                    //                                                                    Field keyword
                    // ********************************************************************************

                    global $datefieldinfo_cache;
                    if (isset($datefieldinfo_cache[$kw[0]]))
                        {
                        $datefieldinfo=$datefieldinfo_cache[$kw[0]];
                        }
                    else
                        {
                        $datefieldinfo=sql_query("select ref from resource_type_field where name='" . escape_check($kw[0]) . "' and type IN (4,6,10)",0);
                        $datefieldinfo_cache[$kw[0]]=$datefieldinfo;
                        }

                    if (count($datefieldinfo) && substr($kw[1],0,5)!="range")
                        {
                        $c++;
                        $datefieldinfo=$datefieldinfo[0];
                        $datefield=$datefieldinfo["ref"];
                        if ($sql_filter!="")
                            {
                            $sql_filter.=" and ";
                            }
                        $val=str_replace("n","_", $kw[1]);
                        $val=str_replace("|","-", $val);
                        $sql_filter.="rd" . $c . ".value like '". $val . "%' ";
                        $sql_join.=" join resource_data rd" . $c . " on rd" . $c . ".resource=r.ref and rd" . $c . ".resource_type_field='" . $datefield . "'";
                        }
                    elseif ($kw[0]=="day")
                        {
                        if ($sql_filter!="")
                            {
                            $sql_filter.=" and ";
                            }
                        $sql_filter.="r.field$date_field like '____-__-" . $kw[1] . "%' ";
                        }
                    elseif ($kw[0]=="month")
                        {
                        if ($sql_filter!="")
                            {
                            $sql_filter.=" and ";
                            }
                        $sql_filter.="r.field$date_field like '____-" . $kw[1] . "%' ";
                        }
                    elseif('year' == $kw[0])
                        {
                        if('' != $sql_filter)
                            {
                            $sql_filter .= ' AND ';
                            }
                        $sql_filter.= "rd{$c}.resource_type_field = {$date_field} AND rd{$c}.value LIKE '{$kw[1]}%' ";
                        $sql_join .= " INNER JOIN resource_data rd{$c} ON rd{$c}.resource = r.ref AND rd{$c}.resource_type_field = '{$date_field}'";
                        }
                    elseif ($kw[0]=="startdate")
                        {
                        if ($sql_filter!="")
                            {
                            $sql_filter.=" and ";
                            }
                        $sql_filter.="r.field$date_field >= '" . $kw[1] . "' ";
                        }
                    elseif ($kw[0]=="enddate")
                        {
                        if ($sql_filter!="")
                            {
                            $sql_filter.=" and ";
                            }
                        $sql_filter.="r.field$date_field <= '" . $kw[1] . " 23:59:59' ";
                        }
                        # Additional date range filtering
                    elseif (count($datefieldinfo) && substr($kw[1],0,5)=="range")
                        {
                        $c++;
                        $rangefield=$datefieldinfo[0]["ref"];
                        $daterange=false;
                        $rangestring=substr($kw[1],5);
                        if (strpos($rangestring,"start")!==FALSE )
                            {
                            $rangestartpos=strpos($rangestring,"start")+5;
                            $rangestart=str_replace(" ","-",substr($rangestring,$rangestartpos,strpos($rangestring,"end")?strpos($rangestring,"end")-$rangestartpos:10));
                            if ($sql_filter!="")
                                {
                                $sql_filter.=" and ";
                                }
                            $sql_filter.="rd" . $c . ".value >= '" . $rangestart . "'";
                            }
                        if (strpos($kw[1],"end")!==FALSE )
                            {
                            $rangeend=str_replace(" ","-",$rangestring);
                            if ($sql_filter!="")
                                {
                                $sql_filter.=" and ";
                                }
                            $sql_filter.="rd" . $c . ".value <= '" . substr($rangeend,strpos($rangeend,"end")+3,10) . " 23:59:59'";
                            }
                        $sql_join.=" join resource_data rd" . $c . " on rd" . $c . ".resource=r.ref and rd" . $c . ".resource_type_field='" . $rangefield . "'";
                        }
                    elseif (!hook('customsearchkeywordfilter', null, array($kw)))
                        {

                        // TODO: suss this out

                        /*

                        if (!isset($fieldinfo[0]["type"]))
                            {
                            return false;       // this is a duff field, i.e. does not exist
                            }

                        if($fieldinfo[0]["type"]==FIELD_TYPE_CATEGORY_TREE)
                            {
                            $ckeywords=preg_split('/[\|;]/',$kw[1]);
                            }
                        else
                            {
                            $ckeywords=explode(";",$kw[1]);
                            }

                        # Create an array of matching field IDs.
                        $fields=array();
                        foreach ($fieldinfo as $fi)
                            {
                            if (in_array($fi["ref"], $hidden_indexed_fields))
                                {
                                # Attempt to directly search field that the user does not have access to.
                                return false;
                                }

                            # Add to search array
                            $fields[]=$fi["ref"];
                            }

                        # Special handling for dates
                        if ($fieldinfo[0]["type"]==FIELD_TYPE_DATE_AND_OPTIONAL_TIME || $fieldinfo[0]["type"]==FIELD_TYPE_EXPIRY_DATE || $fieldinfo[0]["type"]==FIELD_TYPE_DATE)
                            {
                            $ckeywords=array(str_replace(" ","-",$kw[1]));
                            }

                        */

                        }
                    }
                else
                    {

                    // ********************************************************************************
                    //                                             Normal keyword (not tied to a field)
                    // ********************************************************************************

                    # Searches all fields that the user has access to
                    # If ignoring field specifications then remove them.

                    if ($field_short_name_specified)
                        {
                        $keyword=$kw[1];
                        }

                    $keywords_expanded=explode(';',$keyword);
                    $keywords_expanded_or=count($keywords_expanded) > 1;

                    // TODO: restrict by field name

                    foreach($keywords_expanded as $keyword)
                        {

                        // TODO: do we need to kill of $ignore_filters ?

                        //echo "keyword={$keyword}" . PHP_EOL;

                        /*
                        if (strpos($keyword,":")!==false && $ignore_filters)
                            {
                            $s=explode(":",$keyword);$keyword=$s[1];
                            }
                        */

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
                                $nodatafield = sql_value("select ref value from resource_type_field where name='" . escape_check($nodatafield) . "'", "");
                                }

                            if ($nodatafield == "" || !is_numeric($nodatafield))
                                {
                                exit('invalid !empty search');
                                }
                            $empty = true;
                            }

                        global $noadd, $wildcard_always_applied, $wildcard_always_applied_leading;
                        if (in_array($keyword, $noadd)) # skip common words that are excluded from indexing
                            {
                            $skipped_last = true;       // TODO: sort this out
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

                                    if ($wildcard_always_applied_leading)
                                        {
                                        $keyword = '*' . $keyword;
                                        }
                                    }

                                # Keyword contains a wildcard. Expand.
                                global $wildcard_expand_limit;
                                $wildcards = sql_array("select ref value from keyword where keyword like '" . escape_check(str_replace("*", "%", $keyword)) . "' order by hit_count desc limit " . $wildcard_expand_limit);
                                }

                            $keyref = resolve_keyword(str_replace('*', '', $keyword)); # Resolve keyword. Ignore any wildcards when resolving. We need wildcards to be present later but not here.
                            if ($keyref === false && !$omit && !$empty && count($wildcards) == 0)
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

                                if ($keyref === false)
                                    {
                                    # make a new keyword
                                    $keyref = resolve_keyword(str_replace('*', '', $keyword), true);
                                    }
                                # Key match, add to query.
                                $c++;

                                $relatedsql = "";
                                if (!$quoted_string) # Do not use related fields or wildcard for quoted string search - the keywords are treated as literal in this case.
                                    {
                                    # Add related keywords
                                    $related = get_related_keywords($keyref);

                                    # Merge wildcard expansion with related keywords
                                    $related = array_merge($related, $wildcards);
                                    if (count($related) > 0)
                                        {
                                        $relatedsql = " or k" . $c . ".keyword IN ('" . join("','", $related) . "')";
                                        }
                                    }

                                # Form join
                                $sql_exclude_fields = hook("excludefieldsfromkeywordsearch");

                                if ($omit)
                                    {
                                    # Exclude matching resources from query (omit feature)
                                    if ($sql_filter != "")
                                        {
                                        $sql_filter .= " and ";
                                        }

                                    // TODO: deprecate this once nodes stable START

                                    // ----- check that keyword does not exist in the resource_keyword table -----

                                    $sql_filter .= "r.ref not in (select resource from resource_keyword where keyword='$keyref')"; # Filter out resources that do contain the keyword.
                                    $sql_filter .= " AND ";

                                    // TODO: deprecate this once nodes stable END

                                    // ----- check that keyword does not exist via resource_node->node_keyword relationship -----

                                    $sql_filter .= "`r`.`ref` NOT IN (SELECT `resource` FROM `resource_node` JOIN `node_keyword` ON `resource_node`.`node`=`node_keyword`.`node`" .
                                        " WHERE `resource_node`.`resource`=`r`.`ref` AND `node_keyword`.`keyword`={$keyref})";

                                    } else
                                    {
                                    # Include in query

                                    // --------------------------------------------------------------------------------
                                    // Start of normal union for resource keywords
                                    // --------------------------------------------------------------------------------

                                    // these restrictions apply to both !empty searches as well as normal keyword searches (i.e. both branches of next if statement)
                                    $union_restriction_clause = "";
                                    $union_restriction_clause_node = "";

                                    // TODO: change $c to [union_index]

                                    if (!empty($sql_exclude_fields))
                                        {
                                        $union_restriction_clause .= " and k" . $c . ".resource_type_field not in (" . $sql_exclude_fields . ")";
                                        $union_restriction_clause_node .= "AND nk{$c}.node NOT IN (SELECT ref FROM node WHERE nk{$c}.node=node.ref AND node.resource_type_field IN (" . $sql_exclude_fields .  "))";
                                        }

                                    if (count($hidden_indexed_fields) > 0)
                                        {
                                        $union_restriction_clause .= " and k" . $c . ".resource_type_field not in ('" . join("','", $hidden_indexed_fields) . "')";
                                        $union_restriction_clause_node .= "AND nk{$c}.node NOT IN (SELECT ref FROM node WHERE nk{$c}.node=node.ref AND node.resource_type_field IN (" . join(",", $hidden_indexed_fields) . "))";
                                        }

                                    if ($empty)  // we are dealing with a special search checking if a field is empty
                                        {
                                        $rtype = sql_value("select resource_type value from resource_type_field where ref='$nodatafield'", 0);
                                        if ($rtype != 0)
                                            {
                                            if ($rtype == 999)
                                                {
                                                $restypesql = "and (r" . $c . ".archive=1 or r" . $c . ".archive=2) and ";
                                                if ($sql_filter != "")
                                                    {
                                                    $sql_filter .= " and ";
                                                    }
                                                $sql_filter .= str_replace("r" . $c . ".archive='0'", "(r" . $c . ".archive=1 or r" . $c . ".archive=2)", $sql_filter);
                                                } else
                                                {
                                                $restypesql = "and r" . $c . ".resource_type ='$rtype' ";
                                                }
                                            } else
                                            {
                                            $restypesql = "";
                                            }
                                        $union = "select ref as resource, [bit_or_condition] 1 as score from resource r" . $c . " left outer join resource_data rd" . $c . " on r" . $c . ".ref=rd" . $c .
                                            ".resource and rd" . $c . ".resource_type_field='$nodatafield' where  (rd" . $c . ".value ='' or rd" . $c .
                                            ".value is null or rd" . $c . ".value=',') $restypesql  and r" . $c . ".ref>0 group by r" . $c . ".ref ";
                                        $union .= $union_restriction_clause;
                                        $sql_keyword_union[] = $union;
                                        }
                                    else  // we are dealing with a standard keyword match
                                        {
                                        $filter_by_resource_field_type = "";        // TODO: deprecate this once nodes stable
                                        $filter_by_resource_field_type_node = "";

                                        if ($sql_restrict_by_field_types != "")
                                            {
                                            $filter_by_resource_field_type = "and k{$c}.resource_type_field in ({$sql_restrict_by_field_types})";       // TODO: deprecate this once nodes stable
                                            $filter_by_resource_field_type_node = "AND EXISTS(SELECT * FROM node WHERE nk{$c}.node=node.ref and node.resource_type_field in ({$sql_restrict_by_field_types})";
                                            }

                                        // ----- resource_keyword sub query -----

                                        // TODO: deprecate this once nodes stable START

                                        $union = "SELECT resource, [bit_or_condition] SUM(hit_count) AS score FROM resource_keyword k{$c}" .
                                            " WHERE (k{$c}.keyword={$keyref} {$filter_by_resource_field_type} {$relatedsql} {$union_restriction_clause})" .
                                            " GROUP BY resource UNION ";

                                        // TODO: deprecate this once nodes stable END

                                        // ----- resource_node -> node_keyword sub query -----

                                        $union .= "SELECT resource, [bit_or_condition] SUM(hit_count) AS score FROM resource_node rn[union_index]" .
                                            " LEFT OUTER JOIN `node_keyword` nk[union_index] ON rn[union_index].node=nk[union_index].node " .
                                            " WHERE (nk[union_index].keyword={$keyref} {$filter_by_resource_field_type_node} {$relatedsql} {$union_restriction_clause_node})" .
                                            " GROUP BY resource";
                                        $sql_keyword_union[] = $union;

                                        // ---- end of resource_node -> node_keyword sub query -----

                                        // if a quoted search then check the keywords are in sequence

                                        //TODO: Test this with <search term> <quoted search term>, i.e. quoted after first keyword

                                        if ($quoted_string && $c > 1)
                                            {
                                            $sql_keyword_union_criteria[] = "`h`.`keyword_[union_index]_found` AND `h`.`keyword_[union_index]_position` > `h`.`keyword_[union_index_minus_one]_position`";
                                            } else
                                            {
                                            $sql_keyword_union_criteria[] = "`h`.`keyword_[union_index]_found`";
                                            }

                                        $sql_keyword_union_aggregation[] = "BIT_OR(`keyword_[union_index]_found`) AS `keyword_[union_index]_found`, " .
                                            "MAX(`keyword_[union_index]_position`) AS `keyword_[union_index]_position`";

                                        $sql_keyword_union_or[]=$keywords_expanded_or;

                                        }

                                    // --------------------------------------------------------------------------------

                                    }       // end if not omit

                                # Log this
                                if ($stats_logging)
                                    {
                                    daily_stat("Keyword usage", $keyref);
                                    }
                                }       // end found found

                            $skipped_last = false;
                            }       // end handle wildcards
                        }       // end keywords expanded loop
                    }       // end normal keyword
                }       // end of check if special search
            }       // end n keyword loop
        }       // end keysearch if

    // *******************************************************************************
    //
    //                                                                    END keywords
    //
    // *******************************************************************************

    // *******************************************************************************
    //                                                       START add node conditions
    // *******************************************************************************

    $node_bucket_sql="";
    foreach($node_bucket as $node_bucket_or)
        {
        $node_bucket_sql.='EXISTS (SELECT `resource` FROM `resource_node` WHERE `ref`=`resource` AND `node` IN (' .
            implode(',',$node_bucket_or) . ')) AND ';
        }
    $sql_filter=$node_bucket_sql . $sql_filter;

    if(count($node_bucket_not)>0)
        {
        $sql_filter='NOT EXISTS (SELECT `resource` FROM `resource_node` WHERE `ref`=`resource` AND `node` IN (' .
            implode(',',$node_bucket_not) . ')) AND ' . $sql_filter;
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

            for ($n=0;$n<count($suggested);$n++)
                {
                if ($suggested[$n]!="")
                    {
                    if ($suggest!="")
                        {
                        $suggest.=$suggestjoin;
                        }
                    $suggest.=$suggested[$n];
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

    global $usersearchfilter;
    if (strlen($usersearchfilter)>0)
        {
        $sf=explode(";",$usersearchfilter);
        for ($n=0;$n<count($sf);$n++)
            {
            $s=explode("=",$sf[$n]);
            if (count($s)!=2)
                {
                exit ("Search filter is not correctly configured for this user group.");
                }

            # Support for "NOT" matching. Return results only where the specified value or values are NOT set.
            $filterfield=$s[0];$filter_not=false;
            if (substr($filterfield,-1)=="!")
                {
                $filter_not=true;
                $filterfield=substr($filterfield,0,-1);# Strip off the exclamation mark.
                }

            # Support for multiple fields on the left hand side, pipe separated - allows OR matching across multiple fields in a basic way
            $filterfields=explode("|",escape_check($filterfield));

            # Find field(s) - multiple fields can be returned to support several fields with the same name.
            $f=sql_array("select ref value from resource_type_field where name in ('" . join("','",$filterfields) . "')");
            if (count($f)==0)
                {
                exit ("Field(s) with short name '" . $filterfield . "' not found in user group search filter.");
                }

            # Find keyword(s)
            $ks=explode("|",strtolower(escape_check($s[1])));
            for($x=0;$x<count($ks);$x++)
                {
                # Cleanse the string as keywords are stored without special characters
                $ks[$x]=cleanse_string($ks[$x],true);

                global $stemming;
                if ($stemming && function_exists("GetStem")) // Stemming enabled. Highlight any words matching the stem.
                    {
                    $ks[$x]=GetStem($ks[$x]);
                    }
                }

            $modifiedsearchfilter=hook("modifysearchfilter");
            if ($modifiedsearchfilter)
                {
                $ks=$modifiedsearchfilter;
                }
            $kw=sql_array("select ref value from keyword where keyword in ('" . join("','",$ks) . "')");

            if (!$filter_not)
                {
                # Standard operation ('=' syntax)
                // TODO: change resource_keyword to resource_node -> node_keyword
                // echo "***************** HERE 4 *****************" . PHP_EOL;
                $sql_join.=" join resource_keyword filter" . $n . " on r.ref=filter" . $n . ".resource and filter" . $n . ".resource_type_field in ('" . join("','",$f) . "') and ((filter" . $n . ".keyword in ('" .     join("','",$kw) . "')) ";

                # Option for custom access to override search filters.
                # For this resource, if custom access has been granted for the user or group, nullify the search filter for this particular resource effectively selecting "true".
                global $custom_access_overrides_search_filter;
                if (!checkperm("v") && !$access_override && $custom_access_overrides_search_filter) # only for those without 'v' (which grants access to all resources)
                    {
                    $sql_join.="or ((rca.access is not null and rca.access<>2) or (rca2.access is not null and rca2.access<>2))";
                    }
                $sql_join.=")";

                if ($search_filter_strict > 1)
                    {
                    $sql_join.=" join resource_data dfilter" . $n . " on r.ref=dfilter" . $n . ".resource and dfilter" . $n . ".resource_type_field in ('" . join("','",$f) . "') and (find_in_set('". join ("', dfilter" . $n . ".value) or find_in_set('", explode("|",escape_check($s[1]))) ."', dfilter" . $n . ".value))";
                    }
                }
            else
                {
                # Inverted NOT operation ('!=' syntax)
                if ($sql_filter!="")
                    {
                    $sql_filter.=" and ";
                    }
                // TODO: change resource_keyword to resource_node -> node_keyword
                // echo "***************** HERE 5 *****************" . PHP_EOL;
                $sql_filter .= "((r.ref not in (select resource from resource_keyword where resource_type_field in ('" . join("','",$f) . "') and keyword in ('" .    join("','",$kw) . "'))) "; # Filter out resources that do contain the keyword(s)

                # Option for custom access to override search filters.
                # For this resource, if custom access has been granted for the user or group, nullify the search filter for this particular resource effectively selecting "true".
                global $custom_access_overrides_search_filter;
                if (!checkperm("v") && !$access_override && $custom_access_overrides_search_filter) # only for those without 'v' (which grants access to all resources)
                    {
                    $sql_filter.="or ((rca.access is not null and rca.access<>2) or (rca2.access is not null and rca2.access<>2))";
                    }

                $sql_filter.=")";
                }
            }
        }

    $userownfilter=hook("userownfilter");
    if ($userownfilter)
        {
        $sql_join.=$userownfilter;
        }

    // *******************************************************************************
    //
    //                                                                   END filtering
    //
    // *******************************************************************************

    # Handle numeric searches when $config_search_for_number=false, i.e. perform a normal search but include matches for resource ID first
    global $config_search_for_number;
    if (!$config_search_for_number && is_numeric($search))
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

        for($i=1; $i<=count($sql_keyword_union); $i++)
            {
            $bit_or_condition="";
            for ($y=1; $y<=count($sql_keyword_union); $y++)
                {
                if ($i==$y)
                    {
                    $bit_or_condition .= "`position` AS `keyword_{$y}_position`, TRUE AS `keyword_{$y}_found`,";
                    }
                else
                    {
                    $bit_or_condition .= "-1 AS `keyword_{$y}_position`, FALSE AS `keyword_{$y}_found`,";
                    }
                }
            $sql_keyword_union[($i-1)]=str_replace('[bit_or_condition]',$bit_or_condition,$sql_keyword_union[($i-1)]);
            $sql_keyword_union[($i-1)]=str_replace('[union_index]',$i,$sql_keyword_union[($i-1)]);
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

        $sql_join .= " join (
        select resource,sum(score) as score,
        " . join(", ", $sql_keyword_union_aggregation) . " from
        (" . join(" union ", $sql_keyword_union) . ") as hits group by resource) as h on h.resource=r.ref ";

        if ($sql_filter!="") {$sql_filter.=" and ";}


        if(count($sql_keyword_union_or)!=count($sql_keyword_union_criteria))
            {
            die("Search error - union criteria mismatch");
            }


        $sql_filter.="(";

        for($i=0; $i<count($sql_keyword_union_or); $i++)
            {
            if($i==0)
                {
                $sql_filter.=$sql_keyword_union_criteria[$i];
                continue;
                }

            if($sql_keyword_union_or[$i]!=$sql_keyword_union_or[$i-1])
                {
                $sql_filter.=') and (' . $sql_keyword_union_criteria[$i];
                continue;
                }

            if($sql_keyword_union_or[$i])
                {
                $sql_filter.=' OR ';
                }
            else
                {
                $sql_filter.=' AND ';
                }

            $sql_filter.=$sql_keyword_union_criteria[$i];
            }

        $sql_filter.=")";

        # Use amalgamated resource_keyword hitcounts for scoring (relevance matching based on previous user activity)
        $score="h.score";
        }

    # Can only search for resources that belong to themes
    if (checkperm("J"))
        {
        $sql_join=" join collection_resource jcr on jcr.resource=r.ref join collection jc on jcr.collection=jc.ref and length(jc.theme)>0 " . $sql_join;
        }

    # --------------------------------------------------------------------------------
    # Special Searches (start with an exclamation mark)
    # --------------------------------------------------------------------------------
    $special_results=search_special($search,$sql_join,$fetchrows,$sql_prefix,$sql_suffix,$order_by,$orig_order,$select,$sql_filter,$archive,$return_disk_usage,$return_refs_only);
    if ($special_results!==false)
        {
        return $special_results;
        }

    # -------------------------------------------------------------------------------------
    # Standard Searches
    # -------------------------------------------------------------------------------------

    # We've reached this far without returning.
    # This must be a standard (non-special) search.

    # Construct and perform the standard search query.
    #$sql="";
    if ($sql_filter!="")
        {
        if ($sql!="")
            {
            $sql.=" and ";
            }
        $sql.=$sql_filter;
        }

    # Append custom permissions
    $t.=$sql_join;

    if ($score=="")
        {
        $score="r.hit_count";
        } # In case score hasn't been set (i.e. empty search)

    global $max_results;
    if (($t2!="") && ($sql!=""))
        {
        $sql=" and " . $sql;
        }

    # Compile final SQL

    # Performance enhancement - set return limit to number of rows required
    if ($search_sql_double_pass_mode && $fetchrows!=-1)
        {
        $max_results=$fetchrows;
        }

    $results_sql=$sql_prefix . "select distinct $score score, $select from resource r" . $t . "  where $t2 $sql group by r.ref order by $order_by limit $max_results" . $sql_suffix;

    # Debug
    debug('$results_sql=' . $results_sql);


if(false)   // TODO: remove this completely
    {
print_r ($sql_keyword_union);
print_r ($sql_keyword_union_aggregation);
print_r ($sql_keyword_union_criteria);
print_r ($sql_keyword_union_or);
echo $results_sql;
    }

    if($return_refs_only)
        {
        # Execute query but only ask for ref columns back from mysql_query();
        # We force verbatim query mode on (and restore it afterwards) as there is no point trying to strip slashes etc. just for a ref column
        global $mysql_verbatim_queries;
        $mysql_vq=$mysql_verbatim_queries;
        $mysql_verbatim_queries=true;
        $result=sql_query($results_sql,false,$fetchrows,true,2,true,array('ref'));
        $mysql_verbatim_queries=$mysql_vq;
        }
    else
        {
        # Execute query as normal
        $result=sql_query($results_sql,false,$fetchrows);

        # Performance improvement - perform a second count-only query and pad the result array as necessary
        if($search_sql_double_pass_mode && count($result)>=$max_results)
            {
            $count_sql="select count(distinct r.ref) value from resource r" . $t . "  where $t2 $sql";
            $count=sql_value($count_sql,0);
            $result=array_pad($result,$count,0);
            }
        }

    debug("Search found " . count($result) . " results");
    if (count($result)>0)
        {
        hook("beforereturnresults","",array($result, $archive));
        return $result;
        }

    hook('zero_search_results');

    # (temp) - no suggestion for field-specific searching for now - TO DO: modify function below to support this
    if (strpos($search,":")!==false)
        {
        return "";
        }

    # All keywords resolved OK, but there were no matches
    # Remove keywords, least used first, until we get results.
    $lsql="";
    $omitmatch=false;

    for ($n=0;$n<count($keywords);$n++)
        {
        if (substr($keywords[$n],0,1)=="-")
            {
            $omitmatch=true;
            $omit=$keywords[$n];
            }
        if ($lsql!="")
            {
            $lsql.=" or ";
            }
        $lsql.="keyword='" . escape_check($keywords[$n]) . "'";
        }

    if ($omitmatch)
        {
        return trim_spaces(str_replace(" " . $omit . " "," "," " . join(" ",$keywords) . " "));
        }

    if ($lsql!="")
        {
        $least=sql_value("select keyword value from keyword where $lsql order by hit_count asc limit 1","");
        return trim_spaces(str_replace(" " . $least . " "," "," " . join(" ",$keywords) . " "));
        }
    else
        {
        return array();
        }
    }

// Take the current search URL and extract any nodes (putting into buckets) removing terms from $search
//
// UNDER DEVELOPMENT.  Currently supports:
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

        if(count($tokens[1])==1 && $tokens[1][0]==NODE_TOKEN_NOT)      // you are currently only allowed not condition for a single token within a single word
            {
            $node_bucket_not[]=$tokens[2][0];       // add the node number to the node_bucket_not
            continue;
            }

        $node_bucket[]=$tokens[2];
        }
    }

