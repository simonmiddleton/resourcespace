<?php
if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}
include_once dirname(__FILE__) . "/../../include/general.php";
include_once dirname(__FILE__) . "/../../include/resource_functions.php";

create_resource(1);

# Did it work?
return (get_resource_data(1)!==false);
