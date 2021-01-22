<?php
// Test of staticsync functionality
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

// Staticsync affects this so keep a copy and restore it later
$saved_user_data = $userdata;
$saved_userref      = $userref;
$saved_perms        = $userpermissions;

// Set up staticsync to use a folder and make sure it exists.
$syncdir=$storagedir . "/staticsync/";
if (!file_exists($syncdir))
    {
    mkdir($syncdir);
    }

// Set up test path
$test_path=$syncdir . "test_folder/featured/";
if (!file_exists($test_path))
    {
    mkdir($test_path,0777,true);
    }
//chmod($test_path,0777);

// Set our test config
$staticsync_userref=$userref;
$theme_category_levels=20;
$staticsync_ingest=true;
$staticsync_autotheme = true;

// Create file to sync
copy(dirname(__FILE__) . '/../../gfx/homeanim/1.jpg', $test_path . "teststatic.jpg");

// Required for test C 
$staticsync_folder_structure=true;

// Required for test D
$staticsync_extension_mapping[2]=array("txt");
file_put_contents($test_path . "testtextsync.txt","TEST");

// Required for test F
$sync_tree_field= create_resource_type_field("Sync tree", 0, FIELD_TYPE_CATEGORY_TREE, "synctree",TRUE);
$staticsync_mapped_category_tree = $sync_tree_field;

// Required for Test G
$projectspath = $test_path . "projects/conferenceA/";
echo "Creating " . $projectspath;
if (!file_exists($projectspath))
    {
    mkdir($projectspath,0777,true);
    }
file_put_contents($projectspath . "projecta.txt","TEST");
$project_field = create_resource_type_field("Sync Project", 0, FIELD_TYPE_TEXT_BOX_SINGLE_LINE, "syncproject",TRUE);

$staticsync_mapfolders[]=array
    (
    "match"=>"/projects/",
    "field"=>$project_field,
    "level"=>4
    );

// Run staticsync, but hold back the output (has to be an include not a PHP exec so the above test config is used)
ob_start();
include (dirname(__FILE__) . "/../../pages/tools/staticsync.php");
ob_clean();

// Check the file has gone
if (file_exists($test_path . "teststatic.jpg"))
    {
    echo "Test A failed; File was not ingested.";
    return false;
    }

// Test B: Check that a search for the filename returns a result
$results=do_search("teststatic");
if (!is_array($results) || count($results)==0)
    {
    echo "Test B failed: ingested file could not be found.";
    return false;
    }

$resid = $results[0]["ref"];

// Test C: Check that $staticsync_autotheme worked
$fcs = get_featured_collections(0,array());
$testfc = array_search("Test_folder",array_column($fcs,"name"));
if($testfc===false)
    {
    echo "Test C: Featured collection 'Test_folder' not created by \$staticsync_autotheme - ";
    return false;
    }

// Test D: Check that $staticsync_autotheme created the correct sub featured collection
$subfcs = get_featured_collections($fcs[$testfc]["ref"],array());
$featuredfc = array_search("Featured",array_column($subfcs,"name"));
if($featuredfc===false)
    {
    echo "Test D: Featured collection 'Featured' not created by \$staticsync_autotheme - ";
    return false;
    }

// Test E -check $staticsync_extension_mapping
$results=do_search("testtextsync");
if (!is_array($results) || count($results)==0 || $results[0]["resource_type"] != 2)
    {
    echo "Test E failed: \$staticsync_extension_mapping failed";
    return false;
    }

// Test F - $staticsync_mapped_category_tree
$treedata = get_data_by_field($resid,$sync_tree_field);
if($treedata != "test_folder, featured")
    {
    echo "Test F failed: \$staticsync_mapped_category_tree failed";
    return false;
    }


// Test G,H - Check extracting data using $staticsync_mapfolders works
$results=do_search("projecta");
if (!is_array($results) || count($results)==0)
    {
    echo "Test G failed: \$staticsync_extension_mapping failed";
    return false;
    }
$mappeddata = get_data_by_field($results[0]["ref"],$project_field);
if(trim($mappeddata) != "Conference A")
    {
    echo "Test H failed: \$staticsync_mapped_category_tree failed";
    return false;
    }


// Test H - staticsync_alternatives_suffix
// Test I - staticsync_alternative_file_text
// Test J - staticsync_alt_suffix_array


// Check that the collection was created (TO DO)
/*
$themes_in_my_collections=true;
$collections=get_resource_collections($results[0]["ref"]);
print_r($collections);
*/

$userref            = $saved_userref;
$userpermissions    = $saved_perms;
$userdata           = $saved_user_data;

return true;

