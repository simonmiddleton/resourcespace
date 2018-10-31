<?php
# This is based on pages/tools/update_previews.php but for use on the server backend to avoid browser timeouts etc.
# previewbased is an option that can help preserve alternative previews, 
# Recreating previews would normally use the original file and overwrite alternative previews that have been uploaded,
# but with previewbased=true, it will try to find a suitable large preview image to generate the smaller versions from.
# If you want to recreate preview for a single resource, you can pass ref=[ref]&only=true
# also includes optional -videoupdate to cater for systems moving from old flv videos to HTML5 compatible video

include_once __DIR__ . "/../include/db.php";
include_once __DIR__ . "/../include/general.php";
include_once __DIR__ . "/../include/image_processing.php";
include_once __DIR__ . "/../include/resource_functions.php";
include_once __DIR__ . "/../include/collections_functions.php";

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) != 'cli')
        {
        exit("Command line execution only.");
        }
if(isset($argv[1]) && strtolower($argv[1]) == "collection" && isset($argv[2]) && is_numeric($argv[2]))
    {
    $collectionid = $argv[2];   
    }
elseif(isset($argv[1]) && strtolower($argv[1]) == "resource" && isset($argv[2]) && is_numeric($argv[2]))
    {
    $ref = $argv[2];
    if(isset($argv[3]) && is_numeric($argv[3]))
        {
        $max = $argv[3];
        }
    }
else
    {
    echo "update_previews.php - update previews for all/selected resources\n\n";
    echo "- extra options to use existing uploaded previews or to force recreation of video previews e.g. when changing to mp4/hls previews\n";
    echo "USAGE:\n";
    echo "php update_previews.php [collection|resource] [id] [maxref] [-previewbased] [-videoupdate]\n\n";
    echo "examples\n";
    echo "php update_previews.php collection 247\n";
    echo "- this will update previews for all resources in collection #247\n\n";
    echo "php update_previews.php collection 380 -previewbased\n";
    echo "- this will update previews for all resources in collection #380, utilising any existing uploaded previews\n\n";
    echo "php update_previews.php resource 19564\n";
    echo "- this will update previews for all resources starting with resource ID #19564\n\n";
    echo "php update_previews.php resource 19564 19800\n";
    echo "- this will update previews for resources starting with resource ID #19564 and ending with resource 19800\n\n";
    echo "php update_previews.php resource 1 -videoupdate\n";
    echo "- this will update previews for all video resources that do not have the required '\$ffmpeg_preview_extension' extension or hls m3u8 playlist files\n\n";
    exit();
    }

$previewbased = in_array("-previewbased",$argv);
$videoupdate = in_array("-videoupdate",$argv);

function update_preview($ref, $previewbased)
    {
    $resourceinfo=sql_query("select * from resource where ref='$ref'");
    if (count($resourceinfo)>0 && !hook("replaceupdatepreview", '', array($ref, $resourceinfo[0])))
        {
        if(!empty($resourceinfo[0]['file_path'])){$ingested=false;}
        else{$ingested=true;}
        create_previews($ref, false,($previewbased?"jpg":$resourceinfo[0]["file_extension"]),false, $previewbased,-1,true,$ingested);
        hook("afterupdatepreview","",array($ref));
        return true;
        }
    return false;
    }
    
if (!isset($collectionid))
    {
    $conditions = array();
    if (isset($max))
        {
        $conditions[] = "ref <='" . escape_check($max) . "'";
        }
    if ($videoupdate)
        {
        $conditions[] = "file_extension in ('" . implode("','",$ffmpeg_supported_extensions) . "')";
        }
     if (isset($resource_deletion_state))
        {
        $conditions[] = "archive <> '" . $resource_deletion_state . "'";
        }
    $resources = sql_array("SELECT ref value FROM resource WHERE ref>='" . escape_check($ref)  . "'" . ((count($conditions) > 0) ? " AND " . implode(" AND ", $conditions):"") . " ORDER BY ref asc",0);
    }
else
    {
    $resources = get_collection_resources($collectionid);   
    }

if(is_array($resources) && count($resources) > 0)
    {
    foreach ($resources as $resource)
        {
        if($videoupdate)
            {
            $checkflvpreview = get_resource_path($resource, true, 'pre', false, 'flv', true, 1, false, '');
            if($video_preview_player_hls != 0)
                {
                $correctvideo_preview = get_resource_path($resource, true, "pre", false, "m3u8", true, 1, false);
                }
            else
                {
                $correctvideo_preview = get_resource_path($resource, true, 'pre', false, $ffmpeg_preview_extension, true, 1, false);
                }
            echo "Checking for video preview of resource #" . $resource .  ".....";
            if(file_exists($correctvideo_preview))
                {
                echo "...already exists, skipping\n";
                continue;
                }
            }
            
        echo "Recreating previews for resource #" . $resource . "...";
        ob_flush(); 
        if (update_preview($resource, $previewbased))
            {
            echo "....completed\n"; 
            }
        else
            {
            echo "FAILED - skipping\n";
            }
        ob_flush();
        }
    }
else
    {
    echo "No resources found\n";    
    }
echo "\nFinished\n";
