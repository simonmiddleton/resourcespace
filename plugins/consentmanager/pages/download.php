<?php
include "../../../include/db.php";
include_once "../../../include/authenticate.php";
include "../include/file_functions.php";

$ref=getvalescaped("ref","",true);
$resource=getvalescaped("resource","",true);
$file_path=get_consent_file_path($ref);

# Check access
if ($resource!="")
    {
    $edit_access=get_edit_access($resource);
    if (!$edit_access) {exit("Access denied");} # Should never arrive at this page without edit access
    }
else
    {
    # Editing all consents via Manage Consents - admin only
    if (!checkperm("a")) {exit("Access denied");} 
    }

// Load consent details
$consent=sql_query("select name,email,telephone,consent_usage,notes,expires,file from consent where ref='$ref'");
if (count($consent)==0) {exit("Consent record not found.");}
$consent=$consent[0];

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $consent["file"] . '"');
readfile($file_path);
