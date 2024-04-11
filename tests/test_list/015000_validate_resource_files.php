<?php
command_line_only();


$enable_thumbnail_creation_on_upload = false; // Don't need previews here
$GLOBALS["valid_upload_paths"][] = realpath(__DIR__ . "/../../");

// Create resources with files
$test_image_type = create_resource_type("15000 image resources");

$test15000_resources = [];
$test15000_resources[0] = create_resource($test_image_type,0);
$test15000_resources[1] = create_resource($test_image_type,0);
$test15000_resources[2] = create_resource($test_image_type,0);



// TODO remove
$debug_log=true;
$debug_log_location = "/var/log/resourcespace/debug_dev.log";


foreach ($test15000_resources as $resource) {
    upload_file($resource,true,false,false,__DIR__ . "/../../gfx/watermark.png",false,false);
}

// Test A - Check only for presence of main resource files
$result = validate_resource_files($test15000_resources,["file_exists"=>true]);
if (array_search(false,$result) !== false) {
    echo " Test A - Failed to validate presence of resource files";
    return false;
}

// Test B - File missing
$path = get_resource_path($test15000_resources[1],true,'',false,'png');
unlink($path);
$result = validate_resource_files($test15000_resources,["file_exists"=>true]);
if (array_search(false,$result) !== $test15000_resources[1]) {
    echo " Test B - Failed to detect absence of resource file";
    return false;
}


// Test C - Check resource type in $file_integrity_ignore_resource_types
$ignore_image_type = create_resource_type("15000 ignore resource type");
$file_integrity_ignore_resource_types[] = $ignore_image_type;
update_resource_type($test15000_resources[1],$ignore_image_type);
$tovalidate = get_resources_to_validate();
if(in_array($test15000_resources[1],$tovalidate)) {
    echo " Test B - get_resources_to_validate() returned resource of type in \$file_integrity_ignore_resource_types array";
    return false;
}

////////////////////////////////////////////

// Create resources without files
$non_image_type = create_resource_type("15000 data resources");
$data_only_resource_types[] = $non_image_type; 
$test15000_resources[3] = create_resource($non_image_type,0);


return true;
