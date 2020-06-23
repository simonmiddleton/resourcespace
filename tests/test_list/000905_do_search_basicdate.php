<?php

if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// Check basic date search (based on searchbar)
// Save current setings
$saved_date_field = $date_field;

$resourcea  = create_resource(1,0);
$resourceb  = create_resource(1,0);
$resourcec  = create_resource(1,0);

// create new date range field and use as date field
$newdatefield   = create_resource_type_field("Basic date test",0,FIELD_TYPE_DATE,"testbasicdate");
$date_field     = $newdatefield;

update_field($resourcea,$newdatefield,"2017-07-15");
update_field($resourceb,$newdatefield,"2017-07-02");
update_field($resourcec,$newdatefield,"2017-04-08");

// SUBTEST A
// Ensure that params are converted correctly into a search string
$search="";
$_POST["basicyear"]     = "2017";
$_POST["basicmonth"]    = "07";
$_POST["basicday"]      = "15";

$search = update_search_from_request($search);
if($search != "basicyear:2017, basicmonth:07, basicday:15")
    {
    echo "ERROR - SUBTEST A\n";
    return false;
    }

// SUBTEST B
// Do the actual search - should return only resource A
$results = do_search($search);
if(count($results) !=  1 || !in_array($resourcea, array_column($results,"ref"))) 
    {
    echo "ERROR - SUBTEST B\n";
    return false;
    }

// SUBTEST C
// Do another search - return A and B
$search="";
$_POST["basicyear"]     = "2017";
$_POST["basicmonth"]    = "07";
unset($_POST["basicday"]);
$search = update_search_from_request($search);
$results = do_search($search);
if(count($results) !=  2 || !in_array($resourcea, array_column($results,"ref")) || !in_array($resourceb, array_column($results,"ref")) || in_array($resourcec, array_column($results,"ref"))) 
    {
    echo "ERROR - SUBTEST C\n";
    return false;
    }

// Reset settings
$date_field = $saved_date_field;

return true;
