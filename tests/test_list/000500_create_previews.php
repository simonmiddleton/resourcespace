<?php
command_line_only();

include_once dirname(__FILE__) . "/../../include/image_processing.php";

$resource500 = create_resource(1,0);

// Test A - check that minimal previews are created
$enable_thumbnail_creation_on_upload = false;

$valid_upload_paths[] = dirname(__FILE__) . '/../../gfx/homeanim/';
upload_file($resource500,true,false,false,dirname(__FILE__) . '/../../gfx/homeanim/1.jpg',false,false);

if(start_previews($resource500) 
    || file_exists(get_resource_path($resource500,true,'lpr'))
    || !file_exists(get_resource_path($resource500,true,'col'))
    || !file_exists(get_resource_path($resource500,true,'thm'))
    || !file_exists(get_resource_path($resource500,true,'pre'))
) {
    echo 'Test A (minimal previews not created) - ';
    return false;
}

$previews_allow_enlarge = true; // To always get lpr size
$image_alternatives[0]["name"]="PNG File";
$image_alternatives[0]["source_extensions"]="jpg";
$image_alternatives[0]["source_params"]="";
$image_alternatives[0]["filename"]="alternative_png";
$image_alternatives[0]["target_extension"]="png";
$image_alternatives[0]["params"]="-density 300"; # 300 dpi
$image_alternatives[0]["icc"]=false;



$debug_log=true;
$debug_log_location = "/var/log/resourcespace/debug_dev.log";
$debug_extended_info = true;


// Test B - perform full preview creation
create_previews($resource500);

if(!file_exists(get_resource_path($resource500,true,'lpr'))) {
    echo 'Test B (full previews not created) - ';
    return false;
}

// Test C - check alternatives
$alternatives = get_alternative_files($resource500);
if(count($alternatives) === 0 || !file_exists(get_resource_path($resource500,true,'',true,'png',true,1,false,'',$alternatives[0]["ref"]))) {
    echo 'Test C ($image_alternatives not created) - ';
    return false;
}
