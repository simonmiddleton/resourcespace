<?php
include_once __DIR__ . "/../../include/db.php";

$all_ugs = sql_query("SELECT ref, `name`, permissions FROM usergroup");
foreach($all_ugs as $ug)
    {
    logScript("Checking permissions: {$ug["permissions"]}");
    $permissions = trim_array(explode(",", $ug["permissions"]));

    $old_style_perms = array();
    foreach($permissions as $perm)
        {
        $matches = array();
        if(preg_match("/^(j\-?){1}(.+[^*])$/", $perm, $matches) === 1)
            {
            /* Examples of a match:
            Array
            (
                [0] => jFC1     --> actual permission (full regex match)
                [1] => j        --> permission type (j for root levels, "j-" for sub-categories which are also reverse permissions)
                [2] => FC1      --> the featured collection category branch path (separated by pipes "|")
            )
            Array
            (
                [0] => j-FC1|FC1/1.1|FC1/1.1/1.1.1
                [1] => j-
                [2] => FC1|FC1/1.1|FC1/1.1/1.1.1
            )
            */
            $old_style_perms[] = $matches;
            }
        }

    $update_permissions = false;
    foreach($old_style_perms as $j_perm)
        {
        $find_fc_by_name = explode("|", $j_perm[2]);
        $find_fc_by_name = trim(end($find_fc_by_name));

        if($find_fc_by_name == "")
            {
            logScript("Unable to determine the leaf node name for Featured Collection Category '{$j_perm[2]}'");
            continue;
            }

        $fc_categ_ref = sql_value(
            sprintf(
                "SELECT ref AS `value` FROM collection WHERE public = 1 AND `type`= %s AND `name` = '%s'",
                COLLECTION_TYPE_FEATURED,
                escape_check($find_fc_by_name)
            ),
            null);

        if(is_null($fc_categ_ref))
            {
            logScript("Unable to find Featured Collection Category named '{$j_perm}'");
            continue;
            }

        $found_branch_path = get_featured_collection_category_branch_by_leaf($fc_categ_ref, array());
        $branch_path_str = array_reduce($found_branch_path, function($carry, $item) { return "{$carry}|{$item["name"]}"; }, "");
        $branch_path_str = mb_substr($branch_path_str, 1, mb_strlen($branch_path_str));

        if($j_perm[2] != $branch_path_str)
            {
            logScript("Found a featured collection category but computed branch paths are different! Old permission has '{$j_perm[2]}' and new one has '{$branch_path_str}'. ResourceSpace might have found a similar named collection incorrectly!");
            continue;
            }

        // add the new permission format
        $new_fc_perm = ($j_perm[1] == "j" ? "" : "-") . "j{$fc_categ_ref}";
        $permissions[] = $new_fc_perm;

        // remove the old permission format
        $permissions = array_diff($permissions, array("{$j_perm[0]}"));

        $update_permissions = true;
        }

    if($update_permissions)
        {
        $permissions_str = join(",", $permissions);
        logScript("New permissions: " . $permissions_str);
        // sql_query(
        //     sprintf("UPDATE usergroup SET permissions = '%s' WHERE ref = '%s'",
        //     escape_check($permissions_str),
        //     escape_check($ug["ref"])));
        }
    }

echo PHP_EOL;