<?php
set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Starting migrating public collections to use the new collection type - COLLECTION_TYPE_PUBLIC...");

$public_collections = sql_array(sprintf("SELECT ref AS `value` FROM collection WHERE `type` = %s AND public = 1 AND (theme IS NULL OR length(trim(theme)) = 0)", COLLECTION_TYPE_STANDARD));
if(!empty($public_collections))
    {
    sql_query(
        sprintf(
            "UPDATE collection SET `type` = %s WHERE ref IN ('%s')",
            COLLECTION_TYPE_PUBLIC,
            join("', '", $public_collections)));
    }

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Successfully migrated public collections to use the new 'COLLECTION_TYPE_PUBLIC' type");