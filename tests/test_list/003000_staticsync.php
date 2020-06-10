<?php
// Test of staticsync functionality
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

// Staticsync affects this so keep a copy and restore it later
$original_user_data = $userdata;


// Set up staticsync to use a folder and make sure it exists.
$syncdir=$storagedir . "/staticsync/";
if (!file_exists($syncdir)) {mkdir($syncdir);}

// Set our test config
$staticsync_userref=$userref;
$theme_category_levels=20;
$staticsync_folder_structure=true;
$staticsync_ingest=true;

// Test path and image
$test_path=$syncdir . "test_folder/";
if (!file_exists($test_path)) {mkdir($test_path);}
$test_path.="teststatic.jpg";

// Copy the default slideshow image into the staticsync folder as a test resource
copy(dirname(__FILE__) . '/../../gfx/homeanim/1.jpg', $test_path);

// Run staticsync, but hold back the output (has to be an include not a PHP exec so the above test config is used)
ob_start();
include (dirname(__FILE__) . "/../../pages/tools/staticsync.php");
ob_clean();

// Check the file has gone
if (file_exists($test_path)) {echo "File was not ingested.";return false;}

// Check that a search for the filename returns a result
$results=do_search("teststatic");if (count($results)==0) {echo "Ingested file could not be found.";return false;}

// Check that the collection was created (TO DO)
/*
$themes_in_my_collections=true;
$collections=get_resource_collections($results[0]["ref"]);
print_r($collections);
*/


$userdata = $original_user_data;

return true;

