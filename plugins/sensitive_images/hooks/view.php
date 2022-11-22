<?php

function HookSensitive_imagesViewReplacepreviewlink()
    {
    global $ref,$sensitive_images_field, $sensitive_images_blur_level, $resource, $image_preview_zoom;
    if ($sensitive_images_field==0) {return false;} // not configured yet
    $sensitive=$resource["field" . $sensitive_images_field];
    $sensitive_images_blur_preview=$sensitive_images_blur_level + 16;    
    if ($sensitive!="")
        {
        $image_preview_zoom=false;
        ?>
        <style>
        #previewimage {filter: blur(<?php echo (int)$sensitive_images_blur_preview; ?>px);}

        #previewimagewrapper::before 
                {
                content: '<?php echo htmlspecialchars($sensitive) ?>. Click image to show.';
                z-index: 5;
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate( -50%, -50% );
                color: white;
                font-size: 30px !important;
                font-weight: bold;
                text-align: center;
                text-shadow: 0px 1px 4px #000000a6;
                }
        </style>
        <?php
        }
    }
