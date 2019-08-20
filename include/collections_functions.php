<?php
# Collections functions
# Functions to manipulate collections

if (!function_exists("get_user_collections")){
function get_user_collections($user,$find="",$order_by="name",$sort="ASC",$fetchrows=-1,$auto_create=true)
	{
	global $usergroup;
	# Returns a list of user collections.
	$sql="";
	$keysql="";
	$extrasql="";
	if ($find=="!shared")
		{
		# only return shared collections
		$sql=" where (public='1' or c.ref in (select distinct collection from user_collection where user<>'" . escape_check($user) . "' union select distinct collection from external_access_keys))";				
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
    
    # Include themes in my collecions? 
    # Only filter out themes if $themes_in_my_collections is set to false in config.php
   	global $themes_in_my_collections;
   	if (!$themes_in_my_collections)
   		{
		if ($sql==""){$sql=" where ";} else {$sql.=" and ";}	
   		$sql.=" (length(c.theme)=0 or c.theme is null) ";
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
   
	$return="select * from (select c.*,u.username,u.fullname,count(r.resource) count from user u join collection c on u.ref=c.user and c.user='" . escape_check($user) . "' left outer join collection_resource r on c.ref=r.collection $sql $extrasql group by c.ref
	union
	select c.*,u.username,u.fullname,count(r.resource) count from user_collection uc join collection c on uc.collection=c.ref and uc.user='" . escape_check($user) . "' and c.user<>'" . escape_check($user) . "' left outer join collection_resource r on c.ref=r.collection left join user u on c.user=u.ref $sql group by c.ref
	union
	select c.*,u.username,u.fullname,count(r.resource) count from usergroup_collection gc join collection c on gc.collection=c.ref and gc.usergroup='$usergroup' and c.user<>'" . escape_check($user) . "' left outer join collection_resource r on c.ref=r.collection left join user u on c.user=u.ref $sql group by c.ref) clist $keysql group by ref $order_sort";

	$return=sql_query($return);
	
	if ($order_by=="name"){
		if ($sort=="ASC"){usort($return, 'collections_comparator');}
		else if ($sort=="DESC"){usort($return,'collections_comparator_desc');}
	}
	
	// To keep My Collection creation consistent: Check that user has at least one collection of his/her own  (not if collection result is empty, which may include shares), 
	$hasown=false;
	for ($n=0;$n<count($return);$n++){
		if ($return[$n]['user']==$user){
			$hasown=true;
		}
	}

	if (!$hasown && $auto_create && $find=="") # User has no collections of their own, and this is not a search. Make a new 'My Collection'
		{
		# No collections of one's own? The user must have at least one My Collection
		global $usercollection;
		$name=get_mycollection_name($user);
		$usercollection=create_collection ($user,$name,0,1); // make not deletable
		set_user_collection($user,$usercollection);
		
		# Recurse to send the updated collection list.
		return get_user_collections($user,$find,$order_by,$sort,$fetchrows,false);
		}

	return $return;
	}
}	

if (!function_exists("get_collection")){
function get_collection($ref)
	{
    # Returns all data for collection $ref.
    $return=sql_query("select c.*, c.theme2, c.theme3, c.keywords, u.fullname, u.username, c.home_page_publish, c.home_page_text, c.home_page_image, c.session_id, c.description from collection c left outer join user u on u.ref = c.user where c.ref = '" . escape_check($ref) . "'");
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
		return $return;}
	
	return false;
	}
}

function get_collection_resources($collection)
    {
    global $userref;

    # Returns all resources in collection
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

    $plugin_collection_resources=hook('replace_get_collection_resources');
    if(is_array($plugin_collection_resources))
        {
        return $plugin_collection_resources;
        }	

    return sql_array("select resource value from collection_resource where collection='" . escape_check($collection) . "' order by sortorder asc, date_added desc, resource desc"); 
    }

function add_resource_to_collection($resource,$collection,$smartadd=false,$size="",$addtype="")
	{
    if((string)(int)$collection != (string)$collection || (string)(int)$resource != (string)$resource)
        {
        return false;
        }

	global $collection_allow_not_approved_share, $collection_block_restypes;	
	$addpermitted=collection_writeable($collection) || $smartadd;
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
        $keys=get_collection_external_access($collection);
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
				collection_log($collection,"s",$resource, $keys[$n]["access_key"]);
				}
			
			}
		
		hook("Addtocollectionsuccess", "", array( "resourceId" => $resource, "collectionId" => $collection ) );
		
		if(!hook("addtocollectionsql", "", array( $resource,$collection, $size)))
			{
			sql_query("delete from collection_resource where resource='" . escape_check($resource) . "' and collection='" . escape_check($collection) . "'");
			sql_query("insert into collection_resource(resource,collection,purchase_size) values ('" . escape_check($resource) . "','" . escape_check($collection) . "','$size')");
			}
		
		#log this
		collection_log($collection,"a",$resource);
		return true;
		}
	else
		{
		hook("Addtocollectionfail", "", array( "resourceId" => $resource, "collectionId" => $collection ) );
		return false;
		}
	}

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
		
		#log this
		collection_log($collection,"r",$resource);
		return true;
		}
	else
		{
		hook("Removefromcollectionfail", "", array( "resourceId" => $resource, "collectionId" => $collection ) );
		return false;
		}
	}
	
function collection_writeable($collection)
	{
	# Returns true if the current user has write access to the given collection.
	$collectiondata=get_collection($collection);
	global $userref,$usergroup;
	global $allow_smart_collections;
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
	debug("anonymous_login : " . $anonymous_login);
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
		|| checkperm("a");
	return $writable;
	
	}
	
function collection_readable($collection)
	{
	# Returns true if the current user has read access to the given collection.

	# Fetch collection details.
	if (!is_numeric($collection)) {return false;}
	$collectiondata=get_collection($collection);
	
	# Load a list of attached users
	$attached=sql_array("select user value from user_collection where collection='$collection'");
	$attached_groups=sql_array("select usergroup value from usergroup_collection where collection='$collection'");
	global $userref,$usergroup;

	global $ignore_collection_access, $collection_commenting;
	# Access if collection_commenting is enabled and request feedback checked
	# Access if it's a public collection (or theme)
	# Access if k is not empty or option to ignore collection access is enabled and k is empty
	if ($collection_commenting && $collectiondata['request_feedback'] == 1 || $collectiondata["public"]==1 || getval("k","")!="" || getval("k","")=="" && $ignore_collection_access)
		{
		return true;
		}

	# Perform these checks only if a user is logged in
	global $userref;
	if (is_numeric($userref))
		{
		# Access if:
		#	- It's their collection
		# 	- It's a public collection (or theme)
		#	- They have the 'access and edit all collections' admin permission
		# 	- They are attached to this collection
		#   - Option to ignore collection access is enabled and k is empty
		if($userref==$collectiondata["user"] || $collectiondata["public"]==1 || checkperm("h") || in_array($userref,$attached)  || in_array($usergroup,$attached_groups)|| /*(checkperm("R") && $request) ||*/ getval("k","")!="" || (getval("k","")=="" && $ignore_collection_access))
			{
			return true;
			}

		}

	return false;
	}
	
function set_user_collection($user,$collection)
	{
	global $usercollection,$username,$anonymous_login,$anonymous_user_session_collection;
	if(!(isset($anonymous_login) && $username==$anonymous_login) || !$anonymous_user_session_collection)
		{		
		sql_query("update user set current_collection='" . escape_check($collection) . "' where ref='" . escape_check($user) . "'");
		}
	$usercollection=$collection;
	}
	
if (!function_exists("create_collection")){	
function create_collection($userid,$name,$allowchanges=0,$cant_delete=0,$ref=0,$public=false,$categories=array())
	{
	global $username,$anonymous_login,$rs_session, $anonymous_user_session_collection;
	debug("create_collection(\$userid = {$userid}, \$name = {$name}, \$ref = '" . escape_check($ref) . "'");
	if($username==$anonymous_login && $anonymous_user_session_collection)
		{		
		// We need to set a collection session_id for the anonymous user. Get session ID to create collection with this set
		$rs_session=get_rs_session_id(true);
		}
	else
		{	
		$rs_session="";
		}
	
	$categorysql = "";
	$themecolumns = "";
	$themecount = 1;
	if(count($categories) > 0)
		{
		foreach($categories as $category)
			{
			$themecolumns .= ",theme" . 	($themecount == 1 ? "" : $themecount);
			$categorysql .= ",'" . escape_check($category) . "'";
			$themecount++;
			}
		}

	# Creates a new collection and returns the reference
	sql_query("insert into collection (" . ($ref!=0?"ref,":"") . "name,user,created,allow_changes,cant_delete,session_id,public" . $themecolumns . ") values (" . ($ref!=0?"'" . escape_check($ref) . "',":"") . "'" . escape_check($name) . "','$userid',now(),'" . escape_check($allowchanges) . "','" . escape_check($cant_delete) . "'," . (($rs_session=="")?"NULL":"'" . $rs_session . "'") . "," . ($public ? "1" : "0" ) . $categorysql . ")");
	//echo "insert into collection (" . ($ref!=0?"ref,":"") . "name,user,created,allow_changes,cant_delete,session_id,public" . $themecolumns . ") values (" . ($ref!=0?"'" . $ref . "',":"") . "'" . escape_check($name) . "','$userid',now(),'$allowchanges','$cant_delete'," . (($rs_session=="")?"NULL":"'" . $rs_session . "'") . "," . ($public ? "1" : "0" ) . $categorysql . ")" . "\n";
	$ref=sql_insert_id();

	index_collection($ref);	
	return $ref;
	}	
}
	
function delete_collection($collection)
	{
	# Deletes the collection with reference $ref
	global $home_dash, $lang;
	if(!is_array($collection)){$collection=get_collection($collection);}
	$ref=$collection["ref"];
	
	# Permissions check
	if (!collection_writeable($ref)) {return false;}
	
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
	// log this
	collection_log($ref,"X",0, $collection["name"] . " (" . $lang["owner"] . ":" . $collection["username"] . ")");
	}
	
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

if (!function_exists("search_public_collections")){	
function search_public_collections($search="", $order_by="name", $sort="ASC", $exclude_themes=true, $exclude_public=false, $include_resources=false, $override_group_restrict=false, $search_user_collections=false)
	{
	global $userref;

	# Performs a search for themes / public collections.
	# Returns a comma separated list of resource refs in each collection, used for thumbnail previews.
	$sql="";
	$keysql="";
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
                    $keysql.="join collection_keyword k" . $n . " on k" . $n . ".collection=c.ref and (k" . $n . ".keyword='$keyref')";
                    }
			    //$keysql="or keyword in (" . join (",",$keyrefs) . ")";
			    }
			}
        
        global $search_public_collections_ref;
        if ($search_public_collections_ref && is_numeric($search)){$spcr="or c.ref='" . escape_check($search) . "'";} else {$spcr="";}    
		//$sql.="and (c.name rlike '%$search%' or u.username rlike '%$search%' or u.fullname rlike '%$search%' $spcr )";
		}

	if ($exclude_themes) # Include only public collections.
		{
		$sql.=" and (length(c.theme)=0 or c.theme is null)";
		}
	
	if (($exclude_public) && !$search_user_collections) # Exclude public only collections (return only themes)
		{
		$sql.=" and length(c.theme)>0";
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
	
	if ($search_user_collections) $sql_public="(c.public=1 or c.user=$userref)";
	else $sql_public="c.public=1";

	# Run the query
	if ($include_resources)
		{    
        return sql_query("select distinct c.*,u.username,u.fullname, count( DISTINCT cr.resource ) count from collection c left join collection_resource cr on c.ref=cr.collection left outer join user u on c.user=u.ref left outer join collection_keyword k on c.ref=k.collection $keysql where $sql_public $sql group by c.ref order by " . escape_check($order_by) . " " . escape_check($sort));
		}
	else
		{
		return sql_query("select distinct c.*,u.username,u.fullname from collection c left outer join user u on c.user=u.ref left outer join collection_keyword k on c.ref=k.collection $keysql where $sql_public $sql group by c.ref order by " . escape_check($order_by) . " " . escape_check($sort));
		}
	}
}


function do_collections_search($search,$restypes,$archive=0,$order_by='',$sort="DESC")
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
		# The old way - same search as when searching within publich collections.
		$collections=search_public_collections($search,"theme","ASC",!$search_includes_themes_now,!$search_includes_public_collections_now,true,false, $search_includes_user_collections_now);
		}
	
	
        $condensedcollectionsresults=array();
        $result=$collections;

    	}
       
    
    		
    return $result;
    }



function add_collection($user,$collection)
	{
	# Add a collection to a user's 'My Collections'
	
	// Don't add if we are anonymous - we can only have one collection
	global $anonymous_login,$username,$anonymous_user_session_collection;
 	if (isset($anonymous_login) && ($username==$anonymous_login) && $anonymous_user_session_collection)
		{return false;}
	
	# Remove any existing collection first
	remove_collection($user,$collection);
	# Insert row
	sql_query("insert into user_collection(user,collection) values ('" . escape_check($user) . "','" . escape_check($collection) . "')");
	#log this
	collection_log($collection,"S",0, sql_value ("select username as value from user where ref = '" . escape_check($user) . "'",""));
	}

function remove_collection($user,$collection)
	{
	# Remove someone else's collection from a user's My Collections
	sql_query("delete from user_collection where user='" . escape_check($user) . "' and collection='" . escape_check($collection) . "'");
	#log this
	collection_log($collection,"T",0, sql_value ("select username as value from user where ref = '" . escape_check($user) . "'",""));
	}

if (!function_exists("index_collection")){
function index_collection($ref,$index_string='')
	{
	# Update the keywords index for this collection
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
}

function save_collection($ref, $coldata=array())
	{
	global $theme_category_levels,$attach_user_smart_groups;
	
	if (!is_numeric($ref) || !collection_writeable($ref))
        {
        return false;
        }
	
    if(count($coldata) == 0)
        {
        // Old way
        $coldata["name"]            = getval("name","");
        $coldata["allow_changes"]   = getval("allow_changes","") != "" ? 1 : 0;
        $coldata["public"]          = getval('public', 0, true);
        $coldata["keywords"]        = getval("keywords","");
        for($n=1;$n<=$theme_category_levels;$n++)
            {
            if ($n==1)
                {
                $themeindex = "";
                }
            else
                {
                $themeindex = $n;
                }
            $themename = getvalescaped("theme$themeindex","");
			if($themename != "")
                {
                $coldata["theme" . $themeindex] = $themename;
                }
            
			if (getval("newtheme$themeindex","")!="")
                {
				$coldata["theme". $n] = trim(getval("newtheme$themeindex",""));
				}    
            }
            
        if (checkperm("h"))
            {
            $coldata["home_page_publish"]   = (getval("home_page_publish","") != "") ? "1" : "0";
            $coldata["home_page_text"]      = getval("home_page_text","");
            if (getval("home_page_image","") != "")
                {
                $coldata["home_page_image"] = getval("home_page_image","");
                }
            }
        }
        
    $oldcoldata = get_collection($ref);    

	// Create sql column update text
	if (!hook('modifysavecollection'))
        {
        $sqlset = array();
        foreach($coldata as $colopt=>$colset)
            {
            if(!isset($oldcoldata[$colopt]) || $colset != $oldcoldata[$colopt])
                {
                $sqlset[$colopt] = $colset;    
                }                
            }
        
        if(count($sqlset) > 0)
            {
            $sqlupdate = "";
            foreach($sqlset as $colopt => $colset)
                {
                if($sqlupdate != "")
                    {
                    $sqlupdate .= ",";    
                    }
                $sqlupdate .= $colopt . "='" . escape_check($colset) . "' ";   
                }
                
            $sql = "UPDATE collection SET " . $sqlupdate . " WHERE ref='" . $ref . "'";
            sql_query($sql);
            
            // Log the changes
            foreach($sqlset as $colopt => $colset)
                {
                switch($colopt)
                    {
                    case "public";
                        collection_log($ref, 'A', 0, $colset ? 'public' : 'private');
                    break;    
                    case "allow_changes";
                        collection_log($ref, 'U', 0,  $colset ? 'true' : 'false' );
                    break; 
                    default;
                        collection_log($ref, 'e', 0,  $colopt  . " = " . $colset);
                    break;
                    }
                 
                }
            }
        } # end replace hook - modifysavecollection
	
	index_collection($ref);
  
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

    # If 'users' is specified (i.e. access is private) then rebuild users list
    $users=getvalescaped("users",false);
	if (($users)!="")
		{
		# Build a new list and insert
		$users=resolve_userlist_groups($users);
		$ulist=array_unique(trim_array(explode(",",$users)));
		$urefs=sql_array("select ref value from user where username in ('" . join("','",$ulist) . "')");
		if (count($urefs)>0)
			{
			sql_query("insert into user_collection(collection,user) values ($ref," . join("),(" . $ref . ",",$urefs) . ")");
			$new_attached_users=array_diff($urefs, $old_attached_users);
			}
		#log this
		collection_log($ref,"S",0, join(", ",$ulist));
		
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
				collection_log($ref,"S",0, $groupnames);
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
	if (getval("relateall","")!="")
		{
        relate_all_collection($ref);
		}
		
	# Remove all resources?
	if (getval("removeall","")!="")
		{
		remove_all_resources_from_collection($ref);
		}
		
	# Delete all resources?
	if (getval("deleteall","")!="" && !checkperm("D"))
		{
		
		if(allow_multi_edit($ref)) {
			delete_resources_in_collection($ref);
		}

		}
		
	$result_limit = getvalescaped("result_limit", 0, true);

	# Update limit count for saved search
	if ($result_limit > 0)
		{
		sql_query("update collection_savedsearch set result_limit='" . $result_limit . "' where collection='$ref'");
		}
	
	refresh_collection_frame();
	}

function get_max_theme_levels(){
	// return the maximum number of theme category levels (columns) present in the collection table
	$sql = "show columns from collection like 'theme%'";
	$results = sql_query($sql);
	foreach($results as $result) {
		if ($result['Field'] == 'theme'){
			$level = 1;
		} else {
			$thislevel = substr($result['Field'],5);
			if (is_numeric($thislevel) && $thislevel > $level){
				$level = $thislevel;
			}
		}
	}
	return $level;
}

function get_theme_headers($themes=array())
	{
	# Return a list of theme headers, i.e. theme categories
	#return sql_array("select theme value,count(*) c from collection where public=1 and length(theme)>0 group by theme order by theme");
	# Work out which theme category level we are selecting based on the higher selected levels provided.
	$selecting="theme";

	$theme_path = "";	
	$sql="";	
	for ($x=0;$x<count($themes);$x++){		
		if ($x>0) $theme_path .= "|";		
		$theme_path .= $themes[$x];		
		if (isset($themes[$x])){
			$selecting="theme".($x+2);
		}	
		if (isset($themes[$x]) && $themes[$x]!="" && $x==0) {
			$sql.=" and theme LIKE '%" . escape_check($themes[$x]) . "'";
		}
		else if (isset($themes[$x])&& $themes[$x]!=""&& $x!=0) {
			$sql.=" and theme".($x+1)." LIKE '%" . escape_check($themes[$x]) . "'";
		}
	}	
	$return=array();
	
	$themes=sql_query("select * from collection where public=1 and $selecting is not null and length($selecting)>0 $sql");
	for ($n=0;$n<count($themes);$n++)
		{		
		if (
				(!in_array($themes[$n][$selecting],$return)) &&					# de-duplicate as there are multiple collections per theme category				
				(checkperm("j*") || checkperm("j" . $themes[$n]["theme"])) &&	# and we have permission to access then add to array							
				(!checkperm ("j-${theme_path}|" . $themes[$n][$selecting]))		# path must not be in j-<path> exclusion				
			) 
			{											
				$return[]=$themes[$n][$selecting];
			}
		}
	usort($return,"themes_comparator");	
	return $return;
	}
	
if (!function_exists("themes_comparator")){
function themes_comparator($a, $b)
	{
	return strnatcasecmp(i18n_get_collection_name($a), i18n_get_collection_name($b));
	}
}

function collections_comparator($a, $b)
	{
	return strnatcasecmp(i18n_get_collection_name($a), i18n_get_collection_name($b));
	}

function collections_comparator_desc($a, $b)
	{
	return strnatcasecmp(i18n_get_collection_name($b), i18n_get_collection_name($a));
	}		

if (!function_exists("get_themes")){
function get_themes($themes=array(""),$subthemes=false)
	{	
	$themes_order_by=getvalescaped("themes_order_by",getvalescaped("saved_themes_order_by","name"));
	$sort=getvalescaped("sort",getvalescaped("saved_themes_sort","ASC"));	
	global $themes_column_sorting,$themes_with_resources_only,$descthemesorder;
	if (!$themes_column_sorting && !$descthemesorder)
		{
		$themes_order_by="name";
		$sort="ASC";
		} // necessary to avoid using a cookie that can't be changed if this is turned off.
	$sort = ($descthemesorder)? "DESC" : $sort;
	# Return a list of themes under a given header (theme category).
	$sql="select *,(select count(*) from collection_resource cr where cr.collection=c.ref) c from collection c  where c.theme='" . escape_check($themes[0]) . "' ";
	
	for ($x=1;$x<count($themes)+1;$x++){
		if (isset($themes[$x])&&$themes[$x]!=""){
			$sql.=" and theme".($x+1)."='" . escape_check($themes[$x]) . "' ";
		}
		else {
			global $theme_category_levels;
			if (($x+1)<=$theme_category_levels && !$subthemes){
			$sql.=" and (theme".($x+1)."='' or theme".($x+1)." is null) ";
			}
		}
	}

	$order_sort="";
	if ($themes_order_by!="name"){$order_sort=" order by $themes_order_by $sort";}
	$sql.=" and c.public=1    $order_sort;";
	//echo $sql . "\n";
	$collections=sql_query($sql);
	if ($themes_order_by=="name"){
		if ($sort=="ASC"){usort($collections, 'collections_comparator');}
		else if ($sort=="DESC"){usort($collections,'collections_comparator_desc');}
	}
	
	if ($themes_with_resources_only) {
		$collections_orig = $collections;
		$collections = array();
		for ($i=0;$i<count($collections_orig);$i++) {
			$resources = do_search('!collection'.$collections_orig[$i]['ref']);
			if (count($resources) > 0) {
				$collections[] = $collections_orig[$i];
			}
		}
	}	

	return $collections;
	}
}

function get_smart_theme_headers()
	{
	# Returns a list of smart theme headers, which are basically fields with a 'smart theme name' set.
	return sql_query("SELECT ref, name, smart_theme_name, type FROM resource_type_field WHERE length(smart_theme_name) > 0 ORDER BY smart_theme_name");
	}

function get_smart_themes_nodes($field, $is_category_tree, $parent = null)
    {
    global $smart_themes_omit_archived, $themes_category_split_pages;

    $return = array();

    // Determine if this should cascade onto children for category tree type
    $recursive = false;
    if($is_category_tree && !$themes_category_split_pages)
        {
        $recursive = true;
        }

    $nodes = get_nodes($field, ((0 == $parent) ? null : $parent), $recursive);

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

        if($is_category_tree)
            {
            if(is_parent_node($nodes[$n]['ref']))
                {
                $parent_node_to_use = $nodes[$n]['ref'];
                $is_parent          = true;
                }

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

if (!function_exists("email_collection")){
function email_collection($colrefs,$collectionname,$fromusername,$userlist,$message,$feedback,$access=-1,$expires="",$useremail="",$from_name="",$cc="",$themeshare=false,$themename="",$themeurlsuffix="",$list_recipients=false, $add_internal_access=false,$group="",$sharepwd="")
	{
	# Attempt to resolve all users in the string $userlist to user references.
	# Add $collection to these user's 'My Collections' page
	# Send them an e-mail linking to this collection
	#  handle multiple collections (comma seperated list)
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
	$reflist=trim_array(explode(",",$colrefs));
	$emails_keys=resolve_user_emails($ulist);

    if(0 === count($emails_keys))
        {
        return $lang['email_error_user_list_not_valid'];
        }

	$emails=$emails_keys['emails'];
	$key_required=$emails_keys['key_required'];

	# Add the collection(s) to the user's My Collections page
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
				collection_log($reflist[$nx1],"S",0, sql_value ("select username as value from user where ref = $urefs[$nx2]",""));

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
			$url=$baseurl . "/pages/themes.php" . $themeurlsuffix;			
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
				collection_log($reflist[$nx2],"E",0, $emails[$nx1]);
				}
			
			}
		else
			{
			for ($nx2=0;$nx2<count($reflist);$nx2++)
				{
				$url="";
				$key="";
				$emailcollectionmessageexternal=false;
				# Do we need to add an external access key for this user (e-mail specified rather than username)?
				if ($key_required[$nx1])
					{
					$k=generate_collection_access_key($reflist[$nx2],$feedback,$emails[$nx1],$access,$expires,$group,$sharepwd);
					$key="&k=". $k;
					$emailcollectionmessageexternal=true;
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
				collection_log($reflist[$nx2],"E",0, $emails[$nx1]);
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
			# Set empty expiration tempaltevars
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
}	


function generate_collection_access_key($collection,$feedback=0,$email="",$access=-1,$expires="",$group="", $sharepwd="")
	{
	# For each resource in the collection, create an access key so an external user can access each resource.
	global $userref,$usergroup,$scramble_key;
	if ($group=="" || !checkperm("x")) {$group=$usergroup;} # Default to sharing with the permission of the current usergroup if not specified OR no access to alternative group selection.
	$k=substr(md5($collection . "," . time()),0,10);
	$r=get_collection_resources($collection);
	for ($m=0;$m<count($r);$m++)
		{
		# Add the key to each resource in the collection
		if(can_share_resource($r[$m]))
			{
			sql_query("insert into external_access_keys(resource,access_key,collection,user,usergroup,request_feedback,email,date,access,expires, password_hash) values ('" . $r[$m] . "','$k','$collection','$userref','$group','$feedback','" . escape_check($email) . "',now(),$access," . (($expires=="")?"null":"'" . escape_check($expires) . "'"). "," . (($sharepwd != "" && $sharepwd != "(unchanged)") ? "'" . hash('sha256', $k . $sharepwd . $scramble_key) . "'": "null") . ");");
			}
		}
	
	hook("generate_collection_access_key","",array($collection,$k,$userref,$feedback,$email,$access,$expires,$group,$sharepwd));
	return $k;
	}
	
function get_saved_searches($collection)
	{
	return sql_query("select * from collection_savedsearch where collection='" . escape_check($collection) . "' order by created");
	}

function add_saved_search($collection)
	{
	sql_query("insert into collection_savedsearch(collection,search,restypes,archive) values ('" 
		. escape_check($collection) . "','" . getvalescaped("addsearch","") . "','" . getvalescaped("restypes","") . "','" . getvalescaped("archive","") . "')");
	}

function remove_saved_search($collection,$search)
	{
	sql_query("delete from collection_savedsearch where collection='" . escape_check($collection) . "' and ref='" . escape_check($search) . "'");
	}

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
	}

function get_search_title($searchstring){
	// for naming smart collections, takes a full searchstring with the form 'search=restypes=archive=starsearch=' (all parameters optional)
	// and uses search_title_processing to autocreate a more informative title 
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
    if ($restypes!=""){ 
		$resource_types=get_resource_types($restypes);
		foreach($resource_types as $type){
			$typenames[]=$type['name'];
		}
		$search_title.=" [".implode(', ',$typenames)."]";
	}
	$title=str_replace(">","",strip_tags($search_title));
	return $title;
}

function add_saved_search_items($collection, $search = "", $restypes = "", $archivesearch = "", $order_by = "relevance", $sort = "desc", $daylimit = "", $starsearch = "")
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
   
    $results=do_search($search, $restypes, $order_by, $archivesearch,-1,$sort,false,$starsearch,false,false,$daylimit);

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
				collection_log($collection,"s",$resource, $keys[$n]["access_key"]);	
				
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
				collection_log($collection,"a",$resource);
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

if (!function_exists("allow_multi_edit")){
function allow_multi_edit($collection,$collectionid = 0)
	{
	global $resource;
	# Returns true or false, can all resources in this collection be edited by the user?

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
		if(!is_array($collection))
			{
			// Need the collection resources so need to run the search
			$collectionid = $collection;
			$collection = do_search("!collection{$collectionid}", '', '', 0, -1, '', false, 0, false, false, '', false, true, true,false);
			}
			
		$resultcount = count($collection);
		$editresults = 	do_search("!collection{$collectionid}", '', '', 0, -1, '', false, 0, false, false, '', false, true, true,true);
		$editcount = count($editresults);
		if($resultcount != $editcount){return false;}
		}
	
			
	if(hook('denyaftermultiedit', '', array($collection))) { return false; }

	return true;
	}
}	

function get_theme_image($themes=array(), $collection="", $smart=false)
	{
	# Returns an array of resource references that can be used as theme category images.
	global $theme_images_number;
	global $theme_category_levels;
	global $usergroup, $userref;
	global $userpermissions;
	# Resources that have been specifically chosen using the option on the collection comments page will be returned first based on order by.
	
	# have this hook return an empty array if a plugin needs to return a false value from function
	$images_override=hook('get_theme_image_override','', array($themes, $collection, $smart));
	
	if ($images_override!==false && is_array($images_override))
		{
		$images=$images_override;
		}
	else 
		{
		if ($smart)
			{
			$nodestring = '';
			foreach($themes as $node)
                {
				$nodestring .= NODE_TOKEN_PREFIX . $node['ref'];
                }
                
			if($nodestring=='')
				{
				return false;
				}
                
            // As we are using nodes just do a simple search so that permissions are honoured
            $images = do_search($nodestring,'','hit_count',0,-1,'desc',false,0,false,false,'',true,false,true);
            return is_array($images) ? array_column($images, "ref") : array();
			}
		else
		{
			$sqlfilter_custom="";
			$sqlselect="SELECT r.ref, cr.use_as_theme_thumbnail, theme2, r.hit_count FROM collection c "
							."JOIN collection_resource cr on cr.collection=c.ref "
							."JOIN resource r on r.ref=cr.resource and r.archive=0 and r.ref>0 and r.has_image=1 ";
			
			// Add custom access joins if necessary
			if (!checkperm("v"))
				{
				$sqlselect.= " LEFT OUTER JOIN resource_custom_access rca2 " 
										."ON r.ref=rca2.resource "
										."AND rca2.user='$userref' "
										."AND (rca2.user_expires IS null or rca2.user_expires>now()) "
										."AND rca2.access<>2 "
							." LEFT OUTER JOIN resource_custom_access rca "
										."ON r.ref=rca.resource "
										."AND rca.usergroup='$usergroup' "
										."AND rca.access<>2 ";

				# Check both the resource access, but if confidential is returned, also look at the joined user-specific or group-specific custom access for rows.
				$sqlfilter_custom.=" AND (     r.access<>'2' " 
				                  ."       OR (r.access=2 AND ( (rca.access IS NOT null AND rca.access<>2) OR (rca2.access IS NOT null AND rca2.access<>2) ) ) )";

				}

			// Build filter, attaching custom access filtering, if any 	
			$sqlfilter=" WHERE c.public=1 and c.theme='" . escape_check($themes[0]) . "' "
			           .$sqlfilter_custom;

			// Attach filter to principal select		   
			$sqlselect.=$sqlfilter;

			$orderby  =" ORDER BY ti.use_as_theme_thumbnail desc";
			
			$orderby_theme='';
			for ($n=2;$n<=count($themes)+1;$n++)
                {
				if (isset($themes[$n-1]))
                    {
					$sqlselect.=" AND theme".$n."='" . escape_check($themes[$n-1]) . "' ";
                    } 
				else
                    {
					if ($n<=$theme_category_levels)
                        {
						# Resources in sub categories can be used but should be below those in the current category
						$orderby_theme=" ORDER BY theme".$n;
                        }
                    }
                } 

			if($collection != "")
				{
				$sqlselect.=" and c.ref = '" . escape_check($collection) .  "'";
				}
				
			$orderby.=",ti.hit_count desc,ti.ref desc";
			}
	
		$sql = "SELECT ti.ref value from (" . $sqlselect . $orderby_theme . ") ti "
		       .$orderby . " limit " . escape_check($theme_images_number);

        $images=sql_array($sql,0);

        }
    if (count($images)>0) {return $images;}
	return false;
	}

function swap_collection_order($resource1,$resource2,$collection)
	{
	# Inserts $resource1 into the position currently occupied by $resource2 

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

function update_collection_order($neworder,$collection,$offset=0)
	{	
	if (!is_array($neworder)) {
		exit ("Error: invalid input to update collection function.");
	}

	$updatesql= "update collection_resource set sortorder=(case resource ";
	$counter = 1 + $offset;
	foreach ($neworder as $colresource){
		$updatesql.= "when '" . escape_check($colresource) . "' then '$counter' ";
		$counter++;
	}
	$updatesql.= "else sortorder END) WHERE collection='" . escape_check($collection) . "'";
	sql_query($updatesql);
	$updatesql="update collection_resource set sortorder=99999 WHERE collection='" . escape_check($collection) . "' and sortorder is NULL";
	sql_query($updatesql);
	}
	
function get_collection_resource_comment($resource,$collection)
	{
	$data=sql_query("select *,use_as_theme_thumbnail from collection_resource where collection='" . escape_check($collection) . "' and resource='" . escape_check($resource) . "'","");
	return $data[0];
	}
	
function save_collection_resource_comment($resource,$collection,$comment,$rating)
	{
	# get data before update so that changes can be logged.	
	$data=sql_query("select comment,rating from collection_resource where resource='" . escape_check($resource) . "' and collection='" . escape_check($collection) . "'");
	sql_query("update collection_resource set comment='" . escape_check($comment) . "',rating=" . (($rating!="")?"'" . escape_check($rating) . "'":"null") . ",use_as_theme_thumbnail='" . (getval("use_as_theme_thumbnail","")==""?0:1) . "' where resource='" . escape_check($resource) . "' and collection='" . escape_check($collection) . "'");
	
	# log changes
	if ($comment!=$data[0]['comment']){collection_log($collection,"m",$resource);}
	if ($rating!=$data[0]['rating']){collection_log($collection,"*",$resource);}
	return true;
	}

function relate_to_collection($ref,$collection)	
	{
	# Relates every resource in $collection to $ref
		$colresources = get_collection_resources($collection);
		sql_query("delete from resource_related where resource='" . escape_check($ref) . "' and related in ('" . join("','",$colresources) . "')");  
		sql_query("insert into resource_related(resource,related) values (" . escape_check($ref) . "," . join("),(" . $ref . ",",$colresources) . ")");
	}	
	
function get_mycollection_name($userref)
	{
	# Fetches the next name for a new My Collection for the given user (My Collection 1, 2 etc.)
	global $lang;
	for ($n=1;$n<500;$n++)
		{
		# Construct a name for this My Collection. The name is translated when displayed!
		if ($n==1)
			{
			$name = "My Collection"; # Do not translate this string!
			}
		else
			{
			$name = "My Collection " . $n; # Do not translate this string!
			}
		$ref=sql_value("select ref value from collection where user='" . escape_check($userref) . "' and name='$name'",0);
		if ($ref==0)
			{
			# No match!
			return $name;
			}
		}
	# Tried nearly 500 names(!) so just return a standard name 
	return "My Collection";
	}
	
function get_collection_comments($collection)
	{
	return sql_query("select * from collection_resource where collection='" . escape_check($collection) . "' and length(comment)>0 order by date_added");
	}

function send_collection_feedback($collection,$comment)
	{
	# Sends the feedback to the owner of the collection.
	global $applicationname,$lang,$userfullname,$userref,$k,$feedback_resource_select,$feedback_email_required,$regex_email;
	
	$cinfo=get_collection($collection);if ($cinfo===false) {exit("Collection not found");}
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
			
	
	# Cancel the feedback request for this resource.
	/* - Commented out - as it may be useful to leave the feedback request in case the user wishes to leave
	     additional feedback or make changes.
	     
	if (isset($userref))
		{
		sql_query("update user_collection set request_feedback=0 where collection='$collection' and user='$userref'");
		}
	else
		{
		sql_query("update external_access_keys set request_feedback=0 where access_key='$k'");
		}
	*/
	}

function copy_collection($copied,$current,$remove_existing=false)
	{	
	# Get all data from the collection to copy.
	$copied_collection=sql_query("select cr.resource, r.resource_type from collection_resource cr join resource r on cr.resource=r.ref where collection='" . escape_check($copied) . "'","");
	
	if ($remove_existing)
		{
		#delete all existing data in the current collection
		sql_query("delete from collection_resource where collection='" . escape_check($current) . "'");
		collection_log($current,"R",0);
		}
	
	#put all the copied collection records in
	foreach($copied_collection as $col_resource)
		{
		# Use correct function so external sharing is honoured.
		add_resource_to_collection($col_resource['resource'],$current,true,"",$col_resource['resource_type']);
		}
	
	hook('aftercopycollection','',array($copied,$current));
	}

if (!function_exists("collection_is_research_request")){
function collection_is_research_request($collection)
	{
	# Returns true if a collection is a research request
	return (sql_value("select count(*) value from research_request where collection='" . escape_check($collection) . "'",0)>0);
	}
}	

if (!function_exists("add_to_collection_link")){
function add_to_collection_link($resource,$search="",$extracode="",$size="",$class="")
    {
    # Generates a HTML link for adding a resource to a collection
    global $lang;

    return "<a class=\"addToCollection " . $class . "\" href=\"#\" title=\"" . $lang["addtocurrentcollection"] . "\" onClick=\"AddResourceToCollection(event,'" . $resource . "','" . $size . "');" . $extracode . "return false;\">";

    }
}

if (!function_exists("remove_from_collection_link")){		
function remove_from_collection_link($resource,$search="",$class="")
    {
    # Generates a HTML link for removing a resource to a collection
    global $lang, $pagename;

    return "<a class=\"removeFromCollection " . $class . "\" href=\"#\" title=\"" . $lang["removefromcurrentcollection"] . "\" onClick=\"RemoveResourceFromCollection(event,'" . $resource . "','" . $pagename . "');return false;\">";

    }
}

function change_collection_link($collection)
    {
    # Generates a HTML link for adding a changing the current collection
    global $lang;
    return '<a onClick="ChangeCollection('.$collection.',\'\');return false;" href="collections.php?collection='.$collection.'">' . LINK_CARET . $lang["selectcollection"].'</a>';
    }
if(!function_exists("get_collection_external_access")){
function get_collection_external_access($collection)
	{
	# Return all external access given to a collection.
	# Users, emails and dates could be multiple for a given access key, an in this case they are returned comma-separated.
	return sql_query("select access_key,group_concat(DISTINCT user ORDER BY user SEPARATOR ', ') users,group_concat(DISTINCT email ORDER BY email SEPARATOR ', ') emails,max(date) maxdate,max(lastused) lastused,access,expires,usergroup,password_hash from external_access_keys where collection='" . escape_check($collection) . "' group by access_key order by date");
	}
}
function delete_collection_access_key($collection,$access_key)
	{
	# Get details for log
	$users = sql_value("select group_concat(DISTINCT email ORDER BY email SEPARATOR ', ') value from external_access_keys where collection='" . escape_check($collection) . "' and access_key = '" . escape_check($access_key) . "' group by access_key ", "");
	# Deletes the given access key.
	sql_query("delete from external_access_keys where access_key='" . escape_check($access_key) . "' and collection='" . escape_check($collection) . "'");
	# log changes
	collection_log($collection,"t","",$users);

	}
	
function collection_log($collection,$type,$resource,$notes = "")
	{
	global $userref;
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
    
function get_collection_log($collection, $fetchrows=-1)
	{
	global $view_title_field;	
	return sql_query("select c.date,u.username,u.fullname,c.type,r.field".$view_title_field." title,c.resource, c.notes from collection_log c left outer join user u on u.ref=c.user left outer join resource r on r.ref=c.resource where collection='$collection' order by c.date desc",false,$fetchrows);
	}
	
function get_collection_videocount($ref)
	{
	global $videotypes;
    #figure out how many videos are in a collection. if more than one, can make a playlist
	$resources = do_search("!collection" . $ref);
	$videocount=0;
	foreach ($resources as $resource){if (in_array($resource['resource_type'],$videotypes)){$videocount++;}}
	return $videocount;
	}
	
function collection_max_access($collection)	
	{
	# Returns the maximum access (the most permissive) that the current user has to the resources in $collection.
	$maxaccess=2;
	$result=do_search("!collection" . $collection);
	for ($n=0;$n<count($result);$n++)
		{
		$ref=$result[$n]["ref"];
		# Load access level
		$access=get_resource_access($result[$n]);
		if ($access<$maxaccess) {$maxaccess=$access;}
		}
	return $maxaccess;
	}

function collection_min_access($collection)
    {
    # Returns the minimum access (the least permissive) that the current user has to the resources in $collection.
    $minaccess = 0;
    if(is_array($collection))
        {
        $result = $collection;
        }
    else
        {
        $result = do_search("!collection{$collection}", '', 'relevance', 0, -1, 'desc', false, '', false, '');
        }

    for($n = 0; $n < count($result); $n++)
        {
        $ref = $result[$n]['ref'];

        # Load access level
        $access = get_resource_access($result[$n]);
        if($access > $minaccess)
            {
            $minaccess = $access;
            }
        }

    return $minaccess;
    }
	
function collection_set_public($collection)
	{
	// set an existing collection to be public
		if (is_numeric($collection)){
			$sql = "update collection set public = '1' where ref = '$collection'";
			sql_query($sql);
			return true;
		} else {
			return false;
		}
	}

function collection_set_private($collection)
	{
	// set an existing collection to be private
		if (is_numeric($collection)){
			$sql = "update collection set public = '0' where ref = '$collection'";
			sql_query($sql);
			return true;
		} else {
			return false;
		}
	}

/**
* Set a collection as a featured collection.
*
* @param integer $collection - reference of collection
* @param array  $categories - array of categories
*
* @return boolean
*/
function collection_set_themes ($collection, $categories = array())
	{
	global $theme_category_levels;
	if(!is_numeric($collection) || !is_array($categories) || count($categories) > $theme_category_levels){return false;}
	$sql="update collection set public = 1";
	for($n=0;$n<count($categories);$n++)
		{	
		if ($n==0){$categoryindex="";} else {$categoryindex=$n+1;}
		$sql .= ",theme" . $categoryindex . "='" . escape_check($categories[$n]) . "'";
		}
	
	$sql .= " where ref = '" . $collection . "'";
	sql_query($sql);
	return true;
	}
	
function remove_all_resources_from_collection($ref){
    // abstracts it out of save_collection()
    $removed_resources = sql_array('SELECT resource AS value FROM collection_resource WHERE collection = ' . escape_check($ref) . ';');

    // First log this for each resource (in case it was done by mistake)
    foreach($removed_resources as $removed_resource_id)
        {
        collection_log($ref, 'r', $removed_resource_id, ' - Removed all resources from collection ID ' . $ref);
        }

    sql_query('DELETE FROM collection_resource WHERE collection = ' . escape_check($ref));
    sql_query("DELETE FROM external_access_keys WHERE collection='" . escape_check($ref) . "'");

    collection_log($ref, 'R', 0);
    }	

if (!function_exists("get_home_page_promoted_collections")){
function get_home_page_promoted_collections()
	{
	return sql_query("select collection.ref,collection.name,collection.home_page_publish,collection.home_page_text,collection.home_page_image,resource.thumb_height,resource.thumb_width, resource.resource_type, resource.file_extension from collection left outer join resource on collection.home_page_image=resource.ref where collection.public=1 and collection.home_page_publish=1 order by collection.ref desc");
	}
}


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

function edit_collection_external_access($key,$access=-1,$expires="",$group="",$sharepwd="")
	{
	global $userref,$usergroup, $scramble_key;
	if ($group=="" || !checkperm("x")) {$group=$usergroup;} # Default to sharing with the permission of the current usergroup if not specified OR no access to alternative group selection.
	if ($key==""){return false;}
	# Update the expiration and acccess
	sql_query("update external_access_keys set access='$access', expires=" . (($expires=="")?"null":"'" . escape_check($expires) . "'") . ",date=now(),usergroup='$group'" . (($sharepwd != "(unchanged)") ? ", password_hash='" . (($sharepwd == "") ? "" : hash('sha256', $key . $sharepwd . $scramble_key)) . "'" : "") . " where access_key='$key'");
	hook("edit_collection_external_access","",array($key,$access,$expires,$group,$sharepwd));
	return true;
	}
	
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
		$hidden_collections=explode(",",sql_value("select hidden_collections from user where ref='" . escape_check($user) . "'",""));
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
	sql_query("update user set hidden_collections ='" . implode(",",$hidden_collections) . "' where ref='" . escape_check($user) . "'");
	}
	
function get_session_collections($rs_session,$userref="",$create=false)
	{
	$extrasql="";
	if($userref!="")
		{
		$extrasql="and user='" . escape_check($userref) ."'";	
		}
	$collectionrefs=sql_array("select ref value from collection where session_id='" . escape_check($rs_session) . "' " . $extrasql,"");
	if(count($collectionrefs)<1 && $create)
		{
		$collectionrefs[0]=create_collection($userref,"My Collection",0,1); # Do not translate this string!	
		}		
	return $collectionrefs;	
	}

function update_collection_user($collection,$newuser)
	{	
	if (!collection_writeable($collection))
		{debug("FAILED TO CHANGE COLLECTION USER " . $collection);return false;}
		
	sql_query("UPDATE collection SET user='" . escape_check($newuser) . "' WHERE ref='" . escape_check($collection) . "'");  
	return true;	
	}

if(!function_exists("compile_collection_actions")){	
function compile_collection_actions(array $collection_data, $top_actions, $resource_data=array())
    {
    global $baseurl_short, $lang, $k, $userrequestmode, $zipcommand, $collection_download, $use_zip_extension, $archiver_path,
           $manage_collections_contact_sheet_link, $manage_collections_share_link, $allow_share,
           $manage_collections_remove_link, $userref, $collection_purge, $show_edit_all_link, $result,
           $edit_all_checkperms, $preview_all, $order_by, $sort, $archive, $contact_sheet_link_on_collection_bar,
           $show_searchitemsdiskusage, $emptycollection, $remove_resources_link_on_collection_bar, $count_result,
           $download_usage, $home_dash, $top_nav_upload_type, $pagename, $offset, $col_order_by, $find, $default_sort,
           $default_collection_sort, $starsearch, $restricted_share, $hidden_collections, $internal_share_access, $search,
           $usercollection, $disable_geocoding, $geo_locate_collection, $collection_download_settings, $contact_sheet,
           $allow_resource_deletion, $pagename,$upload_then_edit, $enable_related_resources,$list;
               
	#This is to properly render the actions drop down in the themes page	
	if ( isset($collection_data['ref']) && $pagename!="collections" )
		{
		$count_result = count(get_collection_resources($collection_data['ref']));
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
	
    
    // View all resources
    if(
        !$top_actions // View all resources makes sense only from collection bar context
        && (
            ($k=="" || $internal_share_access)
            && (isset($collection_data["c"]) && $collection_data["c"] > 0)
            || (is_array($result) && count($result) > 0)
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
    if($pagename == 'collection_manage') 
        {
        $min_access = collection_min_access($collection_data['ref']);
        }
    else
        {
        $min_access = collection_min_access($result);
        }

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
    if(
        (
            (checkperm('c') || checkperm('d'))
            && $collection_data['savedsearch'] == 0
            && (
                    $userref == $collection_data['user']
                    || $collection_data['allow_changes'] == 1
                    || checkperm('h')
                )
        )
        && ($k == '' || $internal_share_access))
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
     if(0 < $count_result && ($k=="" || $internal_share_access) && isset($emptycollection) && $remove_resources_link_on_collection_bar && collection_writeable($collection_data['ref']))
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
            // Manage My Collections
            $data_attribute['url'] = $baseurl_short . 'pages/collection_manage.php';
            $options[$o]['value']='manage_collections';
            $options[$o]['label']=$lang['managemycollections'];
            $options[$o]['data_attr']=$data_attribute;
            $options[$o]['category'] = ACTIONGROUP_COLLECTION;
            $options[$o]['order_by'] = 60;
            $o++;

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
        && ($pagename == 'themes' || $pagename === 'collection_manage' || $pagename === 'resource_collection_list' || $top_actions)
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
    if(!checkperm('b') && collection_readable($collection_data['ref']) && ($top_actions || (is_array($list) && count($list) > 1)))
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
    if((($userref == $collection_data['user']) || (checkperm('h')))  && ($k == '' || $internal_share_access)) 
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
    if(0 < $count_result && ($k=="" || $internal_share_access) && $manage_collections_share_link && $allow_share && (checkperm('v') || checkperm ('g') || (collection_min_access($collection_data['ref'])<=1 && $restricted_share))) 
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/collection_share.php",$urlparams);
        $options[$o]['value']='share_collection';
        $options[$o]['label']=$lang['share'];
        $options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_SHARE;
        $options[$o]['order_by']  = 140;
        $o++;
        }
        
    // Home_dash is on, AND NOT Anonymous use, AND (Dash tile user (NOT with a managed dash) || Dash Tile Admin)
    if(!$top_actions && $home_dash && ($k == '' || $internal_share_access) && checkPermission_dashcreate())
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
    if(checkperm("h") && ($k == '' || $internal_share_access))
        {
        $data_attribute['url'] = generateURL($baseurl_short . "pages/collection_set_category.php",$urlparams);
        $options[$o]['value']='collection_set_category';
        $options[$o]['label']=$lang['collection_set_theme_category'];
        $options[$o]['data_attr']=$data_attribute;
        $options[$o]['category'] = ACTIONGROUP_SHARE;
        $options[$o]['order_by']  = 160;
        $o++;
        }
		
    // Request all
    if($count_result > 0 && ($k == '' || $internal_share_access))
        {
		# Ability to request a whole collection (only if user has restricted access to any of these resources)
		if($pagename == 'collection_manage') 
			{
			$min_access = collection_min_access($collection_data['ref']);
			}
		else
		    {
			$min_access = collection_min_access($result);
			}
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
    if(($k=="" || $internal_share_access) && !$top_actions && $show_searchitemsdiskusage && 0 < $count_result) 
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

		// Hide Collection
		$user_mycollection=sql_value("select ref value from collection where user='" . escape_check($userref) . "' and name='My Collection' order by ref limit 1","");
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
		
		$options[$o]['value'] = 'hide_collection';
		$options[$o]['label'] = $lang['hide_collection'];
		$options[$o]['extra_tag_attributes']=$extra_tag_attributes;	
        $options[$o]['category']  = ACTIONGROUP_ADVANCED;
        $options[$o]['order_by']  = 270;
		$o++;
        }
        
    
    // Relate all resources
    if($enable_related_resources) 
        {
        $options[$o]['value'] = 'relate_all';
        $options[$o]['label'] = $lang['relateallresources'];
        $options[$o]['data_attr']=$data_attribute;
        $options[$o]['category']  = ACTIONGROUP_ADVANCED;
        $options[$o]['order_by']  = 280;
        $o++;
        }

    // Add extra collection actions and manipulate existing actions through plugins
    $modified_options = hook('render_actions_add_collection_option', '', array($top_actions,$options,$collection_data));
    if(is_array($modified_options) && !empty($modified_options))
		{
        $options=$modified_options;
        }

    return $options;
    }
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
* Show the new featured collection form.
*
* @param array $themearray array of theme levels at which featured collection will be created 
* 
* @return void
*/
function new_featured_collection_form(array $themearray = array())
    {
    global $lang;

    if(!checkperm('h'))
        {
        http_response_code(401);
        exit($lang['error-permissiondenied']);
        }

    $themes_count = count($themearray);
    ?>
    <div class="BasicsBox">
        <h1><?php echo $lang["createnewcollection"] ?></h1>
        <form id="new_collection_form"
              name="new_collection_form"
              class="modalform"
              method="POST"
              action="<?php echo $_SERVER['PHP_SELF'] ?>"
              onsubmit="return CentralSpacePost(this, true);">
            <?php generateFormToken("new_collection_form"); ?>
            <div class="Question">
                <label for="collectionname" ><?php echo $lang["collectionname"] ?></label>
                <input type="text" name="collectionname" required="true"></input>
                <div class="clearleft"></div>
            </div>

        <?php
        if(0 < $themes_count)
            {
            ?>
            <div class="Question">
                <label for="location" ></label>
                <div>
                    <input type="radio"
                           name="location" 
                           value="root" 
                           onclick="jQuery('#theme_category_name').slideUp();jQuery('#category_name_input').prop('required',false);"
                           checked
                           ><?php echo "&nbsp;" . $lang["create_new_here"]; ?></input>
                </div>
                <label for="location" ></label>
                <div>
                    <input type="radio"
                           name="location"
                           value="subfolder"
                           onclick="jQuery('#theme_category_name').slideDown();jQuery('#category_name_input').prop('required',true);"
                           ><?php echo "&nbsp;" . $lang["create_new_below"]; ?></input>
                </div>
                <div class="clearleft"></div>
            </div>
            <?php
            }
            ?>
            <div class="Question" id="theme_category_name" <?php if($themes_count > 0) {?>style="display:none;" <?php }?></div>
                <label for="category_name" ><?php echo $lang["themecategory"] ?></label>
                <input type="text" name="category_name" id="category_name_input" <?php if($themes_count == 0) {?>required="true" <?php }?>></input>
                <div class="clearleft"></div>
            </div>
        <?php
        for($n = 0; $n < $themes_count; $n++)
            {
            echo "<input type='hidden' name='theme" . ($n > 0 ? $n + 1 : "") . "' value='" . htmlspecialchars($themearray[$n], ENT_QUOTES) . "'></input>";
            }

        // Root level does not allow collections so the only option for the user is to just create a featured collection
        // category level
        if(0 === $themes_count)
            {
            ?>
            <input type="hidden" name="location" value="subfolder">
            <?php
            }
            ?>
            <input type='hidden' name='create' value='true'></input>
            <div class="QuestionSubmit" >
                <label></label>
                <input type="submit" name="create" value="<?php echo $lang["create"] ?>"></input>
                <div class="clearleft"></div>
            </div>
        </form>
    </div>
    <?php

    return;
	}
    
/**
* Obtain details of the last resource edited in the given collection.
*
* @param int $collection    Collection ID
*
* @return array | false     Array containing details of last edit (resource ID, timestamp and username of user who performed edit)
*/    
function get_last_resource_edit($collection)
    {
    if(!is_numeric($collection))
        {
        return false;
        }
    $plugin_last_resource_edit=hook('override_last_resource_edit');
    if($plugin_last_resource_edit===true){
    	return false;
    }
    $lastmodified  = sql_query("SELECT r.ref, r.modified FROM collection_resource cr LEFT JOIN resource r ON cr.resource=r.ref WHERE cr.collection='" . $collection . "' ORDER BY r.modified DESC");
    $lastuserdetails = sql_query("SELECT u.username, u.fullname, rl.date FROM resource_log rl LEFT JOIN user u on u.ref=rl.user WHERE rl.resource ='" . $lastmodified[0]["ref"] . "' AND rl.type='e'");
    if(count($lastuserdetails) == 0)
        {
        return false;
        }
        
    $timestamp = max($lastuserdetails[0]["date"],$lastmodified[0]["modified"]);
        
    $lastusername = (trim($lastuserdetails[0]["fullname"]) != "") ? $lastuserdetails[0]["fullname"] : $lastuserdetails[0]["username"];
    return array("ref" => $lastmodified[0]["ref"],"time" => $timestamp, "user" => $lastusername);
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
    for($n=1;$n <= $levels;$n++)
        {
        $themeindex = ($n == 1 ? "" : $n);
        $themename = getval("theme$themeindex","");
        if($themename != "")
            {
            $themes[] = $themename;
            }
        else
            {
            break;    
            }
        }           
    return $themes;
    }

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

function collection_download_use_original_filenames_when_downloading(&$filename, $ref, $collection_download_tar, &$filenames)
    {
    if(trim($filename) === '')
        {
        return;
        }

    global $pextension, $usesize, $subbed_original, $prefix_resource_id_to_filename, $prefix_filename_string, $server_charset,
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

    $fs=explode("/",$filename);$filename=$fs[count($fs)-1];

    # Convert $filename to the charset used on the server.
    if (!isset($server_charset)) {$to_charset = 'UTF-8';}
    else
        {
        if ($server_charset!="") {$to_charset = $server_charset;}
        else {$to_charset = 'UTF-8';}
        }
    $filename = mb_convert_encoding($filename, $to_charset, 'UTF-8');
    
    // check if a file has already been processed with this name
    if(in_array($filename, $filenames))
        {
        $path_parts = pathinfo($filename);
        if(isset($path_parts['extension']) && isset($path_parts['filename']))
            {
            $filename_ext = $path_parts['extension'];
            $filename_wo  = $path_parts['filename'];

            // Run through function to guarantee unique filename
            $filename = makeFilenameUnique($filenames, $filename_wo, $lang["_dupe"], $filename_ext);
            }
        }
    
    // Add the filename to the array so it can be checked in the next loop
    $filenames[] = $filename;

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

function collection_download_process_text_file($ref, $collection, $filename)
    {
    global $lang, $zipped_collection_textfile, $includetext, $size, $subbed_original, $k, $text, $sizetext;

    #Add resource data/collection_resource data to text file
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
            $fields = get_resource_field_data($ref, false, true, -1, true);
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

function update_zip_progress_file($note)
    {
    global $progress_file;
    $fp = fopen($progress_file, 'w');       
    $filedata=$note;
    fwrite($fp, $filedata);
    fclose($fp);
    }

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

            $metadata = get_resource_field_data($result[$n]['ref'], false, true, -1, '' != $k);

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
    # Append summary notes about the completeness of the package, write the text file, add to archive, and schedule for deletion
    if($zipped_collection_textfile == true && $includetext == "true")
        {
        $qty_sizes = count($available_sizes[$size]);
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

function collection_download_process_csv_metadata_file(array $result, $id, $collection, $collection_download_tar, $use_zip_extension, &$zip, &$path, array &$deletion_array)
    {
    // Include the CSV file with the metadata of the resources found in this collection
    $csv_file    = get_temp_dir(false, $id) . '/Col-' . $collection . '-metadata-export.csv';
    $csv_fh      = fopen($csv_file, 'w') OR die("can't open file");
    $csv_content = generateResourcesMetadataCSV($result);
    fwrite($csv_fh, $csv_content);
    fclose($csv_fh);

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

function collection_download_process_command_to_file($use_zip_extension, $collection_download_tar, $id, $collection, $size, &$path)
    {
    global $config_windows, $cmdfile;

    # Write command parameters to file.
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


function collection_cleanup_inaccessible_resources($collection)
    {
    global $userref;

    # Delete any resources from collection moved out of users archive status permissions by other users
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