<?php include "../../include/boot.php";

// Copy files from the remote system
// Before running this the scramble key must be the same on both systems so copy over the relevant config.php entries first.

// Output all files in a folder, recursively.
function ShowFilesInFolderRecursive ($path)
{
    global $storagedir;
    $foldercontents = new DirectoryIterator($path);
    foreach ($foldercontents as $object) {
        if ($object->isDot()) {
            continue;
        }
        $objectname = $object->getFilename();

        if ($object->isDir() && $objectname !== "tmp") {
            ShowFilesInFolderRecursive($path . DIRECTORY_SEPARATOR . $objectname);
        } elseif (substr($objectname,-4) != ".php") {
            // Don't attempt to get PHP as will just execute on the remote server
            echo (substr($path,strlen($storagedir)) . DIRECTORY_SEPARATOR . $objectname) . "\t" . $object->getSize() . "\n";
        }
    }
}

$access_key = hash("sha256",date("Y-m-d") . $scramble_key); // Generate an access key that changes every day.

if (php_sapi_name() != "cli") {
    // Mode is fetch of file list - being accessed remotely
    if (getval("access_key","") != $access_key) {
        exit("Access denied");
    }
    ShowFilesInFolderRecursive($storagedir);
    exit();
}

// CLI access, connect to the remote system, retrieve the list and start the download. username and password for basic auth can be provided if required
if (!isset($argv[1])) {
    echo "Usage: " . PHP_EOL . "  php filestore_sync.php [username:password@][base url of remote system]" . PHP_EOL;
    echo "  e.g. php filestore_sync.php a.user:mypassword@https://acme.resourcespace.com" . PHP_EOL;
    echo "       php filestore_sync.php https://acme.resourcespace.com" . PHP_EOL;
    exit();
}

$url = $argv[1];
$auth_part = strpos($url, "@");
if ($auth_part !== false) {
    // Get basic auth credentials
    $credentials = substr($url, 0, $auth_part);
    $url = substr($url, $auth_part+1);
    if (strpos($credentials, ":") !== false) {
        $credparts = explode(":", $credentials);
        $remote_user = trim($credparts[0]);
        $remote_password = trim($credparts[1] ?? "");
    }
}

ob_end_flush(); // Disable output buffering (causes the output to appear in bursts)

// Get the file
$ch = curl_init($url . "/pages/tools/filestore_sync.php?access_key=" . $access_key);
if (trim($remote_user ?? "") !== "" && trim($remote_password ?? "") !== "") {
    echo "Using basic authentication for " . $remote_user . ":" . $remote_password . PHP_EOL;
    curl_setopt($ch, CURLOPT_USERPWD, $remote_user . ":" . $remote_password);
}
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 minutes
$file_list = curl_exec($ch);
curl_close($ch);

// Process the file list
$files = explode("\n", $file_list);
array_pop($files); // Last one is blank as terminates with \n
echo "File list fetched - " . count($files) . " files to check.\n";flush();

$counter=0;
foreach ($files as $file) {
    if (substr($file, 0, 3) == "tmp") {
        continue;
    }
    $counter++;
    $s = explode("\t", $file);
    $file = $s[0];
    $filesize = $s[1];
    $file = str_replace("\\", "/", $file); // Windows path support
    if (!file_exists($storagedir . $file) || filesize($storagedir . $file) != $filesize) {
        echo "(" . $counter . "/" . count($files) . ") Copying " . $file . " - " . $filesize . " bytes\n";
        flush();

        // Download the file
        $ch = curl_init($url . "/filestore/" . $file);
        if (trim($remote_user ?? "") !== "" && trim($remote_password ?? "") !== "") {
            echo "Using basic authentication for " . $remote_user . PHP_EOL;
            curl_setopt($ch, CURLOPT_USERPWD, $remote_user . ":" . $remote_password);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);

        if ($result !== false) {
            $contents = $result;
        } else {
            echo "Error: " . curl_errno($ch) . PHP_EOL;
            continue;
        }
        curl_close($ch);

        // Check folder exists
        $s = explode("/", dirname($file));
        $checkdir = $storagedir;
        foreach ($s as $dirpart) {
            $checkdir .= $dirpart . "/";
            if (!file_exists($checkdir)) {
                mkdir($checkdir, 0777);
            }
        }

        // Write the file to disk
        file_put_contents($storagedir . $file, $contents);
    } else {
        echo "In place and size matches: " . $file . "\n";
    }
}
echo "Complete.\n";