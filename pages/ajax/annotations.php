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

$action   = getvalescaped('action', '');
$resource = getvalescaped('resource', 0, true);
$page     = getvalescaped('page', 0, true);

// Get annotation data if an ID has been provided
$annotation_id = getvalescaped('annotation_id', 0, true);
$annotation    = getval('annotation', array());
if(0 < $annotation_id)
    {
    $annotation = getAnnotation($annotation_id);
    }

if('get_resource_annotations' == $action)
    {
    $return['data'] = getAnnotoriousResourceAnnotations($resource, $page);
    }

// Create new annotation
if('create' == $action && 0 < $resource)
    {
    if(0 === count($annotation))
        {
        $return['error'] = array(
            'status' => 400,
            'title'  => 'Bad Request',
            'detail' => 'ResourceSpace expects an annotation object');

        echo json_encode($return);
        exit();
        }

    $annotation_id = createAnnotation($annotation);

    if(false === $annotation_id)
        {
        $return['error'] = array(
            'status' => 500,
            'title'  => 'Internal Server Error',
            'detail' => 'ResourceSpace was not able to create the annotation.');

        echo json_encode($return);
        exit();
        }

    $return['data'] = $annotation_id;
    }

// Update annotation
if('update' == $action && 0 < $resource)
    {
    if(0 === count($annotation))
        {
        $return['error'] = array(
            'status' => 400,
            'title'  => 'Bad Request',
            'detail' => 'ResourceSpace expects an annotation object');

        echo json_encode($return);
        exit();
        }

    $return['data'] = updateAnnotation($annotation);
    }

// Delete annotation
if('delete' == $action && 0 < $annotation_id && 0 !== count($annotation))
    {
    $return['data'] = deleteAnnotation($annotation);
    }

// Get available fields (white listed) for annotations
if('get_allowed_fields' == $action)
    {
    foreach($annotate_fields as $annotate_field)
        {
        $field_data = get_resource_type_field($annotate_field);

        // Make sure user has access to this field
        if((checkperm('f*') || checkperm("f{$annotate_field}"))
            && !checkperm("f-{$annotate_field}")
        )
            {
            $field_data['title'] = i18n_get_translated($field_data['title']);

            $return['data'][] = $field_data;
            }
        }

    if(!isset($return['data']))
        {
        $return['error'] = array(
            'status' => 404,
            'title'  => 'Not Found',
            'detail' => '$annotate_fields config option does not have any fields set (i.e. it is empty)');

        echo json_encode($return);
        exit();
        }
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