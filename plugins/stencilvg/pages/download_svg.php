<?php

include_once "../../../include/db.php";
include_once "../../../include/authenticate.php";
include_once "../../../include/image_processing.php";

$save=(getval("save","0")==1);
$svg=getval("svg","");
$filename=getval("filename","");

if ($save)
    {
    // Save as a new resource

    // Create the resource record
    $ref=create_resource($stencilvg_resource_type_for_new,0,$userref);

    // Dump the supplied SVG data to the file and process it.
    $svg_path=get_resource_path($ref,true,'',true,"svg");
    file_put_contents($svg_path,$svg);
    upload_file($ref,true,false,false,$svg_path);
    create_previews($ref,false,"svg");
    update_field($ref,$view_title_field,$filename); // Filename as default title.

    // Send the user to the newly created resource
    redirect("pages/view.php?ref=" . $ref);
    }
else
    {
    // Downloading so just send the posted contents as a file.
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Content-Type: image/svg+xml");
    echo $svg;
    }
