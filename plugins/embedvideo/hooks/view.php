<?php

function HookEmbedvideoViewAfterresourceactions()
    {
    global $embedvideo_resourcetype,$ffmpeg_preview_extension,$resource,$ref,$ffmpeg_preview_max_width,$ffmpeg_preview_max_height,$baseurl,$lang,$preload,$video_preview_original;
    
    if ($resource["resource_type"] != $embedvideo_resourcetype)
        {
        return false;
        }

    if ($video_preview_original || !file_exists(get_resource_path($ref,true,"pre",false,$ffmpeg_preview_extension)))
        {
        $flashpath=get_resource_path($ref,false,"",false,$resource['file_extension'],-1,1,false,"",-1,false);
        }
    else
        {
        $flashpath=get_resource_path($ref,false,"pre",false,$ffmpeg_preview_extension,-1,1,false,"",-1,false);
        }
    
    $thumb=get_resource_path($ref,false,"pre",false,"jpg"); 

    ?>
    <li>
        <a href="#" onClick="jQuery('#embed-video').toggle();jQuery('#embed-video-help').toggle();">
            <?php echo "<i class='fa fa-share-alt'></i>&nbsp;" . $lang["embed"] ?>
        </a>
    </li>

    <p id="embed-video-help">
        <?php echo $lang["embed_help"] ?>
    </p>

    <textarea id="embed-video"><?php

    if (!hook("replaceembedcode"))
        {            
        echo htmlspecialchars('<script type="text/javascript" src="' . $baseurl . '/lib/js/videojs-extras.js"></script>
        <link href="' . $baseurl . '/lib/videojs/video-js.css" rel="stylesheet">
        <script src="' . $baseurl . '/lib/videojs/video.dev.js"></script>
        <script src="' . $baseurl . '/lib/js/videojs-extras.js"></script>
        <script src="' . $baseurl . '/lib/videojs/video.min.js"></script>
        <!-- START VIDEOJS -->
        <video 
            id="introvideo' .  $ref . '"
            controls
            preload="' . $preload  . '"
            width="' . $ffmpeg_preview_max_width . '" 
            height="' . $ffmpeg_preview_max_height . '" 
            class="Picture"
            poster="' . $thumb . '">
            <source src="' . $flashpath . '" type="video/' . $ffmpeg_preview_extension . '" >
            <p class="vjs-no-js">To view this video please enable JavaScript, and consider upgrading to a web browser that <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a></p>
        </video>');
        } // end hook replaceembedcode
    ?>
    </textarea>
    <?php

    return true;
    }

