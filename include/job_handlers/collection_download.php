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

global $lang, $baseurl, $baseurl_short, $offline_job_delete_completed, $exiftool_write_option, $usage, $usagecomment,
$text, $collection_download_settings, $pextension, $scramble_key, $archiver_fullpath,$archiver_listfile_argument, $ffmpeg_preview_extension,
$collection_download_settings,$restricted_full_download, $ffmpeg_supported_extensions, $ffmpeg_audio_extensions;

$dl_data = [
    "collection"            => $job_data['collection'],
    "collectiondata"        => $job_data['collectiondata'],
    "collection_resources"  => $job_data['result'],
    "size"                  => $job_data['size'],
    "exiftool_write_option" => $job_data['exiftool_write_option'],
    "useoriginal"           => $job_data['useoriginal'],
    "id"                    => $job_data['id'],
    "includetext"           => $job_data['includetext'],
    "count_data_only_types" => $job_data['count_data_only_types'],
    "usage"                 => $job_data['usage'],
    "usagecomment"          => $job_data['usagecomment'],
    "settings_id"           => $job_data['settings_id'],
    "include_csv_file"      => $job_data['include_csv_file'],
];

// Used by format chooser
if (isset($job_data["ext"])) {
    global $job_ext;
    $job_ext = $job_data["ext"];
}
$GLOBALS["debug_log"] = true;
// Set up the user who requested the collection download as it needs to be processed in its name
$user_select_sql = new PreparedStatementQuery();
$user_select_sql->sql = "u.ref = ?";
$user_select_sql->parameters = ["i",$job['user']];

$user_data = validate_user($user_select_sql, true);
if (count($user_data) > 0) {
    setup_user($user_data[0]);
} else {
    job_queue_update($jobref, $job_data, STATUS_ERROR);
    return;
}

$zipinfo = process_collection_download($dl_data);
collection_log($job_data['collection'], LOG_CODE_COLLECTION_COLLECTION_DOWNLOADED, "", $job_data['size']);

if (!is_valid_rs_path($zipinfo["path"])) {
    job_queue_update($jobref, $job_data, STATUS_ERROR);
    message_add($job["user"], $GLOBALS["lang"]["nothing_to_download"]);
    return;
}

if ($offline_job_delete_completed) {
    job_queue_delete($jobref);
} else {
    job_queue_update($jobref, $job_data, STATUS_COMPLETE);
}

$download_url   = $baseurl_short . "pages/download.php?userfile=" . $user_data[0]["ref"] . "_" . $zipinfo["id"] . ".zip&filename=" . pathinfo($zipinfo["filename"], PATHINFO_FILENAME);
message_add($job["user"], $job_success_text, $download_url);

$delete_job_data = array();
$delete_job_data["file"] = $zipinfo["path"];
$delete_date = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * DOWNLOAD_FILE_LIFETIME)); // Delete file after set number of days
$job_code = md5($zipinfo["path"]);
job_queue_add("delete_file", $delete_job_data, "", $delete_date, "", "", $job_code);
