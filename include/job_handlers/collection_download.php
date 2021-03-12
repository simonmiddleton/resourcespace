<?php
/*
Job handler to process collection downloads

Requires the following job data:
$job_data['collection'] - 
$job_data['collectiondata'] - 
$job_data['result'] - Search result of !collectionX
$job_data['size'] - Requested size
$job_data['exiftool_write_option']
$job_data['useoriginal'] - 
$job_data['id'] - 
$job_data['includetext'] - 
$job_data['count_data_only_types'] - 
$job_data['usage'] - 
$job_data['usagecomment'] - 
$job_data['settings_id'] - 
$job_data['include_csv_file'] - User input opting to include the CSV file in the downloaded archive
*/
include_once __DIR__ . '/../pdf_functions.php';
include_once __DIR__ . '/../csv_export_functions.php';

global $lang, $baseurl, $offline_job_delete_completed, $exiftool_write_option, $usage, $usagecomment,
$text, $collection_download_settings, $pextension, $scramble_key, $archiver_fullpath,$archiver_listfile_argument,
$collection_download_settings,$restricted_full_download, $download_file_lifetime;

foreach($job_data as $arg => $value)
    {
    $$arg = $value;
    }

if(isset($job_data["ext"]))
    {
    global $job_ext;
    $job_ext = $job_data["ext"];
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

if(!isset($collectiondata) && isset($collection))
    {
    $collectiondata = get_collection($collection);
    }

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
$usertempdir=get_temp_dir(false,"rs_" . $user_data[0]["ref"] . "_" . $id);
$randstring=md5(rand() . microtime());
$zippath = get_temp_dir(false,'user_downloads');
$zipfile = $zippath . "/" . $user_data[0]["ref"] . "_" . md5($user_data[0]["username"] . $randstring . $scramble_key) . ".zip";
$zip = new ZipArchive();
$zip->open($zipfile, ZIPARCHIVE::CREATE);

$path = "";
$deletion_array = array();
$filenames = array(); # set up an array to store the filenames as they are found (to analyze dupes)
$used_resources = array();
$subbed_original_resources = array();
for($n = 0; $n < count($collection_resources); $n++)
    {
    $ref = $collection_resources[$n]["ref"];
    $resource_data = get_resource_data($ref);
    $collection_resources[$n]["resource_type"] = $resource_data['resource_type']; // Update as used in other functions
    resource_type_config_override($resource_data['resource_type']);

    $copy = false; 
    $ref = $collection_resources[$n]['ref'];
    $access = get_resource_access($resource_data);
    $use_watermark = check_use_watermark();

    // Do not download resources without proper access level
    if(!($access == 0 || $access == 1))
        {
        continue;
        }

    # Get all possible sizes for this resource. If largest available has been requested then include internal or user could end up with no file depite being able to see the preview
    $sizes=get_all_image_sizes($size=="largest",$access>=1);

    # Check availability of original file 
    $p=get_resource_path($ref,true,"",false,$resource_data["file_extension"]);
    if (file_exists($p) && (($access==0) || ($access==1 && $restricted_full_download)) && resource_download_allowed($ref,'',$resource_data['resource_type']))
        {
        $available_sizes['original'][]=$ref;
        }

    # Check for the availability of each size and load it to the available_sizes array
    foreach ($sizes as $sizeinfo)
        {
        $size_id=$sizeinfo['id'];
        $size_extension = get_extension($resource_data, $size_id);
        $p=get_resource_path($ref,true,$size_id,false,$size_extension);

        if (resource_download_allowed($ref,$size_id,$resource_data['resource_type']))
            {
            if (hook('size_is_available', '', array($resource_data, $p, $size_id)) || file_exists($p))
                $available_sizes[$size_id][]=$ref;
            }
        }      

    // Check which size to use
    if($size=="largest")
        {
        foreach($available_sizes as $available_size => $resources)
            {
            if(in_array($ref,$resources))
                {   
                $usesize = $available_size;
                if($available_size == 'original')
                    {
                    $usesize = "";
                    // Has access to the original so no need to check previews
                    break;
                    }
                }
            }
        }
    else
        {
        $usesize = ($size == 'original') ? "" : $size;
        }

    $pextension = get_extension($resource_data, $usesize);
    $p = get_resource_path($ref, true, $usesize, false, $pextension, -1, 1, $use_watermark);

    $subbed_original = false;
    $target_exists = file_exists($p);
    $replaced_file = false;

    $new_file = hook('replacedownloadfile', '', array($resource_data, $usesize, $pextension, $target_exists));
    if ($new_file != '' && $p != $new_file)
        {
        $p = $new_file;
        $deletion_array[] = $p;
        $replaced_file = true;
        $target_exists = file_exists($p);
        }
    else if(!$target_exists && $useoriginal == 'yes' && resource_download_allowed($ref,'',$resource_data['resource_type']))
        {
        // this size doesn't exist, so we'll try using the original instead
        $p = get_resource_path($ref, true, '', false, $resource_data['file_extension'], -1, 1, $use_watermark);
        $pextension = $resource_data['file_extension'];
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
            && resource_download_allowed($ref, $usesize, $resource_data['resource_type'])
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
    $filename=get_download_filename($ref,$usesize,0,$pextension);

    if($GLOBALS["original_filenames_when_downloading"])
        {
        collection_download_use_original_filenames_when_downloading($filename, $ref, false, $filenames,$id);
        }
    else
        {
        $newfile = set_unique_filename($filename,$filenames);    
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
        array_column($collection_resources,"ref"),
        $id,
        $collection,
        false,
        $GLOBALS['use_zip_extension'],
        $zip,
        $path,
        $deletion_array);
    }

collection_download_process_command_to_file($GLOBALS['use_zip_extension'], false, $id, $collection, $size, $path);

$archiver = ($archiver_fullpath!=false) && (isset($archiver_listfile_argument)) && (isset($collection_download_settings) ? is_array($collection_download_settings) : false);

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

if($offline_job_delete_completed)
    {
    job_queue_delete($jobref);
    }
else
    {
    job_queue_update($jobref, $job_data, STATUS_COMPLETE);
    }

$download_url   = $baseurl . "/pages/download.php?userfile=" . $user_data[0]["ref"] . "_" . $randstring . $suffix . "&filename=" . pathinfo($filename,PATHINFO_FILENAME);
message_add($job["user"], $job_success_text, $download_url);

$delete_job_data=array();
$delete_job_data["file"]=$zipfile;
$delete_date = date('Y-m-d H:i:s',time()+(60*60*24*(int)$download_file_lifetime)); // Delete file after set number of days
$job_code=md5($zipfile);
job_queue_add("delete_file",$delete_job_data,"",$delete_date,"","",$job_code);