<?php
$cropper_default_target_format = 'JPG';
$cropper_allowed_extensions = array('TIF','TIFF','JPG','JPEG','PNG','GIF','BMP','PSD'); // file formats that can be transformed
$cropper_formatarray = array('TIF','JPEG','JPG','PNG'); // output formats allowed for transform operations
$cropper_cropsize='pre';
$cropper_use_filename_as_title=true;
$cropper_allow_scale_up = true; // if false, scaling parameters that would result in enlargement are ignored
$cropper_rotation = true; // if true, enables flipping and rotation of images
$cropper_transform_original = false;
$cropper_use_repage = true; // use repage feature to remove image geometry after transformation. This is necessary for most ImageMagick-based systems to behave correctly.
$cropper_jpeg_rgb = true; // when creating a jpeg, make sure it is RGB
$cropper_enable_batch = false; // enable batch transform of collections
$cropper_enable_alternative_files = true;
$cropper_enable_replace_slideshow = true;
$cropper_restricteduse_groups=array();
$cropper_resolutions=array();
$cropper_quality_select = false;
$cropper_srgb_option = false;
$cropper_preset_sizes = array(
    "Facebook"  => array(
        "Profile Photo"	            => "180x180",
        "Cover Photo"               => "820x312",
        "Shared Image (Timeline)"   => "1200x630",
        "Shared Image (Newsfeed)"   => "1200x630", 
        "Shared Link (Timeline)"    => "1200x628",
        "Shared Link (Newsfeed)"    => "1200x628",
        "Event Image"           	=> "1920x1080",
        ), 
    "Twitter"  => array(
        "Profile Photo" => "400x400",
        "Header Photo" => "1500x500",
        "Image from a Tweet with shared link" => "1200x628",
        "Tweet sharing a single image" => "1200x675",
    ),
    "Instagram"  => array(
        "Profile Picture" => "110x110",
        "Photo Thumbnails" => "161x161",
        "Photo Size (Instagram App)" => "1080x1080",
        "Instagram Stories" => "1080x1920",
        ),
    "LinkedIn"  => array(
        "Personal Profile Image" => "300x300",
        "Personal Background Image" => "1584x396",
        "Company Logo Image" => "300x300",
        "Company Main Image" => "1128x191",
        "Shared Link" => "1200x627",
        "Shared Image" => "1200x627",
        "Life Tab: Main Image" => "1128x376",        
        ),    
    "Pinterest"  => array(
        "Profile Picture" => "165x165",
        "Pins (main page)" => "236",
        "Pins (on board)" => "236",
        "Pins (expanded)" => "600x900",
        "Pin Board (large thumbnail)" => "222x150",
        "Pin Board (smaller thumbnail)" => "55x55",

        ),
    "YouTube"  => array(
        "Channel Profile Image" => "800x800",
        "Channel Cover Art" => "2560x1440",
        "Channel cover: Safe area for logos and text" => "1235x338",
        "Video Uploads" => "1280x720",
        ),
    );
