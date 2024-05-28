<?php
# Video player - plays the preview file created to preview video resources.
include_once __DIR__ . '/../include/video_functions.php';
global $alternative,$css_reload_key,$display,$video_search_play_hover,$video_view_play_hover,$video_preview_play_hover,
$keyboard_navigation_video_search,$keyboard_navigation_video_view,$keyboard_navigation_video_preview,
$resource, $ffmpeg_preview_gif;

# Check for search page and the use of an alt file for video playback
$use_video_alts = false;
$alternative = is_null($alternative) ? -1 : $alternative;

//Create array of video sources
$video_preview_sources=array();
$vidindex=0;

$view_as_gif = false;
if ($ffmpeg_preview_gif && $resource['file_extension'] == 'gif' && $alternative === -1)
    {
    $view_as_gif = true;
    }

// Look for a standard preview video with the expected extension.
$video_preview = get_resource_path($ref, true, 'pre', false, $ffmpeg_preview_extension, true, 1, false, '', $alternative);
if(file_exists($video_preview))
    {
    $video_preview_path = get_resource_path($ref, false, 'pre', false, $ffmpeg_preview_extension, true, 1, false, '', $alternative, true);
    $video_preview_type = "video/{$ffmpeg_preview_extension}";
    }       
        
if((!file_exists($video_preview) || $video_preview_original) && get_resource_access($ref) == 0)
    {
    # Attempt to play the source file direct (not a preview). For direct MP4 upload support - the file itself is an MP4. Or, with the preview functionality disabled, we simply allow playback of uploaded video files.
    $origvideofile = get_resource_path($ref, true, '', false, $resource['file_extension'], true, 1, false, '', $alternative);
    if(file_exists($origvideofile) && strtolower($resource['file_extension']) == "mp4") # Check video js supported file type
        {
        $video_preview_path = get_resource_path($ref, false, $hide_real_filepath ? 'videojs' : '', false, $resource['file_extension'], true, 1, false, '', $alternative, false);
        if (!$hide_real_filepath && strpos($video_preview_path, 'download.php') !== false)
            {
            // A direct URL to the file was expected but download.php was used instead. Original file maybe within staticsync's $syncdir (no ingest mode).
            $video_preview_path = str_replace('size=&', 'size=videojs&', $video_preview_path);
            }
        $video_preview_type = "video/{$ffmpeg_preview_extension}";
        }
    }

if(isset($video_preview_path))
    {
    $video_preview_sources[$vidindex]['url']         = $video_preview_path;
    $video_preview_sources[$vidindex]['url_encoded'] = urlencode($video_preview_path);
    $video_preview_sources[$vidindex]['type']        = $video_preview_type;
    $video_preview_sources[$vidindex]['label']       = '';

    $vidindex++;
    }

if($use_video_alts)
    {
    # blank alt variable to use proper preview image
    $alternative = -1;
    }
    
if(isset($videojs_resolution_selection) && !$view_as_gif)
    {
    // Add in each of the videojs_resolution_selection items that use alternative files for previews
    $s_count = is_array($videojs_resolution_selection) ? count($videojs_resolution_selection) : 0;
    for($s=0;$s<$s_count;$s++)
        {
        if($videojs_resolution_selection[$s]['name']=='' && isset($video_preview_path))
            {
            // The default source was set earlier, just update the label
            $video_preview_sources[0]["label"]=isset($videojs_resolution_selection[$s]['label'])?$videojs_resolution_selection[$s]['label']:"";
            }
        else{
            $alt_data=ps_query("select " . columns_in("resource_alt_files") . " from resource_alt_files where resource=? and name=?",
        array("i",$ref,"s",$videojs_resolution_selection[$s]['name']));
            if(!empty($alt_data))
                {
                $alt_data = $alt_data[0];
                $res_path = get_resource_path($ref, false, '', false, $alt_data['file_extension'], true, 1, false, '', $alt_data['ref'], false);
                $res_ext  = $alt_data['file_extension'];
                }
            }
        if(isset($res_path) && isset($res_ext))
            {
            $video_preview_sources[$vidindex]["url"]=$res_path;
            $video_preview_sources[$vidindex]["type"]="video/" . $res_ext;
            $video_preview_sources[$vidindex]["label"]=i18n_get_translated($videojs_resolution_selection[$s]['label']);
            $vidindex++;
            }
        }
    }

$thumb     = get_resource_path($ref, false, 'pre', false, 'jpg', true, 1, false, '', $alternative);
$thumb_raw = $thumb;
$thumb     = urlencode($thumb);

# Choose a colour based on the theme.
$theme=(isset($userfixedtheme) && $userfixedtheme!="")?$userfixedtheme:getval("colourcss","greyblu");
$color="505050";$bgcolor1="666666";$bgcolor2="111111";$buttoncolor="999999";
if ($theme=="greyblu") {$color="446693";$bgcolor1="6883a8";$bgcolor2="203b5e";$buttoncolor="adb4bb";}   
if ($theme=="whitegry") {$color="ffffff";$bgcolor1="ffffff";$bgcolor2="dadada";$buttoncolor="666666";}  
if ($theme=="black") {$bgcolor1="666666";$bgcolor2="111111";$buttoncolor="999999";} 

$width=$ffmpeg_preview_max_width;
$height=$ffmpeg_preview_max_height;

$preload='auto';
// preview size adjustments for search
if ($pagename=="search"){
    switch($display){
        case "xlthumbs":
            $width="350";
            $height=350/$ffmpeg_preview_max_width*$ffmpeg_preview_max_height;
            break;
        case "thumbs":
            $width="150";
            $height=150/$ffmpeg_preview_max_width*$ffmpeg_preview_max_height;
            break;
    }
}

// Play video on hover?
$play_on_hover = false;
if (
    ($pagename == 'search' && $video_search_play_hover)
    || ($pagename == 'view' && $video_view_play_hover)
    || ($pagename == 'preview' && $video_preview_play_hover)
) {
    $play_on_hover=true;
}
    
// Using keyboard hotkeys?
$playback_hotkeys = false;
if (
    ($pagename == 'search' && $keyboard_navigation_video_search)
    || ($pagename == 'view' && $keyboard_navigation_video_view)
    || ($pagename=='preview' && $keyboard_navigation_video_preview)
) {
    $playback_hotkeys=true;
}

global $ffmpeg_preview_extension,$css_reload_key,$context;
?>
<link href="<?php echo $baseurl_short?>lib/videojs/video-js.min.css?r=<?php echo $css_reload_key?>" rel="stylesheet">
<script src="<?php echo $baseurl_short?>lib/videojs/video.min.js?r=<?php echo $css_reload_key?>"></script>
<script src="<?php echo $baseurl_short?>js/videojs-extras.js?r=<?php echo $css_reload_key?>"></script>
<?php

if(isset($videojs_resolution_selection))
    {
    ?>
    <link href="<?php echo $baseurl_short?>lib/videojs-resolution-switcher/videojs-resolution-switcher.css?r=<?php echo $css_reload_key?>" rel="stylesheet">
    <script src="<?php echo $baseurl_short?>lib/videojs-resolution-switcher/videojs-resolution-switcher.js?r=<?php echo $css_reload_key?>"></script>
    <?php
    }
    
    
    
?>
<!-- START VIDEOJS -->
<div class="videojscontent">
    <video 
        id="<?php echo $context ?>_<?php echo $display ?>_introvideo<?php echo $ref?>"
        controls
        data-setup='{ 
                <?php if ($view_as_gif)
                     {
                     ?>
                     "controls": false,
                     "autoplay": true,
                     "loop": true,
                     "muted": true
                     <?php
                     }
                ?>
            <?php if($play_on_hover && !$view_as_gif) { ?>
                "loadingSpinner" : false,
                "TextTrackDisplay" : true,
                "nativeTextTracks": false,
                "children": { 
                    "bigPlayButton":false, 
                    "controlBar": { 
                        "children": { 
                            "playToggle": false, 
                            "volumeControl":false
                        }
                    }
                }
            <?php }
            if(isset($videojs_resolution_selection) && count($video_preview_sources)>0 && !$view_as_gif)
                {?>
                "plugins": {
                        "videoJsResolutionSwitcher": {
                            "default": "<?php echo $videojs_resolution_selection_default_res?>"
                        }
                        }
            <?php } ?>
        }'
        preload="<?php echo $preload?>"
        width="<?php echo $width?>" 
        height="<?php echo $height?>" 
        class="video-js vjs-default-skin vjs-big-play-centered <?php if($pagename=='search'){echo "video-$display";} if($view_as_gif){echo ' vjs-gif-transparent';}?>" 
        poster="<?php echo $thumb_raw?>"
        <?php if($play_on_hover){ ?>
            onmouseout="videojs_<?php echo $context ?>_<?php echo $display ?>_introvideo<?php echo $ref ?>[0].pause();<?php echo $pagename !== 'search' ? "jQuery('.vjs-big-play-button').show();" : '';?>"
            onmouseover="videojs_<?php echo $context ?>_<?php echo $display ?>_introvideo<?php echo $ref ?>[0].play();<?php echo $pagename !== 'search' ? "jQuery('.vjs-big-play-button').hide();" : '';?>"
        <?php } ?>
        >
        <?php	          
        foreach($video_preview_sources as $video_preview_source)
            {
            ?>
            <source src="<?php echo $video_preview_source["url"]; ?>" type='<?php echo $video_preview_source["type"]; ?>' label='<?php echo escape($video_preview_source["label"] != "" ? $video_preview_source["label"] : $lang["preview"]); ?>'/>
            <?php	
            }?>
        <p class="vjs-no-js">To view this video please enable JavaScript, and consider upgrading to a web browser that <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a></p>
        <?php hook("html5videoextra"); ?>
        <?php display_video_subtitles($ref,$access); ?>
    </video>

<?php if($play_on_hover && !$view_as_gif){ ?>   
        <script>
        var videojs_<?php echo $context ?>_<?php echo $display ?>_introvideo<?php echo $ref ?> = jQuery('#<?php echo $context ?>_<?php echo $display ?>_introvideo<?php echo $ref ?>');
        </script>
<?php } ?>

</div>

<!-- START DISABLE VIDEOJS RIGHT CONTEXT MENU -->
<script>
    jQuery('.video-js').bind('contextmenu',function() { return false; });
</script>
<!-- END DISABLE VIDEOJS RIGHT CONTEXT MENU -->

<!-- END VIDEOJS -->
        