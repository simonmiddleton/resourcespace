<?php
include dirname(__FILE__) . '/../../include/db.php';
include_once dirname(__FILE__) . '/../../include/general.php';
include dirname(__FILE__) . '/../../include/authenticate.php';
include_once dirname(__FILE__) . '/../../include/node_functions.php';
include_once dirname(__FILE__) . '/../../include/annotation_functions.php';

if(!$annotate_enabled)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit($lang['error-permissiondenied']);
    }

$return   = array();
$resource = getvalescaped('resource', 0, true);

$return = getAnnotoriousResourceAnnotations($resource);

echo json_encode($return);
exit();