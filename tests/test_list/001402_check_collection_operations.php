<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

// Setup test specific user
$user_general = new_user("test_001402_general", 2);
if($user_general === false)
    {
    $user_general = sql_value("SELECT ref AS `value` FROM user WHERE username = 'test_001402_general'", 0);
    }
if($user_general === 0)
    {
    echo "Setup test: users - ";
    return false;
    }
$user_general_data = get_user($user_general);

// Create a number of type 1 resources which are active
$resource_list=array();
$resource_list[] = create_resource(1, 0);
$resource_list[] = create_resource(1, 0);
$resource_list[] = create_resource(1, 0);
$resource_list[] = create_resource(1, 0);

// print_r($resource_list);

// Ensure creation was successful
foreach ($resource_list as $resource_entry)
    {
    if($resource_entry === false)
        {
        echo "Setup test: resources - ";
        return false;
        }
    }

// Create an empty collection
$collection_ref = create_collection($user_general, "test_001402", 1);

// Ensure that reordering an empty collection does not fail
$new_order=array();
try
    {
    update_collection_order($new_order,$collection_ref);
    }
catch(Exception $e)
    {
    echo "Update empty collection order";
    return false;
    }

// Ensure that deriving the minimum access for an empty collection does not fail
try
    {
    $min_access = collection_min_access($collection_ref);
    }
catch(Exception $e)
    {
    echo "Minimum access for empty collection";
    return false;
    }

// Ensure that deriving the maximum access for an empty collection does not fail
try
    {
    $max_access = collection_max_access($collection_ref);
    }
catch(Exception $e)
    {
    echo "Maximum access for empty collection";
    return false;
    }

// Add resources to the collection
foreach ($resource_list as $resource_entry)
    {
    if(!add_resource_to_collection($resource_entry, $collection_ref))
        {
        echo "Setup test: collection resources - ";
        return false;
        }
    }

// Ensure that reordering the populated collection is successful
$new_order=array($resource_list[3],$resource_list[0],$resource_list[2],$resource_list[1]);
try
    {
    update_collection_order($new_order,$collection_ref);
    }
catch(Exception $e)
    {
    echo "Update populated collection order exception";
    return false;
    }

// Ensure that the resulting order is as expected
$resource_order_sql = "select resource value from collection_resource WHERE collection='".escape_check($collection_ref)."' ORDER BY sortorder";
$resource_order = sql_array($resource_order_sql);

$expected_order=array($resource_list[3],$resource_list[0],$resource_list[2],$resource_list[1]);
if ($resource_order != $expected_order)
    {
    echo "\nUpdate populated collection order wrong\n";
    return false;
    }

// Tear down
unset($user_general, $user_general_data);
unset($resource_list, $resource_entry, $collection_ref, $new_order);
unset($min_access, $max_access);
unset($resource_order_sql, $resource_order, $expected_order);

return true;