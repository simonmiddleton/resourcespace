<?php
include "../include/db.php";
header('Content-Type: application/json');
include_once "../include/node_functions.php";
include_once "../include/image_processing.php";
include_once "../include/api_functions.php";
include_once "../include/ajax_functions.php";
include_once "../include/api_bindings.php";

if (!$enable_remote_apis) {exit("API not enabled.");}

debug("API:");
define("API_CALL", true);

# Get parameters
$user=getvalescaped("user","");
$sign=getvalescaped("sign","");
$query=$_SERVER["QUERY_STRING"];
$pretty = filter_var(getval('pretty', ''), FILTER_VALIDATE_BOOLEAN); # Should response be prettyfied?

// Parse query string and remove optional params (signature will be incorrect otherwise). For example, pretty JSON is just
// how the client wants the response back, doesn't need to to be part of the signing key process.
parse_str($query, $query_params);
unset($query_params['pretty']);
$query = http_build_query($query_params);

# Support POST request where 'query' is POSTed and is the full query string.
if (getval("query","")!="") {$query=getval("query","");}

# Authenticate based on the provided signature.
if(!check_api_key($user, $query, $sign))
    {
    debug("API: Invalid signature");
    exit("Invalid signature");
    }

# Log user in (if permitted)
$validuser = setup_user(get_user(get_user_by_username($user)));
if(!$validuser)
    {
    ajax_permission_denied();
    }

debug("API: set up user '{$user}' signed with '{$sign}'");

# Run the requested query
echo execute_api_call($query, $pretty);
debug("API: finished execute_api_call({$query});");

/*
 * API v2 - To Do
 *
 * POST requirement for anything that performs an action
 * Better support for parameters - URL/GET perhaps a bit too limiting in some cases? Perhaps support for parameters being JSON encoded?
 *
 */
