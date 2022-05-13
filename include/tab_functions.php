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

