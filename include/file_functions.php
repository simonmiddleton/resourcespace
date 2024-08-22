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
* @return string|false Return the checksum value, false otherwise.
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
        $use=filesize_unlimited($path) . "_" . file_get_contents($path,false,null,0,50000);
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
 * @param string $key Optional key to use - to prevent conflicts when simultaneous calls use same file name 
 * 
 * @return string|bool Returns the new temp filestore location or false otherwise.
 */
function temp_local_download_remote_file(string $url, string $key = "")
    {
    $userref = $GLOBALS['userref'] ?? 0;
    if($userref === 0)
        {
        return false;
        }

    if ($key != "" && preg_match('/\W+/', $key) !== 0)
        {
        // Block potential path traversal - allow only word characters.
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

    if(strpos($filename,".") === false && filter_var($url_original, FILTER_VALIDATE_URL))
        {
        // $filename not valid, try and get from HTTP header
        $urlinfo = parse_url($url);
        if (!isset($urlinfo["scheme"]) || !in_array($urlinfo["scheme"],["http","https"]))
            {
            return false;
            }

        $headers = get_headers($url_original,true);
        foreach($headers as $header=>$headervalue)
            {
            if (
                strtolower($header) == "content-disposition"
                // Check for double quotes first (e.g. attachment; filename="O'Malley's Bar.pdf")
                // OR Check for single quotes (e.g. attachment; filename='Space Travel.jpg')
                // OR Get file name up to first space
                && 
                ( 
                    preg_match('/.*filename=[\"]([^\"]+)/', $headervalue, $matches)
                    || preg_match('/.*filename=[\']([^\']+)/', $headervalue, $matches)
                    || preg_match("/.*filename=([^ ]+)/", $headervalue, $matches) 
                )
                ) {
                    $filename = $matches[1];
                }
            }
        
        $extension = pathinfo(basename($filename), PATHINFO_EXTENSION);
        $filename = safe_file_name(pathinfo(basename($filename), PATHINFO_FILENAME)) . ".{$extension}";
        }

    if (is_banned_extension($extension))
        {
        debug('[temp_local_download_remote_file] WARN: Banned extension for ' . $filename);
        return false;
        }

    // Get temp location
    $tmp_uniq_path_id = $userref . "_" . $key . generateUserFilenameUID($userref);
    $tmp_dir = get_temp_dir(false) . "/remote_files/" . $tmp_uniq_path_id;
    if(!is_dir($tmp_dir))
        {
        mkdir($tmp_dir,0777,true);
        }
    $tmp_file_path = $tmp_dir. "/" . $filename;
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
    if(in_array(strtolower($extension),array_map("strtolower",$validextensions)) && !is_banned_extension($extension))
        {
        return true;
        }
    return false;
    }

/**
 * Is the given extension in the list of blocked extensions?
 * Also ensures extension is no longer than 10 characters due to resource.file_extension database column limit
 *
 * @param  string    $extension - file extension to check
 */
function is_banned_extension(string $extension): bool
{
    return !(
        preg_match('/^[a-zA-Z0-9_-]{1,10}$/', $extension) === 1
        && !in_array(mb_strtolower($extension), array_map('mb_strtolower', $GLOBALS['banned_extensions']))
    );
}

/**
 * Remove empty folder from path to file. Helpful to remove a temp directory once the file it was created to hold no longer exists.
 * This function should be called only once the directory to be removed is empty.
 *
 * @param   string   $path_to_file   Full path to file in filestore.
 * 
 * @return void
 */
function remove_empty_temp_directory(string $path_to_file = "")
    {
    if ($path_to_file != "" && !file_exists($path_to_file))
        {
        $tmp_path_parts = pathinfo($path_to_file);
        $path_to_folder = str_replace(DIRECTORY_SEPARATOR . $tmp_path_parts['basename'], '', $path_to_file);
        rmdir($path_to_folder);
        }
    }


/**
 * Confirm upload path is one of valid paths.
 *
 * @param   string   $file_path            Upload path.
 * @param   array    $valid_upload_paths   Array of valid upload paths to test against.
 * 
 * @return  bool     true when path is valid else false
 */
function is_valid_upload_path(string $file_path, array $valid_upload_paths) : bool
    {
    $GLOBALS["use_error_exception"] = true;
    try
        {
        $file_path = realpath($file_path);
        }
    catch (Exception $e)
        {
        debug("Invalid file path specified" . $e->getMessage());
        return false;
        }
    unset($GLOBALS["use_error_exception"]);

    foreach($valid_upload_paths as $valid_upload_path)
        {
        if(is_dir($valid_upload_path))
            {
            $checkpath = realpath($valid_upload_path);
            if(strpos($file_path,$checkpath) === 0)
                {
                return true;
                }
            }
        }

    return false;
    }

/**
 * Validate the files on disk that are associated with the given resources
 *
 * @param array $resources      Array of resource IDs or
 *                              array of resource data e.g, from search results
 * @param array $criteria       Array with an array of callables for each resource with the
 *                              required return values in order to pass the check e.g.
 *                              'file_exists" =>true for a file presence only check
 *
 * @return array $results       An array with resource ID as the index and the results of the check as the value (boolean)
 *                              e.g. ["1234" => true, "1235" => false]
 */
function validate_resource_files(array $resources,array $criteria = []): array
{
    $checkresources = isset($resources[0]["ref"]) ? $resources : get_resource_data_batch($resources);
    $results = [];
    foreach($checkresources as $resource) {
        if (!is_int_loose($resource["ref"])) {
            $results[$resource["ref"]] = false;
            continue;
        }
        $filepath = get_resource_path($resource["ref"],true,'',false,$resource["file_extension"] ?? "jpg");
        $results[$resource["ref"]] = false;
        foreach ($criteria as $criterion=>$expected) {
            if(!is_callable($criterion)) {
                $results[$resource["ref"]] = false;
                // Not a valid check
                continue 2;
            }
            $cscheck = $expected === "%RESOURCE%file_checksum";
            if(substr($expected,0,10) == "%RESOURCE%") {
                // $expected is a resource table column
                $expected = $resource[substr($expected,10)];
            }
            $testresult = call_user_func($criterion,$filepath);
            if ($cscheck && ($expected === null || $expected === "")) {
                // No checksum is currently recorded. Update it now that it's been calculated
                $results[$resource["ref"]] = true;
                debug("Skipping checksum check for resource " . $resource["ref"] . " - no existing checksum");
                ps_query("UPDATE resource SET file_checksum = ? WHERE ref = ?", ['s', $testresult, 'i', $resource["ref"]]);
                continue;
            }

            $results[$resource["ref"]] = $testresult === $expected;
            if ($results[$resource["ref"]] === false) {
                debug($resource["ref"] . " failed integrity check. Expected: " . $criterion . "=". $expected . ", got : " . $testresult);
                // No need to continue with other $criteria as check has failed
                continue 2;
            }
        }
    }
    return $results;
}

/**
 * Check if a given file path is from a valid RS accessible location
 *
 * @param   string   $path
 * @param   array    $override_paths   Override checking of the default RS paths to check a specific location only.
 */
function is_valid_rs_path(string $path, array $override_paths = []): bool
{
    debug_function_call(__FUNCTION__, func_get_args());
    if ($GLOBALS["config_windows"]) {
        $path = str_replace("\\", "/", $path);
    }

    $sourcerealpath = realpath($path);
    $source_path_not_real = !$sourcerealpath || !file_exists($sourcerealpath);
    debug('source_path_not_real = ' . json_encode($source_path_not_real));

    $checkname  = $path;
    $pathinfo = pathinfo($path);
    if (($pathinfo["extension"] ?? "") === "icc") {
        // ResourceSpace generated .icc files have a double extension, need to strip extension again before checking
        $checkname = $pathinfo["filename"] ?? "";
    }
    debug("checkname = {$checkname}");

    if ($source_path_not_real) {
        if (!(preg_match('/^(?!\.)(?!.*\.$)(?!.*\.\.)[a-zA-Z0-9_\-[:space:]\/:.]+$/', ($pathinfo['dirname'] ?? "")) && is_safe_basename($checkname))
        ) {
            debug('Invalid non-existing path');
            return false;
        }
    }

    // Check if path contains symlinks, if so don't use the value returned by realpath() as it is unlikely to match the expected paths
    $symlink = false;
    $path_parts = array_filter(explode("/", $path));
    $checkpath = "";
    foreach($path_parts as $path_part) {
        $checkpath .= "/" . $path_part;
        if(is_link($checkpath)) {
            debug("{$checkpath} is a symlink");
            $symlink = true;
            break;
        }
    }
    $path_to_validate = ($source_path_not_real || $symlink) ? $path : $sourcerealpath;
    debug("path_to_validate = {$path_to_validate}");

    if (count($override_paths) > 0) {
        $default_paths = $override_paths;
    } else {
        $default_paths = [
            dirname(__DIR__) . '/gfx',
            $GLOBALS['storagedir'],
            $GLOBALS['syncdir'],
            $GLOBALS['fstemplate_alt_storagedir'],
        ];
        if (isset($GLOBALS['tempdir'])) {
            $default_paths[] = $GLOBALS['tempdir'];
        }
    }
    $allowed_paths = array_filter(array_map('trim', array_unique($default_paths)));
    debug('allowed_paths = ' . implode(', ', $allowed_paths));

    foreach ($allowed_paths as $allowed_path) {
        debug("Iter allowed path - {$allowed_path}");
        $validpath = ($source_path_not_real || $symlink) ? $allowed_path : realpath($allowed_path);
        if ($GLOBALS["config_windows"]) {
            $allowed_path = str_replace("\\","/", $allowed_path);
            $validpath = str_replace("\\","/", $validpath);
            $path_to_validate = str_replace("\\","/", $path_to_validate);
        }
        debug("validpath = {$validpath}");
        debug("path_to_validate = {$path_to_validate}");
        if ($validpath !== false && mb_strpos($path_to_validate, $validpath) === 0) {
            debug('Path allowed');
            return true;
        }
    }

    debug('Default as an invalid path');
    return false;
}

/**
 * Validation helper function to determine if a path base name is unsafe (e.g OS command injection).
 * Very strict, limited to specific characters only. Should only be used for filenames originating in ResourceSpace.
 */
function is_safe_basename(string $val): bool
{
    $file_name = pathinfo($val, PATHINFO_FILENAME);
    return
        safe_file_name($file_name) === str_replace(' ', '_', $file_name)
        && !is_banned_extension(pathinfo($val, PATHINFO_EXTENSION));
}
