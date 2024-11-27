<?php

/**
 * Check if the user should have read access to a consent record
 *
 * Determines if the user has read access for a specific resource or general read permissions.
 *
 * @param int|null $resource The ID of the resource to check read access for. If null, checks for general read permissions.
 * @return bool              Returns true if the user has the required permissions; false otherwise.
 */
function consentmanager_check_read($resource = null)
{
    // Default to no access
    $has_access = false;

    if (is_numeric($resource)) {
        // Check read access for this specific resource
        $has_access = get_resource_access($resource) == 0 || checkperm("cm");
    } else {
        // Check general read access if no resource specified
        $has_access = checkperm("t") || checkperm("cm");
    }

    return $has_access;
}

/**
 * Check if the user should have write access to a consent record
 *
 * Determines if the user has write access for a specific resource or general write permissions.
 *
 * @param int|null $resource The ID of the resource to check write access for. If null, checks for general write permissions.
 * @return bool              Returns true if the user has the required permissions; false otherwise.
 */
function consentmanager_check_write($resource = null)
{
    // Default to no access
    $has_access = false;

    if (is_numeric($resource)) {
        // Check write access for this specific resource
        $has_access = get_edit_access($resource) || checkperm("cm");
    } else {
        // Check general write access if no resource specified
        $has_access = checkperm("t") || checkperm("cm");
    }

    return $has_access;
}

/**
 * Get a list of consents for a given resource
 *
 * This function retrieves a list of consent records associated with a specified resource.
 * Each record includes the consent ID, name, expiration date, and consent usage.
 *
 * @param int $resource The ID of the resource for which to retrieve associated consents.
 * @return array|bool   Returns an array of consents associated with the resource if the user has read access;
 *                      otherwise, returns false.
 */
function consentmanager_get_consents($resource)
    {
    if (!consentmanager_check_read($resource)) {return false;}
    return ps_query("select consent.ref,consent.name,consent.expires,consent.consent_usage from consent join resource_consent on consent.ref=resource_consent.consent where resource_consent.resource= ? order by ref", ['i', $resource]);
    }

/**
 * Delete a consent record
 *
 * This function deletes a consent record and its associations with resources 
 * by removing entries from the `consent` and `resource_consent` tables.
 *
 * @param int $resource The ID of the consent record to be deleted.
 * @return bool         Returns true if the consent record was successfully deleted, 
 *                      or false if the user does not have write access to the resource.
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
 * This function creates a new consent record by inserting the provided details 
 * into the `consent` table. It returns the ID of the newly created consent 
 * record if successful.
 *
 * @param string $name           The name of the individual giving consent.
 * @param string $email          The email address of the individual.
 * @param string $telephone      The telephone number of the individual.
 * @param string $consent_usage  Description of the intended usage for which consent is given.
 * @param string $notes          Any additional notes related to the consent record.
 * @param string $expires        The expiry date of the consent, formatted as a string.
 * @return int|bool              Returns the ID of the new consent record on success, 
 *                               or false if the user does not have write access.
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
 * This function links a consent record to a specified resource by inserting 
 * an entry in the `resource_consent` table. It also logs this action in the 
 * resource's log.
 *
 * @param int $consent  The ID of the consent record to be linked to the resource.
 * @param int $resource The ID of the resource to which the consent is being linked.
 * @return bool         Returns true if the consent was successfully linked, 
 *                      false if the user does not have write access to the resource.
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
 * Unlink a consent record from a resource
 *
 * This function removes the association between a specified consent record and a resource.
 * The action is logged for the resource.
 *
 * @param int $consent  The ID of the consent record to unlink.
 * @param int $resource The ID of the resource from which to unlink the consent.
 * @return bool         Returns true if the consent record is successfully unlinked; 
 *                      returns false if the user does not have write access to the resource.
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
 * This function links or unlinks all resources in a specified collection to/from 
 * a given consent record. If unlinking, it removes existing relationships between 
 * the consent record and the resources. If linking, it creates new relationships.
 * Each action is logged.
 *
 * @param int  $consent     The ID of the consent record to link or unlink.
 * @param int  $collection  The ID of the collection containing the resources to process.
 * @param bool $unlink      Set to true to unlink resources from the consent; set to 
 *                          false to link resources to the consent.
 * @return bool             Returns true if the process completes successfully; 
 *                          returns false if an invalid consent ID is provided.
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
 * This function retrieves the details of a specified consent record, including 
 * the subject's name, email, telephone number, consent usage types, notes, expiry 
 * date, and file. It also fetches a list of resources associated with the consent.
 *
 * @param int $consent The ID of the consent record to fetch.
 * @return array|bool  Returns an associative array containing consent details and 
 *                     associated resources if the user has read access; returns false 
 *                     if access is denied or the consent record does not exist.
 */
function consentmanager_get_consent($consent)
    {
    if (!consentmanager_check_read()) {return false;}

    $consent=ps_query("select ref,name,email,telephone,consent_usage,notes,expires,file from consent where ref= ?", ['i', $consent]);
    if (empty($consent)) {return false;}
    $consent=$consent[0];
    $resources=ps_array("select distinct resource value from resource_consent where consent= ? order by resource", ['i', $consent['ref']]);
    $consent["resources"]=$resources;
    return $consent;
    }

/**
 * Update a consent record
 *
 * This function updates the details of an existing consent record with the provided 
 * information. It allows modification of the subject's name, email, telephone number, 
 * consent usage types, notes, and expiry date.
 *
 * @param int    $consent        The ID of the consent record to update.
 * @param string $name           The name of the individual giving consent.
 * @param string $email          The email address of the individual.
 * @param string $telephone      The telephone number of the individual.
 * @param string $consent_usage  A description of the permitted usage types for the consent.
 * @param string $notes          Additional notes related to the consent record.
 * @param string $expires        The expiry date of the consent record, formatted as a string.
 * @return bool                  Returns true if the consent record was successfully updated, 
 *                               or false if the user does not have write access.
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
 * This function retrieves all consent records that are linked to resources within
 * a specified collection. It returns an array of consents associated with the resources
 * in the collection.
 *
 * @param int $collection The ID of the collection containing the resources for which 
 *                        to retrieve consent records.
 * @return array|bool     Returns an array of consent records if the user has read access;
 *                        otherwise, returns false.
 */
function consentmanager_get_all_consents_by_collection(int $collection)
    {
    if (!consentmanager_check_read()) {return false;}
    return ps_query("select ref,name from consent where ref in (select consent from resource_consent where resource in (select resource from collection_resource where collection=?)) order by ref",["i",$collection]);
    }


/**
 * Fetch all consent records, optionally filtered by search text
 *
 * This function retrieves all consent records from the database. If a search 
 * string is provided, it filters the results based on the name of the person
 * associated with each consent record.
 *
 * @param string $findtext Optional. A search string to filter the results by the 
 *                          name of the person giving consent. If empty, returns 
 *                          all records.
 * @return array|bool       Returns an array of consent records if the user has 
 *                          read access; otherwise, returns false.
 */
function consentmanager_get_all_consents(string $findtext="")
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