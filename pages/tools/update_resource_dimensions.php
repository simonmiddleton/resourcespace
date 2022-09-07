<?php
#
# Script to update resource_dimensions table for all resources.

include "../../include/db.php";

include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}

set_time_limit(0);

if(!$exiftool_resolution_calc)
    {
    die("Please turn on the exiftool resolution calculator in your config.php file.");
    }
else
    {
    $exiftool_fullpath = get_utility_path("exiftool");
    if($exiftool_fullpath==false){
    die("Could not find exiftool. Aborting...");
    }
else
    {
    # Get all resources in the DB
    # $view_title_field is not user provided
    $resources=ps_query("select ref,field".$view_title_field.",file_extension from resource where ref>0 order by ref");

    foreach($resources as $resource)
        {
        $resource_path=get_resource_path($resource['ref'],true,"",false,$resource['file_extension']);
        if (file_exists($resource_path) && !in_array($resource['file_extension'],$exiftool_no_process))
            {
            $resource=get_resource_data($resource['ref']);
            exiftool_resolution_calc($resource_path,$resource['ref'],true);
            echo "Ref: ".$resource['ref']." - ".$resource['field'.$view_title_field]." - updating resource_dimensions record.<br/>";
            }
        }
    }
    echo "Finished updating resource_dimensions.<br/>";
}
