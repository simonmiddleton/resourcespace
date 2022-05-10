<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

include_once dirname(__FILE__, 3) . '/include/annotation_functions.php';


// Set up
$annotate_fields = [3]; # Make Country an annotateable field
$resource_ref = create_resource(1, 0);
$uk = get_node_by_name(get_nodes(3), 'United Kingdom', false);
$annotorious_annotation = [
    'resource' => $resource_ref,
    'resource_type_field' => 3,
    'page' => 0, # optional
    'tags' => [], # optional
    'shapes' => [
        [
            'type' => 'rect',
            'geometry' => [
                'x' => 0.10,
                'y' => 0.20,
                'width' => 0.50,
                'height' => 0.60,
            ],
        ]
    ],
];



// Simple use
$annotation_ref = createAnnotation($annotorious_annotation);
if($annotation_ref === false)
    {
    echo 'Unable to create annotation - ';
    return false;
    }

// Create annotation with tags
$annotation['tags'] = [$uk];
$annotation_ref = createAnnotation($annotorious_annotation);
if($annotation_ref === false)
    {
    echo 'Annotation w/ tags - ';
    return false;
    }


// Read-only mode should fail to create new annotations
$annotate_read_only = true;
$annotation_ref = createAnnotation($annotorious_annotation);
if($annotation_ref !== false)
    {
    echo 'Read-only mode - ';
    return false;
    }



// Tear down
unset($annotate_fields, $resource_ref, $uk, $annotorious_annotation, $annotation_ref, $annotate_read_only);

return true;