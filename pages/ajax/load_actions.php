<?php
include '../../include/db.php';
include '../../include/authenticate.php';

$forpage=getvalescaped('page', '');
$type=getvalescaped('actiontype', '');
$ref=getvalescaped('ref', '',true);

switch ($type)
    {
    case "collection":
        hook('render_themes_list_tools', '', $ref);
        $collection_data = get_collection($ref);
        render_actions($collection_data,false,false,$ref,array(),true, $forpage);
    break;

    case "selection_collection":
        render_selected_collection_actions();
        break;

    case "search":
        $search = getvalescaped("search", "");
        $restypes = getvalescaped("restypes", "");
        $order_by = getvalescaped("order_by", "relevance");
        $archive = getvalescaped("archive", "0");
        $per_page = getvalescaped("per_page", null, true);
        $offset = getvalescaped("offset", null, true);
        $fetchrows = (!is_null($per_page) || !is_null($offset) ? $per_page + $offset : -1);
        $sort = getvalescaped("sort", "desc");
        // $access_override = false;
        $starsearch = getvalescaped("starsearch", 0, true);
        // $ignore_filters = false;
        // $return_disk_usage = false;
        $recent_search_daylimit = getvalescaped("recent_search_daylimit", "");
        $go = getvalescaped("go", "");
        // $stats_logging = true;
        // $return_refs_only = false;
        $editable_only = getvalescaped("foredit","")=="true";

        $result = do_search(
            $search,
            $restypes,
            $order_by,
            $archive,
            $fetchrows,
            $sort,
            false,
            $starsearch,
            false,
            false,
            $recent_search_daylimit,
            $go, 
            true, 
            false, 
            $editable_only);
        $resources_count = is_array($result) ? count($result) : 0;
        // Is this a collection search?
        $collectiondata = array();
        $collection_search_strpos = strpos($search, "!collection");
        $collectionsearch = $collection_search_strpos !== false && $collection_search_strpos === 0; // We want the default collection order to be applied
        if($collectionsearch)
            {
            // Collection search may also have extra search keywords passed to search within a collection
            $search_trimmed = substr($search,11); // The collection search must always be the first part of the search string
            $search_elements = split_keywords($search_trimmed, false, false, false, false, true);
            $collection = (int)array_shift($search_elements);
            $search = "!collection" . $collection . " " . implode(", ",$search_elements);
            $collectiondata = get_collection($collection);  
            }

        render_actions($collectiondata, true, false);
        break;

    case "resource":
    break;
    }
    

