<?php
# Simple syntax check of pages 
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

$TestPage = function($page) {
    $html="";
    global $php_path;
    if (!isset($php_path)) {$php_path="/usr/bin";} # fair assumption, if not specifically set; means it will work on many systems
 
    # Run PHP in lint mode against the file.
    $result=exec("cd " . escapeshellarg(dirname($page)). ";" . $php_path . "/php -l " . escapeshellarg(basename($page)) );
    return (strpos($result,"No syntax errors")!==false);
};
 
$exclude_paths = array(
    "/lib/",
    "/filestore/",
);
$Directory = new RecursiveDirectoryIterator(dirname(__FILE__) . "/../../");
$Iterator = new RecursiveIteratorIterator($Directory);
foreach ($Iterator as $i)
    {
    if($i->getExtension() !== "php")
        {
        continue;
        }

    $path = $i->getPathName();

    foreach($exclude_paths as $ex_path)
        {
        if(strpos($path, $ex_path) !== false)
            {
            continue 2;
            }
        }

    $pages[] = $path; 
    }
 
# Test each page.
foreach ($pages as $page)
    {
    if (!$TestPage($page)) {echo $page;return false;}
    }

// Teardown
unset($TestPage);

return true;
