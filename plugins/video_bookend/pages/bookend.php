<?php
ob_start(); $nocache=true;
include_once "../../../include/db.php";
include_once "../../../include/general.php";
include_once "../../../include/authenticate.php";
include_once "../../../include/collections_functions.php";
include_once "../../../include/search_functions.php";
include_once "../../../include/resource_functions.php";
include_once "../../../include/image_processing.php";

$temp_dir     = get_temp_dir();
$ref          = getvalescaped("ref", 0, true);
$alternatives = get_alternative_files($ref);

if (getval("submit", "") != "" && enforcePostRequest(false))
    {
	$ffmpeg_fullpath = get_utility_path("ffmpeg");

    for($n = 1; $n <= 2; $n++)
        {
        $alt = getval("image{$n}", -1, true);

        if($alt != "")
            {
            # Find extension
            $extension = "";
            foreach($alternatives as $alternative)
                {
                if ($alternative["ref"]==$alt)
                    {
                    $extension=$alternative["file_extension"];
                    }
                }
            
            $image=get_resource_path($ref,true,"",false,$extension,true,1,false,'',$alt);
            
            # Encode video (add silent audio track)
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
        }

    # Join videos
    $final    = "{$temp_dir}/video_bookend_temp_final_{$ref}.mp4";
    $resource = get_resource_data($ref);
    $source   = get_resource_path($ref, true, "", false, $resource["file_extension"]);

    $shell_exec_cmd  = $ffmpeg_fullpath;
    $shell_exec_cmd .= " -i " . escapeshellarg($path[1]);
    $shell_exec_cmd .= " -i " . escapeshellarg($source);    
    $shell_exec_cmd .= " -i " . escapeshellarg($path[2]);
    $shell_exec_cmd .= " -filter_complex \"[0:v:0][0:a:0][1:v:0][1:a:0][2:v:0][2:a:0] concat=n=3:v=1:a=1 [outv] [outa]\"";
    $shell_exec_cmd .= " -map \"[outv]\" -map \"[outa]\" -strict -2 ";
    $shell_exec_cmd .= escapeshellarg($final);

    run_external($shell_exec_cmd, $return_code);

    $final_video_file_size = filesize_unlimited($final);

    // Videos have been joined successfully, send file to user
    if($return_code == 0 && file_exists($final) && $final_video_file_size > 0)
        {
        $file_handle = fopen($final, 'rb');

        if($file_handle === false)
            {
            $error = $lang["bookend-could-not-open-file"];
            }

        if(!isset($error))
            {
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

                if(0 != connection_status()) 
                    {
                    break;
                    }
                }
            fclose($file_handle);

            unlink($path[1]);
            unlink($path[2]);
            unlink($final);

            exit();
            }
        }

    unlink($path[1]);
    unlink($path[2]);

    $error = $lang["bookend-failed"];
    }

include "../../../include/header.php";

if(isset($error))
    {
    ?>
    <div class="PageInformal"><?php echo htmlspecialchars($error); ?></div>
    <?php
    }
?>
<h1><?php echo htmlspecialchars($lang["bookend"]);?></h1>
<p><?php echo htmlspecialchars($lang["bookend-intro"]); ?></p>
<form method="post">
<?php
generateFormToken("video_bookend");

for($n = 1; $n <= 2; $n++)
    {
    ?>
    <div class="Question">
        <label for=""><?php echo $lang["bookend-image-" . $n] ?></label>
        <select name="image<?php echo $n ?>" id="image<?php echo $n ?>">
            <option value=""><?php echo $lang["bookend-noimage-" . $n] ?></option>
            <?php
            foreach ($alternatives as $alternative)
                {
                ?>
                <option value="<?php echo $alternative["ref"] ?>"><?php echo htmlspecialchars($alternative["name"]); ?></option>
                <?php
                }
                ?>
        </select>
        <div class="clearerleft"></div>
    </div>
    <?php
    }
    ?>
    <div class="QuestionSubmit">
        <input type="submit"  name="submit" value="<?php echo $lang["action-download"]; ?>" style="width:150px;">
    </div>
</form>
<?php
include "../../../include/footer.php";