<?php
include_once "../include/db.php";

if($annotate_enabled)
    {
    include_once '../include/annotation_functions.php';
    }


# External access support (authenticate only if no key provided, or if invalid access key provided)
$s = explode(" ",getvalescaped("search",""));
$k = getvalescaped("k","");
$resetlockedfields = getvalescaped("resetlockedfields","") != "";

if (($k=="") || (!check_access_key_collection(str_replace("!collection","",$s[0]),$k))) {include "../include/authenticate.php";}

// Set a flag for logged in users if $external_share_view_as_internal is set and logged on user is accessing an external share
$internal_share_access = internal_share_access();

if ($k=="" || $internal_share_access)
    {
    #note current user collection for add/remove links if we haven't got it set already
    if(!isset($usercollection))
        {
        if((isset($anonymous_login) && ($username==$anonymous_login)) && isset($rs_session) && $anonymous_user_session_collection)
            {   
            $sessioncollections=get_session_collections($rs_session,$userref,true); 
            $usercollection=$sessioncollections[0];
            $collection_allow_creation=false; // Hide all links that allow creation of new collections                      
            }
        else
            {
            if(isset($user))
                {
                $user=get_user($userref);
                }
            $usercollection=$user['current_collection'];
            }
        }
    // Get usercollection resources - used for checks against the list (e.g is one of the resources found by search in the collection?)
    $usercollection_resources = get_collection_resources($usercollection);
    }

// Disable checkboxes for external users.
$use_selection_collection = true;
if($k != "" && !$internal_share_access || (isset($anonymous_login) && $username == $anonymous_login && !$anonymous_user_session_collection))
    {
    $use_selection_collection = false;
    }

$search = getvalescaped('search', '');
$modal  = ('true' == getval('modal', ''));
$collection_add=getvalescaped("collection_add",""); // Need this if redirected here from upload
$initial_search_cookie = (isset($_COOKIE['search']) ? trim(strip_leading_comma($_COOKIE['search'])) : '');
$initial_restypes_cookie = (isset($_COOKIE['restypes']) ? trim($_COOKIE['restypes']) : '');
$initial_saved_archive_cookie = (isset($_COOKIE['saved_archive']) ? trim($_COOKIE['saved_archive']) : '');

if(false !== strpos($search, TAG_EDITOR_DELIMITER))
    {
    $search = str_replace(TAG_EDITOR_DELIMITER, ' ', $search);
    }

hook("moresearchcriteria");

// When searching for specific field options we convert search into nodeID search format (@@nodeID)
// This is done because if we also have the field displayed and we search for country:France this needs to 
// convert to @@74 in order for the field to have this option selected
$keywords = split_keywords($search, false, false, false, false, true);
foreach($keywords as $keyword)
    {
    if('' == trim($keyword))
        {
        continue;
        }

    if(false === strpos($search, ':'))
        {
        continue;
        }
    if(substr($keyword,0,1) =="\"" && substr($keyword,-1,1) == "\"")
        {
        $specific_field_search=explode(":",substr($keyword,1,-1));
        }
    else
        {
        $specific_field_search = explode(':', $keyword);
        }

    if(2 !== count($specific_field_search))
        {
        continue;
        }

    $field_shortname = trim($specific_field_search[0]);

    if('' == $field_shortname)
        {
        continue;
        }

    $field_shortname = escape_check($field_shortname);
    $resource_type_field = sql_value("SELECT ref AS `value` FROM resource_type_field WHERE `name` = '{$field_shortname}'", 0, "schema");

    if(0 == $resource_type_field)
        {
        continue;
        }

    $nodes = get_nodes($resource_type_field, null, true);
    
    // Check if multiple nodes have been specified for an OR search
    $keywords_expanded=explode(';',$specific_field_search[1]);
    $subnodestring = "";
    foreach($keywords_expanded as $keyword_expanded)
        {
        $node_found = get_node_by_name($nodes, $keyword_expanded);

        if(0 < count($node_found))
            {
            $subnodestring .= NODE_TOKEN_PREFIX . $node_found['ref'];
            }
        }
    if($subnodestring != "")
        {
        $search = str_ireplace($keyword, $subnodestring, $search);
        }
    }
    
# create a display_fields array with information needed for detailed field highlighting
$df=array();


$all_field_info=get_fields_for_search_display(array_unique(array_merge($sort_fields,$thumbs_display_fields,$list_display_fields)));

# get display and normalize display specific variables
$display=getvalescaped("display",$default_display);rs_setcookie('display', $display,0,"","",false,false);

switch ($display)
    {
    case "list":
        $display_fields = $list_display_fields; 
        $results_title_trim = $list_search_results_title_trim;
        break;
        
    case "xlthumbs":            
    case "thumbs": 
    case "strip":
        
    default:
        $display_fields = $thumbs_display_fields;  
        if (isset($search_result_title_height)) { $result_title_height = $search_result_title_height; }
        $results_title_trim = $search_results_title_trim;
        $results_title_wordwrap = $search_results_title_wordwrap;
        break;      
    }

$n=0;
foreach ($display_fields as $display_field)
    {
    # Find field in selected list
    for ($m=0;$m<count($all_field_info);$m++)
        {
        if ($all_field_info[$m]["ref"]==$display_field)
            {
            $field_info=$all_field_info[$m];
            $df[$n]['ref']=$display_field;
            $df[$n]['type']=$field_info['type'];
            $df[$n]['indexed']=$field_info['keywords_index'];
            $df[$n]['partial_index']=$field_info['partial_index'];
            $df[$n]['name']=$field_info['name'];
            $df[$n]['title']=$field_info['title'];
            $df[$n]['value_filter']=$field_info['value_filter'];
            $n++;
            }
        }
    }
$n=0;   
$df_add=hook("displayfieldsadd");
# create a sort_fields array with information for sort fields
$n=0;
$sf=array();
foreach ($sort_fields as $sort_field)
    {
    # Find field in selected list
    for ($m=0;$m<count($all_field_info);$m++)
        {
        if ($all_field_info[$m]["ref"]==$sort_field)
            { 
            $field_info=$all_field_info[$m];
            $sf[$n]['ref']=$sort_field;
            $sf[$n]['title']=$field_info['title'];
            $n++;
            }
        }
    }
$n=0;   

$saved_search=$search;

# Append extra search parameters from the quick search.
if (!$config_search_for_number || !is_numeric($search)) # Don't do this when the search query is numeric, as users typically expect numeric searches to return the resource with that ID and ignore country/date filters.
    {
    // For the simple search fields, collect from the GET and POST requests and assemble into the search string.
    $search = update_search_from_request($search);
    }

$searchresourceid = "";
if (is_numeric(trim(getvalescaped("searchresourceid","")))){
    $searchresourceid = trim(getvalescaped("searchresourceid",""));
    $search = "!resource$searchresourceid";
}

// Is this a collection search?
$collection_search_strpos = strpos($search, "!collection");
$collectionsearch = $collection_search_strpos !== false && $collection_search_strpos === 0; // We want the default collection order to be applied
if($collectionsearch)
    {
    // Collection search may also have extra search keywords passed to search within a collection
    $search_trimmed = substr($search,11); // The collection search must always be the first part of the search string
    $search_elements = split_keywords($search_trimmed, false, false, false, false, true);
    $collection = (int)array_shift($search_elements);
    $search = "!collection" . $collection . " " . implode(", ",$search_elements);
    }

hook("searchstringprocessing");

# Fetch and set the values
$offset=getvalescaped("offset",0,true);if (strpos($search,"!")===false) {rs_setcookie('saved_offset', $offset,0,"","",false,false);}
$offset = intval($offset); 
if ($offset<0) {$offset=0;} 

$order_by=getvalescaped("order_by","");if (strpos($search,"!")===false || strpos($search,"!properties")!==false) {rs_setcookie('saved_order_by', $order_by,0,"","",false,false);}
if ($order_by=="")
    {
    if ($collectionsearch) // We want the default collection order to be applied
        {
        $order_by=$default_collection_sort;
        }
    else
        {
        $order_by=$default_sort;
        }
    }

if (substr($order_by,0,5)=="field")
    {
    $order_by_field = substr($order_by,5);
        {
        if(!metadata_field_view_access($order_by_field))
            {
            $order_by = 'relevance';
            }
        }
    }

$per_page=getvalescaped("per_page",$default_perpage, true); 
$per_page= (!in_array($per_page,$results_display_array)) ? $default_perpage : $per_page;

rs_setcookie('per_page', $per_page,0,"","",false,false);

// Clear special selection collection if user runs a new search. Paging is not a new search. Also we allow for users that
// want to see what they've selected so far. Client side we can POST clear_selection_collection=no to prevent it from clearing
// (e.g when batch editing)
$clear_selection_collection = (getval("clear_selection_collection", "") != "no");
$paging_request = in_array(getval("go", ""), array("next", "prev", "page"));

// Preserve selection on display layout change.
$displaytypes = array('xlthumbs', 'thumbs', 'strip', 'list');
if (isset($_POST['display']))
    {
    $thumbtypechange = in_array($_POST['display'], $displaytypes);
    }
else if (!isset($_POST['display']) && isset($_GET['display']))
    {
    $thumbtypechange = in_array($_GET['display'], $displaytypes);
    }
else
    {
    $thumbtypechange = false;
    }

$view_selected_request = ($use_selection_collection && mb_strpos($search, "!collection{$USER_SELECTION_COLLECTION}") !== false);
if($use_selection_collection && $clear_selection_collection && !$paging_request && !$thumbtypechange && !$view_selected_request && !is_null($USER_SELECTION_COLLECTION))
    {
    remove_all_resources_from_collection($USER_SELECTION_COLLECTION);
    }

// Construct archive string and array
$archive_choices=getvalescaped("archive","");
$archive_standard = $archive_choices=="";
$selected_archive_states = array();
if(!is_array($archive_choices)){$archive_choices=explode(",",$archive_choices);}
foreach($archive_choices as $archive_choice)
    {
    if(is_numeric($archive_choice)) {$selected_archive_states[] = $archive_choice;}  
    }

$archive = implode(",",$selected_archive_states);
$archivesearched = in_array(2,$selected_archive_states);

// Disable search through all workflow states when an archive state is specifically requested
// This prevents links like 'View deleted resources' showing resources in all states
if($search_all_workflow_states && !$archive_standard)
    {
    $search_all_workflow_states = false;
    }

if(false === strpos($search, '!'))
    {
    rs_setcookie('saved_archive', $archive,0,"","",false,false);
    }

if($resetlockedfields)
    {
    // Reset locked metadata fields cookie after editing resources in upload_review_mode
    rs_setcookie('lockedfields', '',0,"","",false,false);
    }


$jumpcount=0;

if (getvalescaped('recentdaylimit', '', true)!="") //set for recent search, don't set cookie
    {
    $daylimit=getvalescaped('recentdaylimit', '', true);
    }
else if($recent_search_period_select==true && strpos($search,"!")===false) //set cookie for paging
    {
    $daylimit=getvalescaped("daylimit",""); 
    rs_setcookie('daylimit', $daylimit,0,"","",false,false);
    }
else {$daylimit="";} // clear cookie for new search

# Most sorts such as popularity, date, and ID should be descending by default,
# but it seems custom display fields like title or country should be the opposite.
$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field")
    {
    $default_sort_direction="ASC";
    }
if ($order_by=="field{$date_field}")
    {
    $default_sort_direction="DESC";
    }
elseif ($order_by=="collection")
    {
    $default_sort_direction="ASC";
    }
$sort=getvalescaped("sort",$default_sort_direction);rs_setcookie('saved_sort', $sort,0,"","",false,false);
$revsort = ($sort=="ASC") ? "DESC" : "ASC";

## If displaying a collection
# Enable/disable the reordering feature. Just for collections for now.
$allow_reorder=false;

# get current collection resources to pre-fill checkboxes
if($use_selection_collection)
    {
    if(is_null($USER_SELECTION_COLLECTION))
        {
        $selection_collection_resources = array();
        $selection_collection_resources_count = 0;        
        }
    else
        {
        $selection_collection_resources = get_collection_resources($USER_SELECTION_COLLECTION);
        $selection_collection_resources_count = count($selection_collection_resources);
        }
    }

$hiddenfields=getvalescaped("hiddenfields","");

# fetch resource types from query string and generate a resource types cookie
if (getvalescaped("resetrestypes","")=="")
    {
    $restypes=getvalescaped("restypes","");
    }
else
    { 
    $restypes="";
    reset($_POST);reset($_GET);foreach (array_merge($_GET, $_POST) as $key=>$value)

        {
        
        $hiddenfields=Array();
        if ($key=="rttickall" && $value=="on"){$restypes="";break;} 
        if ((substr($key,0,8)=="resource")&&!in_array($key, $hiddenfields)) {if ($restypes!="") {$restypes.=",";} $restypes.=substr($key,8);}
        }

    rs_setcookie('restypes', $restypes,0,"","",false,false);

    # This is a new search, log this activity
    if ($archivesearched) {daily_stat("Archive search",0);} else {daily_stat("Search",0);}
    }
$modified_restypes=hook("modifyrestypes_aftercookieset");
if($modified_restypes){$restypes=$modified_restypes;}

# if search is not a special search (ie. !recent), use starsearchvalue.
if (getvalescaped("search","")!="" && strpos(getvalescaped("search",""),"!")!==false)
    {
    $starsearch="";
    }
else
    {
    $starsearch=getvalescaped("starsearch",""); 
    rs_setcookie('starsearch', $starsearch,0,"","",false,false);
}

# If returning to an old search, restore the page/order by and other non search string parameters
$old_search = (!array_key_exists('search', $_GET) && !array_key_exists('search', $_POST));
if ($old_search)
    {
    $offset=getvalescaped("saved_offset",0,true);rs_setcookie('saved_offset', $offset,0,"","",false,false);
    $order_by=getvalescaped("saved_order_by","relevance");
    if ($collectionsearch) // We want the default collection order to be applied
        {
        $order_by=$default_collection_sort;
        }
    else
        {
        $order_by=$default_sort;
        }
    rs_setcookie('saved_order_by', $order_by,0,"","",false,false);
    $sort=getvalescaped("saved_sort","");rs_setcookie('saved_sort', $sort,0,"","",false,false);
    $archivechoices=getvalescaped("saved_archive",0);rs_setcookie('saved_archive', $archivechoices,0,"","",false,false);
    if(!is_array($archivechoices)){$archivechoices=explode(",",$archivechoices);}
    foreach($archivechoices as $archivechoice)
        {
        if(is_numeric($archivechoice)) {$selected_archive_states[] = $archivechoice;}  
        }
    $archive=implode(",",$selected_archive_states);
    }
    
hook("searchparameterhandler"); 
    
# If requested, refresh the collection frame (for redirects from saves)
if (getvalescaped("refreshcollectionframe","")!="")
    {
    refresh_collection_frame();
    }

# Initialise the results references array (used later for search suggestions)
$refs=array();

# Special query? Ignore restypes
if (strpos($search,"!")!==false &&  substr($search,0,11)!="!properties" && !$special_search_honors_restypes) {$restypes="";}

# Do the search!
$search=refine_searchstring($search);

$editable_only = getval("foredit","")=="true";

$get_post_array = array_merge($_GET, $_POST);
$search_access = null; # admins can search for resources with a specific access from advanced search
if(array_key_exists("access", $get_post_array))
    {
    $search_access = $get_post_array["access"];
    }
rs_setcookie("access", $search_access, 0, "{$baseurl_short}pages/", "", false, false);

$searchparams= array(
    'search'         => $search,
    'k'              => $k,
    'modal'          => $modal,  
    'display'        => $display,
    'order_by'       => $order_by,
    'offset'         => $offset,
    'per_page'       => $per_page,
    'archive'        => $archive,
    'sort'           => $sort,
    'restypes'       => $restypes,
    'recentdaylimit' => getvalescaped('recentdaylimit', '', true),
    'foredit'        => ($editable_only?"true":""),
    'noreload'       => "true",
    'access'         => $search_access,
);
 
$checkparams = array();
$checkparams[] = "order_by";
$checkparams[] = "sort";
$checkparams[] = "display";
$checkparams[] = "k";

foreach($checkparams as $checkparam)
    {
    if(preg_match('/[^a-z:_\-0-9]/i', $$checkparam))
        {
        exit($lang['error_invalid_input'] . ":- <pre>" . $checkparam . " : " . htmlspecialchars($$checkparam) . "</pre>");
        }
    }

check_order_by_in_table_joins($order_by);

if(false === strpos($search, '!') || '!properties' == substr($search, 0, 11) )
    {
    rs_setcookie('search', $search,0,"","",false,false);
    }

# set cookie when search form has been submitted - controls display of search results link in header_links.php
if( isset($_REQUEST["search"]) && $_REQUEST["search"] == "" )
    {
    rs_setcookie('search_form_submit', true,0,"","",false,false);
    }
hook('searchaftersearchcookie');

$rowstoretrieve = $per_page+$offset;

// Do collections search first as this will determine the rows to fetch for do_search() - not for external shares
if(($k=="" || $internal_share_access) && strpos($search,"!")===false && $archive_standard)
    {
    $collections=do_collections_search($search,$restypes,0,$order_by,$sort,$rowstoretrieve);
    if(is_array($collections))
        {
        $colcount = count($collections);
        } 
    else
        {
        $colcount = 0;
        }
    $resourcestoretrieve = max(($rowstoretrieve-$colcount),0);
    }
else
    {
    $resourcestoretrieve = $rowstoretrieve;
    $colcount = 0;
    }

if ($search_includes_resources || substr($search,0,1)==="!")
    {
    $search_includes_resources=true; // Always enable resource display for special searches.
    if (!hook("replacesearch"))
        {   
        $result=do_search($search,$restypes,$order_by,$archive,$resourcestoretrieve,$sort,false,$starsearch,false,false,$daylimit, getvalescaped("go",""), true, false, $editable_only, false, $search_access);
        $full_dataset = do_search($search, $restypes, $order_by, $archive, -1, $sort, false, $starsearch, false, false, $daylimit, false, false, false, $editable_only, false, $search_access);
        }
    }
else
    {
    $result=array(); # Do not return resources (e.g. for collection searching only)
    $full_dataset = array();
    }

# Allow results to be processed by a plugin
$hook_result=hook("process_search_results","search",array("result"=>$result,"search"=>$search));
if ($hook_result!==false) {$result=$hook_result;}

$count_result = (is_array($result) ? count($result) : 0);

// Log the search and attempt to reduce log spam by only recording initial searches. Basically, if either of the search 
// string or resource types or archive states changed. Changing, for example, display or paging don't count as different
// searches.
$same_search_param = (trim(strip_leading_comma($search)) === $initial_search_cookie);
$same_restypes_param = (trim($restypes) === $initial_restypes_cookie);
$same_archive_param = (trim($archive) === $initial_saved_archive_cookie);
if(!$old_search && (!$same_search_param || !$same_restypes_param || !$same_archive_param))
    {
    log_search_event(trim(strip_leading_comma($search)), explode(',', $restypes), explode(',', $archive), $count_result);
    }

if ($collectionsearch)
    {
    $collectiondata = get_collection($collection);  

    if ($k!="" && !$internal_share_access) {$usercollection=$collection;} # External access - set current collection.
    if (!$collectiondata)
        {?>
        <script>alert('<?php echo $lang["error-collectionnotfound"];?>');document.location='<?php echo $baseurl."/pages/" . $default_home_page;?>'</script>
        <?php
        } 
    # Check to see if this user can edit (and therefore reorder) this resource
    if (($userref==$collectiondata["user"]) || ($collectiondata["allow_changes"]==1) || (checkperm("h")))
        {
        $allow_reorder=true;
        }
    }

# Include function for reordering
if ($allow_reorder && $display!="list")
    {
    # Also check for the parameter and reorder as necessary.
    $reorder=getvalescaped("reorder",false);
    if ($reorder)
        {
        $neworder=json_decode(getvalescaped("order",false));
        update_collection_order($neworder,$collection,$offset);
        exit("SUCCESS");
        }
    }

include ("../include/search_title_processing.php");

    
# Special case: numeric searches (resource ID) and one result: redirect immediately to the resource view.
if ((($config_search_for_number && is_numeric($search)) || $searchresourceid > 0) && is_array($result) && count($result)==1)
    {
    redirect(generateURL($baseurl_short."pages/view.php",$searchparams,array("ref"=>$result[0]["ref"])));
    }
    

# Include the page header to and render the search results
include "../include/header.php";
if($k=="" || $internal_share_access)
    {
     ?>
    <script type="text/javascript">
    var dontReloadSearchBar=<?php echo getval('noreload', null)!=null ? 'true' : 'false' ?>;
    if (dontReloadSearchBar !== true)
        ReloadSearchBar();
    ReloadLinks();
    </script>
    <?php
    }

if($use_selection_collection)
    {
    ?>
    <script>
    var searchparams = <?php echo json_encode($searchparams); ?>;
    </script>
    <?php
    }

// Allow Drag & Drop from collection bar to CentralSpace only when special search is "!collection"
if($collectionsearch && collection_writeable(substr($search, 11)))
    {
    ?>
    <script>
        jQuery(document).ready(function() {
        if(is_touch_device())
            {
            return false;
            }
            jQuery('#CentralSpaceResources').droppable({
                accept: '.CollectionPanelShell',

                drop: function(event, ui) {
                    if(!is_special_search('!collection', 11)) {
                        return false;
                    }

                    // get the current collection from the search page (ie. CentralSpace)
                    var query_strings = getQueryStrings();
                    if(is_empty(query_strings)) {
                        return false;
                    }

                    var resource_id = jQuery(ui.draggable).attr("id");
                    resource_id = resource_id.replace('ResourceShell', '');
                    var collection_id = query_strings.search.substring(11);

                    jQuery('#trash_bin').hide();
                    AddResourceToCollection(event, resource_id, '', collection_id);
                    CentralSpaceLoad(window.location.href, true);
                }
            });

            jQuery('#CentralSpace').trigger('CentralSpaceSortable');
        });
    </script>
    <?php
    }

if(!$collectionsearch)
    {
    ?>
    <!-- Search items should only be draggable if results are not a collection -->
    <script>    
    // The below numbers are hardcoded mid points for thumbs and xlthumbs
    var thumb_vertical_mid = <?php if($display=='xlthumbs'){?>197<?php } else {?>123<?php }?>;
    var thumb_horizontal_mid = <?php if($display=='xlthumbs'){?>160<?php } else {?>87<?php }?>;
    jQuery(document).ready(function() {
        if(is_touch_device())
            {
            return false;
            }
        jQuery('.ResourcePanel').draggable({
            distance: 50,
            connectWith: '#CollectionSpace, .BrowseBarLink',
            appendTo: 'body',
            zIndex: 99000,
            helper: 'clone',
            revert: false,
            scroll: false,
            cursorAt: {top: thumb_vertical_mid, left: thumb_horizontal_mid},
            drag: function (event, ui)
                {
                jQuery(ui.helper).css('opacity','0.6');
                jQuery(ui.helper).css('transform','scale(0.8)');
                },
        });
    });
    </script>
    <?php
    }
    
if ($allow_reorder && $display!="list" && $order_by == "collection") {
    global $usersession;
?>
    <script type="text/javascript">
    var allow_reorder = true;
    
    function ReorderResources(idsInOrder) {
        var newOrder = [];
        jQuery.each(idsInOrder, function() {
            newOrder.push(this.substring(13));
            });
        jQuery.ajax({
          type: 'POST',
          url: 'search.php?search=!collection<?php echo urlencode($collection) ?>&reorder=true&offset=<?php echo urlencode($offset) ?>',
          data: {
            order: JSON.stringify(newOrder),
            <?php echo generateAjaxToken('reorder_search'); ?>
            },
          success: function(){
          <?php if (isset($usercollection) && ($usercollection==$collection)) { ?>
             UpdateCollectionDisplay('<?php echo isset($k)?htmlspecialchars($k):"" ?>');
          <?php } ?>
            } 
        });
    }
    jQuery('#CentralSpace').on('CentralSpaceSortable', function() {
        if(is_touch_device())
            {
            return false;
            }
        jQuery('.ui-sortable').sortable('enable');
        jQuery('#CentralSpaceResources').sortable({
            connectWith: '#CollectionSpace',
            appendTo: 'body',
            zIndex: 99000,
            scroll: false,
            helper: function(event, ui)
                {
                //Hack to append the element to the body (visible above others divs), 
                //but still belonging to the scrollable container
                jQuery('#CentralSpaceResources').append('<div id="CentralSpaceResourceClone" class="ui-state-default">' + ui[0].outerHTML + '</div>');   
                jQuery('#CentralSpaceResourceClone').hide();
                setTimeout(function() {
                    jQuery('#CentralSpaceResourceClone').appendTo('body'); 
                    jQuery('#CentralSpaceResourceClone').show();
                }, 1);
                
                return jQuery('#CentralSpaceResourceClone');
                },
            items: '.ResourcePanel',
            cancel: '.DisableSort',
            
            start: function (event, ui)
                {
                InfoBoxEnabled=false;
                if (jQuery('#InfoBox')) {jQuery('#InfoBox').hide();}
                if (jQuery('#InfoBoxCollection')) {jQuery('#InfoBoxCollection').hide();}
                if(is_special_search('!collection', 11))
                    {
                    // get the current collection from the search page (ie. CentralSpace)
                    var query_strings = getQueryStrings();
                    if(is_empty(query_strings))
                        {
                        return false;
                        }
                    var collection_id = query_strings.search.substring(11);

                    jQuery('#trash_bin').show();
                    }
                },

            update: function(event, ui)
                {
                // Don't reorder when top and bottom collections are the same and you drag & reorder from top to bottom
                if(ui.item[0].parentElement.id == 'CollectionSpace')
                    {
                    return false;
                    }

                InfoBoxEnabled=true;

                <?php if ($sort == "ASC")
                    {?>
                    var idsInOrder = jQuery('#CentralSpaceResources').sortable("toArray");
                    ReorderResources(idsInOrder);
                    <?php
                    }
                else
                    {?>
                   return false;
                    <?php
                    }?>
                    
                if(is_special_search('!collection', 11))
                    {
                    jQuery('#trash_bin').hide();
                    }
                },

            stop: function(event, ui)
                {
                InfoBoxEnabled=true;
                if(is_special_search('!collection', 11))
                    {
                    jQuery('#trash_bin').hide();
                    }
                }
        });
        jQuery('.ResourcePanelShell').disableSelection();
        jQuery('.ResourcePanelShellLarge').disableSelection();
        jQuery('.ResourcePanelShellSmall').disableSelection();

        // CentralSpace should only be sortable (ie. reorder functionality) for collections only
        if(!allow_reorder)
            {
            jQuery('#CentralSpaceResources').sortable('disable');
            }
    });
    </script>
<?php }
    elseif (!hook("noreorderjs")) { ?>
    <script type="text/javascript">
        var allow_reorder = false;
        jQuery(document).ready(function () {
            jQuery('#CentralSpaceResources .ui-sortable').sortable('disable');
            jQuery('.ResourcePanelShell').enableSelection();
            jQuery('.ResourcePanelShellLarge').enableSelection();
            jQuery('.ResourcePanelShellSmall').enableSelection();
        });
    
    </script>
    <?php }

if(getval("promptsubmit","")!= "" && getval("archive","")=="-2" && checkperm("e-1"))
    {
    // User has come here from upload. Show a prompt to submit the resources for review
    ?>
    <script>    
    jQuery(document).ready(function() {
        jQuery("#modal_dialog").html('<?php echo $lang["submit_dialog_text"]; ?>');
        jQuery("#modal_dialog").dialog({
                                    title:'<?php echo $lang["submit_review_prompt"]; ?>',
                                    modal: true,
                                    width: 400,
                                    resizable: false,
                                    dialogClass: 'no-close',
                                    buttons: {
                                        "<?php echo $lang['action_submit_review'] ?>": function() {
                                                jQuery.ajax({
                                                    type: "POST",
                                                    dataType: "json",
                                                    url: baseurl_short+"pages/ajax/user_action.php",
                                                    data: {
                                                        "action" : "submitpending",
                                                        "collection_add" : "<?php echo $collection_add ?>",
                                                        <?php echo generateAjaxToken('submit_for_review'); ?>
                                                    },
                                                    success: function(response){
                                                            <?php
                                                            if(is_numeric($collection_add))
                                                                {
                                                                echo "window.location.href='" .  $baseurl_short . "pages/search.php?search=!collection" . $collection_add . "';";
                                                                }
                                                            else
                                                                {
                                                                echo "window.location.href='" .  $baseurl_short . "pages/search.php?search=!contributions" . $userref . "&archive=-1&order_by=date&sort=desc';";
                                                                }
                                                            ?>
                                                            },
                                                    error: function(response) {
                                                        styledalert('<?php echo $lang["error"]; ?>',response['responseJSON']['message']);
                                                        }
                                                    });
                                                
                                                
                                            },    
                                        "<?php echo $lang['action_continue_editing'] ?>": function() { 
                                                jQuery(this).dialog('close');
                                                <?php 
                                                if ($collection_add!="")
                                                    {?>
                                                    window.location.href='<?php echo $baseurl_short?>pages/search.php?search=!collection<?php echo $collection_add ?>';
                                                    <?php
                                                    }?>
                                                }
                                            }
                                });
    });
    </script>
    <?php
    }

# Hook to replace all search results (used by ResourceConnect plugin, allows search mechanism to be entirely replaced)
if (!hook("replacesearchresults")) { 

# Extra CSS to support more height for titles on thumbnails.
if (isset($result_title_height))
    {
    ?>
    <style>
    .ResourcePanelInfo .extended
        {
        white-space:normal;
        height: <?php echo $result_title_height ?>px;
        }
    </style>
    <?php
    }

hook('searchresultsheader');

if ($search_titles)
    {
    hook("beforesearchtitle");
    echo $search_title;
    hook("aftersearchtitle");
    hook("beforecollectiontoolscolumn");
    }

if (!hook("replacesearchheader")) # Always show search header now.
    {
    $resources_count=is_array($result)?count($result):0;
    if (isset($collections)) 
        {
        $result_count=$colcount+$resources_count;
        }
    else
        {
        $result_count = $resources_count;
        }
    ?>
    <div class="BasicsBox">
    <div class="TopInpageNav">
    <div class="TopInpageNavLeft">

<?php
if($responsive_ui)
    {
    ?>
    <div class="ResponsiveResultDisplayControls">
        <a href="#" id="Responsive_ResultDisplayOptions" class="ResourcePanel ResponsiveButton" style="display:none;"><?php echo $lang['responsive_result_settings']; ?></a>
        <div id="ResponsiveResultCount" style="display:none;">
        <?php
        if($use_selection_collection && $selection_collection_resources_count > 0)
            {
            echo render_selected_resources_counter(count($selection_collection_resources));
            }
        else if(isset($collections)) 
            {
            ?>
            <span class="Selected">
            <?php
            echo number_format($result_count);
            ?>
            </span>
            <?php
            echo ($result_count==1) ? $lang['youfoundresult'] : $lang['youfoundresults'];
            } 
        else
            {
            ?>
            <span class="Selected">
            <?php
            echo number_format($resources_count);
            ?>
            </span>
            <?php
            echo ($resources_count==1)? $lang['youfoundresource'] : $lang['youfoundresources'];
            }
            ?>
        </div>
    </div>
    <?php
    }
    hook('responsiveresultoptions');
    ?>
    <div id="SearchResultFound" class="InpageNavLeftBlock">
    <?php
    if($use_selection_collection && $selection_collection_resources_count > 0)
        {
        echo render_selected_resources_counter(count($selection_collection_resources));
        }
    else if (isset($collections)) 
        {
        ?>
        <span class="Selected">
        <?php
        echo number_format($result_count)?> </span><?php echo ($result_count==1) ? $lang["youfoundresult"] : $lang["youfoundresults"];
        }
    else
        {
        ?>
        <span class="Selected">
        <?php
        echo number_format($resources_count)?> </span><?php echo ($resources_count==1)? $lang["youfoundresource"] : $lang["youfoundresources"];
        }
     ?></div>
    <?php
    if(!hook('replacedisplayselector','',array($search,(isset($collections)?$collections:""))))
        {
        ?>
        <div class="InpageNavLeftBlock <?php if($iconthumbs) {echo 'icondisplay';} ?>">
        <?php   
        if($display_selector_dropdowns)
            {
            ?>
            <select style="width:auto" id="displaysize" name="displaysize" onchange="CentralSpaceLoad(this.value,true);">
            <?php if ($xlthumbs==true) { ?><option <?php if ($display=="xlthumbs"){?>selected="selected"<?php } ?> value="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"xlthumbs")) ?>"><?php echo $lang["xlthumbs"]?></option><?php } ?>
            <option <?php if ($display=="thumbs"){?>selected="selected"<?php } ?> value="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"thumbs")) ?>"><?php echo $lang["largethumbs"]?></option>
            <?php if ($searchlist==true) { ?><option <?php if ($display=="list"){?>selected="selected"<?php } ?> value="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"list")) ?>"><?php echo $lang["list"]?></option><?php } ?>
            </select>
            <?php
            }
        elseif($iconthumbs)
            {
            if($xlthumbs == true)
                {
                if($display == 'xlthumbs')
                    {
                    ?><span class="xlthumbsiconactive"></span><?php
                    }
                else
                    {
                    ?>
                    <a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"xlthumbs")); ?>" title='<?php echo $lang["xlthumbstitle"] ?>' onClick="return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this);">
                        <span class="xlthumbsicon"></span>
                    </a>
                    <?php
                    }
                }
            if($display == 'thumbs')
                {
                ?><span class="largethumbsiconactive"></span><?php
                }
            else
                {
                ?>
                <a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"thumbs")); ?>" title='<?php echo $lang["largethumbstitle"] ?>' onClick="return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this);">
                    <span class="largethumbsicon"></span>
                </a>
                <?php
                }
            if($display == 'strip')
                {
                ?><span class="stripiconactive"></span><?php
                }
            else
                {
                ?>
                <a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"strip")); ?>" title='<?php echo $lang["striptitle"] ?>' onClick="return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this);">
                    <span class="stripicon"></span>
                </a>
                <?php
                }
                
            if ($searchlist == true) 
                {
                if($display == 'list')
                    {
                    ?><span class="smalllisticonactive"></span><?php
                    }
                else
                    {
                    ?>
                    <a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"list")); ?>" title='<?php echo $lang["listtitle"] ?>' onClick="return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this);">
                        <span class="smalllisticon"></span>
                    </a>
                    <?php
                    }
                }
    
                hook('adddisplaymode');
            }
        else
            {
            if ($xlthumbs==true) { ?> <?php if ($display=="xlthumbs") { ?><span class="Selected"><?php echo $lang["xlthumbs"]?></span><?php } else { ?><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"xlthumbs")) ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["xlthumbs"]?></a><?php } ?>&nbsp; |&nbsp;<?php } ?>
            <?php if ($display=="thumbs") { ?> <span class="Selected"><?php echo $lang["largethumbs"]?></span><?php } else { ?><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"thumbs")) ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["largethumbs"]?></a><?php } ?>&nbsp; |&nbsp; 
            <?php if ($display=="strip") { ?><span class="Selected"><?php echo $lang["striptitle"]?></span><?php } else { ?><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"strip")) ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["striptitle"]?></a><?php } ?>&nbsp; |&nbsp;
            <?php if ($display=="list") { ?> <span class="Selected"><?php echo $lang["list"]?></span><?php } else { ?><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("display"=>"list")) ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["list"]?></a><?php } ?> <?php hook("adddisplaymode"); ?> 
            <?php
            }
        ?>
        </div>
        <?php
        }
    if ($display_selector_dropdowns && $recent_search_period_select && strpos($search,"!")===false && getvalescaped('recentdaylimit', '', true)==""){?>
    <div class="InpageNavLeftBlock">
        <select style="width:auto" id="resultsdisplay" name="resultsdisplay" onchange="CentralSpaceLoad(this.value,true);">
        <?php for($n=0;$n<count($recent_search_period_array);$n++){
            if ($display_selector_dropdowns){?>
                <option <?php if ($daylimit==$recent_search_period_array[$n]){?>selected="selected"<?php } ?> value="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("daylimit"=>$recent_search_period_array[$n])); ?>"><?php echo str_replace("?",$recent_search_period_array[$n],$lang["lastndays"]); ?></option>
            <?php } ?>
        <?php } ?>
        <option <?php if ($daylimit==""){?>selected="selected"<?php } ?> value="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("daylimit"=>"")); ?>"><?php echo $lang["anyday"] ?></option>
        </select>
    </div>
    <?php } 
    
    # order by
    #if (strpos($search,"!")===false)
    if ($search!="!duplicates" && $search!="!unused" && !hook("replacesearchsortorder")) 
        {
        // Relevance is the default sort sequence if viewing resources following a search
        $default_sort_order='relevance';
        $rel=$lang["relevance"];
        if(!hook("replaceasadded"))
            {
            if (isset($collection))
                {
                // Collection is the default sort sequence if viewing resources in a collection
                $default_sort_order='collection';
                $rel=$lang["collection_order_description"];
                }
            elseif (strpos($search,"!")!==false && substr($search,0,11)!="!properties") 
                {
                // As Added is the default sort sequence if viewing recently added resources 
                $default_sort_order = 'resourceid';
                $rel=$lang["asadded"];
                }
            }
        // Build the available sort sequence entries, starting with the default derived above
        $orderFields = array($default_sort_order => $rel);
        if ($random_sort)
            $orderFields['random'] = $lang['random'];
        if ($popularity_sort)
            $orderFields['popularity'] = $lang['popularity'];
        if ($orderbyrating)
            $orderFields['rating'] = $lang['rating'];
        if ($date_column)
            $orderFields['date'] = $lang['date'];
        if ($colour_sort)
            $orderFields['colour'] = $lang['colour'];
        if ($order_by_resource_id)
            $orderFields['resourceid'] = $lang['resourceid'];
        if ($order_by_resource_type)
            $orderFields['resourcetype'] = $lang['type'];
        
        $orderFields['modified'] = $lang['modified'];

        # Add thumbs_display_fields to sort order links for thumbs views
        for ($x=0;$x<count($sf);$x++)
            {
            if (!isset($metadata_template_title_field)){$metadata_template_title_field=false;}
            if ($sf[$x]['ref']!=$metadata_template_title_field)
                {
                $orderFields['field' . $sf[$x]['ref']] = htmlspecialchars($sf[$x]['title']);
                }
            }

        $modifiedFields = hook('modifyorderfields', '', array($orderFields));
        if ($modifiedFields)
            $orderFields = $modifiedFields;

        if (!hook('sortordercontainer'))
            {
            ?>
            <div id="searchSortOrderContainer" class="InpageNavLeftBlock ">
            <?php

            if(!hook('render_sort_order_differently', '', array($orderFields)))
                {
                render_sort_order($orderFields,$default_sort_order);
                }

            hook('sortorder');
            ?>
            </div>
            <?php
            }
        }

        if($display_selector_dropdowns || $perpage_dropdown)
            {
            ?>
            <div class="InpageNavLeftBlock">
                <select id="resultsdisplay" style="width:auto" name="resultsdisplay" onchange="<?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this.value,true);">
            <?php
            for($n = 0; $n < count($results_display_array); $n++)
                {
                if($display_selector_dropdowns || $perpage_dropdown)
                    {
                    if (isset($searchparams["offset"]))
                        {
                        $new_offset = floor($searchparams["offset"] / $results_display_array[$n]) * $results_display_array[$n];
                        }
                    ?>
                    <option <?php if($per_page == $results_display_array[$n]) { ?>selected="selected"<?php } ?> value="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("per_page"=>$results_display_array[$n],"offset"=>$new_offset)); ?>"><?php echo str_replace("?",$results_display_array[$n],$lang["perpage_option"]); ?></option>
                    <?php
                    }
                }
                ?>
                </select>
            </div>
            <?php
            }

        if(!isset($collectiondata) || !$collectiondata)
            {
            $collectiondata = array();
            $collectionsearch = false;
            }

        $url=generateURL($baseurl . "/pages/search.php",$searchparams); // Moved above render_actions as $url is used to render search actions
        if($use_selection_collection && $selection_collection_resources_count > 0)
            {
            render_selected_collection_actions();
            }
        else
            {
            render_actions($collectiondata, true, false, "", $full_dataset);
            }

        hook("search_header_after_actions");

        if(isset($is_authenticated) && $is_authenticated)
            {
            render_upload_here_button($searchparams);

            if($use_selection_collection && $selection_collection_resources_count > 0)
                {
                render_edit_selected_btn();
                render_clear_selected_btn();
                }
            }
        
        if (!$display_selector_dropdowns && !$perpage_dropdown){?>
        <div class="InpageNavLeftBlock">
        <?php 
        for($n=0;$n<count($results_display_array);$n++){?>
        <?php if ($per_page==$results_display_array[$n]){?><span class="Selected"><?php echo urlencode($results_display_array[$n])?></span><?php } else { ?><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("per_page"=>$results_display_array[$n])); ?>" onClick="return CentralSpaceLoad(this);"><?php echo urlencode($results_display_array[$n])?></a><?php } ?><?php if ($n>-1&&$n<count($results_display_array)-1){?>&nbsp;|<?php } ?>
        <?php } ?>
        </div>
        <?php } 
    
        if (!$display_selector_dropdowns && $recent_search_period_select && strpos($search,"!")===false && getvalescaped('recentdaylimit', '', true)==""){?>
        <div class="InpageNavLeftBlock">
        <?php 
        for($n=0;$n<count($recent_search_period_array);$n++){
            if ($daylimit==$recent_search_period_array[$n]){?><span class="Selected"><?php echo htmlspecialchars(str_replace("?",$recent_search_period_array[$n],$lang["lastndays"]))?> </span>&nbsp;|&nbsp;<?php } else { ?><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("daylimit"=>$recent_search_period_array[$n])); ?>" onClick="return CentralSpaceLoad(this);"><?php echo htmlspecialchars(str_replace("?",$recent_search_period_array[$n],$lang["lastndays"]))?></a>&nbsp;|&nbsp;<?php } 
            }
        if ($daylimit==""){?><span class="Selected"><?php echo $lang["all"] ?></span><?php } else { ?><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("daylimit"=>"")); ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["all"]?></a><?php } 
        ?>              
        </div>
        <?php } ?>      
        
    <?php
    $totalpages=ceil($result_count/$per_page);
    if ($offset>$result_count) {$offset=0;}
    $curpage=floor($offset/$per_page)+1;
    
    ?>
    </div>
    <?php hook("stickysearchresults"); ?> <!--the div TopInpageNavRight was added in after this hook so it may need to be adjusted -->
    <div class="TopInpageNavRight">
    <?php
        pager(false);
        $draw_pager=true;
    ?>
    </div>
    <div class="clearerleft"></div>
    </div>
    </div>
    <?php
} 
    hook("stickysearchresults");

    // Show collection title and description.
    if ($collectionsearch)
        {
        if ((isset($collectiondata) && array_key_exists("name",$collectiondata)) && $show_collection_name)
            { ?>
            <div class="RecordHeader">
                <h1 class="SearchTitle">
                <?php echo i18n_get_collection_name($collectiondata); ?>
                </h1>
            <?php
            if((isset($collectiondata) && array_key_exists("description",$collectiondata)) && trim($collectiondata['description']) != "")
                {
                echo "<p>" . htmlspecialchars($collectiondata['description']) . "</p>";
                }
            echo "</div>";
            }
        }
    
    hook("beforesearchresults");
    
    # Archive link
    if ((!$archivesearched) && (strpos($search,"!")===false) && $archive_search && $archive_standard) 
        {        
        $saved_archive_standard = $archive_standard;
        $archive_standard = false;
        // Search archive but don't log this to daily_stat
        $arcresults=do_search($search,$restypes,$order_by,2,0,'desc',false,0,false,false,'',false,false);
        $archive_standard = $saved_archive_standard;
        
        if (is_array($arcresults)) {$arcresults=count($arcresults);} else {$arcresults=0;}
        if ($arcresults>0) 
            {
            ?>
            <div class="SearchOptionNav"><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("archive"=>2)); ?>" onClick="return CentralSpaceLoad(this);"><?php echo LINK_CARET ?><?php echo $lang["view"]?> <span class="Selected"><?php echo number_format($arcresults)?></span> <?php echo ($arcresults==1)?$lang["match"]:$lang["matches"]?> <?php echo $lang["inthearchive"]?></a></div>
            <?php 
            }
        else
            {
            ?>
            <div class="InpageNavLeftBlock"><?php echo LINK_CARET ?><?php echo $lang["nomatchesinthearchive"]?></div>
            <?php 
            }
        }
    echo $search_title_links;
    if($collectionsearch) // Fetch collection name 
        {
            $collectionsearchname = htmlspecialchars($collectiondata["name"]);
        }
    else
        {
            $collectionsearchname = "";
        }
    hook("beforesearchresults2");
    hook("beforesearchresultsexpandspace");
    
    // DRAG AND DROP TO UPLOAD FUNCTIONALITY
    // Generate a URL for drag drop function - fires same URL as "upload here" when dragging.
    $drag_upload_params=render_upload_here_button($searchparams,true);
    $drag_over="";
    if (is_array($drag_upload_params) && ($display=='thumbs' || $display=='xlthumbs') && $order_by == 'collection')
        {
        $drag_url=generateURL("{$GLOBALS['baseurl']}/pages/upload_plupload.php", $drag_upload_params);
        $drag_over=" onDragOver=\"UploadViaDrag('" . $drag_url . "');\" ";
        }
    ?>
    <script>
    var DragUploading=false
    function UploadViaDrag(url)
        {
        if (DragUploading) {return false;}
        DragUploading=true;CentralSpaceLoad(url);
        }
    </script>
    
    
    <div class="clearerleft"></div>
    <div id="CentralSpaceResources" collectionSearchName="<?php echo $collectionsearchname ?>" <?php echo $drag_over ?>>
    <?php

    if ((!is_array($result) || count($result)<1) && empty($collections))
        {
        // No matches found? Log this in
        ?>
        <div class="BasicsBox"> 
          <div class="NoFind">
            <p><?php echo $lang["searchnomatches"]?></p>
            <?php
            if(!$collectionsearch) // Don't show hints if a collection search is empty 
                {
                if ($result!="" && !is_array($result))
                    {
                    ?>
                    <p><?php echo $lang["try"]?>: <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode(strip_tags($result))?>"><?php echo strip_tags($result)?></a></p>
                    <?php $result=array();
                    }
                else
                    {
                    ?>
                    <p><?php if (strpos($search,"country:")!==false) { ?><p><?php echo $lang["tryselectingallcountries"]?> <?php } 
                    elseif (strpos($search,"basicyear:")!==false) { ?><p><?php echo $lang["tryselectinganyyear"]?> <?php } 
                    elseif (strpos($search,"basicmonth:")!==false) { ?><p><?php echo $lang["tryselectinganymonth"]?> <?php } 
                    elseif (strpos($search,":")!==false) { ?><p><?php echo $lang["field_search_no_results"]; } 
                    else        {?><?php echo $lang["trybeinglessspecific"]?><?php } ?> <?php echo $lang["enteringfewerkeywords"]?></p>
                    <?php
                    }
                hook("afterresulthints");
                }
          ?>
          </div>
        </div>
        <?php
        }

    $list_displayed = false;
    # Listview - Display title row if listview and if any result.
    if ($display=="list" && ((is_array($result) && count($result)>0) || (isset($collections) && is_array($collections) && $colcount>0)))
        {
        $list_displayed = true;
        ?>
        <div class="BasicsBox"><div class="Listview">
        <table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">

        <?php if(!hook("replacelistviewtitlerow")){?>   
        <tr class="ListviewTitleStyle">
        <?php if (!hook("listcheckboxesheader")){?>
        <?php if ($use_selection_collection){?><td><?php echo $lang['addremove'];?></td><?php } ?>
        <?php } # end hook listcheckboxesheader 

        for ($x=0;$x<count($df);$x++)
            {?>
            <?php if ($order_by=="field".$df[$x]['ref']) {?><td class="Selected"><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"field" . $df[$x]['ref'],"sort"=>$revsort)); ?>" onClick="return CentralSpaceLoad(this);"><?php echo htmlspecialchars($df[$x]['title'])?></a><div class="<?php echo urlencode($sort)?>">&nbsp;</div></td><?php } else { ?><td><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"field" . $df[$x]['ref'])); ?>" onClick="return CentralSpaceLoad(this);"><?php echo htmlspecialchars($df[$x]['title'])?></a></td><?php } ?>
            <?php }
        
        hook("searchbeforeratingfieldtitlecolumn");
        if ($id_column){?><?php if ($order_by=="resourceid"){?><td class="Selected"><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"resourceid","sort"=>$revsort)); ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["id"]?></a><div class="<?php echo urlencode($sort)?>">&nbsp;</div></td><?php } else { ?><td><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"resourceid")); ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["id"]?></a></td><?php } ?><?php } ?>
        <?php if ($resource_type_column){?><?php if ($order_by=="resourcetype"){?><td class="Selected"><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"resourcetype","sort"=>$revsort)); ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["type"]?></a><div class="<?php echo urlencode($sort)?>">&nbsp;</div></td><?php } else { ?><td><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"resourcetype","sort"=>"ASC")); ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["type"]?></a></td><?php } ?><?php } ?>
        <?php if ($list_view_status_column){?><?php if ($order_by=="status"){?><td class="Selected"><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"status","sort"=>$revsort)); ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["status"]?></a><div class="<?php echo urlencode($sort)?>">&nbsp;</div></td><?php } else { ?><td><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"status")); ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["status"]?></a></td><?php } ?><?php } ?>
        <?php if ($date_column){?><?php if ($order_by=="date"){?><td class="Selected"><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"date","sort"=>$revsort)); ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["date"]?></a><div class="<?php echo urlencode($sort)?>">&nbsp;</div></td><?php } else { ?><td><a href="<?php echo generateURL($baseurl_short."pages/search.php",$searchparams,array("order_by"=>"date")); ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["date"]?></a></td><?php } ?><?php } ?>
        <?php hook("addlistviewtitlecolumn");?>
        <td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
        </tr>
        <?php } ?> <!--end hook replace listviewtitlerow-->
        <?php
        }
        # Include public collections and themes in the main search, if configured.      
        if (isset($collections)&& strpos($search,"!")===false && $archive_standard && !hook('replacesearchpublic','',array($search,$collections)))
            {
            include "../include/search_public.php";
            }
    if ($search_includes_resources) {

    hook('searchresources');
    
    # work out common keywords among the results
    if (is_array($result) && (count($result)>$suggest_threshold) && (strpos($search,"!")===false) && ($suggest_threshold!=-1))
        {
        for ($n=0;$n<count($result);$n++)
            {
            if ($result[$n]["ref"]) {$refs[]=$result[$n]["ref"];} # add this to a list of results, for query refining later
            }
        $suggest=suggest_refinement($refs,$search);
        if (count($suggest)>0)
            {
            ?><p><?php echo $lang["torefineyourresults"]?>: <?php
            for ($n=0;$n<count($suggest);$n++)
                {
                if ($n>0) {echo ", ";}
                ?><a  href="<?php echo $baseurl_short?>pages/search.php?search=<?php echo  urlencode(strip_tags($suggest[$n])) ?>" onClick="return CentralSpaceLoad(this);"><?php echo stripslashes($suggest[$n])?></a><?php
                }
            ?></p><?php
            }
        }
        
    $rtypes=array();
    if (!isset($types)){$types=get_resource_types();}
    for ($n=0;$n<count($types);$n++) {$rtypes[$types[$n]["ref"]]=$types[$n]["name"];}
    if (is_array($result) && count($result)>0)
        {
        /**
         * If global var $annotate_enabled global == true, then ResourcePanel height is adjusted in thumbs.php.
         * If there is a mix of resource_types in results, and there is a config option for a particular resource_type that overrides $annotate_enabled, then display of ResourcePanels in search.php is affected.
         * This line detects if $annotate_enabled == true in config, and ensures that all ResourcePanels have same height value 
         */
        if ($annotate_enabled == true) 
            {
            $annotate_enabled_adjust_size_all = true;
            }
     
        # loop and display the results
        $startresource = max($offset-$colcount,0);
        $endresource = $result_count-$colcount;
        for ($n=$startresource;(($n<$endresource) && ($n<($resourcestoretrieve)));$n++)
            {
            # Allow alternative configuration settings for this resource type.
            resource_type_config_override($result[$n]["resource_type"]);
            
            if ($order_by=="resourcetype" && $display!="list")
                {
                if ($n==0 || ((isset($result[$n-1])) && $result[$n]["resource_type"]!=$result[$n-1]["resource_type"]))
                    {
                    if($result[$n]["resource_type"]!="")
                        {
                        echo "<h1 class=\"SearchResultsDivider\" style=\"clear:left;\">" . htmlspecialchars($rtypes[$result[$n]["resource_type"]]) .  "</h1>";
                        }
                    else
                        {
                        echo "<h1 class=\"SearchResultsDivider\" style=\"clear:left;\">" . $lang['unknown'] .  "</h1>";
                        }
                    }
                }

            $ref = $result[$n]["ref"];
        
            $GLOBALS['get_resource_data_cache'][$ref] = $result[$n];
            $url=generateURL($baseurl_short."pages/view.php",$searchparams, array("ref"=>$ref));
            
            if ($result[$n]["access"]==0 && !checkperm("g") && !$internal_share_access)
                {
                # Resource access is open but user does not have the 'g' permission. Set access to restricted. If they have been granted specific access this will be added next
                $result[$n]["access"]=1; 
                }           
                
            // Check if user or group has been granted specific access level as set in array returned from do_search function. 
            if(isset($result[$n]["user_access"]) && $result[$n]["user_access"]!="")
                {$result[$n]["access"]=$result[$n]["user_access"];}
            elseif (isset($result[$n]["group_access"]) && $result[$n]["group_access"]!="")
                {$result[$n]["access"]=$result[$n]["group_access"];}
            // Global $access needs to be set to check watermarks in search views (and may be used in hooks)        
            $access=$result[$n]["access"];
        
            if (isset($result[$n]["url"])) {$url = $result[$n]["url"];} # Option to override URL in results, e.g. by plugin using process_Search_results hook above
 
            hook('beforesearchviewcalls');

            if ($display=="thumbs")
                {
                #  ---------------------------- Thumbnails view ----------------------------
                include 'search_views/thumbs.php';
                } 

            if ($display=="strip")
                {
                #  ---------------------------- Thumbnails view ----------------------------
                include 'search_views/strip.php';
                }


            if ($display=="xlthumbs")
                {
                #  ---------------------------- X-Large Thumbnails view ----------------------------
                include "search_views/xlthumbs.php";
                }

            if ($display=="list")
                {
                # ----------------  List view -------------------
                include "search_views/list.php";
                }

            if ($display=="stripes")
                {
                # ----------------  Stripes view -------------------
                include "search_views/stripes.php";
                }

            hook('customdisplaymode');
            }
    }
        }
    # Listview - Add closing tag if a list is displayed.
    if ($list_displayed==true)
        {
        ?>
        </table>
        </div></div>
        <?php
        }
    
if ($display=="strip")
    {
    #  ---------------------------- Extra footer for strip view ----------------------------
    include 'search_views/' . $display . '_footer.php';
    }

$url=generateURL($baseurl . "/pages/search.php",$searchparams); 

?>
</div> <!-- end of CentralSpaceResources -->

<?php
if(!$modal)
    {
    if(!hook('bottomnavigation'))
        {
        ?>
        <!--Bottom Navigation - Archive, Saved Search plus Collection-->
        <div class="BottomInpageNav">
            <?php hook('add_bottom_in_page_nav_left'); ?>
            <div class="BottomInpageNavRight">  
           <?php 
           if (isset($draw_pager)) {pager(false);} 
            ?>
            </div>
            <div class="clearerleft"></div>
        </div>
        <?php
        }
    }
} # End of replace all results hook conditional

hook("endofsearchpage");

if($search_anchors)
    {
    ?>
    <script>
    place     = '<?php echo getvalescaped("place", ""); ?>';
    display   = '<?php echo $display; ?>';
    highlight = '<?php echo $search_anchors_highlight; ?>';

    jQuery(document).ready(function()
        {
        if(place)
            {
            ele_id        = 'ResourceShell' + place;
            elementScroll = document.getElementById(ele_id);

            if(jQuery(elementScroll).length)
                {
                elementScroll.scrollIntoView();

                if(highlight)
                    {
                    jQuery(elementScroll).addClass('search-anchor');
                    }
                }
            }
        });
    </script>
    <?php
    }
    ?>
<script>
function toggle_addremove_to_collection_icon(el)
    {
    var icon = jQuery(el);
    var resource_shell = jQuery('#ResourceShell' + icon.data('resource-ref'));

    if(icon.hasClass('addToCollection'))
        {
        icon.addClass('DisplayNone');
        var rfc = icon.siblings('.removeFromCollection');
        if(rfc.length > 0)
            {
            jQuery(rfc[0]).removeClass('DisplayNone');
            }
        }
    else if(icon.hasClass('removeFromCollection'))
        {
        var atc = resource_shell.find('div.ResourcePanelIcons > a.addToCollection');
        if(atc.length > 0)
            {
            jQuery(atc[0]).removeClass('DisplayNone');
            }

        var rfc = atc.siblings('.removeFromCollection');
        if(rfc.length > 0)
            {
            jQuery(rfc[0]).addClass('DisplayNone');
            }
        }

    return;
    }


<?php
if($use_selection_collection)
    {
    ?>
    jQuery(document).ready(function()
        {
        var resource_starting=null; // Regular click resource marks the start of a range
        var resource_ending=null; // Shifted click resource marks the end of a range
        var primary_action = null;

        // Process the clicked box
        jQuery(".checkselect").click(function(e)
            {
            var resource_selections=[];
            var input = e.target;
            var box_resource = jQuery(input).data("resource");
            var box_checked = jQuery(input).prop("checked");
            if (!e.shiftKey) {
                // Regular click; note the action required if there is a range to be processed
                primary_action=box_checked;
                resource_starting=box_resource;
                resource_ending=null;
            } else {
                if (!resource_starting) {
                    alert('Cannot end range without a start.\nPlease release the shift key.');
                    if(jQuery(input).prop("checked")) {
                        this.removeAttribute("checked");
                        } 
                    else  {
                        this.setAttribute("checked", "checked");
                        }
                    return false;
                }
                resource_ending=box_resource; // Shifted click resource
            }

            // Process all clicked boxes
            jQuery(".checkselect").each(function()
                {
                // Fetch the event and store it in the selection array
                var toggle_event = jQuery.Event("click", { target: this });
                var toggle_input = toggle_event.target;
                var box_resource = jQuery(toggle_input).data("resource");
                var box_checked = jQuery(toggle_input).prop("checked");
                resource_selections.push({box_resource: box_resource, box_checked: box_checked});
                });

            // Process resources within a clicked range
            var res_list=[];
            if (resource_starting && resource_ending) {
                console.log("PROCESS " + resource_starting + " TO " + resource_ending);
                var found_start = false;
                var found_end = false;
                for (i = 0; i < resource_selections.length; i++) {
                    if (resource_selections[i].box_resource == resource_starting) {
                        // Range starting point is being processed; skip because already processed by single shot; move on
                        found_start = true;
                    }
                    else if (resource_selections[i].box_resource == resource_ending) {
                        // Range ending point is being processed; process it and move on (because it may be before the startin point)
                        found_end = true;
                        res_list.push(resource_selections[i].box_resource); // Resource to process
                    }
                    else {
                        // Element is not at the starting point or ending point; check whether its within the range
                        if ( !found_start && !found_end ) {
                            // Range is not yet being processed; skip
                        }
                        else if (found_start && found_end) {
                            // Both starting and ending points have been processed; quit loop
                            break;
                        }
                        else {
                            // Process the element within the range
                            res_list.push(resource_selections[i].box_resource); // Resource to process
                        }
                    }
                }
                
                // AJAX will be used to send multiple resources and actions
                var csrf_data = '{<?php echo generateAjaxToken("ProcessCollectionResourceSelection"); ?>}';
                // Convert token from format {CSRFToken:"data"} to strict JSON format which is {"CSRFToken":"data"} so that it can be parsed 
                var csrf_data = csrf_data.replace('<?php echo $CSRF_token_identifier; ?>','"<?php echo $CSRF_token_identifier; ?>"');
                ProcessCollectionResourceSelection(res_list, primary_action, '<?php echo $USER_SELECTION_COLLECTION; ?>', csrf_data);

                // Reset processing points
                resource_starting=null;
                resource_ending=null;
                primary_action = null;
                }

            else if (resource_starting) {
                console.log("PROCESS " + resource_starting + " ONLY");
                for (i = 0; i < resource_selections.length; i++) {
                    if (resource_selections[i].box_resource == resource_starting) {
                        // Range starting point is being processed; skip because already processed by single shot; move on
                        res_list.push(resource_selections[i].box_resource); // One resource to process
                        break;
                    }
                }

                // AJAX will be used to send single resource and action only
                var csrf_data = '{<?php echo generateAjaxToken("ProcessCollectionResourceSelection"); ?>}';
                // Convert token from format {CSRFToken:"data"} to strict JSON format which is {"CSRFToken":"data"} so that it can be parsed 
                var csrf_data = csrf_data.replace('<?php echo $CSRF_token_identifier; ?>','"<?php echo $CSRF_token_identifier; ?>"');
                ProcessCollectionResourceSelection(res_list, primary_action, '<?php echo $USER_SELECTION_COLLECTION; ?>', csrf_data);
                }

            else if (resource_ending) {
                console.log("ERROR - ENDING ONLY");
                }

            console.log("RESOURCE_LIST\n" + JSON.stringify(res_list));

            });
        });
    <?php
    }
    ?>
</script>
<?php
include '../include/footer.php';