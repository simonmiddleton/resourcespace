<?php

function HookVideo_tracksDownloadModifydownloadpath()
    {
    $video_track_string=getval("video_tracks_export","");
    if($video_track_string!="")
        {
        global $path, $userref;
        $video_track_details=json_decode(base64_decode($video_track_string),true);
        if($video_track_details[0] !=0 && $video_track_details[0]!=$userref){return false;}

        if (strpos($video_track_details[1], '..') !== false || (isset($video_track_details[2]) && strpos($video_track_details[2], '..') !== false))
            {
            // Block path traversal.
            return false;
            }

        // New job which has a randomised basename? Use it instead {@see create_video.php}
        if(isset($video_track_details[2]))
            {
            // This is done so users don't download a file with a random name.
            $GLOBALS['filename'] = $video_track_details[1];
            $path = get_temp_dir(false, 'video_tracks_exports') . DIRECTORY_SEPARATOR . $video_track_details[2];
            }
        else
            {
            $path = get_temp_dir(false, 'video_tracks_exports') . DIRECTORY_SEPARATOR . $video_track_details[1];
            }

        return true;
        }       
    return false;
    }

