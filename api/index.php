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
$user       = getvalescaped("user","");
$sign       = getvalescaped("sign","");
$authmode   = getvalescaped("authmode","userkey");
$query      = $_SERVER["QUERY_STRING"];
$pretty = filter_var(getval('pretty', ''), FILTER_VALIDATE_BOOLEAN); # Should response be prettyfied?

// Parse query string and remove optional params (signature will be incorrect otherwise). For example, pretty JSON is just
// how the client wants the response back, doesn't need to to be part of the signing key process.
parse_str($query, $query_params);
unset($query_params['pretty']);
$query = http_build_query($query_params);

# Support POST request where 'query' is POSTed and is the full query string.
if (getval("query","")!="") {$query=getval("query","");}

# Remove the sign and authmode parameters if passed as these would not have been present when signed on the client.
$strip_params = array("sign","authmode");
parse_str($query,$params);
foreach($strip_params as $strip_param)
    {
    unset($params[$strip_param]);
    }
$query = http_build_query($params);

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

