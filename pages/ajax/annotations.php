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

$return        = array();

$action        = getvalescaped('action', '');
$resource      = getvalescaped('resource', 0, true);

// Get annotation data if an ID has been provided
$annotation_id = getvalescaped('annotation_id', 0, true);
if(0 < $annotation_id)
    {
    $annotation = getAnnotation($annotation_id);
    }

if('get_resource_annotations' == $action)
    {
    $return['data'] = getAnnotoriousResourceAnnotations($resource);
    }

/*if('add' == $action)
    {
    // 
    }*/

if('delete' == $action && isset($annotation))
    {
    $return['data'] = deleteAnnotation($annotation);
    }



// If by this point we still don't have a response for the request,
// create one now telling client code this is a bad request
if(0 === count($return))
    {
    $return['error'] = array(
        'status' => 400,
        'title'  => 'Bad Request',
        'detail' => 'The request could not be handled by annotations.php. This is the default response!');
    }

echo json_encode($return);
exit();