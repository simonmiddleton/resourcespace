<?php
ob_start(); $nocache=true;
$rs_root = dirname(dirname(dirname(__DIR__)));
include_once "{$rs_root}/include/db.php";

include_once "{$rs_root}/include/authenticate.php";
ob_end_clean();

$id = getval("id", ""); # ID can be a string or integer based on how each provider implemented it
$file_path = getval("file", "");

if(!\ImageBanks\validFileSource($file_path, $image_banks_loaded_providers))
    {
    $log_activity_note = str_replace("%FILE", $file_path, $lang["image_banks_local_download_attempt"]);
    log_activity($log_activity_note, LOG_CODE_SYSTEM, null, 'user', null, null, null, null, $userref, false);
    exit();
    }

$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);

$file = file_get_contents($file_path);
$file_size = strlen($file);
$filename = safe_file_name($id) . ".{$file_ext}";

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Content-Length: {$file_size}");

echo $file;

exit();