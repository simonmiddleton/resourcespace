<?php
/**
* Get the "museumplus_data_md5" column from the resource table for a batch of resources
* 
* @param array $refs List of resource IDs
* 
* @return array Return key is the resource ref, value is the MD5 hash
*/
function mplus_resource_get_data_md5(array $refs)
    {
    $r_refs = array_filter($refs, 'is_numeric');
    $results = sql_query("SELECT ref, museumplus_data_md5 FROM resource WHERE ref IN ('" . implode("', '", $r_refs) . "')");
    return array_column($results, 'museumplus_data_md5', 'ref');
    }