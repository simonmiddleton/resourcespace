<?php
include dirname(__DIR__, 3) . '/include/boot.php';
$k = getval('k','');
$upload_collection = getval('upload_share_active','');
if ($k=="" || (!check_access_key_collection($upload_collection,$k))) {
    include dirname(__DIR__, 3) . '/include/authenticate.php';
}
$field    = getval('field', '');
$keyword  = getval('term', '');
$readonly = ('' != getval('readonly', '') ? true : false);

$results = suggest_dynamic_keyword_nodes($field, $keyword, $readonly);

// We return an array of objects with label and value properties: [ { label: "Node ID 1 - option name", value: "101" }, ... ]
// This will later be used by jQuery autocomplete
echo json_encode($results);
exit();