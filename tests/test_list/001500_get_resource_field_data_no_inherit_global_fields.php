<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }


$return = true;

// IMPORTANT: this needs to be changed if we have more/ less metadata fields specific to "Video" resource type
$expected_number_of_fields = 5;

// Check for a resource type which is not inheriting global fields
sql_query("UPDATE resource_type SET inherit_global_fields = 0 WHERE ref = 3");

// Create new resource
$videoresource = create_resource(3);

if($expected_number_of_fields < count(get_resource_field_data($videoresource)))
    {
    $return = false;
    }

// Reset Video resource type to the original state:
sql_query("UPDATE resource_type SET inherit_global_fields = 1 WHERE ref = 3");

return $return;
