<?php

include_once __DIR__ . "/../../include/db.php";
include_once __DIR__ . "/../../include/general.php";

// ---------------------------------------------------------------------------------------------------------------------
// Step 1.  Check that composite primary key has been set on resource_node table (since added to dbstruct)
// ---------------------------------------------------------------------------------------------------------------------

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT,"Checking primary key and removing duplicates from resource_node table");

$rnindexes =sql_query("SHOW KEYS FROM resource_node WHERE Key_name = 'PRIMARY'");
$rnkeys = array_column($rnindexes,"Column_name");

if(!in_array("resource",$rnkeys) || !in_array("node",$rnkeys))    
    {
    // Copy to a temporary table and then rename to remove duplicates. Allows use of INSERT  - ON DUPLICATE KEY syntax when inserting new nodes
    sql_query("DROP TABLE IF EXISTS resource_node_deduped");
    db_begin_transaction();
    sql_query("CREATE TABLE resource_node_deduped like resource_node");
    sql_query("ALTER TABLE resource_node_deduped ADD PRIMARY KEY(resource,node)");
    sql_query("INSERT INTO resource_node_deduped SELECT * FROM resource_node ON DUPLICATE KEY UPDATE resource_node_deduped.hit_count=resource_node.hit_count");
    sql_query("RENAME TABLE resource_node TO resource_node_with_dupes");
    sql_query("RENAME TABLE resource_node_deduped TO resource_node");
    sql_query("DROP TABLE resource_node_with_dupes");
    db_end_transaction();
    }

    


