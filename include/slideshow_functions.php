<?php
/**
* Create a new slideshow image record
* 
* @param integer $resource_ref              ID of the resource this slideshow is related to. Use NULL if no link is required
* @param integer $homepage_show             Set to 1 if slideshow image should appear on the home page
* @param integer $featured_collections_show Set to 1 if slideshow image should appear on the featured collections page
* @param integer $login_show                Set to 1 if slideshow image should appear on the login page
* 
* @return boolean|integer Returns ID of the new slideshow record or FALSE
*/
function set_slideshow($resource_ref = NULL, $homepage_show = 1, $featured_collections_show = 1, $login_show = 0)
    {
    if(
        (!is_null($resource_ref) && !is_numeric($resource_ref))
        || !is_numeric($homepage_show)
        || !is_numeric($featured_collections_show)
        || !is_numeric($login_show))
        {
        return false;
        }

    if(is_null($resource_ref))
        {
        $resource_ref = 'NULL';
        }
    else
        {
        $resource_ref = "'" . escape_check($resource_ref) . "'";
        }

    $homepage_show = escape_check($homepage_show);
    $featured_collections_show = escape_check($featured_collections_show);
    $login_show = escape_check($login_show);

    $query = "INSERT INTO slideshow (resource_ref, homepage_show, featured_collections_show, login_show)
                 VALUES ({$resource_ref}, '{$homepage_show}', '{$featured_collections_show}', '{$login_show}')";

    sql_query($query);

    $new_ref = sql_insert_id();
    if($new_ref != 0)
        {
        log_activity("Added new slideshow image", LOG_CODE_CREATED, null, 'slideshow', 'ref', $new_ref);

        return $new_ref;
        }

    return false;
    }

/**
* Delete slideshow record
*
* @param integer $ref ID of the slideshow
*
* @return void
*/
function delete_slideshow($ref)
    {
    $ref = escape_check($ref);
    $query = "DELETE FROM slideshow WHERE ref = '{$ref}'";
    sql_query($query);

    return;
    }