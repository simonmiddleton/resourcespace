<?php
command_line_only();

$resource_types = get_resource_types();
$photo_exif_fields = get_exiftool_fields(1);
$doc_exif_fields = get_exiftool_fields(2);

// Create new photo only resource type field mapping
$fieldcolumns = get_resource_type_field_columns();
$savecolumns = array_filter($fieldcolumns,function($v,$k){return $k=="exiftool_field";},ARRAY_FILTER_USE_BOTH);
$newfield = create_resource_type_field("test 260 field",1,FIELD_TYPE_TEXT_BOX_SINGLE_LINE,"260test");
save_resource_type_field($newfield,$savecolumns,["exiftool_field"=>"test260exifmapping"]);

$use_cases = [
    [
        'name' => 'Title field not returning expected mapping',
        'resource_types' => array_column($resource_types,"ref"),
        'expected_mappings' => [[8,"IPTC:ObjectName,XMP:title"]],
    ],
    [
        'name' => 'Fields not returning expected mapping',
        'resource_types' => [1],
        'expected_mappings' => [
                                [$newfield,"test260exifmapping"],
                                [87,"ExtDescrAccessibility"]
                            ],
        'expectedcount' => count($photo_exif_fields) + 1,
    ],
    [
        'name' => 'Check no additional document mappings are returned',
        'resource_types' => 2,
        'expectedcount' => count($doc_exif_fields),
    ],
];

foreach ($use_cases as $uc)
    {
    $exif_fields = get_exiftool_fields($uc["resource_types"]);
    if(isset($uc['expectedcount']) && count($exif_fields) != $uc['expectedcount'])
        {    
        echo "Use case: {$uc['name']} - invalid count: " . count($exif_fields) . ", expected {$uc['expectedcount']} ";
        return false;
        }

    if(isset($uc['expected_mappings']))
        {
        foreach($uc['expected_mappings'] as  $expected_mapping)
            {
            $idx_field = array_search($expected_mapping[0],array_column($exif_fields,"ref"));
            if($exif_fields[$idx_field]["exiftool_field"] != $expected_mapping[1])
                {
                echo "Use case: {$uc['name']} - invalid mapping for field#{$expected_mapping[0]} {$exif_fields[$idx_field]["exiftool_field"]}, expected {$expected_mapping} ";
                return false;
                }
            }
        }
    }

delete_resource_type_field($newfield);