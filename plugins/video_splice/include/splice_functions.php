<?php
function generate_merged_video($videos, $video_splice_type, $target_video_command, $target_video_extension, $target_audio, $target_width, $target_height, $target_frame_rate, $description)
    {
    include_once __DIR__ . "/../../../include/image_processing.php";

    global $ffmpeg_global_options, $videosplice_description_field, $username, $scramble_key;

    // Build up the ffmpeg command
    $ffmpeg_fullpath = get_utility_path("ffmpeg");
    $ffprobe_fullpath = get_utility_path("ffprobe");
    $randstring=md5(rand() . microtime());

    $target_order_count = 0;
    $target_completed_locations = array();

    $ref = copy_resource($videos[0]["ref"]); # Base new resource on first video (top copy metadata).
    $target_temp_location = get_temp_dir(false,"splice/" . $ref . "_" . md5($username . $randstring . $scramble_key));
    $resource_location = get_resource_path(
        $ref,
        true,
        "",
        true,
        $target_video_extension,
        -1,
        1,
        false,
        "",
        -1,
        false
    );

    if(!empty($description))
    {
    update_field($ref, $videosplice_description_field, $description);
    }

    foreach ($videos as $video) 
        {
        $filesource = get_resource_path($video["ref"],true,"",false,get_resource_data($video["ref"])["file_extension"]);
        $has_no_audio = empty(run_command($ffprobe_fullpath . " -i " . escapeshellarg($filesource) . " -show_streams -select_streams a -loglevel error", true))?"_noaudio":"";  
        $target_completed_location = $target_temp_location . "/" . $target_order_count . $has_no_audio . "." . $target_video_extension; 

        $video_splice_options = '-f ' . $target_video_command . ' ' . $target_audio . ' -vf "fps=' . $target_frame_rate . ',scale=' . $target_width . ':' . $target_height . ':force_original_aspect_ratio=decrease,pad=' . $target_width . ':' . $target_height . ':(ow-iw)/2:(oh-ih)/2" -sws_flags lanczos';
        $video_splice_command = $ffmpeg_fullpath . " " . $ffmpeg_global_options . " -i " . escapeshellarg($filesource) . " " . $video_splice_options . " " . $target_completed_location;

        $output=run_command($video_splice_command);

        if(!empty($has_no_audio))
            {
            $no_audio_command = $ffmpeg_fullpath . " -i " . escapeshellarg($target_completed_location) . " -f lavfi -i anullsrc -vcodec copy " . $target_audio . " -shortest " . str_replace("_noaudio","",$target_completed_location);
            $output=run_command($no_audio_command);
            unlink($target_completed_location);
            $target_completed_location = str_replace("_noaudio","",$target_completed_location);
            }
        $target_completed_locations[] = $target_completed_location;
        $target_order_count++;
        }

    $list_file_command = "";

    foreach ($target_completed_locations as $target_completed_location) 
        {
        // Build list file contents
        $list_file_command .= "file '" . $target_completed_location . "'\n";
        }

    file_put_contents($target_temp_location . "/list.txt", $list_file_command);

    $merge_command = "ffmpeg -f concat -safe 0 -i '" . $target_temp_location . "/list.txt" . "' -c copy '" . $target_temp_location . "/merged." . $target_video_extension . "'";

    $output=run_command($merge_command);

    // Tidy up as we go along now final file created
    unlink($target_temp_location . "/list.txt");
    foreach ($target_completed_locations as $target_completed_location) 
        {
        unlink($target_completed_location);
        }

    // Place new merged file in resource location
    rename($target_temp_location . "/merged." . $target_video_extension, $resource_location);
    rmdir($target_temp_location);
    create_previews($ref,false,$target_video_extension);

    return $ref;
    }