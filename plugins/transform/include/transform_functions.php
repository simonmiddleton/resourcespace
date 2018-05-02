<?php

function generate_transform_preview($ref, $destpath)
    {
	global $storagedir,$imagemagick_path,$imversion;

	if (!isset($imversion))
        {
		$imversion = get_imagemagick_version();
        }

    // get imagemagick path
    $command = get_utility_path("im-convert");
    if ($command==false)
        {
        exit("Could not find ImageMagick 'convert' utility.");
        }
    
    $orig_ext = sql_value("select file_extension value from resource where ref = '$ref'",'');
	$transformsourcepath=get_resource_path($ref,true,'scr',false,'jpg'); //use screen size if available to save time
	if(!file_exists($transformsourcepath)) // use original if screen not available
		{$transformsourcepath= get_resource_path($ref,true,'',false,$orig_ext);}
	
	$modified_transformsourcepath=hook("modifytransformsourcepath");
	if ($modified_transformsourcepath)
        {
		$transformsourcepath=$modified_transformsourcepath;
        }

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

    $command .= " \"$transformsourcepath\"[0] +matte -flatten $colorspace1 -geometry 450 $colorspace2 \"$destpath\"";
    run_command($command);
    
    // while we're here, clean up any old files still hanging around
    $parentfolder = dirname($destpath);
    $foldercontents = new DirectoryIterator($parentfolder);
    foreach($foldercontents as $objectindex => $object)
        {           
        if($object->isDot())
            {
            continue;
            }
        if($object->isReadable() && time()-$object->getMTime() > 24*60*60)
            {
            $tmpfilename = $object->getFilename();
            if($object->isFile() && strpos($tmpfilename,"transform") === 0)
                {
                unlink($parentfolder . DIRECTORY_SEPARATOR . $tmpfilename);
                }
            } 
        }

	return true;
    }
