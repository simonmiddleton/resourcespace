<?php

// Generate blurhashes for all resources missing them.
// Capped at 500 so a block is done each cron to avoid excessive server load.
include_once __DIR__ . "/../../include/image_processing.php";

$all_missing=ps_array("select ref value from resource where has_image=1 and (blurhash is null or length(blurhash)=0) order by ref desc limit 500");
foreach ($all_missing as $ref) 
{
    $thm=get_resource_path($ref,true,'thm',false,'jpg');
    if (file_exists($thm))
    {   
        $image=imagecreatefromjpeg($thm);
        if ($image!==false)
        {
            logScript("Generating blurhash for resource $ref");ob_flush();
            extract_mean_colour_and_blurhash($image,$ref);
        }
    }
}
