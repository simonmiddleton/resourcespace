<?php

use function ImageBanks\validFileSource;

ob_start(); $nocache=true;
$rs_root = dirname(__DIR__, 3);
include_once "{$rs_root}/include/db.php";
include_once "{$rs_root}/include/authenticate.php";
ob_end_clean();

$id = getval("id", ""); # ID can be a string or integer based on how each provider implemented it
$file_path = getval("file", "");

printf('<br>id = %s', $id);
printf('<br>file_path = %s', $file_path);

/*
todo:
- change logic to get the Providers' ID and only validate against that one.
- allow the selected Provider to provide further info on the file (e.g RS provider could parse the URL and extract the
    extension or make further API calls as needed).
    e.g: fct getDownloadFileInfo(SplFileInfo $file): SplFileInfo
Pixabay and Shuttherstock will simply return the input.

Note: similar logic is on ajax.php
*/
if(!validFileSource($file_path, $image_banks_loaded_providers))
    {
    $log_activity_note = str_replace("%FILE", $file_path, $lang["image_banks_local_download_attempt"]);
    log_activity($log_activity_note, LOG_CODE_SYSTEM, null, 'user', null, null, null, null, $userref, false);
    exit();
    }

$file = file_get_contents($file_path);
$file_size = strlen($file);
$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
$filename = "{$id}.{$file_ext}";

printf('<br>filename = %s', $filename);
printf('<br>file_ext = %s', $file_ext);
die('<br>You died at line ' . __LINE__ . ' in file ' . __FILE__);

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Content-Length: {$file_size}");
echo $file;
exit();
