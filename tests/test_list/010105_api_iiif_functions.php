<?php
command_line_only();
$iiif_enabled = true;

// Set up to prevent other tests affecting results
ps_query("TRUNCATE resource");
ps_query("TRUNCATE resource_node");
ps_query("TRUNCATE resource_log");
$baseurl_saved = $baseurl;
$baseurl_short_saved = $baseurl_short;
$baseurl = "https://test010105.resourcespace.com/resourcespace";
$baseurl_short = "/resourcespace/";

include_once(__DIR__ . "/../../include/api_functions.php");
// Set up a IIIF request object

$iiif_options["rootlevel"] = $baseurl_short . "iiif/";
$iiif_options["rooturl"] = $baseurl . "/iiif/";
$iiif_options["rootimageurl"] = $baseurl . "/iiif/image/";
$iiif_options["identifier_field"] = create_resource_type_field("Object ID",0,FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,"objectid",true);
$iiif_options["description_field"] = 18;
$iiif_options["sequence_field"] = create_resource_type_field("Page",0,FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,"page");
$iiif_options["license_field"] = create_resource_type_field("License",0,FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,"license");
$iiif_options["title_field"] = 8;
$iiif = new IIIFRequest($iiif_options);

// Set up some IIIF resources
$resourcea=create_resource(1,0);
$resourceb=create_resource(1,0);
$resourcec=create_resource(2,0);

$objectid = "10105";

$test_object = set_node(NULL, $iiif->identifier_field, $objectid,'',1);
$page1_id = set_node(NULL, $iiif->sequence_field, "1",'',1);
$page2_id = set_node(NULL, $iiif->sequence_field, "2",'',2);
$page3_id = set_node(NULL, $iiif->sequence_field, "3",'',3);
$license_id = set_node(NULL, $iiif->license_field, "Public domain",'',1);

add_resource_nodes($resourcea,array($test_object,$page1_id,$license_id));
add_resource_nodes($resourceb,array($test_object,$page2_id,$license_id));
add_resource_nodes($resourcec,array($test_object,$page3_id,$license_id));
$resourceatitle = "Resource A Title";
$resourcebtitle = "Resource B Title";
$resourcectitle = "Resource C Title";
update_field($resourcea,$iiif->title_field,$resourceatitle);
update_field($resourceb,$iiif->title_field,$resourcebtitle);
update_field($resourcec,$iiif->title_field,$resourcectitle);
$resourceadescription = "Resource A description";
$resourcebdescription = "Resource B description";
$resourcecdescription = "Resource C description";
update_field($resourcea,$iiif->description_field,$resourceadescription);
update_field($resourceb,$iiif->description_field,$resourcebdescription);
update_field($resourcec,$iiif->description_field,$resourcecdescription);


$iiif_file = get_temp_dir() . DIRECTORY_SEPARATOR . "10105.jpg";
$image = imagecreate(800, 800);
$bg_col = imagecolorallocate($image, 136, 204, 119);
$text_col = imagecolorallocate($image, 255, 255, 255);
imagestring($image, 5, 20, 15,  'IIIF', $text_col);
imagejpeg($image, $iiif_file,50);
upload_file($resourcea,false,false,false,$iiif_file,false,true);

// setup IIIF user and run tests
$allresources = [$resourcea,$resourceb,$resourcec];

$original_user_data = $userdata;
$iiif_userid = new_user("iiif_test",2);
$iiif_user = get_user($iiif_userid);
$userdata[0] = $iiif_user;
setup_user($iiif_user);

// Manifest test
$testurl = $iiif->rooturl . $objectid . "/manifest";
$iiif->parseUrl($testurl);
$iiif->getResources();
$iiif->generateManifest();

if(!match_values(array_column($iiif->searchresults,"ref"),$allresources))
    {
    echo "Incorrect resources returned";
    return false;
    }
if($iiif->getresponse("label")["en"][0] != $resourceatitle)
    {
    echo "Incorrect label returned. Expected: '" . $resourceatitle . "', got: '" . $iiif->getresponse("label")["en"][0] . "'";
    return false;
    }
if($iiif->getresponse("summary")["en"][0] != $resourceadescription)
    {
    echo "Incorrect summary returned. Expected: '" . $resourceadescription . "', got: '" . $iiif->getresponse("summary")["en"][0] . "'";
    return false;
    }
$requiredstatement = $iiif->getresponse("requiredStatement")["value"]["en"][0];
if($requiredstatement != "Public domain")
    {
    echo "Incorrect rights attribute returned. Expected: '" . "Public domain" . "', got: '" . $requiredstatement . "'";
    return false;
    }

foreach($iiif->getresponse("metadata") as $metadata_item)
    {
    switch ($metadata_item["label"]["en"][0])
        {
        case "Title":
            if ($metadata_item["value"]["en"][0] != $resourceatitle)
                {
                echo "Incorrect title returned. Expected: '" . $resourceatitle . "', got: '" . $metadata_item["value"]["en"][0]  . "'";
                return false;
                }
            break;
        case "Caption":
            if($metadata_item["value"]["en"][0] != $resourceadescription)
                {
                echo "Incorrect caption returned. Expected: '" . $resourceadescription . "', got: '" . $metadata_item["value"]["en"][0]  . "'";
                return false;
                }
            break;
        case "License":
            if($metadata_item["value"]["en"][0] != "Public domain")
                {
                echo "Incorrect caption returned. Expected: 'Public domain', got: '" . $metadata_item["value"]["en"][0]  . "'";
                return false;
                }
            break;        
        }    
    }

// Check items
if(count($iiif->getresponse("items")) != 1)
    {
    echo "Invalid items returned";
    return false;
    }

$expected_image_url =  $iiif->rootimageurl . $resourcea . "/full/max/0/default.jpg";
if(!isset($iiif->getresponse("items")[0]["items"][0]["items"][0]["body"]["id"]) || $iiif->getresponse("items")[0]["items"][0]["items"][0]["body"]["id"] != $expected_image_url)
    {
    echo "Invalid Annotation image returned. Expected '" . $expected_image_url . "' , got '" . ($iiif->getresponse("items")[0]["items"][0]["items"][0]["body"]["id"] ?? "" ). "'";
    return false;
    }

// Tear down
unset ($iiif);
setup_user($original_user_data);
$baseurl = $baseurl_saved;
$baseurl_short = $baseurl_short_saved;

return true;
