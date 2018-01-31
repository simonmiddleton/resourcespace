<?php
include_once "../../../include/db.php";
include_once "../../../include/general.php";
include_once "../../../include/authenticate.php";
include_once "../../../include/collections_functions.php";
include_once "../../../include/search_functions.php";
include_once "../../../include/resource_functions.php";
include_once "../../../include/image_processing.php";

$ref=getvalescaped("ref","");

# Fetch alternative files
$alternatives=get_alternative_files($ref);

if (getval("submit","")!="")
    {
    # Establish FFMPEG location.
	$ffmpeg_fullpath = get_utility_path("ffmpeg");

    $images=array();
    for($n=1;$n<=2;$n++)
        {            
        $alt=getval("image" . $n,"");
        if ($alt!="")
            {
            # Find extension
            $extension="";
            foreach($alternatives as $alternative)
                {
                if ($alternative["ref"]==$alt)
                    {
                    $extension=$alternative["file_extension"];
                    }
                }
            
            $image=get_resource_path($ref,true,"",false,$extension,true,1,false,'',$alt);
            // echo $image;
            
            # Encode video (add silent audio track)
            $path[$n] = get_temp_dir() . "/video_bookend_temp_alt_" . $alt. ".mp4";
            $shell_exec_cmd = $ffmpeg_fullpath . " -y -loop 1 -i " . escapeshellarg($image) . " -i aevalsrc=0 -c:v libx264 -c:a mp3 -t $video_bookend_seconds " . escapeshellarg($path[$n]);
            $output = exec($shell_exec_cmd);
            // echo "<p>" . $shell_exec_cmd . "</p>";
            }
        }

    # Combine
    $final= get_temp_dir() . "/video_bookend_temp_final_" . $ref. ".mp4";
    $resource=get_resource_data($ref);
    $source=get_resource_path($ref,true,"",false,$resource["file_extension"]);
    
    $shell_exec_cmd = $ffmpeg_fullpath;
    $shell_exec_cmd .= " -i " . escapeshellarg($path[1]);
    $shell_exec_cmd .= " -i " . escapeshellarg($source);    
    $shell_exec_cmd .= " -i " . escapeshellarg($path[2]);
    $shell_exec_cmd .= " -filter_complex \"[0:v:0][0:a:0][1:v:0][1:a:0][2:v:0][2:a:0]concat=n=3:v=1:a=1[outv][outa]\" -map \"[outv]\" -map \"[outa]\" ";
    $shell_exec_cmd .= escapeshellarg($final);
    
    echo $shell_exec_cmd;
    /*
    $output = exec($shell_exec_cmd);
    */
     # TO DO - Send file to user
     
     # TO DO - cleanup temporary files
     
    
    exit();
    }


include "../../../include/header.php";
?>

<h1><?php echo $lang["bookend"]?></h1>

<p><?php echo $lang["bookend-intro"] ?></p>

<form method="post">

<?php for ($n=1;$n<=2;$n++) { ?>
<div class="Question">
    <label for=""><?php echo $lang["bookend-image-" . $n] ?></label>
    <select name="image<?php echo $n ?>" id="image<?php echo $n ?>">
        <option value=""><?php echo $lang["bookend-noimage-" . $n] ?></option>
        <?php foreach ($alternatives as $alternative) { ?>
        
            <option value="<?php echo $alternative["ref"] ?>"><?php echo htmlspecialchars($alternative["name"]) ?></option>
        <?php } ?>
    </select>
    <div class="clearerleft"></div>
</div>
<?php } ?>

<div class="QuestionSubmit">
<input type="submit"  name="submit" value="<?php echo $lang["action-download"]?>" style="width:150px;">
</div>

</form>

<?php
include "../../../include/footer.php";


