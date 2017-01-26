<?php

# Check that resource_nodes has been populated

if(!isset($sysvars['resource_node_migration_state']) || $sysvars['resource_node_migration_state'] != "COMPLETE")
    {
    echo "Populating resource_node and node_keyword tables\r\n";
    populate_resource_nodes(((isset($sysvars['resource_node_migration_state']))?$sysvars['resource_node_migration_state']:0));
    }
    