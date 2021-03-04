<?php
# Collections functions
# Functions to manipulate collections

/**
 * Return all collections belonging to or shared with $user
 *
 * @param  integer $user
 * @param  string $find A search string
 * @param  string $order_by Column to sort by
 * @param  string $sort ASC or DESC sort order
 * @param  integer $fetchrows   How many rows to fetch
 * @param  boolean $auto_create Create a default My Collection if one doesn't exist
 * @return array
 */
function get_user_collections($user,$find="",$order_by="name",$sort="ASC",$fetchrows=-1,$auto_create=true)
	{
	global $usergroup;

    $sql = "";
    $keysql = "";
    $extrasql = "";

	if ($find=="!shared")
		{
		# only return shared collections
		$sql=" where (c.`type` = " . COLLECTION_TYPE_PUBLIC . " or c.ref in (select distinct collection from user_collection where user<>'" . escape_check($user) . "' union select distinct collection from external_access_keys))";				
		}
	elseif (strlen($find)==1 && !is_numeric($find))
		{
		# A-Z search
		$sql=" where c.name like '" . escape_check($find) . "%'";
		}
	elseif (strlen($find)>1 || is_numeric($find))
		{  
		$keywords=split_keywords($find);
		$keyrefs=array();
		$keysql="";
		for ($n=0;$n<count($keywords);$n++)
			{
			$keyref=resolve_keyword($keywords[$n],false);
			if ($keyref!==false) {$keyrefs[]=$keyref;}

			$keysql.=" join collection_keyword k" . $n . " on k" . $n . ".collection=ref and (k" . $n . ".keyword='$keyref')";	
			//$keysql="or keyword in (" . join (",",$keyrefs) . ")";
			}

 
		//$sql.="and (c.name rlike '$search' or u.username rlike '$search' or u.fullname rlike '$search' $spcr )";
		}

    // Type filter
    global $themes_in_my_collections;
    $sql .= sprintf(
        "%s c.`type` IN (%s, %s%s)",
        ($sql == "" ? "WHERE" : " AND"),
        COLLECTION_TYPE_STANDARD,
        COLLECTION_TYPE_PUBLIC,
        ($themes_in_my_collections ? ", " . COLLECTION_TYPE_FEATURED : ""));

    if($themes_in_my_collections)
        {
        // If we show featured collections, remove the categories
        $keysql .= sprintf(
            " WHERE (clist.`type` IN (%s, %s) OR (clist.`type` = %s AND clist.`count` > 0))",
            COLLECTION_TYPE_STANDARD,
            COLLECTION_TYPE_PUBLIC,
            COLLECTION_TYPE_FEATURED);
        }

	global $anonymous_login,$username,$anonymous_user_session_collection;

    if(isset($anonymous_login) && ($username==$anonymous_login) && $anonymous_user_session_collection)
        {
        // Anonymous user - only get the user's own collections that are for this session - although we can still join to 
        // get collections that have been specifically shared with the anonymous user 
        if('' == $sql)
            {
            $extrasql = " where ";
            }
        else
            {
            $extrasql .= " and ";
            }

        global $rs_session;

        $extrasql .= " (c.session_id='{$rs_session}')";
        }

   
	$order_sort="";
	if ($order_by!="name"){$order_sort=" order by $order_by $sort";}

    $return = sprintf(
        'SELECT * FROM (
                         SELECT c.*, u.username, u.fullname, count(r.resource) AS count
                           FROM user AS u
                           JOIN collection AS c ON u.ref = c.user AND c.user = \'%1$s\'
                LEFT OUTER JOIN collection_resource AS r ON c.ref = r.collection
                          %2$s %3$s
                       GROUP BY c.ref
        
                          UNION
                         SELECT c.*, u.username, u.fullname, count(r.resource) AS count
                           FROM user_collection AS uc
                           JOIN collection AS c ON uc.collection = c.ref AND uc.user = \'%1$s\' AND c.user <> \'%1$s\'
                LEFT OUTER JOIN collection_resource AS r ON c.ref = r.collection
                      LEFT JOIN user AS u ON c.user = u.ref
                          %2$s
                       GROUP BY c.ref
        
                          UNION
                         SELECT c.*, u.username, u.fullname, count(r.resource) AS count
                           FROM usergroup_collection AS gc
                           JOIN collection AS c ON gc.collection = c.ref AND gc.usergroup = \'%4$s\' AND c.user <> \'%1$s\'
                LEFT OUTER JOIN collection_resource AS r ON c.ref = r.collection
                      LEFT JOIN user AS u ON c.user = u.ref
                          %2$s
                        GROUP BY c.ref
        ) AS clist
        %5$s
        GROUP BY ref %6$s',
        escape_check($user), # %1$s
        $sql, # %2$s
        $extrasql, # %3$s
        escape_check($usergroup), # %4$s
        $keysql, # %5$s
        $order_sort # %6$s
    );
    $return = sql_query($return);
	
	if ($order_by=="name"){
		if ($sort=="ASC"){usort($return, 'collections_comparator');}
		else if ($sort=="DESC"){usort($return,'collections_comparator_desc');}
	}
	
	// To keep Default Collection creation consistent: Check that user has at least one collection of his/her own  (not if collection result is empty, which may include shares), 
	$hasown=false;
	for ($n=0;$n<count($return);$n++){
		if ($return[$n]['user']==$user){
			$hasown=true;
		}
	}

	if (!$hasown && $auto_create && $find=="") # User has no collections of their own, and this is not a search. Make a new 'Default Collection'
		{
		# No collections of one's own? The user must have at least one Default Collection
		global $usercollection;
		$usercollection=create_collection ($user,"Default Collection",0,1); // make not deletable
		set_user_collection($user,$usercollection);
		
		# Recurse to send the updated collection list.
		return get_user_collections($user,$find,$order_by,$sort,$fetchrows,false);
		}

	return $return;
	}


$GLOBALS['get_collection_cache'] = array();
/**
 * Returns all data for collection $ref.
 *
 * @param  int  $ref        Collection ID
 * @param bool  $usecache   Optionally retrieve from cache
 * 
 * @return array|boolean
 */
function get_collection($ref, $usecache = false)
	{
    if(isset($GLOBALS['get_collection_cache'][$ref]) && $usecache)
        {
        return $GLOBALS['get_collection_cache'][$ref];
        }
    $return=sql_query("select c.*, c.keywords, u.fullname, u.username, c.home_page_publish, c.home_page_text, c.home_page_image, c.session_id, c.description, c.thumbnail_selection_method, c.bg_img_resource_ref from collection c left outer join user u on u.ref = c.user where c.ref = '" . escape_check($ref) . "'");
    if (count($return)==0)
        {
        return false;
        }
    else 
		{
		$return=$return[0];
		$return["users"]=join(", ",sql_array("select u.username value from user u,user_collection c where u.ref=c.user and c.collection='" . escape_check($ref) . "' order by u.username"));
		global $attach_user_smart_groups,$lang;
		if($attach_user_smart_groups)
			{
			$return["groups"]=join(", ",sql_array("select concat('{$lang["groupsmart"]}: ',u.name) value from usergroup u,usergroup_collection c where u.ref=c.usergroup and c.collection='" . escape_check($ref) . "' order by u.name"));
			}
			
		global $userref,$k,$attach_user_smart_groups;
		$request_feedback=0;
		if ($return["user"]!=$userref)
			{
			# If this is not the user's own collection, fetch the user_collection row so that the 'request_feedback' property can be returned.
			$request_feedback=sql_value("select request_feedback value from user_collection where collection='" . escape_check($ref) . "' and user='$userref'",0);
			if(!$request_feedback && $attach_user_smart_groups && $k=="")
				{
				# try to set via usergroup_collection
				global $usergroup;
				$request_feedback=sql_value("select request_feedback value from usergroup_collection where collection='" . escape_check($ref) . "' and usergroup='$usergroup'",0);
				}
			}
		if ($k!="")
			{
			# If this is an external user (i.e. access key based) then fetch the 'request_feedback' value from the access keys table
			$request_feedback=sql_value("select request_feedback value from external_access_keys where access_key='$k' and request_feedback=1",0);
			}
		
		$return["request_feedback"]=$request_feedback;
		
        // Legacy property which is now superseeded by types. FCs need to be public before they can be put under a category by an admin (perm h)
        global $COLLECTION_PUBLIC_TYPES;
        $return["public"] = (int) in_array($return["type"], $COLLECTION_PUBLIC_TYPES);

        $GLOBALS['get_collection_cache'][$ref] = $return;
        return $return;
        }
	
	return false;
	}

/**
 * Returns all resources in collection
 *
 * @param  int  $collection   ID of collection being requested
 * 
 * @return array|boolean
 */
function get_collection_resources($collection)
    {
    global $userref;

    # For many cases (e.g. when displaying a collection for a user) a search is used instead so permissions etc. are honoured.
    if((string)(int)$collection != (string)$collection)
        {
        return false;
        }

    # Check if review collection if so delete any resources moved out of users archive status permissions by other users
    if((string)$collection == "-".$userref)
        {
        collection_cleanup_inaccessible_resources($collection);
        }
	
    $plugin_collection_resources=hook('replace_get_collection_resources', "", array($collection));
    if(is_array($plugin_collection_resources))
        {
        return $plugin_collection_resources;
        }	

    return sql_array("select resource value from collection_resource where collection='" . escape_check($collection) . "' order by sortorder asc, date_added desc, resource desc"); 
    }

/**
* Get all resources in a collection without checking permissions or filtering by workflow states.
* This is useful when you want to get all the resources for further subprocessing (@see render_selected_collection_actions() 
* as an example)
* 
* @param integer $ref Collection ID
* 
* @return array
*/
function get_collection_resources_with_data($ref)
    {
    if(!is_numeric($ref))
        {
        return array();
        }

    $ref = escape_check($ref);

    $result = sql_query("
            SELECT r.*
              FROM collection_resource AS cr
        RIGHT JOIN resource AS r ON cr.resource = r.ref
             WHERE cr.collection = '{$ref}'
          ORDER BY cr.sortorder ASC , cr.date_added DESC , cr.resource DESC
    ");

    if(!is_array($result))
        {
        return array();
        }

    return $result;
    }


/**
 * Add resource $resource to collection $collection
 *
 * @param  integer $resource
 * @param  integer $collection
 * @param  boolean $smartadd
 * @param  string $size
 * @param  string $addtype
 * @return boolean
 */
function add_resource_to_collection($resource,$collection,$smartadd=false,$size="",$addtype="")
	{
    if((string)(int)$collection != (string)$collection || (string)(int)$resource != (string)$resource)
        {
        return false;
        }

    global $collection_allow_not_approved_share, $collection_block_restypes;

    $addpermitted = (
        (collection_writeable($collection) && !is_featured_collection_category_by_children($collection))
        || $smartadd
    );

	if ($addpermitted && !$smartadd && (count($collection_block_restypes)>0)) // Can't always block adding resource types since this may be a single resource managed request
		{
		if($addtype=="")
			{
			$addtype=sql_value("select resource_type value from resource where ref='" . escape_check($resource) . "'",0);
			}
		if(in_array($addtype,$collection_block_restypes))
			{
			$addpermitted=false;
			}
		}

    if ($addpermitted)	
        {
        # Check if this collection has already been shared externally. If it has, we must fail if not permitted or add a further entry
        # for this specific resource, and warn the user that this has happened.
        $keys = get_external_shares(array("share_collection"=>$collection,"share_type"=>0));
        if (count($keys)>0)
            {
            $archivestatus=sql_value("select archive as value from resource where ref='" . escape_check($resource) . "'","");
            if ($archivestatus<0 && !$collection_allow_not_approved_share) {global $lang; $lang["cantmodifycollection"]=$lang["notapprovedresources"] . $resource;return false;}

            // Check if user can share externally and has open access. We shouldn't add this if they can't share externally, have restricted access or only been granted access
            if (!can_share_resource($resource)){return false;}
			
            # Set the flag so a warning appears.
            global $collection_share_warning;
            # Check to see if all shares have expired
            $expiry_dates=sql_array("select distinct expires value from external_access_keys where collection='" . escape_check($collection) . "'");
            $datetime=time();
            $collection_share_warning=true;
            foreach($expiry_dates as $key => $date)
                {
                if($date!="" && $date<$datetime){$collection_share_warning=false;}
                }
			
			for ($n=0;$n<count($keys);$n++)
				{
				# Insert a new access key entry for this resource/collection.
				global $userref;
				
				sql_query("insert into external_access_keys(resource,access_key,user,collection,date,expires,access,usergroup,password_hash) values ('" . escape_check($resource) . "','" . escape_check($keys[$n]["access_key"]) . "','$userref','" . escape_check($collection) . "',now()," . ($keys[$n]["expires"]==''?'null':"'" . escape_check($keys[$n]["expires"]) . "'") . ",'" . escape_check($keys[$n]["access"]) . "'," . (($keys[$n]["usergroup"]!="")?"'" . escape_check($keys[$n]["usergroup"]) ."'":"NULL") . ",'" . $keys[$n]["password_hash"] . "')");
				
				#log this
				collection_log($collection,LOG_CODE_COLLECTION_SHARED_RESOURCE_WITH,$resource, $keys[$n]["access_key"]);
				}
			
			}
		
		hook("Addtocollectionsuccess", "", array( "resourceId" => $resource, "collectionId" => $collection ) );
		
		if(!hook("addtocollectionsql", "", array( $resource,$collection, $size)))
			{
			sql_query("delete from collection_resource where resource='" . escape_check($resource) . "' and collection='" . escape_check($collection) . "'");
			sql_query("insert into collection_resource(resource,collection,purchase_size) values ('" . escape_check($resource) . "','" . escape_check($collection) . "','$size')");
			}
		
		// log this
		collection_log($collection,LOG_CODE_COLLECTION_ADDED_RESOURCE,$resource);

		// Clear theme image cache
		clear_query_cache("themeimage");
        clear_query_cache('col_total_ref_count_w_perm');

		return true;
		}
	else
		{
		hook("Addtocollectionfail", "", array( "resourceId" => $resource, "collectionId" => $collection ) );
		return false;
		}
	}

/**
 * Remove resource $resource from collection $collection
 *
 * @param  integer $resource
 * @param  integer $collection
 * @param  boolean $smartadd
 * @param  string $size
 * @return boolean
 */
function remove_resource_from_collection($resource,$collection,$smartadd=false,$size="")
    {
    if((string)(int)$collection != (string)$collection || (string)(int)$resource != (string)$resource)
        {
        return false;
        }

    if (collection_writeable($collection)||$smartadd)
		{	
		hook("Removefromcollectionsuccess", "", array( "resourceId" => $resource, "collectionId" => $collection ) );
		
		if(!hook("removefromcollectionsql", "", array( $resource,$collection, $size)))
			{
			sql_query("delete from collection_resource where resource='" . escape_check($resource) . "' and collection='" . escape_check($collection) . "'");
			sql_query("delete from external_access_keys where resource='" . escape_check($resource) . "' and collection='" . escape_check($collection) . "'");
			}
		
		// log this
		collection_log($collection,LOG_CODE_COLLECTION_REMOVED_RESOURCE,$resource);

		// Clear theme image cache
		clear_query_cache("themeimage");
        clear_query_cache('col_total_ref_count_w_perm');

		return true;
		}
	else
		{
		hook("Removefromcollectionfail", "", array( "resourceId" => $resource, "collectionId" => $collection ) );
		return false;
		}
	}
    
    
/**
 * Is the collection $collection writable by the current user?
 * Returns true if the current user has write access to the given collection.
 *
 * @param  integer $collection
 * @return boolean
 */
function collection_writeable($collection)
    {
    $collectiondata = get_collection($collection);
    if($collectiondata===false)
        {
        return false;
        }

    global $userref,$usergroup, $allow_smart_collections;
    if ($allow_smart_collections && !isset($userref))
        { 
        if (isset($collectiondata['savedsearch'])&&$collectiondata['savedsearch']!=null)
            {
            return false; // so "you cannot modify this collection"
            }
        }

    # Load a list of attached users
    $attached=sql_array("select user value from user_collection where collection='" . escape_check($collection) . "'");
    $attached_groups=sql_array("select usergroup value from usergroup_collection where collection='" . escape_check($collection) . "'");

    // Can edit if 
    // - The user owns the collection (if we are anonymous user and are using session collections then this must also have the same session id )
    // - The user has system setup access (needs to be able to sort out user issues)
    // - Collection changes are allowed and :-
    //    a) User is attached to the collection or
    //    b) Collection is public or a theme and the user either has the 'h' permission or the collection is editable

    global $usercollection,$username,$anonymous_login,$anonymous_user_session_collection, $rs_session;
    debug("collection session : " . $collectiondata["session_id"]);
    debug("collection user : " . $collectiondata["user"]);
    debug("anonymous_login : " . isset($anonymous_login) && is_string($anonymous_login) ? $anonymous_login : "(no)");
    debug("userref : " . $userref);
    debug("username : " . $username);
    debug("anonymous_user_session_collection : " . (($anonymous_user_session_collection)?"TRUE":"FALSE"));

    $writable=
        // User either owns collection AND is not the anonymous user, or is the anonymous user with a matching/no session
        ($userref==$collectiondata["user"] && (!isset($anonymous_login) || $username!=$anonymous_login || !$anonymous_user_session_collection || $collectiondata["session_id"]==$rs_session))
        // Collection is public AND either they have the 'h' permission OR allow_changes has been set
        || ((checkperm("h") || $collectiondata["allow_changes"]==1) && $collectiondata["public"]==1)
        // Collection has been shared but is not public AND user is either attached or in attached group
        || ($collectiondata["allow_changes"]==1 && $collectiondata["public"]==0 && (in_array($userref,$attached) || in_array($usergroup,$attached_groups)))
        // System admin
        || checkperm("a")
        // Adding to active upload_share
        || upload_share_active() == $collection;

    // Check if user has permission to manage research requests. If they do and the collection is research request allow writable.
    if ($writable === false && checkperm("r"))
        {
        include_once 'research_functions.php';
        $research_requests = get_research_requests();
        $collections = array();
        foreach ($research_requests as $research_request)
            {
            $collections[] = $research_request["collection"];
            }
        if (in_array($collection,$collections))
            {
            $writable = true;
            }
        }
        
    return $writable;

    }
	
/**
 * Returns true if the current user has read access to the given collection.
 *
 * @param  integer $collection
 * @return boolean
 */
function collection_readable($collection)
	{
    global $userref, $usergroup, $ignore_collection_access, $collection_commenting;

    # Precautionary check to see if user has featured collection access or collection is their own
    if(getval("k","") == "" && !in_array($collection, array_column(get_user_collections($userref,"","name","ASC",-1,false), "ref")) && !featured_collection_check_access_control($collection)) {return false;}

	# Fetch collection details.
	if (!is_numeric($collection)) {return false;}
    $collectiondata=get_collection($collection);
    if($collectiondata === false)
        {
        return false;
        }

	# Load a list of attached users
	$attached=sql_array("select user value from user_collection where collection='$collection'");
	$attached_groups=sql_array("select usergroup value from usergroup_collection where collection='$collection'");

	# Access if collection_commenting is enabled and request feedback checked
	# Access if it's a public collection (or theme)
	# Access if k is not empty or option to ignore collection access is enabled and k is empty
    if (($collection_commenting && $collectiondata['request_feedback'] == 1)
         ||
        $collectiondata["public"]==1 
         ||
        getval("k","")!=""
         ||
        (getval("k","")=="" && $ignore_collection_access))
		{
		return true;
		}

	# Perform these checks only if a user is logged in
	if (is_numeric($userref))
		{
		# Access if:
		#	- It's their collection
		# 	- It's a public collection (or theme)
		#	- They have the 'access and edit all collections' admin permission
		# 	- They are attached to this collection
		#   - Option to ignore collection access is enabled and k is empty
		if($userref==$collectiondata["user"] || $collectiondata["public"]==1 || checkperm("h") || in_array($userref,$attached)  || in_array($usergroup,$attached_groups) || checkperm("R") || getval("k","")!="" || (getval("k","")=="" && $ignore_collection_access))
			{
			return true;
			}
		}

	return false;
	}
	
/**
 * Sets the current collection of $user to be $collection 
 *
 * @param  integer $user
 * @param  integer $collection
 * @return void
 */
function set_user_collection($user,$collection)
	{
	global $usercollection,$username,$anonymous_login,$anonymous_user_session_collection;
	if(!(isset($anonymous_login) && $username==$anonymous_login) || !$anonymous_user_session_collection)
		{		
		sql_query("update user set current_collection='" . escape_check($collection) . "' where ref='" . escape_check($user) . "'");
		}
	$usercollection=$collection;
	}
    
    
/**
 * Creates a new collection for user $userid called $name
 *
 * @param  integer $userid
 * @param  string $name
 * @param  boolean $allowchanges
 * @param  boolean $cant_delete
 * @param  integer $ref
 * @param  boolean $public
 * @return integer
 */
function create_collection($userid,$name,$allowchanges=0,$cant_delete=0,$ref=0,$public=false, $extraparams=array())
	{
    debug_function_call("create_collection", func_get_args());

	global $username,$anonymous_login,$rs_session, $anonymous_user_session_collection;
	if(($username==$anonymous_login && $anonymous_user_session_collection) || upload_share_active())
		{		
		// We need to set a collection session_id for the anonymous user. Get session ID to create collection with this set
		$rs_session=get_rs_session_id(true);
		}
	else
		{	
		$rs_session="";
        }
        
    $setcolumns = array();
    $extracolopts = array("type",
                        "keywords",
                        "saved_search",
                        "session_id",
                        "description",
                        "savedsearch",
                        "parent",
                        "thumbnail_selection_method",
                    );
    foreach($extracolopts as $coloption)
        {
        if(isset($extraparams[$coloption]))
            {
            $setcolumns[$coloption] = escape_check($extraparams[$coloption]);
            }
        }

    $setcolumns["name"]             = escape_check(mb_strcut($name, 0, 100));
    $setcolumns["user"]             = is_numeric($userid) ? $userid : 0;
    $setcolumns["allow_changes"]    = escape_check($allowchanges);
    $setcolumns["cant_delete"]      = escape_check($cant_delete);
    $setcolumns["public"]           = $public ? COLLECTION_TYPE_PUBLIC : COLLECTION_TYPE_STANDARD;
    if($ref != 0)
        {
        $setcolumns["ref"] = (int)$ref;
        }
    if(trim($rs_session) != "")
        {
        $setcolumns["session_id"]   = escape_check($rs_session);
        }
    if($public)
        {
        $setcolumns["type"]         = COLLECTION_TYPE_PUBLIC;
        }

    $insert_columns = array_keys($setcolumns);
    $insert_values  = array_values($setcolumns);

    $sql = "INSERT INTO collection
            (" . implode(",",$insert_columns) . ", created)
            VALUES
            ('" . implode("','",$insert_values). "',NOW())";
    
    sql_query($sql);

    $ref = sql_insert_id();
    index_collection($ref);

    return $ref;
    }
    
    
/**
 * Deletes the collection with reference $ref
 *
 * @param  integer $collection
 * @return boolean|void
 */
function delete_collection($collection)
	{
	global $home_dash, $lang;
    if(!is_array($collection))
        {
        $collection=get_collection($collection);
        }
    if(!$collection)
        {
        return false;
        }
    $ref=$collection["ref"];
    $type = $collection["type"];
	
    if(!collection_writeable($ref) || is_featured_collection_category_by_children($ref))
        {
        return false;
        }

	hook("beforedeletecollection","",array($ref));
	sql_query("delete from collection where ref='$ref'");
	sql_query("delete from collection_resource where collection='$ref'");
	sql_query("delete from collection_keyword where collection='$ref'");
	
	if($home_dash)
		{
		// Delete any dash tiles pointing to this collection
		$collection_dash_tiles=sql_array("select ref value from dash_tile WHERE link like '%search.php?search=!collection" . $ref . "&%'",0);
		if(count($collection_dash_tiles)>0)
			{
			sql_query("delete from dash_tile WHERE ref in (" .  implode(",",$collection_dash_tiles) . ")");
			sql_query("delete from user_dash_tile WHERE dash_tile in (" .  implode(",",$collection_dash_tiles) . ")");
			}
		}

    collection_log($ref,LOG_CODE_COLLECTION_DELETED_COLLECTION,0, $collection["name"] . " (" . $lang["owner"] . ":" . $collection["username"] . ")");

    if($type == COLLECTION_TYPE_FEATURED)
        {
        clear_query_cache("featured_collections");
        }
	}
	
/**
 * Adds script to page that refreshes the Collection bar
 *
 * @param  integer $collection  Collection id
 * @return void
 */
function refresh_collection_frame($collection="")
    {
    # Refresh the CollectionDiv
    global $baseurl, $headerinsert;

    if (getvalescaped("ajax",false))
        {
        echo "<script  type=\"text/javascript\">
        CollectionDivLoad(\"" . $baseurl . "/pages/collections.php" . ((getval("k","")!="")?"?collection=" . urlencode(getval("collection",$collection)) . "&k=" . urlencode(getval("k","")) . "&":"?") . "nc=" . time() . "\");	
        </script>";
        }
    else
        {
        $headerinsert.="<script  type=\"text/javascript\">
        CollectionDivLoad(\"" . $baseurl . "/pages/collections.php" . ((getval("k","")!="")?"?collection=" . urlencode(getval("collection",$collection)) . "&k=" . urlencode(getval("k","")) . "&":"?") . "nc=" . time() . "\");
        </script>";
        }
    }

/**
 * Performs a search for featured collections / public collections.
 *
 * @param  string $search
 * @param  string $order_by
 * @param  string $sort
 * @param  boolean $exclude_themes
 * @param  boolean $exclude_public
 * @param  boolean $include_resources
 * @param  boolean $override_group_restrict
 * @param  boolean $search_user_collections
 * @param  integer $fetchrows
 * @return array
 */
function search_public_collections($search="", $order_by="name", $sort="ASC", $exclude_themes=true, $exclude_public=false, $include_resources=false, $override_group_restrict=false, $search_user_collections=false, $fetchrows=-1)
    {
    global $userref;

    $keysql = "";
    $sql = "";

    // Validate sort & order_by
    $sort = (in_array($sort, array("ASC", "DESC")) ? $sort : "ASC");
    $valid_order_bys = array("fullname", "name", "ref", "count", "type", "created");
    $order_by = (in_array($order_by, $valid_order_bys) ? $order_by : "name");

	# Keywords searching?
	$keywords=split_keywords($search);  
	if (strlen($search)==1 && !is_numeric($search)) 
		{
		# A-Z search
		$sql="and c.name like '" . escape_check($search) . "%'";
		}
	elseif (substr($search,0,16)=="collectiontitle:")
	    {
	    # A-Z specific title search
	    
	    $newsearch="";
	    for ($n=0;$n<count($keywords);$n++)
	    	{
	    	   if (substr($keywords[$n],0,16)=="collectiontitle:") $newsearch.=" ".substr($keywords[$n],16);    // wildcard * - %
	    	}

        $newsearch = strpos($newsearch,'*')===false ? '%' . trim($newsearch) . '%' : str_replace('*', '%', trim($newsearch));
        $sql="and c.name like '$newsearch'";
	    	
	    }
	if (strlen($search)>1 || is_numeric($search))
		{  
		
		$keyrefs=array();
		for ($n=0;$n<count($keywords);$n++)
			{
			if (substr($keywords[$n],0,16)!="collectiontitle:")
    		    {
    		    if (substr($keywords[$n],0,16)=="collectionowner:") 
    		        {
    			    $keywords[$n]=substr($keywords[$n],16);
	    		    $keyref=$keywords[$n];
                       $sql.=" and (u.username rlike '$keyref' or u.fullname rlike '$keyref')";	
                    }
                elseif (substr($keywords[$n],0,19)=="collectionownerref:") 
                    {
                    $keywords[$n]=substr($keywords[$n],19);
                    $keyref=$keywords[$n];
                       $sql.=" and (c.user='$keyref')";
                    } 
                else
                    {
                    if (substr($keywords[$n],0,19)=="collectionkeywords:") $keywords[$n]=substr($keywords[$n],19);
		    # Support field specific matching - discard the field identifier as not appropriate for collection searches.
		    if (strpos($keywords[$n],":")!==false) {$keywords[$n]=substr($keywords[$n],strpos($keywords[$n],":")+1);}
                    $keyref=resolve_keyword($keywords[$n],false);
                    if ($keyref!==false) {$keyrefs[]=$keyref;}
                    $keysql .= " JOIN collection_keyword AS k" . $n . " ON k" . $n . ".collection = c.ref AND (k" . $n . ".keyword = '$keyref')";
                    }
			    }
			}
        
        global $search_public_collections_ref;
        if ($search_public_collections_ref && is_numeric($search))
            {
            $spcr="or c.ref='" . escape_check($search) . "'";
            }
        else
            {
            $spcr="";
            }    
        }
	
	# Restrict to parent, child and sibling groups?
	global $public_collections_confine_group,$userref,$usergroup;
	if ($public_collections_confine_group && !$override_group_restrict)
		{
		# Form a list of all applicable groups
		$groups=array($usergroup); # Start with user's own group
		$groups=array_merge($groups,sql_array("select ref value from usergroup where parent='" . escape_check($usergroup) . "'")); # Children
		$groups=array_merge($groups,sql_array("select parent value from usergroup where ref='" . escape_check($usergroup) . "'")); # Parent
		$groups=array_merge($groups,sql_array("select ref value from usergroup where parent<>0 and parent=(select parent from usergroup where ref='" . escape_check($usergroup) . "')")); # Siblings (same parent)
		
		$sql.=" and u.usergroup in ('" . join ("','",$groups) . "')";
		}

    // Add extra elements to the SELECT statement if needed
    $select_extra = "";
    if($include_resources)
        {
        $select_extra .= ", count(DISTINCT cr.resource) AS count";
        }

    // Filter by type (public/featured collections)
    $public_type_filter_sql = "c.`type` = " . COLLECTION_TYPE_PUBLIC;
    if($search_user_collections)
        {
        $public_type_filter_sql = sprintf('(c.`type` = %s OR c.user = \'%s\')', COLLECTION_TYPE_PUBLIC, escape_check($userref));
        }
    $featured_type_filter_sql = sprintf(
        "(c.`type` = %s %s)",
        COLLECTION_TYPE_FEATURED,
        trim(featured_collections_permissions_filter_sql("AND", "c.ref"))
    );
    if($exclude_themes)
        {
        $featured_type_filter_sql = "";
        }
    else if($exclude_public && !$search_user_collections)
        {
        $public_type_filter_sql = "";
        }
    $type_filter_sql = sprintf(
        ($public_type_filter_sql != "" && $featured_type_filter_sql != "" ? "(%s%s)" : "%s%s"),
        $public_type_filter_sql,
        ($public_type_filter_sql != "" && $featured_type_filter_sql != "" ? " OR {$featured_type_filter_sql}" : $featured_type_filter_sql)
    );

    $where_clause_osql = 'col.`type` = ' . COLLECTION_TYPE_PUBLIC;
    if($search_user_collections)
        {
        $where_clause_osql = sprintf('col.`type` IN (%s, %s)', COLLECTION_TYPE_STANDARD, COLLECTION_TYPE_PUBLIC);
        }
    if($featured_type_filter_sql !== '')
        {
        $where_clause_osql .= ' OR (col.`type` = ' . COLLECTION_TYPE_FEATURED . ' AND col.is_featured_collection_category = false)';
        }

    $main_sql = sprintf(
        "SELECT *
           FROM (
                         SELECT DISTINCT c.*,
                                u.username,
                                u.fullname,
                                if(c.`type` = %s AND COUNT(DISTINCT cc.ref)>0, true, false) AS is_featured_collection_category
                                %s
                           FROM collection AS c
                LEFT OUTER JOIN collection AS cc ON c.ref = cc.parent
                LEFT OUTER JOIN collection_resource AS cr ON c.ref = cr.collection
                LEFT OUTER JOIN user AS u ON c.user = u.ref
                LEFT OUTER JOIN collection_keyword AS k ON c.ref = k.collection
                          %s # keysql
                          WHERE %s # type_filter_sql
                            %s
                       GROUP BY c.ref
                       ORDER BY %s
           ) AS col
          WHERE %s",
        COLLECTION_TYPE_FEATURED,
        $select_extra,
        $keysql,
        $type_filter_sql,
        $sql, # extra filters
        "{$order_by} {$sort}",
        $where_clause_osql
    );

    return sql_query($main_sql, '', $fetchrows);
    }



/**
 * Search within available collections
 *
 * @param  string $search
 * @param  string $restypes
 * @param  integer $archive
 * @param  string $order_by
 * @param  string $sort
 * @param  integer $fetchrows
 * @return array
 */
function do_collections_search($search,$restypes,$archive=0,$order_by='',$sort="DESC", $fetchrows = -1)
    {
    global $search_includes_themes, $search_includes_public_collections, $search_includes_user_collections, $userref, $collection_search_includes_resource_metadata, $default_collection_sort;
    
    if($order_by=='')
        {
        $order_by=$default_collection_sort;
        }
    $result=array();
    
    # Recognise a quoted search, which is a search for an exact string
    $quoted_string=false;
    if (substr($search,0,1)=="\"" && substr($search,-1,1)=="\"") 
        {
        $quoted_string=true;
        $search=substr($search,1,-1);
        } 
    $search_includes_themes_now=$search_includes_themes;
    $search_includes_public_collections_now=$search_includes_public_collections;
    $search_includes_user_collections_now=$search_includes_user_collections;
    if ($restypes!="") 
        {
        $restypes_x=explode(",",$restypes);
        $search_includes_themes_now=in_array("themes",$restypes_x);
        $search_includes_public_collections_now=in_array("pubcol",$restypes_x);
        $search_includes_user_collections_now=in_array("mycol",$restypes_x);
        } 

    if ($search_includes_themes_now || $search_includes_public_collections_now || $search_includes_user_collections_now)
        {
        if ($collection_search_includes_resource_metadata)
            {
            # Include metadata from resources when searching - using a special search
                $collections=do_search("!contentscollection"
                    . ($search_includes_user_collections_now?'U':'')
                    . ($search_includes_public_collections_now?'P':'')
                    . ($search_includes_themes_now?'T':'')
                    . " " . $search,"",$order_by,0,-1,$sort);
            }
        else
            {
            # The old way - same search as when searching within public collections.
            $collections=search_public_collections($search,"theme","ASC",!$search_includes_themes_now,!$search_includes_public_collections_now,true,false, $search_includes_user_collections_now, $fetchrows);
            }
        
        $condensedcollectionsresults=array();
        $result=$collections;
        }
       
    
    
    return $result;
    }


/**
 * Add a collection to a user's 'My Collections'
 *
 * @param  integer  $user         ID of user
 * @param  integer  $collection   ID of collection
 * 
 * @return boolean
 */
function add_collection($user,$collection)
	{
	// Don't add if we are anonymous - we can only have one collection
	global $anonymous_login,$username,$anonymous_user_session_collection;
 	if (isset($anonymous_login) && ($username==$anonymous_login) && $anonymous_user_session_collection)
		{return false;}

	remove_collection($user,$collection);
	sql_query("insert into user_collection(user,collection) values ('" . escape_check($user) . "','" . escape_check($collection) . "')");
    clear_query_cache('col_total_ref_count_w_perm');
	collection_log($collection,LOG_CODE_COLLECTION_SHARED_COLLECTION,0, sql_value ("select username as value from user where ref = '" . escape_check($user) . "'",""));

    return true;
	}


/**
 * Remove someone else's collection from a user's My Collections
 *
 * @param  integer $user
 * @param  integer $collection
 */
function remove_collection($user,$collection)
	{
	sql_query("delete from user_collection where user='" . escape_check($user) . "' and collection='" . escape_check($collection) . "'");
    clear_query_cache('col_total_ref_count_w_perm');
	collection_log($collection,LOG_CODE_COLLECTION_STOPPED_SHARING_COLLECTION,0, sql_value ("select username as value from user where ref = '" . escape_check($user) . "'",""));
	}

/**
 * Update the keywords index for this collection
 *
 * @param  integer $ref
 * @param  string $index_string
 * @return integer  How many keywords were indexed?
 */
function index_collection($ref,$index_string='')
	{
	# 
	sql_query("delete from collection_keyword where collection='" . escape_check($ref) . "'"); # Remove existing keywords
	# Define an indexable string from the name, themes and keywords.

	global $index_collection_titles;

	if ($index_collection_titles)
		{
			$indexfields = 'c.ref,c.name,c.keywords,c.description';
		} else {
			$indexfields = 'c.ref,c.keywords';
		}
	global $index_collection_creator;
	if ($index_collection_creator)
		{
			$indexfields .= ',u.fullname';
		} 
		
	
	// if an index string wasn't supplied, generate one
	if (!strlen($index_string) > 0){
		$indexarray = sql_query("select $indexfields from collection c left join user u on u.ref=c.user where c.ref = '" . escape_check($ref) . "'");
		for ($i=0; $i<count($indexarray); $i++){
			$index_string = "," . implode(',',$indexarray[$i]);
		} 
	}

	$keywords=split_keywords($index_string,true);
	for ($n=0;$n<count($keywords);$n++)
		{
		if(trim($keywords[$n])==""){continue;}
		$keyref=resolve_keyword($keywords[$n],true);
		sql_query("insert into collection_keyword values ('" . escape_check($ref) . "','$keyref')");
		}
	// return the number of keywords indexed
	return $n;
	}


/**
 * Process the save action when saving a collection
 *
 * @param  integer $ref
 * @param  array $coldata
 * 
 * @return false|void
 */
function save_collection($ref, $coldata=array())
	{
	global $attach_user_smart_groups;
	
	if (!is_numeric($ref) || !collection_writeable($ref))
        {
        return false;
        }
	
    if(count($coldata) == 0)
        {
        // Old way
        $coldata["name"]                = getval("name","");
        $coldata["allow_changes"]       = getval("allow_changes","") != "" ? 1 : 0;
        $coldata["public"]              = getval('public',0,true);
        $coldata["keywords"]            = getval("keywords","");
        $coldata["result_limit"]        = getval("result_limit",0,true);
        $coldata["relateall"]           = getval("relateall","") != "";
        $coldata["removeall"]           = getval("removeall","") != "";
        $coldata["deleteall"]           = getval("deleteall","") != "";
        $coldata["users"]               = getval("users","");

        if (checkperm("h"))
            {
            $coldata["home_page_publish"]   = (getval("home_page_publish","") != "") ? "1" : "0";
            $coldata["home_page_text"]      = getval("home_page_text","");
            $home_page_image = getval("home_page_image",0,true);
            if ($home_page_image > 0)
                {
                $coldata["home_page_image"] = $home_page_image;
                }
            }
        }
        
    $oldcoldata = get_collection($ref);    

	if (!hook('modifysavecollection'))
        {
        $sqlset = array();
        foreach($coldata as $colopt => $colset)
            {
            // Public collection
            if($colopt == "public" && $colset == 1)
                {
                $sqlset["type"] = COLLECTION_TYPE_PUBLIC;
                }

            // "featured_collections_changes" is determined by collection_edit.php page
            // This is meant to override the type if collection has a parent. The order of $coldata elements matters!
            if($colopt == "featured_collections_changes" && !empty($colset))
                {
                $sqlset["type"] = COLLECTION_TYPE_FEATURED;
                $sqlset["parent"] = null;

                if(isset($colset["update_parent"]))
                    {
                    $force_featured_collection_type = isset($colset["force_featured_collection_type"]);

                    // A FC root category is created directly from the collections_featured.php page so not having a parent, means it's just public
                    if($colset["update_parent"] == 0 && !$force_featured_collection_type)
                        {
                        $sqlset["type"] = COLLECTION_TYPE_PUBLIC;
                        }
                    else
                        {
                        $sqlset["parent"] = (int) $colset["update_parent"];
                        }
                    }

                if(isset($colset["thumbnail_selection_method"]))
                    {
                    $sqlset["thumbnail_selection_method"] = $colset["thumbnail_selection_method"];
                    }
                
                if(isset($colset["thumbnail_selection_method"]) || isset($colset["name"]))
                    {
                    // Prevent the parent from being changed if user only modified the thumbnail_selection_method or name
                    $sqlset["parent"] = (!isset($colset["update_parent"]) ? $oldcoldata["parent"] : $sqlset["parent"]);
                    }

                // Prevent unnecessary changes
                foreach(array("type", "parent", "thumbnail_selection_method") as $puc_to_prop)
                    {
                    if(isset($sqlset[$puc_to_prop]) && $oldcoldata[$puc_to_prop] == $sqlset[$puc_to_prop])
                        {
                        unset($sqlset[$puc_to_prop]);
                        }
                    }

                continue;
                }
            if(!isset($oldcoldata[$colopt]) || $colset != $oldcoldata[$colopt] && $colopt != "users")
                {
                $sqlset[$colopt] = $colset;
                }
            }

        // If collection is set as private by caller code, disable incompatible properties used for COLLECTION_TYPE_FEATURED (set by the user or exsting)
        if(isset($sqlset["public"]) && $sqlset["public"] == 0)
            {
            $sqlset["type"] = COLLECTION_TYPE_STANDARD;
            $sqlset["parent"] = null;
            $sqlset["thumbnail_selection_method"] = null;
            $sqlset["bg_img_resource_ref"] = null;
            }

        if(count($sqlset) > 0)
            {
            $sqlupdate = "";
            $clear_fc_query_cache = false;
            foreach($sqlset as $colopt => $colset)
                {
                if($sqlupdate != "")
                    {
                    $sqlupdate .= ", ";    
                    }

                if(in_array($colopt, array("type", "parent", "thumbnail_selection_method", "bg_img_resource_ref")))
                    {
                    $clear_fc_query_cache = true;
                    }

                if(in_array($colopt, array("parent", "thumbnail_selection_method", "bg_img_resource_ref")))
                    {
                    $sqlupdate .= $colopt . " = " . sql_null_or_val((string) $colset, $colset == 0);
                    continue;
                    }

                $sqlupdate .= $colopt . " = '" . escape_check($colset) . "' ";
                }

            $sql = "UPDATE collection SET {$sqlupdate} WHERE ref = '{$ref}'";
            sql_query($sql);

            if($clear_fc_query_cache)
                {
                clear_query_cache("featured_collections");
                }

            // Log the changes
            foreach($sqlset as $colopt => $colset)
                {
                switch($colopt)
                    {
                    case "public";
                        collection_log($ref, LOG_CODE_COLLECTION_ACCESS_CHANGED, 0, $colset ? 'public' : 'private');
                    break;    
                    case "allow_changes";
                        collection_log($ref, LOG_CODE_UNSPECIFIED, 0,  $colset ? 'true' : 'false' );
                    break; 
                    default;
                        collection_log($ref, LOG_CODE_EDITED, 0,  $colopt  . " = " . $colset);
                    break;
                    }
                 
                }
            }
        } # end replace hook - modifysavecollection

	index_collection($ref);

    # If 'users' is specified (i.e. access is private) then rebuild users list
	if (isset($coldata["users"]))
        {
        $old_attached_users=sql_array("SELECT user value FROM user_collection WHERE collection='$ref'");
        $new_attached_users=array();
        $collection_owner=sql_value("SELECT u.fullname value FROM collection c LEFT JOIN user u on c.user=u.ref WHERE c.ref='$ref'","");
        if($collection_owner=='')
            {
            $collection_owner=sql_value("SELECT u.username value FROM collection c LEFT JOIN user u on c.user=u.ref WHERE c.ref='$ref'","");
            }
        
        sql_query("delete from user_collection where collection='$ref'");
        
        if ($attach_user_smart_groups)
            {
            $old_attached_groups=sql_array("SELECT usergroup value FROM usergroup_collection WHERE collection='$ref'");
            sql_query("delete from usergroup_collection where collection='$ref'");
            }
    
        # Build a new list and insert
        $users=resolve_userlist_groups($coldata["users"]);
        $ulist=array_unique(trim_array(explode(",",$users)));
        $ulist = array_map("escape_check",$ulist);
        $urefs=sql_array("select ref value from user where username in ('" . join("','",$ulist) . "')");
        if (count($urefs)>0)
            {
            sql_query("insert into user_collection(collection,user) values ($ref," . join("),(" . $ref . ",",$urefs) . ")");
            $new_attached_users=array_diff($urefs, $old_attached_users);
            }
        #log this
        collection_log($ref,LOG_CODE_COLLECTION_SHARED_COLLECTION,0, join(", ",$ulist));
		
        if($attach_user_smart_groups)
            {
            $groups=resolve_userlist_groups_smart($users);
            $groupnames='';
            if($groups!='')
                {
                $groups=explode(",",$groups);
                if (count($groups)>0)
                    { 
                    foreach ($groups as $group)
                        {
                        sql_query("insert into usergroup_collection(collection,usergroup) values ('$ref','$group')");
                        // get the group name
                        if($groupnames!='')
                            {
                            $groupnames.=", ";
                            }
                        $groupnames.=sql_value("select name value from usergroup where ref='{$group}'","");
                        }

                    $new_attached_groups=array_diff($groups, $old_attached_groups);
                    if(!empty($new_attached_groups))
                        {
                        foreach($new_attached_groups as $newg)
                            {
                            $group_users=sql_array("SELECT ref value FROM user WHERE usergroup=$newg");
                            $new_attached_users=array_merge($new_attached_users, $group_users);
                            }
                        }
                    }
                #log this
                collection_log($ref,LOG_CODE_COLLECTION_SHARED_COLLECTION,0, $groupnames);
                }
            }
        # Send a message to any new attached user
        if(!empty($new_attached_users))
            {
            global $baseurl, $lang;

            $new_attached_users=array_unique($new_attached_users);
            message_add($new_attached_users,str_replace(array('%user%', '%colname%'), array($collection_owner, getvalescaped("name","")), $lang['collectionprivate_attachedusermessage']),$baseurl . "/?c=" . $ref);
            }
        }

    # Relate all resources?
    if (isset($coldata["relateall"]) && $coldata["relateall"] != "")
        {
        relate_all_collection($ref);
        }

    # Remove all resources?
    if (isset($coldata["removeall"]) && $coldata["removeall"]!="")
        {
        remove_all_resources_from_collection($ref);
        }
		
	# Delete all resources?
	if (isset($coldata["deleteall"]) && $coldata["deleteall"]!="" && !checkperm("D"))
        {
        if(allow_multi_edit($ref))
            {
            delete_resources_in_collection($ref);
            }
        }

    # Update limit count for saved search
	if (isset($coldata["result_limit"]) && (int)$coldata["result_limit"] > 0)
        {
        sql_query("update collection_savedsearch set result_limit='" . $result_limit . "' where collection='$ref'");
        }

    refresh_collection_frame();
    }


/**
* Case insensitive string comparisons using a "natural order" algorithm for collection names
* 
* @param string $a
* @param string $b
* 
* @return integer < 0 if $a is less than $b > 0 if $a is greater than $b, and 0 if they are equal.
*/
function collections_comparator($a, $b)
	{
	return strnatcasecmp(i18n_get_collection_name($a), i18n_get_collection_name($b));
	}

/**
* Case insensitive string comparisons using a "natural order" algorithm for collection names
* 
* @param string $b
* @param string $a
* 
* @return integer < 0 if $a is less than $b > 0 if $a is greater than $b, and 0 if they are equal.
*/
function collections_comparator_desc($a, $b)
	{
	return strnatcasecmp(i18n_get_collection_name($b), i18n_get_collection_name($a));
	}


/**
 * Returns a list of smart theme headers, which are basically fields with a 'smart theme name' set.
 *
 * @return array
 */
function get_smart_theme_headers()
	{
	return sql_query("SELECT ref, name, smart_theme_name, type FROM resource_type_field WHERE length(smart_theme_name) > 0 ORDER BY smart_theme_name", "featured_collections");
	}

/**
 * get_smart_themes_nodes
 *
 * @param  integer $field
 * @param  boolean $is_category_tree
 * @param  integer $parent
 * @param  array   $field_meta - resource type field metadata
 * @return array
 */
function get_smart_themes_nodes($field, $is_category_tree, $parent = null, array $field_meta = array())
    {
    global $smart_themes_omit_archived;

    $return = array();

    // Determine if this should cascade onto children for category tree type
    $recursive = false;
    if($is_category_tree)
        {
        $recursive = true;
        }

    $nodes = get_nodes($field, ((0 == $parent) ? null : $parent), $recursive);

    if(isset($field_meta['automatic_nodes_ordering']) && (bool) $field_meta['automatic_nodes_ordering'])
                {
                $nodes = reorder_nodes($nodes);
                $nodes = array_values($nodes); // reindex nodes array
                }

    if(0 === count($nodes))
        {
        return $return;
        }
  
    /*
    Tidy list so it matches the storage format used for keywords
    The translated version is fetched as each option will be indexed in the local language version of each option
    */
    $options_base = array();
    for($n = 0; $n < count($nodes); $n++)
        {
        $options_base[$n] = escape_check(trim(mb_convert_case(i18n_get_translated($nodes[$n]['name']), MB_CASE_LOWER, 'UTF-8')));
        }
    
    // For each option, if it is in use, add it to the return list
    for($n = 0; $n < count($nodes); $n++)
        {
        //$cleaned_option_base = str_replace('-', ' ', $options_base[$n]);
        $cleaned_option_base = preg_replace('/\W/',' ',$options_base[$n]);      // replace any non-word characters with a space
        $cleaned_option_base = trim($cleaned_option_base);      // trim (just in case prepended / appended space characters)

        $tree_node_depth    = 0;
        $parent_node_to_use = 0;
        $is_parent          = false;

        if(is_parent_node($nodes[$n]['ref']))
            {
            $parent_node_to_use = $nodes[$n]['ref'];
            $is_parent          = true;

            $tree_node_depth = get_tree_node_level($nodes[$n]['ref']);

            if(!is_null($parent) && is_parent_node($parent))
                {
                $tree_node_depth--;
                }
            }

        $c                       = count($return);
        $return[$c]['name']      = trim(i18n_get_translated($nodes[$n]['name']));
        $return[$c]['indent']    = $tree_node_depth;
        $return[$c]['node']      = $parent_node_to_use;
        $return[$c]['is_parent'] = $is_parent;
        $return[$c]['ref'] = $nodes[$n]['ref'];
        }

    return $return;
    }

/**
 * E-mail a collection to users
 * 
 *  - Attempt to resolve all users in the string $userlist to user references.
 *  - Add $collection to these user's 'My Collections' page
 *  - Send them an e-mail linking to this collection
 *  - Handle multiple collections (comma seperated list)
 *
 * @param  mixed $colrefs
 * @param  string $collectionname
 * @param  string $fromusername
 * @param  string $userlist
 * @param  string $message
 * @param  string $feedback
 * @param  integer $access
 * @param  string $expires
 * @param  string $useremail
 * @param  string $from_name
 * @param  string $cc
 * @param  boolean $themeshare
 * @param  string $themename
 * @param  string $themeurlsuffix
 * @param  boolean $list_recipients
 * @param  boolean $add_internal_access
 * @param  string $group
 * @param  string $sharepwd
 * @return void
 */
function email_collection($colrefs,$collectionname,$fromusername,$userlist,$message,$feedback,$access=-1,$expires="",$useremail="",$from_name="",$cc="",$themeshare=false,$themename="",$themeurlsuffix="",$list_recipients=false, $add_internal_access=false,$group="",$sharepwd="")
	{
	global $baseurl,$email_from,$applicationname,$lang,$userref, $email_multi_collections,$usergroup,$attach_user_smart_groups;
	if ($useremail==""){$useremail=$email_from;}
	if ($group==""){$group=$usergroup;}
	
	if (trim($userlist)=="") {return ($lang["mustspecifyoneusername"]);}
	$userlist=resolve_userlist_groups($userlist);
	
	if($attach_user_smart_groups && strpos($userlist,$lang["groupsmart"] . ": ")!==false){
		$groups_users=resolve_userlist_groups_smart($userlist,true);
		if($groups_users!=''){
			if($userlist!=""){
				$userlist=remove_groups_smart_from_userlist($userlist);
				if($userlist!="")
					{
					$userlist.=",";
					}
			}
			$userlist.=$groups_users;
		}
	}

	$ulist=trim_array(explode(",",$userlist));
	$emails=array();
	$key_required=array();
	if ($feedback) {$feedback=1;} else {$feedback=0;}

    $reflist = trim_array(explode(",", $colrefs));
    // Take out the FC category from the list as this is more of a dummy record rather than a collection we'll be giving
    // access to users. See generate_collection_access_key() when collection is a featured collection category.
    $fc_category_ref = ($themeshare ? array_shift($reflist) : null);

	$emails_keys=resolve_user_emails($ulist);
    if(0 === count($emails_keys))
        {
        return $lang['email_error_user_list_not_valid'];
        }

    $emails=$emails_keys['emails'];
    $key_required=$emails_keys['key_required'];

    # Add the collection(s) to the user's My Collections page
    $ulist = array_map("escape_check",$ulist);
    $urefs=sql_array("select ref value from user where username in ('" . join("','",$ulist) . "')");
    if (count($urefs)>0)
        {
        # Delete any existing collection entries
        sql_query("delete from user_collection where collection in ('" .join("','", $reflist) . "') and user in ('" . join("','",$urefs) . "')");
        
        # Insert new user_collection row(s)
        #loop through the collections
        for ($nx1=0;$nx1<count($reflist);$nx1++)
            {
            #loop through the users
            for ($nx2=0;$nx2<count($urefs);$nx2++)
                {
                sql_query("insert into user_collection(collection,user,request_feedback) values ($reflist[$nx1], $urefs[$nx2], $feedback )");
                if ($add_internal_access)
                    {		
                    foreach (get_collection_resources($reflist[$nx1]) as $resource)
                        {
                        if (get_edit_access($resource))
                            {
                            open_access_to_user($urefs[$nx2],$resource,$expires);
                            }
                        }
                    }
                
                #log this
                collection_log($reflist[$nx1],LOG_CODE_COLLECTION_SHARED_COLLECTION,0, sql_value ("select username as value from user where ref = $urefs[$nx2]",""));
                }
            }
        }

    # Send an e-mail to each resolved user

    # htmlbreak is for composing list
    $htmlbreak="\r\n";
    global $use_phpmailer;
    if ($use_phpmailer){$htmlbreak="<br /><br />";$htmlbreaksingle="<br />";} 

    if ($fromusername==""){$fromusername=$applicationname;} // fromusername is used for describing the sender's name inside the email
    if ($from_name==""){$from_name=$applicationname;} // from_name is for the email headers, and needs to match the email address (app name or user name)

    $templatevars['message']=str_replace(array("\\n","\\r","\\"),array("\n","\r",""),$message);	
    if (trim($templatevars['message'])==""){$templatevars['message']=$lang['nomessage'];} 

    $templatevars['fromusername']=$fromusername;
    $templatevars['from_name']=$from_name;

    if(count($reflist)>1){$subject=$applicationname.": ".$lang['mycollections'];}
    else { $subject=$applicationname.": ".$collectionname;}

    if ($fromusername==""){$fromusername=$applicationname;}
	
    $externalmessage=$lang["emailcollectionmessageexternal"];
    $internalmessage=$lang["emailcollectionmessage"];
    $viewlinktext=$lang["clicklinkviewcollection"];
    if ($themeshare) // Change the text if sharing a theme category
        {
        $externalmessage=$lang["emailthemecollectionmessageexternal"];
        $internalmessage=$lang["emailthememessage"];
        $viewlinktext=$lang["clicklinkviewcollections"];
        }
        
    ##  loop through recipients
    for ($nx1=0;$nx1<count($emails);$nx1++)
        {
        ## loop through collections
        $list="";
        $list2="";
        $origviewlinktext=$viewlinktext; // Save this text as we may change it for internal theme shares for this user
        if ($themeshare && !$key_required[$nx1]) # don't send a whole list of collections if internal, just send the theme category URL
            {
            $url="";
            $subject=$applicationname.": " . $themename;
            $url=$baseurl . "/pages/collections_featured.php" . $themeurlsuffix;			
            $viewlinktext=$lang["clicklinkviewthemes"];
            $emailcollectionmessageexternal=false;
            if ($use_phpmailer){
                    $link="<a href=\"$url\">" . $themename . "</a>";	
                    
                    $list.= $htmlbreak.$link;	
                    // alternate list style				
                    $list2.=$htmlbreak.$themename.' -'.$htmlbreaksingle.$url;
                    $templatevars['list2']=$list2;					
                    }
                else
                    {
                    $list.= $htmlbreak.$url;
                    }
            for ($nx2=0;$nx2<count($reflist);$nx2++)
                {				
                #log this
                collection_log($reflist[$nx2],LOG_CODE_COLLECTION_EMAILED_COLLECTION,0, $emails[$nx1]);
                }
            
            }
        else
            {
            // E-mail external share, generate the access key based on the FC category. Each sub-collection will have the same key.
            if($key_required[$nx1] && $themeshare && !is_null($fc_category_ref))
                {
                $k = generate_collection_access_key($fc_category_ref, $feedback, $emails[$nx1], $access, $expires, $group, $sharepwd, $reflist);
                $fc_key = "&k={$k}";
                }

            for ($nx2=0;$nx2<count($reflist);$nx2++)
                {
                $url="";
                $key="";
                $emailcollectionmessageexternal=false;

                # Do we need to add an external access key for this user (e-mail specified rather than username)?
                if ($key_required[$nx1] && !$themeshare)
                    {
                    $k=generate_collection_access_key($reflist[$nx2],$feedback,$emails[$nx1],$access,$expires,$group,$sharepwd);
                    $key="&k=". $k;
                    $emailcollectionmessageexternal=true;
                    }
                // If FC category, the key is valid across all sub-featured collections. See generate_collection_access_key()
                else if($key_required[$nx1] && $themeshare && !is_null($fc_category_ref))
                    {
                    $key = $fc_key;
                    $emailcollectionmessageexternal = true;
                    }

                $url=$baseurl . 	"/?c=" . $reflist[$nx2] . $key;		
                $collection = array();
                $collection = sql_query("select name,savedsearch from collection where ref='$reflist[$nx2]'");
                if ($collection[0]["name"]!="") {$collection_name = i18n_get_collection_name($collection[0]);}
                else {$collection_name = $reflist[$nx2];}
                if ($use_phpmailer){
                    $link="<a href=\"$url\">$collection_name</a>";	
                    $list.= $htmlbreak.$link;	
                    // alternate list style				
                    $list2.=$htmlbreak.$collection_name.' -'.$htmlbreaksingle.$url;
                    $templatevars['list2']=$list2;					
                    }
                else
                    {
                    $list.= $htmlbreak . $collection_name . $htmlbreak . $url . $htmlbreak;
                    }
                #log this
                collection_log($reflist[$nx2],LOG_CODE_COLLECTION_EMAILED_COLLECTION,0, $emails[$nx1]);
                }
            }
        //$list.=$htmlbreak;	
        $templatevars['list']=$list;
        $templatevars['from_name']=$from_name;
        if(isset($k)){
            if($expires==""){
                $templatevars['expires_date']=$lang["email_link_expires_never"];
                $templatevars['expires_days']=$lang["email_link_expires_never"];
            }
            else{
                $day_count=round((strtotime($expires)-strtotime('now'))/(60*60*24));
                $templatevars['expires_date']=$lang['email_link_expires_date'].nicedate($expires);
                $templatevars['expires_days']=$lang['email_link_expires_days'].$day_count;
                if($day_count>1){
                    $templatevars['expires_days'].=" ".$lang['expire_days'].".";
                }
                else{
                    $templatevars['expires_days'].=" ".$lang['expire_day'].".";
                }
            }
        }
        else{
            # Set empty expiration templatevars
            $templatevars['expires_date']='';
            $templatevars['expires_days']='';
        }
        if ($emailcollectionmessageexternal ){
            $template=($themeshare)?"emailthemeexternal":"emailcollectionexternal";
        }
        else {
            $template=($themeshare)?"emailtheme":"emailcollection";
        }

        if (is_array($emails) && (count($emails) > 1) && $list_recipients===true) {
            $body = $lang["list-recipients"] ."\n". implode("\n",$emails) ."\n\n";
            $templatevars['list-recipients']=$lang["list-recipients"] ."\n". implode("\n",$emails) ."\n\n";
        }
        else {
            $body = "";
        }
        $body.=$templatevars['fromusername']." " . (($emailcollectionmessageexternal)?$externalmessage:$internalmessage) . "\n\n" . $templatevars['message']."\n\n" . $viewlinktext ."\n\n".$templatevars['list'];
        send_mail($emails[$nx1],$subject,$body,$fromusername,$useremail,$template,$templatevars,$from_name,$cc);
        $viewlinktext=$origviewlinktext;
        }
    hook("additional_email_collection","",array($colrefs,$collectionname,$fromusername,$userlist,$message,$feedback,$access,$expires,$useremail,$from_name,$cc,$themeshare,$themename,$themeurlsuffix,$template,$templatevars));
    # Return an empty string (all OK).
    return "";
    }



/**
 * Generate an external access key to allow external people to view the resources in this collection.
 *
 * @param  integer  $collection  Collection ref -or- collection data structure
 * @param  integer  $feedback
 * @param  string   $email
 * @param  integer  $access
 * @param  string   $expires
 * @param  string   $group
 * @param  string   $sharepwd
 * @param  array    $sub_fcs     List of sub-featured collections IDs (collection_email.php page has logic to determine 
 *                               this which is carried forward to email_collection())
 * 
 * @return string   The generated key used for external sharing
 */
function generate_collection_access_key($collection,$feedback=0,$email="",$access=-1,$expires="",$group="", $sharepwd="", array $sub_fcs = array())
    {
    global $userref, $usergroup, $scramble_key;

    // Default to sharing with the permission of the current usergroup if not specified OR no access to alternative group selection.
    if($group == "" || !checkperm("x"))
        {
        $group = $usergroup;
        }

    if(!is_array($collection))
        {
        $collection = get_collection($collection);
        }

    if(!empty($collection) && $collection["type"] == COLLECTION_TYPE_FEATURED && !isset($collection["has_resources"]))
        {
        $collection_resources = get_collection_resources($collection["ref"]);
        $collection["has_resources"] = (is_array($collection_resources) && !empty($collection_resources) ? 1 : 0);
        }
    $is_featured_collection_category = is_featured_collection_category($collection);

    // We build a collection list to allow featured collections children that are externally shared as part of a parent,
    // to all be shared with the same parameters (e.g key, access, group). When the collection is not COLLECTION_TYPE_FEATURED
    // this will hold just that collection
    $collections = array($collection["ref"]);
    if($is_featured_collection_category)
        {
        $collections = (!empty($sub_fcs) ? $sub_fcs : get_featured_collection_categ_sub_fcs($collection));
        }

    // Generate the key based on the original collection. For featured collection category, all sub featured collections
    // will share the same key
    $k = generate_share_key($collection["ref"]);

    $main_collection = $collection; // keep record of this info as we need it at the end to record the successful generation of a key for a featured collection category
    $created_sub_fc_access_key = false;
    foreach($collections as $collection)
        {
        $r = get_collection_resources($collection);
        $shareable_resources = array_filter($r, function($resource_ref) { return can_share_resource($resource_ref); });
        foreach($shareable_resources as $resource_ref)
            {
            $sql = sprintf("INSERT INTO external_access_keys(resource, access_key, collection, `user`, usergroup, request_feedback, email, `date`, access, expires, password_hash) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW(), '%s', %s, %s)",
                $resource_ref,
                $k,
                escape_check($collection),
                $userref,
                escape_check($group),
                escape_check($feedback),
                escape_check($email),
                escape_check($access),
                sql_null_or_val($expires, $expires == ""),
                sql_null_or_val(hash("sha256", $k . $sharepwd . $scramble_key), !($sharepwd != "" && $sharepwd != "(unchanged)"))
            );
            sql_query($sql);
            $created_sub_fc_access_key = true;
            }

        hook("generate_collection_access_key", "", array($collection, $k, $userref, $feedback, $email, $access, $expires, $group, $sharepwd));
        }

    if($is_featured_collection_category && $created_sub_fc_access_key)
        {
        // add for FC category. No resource. This is a dummy record so we can have a way to edit the external share done 
        // at the featured collection category level
        $sql = sprintf("INSERT INTO external_access_keys(resource, access_key, collection, `user`, usergroup, request_feedback, email, `date`, access, expires, password_hash) VALUES (NULL, '%s', '%s', '%s', '%s', '%s', '%s', NOW(), '%s', %s, %s)",
            $k,
            escape_check($main_collection["ref"]),
            $userref,
            escape_check($group),
            escape_check($feedback),
            escape_check($email),
            escape_check($access),
            sql_null_or_val($expires, $expires == ""),
            sql_null_or_val(hash("sha256", $k . $sharepwd . $scramble_key), !($sharepwd != "" && $sharepwd != "(unchanged)"))
        );
        sql_query($sql);
        }

    return $k;
    }
	
/**
 * Returns all saved searches in a collection
 *
 * @param  integer $collection
 * @return void
 */
function get_saved_searches($collection)
	{
	return sql_query("select * from collection_savedsearch where collection='" . escape_check($collection) . "' order by created");
	}

/**
 * Add a saved search to a collection
 *
 * @param  integer $collection
 * @return void
 */
function add_saved_search($collection)
	{
	sql_query("insert into collection_savedsearch(collection,search,restypes,archive) values ('" 
		. escape_check($collection) . "','" . getvalescaped("addsearch","") . "','" . getvalescaped("restypes","") . "','" . getvalescaped("archive","") . "')");
	}

/**
 * Remove a saved search from a collection
 *
 * @param  integer $collection
 * @param  integer $search
 * @return void
 */
function remove_saved_search($collection,$search)
	{
	sql_query("delete from collection_savedsearch where collection='" . escape_check($collection) . "' and ref='" . escape_check($search) . "'");
	}

/**
 * Greate a new smart collection using submitted values
 *
 * @return void
 */
function add_smart_collection()
 	{
	global $userref;

	$search=getvalescaped("addsmartcollection","");
	$restypes=getvalescaped("restypes","");
	if($restypes=="Global"){$restypes="";}
	$archive = getvalescaped('archive', 0, true);
	$starsearch=getvalescaped("starsearch",0);
	
	// more compact search strings should work with get_search_title
	$searchstring=array();
	if ($search!=""){$searchstring[]="search=$search";}
	if ($restypes!=""){$searchstring[]="restypes=$restypes";}
	if ($starsearch!=""){$searchstring[]="starsearch=$starsearch";}
	if ($archive!=0){$searchstring[]="archive=$archive";}
	$searchstring=implode("&",$searchstring);
	
	if ($starsearch==""){$starsearch=0;}
	$newcollection=create_collection($userref,get_search_title($searchstring),1);	

	sql_query("insert into collection_savedsearch(collection,search,restypes,archive,starsearch) values ('$newcollection','" . $search . "','" . $restypes . "','" . $archive . "','".$starsearch."')");
	$savedsearch=sql_insert_id();
	sql_query("update collection set savedsearch='$savedsearch' where ref='$newcollection'"); 
    set_user_collection($userref,$newcollection);
    refresh_collection_frame($newcollection);
	}

/**
 * Get a display friendly name for the given search string
 * Takes a full searchstring of the form 'search=restypes=archive=starsearch=' and
 * uses search_title_processing to autocreate a more informative title 
 *
 * @param  string $searchstring     Search string
 * 
 * @return string Friendly name for search
 */
function get_search_title($searchstring)
    {    
    $order_by="";
    $sort="";
    $offset="";
    $k=getvalescaped("k","");

    $search_titles=true;
    $search_titles_searchcrumbs=true;
    $use_refine_searchstring=true;
    $search_titles_shortnames=false;

    global $lang,$userref,$baseurl,$collectiondata,$result,$display,$pagename,$collection,$userrequestmode,$preview_all;

    parse_str($searchstring,$searchvars);
    if (isset($searchvars["archive"])){$archive=$searchvars["archive"];}else{$archive=0;}
    if (isset($searchvars["search"])){$search=$searchvars["search"];}else{$search="";}
    if (isset($searchvars["starsearch"])){$starsearch=$searchvars["starsearch"];}else{$starsearch="";}
    if (isset($searchvars["restypes"])){$restypes=$searchvars["restypes"];}else{$restypes="";}

    $collection_dropdown_user_access_mode=false;
    include(dirname(__FILE__)."/search_title_processing.php");

    if ($starsearch!=0){$search_title.="(".$starsearch;$search_title.=($starsearch>1)?" ".$lang['stars']:" ".$lang['star'];$search_title.=")";}
    if ($restypes!="")
        { 
        $resource_types=get_resource_types($restypes);
        foreach($resource_types as $type)
            {
            $typenames[]=$type['name'];
            }
        $search_title.=" [".implode(', ',$typenames)."]";
        }
    $title=str_replace(">","",strip_tags($search_title));
    return $title;
    }

/**
 * Adds all the resources in the provided search to $collection
 *
 * @param  integer $collection
 * @param  string $search
 * @param  string $restypes
 * @param  string $archivesearch
 * @param  string $order_by
 * @param  string $sort
 * @param  string $daylimit
 * @param  string $starsearch
 * @param  int    $res_access          The ID of the resource access level
 * @return boolean
 */
function add_saved_search_items($collection, $search = "", $restypes = "", $archivesearch = "", $order_by = "relevance", $sort = "desc", $daylimit = "", $starsearch = "",$res_access = "")
	{
    if((string)(int)$collection != $collection)
        {
        // Not an integer
        return false;
        }
    
	global $collection_share_warning, $collection_allow_not_approved_share, $userref, $collection_block_restypes, $search_all_workflow_states;
    
	# Adds resources from a search to the collection.
    if($search_all_workflow_states && 0 != $archivesearch)
        {
        $search_all_workflow_states = false;
        }
   
    $results=do_search($search, $restypes, $order_by, $archivesearch,-1,$sort,false,$starsearch,false,false,$daylimit,false,true,false,false,false,$res_access);

	if(!is_array($results) || count($results) == 0)
        {
        return false;
        }
        
	# Check if this collection has already been shared externally. If it has, we must add a further entry
	# for this specific resource, and warn the user that this has happened.
	$keys = get_collection_external_access($collection);
	$resourcesnotadded = array(); # record the resources that are not added so we can display to the user
	$blockedtypes = array();# Record the resource types that are not added 
	
    // To maintain current collection order but add the search items in the correct order we must first ove the existing collection resoruces out the way
    $searchcount = count($results);
    if($searchcount > 0)
        {
        sql_query("UPDATE collection_resource SET sortorder = if(isnull(sortorder),'" . $searchcount . "',sortorder + '" . $searchcount . "') WHERE collection='" . $collection . "'");
        }

	for ($r=0;$r<$searchcount;$r++)
		{
		$resource=$results[$r]["ref"];
		$archivestatus=$results[$r]["archive"];
		
		if(in_array($results[$r]["resource_type"],$collection_block_restypes))
			{
			$blockedtypes[] = $results[$r]["resource_type"];
			continue;
			}

		if (count($keys)>0)
			{			
			if ($archivestatus<0 && !$collection_allow_not_approved_share)
				{
				$resourcesnotadded[$resource] = $results[$r];
				continue;
				}
			for ($n=0;$n<count($keys);$n++)
				{
				# Insert a new access key entry for this resource/collection.
				sql_query("insert into external_access_keys(resource,access_key,user,collection,date,expires,access,usergroup,password_hash) values ('" . escape_check($resource) . "','" . escape_check($keys[$n]["access_key"]) . "','$userref','" . escape_check($collection) . "',now()," . ($keys[$n]["expires"]==''?'null':"'" . escape_check($keys[$n]["expires"]) . "'") . ",'" . escape_check($keys[$n]["access"]) . "'," . (($keys[$n]["usergroup"]!="")?"'" . escape_check($keys[$n]["usergroup"]) ."'":"NULL") . ",'" . $keys[$n]["password_hash"] . "')");
                #log this
				collection_log($collection,LOG_CODE_COLLECTION_SHARED_RESOURCE_WITH,$resource, $keys[$n]["access_key"]);	
				
				# Set the flag so a warning appears.
				$collection_share_warning=true;	
				}
			}
		}	
		
		
	if (is_array($results))
		{		
		$modifyNotAdded = hook('modifynotaddedsearchitems', '', array($results, $resourcesnotadded));
		if (is_array($modifyNotAdded))
			$resourcesnotadded = $modifyNotAdded;

		for ($n=0;$n<$searchcount;$n++)
			{
			$resource=$results[$n]["ref"];
			if (!isset($resourcesnotadded[$resource]) && !in_array($results[$n]["resource_type"],$collection_block_restypes))
				{
				sql_query("delete from collection_resource where resource='$resource' and collection='$collection'");
				sql_query("insert into collection_resource(resource,collection,sortorder) values ('$resource','$collection','$n')");
				
				#log this
				collection_log($collection,LOG_CODE_COLLECTION_ADDED_RESOURCE,$resource);
				}
			}
		}

	if (!empty($resourcesnotadded) || count($blockedtypes)>0)
		{
		# Translate to titles only for displaying them to the user
		global $view_title_field;
		$titles = array();
		foreach ($resourcesnotadded as $resource)
			{
			$titles[] = i18n_get_translated($resource['field' . $view_title_field]);
			}
		if(count($blockedtypes)>0)
			{
			$blocked_restypes=array_unique($blockedtypes);
			// Return a list of blocked resouce types
			$titles["blockedtypes"]=$blocked_restypes;
			}
		return $titles;
		}
	if(count($blockedtypes)>0)
		{
		}
	return array();
	}

/**
 * Returns true or false, can all resources in this collection be edited by the user?
 *
 * @param  array|int  $collection     Collection IDs
 * @param  array      $collectionid        
 * 
 * @return boolean
 */
function allow_multi_edit($collection,$collectionid = 0)
	{
	global $resource;

	if (is_array($collection) && $collectionid == 0)
		{
		// Do this the hard way by checking every resource for edit access
		for ($n=0;$n<count($collection);$n++)
			{
			$resource = $collection[$n];
			if (!get_edit_access($collection[$n]["ref"],$collection[$n]["archive"],false,$collection[$n]))
				{
				return false;
				}
			}	
		}
	else
		{            
		// Instead of checking each resource we can do a comparison between a search for all resources in collection and a search for editable resources
        $resultcount = 0;
		if(!is_array($collection))
			{
			// Need the collection resources so need to run the search
			$collectionid = $collection;
			$collection = do_search("!collection{$collectionid}", '', '', 0, -1, '', false, 0, false, false, '', false, true, true,false);
			}
        if(is_array($collection))
            {
            $resultcount = count($collection);
            }
        $editcount = 0;
        $editresults = 	do_search("!collection{$collectionid}", '', '', 0, -1, '', false, 0, false, false, '', false, true, true,true);
        if(is_array($editresults))
            {
            $editcount = count($editresults);
            }
		if($resultcount != $editcount){return false;}
		}
	
			
	if(hook('denyaftermultiedit', '', array($collection))) { return false; }

	return true;
	}


/**
* Get featured collection resources (including from child nodes). For normal FCs this is using the collection_resource table.
* For FC categories, this will check within normal FCs contained by that category. Normally used in combination with 
* generate_featured_collection_image_urls() but useful to determine if a FC category is full of empty FCs.
* 
* @param array $c   Collection data structure similar to the one returned by {@see get_featured_collections()}
* @param array $ctx Extra context used to get FC resources (e.g smart FC?, limit on number of resources returned). Context 
*                   information should take precedence over internal logic (e.g determining the result limit)
* 
* @return array
*/
function get_featured_collection_resources(array $c, array $ctx)
    {
    if(!isset($c["ref"]) || !is_int((int) $c["ref"]))
        {
        return array();
        }

    global $CACHE_FC_RESOURCES, $themes_simple_images;
    $CACHE_FC_RESOURCES = (!is_null($CACHE_FC_RESOURCES) && is_array($CACHE_FC_RESOURCES) ? $CACHE_FC_RESOURCES : array());
    // create a unique ID for this result set as the context for the same FC may differ
    $cache_id = $c["ref"] . md5(json_encode($ctx));
    if(isset($CACHE_FC_RESOURCES[$cache_id]))
        {
        return $CACHE_FC_RESOURCES[$cache_id];
        }

    $c_ref_escaped = escape_check($c["ref"]);
    $limit = (isset($ctx["limit"]) && (int) $ctx["limit"] > 0 ? (int) $ctx["limit"] : null);
    $use_thumbnail_selection_method = (isset($ctx["use_thumbnail_selection_method"]) ? (bool) $ctx["use_thumbnail_selection_method"] : false);
    $all_fcs = (isset($ctx["all_fcs"]) && is_array($ctx["all_fcs"]) ? $ctx["all_fcs"] : array());

    // Smart FCs
    if(isset($ctx["smart"]) && $ctx["smart"] === true)
        {
        // Root smart FCs don't have an image (legacy reasons)
        if(is_null($c["parent"]))
            {
            return array();
            }

        $node_search = NODE_TOKEN_PREFIX . $c['ref'];

        $limit = (!is_null($limit) ? $limit : 1);

        // Access control is still in place (ie permissions are honoured)
        $smart_fc_resources = do_search($node_search, '', 'hit_count', 0, $limit, 'desc', false, 0, false, false, '', true, false, true);
        $smart_fc_resources = (is_array($smart_fc_resources) ? array_column($smart_fc_resources, "ref") : array());

        $CACHE_FC_RESOURCES[$cache_id] = $smart_fc_resources;

        return $smart_fc_resources;
        }

    // Access control
    $rca_joins = array();
    $rca_where = '';
    $fc_permissions_where = '';
    if(!checkperm("v"))
        {
        global $usergroup, $userref;
        $rca_joins = array(
            sprintf('LEFT JOIN resource_custom_access AS rca_u ON r.ref = rca_u.resource AND rca_u.user = \'%s\' AND (rca_u.user_expires IS NULL OR rca_u.user_expires > now())', escape_check($userref)),
            sprintf('LEFT JOIN resource_custom_access AS rca_ug ON r.ref = rca_ug.resource AND rca_ug.usergroup = \'%s\'', escape_check($usergroup)),
        );
        $rca_where = sprintf(
            'AND (r.access < %1$s OR (r.access IN (%1$s, %2$s) AND ((rca_ug.access IS NOT NULL AND rca_ug.access < %1$s) OR (rca_u.access IS NOT NULL AND rca_u.access < %1$s))))',
            RESOURCE_ACCESS_CONFIDENTIAL,
            RESOURCE_ACCESS_CUSTOM_GROUP);
        $fc_permissions_where = featured_collections_permissions_filter_sql("AND", "c.ref");
        }

    if($use_thumbnail_selection_method && isset($c["thumbnail_selection_method"]) && isset($c["bg_img_resource_ref"]))
        {
        global $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS, $theme_images_number;

        if($c["thumbnail_selection_method"] == $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["no_image"])
            {
            return array();
            }
        else if($c["thumbnail_selection_method"] == $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["manual"])
            {
            $limit = 1;
            $union = sprintf("
                UNION SELECT ref, 1 AS use_as_theme_thumbnail, r.hit_count FROM resource AS r %s WHERE r.ref = '%s' %s",
                implode(" ", $rca_joins),
                escape_check($c["bg_img_resource_ref"]),
                $rca_where);
            }
        // For most_popular_image & most_popular_images we change the limit only if it hasn't been provided by the context.
        else if($c["thumbnail_selection_method"] == $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"] && is_null($limit))
            {
            $limit = 1;
            }
        else if($c["thumbnail_selection_method"] == $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_images"] && is_null($limit))
            {
            $limit = $theme_images_number;
            }
        }

    // A SQL statement. Each array index represents a different SQL clause.
    $subquery = array(
        "select" => "SELECT r.ref, cr.use_as_theme_thumbnail, r.hit_count",
        "from" => "FROM collection AS c",
        "join" => array_merge(
            array(
                "JOIN collection_resource AS cr ON cr.collection = c.ref",
                "JOIN resource AS r ON r.ref = cr.resource AND r.archive = 0 AND r.ref > 0",
            ),
            $rca_joins
        ),
        "where" => "WHERE c.ref = '{$c_ref_escaped}' AND c.`type` = " . COLLECTION_TYPE_FEATURED,
    );

    if(is_featured_collection_category($c))
        {
        $all_fcs = sql_query("SELECT ref, parent FROM collection WHERE `type`='" . COLLECTION_TYPE_FEATURED . "'", "featured_collections");
        $all_fcs_rp = array_column($all_fcs, 'parent','ref');

        // Array to hold resources
        $fcresources=array();

        // Create stack of collections to search 
        // (not a queue as we want to get to the lowest child collections first where the resources are)
        $colstack = new SplStack(); // 
        $children = array_keys($all_fcs_rp,$c["ref"]);
        foreach($children as $child_fc)
            {
            $colstack->push($child_fc);
            }

        while(count($fcresources) < $themes_simple_images && !$colstack->isEmpty())
            {
            $checkfc = $colstack->pop();
            if(!in_array($checkfc,$all_fcs_rp))
                {
                $subfcimages = get_collection_resources($checkfc);        
                if(is_array($subfcimages) && count($subfcimages) > 0)
                    {
                    $fcresources = array_merge($fcresources,$subfcimages);                
                    }
                continue;
                }       
     
            // Either a parent FC or no results, add sub fcs to stack
            $children = array_keys($all_fcs_rp,$checkfc);
            foreach($children as $child_fc)
                {
                $colstack->push($child_fc);
                }
            }
        $subquery["where"] = "WHERE r.ref IN ('" . implode("','",$fcresources) . "')";
        }

    $subquery["join"] = implode(" ", $subquery["join"]);
    $subquery["where"] .= " {$rca_where} {$fc_permissions_where}";

    $sql = sprintf("SELECT DISTINCT ti.ref AS `value`, ti.use_as_theme_thumbnail, ti.hit_count FROM (%s %s) AS ti ORDER BY ti.use_as_theme_thumbnail DESC, ti.hit_count DESC, ti.ref DESC %s",
        implode(" ", $subquery),
        (isset($union) ? $union : ''),
        sql_limit(null, $limit)
    );

    $fc_resources = sql_array($sql, "themeimage");

    $CACHE_FC_RESOURCES[$cache_id] = $fc_resources;

    return $fc_resources;
    }


/**
* Get a list of featured collections based on a higher level featured collection category. This returns all direct/indirect
* collections under that category.
* 
* @param array $c   Collection data structure
* @param array $ctx Contextual data (e.g disable access control). This param MUST NOT get exposed over the API
* 
* @return array 
*/
function get_featured_collection_categ_sub_fcs(array $c, array $ctx = array())
    {
    global $CACHE_FC_CATEG_SUB_FCS;
    $CACHE_FC_CATEG_SUB_FCS = (!is_null($CACHE_FC_CATEG_SUB_FCS) && is_array($CACHE_FC_CATEG_SUB_FCS) ? $CACHE_FC_CATEG_SUB_FCS : array());
    if(isset($CACHE_FC_CATEG_SUB_FCS[$c["ref"]]))
        {
        return $CACHE_FC_CATEG_SUB_FCS[$c["ref"]];
        }

    $access_control = (isset($ctx["access_control"]) && is_bool($ctx["access_control"]) ? $ctx["access_control"] : true);
    $all_fcs = (isset($ctx["all_fcs"]) && is_array($ctx["all_fcs"]) && !empty($ctx["all_fcs"]) ? $ctx["all_fcs"] : get_all_featured_collections());

    $collections = array();

    $allowed_fcs = ($access_control ? compute_featured_collections_access_control() : true);
    if($allowed_fcs === false)
        {
        $CACHE_FC_CATEG_SUB_FCS[$c["ref"]] = $collections;
        return $collections;
        }
    else if(is_array($allowed_fcs))
        {
        $allowed_fcs_flipped = array_flip($allowed_fcs);
        
        // Collection is not allowed
        if(!isset($allowed_fcs_flipped[$c['ref']]))
            {
            $CACHE_FC_CATEG_SUB_FCS[$c["ref"]] = $collections;
            return $collections;
            }
        }

    $all_fcs_rp = reshape_array_by_value_keys($all_fcs, 'ref', 'parent');
    $all_fcs = array_flip_by_value_key($all_fcs, 'ref');

    $queue = new SplQueue();
    $queue->setIteratorMode(SplQueue::IT_MODE_DELETE);
    $queue->enqueue($c['ref']);

    while(!$queue->isEmpty())
        {
        $fc = $queue->dequeue();

        $fc_parent = ($all_fcs[$fc]['parent'] > 0 ? $all_fcs[$fc]['parent'] : 0);
        $fc_children = array();

        if(
            $all_fcs[$fc]['has_resources'] > 0
            && (
                $allowed_fcs === true
                || (is_array($allowed_fcs) && isset($allowed_fcs_flipped[$fc]))
            )
        )
            {
            $collections[] = $fc;
            }
        else if($all_fcs[$fc]['has_children'] > 0)
            {
            $fc_children = array_keys($all_fcs_rp, $fc);
            }

        foreach($fc_children as $fc_child_ref)
            {
            $queue->enqueue($fc_child_ref);
            }
        }

    $CACHE_FC_CATEG_SUB_FCS[$c["ref"]] = $collections;

    debug("get_featured_collection_categ_sub_fcs(ref = {$c["ref"]}): returned collections: " . implode(", ", $collections));
    return $collections;
    }


/**
* Get preview URLs for a list of resource IDs
* 
* @param array  $resource_refs  List of resources
* @param string $size           Preview size
* 
* @return array List of images URLs
*/
function generate_featured_collection_image_urls(array $resource_refs, string $size)
    {
    $images = array();

    $refs_list = array_filter($resource_refs, 'is_numeric');
    if(empty($refs_list))
        {
        return $images;
        }
    $refs_list = "'" . implode("','", $refs_list) . "'";

    $refs_rtype = sql_query("SELECT ref, resource_type FROM resource WHERE ref IN ({$refs_list})", 'featured_collections');

    foreach($refs_rtype as $ref_rt)
        {
        $ref = $ref_rt['ref'];
        $resource_type = $ref_rt['resource_type'];

        if(file_exists(get_resource_path($ref, true, $size, false)) && resource_download_allowed($ref, $size, $resource_type))
            {
            $images[] = get_resource_path($ref, false, $size, false);
            }
        }

    return $images;
    }


/**
 * Inserts $resource1 into the position currently occupied by $resource2 
 *
 * @param  integer $resource1
 * @param  integer $resource2
 * @param  integer $collection
 * @return void
 */
function swap_collection_order($resource1,$resource2,$collection)
	{

	// sanity check -- we should only be getting IDs here
	if (!is_numeric($resource1) || !is_numeric($resource2) || !is_numeric($collection)){
		exit ("Error: invalid input to swap collection function.");
	}
	//exit ("Swapping " . $resource1 . " for " . $resource2);
	
	$query = "select resource,date_added,sortorder  from collection_resource where collection='$collection' and resource in ('$resource1','$resource2')  order by sortorder asc, date_added desc";
	$existingorder = sql_query($query);

	$counter = 1;
	foreach ($existingorder as $record){
		$rec[$counter]['resource']= $record['resource'];		
		$rec[$counter]['date_added']= $record['date_added'];
		if (strlen($record['sortorder']) == 0){
			$rec[$counter]['sortorder'] = "NULL";
		} else {		
			$rec[$counter]['sortorder']= "'" . $record['sortorder'] . "'";
		}
			
		$counter++;	
	}

	
	$sql1 = "update collection_resource set date_added = '" . $rec[1]['date_added'] . "', 
		sortorder = " . $rec[1]['sortorder'] . " where collection = '$collection' 
		and resource = '" . $rec[2]['resource'] . "'";

	$sql2 = "update collection_resource set date_added = '" . $rec[2]['date_added'] . "', 
		sortorder = " . $rec[2]['sortorder'] . " where collection = '$collection' 
		and resource = '" . $rec[1]['resource'] . "'";

	sql_query($sql1);
	sql_query($sql2);

	}

/**
 * Reorder the items in a collection using $neworder as the order by metric
 *
 * @param  array $neworder  Array of columns to order by
 * @param  integer $collection
 * @param  integer $offset
 * @return void
 */
function update_collection_order($neworder,$collection,$offset=0)
	{	
	if (!is_array($neworder)) {
		exit ("Error: invalid input to update collection function.");
	}

    if (count($neworder)>0) {
        $updatesql= "update collection_resource set sortorder=(case resource ";
        $counter = 1 + $offset;
        foreach ($neworder as $colresource){
            $updatesql.= "when '" . escape_check($colresource) . "' then '$counter' ";
            $counter++;
        }
        $updatesql.= "else sortorder END) WHERE collection='" . escape_check($collection) . "'";
        sql_query($updatesql);
    }
	$updatesql="update collection_resource set sortorder=99999 WHERE collection='" . escape_check($collection) . "' and sortorder is NULL";
	sql_query($updatesql);
	}
    
    
/**
 * Return comments and other columns stored in the collection_resource join.
 *
 * @param  integer $resource
 * @param  integer $collection
 * @return array
 */
function get_collection_resource_comment($resource,$collection)
	{
	$data=sql_query("select *,use_as_theme_thumbnail from collection_resource where collection='" . escape_check($collection) . "' and resource='" . escape_check($resource) . "'","");
    if (!isset($data[0]))
		{
		return false;
		}
    return $data[0];
	}
	
/**
 * Save a comment and/or rating for the instance of a resource in a collection.
 *
 * @param  integer $resource
 * @param  integer $collection
 * @param  string $comment
 * @param  integer $rating
 * @return boolean
 */
function save_collection_resource_comment($resource,$collection,$comment,$rating)
	{
	# get data before update so that changes can be logged.	
	$data=sql_query("select comment,rating from collection_resource where resource='" . escape_check($resource) . "' and collection='" . escape_check($collection) . "'");
	sql_query("update collection_resource set comment='" . escape_check($comment) . "',rating=" . (($rating!="")?"'" . escape_check($rating) . "'":"null") . ",use_as_theme_thumbnail='" . (getval("use_as_theme_thumbnail","")==""?0:1) . "' where resource='" . escape_check($resource) . "' and collection='" . escape_check($collection) . "'");
	
	# log changes
	if ($comment!=$data[0]['comment']){collection_log($collection,LOG_CODE_COLLECTION_ADDED_RESOURCE_COMMENT,$resource);}
	if ($rating!=$data[0]['rating']){collection_log($collection,LOG_CODE_COLLECTION_ADDED_RESOURCE_RATING,$resource);}
	return true;
	}

    
/**
 * Relates every resource in $collection to $ref
 *
 * @param  integer $ref
 * @param  integer $collection
 * @return void
 */
function relate_to_collection($ref,$collection)	
	{
    $colresources = get_collection_resources($collection);
    sql_query("delete from resource_related where resource='" . escape_check($ref) . "' and related in ('" . join("','",$colresources) . "')");  
    sql_query("insert into resource_related(resource,related) values (" . escape_check($ref) . "," . join("),(" . $ref . ",",$colresources) . ")");
	}

/**
 * Fetch all the comments for a given collection.
 *
 * @param  integer $collection
 * @return array
 */
function get_collection_comments($collection)
	{
	return sql_query("select * from collection_resource where collection='" . escape_check($collection) . "' and length(comment)>0 order by date_added");
	}

/**
 * Sends the feedback to the owner of the collection
 *
 * @param  integer $collection  Collection ID
 * @param  string  $comment     Comment text
 * @return void
 */
function send_collection_feedback($collection,$comment)
    {
    global $applicationname,$lang,$userfullname,$userref,$k,$feedback_resource_select,$feedback_email_required,$regex_email;

    $cinfo=get_collection($collection);    
    if($cinfo===false)
        {
        error_alert($lang["error-collectionnotfound"]);
        exit();
        }
    $user=get_user($cinfo["user"]);
    $body=$lang["collectionfeedbackemail"] . "\n\n";

    if (isset($userfullname))
        {
        $body.=$lang["user"] . ": " . $userfullname . "\n";
        }
    else
        {
        # External user.
        if ($feedback_email_required && !preg_match ("/${regex_email}/", getvalescaped("email",""))) {$errors[]=$lang["youremailaddress"] . ": " . $lang["requiredfield"];return $errors;}
        $body.=$lang["fullname"] . ": " . getval("name","") . "\n";
        $body.=$lang["email"] . ": " . getval("email","") . "\n";
        }
    $body.=$lang["message"] . ": " . stripslashes(str_replace("\\r\\n","\n",trim($comment)));

    $f=get_collection_comments($collection);
    for ($n=0;$n<count($f);$n++)
        {
        $body.="\n\n" . $lang["resourceid"] . ": " . $f[$n]["resource"];
        $body.="\n" . $lang["comment"] . ": " . trim($f[$n]["comment"]);
        if (is_numeric($f[$n]["rating"]))
            {
            $body.="\n" . $lang["rating"] . ": " . substr("**********",0,$f[$n]["rating"]);
            }
        }

    if ($feedback_resource_select)
        {
        $body.="\n\n" . $lang["selectedresources"] . ": ";
        $file_list="";
        $result=do_search("!collection" . $collection);
        for ($n=0;$n<count($result);$n++)
            {
            $ref=$result[$n]["ref"];
            if (getval("select_" . $ref,"")!="")
                {
                global $filename_field;
                $filename=get_data_by_field($ref,$filename_field);
                $body.="\n" . $ref . " : " . $filename;

                # Append to a file list that is compatible with Adobe Lightroom
                if ($file_list!="") {$file_list.=", ";}
                $s=explode(".",$filename);
                $file_list.=$s[0];
                }
            }
        # Append Lightroom compatible summary.
        $body.="\n\n" . $lang["selectedresourceslightroom"] . "\n" . $file_list;
        }
    $cc=getval("email","");
    get_config_option($user['ref'],'email_user_notifications', $send_email);
    // Always send a mail for the feedback whatever the user preference, since the  feedback may be very long so can then refer to the CC'd email
    if (filter_var($cc, FILTER_VALIDATE_EMAIL))
        {
        send_mail($user["email"],$applicationname . ": " . $lang["collectionfeedback"] . " - " . $cinfo["name"],$body,"","","",NULL,"",$cc);
        }
    else
        {
        send_mail($user["email"],$applicationname . ": " . $lang["collectionfeedback"] . " - " . $cinfo["name"],$body);
        }

    if(!$send_email)
        {
        // Add a system notification message as well if the user has not 'opted out'
        global $userref;
        message_add($user["ref"],$lang["collectionfeedback"] . " - " . $cinfo["name"] . "<br />" . $body,"",(isset($userref))?$userref:$user['ref'],MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,60 * 60 *24 * 30);
        }
    }

/**
 * Copy a collection contents
 *
 * @param  integer $copied    The collection to copy from
 * @param  integer $current   The collection to copy to
 * @param  boolean $remove_existing   Should existing items be removed?
 * @return void
 */
function copy_collection($copied,$current,$remove_existing=false)
	{	
	# Get all data from the collection to copy.
	$copied_collection=sql_query("select cr.resource, r.resource_type from collection_resource cr join resource r on cr.resource=r.ref where collection='" . escape_check($copied) . "'","");
	
	if ($remove_existing)
		{
		#delete all existing data in the current collection
		sql_query("delete from collection_resource where collection='" . escape_check($current) . "'");
		collection_log($current,LOG_CODE_COLLECTION_REMOVED_ALL_RESOURCES,0);
		}
	
	#put all the copied collection records in
	foreach($copied_collection as $col_resource)
		{
		# Use correct function so external sharing is honoured.
		add_resource_to_collection($col_resource['resource'],$current,true,"",$col_resource['resource_type']);
		}
	
	hook('aftercopycollection','',array($copied,$current));
	}

/**
 * Returns true if a collection is a research request
 *
 * @param  int  $collection   Collection ID
 * 
 * @return boolean
 */
function collection_is_research_request($collection)
	{
	return (sql_value("select count(*) value from research_request where collection='" . escape_check($collection) . "'",0)>0);
	}


/**
 * Generates a HTML link for adding a resource to a collection
 *
 * @param  integer  $resource   ID of resource
 * @param  string   $search     Search parameters
 * @param  string   $extracode  Additonal code to be run when link is selected
 * @param  string   $size       Resource size if appropriate
 * @param  string   $class      Class to be applied to link
 * 
 * @return string
 */
function add_to_collection_link($resource,$search="",$extracode="",$size="",$class="")
    {
    global $lang;

    return "<a class=\"addToCollection " . $class . "\" href=\"#\" title=\"" . $lang["addtocurrentcollection"] . "\" onClick=\"AddResourceToCollection(event,'" . $resource . "','" . $size . "');" . $extracode . " return false;\" data-resource-ref=\"{$resource}\">";

    }


/**
 * Render a "remove from collection" link wherever such a function is shown in the UI
 *
 * @param  integer  $resource
 * @param  string   $search
 * @param  string   $class
 * @param  string   $onclick  Additional onclick code to call before returning false.
 * 
 * @return void
 */
function remove_from_collection_link($resource,$search="",$class="", string $onclick = '')
    {
    # Generates a HTML link for removing a resource to a collection
    global $lang, $pagename;

    return "<a class=\"removeFromCollection " . $class . "\" href=\"#\" title=\"" . $lang["removefromcurrentcollection"] . "\" onClick=\"RemoveResourceFromCollection(event,'" . $resource . "','" . $pagename . "');{$onclick} return false;\" data-resource-ref=\"{$resource}\">";
    }


/**
 * Generates a HTML link for adding a changing the current collection
 *
 * @param  integer $collection
 * @return string
 */
function change_collection_link($collection)
    {
    global $lang;
    return '<a onClick="ChangeCollection('.$collection.',\'\');return false;" href="collections.php?collection='.$collection.'">' . LINK_CARET . $lang["selectcollection"].'</a>';
    }

/**
 * Return all external access given to a collection.
 * Users, emails and dates could be multiple for a given access key, an in this case they are returned comma-separated.
 *
 * @param  integer $collection
 * @return array
 */
function get_collection_external_access($collection)
	{
	global $userref;

	# Restrict to only their shares unless they have the elevated 'v' permission
    $condition="AND upload=0 ";
    if (!checkperm("v"))
        {
        $condition .= "AND user='" . escape_check($userref) . "'";
        }
	return sql_query("SELECT access_key,GROUP_CONCAT(DISTINCT user ORDER BY user SEPARATOR ', ') users,GROUP_CONCAT(DISTINCT email ORDER BY email SEPARATOR ', ') emails,MAX(date) maxdate,MAX(lastused) lastused,access,expires,usergroup,password_hash,upload,status from external_access_keys WHERE collection='" . escape_check($collection) . "' $condition group by access_key order by date");
	}


/**
 * Delete a specific collection access key, withdrawing access via that key to the collection in question
 *
 * @param  integer $collection
 * @param  string $access_key
 * @return void
 */
function delete_collection_access_key($collection,$access_key)
	{
	# Get details for log
	$users = sql_value("SELECT group_concat(DISTINCT email ORDER BY email SEPARATOR ', ') value FROM external_access_keys WHERE collection='" . escape_check($collection) . "' AND access_key = '" . escape_check($access_key) . "' group by access_key ", "");
	# Deletes the given access key.
    $sql = "DELETE FROM external_access_keys WHERE access_key='" . escape_check($access_key) . "'";
    if($collection != 0)
        {
        $sql .= " AND collection='" . escape_check($collection) . "'";
        }
    sql_query($sql);
	# log changes
	collection_log($collection,LOG_CODE_COLLECTION_STOPPED_RESOURCE_ACCESS,"",$users . " (" . $access_key. ")");
	}
	
/**
 * Add a new row to the collection log (e.g. after an action on that collection)
 *
 * @param  integer $collection
 * @param  string $type Action type
 * @param  integer $resource
 * @param  string $notes
 * @return void
 */
function collection_log($collection,$type,$resource,$notes = "")
	{
	global $userref;

	if (!is_numeric($collection)) {return false;}

	$modifiedcollogtype=hook("modifycollogtype","",array($type,$resource));
	if ($modifiedcollogtype) {$type=$modifiedcollogtype;}
	
	$modifiedcollognotes=hook("modifycollognotes","",array($type,$resource,$notes));
	if ($modifiedcollognotes) {$notes=$modifiedcollognotes;}

    $user = ($userref != "" ? "'" . escape_check($userref) . "'" : "NULL");
    $collection = escape_check($collection);
    $type = escape_check($type);
    $resource = $resource != "" ? "'" . escape_check($resource) . "'" : "NULL";
    $notes = escape_check(mb_strcut($notes, 0, 255));

	sql_query("
        INSERT INTO collection_log (date, user, collection, type, resource, notes)
             VALUES (now(), {$user}, '{$collection}', '{$type}', {$resource}, '{$notes}')");
	}
    
/**
 * Return the log for $collection
 *
 * @param  integer $collection
 * @param  integer $fetchrows   How many rows to fetch
 * @return array
 */
function get_collection_log($collection, $fetchrows = -1)
	{
    debug_function_call("get_collection_log", func_get_args());

	global $view_title_field;

    $extra_fields = hook("collection_log_extra_fields");
    if(!$extra_fields)
        {
        $extra_fields = "";
        }

	return sql_query("
                 SELECT c.ref,
                        c.date,
                        u.username,
                        u.fullname,
                        c.type,
                        r.field{$view_title_field} AS title,
                        c.resource,
                        c.notes
                        {$extra_fields}
                   FROM collection_log AS c
        LEFT OUTER JOIN user AS u ON u.ref = c.user
        LEFT OUTER JOIN resource AS r ON r.ref = c.resource
                  WHERE collection = '{$collection}'
               ORDER BY c.ref DESC", false, $fetchrows);
	}
        
/**
 * Returns the maximum access (the most permissive) that the current user has to the resources in $collection.
 *
 * @param  integer $collection
 * @return integer
 */
function collection_max_access($collection)	
	{
	$maxaccess=2;
    $result=do_search("!collection" . $collection);
    if (!is_array($result))
        {
        $result = array();
        }
	for ($n=0;$n<count($result);$n++)
		{
		$ref=$result[$n]["ref"];
		# Load access level
		$access=get_resource_access($result[$n]);
		if ($access<$maxaccess) {$maxaccess=$access;}
		}
	return $maxaccess;
	}

/**
 * Returns the minimum access (the least permissive) that the current user has to the resources in $collection.
 *
 *  Can be passed a collection ID or the results of a collection search, the result will be the most restrictive 
 *  access that is found.
 * 
 * @param  integer|array $collection    Collection ID as an integer or the result of a search as an array
 * 
 * @return integer                      0 - Open, 1 - restricted, 2 - Confidential
 */

function collection_min_access($collection)
    {
    global $k, $internal_share_access;
    if(is_array($collection))
        {
        $result = $collection;
        }
    else
        {
        $result = do_search("!collection{$collection}", '', 'relevance', 0, -1, 'desc', false, '', false, '');
        if (!is_array($result))
            {
            $result = array();
            }
        }
    if(count($result) > 0 && isset($result[0]["access"]) && !checkperm("v"))
        {
        $minaccess = max(array_column($result,"access"));
        }
    else
        {
        $minaccess = 0;
        }
    if($k != "")
		{
		# External access - check how this was shared. If internal share access and share is more open than the user's access return that
		$minextaccess = sql_value("SELECT max(access) value FROM external_access_keys WHERE resource IN ('" . implode("','",array_column($result,"ref")) . "') AND access_key = '" . escape_check($k) . "' AND (expires IS NULL OR expires > NOW())", -1);
        if($minextaccess != -1 && (!$internal_share_access || ($internal_share_access && ($minextaccess < $minaccess))))
            {
            return ($minextaccess);
            }
		}
    
    if ($minaccess = 3)
        {
            # Custom permissions are being used so test access to each resource, restricting access as needed
            $minaccess = 0;
        }

    for($n = 0; $n < count($result); $n++)
        {
        $access = get_resource_access($result[$n]);
        if($access > $minaccess)
            {
            $minaccess = $access;
            }
        }

    return $minaccess;
    }

/**
 * Set an existing collection to be public
 *
 * @param  integer  $collection   ID of collection
 * 
 * @return boolean
 */
function collection_set_public($collection)
	{
		if (is_numeric($collection)){
			$sql = "UPDATE collection SET `type` = " . COLLECTION_TYPE_PUBLIC . " WHERE ref = '$collection'";
			sql_query($sql);
			return true;
		} else {
			return false;
		}
	}

	
/**
 * Remove all resources from a collection
 *
 * @param  integer $ref The collection in question
 * @return void
 */
function remove_all_resources_from_collection($ref){
    // abstracts it out of save_collection()
    $removed_resources = sql_array("SELECT resource AS value FROM collection_resource WHERE collection = '" . escape_check($ref) . "';");

    collection_log($ref, LOG_CODE_COLLECTION_REMOVED_ALL_RESOURCES, 0);
    foreach($removed_resources as $removed_resource_id)
        {
        collection_log($ref, LOG_CODE_COLLECTION_REMOVED_RESOURCE, $removed_resource_id, ' - Removed all resources from collection ID ' . $ref);
        }

    sql_query("DELETE FROM collection_resource WHERE collection = '" . escape_check($ref) . "'");
    sql_query("DELETE FROM external_access_keys WHERE collection = '" . escape_check($ref) . "'");
    }	

function get_home_page_promoted_collections()
	{
    global $COLLECTION_PUBLIC_TYPES;
    $public_types = join(", ", $COLLECTION_PUBLIC_TYPES);
	return sql_query("select collection.ref, collection.`type`,collection.name,collection.home_page_publish,collection.home_page_text,collection.home_page_image,resource.thumb_height,resource.thumb_width, resource.resource_type, resource.file_extension from collection left outer join resource on collection.home_page_image=resource.ref where collection.`type` IN ({$public_types}) and collection.home_page_publish=1 order by collection.ref desc");
	}


/**
 * Return an array of distinct archive/workflow states for resources in $collection
 *
 * @param  integer $collection
 * @return array
 */
function is_collection_approved($collection)
		{
		if (is_array($collection)){$result=$collection;}
		else
			{
			$result=do_search("!collection" . $collection,"","relevance",0,-1,"desc",false,"",false,"");
			}	
		if (!is_array($result) || count($result)==0){return true;}
		
		$collectionstates=array();
		global $collection_allow_not_approved_share;
		for ($n=0;$n<count($result);$n++)
			{
			$archivestatus=$result[$n]["archive"];
			if ($archivestatus<0 && !$collection_allow_not_approved_share) {return false;}
			$collectionstates[]=$archivestatus;
			}
		return array_unique($collectionstates);
		}

/**
 * Update an existing external access share
 *
 * @param  string $key          External access key
 * @param  int $access          Share access level
 * @param  string $expires      Share expiration date
 * @param  int $group           ID of usergroup that share will emulate permissions for
 * @param  string $sharepwd     Share password
 * @param  array $shareopts     Array of additional share options
 *                              "collection"    - int   collection ID
 *                              "upload"        - bool  Set to true if share is an upload link (no visibility of existing resources)
 * 
 * @return boolean
 */
function edit_collection_external_access($key,$access=-1,$expires="",$group="",$sharepwd="", $shareopts=array())
	{
    global $usergroup, $scramble_key, $lang;
    
    $extraopts = array("collection", "upload");
    foreach($extraopts as $extraopt)
        {
        if(isset($shareopts[$extraopt]))
            {
            $$extraopt = $shareopts[$extraopt];
            }
        }
    if ($key=="")
        {
        return false;
        }

    if(!isset($upload) || !$upload)
        {
        // Only relevant for non-upload shares
        if ($group=="" || !checkperm("x"))
            {
            // Default to sharing with the permission of the current usergroup if not specified OR no access to alternative group selection.
            $group=$usergroup;
            }
        }
    // Ensure these are escaped as required here
    $setvals = array(
        "access"    => (int)$access,
        "date"      => "now()",
        "usergroup" => (int)$group,
        "upload"    => isset($upload) && $upload ? "1" : "upload",
        );
    if($expires!="") 
        {
        $setvals["expires"] = "'" . escape_check($expires) . "'";
        }
    else
        {
        $setvals["expires"] = "NULL";
        }
    if($sharepwd != "(unchanged)")
        {
        $setvals["password_hash"] = ($sharepwd == "") ? "''" : "'" . hash('sha256', $key . $sharepwd . $scramble_key) . "'";
        }
    $setsql = "";
    foreach($setvals as $setkey => $setval)
        {
        $setsql .= $setsql == "" ? "" : ",";
        $setsql .= $setkey . "=" . $setval ;
        }
	sql_query("UPDATE external_access_keys
                  SET " . $setsql . "
                WHERE access_key='$key'" . 
                      (isset($collection) ? " AND collection='" . (int)$collection . "'": "")
                );
    hook("edit_collection_external_access","",array($key,$access,$expires,$group,$sharepwd, $shareopts));
    if(isset($collection))
        {
        $lognotes = array("access_key" => $key);
        foreach($setvals as $column => $value)
            {
            if($column=="password_hash")
                {
                $lognotes[] = trim($value) != "" ? "password=TRUE" : "";
                }
            else
                {
                $lognotes[] = $column . "=" .  $value;
                }
            }
        collection_log($collection,LOG_CODE_COLLECTION_EDIT_UPLOAD_SHARE,NULL,"(" . implode(",",$lognotes) . ")");
        }    
       
	return true;
	}
	
/**
 * Hide or show a collection from the My Collections area.
 *
 * @param  integer $colref
 * @param  boolean $show    Show or hide?
 * @param  integer $user  
 * @return void
 */
function show_hide_collection($colref, $show=true, $user="")
	{
	global $userref;
	if($user=="" || $user==$userref)
		{
		// Working with logged on user, use global variable 
		$user=$userref;
		global $hidden_collections;
		}
	else
		{
		//Get hidden collections for user
		$hidden_collections=explode(",",sql_value("SELECT hidden_collections FROM user WHERE ref='" . escape_check($user) . "'",""));
		}
		
	if($show)
		{
		debug("Unhiding collection " . $colref . " from user " . $user);
		if(($key = array_search($colref, $hidden_collections)) !== false)
			{
			unset($hidden_collections[$key]);
			}
		}
	else
		{
		debug("Hiding collection " . $colref . " from user " . $user);
		if(($key = array_search($colref, $hidden_collections)) === false) 
			{
			$hidden_collections[]=$colref;
			}
		}
	sql_query("UPDATE user SET hidden_collections ='" . implode(",",$hidden_collections) . "' WHERE ref='" . escape_check($user) . "'");
	}
	
/**
 * Get an array of collection IDs for the specified ResourceSpace session and user
 *
 * @param  string  $rs_session  Session id - as obtained by get_rs_session_id()
 * @param  integer $userref     User ID
 * @param  boolean $create      Create new collection?
 * 
 * @return array Array of collection IDs for the specified sesssion
 */
function get_session_collections($rs_session,$userref="",$create=false)
	{
	$extrasql="";
	if($userref!="")
		{
		$extrasql="AND user='" . escape_check($userref) ."'";	
        }
    else
        {
        $userref='NULL';
        }
	$collectionrefs=sql_array("SELECT ref value FROM collection WHERE session_id='" . escape_check($rs_session) . "' AND type IN ('" . COLLECTION_TYPE_STANDARD . "','" . COLLECTION_TYPE_UPLOAD . "','" . COLLECTION_SHARE_UPLOAD . "') " . $extrasql,"");
	if(count($collectionrefs)<1 && $create)
		{
        if(upload_share_active())
            {
            $collectionrefs[0]=create_collection($userref,"New uploads",0,1,0,false,array("type"=>5)); # Do not translate this string!
            }
        else
            {
            $collectionrefs[0]=create_collection($userref,"Default Collection",0,1); # Do not translate this string!	
            }
		}		
	return $collectionrefs;	
	}

/**
 * Update collection to belong to a new user
 *
 * @param  integer $collection  Collection ID
 * @param  integer $newuser     User ID to assign collection to
 * 
 * @return boolean success|failure
 */
function update_collection_user($collection,$newuser)
	{	
	if (!collection_writeable($collection))
		{debug("FAILED TO CHANGE COLLECTION USER " . $collection);return false;}
		
	sql_query("UPDATE collection SET user='" . escape_check($newuser) . "' WHERE ref='" . escape_check($collection) . "'");  
	return true;	
	}

/**
* Helper function for render_actions(). Compiles actions that are normally valid for collections
* 
* @param array   $collection_data  Collection data
* @param boolean $top_actions      Set to true if actions are to be rendered in the search filter bar (above results)
* @param array   $resource_data    Resource data
* 
* @return array
*/
function compile_collection_actions(array $collection_data, $top_actions, $resource_data=array())
    {
    global $baseurl_short, $lang, $k, $userrequestmode, $zipcommand, $collection_download, $use_zip_extension, $archiver_path,
           $manage_collections_contact_sheet_link, $manage_collections_share_link, $allow_share, $enable_collection_copy,
           $manage_collections_remove_link, $userref, $collection_purge, $show_edit_all_link, $result,
           $edit_all_checkperms, $preview_all, $order_by, $sort, $archive, $contact_sheet_link_on_collection_bar,
           $show_searchitemsdiskusage, $emptycollection, $remove_resources_link_on_collection_bar, $count_result,
           $download_usage, $home_dash, $top_nav_upload_type, $pagename, $offset, $col_order_by, $find, $default_sort,
           $default_collection_sort, $starsearch, $restricted_share, $hidden_collections, $internal_share_access, $search,
           $usercollection, $disable_geocoding, $geo_locate_collection, $collection_download_settings, $contact_sheet,
           $allow_resource_deletion, $pagename,$upload_then_edit, $enable_related_resources,$list, $enable_themes,
           $system_read_only;
               
	#This is to properly render the actions drop down in the themes page	
	if ( isset($collection_data['ref']) && $pagename!="collections" )
		{
        if(!is_array($result))
            {
            $result = get_collection_resources_with_data($collection_data['ref']);
            }

        if(('' == $k || $internal_share_access) && is_null($list))
            {
            $list = get_user_collections($userref);
            }

		$count_result = count($result);
		}
	
	if(isset($search) && substr($search, 0, 11) == '!collection' && ($k == '' || $internal_share_access))
		{ 
		# Extract the collection number - this bit of code might be useful as a function
    	$search_collection = explode(' ', $search);
    	$search_collection = str_replace('!collection', '', $search_collection[0]);
    	$search_collection = explode(',', $search_collection); // just get the number
    	$search_collection = escape_check($search_collection[0]);
    	}

    // Collection bar actions should always be a special search !collection[ID] (exceptions might arise but most of the 
    // time it should be handled using the special search). If top actions then search may include additional refinement inside the collection

    if(isset($collection_data['ref']) && !$top_actions)
        {
        $search = "!collection{$collection_data['ref']}";
        }

    $urlparams = array(
        "search"      =>  $search,
        "collection"  =>  (isset($collection_data['ref']) ? $collection_data['ref'] : ""),
        "ref"         =>  (isset($collection_data['ref']) ? $collection_data['ref'] : ""),
        "restypes"    =>  isset($_COOKIE['restypes']) ? $_COOKIE['restypes'] : "",
        "starsearch"  =>  $starsearch,
        "order_by"    =>  $order_by,
        "col_order_by"=>  $col_order_by,
        "sort"        =>  $sort,
        "offset"      =>  $offset,
        "find"        =>  $find,
        "k"           =>  $k);
    
    $options = array();
	$o=0;

    if(empty($collection_data))
        {
        return $options;
        }
	
	if(empty($order_by))
    	{
		$order_by = $default_collection_sort;
		}
    
    if($pagename == 'collection_manage') 
        {
        $min_access = collection_min_access($collection_data['ref']);
        }
    else
        {
        $min_access = collection_min_access(empty($resource_data) ? $result : $resource_data);
        }

    // If resourceconnect plugin activated, need to consider if resource connect resources exist in the collection - if yes display view all resources link	
	$count_resourceconnect_resources = hook("countresult","", array($urlparams["collection"],0));
	$count_resourceconnect_resources = is_numeric($count_resourceconnect_resources) ? $count_resourceconnect_resources : 0;

    // View all resources
    if(
        !$top_actions // View all resources makes sense only from collection bar context
        && (
            ($k=="" || $internal_share_access)
            && (isset($collection_data["c"]) && $collection_data["c"] > 0)
            || (is_array($result) && count($result) > 0) || ($count_resourceconnect_resources > 0)
        )
    )
        {
        $tempurlparams = array(
            'sort' => 'ASC',
            'search' => (isset($collection_data['ref']) ? "!collection{$collection_data['ref']}" : $search),
        );

        $data_attribute['url'] = generateURL($baseurl_short . "pages/search.php",$urlparams,$tempurlparams);
        $options[$o]['value']='view_all_resources_in_collection';
		$options[$o]['label']=$lang['view_all_resources'];
		$options[$o]['data_attr']=$data_attribute;
		$options[$o]['category'] = ACTIONGROUP_RESOURCE;
        $options[$o]['order_by'] = 10;
		$o++;
        }
    
    // Download option
    if($min_access == 0 )
        {
        if( $download_usage && ( isset($zipcommand) || $use_zip_extension || ( isset($archiver_path) && isset($collection_download_settings) ) ) && $collection_download && $count_result > 0)
            {
            $download_url = generateURL($baseurl_short . "pages/download_usage.php",$urlparams);
            $data_attribute['url'] = generateURL($baseurl_short . "pages/terms.php",$urlparams,array("url"=>$download_url));
            $options[$o]['value']='download_collection';
            $options[$o]['label']=$lang['action-download'];
            $options[$o]['data_attr']=$data_attribute;
            $options[$o]['category'] = ACTIONGROUP_RESOURCE;
            $options[$o]['order_by'] = 20;
            $o++;
            }
        else if( (isset($zipcommand) || $use_zip_extension || ( isset($archiver_path) && isset($collection_download_settings) ) ) && $collection_download && $count_result > 0)
            {
            $download_url = generateURL($baseurl_short . "pages/collection_download.php",$urlparams);
            $data_attribute['url'] = generateURL($baseurl_short . "pages/terms.php",$urlparams,array("url"=>$download_url));
            $options[$o]['value']='download_collection';
            $options[$o]['label']=$lang['action-download'];
            $options[$o]['data_attr']=$data_attribute;
            $options[$o]['category'] = ACTIONGROUP_RESOURCE;
            $options[$o]['order_by'] = 20;
            $o++;
            }
        }

    // Upload to collection
    if(allow_upload_to_collection($collection_data))
        {
        if($upload_then_edit)
            {
            $data_attribute['url'] = generateURL($baseurl_short . "pages/upload_plupload.php",array(),array("collection_add"=>$collection_data['ref']));
            }
        else
            {
            $data_attribute['url'] = generateURL($baseurl_short . "pages/edit.php",array(),array("uploader"=>$top_nav_upload_type,"ref"=>-$userref, "collection_add"=>$collection_data['ref']));
            }

        $options[$o]['value']='upload_collection';
		$options[$o]['label']=$lang['action-upload-to-collection'];
		$options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_RESOURCE;
        $options[$o]['order_by'] = 30;
		$o++;
        }
    

    // Preview all
    if((is_array($result) && count($result) != 0) && ($k=="" || $internal_share_access) && $preview_all)
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/preview_all.php",$urlparams);
        $options[$o]['value']='preview_all';
		$options[$o]['label']=$lang['preview_all'];
        $options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_RESOURCE;
        $options[$o]['order_by'] = 40;
		$o++;
        }

     // Remove all resources from collection
     if(!checkperm("b") && 0 < $count_result && ($k=="" || $internal_share_access) && isset($emptycollection) && $remove_resources_link_on_collection_bar && collection_writeable($collection_data['ref']))
     {
     $data_attribute['url'] = generateURL($baseurl_short . "pages/collections.php",$urlparams,array("emptycollection"=>$collection_data['ref'],"removeall"=>"true","ajax"=>"true","submitted"=>"removeall"));
     $options[$o]['value']     = 'empty_collection';
     $options[$o]['label']     = $lang['emptycollection'];
     $options[$o]['data_attr'] = $data_attribute;
    $options[$o]['category']  = ACTIONGROUP_RESOURCE;
    $options[$o]['order_by'] = 50;
     $o++;
     }
 
    if(!collection_is_research_request($collection_data['ref']) || !checkperm('r'))
        {
        if(!$top_actions && checkperm('s') && $pagename === 'collections')
            {
            // Collection feedback
            if(isset($collection_data['request_feedback']) && $collection_data['request_feedback'])
                {
                $data_attribute['url'] = sprintf('%spages/collection_feedback.php?collection=%s&k=%s',
                    $baseurl_short,
                    urlencode($collection_data['ref']),
                    urlencode($k)
                );
                $options[$o]['value']='collection_feedback';
				$options[$o]['label']=$lang['sendfeedback'];
				$options[$o]['data_attr']=$data_attribute;
                $options[$o]['category'] = ACTIONGROUP_RESOURCE;
                $options[$o]['order_by'] = 70;
				$o++;
                }
            }
        }
    else
        {
        $research = sql_value('SELECT ref value FROM research_request WHERE collection="' . escape_check($collection_data['ref']) . '";', 0);

        // Manage research requests
        $data_attribute['url'] = generateURL($baseurl_short . "pages/team/team_research.php",$urlparams);
        $options[$o]['value']='manage_research_requests';
		$options[$o]['label']=$lang['manageresearchrequests'];
		$options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_RESEARCH;
        $options[$o]['order_by'] = 80;
		$o++;

        // Edit research requests
        $data_attribute['url'] = generateURL($baseurl_short . "pages/team/team_research_edit.php",$urlparams,array("ref"=>$research));
        $options[$o]['value']='edit_research_requests';
		$options[$o]['label']=$lang['editresearchrequests'];
		$options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_RESEARCH;
        $options[$o]['order_by'] = 90;
		$o++;
        }

    // Select collection option - not for collection bar
    if($pagename != 'collections' && ($k == '' || $internal_share_access) && !checkperm('b')
        && ($pagename == 'load_actions' || $pagename == 'themes' || $pagename === 'collection_manage' || $pagename === 'resource_collection_list' || $top_actions)
        && ((isset($search_collection) && isset($usercollection) && $search_collection != $usercollection) || !isset($search_collection))
        && collection_readable($collection_data['ref'])
    )
        {
        $options[$o]['value'] = 'select_collection';
        $options[$o]['label'] = $lang['selectcollection'];
        $options[$o]['category'] = ACTIONGROUP_COLLECTION;
        $options[$o]['order_by'] = 100;
        $o++;
        }

    // Copy resources from another collection. Must be in top actions or have more than one collection available if on collections.php
    if(
        !checkperm('b')
        && ($k == '' || $internal_share_access)
        && collection_readable($collection_data['ref'])
        && ($top_actions || (is_array($list) && count($list) > 1))
        && $enable_collection_copy
    )
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/collection_copy_resources.php",array("ref"=>$collection_data['ref']));
        $options[$o]['data_attr'] = $data_attribute;
        $options[$o]['value'] = 'copy_collection';
        $options[$o]['label'] = $lang['copyfromcollection'];
        $options[$o]['category'] = ACTIONGROUP_RESOURCE;
        $options[$o]['order_by'] = 105;
        $o++;
        }

    // Edit Collection
    if((($userref == $collection_data['user']) || (checkperm('h')))  && ($k == '' || $internal_share_access) && !$system_read_only) 
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/collection_edit.php",$urlparams);
        $options[$o]['value']='edit_collection';
        $options[$o]['label']=$lang['editcollection'];
        $options[$o]['data_attr'] = $data_attribute;
        $options[$o]['category'] = ACTIONGROUP_EDIT;
        $options[$o]['order_by'] = 110;
        $o++;
        }
    // work this out in one place to prevent multiple calls as function is expensive
    $allow_multi_edit=allow_multi_edit(empty($resource_data) ? $collection_data['ref'] : $resource_data, $collection_data['ref']);

    // Edit all
    # If this collection is (fully) editable, then display an edit all link
    if(($k=="" || $internal_share_access) && $show_edit_all_link && $count_result>0)
        {
        if($allow_multi_edit)
            {
            $extra_params = array(
                'editsearchresults' => 'true',
            );

            $data_attribute['url'] = generateURL($baseurl_short . "pages/edit.php", $urlparams, $extra_params);
            $options[$o]['value']='edit_all_in_collection';
            $options[$o]['label']=$lang['edit_all_resources'];
            $options[$o]['data_attr']=$data_attribute;
            $options[$o]['category'] = ACTIONGROUP_EDIT;
            $options[$o]['order_by'] = 120;
            $o++;
            }
        }
    
    
    // Edit Previews
	if (($k=="" || $internal_share_access) && $count_result > 0 && !(checkperm('F*')) && ($userref == $collection_data['user'] || $collection_data['allow_changes'] == 1 || checkperm('h')) && $allow_multi_edit)
        {
        $main_pages   = array('search', 'collection_manage', 'collection_public', 'themes');
        $back_to_page = (in_array($pagename, $main_pages) ? htmlspecialchars($pagename) : '');
        $data_attribute['url'] = generateURL($baseurl_short . "pages/collection_edit_previews.php",$urlparams,array("backto"=>$back_to_page));
        $options[$o]['value']     = 'edit_previews';
        $options[$o]['label']     = $lang['editcollectionresources'];
        $options[$o]['data_attr'] = $data_attribute;
        $options[$o]['category']  = ACTIONGROUP_EDIT;
        $options[$o]['order_by']  = 130;
        $o++;
        }

    // Share
    if(allow_collection_share($collection_data))
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/collection_share.php",$urlparams);
        $options[$o]['value']='share_collection';
        $options[$o]['label']=$lang['share'];
        $options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_SHARE;
        $options[$o]['order_by']  = 140;
        $o++;
        }

    // Share external link to upload to collection, not permitted if already externally shared for view access
    $eakeys = get_external_shares(array("share_collection"=>$collection_data['ref'],"share_type"=>0));
    if(can_share_upload_link($collection_data) && count($eakeys) == 0)
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/share_upload.php",array(),array("share_collection"=>$collection_data['ref']));
        $options[$o]['value']='share_upload';
		$options[$o]['label']=$lang['action-share-upload-link'];
		$options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_SHARE;
        $options[$o]['order_by'] = 30;
		$o++;
        }
        
    // Home_dash is on, AND NOT Anonymous use, AND (Dash tile user (NOT with a managed dash) || Dash Tile Admin)
    if(!$top_actions && $home_dash && ($k == '' || $internal_share_access) && checkPermission_dashcreate() && !$system_read_only)
        {
        $tileparams = array(
            "create"            =>"true",
            "tltype"            =>"srch",
            "promoted_resource" =>"true",
            "freetext"          =>"true",
            "all_users"         =>"1",
            "link"              => $baseurl_short . "pages/search.php?search=!collection" . $collection_data['ref'],
            );
        
        $data_attribute['url'] = generateURL($baseurl_short . "pages/dash_tile.php",$urlparams,$tileparams);
        $options[$o]['value']='save_collection_to_dash';
        $options[$o]['label']=$lang['createnewdashtile'];
        $options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_SHARE;
        $options[$o]['order_by']  = 150;
        $o++;
        }

    // Add option to publish as featured collection
    if($enable_themes && ($k == '' || $internal_share_access) && checkperm("h"))
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/collection_set_category.php", $urlparams);
        $options[$o]['value'] = 'collection_set_category';
        $options[$o]['label'] = $lang['collection_set_theme_category'];
        $options[$o]['data_attr'] = $data_attribute;
        $options[$o]['category'] = ACTIONGROUP_SHARE;
        $options[$o]['order_by'] = 160;
        $o++;
        }

    // Request all
    if($count_result > 0 && ($k == '' || $internal_share_access))
        {
		# Ability to request a whole collection (only if user has restricted access to any of these resources)
        if($min_access != 0)
            {                
            $data_attribute['url'] = generateURL($baseurl_short . "pages/collection_request.php",$urlparams);
            $options[$o]['value']='request_all';
            $options[$o]['label']=$lang['requestall'];
            $options[$o]['data_attr']=$data_attribute;
            $options[$o]['category'] = ACTIONGROUP_RESOURCE;
            $options[$o]['order_by']  = 170;
            $o++;
            }
        }

	if(($geo_locate_collection && !$disable_geocoding) && $count_result > 0)
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/geolocate_collection.php",$urlparams);
        $options[$o]['value']='geolocatecollection';
        $options[$o]['label']=$lang["geolocatecollection"];
        $options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_RESOURCE;
        $options[$o]['order_by']  = 180;
        $o++;            
        }
	

    // Contact Sheet
    if(0 < $count_result && ($k=="" || $internal_share_access) && $contact_sheet == true && ($manage_collections_contact_sheet_link || $contact_sheet_link_on_collection_bar))
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/contactsheet_settings.php",$urlparams);
        $options[$o]['value']='contact_sheet';
        $options[$o]['label']=$lang['contactsheet'];
        $options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_ADVANCED;
        $options[$o]['order_by']  = 190;
        $o++;
        }

    // Remove
    if(($k=="" || $internal_share_access)
        && $manage_collections_remove_link
        && $userref != $collection_data['user']
        && !checkperm('b')
        && collection_readable($collection_data['ref'])
    )
        {
        $options[$o]['value']='remove_collection';
        $options[$o]['label']=$lang['action-remove'];
        $options[$o]['category'] = ACTIONGROUP_COLLECTION;
        $options[$o]['order_by']  = 200;
        $o++;
        }

    // Delete
    if(($k=="" || $internal_share_access) && (($userref == $collection_data['user']) || checkperm('h')) && ($collection_data['cant_delete'] == 0)) 
        {
        $options[$o]['value']='delete_collection';
        $options[$o]['label']=$lang['action-deletecollection'];
        $options[$o]['category'] = ACTIONGROUP_EDIT;
        $options[$o]['order_by']  = 210;
        $o++;
        }

    // Collection Purge
    if(($k=="" || $internal_share_access) && $collection_purge && isset($collections) && checkperm('e0') && $collection_data['cant_delete'] == 0)
        {
        $options[$o]['value']='purge_collection';
        $options[$o]['label']=$lang['purgeanddelete'];
        $options[$o]['category'] = ACTIONGROUP_EDIT;
        $options[$o]['order_by']  = 220;
        $o++;
        }

    // Collection log
    if(($k=="" || $internal_share_access) && ($userref== $collection_data['user'] || (checkperm('h'))))
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/collection_log.php",$urlparams);
        $options[$o]['value']='collection_log';
        $options[$o]['label']=$lang['action-log'];
        $options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_ADVANCED;
        $options[$o]['order_by']  = 230;
        $o++;
        }
          
    // Delete all
    // Note: functionality moved from edit collection page
    if(($k=="" || $internal_share_access) 
		&& !$top_actions
        && ((is_array($result) && count($result) != 0) || $count_result != 0)
        && (isset($allow_resource_deletion) && $allow_resource_deletion)
        && collection_writeable($collection_data['ref'])
        && $allow_multi_edit
        && !checkperm('D'))
        {
        $options[$o]['value']='delete_all_in_collection';
        $options[$o]['label']=$lang['deleteallresourcesfromcollection'];
        $options[$o]['category'] = ACTIONGROUP_EDIT;
        $options[$o]['order_by']  = 240;
        $o++;
        }

    // Show disk usage
    if(($k=="" || $internal_share_access) && (checkperm('a') || checkperm('v')) && !$top_actions && $show_searchitemsdiskusage && 0 < $count_result) 
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/search_disk_usage.php",$urlparams);
        $options[$o]['value']='search_items_disk_usage';
        $options[$o]['label']=$lang['collection_disk_usage'];
        $options[$o]['data_attr'] = $data_attribute;
        $options[$o]['category']  = ACTIONGROUP_ADVANCED;
        $options[$o]['order_by']  = 250;
        $o++;
        }

    // CSV export of collection metadata
    if(0 < $count_result 
        && !$top_actions
        && ($k =='' || $internal_share_access)
        && collection_readable($collection_data['ref'])
    )
        {
        $options[$o]['value']            = 'csv_export_results_metadata';
        $options[$o]['label']            = $lang['csvExportResultsMetadata'];
        $data_attribute['url'] = generateURL($baseurl_short . "pages/csv_export_results_metadata.php",$urlparams);
        $options[$o]['data_attr'] = $data_attribute;
        $options[$o]['category']  = ACTIONGROUP_ADVANCED;
        $options[$o]['order_by']  = 260;
        $o++;

        if(!checkperm('b') && !$system_read_only)
            {
            // Hide Collection
            $user_mycollection=sql_value("select ref value from collection where user='" . escape_check($userref) . "' and name='Default Collection' order by ref limit 1","");
            // check that this collection is not hidden. use first in alphabetical order otherwise
            if(in_array($user_mycollection,$hidden_collections)){
                $hidden_collections_list=implode(",",array_filter($hidden_collections));
                $user_mycollection=sql_value("select ref value from collection where user='" . escape_check($userref) . "'" . ((trim($hidden_collections_list)!='')?" and ref not in(" . $hidden_collections_list . ")":"") . " order by ref limit 1","");
            }
            $extra_tag_attributes = sprintf('
                    data-mycol="%s"
                ',
                urlencode($user_mycollection)
            );
            
            if ($pagename != "load_actions")
                {
                $options[$o]['value'] = 'hide_collection';
                $options[$o]['label'] = $lang['hide_collection'];
                $options[$o]['extra_tag_attributes']=$extra_tag_attributes;	
                $options[$o]['category']  = ACTIONGROUP_ADVANCED;
                $options[$o]['order_by']  = 270;
                $o++;
                }
        }
        }
        
    
    // Relate all resources
    if($enable_related_resources && $allow_multi_edit && 0 < $count_result && $count_resourceconnect_resources == 0) 
        {
        $options[$o]['value'] = 'relate_all';
        $options[$o]['label'] = $lang['relateallresources'];
        $options[$o]['category']  = ACTIONGROUP_ADVANCED;
        $options[$o]['order_by']  = 280;
        $o++;
        }

    // Add extra collection actions and manipulate existing actions through plugins
    $modified_options = hook('render_actions_add_collection_option', '', array($top_actions,$options,$collection_data, $urlparams));
    if(is_array($modified_options) && !empty($modified_options))
		{
        $options=$modified_options;
        }

    return $options;
    }


/**
* Make a filename unique by appending a dupe-string.
*
* @param array $base_values
* @param string $filename
* @param string $dupe_string
* @param string $extension
* @param int $dupe_increment
*
* @return string Unique filename
*/
function makeFilenameUnique($base_values, $filename, $dupe_string, $extension, $dupe_increment = null)
    {
    // Create filename to check if exist in $base_values
    $check_filename = $filename . ($dupe_increment ? $dupe_string . $dupe_increment : '') . '.' . $extension;

    if(!in_array($check_filename, $base_values))
        {
        // Confirmed filename does not exist yet
        return $check_filename;
        }

    // Recursive call this function with incremented value
    // Doing $dupe_increment = null, ++$dupe_increment results in $dupe_increment = 1
    return makeFilenameUnique($base_values, $filename, $dupe_string, $extension, ++$dupe_increment);
    }

/**
* Render the new featured collection form
*
* @param int $parent Featured collection parent. Use zero for root featured collection category 
* 
* @return void
*/
function new_featured_collection_form(int $parent)
    {
    global $baseurl_short, $lang, $collection_allow_creation;

    if(!$collection_allow_creation)
        {
        return;
        }

    if(!checkperm('h'))
        {
        http_response_code(401);
        exit($lang['error-permissiondenied']);
        }

    $form_action = "{$baseurl_short}pages/collection_manage.php";
    ?>
    <div class="BasicsBox">
        <h1><?php echo $lang["createnewcollection"]; ?></h1>
        <form name="new_collection_form" id="new_collection_form" class="modalform"
              method="POST" action="<?php echo $form_action; ?>" onsubmit="return CentralSpacePost(this, true);">
            <?php generateFormToken("new_collection_form"); ?>
            <input type="hidden" name="call_to_action_tile" value="true"></input>
            <input type="hidden" name="parent" value="<?php echo $parent; ?>"></input>
            <div class="Question">
                <label for="newcollection" ><?php echo $lang["collectionname"]; ?></label>
                <input type="text" name="name" id="newcollection" maxlength="100" required="true"></input>
                <div class="clearleft"></div>
            </div>
            <div class="QuestionSubmit" >
                <label></label>
                <input type="submit" name="create" value="<?php echo $lang["create"]; ?>"></input>
                <div class="clearleft"></div>
            </div>
        </form>
    </div>
    <?php

    return;
	}
    

/**
* Get a themes array
*
* @param int $levels    Number of levels to parse from request
* 
* @return array         Array containing names of themes matching the syntax used in the collection table i.e. theme, theme2, theme3
*/   
function GetThemesFromRequest($levels)
    {
    $themes = array();
    for($n = 0; $n <= $levels; $n++)
        {
        $themeindex = ($n == 0 ? "" : $n);
        $themename = getval("theme$themeindex","");
        if($themename != "")
            {
            $themes[] = $themename;
            }
        // Legacy inconsistency when naming themes params. Sometimes the root theme was also named theme1. We check if theme 
        // is found, but if not, we just go to theme1 rather than break.
        else if($themeindex == 0 && $themename == "")
            {
            continue;
            }
        else
            {
            break;    
            }
        }           
    return $themes;
    }

/**
 * Define the archive file.
 *
 * @param  array|boolean  $archiver    
 * @param  string         $settings_id   
 * @param  string         $usertempdir
 * @param  string         $collection
 * @param  string         $size
 * @param  object         $zip
 * @param  string         $zipfile
 * 
 * @return string
 */
function collection_download_get_archive_file($archiver, $settings_id, $usertempdir, $collection, $size, &$zip, &$zipfile)
    {
    global $lang, $use_zip_extension, $collection_download_settings;

    if($use_zip_extension)
        {
        $zipfile = $usertempdir . "/zip.zip";
        $zip = new ZipArchive();
        $zip->open($zipfile, ZIPARCHIVE::CREATE);
        }
    else if($archiver)
        {
        $zipfile = $usertempdir . "/".$lang["collectionidprefix"] . $collection . "-" . $size . "." . $collection_download_settings[$settings_id]["extension"];
        }
    else
        {
        $zipfile = $usertempdir . "/".$lang["collectionidprefix"] . $collection . "-" . $size . ".zip";
        }

    return;
    }

function collection_download_use_original_filenames_when_downloading(&$filename, $ref, $collection_download_tar, &$filenames,$id='')
    {
    if(trim($filename) === '')
        {
        return;
        }

    global $pextension, $usesize, $subbed_original, $prefix_resource_id_to_filename, $prefix_filename_string,
           $download_filename_id_only, $deletion_array, $use_zip_extension, $copy, $exiftool_write_option, $p, $size, $lang;

    # Only perform the copy if an original filename is set.

    # now you've got original filename, but it may have an extension in a different letter case. 
    # The system needs to replace the extension to change it to jpg if necessary, but if the original file
    # is being downloaded, and it originally used a different case, then it should not come from the file_extension, 
    # but rather from the original filename itself.
    
    # do an extra check to see if the original filename might have uppercase extension that can be preserved.   
    # also, set extension to "" if the original filename didn't have an extension (exiftool identification of filetypes)
    $pathparts = pathinfo($filename);
    if(isset($pathparts['extension']))
        {
        if(strtolower($pathparts['extension']) == $pextension)
            {
            $pextension = $pathparts['extension'];
            }
        }

    if ($usesize!=""&&!$subbed_original){$append="-".$usesize;}else {$append="";}
	$basename_minus_extension=remove_extension($pathparts['basename']);

    $fs=explode("/",$filename);
    $filename=$fs[count($fs)-1]; 
    set_unique_filename($filename,$filenames);
   
    # Copy to tmp (if exiftool failed) or rename this file
    # this is for extra efficiency to reduce copying and disk usage
    
    if(!($collection_download_tar || $use_zip_extension))
        {
        // the copy or rename to the filename is not necessary using the zip extension since the archived filename can be specified.
        $newpath = get_temp_dir(false,$id) . '/' . $filename;

        if(!$copy && $exiftool_write_option)
            {
            rename($p, $newpath);
            }
        else
            {
            copy($p,$newpath);
            }

        # Add the temporary file to the post-archiving deletion list.
        $deletion_array[] = $newpath;

        # Set p so now we are working with this new file
        $p = $newpath;
        }

    if(empty($filename))
        {
        $filename=$prefix_filename_string . $ref . "_" . $size . "." . $pextension;
        }

    return;
    }

/**
 * Add resource data/collection_resource data to text file during a collection download.
 *
 * @param  integer $ref
 * @param  integer $collection
 * @param  string $filename
 * @return void
 */
function collection_download_process_text_file($ref, $collection, $filename)
    {
    global $lang, $zipped_collection_textfile, $includetext, $size, $subbed_original, $k, $text, $sizetext;

    if (($zipped_collection_textfile==true)&&($includetext=="true"))
        {
        if ($size==""){$sizetext="";}else{$sizetext="-".$size;}
        if ($subbed_original) { $sizetext = '(' . $lang['substituted_original'] . ')'; }
        if($k === '')
            {
            $fields = get_resource_field_data($ref);
            }
        else
            {
            // External shares should take into account fields that are not meant to show in that case
            $fields = get_resource_field_data($ref, false, true, NULL, true);
            }
        $commentdata=get_collection_resource_comment($ref,$collection);
        if (count($fields)>0)
            { 
            $text.= ($sizetext=="" ? "" : $sizetext) ." ". $filename. "\r\n-----------------------------------------------------------------\r\n";
            $text.= $lang["resourceid"] . ": " . $ref . "\r\n";
                for ($i=0;$i<count($fields);$i++){
                    $value=$fields[$i]["value"];
                    $title=str_replace("Keywords - ","",$fields[$i]["title"]);
                    if ((trim($value)!="")&&(trim($value)!=",")){$text.= wordwrap("* " . $title . ": " . i18n_get_translated($value) . "\r\n", 65);}
                }
            if(trim($commentdata['comment'])!=""){$text.= wordwrap($lang["comment"] . ": " . $commentdata['comment'] . "\r\n", 65);}    
            if(trim($commentdata['rating'])!=""){$text.= wordwrap($lang["rating"] . ": " . $commentdata['rating'] . "\r\n", 65);}   
            $text.= "-----------------------------------------------------------------\r\n\r\n";    
            }
        }

    return;
    }


/**
 * Update the resource log to show the download during a collection download.
 *
 * @param  string $tmpfile
 * @param  array $deletion_array
 * @param  integer $ref The resource ID
 * @return void
 */
function collection_download_log_resource_ready($tmpfile, &$deletion_array, $ref)
    {
    global $usage, $usagecomment, $size, $resource_hit_count_on_downloads;

    # build an array of paths so we can clean up any exiftool-modified files.
    if($tmpfile!==false && file_exists($tmpfile)){$deletion_array[]=$tmpfile;}

    daily_stat("Resource download",$ref);
    resource_log($ref,'d',0,$usagecomment,"","", (int) $usage,$size);
    
    # update hit count if tracking downloads only
    if ($resource_hit_count_on_downloads)
        { 
        # greatest() is used so the value is taken from the hit_count column in the event that new_hit_count is zero to support installations that did not previously have a new_hit_count column (i.e. upgrade compatability).
        sql_query("update resource set new_hit_count=greatest(hit_count,new_hit_count)+1 where ref='" . escape_check($ref) . "'");
        }

    return;
    }

/**
 * Adds a progress indicator to the zip progress file, so we can show the zip progress to the user.
 *
 * @param  string $note The note to display
 * @return void
 */
function update_zip_progress_file($note)
    {
    global $progress_file, $offline_job_in_progress;
    if($offline_job_in_progress)
        {
        return false;
        }
    $fp = fopen($progress_file, 'w');       
    $filedata=$note;
    fwrite($fp, $filedata);
    fclose($fp);
    }

/**
 * Add PDFs for "data only" types to a zip file during creation.
 *
 * @param  array $result
 * @param  integer $id
 * @param  boolean $collection_download_tar
 * @param  string $usertempdir
 * @param  object $zip
 * @param  string $path
 * @param  array $deletion_array
 * @return void
 */
function collection_download_process_data_only_types(array $result, $id, $collection_download_tar, $usertempdir, &$zip, &$path, &$deletion_array)
    {
    global $data_only_resource_types, $k, $usage, $usagecomment, $resource_hit_count_on_downloads, $use_zip_extension;

    for($n = 0; $n < count($result); $n++)
        {
        // Data-only type of resources should be generated and added in the archive
        if(in_array($result[$n]['resource_type'], $data_only_resource_types))
            {
            $template_path = get_pdf_template_path($result[$n]['resource_type']);
            $pdf_filename = 'RS_' . $result[$n]['ref'] . '_data_only.pdf';
            $pdf_file_path = get_temp_dir(false, $id) . '/' . $pdf_filename;

            // Go through fields and decide which ones we add to the template
            $placeholders = array(
                'resource_type_name' => get_resource_type_name($result[$n]['resource_type'])
            );

            $metadata = get_resource_field_data($result[$n]['ref'], false, true, NULL, '' != $k);

            foreach($metadata as $metadata_field)
                {
                $metadata_field_value = trim(tidylist(i18n_get_translated($metadata_field['value'])));

                // Skip if empty
                if('' == $metadata_field_value)
                    {
                    continue;
                    }

                $placeholders['metadatafield-' . $metadata_field['ref'] . ':title'] = $metadata_field['title'];
                $placeholders['metadatafield-' . $metadata_field['ref'] . ':value'] = $metadata_field_value;
                }
            generate_pdf($template_path, $pdf_file_path, $placeholders, true);

            // Go and add file to archive
           if($collection_download_tar)
                {
                // Add a link to pdf 
                symlink($pdf_file_path, $usertempdir . DIRECTORY_SEPARATOR  . $pdf_filename); 
                }
            elseif($use_zip_extension)
                {
                $zip->addFile($pdf_file_path, $pdf_filename);
                }
            else
                {
                $path .= $pdf_file_path . "\r\n";
                }
            $deletion_array[] = $pdf_file_path;

            continue;
            }

        daily_stat('Resource download', $result[$n]['ref']);
        resource_log($result[$n]['ref'], 'd', 0, $usagecomment, '', '', (int) $usage);

        if($resource_hit_count_on_downloads)
            { 
            /*greatest() is used so the value is taken from the hit_count column in the event that new_hit_count is zero
            to support installations that did not previously have a new_hit_count column (i.e. upgrade compatability).*/
            $resource_ref_escaped = escape_check($result[$n]['ref']);
            sql_query("UPDATE resource SET new_hit_count = greatest(hit_count, new_hit_count) + 1 WHERE ref = '{$resource_ref_escaped}'");
            }
        }
    }

/**
 * Append summary notes about the completeness of the package, write the text file, add to archive, and schedule for deletion.
 *
 * @return void
 */
function collection_download_process_summary_notes(
    array $result,
    array $available_sizes,
    &$text,
    array $subbed_original_resources,
    array $used_resources,
    $id,
    $collection,
    $collectiondata,
    $collection_download_tar,
    $usertempdir,
    $filename,
    &$path,
    array &$deletion_array,
    $size,
    &$zip)
    {
    global $lang, $zipped_collection_textfile, $includetext, $sizetext, $use_zip_extension, $p;
    
    if($zipped_collection_textfile == true && $includetext == "true")
        {
        $qty_sizes = isset($available_sizes[$size]) ? count($available_sizes[$size]) : 0;
        $qty_total = count($result);
        $text.= $lang["status-note"] . ": " . $qty_sizes . " " . $lang["of"] . " " . $qty_total . " ";
        switch ($qty_total) {
        case 0:
            $text.= $lang["resource-0"] . " ";
            break;
        case 1:
            $text.= $lang["resource-1"] . " ";
            break;
        default:
            $text.= $lang["resource-2"] . " ";
            break;
        }

        switch ($qty_sizes) {
        case 0:
            $text.= $lang["were_available-0"] . " ";
            break;
        case 1:
            $text.= $lang["were_available-1"] . " ";
            break;
        default:
            $text.= $lang["were_available-2"] . " ";
            break;
        }
        $text.= $lang["forthispackage"] . ".\r\n\r\n";

        foreach ($result as $resource) {
        if (in_array($resource['ref'],$subbed_original_resources)){
        $text.= $lang["didnotinclude"] . ": " . $resource['ref'];
        $text.= " (".$lang["substituted_original"] . ")";
        $text.= "\r\n";
        } elseif (!in_array($resource['ref'],$used_resources)) {
                $text.= $lang["didnotinclude"] . ": " . $resource['ref'];
        $text.= "\r\n";
            }
        }

        $textfile = get_temp_dir(false,$id) . "/". $collection . "-" . safe_file_name(i18n_get_collection_name($collectiondata)) . $sizetext . ".txt";
        $fh = fopen($textfile, 'w') or die("can't open file");
        fwrite($fh, $text);
        fclose($fh);
        if($collection_download_tar)
            {
            debug("collection_download adding symlink: " . $p . " - " . $usertempdir . DIRECTORY_SEPARATOR . $filename);
            @symlink($textfile, $usertempdir . DIRECTORY_SEPARATOR . $collection . "-" . safe_file_name(i18n_get_collection_name($collectiondata)) . $sizetext . '.txt');
            }
        elseif ($use_zip_extension){
            $zip->addFile($textfile,$collection . "-" . safe_file_name(i18n_get_collection_name($collectiondata)) . $sizetext . ".txt");
        } else {
            $path.=$textfile . "\r\n";  
        }
        $deletion_array[]=$textfile;    
        }

    return;
    }

/**
 * Add a CSV containing resource metadata to a downloaded zip file during creation of the zip.
 *
 * @param  array $result
 * @param  integer $id
 * @param  integer $collection
 * @param  boolean $collection_download_tar
 * @param  boolean $use_zip_extension
 * @param  object $zip
 * @param  string $path
 * @param  array $deletion_array
 * @return void
 */
function collection_download_process_csv_metadata_file(array $result, $id, $collection, $collection_download_tar, $use_zip_extension, &$zip, &$path, array &$deletion_array)
    {
    // Include the CSV file with the metadata of the resources found in this collection
    $csv_file    = get_temp_dir(false, $id) . '/Col-' . $collection . '-metadata-export.csv';
        if(isset($result[0]["ref"]))
        {
        $result = array_column($result,"ref");  
        }
    generateResourcesMetadataCSV($result, false,false,$csv_file);
    
    // Add link to file for use by tar to prevent full paths being included.
    if($collection_download_tar)
        {
        global $p, $usertempdir, $filename;
        debug("collection_download adding symlink: " . $p . " - " . $usertempdir . DIRECTORY_SEPARATOR . $filename);
        @symlink($csv_file, $usertempdir . DIRECTORY_SEPARATOR . 'Col-' . $collection . '-metadata-export.csv');
        }
    elseif($use_zip_extension)
        {
        $zip->addFile($csv_file, 'Col-' . $collection . '-metadata-export.csv');
        }
    else
        {
        $path .= $csv_file . "\r\n";
        }
    $deletion_array[] = $csv_file;
    }

/**
 * Write the batch download command parameters to a file ready for execution.
 *
 * @param  boolean $use_zip_extension
 * @param  boolean $collection_download_tar
 * @param  integer $id
 * @param  integer $collection
 * @param  string $size
 * @param  string $path
 * @return void
 */
function collection_download_process_command_to_file($use_zip_extension, $collection_download_tar, $id, $collection, $size, &$path)
    {
    global $config_windows, $cmdfile;


    //update_progress_file("writing zip command");  
    if (!$use_zip_extension && !$collection_download_tar)
        {
        $cmdfile = get_temp_dir(false,$id) . "/zipcmd" . $collection . "-" . $size . ".txt";
        $fh = fopen($cmdfile, 'w') or die("can't open file");
        # Remove Windows line endings - fixes an issue with using tar command - somehow the file has got Windows line breaks
        if(!$config_windows) 
            {$path=preg_replace('/\r\n/', "\n", $path);}
        fwrite($fh, $path);
        fclose($fh);
        }
    }

/**
 * Modifies the filename for downloading as part of the specified collection
 *
 * @param  string &$filename        Filename (passed by reference)
 * @param  integer $collection      Collection ID
 * @param  string $size             Size code e.g scr,pre
 * @param  string $suffix           String suffix to add (before file extension)
 * @param  array $collectiondata    Collection data obtained by get_collection()
 * @return void
 */
function collection_download_process_collection_download_name(&$filename, $collection, $size, $suffix, array $collectiondata)
    {
    global $lang, $use_collection_name_in_zip_name;

    $filename = hook('changecollectiondownloadname', null, array($collection, $size, $suffix));
    if (empty($filename))
        {
        if ($use_collection_name_in_zip_name)
            {
            # Use collection name (if configured)
            $filename = $lang["collectionidprefix"] . $collection . "-"
                    . safe_file_name(i18n_get_collection_name($collectiondata)) . "-" . $size
                    . $suffix;
            }
        else
            {
            # Do not include the collection name in the filename (default)
            $filename = $lang["collectionidprefix"] . $collection . "-" . $size . $suffix;
            }
        }
    }

/**
 * Executes the archiver command when downloading a collection.
 *
 * @param  boolean $collection_download_tar
 * @param  object $zip
 * @param  string $filename
 * @param  string $usertempdir
 * @param  boolean $archiver
 * @param  integer $settings_id
 * @param  string $zipfile
 * @return void
 */
function collection_download_process_archive_command($collection_download_tar, &$zip, $filename, $usertempdir, $archiver, $settings_id, &$zipfile)
    {
    global $lang, $use_zip_extension, $collection_download_settings, $archiver_listfile_argument, $cmdfile, $config_windows;

    $archiver_fullpath = get_utility_path("archiver");

    # Execute the archiver command.
    # If $collection_download is true the $collection_download_settings are used if defined, else the legacy $zipcommand is used.
    if ($use_zip_extension && !$collection_download_tar)
        {
        update_zip_progress_file("zipping");
        $wait=$zip->close();
        update_zip_progress_file("complete");
        sleep(1);
        }
     else if ($collection_download_tar)
        {
        header("Content-type: application/tar");
        header("Content-disposition: attachment; filename=" . $filename );
        debug("collection_download tar command: tar -cv -C " . $usertempdir . " . ");
        $cmdtempdir = escapeshellarg($usertempdir);
        passthru("find " . $cmdtempdir . ' -printf "%P\n" | tar -cv --no-recursion --dereference -C ' . $cmdtempdir . " -T -");
        exit();
        }
    else if ($archiver)
        {
        update_zip_progress_file("zipping");
        $wait=run_command($archiver_fullpath . " " . $collection_download_settings[$settings_id]["arguments"] . " " . escapeshellarg($zipfile) . " " . $archiver_listfile_argument . escapeshellarg($cmdfile));
        update_zip_progress_file("complete");
        }
    else if (!$use_zip_extension)
        {
        update_zip_progress_file("zipping");    
        if ($config_windows)
            # Add the command file, containing the filenames, as an argument.
            {
            $wait=exec("$zipcommand " . escapeshellarg($zipfile) . " @" . escapeshellarg($cmdfile));
            }
        else
            {
            # Pipe the command file, containing the filenames, to the executable.
            $wait=exec("$zipcommand " . escapeshellarg($zipfile) . " -@ < " . escapeshellarg($cmdfile));
            }
            update_zip_progress_file("complete");
        }
    }

/**
 * Remove temporary files created during download by exiftool for adding metadata.
 *
 * @param  array $deletion_array    An array of file paths
 * @return void
 */
function collection_download_clean_temp_files(array $deletion_array)
    {
    global $use_zip_extension, $cmdfile;

    # Archive created, schedule the command file for deletion.
    if (!$use_zip_extension)
        {
        $deletion_array[]=$cmdfile;
        }
    
    # Remove temporary files.
    foreach($deletion_array as $tmpfile)
        {
        delete_exif_tmpfile($tmpfile);
        }
    }

/**
 * Delete any resources from collection moved out of users archive status permissions by other users
 *
 * @param  integer  $collection   ID of collection
 * 
 * @return void
 */
function collection_cleanup_inaccessible_resources($collection)
    {
    global $userref;

    $editable_states = array_column(get_editable_states($userref), 'id');
    sql_query("DELETE a 
                FROM   collection_resource AS a 
                INNER JOIN resource AS b 
                ON a.resource = b.ref 
                WHERE  a.collection = '" . $collection . "' 
                AND b.archive NOT IN ( '" . implode("', '", $editable_states) . "' );");
    }
/**
* Relate all resources in a collection
* 
* @param integer $collection ID of collection
*
* @return boolean
*/
function relate_all_collection($collection, $checkperms = true)
    {
    if((string)(int)$collection != (string)$collection || ($checkperms && !allow_multi_edit($collection)))
        {
        return false;
        }

    $rlist = get_collection_resources($collection);
    for ($n=0;$n<count($rlist);$n++)
        {
        for ($m=0;$m<count($rlist);$m++)
            {
            if ($rlist[$n]!=$rlist[$m]) # Don't relate a resource to itself
                { 
                if (count(sql_query("SELECT 1 FROM resource_related WHERE resource='".$rlist[$n]."' and related='".$rlist[$m]."' LIMIT 1"))!=1) 
                    {
                    sql_query("insert into resource_related (resource,related) values ('" . $rlist[$n] . "','" . $rlist[$m] . "')");
                    }
                }
            }
        }
    return true;
    }


/**
* Update collection type for one collection or batch
* 
* @param  integer|array  $cid   Collection ID -or- list of collection IDs
* @param  integer        $type  Collection type. @see include/definitions.php for available options
* 
* @return boolean
*/
function update_collection_type($cid, $type)
    {
    debug_function_call("update_collection_type", func_get_args());

    if(!is_array($cid))
        {
        $cid = array($cid);
        }

    $cid = array_filter($cid, "is_numeric");

    if(empty($cid))
        {
        return false;
        }

    if(!in_array($type, definitions_get_by_prefix("COLLECTION_TYPE")))
        {
        return false;
        }

    foreach($cid as $ref)
        {
        collection_log($ref, LOG_CODE_EDITED, "", "Update collection type to '{$type}'");
        }

    $cid_list = "'" . implode("', '", $cid) . "'";

    sql_query("UPDATE collection SET `type` = '{$type}' WHERE ref IN ({$cid_list})");

    return true;
    }


/**
* Update collection parent for this collection
* 
* @param integer @cid    The collection ID
* @param integer @parent The featured collection ID that is the parent of this collection
* 
* @return boolean
*/
function update_collection_parent(int $cid, int $parent)
    {
    if($cid <= 0 || $parent <= 0)
        {
        return false;
        }

    collection_log($cid, LOG_CODE_EDITED, "", "Update collection parent to '{$parent}'");
    sql_query("UPDATE collection SET `parent` = '{$parent}' WHERE ref = '{$cid}'");

    return true;
    }


/**
* Get a users' collection of type SELECTION.
* 
* There can only be one collection of this type per user. If more, the first one found will be used instead.
* 
* @param integer  $user  User ID
* 
* @return null|integer  Returns NULL if none found or the collection ID
*/
function get_user_selection_collection($user)
    {
    global $rs_session;
    if(!is_numeric($user))
        {
        return null;
        }
    $sql = sprintf("SELECT ref AS `value` FROM collection WHERE `user` = '%s' AND `type` = '%s' %s ORDER BY ref ASC",
        escape_check($user),
        COLLECTION_TYPE_SELECTION,
        ((isset($rs_session) && $rs_session != "") ? " AND session_id='" . $rs_session . "'"  : "")
    );

    return sql_value($sql, null);
    }


/**
* Delete all collections that are not in use e.g. session collections for the anonymous user. Will not affect collections that are public.
* 
* @param integer $userref - ID of user to delete collections for 
* @param integer $days - minimum age of collections to delete in days
* 
* @return integer - number of collections deleted
*/
function delete_old_collections($userref=0, $days=30)
    {
    if($userref==0 || !is_numeric($userref))
        {
        return 0;
        }

    $userref = escape_check($userref);
    $days = escape_check($days);

    $deletioncount = 0;
    $old_collections=sql_array("SELECT ref value FROM collection WHERE user ='{$userref}' AND created < DATE_SUB(NOW(), INTERVAL '{$days}' DAY) AND `type` = " . COLLECTION_TYPE_STANDARD, 0);
    foreach($old_collections as $old_collection)
        {
        sql_query("DELETE FROM collection_resource WHERE collection='" . $old_collection . "'");
        sql_query("DELETE FROM collection WHERE ref='" . $old_collection . "'");
        $deletioncount++;
        }
    return $deletioncount;
    }

/**
* Get all featured collections
* 
* @return array
*/
function get_all_featured_collections()
    {
    return sql_query(
        sprintf(
              "SELECT DISTINCT c.ref,
                      c.`name`,
                      c.`type`,
                      c.parent,
                      c.thumbnail_selection_method,
                      c.bg_img_resource_ref,
                      c.created,
                      count(DISTINCT cr.resource) > 0 AS has_resources,
                      count(DISTINCT cc.ref) > 0 AS has_children
                 FROM collection AS c
            LEFT JOIN collection_resource AS cr ON c.ref = cr.collection
            LEFT JOIN collection AS cc ON c.ref = cc.parent
                WHERE c.`type` = %s
             GROUP BY c.ref",
            COLLECTION_TYPE_FEATURED
        ),
        "featured_collections");
    }


/**
* Get all featured collections by parent node
* 
* @param integer $parent  The ref of the parent collection. When a featured collection contains another collection, it is
*                         then considered a featured collection category and won't have any resources associated with it.
* @param array   $ctx     Contextual data (e.g disable access control). This param MUST NOT get exposed over the API
* 
* @return array List of featured collections (with data) 
*/
function get_featured_collections(int $parent, array $ctx)
    {
    if($parent < 0)
        {
        return array();
        }

    $access_control = (isset($ctx["access_control"]) && is_bool($ctx["access_control"]) ? $ctx["access_control"] : true);

    $allfcs = sql_query(
        sprintf(
              "SELECT DISTINCT c.ref,
                      c.`name`,
                      c.`type`,
                      c.parent,
                      c.thumbnail_selection_method,
                      c.bg_img_resource_ref,
                      c.created,
                      count(DISTINCT cr.resource) > 0 AS has_resources,
                      count(DISTINCT cc.ref) > 0 AS has_children
                 FROM collection AS c
            LEFT JOIN collection_resource AS cr ON c.ref = cr.collection
            LEFT JOIN collection AS cc ON c.ref = cc.parent
                WHERE c.`type` = %s
                  AND c.parent %s
             GROUP BY c.ref",
            COLLECTION_TYPE_FEATURED,
            sql_is_null_or_eq_val((string) $parent, $parent == 0)
            )
        );

    if(!$access_control)
        {
        return $allfcs;
        }

    $validcollections = array();
    foreach($allfcs as $fc)
        {
        if(featured_collection_check_access_control($fc["ref"]))
            {
            $validcollections[]=$fc;
            }
        }
    return $validcollections;
    }


/**
* Build appropriate SQL (for WHERE clause) to filter out featured collections for the user. The function will use either an
* IN or NOT IN depending which list is smaller to increase performance of the search
* 
* @param string $prefix SQL WHERE clause element. Mostly should be either WHERE, AND -or- OR depending on the SQL statement 
*                       this is part of.
* @param string $column SQL column on which to apply the filter for
* 
* @return string Returns "" if user should see all featured collections or a SQL filter (e.g AND ref IN("32", "34") )
*/
function featured_collections_permissions_filter_sql(string $prefix, string $column)
    {
    global $CACHE_FC_PERMS_FILTER_SQL;
    $CACHE_FC_PERMS_FILTER_SQL = (!is_null($CACHE_FC_PERMS_FILTER_SQL) && is_array($CACHE_FC_PERMS_FILTER_SQL) ? $CACHE_FC_PERMS_FILTER_SQL : array());
    $cache_id = md5("{$prefix}-{$column}");
    if(isset($CACHE_FC_PERMS_FILTER_SQL[$cache_id]) && is_string($CACHE_FC_PERMS_FILTER_SQL[$cache_id]))
        {
        return $CACHE_FC_PERMS_FILTER_SQL[$cache_id];
        }
    // $prefix & $column are used to generate the right SQL (e.g AND ref IN(list of IDs)). If developer/code, passes empty strings,
    // that's not this functions' responsibility. We could error here but the code will error anyway because of the bad SQL so
    // we might as well fix the problem at its root (ie. where we call this function with bad input arguments).
    $prefix = " " . trim($prefix);
    $column = trim($column);

    $computed_fcs = compute_featured_collections_access_control();
    if($computed_fcs === true)
        {
        $return = ""; # No access control needed! User should see all featured collections
        }
    else if(is_array($computed_fcs))
        {
        $fcs_list = "'" . join("', '", array_map('escape_check', $computed_fcs)) . "'";
        $return = "{$prefix} {$column} IN ({$fcs_list})";
        }
    else
        {
        // User is not allowed to see any of the available FCs
        $return = "{$prefix} 1 = 0";
        }

    $CACHE_FC_PERMS_FILTER_SQL[$cache_id] = $return;
    return $return;
    }


/**
* Access control function used to determine if a featured collection should be accessed by the user
* 
* @param integer $c_ref Collection ref to be tested
* 
* @return boolean Returns TRUE if user should have access to the featured collection (no parent category prevents this), FALSE otherwise
*/
function featured_collection_check_access_control(int $c_ref)
    {
    if(checkperm("-j" . $c_ref))
        {
        return false;
        }
    elseif(checkperm("j*") || checkperm("j" . $c_ref))
        {
        return true;
        }
    else
        {
        // Get all parents
        $allparents = sql_query("
                SELECT  C2.ref, C2.parent
                  FROM  (SELECT @r AS p_ref,
                        (SELECT @r := parent FROM collection WHERE ref = p_ref) AS parent,
                        @l := @l + 1 AS lvl
                  FROM  (SELECT @r := '" . $c_ref . "', @l := 0) vars,
                        collection c
                 WHERE  @r <> 0) C1
                  JOIN  collection C2
                    ON  C1.p_ref = C2.ref
              ORDER BY  C1.lvl DESC",
                "featured_collections");

          foreach($allparents as $parent)
                {
                if(checkperm("-j" . $parent["ref"]))
                    {
                    // Denied access to parent
                    return false;
                    }
                elseif(checkperm("j" . $parent["ref"]))
                    {
                    return true;
                    }
                }
        return false; // No explicit permission given and user doesn't have f*
        }
    }


/**
* Helper comparison function for ordering featured collections by the "has_resource" property,  then by name, this takes into account the legacy
* use of '*' as a prefix to move to the start.
* 
* @param array $a First featured collection data structure to compare
* @param array $b Second featured collection data structure to compare
* 
* @return Return an integer less than, equal to, or greater than zero if the first argument is considered to be 
*         respectively less than, equal to, or greater than the second.
*/
function order_featured_collections(array $a, array $b)
    {
    if($a["has_resources"] == $b["has_resources"])
        {
        return strnatcasecmp($a["name"],$b["name"]);
        }

    return ($a["has_resources"] < $b["has_resources"] ? -1 : 1);
    }

/**
* Get featured collection categories
* 
* @param integer $parent  The ref of the parent collection.
* @param array   $ctx     Extra context for get_featured_collections(). Mostly used for overriding access control (e.g 
*                         on the admin_group_permissions.php where we want to see all available featured collection categories).
* 
* @return array
*/
function get_featured_collection_categories(int $parent, array $ctx)
    {
    return array_values(array_filter(get_featured_collections($parent, $ctx), "is_featured_collection_category"));
    }

/**
* Check if a collection is a featured collection category
* 
* @param array $fc A featured collection data structure as returned by {@see get_featured_collections()}
* 
* @return boolean
*/
function is_featured_collection_category(array $fc)
    {
    if(!isset($fc["type"]) || !isset($fc["has_resources"]))
        {
        return false;
        }

    return ($fc["type"] == COLLECTION_TYPE_FEATURED && $fc["has_resources"] == 0);
    }

/**
* Check if a collection is a featured collection category by checking if the collection has been used as a parent. This 
* function will make a DB query to find this out, it does not use existing structures.
* 
* Normally a featured collection is a category if it has no resources. In some circumstances, when it's impossible to 
* determine whether it should be or not, relying on children is another approach.
* 
* @param integer $c_ref Collection ID
* 
* @return boolean
*/
function is_featured_collection_category_by_children(int $c_ref)
    {
    $sql = sprintf(
          "SELECT DISTINCT c.ref AS `value`
             FROM collection AS c
        LEFT JOIN collection AS cc ON c.ref = cc.parent
            WHERE c.`type` = %s
              AND c.ref = '%s'
         GROUP BY c.ref
           HAVING count(DISTINCT cc.ref) > 0",
        COLLECTION_TYPE_FEATURED,
        escape_check($c_ref)
    );
    $found_ref = sql_value($sql, 0);

    return ($found_ref > 0);
    }

/**
* Validate a collection parent value
* 
* @param int|array $c  Collection ref -or- collection data as returned by {@see get_collection()}
* 
* @return null|integer 
*/
function validate_collection_parent($c)
    {
    if(!is_array($c) && !is_int($c))
        {
        return null;
        }
    
    $collection = $c;
    if(!is_array($c) && is_int($c))
        {
        $collection = get_collection($c);
        if($collection === false)
            {
            return null;
            }
        }

    return (trim($collection["parent"]) == "" ? null : (int) $collection["parent"]);
    }

/**
* Get to the root of the branch starting from the leaf featured collection
* 
* @param  integer  $ref  Collection ref which is considered a leaf of the tree
* @param  array    $fcs  List of all featured collections
* 
* @return array Branch path structure starting from root to the leaf
*/
function get_featured_collection_category_branch_by_leaf(int $ref, array $fcs)
    {
    if(empty($fcs))
        {
        $fcs = get_all_featured_collections();
        }

    return compute_node_branch_path($fcs, $ref);
    }

/**
* Process POSTed featured collections categories data for a collection
* 
* @param integer $depth       The depth from which to start from. Usually zero.
* @param array   $branch_path A full branch path of the collection. {@see get_featured_collection_category_branch_by_leaf()}
* 
* @return array Returns changes done regarding the collection featured collection category structure. This information
*               then can be provided to {@see save_collection()} as: $coldata["featured_collections_changes"]
*/
function process_posted_featured_collection_categories(int $depth, array $branch_path)
    {
    global $enable_themes, $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS;

    if(!($enable_themes && checkperm("h")))
        {
        return array();
        }

    if($depth < 0)
        {
        return array();
        }

    debug("process_posted_featured_collection_categories: Processing at \$depth = {$depth}");

    // For public collections, the branch path doesn't exist (why would it?) in which case only root categories are valid
    $current_lvl_parent = (!empty($branch_path) ? (int) $branch_path[$depth]["parent"] : 0);
    debug("process_posted_featured_collection_categories: \$current_lvl_parent: " . gettype($current_lvl_parent) . " = " . json_encode($current_lvl_parent));

    $selected_fc_category = getval("selected_featured_collection_category_{$depth}", null, true);
    debug("process_posted_featured_collection_categories: \$selected_fc_category: " . gettype($selected_fc_category) . " = " . json_encode($selected_fc_category));

    $force_featured_collection_type = (getval("force_featured_collection_type", "") == "true");
    debug("process_posted_featured_collection_categories: \$force_featured_collection_type: " . gettype($force_featured_collection_type) . " = " . json_encode($force_featured_collection_type));

    // Validate the POSTed featured collection category for this depth level
    $valid_categories = array_merge(array(0), array_column(get_featured_collection_categories($current_lvl_parent, array()), "ref"));
    if(
        !is_null($selected_fc_category)
        && isset($branch_path[$depth])
        && !in_array($selected_fc_category, $valid_categories))
        {
        return array();
        }

    $fc_category_at_level = (empty($branch_path) ? null : $branch_path[$depth]["ref"]);
    debug("process_posted_featured_collection_categories: \$fc_category_at_level: " . gettype($fc_category_at_level) . " = " . json_encode($fc_category_at_level));

    if($selected_fc_category != $fc_category_at_level || $force_featured_collection_type)
        {
        $new_parent = ($selected_fc_category == 0 ? $current_lvl_parent : $selected_fc_category);
        debug("process_posted_featured_collection_categories: \$new_parent: " . gettype($new_parent) . " = " . json_encode($new_parent));

        $fc_update = array("update_parent" => $new_parent);

        if($force_featured_collection_type)
            {
            $fc_update["force_featured_collection_type"] = true;
            }

        // When moving a public collection to featured, default to most popular image
        if($depth == 0 && is_null($fc_category_at_level) && (int) $new_parent > 0)
            {
            $fc_update["thumbnail_selection_method"] = $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"];
            }

        return $fc_update;
        }

    if(is_null($selected_fc_category))
        {
        return array();
        }

    return process_posted_featured_collection_categories(++$depth, $branch_path);
    }


/**
* Find existing featured collection ref using its name and parent
* 
* @param string $name Featured collection name to search by
* @param null|integer $parent The featured collection parent
* 
* @return null|integer
*/
function get_featured_collection_ref_by_name(string $name, $parent)
    {
    if(!is_null($parent) && !is_int($parent))
        {
        return null;
        }

    $ref = sql_value(
        sprintf("SELECT ref AS `value` FROM collection WHERE `name` = '%s' AND `type` = '%s' AND parent %s",
            escape_check(trim($name)),
            COLLECTION_TYPE_FEATURED,
            sql_is_null_or_eq_val((string) $parent, is_null($parent))
        ), 
        null,
        "featured_collections"
    );

    return (is_null($ref) ? null : (int) $ref);
    }


/**
* Check if user is allowed to share collection
* 
* @param array $c Collection data 
* 
* @return boolean Return TRUE if user is allowed to share the collection, FALSE otherwise
*/
function allow_collection_share(array $c)
    {
    global $allow_share, $manage_collections_share_link, $k, $internal_share_access,
    $restricted_share, $system_read_only, $system_read_only;

    if(!isset($GLOBALS["count_result"]))
        {
        $collection_resources = get_collection_resources($c["ref"]);
        $collection_resources = (is_array($collection_resources) ? count($collection_resources) : 0);
        }
    else
        {
        $collection_resources = $GLOBALS["count_result"];
        }
    $internal_share_access = (!is_null($internal_share_access) && is_bool($internal_share_access) ? $internal_share_access : internal_share_access());

    if(
        $allow_share
        && !$system_read_only
        && $manage_collections_share_link
        && $collection_resources > 0
        && ($k == "" || $internal_share_access)
        && !checkperm("b")
        && (checkperm("v")
            || checkperm ("g") 
            || collection_min_access($c["ref"]) <= RESOURCE_ACCESS_RESTRICTED
            || $restricted_share)
    )
        {
        return true;
        }

    return false;
    }


/**
* Check if user is allowed to share featured collection. If the featured collection provided is a category, then this
* function will return FALSE if at least one sub featured collection has no share access (this is kept consistent with 
* the check for normal collections when checking resources).
* 
* @param array $c Collection data. You can add "has_resources" and "sub_fcs" keys if you already have this information
* 
* @return boolean Return TRUE if user is allowed to share the featured collection, FALSE otherwise
*/
function allow_featured_collection_share(array $c)
    {
    if($c["type"] != COLLECTION_TYPE_FEATURED)
        {
        return allow_collection_share($c);
        }

    if(!featured_collection_check_access_control($c["ref"]))
        {
        return false;
        }

    if(!isset($c["has_resources"]))
        {
        $collection_resources = get_collection_resources($c["ref"]);
        $c["has_resources"] = (is_array($collection_resources) && !empty($collection_resources) ? 1 : 0);
        }

    // Not a category, can be treated as a simple collection
    if(!is_featured_collection_category($c))
        {
        return allow_collection_share($c);
        }

    $sub_fcs = (!isset($c["sub_fcs"]) ? get_featured_collection_categ_sub_fcs($c) : $c["sub_fcs"]);
    return array_reduce($sub_fcs, function($carry, $item)
        {
        // Fake a collection data structure. allow_collection_share() only needs the ref
        $c = array("ref" => $item);
        $fc_allow_share = allow_collection_share($c);

        // FALSE if at least one collection has no share access (consistent with the check for normal collections when checking resources)
        return (!is_bool($carry) ? $fc_allow_share : $carry && $fc_allow_share);
        }, null);
    }


/**
* Filter out featured collections that have a different root path. The function builds internally the path to the root from
* the provided featured collection ref and then filters out any featured collections that have a different root path.
* 
* @param array $fcs   List of featured collections refs to filter out
* @param int   $c_ref A root featured collection ref
* @param array $ctx   Contextual data
* 
* @return array
*/
function filter_featured_collections_by_root(array $fcs, int $c_ref, array $ctx = array())
    {
    if(empty($fcs))
        {
        return array();
        }

    global $CACHE_FCS_BY_ROOT;
    $CACHE_FCS_BY_ROOT = (!is_null($CACHE_FCS_BY_ROOT) && is_array($CACHE_FCS_BY_ROOT) ? $CACHE_FCS_BY_ROOT : array());
    $cache_id = $c_ref . md5(json_encode($fcs));
    if(isset($CACHE_FCS_BY_ROOT[$cache_id][$c_ref]))
        {
        return $CACHE_FCS_BY_ROOT[$cache_id][$c_ref];
        }

    $all_fcs = (isset($ctx["all_fcs"]) && is_array($ctx["all_fcs"]) ? $ctx["all_fcs"] : array());
    $branch_path_fct = function($carry, $item) { return "{$carry}/{$item["ref"]}"; };

    $category_branch_path = get_featured_collection_category_branch_by_leaf($c_ref, $all_fcs);
    $category_branch_path_str = array_reduce($category_branch_path, $branch_path_fct, "");

    $collections = array_filter($fcs, function(int $ref) use ($branch_path_fct, $category_branch_path_str, $all_fcs)
        {
        $branch_path = get_featured_collection_category_branch_by_leaf($ref, $all_fcs);
        $branch_path_str = array_reduce($branch_path, $branch_path_fct, "");
        return (substr($branch_path_str, 0, strlen($category_branch_path_str)) == $category_branch_path_str);
        });

    $CACHE_FCS_BY_ROOT[$cache_id][$c_ref] = $collections;

    return array_values($collections);
    }


/**
* Get all featured collections branches where the specified resources can be found.
* 
* @param array $r_refs List of resource IDs
* 
* @return array Returns list of featured collections (categories included) that contain the specified resource(s).
*/
function get_featured_collections_by_resources(array $r_refs)
    {
    $resources = array_filter($r_refs, "is_numeric");
    if(empty($resources))
        {
        return array();
        }

    $sql = sprintf(
        "SELECT c.ref, c.`name`, c.`parent`
           FROM collection_resource AS cr
           JOIN collection AS c ON cr.collection = c.ref AND c.`type` = %s
          WHERE cr.resource IN (%s)
            %s # access control filter (ok if empty - it means we don't want permission checks or there's nothing to filter out)",
        COLLECTION_TYPE_FEATURED,
        "'" . join("', '", $resources) . "'",
        trim(featured_collections_permissions_filter_sql("AND", "c.ref"))
    );
    $fcs = sql_query($sql);

    $results = array();
    foreach($fcs as $fc)
        {
        $results[] = get_featured_collection_category_branch_by_leaf($fc["ref"], array());
        }

    return $results;
    }


/**
* Verify if a featured collection can be deleted. To be deleted, it MUST not have any resources or children (if category).
* 
* @param integer $ref Collection ID
* 
* @return boolean Returns TRUE if the featured collection can be deleted, FALSE otherwise
*/
function can_delete_featured_collection(int $ref)
    {
    $sql = sprintf(
          "SELECT DISTINCT c.ref AS `value`
             FROM collection AS c
        LEFT JOIN collection AS cc ON c.ref = cc.parent
        LEFT JOIN collection_resource AS cr ON c.ref = cr.collection
            WHERE c.`type` = %s
              AND c.ref = '%s'
         GROUP BY c.ref
           HAVING count(DISTINCT cr.resource) = 0
              AND count(DISTINCT cc.ref) = 0",
        COLLECTION_TYPE_FEATURED,
        escape_check($ref)
    );

    return (sql_value($sql, 0) > 0);
    }


/**
 * Remove all instances of the specified character from start of string
 *
 * @param  string $string   String to update
 * @param  string $char     Character to remove
 * @return string
 */
function strip_prefix_chars($string,$char)
    {
    while(strpos($string,$char)===0)
        {
        $regmatch = preg_quote($char);
        $string = preg_replace("/" . $regmatch . '/','',$string,1);
        }
    return $string;
    }


/**
* Check access control if user is allowed to upload to a collection.
* 
* @param array $c Collection data structure
* 
* @return boolean
*/
function allow_upload_to_collection(array $c)
    {
    if(empty($c))
        {
        return false;
        }

    if(
        $c["type"] == COLLECTION_TYPE_SELECTION
        // Featured Collection Categories can't contain resources, only other featured collections (categories or normal)
        || ($c["type"] == COLLECTION_TYPE_FEATURED && is_featured_collection_category_by_children($c["ref"]))
    )
        {
        return false;
        }

    global $userref, $k, $internal_share_access;

    $internal_share_access = (!is_null($internal_share_access) && is_bool($internal_share_access) ? $internal_share_access : internal_share_access());

    if(
        ($k == "" || $internal_share_access)
        && ($c["savedsearch"] == "" || $c["savedsearch"] == 0)
        && ($userref == $c["user"] || $c["allow_changes"] == 1 || checkperm("h") || checkperm("a"))
        && (checkperm("c") || checkperm("d"))
    )
        {
        return true;
        }

    return false;
    }


/**
* Compute the featured collections allowed based on current access control
* 
* @return boolean|array Returns FALSE if user should not see any featured collections (usually means misconfiguration) -or-
*                       TRUE if user has access to all featured collections. If some access control is in place, then the
*                       return will be an array with all the allowed featured collections
*/
function compute_featured_collections_access_control()
    {
    global $CACHE_FC_ACCESS_CONTROL, $userpermissions;
    if(!is_null($CACHE_FC_ACCESS_CONTROL))
        {
        return $CACHE_FC_ACCESS_CONTROL;
        }

    $all_fcs = sql_query(sprintf("SELECT ref, parent FROM collection WHERE `type` = %s", COLLECTION_TYPE_FEATURED), "featured_collections");
    $all_fcs_rp = reshape_array_by_value_keys($all_fcs, 'ref', 'parent');
    // Set up arrays to store permitted/blocked featured collections
    $includerefs = array();
    $excluderefs = array();
    if(checkperm("j*"))
        {
        // Check for -jX permissions.
        foreach($userpermissions as $userpermission)
            {
            if(substr($userpermission,0,2) == "-j")
                {
                $fcid = substr($userpermission,2);
                if(is_int_loose($fcid))
                    {
                    // Collection access has been explicitly denied
                    $excluderefs[] = $fcid;
                    }                
                }
            }
        if(count($excluderefs) == 0)
            {
            return true;
            }
        }
    else
        {
        // No access to all, check for j{field} permissions that open up access
        foreach($userpermissions as $userpermission)
            {
            if(substr($userpermission,0,1) == "j")
                {
                $fcid = substr($userpermission,1);
                if(is_int_loose($fcid))
                    {
                    $includerefs[] = $fcid;
                    // Add children of this collection unless a -j permission has been added below it
                    $children = array_keys($all_fcs_rp,$fcid);
                    $queue = new SplQueue();
                    $queue->setIteratorMode(SplQueue::IT_MODE_DELETE);
                    foreach($children as $child_fc)
                        {
                        $queue->enqueue($child_fc);
                        }
                
                    while(!$queue->isEmpty())
                        {
                        $checkfc = $queue->dequeue();
                        if(!checkperm("-j" . $checkfc))
                            {
                            $includerefs[] = $checkfc;
                            // Also add children of this collection to queue to check
                            $fcs_sub = array_keys($all_fcs_rp,$checkfc);
                            foreach($fcs_sub as $fc_sub)
                                {
                                $queue->enqueue($fc_sub);
                                }
                            }
                        }
                    }
                }
            }
        
        if(count($includerefs) == 0)
            {
            // Misconfiguration - user can only see specific FCs but none have been selected
            return false;
            }
        }

    $return = array();
    foreach($all_fcs_rp as $fc => $fcp)
        {
        if(in_array($fc, $includerefs) && !in_array($fc,$excluderefs))
            {
            $return[] = $fc;
            }
        }
        
    $CACHE_FC_ACCESS_CONTROL = $return;
    return $return;
    }

/**
 * Remove all old anonymous collections
 *
 * @param  int $limit   Maximum number of collections to delete - if run from browser this is kept low to avoid delays
 * @return void
 */
function cleanup_anonymous_collections(int $limit = 100)
    {
    global $anonymous_login;

    $sql_limit = $limit == 0 ? "" : "LIMIT " . $limit;

    if(!is_array($anonymous_login))
        {
        $anonymous_login = array($anonymous_login);
        }
    foreach ($anonymous_login as $anonymous_user)
        {
        $user = get_user_by_username($anonymous_user);
        if(is_int_loose($user))
            {
            sql_query("DELETE FROM collection WHERE user ='" . $user . "' AND created < (curdate() - interval '2' DAY) ORDER BY created ASC " . $sql_limit);
            }
        }
    }

/**
 * Check if user is permitted to create an external upload link for the given collection
 *
 * @param  array $collection_data   Array of collection data
 * @return boolean
 */
function can_share_upload_link($collection_data)
    {
    global $usergroup,$upload_link_usergroups;
    if(!is_array($collection_data) && is_numeric($collection_data))
        {
        $collection_data = get_collection($collection_data);
        }
    return allow_upload_to_collection($collection_data) && (checkperm('a') || checkperm("exup") || in_array($usergroup,$upload_link_usergroups));
    }
    
/**
 * Check if user can edit an existing upload share
 *
 * @param  int $collection          Collection ID of share
 * @param  string $uploadkey        External upload key
 * 
 * @return bool
 */
function can_edit_upload_share($collection,$uploadkey)
    {
    global $userref;
    if(checkperm('a'))
        {
        return true;
        }
    $share_details = get_external_shares(array("share_collection"=>$collection,"share_type"=>1, "access_key"=>$uploadkey));
    $details = isset($share_details[0]) ? $share_details[0] : array();
    return ((isset($details["user"]) && $details["user"] == $userref)
        || 
      (checkperm("ex") && isset($details["expires"]) && empty($details["expires"]))
    );
    }

/**
 * Creates an upload link for a collection that can be shared
 *
 * @param  int      $collection  Collection ID
 * @param  array    $shareoptions - values to set
 *                      'usergroup'     Usergroup id to share as (must be in $upload_link_usergroups array)
 *                      'expires'       Expiration date in 'YYYY-MM-DD' format
 *                      'password'      Optional password for share access
 *                      'emails'        Optional array of email addresses to generate keys for
 * 
 * @return string   Share access key
 */
function create_upload_link($collection,$shareoptions)
    {
    global $upload_link_usergroups, $lang, $scramble_key, $usergroup, $userref;
    global $baseurl, $applicationname;
    
    $stdshareopts = array("user","usergroup","expires");

    if(!in_array($shareoptions["usergroup"],$upload_link_usergroups) && !($shareoptions["usergroup"] == $usergroup))
        {
        return $lang["error_invalid_usergroup"];
        }

    if(strtotime($shareoptions["expires"]) < time())
        {
        return $lang["error_invalid_date"];
        }
    // Generate as many new keys as required
    $newkeys = array();
    $numkeys = isset($shareoptions["emails"]) ? count($shareoptions["emails"]) : 1;
    for ($n=0;$n<$numkeys;$n++)
        {
        $newkeys[$n] = generate_share_key($collection);
        }
    
    // Create array to store sql insert data
    $setcolumns = array(
        "collection"    => $collection,
        "user"          => $userref,
        "upload"        => '1',
        "date"          => date("Y-m-d H:i",time()),
        );
    foreach($stdshareopts as $option)
        {
        if(isset($shareoptions[$option]))
            {
            $setcolumns[$option] = escape_check($shareoptions[$option]);
            }
        }
    
    $newshares = array(); // Create array of new share details to return
    for($n=0;$n<$numkeys;$n++)
        {       
        $setcolumns["access_key"] = $newkeys[$n];
        if(isset($shareoptions["password"]) && $shareoptions["password"] != "")
            {
            // Only set if it has actually been set to a string
            $setcolumns["password_hash"] = hash('sha256', $newkeys[$n] . $shareoptions["password"] . $scramble_key);
            }

        if(isset($shareoptions["emails"][$n]))
            {
            if(!filter_var($shareoptions["emails"][$n], FILTER_VALIDATE_EMAIL))
                {
                $newshares[$n] = "";
                continue;
                }
            $setcolumns["email"] = $shareoptions["emails"][$n];
            }
        $insert_columns = array_keys($setcolumns);
        $insert_values  = array_values($setcolumns);


        $sql = "INSERT INTO external_access_keys
                (" . implode(",",$insert_columns) . ")
                VALUES  ('" . implode("','",$insert_values). "')";
        sql_query($sql);

        $newshares[$n] = $newkeys[$n];

        if(isset($shareoptions["emails"][$n]))
            {
            // Send email
            $url=$baseurl . "/?c=" . $collection . "&k=" . $newkeys[$n];		
            $coldata = get_collection($collection, true);
            $userdetails=get_user($userref); 
			$collection_name = i18n_get_collection_name($coldata);
            $link="<a href='" . $url . "'>" . $collection_name . "</a>";
            $passwordtext = (isset($shareoptions["password"]) && $shareoptions["password"] != "") ? $lang["upload_share_email_password"] . " : '" . $shareoptions["password"] . "'" : "";
            $templatevars = array();	
            $templatevars['link']           = $link;  
            $templatevars['message']        = trim($shareoptions["message"]) != "" ? $shareoptions["message"] : "";        
            $templatevars['from_name']      = $userdetails["fullname"]=="" ? $userdetails["username"] : $userdetails["fullname"];
            $templatevars['applicationname']= $applicationname;
            $templatevars['passwordtext']   = $passwordtext;
            $expires = isset($shareoptions["expires"]) ? $shareoptions["expires"] : "";
            if($expires=="")
                {
                $templatevars['expires_date']=$lang["email_link_expires_never"];
                $templatevars['expires_days']=$lang["email_link_expires_never"];
                }
            else
                {
                $day_count=round((strtotime($expires)-strtotime('now'))/(60*60*24));
                $templatevars['expires_date']=$lang['email_link_expires_date'].nicedate($expires);
                $templatevars['expires_days']=$lang['email_link_expires_days'].$day_count;
                if($day_count>1)
                    {
                    $templatevars['expires_days'].=" ".$lang['expire_days'].".";
                    }
                else
                    {
                    $templatevars['expires_days'].=" ".$lang['expire_day'].".";
                    }
                }
            $subject = $lang["upload_share_email_subject"] . $applicationname;

            $body = $templatevars['from_name'] . " " . $lang["upload_share_email_text"] . $applicationname;
            $body .= "<br/><br/>\n" . ($templatevars['message'] != "" ? $templatevars['message'] : "");
            $body .= "<br/><br/>\n" . $templatevars['link'];
            if($passwordtext != "")
                {
                $body .= "<br/><br/>\n" . $passwordtext;
                }
            send_mail($shareoptions["emails"][$n],$subject,$body,$templatevars['from_name'],"","upload_share_email_template",$templatevars);
            }
        $lognotes = array();
        foreach($setcolumns as $column => $value)
            {
            if($column=="password_hash")
                {
                $lognotes[] = trim($value) != "" ? "password=TRUE" : "";
                }
            else
                {
                $lognotes[] = $column . "=" .  $value;
                }
            }
        collection_log($collection,LOG_CODE_COLLECTION_SHARED_UPLOAD,NULL,(isset($shareoptions["emails"][$n]) ? $shareoptions["emails"][$n] : "") . "(" . implode(",",$lognotes) . ")");
        }

    return $newshares;    
    }

/**
 * Generates an external share key based on provided string
 *
 * @param  string   $string
 * @return string   Generated key
 */
function generate_share_key($string)
    {
    return substr(md5($string . "," . time() . rand()), 0, 10);
    }
    
/**
 * Check if an external upload link is being used
 *
 * @return mixed false|int  ID of upload collection, or false if not active
 */
function upload_share_active()
    {
    global $upload_share_active;
    if(isset($upload_share_active))
        {
        return $upload_share_active;
        }
    elseif(isset($_COOKIE["upload_share_active"]) && getval("k","") != "")
        {
        $upload_share_active = (int)$_COOKIE["upload_share_active"];
        return $upload_share_active;
        }
    return false;
    }

/**
 * Set up external upload share  
 *
 * @param  string $key          access key
 * @param  array $shareopts     Array of share options
 *                              "collection"    - (int) collection ID
 *                              "user"          - (int) user ID of share creator
 *                              "usergroup"     - (int) usergroup ID used for share
 * @return void
 */
function upload_share_setup(string $key,$shareopts = array())
    {
    debug_function_call("upload_share_setup",func_get_args());
    global $baseurl, $pagename, $upload_share_active, $upload_then_edit;
    global $upload_link_workflow_state, $override_status_default;

    $rqdopts = array("collection", "usergroup", "user");
    foreach($rqdopts as $rqdopt)
        {
        if(!isset($shareopts[$rqdopt]))
            {
            return false;
            }
        $$rqdopt = (int)$shareopts[$rqdopt];
        }

    emulate_user($user, $usergroup);
    $upload_share_active = upload_share_active();
    $rs_session = get_rs_session_id(true);
    $upload_then_edit = true;
    
    if(!$upload_share_active || $upload_share_active != $collection)
        {
        // Create a new session even if one exists to ensure a new temporary collection is created for this share
        rs_setcookie("rs_session",'', 7, "", "",substr($baseurl,0,5)=="https", true);
        rs_setcookie("upload_share_active",$collection, 1, "", "", substr($baseurl,0,5)=="https", true);
        $upload_share_active = true;
        }

    // Set default archive state
    if(in_array($upload_link_workflow_state, get_workflow_states()))
        {
        $override_status_default = $upload_link_workflow_state;
        }

    // Upload link key can only work on these pages
    $validpages = array(
        "upload_plupload",
        "edit",
        "category_tree_lazy_load",
        "suggest_keywords",
        "add_keyword",
        "download", // Required to see newly created thumbnails if $hide_real_filepath=true;
        );

    if(!in_array($pagename,$validpages))
        {
        $uploadurl = get_upload_url($collection,$key);
        redirect($uploadurl);
        exit();
        }
    return true;
    }


/**
 * Notify the creator of an external upload share that resources have been uploaded
 *
 * @param  int $collection      Ref of external shared collection 
 * @param  string $k            External upload access key
 * @param  int $tempcollection  Ref of temporay upload collection
 * @return void
 */
function external_upload_notify($collection, $k, $tempcollection)
    {
    global $applicationname,$baseurl,$lang;

    $upload_share = get_external_shares(array("share_collection"=>$collection,"share_type"=>1, "access_key"=>$k));
    if(!isset($upload_share[0]["user"]))
        {
        debug("external_upload_notify() - unable to find external share details: " . func_get_args());
        }
    $user               = $upload_share[0]["user"];
    $templatevars       = array();
    $url                = $baseurl . "/?c=" . (int)$collection;
    $templatevars['url']= $url;	
    		
    $message=$lang["notify_upload_share_new"] . "\n\n". $lang["clicklinkviewcollection"] . "\n\n" . $url;
    $notificationmessage=$lang["notify_upload_share_new"];
        
    // Does the user want an email or notification?
    get_config_option($user,'email_user_notifications', $send_email);    
    if($send_email)
        {
        $notify_email=sql_value("select email value from user where ref='$user'","");
        if($notify_email!='')
            {
            send_mail($notify_email,$applicationname . ": " . $lang["notify_upload_share_new_subject"],$message,"","","emailnotifyuploadsharenew",$templatevars);
            }
        }        
    else
        {
        global $userref;
        message_add($user,$notificationmessage,$url,0);
        }
    }


/**
 * Purge all expired shares/**
 * @param  array $filteropts    Array of options to filter shares purged
 *                              "share_group"       - (int) Usergroup ref 'shared as'
 *                              "share_user"        - (int) user ID of share creator
 *                              "share_type"        - (int) 0=view, 1=upload
 *                              "share_collection"  - (int) Collection ID
 * @return void
 */
function purge_expired_shares($filteropts)
    {
    global $userref;

    $validfilterops = array(
        "share_group",
        "share_user",
        "share_type",
        "share_collection",
    );
    foreach($validfilterops as $validfilterop)
        {
        if(isset($filteropts[$validfilterop]))
            {
            $$validfilterop = $filteropts[$validfilterop];
            }
        else
            {
            $$validfilterop = NULL;
            }
        }
   
    $conditions = array();
    if((int)$share_user > 0 && ($share_user == $userref || checkperm_user_edit($share_user)))
        {
        $conditions[] = "user ='" . (int)$share_user . "'";
        }
    elseif(!checkperm('a') && !checkperm('ex'))
        {
        $conditions[] = "user ='" . (int)$userref . "'";
        }

    if(!is_null($share_group) && (int)$share_group > 0  && checkperm('a'))
        {
        $conditions[] = "usergroup ='" . (int)$share_group . "'";
        }
    if($share_type == 0)
        {
        $conditions[] = "(upload=0 OR upload IS NULL)";
        }
    elseif($share_type == 1)
        {
        $conditions[] = "upload=1";
        }
    if((int)$share_collection > 0)
        {
        $conditions[] = "collection ='" . (int)$share_collection . "'";
        }

    $conditional_sql=" WHERE expires < now()";
    if (count($conditions)>0)
        {
        $conditional_sql .= " AND " . implode(" AND ",$conditions);
        }

    $purge_query = "DELETE FROM external_access_keys " . $conditional_sql;
    sql_query($purge_query);
    $deleted = sql_affected_rows();
    return $deleted;
    }