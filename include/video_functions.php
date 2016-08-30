<?php
/**
* Get video resolution using FFMpeg
* 
* @param string $ffmpeg_fullpath
* @param string $file            Path to video file
* 
* @return array
*/
function get_video_resolution($ffmpeg_fullpath, $file)
    {
    $video_resolution = array(
        'width'  => 0,
        'height' => 0,
    );

    $cmd    = $ffmpeg_fullpath . ' -i ' . escapeshellarg($file) . " 2>&1 | grep Stream | grep -oP ', \K[0-9]+x[0-9]+'";
    $output = run_command($cmd, true);

    $video_resolution_pieces = explode('x', $output);
    if(2 === count($video_resolution_pieces))
        {
        $video_resolution['width']  = $video_resolution_pieces[0];
        $video_resolution['height'] = $video_resolution_pieces[1];
        }

    return $video_resolution;
    }