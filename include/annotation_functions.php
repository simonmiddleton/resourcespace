<?php
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

    // Build an annotations array of Annotorious annotation objects
    // IMPORTANT: until ResourceSpace will have text fields implemented as nodes, text should be left blank
    foreach(getResourceAnnotations(escape_check($resource)) as $annotation)
        {
        $annotations[] = array(
                'src'    => $annotation['src'],
                'text'   => '',
                'shapes' => array(
                    array(
                        'type'     => 'rect',
                        'units'    => 'pixel',
                        'geometry' => array(
                            'x'      => (int) $annotation['x'],
                            'y'      => (int) $annotation['y'],
                            'width'  => (int) $annotation['width'],
                            'height' => (int) $annotation['height'],
                        )
                    )
                ),
                'editable' => annotationEditable($annotation),
                // Note: tags is a custom property of Annotation object
                'tags' => getAnnotationTags($annotation),
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