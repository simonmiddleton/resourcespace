<?php
/**
 * Ensures the filename cannot leave the directory set.
 *
 * @param string $name
 * @return string
 */
function safe_file_name($name)
    {
    // Returns a file name stripped of all non alphanumeric values
    // Spaces are replaced with underscores
    $alphanum = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
    $name = str_replace(' ', '_', $name);
    $newname = '';

    for($n = 0; $n < strlen($name); $n++)
        {
        $c = substr($name, $n, 1);
        if(strpos($alphanum, $c) !== false)
            {
            $newname .= $c;
            }
        }

    // Set to 250 to allow for total length to be below 255 limit including filename and extension
    $newname = mb_substr($newname, 0, 250); 

    return $newname;
    }


/**
* Generate a UID for filnames that can be different from user to user (e.g. contact sheets)
* 
* @param integer $user_id
* 
* @return string
*/
function generateUserFilenameUID($user_id)
    {
    if(!is_numeric($user_id) || 0 >= $user_id)
        {
        trigger_error('Bad parameter for generateUserFilenameUID()!');
        }

    global $rs_session, $scramble_key;

    $filename_uid = '';

    if(isset($rs_session))
        {
        $filename_uid .= $rs_session;
        }

    $filename_uid .= $user_id;

    return substr(hash('sha256', $filename_uid . $scramble_key), 0, 15);
    }


/**
* Checks if a path is part of a whitelisted list of paths. This applies to both folders and files.
* 
* Note: the function is not supposed to check/ validate the syntax of the path (ie. UNIX/ Windows)
* 
* @param  string  $path               Path which is going to be checked against whitelisted paths
* @param  array   $whitelisted_paths  List of whitelisted paths
* 
* @return boolean
*/
function isPathWhitelisted($path, array $whitelisted_paths)
    {
    foreach($whitelisted_paths as $whitelisted_path)
        {
        if(substr_compare($whitelisted_path, $path, 0, strlen($path)) === 0)
            {
            return true;
            }
        }

    return false;
    }


/**
* Return a checksum for the given file path.
* 
* @param  string  $path     Path to file
* @param  bool  $forcefull  Force use of whole file and ignore $file_checksums_50k setting
* 
* @return string
*/
function get_checksum($path, $forcefull = false)
    {
    debug("get_checksum( \$path = {$path} );");
    global $file_checksums_50k;
    if (!is_readable($path))
        {
        return false;    
        }

    # Generate the ID
    if ($file_checksums_50k && !$forcefull)
        {
        # Fetch the string used to generate the unique ID
        $use=filesize_unlimited($path) . "_" . file_get_contents($path,null,null,0,50000);
        $checksum=md5($use);
        }
    else
        {
        $checksum=md5_file($path);
        }
    return $checksum;
    }


/**
 * Download remote file to the temp filestore location.
 * 
 * @param string $url Source URL
 * 
 * @return string|bool Returns the new temp filestore location or false otherwise.
 */
function temp_local_download_remote_file(string $url)
    {
    $userref = $GLOBALS['userref'] ?? 0;
    if($userref === 0)
        {
        return false;
        }

    $url = trim($url);
    $url_original = $url;
    // Remove query string from URL
    $url = explode('?', $url);
    $url = reset($url);
    
    $path_parts = pathinfo(basename($url));
    $filename = safe_file_name($path_parts['filename'] ?? '');
    $extension = $path_parts['extension'] ?? '';
    $filename .= ($extension !== '' ? ".{$extension}" : '');

    if(strpos($filename,".") == false && filter_var($url_original, FILTER_VALIDATE_URL))
        {
        // $filename not valid, try and get from HTTP header
        $headers = get_headers($url_original,true);
        foreach($headers as $header=>$headervalue)
            {
            if(strtolower($header) == "content-disposition")
                {
                // Check for double quotes first (e.g. attachment; filename="O'Malley's Bar.pdf")
                if(preg_match('/.*filename=[\"]([^\"]+)/', $headervalue, $matches))
                    {
                    $filename = $matches[1];
                    }
                // Check for single quotes (e.g. attachment; filename='Space Travel.jpg')
                elseif(preg_match('/.*filename=[\']([^\']+)/', $headervalue, $matches))
                    {
                    $filename = $matches[1];
                    }
                // Get file name up to first space
                else if(preg_match("/.*filename=([^ ]+)/", $headervalue, $matches))
                    {
                    $filename = $matches[1];
                    }
                }
            }
        }
    // Get temp location
    $tmp_uniq_path_id = sprintf('remote_files/%s_%s', $userref, generateUserFilenameUID($userref));
    $tmp_file_path = sprintf('%s/%s',
        get_temp_dir(false, $tmp_uniq_path_id),
        $filename);

    if($tmp_file_path == $url)
        {
        // Already downloaded earlier by API call 
        return $tmp_file_path;
        }

    // Download the file
    $GLOBALS['use_error_exception'] = true;
    try
        {
        if(copy($url_original, $tmp_file_path))
            {
            return $tmp_file_path;
            }
        }
    catch(Throwable $t)
        {
        debug(sprintf(
            'Failed to download remote file from "%s" to temp location "%s". Reason: %s',
            $url_original,
            $tmp_file_path,
            $t->getMessage()
        ));
        }
    unset($GLOBALS['use_error_exception']);

    return false;
    }

/**
 * Basic check of uploaded file against list of allowed extensions
 *
 * @param  array    $uploadedfile - an element from the $_FILES PHP reserved variable 
 * @param  array    $validextensions   Array of valid extension strings
 * @return bool
 */
function check_valid_file_extension($uploadedfile,array $validextensions)
    {
    $pathinfo   = pathinfo($uploadedfile['name']);
    $extension  = $pathinfo['extension'] ?? "";
    if(in_array(strtolower($extension),array_map("strtolower",$validextensions)))
        {
        return true;
        }
    return false;
    }




