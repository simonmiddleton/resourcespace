<?php
include dirname(__FILE__) . '/../../../include/db.php';
include_once dirname(__FILE__) . '/../../../include/general.php';
include dirname(__FILE__) . '/../../../include/authenticate.php';
include_once dirname(__FILE__) . '/../../../include/resource_functions.php';
include_once dirname(__FILE__) . '/../../../include/node_functions.php';

$field   = getvalescaped('field', '');
$keyword = getvalescaped('keyword', '');

if(!checkperm('bdk' . $field))
    {
    set_node(null, $field, $keyword, null, null);
    }