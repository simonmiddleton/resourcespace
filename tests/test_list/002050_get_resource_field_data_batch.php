<?php
// create 5 new resources
$resourcea=create_resource(1,0);
$resourceb=create_resource(1,0);
$resourcec=create_resource(2,0);
$resourced=create_resource(2,0);
$resourcee=create_resource(2,0);

debug("Resource A: " . $resourcea);
debug("Resource B: " . $resourceb);
debug("Resource C: " . $resourcec);
debug("Resource D: " . $resourced);
debug("Resource E: " . $resourcee);


// create new 'genre' field
$genrefield = create_resource_type_field("Genre",0,FIELD_TYPE_CHECK_BOX_LIST,"genre");

// create new 'fruit' field
$fruitfield = create_resource_type_field("Fruit",0,FIELD_TYPE_TEXT_BOX_SINGLE_LINE, "fruit");

// Add new nodes to fields
$comedynode = set_node(NULL, $genrefield, "Comedy",'',1000);
$actionnode = set_node(NULL, $genrefield, "Action",'',1000);
$horrornode = set_node(NULL, $genrefield, "Horror",'',1000);

// Add nodes to resource a
add_resource_nodes($resourcea,array($comedynode, $actionnode));
// Add node to resource b
add_resource_nodes($resourceb,array($comedynode, $horrornode));
// Add nodes to resource c
add_resource_nodes($resourcec,array($comedynode, $actionnode, $horrornode));
// Add nodes to resource d
add_resource_nodes($resourced,array($actionnode));
// Add node to resource e
add_resource_nodes($resourcee,array($horrornode));

// Add text data to resources
update_field($resourcea,$fruitfield,"Lemon");
update_field($resourceb,$fruitfield,"Orange");
update_field($resourcec,$fruitfield,"Mango");
update_field($resourced,$fruitfield,"Apple");
update_field($resourcee,$fruitfield,"Banana");

$allresources = array($resourcea,$resourceb,$resourcec,$resourced,$resourcee);
$alldata = get_resource_field_data_batch($allresources);
if(
    ($alldata[$resourcea][0]["value"] != "Comedy,Action")
     || 
    ($alldata[$resourcea][1]["value"] != "Lemon")
     || 
    ($alldata[$resourceb][0]["value"] != "Comedy,Horror")
     ||
    ($alldata[$resourceb][1]["value"] != "Orange")
     ||
     ($alldata[$resourcec][0]["value"] != "Comedy,Action,Horror")
     ||
     ($alldata[$resourcec][1]["value"] != "Mango")
     ||
     ($alldata[$resourced][0]["value"] != "Action")
     ||
     ($alldata[$resourced][1]["value"] != "Apple")
     ||
     ($alldata[$resourcee][0]["value"] != "Horror")
     ||
     ($alldata[$resourcee][1]["value"] != "Banana")
     )
    {
    return false;
    }