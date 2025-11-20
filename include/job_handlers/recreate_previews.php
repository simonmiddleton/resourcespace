<?php

include_once __DIR__ . '/../image_processing.php';

# $job_data["process_type"] -> Either "resource" or "collection"
# $job_data["refs"] -> Comma-separated list of references (can be either Resource or Collection refs)
# $job_data["sizes"] -> List of image sizes to generate, by preview_size.id
# $job_data["types"] -> List of resource types to process, by resource_type.ref
# $job_data["use_existing"] -> Optional. Boolean, will default to false if not specified
# $job_data["video_update"] -> Optional. Boolean, will default to false if not specified








if (in_array("sizes", $argv)) {
    $sizes  = explode(",", $argv[array_search("sizes", $argv) + 1]);
} else {
    $sizes = array();
}

if (in_array("types", $argv)) {
    $resource_types = explode(",", $argv[array_search("types", $argv) + 1]);
} else {
    $resource_types = array();
}

$previewbased = in_array("-previewbased", $argv);
$videoupdate = in_array("-videoupdate", $argv);
$delete_existing = in_array("-delete", $argv) && !$previewbased && !$videoupdate && count($sizes) == 0;

function update_preview($ref, $previewbased, $sizes, $delete_existing)
{
    $resourceinfo = ps_query("select file_path, file_extension from resource where ref = ?", array("i", (int)$ref));
    if (count($resourceinfo) > 0 && !hook("replaceupdatepreview", '', array($ref, $resourceinfo[0]))) {
        if (!empty($resourceinfo[0]['file_path'])) {
            $ingested = false;
        } else {
            $ingested = true;
        }
        if ($delete_existing) {
            delete_previews($ref);
        }
        create_previews(
            $ref, 
            false, 
            ($previewbased || in_array($resourceinfo[0]["file_extension"], NON_PREVIEW_EXTENSIONS) ? "jpg" : $resourceinfo[0]["file_extension"]), 
            false, 
            $previewbased || in_array($resourceinfo[0]["file_extension"], NON_PREVIEW_EXTENSIONS), 
            -1, 
            true, 
            $ingested, 
            true, 
            $sizes
        );
        hook("afterupdatepreview", "", array($ref));
        update_disk_usage($ref);
        return true;
    }
    return false;
}

if (!isset($collectionid)) {
    $conditions = array();
    $conditions_params = array("i", (int)$ref);
    if (isset($max)) {
        $conditions[] = "ref <= ?";
        $conditions_params = array_merge($conditions_params, array("i", $max));
    }
    if ($videoupdate) {
        $conditions[] = "file_extension in (" . ps_param_insert(count($ffmpeg_supported_extensions)) . ")";
        $conditions_params = array_merge($conditions_params, ps_param_fill($ffmpeg_supported_extensions, "s"));
    }
    if (isset($resource_deletion_state)) {
        $conditions[] = "archive <> ?";
        $conditions_params = array_merge($conditions_params, array("i", $resource_deletion_state));
    }
    if (!empty($resource_types)) {
        $conditions[] = "resource_type in (" . ps_param_insert(count($resource_types)) . ")";
        $conditions_params = array_merge($conditions_params, ps_param_fill($resource_types, "i"));
    }
    $resources = ps_array("SELECT ref value FROM resource WHERE ref >= ?" . ((count($conditions) > 0) ? " AND " . implode(" AND ", $conditions) : "") . " ORDER BY ref asc", $conditions_params, 0);
} else {
    $resources = get_collection_resources($collectionid);
}

if (is_array($resources) && count($resources) > 0) {
    hook('beforescriptaction');
    foreach ($resources as $resource) {
        if ($videoupdate) {
            $checkflvpreview = get_resource_path($resource, true, 'pre', false, 'flv', true, 1, false, '');
            $correctvideo_preview = get_resource_path($resource, true, 'pre', false, $ffmpeg_preview_extension, true, 1, false);
            echo "Checking for video preview of resource #" . $resource .  ".....";
            if (file_exists($correctvideo_preview)) {
                echo "...already exists, skipping\n";
                continue;
            }
        }

        echo "Recreating previews for resource #" . $resource . "...";
        ob_flush();
        if (update_preview($resource, $previewbased, $sizes, $delete_existing)) {
            echo "....completed\n";
        } else {
            echo "FAILED - skipping\n";
        }
        ob_flush();
    }
} else {
    echo "No resources found\n";
}
echo "\nFinished\n";
