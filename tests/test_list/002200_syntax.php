<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

# Simple syntax check of pages to ensure they all function at a very basic level and arrive at the footer.
 
# Build array of pages to scan.
$Directory = new RecursiveDirectoryIterator(dirname(__FILE__) . "/../../");
$Iterator = new RecursiveIteratorIterator($Directory);
foreach ($Iterator as $i)
    {
    $i=$i->getPathName();
 
    # Parse all PHP files but not those in the lib folder(s) which are external.
    if (strpos($i,".php")!==false && strpos($i,"/lib/")===false) {$pages[]=$i;}
    }
 
# Test each page.
foreach ($pages as $page)
    {
    if (!TestPage($page)) {echo $page;return false;}
    }
return true;
 
function TestPage($page)
    {
    $html="";
    global $php_path;
    if (!isset($php_path)) {$php_path="/usr/bin";} # fair assumption, if not specifically set; means it will work on many systems
 
    # echo "\n" . $page;
 
    # Run PHP in lint mode against the file.
    $result=exec("cd " . escapeshellarg(dirname($page)). ";" . $php_path . "/php -l " . escapeshellarg(basename($page)) );
    return (strpos($result,"No syntax errors")!==false);
    }
