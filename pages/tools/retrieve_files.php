<?php
/**
* Utility script to import files from a remote ResourceSpace system that is only accessible over HTTP/HTTPS
*
* The auto_login plugin can be used to enable temporary unauthenticated access from the receiving server's public IP
* *** CAUTION SHOULD BE USED IF SERVER IS ON AN INTERNAL NETWORK ****
* Optionally a minimum and maximum resource ID can be passed to test the behaviour for a few resources initially
*
* Chnage $remotebaseurl to point to the old server
*/

if('cli' != PHP_SAPI)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied - Command line only!');
    }


### CHANGE THIS ####
$remotebaseurl = 'http://old.resourcespaceurl.com';
####################


include __DIR__ . '/../../include/db.php';
include_once __DIR__ . '/../../include/general.php';
include_once __DIR__ . '/../../include/resource_functions.php';
set_time_limit(0);

if(isset($argv[1]) && is_numeric($argv[1]))
    {
    $min = $argv[1];
    if(isset($argv[2]) && is_numeric($argv[2]))
        {
        $max = $argv[2];
        }
    }

$conditions = array();
if (isset($min))
    {
    $conditions[] = "ref >='" . escape_check($min) . "'";
    }
if (isset($max))
    {
    $conditions[] = "ref <='" . escape_check($max) . "'";
    }
$sql = "SELECT ref, file_path, file_extension FROM resource WHERE ref>0 " . ((count($conditions) > 0) ? " AND " . implode(" AND ", $conditions):"") . " ORDER BY ref DESC";

$allresources = sql_query($sql);
$errors = array();
$missingdirs = array();
$missingfiles = array();

$hide_real_filepath = true;


foreach($allresources as $resource)
    {
    $local_path= get_resource_path($resource["ref"],true,'',false,$resource["file_extension"],true);
    echo "Checking for presence of file - " . $local_path . "\n";
    if (!file_exists($local_path))
        {
        echo " - File missing - checking URL\n";
        $local_url = get_resource_path($resource["ref"],false,'',false,$resource["file_extension"],true);
        $remote_url = str_replace($baseurl, $remotebaseurl, $local_url);
        $file_headers = @get_headers($remote_url);

        if($file_headers[0] == 'HTTP/1.0 404 Not Found')
            {
             echo " - The remote file " . $resource["ref"]  . " does not exist\n";
             $missingfiles[] = $resource["ref"];
            }
        else if ($file_headers[0] == 'HTTP/1.0 302 Found' && $file_headers[7] == 'HTTP/1.0 404 Not Found')
            {
            echo " - The file  " . $resource["ref"]  . " does not exist, and I got redirected to a custom 404 page..\n";
             $missingfiles[] = $resource["ref"];
            }
        else
            {
            echo " - Found remote file, ingesting\n";
            global $get_resource_path_fpcache;
            $get_resource_path_fpcache[$resource["ref"]] = ""; // Forces get_resource_path to ignore the file path 
            $newpath = get_resource_path($resource["ref"],true,"",true,$resource["file_extension"],true,1,false,'');
            copy($remote_url,$newpath);
            echo str_pad(" - Copying file to " . $newpath . "\n",30);
            }
        }
    ob_flush();
    flush();
    }

echo "\nMissing files: -\n";
 echo implode(",",$missingfiles);
