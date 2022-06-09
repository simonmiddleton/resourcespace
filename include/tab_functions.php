<?php
/**
 * Access control check if user is allowed to manage system tabs.
 * 
 * @return bool
 * */
function acl_can_manage_tabs()
    {
    return checkperm('a');
    }



function get_tabs_with_usage_count(int $per_page, int $offset)
    {
    $query = new PreparedStatementQuery(
        'SELECT t.ref,
                t.`name`,
                t.order_by,
                (SELECT count(ref) FROM resource_type_field WHERE tab = t.ref) AS usage_rtf,
                (SELECT count(ref) FROM resource_type WHERE tab = t.ref) AS usage_rt
           FROM tab AS t
           ORDER BY order_by ASC' # TODO: needs to be parameterised
    );

    return sql_limit_with_total_count($query, $per_page, $offset);
    }


/**
 * Create a new system tab record
 * NOTE: order_by should only be set when re-ordering the set by the user. {@see sql_reorder_records('tab', $refs)}
 * 
 * @return bool|int Return new tab record ID or FALSE otherwise
 */
function create_tab(array $tab)
    {
    $name = trim($tab['name'] ?? '');
    if($name !== '' && acl_can_manage_tabs())
        {
        ps_query('
            INSERT INTO tab (`name`, order_by)
                     VALUES (
                        ?,
                        (
                            SELECT * FROM (
                                (SELECT ifnull(tab.order_by, 0) + 10 FROM tab ORDER BY ref DESC LIMIT 1)
                                UNION SELECT 10
                            ) AS nob
                            LIMIT 1
                        ))',
             ['s', $name]
         );
        return sql_insert_id();
        }

    return false;
    }


/**
 * Delete system tabs.
 * 
 * IMPORTANT: never allow the "Default" tab (ref #1) to be deleted because this is the fallback location for information 
 * that has no association with other tabs.
 * 
 * @param array $refs List of tab IDs
 * 
 * @return bool Returns TRUE if it executed the query, FALSE otherwise
 */
function delete_tabs(array $refs)
    {
    if(!acl_can_manage_tabs())
        {
        return false;
        }

    $refs_chunked = array_chunk(
        // Sanitise list: only numbers and never allow the "Default" tab (ref #1) to be deleted
        array_diff(array_filter($refs, 'is_int_loose'), [1]),
        SYSTEM_DATABASE_IDS_CHUNK_SIZE
    );
    foreach($refs_chunked as $refs_list)
        {
        $return = ps_query(
            "DELETE FROM tab WHERE ref IN (" . ps_param_insert(count($refs_list)) . ")",
            ps_param_fill($refs_list, 'i')
        );
        }

    return isset($return);
    }


/**
 * Update an existing tab.
 * NOTE: order_by should only be set when re-ordering the set by the user. {@see sql_reorder_records('tab', $refs)}
 * 
 * @param array $tab A tab record (type)
 * 
 * @return bool Returns TRUE if it executed the query, FALSE otherwise
 */
function save_tab(array $tab)
    {
    $ref = (int) $tab['ref'];
    $name = trim($tab['name']);
    
    if($ref > 0 && $name !== '' && acl_can_manage_tabs())
        {
        ps_query('UPDATE tab SET `name` = ? WHERE ref = ?', ['s', $name, 'i', $ref]);
        return true;
        }

    return false;
    }