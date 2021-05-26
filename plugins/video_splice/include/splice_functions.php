<?php
function generate_merged_video($videos, $video_splice_type, $target_video_command, $target_video_extension, $target_audio, $target_width, $target_height, $target_frame_rate, $description)
    {
    include_once __DIR__ . "/../../../include/image_processing.php";

    global $ffmpeg_global_options, $videosplice_description_field, $username, $scramble_key, $ffmpeg_std_video_options, $ffmpeg_std_audio_options, $ffmpeg_std_frame_rate_options, $video_export_folder, $offline_job_queue, $download_chunk_size;

    // Build up the ffmpeg command
    $ffmpeg_fullpath = get_utility_path("ffmpeg");
    $ffprobe_fullpath = get_utility_path("ffprobe");
    $randstring=md5(rand() . microtime());

    $target_order_count = 0;
    $target_completed_locations = array();

    $video_refs = array_column($videos, 'ref');
    $target_temp_location = get_temp_dir(false,"splice/" . implode("-", $video_refs) . "_" . md5($username . $randstring . $scramble_key));

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

    if ($video_splice_type == "video_splice_save_new") 
        {
        // Create new resource based around the first resources metadata
        $ref = copy_resource($videos[0]["ref"]);
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

        return $ref;
        }

    if ($video_splice_type == "video_splice_save_export") 
        {
        // Get parent array keys as they are shorter and tidier
        $filename_video = array_keys($ffmpeg_std_video_options)[array_search($target_video_command, array_column($ffmpeg_std_video_options, 'command'))];
        $filename_audio = array_keys($ffmpeg_std_audio_options)[array_search($target_audio, array_column($ffmpeg_std_audio_options, 'command'))];
        $filename_framerate = array_keys($ffmpeg_std_frame_rate_options)[array_search($target_frame_rate, array_column($ffmpeg_std_frame_rate_options, 'value'))];

        // Save into export directory
        $export_folder_location = $video_export_folder . DIRECTORY_SEPARATOR . implode("-", $video_refs) . "_" . safe_file_name($filename_video . "_" . $filename_audio . "_" . $target_width . "x" . $target_height . "_" . str_replace(".", "-", $filename_framerate)) . "." . $target_video_extension;  
        rename($target_temp_location . "/merged." . $target_video_extension, $export_folder_location);
        rmdir($target_temp_location);

        return true;
        }

    if ($video_splice_type == "video_splice_download") 
        {
        // Move to download directory 
        $filename = implode("-", $video_refs) . "_" . md5($username . $randstring . $scramble_key) . "." . $target_video_extension;
        $download_file_location = get_temp_dir(false,"user_downloads/") . $filename;
        rename($target_temp_location . "/merged." . $target_video_extension, $download_file_location);
        rmdir($target_temp_location);

        if($offline_job_queue)
            { 
            // $job_data=array();
            // $job_success_lang=$lang["download_file_created"]  . " - " . str_replace(array('%ref','%title'),array($ref,$resource['field' . $view_title_field]),$lang["ref-title"]);
            // $job_failure_lang=$lang["download_file_creation_failed"] . " - " . str_replace(array('%ref','%title'),array($ref,$resource['field' . $view_title_field]),$lang["ref-title"]);
            // $job_data["resource"]=$ref;
            // $job_data["command"]=$shell_exec_cmd;    
            // $job_data["outputfile"]=$download_file_location;    
            // $job_data["url"]=$baseurl . "/pages/download.php?userfile=" . $ref . "_" . $randstring . "." . $video_track_command["extension"];
            // $job_data["lifetime"]=$download_file_lifetime;
            // $job_code=$ref . $userref . md5($job_data["command"]); // unique code for this job, used to prevent duplicate job creation
            // $jobadded=job_queue_add("create_download_file",$job_data,$userref,'',$job_success_lang,$job_failure_lang,$job_code);
            // if($jobadded!==true)
            //     {
            //     $message =  $jobadded;  
            //     }
            // else
            //     {
            //     $message=$lang["video_tracks_offline_notice"];
            //     }
            }
        else
            {  
            // Download file
            $filesize = filesize_unlimited($download_file_location);
            ob_flush();
            
            header(sprintf('Content-Disposition: attachment; filename="%s"', $filename));
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
            #Delete File:
            unlink($download_file_location);
            }

        return true;
        }
    }