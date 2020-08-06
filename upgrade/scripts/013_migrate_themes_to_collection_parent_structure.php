<?php
$featured_collections = sql_query("SELECT * FROM collection WHERE public = 1 AND length(theme) > 0");

echo "<pre>";print_r($featured_collections);echo "</pre>";

foreach($featured_collections as $collection)
    {
    // Ensure the full tree structure exists first to support this.
    $parent = null;
    for($level = 1; $level <= $theme_category_levels; $level++)
        {
        $col = "theme" . ($level == 1 ? "" : $level);

        if(trim($collection[$col]) == "")
            {
            continue;
            }

        $parent_sql_val = (is_null($parent) ? "NULL" : escape_check($parent));
        $new_fc_name = escape_check($collection[$col]);

        logScript("Processing collection #{$collection["ref"]} - column {$col} = '{$collection[$col]}' and parent = '{$parent_sql_val}'");

        $fc_ref = sql_value("SELECT ref AS `value` FROM collection WHERE public = 1 AND name = '{$new_fc_name}' AND parent = '$parent'", null);
        if(is_null($fc_ref))
            {
            // logScript("INSERT INTO collection(name, public, type, parent) VALUES ('{$new_fc_name}', 1, '".COLLECTION_TYPE_FEATURED."', '{$parent_sql_val}')");
            $fc_ref = create_collection($collection["user"], $collection[$col], 0, 0, 0, true);

            if(!is_null($parent) && !update_collection_parent($fc_ref, $parent))
                {
                set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Upgrade script: Unable to set collection parent to '{$parent}' for collection #{$collection["ref"]} - column {$col} = '{$collection[$col]}'");
                }

            if(!update_collection_type($fc_ref, COLLECTION_TYPE_FEATURED))
                {
                set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Upgrade script: Unable to set collection type to COLLECTION_TYPE_FEATURED for collection #{$collection["ref"]} - column {$col} = '{$collection[$col]}' and parent = '{$parent_sql_val}'");
                }

            logScript("Created new FC #{$fc_ref}");
            }

        // Set the parent to this collection as we descend down the tree
        $parent = $fc_ref;
        }
    
    // The necessary parts of the tree now exist to support this collection. Drop it into the tree.
    update_collection_parent($collection["ref"], $parent);
    }

# TO DO - map existing j perms to new collection/user group access system

// set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "");
