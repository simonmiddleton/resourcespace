<?php
function generate_merged_video($videos, $video_splice_type, $target_video_command, $target_video_extension, $target_audio_command, $target_width, $target_height, $target_frame_rate, $description, $auto_populate_video_info, $offline)
    {
    include_once __DIR__ . "/../../../include/image_processing.php";

    global $ffmpeg_global_options, $videosplice_resourcetype, $videosplice_description_field, $videosplice_video_bitrate_field, $videosplice_video_size_field, $videosplice_frame_rate_field, $videosplice_aspect_ratio_field, $username, $scramble_key, $ffmpeg_std_video_options, $ffmpeg_std_audio_options, $ffmpeg_std_frame_rate_options, $video_export_folder, $download_chunk_size, $userref, $date_field, $lang;

    // Grab ffmpeg and ffprobe paths
    $ffmpeg_fullpath = get_utility_path("ffmpeg");
    $ffprobe_fullpath = get_utility_path("ffprobe");

    // Generate random string for filename usage
    $randstring=md5(rand() . microtime());

    $target_order_count = 0;
    $target_completed_locations = array();

    $video_refs = array_column($videos, 'ref');

    // Generate temp location to do re encoding, adding blank audio and splicing
    $target_temp_location = get_temp_dir(false,"splice/" . implode("-", $video_refs) . "_" . md5($username . $randstring . $scramble_key));

    // Loop through original videos creating new ones encoding as per options
    foreach ($videos as $video)
        {
        $filesource = get_resource_path($video["ref"],true,"",false,get_resource_data($video["ref"])["file_extension"]);
        $has_no_audio = empty(run_command($ffprobe_fullpath . " -i " . $filesource . " -show_streams -select_streams a -loglevel error", true))?"_noaudio":"";
        $target_completed_location = $target_temp_location . "/" . $target_order_count . $has_no_audio . "." . $target_video_extension;

        // SECURITY NOTE: The target_video_command and target_audio_command are not escaped as they contain multiple option/value combinations thus cannot be escaped
        $video_splice_options = '-f ' . $target_video_command . ' ' . $target_audio_command . ' -vf "fps=' . escapeshellarg($target_frame_rate) . ',scale=' . escapeshellarg($target_width) . ':' . escapeshellarg($target_height) . ':force_original_aspect_ratio=decrease,pad=' . escapeshellarg($target_width) . ':' . escapeshellarg($target_height) . ':(ow-iw)/2:(oh-ih)/2" -sws_flags lanczos';
        $video_splice_command = $ffmpeg_fullpath . " " . $ffmpeg_global_options . " -i " . $filesource . " " . $video_splice_options . " " . escapeshellarg($target_completed_location);

        $output=run_command($video_splice_command);

        // If file has no audio channels create blank audio channel to ensure all video files contain audio thus wont loose audio on videos that have it
        if(!empty($has_no_audio))
            {
            $no_audio_command = $ffmpeg_fullpath . " -i " . escapeshellarg($target_completed_location) . " -f lavfi -i anullsrc -vcodec copy " . $target_audio_command . " -shortest " . str_replace("_noaudio","",escapeshellarg($target_completed_location));
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
        // Build list file contents (easiest way to merge videos)
        $list_file_command .= "file '" . $target_completed_location . "'\n";
        }

    file_put_contents($target_temp_location . "/list.txt", $list_file_command);

    // Merge video files using list file
    $merge_command = $ffmpeg_fullpath . " -f concat -safe 0 -i '" . $target_temp_location . "/list.txt" . "' -c copy '" . $target_temp_location . "/merged." . escapeshellarg($target_video_extension) . "'";
    $output=run_command($merge_command);

    // Tidy up as we go along now final file created
    unlink($target_temp_location . "/list.txt");
    foreach ($target_completed_locations as $target_completed_location)
        {
        unlink($target_completed_location);
        }

    if ($video_splice_type == "video_splice_save_new")
        {
        // Create new blank resource with the type specified in the config
        $ref = create_resource($videosplice_resourcetype, 0);
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

        // Place new merged file in resource location
        rename($target_temp_location . "/merged." . $target_video_extension, $resource_location);
        rmdir($target_temp_location);
        create_previews($ref,false,$target_video_extension);

        // Add file extension to db so front end recognises the file
        sql_query("update resource set file_extension='" . $target_video_extension . "' where ref='$ref'");

        // Add current date to date field
        update_field($ref, $date_field, date("Y-m-d H:i:s"));

        // If description provided add it
        if(!empty($description))
        {
        update_field($ref, $videosplice_description_field, $description);
        }

        // If user wants video information auto populated then add it
        if($auto_populate_video_info)
            {
            update_field($ref, $videosplice_video_bitrate_field, array_keys($ffmpeg_std_video_options)[array_search($target_video_command, array_column($ffmpeg_std_video_options, 'command'))]);
            update_field($ref, $videosplice_video_size_field, $target_width . "x" . $target_height);
            update_field($ref, $videosplice_frame_rate_field, array_keys($ffmpeg_std_frame_rate_options)[array_search($target_frame_rate, array_column($ffmpeg_std_frame_rate_options, 'value'))]);

            // Quick way to figure out standard ratios
            $ratio = substr($target_width > $target_height?$target_width / $target_height:$target_height / $target_width, 0, 3);
            switch ($ratio)
                {
                case 1.3:
                    $aspect_ratio = "4:3";
                    break;

                case 1.6:
                    $aspect_ratio = "16:10";
                    break;

                case 1.7:
                    $aspect_ratio = "16:9";
                    break;
                }

            if(isset($aspect_ratio))
                {
                update_field($ref, $videosplice_aspect_ratio_field, $aspect_ratio);
                }
            }

        return $ref;
        }

    elseif ($video_splice_type == "video_splice_save_export")
        {
        // Save into export directory
        $export_filename = implode("-", $video_refs) . "_" . md5($username . $randstring . $scramble_key) . "." . $target_video_extension;
        $export_folder_location = $video_export_folder . DIRECTORY_SEPARATOR . $export_filename;
        rename($target_temp_location . "/merged." . $target_video_extension, $export_folder_location);
        rmdir($target_temp_location);

        return true;
        }

    elseif ($video_splice_type == "video_splice_download")
        {
        // Move to download directory
        $download_filename = $userref . "_" . md5($username . $randstring . $scramble_key) . "." . $target_video_extension;
        $download_file_location = get_temp_dir(false,"user_downloads/") . $download_filename;
        rename($target_temp_location . "/merged." . $target_video_extension, $download_file_location);
        rmdir($target_temp_location);

        if($offline)
            {
            // For offline we want to return the location of the file so it can be added as a link in the user message
            return $userref . "_" . $randstring . "." . $target_video_extension;
            }
        else
            {
            // Download file
            $filesize = filesize_unlimited($download_file_location);
            ob_flush();

            header(sprintf('Content-Disposition: attachment; filename="%s"', $download_filename));
            header("Content-Length: " . $filesize);
            set_time_limit(0);

            $sent = 0;
            $handle = fopen($download_file_location, "r");

            // Now we need to loop through the file and echo out chunks of file data
            while($sent < $filesize)
                {
                echo fread($handle, $download_chunk_size);
                ob_flush();
                $sent += $download_chunk_size;
                }
            // Delete File
            fclose($handle);
            unlink($download_file_location);
            }

        return true;
        }

    // if one of the above options has not worked then return false
    return false;
    }