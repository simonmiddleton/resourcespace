<?php
# Video player - plays the preview file created to preview video resources.

global $alternative,$css_reload_key,$display,$video_search_play_hover,$video_view_play_hover,$video_preview_play_hover,$video_player_thumbs_view_alt,$video_player_thumbs_view_alt_name,$keyboard_navigation_video_search,$keyboard_navigation_video_view,$keyboard_navigation_video_preview,$video_hls_streams,$video_preview_player_hls,$video_preview_hls_support;

$ref_escaped                               = escape_check($ref);
$video_player_thumbs_view_alt_name_escaped = escape_check($video_player_thumbs_view_alt_name);

# Check for search page and the use of an alt file for video playback
$use_video_alts = false;
$alternative = is_null($alternative) ? -1 : $alternative;

if(
       $video_player_thumbs_view_alt
    && isset($video_player_thumbs_view_alt_name)
    && 'search' == $pagename
    && 'list' != $display
)
    {
    $use_video_alts = true;

    $alternative = sql_value("
            SELECT ref AS `value`
              FROM resource_alt_files
             WHERE resource = '{$ref_escaped}'
               AND name = '{$video_player_thumbs_view_alt_name_escaped}'
        ",
        -1);
    }

//Create array of video sources
$video_preview_sources=array();
$vidindex=0;

if($video_preview_hls_support!=1 || !$video_preview_player_hls) 
	{
	// Look for a standard preview video with the expected extension.
	$video_preview = get_resource_path($ref, true, 'pre', false, $ffmpeg_preview_extension, true, 1, false, '', $alternative);

	if(file_exists($video_preview))
		{
		$video_preview_path = get_resource_path($ref, false, 'pre', false, $ffmpeg_preview_extension, true, 1, false, '', $alternative, false);
		$video_preview_type = "video/{$ffmpeg_preview_extension}";
		}		
	else if(!file_exists($video_preview) && 'flv' != $ffmpeg_preview_extension)
		{
		// No preview file of the default type found. For legacy systems that were not using MP4 previews there may be an FLV preview.
		$video_preview = get_resource_path($ref, true, 'pre', false, 'flv', true, 1, false, '', $alternative);

		if(file_exists($video_preview))
			{
			$video_preview_path = get_resource_path($ref, false, 'pre', false, 'flv', true, 1, false, '', $alternative, false);
			$video_preview_type = 'video/flv';
			}
		}
			
	if((!file_exists($video_preview) || $video_preview_original) && get_resource_access($ref) == 0)
		{
		# Attempt to play the source file direct (not a preview). For direct MP4/FLV upload support - the file itself is an FLV/MP4. Or, with the preview functionality disabled, we simply allow playback of uploaded video files.
		$origvideofile = get_resource_path($ref, true, '', false, $ffmpeg_preview_extension, true, 1, false, '', $alternative);

		if(file_exists($origvideofile))
			{
			$video_preview_path = get_resource_path($ref, false, '', false, $ffmpeg_preview_extension, true, 1, false, '', $alternative, false);
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
	}
	
if($video_preview_hls_support!=0)
	{
	$playlistfile=get_resource_path($ref,true,"pre",false,"m3u8",-1,1,false,"",$alternative,false);
	if(file_exists($playlistfile))
		{
        $hide_real_filepath = false;

		$playlisturl=get_resource_path($ref,false,"pre",false,"m3u8",-1,1,false,"",$alternative,false);
		$video_preview_sources[$vidindex]["url"]=$playlisturl;
		$video_preview_sources[$vidindex]["type"]="application/x-mpegURL";
		$video_preview_sources[$vidindex]["label"]="Auto";
		$vidindex++;

        $hide_real_filepath = true;
		}
	$videojs_resolution_selection_default_res="Auto";
	}		

if($use_video_alts)
    {
    # blank alt variable to use proper preview image
    $alternative = -1;
    }
	
if(isset($videojs_resolution_selection))
	{
	// Add in each version of the hls stream
	foreach ($video_hls_streams as $video_hls_stream)
		{
		$hlsfile=get_resource_path($ref,true,"pre_" . $video_hls_stream["id"],false,"m3u8",-1,1,false,"",$alternative,false);
		if(file_exists($hlsfile))
			{
            $hide_real_filepath = false;

			$hlsurl=get_resource_path($ref,false,"pre_" . $video_hls_stream["id"],false,"m3u8",-1,1,false,"",$alternative,false);
			$video_preview_sources[$vidindex]["url"]=$hlsurl;
			$video_preview_sources[$vidindex]["type"]="application/x-mpegURL";
			$video_preview_sources[$vidindex]["label"]=i18n_get_translated($video_hls_stream['label']);
			$vidindex++;

            $hide_real_filepath = true;
			}
		}
	
	// Add in each of the videojs_resolution_selection items that use alternative files	for previews
	$s_count = is_array($videojs_resolution_selection) ? count($videojs_resolution_selection) : 0;
	for($s=0;$s<$s_count;$s++)
		{
		if($videojs_resolution_selection[$s]['name']=='' && isset($video_preview_path))
			{
			// The default source was set earlier, just update the label
			$video_preview_sources[0]["label"]=isset($videojs_resolution_selection[$s]['label'])?$videojs_resolution_selection[$s]['label']:"";
			}
		else{
			$alt_data=sql_query("select * from resource_alt_files where resource='" . escape_check($ref) . "' and name='{$videojs_resolution_selection[$s]['name']}'");
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
		case "smallthumbs":
			$width="75";
			$height=75/$ffmpeg_preview_max_width*$ffmpeg_preview_max_height;
			break;
	}
}
// play video on hover?
$play_on_hover=false;
if(($pagename=='search' && $video_search_play_hover) || ($pagename=='view' && $video_view_play_hover) || (($pagename=='preview' || $pagename=='preview_all') && $video_preview_play_hover))
	{
	$play_on_hover=true;
	}
	
// using keyboard hotkeys?
$playback_hotkeys=false;
if(($pagename=='search' && $keyboard_navigation_video_search) || ($pagename=='view' && $keyboard_navigation_video_view) || (($pagename=='preview' || $pagename=='preview_all') && $keyboard_navigation_video_preview))
	{
	$playback_hotkeys=true;
	}

if(!hook("swfplayer"))
	{
	if (!$videojs && isset($video_preview_sources[0]["url"])) 
		{ ?>
		<object type="application/x-shockwave-flash" data="<?php echo $baseurl_short?>lib/flashplayer/player_flv_maxi.swf?t=<?php echo time() ?>" width="<?php echo $width?>" height="<?php echo $height?>" class="Picture">
		     <param name="allowFullScreen" value="true" />
		     <param name="movie" value="<?php echo $baseurl_short?>lib/flashplayer/player_flv_maxi.swf" />
		     <param name="FlashVars" value="flv=<?php echo $video_preview_sources[0]["url_encoded"]; ?>&amp;width=<?php echo $width?>&amp;height=<?php echo $height?>&amp;margin=0&amp;showvolume=1&amp;volume=200&amp;showtime=2&amp;autoload=1&amp;<?php if ($pagename!=="search"){?>showfullscreen=1<?php } ?>&amp;showstop=1&amp;buttoncolor=<?php echo $buttoncolor?>&playercolor=<?php echo $color?>&bgcolor=<?php echo $color?>&bgcolor1=<?php echo $bgcolor1?>&bgcolor2=<?php echo $bgcolor2?>&startimage=<?php echo $thumb?>&playeralpha=75&autoload=1&buffermessage=&buffershowbg=0" />
		</object>
		<?php 
		} 
	else 
		{ 
		global $ffmpeg_preview_extension,$css_reload_key,$context,$video_preview_hls_support;
		?>
		<link href="<?php echo $baseurl_short?>lib/videojs/video-js.min.css?r=<?=$css_reload_key?>" rel="stylesheet">
		<script src="<?php echo $baseurl_short?>lib/videojs/video.min.js?r=<?=$css_reload_key?>"></script>
		<script src="<?php echo $baseurl_short?>lib/js/videojs-extras.js?r=<?=$css_reload_key?>"></script>
		<?php
		if($video_preview_hls_support!=0)
			{
			?>
			<script src="<?php echo $baseurl_short?>lib/js/videojs-contrib-hls.js?<?php echo $css_reload_key?>"></script>
			<?php		
			}
		if(isset($videojs_resolution_selection))
			{
			?>
  			<link href="<?php echo $baseurl_short?>lib/videojs-resolution-switcher/videojs-resolution-switcher.css?r=<?=$css_reload_key?>" rel="stylesheet">
  			<script src="<?php echo $baseurl_short?>lib/videojs-resolution-switcher/videojs-resolution-switcher.js?r=<?=$css_reload_key?>"></script>
  			<?php
  			}
			
			
			
  		?>
		<!-- START VIDEOJS -->
		<div class="videojscontent">
			<video 
				id="<?php echo $context ?>_<?php echo $display ?>_introvideo<?php echo $ref?>"
				controls
				data-setup='{ 
					<?php if($play_on_hover){?>
						"loadingSpinner" : false,
						"TextTrackDisplay" : true,
						"nativeTextTracks": false,
						"children": { 
							"bigPlayButton":false, 
							<?php if($pagename=='search' && $display=='smallthumbs'){?>
								"controlBar": false
							<?php }
							else{ ?>
								"controlBar": { 
									"children": { 
										"playToggle": false, 
										"volumeControl":false
									}
								}
							<?php } ?>
						}
					<?php }
					if(isset($videojs_resolution_selection) && count($video_preview_sources)>0)
						{?>
						"plugins": {
								"videoJsResolutionSwitcher": {
								  "default": "<?php echo $videojs_resolution_selection_default_res?>"
								  <?php
								  if($videojs_resolution_selection_dynamicLabel)
								  	{
								  	?>,
								  	"dynamicLabel": true
								  	<?php
								  	}
								  	?>
								}
							  }
					<?php } ?>
				}'
				preload="<?php echo $preload?>"
				width="<?php echo $width?>" 
				height="<?php echo $height?>" 
				class="video-js vjs-default-skin vjs-big-play-centered <?php if($pagename=='search'){echo "video-$display";}?>" 
				poster="<?php echo $thumb_raw?>"
				<?php if($play_on_hover){ ?>
					onmouseout="videojs_<?php echo $context ?>_<?php echo $display ?>_introvideo<?php echo $ref ?>[0].pause();"
					onmouseover="videojs_<?php echo $context ?>_<?php echo $display ?>_introvideo<?php echo $ref ?>[0].play();"
				<?php } ?>
				>
				<?php				
				foreach($video_preview_sources as $video_preview_source)
					{
					?>
					<source src="<?php echo $video_preview_source["url"] ?>" type='<?php echo $video_preview_source["type"]; ?>' label='<?php echo ($video_preview_source["label"]!=""?$video_preview_source["label"]:$lang["preview"]); ?>'/>
					<?php	
					}?>
				<p class="vjs-no-js">To view this video please enable JavaScript, and consider upgrading to a web browser that <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a></p>
				<?php hook("html5videoextra"); ?>
			</video>
		
		<?php if($play_on_hover){ ?>	
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
		<?php
		}
	}
