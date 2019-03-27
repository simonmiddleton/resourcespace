<?php
include_once __DIR__ . "/../../include/db.php";
include_once __DIR__ . "/../../include/general.php";
include_once __DIR__ . "/../../include/resource_functions.php";
include_once __DIR__ . "/../../include/search_functions.php";
include_once __DIR__ . "/../../include/migration_functions.php";

if($search_filter_nodes && (!isset($sysvars["SEARCH_FILTER_MIGRATION"]) || $sysvars["SEARCH_FILTER_MIGRATION"] == 0))
    {
    $notification_users = get_notification_users();
    $groups = sql_query("SELECT ref, name,search_filter FROM usergroup WHERE search_filter_id IS NULL OR search_filter_id=0");
    foreach($groups as $group)
        {
        $filtertext = trim($group["search_filter"]);
        if($filtertext == "")
            {
            continue;
            }
            
        // Migrate unless marked not to due to failure (flag will be reset if group is edited)
        $migrateresult = migrate_search_filter($filtertext);        
        if(is_numeric($migrateresult))
            {
            message_add(array_column($notification_users,"ref"), $lang["filter_search_success"] . ": '" . $filtertext . "'",generateURL($baseurl_short . "pages/admin/admin_group_management_edit.php",array("ref"=>$group["ref"])));
            // Successfully migrated - now use the new filter
            sql_query("UPDATE usergroup SET search_filter_id='" . $migrateresult . "' WHERE ref='" . $group["ref"] . "'");
            }
        elseif(is_array($migrateresult))
            {
            debug("FILTER MIGRATION: Error migrating filter: '" . $filtertext . "' - " . implode('\n' ,$migrateresult));
            // Error - set flag so as not to reattempt migration and notify admins of failure
            sql_query("UPDATE usergroup SET search_filter_id='-1' WHERE ref='" . $group["ref"] . "'");
                
            message_add(array_column($notification_users,"ref"), $lang["filter_migration"] . " - " . $lang["filter_search_error"] . ": <br />" . implode('\n' ,$migrateresult),generateURL($baseurl_short . "/pages/admin/admin_group_management_edit.php",array("ref"=>$usergroup)));
            }
        }
        
    $filterusers = sql_query("SELECT ref, search_filter_override, search_filter_o_id FROM user WHERE search_filter_o_id IS NULL OR search_filter_o_id=0");
    foreach($filterusers as $user)
        {
        $filtertext = trim($user["search_filter_override"]);
        if($filtertext == "")
            {
            continue;
            }
        // Migrate unless marked not to due to failure (flag will be reset if group is edited)
        $migrateresult = migrate_search_filter($filtertext);
        if(is_numeric($migrateresult))
            {
            message_add(array_column($notification_users,"ref"), $lang["filter_search_success"] . ": '" . $filtertext . "'",generateURL($baseurl_short . "/pages/admin/admin_group_management_edit.php",array("ref"=>$group["ref"])));
            // Successfully migrated - now use the new filter
            sql_query("UPDATE user SET search_filter_o_id='" . $migrateresult . "' WHERE ref='" . $user["ref"] . "'");
            }
        elseif(is_array($migrateresult))
            {
            debug("FILTER MIGRATION: Error migrating filter: '" . $filtertext . "' - " . implode('\n' ,$migrateresult));
            // Error - set flag so as not to reattempt migration and notify admins of failure
            sql_query("UPDATE user SET search_filter_o_id='-1' WHERE ref='" . $user["ref"] . "'");
            message_add(array_column($notification_users,"ref"), $lang["filter_migration"] . " - " . $lang["filter_search_error"] . ": <br />" . implode('\n' ,$migrateresult),generateURL($baseurl_short . "/pages/admin/admin_group_management_edit.php",array("ref"=>$usergroup)));
            }            
        }
    
    set_sysvar("SEARCH_FILTER_MIGRATION",1);
    }
    