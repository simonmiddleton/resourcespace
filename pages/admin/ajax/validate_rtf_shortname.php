<?php
include dirname(__FILE__) . '/../../../include/db.php';
include_once dirname(__FILE__) . '/../../../include/authenticate.php';
include_once dirname(__FILE__) . '/../../../include/ajax_functions.php';


$ref = getvalescaped("ref", 0, true);
if(!checkperm("a") || $ref == 0 || !metadata_field_view_access($ref))
    {
    ajax_permission_denied();
    }

$new_shortname = getvalescaped("new_shortname", "");
$rtf_data = get_resource_type_field($ref);
$duplicate = (boolean) sql_value("SELECT count(ref) AS `value` FROM resource_type_field WHERE `name` = '{$new_shortname}'", 0, "schema");

$is_synced_field = (
    (int) $rtf_data["sync_field"] > 0
    && (bool) sql_value("SELECT count(ref) AS `value` FROM resource_type_field WHERE ref = '{$rtf_data["sync_field"]}' OR sync_field = '{$rtf_data["ref"]}'", false, "schema")
);

$return["data"]["valid"] = true;
if($rtf_data["name"] != $new_shortname && $duplicate && !$is_synced_field)
    {
    $return["data"]["valid"] = false;
    }

echo json_encode($return);
exit();