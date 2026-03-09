<?php

/*
Job handler to fetch files from a local path  for batch replacement of alternative files

Requires the following job data:-
$job_data['import_path'] - Folder to scan for files to import
*/

global $lang, $baseurl_short, $offline_job_delete_completed, $fstemplate_alt_threshold;
global $notify_on_resource_change_days, $replace_batch_existing;

$local_path     = $job_data['import_path'];
$minref         = $job_data['batch_replace_min'];
$maxref         = $job_data['batch_replace_max'];
$collectionid   = $job_data['batch_replace_col'];
$filename_field = $job_data['filename_field'];
$no_exif         = ($job_data['no_exif'] == "yes") ? true : false ;

if (!file_exists($local_path)) {
    job_queue_update($jobref, $job_data, STATUS_ERROR);
}

$logtext = array();

include_once __DIR__ . '/../image_processing.php';

if (!isset($collectionid) || $collectionid == 0) {
    $conditions = array();
    $minref = max((int)($minref), $fstemplate_alt_threshold);
    $firstref = max($fstemplate_alt_threshold, $minref);

    $sql_params = array("i", $minref);
    $sql_condition = "";

    if ($maxref > 0) {
        $sql_condition = " AND ref <= ?";
        $sql_params = array_merge($sql_params, array("i", (int)$maxref));
    }

    $replace_resources = ps_array("SELECT ref value FROM resource WHERE ref >= ? " . $sql_condition . " ORDER BY ref ASC", $sql_params, 0);
    $logtext[] = "Replacing files for resource IDs. Min ID: " . $minref  . (($maxref > 0) ? " Max ID: " . $maxref : "");
} else {
    $replace_resources = get_collection_resources($collectionid);
    $logtext[] = "Replacing resources within collection " . $collectionid . " only";
}


$logtext[] = "Embedded (EXIF) data extraction: " . ($no_exif ? "OFF" : "ON");
$replaced = array();
$errors = array();

$foldercontents = new DirectoryIterator($local_path);
foreach ($foldercontents as $objectindex => $object) {
    if ($object->isDot() || $object->isDir() || !($object->isReadable())) {
        continue;
    }

    $filename   = $object->getFilename();
    $extension  = $object->getExtension();
    $full_path = $local_path . DIRECTORY_SEPARATOR . $filename;

    $alternatives = get_alternative_files_by_filename($filename, $collectionid, $min, $max);

    if (count($alternatives) == 1) {
        $alternative = $alternatives[0]['ref'];
        $resource = $alternatives[0]['resource'];
        $extension = $alternatives[0]['extension'];

        // A single resource has been found with the same filename
        $altfile = get_resource_path($resource, true, '', true, $extension, true, 1, false, '', $alternative);
        try {
            copy($full_path, $altfile);
            $success = filesize_unlimited($full_path) == filesize_unlimited($altfile);
        } catch (Exception $e) {
            $success = false;
            $errors[] = "ERROR - Copy operation failed for {$full_path} : " . $e->getMessage();
        }
        if ($success) {
            // update resource alt file table
            ps_query();
            resource_log($resource, "u", 0);
            create_previews($resource, false, $extension, false, false, $alternative);
            if ($notify_on_resource_change_days != 0) {
                notify_resource_change($resource);
            }
            $replaced[] = $resource;
            try_unlink($full_path);
        } else {
            $errors[] = "Failed to copy file from : " .  $full_path;
        }
    } elseif (count($valid_resources) == 0) {
        // No resource found with the same filename
        $errors[] = "Failed to find matching alternative file record for: " .  $filename;
    } else {
        // Multiple resources found with the same filename
        if ($replace_alt_batch_existing) {
            $copy_error = false;
            foreach ($alternatives as $alternative) {
                $alternative = $alternative['ref'];
                $resource = $alternative['resource'];
                $extension = $alternative['extension'];
                $altfile = get_resource_path($alternative['resource'], true, '', true, $extension);
                try {
                    copy($full_path, $altfile);
                    $success = filesize_unlimited($full_path) == filesize_unlimited($altfile);
                } catch (Exception $e) {
                    $success = false;
                    $errors[] = "ERROR - Copy operation failed for {$full_path} : " . $e->getMessage();
                }
                if ($success) {
                    ps_query("update resource set file_extension = lower(?) where ref = ?", array("s", $extension, "i", $valid_resource));
                    resource_log($valid_resource, "u", 0);
                    if (!$no_exif) {
                        extract_exif_comment($valid_resource, $extension);
                    }
                    create_previews($valid_resource, false, $extension);
                    if ($notify_on_resource_change_days != 0) {
                        notify_resource_change($valid_resource);
                    }
                    $replaced[] = $resource;
                } else {
                    $errors[] = "Failed to copy file from : " .  $full_path;
                    $copy_error = true;
                }
            }
            // Attempt to delete
            if (!$copy_error) {
                try_unlink($full_path);
            }
        } else {
            // Multiple resources found with the same filename
            $resourcelist = implode(",", array_column($alternatives, 'resource'));
            $errors[] = "ERROR - multiple alternative file records found with filename '" . $filename . "'. Resource IDs : " . $resourcelist;
        }
    }
}

$logtext[] = "Replaced " . count($replaced) . " alternative files: -";

if (count($replaced) > 0) {
    $logtext[] = "Replaced alternative files for resource IDs:";
    $logtext[] = implode(",", $replaced);
}

if (count($errors) > 0) {
    $logtext[] = "ERRORS: -";
    $logtext = array_merge($logtext, $errors);
    job_queue_update($jobref, $job_data, STATUS_ERROR);
} else {
    if ($offline_job_delete_completed) {
        job_queue_delete($jobref);
    } else {
        job_queue_update($jobref, $job_data, STATUS_COMPLETE);
    }
    $jobsuccess = true;
}

echo " --> " . implode("\n --> ", $logtext) . "\n";
message_add($job["user"], implode("<br />", $logtext), (count($replaced) > 0) ? $baseurl_short . "pages/search.php?search=!list" . implode(":", $replaced) : "");
