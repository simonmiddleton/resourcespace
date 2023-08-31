<?php
include dirname(__FILE__) . '/../../../include/db.php';
include_once dirname(__FILE__) . '/../../../include/authenticate.php';
include_once dirname(__FILE__) . '/../../../include/ajax_functions.php';


$ref = getval("ref", 0, true);
if(!checkperm("a") || $ref == 0 || !metadata_field_view_access($ref))
    {
    ajax_permission_denied();
    }

$new_shortname = getval("new_shortname", "");
$rtf_data = get_resource_type_field($ref);
$duplicate = (boolean) ps_value("SELECT count(ref) AS `value` FROM resource_type_field WHERE `name` = ?", array("s",$new_shortname),0, "schema");

$return["data"]["valid"] = $rtf_data["name"] != $new_shortname && !$duplicate;
echo json_encode($return);
exit();