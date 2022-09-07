<?php
include "../../../include/db.php";
include_once "../../../include/authenticate.php";
include "../include/file_functions.php";

$ref=getval("ref","",true);
$resource=getval("resource","",true);
$file_path=get_consent_file_path($ref);

# Check access
if ($resource!="")
    {
    $edit_access=get_edit_access($resource);
    if (!$edit_access && !checkperm("cm")) {exit("Access denied");} # Should never arrive at this page without edit access
    }
else
    {
    # Editing all consents via Manage Consents - admin only
    if (!checkperm("a") && !checkperm("cm")) {exit("Access denied");} 
    }

// Load consent details
$consent=ps_query("select name,email,telephone,consent_usage,notes,expires,file from consent where ref= ?", ['i', $ref]);
if (count($consent)==0) {exit("Consent record not found.");}
$consent=$consent[0];

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $consent["file"] . '"');
readfile($file_path);
