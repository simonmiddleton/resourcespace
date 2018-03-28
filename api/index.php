<?php
include "../include/db.php";
include_once "../include/general.php";
include_once "../include/search_functions.php";
include_once "../include/resource_functions.php";
include_once "../include/collections_functions.php";
include_once "../include/image_processing.php";
include_once "../include/api_functions.php";
include_once "../include/api_bindings.php";

if (!$enable_remote_apis) {exit("API not enabled.");}

debug("API:");

# Get parameters
$user=getvalescaped("user","");
$sign=getvalescaped("sign","");
$query=$_SERVER["QUERY_STRING"];

# Support POST request where 'query' is POSTed and is the full query string.
if (getval("query","")!="") {$query=getval("query","");}

# Authenticate based on the provided signature.
if(!check_api_key($user, $query, $sign))
    {
    debug("API: Invalid signature");
    exit("Invalid signature");
    }

# Log them in.
setup_user(get_user(get_user_by_username($user)));
debug("API: set up user '{$user}' signed with '{$sign}'");

# Run the requested query
echo execute_api_call($query);
debug("API: finished execute_api_call({$query});");

/*
 * API v2 - To Do
 *
 * POST requirement for anything that performs an action
 * Better support for parameters - URL/GET perhaps a bit too limiting in some cases? Perhaps support for parameters being JSON encoded?
 *
 */
