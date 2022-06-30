<?php
command_line_only();


include_once dirname(__FILE__, 3) . '/include/annotation_functions.php';

// Set up
$rtf_tags = create_resource_type_field('test_010402', 0, FIELD_TYPE_DYNAMIC_KEYWORDS_LIST);
$annotate_fields = [$rtf_tags];

$node_optionA = $node_optionB = [];
get_node(set_node(null, $rtf_tags, 'Option A', null, ''), $node_optionA);
get_node(set_node(null, $rtf_tags, 'Option B', null, ''), $node_optionB);

$resource_ref = create_resource(1, 0);

$annotorious_annotation = [
    'resource' => $resource_ref,
    'resource_type_field' => $rtf_tags,
    'page' => 0,
    'tags' => [$node_optionA],
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
$annotation_ref = createAnnotation($annotorious_annotation);

$annotation = $annotorious_annotation;
$annotation['tags'] = [$node_optionB];



// Update tags but missing its ID
if(updateAnnotation($annotation) !== false)
    {
    echo 'updateAnnotation (no ref) - ';
    return false;
    }


// Update tags for an existing annotation
$annotation['ref'] = $annotation_ref;
if(updateAnnotation($annotation) === false)
    {
    echo 'updateAnnotation - ';
    return false;
    }



// Tear down
unset($rtf_tags, $node_optionA, $node_optionB, $resource_ref, $annotorious_annotation, $annotation);

return true;