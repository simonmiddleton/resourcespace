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