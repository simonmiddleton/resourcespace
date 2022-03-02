<?php
include "../../../include/db.php";
include_once "../../../include/authenticate.php";
include "../include/file_functions.php";

$ref=getvalescaped("ref","",true);
$resource=getvalescaped("resource","",true);
$file_path=get_license_file_path($ref);

# Check access
if ($resource!="")
    {
    $edit_access=get_edit_access($resource);
    if (!$edit_access) {exit("Access denied");} # Should never arrive at this page without edit access
    }
else
    {
    # Editing all license via Manage Licenses - admin only
    if (!checkperm("a")) {exit("Access denied");} 
    }

// Load license details
$license=sql_query("select outbound,holder,license_usage,description,expires,file from license where ref='$ref'");
if (count($license)==0) {exit("License record not found.");}
$license=$license[0];

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $license["file"] . '"');
readfile($file_path);
