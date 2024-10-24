<?php
include "../../../include/boot.php";
include "../../../include/authenticate.php";
// For the given resource return a PNG no larger than 1024x1024 pixels as required by OpenAI.

$ref=getval("ref",0,true);
$access=get_resource_access($ref);
if ($access!=0)
    {
    // They shouldn't arrive here
    exit("Access denied");
    }

$jpg=get_resource_path($ref,true,"",false,"jpg");
if (!file_exists($jpg))
    {
    // No luck, try different extension
    $jpg=get_resource_path($ref,true,"",false,"jpeg");
    }
if (!file_exists($jpg))
    {
    // No luck, try scr size (internal size so always 'jpg')
    $jpg=get_resource_path($ref,true,"scr",false,"jpg");
    }
if (!file_exists($jpg))
    {
    exit("A suitable JPEG source file could not be found.");
    }

$image=imagecreatefromstring(file_get_contents($jpg));
$source_width=imagesx($image);
$source_height=imagesy($image);


// Rescale image
if ($source_width>$source_height)
    {
    // Landscape image, width is 1024
    $target_width=1024;
    $target_height=floor(($source_height/$source_width)*1024);
    }
else
    {
    // Portrait (or square), height is 1024
    $target_width=floor(($source_width/$source_height)*1024);;
    $target_height=1024;
    }
$png = imagecreatetruecolor($target_width, $target_height);
imagecopyresampled($png, $image, 0, 0, 0, 0, $target_width, $target_height, $source_width, $source_height);


header('Content-Type: image/png');
imagepng($png);
