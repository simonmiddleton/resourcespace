<?php
// Clean up old temp files. These can be left on the system as a result of cancelled or failed downloads/uploads
if($purge_temp_folder_age==0)
    {
    
    if('cli' == PHP_SAPI)
        {
        echo " - Config purge_temp_folder_age is set to 0 and is considered deactivated. Skipping delete temp files - FAILED" . $LINE_END;
        }
    debug("Config purge_temp_folder_age is set to 0 and is considered deactivated. Skipping delete temp files - FAILED");
    return;
    }
    
$last_delete_tmp_files  = get_sysvar('last_delete_tmp_files', '1970-01-01');

# No need to run if already run in last 24 hours.
if (time()-strtotime($last_delete_tmp_files) < 24*60*60)
    {
    if('cli' == PHP_SAPI)
        {
        echo " - Skipping delete_tmp_files job   - last run: " . $last_delete_tmp_files . $LINE_END;
        }
    return false;
    }

// Set up array of folders to scan
$folderstoscan = array();
$folderstoscan[] = get_temp_dir(false);
$folderstoscan[] = get_temp_dir(false) . DIRECTORY_SEPARATOR . "plupload";
$folderstoscan[] = get_temp_dir(false) . DIRECTORY_SEPARATOR . "querycache";

$modified_folderstoscan = hook("add_folders_to_delete_from_temp", "", array($folderstoscan));
if(is_array($modified_folderstoscan) && !empty($modified_folderstoscan))
    {
    $folderstoscan = $modified_folderstoscan;
    }

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
    
    if('cli' == PHP_SAPI)
        {
        echo __FILE__. " - deleting directory " . $foldertodelete ." - " . ($success ? "SUCCESS" : "FAILED")  . $LINE_END;
        }
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
    
    if('cli' == PHP_SAPI)
        {
        echo __FILE__. " - deleting file " . $filetodelete ." - " . ($success ? "SUCCESS" : "FAILED")  . $LINE_END;
        }
    debug(__FILE__. " - deleting file " . $filetodelete ." - " . ($success ? "SUCCESS" : "FAILED"));
    }

# Update last sent date/time.
set_sysvar("last_delete_tmp_files",date("Y-m-d H:i:s")); 



