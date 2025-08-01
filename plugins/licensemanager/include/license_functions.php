<?php

/**
 * Check if the user should have read access to a license record
 *
 * Determines if the user has read access for a specific resource or general read permissions.
 *
 * @param int|null $resource The ID of the resource to check read access for. If null, checks for general read permissions.
 * @return bool              Returns true if the user has the required permissions; false otherwise.
 */
function licensemanager_check_read($resource = null)
{
    // Default to no access
    $has_access = false;

    if (is_numeric($resource)) {
        // Check read access for this specific resource
        $has_access = get_resource_access($resource) == 0 || checkperm("lm");
    } else {
        // Check general read access if no resource specified
        $has_access = checkperm("t") || checkperm("lm");
    }

    return $has_access;
}

/**
 * Check if the user should have write access to a license record
 *
 * Determines if the user has write access for a specific resource or general write permissions.
 *
 * @param int|null $resource The ID of the resource to check write access for. If null, checks for general write permissions.
 * @return bool              Returns true if the user has the required permissions; false otherwise.
 */
function licensemanager_check_write($resource = null)
{
    // Default to no access
    $has_access = false;

    if (is_numeric($resource)) {
        // Check write access for this specific resource
        $has_access = get_edit_access($resource) || checkperm("lm");
    } else {
        // Check general write access if no resource specified
        $has_access = checkperm("t") || checkperm("lm");
    }

    return $has_access;
}

/**
 * Get a list of licenses for a given resource
 *
 * This function retrieves a list of license records associated with a specified resource.
 * Each record includes the license details, expiration date, and consent usage.
 *
 * @param int           $resource The ID of the resource for which to retrieve associated licenses.
 * @return array|bool   Returns an array of licenses associated with the resource if the user has read access;
 *                      otherwise, returns false.
 */
function licensemanager_get_licenses($resource)
{
    if (!licensemanager_check_read($resource)) {
        return false;
    }

    return ps_query("select " . columns_in('license', 'license', 'licensemanager') . " from license join resource_license on license.ref=resource_license.license where resource_license.resource= ? order by ref", ['i', $resource]);
}

/**
 * Delete a license record
 *
 * This function deletes a license record and its associations with resources
 * by removing entries from the `license` and `resource_license` tables.
 *
 * @param int           $ref The ID of the license record to be deleted.
 * @return bool         Returns true if the license record was successfully deleted,
 *                      or false if the user does not have write access to the resource.
 */
function licensemanager_delete_license($ref): bool
{
    if (!licensemanager_check_write($ref)) {
        return false;
    }

    ps_query("delete from license where ref= ?", ['i', $ref]);
    ps_query("delete from resource_license where license= ?", ['i', $ref]);

    return true;
}

/**
 * Link a license record with a resource
 *
 * This function links a license record to a specified resource by inserting
 * an entry in the `resource_license` table. It also logs this action in the
 * resource's log.
 *
 * @param int $license  The ID of the license record to be linked to the resource.
 * @param int $resource The ID of the resource to which the license is being linked.
 * @return bool         Returns true if the license was successfully linked,
 *                      false if the user does not have write access to the resource.
 */
function licensemanager_link_license($license, $resource)
{
    global $lang;

    if (!licensemanager_check_write($resource)) {
        return false;
    }

    // Check if license exists
    $license_check = ps_query("select " . columns_in('license', null, 'licensemanager') . " from license where ref= ?", ['i', $license]);

    if (empty($license_check)) {
        return false;
    }

    ps_query("insert into resource_license(resource, license) values (?, ?)", ['i', $resource, 'i', $license]);
    resource_log($resource, "", "", $lang["new_license"] . " " . $license);

    return true;
}

/**
 * Retrieve a license record
 *
 * This function retrieves the details of a specified license record
 * It also fetches a list of resources associated with the license.
 *
 * @param int $license The ID of the license record to fetch.
 * @return array|bool  Returns an associative array containing license details and
 *                     associated resources if the user has read access; returns false
 *                     if access is denied or the license record does not exist.
 */
function licensemanager_get_license($license): array|bool
{
    if (!licensemanager_check_read()) {
        return false;
    }

    $license = ps_query("select " . columns_in('license', null, 'licensemanager') . " from license where ref= ?", ['i', $license]);

    if (empty($license)) {
        return false;
    }

    $license = $license[0];
    $resources = ps_array("select distinct resource value from resource_license where license= ? order by resource", ['i', $license['ref']]);
    $license["resources"] = $resources;

    return $license;
}

/**
 * Fetch all license records, optionally filtered by search text
 *
 * This function retrieves all license records from the database. If a search
 * string is provided, it filters the results based on licensor/licensee/medium/description
 * It can also filted based on license status e.g all, active (non-expired), expiring (expiring within a configured amount of days), 
 * expired. Defaults to returning all. 
 *
 * @param string $findtext          Optional. A search string to filter the results by the
 *                                  licensor/licensee/medium/description of the license. 
 *                                  If empty, returns all records.
 * @param string $license_status    Status of license records to return
 * @return array|bool               Returns an array of license records if the user has
 *                                  read access; otherwise, returns false.
 */
function licensemanager_get_all_licenses(string $findtext = "", string $license_status = "all"): array|bool
{

    global $license_expiry_notification_days;

    if (!licensemanager_check_read()) {
        return false;
    }

    $sql = "select " . columns_in('license', null, 'licensemanager') . " from license";
    
    $where_sql = "";
    $params = [];

    $orderby_sql = "";

    if ($findtext != "") {
        $where_sql    = "where description like CONCAT('%', ?, '%') or holder like CONCAT('%', ?, '%') or license_usage like CONCAT('%', ?, '%')";
        $params = ['s', $findtext, 's', $findtext, 's', $findtext];
    }

    // All - no filter on licenses
    // Active - any that haven't expired yet or have no date set
    // Expiring - any that have an expiry date within $license_expiry_notification_days days
    // Expired - any that the expiry date has been passed
    if ($license_status == 'expired') {
        
        if ($where_sql == "") {
            $where_sql = " where";
        }
        
        $where_sql .= " expires < CURDATE()";
        $orderby_sql = " order by expires desc, ref";        

    } elseif ($license_status == 'expiring') {

        if ($where_sql == "") {
            $where_sql = " where";
        }
        
        $where_sql .= " expires >= CURDATE() AND expires <= CURDATE() + INTERVAL ? DAY";
        array_push($params, 'i', $license_expiry_notification_days);
        $orderby_sql = " order by expires asc, ref";

    } elseif ($license_status == 'active') {
        
        if ($where_sql == "") {
            $where_sql = " where";
        }

        $where_sql .= " expires >= CURDATE() OR expires is NULL";
        $orderby_sql = " order by ref";

    } else {
        $orderby_sql = " order by ref";
    }

    return ps_query($sql . $where_sql . $orderby_sql, $params);
}

/**
 * 
 */
function licensemanager_get_all_licenses_grouped(): array|bool
{
    if (!licensemanager_check_read()) {
        return false;
    }

    $sql = "select *
            from (
                select l.*, 
                case when l.expires < CURDATE() 
                then 'expired' 
                else 'active' end as license_status
                from license l
            ) ls
            order by license_status, ref";

    return ps_query($sql);
}

function licensemanager_get_expired_license_resources(int $archive_status): array|bool
{
    $sql = "select distinct r.ref value
            from license l
            inner join resource_license rl on l.ref = rl.license
            inner join resource r on rl.resource = r.ref
            where l.expires < CURDATE()
            and r.archive <> ?
            and r.archive <> 3
            order by r.ref;";
    
    $expired_license_resources = ps_array($sql, ['i', $archive_status]);

    return $expired_license_resources;
}

function licensemanager_process_expired_archiving(): bool {

    global $license_expired_workflow_state;

    logScript("License Manager: automatic archiving job starting:");

    $expired_license_resources = licensemanager_get_expired_license_resources($license_expired_workflow_state);

    foreach ($expired_license_resources as $resource) {
        logScript("License Manager: changing status of resource #" . $resource);
        update_archive_status($resource, $license_expired_workflow_state, [], 0, "- due to expired license record");
    }

    logScript("License Manager: automatic archiving job complete");

    return true;
}