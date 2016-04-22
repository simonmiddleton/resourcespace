<?php
include_once dirname(__FILE__) . "/../../include/general.php";
include_once dirname(__FILE__) . "/../../include/resource_functions.php";


$new=copy_resource(1);



# Did it work?
if (get_resource_data($new)===false) {return false;}

# Was the title field we set on the original resource copied?
return (get_data_by_field($new,8)=="Test title");
