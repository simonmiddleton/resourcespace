<?php 
#
# This file contains the integration code with ImageMagick
# It also contains integration code for those types that we need ImageMagick to be able to process
# for example types that use GhostScript or FFmpeg.
#

global $imagemagick_path, $imagemagick_preserve_profiles, $imagemagick_quality, $imagemagick_colorspace, $ghostscript_path, $pdf_pages, $antiword_path, $unoconv_path, $pdf_resolution, $pdf_dynamic_rip, $ffmpeg_audio_extensions, $ffmpeg_audio_params, $qlpreview_path,$ffmpeg_supported_extensions, $qlpreview_exclude_extensions, $ffmpeg_global_options,$ffmpeg_snapshot_fraction, $ffmpeg_snapshot_seconds,$ffmpeg_no_new_snapshots, $lang, $dUseCIEColor, $blender_path;

resource_log($ref,LOG_CODE_TRANSFORMED,'','','',$lang['createpreviews'] . ":\n");

# Locate utilities
$exiftool_fullpath = get_utility_path("exiftool");
$ghostscript_fullpath = get_utility_path("ghostscript");

global $keep_for_hpr;
$preprocess=true; // indicate that an intermediate jpg is being made, so that image_processing doesn't skip the hpr

if (!$previewonly)
    {
    $file=get_resource_path($ref,true,"",false,$extension,-1,1,false,"",$alternative); 
    $target=get_resource_path($ref,true,"",true,"jpg",-1,1,false,"",$alternative); 
    }
else
    {
    # Use temporary preview source/destination - user has uploaded a file intended to replace the previews only.
    $file=get_resource_path($ref,true,"tmp",false,$extension);
    $target=get_resource_path($ref,true,"tmp",false,"jpg");
    }
    
# Set up ImageMagick
putenv("MAGICK_HOME=" . $imagemagick_path);

$snapshotcheck=false;
if (in_array($extension, $ffmpeg_supported_extensions)){
    $snapshotcheck=file_exists(get_resource_path($ref,true,"pre",false,'jpg',-1,1,false,""));
    if ($snapshotcheck){sql_query("update resource set has_image=1 where ref='$ref'");}
}

if ($alternative==-1 && !($snapshotcheck && in_array($extension, $ffmpeg_supported_extensions) && $ffmpeg_no_new_snapshots))
    {
    # Reset the 'has thumbnail image' status in case previewing fails with this new file. 
    sql_query("update resource set has_image=0 where ref='$ref'"); 
    }


# Set up target file
if(!hook("previewpskipdel")):
if (file_exists($target)) {unlink($target);}
endif;

# Locate imagemagick.
$convert_fullpath = get_utility_path("im-convert");
if ($convert_fullpath==false) {exit("Could not find ImageMagick 'convert' utility at location '$imagemagick_path'");}

debug ("Starting preview preprocessing. File extension is $extension.",$ref);

hook("metadata");

/* ----------------------------------------
    Plugin-added preview support
   ----------------------------------------
*/

$preview_preprocessing_results=hook("previewsupport","", array( "extension" => $extension ,"file"=>$file,"target"=>$target));
if (is_array($preview_preprocessing_results)){
    if (isset($preview_preprocessing_results['file'])){
        $file=$preview_preprocessing_results['file'];
    }
    if (isset($preview_preprocessing_results['extension'])){
        $extension=$preview_preprocessing_results['extension'];
    }
    if (isset($preview_preprocessing_results['keep_for_hpr'])){
        $keep_for_hpr=$preview_preprocessing_results['keep_for_hpr'];
    }
}
    
/* ----------------------------------------
    QuickLook Previews (Mac only)
    For everything except Audio/Video files, attempt to generate a QuickLook preview first.
   ----------------------------------------
*/
if (isset($qlpreview_path) && !in_array($extension, $qlpreview_exclude_extensions) && !in_array($extension, $ffmpeg_supported_extensions) && !in_array($extension, $ffmpeg_audio_extensions) && !isset($newfile))
    {
    $qlpreview_command=$qlpreview_path."/qlpreview -generatePreviewOnly yes -imageType jpg -maxWidth 800 -maxHeight 800 -asIcon no -preferFileIcon no -inPath " . escapeshellarg($file) . " -outPath " . escapeshellarg($target);
    debug("qlpreview command: " . $qlpreview_command);
    $output=run_command($qlpreview_command);

    #sleep(4); # Delay to allow processing
    if (file_exists($target)){$newfile = $target;debug("qlpreview success!");}
    }

/* ----------------------------------------
    Try InDesign - for CS5 (page previews)
   ----------------------------------------
*/
if ($exiftool_fullpath!=false)
    {
    if ($extension=="indd" && !isset($newfile))
        {
        $indd_thumbs = extract_indd_pages ($file);
        $pagescommand="";
        if (is_array($indd_thumbs))
            {
            
            $n=0;
            foreach ($indd_thumbs as $indd_page){
                $target_pg = str_replace(".jpg","_" . $n . ".jpg", $target);
                $pagescommand.=" " . $target_pg;
                base64_to_jpeg( str_replace("base64:","",$indd_page), $target_pg);
                
                $n++;
            }
        } 
        
        
        // process jpgs as a pdf so the existing pdf paging code can be used.   
        if (is_array($indd_thumbs)){
            $file=get_resource_path($ref,true,"",false,"pdf");      
            $jpg2pdfcommand = $convert_fullpath . " " . $pagescommand . " " . escapeshellarg($file);

            $output=run_command($jpg2pdfcommand);

            $n=0;
            foreach ($indd_thumbs as $indd_page){
                $target_pg = str_replace(".jpg","_" . $n . ".jpg", $target);
                if (file_exists($target_pg)){   
                    unlink($target_pg);
                }
                $n++;
            }
            
            $extension="pdf";
            $dUseCIEColor=false;
            $n=0;   
            }
        }
    }   
    
    
/* ----------------------------------------
    Try PhotoshopThumbnail
   ----------------------------------------
*/
# Note: for good results, Photoshop Preferences must be set to save Preview image at Extra Large size.
if (($extension=="psd" || $extension=="psb") && !isset($newfile))
    {
    global $photoshop_thumb_extract;
    if ($photoshop_thumb_extract)
        {
        if ($exiftool_fullpath!=false)
            {
            $cmd=$exiftool_fullpath.' -b -PhotoshopThumbnail '.escapeshellarg($file).' > '.$target;
            $output=run_command($cmd);
            }
        if (file_exists($target))
            {
            #if the file contains an image, use it; if it's blank, it needs to be erased because it will cause an error in ffmpeg_processing.php
            if (filesize_unlimited($target)>0){$newfile = $target;}else{unlink($target);}
            }
        }
    }


    
/* ----------------------------------------
    Photoshop Transparency Checkerboard
   ----------------------------------------
*/
# composite checkerboard for PSD transparency. Not applicable to $photoshop_thumb_extract.
global $psd_transparency_checkerboard;
if ($extension=="psd" && !isset($newfile) && $psd_transparency_checkerboard)
    {
    $composite_fullpath = get_utility_path("im-composite");
    $cmd=$composite_fullpath . " -compose Dst_Over -tile pattern:checkerboard ".escapeshellarg($file)."[0] ".$target;
    $wait = run_command($cmd);
    if (file_exists($target)){
        $newfile=$target;
    }
        
}   
        
    
    
/* ----------------------------------------
    Try SWF
   ----------------------------------------
*/
# Note: gnash-dump must be compiled on the server. http://www.xmission.com/~ink/gnash/gnash-dump/README.txt
# Ubuntu: ./configure --prefix=/usr/local/gnash-dump --enable-renderer=agg \
# --enable-gui=gtk,dump --disable-kparts --disable-nsapi --disable-menus
# several dependencies will also be necessary, according to ./configure

if ($extension=="swf" && !isset($newfile))
    {
    global $dump_gnash_path;
    if (isset($dump_gnash_path))
        {
        $cmd=$dump_gnash_path.'/dump-gnash -t 1 --screenshot 5 --screenshot-file '.$target.' '.escapeshellarg($file);
        $output=run_command($cmd);
        }
    if (file_exists($target))
        {
        #if the file contains an image, use it; if it's blank, it needs to be erased because it will cause an error in ffmpeg_processing.php
        if (filesize_unlimited($target)>0){$newfile = $target;}else{unlink($target);}
        }
        
    }   


/* ----------------------------------------
    Try RAW preview extraction via exiftool
   ----------------------------------------
*/

if (($extension=="cr2" || $extension=="nef" || $extension=="dng" || $extension=="raf" || $extension=="rw2" || 'arw' == $extension) && !isset($newfile))
    {
    global $cr2_thumb_extract;
    global $nef_thumb_extract;
    global $dng_thumb_extract;
    global $rw2_thumb_extract;
    global $raf_thumb_extract;
    global $arw_thumb_extract;
    
    if (($extension=="cr2" && $cr2_thumb_extract) || 
        ($extension=="nef" && $nef_thumb_extract) || 
        ($extension=="dng" && $dng_thumb_extract) || 
        ($extension=="rw2" && $rw2_thumb_extract) || 
        ($extension=="raf" && $raf_thumb_extract) ||
        ('arw' == $extension && $arw_thumb_extract)
    )
        {
        if ($exiftool_fullpath!=false)
            {   
            // previews are stored in a couple places, and some nef files have large previews in -otherimage
            if ($extension=="rw2"){$bin_tag=" -jpgfromraw ";}
            if ($extension=="nef"){$bin_tag=" -otherimage ";}
            if ($extension=="cr2"||$extension=="dng"||$extension=="raf" || 'arw' == $extension)
                {
                $bin_tag = " -previewimage ";
                }

            // Attempt extraction. Replaced ">" with -w since this has been seen to fail on some Windows servers
            $cmd=$exiftool_fullpath.' -b '.$bin_tag.' '.escapeshellarg($file).' -w %d%f.jpg';
            $wait=run_command($cmd);
            $extractedpreview=preg_replace('"\.' . pathinfo($file, PATHINFO_EXTENSION) . '$"', '.jpg', $file);
            if($target!=$extractedpreview && file_exists($extractedpreview))
                {
                rename($extractedpreview, $target);
                }
            
        

            // check for nef -otherimage failure
            if ($extension=="nef"&&!filesize_unlimited($target)>0)
                {
                if(file_exists($target))
                    {
                    unlink($target);
                    }

                $bin_tag=" -previewimage ";
                //2nd attempt
                $cmd=$exiftool_fullpath.' -b '.$bin_tag.' '.escapeshellarg($file).' -w %d%f.jpg';
                $wait=run_command($cmd);
                $extractedpreview=preg_replace('"\.' . pathinfo($file, PATHINFO_EXTENSION) . '$"', '.jpg', $file);
                if($target!=$extractedpreview && file_exists($extractedpreview))
                    {
                    rename($extractedpreview, $target);
                    }
                }
                
            // NOTE: in case of failures, other suboptimal possibilities 
            // may be explored in the future such as -thumbnailimage and -jpgfromraw, like this:
            // //check for failure
            //if (!filesize_unlimited($target)>0)
                //{
                //unlink($target);  
                //$bin_tag=" -thumbnailimage ";
                //attempt
                //$wait=run_command($exiftool_fullpath.' -b '.$bin_tag.' '.$file.' > '.$target);
                //}
            
            if (filesize_unlimited($target)>0)
                {
                $orientation=get_image_orientation($file);
                if ($orientation!=0)
                    {
                    $mogrify_fullpath = get_utility_path("im-mogrify");
                    if ($mogrify_fullpath!=false)
                        {
                        $command = $mogrify_fullpath . ' -rotate +' . $orientation .' '. $target;
                        $wait = run_command($command);
                        }
                    }
                $newfile = $target;$keep_for_hpr=true;
                }
            elseif(file_exists($target))
                {
                unlink($target);
                }   
            }
        }
    }   

/* ---------------------------------------- 
        Try Apple iWork Formats 
        The following are to generate previews for the Apple iWork files such 
as Apple Pages, Apple Keynote, and Apple Numbers. 
   ---------------------------------------- 
*/ 
if ( (($extension=="pages") || ($extension=="numbers") || (!isset($unoconv_path) && $extension=="key")) && !isset($newfile)) 
    {
    $cmd="unzip -p ".escapeshellarg($file)." \"QuickLook/Thumbnail.jpg\" > $target";
    $output=run_command($cmd);
    $newfile = $target;
    }   
        
    
/* ----------------------------------------
    Unoconv is a python-based utility to run files through OpenOffice. It is available in Ubuntu.
    This adds conversion of office docs to PDF format and adds them as alternative files (this behaviour can be disabled by
    adding the extension to non_image_types list)
    One could also see the potential to base previews on the PDFs for paging and better quality for most of these formats.
   ----------------------------------------
*/
global $unoconv_extensions;
if (in_array($extension,$unoconv_extensions) && $extension!='pdf' && isset($unoconv_path) && !isset($newfile))
    {
    global $config_windows;
    $unocommand=$unoconv_path . "/unoconv";
    if (!file_exists($unocommand)) {exit("Unoconv executable not found at '$unoconv_path'");}
    if($config_windows)
       {
       global $unoconv_python_path;
       $cmd_uno_python_path=$unoconv_python_path . DIRECTORY_SEPARATOR . 'python.exe';
       if(!file_exists($cmd_uno_python_path))
            {
            exit("Unoconv's OpenOffice Python executable not found at '$unoconv_python_path'");
            }
       }
    $cmd=($config_windows ? escapeshellarg($cmd_uno_python_path) . ' ' : '') . escapeshellarg($unocommand) . " --format=pdf " . escapeshellarg($file);
    $output=run_command($cmd);

    $path_parts=pathinfo($file);
    $basename_minus_extension=remove_extension($path_parts['basename']);
    $pdffile=$path_parts['dirname']."/".$basename_minus_extension.".pdf";

    $no_alt_condition = (
        $GLOBALS['non_image_types_generate_preview_only']
        && in_array($extension, $GLOBALS['non_image_types']) 
        && in_array($extension, config_merge_non_image_types())
    );

    if(file_exists($pdffile) && $no_alt_condition)
        {
        // Set vars so we continue generating previews as if this is a PDF file
        $extension = "pdf";
        $file = $pdffile;
        $unoconv_fake_pdf_file = true;

        // We need to avoid a job spinning off another job because create_previews() can run as an offline job and it 
        // includes preview_preprocessing.php.
        global $offline_job_queue, $offline_job_in_progress;

        if($offline_job_queue && !$offline_job_in_progress)
            {
            $extract_text_job_data = array(
                'ref'       => $ref,
                'extension' => $extension,
                'path'      => $file,
            );

            job_queue_add('extract_text', $extract_text_job_data);
            }
        else
            {
            extract_text($ref, $extension, $pdffile);
            }
        }
    else if (file_exists($pdffile))
        {
        # Attach this PDF file as an alternative download.
        sql_query("delete from resource_alt_files where resource = '".$ref."' and unoconv='1'");    
        $alt_ref=add_alternative_file($ref,"PDF version");
        $alt_path=get_resource_path($ref,true,"",false,"pdf",-1,1,false,"",$alt_ref);
        global $lang;   
        $alt_description=$lang['unoconv_pdf'];
        copy($pdffile,$alt_path);unlink($pdffile);
        sql_query("update resource_alt_files set file_name='$ref-converted.pdf',description='$alt_description',file_extension='pdf',file_size='".filesize_unlimited($alt_path)."',unoconv='1' where resource='$ref' and ref='$alt_ref'");

        # Set vars so we continue generating thumbs/previews as if this is a PDF file
        $extension="pdf";
        $file=$alt_path;

        // We need to avoid a job spinning off another job because create_previews() can run as an offline job and it 
        // includes preview_preprocessing.php.
        global $offline_job_queue, $offline_job_in_progress;

        if($offline_job_queue && !$offline_job_in_progress)
            {
            $extract_text_job_data = array(
                'ref'       => $ref,
                'extension' => $extension,
                'path'      => $alt_path,
            );

            job_queue_add('extract_text', $extract_text_job_data);
            }
        else
            {
            extract_text($ref, $extension, $alt_path);
            }
        }
    }
    
/* ----------------------------------------
    Calibre E-book processing
   ----------------------------------------
*/
global $calibre_extensions;
global $calibre_path;
if (in_array($extension,$calibre_extensions) && isset($calibre_path) && !isset($newfile))
    {
    $calibrecommand=$calibre_path . "/ebook-convert";
    if (!file_exists($calibrecommand)) {exit("Calibre executable not found at '$calibre_path'");}
    
    $path_parts=pathinfo($file);
    $basename_minus_extension=remove_extension($path_parts['basename']);
    $pdffile=$path_parts['dirname']."/".$basename_minus_extension.".pdf";

    $cmd="xvfb-run ". $calibrecommand . " " . escapeshellarg($file) . " " . escapeshellarg($pdffile) ." ";
    $wait=run_command($cmd);

    if (file_exists($pdffile))
        {
        # Attach this PDF file as an alternative download.
        sql_query("delete from resource_alt_files where resource = '".$ref."' and unoconv='1'");    
        $alt_ref=add_alternative_file($ref,"PDF version");
        $alt_path=get_resource_path($ref,true,"",false,"pdf",-1,1,false,"",$alt_ref);
        global $lang;
        $alt_description=$lang['calibre_pdf'];  
        copy($pdffile,$alt_path);unlink($pdffile);
        sql_query("update resource_alt_files set file_name='$ref-converted.pdf',description='$alt_description',file_extension='pdf',file_size='".filesize_unlimited($alt_path)."',unoconv='1' where resource='$ref' and ref='$alt_ref'");

        # Set vars so we continue generating thumbs/previews as if this is a PDF file
        $extension="pdf";
        $file=$alt_path;
        }
    }   
    

/* ----------------------------------------
    Try OpenDocument Format
   ----------------------------------------
*/
if ((($extension=="odt") || ($extension=="ott") || ($extension=="odg") || ($extension=="otg") || ($extension=="odp") || ($extension=="otp") || ($extension=="ods") || ($extension=="ots") || ($extension=="odf") || ($extension=="otf") || ($extension=="odm") || ($extension=="oth")) && !isset($newfile))

    {
    $cmd="unzip -p ".escapeshellarg($file)." \"Thumbnails/thumbnail.png\" > $target";
    $output=run_command($cmd);

    $odcommand = $convert_fullpath . " \"$target\"[0]  \"$target\""; 
    $output = run_command($odcommand);

    if(file_exists($target)){$newfile = $target;}
    }


/* ----------------------------------------
    Try Microsoft OfficeOpenXML Format
    Also try Micrsoft XPS... the sample document I've seen uses the same path for the preview, 
    so it will likely work in most cases, but I think the specs allow it to go anywhere.
   ----------------------------------------
*/
if ((($extension=="docx") || ($extension=="xlsx") || ($extension=="pptx") || ($extension=="xps")) && !isset($newfile) && in_array($extension,$unoconv_extensions) )
    {
    $cmd="unzip -p ".escapeshellarg($file)." \"docProps/thumbnail.jpeg\" > $target";
    $output=run_command($cmd);
    $newfile = $target;
    }



/* ----------------------------------------
    Try Blender 3D. This runs Blender on the command line to render the first frame of the file.
   ----------------------------------------
*/

if ($extension=="blend" && isset($blender_path) && !isset($newfile))
    {
    $blendercommand=$blender_path;  
    if (!file_exists($blendercommand)|| is_dir($blendercommand)) {$blendercommand=$blender_path . "/blender";}
    if (!file_exists($blendercommand)) {$blendercommand=$blender_path . "\blender.exe";}
    if (!file_exists($blendercommand)) {exit("Could not find blender application. '$blendercommand'");} 

    $cmd=$blendercommand. " -b ".escapeshellarg($file)." -F JPEG -o $target -f 1";
    $error=run_command($cmd);

    if (file_exists($target."0001"))
        {
        copy($target."0001","$target");
        unlink($target."0001");
        $newfile = $target;
        }
    if (file_exists($target."0001.jpg"))
        {
        copy($target."0001.jpg","$target");
        unlink($target."0001.jpg");
        $newfile = $target;
        }    
    }



/* ----------------------------------------
    Microsoft Word previews using Antiword
    (note: this is very basic)
   ----------------------------------------
*/
if ($extension=="doc" && isset($antiword_path) && isset($ghostscript_path) && !isset($newfile))
    {
    $command=$antiword_path . "/antiword";
    if (!file_exists($command)) {$command=$antiword_path . "\antiword.exe";}
    if (!file_exists($command)) {exit("Antiword executable not found at '$antiword_path'");}

    $cmd=$command . " -p a4 " . escapeshellarg($file) . " > \"" . $target . ".ps" . "\"";
    $output=run_command($cmd);

    if (file_exists($target . ".ps"))
        {
        # Postscript file exists
        $gscommand = $ghostscript_fullpath . " -dBATCH -dNOPAUSE -sDEVICE=jpeg -r150 -sOutputFile=" . escapeshellarg($target) . "  -dFirstPage=1 -dLastPage=1 -dEPSCrop " . escapeshellarg($target . ".ps");
        $output = run_command($gscommand);

        if (file_exists($target))
            {
            # A JPEG was created. Set as the file to process.
            $newfile=$target;
            }
        }
    }

/* ----------------------------------------
    Try MP3 preview extraction via exiftool
   ----------------------------------------
*/

if (($extension=="mp3" || $extension=="flac") && !isset($newfile))
    {
    if ($exiftool_fullpath!=false)
        {
        $cmd=$exiftool_fullpath.' -b -picture '.escapeshellarg($file).' > '.$target;
        $output=run_command($cmd);
        }
    if (file_exists($target))
        {
        #if the file contains an image, use it; if it's blank, it needs to be erased because it will cause an error in ffmpeg_processing.php
        if (filesize_unlimited($target)>0){$newfile = $target;}else{unlink($target);}
        }
    }



/* ----------------------------------------
    Try text file to JPG conversion
   ----------------------------------------
*/
# Support text files simply by rendering them on a JPEG.
if ($extension=="txt" && !isset($newfile))
    {
    $text=wordwrap(file_get_contents($file),90);
    $width=650;$height=850;
    $font=dirname(__FILE__). "/../gfx/fonts/vera.ttf";
    $im=imagecreatetruecolor($width,$height);
    $col=imagecolorallocate($im,255,255,255);
    imagefilledrectangle($im,0,0,$width,$height,$col);
    $col=imagecolorallocate($im,0,0,0);
    imagettftext($im,9,0,10,25,$col,$font,$text);
    imagejpeg($im,$target);
    $newfile=$target;
    }


/* ----------------------------------------
    Try FFMPEG for video files
   ----------------------------------------
*/
$ffmpeg_fullpath = get_utility_path('ffmpeg');
$php_fullpath    = get_utility_path("php");

global $ffmpeg_preview,$ffmpeg_preview_seconds,$ffmpeg_preview_extension,$ffmpeg_preview_options,
       $ffmpeg_preview_min_width, $ffmpeg_preview_min_height, $ffmpeg_preview_max_width,
       $ffmpeg_preview_max_height, $ffmpeg_preview_async, $ffmpeg_preview_force,
       $ffmpeg_snapshot_frames, $video_preview_hls_support,$ffmpeg_hls_preview_options, $video_hls_streams, $h264_profiles;

debug('FFMPEG-VIDEO: ####################################################################');
debug('FFMPEG-VIDEO: Start trying FFMPeg for video files -- resource ID ' . $ref);

// If a snapshot has already been created and $ffmpeg_no_new_snapshots, never revert the snapshot (this is usually a custom preview)
if(false != $ffmpeg_fullpath && $snapshotcheck && in_array($extension, $ffmpeg_supported_extensions) && $ffmpeg_no_new_snapshots)
    {
    debug('FFMPEG-VIDEO: Create a preview for this video by going straight to ffmpeg_processing.php');

    $target = get_resource_path($ref, true, 'pre', false, 'jpg', -1, 1, false, '');
    
    include dirname(__FILE__) . '/ffmpeg_processing.php';
    }
else if (($ffmpeg_fullpath!=false) && !isset($newfile) && in_array($extension, $ffmpeg_supported_extensions))
    {
    debug('FFMPEG-VIDEO: Start process for creating previews...');
    
    if($alternative==-1)
        {
        //If we are recreating previews, we should remove the previously created snapshots
        $directory = dirname(get_resource_path($ref,true,"pre",true) );
        foreach (glob($directory . "/*") as $filetoremove)
            {
            if (strpos($filetoremove, 'snapshot_')!==false)
                {
                unlink($filetoremove);
                }
            }
        }
    
    $snapshottime = 1;
    $duration = 0; // Set this as default if the duration is not determined so that previews will always work
    $cmd = $ffmpeg_fullpath . ' -i ' . escapeshellarg($file);
    $out = run_command($cmd, true);
        
    debug("FFMPEG-VIDEO: Running information command: {$cmd}");

    if(preg_match('/Duration: (\d+):(\d+):(\d+)\.\d+, start/', $out, $match))
        {
        $duration = $match[1] * 3600 + $match[2] * 60 + $match[3];
        debug("FFMPEG-VIDEO: \$duration = {$duration} seconds");
        
        if(isset($ffmpeg_snapshot_seconds)) // Overrides the other settings
            {
            if($ffmpeg_snapshot_seconds < $duration)
                {
                $snapshottime = $ffmpeg_snapshot_seconds;
                }
            }
        elseif(10 < $duration)
            {
            $snapshottime = floor($duration * (isset($ffmpeg_snapshot_fraction) ? $ffmpeg_snapshot_fraction : 0.1));
            }
        
        // Generate snapshots for the whole video (not for alternatives)
        // Custom target used ONLY for captured snapshots during the video
        if(1 < $ffmpeg_snapshot_frames && -1 == $alternative)
            {
            $snapshot_scale           = '';
            $escaped_file             = escapeshellarg($file);
            $escaped_target           = escapeshellarg(get_resource_path($ref, true, 'snapshot', false, 'jpg', -1, 1, false, ''));
            $snapshot_points_distance = max($duration / $ffmpeg_snapshot_frames,1);
            
            // Find video resolution, figure out whether it is landscape/ portrait and adjust the scaling for the snapshots accordingly
            include_once dirname(__FILE__) . '/video_functions.php';

            $video_resolution = get_video_resolution($file);
            
            if ($exiftool_fullpath!=false)
                {
                // Has it been rotated?
                $command = $exiftool_fullpath . " -s -s -s -Rotation " . escapeshellarg($file);
                $rotation = run_command($command);
                if($rotation == "90" || $rotation == "270")
                    {
                    $orig_video_resolution = $video_resolution;
                    $video_resolution['width']  = $orig_video_resolution['height'];
                    $video_resolution['height'] = $orig_video_resolution['width'];
                    }                   
                }
            
            
            $snapshot_size    = sql_query('SELECT width, height FROM preview_size WHERE id = "pre"');

            if(isset($snapshot_size[0]) && 0 < count($snapshot_size[0]))
                {
                $snapshot_width  = $snapshot_size[0]['width'];
                $snapshot_height = $snapshot_size[0]['height'];
                }

            if($video_resolution['width'] > $video_resolution['height'] && isset($snapshot_width) && $video_resolution['width'] >= $snapshot_width)
                {
                // Landscape
                $snapshot_scale = "-vf scale={$snapshot_width}:-1";
                }
            else if($video_resolution['width'] < $video_resolution['height'] && isset($snapshot_height) && $video_resolution['height'] >= $snapshot_height)
                {
                // Portrait
                $snapshot_scale = "-vf scale=-1:{$snapshot_height}";
                }
            else
                {
                // Square
                $snapshot_scale = "-vf scale={$snapshot_width}:-1";
                }

            for($snapshot_point = 0, $i = 1; $snapshot_point <= $duration; $snapshot_point += $snapshot_points_distance, $i++)
                {
                $escaped_snapshot_target = str_replace('snapshot', "snapshot_{$i}", $escaped_target);

                $cmd = "{$ffmpeg_fullpath} {$ffmpeg_global_options} -y -ss {$snapshot_point} -i {$escaped_file} {$snapshot_scale} -frames:v 1 {$escaped_snapshot_target}";
                run_command($cmd);
                }
            }
        }

    if('mxf' == $extension || $duration == 0)
        {
        $snapshottime = 0;
        }

    if(!hook('previewpskipthumb', '', array($file)))
        {
        $cmd = $ffmpeg_fullpath . ' ' . $ffmpeg_global_options . ' -y -i ' . escapeshellarg($file) . ' -f image2 -vframes 1 -ss ' . $snapshottime . ' ' . escapeshellarg($target);
        $output = run_command($cmd);

        debug("FFMPEG-VIDEO: Get snapshot: {$cmd}");
        }

    if (file_exists($target)) 
        {
        $newfile=$target;
        debug('FFMPEG-VIDEO: $newfile = ' . $newfile);
       

        if ($ffmpeg_preview && ($extension!=$ffmpeg_preview_extension || $ffmpeg_preview_force || $video_preview_hls_support!=0) )
            {
                debug('FFMPEG-VIDEO: Before running the actual preview command...');
                if ($ffmpeg_preview_async && $php_fullpath !== false)
                    {
                        debug('FFMPEG-VIDEO: Create preview asynchronously...');
                    global $scramble_key;
                    exec($php_fullpath . dirname(__FILE__) . "/ffmpeg_processing.php " . 
                        escapeshellarg($scramble_key) . " " . 
                        escapeshellarg($ref) . " " . 
                        escapeshellarg($file) . " " . 
                        escapeshellarg($target) . " " . 
                        escapeshellarg($previewonly) . " " . 
                escapeshellarg($snapshottime) . " " .
                                    escapeshellarg($alternative) . " " .
                    (isset($_SERVER['HTTP_HOST']) ?  escapeshellarg($_SERVER['HTTP_HOST']) : '') . " " .
                                    "> /dev/null 2>&1 &");
                    }
                else 
                    {
                        debug('FFMPEG-VIDEO: include ffmpeg_processing.php file...');
                    include (dirname(__FILE__)."/ffmpeg_processing.php");
                    }
            }
        }
        debug('FFMPEG-VIDEO: ####################################################################');
    } 


/* ----------------------------------------
    Try FFMPEG for audio files
   ----------------------------------------
*/
if (($ffmpeg_fullpath!=false) && in_array($extension, $ffmpeg_audio_extensions))
    {
    # Produce the MP3 preview.
    $mp3file = get_resource_path($ref,true,"",false,"mp3",1,1,false,"",$alternative);

    $cmd=$ffmpeg_fullpath . " -y -i " . escapeshellarg($file) . " " . $ffmpeg_audio_params . " " . escapeshellarg($mp3file);
    $output = run_command($cmd);

    if(!file_exists($mp3file))
        {
        sql_query("update resource set preview_attempts=ifnull(preview_attempts,0) + 1 where ref='$ref'");
        echo debug("Failed to process resource " . $ref . " - MP3 creation failed.");
        }   
    }


/* ----------------------------------------
    Try ImageMagick
   ----------------------------------------
*/
if ((!isset($newfile)) && (!in_array($extension, $ffmpeg_audio_extensions))&& (!in_array($extension, $ffmpeg_supported_extensions)))
    {
    $prefix="";

    # Preserve colour profiles?    
    $profile="+profile icc -colorspace ".$imagemagick_colorspace; # By default, strip the colour profiles ('+' is remove the profile, confusingly)
    if ($imagemagick_preserve_profiles) {$profile="";}
    
    # CR2 files need a cr2: prefix
    if ($extension=="cr2") {$prefix="cr2:";}

    $photoshop_eps = false;
    global $photoshop_eps_miff;  
    if ($photoshop_eps_miff){
        
        # Recognize Photoshop EPS(F) pixel data files
        if ($extension=="eps")
        {
        $eps_file = fopen($file, 'r');
        $i = 0;
        while (!$photoshop_eps && ($eps_line = fgets($eps_file)) && ($i < 100))
        {
        if (@preg_match("/%%BoundingBox: [0-9]+ [0-9]+ ([0-9]+) ([0-9]+)/i", $eps_line, $regs))
            {
            $eps_bbox_x = $regs[1];
            $eps_bbox_y = $regs[2];
            }
        if (@preg_match("/%ImageData: ([0-9]+) ([0-9]+)/i", $eps_line, $regs))
            {
            $eps_data_x = $regs[1];
            $eps_data_y = $regs[2];
            }
        if (@preg_match("/%BeginPhotoshop:/i",$eps_line))
            {
            $photoshop_eps = true;
            }
        $i++;
        }
        if ($photoshop_eps)
            {
            $eps_density_x = $eps_data_x / $eps_bbox_x * 72;
            $eps_density_y = $eps_data_y / $eps_bbox_y * 72;
            $eps_target=get_resource_path($ref,true,"",false,"miff");
            $nfcommand = $convert_fullpath . ' -compress zip -colorspace '.$imagemagick_colorspace.' -quality 100 -density ' . sprintf("%.1f", $eps_density_x ). 'x' . sprintf("%.1f", $eps_density_y) . ' ' . escapeshellarg($file) . '[0] ' . escapeshellarg($eps_target);
            $output=run_command($nfcommand);
            if (file_exists($eps_target))
            {
            #  create_previews_using_im($ref,false,'miff',$previewonly);
            $extension = 'miff';
            }
            }
        }
    }

   if (($extension=="pdf") || (($extension=="eps") && !$photoshop_eps) || ($extension=="ai") || ($extension=="ps")) 
        {
        debug("PDF multi page preview generation starting",RESOURCE_LOG_APPEND_PREVIOUS);
        
      # For EPS/PS/PDF files, use GS directly and allow multiple pages.
    # EPS files are always single pages:
    if ($extension=="eps") {$pdf_pages=1;}
    if ($extension=="ai") {$pdf_pages=1;}
    if ($extension=="ps") {$pdf_pages=1;}
    $resolution=$pdf_resolution;
    $scr_size=sql_query("select width,height from preview_size where id='scr'");
    if(empty($scr_size)){
        # since this is not an application required size we can't assume there's a record for it
        $scr_size=sql_query("select width,height from preview_size where id='pre'");
    }
    $scr_width=$scr_size[0]['width'];
    $scr_height=$scr_size[0]['height'];
    
        if ($pdf_dynamic_rip) {

        /* We want to rip at ~150 dpi by default because it provides decent 
        * quality previews and speed in the end. It is not always efficient to just 
        * rip at 150, though, because for very large pages, a lot of pixels 
        * get wasted when we resize to 850 pixels. Also, if the page size is 
        * quite small, ripping at 150 may not provide enough quality for the 
        * scr size preview. So, use PDFinfo to calculate a rip resolution 
        * that will give us a source bitmap of approximately 1600 pixels.
        */

            if ($extension=="pdf"){
                
                $pdfinfocommand="pdfinfo ".escapeshellarg($file);
                $pdfinfo=shell_exec($pdfinfocommand);
                $pdfinfo=explode("\n",$pdfinfo);
                $pdfinfo=preg_grep("/\bPage\b.+\bsize\b/",$pdfinfo);
                sort($pdfinfo);
                #die(print_r($pdfinfo));
                if (isset($pdfinfo[0])){
                    $pdfinfo=$pdfinfo[0];
                    }
                else {
                    $pdfinfo="";
                    }
                if ($pdfinfo!=""){  
                    $pdfinfo=explode(":",$pdfinfo);
                    $wh=explode("x",$pdfinfo[1]);   
                    $w=round(trim($wh[0]));
                    $h=explode(" ",$wh[1]); 
                    $h=round(trim($h[1]));
                    if($w>$h){
                        $pdf_max_dim=$w;
                        }
                    else{
                        $pdf_max_dim=$h;
                        }
                    $resolution=ceil((max($scr_width,$scr_height)*2)/($pdf_max_dim/72));
                }
                
                
            }
            if ($extension=="eps"){
                # Locate imagemagick.
                $identify_fullpath = get_utility_path("im-identify");
                if ($identify_fullpath==false)
                    {
                    debug("ERROR: Could not find ImageMagick 'identify' utility at location '$imagemagick_path'.");
                    return false;
                    }

                $pdfinfocommand = $identify_fullpath . " " .escapeshellarg($file);
                $pdfinfo=run_command($pdfinfocommand);
                $pdfinfo=explode(" ",$pdfinfo);
                if (isset($pdfinfo[2])){
                    $pdfinfo=$pdfinfo[2];
                    $pdfinfo=explode("+",$pdfinfo);
                    $pdfinfo=$pdfinfo[0];
                    }
                else {
                    $pdfinfo="";
                    }
                if ($pdfinfo!=""){  
                    $pdfinfo=str_replace("x"," ",$pdfinfo);
                    $pdfinfo=explode(" ",trim($pdfinfo));
                    if($pdfinfo[0]>$pdfinfo[1]){
                        $pdf_max_dim=$pdfinfo[0];
                        }
                    else{
                        $pdf_max_dim=$pdfinfo[1];
                        }
                }
                $resolution=ceil((max($scr_width,$scr_height)*2)/($pdf_max_dim/72));
            }
        }
        
    # Create multiple pages.
    for ($n=1;$n<=$pdf_pages;$n++)
        {
        # Set up target file
        $size="";if ($n>1) {$size="scr";} # Use screen size for other pages.
        $target=get_resource_path($ref,true,$size,false,"jpg",-1,$n,false,"",$alternative); 
        if (file_exists($target)) {unlink($target);}
        
        $preview_quality=get_preview_quality($size);

        if ($dUseCIEColor){$dUseCIEColor=" -dUseCIEColor ";} else {$dUseCIEColor="";}
        $gscommand2 = $ghostscript_fullpath . " -dBATCH -r".$resolution." ".$dUseCIEColor." -dNOPAUSE -sDEVICE=jpeg -dJPEGQ=".$preview_quality." -sOutputFile=" . escapeshellarg($target) . "  -dFirstPage=" . $n . " -dLastPage=" . $n . " -dEPSCrop -dUseCropBox " . escapeshellarg($file);
        $output=run_command($gscommand2);

        # Stop trying when after the last page
        if (strstr($output, 'FirstPage > LastPage'))
            {
            break;
            }

        debug("PDF multi page preview: page $n, executing " . $gscommand2);

    
        # Set that this is the file to be used.
        if (file_exists($target) && $n==1)
            {
            $newfile=$target;$pagecount=$n;
            debug("Page $n generated successfully",RESOURCE_LOG_APPEND_PREVIOUS);
            }
            
        # resize directly to the screen size (no other sizes needed)
         if (file_exists($target)&& $n!=1)
            {
            $command2 = $convert_fullpath . " " . $prefix . escapeshellarg($target) . "[0] -quality $preview_quality -resize ".$scr_width."x".$scr_height . " ".escapeshellarg($target);
            $output=run_command($command2); $pagecount=$n;

            # Add a watermarked image too?
            global $watermark;
            if (!hook("replacewatermarkcreation","",array($ref,$size,$n,$alternative))){
                if (isset($watermark) && $alternative==-1)
                    {
                $path=get_resource_path($ref,true,$size,false,"",-1,$n,true,"",$alternative);
                if (file_exists($path)) {unlink($path);}
                    $watermarkreal=dirname(__FILE__). "/../" . $watermark;
                    
                $command2 = $convert_fullpath . " \"$target\"[0] $profile -quality $preview_quality -resize ".$scr_width."x".$scr_height. " -tile " . escapeshellarg($watermarkreal) . " -draw \"rectangle 0,0 $scr_width,$scr_height\" " . escapeshellarg($path); 
                    $output=run_command($command2);
                }
            }
        }
        
        # Splitting of PDF files to multiple resources
        global $pdf_split_pages_to_resources;
        if (file_exists($target) && $pdf_split_pages_to_resources)
            {
            # Create a new resource based upon the metadata/type of the current resource.
            $copy=copy_resource($ref);
                        
            # Find out the path to the original file.
            $copy_path=get_resource_path($copy,true,"",true,"pdf");
            
            # Extract this one page to a new resource.
            $gscommand2 = $ghostscript_fullpath . " -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile=" . escapeshellarg($copy_path) . "  -dFirstPage=" . $n . " -dLastPage=" . $n . " " . escapeshellarg($file);
            $output=run_command($gscommand2);

            # Update the file extension
            sql_query("update resource set file_extension='pdf' where ref='$copy'");
        
            # Create preview for the page.
            $pdf_split_pages_to_resources=false; # So we don't get stuck in a loop creating split pages for the single page PDFs.
            create_previews($copy,false,"pdf");
            $pdf_split_pages_to_resources=true;
            }
            
        }
        // set page number
        if (isset($pagecount) && $alternative!=-1){
            sql_query("update resource_alt_files set page_count=$pagecount where ref=$alternative");
            }
        else if (isset($pagecount)){

            $pagecount_escaped = escape_check($pagecount);
            $ref_escaped = escape_check($ref);
            $sql = "SELECT count(*) AS value FROM `resource_dimensions` WHERE resource = '$ref_escaped'";
            $query = sql_value($sql, 0);

            if($query == 0)
                {
                sql_query("INSERT INTO resource_dimensions (resource, page_count) VALUES ('{$ref_escaped}', '{$pagecount_escaped}')");
                }
            else
                {
                sql_query("UPDATE resource_dimensions SET page_count = '{$pagecount_escaped}' WHERE resource = '{$ref_escaped}'");
                }            
            }
    }
    else
        {
        # Not a PDF file, so single extraction only.
            create_previews_using_im($ref,false,$extension,$previewonly,false,$alternative,$ingested, $onlysizes);
            }
    }

$non_image_types = config_merge_non_image_types();

# If a file has been created, generate previews just as if a JPG was uploaded.
if (isset($newfile))
    {
    if($GLOBALS['non_image_types_generate_preview_only'] && in_array($extension, $GLOBALS['non_image_types']))
        {
        $file_used_for_previewonly = get_resource_path($ref, true, "tmp", false, "jpg");

        if(copy($newfile, $file_used_for_previewonly))
            {
            $previewonly = true;
            debug("preview_preprocessing: changing previewonly = true for non-image file");
            }
        }

    create_previews($ref,false,"jpg",$previewonly,false,$alternative);

    if(
        $GLOBALS['non_image_types_generate_preview_only']
        && in_array($extension, $GLOBALS['non_image_types'])
        && file_exists($file_used_for_previewonly))
        {
        unlink($file_used_for_previewonly);
        }

    if(isset($unoconv_fake_pdf_file) && $unoconv_fake_pdf_file)
        {
        unlink($file);
        }
    }