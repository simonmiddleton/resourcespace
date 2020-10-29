<?php
/*we will use output buffering to prevent any included files 
from outputting stray characters that will mess up the binary download
we will clear the buffer and start over right before we download the file*/
ob_start(); $nocache=true;
include_once dirname(__FILE__) . '/../include/db.php';
include_once dirname(__FILE__) . '/../include/resource_functions.php';
ob_end_clean(); 

$k="";

if($download_no_session_cache_limiter)
    {
    session_cache_limiter(false);
    }

$direct = (0 < strlen(getvalescaped('direct', '')) ? true : false);

// if direct downloading without authentication is enabled, skip the authentication step entirely
if(!($direct_download_noauth && $direct))
    {
    // External access support (authenticate only if no key provided, or if invalid access key provided)
    $k = getvalescaped('k', '');

    if(('' == $k || !check_access_key(getvalescaped('ref', '', true), $k)) && !(getval("slideshow",0,true) > 0))
        {
        include dirname(__FILE__) . '/../include/authenticate.php';
        }
    }

    
// Set a flag for logged in users if $external_share_view_as_internal is set and logged on user is accessing an external share
$internal_share_access = internal_share_access();

$ref            = getvalescaped('ref', '', true);
$size           = getvalescaped('size', '');
$alternative    = getvalescaped('alternative', -1, true);
$page           = getvalescaped('page', 1);
$usage          = getvalescaped('usage', '-1');
$usagecomment   = getvalescaped('usagecomment', '');
$ext            = getvalescaped('ext', '');
$snapshot_frame = getvalescaped('snapshot_frame', 0, true);
$modal          = (getval("modal","")=="true");

if(!preg_match('/^[a-zA-Z0-9]+$/', $ext))
    {
    $ext='jpg';
    }

// Is this a user specific download?
$userfiledownload = getvalescaped('userfile', '');
if('' != $userfiledownload)
    {
    $noattach       = '';
    $exiftool_write = false;
    $filedetails    = explode('_', $userfiledownload);
    $ref            = (int)$filedetails[0];
    $downloadkey    = strip_extension($filedetails[1]);
    $ext            = safe_file_name(substr($filedetails[1], strlen($downloadkey) + 1));
    $path           = get_temp_dir(false, 'user_downloads') . '/' . $ref . '_' . md5($username . $downloadkey . $scramble_key) . '.' . $ext;
    $rqstname       = getval("filename","");
    if($rqstname!="")
        {
        $filename   = safe_file_name($rqstname) . "." . $ext;
        }
    hook('modifydownloadpath');
    }
elseif(getval("slideshow",0,true) != 0)
    {
    $noattach       = true;
    $path           = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $homeanim_folder . DIRECTORY_SEPARATOR . getval("slideshow",0,true) . ".jpg";
    }
elseif(getval("tempfile","") != "")
    {
    $noattach       = true;
    $exiftool_write = false;
    $filedetails    = explode('_', getval("tempfile",""));
    $code           = safe_file_name($filedetails[0]);
    $ref            = (int)$filedetails[1];
    $downloadkey    = strip_extension($filedetails[2]);
    $ext            = safe_file_name(substr($filedetails[2], strlen($downloadkey) + 1));
    $path           = get_temp_dir(false,"") . '/' . $code . '_' . $ref . "_" . md5($username . $downloadkey . $scramble_key) . '.' . $ext;
    }
else
    {
    $resource_data = get_resource_data($ref);

    resource_type_config_override($resource_data['resource_type']);

    if($direct_download_noauth && $direct)
        {
        // if this is a direct download and direct downloads w/o authentication are enabled, allow regardless of permissions
        $allowed = true;
        }
    else
        {
        // Permissions check
        $allowed = resource_download_allowed($ref, $size, $resource_data['resource_type'], $alternative);
        debug("PAGES/DOWNLOAD.PHP: \$allowed = " . ($allowed == true ? 'TRUE' : 'FALSE'));
        }

    if(!$allowed || $ref <= 0)
        {
        $error = $lang['error-permissiondenied'];
        if(getval("ajax","") != "")
            {
            error_alert($error, true,200);
            }
        else
            {
            include "../include/header.php";
            $onload_message = array("title" => $lang["error"],"text" => $error);
            include "../include/footer.php";
            }
        exit();
        }

    // additional access check, as the resource download may be allowed, but access restriction should force watermark.  
    $access        = get_resource_access($ref);
    $use_watermark = check_use_watermark(getval("dl_key",""),$ref);

    // If no extension was provided, we fallback to JPG.
    if('' == $ext)
        {
        $ext = 'jpg';
        }

    $noattach = getval('noattach','');

    // Where we are getting mp3 preview for videojs, clear size as we want to get the auto generated mp3 file rather than a custom size.
    if ($size == 'videojs' && $ext == 'mp3')
    {
        $size="";
    }
    
    $path     = get_resource_path($ref, true, $size, false, $ext, -1, $page, $use_watermark && $alternative == -1, '', $alternative);

    // Snapshots taken for videos? Make sure we convert to the real snapshot file
    if(1 < $ffmpeg_snapshot_frames && 0 < $snapshot_frame)
        {
        $path = str_replace('snapshot', "snapshot_{$snapshot_frame}", $path);
        }

    hook('modifydownloadpath');
        
    if(!file_exists($path) && '' != $noattach)
        {
        # Return icon for file (for previews)
        $info = get_resource_data($ref);
        $path = '../gfx/' . get_nopreview_icon($info['resource_type'], $ext, 'thm');
        }

    // Process metadata
    // Note: only for downloads (not previews)
    if('' == $noattach && -1 == $alternative)
        {
        // Strip existing metadata only if we do not plan on writing metadata, otherwise this will be done twice
        if($exiftool_remove_existing && !$exiftool_write)
            {
            $temp_file_stripped_metadata = createTempFile($path, '', '');

            if($temp_file_stripped_metadata !== false && stripMetadata($temp_file_stripped_metadata))
                {
                $path = $temp_file_stripped_metadata;
                }
            }

        // writing RS metadata to files: exiftool
        if($exiftool_write)
            {
            $tmpfile = write_metadata($path, $ref);

            if(false !== $tmpfile && file_exists($tmpfile))
                {
                $path = $tmpfile;
                }
            }
        }
    }

debug("PAGES/DOWNLOAD.PHP: Preparing to download/ stream file '{$path}'");

// File does not exist
if(!file_exists($path))
    {
    debug("PAGES/DOWNLOAD.PHP: File does not exist!");

    header('HTTP/1.0 404 Not Found');
    exit();
    }

hook('modifydownloadfile'); 

$file_size   = filesize_unlimited($path);
$file_handle = fopen($path, 'rb');

debug("PAGES/DOWNLOAD.PHP: \$file_size = {$file_size}");

// File could not be opened
if(!$file_handle)
    {
    debug("PAGES/DOWNLOAD.PHP: File could not be opened!");

    header('HTTP/1.0 500 Internal Server Error');
    exit();
    }

// Log this activity (download only, not preview)
if('' == $noattach)
    {
    daily_stat('Resource download', $ref);

    resource_log($ref, LOG_CODE_DOWNLOADED, 0, $usagecomment, '', '', $usage, ($alternative != -1 ? $alternative : $size));

    hook('moredlactions');

    // update hit count if tracking downloads only
    if($resource_hit_count_on_downloads)
        {
        // greatest() is used so the value is taken from the hit_count column in the event that new_hit_count
        // is zero to support installations that did not previously have a new_hit_count column (i.e. upgrade compatability).
        sql_query("UPDATE resource SET new_hit_count = greatest(hit_count, new_hit_count) + 1 WHERE ref = '{$ref}'");
        }
    
    // We compute a file name for the download.
    if(!isset($filename) || $filename == "")
        {
        $filename = get_download_filename($ref, $size, $alternative, $ext);
        }
    }

// Set appropriate headers for attachment or streamed file
if(!$direct && isset($filename))
    {
    header("Content-Disposition: attachment; filename=\"{$filename}\"");

    debug("PAGES/DOWNLOAD.PHP: Set header for attachment file");
    }
else
    {
    header('Content-Disposition: inline;');
    header('Content-Transfer-Encoding: binary');

    debug("PAGES/DOWNLOAD.PHP: Set header for streamed file");
    }

// We declare the downloaded content mime type
$mime = get_mime_type($path);
header("Content-Type: {$mime}");

debug("PAGES/DOWNLOAD.PHP: Set MIME type to '{$mime}'");

// Check if http_range is sent by browser (or download manager)
if(isset($_SERVER['HTTP_RANGE']) && strpos($_SERVER['HTTP_RANGE'],"=")!==false) # Check it's set and also contains the expected = delimiter
    {
    debug("PAGES/DOWNLOAD.PHP: HTTP_RANGE is set to '{$_SERVER['HTTP_RANGE']}'");

    list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

    if('bytes' == $size_unit)
        {
        /* Multiple ranges could be specified at the same time, but for simplicity only serve the first range
        http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt

        IMPORTANT: If multiple ranges are not specified, PHP can return an error for "Undefined offset: 1",
        so we pad the array with an empty string */
        list($range, $extra_ranges) = array_pad(explode(',', $range_orig, 2), 2, '');
        }
    else
        {
        debug("PAGES/DOWNLOAD.PHP: Requested range was not valid");

        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: */{$file_size}");
        exit();
        }
    }
else
    {
    debug("PAGES/DOWNLOAD.PHP: HTTP_RANGE is not set!");

    $range = '';
    }

debug("PAGES/DOWNLOAD.PHP: \$range = {$range}");

// Figure out download piece from range (if set)
list($seek_start, $seek_end) = array_pad(explode('-', $range, 2), 2, '');

// Set start and end based on range (if set), else set defaults
// also check for invalid ranges.
$seek_end   = (empty($seek_end)) ? ($file_size - 1) : min(abs(intval($seek_end)), ($file_size - 1));
$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);

debug("PAGES/DOWNLOAD.PHP: \$seek_start = {$seek_start}");
debug("PAGES/DOWNLOAD.PHP: \$seek_end = {$seek_end}");

// Only send partial content header if downloading a piece of the file (IE workaround)
if(0 < $seek_start || $seek_end < ($file_size - 1))
    {
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes {$seek_start}-{$seek_end}/{$file_size}");
    header('Content-Length: ' . ($seek_end - $seek_start + 1));

    debug("PAGES/DOWNLOAD.PHP: Content-Range: bytes {$seek_start}-{$seek_end}/{$file_size}");
    debug('PAGES/DOWNLOAD.PHP: Content-Length: ' . ($seek_end - $seek_start +1));
    }
else
    {
    header("Content-Length: {$file_size}");

    debug("PAGES/DOWNLOAD.PHP: Content-Length: {$file_size}");
    }

header('Accept-Ranges: bytes');

set_time_limit(0);

if(!hook('replacefileoutput'))
    {
    $sent = (0 == fseek($file_handle, $seek_start) ? $seek_start : 0);

    while($sent < $file_size)
        {
        echo fread($file_handle, $download_chunk_size);

        ob_flush();
        flush();

        $sent += $download_chunk_size;

        if(0 != connection_status()) 
            {
            break;
            }
        }

    fclose($file_handle);
    }

// Deleting Exiftool temp File:
// Note: Only for downloads (not previews)
if('' == $noattach && -1 == $alternative && $exiftool_write && file_exists($tmpfile))
    {
    delete_exif_tmpfile($tmpfile);
    }

hook('beforedownloadresourceexit');

exit();
