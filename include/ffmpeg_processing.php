<?php

if (!defined("RUNNING_ASYNC")) {define("RUNNING_ASYNC", !isset($ffmpeg_preview));}

if (RUNNING_ASYNC)
    {
    if(!isset($_SERVER['HTTP_HOST']) && isset($_SERVER['argv'][8]))
        {
        $_SERVER['HTTP_HOST'] = $_SERVER['argv'][8];
        }

    require dirname(__FILE__)."/boot.php";
    
    if (empty($_SERVER['argv'][1]) || $scramble_key!==$_SERVER['argv'][1]) {exit("Incorrect scramble_key");}
    
    if (empty($_SERVER['argv'][2])) {exit("Ref param missing");}
    $ref=$_SERVER['argv'][2];
    
    if (empty($_SERVER['argv'][3])) {exit("File param missing");}
    $file=$_SERVER['argv'][3];
    
    if (empty($_SERVER['argv'][4])) {exit("Target param missing");}
    $target=$_SERVER['argv'][4];
    
    if (!isset($_SERVER['argv'][5])) {exit("Previewonly param missing");}
    $previewonly=$_SERVER['argv'][5];
    
    if (!isset($_SERVER['argv'][6])) {exit("Snapshottime param missing");}
    $snapshottime=$_SERVER['argv'][6];

    if (!isset($_SERVER['argv'][7])) {exit("Alternative param missing");}
    $alternative=$_SERVER['argv'][7];

    debug ("Starting ffmpeg_processing.php async with parameters: ref=$ref, file=$file, target=$target, previewonly=$previewonly, snapshottime=$snapshottime, alternative=$alternative",$ref);

    ps_query("UPDATE resource SET is_transcoding = 1 WHERE ref = ?", array("i", $ref));
    }

if(!is_numeric($ref))
    {
    trigger_error("Parameter 'ref' must be numeric!");
    }

# Increase timelimit
set_time_limit(0);

$ffmpeg_fullpath = get_utility_path("ffmpeg");

if($generateall) {
    # Create a preview video
    $targetfile=get_resource_path($ref,true,"pre",false,$ffmpeg_preview_extension,-1,1,false,"",$alternative); 

    $snapshotsize=getimagesize($target);
    $width=$snapshotsize[0];
    $height=$snapshotsize[1];
    $sourcewidth=$width;
    $sourceheight=$height;
    $deletefiles = array();
    global $config_windows, $ffmpeg_get_par;
    if ($ffmpeg_get_par)
        {
        $par = 1;
        # Find out the Pixel Aspect Ratio
        $shell_exec_cmd = $ffmpeg_fullpath . " -i " . escapeshellarg($file) . " 2>&1";
        
        if (isset($ffmpeg_command_prefix))
        {$shell_exec_cmd = $ffmpeg_command_prefix . " " . $shell_exec_cmd;}
        
        if ($config_windows)
            {
            # Windows systems have a hard time with the long paths used for video generation. This work-around creates a batch file containing the command, then executes that.
            $tmp_ffmpeg_file = get_temp_dir() . "/ffmpeg_" . $ref . "_" . uniqid() . ".bat";
            file_put_contents($tmp_ffmpeg_file,$shell_exec_cmd);
            $shell_exec_cmd = $tmp_ffmpeg_file;
            $deletefiles[] = $tmp_ffmpeg_file;
            }
        
        $output=run_command($shell_exec_cmd);
            
        preg_match('/PAR ([0-9]+):([0-9]+)/m', $output, $matches);
        
        if (intval($matches[1]??0) > 0 && intval($matches[2]??0) > 0)
            {
            $par = $matches[1] / $matches[2];
            if($par < 1)
                {
                $width = ceil($width * $par);
                }
            elseif($par > 1)
                {
                $height = ceil($height / $par);
                }
            }
        }

    if($height<$ffmpeg_preview_min_height)
        {
        $height=$ffmpeg_preview_min_height;
        }

    if($width<$ffmpeg_preview_min_width)
        {
        $width=$ffmpeg_preview_min_width;
        }

    if($height>$ffmpeg_preview_max_height)
        {
        $width=ceil($width*($ffmpeg_preview_max_height/$height));
        $height=$ffmpeg_preview_max_height;
        }
        
    if($width>$ffmpeg_preview_max_width)
        {
        $height=ceil($height*($ffmpeg_preview_max_width/$width));
        $width=$ffmpeg_preview_max_width;
        }
        
    # Frame size must be a multiple of two
    if ($width % 2){$width++;}
    if ($height % 2) {$height++;}

    /* Plugin hook to modify the output W & H before running ffmpeg. Better way to return both W and H at the same is appreciated.  */
    $tmp = hook("ffmpegbeforeexec", "", array($ffmpeg_fullpath, $file));
    if (is_array($tmp) && $tmp) {
        list($width, $height) = $tmp;
    }

    if (hook("replacetranscode","",array($file,$targetfile,$ffmpeg_global_options,$ffmpeg_preview_options,$width,$height)))
        {
        exit(); // Do not proceed, replacetranscode hook intends to avoid everything below
        }

    if ($extension == 'gif')
        {
        global $ffmpeg_preview_gif_options;
        $ffmpeg_preview_options = $ffmpeg_preview_gif_options;
        }

    $shell_exec_cmd = $ffmpeg_fullpath . " $ffmpeg_global_options -y -i " . escapeshellarg($file) . " $ffmpeg_preview_options -t $ffmpeg_preview_seconds -s {$width}x{$height} " . escapeshellarg($targetfile);
    
    if (isset($ffmpeg_command_prefix))
        {
        $shell_exec_cmd = $ffmpeg_command_prefix . " " . $shell_exec_cmd;
        }
    
    $tmp = hook("ffmpegmodpreparams", "", array($shell_exec_cmd, $ffmpeg_fullpath, $file));
    if ($tmp)
        {
        $shell_exec_cmd = $tmp;
        }
    
    // Store the command so it can be tweaked if required
    $ffmpeg_command = $shell_exec_cmd;
    
    if ($config_windows)
        {
        # Windows systems have a hard time with the long paths used for video generation. This work-around creates a batch file containing the command, then executes that.
        $tmp_ffmpeg_file = get_temp_dir() . "/ffmpeg_" . $ref . "_" . uniqid() . ".bat";
        file_put_contents($tmp_ffmpeg_file,$shell_exec_cmd);
        $shell_exec_cmd = $tmp_ffmpeg_file;
        }
    
    $output=run_command($shell_exec_cmd);
    
    if (!file_exists($targetfile))
        {
        // Check if trying to create MP4 file as this may require the '-strict experimental' flag due to the AAC codec required for most MP4 web video
        if($ffmpeg_preview_extension == "mp4" && strpos($ffmpeg_preview_options,"experimental") == false)
            {
            $shell_exec_cmd = str_replace($ffmpeg_preview_options,$ffmpeg_preview_options . " -strict experimental ",$ffmpeg_command);
            if ($config_windows)
                {
                file_put_contents($tmp_ffmpeg_file,$shell_exec_cmd . '" > NUL"');
                $shell_exec_cmd = $tmp_ffmpeg_file;
                $deletefiles[] = $tmp_ffmpeg_file;
                }
            }
        
        $output=run_command($shell_exec_cmd);
                
        if (!file_exists($targetfile))
            {
            debug("FFmpeg failed: " . $shell_exec_cmd);
            }
        }

    if (
        $ffmpeg_get_par 
        && (isset($snapshotcheck) 
        && $snapshotcheck==false)
        && $par > 0 
        && $par <> 1
        ) {
        # recreate snapshot with correct PAR
        $width=$sourcewidth;
        $height=$sourceheight;
        if($par < 1)
            {
            $width = ceil($sourcewidth * $par);
            }
        elseif($par > 1)
            {
            $height = ceil($sourceheight / $par);
            }
        # Frame size must be a multiple of two
        if ($width % 2){$width++;}
        if ($height % 2) {$height++;}
        $shell_exec_cmd = $ffmpeg_fullpath . "  $ffmpeg_global_options -y -i " . escapeshellarg($file) . " -s {$width}x{$height} -f image2 -vframes 1 -ss ".$snapshottime." " . escapeshellarg($target);
        $output = run_command($shell_exec_cmd);
        }

    if (!file_exists($targetfile))
        {
        debug("FFmpeg failed: ".$shell_exec_cmd);
        }

    # Handle alternative files.
    global $ffmpeg_alternatives;
    if (isset($ffmpeg_alternatives) && $generateall)
        {
        $ffmpeg_alt_previews=array();
        for($n=0;$n<count($ffmpeg_alternatives);$n++)
            {
            $generate=true;
            if (
                isset($ffmpeg_alternatives[$n]["lines_min"])
                # If this alternative size is larger than the source, do not generate.
               && $ffmpeg_alternatives[$n]["lines_min"] >= $sourceheight
                ) {
                $generate=false;
            }

            $tmp = hook("preventgeneratealt", "", array($file));
            if ($tmp===true) {$generate = false;}

            if ($generate) # OK to generate this alternative?
                {
                # Remove any existing alternative file(s) with this name.
                $existing = ps_query("select ref from resource_alt_files where resource = ? and name = ?", array("i", $ref, "s", $ffmpeg_alternatives[$n]["name"]));
                for ($m=0;$m<count($existing);$m++)
                    {
                    delete_alternative_file($ref,$existing[$m]["ref"]);
                    }

                $alt_type = '';
                if(isset($ffmpeg_alternatives[$n]['alt_type'])) {
                    $alt_type = $ffmpeg_alternatives[$n]["alt_type"];
                }

                # Create the alternative file.
                $aref=add_alternative_file($ref,$ffmpeg_alternatives[$n]["name"],'', '', '', 0, $alt_type);
                $apath=get_resource_path($ref,true,"",true,$ffmpeg_alternatives[$n]["extension"],-1,1,false,"",$aref);
                
                # Process the video 
                $shell_exec_cmd = $ffmpeg_fullpath . "  $ffmpeg_global_options -y -i " . escapeshellarg($file) . " " . $ffmpeg_alternatives[$n]["params"] . " " . escapeshellarg($apath);

                $tmp = hook("ffmpegmodaltparams", "", array($shell_exec_cmd, $ffmpeg_fullpath, $file, $n, $aref));
                if($tmp) {$shell_exec_cmd = $tmp;}
                
                $output = run_command($shell_exec_cmd);  

                if (file_exists($apath))
                    {
                    # Update the database with the new file details.
                    $file_size = filesize_unlimited($apath);    
                    ps_query("update resource_alt_files set file_name = ?, file_extension = ?, file_size = ?, creation_date = now() where ref = ?", 
                        array("s", $ffmpeg_alternatives[$n]["filename"] . "." . $ffmpeg_alternatives[$n]["extension"], "s", $ffmpeg_alternatives[$n]["extension"], "i", $file_size, "i", $aref));
                    // add this filename to be added to resource.ffmpeg_alt_previews
                    if (isset($ffmpeg_alternatives[$n]['alt_preview']) && $ffmpeg_alternatives[$n]['alt_preview']==true){
                        $ffmpeg_alt_previews[]=basename($apath);
                        }
                    }
                else 
                    {
                    # Remove the alternative file entries with this name as ffmpeg has failed to create file.
                    $existing = ps_query("select ref from resource_alt_files where resource = ? and name = ?", array("i", $ref, "s", $ffmpeg_alternatives[$n]["name"]));
                    for ($m=0;$m<count($existing);$m++)
                        {
                        delete_alternative_file($ref,$existing[$m]["ref"]);
                        }
                    }

                    if(!file_exists($apath) && file_exists($targetfile) && RUNNING_ASYNC) {
                        debug('FFmpeg alternative failed: ' . $shell_exec_cmd);
                        # Change flag as the preview was created and that is the most important of them all
                        ps_query("UPDATE resource SET is_transcoding = 0 WHERE ref = ?", array("i", $ref));
                    }
                }
        }
    }
}

if(isset($deletefiles))
    {
    foreach($deletefiles as $deletefile)
        {
        unlink($deletefile);
        }
    }

if (RUNNING_ASYNC)
    {
    ps_query("UPDATE resource SET is_transcoding = 0 WHERE ref = ?", array("i", $ref));
    
    if ($previewonly)
        {
        unlink($file);
        }
    }

