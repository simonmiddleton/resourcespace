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