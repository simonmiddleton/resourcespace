<?php
command_line_only();

//TODO Remove this once complete
return true;

// This is a test for the rse_version plugin, now enabled in config.default.php so no point being in the plugin subfolder
$testid = "test_1125_" . uniqid();

// Add a new node field
$resource_type_field = create_resource_type_field($testid . "_tree", 1, FIELD_TYPE_CATEGORY_TREE, $testid, false);

// Add nodes
$parent_node_id_1 = set_node(null, $resource_type_field, '~en:Fruit~fr:Frut', null, null);
$parent_node_id_2 = set_node(null, $resource_type_field, '~en:Vegetable~fr:Légume', null, null);
$parent_node_id_3 = set_node(null, $resource_type_field, '~en:Meat~fr:Viande', null, null);
$child_node_id_11 = set_node(null, $resource_type_field, '~en:Apple~fr:Pomme', $parent_node_id_1, null);
$child_node_id_12 = set_node(null, $resource_type_field, '~en:Orange~fr:Orange', $parent_node_id_1, null);
$child_node_id_13 = set_node(null, $resource_type_field, '~en:Cauliflower~fr:Choufleur', $parent_node_id_2, null);
$child_node_id_31 = set_node(null, $resource_type_field, '~en:Courgette~fr:Zucchini', $parent_node_id_2, null);
$child_node_id_32 = set_node(null, $resource_type_field, '~en:Ham~fr:Jambon', $parent_node_id_3, null);
$child_node_id_33 = set_node(null, $resource_type_field, '~en:Pork~fr:Porc', $parent_node_id_3, null);

$resourcea=create_resource(1,0);

// Add nodes to resource
add_resource_nodes($resourcea,array($child_node_id_11, $parent_node_id_1),false,true);


// Test update using API
api_update_field($resourcea,$resource_type_field,"Vegetable/Courgette");


// Simulate a POST update



// Check log
$log = get_resource_log($resourcea,-1,["r.type"=>"e"]);



return true;