
<?php
// Clean up old temp files. These can be left on the system as a result of cancelled or failed downloads/uploads
if($purge_temp_folder_age==0)
    {
    return;
    }
    
// Set up array of folders to scan
$folderstoscan = array();
$folderstoscan[] = get_temp_dir(false);
$folderstoscan[] = get_temp_dir(false) . DIRECTORY_SEPARATOR . "plupload";

// Set up array of folders to exclude
$excludepaths = array();
$excludepaths[] = "process_locks";
$excludepaths[] = "user_downloads";

// Set up arrays to hold items to delete
$folderstodelete=array();
$filestodelete=array();

foreach($folderstoscan as $foldertoscan)
    {
    if(!file_exists($foldertoscan))
        {
        continue;    
        }
    $foldercontents = new DirectoryIterator($foldertoscan);
    foreach($foldercontents as $objectindex => $object)
        {
        if(time()-$object->getMTime() > $purge_temp_folder_age*24*60*60)
            {
            $tmpfilename = $object->getFilename();
            if($object->isDot() || in_array($tmpfilename,$excludepaths))
                {
                continue;
                }
            if ($object->isDir())
                {
                $folderstodelete[] = $foldertoscan . DIRECTORY_SEPARATOR . $tmpfilename;
                }
            elseif($object->isFile())
                {
                $filestodelete[] = $foldertoscan . DIRECTORY_SEPARATOR . $tmpfilename;
                }
            } 
        }
    }

foreach ($folderstodelete as $foldertodelete)
    {
    // Extra check that folder is in an expected path
    if(strpos($foldertodelete,$storagedir) === false && strpos($foldertodelete,$tempdir) === false && strpos($foldertodelete,'filestore/tmp') === false)
        {
        continue;    
        }
    
    $success = @rcRmdir($foldertodelete);
    echo __FILE__. " - deleting directory " . $foldertodelete ." - " . ($success ? "SUCCESS" : "FAILED") . PHP_EOL;
    debug(__FILE__. " - deleting directory " . $foldertodelete ." - " . ($success ? "SUCCESS" : "FAILED"));
    }
    
foreach ($filestodelete as $filetodelete)
    {
    // Extra check that file is in an expected path
    if(strpos($filetodelete,$storagedir) === false && strpos($filetodelete,$tempdir) === false && strpos($filetodelete,'filestore/tmp') === false)
        {
        continue;    
        }
        
    $success = @unlink($filetodelete);
    echo __FILE__. " - deleting file " . $filetodelete ." - " . ($success ? "SUCCESS" : "FAILED") . PHP_EOL;
    debug(__FILE__. " - deleting file " . $filetodelete ." - " . ($success ? "SUCCESS" : "FAILED"));
    }
    


