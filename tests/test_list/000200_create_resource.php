<?php
if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

$resourcenew=create_resource(1,0);
# Did it work?
return (get_resource_data($resourcenew)!==false);
