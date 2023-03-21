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

    $return = ps_query("SELECT " . columns_in("annotation") . " FROM annotation WHERE ref = ?", array("i",$ref));

    if(0 < count($return))
        {
        $return = $return[0];
        }

    return $return;
    }


/**
* General annotations search functionality
* 
* @uses ps_query()
* 
* @param integer $resource
* @param integer $resource_type_field
* @param integer $user
* @param integer $page
* 
* @return array
*/
function getAnnotations($resource = 0, $resource_type_field = 0, $user = 0, $page = 0)
    {
    if(!is_numeric($resource) || !is_numeric($resource_type_field) || !is_numeric($user) || !is_numeric($page))
        {
        return array();
        }

    $sql_where_clause    = '';
    $parameters=array();
    
    if(0 < $resource)
        {
        $sql_where_clause = " resource = ?";
        $parameters = array("i",$resource);
        }

    if(0 < $resource_type_field)
        {
        if('' != $sql_where_clause)
            {
            $sql_where_clause .= ' AND';
            }

        $sql_where_clause .= " resource_type_field = ?";
        $parameters=array_merge($parameters,array("i",$resource_type_field));
        }

    if(0 < $user)
        {
        if('' != $sql_where_clause)
            {
            $sql_where_clause .= ' AND';
            }

        $sql_where_clause .= " user = ?";
        $parameters=array_merge($parameters,array("i",$user));
        }

    if(0 < $page)
        {
        if('' != $sql_where_clause)
            {
            $sql_where_clause .= ' AND';
            }

        $sql_where_clause .= " page = ?";
        $parameters=array_merge($parameters,array("i",$page));
        }

    if('' != $sql_where_clause)
        {
        $sql_where_clause = "WHERE {$sql_where_clause}";
        }

        return ps_query("SELECT " . columns_in("annotation") . " FROM annotation {$sql_where_clause}",$parameters);
    }


/**
* Get number of annotations available for a resource.
* 
* Note: multi page resources will show the total number (ie. all pages)
* 
* @uses ps_value()
* 
* @param integer $resource Resource ID
* 
* @return integer
*/
function getResourceAnnotationsCount($resource)
    {
    if(!is_numeric($resource) || 0 >= $resource)
        {
        return 0;
        }

    return (int) ps_value("SELECT count(ref) AS `value` FROM annotation WHERE resource = ?",array("i",$resource), 0);
    }


/**
* Get annotations for a specific resource
* 
* @param integer $resource Resource ID
* @param integer $page     Page number of a document. Non documents will have 0
* 
* @return array
*/
function getResourceAnnotations($resource, $page = 0)
    {
    if(0 >= $resource)
        {
        return array();
        }

    $parameters=array("i",$resource);
    $sql_page_filter = 'AND `page` IS NULL';

    if(0 < $page)
        {
        $sql_page_filter = "AND `page` IS NOT NULL AND `page` = ?";
        $parameters=array_merge($parameters, array("i",$page));
        }

    return ps_query("SELECT " . columns_in("annotation") . " FROM annotation WHERE resource = ? {$sql_page_filter}", $parameters);
    }


/**
* Create an array of Annotorious annotation objects which can be JSON encoded and passed 
* directly to Annotorious
* 
* @param integer $resource Resource ID
* @param integer $page     Page number of a document
* 
* @return array
*/
function getAnnotoriousResourceAnnotations($resource, $page = 0)
    {
    global $baseurl;

    $annotations = array();
    $can_view_fields = canSeeAnnotationsFields();

    /*
    Build an annotations array of Annotorious annotation objects

    IMPORTANT: until ResourceSpace will have text fields implemented as nodes, text should be left blank.
    
    NOTE: src attribute is generated per resource (dummy source) to avoid issues when source is
    loaded from download.php
    */
    foreach(getResourceAnnotations($resource, $page) as $annotation)
        {
        if(in_array($annotation['resource_type_field'], $can_view_fields))
            {
            $annotations[] = array(
                'src'    => "{$baseurl}/annotation/resource/{$resource}",
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
        }

    return $annotations;
    }


/**
* Check if an annotation can be editable (add/ edit/ remove) by the user
* 
* @uses checkPermission_anonymoususer()
* 
* @param array $annotation
* 
* @return boolean
*/
function annotationEditable(array $annotation)
    {
    debug(sprintf('[annotations][fct=annotationEditable] $annotation = %s', json_encode($annotation)));
    global $userref;

    $add_operation = !isset($annotation['user']);
    $field_edit_access = metadata_field_edit_access($annotation['resource_type_field']);

    /* Non-admin edit authorisation is valid when:
        - user is just adding a new annotation
        - when editing/removing an existing annotation, the annotation was created by the user itself
    */
    $non_admin_athz = ($add_operation || $userref == $annotation['user']);

    // Anonymous users cannot edit by default. They can only edit if they are allowed CRUD operations
    if(checkPermission_anonymoususer())
        {
        return $non_admin_athz && $field_edit_access;
        }

    return (checkperm('a') || $non_admin_athz) && $field_edit_access;
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
    $resource_ref   = $annotation['resource'];
    $annotation_ref = $annotation['ref'];

    $parameters=array("i", $resource_ref, "i", $annotation_ref);
    return ps_query("
            SELECT " . columns_in("node","n") . ",
                   (SELECT 'yes' FROM resource_node WHERE resource = ? AND node = ref) AS tag_searchable
              FROM node AS n
             WHERE ref IN (SELECT node FROM annotation_node WHERE annotation = ?);", $parameters);
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

    $annotation_ref = $annotation['ref'];
    $parameters=array("i",$annotation_ref);

    $nodes_to_remove = array();
    foreach(getAnnotationTags($annotation) as $tag)
        {
        $nodes_to_remove[] = $tag['ref'];
        }

    db_begin_transaction("deleteAnnotation");

    if(0 < count($nodes_to_remove))
        {
        delete_resource_nodes($annotation['resource'], $nodes_to_remove);
        }

    ps_query("DELETE FROM annotation_node WHERE annotation = ?", $parameters);
    ps_query("DELETE FROM annotation WHERE ref = ?", $parameters);
        
    db_end_transaction("deleteAnnotation");

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
    debug(sprintf('[annotations][fct=createAnnotation] Param $annotation = %s', json_encode($annotation)));
    global $userref;

    if(!annotationEditable($annotation))
        {
        debug('[annotations][fct=createAnnotation][warn] annotation not editable');
        return false;
        }
    debug('[annotations][fct=createAnnotation] attempting to create annotation...');

    // ResourceSpace specific properties
    $resource            = $annotation['resource'];
    $resource_type_field = $annotation['resource_type_field'];
    $page                = (isset($annotation['page']) && 0 < $annotation['page'] ? $annotation['page'] : null);
    $tags                = $annotation['tags'] ?? [];

    // Annotorious annotation
    $x      = $annotation['shapes'][0]['geometry']['x'];
    $y      = $annotation['shapes'][0]['geometry']['y'];
    $width  = $annotation['shapes'][0]['geometry']['width'];
    $height = $annotation['shapes'][0]['geometry']['height'];

    ps_query(
        'INSERT INTO annotation (resource, resource_type_field, user, x, y, width, height, page) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            'i', $resource,
            'i', $resource_type_field,
            'i', $userref,
            'd', $x,
            'd', $y,
            'd', $width,
            'd', $height,
            'i', $page,
        ]
    );
    $annotation_ref = sql_insert_id();
    debug('[annotations][fct=createAnnotation] annotation_ref = ' . json_encode($annotation_ref));

    if(0 == $annotation_ref)
        {
        debug('[annotations][fct=createAnnotation][warn] Unable to create annotation');
        return false;
        }

    // Prepare tags before association by adding new nodes to dynamic keywords list (if permissions allow it)
    $prepared_tags = prepareTags($tags);

    // Add any tags associated with it
    if(0 < count($tags))
        {
        addAnnotationNodes($annotation_ref, $prepared_tags);
        add_resource_nodes($resource, array_column($prepared_tags, 'ref'), false);
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
    debug(sprintf('[annotations][fct=updateAnnotation] Param $annotation = %s', json_encode($annotation)));
    if(!isset($annotation['ref']) || !annotationEditable($annotation))
        {
        return false;
        }

    global $userref;

    // ResourceSpace specific properties
    $annotation_ref      = $annotation['ref'];
    $resource_type_field = $annotation['resource_type_field'];
    $resource            = $annotation['resource'];
    $page                = (isset($annotation['page']) && 0 < $annotation['page'] ? $annotation['page'] : null);
    $tags                = $annotation['tags'] ?? [];

    // Annotorious annotation
    $x      = $annotation['shapes'][0]['geometry']['x'];
    $y      = $annotation['shapes'][0]['geometry']['y'];
    $width  = $annotation['shapes'][0]['geometry']['width'];
    $height = $annotation['shapes'][0]['geometry']['height'];

    ps_query(
        'UPDATE annotation SET resource_type_field = ?, user = ?, x = ?, y = ?, width = ?, height = ?, page = ? WHERE ref = ?',
        [
            'i', $resource_type_field,
            'i', $userref,
            'd', $x,
            'd', $y,
            'd', $width,
            'd', $height,
            'i', $page,
            'i', $annotation_ref,
        ]
    );

    // Delete existing associations
    $nodes_to_remove = array();
    foreach(getAnnotationTags($annotation) as $tag)
        {
        $nodes_to_remove[] = $tag['ref'];
        }

    db_begin_transaction("updateAnnotation");

    if(0 < count($nodes_to_remove))
        {
        delete_resource_nodes($resource, $nodes_to_remove);
        }
    
    ps_query("DELETE FROM annotation_node WHERE annotation = ?", ['i', $annotation_ref]);

    // Add any tags associated with this annotation
    if(0 < count($tags))
        {
        // Prepare tags before association by adding new nodes to 
        // dynamic keywords list (if permissions allow it)
        $prepared_tags = prepareTags($tags);

        // Add new associations
        addAnnotationNodes($annotation_ref, $prepared_tags);
        add_resource_nodes($resource, array_column($prepared_tags, 'ref'), false);
        }

    db_end_transaction("updateAnnotation");

    return true;
    }


/**
* Add relations between annotation and nodes
* 
* @param integer $annotation_ref The annotation ID in ResourceSpace
* @param array   $nodes          List of node structures {@see get_nodes()}. Only the "ref" property is required.
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
    $parameters = [];

    foreach($nodes as $node)
        {
        $query_insert_values .= ',(?, ?)';
        $parameters = array_merge($parameters, ['i', $annotation_ref, 'i', $node['ref']]);
        }
    $query_insert_values = substr($query_insert_values, 1);

    ps_query("INSERT INTO annotation_node (annotation, node) VALUES  {$query_insert_values}", $parameters);

    return true;
    }


/**
* Utility function which allows annotation tags to be prepared (i.e make sure they are all valid nodes)
* before creating associations between annotations and tags
* 
* @uses checkperm()
* @uses get_resource_type_field()
* @uses set_node()
* @uses get_node()
* 
* @param array $dirty_tags Original array of tags. These can be (in)valid tags/ new tags.
*                          IMPORTANT: a tag should have the same structure as a node
* 
* @return array
*/
function prepareTags(array $dirty_tags)
    {
    if(0 === count($dirty_tags))
        {
        return array();
        }

    global $annotate_fields;

    $clean_tags = array();

    foreach($dirty_tags as $dirty_tag)
        {
        // Check minimum required information for a node
        if(!isset($dirty_tag['resource_type_field'])
            || 0 >= $dirty_tag['resource_type_field']
            || !in_array($dirty_tag['resource_type_field'], $annotate_fields)
        )
            {
            continue;
            }

        if((!isset($dirty_tag['name']) || '' == $dirty_tag['name']))
            {
            continue;
            }

        // No access to field? Next...
        if(!metadata_field_view_access($dirty_tag['resource_type_field']))
            {
            continue;
            }

        // New node?
        if(is_null($dirty_tag['ref']) || (is_string($dirty_tag['ref']) && '' == $dirty_tag['ref']))
            {
            $dirty_field_data = get_resource_type_field($dirty_tag['resource_type_field']);

            // Only dynamic keywords lists are allowed to create new options from annotations if permission allows it
            if(
                !(
                    FIELD_TYPE_DYNAMIC_KEYWORDS_LIST == $dirty_field_data['type'] 
                    && !checkperm("bdk{$dirty_tag['resource_type_field']}")
                )
            )
                {
                continue;
                }

            // Create new node but avoid duplicates
            $new_node_id = set_node(null, $dirty_tag['resource_type_field'], $dirty_tag['name'], null, null);
            if(false !== $new_node_id && is_numeric($new_node_id))
                {
                $dirty_tag['ref'] = $new_node_id;

                $clean_tags[] = $dirty_tag;
                }

            continue;
            }

        // Existing tags
        $found_node = array();
        if(get_node((int) $dirty_tag['ref'], $found_node))
            {
            if($found_node['resource_type_field'] == $dirty_tag['resource_type_field'])
                {
                $clean_tags[] = $found_node;
                }
            }
        }

    return $clean_tags;
    }