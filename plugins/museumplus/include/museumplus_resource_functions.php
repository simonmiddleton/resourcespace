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


/**
* Clear resource metadata fields that are mapped to any of the modules configured by the plugin.
* 
* @param array $refs List of resource IDs
* 
* @return void
*/
function mplus_resource_clear_metadata(array $refs)
    {
    mplus_log_event('Called mplus_resource_clear_metadata()', ['refs' => $refs], 'debug');

    $refs = array_filter($refs, 'is_int');

    global $museumplus_modules_saved_config, $museumplus_clear_field_mappings_on_change;
    if(
        empty($refs)
        // No modules configured
        || is_null($museumplus_modules_saved_config) || $museumplus_modules_saved_config === ''
        // System configured to not clear data on change
        || $museumplus_clear_field_mappings_on_change == false
    )
        {
        return;
        }

    // Get list of unique metadata fields that are mapped to MuseumPlus modules' fields
    $resource_type_fields = [];
    foreach(plugin_decode_complex_configs($museumplus_modules_saved_config) as $module_cfg)
        {
        $resource_type_fields = array_merge($resource_type_fields, array_column($module_cfg['field_mappings'], 'rs_field'));
        }
    $resource_type_fields = array_values(array_filter(array_unique($resource_type_fields), 'ctype_digit'));
    if(empty($resource_type_fields)) { return; }

    $sql_in_refs = implode('\', \'', $refs);
    $sql_in_rtfs = implode('\', \'', $resource_type_fields);
    sql_query("DELETE FROM resource_data WHERE resource IN ('{$sql_in_refs}') AND resource_type_field IN ('{$sql_in_rtfs}')");
    sql_query(
        "DELETE rn
           FROM resource_node AS rn
      LEFT JOIN node AS n ON n.ref = rn.node
      LEFT JOIN resource_type_field AS rtf ON rtf.ref = n.resource_type_field
          WHERE rn.resource IN ('{$sql_in_refs}')
            AND rtf.ref IN ('{$sql_in_rtfs}')"
    );

    // Clear related 'joined' fields
    $joins = get_resource_table_joins();
    $sql_joins = '';
    foreach($joins as $join)
        {
        if(!in_array($join, $resource_type_fields))
            {
            continue;
            }

        $sql_joins .= sprintf('%sfield%s = NULL',
            ($sql_joins != '' ? ', ' : ''),
            escape_check($join));
        }
    if($sql_joins !== '')
        {
        sql_query("UPDATE resource SET {$sql_joins} WHERE ref IN ('{$sql_in_refs}')");
        }

    mplus_log_event('Cleared metadata field values',
        [
            'refs' => $refs,
            'resource_type_fields' => $resource_type_fields,
        ], 'debug');

    return;
    }