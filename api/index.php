<?php
include "../include/db.php";
header('Content-Type: application/json');
include_once "../include/node_functions.php";
include_once "../include/image_processing.php";
include_once "../include/api_functions.php";
include_once "../include/ajax_functions.php";
include_once "../include/api_bindings.php";
include_once "../include/login_functions.php";
include_once "../include/dash_functions.php";

if (!$enable_remote_apis) {http_response_code(403);exit("API not enabled.");}

debug("API:");
define("API_CALL", true);

# Get parameters
$user       = getval("user","");
$sign       = getval("sign","");
$authmode   = getval("authmode","userkey");
$query      = $_SERVER["QUERY_STRING"];
$pretty = filter_var(getval('pretty', ''), FILTER_VALIDATE_BOOLEAN); # Should response be prettyfied?

# Support POST request where 'query' is POSTed and is the full query string.
if (getval("query","")!="") {$query=getval("query","");}

# Remove the pretty, sign and authmode parameters if passed as these would not have been present when signed on the client.
# For example, pretty JSON is just how the client wants the response back, doesn't need to to be part of the signing key process.
parse_str($query, $query_params);
if (isset($query_params['sign']))
    {
    $query = str_ireplace("sign=" . $query_params['sign'], "!|!|", $query);
    }
if (isset($query_params['authmode']))
    {
    $query = str_ireplace("authmode=" . $query_params['authmode'], "!|!|", $query);
    }
if (isset($query_params['pretty']))
    {
    $query = str_ireplace("pretty=" . $query_params['pretty'], "!|!|", $query);
    }
$query = str_replace("&!|!|", "", ltrim($query, "!|!|&")); # remove joining &

$validauthmodes = array("userkey", "native", "sessionkey");
$function = getval("function","");
if(!in_array($authmode,$validauthmodes))
    {
    $authmode="userkey";
    }
if($function != "login")
    {
    if($authmode == "native")
        {
        include(__DIR__ . "/../include/authenticate.php");
        }
    else
        {
        # Authenticate based on the provided signature.
        if(!check_api_key($user, $query, $sign, $authmode))
            {
            debug("API: Invalid signature");
            http_response_code(401);
            exit("Invalid signature");
            }
    
        # Log user in (if permitted)        
        $validuser = setup_user(get_user(get_user_by_username($user)));
        if(!$validuser)
            {
            ajax_permission_denied();
            }
        }
    }
# Run the requested query
echo execute_api_call($query, $pretty);
debug("API: finished execute_api_call({$query});");

