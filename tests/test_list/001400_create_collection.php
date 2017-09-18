<?php

include_once(__DIR__ . '/../../include/db.php');
include_once(__DIR__ . '/../../include/collections_functions.php');
if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// create new collections
$levels = array("Top level");

$levelacollection = create_collection(1,"TEST COLLECTION 1",0,0,0,true,$levels);
$getthemes=get_themes($levels);

if(count($getthemes) != 1 || !in_array($levelacollection,array_column($getthemes,"ref")))
    {return false;}

$levels[] = "level 2";    
$levelbcollection = create_collection(1,"TEST COLLECTION 2",0,0,0,true,$levels);
$getthemes=get_themes($levels);
if(count($getthemes) != 1 || !in_array($levelbcollection,array_column($getthemes,"ref")))
    {return false;}

$levels[] = "level 3";
$levelccollection = create_collection(1,"TEST COLLECTION 3",0,0,0,true,$levels);
$getthemes=get_themes(array("Top level","level 2"),true);
if(count($getthemes) != 2  || !in_array($levelbcollection,array_column($getthemes,"ref")) || !in_array($levelccollection,array_column($getthemes,"ref")) )
    {return false;}
                       
$theme_category_levels=4;
$levels[] = "level  4";
$leveldcollection = create_collection(1,"TEST COLLECTION 4",0,0,0,true,$levels);
$getthemes=get_themes(array("Top level","level 2"),true);
if(count($getthemes) != 3  || !in_array($levelbcollection,array_column($getthemes,"ref")) || !in_array($levelccollection,array_column($getthemes,"ref")) || !in_array($leveldcollection,array_column($getthemes,"ref")) )
    {return false;}        

return true;
