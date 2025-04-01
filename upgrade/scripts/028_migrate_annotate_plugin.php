<?php

include dirname(__FILE__, 3) . '/include/boot.php'; #todo: remove once done dev/test
// include RESOURCESPACE_BASE_PATH . '/include/boot.php'; #todo: remove once done dev/test
command_line_only(); #todo: remove once done dev/test

if (!is_plugin_activated('annotate')) {
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Annotate plugin is disabled! Skipping...');
    return;
}

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Start migrating the Annotate plugin to the base annotations');

$annotations = ps_query(
    sprintf(
        'SELECT an.ref AS `resource`, n.ref, %s, %s FROM annotate_notes AS an LEFT JOIN node AS n ON an.node = n.ref',
        columns_in('annotate_notes', 'an', 'annotate'),
        columns_in('node', 'n')
    )
);


foreach ($annotations as $annotation) {
    print_r($annotation);

    // Parse the old "username: note" syntax
    $an_noteparts = explode(": ", $annotation["name"] ?? "");

    echo "name: '" . $annotation["name"] . "'" . PHP_EOL;
    $an_username = $an_noteparts[0];
    echo "user: '" . $an_username . "'" . PHP_EOL;
    $an_note = $an_noteparts[1] ?? "";
    $an_userid = get_user_by_username($an_username);
    echo "userid: '" . $an_userid . "'" . PHP_EOL;
    if ($an_userid === false) {
        // No matching user found - maybe username has changed. Keep original note
        $an_userid = 0;
        $an_note = $annotation["name"];
    }

    $annotorious_annotation = [
        'resource' => $annotation['resource'],
        'resource_type_field' => $annotation['resource_type_field'],
        'page' => (int) $annotation['page'] > 1 ? $annotation['page'] : null,
        'tags' => [], # optional
        'shapes' => [
            [
                'type' => 'rect',
                'geometry' => [
                    'x' => (int) $annotation['left_pos'] / (int) $annotation['preview_width'],
                    'y' => (int) $annotation['top_pos'] / (int) $annotation['preview_height'],
                    'width' => (int) $annotation['width'] / (int) $annotation['preview_width'],
                    'height' => (int) $annotation['height'] / (int) $annotation['preview_height'],
                ],
            ]
        ],
    ];

print_r($annotorious_annotation);
die(PHP_EOL . 'Process stopped in file ' . __FILE__ . ' at line ' . __LINE__ . PHP_EOL);
}

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Finished migrating the Annotate plugin to the base annotations!');
