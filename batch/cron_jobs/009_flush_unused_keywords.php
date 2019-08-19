<?php
include_once __DIR__ . "/../../include/db.php";
if($sysvars["upgrade_system_level"] < 2)
    {
    // After migrating to nodes it is prefereable to delete resource_keyword rows for fixed list data
    // as this is now stored in normalised form in resource_node and node_keyword
    
    $fixed_fields=sql_array("SELECT ref value FROM resource_type_field WHERE type IN ('" . join("','",$FIXED_LIST_FIELD_TYPES) . "') ");
    
    foreach($fixed_fields as $fixed_field)
        {
        echo " - Deleting resource_keyword data for field ref#" . $fixed_field . $LINE_END;
        $count_query="SELECT COUNT(*) value FROM resource_keyword WHERE resource_type_field='" . $fixed_field . "'";
        $c=sql_value($count_query,0);
        while ($c>0)
            {
            sql_query("DELETE FROM resource_keyword WHERE resource_type_field ='" . $fixed_field . "' LIMIT 1000");
            $c = $c - 1000;
            echo (" -- " . $c . " rows remaining to delete" . $LINE_END);
            }
        
        }
    }
else
    {
    echo " - Skipping flush_unused_keywords - already complete" . $LINE_END;    
    }
