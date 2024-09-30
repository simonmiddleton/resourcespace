<?php
# Simple syntax check of pages 
command_line_only();


$TestPage = function($page) {
    global $php_path;
    if (!isset($php_path)) {$php_path="/usr/bin";} # fair assumption, if not specifically set; means it will work on many systems
 
    # Run PHP in lint mode against the file.
    $result=exec("cd " . escapeshellarg(dirname($page)). ";" . $php_path . "/php -l " . escapeshellarg(basename($page)) );
    return strpos($result,"No syntax errors") !== false;
};
 
$exclude_paths = array(
    "/lib/",
    "/filestore/",
    "/vendor/",
    "rector.php"
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

$counter = 0;
# Test each page.
$shown_percent = -1;
foreach ($pages as $page)
    {
    $new_percent = round($counter*100/count($pages));
    if ($new_percent!=$shown_percent)
	{
	echo "\e[4D" . str_pad($new_percent,"3"," ",STR_PAD_LEFT) . "%";
        ob_flush();
	$shown_percent=$new_percent;
        }
    if (!$TestPage($page)) {echo $page;return false;}
    $counter++;
    }
    echo "\e[4D    ";
// Teardown
unset($TestPage);

return true;
