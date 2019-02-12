<?php
function get_mapped_fields()
{
    $query = 'SELECT DISTINCT field_id AS value FROM assign_request_map;';
    
    return sql_array($query);
}

function get_mapped_user_by_field($id, $value)
{
    $query = sprintf('
            SELECT user_id AS value
              FROM assign_request_map
             WHERE field_id = \'%s\'
               AND field_value = \'%s\';
        ',
        $id,
        $value
    );

    $query_escaped = escape_check($query);
    return sql_value($query_escaped, 0);
}
?>