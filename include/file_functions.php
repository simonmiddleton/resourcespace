<?php
/**
 * Ensures the filename cannot leave the directory set.
 *
 * @param string $name
 * @return string
 */
function safe_file_name($name)
    {
    // Returns a file name stipped of all non alphanumeric values
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

    $newname = substr($newname, 0, 30);

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
* 
* @return string
*/
function get_checksum($path)
    {
    global $file_checksums_50k;
    if (!is_readable($path))
        {
        return false;    
        }

    # Generate the ID
    if ($file_checksums_50k)
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
