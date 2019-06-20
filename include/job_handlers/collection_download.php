<?php
/*
Job handler to process collection downloads

Requires the following job data:
$job_data['k'] - Share key
$job_data['collection'] - 
$job_data['result'] - Search result of !collectionX
$job_data['size'] - 
$job_data['exiftool_write_option']
$job_data['usertempdir'] - temporary directory for this download
$job_data['useoriginal'] - 
$job_data['id'] - 
$job_data['includetext'] - 
$job_data['progress_file'] - 
$job_data['count_data_only_types'] - 
$job_data['usage'] - 
$job_data['usagecomment'] - 
$job_data['available_sizes'] - 
$job_data['settings_id'] - 
$job_data['include_csv_file'] - User input opting to include the CSV file in the downloaded archive
*/
include_once __DIR__ . '/../search_functions.php';
include_once __DIR__ . '/../resource_functions.php';
include_once __DIR__ . '/../collections_functions.php';
include_once __DIR__ . '/../pdf_functions.php';
include_once __DIR__ . '/../csv_export_functions.php';

global $lang, $baseurl, $offline_job_delete_completed, $exiftool_write_option, $progress_file, $k, $usage, $usagecomment,
$text, $collection_download_settings, $pextension;

foreach($job_data as $arg => $value)
    {
    $$arg = $value;
    }

// Set up the user who requested the collection download as it needs to be processed in its name
$user_data = validate_user("u.ref = '{$job['user']}'", true);
if(count($user_data) > 0)
    {
    setup_user($user_data[0]);
    }
else
    {
    job_queue_update($jobref, $job_data, STATUS_ERROR);
    return;
    }

$collectiondata = get_collection($collection);
$collection_resources = $result;
$modified_collection_resources = hook("modifycollectiondownload");
if(is_array($modified_collection_resources))
    {
    $collection_resources = $modified_collection_resources;
    }

# initiate text file
if($GLOBALS['zipped_collection_textfile'] == true && $includetext == "true")
    {
    $text = i18n_get_collection_name($collectiondata) . "\r\n" .
    $lang["downloaded"] . " " . nicedate(date("Y-m-d H:i:s"), true, true) . "\r\n\r\n" .
    $lang["contents"] . ":\r\n\r\n";
    }

// Define the archive file
collection_download_get_archive_file($archiver, $settings_id, $usertempdir, $collection, $size, $zip, $zipfile);

$path = "";
$deletion_array = array();
$filenames = array(); # set up an array to store the filenames as they are found (to analyze dupes)
$used_resources = array();
$subbed_original_resources = array();

for($n = 0; $n < count($collection_resources); $n++)
    {
    resource_type_config_override($collection_resources[$n]['resource_type']);

    $copy = false; 
    $ref = $collection_resources[$n]['ref'];
    $access = get_resource_access($collection_resources[$n]);
    $use_watermark = check_use_watermark();

    // Do not download resources without proper access level
    if(!($access == 0 || $access == 1))
        {
        continue;
        }

    $pextension = get_extension($collection_resources[$n], $size);
    $usesize = ($size == 'original' ? '' : $usesize = $size);
    $p = get_resource_path($ref, true, $usesize, false, $pextension, -1, 1, $use_watermark);

    $subbed_original = false;
    $target_exists = file_exists($p);
    $replaced_file = false;

    $new_file = hook('replacedownloadfile', '', array($collection_resources[$n], $usesize, $pextension, $target_exists));
    if ($new_file != '' && $p != $new_file)
        {
        $p = $new_file;
        $deletion_array[] = $p;
        $replaced_file = true;
        $target_exists = file_exists($p);
        }
    else if(!$target_exists && $useoriginal == 'yes' && resource_download_allowed($ref,'',$collection_resources[$n]['resource_type']))
        {
        // this size doesn't exist, so we'll try using the original instead
        $p = get_resource_path($ref, true, '', false, $collection_resources[$n]['file_extension'], -1, 1, $use_watermark);
        $pextension = $collection_resources[$n]['file_extension'];
        $subbed_original_resources[] = $ref;
        $subbed_original = true;
        $target_exists = file_exists($p);
        }

    // Move to next resource if file doesn't exist or restricted access and user doesn't have access to the requested size
    if(
        !(
            (
                ($target_exists && $access == 0)
                || (
                    $target_exists
                    && $access == 1
                    && (image_size_restricted_access($size) || ($usesize == '' && $restricted_full_download))
                )
            )
            && resource_download_allowed($ref, $usesize, $collection_resources[$n]['resource_type'])
        )
    )
        {
        continue;
        }

    $used_resources[] = $ref;
    $tmpfile = false;

    if($exiftool_write_option)
        {
        $tmpfile = write_metadata($p, $ref, $id);

        if($tmpfile !==false && file_exists($tmpfile))
            {
            $p = $tmpfile; // file already in tmp, just rename it
            }
        else if(!$replaced_file)
            {
            $copy = true; // copy the file from filestore rather than renaming
            }
        }

    // If using original filenames when downloading, copy the file to new location so the name is included.
    $filename = '';
    # Compute a filename for this resource
    $filename=get_download_filename($ref,$size,0,$pextension);

    if($GLOBALS["original_filenames_when_downloading"])
        {
        collection_download_use_original_filenames_when_downloading($filename, $ref, false, $filenames);
        }

    if(hook("downloadfilenamealt"))
        {
        $filename = hook("downloadfilenamealt");
        }

    collection_download_process_text_file($ref, $collection, $filename);

    hook('modifydownloadfile');

    $path .= $p . "\r\n";

    if($GLOBALS['use_zip_extension'])
        {
        $zip->addFile($p,$filename);
        }

    collection_download_log_resource_ready($tmpfile, $deletion_array, $ref);
    }

if(0 < $count_data_only_types)
    {
    collection_download_process_data_only_types($collection_resources, $id, false, $usertempdir, $zip, $path, $deletion_array);
    }
else if('' == $path)
    {
    job_queue_update($jobref, $job_data, STATUS_ERROR);

    message_add($job["user"], $lang["nothing_to_download"]);

    return;
    }

collection_download_process_summary_notes(
    $collection_resources,
    $available_sizes,
    $text,
    $subbed_original_resources,
    $used_resources,
    $id,
    $collection,
    $collectiondata,
    false,
    $usertempdir,
    $filename,
    $path,
    $deletion_array,
    $size,
    $zip);

if($include_csv_file == 'yes')
    {
    collection_download_process_csv_metadata_file(
        $collection_resources,
        $id,
        $collection,
        false,
        $GLOBALS['use_zip_extension'],
        $zip,
        $path,
        $deletion_array);
    }

collection_download_process_command_to_file($GLOBALS['use_zip_extension'], false, $id, $collection, $size, $path);

if($archiver)
    {
    $suffix = '.' . $collection_download_settings[$settings_id]['extension'];
    }
else
    {
    $suffix = '.zip';
    }

collection_download_process_collection_download_name($filename, $collection, $size, $suffix, $collectiondata);

collection_download_process_archive_command(false, $zip, $filename, $usertempdir, $archiver, $settings_id, $zipfile);

collection_download_clean_temp_files($deletion_array);

$downloadable_zipfile = str_replace(basename($zipfile), $filename, $zipfile);

rename($zipfile, $downloadable_zipfile);

if($offline_job_delete_completed)
    {
    job_queue_delete($jobref);
    }
else
    {
    job_queue_update($jobref, $job_data, STATUS_COMPLETE);
    }

$download_url = convert_path_to_url($downloadable_zipfile);
$download_url = str_replace(
    array('\\', 'include/../'),
    array('/', ''),
    $download_url);

message_add($job["user"], $job_success_text, $download_url);