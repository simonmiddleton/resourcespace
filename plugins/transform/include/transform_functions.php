<?php
/**
 * Perform the requested action on the original file to create a new file
 *
 * @param  mixed $sourcepath      Path to source file
 * @param  mixed $newpath           Path to new file
 * @param  mixed $actions           Array of actions to perform
 * @return boolean  Image created successfully?
 */
function transform_file($sourcepath, $outputpath, $actions)
    {
    global $imagemagick_colorspace, $imagemagick_preserve_profiles, $cropperestricted;
    global $cropper_allow_scale_up;
    global $image_quality_presets, $preview_no_flatten_extensions;
    global $exiftool_no_process;

    $command = get_utility_path("im-convert");
    if ($command==false)
        {
        exit("Could not find ImageMagick 'convert' utility.");
        }

    $imversion = get_imagemagick_version();
    // Set correct syntax for commands to remove alpha channel
    if($imversion[0] >= 7)
        {
        $alphaoff = "-alpha off";
        }
    else
        {
        $alphaoff = "+matte";
        }
    $sf_parts = pathinfo($sourcepath);
    $of_parts = pathinfo($outputpath);
    $commandprefix="";

    if(!isset($actions["rgb"]))
        {
        $profile=" +profile icc -colorspace " . $imagemagick_colorspace; # By default, strip the colour profiles ('+' is remove the profile, confusingly)
        if ($imagemagick_preserve_profiles)
            {
            $profile="";
            }
        }

    if(strtoupper($sf_parts["extension"]) == 'SVG')
        {
        list($origwidth, $origheight) = getSvgSize($sourcepath);
        }
    else
        {
        $origsizes  = getimagesize($sourcepath);
        $origwidth  = $origsizes[0];
        $origheight = $origsizes[1];
        }

    $keep_transparency=false;
    if (strtoupper($of_parts["extension"])=="PNG" || strtoupper($of_parts["extension"])=="GIF")
        {
        $commandprefix = " -background transparent ";
        $keep_transparency=true;
        $command .= $commandprefix . " \"$sourcepath\" ";
        }
    else
        {
        $command .= $commandprefix . " \"$sourcepath\"[0] ";
        }
     
    $quality = isset($actions["quality"]) ? $actions["quality"] : "";
    if ($quality != "" && in_array($quality,$image_quality_presets) && in_array(strtoupper($of_parts["extension"]) , array("PNG","JPG")))
        {
        $command .= " -quality " .  (int)$quality . "% ";
        }

    $colorspace1 = "";
    $colorspace2 = "";
    if(isset($actions["srgb"]))
        {
        if ($imversion[0]<6 || ($imversion[0] == 6 &&  $imversion[1]<7) || ($imversion[0] == 6 && $imversion[1] == 7 && $imversion[2]<5))
            {
            $colorspace1 = " -colorspace sRGB ";
            $colorspace2 =  " -colorspace RGB ";
            }
        else
            {
            $colorspace1 = " -colorspace RGB ";
            $colorspace2 =  " -colorspace sRGB ";
            }
        }

    if(isset($actions["resolution"]) && is_int_loose($actions["resolution"]) && $actions["resolution"] != 0)
        {
        $command .= " -units PixelsPerInch -density " .  $actions["resolution"] . " ";
        }

    if(in_array($sf_parts['extension'], $preview_no_flatten_extensions)
        || 
        (isset($actions["noflatten"]) && $actions["noflatten"] == "true")
        )
        {
        $flatten = "";
        }
    else
        {
        $flatten = "-flatten";
        }

    $command .= $colorspace1;

    if(isset($actions["gamma"]) && is_int_loose($actions["gamma"]) && $actions["gamma"] <> 50)
        {
        $gamma = round($actions["gamma"]/50,2);
        $command .= " -gamma " .  $gamma . " ";
        }

    if ($sf_parts['extension']=="psd" && !$keep_transparency)
        {
        $command .= $alphaoff;
        }

    // Transform actions need to be performed in order the user performed them since they are not commutative
    $tfparams = "";
    // Set var to keep track of rotations so we know if image has swapped height/width. 
    // This is needed to calculate the crop co-ordinates 
    $swaphw = 0;
    foreach($actions["tfactions"] as $tfaction)
        {
        switch ($tfaction)
            {
            case "r90":
                $tfparams .= " -rotate 90 ";
                $swaphw += 1;
                break;
            case "r180":
                $tfparams .= " -rotate 180 ";
                break;
            case "r270":
                $tfparams .= " -rotate 270 ";
                $swaphw += 1;
                break;
            case "x":
                $tfparams .= " -flop ";
            break;
            case "y":
                $tfparams .= " -flip ";
            break;
            default:
                // No transform action
            break;
            }
        }

    $command .= $tfparams;

    if (isset($actions["crop"]) && $actions["crop"] && !$cropperestricted)
        {
        // Need to mathematically convert to the original size
        $xfactor = $swaphw % 2 == 0 ? $origwidth/$actions["cropwidth"] : $origheight/$actions["cropwidth"];
        $yfactor = $swaphw % 2 == 0 ? $origheight/$actions["cropheight"] : $origwidth/$actions["cropheight"];

        debug(" xfactor:  " . $xfactor);
        debug(" yfactor:  " . $yfactor);
        $finalxcoord = round (($actions["xcoord"] * $xfactor),0);
        $finalycoord = round (($actions["ycoord"] * $yfactor),0);	

        // Ensure that new ratio of crop matches that of the specified size or we may end up missing the target size
        // If landscape crop, set the width first, then base the height on that
        $desiredratio = (int)$actions["width"] / (int)$actions["height"];
        if($desiredratio > 1)
            {
            $finalwidth  = round ($actions["width"] * $xfactor,0);
            $finalheight = round ($finalwidth / $desiredratio,0);
            }
        else
            {
            $finalheight = round ($actions["height"] * $yfactor,0);
            $finalwidth= round($finalheight *  $desiredratio,0);
            }

        debug("width:  " . $actions["width"]);
        debug("height:  " . $actions["height"]);
        debug("finalxcoord:  " . $finalxcoord);
        debug("finalycoord:  " . $finalycoord);
        debug("cropwidth:  " . $actions["cropwidth"]);
        debug("cropheight:  " . $actions["cropheight"]);
        debug("origwidth:  " . $origwidth);
        debug("origheight:  " . $origheight);
        debug("new_width:  " . $actions["new_width"]);
        debug("new_height:  " . $actions["new_height"]);
        debug("finalwidth:  " . $finalwidth);
        debug("finalheight:  " . $finalheight);

        $command .= " -crop " . $finalwidth . "x" . $finalheight . "+" . $finalxcoord . "+" . $finalycoord;
        }

    if (isset($actions["repage"]) && $actions["repage"])
        {
        $command .= " +repage"; // force imagemagick to repage image to fix canvas and offset info
        }

    // Did the user request a width? If so, tack that on
    if ((isset($actions["new_width"]) && (int)$actions["new_width"] > 0) || (isset($actions["new_height"]) && (int)$actions["new_height"] > 0))
        {
        $scalewidth = is_numeric($actions["new_width"]) ? true : false;
        $scaleheight = is_numeric($actions["new_height"]) ? true : false;
        
        if (!$cropper_allow_scale_up && (!isset($actions["preview"]) || $actions["preview"] === false))
            {
            // sanity checks
            // don't allow a specified size larger than the natural crop size
            // or the original size of the image
            if (isset($actions["crop"]) && $actions["crop"])
                {
                $checkwidth  = $actions["finalwidth"];
                $checkheight = $actions["finalheight"];
                } 
            else
                {
                $checkwidth     = $actions["origwidth"];
                $checkheight    = $actions["origheight"];
                }

            if (is_numeric($actions["new_width"]) && $actions["new_width"] > $checkwidth)
                {
                // if the requested width is greater than the original or natural size, ignore
                $actions["new_width"] = '';
                $scalewidth = false;
                }

            if (is_numeric($actions["new_height"]) && $actions["new_height"] > $checkheight)
                {
                // if the requested height is greater than original or natural size, ignore
                $actions["new_height"] = '';
                $scaleheight = false;
                }
            }

        if ($scalewidth || $scaleheight)
            {
            // add scaling command
            // note that there is a minor issue here: may be rounding
            // errors when the crop box is scaled up from preview size to original	 size
            // if so and the resulting match doesn't quite match the required width and 
            // height, there may be a tiny amount of distortion introduced as the
            // program scales up or down by a few pixels. This should be
            // imperceptible, but perhaps worth revisiting at some point.
            $command .= " -scale " . (int)$actions["new_width"];
            if ($actions["new_height"] > 0)
                {
                $command .= "x" . (int)$actions["new_height"];
                }
            $command .= " ";
            }
        }

    $command .= $profile  . " \"$outputpath\"";

    $shell_result = run_command($command);

    if (file_exists($outputpath))
        {
        // See if we have got exiftool
        $exiftool_fullpath = get_utility_path("exiftool");
        if (($exiftool_fullpath!=false) && !in_array($of_parts["extension"],$exiftool_no_process))
            {
            $exifcommand = $exiftool_fullpath . ' -m -overwrite_original -E -Orientation#=1 ';
            $exifargs = array();

            if(isset($actions["resolution"]) && $actions["resolution"] != "")
                {
                // Target the Photoshop specific PPI data
                $exifcommand.= " -Photoshop:XResolution=%resolution%";
                $exifcommand.= " -Photoshop:YResolution=%resolution%";
                $exifargs["%resolution%"]  = $actions["resolution"];
                }

            $exifcommand.= " %outputfile%";
            $exifargs["%outputfile%"]  = $outputpath;
            $command = escape_command_args($exifcommand,$exifargs);
            $output = run_command($command);
            }
        }

    return file_exists($outputpath);
    }