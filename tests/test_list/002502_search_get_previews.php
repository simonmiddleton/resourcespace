<?php
command_line_only();

// Create a test field
$field2502 = create_resource_type_field("field2502",0,FIELD_TYPE_TEXT_BOX_SINGLE_LINE,"field2502",true);

// Create 50 resources
$resources=[];
for($n=0;$n<50;$n++)
    {
    $resources[$n] = create_resource(1,0,-1);
    resource_random_jpg($resources[$n],100,100);
    update_field($resources[$n],$field2502,'text2502');
    }

// Default $fetchrows = -1
$results = search_get_previews('text2502');

if(!is_array($results) || count($results) != 50)
    {
    echo "ERROR - SUBTEST A\n";
    return false;
    }

// $fetchrows = 20 (results will be padded)
$results = search_get_previews('text2502','','ref',0,20,"ASC");
if(count(array_column($results,"ref")) != 20 || $results[0]["ref"] !=  $resources[0])
    {
    echo "ERROR - SUBTEST B\n";
    return false;
    }

// $fetchrows = [0,10]
$results = search_get_previews('text2502','','ref',0,[0,10],"ASC");
if(!isset($results["total"]) || count($results["data"]) != 10)
    {
    //     echo "count: " . count($results) . "\n";
    //     echo  $resources[0] . "\n";
    //     echo  $results[0]["ref"] . "\n";

    echo "ERROR - SUBTEST C\n";
    return false;    
    }


return true;