<?php
ob_start(); $nocache=true;
$rs_root = dirname(dirname(dirname(__DIR__)));
include_once "{$rs_root}/include/db.php";
include_once "{$rs_root}/include/general.php";
include_once "{$rs_root}/include/authenticate.php";
ob_end_clean();

$id = getval("id", ""); # ID can be a string or integer based on how each provider implemented it
$file_path = getval("file", "");
$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);

$file = file_get_contents($file_path);
$file_size = strlen($file);
$filename = safe_file_name($id) . ".{$file_ext}";

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Content-Length: {$file_size}");

echo $file;

exit();