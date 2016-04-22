<?php
include_once dirname(__FILE__) . "/../../include/general.php";
include_once dirname(__FILE__) . "/../../include/resource_functions.php";


$new=copy_resource(1);

# To do - add some metadata to the first one in tests prior to this one.
# Then check to see if the data is preserved in the copy.

# Did it work?
return (get_resource_data($new)!==false);
