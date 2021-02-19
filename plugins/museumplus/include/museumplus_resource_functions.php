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
    $r_refs = array_filter($refs, 'is_int_loose');
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

    global $museumplus_modules_saved_config, $museumplus_clear_field_mappings_on_change;
    $refs = array_filter($refs, 'is_int');

    if(
        empty($refs)
        // No modules configured
        || is_null($museumplus_modules_saved_config) || $museumplus_modules_saved_config === ''
        // System configured to not clear existing (old) MuseumPlus data on change
        || $museumplus_clear_field_mappings_on_change === false
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
        if(!is_int_loose($join) || !in_array($join, $resource_type_fields))
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

    mplus_log_event('Cleared metadata field values', ['refs' => $refs, 'resource_type_fields' => $resource_type_fields]);

    return;
    }


/**
* Get all resources associated with a MuseumPlus module.
* 
* @param array $filters Rules to filter results (if applicable). There are "flag" filters (e.g new_and_changed_associations filter)
*                       and filters that take arguments (e.g byref)
* 
* @return array
*/
function mplus_resource_get_association_data(array $filters)
    {
    if(
        !isset($GLOBALS['museumplus_module_name_field'], $GLOBALS['museumplus_modules_saved_config'])
        || !(is_int_loose($GLOBALS['museumplus_module_name_field']) && $GLOBALS['museumplus_module_name_field'] > 0)
        || !(is_string($GLOBALS['museumplus_modules_saved_config']) && $GLOBALS['museumplus_modules_saved_config'] !== '')
    )
        {
        return [];
        }

    $module_name_field_ref = $GLOBALS['museumplus_module_name_field'];
    $modules_config = plugin_decode_complex_configs($GLOBALS['museumplus_modules_saved_config']);


    // Get filters required at a "per module configuration" level.
    // IMPORTANT: do not continue if the plugin isn't properly configured (ie. this information is missing or corrupt)
    $rs_uid_fields = [];
    $per_module_cfg_filters = [];
    foreach($modules_config as $mcfg)
        {
        $module_name = $mcfg['module_name'];
        $rs_uid_field = $mcfg['rs_uid_field'];
        $applicable_resource_types = array_filter($mcfg['applicable_resource_types'], 'is_int_loose');

        if(is_int_loose($rs_uid_field) && $rs_uid_field > 0 && !in_array($rs_uid_field, $rs_uid_fields))
            {
            $rs_uid_fields[] = $rs_uid_field;
            }

        if($module_name !== '' && !empty($applicable_resource_types) && is_int_loose($rs_uid_field) && $rs_uid_field > 0)
            {
            $per_module_cfg_filters[] = sprintf(
                '(%s = \'%s\' AND r.resource_type IN (\'%s\'))',
                ($module_name === 'Object' ? 'coalesce(n.`name`, \'Object\')' : 'n.`name`'),
                $module_name,
                implode('\', \'', $applicable_resource_types)
            );
            }
        }
    if(empty($rs_uid_fields) || empty($per_module_cfg_filters)) { return []; }


    // Additional filters (as required by caller code)
    $additional_filters = [];
    foreach(mplus_validate_resource_association_filters($filters) as $filter_name => $filter_args)
        {
        switch($filter_name)
            {
            case 'byref':
                $refs = array_filter($filter_args, 'is_int_loose');
                $additional_filters[] = 'AND r.ref IN (' . implode(', ', $refs) . ')';
                break;
            }
        }


    // Build and run SQL
    $sqlq = sprintf('
           SELECT r.ref AS `value`
             FROM resource AS r
        LEFT JOIN resource_node AS rn ON r.ref = rn.resource
        LEFT JOIN node AS n ON rn.node = n.ref AND n.resource_type_field = \'%s\'
            WHERE r.archive = 0
              %s # Filters specific to each module configuration (e.g applicable resource types)
              %s # Additional filters
        GROUP BY r.ref
        ORDER BY r.ref DESC
        ',
        escape_check($module_name_field_ref),
        'AND (' . PHP_EOL . implode(PHP_EOL . 'OR ', $per_module_cfg_filters) . PHP_EOL . ')',
        implode(PHP_EOL, $additional_filters)
    );

    return sql_array($sqlq);
    }
