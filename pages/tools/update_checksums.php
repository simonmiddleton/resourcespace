<?php
#
#
# Script to update the file checksum for existing files.
# This should be executed once, when checksums do not exist on the resources in the database, e.g. when upgrading from
# version 1.4 (which did not have the checksum feature) to 1.5
#
# If you would like to recreate all checksums (useful after adjusting $file_checksums_50k) you can pass "recreate=true"
#
$cwd = dirname(__FILE__);
include "$cwd/../../include/db.php";
include_once "$cwd/../../include/general.php";
include "$cwd/../../include/image_processing.php";
include "$cwd/../../include/resource_functions.php";

// Allow access from UI (legacy mode) only if authenticated and admin
if('cli' != PHP_SAPI)
    {
    include "$cwd/../../include/authenticate.php";

    if(!checkperm('a'))
        {
        http_response_code(401);
        exit($lang['error-permissiondenied']);
        }
    }

$recreate = false;
$cli_options = ('cli' == PHP_SAPI ? getopt('', array('recreate')) : array());
if(array_key_exists('recreate', $cli_options))
    {
    $recreate = true;
    }

$recreate = (bool) getvalescaped("recreate", $recreate);
if($recreate)
    {
    $resources=sql_query("SELECT ref,file_extension FROM resource WHERE ref>0 AND integrity_fail=0 AND length(file_extension)>0 ORDER by ref ASC");
    }
else
    {
    $resources=sql_query("SELECT ref,file_extension FROM resource WHERE ref>0 AND integrity_fail=0 AND length(file_extension)>0 AND (file_checksum IS NULL OR file_checksum = '')");
    }

for($n = 0; $n < count($resources); $n++)
	{
	if(generate_file_checksum($resources[$n]["ref"], $resources[$n]["file_extension"], true))
        {
        echo "Key for " . $resources[$n]["ref"] . " generated<br />\n";
        }
    else
        {
        echo "Key for " . $resources[$n]["ref"] . " NOT generated<br />\n";
        }
    }