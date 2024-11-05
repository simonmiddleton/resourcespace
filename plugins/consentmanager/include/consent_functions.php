<?php

/**
 * Check if the user should have write access to a consent record
 *
 */
function consentmanager_check_write($resource=null)
    {
    if (is_numeric($resource))
        {
        // Write access to this resource needed, or general admin if no resource specified.
        $edit_access=get_edit_access($resource);
        if (!$edit_access && !checkperm("cm")) {return false;}
        }
    else
        {
        $is_admin = checkperm("t");
        if (!$is_admin && !checkperm("cm")) {return false;}
        }
    return true;
    }

/**
 * Check if the user should have read access to a consent record
 *
 */
function consentmanager_check_read($resource=null)
    {
    // Check for read access to the resource, or  general admin if no resource specified.
    if (is_numeric($resource))
        {
        // Read access to this resource needed
        $access=get_resource_access($resource);
        if ($access!=0 && !checkperm("cm")) {return false;}
        }
    else
        {
        $is_admin = checkperm("t");
        if (!$is_admin && !checkperm("cm")) {return false;}
        }
    return true;
    }

/**
 * Get a list of consents for a given resource
 *
 */
function consentmanager_get_consents($resource)
    {
    if (!consentmanager_check_read($resource)) {return false;}
    return ps_query("select consent.ref,consent.name,consent.expires,consent.consent_usage from consent join resource_consent on consent.ref=resource_consent.consent where resource_consent.resource= ? order by ref", ['i', $resource]);
    }

/**
 * Delete a consent record
 *
 */
function consentmanager_delete_consent($resource)
    {
    if (!consentmanager_check_write($resource)) {return false;}
    ps_query("delete from consent where ref= ?", ['i', $resource]);
    ps_query("delete from resource_consent where consent= ?", ['i', $resource]);
    return true;
    }


/**
 * Create a new consent record
 *
 */
function consentmanager_create_consent($name, $email, $telephone, $consent_usage, $notes, $expires)
    {
    if (!consentmanager_check_write()) {return false;}
    # New record
    ps_query(
        "insert into consent (name,email,telephone,consent_usage,notes,expires) values ( ?, ?, ?, ?, ?, ?)",
        [
            's',$name,
            's',$email,
            's', $telephone,
            's', $consent_usage,
            's', $notes,
            's', $expires
        ]
    );
    return sql_insert_id();
    }

/**
 * Link a consent record with a resource
 *
 */
function consentmanager_link_consent($consent,$resource)
    {
    global $lang;
    if (!consentmanager_check_write($resource)) {return false;}
    ps_query("insert into resource_consent(resource,consent) values (?, ?)", ['i', $resource, 'i', $consent]);
    resource_log($resource,"","",$lang["new_consent"] . " " . $consent);
    return true;
    }

/**
 * Unlink a consent record / resource 
 *
 */
function consentmanager_unlink_consent($consent,$resource)
    {
    global $lang;
    if (!consentmanager_check_write($resource)) {return false;}
    ps_query("delete from resource_consent where consent= ? and resource= ?", ['i', $consent, 'i', $resource]);
    resource_log($resource,"","",$lang["unlink_consent"] . " " . $consent);
    return true;
    }

/**
 * Link/unlink all resources in a collection with a consent record
 *
 */
function consentmanager_batch_link_unlink($consent,$collection,$unlink)
    {
    $resources=get_collection_resources($collection);

    if($consent <= 0)
        {
        return false;
        }

    foreach ($resources as $resource)
        {
        if (consentmanager_check_write($resource))
            {
            // Always remove any existing relationship
            ps_query("delete from resource_consent where consent= ? and resource= ?", ['i', $consent, 'i', $resource]);

            // Add link?
            if (!$unlink) {ps_query("insert into resource_consent (resource,consent) values (?, ?)", ['i', $resource, 'i', $consent]);}

            // Log
            global $lang;
            resource_log($resource,"","",$lang[($unlink?"un":"") . "linkconsent"] . " " . $consent);
            }
        }
    return true;
    }

/**
 * Retrieve a consent record
 *
 */
function consentmanager_get_consent($consent)
    {
    if (!consentmanager_check_read()) {return false;}

    $consent=ps_query("select name,email,telephone,consent_usage,notes,expires,file from consent where ref= ?", ['i', $consent]);
    if (empty($consent)) {return false;}
    $resources=ps_array("select distinct resource value from resource_consent where consent= ? order by resource", ['i', $consent]);
    $consent=$consent[0];
    $consent["resources"]=$resources;
    return $consent;
    }

/**
 * Update a consent record
 *
 */
function consentmanager_update_consent($consent, $name, $email, $telephone, $consent_usage, $notes, $expires)
    {
    if (!consentmanager_check_write()) {return false;}
    ps_query(
        "update consent set name= ?,email= ?, telephone= ?,consent_usage= ?,notes= ?,expires= ? where ref= ?",
        [
            's', $name,
            's', $email,
            's', $telephone,
            's', $consent_usage,
            's', $notes,
            's', $expires,
            'i', $consent
        ]
    );
    return true;
    }

/**
 * Fetch all consent records linked to resources in a collection
 *
 */
function consentmanager_get_all_consents_by_collection($collection)
    {
    if (!consentmanager_check_read()) {return false;}
    return ps_query("select ref,name from consent where ref in (select consent from resource_consent where resource in (select resource from collection_resource where collection=?)) order by ref",["i",$collection]);
    }

/**
 * Fetch all consent records, optionally filtered by search text
 *
 */
function consentmanager_get_all_consents($findtext="")
    {
    if (!consentmanager_check_read()) {return false;}
    $sql="";
    $params = [];
    if ($findtext!="")
        {
        $sql="where name like ?";
        $params = ['s', "%$findtext%"];
        }
    return ps_query("select ". columns_in('consent', null, 'consentmanager') ." from consent $sql order by ref", $params);
    }