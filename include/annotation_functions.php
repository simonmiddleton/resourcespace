<?php
/**
* Get annotation by ID
* 
* @param integer $ref Annotation ID
* 
* @return array
*/
function getAnnotation($ref)
    {
    if(0 >= $ref)
        {
        return array();
        }

    $ref = escape_check($ref);

    $return = sql_query("SELECT * FROM annotation WHERE ref = '{$ref}'");

    if(0 < count($return))
        {
        $return = $return[0];
        }

    return $return;
    }


/**
* Get annotations for a specific resource
* 
* @param integer $resource Resource ID
* 
* @return array
*/
function getResourceAnnotations($resource)
    {
    if(0 >= $resource)
        {
        return array();
        }

    $resource = escape_check($resource);

    return sql_query("SELECT * FROM annotation WHERE resource = '{$resource}'");
    }


/**
* Create an array of Annotorious annotation objects which can be JSON encoded and passed 
* directly to Annotorious
* 
* @param integer $resource Resource ID
* 
* @return array
*/
function getAnnotoriousResourceAnnotations($resource)
    {
    $annotations = array();

    /*
    Build an annotations array of Annotorious annotation objects

    IMPORTANT: until ResourceSpace will have text fields implemented as nodes, text should be left blank.
    Also, leave src empty as this is handled client side. This will allow us to have the same annotation 
    for multiple sizes of the same image (e.g: pre and scr)
    */
    foreach(getResourceAnnotations(escape_check($resource)) as $annotation)
        {
        $annotations[] = array(
                'src'    => '',
                'text'   => '',
                'shapes' => array(
                    array(
                        'type'     => 'rect',
                        'geometry' => array(
                            'x'      => (float) $annotation['x'],
                            'y'      => (float) $annotation['y'],
                            'width'  => (float) $annotation['width'],
                            'height' => (float) $annotation['height'],
                        )
                    )
                ),
                'editable' => annotationEditable($annotation),

                // Custom ResourceSpace properties for Annotation object
                'ref'                 => (int) $annotation['ref'],
                'resource'            => (int) $annotation['resource'],
                'resource_type_field' => (int) $annotation['resource_type_field'],
                'page'                => (int) $annotation['page'],
                'tags'                => getAnnotationTags($annotation),
            );
        }

    return $annotations;
    }


/**
* Check if an annotation can be editable (edit/ remove) by the user
* 
* @param array $annotation
* 
* @return boolean
*/
function annotationEditable(array $annotation)
    {
    global $userref;

    // TODO: add more checks
    /*
    User can edit an annotation if:
     - they are an admin
     - they created it
    */
    return (
            checkperm('a')
            || $userref == $annotation['user']
        );
    }


/**
* Get all tags of an annotation. Checks if a tag is attached to the resource,
* allowing the user to search by it which is represented by the virtual column
* "tag_searchable"
* 
* @param array $annotation
* 
* @return array
*/
function getAnnotationTags(array $annotation)
    {
    $resource_ref   = escape_check($annotation['resource']);
    $annotation_ref = escape_check($annotation['ref']);

    return sql_query("
            SELECT *,
                   (SELECT 'yes' FROM resource_node WHERE resource = '{$resource_ref}' AND node = ref) AS tag_searchable
              FROM node AS n
             WHERE ref IN (SELECT node FROM annotation_node WHERE annotation = '{$annotation_ref}');
        ");
    }


/**
* Delete annotation
* 
* @see getAnnotation()
* 
* @uses annotationEditable()
* @uses getAnnotationTags()
* @uses delete_resource_nodes()
* @uses db_begin_transaction()
* @uses db_end_transaction()
* 
* @param array $annotation Annotation array as returned by getAnnotation()
* 
* @return boolean
*/
function deleteAnnotation(array $annotation)
    {
    if(!annotationEditable($annotation))
        {
        return false;
        }

    $annotation_ref = escape_check($annotation['ref']);

    $nodes_to_remove = array();
    foreach(getAnnotationTags($annotation) as $tag)
        {
        $nodes_to_remove[] = $tag['ref'];
        }

    db_begin_transaction();

    if(0 < count($nodes_to_remove))
        {
        delete_resource_nodes(escape_check($annotation['resource']), $nodes_to_remove);
        }

    sql_query("DELETE FROM annotation_node WHERE annotation = '{$annotation_ref}'");
    sql_query("DELETE FROM annotation WHERE ref = '{$annotation_ref}'");

    db_end_transaction();

    return true;
    }


/**
* Create new annotations based on Annotorious annotation
* 
* NOTE: Annotorious annotation shape is an array but at the moment they use only the first shape found
* 
* @param array $annotation
* 
* @return boolean|integer Returns false on failure OR the ref of the newly created annotation
*/
function createAnnotation(array $annotation)
    {
    global $userref;

    // Annotorious annotation
    $x                   = escape_check($annotation['shapes'][0]['geometry']['x']);
    $y                   = escape_check($annotation['shapes'][0]['geometry']['y']);
    $width               = escape_check($annotation['shapes'][0]['geometry']['width']);
    $height              = escape_check($annotation['shapes'][0]['geometry']['height']);

    // ResourceSpace specific properties
    $resource            = escape_check($annotation['resource']);
    $resource_type_field = escape_check($annotation['resource_type_field']);
    $tags                = (isset($annotation['tags']) ? $annotation['tags'] : array());
    $page                = (isset($annotation['page']) && 0 < $annotation['page'] ? '\'' . escape_check($annotation['page']) . '\'' : 'NULL');

    $query = "INSERT INTO annotation (resource, resource_type_field, user, x, y, width, height, page)
                   VALUES ('{$resource}', '{$resource_type_field}', '{$userref}', '{$x}', '{$y}', '{$width}', '{$height}', {$page})";
    sql_query($query);

    $annotation_ref = sql_insert_id();

    if(0 == $annotation_ref)
        {
        return false;
        }

    // Add any tags associated with it
    if(0 < count($tags))
        {
        addAnnotationNodes($annotation_ref, $tags);
        add_resource_nodes($resource, array_column($tags, 'ref'));
        }

    return $annotation_ref;
    }


/**
* Update annotation and its tags if needed
* 
* @uses annotationEditable()
* @uses getAnnotationTags()
* @uses delete_resource_nodes()
* @uses addAnnotationNodes()
* @uses add_resource_nodes()
* @uses db_begin_transaction()
* @uses db_end_transaction()
* 
* @param array $annotation
* 
* @return boolean
*/
function updateAnnotation(array $annotation)
    {
    if(!isset($annotation['ref']) || !annotationEditable($annotation))
        {
        return false;
        }

    global $userref;

    // Annotorious annotation
    $x                   = escape_check($annotation['shapes'][0]['geometry']['x']);
    $y                   = escape_check($annotation['shapes'][0]['geometry']['y']);
    $width               = escape_check($annotation['shapes'][0]['geometry']['width']);
    $height              = escape_check($annotation['shapes'][0]['geometry']['height']);

    // ResourceSpace specific properties
    $annotation_ref      = escape_check($annotation['ref']);
    $resource            = escape_check($annotation['resource']);
    $resource_type_field = escape_check($annotation['resource_type_field']);
    $tags                = (isset($annotation['tags']) ? $annotation['tags'] : array());
    $page                = (isset($annotation['page']) ? '\'' . escape_check($annotation['page']) . '\'' : 'NULL');

    $update_query = "
        UPDATE annotation
           SET
               resource_type_field = '{$resource_type_field}',
               user = '{$userref}',
               x = '{$x}',
               y = '{$y}',
               width = '{$width}',
               height = '{$height}',
               page = {$page}
         WHERE ref = '{$annotation_ref}'";
    sql_query($update_query);

    // Add any tags associated with it
    if(0 < count($tags))
        {
        $nodes_to_remove = array();
        foreach(getAnnotationTags($annotation) as $tag)
            {
            $nodes_to_remove[] = $tag['ref'];
            }

        db_begin_transaction();

        // Delete existing associations
        if(0 < count($nodes_to_remove))
            {
            delete_resource_nodes($resource, $nodes_to_remove);
            }
        sql_query("DELETE FROM annotation_node WHERE annotation = '{$annotation_ref}'");

        // Add new associations
        addAnnotationNodes($annotation_ref, $tags);
        add_resource_nodes($resource, array_column($tags, 'ref'));

        db_end_transaction();
        }

    return true;
    }


/**
* Add relations between annotation and nodes
* 
* @param integer $annotation_ref
* @param array   $nodes
* 
* @return boolean
*/
function addAnnotationNodes($annotation_ref, array $nodes)
    {
    if(0 === count($nodes))
        {
        return false;
        }

    $query_insert_values = '';
    foreach($nodes as $node)
        {
        $query_insert_values .= ',(' . escape_check($annotation_ref) . ', ' . escape_check($node['ref']) . ')';
        }
    $query_insert_values = substr($query_insert_values, 1);

    sql_query("INSERT INTO annotation_node (annotation, node) VALUES  {$query_insert_values}");

    return true;
    }