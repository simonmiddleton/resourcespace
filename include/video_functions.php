<?php

/**
* Get video resolution using FFMpeg
* 
* @uses get_video_info()
* 
* @param string $file Path to video file
* 
* @return array
*/
function get_video_resolution($file)
    {
    $video_resolution = array(
        'width'  => 0,
        'height' => 0,
    );

    $video_info = get_video_info($file);

    // Different versions of ffprobe store the dimensions in different parts of the json output
    if(!empty($video_info['width']))
        {
        $video_resolution['width'] = intval($video_info['width']);
        }

    if(!empty($video_info['height']))
        {
        $video_resolution['height'] = intval($video_info['height']);
        }
 
    if(isset($video_info['streams']) && is_array($video_info['streams']))
        {
        foreach( $video_info['streams'] as $stream)
            {
            if(!empty($stream['codec_type']) && 'video' === $stream['codec_type'])
                {
                $video_resolution['width']  = intval($stream['width']);
                $video_resolution['height'] = intval($stream['height']);
                break;
                }
            }
        }

    return $video_resolution;
    }

/**
 * Generate HTML to display subtitles in playback of a video resource. 
 *
 * @param  int   $ref      Resource ID
 * @param  int   $access   Resource access level - e.g. 0 for open access
 * 
 * @return void
 */
function display_video_subtitles($ref,$access)
    {
    global $alt_files_visible_when_restricted;
    $alt_access=hook("altfilesaccess");

    if ($access==0 || $alt_files_visible_when_restricted)
        {
        $alt_access=true; # open access (not restricted)
        }

    if ($alt_access)
        {
        $video_altfiles=get_alternative_files($ref);

        foreach ($video_altfiles as $video_altfile)
            {
            if(mb_strtolower($video_altfile["file_extension"]) =="vtt")
                {
                $video_altfile["path"] = get_resource_path($ref, false, '', true, $video_altfile["file_extension"], -1, 1, false, '',  $video_altfile["ref"]);
                ?>
                <track class="videojs_alt_track" kind="subtitles" src="<?php echo htmlspecialchars($video_altfile["path"]) ?>" label="<?php echo htmlspecialchars($video_altfile["description"]); ?>" ></track>
                <?php
                }
            }
        }
    }