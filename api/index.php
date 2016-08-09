<?php
include "../include/db.php";
include "../include/general.php";
include "../include/search_functions.php";
include "../include/resource_functions.php";
include "../include/collections_functions.php";
include "../include/image_processing.php";
include "../include/api_functions.php";
include "../include/api_bindings.php";

# Get parameters
$user=getvalescaped("user","");
$sign=getvalescaped("sign","");

# Authenticate based on the provided signature.
if (!check_api_key($user,$_SERVER["QUERY_STRING"],$sign)) {exit("Invalid signature");}

# Log them in.
setup_user(get_user(get_user_by_username($user)));

# Run the requested query
echo execute_api_call($_SERVER["QUERY_STRING"]);


/*
 * API v2 - To Do
 *
 * POST requirement for anything that performs an action
 * Better support for parameters - URL/GET perhaps a bit too limiting in some cases? Perhaps support for parameters being JSON encoded?
 *
 */
