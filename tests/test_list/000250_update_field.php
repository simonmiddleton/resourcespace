<?php
command_line_only();


$resourcea=create_resource(1,0);

// Create a standard text field
$field_title = 'Text field ABC';
$text_field_abc = create_resource_type_field($field_title, 1, FIELD_TYPE_TEXT_BOX_SINGLE_LINE, 'textfieldabc', false);
$set_value = "Cheddar";
$update_errors = [];
update_field($resourcea,$text_field_abc,$set_value,$update_errors);

// SUBTEST A - Check value is saved
$test_value = get_data_by_field($resourcea,$text_field_abc);
if($set_value != $test_value)
    {
    echo "SUBTEST A";
    return false;    
    }

// SUBTEST B - Check action has been logged correctly
$reslog = get_resource_log($resourcea);
$logok = false;  
foreach($reslog as $logentry)
    {
    if($logentry["type"]==LOG_CODE_EDITED && $logentry["title"] == $field_title && $logentry["diff"] == "+ " . $set_value)
        {
        $logok = true;        
        }
    }
if(!$logok)
    {
    echo "SUBTEST B";
    return false;    
    }

// Create a category tree field and add some nodes using text values
$field_title = 'Tree field ABC';
$tree_field_abc = create_resource_type_field($field_title, 1, FIELD_TYPE_CATEGORY_TREE, 'treefieldabc', false);

$fruitnode = set_node(NULL, $tree_field_abc, "Fruit",0,10);
$applenode = set_node(NULL, $tree_field_abc, "Apple",$fruitnode,20);
$pearnode = set_node(NULL, $tree_field_abc, "Pear",$fruitnode,30);
$vegnode = set_node(NULL, $tree_field_abc, "Vegetable",0,40);
$broccnode = set_node(NULL, $tree_field_abc, "Broccolli",$vegnode,50);
$carrotnode = set_node(NULL, $tree_field_abc, "Carrot",$vegnode,60);

$update_errors = [];
$set_values = ["Apple","Fruit"];
$log_value = "+ Fruit\n+ Apple";
$set_value = implode(",",$set_values);
$check_value = "Fruit/Apple";
update_field($resourcea,$tree_field_abc,$set_value,$update_errors);

// SUBTEST C - Check value is saved
$test_value = get_tree_strings(get_resource_nodes($resourcea,$tree_field_abc,true));
if(!in_array($check_value,$test_value))
    {
    echo "SUBTEST C";
    return false;    
    }

// SUBTEST D - Check action has been logged correctly
$reslog = get_resource_log($resourcea);
$logok = false;  
foreach($reslog as $logentry)
    {
    if($logentry["type"]==LOG_CODE_EDITED && $logentry["title"] == $field_title && $logentry["diff"] == $log_value)
        {
        $logok = true;        
        }
    }
if(!$logok)
    {
    echo "SUBTEST D";
    return false;    
    }

// Use the previous text field as the title field and update the title
$newtitle = "This is a test title";
$saved_view_title_field = $view_title_field;
$view_title_field=$text_field_abc;
check_db_structs();
update_field($resourcea,$view_title_field,$newtitle);
$test_value = get_data_by_field($resourcea,$view_title_field);
// SUBTEST E - Check value has been set correctly
if($newtitle != $test_value)
    {
    echo "SUBTEST E";
    return false;    
    }

// SUBTEST F - Check value has been set correctly on resource table
$resdata = get_resource_data($resourcea,false);
if($newtitle != $resdata["field" . $view_title_field])
    {
    echo "SUBTEST F";
    return false;    
    }

// Reset
$view_title_field = $saved_view_title_field;

return true;

