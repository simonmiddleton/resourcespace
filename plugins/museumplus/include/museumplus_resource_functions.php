<?php
/**
* Get resource table columns relevant to the MuseumPlus integration
* 
* @param array $refs List of resource IDs
* 
* @return array
*/
function mplus_resource_get_data(array $refs)
    {
    $r_refs = array_filter($refs, 'is_numeric');
    if(empty($r_refs))
        {
        return [];
        }

    $results = sql_query("SELECT ref, museumplus_data_md5, museumplus_technical_id FROM resource WHERE ref IN ('" . implode("', '", $r_refs) . "')");
    return $results;
    }


/**
* Mark resource failed validating the MuseumPlus association
* 
* @param array $resources List of resource IDs (key) and computed MD5 hashes (value). {@see mplus_compute_data_md5()}
* 
* @return void
*/
function mplus_resource_mark_validation_failed(array $resources)
    {
    if(empty($resources))
        {
        return;
        }

    $qvals = [];
    foreach($resources as $ref => $md5)
        {
        // Sanitise input
        if((string)(int) $ref !== (string) $ref || $md5 === '')
            {
            continue;
            }

        // Prepare SQL query values
        $qvals[$ref] = sprintf('(\'%s\', \'%s\', NULL)', $ref, escape_check($md5));
        }
    if(empty($qvals)) { return; }

    // Validate the list of resources input to avoid creating new resources. We only want to update existing ones
    $sql_ref_in = implode('\', \'', array_keys($qvals));
    $valid_refs = sql_array("SELECT ref AS `value` FROM resource WHERE ref IN ('$sql_ref_in')");
    if(empty($valid_refs)) { return; }

    // Update resources with the new computed MD5s
    $sql_values = implode(', ', array_intersect_key($qvals, array_flip($valid_refs)));
    $query = "INSERT INTO resource (ref, museumplus_data_md5, museumplus_technical_id) VALUES {$sql_values}
                       ON DUPLICATE KEY UPDATE museumplus_data_md5 = VALUES(museumplus_data_md5), museumplus_technical_id = VALUES(museumplus_technical_id)";
    sql_query($query);

    mplus_log_event('Validation failed!', [ 'resources' => $valid_refs], 'error');

    return;
    }


/**
* Mark resource module association as valid. This updates the MD5 hash for the current combination of "module name - MpID"
* and the valid technical ID retrieved from MuseumPlus.
* 
* @param array $resources List of resource IDs (key) and MuseumPlus technical ID - ie. "__id" - as value.
* @param array $md5s      List of resource IDs (key) and computed MD5 hashes (value). {@see mplus_compute_data_md5()}
* 
* @return void
*/
function mplus_resource_update_association(array $resources, array $md5s)
    {
    if(empty($resources))
        {
        return;
        }

    $qvals = [];
    foreach($resources as $ref => $mplus_technical_id)
        {
        // Sanitise input
        if((string)(int) $ref !== (string) $ref)
            {
            continue;
            }

        $md5 = (isset($md5s[$ref]) ? $md5s[$ref] : '');

        // Prepare SQL query values
        $qvals[$ref] = sprintf('(\'%s\', %s, %s)',
            $ref,
            sql_null_or_val($md5, $md5 == ''),
            sql_null_or_val($mplus_technical_id, $mplus_technical_id == ''));
        }
    if(empty($qvals)) { return; }

    // Validate the list of resources input to avoid creating new resources. We only want to update existing ones
    $sql_ref_in = implode('\', \'', array_keys($qvals));
    $valid_refs = sql_array("SELECT ref AS `value` FROM resource WHERE ref IN ('$sql_ref_in')");
    if(empty($valid_refs)) { return; }

    // Update resources with the new computed MD5s
    $sql_values = implode(', ', array_intersect_key($qvals, array_flip($valid_refs)));
    $q = "INSERT INTO resource (ref, museumplus_data_md5, museumplus_technical_id) VALUES {$sql_values}
                   ON DUPLICATE KEY UPDATE museumplus_data_md5 = VALUES(museumplus_data_md5), museumplus_technical_id = VALUES(museumplus_technical_id)";
    sql_query($q);

    mplus_log_event('Updated resource module association!', [ 'qvals' => $qvals], 'info');

    return;
    }
