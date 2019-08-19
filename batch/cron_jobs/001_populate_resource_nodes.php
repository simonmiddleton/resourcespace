<?php

# Check that resource_nodes has been populated

if(!isset($sysvars['resource_node_migration_state']) || $sysvars['resource_node_migration_state'] != "COMPLETE")
    {
    echo "Populating resource_node and node_keyword tables" . $LINE_END;
    populate_resource_nodes(((isset($sysvars['resource_node_migration_state']))?$sysvars['resource_node_migration_state']:0));
    }
else
    {
    echo " - Skipping populate_resource_nodes - already run" . $LINE_END;
    }
    