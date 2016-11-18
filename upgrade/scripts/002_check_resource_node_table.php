  <?php

include_once __DIR__ . "/../../include/db.php";
include_once __DIR__ . "/../../include/general.php";

// ---------------------------------------------------------------------------------------------------------------------
// Step 1.  Check that composite primary key has been set on resource_node table (since added to dbstruct)
// ---------------------------------------------------------------------------------------------------------------------

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT,"Checking primary key and removing duplicates from resource_node table");

sql_query("SET session old_alter_table=1");
sql_query("ALTER IGNORE TABLE resource_node ADD PRIMARY KEY(resource,node)");
    
    


