<?php

ob_start(); 

$nocache = true;

include_once("../../../include/db.php");
include_once("../../../include/general.php");
include_once("../../../include/authenticate.php");
include_once("../../../include/collections_functions.php");
include_once("../../../include/search_functions.php");
include_once("../../../include/resource_functions.php");
include_once("../../../include/image_processing.php");

$temp_dir       = get_temp_dir();
$ref            = getvalescaped("ref", 0, true);
$alternatives   = get_alternative_files($ref);

# Form Submitted

if(getval("submit", "") != "" && enforcePostRequest(false))
{

    $ffmpeg_fullpath = get_utility_path("ffmpeg");

    for($n = 1; $n <= 2; $n++)
    {
        $alt = getval("image{$n}", -1, true);

        if($alt != "")
        {
            $extension = "";
            foreach($alternatives as $alternative)
            {
                if($alternative["ref"] == $alt)
                {
                    $extension = $alternative["file_extension"];         
                }
            }                 
        }

        $image = get_resource_path($ref, true, "", false, $extension, true, 1, false, '', $alt);

        # Encode images into videos to be stiched together later (add silent audio track)

        $path[$n] = "{$temp_dir}/video_bookend_temp_alt_{$alt}.mp4";

        $shell_exec_cmd  = $ffmpeg_fullpath . " -y -loop 1 -i " . escapeshellarg($image);
        $shell_exec_cmd .= " -f lavfi -i aevalsrc=0 -c:v libx264";
        // Playback Issues for Quicktime/Other Codecs - FFMpeg suggests "-pix_fmt yuv420p"
        // Please make sure to use images which have sizes divisible by 2. As required by libx264, the "divisible 
        // by 2" for width and height is needed for YUV 4:2:0 chroma subsampled outputs
        $shell_exec_cmd .= " -pix_fmt yuv420p -c:a mp3";
        $shell_exec_cmd .= " -t " . escapeshellarg($video_bookend_seconds) . " ";
        $shell_exec_cmd .= escapeshellarg($path[$n]);
        exec($shell_exec_cmd);

    }

    # Join videos (this will stitch the 2 image => vide files into one (variable $final)
    
    $final = "{$temp_dir}/video_bookend_temp_final_{$ref}.mp4";
    $resource = get_resource_data($ref);
    $source = get_resource_path($ref, true, "", false, $resource["file_extension"]);

    # Build text file so it works with mp4. Also don't use the original mp4 for source as this just freezes when outputed

    $source_pre = get_resource_path($ref,true,"pre",false,$ffmpeg_preview_extension);
    $myfile = fopen("/var/www/html/trunk/include/../filestore/tmp/bookend-videos.txt", "w");
    $txt  = "file '$path[1]' \n";
    $txt .= "file '$source_pre' \n";
    $txt .= "file '$path[2]' \n";
    fwrite($myfile, $txt);
    fclose($myfile);
    
    # ffmpeg join using text file (that stores the files to be joined)

    $shell_exec_cmd  = 'ffmpeg -f concat -safe 0 ';
    $shell_exec_cmd .= '-i /var/www/html/trunk/include/../filestore/tmp/bookend-videos.txt ';
    $shell_exec_cmd .= '-c copy '. $final .'';
    exec($shell_exec_cmd);

    # Delete the previews (and final file when downloaded)

    $final_video_file_size = filesize_unlimited($final);

    # Videos have been joined successfully, send file to user

    if(file_exists($final) && $final_video_file_size > 0)
    {
        $file_handle = fopen($final, 'rb');

        if($file_handle === false)
        {
            $error = $lang["bookend-could-not-open-file"];
        }

        ob_end_clean();
        header("Content-Disposition: attachment; filename=\"bookend_final_{$ref}.mp4\"");
        header("Content-Type: video/mp4");
        header("Content-Length: {$final_video_file_size}");

        $sent = 0;

        while($sent < $final_video_file_size)
        {
            echo fread($file_handle, $download_chunk_size);
            ob_flush();
            flush();
            $sent += $download_chunk_size;
            if(0 != connection_status()) { break; }
        }

        fclose($file_handle);

        @unlink("/var/www/html/trunk/include/../filestore/tmp/bookend-videos.txt");
        @unlink($path[1]);
        @unlink($path[2]);
        @unlink($final);

        exit();
        
    }
    else
    {
        $error = $lang["bookend-failed"];
    }

}

# Display page

include("../../../include/header.php");

if(isset($error))
{
    print '<div class="PageInformal">' . htmlspecialchars($error) . '</div>';
}

print '<h1>' . htmlspecialchars($lang["bookend"]) . '</h1>';
print '<p>' . htmlspecialchars($lang["bookend-intro"]) . '</p>';
print '<form method="post">';

generateFormToken("video_bookend");

for($n = 1; $n <= 2; $n++)
{
    print '<div class="Question">';
    print '<label for="">' . $lang["bookend-image-" . $n] . '</label>';
    print '<select name="image' . $n .'" id="image' . $n . '">';
    print '<option value="">' . $lang["bookend-noimage-" . $n] . '</option>';
    
    foreach ($alternatives as $alternative)
    {
        print '<option value="' . $alternative["ref"] . '">' . htmlspecialchars($alternative["name"]) . '</option>';
    }

    print '</select>';
    print '<div class="clearerleft"></div>';
    print '</div>';
}

print '<div class="QuestionSubmit">';
print '<input type="submit"  name="submit" value="' . $lang["action-download"] .'" style="width:150px;">';
print '</div>';
print '</form>';

include "../../../include/footer.php";