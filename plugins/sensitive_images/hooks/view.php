<?php

function HookSensitive_imagesViewReplacepreviewlink()
    {
    global $ref,$sensitive_images_field,$resource,$image_preview_zoom;
    if ($sensitive_images_field==0) {return false;} // not configured yet
    $sensitive=$resource["field" . $sensitive_images_field];
    if ($sensitive!="")
        {
        $image_preview_zoom=false;
        ?>
        <style>
        #previewimage {filter: blur(15px);}

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
                text-align: center;
                }
        </style>
        <?php
        }
    }
