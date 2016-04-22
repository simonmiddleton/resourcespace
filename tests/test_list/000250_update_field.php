<?php
if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}
include_once dirname(__FILE__) . "/../../include/general.php";
include_once dirname(__FILE__) . "/../../include/resource_functions.php";


update_field(1,8,"Test title");


# Was it set?
return (get_data_by_field(1,8)=="Test title");

