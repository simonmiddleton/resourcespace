<?php include "../../include/db.php";

// Copy files from the remote system
// Before running this the scramble key must be the same on both systems so copy over the relevant config.php entries first.

// Output all files in a folder, recursively.
function ShowFilesInFolderRecursive ($path)
    {
    global $storagedir;
    $foldercontents = new DirectoryIterator($path);
    foreach($foldercontents as $objectindex => $object)
        {
        if($object->isDot())
            {
            continue;
            }
        $objectname = $object->getFilename();

        if ($object->isDir())
            {
            ShowFilesInFolderRecursive($path . DIRECTORY_SEPARATOR . $objectname);
            }				
        else
            {
            if (substr($objectname,-4)!=".php") // Don't attempt to get PHP as will just execute on the remote server
                {
                echo (substr($path,strlen($storagedir)) . DIRECTORY_SEPARATOR . $objectname) . "\t" . $object->getSize() . "\n";
                }
            }
        }
    }


$access_key=hash("sha256",date("Y-m-d") . $scramble_key); // Generate an access key that changes every day.

if (php_sapi_name() != "cli")
    {
    // Mode is fetch of file list - being accessed remotely
    if (getval("access_key","")!=$access_key)
        {
        exit("Access denied");
        }
    ShowFilesInFolderRecursive($storagedir);
    exit();
    }

// CLI access, connect to the remote system, retrieve the list and start the download
if (!isset($argv[1])) {exit("Usage: php filestore_sync.php [base url of remote system]\n");}
$url=$argv[1];
ob_end_flush(); // Disable output buffering (causes the output to appear in bursts)

$file_list=file_get_contents($url . "/pages/tools/filestore_sync.php?access_key=" . $access_key);
if ($file_list=="Access denied") {exit("Access was denied, ensure \$scramble_key is the same on both systems.\n");}

$files=explode("\n",$file_list);array_pop($files); // Last one is blank as terminates with \n
echo "File list fetched - " . count($files) . " files to check.\n";flush();

$counter=0;
foreach ($files as $file)
    {
    $counter++;
    $s=explode("\t",$file);$file=$s[0];$filesize=$s[1];
    $file=str_replace("\\","/",$file); // Windows path support
    if (!file_exists($storagedir . $file) || filesize($storagedir . $file)!=$filesize)
        {
        echo "(" . $counter . "/" . count($files) . ") Copying " . $file . " - " . $filesize . " bytes\n";flush();

        // Download the file
        $contents=file_get_contents($url . "/filestore/" . $file);

        // Check folder exists
        $s=explode("/",dirname($file));
        $checkdir=$storagedir;
        foreach ($s as $dirpart)
                {
                $checkdir.=$dirpart . "/";    
                if (!file_exists($checkdir)) {mkdir($checkdir,0777);}
                }

        // Write the file to disk
        file_put_contents($storagedir . $file,$contents);
        }
    else 
        {
        echo "In place and size matches: "  . $file . "\n";
        }
    }
echo "Complete.\n";