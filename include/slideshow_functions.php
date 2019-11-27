<?php
/**
* Create/ Update a slideshow image record. Use NULL for $ref to create new records.
* 
* @param integer $ref                       ID of the slideshow image. Use NULL to create a new record
* @param integer $resource_ref              ID of the resource this slideshow is related to. Use NULL if no link is required
* @param integer $homepage_show             Set to 1 if slideshow image should appear on the home page
* @param integer $featured_collections_show Set to 1 if slideshow image should appear on the featured collections page
* @param integer $login_show                Set to 1 if slideshow image should appear on the login page
*
* @return boolean|integer  Returns ID of the slideshow image(new/ updated), FALSE otherwise
*/
function set_slideshow($ref, $resource_ref = NULL, $homepage_show = 1, $featured_collections_show = 1, $login_show = 0)
    {
    if(
        (!is_null($ref) && !is_numeric($ref))
        || (!(is_null($resource_ref) || trim($resource_ref) == '') && !is_numeric($resource_ref))
        || !is_numeric($homepage_show)
        || !is_numeric($featured_collections_show)
        || !is_numeric($login_show))
        {
        return false;
        }

    $ref_escaped = ((int) $ref > 0 ? "'" . escape_check($ref) . "'" : 'NULL');
    $resource_ref = ((int) $resource_ref > 0 ? "'" . escape_check($resource_ref) . "'" : 'NULL');
    $homepage_show = escape_check($homepage_show);
    $featured_collections_show = escape_check($featured_collections_show);
    $login_show = escape_check($login_show);

    $query = "
        INSERT INTO slideshow (ref, resource_ref, homepage_show, featured_collections_show, login_show)
             VALUES ({$ref_escaped}, {$resource_ref}, '{$homepage_show}', '{$featured_collections_show}', '{$login_show}')
                 ON DUPLICATE KEY
             UPDATE resource_ref = {$resource_ref},
                    homepage_show = '{$homepage_show}',
                    featured_collections_show = '{$featured_collections_show}',
                    login_show = '{$login_show}'";

    sql_query($query);

    $new_ref = sql_insert_id();
    if(is_null($ref) && $new_ref != 0)
        {
        log_activity("Added new slideshow image", LOG_CODE_CREATED, null, 'slideshow', 'ref', $new_ref);

        return $new_ref;
        }
    else if(!is_null($ref) && $new_ref != 0 && $ref == $new_ref)
        {
        log_activity("Updated slideshow image", LOG_CODE_EDITED, null, 'slideshow', 'ref', $ref);

        return $new_ref;
        }

    return false;
    }

/**
* Delete slideshow record
*
* @param integer $ref ID of the slideshow
*
* @return boolean
*/
function delete_slideshow($ref)
    {
    $file_path = get_slideshow_image_file_path($ref);
    if($file_path != '' && unlink($file_path) === false)
        {
        return false;
        }

    $ref = escape_check($ref);
    $query = "DELETE FROM slideshow WHERE ref = '{$ref}'";
    sql_query($query);

    log_activity("Deleted slideshow image", LOG_CODE_DELETED, null, 'slideshow', 'ref', $ref);

    return true;
    }

/**
* Function used to re-order slideshow images
* 
* @param array $from Slideshow image data we move FROM
* @param array $to   Slideshow image data we move TO
*
* @return  boolean
*/
function reorder_slideshow_images(array $from, array $to)
    {
    if(!file_exists($from['file_path']))
        {
        trigger_error('File "' . $from['file_path'] . '" does not exist or could not be found/accessed!');
        }

    if(!file_exists($to['file_path']))
        {
        trigger_error('File "' . $to['file_path'] . '" does not exist or could not be found/accessed!');
        }

    // Calculate files to be moved around
    $from_file = $from['file_path'];
    $temp_file = "{$from['file_path']}.tmp";
    $to_file   = $to['file_path'];

    // Swap the slideshow images
    if(!copy($from_file, $temp_file))
        {
        trigger_error("Failed to copy '{$from_file}' to temp file '{$temp_file}'");
        }

    if(rename($to_file, $from_file) && rename($temp_file, $to_file))
        {
        set_slideshow(
            $from['ref'],
            $to['resource_ref'],
            $to['homepage_show'],
            $to['featured_collections_show'],
            $to['login_show']);

        set_slideshow(
            $to['ref'],
            $from['resource_ref'],
            $from['homepage_show'],
            $from['featured_collections_show'],
            $from['login_show']);

        return true;
        }

    return false;
    }

/**
* Get the full path for the slideshow image file
* 
* @param integer $ref ID of the slideshow image
* 
* @return string The full path to the slideshow image
*/
function get_slideshow_image_file_path($ref)
    {
    $homeanim_folder_path = dirname(__DIR__) . "/{$GLOBALS['homeanim_folder']}";
    $image_file_path = "{$homeanim_folder_path}/{$ref}.jpg";

    if(!file_exists($image_file_path) || !is_readable($image_file_path))
        {
        return '';
        }

    return $image_file_path;
    }