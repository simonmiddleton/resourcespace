<?php




# User functions
# Functions to create, edit and generally deal with user accounts

/**
* Validate user - check we have a valid user based on SQL criteria e.g. session that is passed in as $user_select_sql
* Will always return false if matches criteria but the user account is not approved or has expired
*
* $user_select_sql example u.session=$variable. 
* Joins to usergroup table as g  which can be used in criteria
*
* @param	string	$user_select_sql		SQL to check - usually session hash e.g. (u.session=$variable) 
* @param 	boolean	$getuserdata			default true. Return user data as required by authenticate.php
* 
* @return boolean|array
*/
function validate_user($user_select_sql, $getuserdata=true)
    {
    if('' == $user_select_sql)
        {
        return false;
        }

    $full_user_select_sql = "
        approved = 1
        AND (
                account_expires IS NULL 
                OR account_expires = '0000-00-00 00:00:00' 
                OR account_expires > now()
            ) "
        . ((strtoupper(trim(substr($user_select_sql, 0, 4))) == 'AND') ? ' ' : ' AND ')
        . $user_select_sql;

    if($getuserdata)
        {
        $userdata = sql_query(
            "   SELECT u.ref,
                       u.username,
                       u.origin,
                       if(find_in_set('permissions',g.inherit_flags) AND pg.permissions IS NOT NULL,pg.permissions,g.permissions) permissions,
                       g.parent,
                       u.usergroup,
                       u.current_collection,
					   (select count(*) from collection where ref=u.current_collection) as current_collection_valid,
                       u.last_active,
                       timestampdiff(second, u.last_active, now()) AS idle_seconds,
                       u.email,
                       u.password,
                       u.fullname,
                       g.search_filter,
                       g.edit_filter,
                       g.ip_restrict ip_restrict_group,
                       g.name groupname,
                       u.ip_restrict ip_restrict_user,
                       u.search_filter_override,
                       u.search_filter_o_id,
                       g.resource_defaults,
                       u.password_last_change,
                       if(find_in_set('config_options',g.inherit_flags) AND pg.config_options IS NOT NULL,pg.config_options,g.config_options) config_options,
                       g.request_mode,
                       g.derestrict_filter,
                       u.hidden_collections,
                       u.accepted_terms,
                       u.session,
                       g.search_filter_id,
                       g.download_limit,
                       g.download_log_days,
                       g.edit_filter_id,
                       g.derestrict_filter_id
                  FROM user AS u
             LEFT JOIN usergroup AS g on u.usergroup = g.ref
			 LEFT JOIN usergroup AS pg ON g.parent=pg.ref
                 WHERE {$full_user_select_sql}"
        );

        return $userdata;
        }
    else
        {
        $validuser = sql_value(
            "      SELECT u.ref AS `value`
                     FROM user AS u 
                LEFT JOIN usergroup g ON u.usergroup = g.ref
                    WHERE {$full_user_select_sql}"
            ,
            ''
        );

        if('' != $validuser)
            {
            return true;
            }
        }

    return false;
    }


/**
*
* Given an array of user data loaded from the user table, set up all necessary global variables for this user
* including permissions, current collection, config overrides and so on.
* 
* @param  array  $userdata  Array of user data obtained by validate_user() from user/usergroup tables
* 
* @return boolean           success/failure flag - used for example to prevent certain users from making API calls
*/
function setup_user($userdata)
	{
    global $userpermissions, $usergroup, $usergroupname, $usergroupparent, $useremail, $userpassword, $userfullname, 
           $ip_restrict_group, $ip_restrict_user, $rs_session, $global_permissions, $userref, $username, $useracceptedterms,
           $anonymous_user_session_collection, $global_permissions_mask, $user_preferences, $userrequestmode,
           $usersearchfilter, $usereditfilter, $userderestrictfilter, $hidden_collections, $userresourcedefaults,
           $userrequestmode, $request_adds_to_collection, $usercollection, $lang, $validcollection, $userpreferences,
           $userorigin, $actions_enable, $actions_permissions, $actions_on, $usersession, $anonymous_login, $resource_created_by_filter,
           $user_dl_limit,$user_dl_days, $search_filter_nodes, $USER_SELECTION_COLLECTION;
		
	# Hook to modify user permissions
	if (hook("userpermissions")){$userdata["permissions"]=hook("userpermissions");} 

    $userref           = $userdata['ref'];
    $username          = $userdata['username'];
    $useracceptedterms = $userdata['accepted_terms'];
	
	# Create userpermissions array for checkperm() function
	$userpermissions=array_diff(array_merge(explode(",",trim($global_permissions)),explode(",",trim($userdata["permissions"]))),explode(",",trim($global_permissions_mask))); 
	$userpermissions=array_values($userpermissions);# Resequence array as the above array_diff() causes out of step keys.
	
	$actions_on=$actions_enable;
	# Enable actions functionality if based on user permissions
	if(!$actions_enable && count($actions_permissions)>0)
		{
		foreach($actions_permissions as $actions_permission)
			{
			if(in_array($actions_permission,$userpermissions))
                {
                $actions_on=true;
                break;
                }
			}
		}
	
	$usergroup=$userdata["usergroup"];
	$usergroupname=$userdata["groupname"];
    $usergroupparent=$userdata["parent"];
    $useremail=$userdata["email"];
    $userpassword=$userdata["password"];
    $userfullname=$userdata["fullname"];
    $userorigin=$userdata["origin"];
    $usersession = $userdata["session"];

    $ip_restrict_group=trim($userdata["ip_restrict_group"]);
    $ip_restrict_user=trim($userdata["ip_restrict_user"]);

    if(isset($anonymous_login) && $username==$anonymous_login && isset($rs_session) && !checkperm('b')) // This is only required if anonymous user has collection functionality
        {
        // Get all the collections that relate to this session
        $sessioncollections=get_session_collections($rs_session,$userref,true); 
        if($anonymous_user_session_collection)
            {
            // Just get the first one if more
            $usercollection=$sessioncollections[0];		
            $collection_allow_creation=false; // Hide all links that allow creation of new collections
            }
        else
            {
            // Unlikely scenario, but maybe we do allow anonymous users to change the selected collection for all other anonymous users
            $usercollection=$userdata["current_collection"];
            }
        }
    else
        {
        $usercollection=$userdata["current_collection"];
        // Check collection actually exists
        $validcollection=$userdata["current_collection_valid"];
        if($validcollection==0)
            {
            // Not a valid collection - switch to user's primary collection if there is one
            $usercollection=sql_value("select ref value from collection where user='$userref' and name like 'Default Collection%' order by created asc limit 1",0);
            if ($usercollection!=0)
                {
                # set this to be the user's current collection
                sql_query("update user set current_collection='$usercollection' where ref='$userref'");
                }
            }
        
        if ($usercollection==0 || !is_numeric($usercollection))
            {
            # Create a collection for this user
            # The collection name is translated when displayed!
            $usercollection=create_collection($userref,"Default Collection",0,1); # Do not translate this string!
            # set this to be the user's current collection
            sql_query("update user set current_collection='$usercollection' where ref='$userref'");
            }
        }
    
    $USER_SELECTION_COLLECTION = get_user_selection_collection($userref);
    if(is_null($USER_SELECTION_COLLECTION) && !(isset($anonymous_login) && $username == $anonymous_login))
        {
        // Don't create a new collection on every anonymous page load, it will be created when an action is performed
        $USER_SELECTION_COLLECTION = create_collection($userref, "Selection Collection (for batch edit)", 0, 1);
        update_collection_type($USER_SELECTION_COLLECTION, COLLECTION_TYPE_SELECTION);
        }

    $newfilter = false;
    if ($search_filter_nodes)
        {
        if(isset($userdata["search_filter_o_id"]) && is_numeric($userdata["search_filter_o_id"]) && $userdata['search_filter_o_id'] > 0)
            {
            // User search filter override
            $usersearchfilter = $userdata["search_filter_o_id"];
            $newfilter = true;
            }
        elseif(isset($userdata["search_filter_id"]) && is_numeric($userdata["search_filter_id"]) && $userdata['search_filter_id'] > 0)
            {
            // Group search filter
            $usersearchfilter = $userdata["search_filter_id"];
            $newfilter = true;
            }
        }
        
    if(!$newfilter)
        {
        // Old style search filter that hasn't been migrated
        $usersearchfilter=isset($userdata["search_filter_override"]) && $userdata["search_filter_override"]!='' ? $userdata["search_filter_override"] : $userdata["search_filter"];
        }
            
    $usereditfilter         = ($search_filter_nodes && isset($userdata["edit_filter_id"]) && is_numeric($userdata["edit_filter_id"]) && $userdata['edit_filter_id'] > 0) ? $userdata['edit_filter_id'] : $userdata["edit_filter"];
    $userderestrictfilter   = ($search_filter_nodes && isset($userdata["derestrict_filter_id"]) && is_numeric($userdata["derestrict_filter_id"]) && $userdata['derestrict_filter_id'] > 0) ? $userdata['derestrict_filter_id'] : $userdata["derestrict_filter"];;

    $hidden_collections=explode(",",$userdata["hidden_collections"]);
    $userresourcedefaults=$userdata["resource_defaults"];
    $userrequestmode=trim($userdata["request_mode"]);
    $user_dl_limit=trim($userdata["download_limit"]);
    $user_dl_days=trim($userdata["download_log_days"]);

    if((int)$user_dl_limit > 0)
        {
        // API cannot be used by these users as would open up opportunities to bypass limits
        if(defined("API_CALL"))
            {
            return false;
            }
        }

    $userpreferences = ($user_preferences) ? sql_query("SELECT user, `value` AS colour_theme FROM user_preferences WHERE user = '" . escape_check($userref) . "' AND parameter = 'colour_theme';","preferences") : FALSE;
    $userpreferences = ($userpreferences && isset($userpreferences[0])) ? $userpreferences[0]: FALSE;

    # Some alternative language choices for basket mode / e-commerce
    if ($userrequestmode==2 || $userrequestmode==3)
        {
        $lang["addtocollection"]=$lang["addtobasket"];
        $lang["action-addtocollection"]=$lang["addtobasket"];
        $lang["addtocurrentcollection"]=$lang["addtobasket"];
        $lang["requestaddedtocollection"]=$lang["buyitemaddedtocollection"];
        $lang["action-request"]=$lang["addtobasket"];
        $lang["managemycollections"]=$lang["viewpurchases"];
        $lang["mycollection"]=$lang["yourbasket"];
        $lang["action-removefromcollection"]=$lang["removefrombasket"];
        $lang["total-collections-0"] = $lang["total-orders-0"];
        $lang["total-collections-1"] = $lang["total-orders-1"];
        $lang["total-collections-2"] = $lang["total-orders-2"];
        
        # The request button (renamed "Buy" by the line above) should always add the item to the current collection.
        $request_adds_to_collection=true;
        }        

    # Apply config override options
    $config_options=trim($userdata["config_options"]);
    if ($config_options!="")
        {
        // We need to get all globals as we don't know what may be referenced here
        extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
        eval($config_options);
        }
    return true;
    }
    

/**
 * Returns a user list. Group or search term is optional. The standard user group names are translated using $lang. Custom user group names are i18n translated.
 *
 * @param  integer $group   If set, a user group to limit the results
 * @param  string $find Search string to filter returned results
 * @param  string $order_by
 * @param  boolean $usepermissions
 * @param  integer $fetchrows
 * @param  string $approvalstate
 * @param  boolean $returnsql
 * @param  string $selectcolumns
 * @param  boolean $selectcolumns    Denotes $find must be an exact username
 * @return array  Matching user records 
 */
function get_users($group=0,$find="",$order_by="u.username",$usepermissions=false,$fetchrows=-1,$approvalstate="",$returnsql=false, $selectcolumns="",$exact_username_match=false)
    {
    global $usergroup, $U_perm_strict;

    $sql = "";
    $find=escape_check(strtolower($find));
    if ($group != 0 && (string)(int)$group == (string)$group) {$sql = "where usergroup IN ($group)";}
    if ($exact_username_match)
        {
        # $find is an exact username
        if ($sql=="") {$sql = "where ";} else {$sql.= " and ";}
        $sql .= "LOWER(username)='$find'";
        }
    else    
        {
        if (strlen($find)>1)
            {
            if ($sql=="") {$sql = "where ";} else {$sql.= " and ";}
            $sql .= "(LOWER(username) like '%$find%' or LOWER(fullname) like '%$find%' or LOWER(email) like '%$find%' or LOWER(comments) like '%$find%')";      
            }
            if (strlen($find)==1)
            {
            if ($sql=="") {$sql = "where ";} else {$sql.= " and ";}
            $sql .= "LOWER(username) like '$find%'";
            }
        }
    if ($usepermissions && (checkperm('E') || (checkperm('U') && !$U_perm_strict)))
        {
        # Only return users in children groups to the user's group
        if ($sql=="") {$sql = "where ";} else {$sql.= " and ";}
        $sql.= "find_in_set('" . $usergroup . "',g.parent) ";
        $sql.= hook("getuseradditionalsql");
        }

    if (is_numeric($approvalstate))
        {
        if ($sql=="") {$sql = "where ";} else {$sql.= " and ";}
        $sql .= "u.approved='$approvalstate'";
        }

    // Return users in both user's user group and children groups
    if ($usepermissions && checkperm('U') && !$U_perm_strict) {
        $sql .= sprintf('
                %1$s (g.ref = "%2$s" OR find_in_set("%2$s", g.parent))
            ',
            ($sql == '') ? 'WHERE' : ' AND',
            $usergroup
        );
    }
    $select=($selectcolumns!="")?$selectcolumns:"u.ref, u.username,u.approved,u.created, u.*, g.name groupname,g.ref groupref,g.parent groupparent";
    $query = "SELECT " . $select . " from user u left outer join usergroup g on u.usergroup=g.ref $sql order by $order_by";
    # Executes query.
    if($returnsql){return $query;}
    $r = sql_query($query, false, $fetchrows);

    # Translates group names in the newly created array.
    for ($n = 0;$n<count($r);$n++)
        {
        if (strpos($select,"groupname") === false || !is_array($r[$n])) {break;} # The padded rows can't be and don't need to be translated.
        $r[$n]["groupname"] = lang_or_i18n_get_translated($r[$n]["groupname"], "usergroup-");
        }

    return $r;
    }   

/**
 * Returns all the users who have the permission $permission.
 * The standard user group names are translated using $lang. Custom user group names are i18n translated. 
 *
 * @param  string $permission The permission code to search for
 * @return array    Matching user records
 */
function get_users_with_permission($permission)
    {
    # First find all matching groups.
    $groups = sql_query("SELECT ref,permissions FROM usergroup");
    $matched = array();
    for ($n = 0;$n<count($groups);$n++) {
        $perms = trim_array(explode(",",$groups[$n]["permissions"]));
        if (in_array($permission,$perms)) {$matched[] = $groups[$n]["ref"];}
    }
    # Executes query.
    $r = sql_query("SELECT u.*,g.name groupname,g.ref groupref,g.parent groupparent FROM user u LEFT OUTER JOIN usergroup g ON u.usergroup=g.ref WHERE (g.ref IN ('" . join("','",$matched) . "') OR (find_in_set('permissions',g.inherit_flags)>0 AND g.parent IN ('" . join("','",$matched) . "'))) ORDER BY username",false);

    # Translates group names in the newly created array.
    $return = array();
    for ($n = 0;$n<count($r);$n++) {
        $r[$n]["groupname"] = lang_or_i18n_get_translated($r[$n]["groupname"], "usergroup-");
        $return[] = $r[$n]; # Adds to return array.
    }

    return $return;
}

/**
 * Retrieve user records by e-mail address
 *
 * @param  string $email    The e-mail address to search for
 * @return array Matching user records
 */
function get_user_by_email($email)
{
    $r = sql_query("SELECT u.*,g.name groupname,g.ref groupref,g.parent groupparent FROM user u LEFT OUTER JOIN usergroup g ON u.usergroup=g.ref WHERE u.email LIKE '%$email%' ORDER BY username",false);

    # Translates group names in the newly created array.
    $return = array();
    for ($n = 0;$n<count($r);$n++) {
        $r[$n]["groupname"] = lang_or_i18n_get_translated($r[$n]["groupname"], "usergroup-");
        $return[] = $r[$n]; # Adds to return array.
    }

    return $return;
}

/**
 * Retrieve user ID by username
 *
 * @param  string $username The username to search for
 * @return mixed  The matching user ID or false if not found
 */
function get_user_by_username($username)
    {
    return sql_value("select ref value from user where username='" . escape_check($username) . "'",false);
    }

/**
 * Returns a list of user groups. The standard user groups are translated using $lang. Custom user groups are i18n translated.
 * Puts anything starting with 'General Staff Users' - in the English default names - at the top (e.g. General Staff).
 *
 * @param  boolean $usepermissions Use permissions (user access)
 * @param  string $find Search string
 * @param  boolean $id_name_pair_array  Return an array of ID->name instead of full records
 * @return array    Matching user group records
 */
function get_usergroups($usepermissions = false, $find = '', $id_name_pair_array = false)
    {
    # Creates a query, taking (if required) the permissions  into account.
    $sql = "";
    if ($usepermissions && checkperm("U")) {
        # Only return users in children groups to the user's group
        global $usergroup,$U_perm_strict;
        if ($sql=="") {$sql = "where ";} else {$sql.= " and ";}
        if ($U_perm_strict) {
            //$sql.= "(parent='$usergroup')";
            $sql.= "find_in_set('" . $usergroup . "',parent)";
        }
        else {
            //$sql.= "(ref='$usergroup' or parent='$usergroup')";
            $sql.= "(ref='$usergroup' or find_in_set('" . $usergroup . "',parent))";
        }
    }

    # Executes query.
    global $default_group;
    $r = sql_query("select *,inherit_flags, download_limit, download_log_days from usergroup $sql order by (ref='$default_group') desc,name");

    # Translates group names in the newly created array.
    $return = array();
    for ($n = 0;$n<count($r);$n++) {
        $r[$n]["name"] = lang_or_i18n_get_translated($r[$n]["name"], "usergroup-");
        $return[] = $r[$n]; # Adds to return array.
    }

    if (strlen($find)>0) {
        # Searches for groups with names which contains the string defined in $find.
        $initial_length = count($return);
        for ($n = 0;$n<$initial_length;$n++) {
            if (strpos(strtolower($return[$n]["name"]),strtolower($find))===false) {
                unset($return[$n]); # Removes this group.
            }
        }
        $return = array_values($return); # Reassigns the indices.
    }

    // Return only an array with ref => name pairs
    if($id_name_pair_array)
        {
        $return_id_name_array = array();

        foreach($return as $user_group)
            {
            $return_id_name_array[$user_group['ref']] = $user_group['name'];
            }

        return $return_id_name_array;
        }

    return $return;

}    

/**
 * Returns the user group corresponding to the $ref. A standard user group name is translated using $lang. A custom user group name is i18n translated.
 *
 * @param  integer $ref User group ID
 * @return mixed False if not found, or the user group record if found.
 */
function get_usergroup($ref)
    {
    $return = sql_query("SELECT ref,name,permissions,parent,search_filter,search_filter_id,edit_filter,ip_restrict,resource_defaults,config_options,welcome_message,request_mode,allow_registration_selection,derestrict_filter,group_specific_logo,inherit_flags, download_limit, download_log_days, edit_filter_id, derestrict_filter_id " . hook('get_usergroup_add_columns') . " FROM usergroup WHERE ref='$ref'");
    if (count($return)==0) {return false;}
    else {
        $return[0]["name"] = lang_or_i18n_get_translated($return[0]["name"], "usergroup-");
        $return[0]["inherit"]=explode(",",trim($return[0]["inherit_flags"]));
        return $return[0];
    }
}

/**
 * Return the user group record matching $ref
 *
 * @param  integer $ref
 * @return array
 */
function get_user($ref)
    {
    global $udata_cache;
        if (isset($udata_cache[$ref])){
          $return=$udata_cache[$ref];
        } else {
    $udata_cache[$ref]=sql_query("SELECT u.*, if(find_in_set('permissions',g.inherit_flags)>0 AND pg.permissions IS NOT NULL,pg.permissions,g.permissions) permissions, g.parent, g.search_filter, g.edit_filter, g.ip_restrict ip_restrict_group, g.name groupname, u.ip_restrict ip_restrict_user, u.search_filter_override,(select count(*) from collection where ref=u.current_collection) as current_collection_valid,u.search_filter_o_id, g.resource_defaults,if(find_in_set('config_options',g.inherit_flags)>0 AND pg.config_options IS NOT NULL,pg.config_options,g.config_options) config_options,g.request_mode, g.derestrict_filter, g.search_filter_id, g.download_limit, g.download_log_days, g.edit_filter_id, g.derestrict_filter_id FROM user u LEFT JOIN usergroup g ON u.usergroup=g.ref LEFT JOIN usergroup pg ON g.parent=pg.ref WHERE u.ref='" . escape_check($ref) . "'");
    }
    
    # Return a user's credentials.
    if (count($udata_cache[$ref])>0) {return $udata_cache[$ref][0];} else {return false;}
    }

/**
* Function used to update or delete a user.
* Note: data is taken from the submitted form
* 
* @param string $ref ID of the user
* 
* @return boolean|string
*/
function save_user($ref)
    {
    global $lang, $home_dash;

    $current_user_data = get_user($ref);

    // Save user details, data is taken from the submitted form.
    if('' != getval('deleteme', ''))
        {
        delete_profile_image($ref);
        sql_query("DELETE FROM user WHERE ref = '" . escape_check($ref) . "'");

        include_once dirname(__FILE__) ."/dash_functions.php";
        empty_user_dash($ref);

        log_activity("{$current_user_data['username']} ({$ref})", LOG_CODE_DELETED, null, 'user', null, $ref);

        return true;
        }
    else
        {
        // Get submitted values
        $username               = trim(getvalescaped('username', ''));
        $password               = trim(getvalescaped('password', ''));
        $fullname               = str_replace("\t", ' ', trim(getvalescaped('fullname', '')));
        $email                  = trim(getval('email', '')); //To be escaped on usage in DB
        $usergroup              = trim(getvalescaped('usergroup', ''));
        $ip_restrict            = trim(getvalescaped('ip_restrict', ''));
        $search_filter_override = trim(getvalescaped('search_filter_override', ''));
        $search_filter_o_id     = trim(getvalescaped('search_filter_o_id', 0, true));
        $comments               = trim(getvalescaped('comments', ''));
        $suggest                = getval('suggest', '');
        $emailresetlink         = getval('emailresetlink', '');
        $approved               = getval('approved', 0, true);

        # Username or e-mail address already exists?
        $c = sql_value("SELECT count(*) value FROM user WHERE ref <> '$ref' AND (username = '" . $username . "' OR email = '" . escape_check($email) . "')", 0);
        if($c > 0 && $email != '')
            {
            return false;
            }

        // Password checks:
        if($suggest != '' || ($password == '' && $emailresetlink != ''))
            {
            $password = make_password();
            }
        elseif($password != $lang['hidden'])    
            {
            $message = check_password($password);
            if($message !== true)
                {
                return $message;
                }
            }

        # Get valid expiry date
        $expires=getvalescaped("account_expires","");
        if($expires == "" || strtotime($expires)==false)
            {
            $expires = 'null';
            }
        else   
            {
            $expires = "'" . date("Y-m-d",strtotime($expires)) . "'";
            }

        $passsql = '';
        if($password != $lang['hidden'])
            {
            # Save password.
            if($suggest == '')
                {
                $password = hash('sha256', md5('RS' . $username . $password));
                }

            $passsql = ",password='" . $password . "',password_last_change=now()";
            }

        // Full name checks
        if('' == $fullname && '' == $suggest)
            {
            return $lang['setup-admin_fullname_error'];
            }

        /*Make sure IP restrict filter is a proper IP, otherwise make it blank
        Note: we do this check only when wildcards are not used*/
        if(false === strpos($ip_restrict, '*'))
            {
            $ip_restrict = (false === filter_var($ip_restrict, FILTER_VALIDATE_IP) ? '' : $ip_restrict);
            }

        $additional_sql = hook('additionaluserfieldssave');

        log_activity(null, LOG_CODE_EDITED, $username, 'user', 'username', $ref);
        log_activity(null, LOG_CODE_EDITED, $fullname, 'user', 'fullname', $ref);
        log_activity(null, LOG_CODE_EDITED, $email, 'user', 'email', $ref);

        if((isset($current_user_data['usergroup']) && '' != $current_user_data['usergroup']) && $current_user_data['usergroup'] != $usergroup)
            {
            log_activity(null, LOG_CODE_EDITED, $usergroup, 'user', 'usergroup', $ref);
            sql_query("DELETE FROM resource WHERE ref = '-{$ref}'");
            }

        log_activity(null, LOG_CODE_EDITED, $ip_restrict, 'user', 'ip_restrict', $ref, null, '');
        log_activity(null, LOG_CODE_EDITED, $search_filter_override, 'user', 'search_filter_override', $ref, null, '');
        log_activity(null, LOG_CODE_EDITED, $expires, 'user', 'account_expires', $ref);
        log_activity(null, LOG_CODE_EDITED, $comments, 'user', 'comments', $ref);
        log_activity(null, LOG_CODE_EDITED, $approved, 'user', 'approved', $ref);

        sql_query("update user set
        username='" . $username . "'" . $passsql . ",
        fullname='" . $fullname . "',
        email='" . escape_check($email) . "',
        usergroup='" . $usergroup . "',
        account_expires=$expires,
        ip_restrict='" . $ip_restrict . "',
        search_filter_override='" . $search_filter_override . "',
        search_filter_o_id='" . $search_filter_o_id . "',
        comments='" . $comments . "',
        approved='" . $approved . "' " . $additional_sql . " where ref='$ref'");
        }

        // Add user group dash tiles as soon as we've changed the user group
        if($home_dash)
            {
            // If user group has changed, remove all user dash tiles that were valid for the old user group
            if($current_user_data['usergroup'] != $usergroup)
                {
                sql_query("DELETE FROM user_dash_tile WHERE user = '{$ref}' AND dash_tile IN (SELECT dash_tile FROM usergroup_dash_tile WHERE usergroup = '{$current_user_data['usergroup']}')");

                include_once __DIR__ . '/dash_functions.php';
                build_usergroup_dash($usergroup, $ref);
                }
            }

    if($emailresetlink != '')
        {
        email_reset_link($email, true);
        }
        
    if(getval('approved', '')!='')
        {
        # Clear any user request messages
        message_remove_related(USER_REQUEST,$ref);
        }

    return true;
    }


/**
 * E-mail the user the welcome message on account creation.
 *
 * @param  string $email
 * @param  string $username
 * @param  string $password
 * @param  integer $usergroup
 * @return void
 */
function email_user_welcome($email,$username,$password,$usergroup)
    {
    global $applicationname,$email_from,$baseurl,$lang,$email_url_save_user;
    
    # Fetch any welcome message for this user group
    $welcome=sql_value("select welcome_message value from usergroup where ref='" . $usergroup . "'","");
    if (trim($welcome)!="") {$welcome.="\n\n";}

    $templatevars['welcome']  = i18n_get_translated($welcome);
    $templatevars['username'] = $username;

    if (trim($email_url_save_user)!="")
        {
        $templatevars['url']=$email_url_save_user;
        }
    else
        {
        $templatevars['url']=$baseurl;
        }
    $message=$templatevars['welcome'] . $lang["newlogindetails"] . "\n\n" . $lang["username"] . ": " . $templatevars['username'] . "\n" . $templatevars['url'];
            
    send_mail($email,$applicationname . ": " . $lang["youraccountdetails"],$message,"","","emaillogindetails",$templatevars);
    }

function email_reset_link($email,$newuser=false)
    {
    debug("password_reset - checking for email: " . $email);
    # Send a link to reset password
    global $password_brute_force_delay, $scramble_key;

    if($email == '')
        {
        return false;
        }

    $details = sql_query("SELECT ref, username, usergroup FROM user WHERE email LIKE '" . escape_check($email) . "' AND approved = 1 AND (account_expires IS NULL OR account_expires > now());");

    if(count($details) == 0)
        {
        sleep($password_brute_force_delay);
        return false;
        }

    $details = $details[0];

    global $applicationname, $email_from, $baseurl, $lang, $email_url_remind_user;

    $password_reset_url_key = create_password_reset_key($details['username']);        

    $templatevars['url'] = $baseurl . '/?rp=' . $details['ref'] . $password_reset_url_key;
        
    if($newuser)
        {
        $templatevars['username']=$details["username"];

        // Fetch any welcome message for this user group
        $welcome = sql_value('SELECT welcome_message AS value FROM usergroup WHERE ref = \'' . $details['usergroup'] . '\'', '');

        if(trim($welcome) != '')
            {
            $welcome .= "\n\n";
            }

        $templatevars['welcome']=i18n_get_translated($welcome);

        $message = $templatevars['welcome'] . $lang["newlogindetails"] . "\n\n" . $baseurl . "\n\n" . $lang["username"] . ": " . $templatevars['username'] . "\n\n" .  $lang["passwordnewemail"] . "\n" . $templatevars['url'];
        send_mail($email,$applicationname . ": " . $lang["newlogindetails"],$message,"","","passwordnewemailhtml",$templatevars);
        }
    else
        {
        $templatevars['username']=$details["username"];
        $message=$lang["username"] . ": " . $templatevars['username'];
        $message.="\n\n" . $lang["passwordresetemail"] . "\n\n" . $templatevars['url'];
        send_mail($email,$applicationname . ": " . $lang["resetpassword"],$message,"","","password_reset_email_html",$templatevars);
        }   
    
    return true;
    }

/**
 * Automatically creates a user account (which requires approval unless $auto_approve_accounts is true).
 *
 * @param  string $hash
 * @return boolean Success?
 */
function auto_create_user_account($hash="")
    {
    global $applicationname, $user_email, $baseurl, $email_notify, $lang, $user_account_auto_creation_usergroup, $registration_group_select, 
           $auto_approve_accounts, $auto_approve_domains, $customContents, $language, $home_dash,$defaultlanguage;

    # Work out which user group to set. Allow a hook to change this, if necessary.
    $altgroup=hook("auto_approve_account_switch_group");
    if ($altgroup!==false)
        {
        $usergroup=$altgroup;
        }
    else
        {
        $usergroup=$user_account_auto_creation_usergroup;
        }

    if ($registration_group_select)
        {
        $usergroup=getvalescaped("usergroup","",true);
        # Check this is a valid selectable usergroup (should always be valid unless this is a hack attempt)
        if (sql_value("select allow_registration_selection value from usergroup where ref='$usergroup'",0)!=1) {exit("Invalid user group selection");}
        }

    $newusername=escape_check(make_username(getval("name","")));

    // Check valid email
    if(!filter_var($user_email, FILTER_VALIDATE_EMAIL))
        {return $lang['setup-emailerr'];}
    
    #check if account already exists
    $check=sql_value("select email value from user where email = '" . escape_check($user_email) . "'","");
    if ($check!=""){return $lang["useremailalreadyexists"];}

    # Prepare to create the user.
    $email=trim(getvalescaped("email","")) ;
    $password=make_password();
    $password = hash('sha256', md5('RS' . $newusername . $password));

    # Work out if we should automatically approve this account based on $auto_approve_accounts or $auto_approve_domains
    $approve=false;
        
    # Block immediate reset
    $bypassemail=false;
        
    if ($auto_approve_accounts==true)
        {
        $approve=true;
        $bypassemail=true; // We can send user  direct to password reset page
        }
    elseif (count($auto_approve_domains)>0)
        {
        # Check e-mail domain.
        foreach ($auto_approve_domains as $domain=>$set_usergroup)
            {
            // If a group is not specified the variables don't get set correctly so we need to correct this
            if (is_numeric($domain)){$domain=$set_usergroup;$set_usergroup="";}
            if (substr(strtolower($email),strlen($email)-strlen($domain)-1)==("@" . strtolower($domain)))
                {
                # E-mail domain match.
                $approve=true;                                

                # If user group is supplied, set this
                if (is_numeric($set_usergroup)) {$usergroup=$set_usergroup;}
                }
            }
        }

    # Create the user
    sql_query("insert into user (username,password,fullname,email,usergroup,comments,approved,lang,unique_hash) values ('" . $newusername . "','" . $password . "','" . getvalescaped("name","") . "','" . $email . "','" . $usergroup . "','" . ( escape_check($customContents) . "\n" . getvalescaped("userrequestcomment","")  ) . "'," . (($approve)?1:0) . ",'$language'," . ($hash!=""?"'" . $hash . "'":"null") . ")");
    $new = sql_insert_id();

    // Create dash tiles for the new user
    if($home_dash)
        {
        include_once dirname(__FILE__) . '/dash_functions.php';

        create_new_user_dash($new);
        build_usergroup_dash($usergroup, $new);
        }

    global $user_registration_opt_in;
    if($user_registration_opt_in && getval("login_opt_in", "") == "yes")
        {
        log_activity($lang["user_registration_opt_in_message"], LOG_CODE_USER_OPT_IN, null, "user", null, null, null, null, $new, false);
        }

    hook("afteruserautocreated", "all",array("new"=>$new));
    global $anonymous_login;
    if(isset($anonymous_login))
        {
        global $rs_session;
        $rs_session=get_rs_session_id();
        if($rs_session!==false)
            {               
            # Copy any anonymous session collections to the new user account 
            global $username, $userref;

            if(is_array($anonymous_login) && array_key_exists($baseurl, $anonymous_login))
                {
                $anonymous_login = $anonymous_login[$baseurl];
                }

            $username=$anonymous_login;
            $userref=sql_value("SELECT ref value FROM user where username='$anonymous_login'","");
            $sessioncollections=get_session_collections($rs_session,$userref,false);
            if(count($sessioncollections)>0)
                {
                foreach($sessioncollections as $sessioncollection)
                    {
                    update_collection_user($sessioncollection,$new);
                    }
                sql_query("UPDATE user SET current_collection='$sessioncollection' WHERE ref='$new'");
                }
            }
        }
    if ($approve)
        {
        # Auto approving        
        if($bypassemail)
            {
            // No requirement to check anything else e.g. a valid email domain. We can take user direct to the password reset page to set the new account
            $password_reset_url_key=create_password_reset_key($newusername);
            redirect($baseurl . "?rp=" . $new . $password_reset_url_key);           
            exit();
            }
        else
            {
            email_reset_link($email, true);
            redirect($baseurl."/pages/done.php?text=user_request");
            exit();
            }           
        }
    else
        {
        # Not auto approving.
        # Build a message to send to an admin notifying of unapproved user (same as email_user_request(),
        # but also adds the new user name to the mail)
        
        $templatevars['name']=getval("name","");
        $templatevars['email']=getval("email","");
        $templatevars['userrequestcomment']=strip_tags(getval("userrequestcomment",""));
        $templatevars['userrequestcustom']=strip_tags($customContents);
        $templatevars['linktouser']="$baseurl?u=$new";

        // Need to global the usergroup so that we can find the appropriate admins
        global $usergroup;
        $approval_notify_users=get_notification_users("USER_ADMIN"); 
        $message_users=array();
        global $user_pref_user_management_notifications, $email_user_notifications;

        // get array of preferred languages for notify users
        $languages_approval_notify_users = array_unique(array_column($approval_notify_users, "lang"));
        // get array of language strings for selected languages
        $language_strings_all = get_languages_notify_users($languages_approval_notify_users);  
         
        foreach($approval_notify_users as $approval_notify_user)
            {
            get_config_option($approval_notify_user['ref'],'user_pref_user_management_notifications', $send_message, $user_pref_user_management_notifications);
            if(!$send_message){continue;} 
            
            get_config_option($approval_notify_user['ref'],'email_user_notifications', $send_email, $email_user_notifications); 
            
            // get preferred language for approval_notify_user
            $message_language = isset($approval_notify_user["lang"]) && $approval_notify_user["lang"] != "" ? $approval_notify_user["lang"] : $defaultlanguage;

            // get preferred language for approval_notify_user
            $lang_pref = $language_strings_all[$message_language];

            if($send_email && $approval_notify_user["email"]!="")
                {
                $message=$lang_pref["userrequestnotification1"] . "\n\n" . $lang_pref["name"] . ": " . $templatevars['name'] . "\n\n" . $lang_pref["email"] . ": " . $templatevars['email'] . "\n\n" . $lang_pref["comment"] . ": " . $templatevars['userrequestcomment'] . "\n\n" . $lang_pref["ipaddress"] . ": '" . $_SERVER["REMOTE_ADDR"] . "'\n\n" . $customContents . "\n\n" . $lang_pref["userrequestnotification3"] . "\n$baseurl?u=$new";
                send_mail($approval_notify_user["email"],$applicationname . ": " . $lang_pref["requestuserlogin"] . " - " . getval("name",""),$message,"",$user_email,"emailuserrequest",$templatevars,getval("name",""));
                }        
            else
                {
                $notificationmessage=$lang_pref["userrequestnotification1"] . "\n" . $lang_pref["name"] . ": " . $templatevars['name'] . "\n" . $lang_pref["email"] . ": " . $templatevars['email'] . "\n" . $lang_pref["comment"] . ": " . $templatevars['userrequestcomment'] . "\n" . $lang_pref["ipaddress"] . ": '" . $_SERVER["REMOTE_ADDR"] . "'\n" . $customContents . "\n" . $lang_pref["userrequestnotification3"];
                message_add($approval_notify_user["ref"],$notificationmessage,$templatevars['linktouser'],$new,MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,60 * 60 *24 * 30, USER_REQUEST,$new );
          
                }
            }

             // set language back to cookie setting
          $language = setLanguage();
          $langfile= dirname(__FILE__)."/../languages/" . safe_file_name($language) . ".php";
          if(file_exists($langfile))
              {
              include $langfile;
              }
      
        }

    return true;
    }


/**
* Email user request to admins
* 
* @return boolean
*/
function email_user_request()
    {
    // E-mails the submitted user request form to the team.
    global $applicationname, $user_email, $baseurl, $email_notify, $lang, $customContents, $account_email_exists_note,
           $account_request_send_confirmation_email_to_requester, $user_registration_opt_in,$defaultlanguage;

    // Get posted vars sanitized:
    $name               = strip_tags(getvalescaped('name', ''));
    $email              = strip_tags(getvalescaped('email', ''));
    $userrequestcomment = strip_tags(getvalescaped('userrequestcomment', ''));

    $user_registration_opt_in_message = "";
    if($user_registration_opt_in && getval("login_opt_in", "") == "yes")
        {
        $user_registration_opt_in_message .= "\n\n{$lang["user_registration_opt_in_message"]}";
        }

    
    $approval_notify_users = get_notification_users("USER_ADMIN"); 
    $message_users         = array();

    // get array of preferred languages for notify users
    $languages_approval_notify_users = array_unique(array_column($approval_notify_users, "lang"));
    // get array of language strings for selected languages
    $language_strings_all = get_languages_notify_users($languages_approval_notify_users);    

    foreach($approval_notify_users as $approval_notify_user)
        {
        get_config_option($approval_notify_user['ref'],'user_pref_user_management_notifications', $send_message);

        if(false == $send_message)
            {
            continue;
            }

        get_config_option($approval_notify_user['ref'],'email_user_notifications', $send_email);

        // get preferred language for approval_notify_user
        $message_language = isset($approval_notify_user["lang"]) && $approval_notify_user["lang"] != "" ? $approval_notify_user["lang"] : $defaultlanguage;

        // get preferred language for approval_notify_user
        $lang_pref = $language_strings_all[$message_language];

        if($send_email && '' != $approval_notify_user['email'])
            {
            // Build a message
            $message = ($account_email_exists_note ? $lang_pref['userrequestnotification1'] : $lang_pref["userrequestnotificationemailprotection1"]) . "\n\n{$lang_pref['name']}: {$name}\n\n{$lang_pref['email']}: {$email}{$user_registration_opt_in_message}\n\n{$lang_pref['comment']}: {$userrequestcomment}\n\n{$lang_pref['ipaddress']}: '{$_SERVER['REMOTE_ADDR']}'\n\n{$customContents}\n\n" . ($account_email_exists_note ? $lang_pref['userrequestnotification2'] : $lang_pref["userrequestnotificationemailprotection2"]) . "\n{$baseurl}";
       
            send_mail(
                $approval_notify_user['email'],
                "{$applicationname}: {$lang_pref['requestuserlogin']} - {$name}",
                $message,
                '',
                $user_email,
                '',
                '',
                $name);
            }
        else
            {
            $notificationmessage = ($account_email_exists_note ? $lang_pref['userrequestnotification1'] : $lang_pref["userrequestnotificationemailprotection1"]) . "\n" . $lang_pref["name"] . ": " . $name . "\n" . $lang_pref["email"] . ": " . $email . "\n" . $lang_pref["comment"] . ": " . $userrequestcomment . "\n" . $lang_pref["ipaddress"] . ": '" . $_SERVER["REMOTE_ADDR"] . "'\n" . escape_check($customContents) . "\n{$user_registration_opt_in_message}";

            message_add($approval_notify_user['ref'], $notificationmessage, '', 0, MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN, 60 * 60 * 24 * 30);
            }
        }
    // set language back to cookie setting
    $language = setLanguage();
    $langfile= dirname(__FILE__)."/../languages/" . safe_file_name($language) . ".php";
    if(file_exists($langfile))
        {
        include $langfile;
        }

    // Send a confirmation e-mail to requester
    if($account_request_send_confirmation_email_to_requester)
        {
        send_mail(
            $email,
            "{$applicationname}: {$lang['account_request_label']}",
            $lang['account_request_confirmation_email_to_requester']);
        }

    return true;
    }

/**
* Create a new user
* * 
* @param string $newuser  - username to create
* @param integer $usergroup  - optional usergroup to assign
* 
* @return boolean|integer  - id of new user or false if user already exists
*/
function new_user($newuser, $usergroup = 0)
    {
    global $lang,$home_dash;
    # Username already exists?
    $c=sql_value("select count(*) value from user where username='" . escape_check($newuser) . "'",0);
    if ($c>0) {return false;}
    
    $cols = array("username");
    $vals = array(escape_check($newuser));
    
    if($usergroup > 0)
        {
        $cols[] = "usergroup";
        $vals[] = (int)$usergroup;    
        }
        
    $sql = "INSERT INTO user (" . implode(",",$cols) . ") VALUES ('" . implode("','",$vals) . "')";
    sql_query($sql);
    
    $newref=sql_insert_id();
    
    #Create Default Dash for the new user
    if($home_dash)
        {
        include_once dirname(__FILE__)."/dash_functions.php";
        create_new_user_dash($newref);
        }
    
    # Create a collection for this user, the collection name is translated when displayed!
    $new=create_collection($newref,"Default Collection",0,1); # Do not translate this string!
    # set this to be the user's current collection
    sql_query("update user set current_collection='$new' where ref='$newref'");
    log_activity($lang["createuserwithusername"],LOG_CODE_CREATED,$newuser,'user','ref',$newref,null,'');
    
    return $newref;
    }



/**
 * Returns a list of active users
 *
 * @return array
 */
function get_active_users()
    {
    global $usergroup, $U_perm_strict;
    $sql = "where logged_in=1 and unix_timestamp(now())-unix_timestamp(last_active)<(3600*2)";
    if (checkperm("U") && $U_perm_strict)
        {
        $sql.= " and find_in_set('" . $usergroup . "',g.parent) ";
        }

    // Return users in both user's user group and children groups
    elseif (checkperm('U') && !$U_perm_strict)
        {
        $sql .= " and (g.ref = '" . $usergroup . "' OR find_in_set('" . $usergroup . "', g.parent))";
        }
    
    # Returns a list of all active users, i.e. users still logged on with a last-active time within the last 2 hours.
    return sql_query("select u.ref, u.username,round((unix_timestamp(now())-unix_timestamp(u.last_active))/60,0) t from user u left outer join usergroup g on u.usergroup=g.ref $sql order by t;");
    }

/**
 * Sets a new password for the current user.
 *
 * @param  string $password
 * @return mixed True if a success or a descriptive string if there's an issue.
 */
function change_password($password)
    {
    global $userref,$username,$lang,$userpassword, $password_reset_mode;

    # Check password
    $message=check_password($password);
    if ($message!==true) {return $message;}

    # Generate new password hash
    $password_hash=hash('sha256', md5("RS" . $username . $password));
    
    # Check password is not the same as the current
    if ($userpassword==$password_hash) {return $lang["password_matches_existing"];}
    
    sql_query("update user set password='$password_hash', password_reset_hash=NULL, login_tries=0, password_last_change=now() where ref='$userref' limit 1");
        return true;
    }

/**
 * Generate a password using the configured settings.
 *
 * @return string The generated password
 */
function make_password()
    {
    global $password_min_length, $password_min_alpha, $password_min_uppercase, $password_min_numeric, $password_min_special;

    $lowercase="abcdefghijklmnopqrstuvwxyz";
    $uppercase="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $alpha=$uppercase . $lowercase;
    $numeric="0123456789";
    $special="!@$%^&*().?";
    
    $password="";
    
    # Add alphanumerics
    for ($n=0;$n<$password_min_alpha;$n++)
        {
        $password.=substr($alpha,rand(0,strlen($alpha)-1),1);
        }
    
    # Add upper case
    for ($n=0;$n<$password_min_uppercase;$n++)
        {
        $password.=substr($uppercase,rand(0,strlen($uppercase)-1),1);
        }
    
    # Add numerics
    for ($n=0;$n<$password_min_numeric;$n++)
        {
        $password.=substr($numeric,rand(0,strlen($numeric)-1),1);
        }
    
    # Add special
    for ($n=0;$n<$password_min_special;$n++)
        {
        $password.=substr($special,rand(0,strlen($special)-1),1);
        }

    # Pad with lower case
    $padchars=$password_min_length-strlen($password);
    for ($n=0;$n<$padchars;$n++)
        {
        $password.=substr($lowercase,rand(0,strlen($lowercase)-1),1);
        }
        
    # Shuffle the password.
    $password=str_shuffle($password);
    
    # Check the password
    $check=check_password($password);
    if ($check!==true) {exit("Error: unable to automatically produce a password that met the criteria. Please check the password criteria in config.php. Generated password was '$password'. Error was: " . $check);}
    
    return $password;
    }

/**
 * Send a bulk e-mail using the bulk e-mail tool.
 *
 * @param  string $userlist
 * @param  string $subject
 * @param  string $text
 * @param  string $html
 * @param  integer $message_type
 * @param  string $url
 * @return string The empty string if all OK, a descriptive string if there's an issue.
 */
function bulk_mail($userlist,$subject,$text,$html=false,$message_type=MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL,$url="")
    {
    global $email_from,$lang,$applicationname;
    
    # Attempt to resolve all users in the string $userlist to user references.
    if (trim($userlist)=="") {return ($lang["mustspecifyoneuser"]);}
    $userlist=resolve_userlist_groups($userlist);
    $ulist=trim_array(explode(",",$userlist));

    $templatevars['text']=stripslashes(str_replace("\\r\\n","\n",$text));
    $body=$templatevars['text'];
    
    if ($message_type==MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL || $message_type==(MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL | MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN))
        {
        $emails = resolve_user_emails($ulist);

        if(0 === count($emails))
            {
            return $lang['email_error_user_list_not_valid'];
            }

        $emails = $emails['emails'];

        # Send an e-mail to each resolved user
        foreach($emails as $email)
            {
            if(filter_var($email, FILTER_VALIDATE_EMAIL))
                {
                send_mail($email,$subject,$body,$applicationname,$email_from,"emailbulk",$templatevars,$applicationname,"",$html);
                }
            }
        }
    if ($message_type==MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN || $message_type==(MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL | MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN))
        {
        $user_refs = array();
        foreach ($ulist as $user)
            {
            $user_ref = sql_value("SELECT ref AS value FROM user WHERE username='" . escape_check($user) . "'", false);
            if ($user_ref !== false)
                {
                array_push($user_refs,$user_ref);
                }
            }
        if($message_type==(MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL | MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN) && $html)
            {
            # strip the tags out
            $body=strip_tags($body);
            }
        message_add($user_refs,$body,$url,null,$message_type);
        }

    # Return an empty string (all OK).
    return "";
    }

/**
 * Returns a user action log for $user.
 * Standard field titles are translated using $lang.  Custom field titles are i18n translated.
 *
 * @param  integer $user
 * @param  integer $fetchrows How many rows to fetch?
 * @return array
 */
function get_user_log($user, $fetchrows=-1)
    {
    global $view_title_field;
    # Executes query.
    $r = sql_query("select r.ref resourceid,r.field".$view_title_field." resourcetitle,l.date,l.type,f.title,l.purchase_size,l.purchase_price, l.notes,l.diff from resource_log l left outer join resource r on l.resource=r.ref left outer join resource_type_field f on f.ref=l.resource_type_field where l.user='$user' order by l.date desc",false,$fetchrows);

    # Translates field titles in the newly created array.
    $return = array();
    for ($n = 0;$n<count($r);$n++) {
        if (is_array($r[$n])) {$r[$n]["title"] = lang_or_i18n_get_translated($r[$n]["title"], "fieldtitle-");}
        $return[] = $r[$n];
    }
    return $return;
    }


/**
 * Given a comma separated user list (from the user select include file) turn all Group: entries into fully resolved list of usernames.
 * Note that this function can't decode default groupnames containing special characters.
 *
 * @param  string $userlist
 * @return string The resolved list
 */
function resolve_userlist_groups($userlist)
    {
    global $lang;
    $ulist=explode(",",$userlist);
    $newlist="";
    for ($n=0;$n<count($ulist);$n++)
        {
        $u=trim($ulist[$n]);
        if (strpos($u,$lang["group"] . ": ")===0)
            {
            # Group entry, resolve

            # Find the translated groupname.
            $translated_groupname = trim(substr($u,strlen($lang["group"] . ": ")));
            # Search for corresponding $lang indices.
            $default_group = false;
            $langindices = array_keys($lang, $translated_groupname);
            if (count($langindices)>0)
                {
                foreach ($langindices as $langindex)
                    {
                    # Check if it is a default group
                    if (strstr($langindex, "usergroup-")!==false)
                        {
                        # Decode the groupname by using the code from lang_or_i18n_get_translated the other way around (it could be possible that someone have renamed the English groupnames in the language file).
                        $untranslated_groupname = trim(substr($langindex,strlen("usergroup-")));
                        $untranslated_groupname = str_replace(array("_", "and"), array(" "), $untranslated_groupname);
                        $groupref = sql_value("select ref as value from usergroup where lower(name)='$untranslated_groupname'",false);
                        if ($groupref!==false)
                            {
                            $default_group = true;
                            break;
                            }
                        }
                    }
                }
            if ($default_group==false)
                {
                # Custom group
                # Decode the groupname
                $untranslated_groups = sql_query("select ref, name from usergroup");
                foreach ($untranslated_groups as $group)
                    {
                    if (i18n_get_translated($group['name'])==$translated_groupname)
                        {
                        $groupref = $group['ref'];
                        break;
                        }
                    }
                }

            # Find and add the users.
            $users = sql_array("SELECT username AS `value` FROM user WHERE usergroup = '{$groupref}'");
            if ($newlist!="") {$newlist.=",";}
            $newlist.=join(",",$users);
            }
        else
            {
            # Username, just add as-is
            if ($newlist!="") {$newlist.=",";}
            $newlist.=$u;
            }
        }
    return $newlist;
    }

/**
 * Given a comma separated user list (from the user select include file) turn all Group: entries into fully resolved list of usernames.
 * Note that this function can't decode default groupnames containing special characters.
 *
 * @param  string $userlist
 * @param  boolean $return_usernames
 * @return string The resolved list
 */
function resolve_userlist_groups_smart($userlist,$return_usernames=false)
    {
    global $lang;
    $ulist=explode(",",$userlist);
    $newlist="";
    for ($n=0;$n<count($ulist);$n++)
        {
        $u=trim($ulist[$n]);
        if (strpos($u,$lang["groupsmart"] . ": ")===0)
            {
            # Group entry, resolve

            # Find the translated groupname.
            $translated_groupname = trim(substr($u,strlen($lang["groupsmart"] . ": ")));
            # Search for corresponding $lang indices.
            $default_group = false;
            $langindices = array_keys($lang, $translated_groupname);
            if (count($langindices)>0);
                { 
                foreach ($langindices as $langindex)
                    {
                    # Check if it is a default group
                    if (strstr($langindex, "usergroup-")!==false)
                        {
                        # Decode the groupname by using the code from lang_or_i18n_get_translated the other way around (it could be possible that someone have renamed the English groupnames in the language file).
                        $untranslated_groupname = trim(substr($langindex,strlen("usergroup-")));
                        $untranslated_groupname = str_replace(array("_", "and"), array(" "), $untranslated_groupname);
                        $groupref = sql_value("select ref as value from usergroup where lower(name)='$untranslated_groupname'",false);
                        if ($groupref!==false)
                            {
                            $default_group = true;
                            break;
                            }
                        }
                    }
                }
            if ($default_group==false)
                { 
                # Custom group
                # Decode the groupname
                $untranslated_groups = sql_query("select ref, name from usergroup");
                
                foreach ($untranslated_groups as $group)
                    {
                    if (i18n_get_translated($group['name'])==$translated_groupname)
                        { 
                        $groupref = $group['ref'];
                        break;
                        }
                    }
                }
            if($return_usernames)
                {
                $users = sql_array("select username value from user where usergroup='$groupref'");
                if ($newlist!="") {$newlist.=",";}
                $newlist.=join(",",$users);
                }
            else
                {
                # Find and add the users.
                if ($newlist!="") {$newlist.=",";}
                $newlist.=$groupref;
                }
            }
        }
    return $newlist;
    }

/**
 * Remove smart lists from the provided user lists.
 *
 * @param  string $ulist    Comma separated list of user list names
 * @return string   The updated list with smart groups removed.
 */
function remove_groups_smart_from_userlist($ulist)
    {
    global $lang;
    
    $ulist=explode(",",$ulist);
    $new_ulist='';
    foreach($ulist as $option)
        {
        if(strpos($option,$lang["groupsmart"] . ": ")===false)
            {
            if($new_ulist!="")
                {
                $new_ulist.=",";
                }
            $new_ulist.=$option;
            }
        }
    return $new_ulist;
    }

    
/**
 * Checks that a password conforms to the configured paramaters. 
 *
 * @param  string $password The password
 * @return mixed True if OK, or a descriptive string if it isn't
 */
function check_password($password)
    {
    global $lang, $password_min_length, $password_min_alpha, $password_min_uppercase, $password_min_numeric, $password_min_special;

    trim($password);
    if (strlen($password)<$password_min_length) {return str_replace("?",$password_min_length,$lang["password_not_min_length"]);}

    $uppercase="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $alpha=$uppercase . "abcdefghijklmnopqrstuvwxyz";
    $numeric="0123456789";
    
    $a=0;$u=0;$n=0;$s=0;
    for ($m=0;$m<strlen($password);$m++)
        {
        $l=substr($password,$m,1);
        if (strpos($uppercase,$l)!==false) {$u++;}

        if (strpos($alpha,$l)!==false) {$a++;}
        elseif (strpos($numeric,$l)!==false) {$n++;}
        else {$s++;} # Not alpha/numeric, must be a special char.
        }
    
    if ($a<$password_min_alpha) {return str_replace("?",$password_min_alpha,$lang["password_not_min_alpha"]);}
    if ($u<$password_min_uppercase) {return str_replace("?",$password_min_uppercase,$lang["password_not_min_uppercase"]);}
    if ($n<$password_min_numeric) {return str_replace("?",$password_min_numeric,$lang["password_not_min_numeric"]);}
    if ($s<$password_min_special) {return str_replace("?",$password_min_special,$lang["password_not_min_special"]);}
    
    
    return true;
    }

/**
 * For a given comma-separated list of user refs (e.g. returned from a group_concat()), return a string of matching usernames.
 *
 * @param  string $users User list - caution, used directly in SQL so must not contain user input
 * @return string Matching usernames.
 */
function resolve_users($users)
    {
    if (trim($users)=="") {return "";}
    $resolved=sql_array("select concat(fullname,' (',username,')') value from user where ref in ($users)");
    return join(", ",$resolved);
    }


/**
 * Verify a supplied external access key
 *
 * @param  array | integer $resources   Resource ID | Array of resource IDs 
 * @param  string $key          The external access key
 * @return boolean Valid?
 */
function check_access_key($resources,$key)
    {    
    if(!is_array($resources))
        {
        $resources = array($resources);
        }

    foreach($resources as $resource)
        {
        $resource = (int)$resource;
        # Option to plugin in some extra functionality to check keys
        if(hook("check_access_key", "", array($resource, $key)) === true)
            {
            return true;
            }
        }
    hook("external_share_view_as_internal_override");

    global $external_share_view_as_internal, $baseurl, $baseurl_short;

    if(
        $external_share_view_as_internal
        && (
            isset($_COOKIE["user"])
            && validate_user("session='" . escape_check($_COOKIE["user"]) . "'", false)
            && !is_authenticated()
        ))
            {
            return false;
            } // We want to authenticate the user if not already authenticated so we can show the page as internal

    $key_escaped = escape_check($key);

    $keys = sql_query("
            SELECT k.user,
                   k.usergroup,
                   k.expires,
                   k.password_hash, 
                   k.access,
                   k.resource
            FROM external_access_keys k 
            LEFT JOIN user u ON u.ref=k.user
            WHERE k.access_key = '$key_escaped'
               AND k.resource IN ('" . implode("','",$resources) . "')
               AND (k.expires IS NULL OR k.expires > now())
               AND u.approved=1
               ORDER BY k.access");

    if(count($keys) == 0 || count(array_diff($resources,array_column($keys,"resource"))) > 0)
        {
        // Check if this is a request for a resource uploaded to an upload_share
        $upload_sharecol = upload_share_active();
        if($upload_sharecol && check_access_key_collection($upload_sharecol,$key))
            {
            $uploadsession = get_rs_session_id();
            $uploadcols = get_session_collections($uploadsession);
            foreach($uploadcols as $uploadcol)
                {
                $sessioncol_resources = get_collection_resources($uploadcol);
                if(!array_diff($sessioncol_resources,$resources))
                    {                       
                    return true;
                    }
                }
            }
        return false;
        }

    if($keys[0]["access"] == -1)
        {
        // If the resources have -1 as access they may have been added without the correct expiry etc.
        sql_query("UPDATE external_access_keys ak
            LEFT JOIN (SELECT * FROM external_access_keys ake WHERE access_key='$key_escaped' ORDER BY access DESC, expires ASC LIMIT 1) ake
                ON ake.access_key=ak.access_key
                AND ake.collection=ak.collection
            SET ak.expires=ake.expires, 
                ak.access=ake.access,
                ak.usergroup=ake.usergroup,
                ak.email=ake.email,
                ak.password_hash=ake.password_hash
            WHERE ak.access_key = '$key_escaped'
            AND ak.access='-1'
            AND ak.expires IS NULL");
        return false;            
        }

    if($keys[0]["password_hash"] != "" && PHP_SAPI != "cli")
        {
        // A share password has been set. Check if user has a valid cookie set
        $share_access_cookie = isset($_COOKIE["share_access"]) ? $_COOKIE["share_access"] : "";
        $check = check_share_password($key,"",$share_access_cookie);
        if(!$check)
            {
            $url = generateURL($baseurl . "/pages/share_access.php",array("k"=>$key,"resource"=>$resources[0],"return_url" => $baseurl . (isset($_SERVER["REQUEST_URI"]) ? urlencode(str_replace($baseurl_short,"/",$_SERVER["REQUEST_URI"])) : "/r=" . $resource . "&k=" . $key)));
            redirect($url);
            exit();
            }
        }
        
    $user       = $keys[0]["user"];
    $group      = $keys[0]["usergroup"];
    $expires    = $keys[0]["expires"];
            
    # Has this expired?
    if ($expires!="" && strtotime($expires)<time())
        {
        global $lang;
        ?>
        <script type="text/javascript">
        alert("<?php echo $lang["externalshareexpired"] ?>");
        history.go(-1);
        </script>
        <?php
        exit();
        }
    # "Emulate" the user that e-mailed the resource by setting the same group and permissions        
    emulate_user($user, $group);
    
    global $usergroup,$userpermissions,$userrequestmode,$usersearchfilter,$external_share_groups_config_options, $search_filter_nodes; 
            $groupjoin="u.usergroup=g.ref";
            $permissionselect="g.permissions";
            if ($keys[0]["usergroup"]!="")
                {
                # Select the user group from the access key instead.
                $groupjoin="g.ref='" . escape_check($keys[0]["usergroup"]) . "' LEFT JOIN usergroup pg ON g.parent=pg.ref";
                $permissionselect="if(find_in_set('permissions',g.inherit_flags) AND pg.permissions IS NOT NULL,pg.permissions,g.permissions) permissions";
                }
    $userinfo=sql_query("select g.ref usergroup," . $permissionselect . " ,g.search_filter,g.config_options,g.search_filter_id,g.derestrict_filter_id,u.search_filter_override, u.search_filter_o_id , g.derestrict_filter_id from user u join usergroup g on $groupjoin where u.ref='$user'");
    if (count($userinfo)>0)
        {
        $usergroup=$userinfo[0]["usergroup"]; # Older mode, where no user group was specified, find the user group out from the table.
        $userpermissions=explode(",",$userinfo[0]["permissions"]);
        
        if ($search_filter_nodes)
            {
            if(isset($userinfo[0]["search_filter_o_id"]) && is_numeric($userinfo[0]["search_filter_o_id"]) && $userinfo[0]['search_filter_o_id'] > 0)
                {
                // User search filter override
                $usersearchfilter = $userinfo[0]["search_filter_o_id"];
                }
            elseif(isset($userinfo[0]["search_filter_id"]) && is_numeric($userinfo[0]["search_filter_id"]) && $userinfo[0]['search_filter_id'] > 0)
                {
                // Group search filter
                $usersearchfilter = $userinfo[0]["search_filter_id"];
                }
            }
        else
            {
            // Old style search filter that hasn't been migrated
            $usersearchfilter=isset($userinfo[0]["search_filter_override"]) && $userinfo[0]["search_filter_override"]!='' ? $userinfo[0]["search_filter_override"] : $userinfo[0]["search_filter"];
            }

        if (hook("modifyuserpermissions")){$userpermissions=hook("modifyuserpermissions");}
        $userrequestmode=0; # Always use 'email' request mode for external users
        
        # Load any plugins specific to the group of the sharing user, but only once as may be checking multiple keys
        global $emulate_plugins_set;            
        if ($emulate_plugins_set!==true)
            {
            global $plugins;
            $enabled_plugins = (sql_query("SELECT name,enabled_groups, config, config_json FROM plugins WHERE inst_version>=0 AND length(enabled_groups)>0  ORDER BY priority"));
            foreach($enabled_plugins as $plugin)
                {
                $s=explode(",",$plugin['enabled_groups']);
                if (in_array($usergroup,$s))
                    {
                    include_plugin_config($plugin['name'],$plugin['config'],$plugin['config_json']);
                    register_plugin($plugin['name']);
                    $plugins[]=$plugin['name'];
                    }
                }
            for ($n=count($plugins)-1;$n>=0;$n--)
                {
                if (!isset($plugins[$n])) { continue; }
                register_plugin_language($plugins[$n]);
                }
            $emulate_plugins_set=true;                  
            }
        
        if($external_share_groups_config_options || stripos(trim(isset($userinfo[0]["config_options"])),"external_share_groups_config_options=true")!==false)
            {

            # Apply config override options
            $config_options=trim($userinfo[0]["config_options"]);

            // We need to get all globals as we don't know what may be referenced here
            extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
            eval($config_options);

            }
        }
    
    # Special case for anonymous logins.
    # When a valid key is present, we need to log the user in as the anonymous user so they will be able to browse the public links.
    global $anonymous_login;
    if (isset($anonymous_login))
        {
        global $username,$baseurl;
        if(is_array($anonymous_login))
        {
        foreach($anonymous_login as $key => $val)
            {
            if($baseurl==$key){$anonymous_login=$val;}
            }
        }
        $username=$anonymous_login;     
        }
    
    # Set the 'last used' date for this key
    sql_query("UPDATE external_access_keys SET lastused = now() WHERE resource IN ('" . implode("','",$resources) . "') AND access_key = '$key_escaped'");
    
    return true;
    }


/**
* Check access key for a collection. For a featured collection category, the check will be done on all sub featured collections.
* 
* @param integer $collection        Collection ID
* @param string  $key               Access key
* 
* @return boolean
*/
function check_access_key_collection($collection, $key)
    {
    if(!is_int_loose($collection))
        {
        return false;
        }

    hook("external_share_view_as_internal_override");
    global $external_share_view_as_internal, $baseurl, $baseurl_short, $pagename;
    if($external_share_view_as_internal && isset($_COOKIE["user"]) && validate_user("session='" . escape_check($_COOKIE["user"]) . "'", false))
        {
        // We want to authenticate the user so we can show the page as internal
        return false;
        }

    $collection = get_collection($collection);
    if($collection === false)
        {
        return false;
        }

    
    // Get key info 
    $keyinfo = sql_query("
                    SELECT user,
                           usergroup,
                           expires,
                           upload,
                           password_hash,
                           collection
                      FROM external_access_keys
                     WHERE access_key = '{$key}'
                       AND (expires IS NULL OR expires > now())");
    
    if(count($keyinfo) == 0)
        {
        return false;
        }
    $collection_resources = get_collection_resources($collection["ref"]);
    $collection["has_resources"] = (is_array($collection_resources) && !empty($collection_resources) ? 1 : 0);
    $is_featured_collection_category = is_featured_collection_category($collection);

    if(!$is_featured_collection_category && (!$collection["has_resources"] && !(bool)$keyinfo[0]["upload"]))
        {
        return false;
        }

    // From this point all collections should have resources. For FC categories, its sub FCs will have resources because
    // get_featured_collection_categ_sub_fcs() does the check internally
    $collections = (!$is_featured_collection_category ? array($collection["ref"]) : get_featured_collection_categ_sub_fcs($collection, array("access_control" => false)));

    if($keyinfo[0]["password_hash"] != "" && PHP_SAPI != "cli")
        {
        // A share password has been set. Check if user has a valid cookie set
        $share_access_cookie = isset($_COOKIE["share_access"]) ? $_COOKIE["share_access"] : "";
        $check = check_share_password($key,"",$share_access_cookie);
        if(!$check)
            {
            $url = generateURL($baseurl . "/pages/share_access.php",array("k"=>$key,"return_url" => $baseurl . (isset($_SERVER["REQUEST_URI"]) ? urlencode(str_replace($baseurl_short,"/",$_SERVER["REQUEST_URI"])) : "/c=" . $collection["ref"] . "&k=" . $key)));
            redirect($url);
            exit();
            }
        }
       
    $sql = "UPDATE external_access_keys SET lastused = NOW() WHERE collection = '" . $collection["ref"] . "' AND access_key = '{$key}'";

    if(in_array($collection["ref"],array_column($keyinfo,"collection")) && (bool)$keyinfo[0]["upload"] === true)
        {
        // External upload link -set session to use for creating temporary collection
        $shareopts = array(
            "collection"    => $collection["ref"],
            "usergroup"     => $keyinfo[0]["usergroup"],
            "user"          => $keyinfo[0]["user"],
            );        
        upload_share_setup($key,$shareopts);
        return true;
        }

    foreach($collections as $collection_ref)
        {
        $resources_alt = hook("GetResourcesToCheck","",array($collection));
        $resources = (is_array($resources_alt) && !empty($resources_alt) ? $resources_alt : get_collection_resources($collection_ref));

        if(!check_access_key($resources, $key))
            {
            return false;
            }

        sql_query(sprintf($sql, escape_check($collection_ref)));
        }

    if($is_featured_collection_category)
        {
        // Update the last used for the dummy record we have for the featured collection category (ie. no resources since
        // a category contains only collections)
        sql_query(sprintf($sql, escape_check($collection["ref"])));
        }

    return true;
    }

/**
 * Generates a unique username for the given name
 *
 * @param  string $name The user's full name
 * @return string   The username to use
 */
function make_username($name)
    {
    # First compress the various name parts
    $s=trim_array(explode(" ",$name));
    
    $name=$s[count($s)-1];
    for ($n=count($s)-2;$n>=0;$n--)
        {
        $name=substr($s[$n],0,1) . $name;
        }
    $name=safe_file_name(strtolower($name));

    # Create fullname usernames:
    global $user_account_fullname_create;
    if($user_account_fullname_create) {
        $name = '';

        foreach ($s as $name_part) {
            $name .= '_' . $name_part;
        }
        
        $name = substr($name, 1);
        $name = safe_file_name($name);
    }
    
    # Check for uniqueness... append an ever-increasing number until unique.
    $unique=false;
    $num=-1;
    while (!$unique)
        {
        $num++;
        $c=sql_value("select count(*) value from user where username='" . escape_check($name . (($num==0)?"":$num)) . "'",0);
        $unique=($c==0);
        }
    return $name . (($num==0)?"":$num);
    }
    
/**
 * Returns a list of user groups selectable in the registration . The standard user groups are translated using $lang. Custom user groups are i18n translated.
 *
 * @return array
 */
function get_registration_selectable_usergroups()
    {
    # Executes query.
    $r = sql_query("select ref,name from usergroup where allow_registration_selection=1 order by name");

    # Translates group names in the newly created array.
    $return = array();
    for ($n = 0;$n<count($r);$n++)
        {
        $r[$n]["name"] = lang_or_i18n_get_translated($r[$n]["name"], "usergroup-");
        $return[] = $r[$n]; # Adds to return array.
        }

    return $return;
    }


/**
 * Give the user full access to the given resource. Used when approving requests.
 *
 * @param  integer $user
 * @param  integer $resource
 * @param  string $expires
 * @return boolean
 */
function open_access_to_user($user,$resource,$expires)
    {
    # Delete any existing custom access
    sql_query("delete from resource_custom_access where user='$user' and resource='$resource'");
    
    # Insert new row
    sql_query("insert into resource_custom_access(resource,access,user,user_expires) values ('$resource','0','$user'," . ($expires==""?"null":"'$expires'") . ")");
    
    return true;
    }

/**
 * Give the user full access to the given resource. Used when approving requests.
 *
 * @param  integer $group
 * @param  integer $resource
 * @param  string $expires
 * @return boolean
 */
function open_access_to_group($group,$resource,$expires)
    {
    # Delete any existing custom access
    sql_query("delete from resource_custom_access where usergroup='$group' and resource='$resource'");
    
    # Insert new row
    sql_query("insert into resource_custom_access(resource,access,usergroup,user_expires) values ('$resource','0','$group'," . ($expires==""?"null":"'$expires'") . ")");
    
    return true;
    }

/**
 * Grants open access to the user list for the specified resource.
 *
 * @param  string $userlist
 * @param  integer $resource
 * @param  string $expires
 * @return void
 */
function resolve_open_access($userlist,$resource,$expires)
    {
    global $open_internal_access,$lang;
    
    $groupids=resolve_userlist_groups_smart($userlist);
    debug("smart_groups: list=".$groupids);
    if($groupids!='')
        {
        $groupids=explode(",",$groupids);
        foreach ($groupids as $group)
            {
            open_access_to_group($group,$resource,$expires);
            }
        $userlist=remove_groups_smart_from_userlist($userlist); 
        }
    if($userlist!='')
        {
        $userlist_array=explode(",",$userlist);
        debug("smart_groups: userlist=".$userlist);
        foreach($userlist_array as $option)
            {
            #user
            $userid=sql_value("select ref value from user where username='$option'","");
            if($userid!="")
                {
                open_access_to_user($userid,$resource,$expires);   
                }
            }
        }
    }
    
/**
 * Remove any user-specific access granted by an 'approve'. Used when declining requests.
 *
 * @param  integer $user
 * @param  integer $resource
 * @return boolean
 */
function remove_access_to_user($user,$resource)
    {
    # Delete any existing custom access
    sql_query("delete from resource_custom_access where user='$user' and resource='$resource'");
    
    return true;
    }
    
/**
 * Returns true if a user account exists with e-mail address $email
 *
 * @param  string $email
 * @return boolean
 */
function user_email_exists($email)
    {
    $email=escape_check(trim(strtolower($email)));
    return (sql_value("select count(*) value from user where email like '$email'",0)>0);
    }


/**
* Return an array of emails from a list of usernames and email addresses. 
* with 'key_required' sibling array preserving the intent of internal/external sharing
* 
* @param array $user_list
* 
* @return array
*/
function resolve_user_emails($user_list)
    {
    global $lang, $user_select_internal;

    $emails_key_required = array();

    foreach($user_list as $user)
        {
        $escaped_username = escape_check($user);
        $email_details    = sql_query("SELECT email, approved, account_expires FROM user WHERE username = '{$escaped_username}'");
        if(isset($email_details[0]) && (time() < strtotime($email_details[0]['account_expires']))) 
          {
          continue;
          }

        // Not a recognised user, if @ sign present, assume e-mail address specified
        if(0 === count($email_details))
            {
            if(false === strpos($user, '@') || (isset($user_select_internal) && $user_select_internal))
                {
                error_alert("{$lang['couldnotmatchallusernames']}: {$escaped_username}");
                die();
                }

            $emails_key_required['unames'][]       = $user;
            $emails_key_required['emails'][]       = $user;
            $emails_key_required['key_required'][] = true;

            continue;
            }

        // Skip internal, not approved accounts
        if($email_details[0]['approved'] != 1)
            {
            debug('EMAIL: ' . __FUNCTION__ . '() skipping e-mail "' . $email_details[0]['email'] . '" because it belongs to user account which is not approved');

            continue;
            }
            
        if(!filter_var($email_details[0]['email'], FILTER_VALIDATE_EMAIL))
            {
            debug("Skipping invalid e-mail address: " . $email_details[0]['email']);
            continue;                    
            }
            
        // Internal, approved user account - add e-mail address from user account
        $emails_key_required['unames'][]       = $user;
        $emails_key_required['emails'][]       = $email_details[0]['email'];
        $emails_key_required['key_required'][] = false;
        }

    return $emails_key_required;
    }



/**
 * Creates a reset key for password reset e-mails
 *
 * @param  string $username The user's username
 * @return string The reset key
 */
function create_password_reset_key($username)
    {
    global $scramble_key;
    $resetuniquecode=make_password();
    $password_reset_hash=hash('sha256', date("Ymd") . md5("RS" . $resetuniquecode . $username . $scramble_key));  
    sql_query("update user set password_reset_hash='$password_reset_hash' where username='" . escape_check($username) . "'");   
    $password_reset_url_key=substr(hash('sha256', date("Ymd") . $password_reset_hash . $username . $scramble_key),0,15);
    return $password_reset_url_key;
    }
    
    
/**
 * For anonymous access - a unique session key to identify the user (e.g. so they can still have their own collections)
 *
 * @param  boolean $create Create one if it doesn't already exist
 * @return mixed    False on failure, the key on success
 */
function get_rs_session_id($create=false)
    {
    global $baseurl, $anonymous_login, $usergroup, $rs_session;
    // Note this is not a PHP session, we are using this to create an ID so we can distinguish between anonymous users or users accessing external upload links 
    $existing_session = isset($rs_session) ? $rs_session : (isset($_COOKIE["rs_session"]) ? $_COOKIE["rs_session"] : "");
    if($existing_session != "")
        {
        if (!headers_sent())
            {
            rs_setcookie("rs_session",$existing_session, 7, "", "", substr($baseurl,0,5)=="https", true); // extend the life of the cookie
            }
        return($existing_session);
        }
    if ($create) 
        {
        // Create a new ID - numeric values only so we can search for it easily
        $rs_session= rand();
        global $baseurl;
        if (!headers_sent())
            {
            rs_setcookie("rs_session",$rs_session, 7, "", "", substr($baseurl,0,5)=="https", true);
            }

        if(!upload_share_active())
            {
            if(is_array($anonymous_login))
                {
                foreach($anonymous_login as $key => $val)
                    {
                    if($baseurl == $key)
                        {
                        $anonymous_login = $val;
                        }
                    }
                }

            $valid = sql_query("select ref,usergroup,account_expires from user where username='" . escape_check($anonymous_login) . "'");

            if (count($valid) >= 1)
                {
                // setup_user hasn't been called yet, we just need the usergroup
                $usergroup = $valid[0]["usergroup"];

                // Log this in the daily stats
                daily_stat("User session", $valid[0]["ref"]);
                }
            }

        return $rs_session;
        }
    return false;
    }
    

/**
 * Returns an array of users (refs and emails) for use when sending email notifications (messages that in the past went
 *  to $email_notify, which can be emulated by using $email_notify_usergroups)
 * 
 * Can be passed a specific user type or an array of permissions
 * Types supported:-
 *      SYSTEM_ADMIN
 *      RESOURCE_ACCESS
 *      RESEARCH_ADMIN  
 *      USER_ADMIN
 *      RESOURCE_ADMIN
 *
 * @param  string $userpermission
 * @return array
 */
function get_notification_users($userpermission="SYSTEM_ADMIN")
    {    
    global $notification_users_cache, $usergroup,$email_notify_usergroups;
    $userpermissionindex=is_array($userpermission)?implode("_",$userpermission):$userpermission;
    if(isset($notification_users_cache[$userpermissionindex]))
        {return $notification_users_cache[$userpermissionindex];}
        
    if(is_array($email_notify_usergroups) && count($email_notify_usergroups)>0)
        {
        // If email_notify_usergroups is set we use these over everything else, as long as they have an email address set
        $notification_users_cache[$userpermissionindex] = sql_query("select ref, email, lang from user where usergroup in (" . implode(",",$email_notify_usergroups) . ") and email <>'' AND approved=1 AND (account_expires IS NULL OR account_expires > NOW())");
        return $notification_users_cache[$userpermissionindex];
        }
    
    if(!is_array($userpermission))
        {
        // We have been passed a specific type of administrator to find 
        switch($userpermission)
            {
            case "USER_ADMIN";
            // Return all users in groups with u permissions AND either no 'U' restriction, or with 'U' but in appropriate group
            $notification_users_cache[$userpermissionindex] = sql_query("select u.ref, u.email, u.lang from usergroup ug join user u on u.usergroup=ug.ref where find_in_set(binary 'u',ug.permissions) <> 0 and u.ref<>'' and u.approved=1 AND (u.account_expires IS NULL OR u.account_expires > NOW())" . (is_int($usergroup)?" and (find_in_set(binary 'U',ug.permissions) = 0 or ug.ref =(select parent from usergroup where ref=" . $usergroup . "))":""));    
            return $notification_users_cache[$userpermissionindex];
            break;
            
            case "RESOURCE_ACCESS";
            // Notify users who can grant access to resources, get all users in groups with R permissions
            $notification_users_cache[$userpermissionindex] = sql_query("select u.ref, u.email from usergroup ug join user u on u.usergroup=ug.ref where find_in_set(binary 'R',ug.permissions) <> 0 AND find_in_set(binary 'Rb',ug.permissions) = 0 AND u.approved=1 AND (u.account_expires IS NULL OR u.account_expires > NOW())");   
            return $notification_users_cache[$userpermissionindex];     
            break;
            
            case "RESEARCH_ADMIN";
            // Notify research admins, get all users in groups with r permissions
            $notification_users_cache[$userpermissionindex] = sql_query("select u.ref, u.email from usergroup ug join user u on u.usergroup=ug.ref where find_in_set(binary 'r',ug.permissions) <> 0 AND u.approved=1 AND (u.account_expires IS NULL OR u.account_expires > NOW())");   
            return $notification_users_cache[$userpermissionindex];     
            break;
                    
            case "RESOURCE_ADMIN";
            // Get all users in groups with t and e0 permissions
            $notification_users_cache[$userpermissionindex] = sql_query("select u.ref, u.email from usergroup ug join user u on u.usergroup=ug.ref where find_in_set(binary 't',ug.permissions) <> 0 AND find_in_set(binary 'e0',ug.permissions) and u.approved=1 AND (u.account_expires IS NULL OR u.account_expires > NOW())");   
            return $notification_users_cache[$userpermissionindex];
            break;
            
            case "SYSTEM_ADMIN";
            default;
            // Get all users in groups with a permission (default if incorrect admin type has been passed)
            $notification_users_cache[$userpermissionindex] = sql_query("select u.ref, u.email from usergroup ug join user u on u.usergroup=ug.ref where find_in_set(binary 'a',ug.permissions) <> 0 AND u.approved=1 AND (u.account_expires IS NULL OR u.account_expires > NOW())");   
            return $notification_users_cache[$userpermissionindex];
            break;
        
            }
        }
    else
        {
        // An array has been passed, find all users with these permissions
        $condition="";
        foreach ($userpermission as $permission)
            {
            if($condition!=""){$condition.=" and ";}
            $condition.="find_in_set(binary '" . $permission . "',ug.permissions) <> 0 AND u.approved=1 AND (u.account_expires IS NULL OR u.account_expires > NOW())";
            }
        $notification_users_cache[$userpermissionindex] = sql_query("select u.ref, u.email from usergroup ug join user u on u.usergroup=ug.ref where $condition");  
        return $notification_users_cache[$userpermissionindex];
        }
    }


/**
 * Validates the user entered antispam code
 *  
 * @param  string               $spamcode The antispam hash to check against
 * @param  string               $usercode The antispam code the user entered
 * @param  string               $spamtime The antispam timestamp
 * @return boolean              Return true if the code was successfully validated, otherwise false
 */ 
function verify_antispam($spamcode="",$usercode="",$spamtime=0)
    {
    global $scramble_key,$password_brute_force_delay;
    if($usercode=="" || $spamcode=="" || $spamtime==0){debug("antispam failed");return false;}
    if($spamcode != hash("SHA256",strtoupper($usercode) . $scramble_key . $spamtime))
        {
        debug("antispam failed: invalid code entered. IP: " . get_ip());
        sleep($password_brute_force_delay);
        return false;
        }
    $prevhashes=sql_array("SELECT unique_hash value FROM user WHERE unique_hash IS NOT null","");
    if(in_array(md5($usercode . $spamtime),$prevhashes))
        {
        debug("antispam failed: code has previously been used  IP: " . get_ip());
        sleep($password_brute_force_delay);
        return false;
        }
    return true;
    }

/**
* Check that access for given external share key is correct
* 
* @param array  $key       External access key 
* @param string $password  Share password to check
* @param string $cookie    Share session cookie that has been set previously
* 
* @return boolean
*/    
function check_share_password($key,$password,$cookie)
    {
    global $scramble_key, $baseurl;
    $sharehash = sql_value("SELECT password_hash value FROM external_access_keys WHERE access_key='" . escape_check($key) . "'","");
    if($password != "")
        {
        $hashcheck = hash('sha256', $key . $password . $scramble_key);
        $valid = $hashcheck == $sharehash;
        debug("checking share access password for key: " . $key);
        }
    else
        {
        $hashcheck = hash('sha256',  date("Ymd") . $key . $sharehash . $scramble_key);
        $valid = $hashcheck == $cookie;
        debug("checking share access cookie for key: " . $key);    
        }
    
    if(!$valid)
        {
        debug("failed share access password for key: " . $key);
        return false;    
        }
        
    if($cookie == "")
        {
        // Set a cookie for this session so password won't be required again
        $sharecookie = hash('sha256',  date("Ymd") . $key . $sharehash . $scramble_key); 
        rs_setcookie("share_access",$sharecookie, 0, "", "", substr($baseurl,0,5)=="https", true);
        }
    
    return true;   
    }

/**
* Offset a datetime to user local time zone
* 
* IMPORTANT: the offset is fixed, there is no calculation for summertime!
* 
* @param string $datetime A date/time string. @see https://www.php.net/manual/en/datetime.formats.php
* @param string $format   The format of the outputted date string. @see https://www.php.net/manual/en/function.date.php
* 
* @return string The date in the specified format
*/
function offset_user_local_timezone($datetime, $format)
    {
    global $user_local_timezone;

    $server_dtz = new DateTimeZone(date_default_timezone_get());
    $user_local_dtz = new DateTimeZone($user_local_timezone);

    // Create two DateTime objects that will contain the same Unix timestamp, but have different timezones attached to them
    $server_dt = new DateTime($datetime, $server_dtz);
    $user_local_dt = new DateTime($datetime, $user_local_dtz);

    $time_offset = $user_local_dt->getOffset() - $server_dt->getOffset();;

    $user_local_dt->add(DateInterval::createFromDateString((string) $time_offset . ' seconds'));

    return $user_local_dt->format($format);
    }


/**
 * Returns whether a user is anonymous or not
 * 
 * @return boolean
 */
function checkPermission_anonymoususer()
    {
    global $baseurl, $anonymous_login, $anonymous_autouser_group, $username, $usergroup;

    return
        (
            (
            isset($anonymous_login)
            && (
                (is_string($anonymous_login) && '' != $anonymous_login && $anonymous_login == $username)
                || (
                    is_array($anonymous_login)
                    && array_key_exists($baseurl, $anonymous_login)
                    && $anonymous_login[$baseurl] == $username
                   )
               )
            )
            || (isset($anonymous_autouser_group) && $usergroup == $anonymous_autouser_group)
        );
    }


/**
 * Does the current user have the ability to administer the dash (the tiles for all users)
 *
 * @return boolean
 */
function checkPermission_dashadmin()	
	{
	return ((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")));
    }
    

/**
 * Does the current user have the dash enabled?
 *
 * @return boolean
 */
function checkPermission_dashuser()
	{
	return !checkperm("dtu");
	}

/**
 * Can the user manage their dash?
 * 
 * Logic:
 * Home_dash is on, And not the Anonymous user with default dash, And (Dash tile user (Not with a managed dash) || Dash Tile Admin)
 *
 * @return boolean
 */
function checkPermission_dashmanage()
	{
	global $managed_home_dash,$unmanaged_home_dash_admins, $anonymous_default_dash;
	return (!checkPermission_anonymoususer() || !$anonymous_default_dash) && ((!$managed_home_dash && (checkPermission_dashuser() || checkPermission_dashadmin()))
				|| ($unmanaged_home_dash_admins && checkPermission_dashadmin()));
    }
    
/**
 * Can the user create tiles?
 *
 * Logic:
 * Home_dash is on, And not Anonymous use, And (Dash tile user (Not with a managed dash) || Dash Tile Admin)
 * 
 * @return boolean
 */
function checkPermission_dashcreate()
	{
	global $managed_home_dash,$unmanaged_home_dash_admins, $system_read_only;
	return !checkPermission_anonymoususer() 
            && 
            !$system_read_only
            &&
				(
					(!$managed_home_dash && (checkPermission_dashuser() || checkPermission_dashadmin())) 
				||
					($managed_home_dash && checkPermission_dashadmin())
				|| 
					($unmanaged_home_dash_admins && checkPermission_dashadmin())
				);
    }


/**
 * Check that the user has the $perm permission
 *
 * @param  string $perm
 * @return boolean Do they have the permission?
 */
function checkperm($perm)
    {
    # 
    global $userpermissions;
    if (!(isset($userpermissions))) {return false;}
    if (in_array($perm,$userpermissions)) {return true;} else {return false;}
    }


/**
 * Check if the current user is allowed to edit user with passed reference
 *
 * @param  integer $user The user to be edited
 * @return boolean
 */
function checkperm_user_edit($user)
	{
	if (!checkperm('u'))    // does not have edit user permission
		{
		return false;
		}
	if (!is_array($user))		// allow for passing of user array or user ref to this function.
		{
		$user=get_user($user);
		}
	$editusergroup=$user['usergroup'];
	if (!checkperm('U') || $editusergroup == '')    // no user editing restriction, or is not defined so return true
		{
		return true;
		}
	global $U_perm_strict, $usergroup;
	// Get all the groups that the logged in user can manage 
	$validgroups = sql_array("SELECT `ref` AS  'value' FROM `usergroup` WHERE " .
		($U_perm_strict ? "FIND_IN_SET('{$usergroup}',parent)" : "(`ref`='{$usergroup}' OR FIND_IN_SET('{$usergroup}',parent))")
	);
	
	// Return true if the target user we are checking is in one of the valid groups
	return (in_array($editusergroup, $validgroups));
	}


/**
* Determine if this is an internal share access request
* 
* @return boolean
*/
function internal_share_access()
    {
    global $k, $external_share_view_as_internal;
    return ($k != "" && $external_share_view_as_internal && is_authenticated());
    }

/**
 * Save or create usergroup
 *
 * @param  int              $ref    Group ref. Set to 0 to create a new group
 * @param  array            $groupoptions array of options to set for group in the form array("columnname" => $value)
 * 
 * @return mixed bool|int   True to indicate existing group has been updated or ID of newly created group
 */
function save_usergroup($ref,$groupoptions)
    {
    $validcolumns = array(
        "name",
        "permissions",
        "parent",
        "search_filter",
        "search_filter_id",
        "edit_filter",
        "edit_filter_id",
        "derestrict_filter",
        "derestrict_filter_id",
        "resource_defaults",
        "config_options",
        "welcome_message",
        "ip_restrict",
        "request_mode",
        "allow_registration_selection",
        "inherit_flags",
        "download_limit",
        "download_log_days"
        );

    $sqlcols = array();
    $sqlvals = array();
    $n=0;
    foreach ($validcolumns as $column)
        {
        if(isset($groupoptions[$column]))
            {
            $sqlcols[$n] = $column;
            $sqlvals[$n] = escape_check($groupoptions[$column]);
            $n++;
            }
        }

    if($ref > 0)
        {
        $sqlsetvals = array();
        for($n=0;$n<count($sqlcols);$n++)
            {
            $sqlsetvals[] = $sqlcols[$n] . "='" . $sqlvals[$n] . "'";
            }
        $sql = "UPDATE usergroup SET " . implode(",",$sqlsetvals) . " WHERE ref=" . (int)$ref;
        sql_query($sql);
        return true;
        }
    else
        {
        $sqlsetvals = array();
        for($n=0;$n<count($sqlcols);$n++)
            {
            $sqlsetvals[] = $sqlcols[$n] . "='" . $sqlvals[$n] . "'";
            }
        $sql = "INSERT INTO usergroup (" . implode(",",$sqlcols) . ") VALUES ('" . implode("','",$sqlvals) . "')";
        sql_query($sql);
        $newgroup = sql_insert_id();
        return $newgroup;
        }
    return false;
    }


 
/**
 * Set user's profile image and profile description (bio). Used by ../pages/user/user_profile_edit.php to setup user's profile.
 *
 * @param  int     $user_ref         User id of user who's profile is being set.
 * @param  string  $profile_text     User entered profile description text (bio).
 * @param  string  $image_path       Path to temp file created if user chose to upload a profile image.
 * 
 * @return boolean     If an error is encountered saving the profile image return will be false.
 */
function set_user_profile($user_ref,$profile_text,$image_path)
    {
    global $storagedir,$imagemagick_path, $scramble_key, $config_windows;
    
    # Check for presence of filestore/user_profiles directory - if it doesn't exist, create it.
    if (!is_dir($storagedir.'/user_profiles'))
        {
        mkdir($storagedir.'/user_profiles',0777);
        }

    # Locate imagemagick.
    $convert_fullpath = get_utility_path("im-convert");
    if ($convert_fullpath == false) 
        {
        debug("ERROR: Could not find ImageMagick 'convert' utility at location '$imagemagick_path'."); 
        return false;
        }
    
    if ($image_path != "" && file_exists($image_path))
        {
        # Work out the extension.
	    $extension = explode(".",$image_path);
        $extension = trim(strtolower($extension[count($extension)-1]));
        if ($extension != 'jpg' && $extension != 'jpeg')
            {
            return false;
            }
        
        # Remove previous profile image.
        delete_profile_image($user_ref);

        # Create profile image filename .
        $profile_image_name = $user_ref . "_" . md5($scramble_key . $user_ref . time()) . "." .$extension;
        $profile_image_path = $storagedir . '/user_profiles' . '/' . $profile_image_name;
        
        # Create profile image - cropped to square from centre.
        $command = $convert_fullpath . ' '. escapeshellarg((!$config_windows && strpos($image_path, ':')!==false ? $extension .':' : '') . $image_path) . " -resize '400x400' -thumbnail 200x200^^ -gravity center -extent '200x200'" . " " . escapeshellarg($profile_image_path);
        $output = run_command($command);

        # Store reference to user image.
        sql_query("update user set profile_image = '$profile_image_name' where ref = '" . escape_check($user_ref) . "'");

        # Remove temp file.
        if (file_exists($profile_image_path))
            {
            unlink($image_path);
            }
        }

    # Update user to set user.profile
    sql_query("update user set profile_text = '" . substr(strip_tags(escape_check($profile_text)),0,500) . "' where ref = '" . escape_check($user_ref) . "'");

    return true;
    }

/**
 * Delete a user's profile image. This will first remove the file and then update the db to clear the existing value.
 *
 * @param  mixed  $user_ref   User id of the user who's profile image is to be deleted.
 * 
 * @return void
 */
function delete_profile_image($user_ref)
    {
    global $storagedir;

    $profile_image_name = sql_value("select profile_image value from user where ref = '" . escape_check($user_ref) . "'","");
    
    if ($profile_image_name != "")
        {
        $path_to_file = $storagedir . '/user_profiles' . '/' . $profile_image_name;

        if (file_exists($path_to_file))
            {
            unlink($path_to_file);
            }
    
        sql_query("update user set profile_image = '' where ref = '" . escape_check($user_ref) . "'");
        }
    }
    
/**
 * Generate the url to the user's profile image. Fetch the url by the user's id or by the profile image filename. 
 *
 * @param  int     $user_ref   User id of the user who's profile image is requested.
 * @param  string  $by_image   The filename of the profile image to fetch having been collected from the db separately: user.profile_image 
 * 
 * @return string     The url to the user's profile image if available or blank if not set.
 */
function get_profile_image($user_ref = "", $by_image = "")
    {
    global $storagedir, $storageurl, $baseurl;

    if (is_dir($storagedir.'/user_profiles'))
        {
        # Only check the db if the profile image name has not been provided.
        if ($by_image == "" && $user_ref != "")
            {
            $profile_image_name = sql_value("select profile_image value from user where ref = '" . escape_check($user_ref) . "'","");
            }
        else
            {
            $profile_image_name = $by_image;
            }

        if ($profile_image_name != "")
            {
            return $storageurl . '/user_profiles/' . $profile_image_name;
            }
        else
            {
            return "";
            }
        }
    return "";    
    }

/**
 * Return user profile for a defined user. 
 *
 * @param  int     $user_ref   User id to fetch profile details for.
 * 
 * @return string     Profile details for the requested user.
 */
function get_profile_text($user_ref)
    {
    return sql_value("select profile_text value from user where ref = '" . escape_check($user_ref) . "'","");
    }


/**
* load language files for all users that need to be notified into an array - use for message and email notification
* load in default language strings first and then overwrite with preferred language strings
*
* @param  array $languages - array of language strings
* @return array $language_strings_all
* */


function get_languages_notify_users(array $languages = array())
    {
    global $applicationname,$defaultlanguage;
    
    $language_strings_all   = array();
    $lang_file_en           = dirname(__FILE__)."/../languages/en.php";
    $lang_file_default      = dirname(__FILE__)."/../languages/" . safe_file_name($defaultlanguage) . ".php";

     // add en and default language lang array values - always need en as some lang arrays do not contain all strings
    include $lang_file_en;
    $language_strings_all["en"] = $lang; 

    include $lang_file_default;
    $language_strings_all[$defaultlanguage] = $lang; 
       
    // remove en and default language from array of languages to use
    $langs2remove = array_unique(array("en", $defaultlanguage));
    foreach($langs2remove as $lang2remove)
        {
        if (in_array($lang2remove, $languages))
            {
            unset($languages[$lang2remove]);
            }
        }

    // load lang array values into array for each language
    foreach($languages as $language)
        {
        $lang = array();

        // include en and default lang array values
        include $lang_file_en;
        include $lang_file_default;

        $lang_file = dirname(__FILE__)."/../languages/" . safe_file_name($language) . ".php";

        if (file_exists($lang_file))
            {
            include $lang_file;
            }
        
        $language_strings_all[$language] = $lang; // append $lang array 
        }     

    return $language_strings_all;
    }

/**
 * Generate upload URL - alters based on $upload_then_edit setting and external uploads
 *
 * @param  string $collection - optional collection
 * @param  string $accesskey - used for external users
 * @return string
 */
function get_upload_url($collection="",$k="")
    {
    global $upload_then_edit, $userref, $baseurl;
    if ($upload_then_edit || $k != "" || !isset($userref))
        {
        $url = generateURL($baseurl . "/pages/upload_plupload.php",array("k" => $k,"collection_add"=>$collection));
        }
    elseif(isset($userref))
        {
        $url = generateURL($baseurl . "/pages/edit.php", array("ref" => "-" . $userref,"collection_add"=>$collection));
        }
    return $url;
    }

/**
 * Used to emulate system users when accessing system anonymously or via external shares
 * Sets global array such as $userpermissions, $username and sets any relevant config options
 *
 * @param  int $user            User ID
 * @param  int $usergroup       usergroup ID  
 * @return void
 */
function emulate_user($user, $usergroup="")
    {
    debug_function_call("emulate_user",func_get_args());
    global $userref, $userpermissions, $userrequestmode, $usersearchfilter, $search_filter_nodes;
    global $external_share_groups_config_options, $emulate_plugins_set, $plugins;
    global $username,$baseurl, $anonymous_login, $upload_link_workflow_state;


    if(!is_numeric($user) || ($usergroup != "" && !is_numeric($usergroup)))
        {
        return false;
        }

    $groupjoin="u.usergroup=g.ref";
    $permissionselect="g.permissions";

    if ($usergroup!="")
        {
        # Select the user group from the access key instead.
        $groupjoin="g.ref='" . escape_check($usergroup) . "' LEFT JOIN usergroup pg ON g.parent=pg.ref";
        $permissionselect="if(find_in_set('permissions',g.inherit_flags) AND pg.permissions IS NOT NULL,pg.permissions,g.permissions) permissions";
        }
    $userinfo=sql_query("select g.ref usergroup," . $permissionselect . " ,g.search_filter,g.config_options,g.search_filter_id,g.derestrict_filter_id,u.search_filter_override, u.search_filter_o_id , g.derestrict_filter_id from user u join usergroup g on $groupjoin where u.ref='$user'");
    if (count($userinfo)>0)
        {
        $usergroup=$userinfo[0]["usergroup"]; # Older mode, where no user group was specified, find the user group out from the table.
        $userpermissions=explode(",",$userinfo[0]["permissions"]);

        if(upload_share_active())
            {
            // Disable some permissions for added security
            $addperms = array('D','b','p');
            $removeperms = array('v','q','i','A','h','a','t','r','m','u','exup');

            // add access to the designated workflow state
            $addperms[] = "e" . $upload_link_workflow_state;

            $userpermissions = array_merge($userpermissions, $addperms);
            $userpermissions = array_diff($userpermissions, $removeperms);
            $userpermissions = array_values($userpermissions);
            $userref = $user;
            }
        
        if ($search_filter_nodes)
            {
            if(isset($userinfo[0]["search_filter_o_id"]) && is_numeric($userinfo[0]["search_filter_o_id"]) && $userinfo[0]['search_filter_o_id'] > 0)
                {
                // User search filter override
                $usersearchfilter = $userinfo[0]["search_filter_o_id"];
                }
            elseif(isset($userinfo[0]["search_filter_id"]) && is_numeric($userinfo[0]["search_filter_id"]) && $userinfo[0]['search_filter_id'] > 0)
                {
                // Group search filter
                $usersearchfilter = $userinfo[0]["search_filter_id"];
                }
            }
        else
            {
            // Old style search filter that hasn't been migrated
            $usersearchfilter=isset($userinfo[0]["search_filter_override"]) && $userinfo[0]["search_filter_override"]!='' ? $userinfo[0]["search_filter_override"] : $userinfo[0]["search_filter"];
            }

        if (hook("modifyuserpermissions")){$userpermissions=hook("modifyuserpermissions");}
        $userrequestmode=0; # Always use 'email' request mode for external users
        
        # Load any plugins specific to the group of the sharing user, but only once as may be checking multiple keys
        if ($emulate_plugins_set!==true)
            {
            $enabled_plugins = (sql_query("SELECT name,enabled_groups, config, config_json FROM plugins WHERE inst_version>=0 AND length(enabled_groups)>0  ORDER BY priority"));
            foreach($enabled_plugins as $plugin)
                {
                $s=explode(",",$plugin['enabled_groups']);
                if (in_array($usergroup,$s))
                    {
                    include_plugin_config($plugin['name'],$plugin['config'],$plugin['config_json']);
                    register_plugin($plugin['name']);
                    $plugins[]=$plugin['name'];
                    }
                }
            for ($n=count($plugins)-1;$n>=0;$n--)
                {
                register_plugin_language($plugins[$n]);
                }
            $emulate_plugins_set=true;                  
            }
        
        if($external_share_groups_config_options || stripos(trim(isset($userinfo[0]["config_options"])),"external_share_groups_config_options=true")!==false)
            {
            # Apply config override options
            $config_options=trim($userinfo[0]["config_options"]);

            // We need to get all globals as we don't know what may be referenced here
            extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
            eval($config_options);
            }
        }
    
    # Special case for anonymous logins.
    # When a valid key is present, we need to log the user in as the anonymous user so they will be able to browse the public links.
    if (isset($anonymous_login))
        {
        if(is_array($anonymous_login))
            {
            foreach($anonymous_login as $key => $val)
                {
                if($baseurl==$key){$anonymous_login=$val;}
                }
            }
        $username=$anonymous_login;     
        }
    }

function is_authenticated()
    {
    global $is_authenticated;
    return isset($is_authenticated) && $is_authenticated;
    }