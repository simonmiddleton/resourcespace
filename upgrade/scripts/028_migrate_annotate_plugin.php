<?php

include dirname(__FILE__, 3) . '/include/boot.php'; #todo: remove once done dev/test
require_once dirname(__FILE__, 3) . '/include/annotation_functions.php';
command_line_only(); #todo: remove once done dev/test

if (!is_plugin_activated('annotate')) {
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Annotate plugin is disabled. Skipping...');
    return;
}

$plugin_config = get_plugin_config('annotate');
if ($plugin_config === null) {
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Annotate plugin was not configured. Skipping...');
    return;
}

// Migrate plugin settings
set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Migrating the Annotate plugin settings');
$set_system_wide_config = ['annotate_enabled' => 1];

if (
    isset($plugin_config['annotate_resource_type_field'])
    && $plugin_config['annotate_resource_type_field'] > 0
    && !in_array($plugin_config['annotate_resource_type_field'], $annotate_fields)
    && in_array(
        $plugin_config['annotate_resource_type_field'],
        array_column(get_resource_type_fields('', 'ref', 'asc', '', get_valid_annotate_field_types(), false), 'ref')
    )
) {
    $annotate_fields[] = $plugin_config['annotate_resource_type_field'];
    $set_system_wide_config['annotate_fields'] = implode(',', $annotate_fields);
}

if (isset($plugin_config['annotate_public_view'])) {
    $set_system_wide_config['annotate_public_view'] = (int) $plugin_config['annotate_public_view'];
}

if (isset($plugin_config['annotate_show_author'])) {
    $set_system_wide_config['annotate_show_author'] = (int) $plugin_config['annotate_show_author'];
}

/*
todo: migrate the following settings
- $annotate_exclude_restypes
*/
print_r($plugin_config);

$sysadmin_users = array_column(get_notification_users('SYSTEM_ADMIN'), 'ref');
foreach ($set_system_wide_config as $name => $value) {
    if (!set_config_option(null, $name, $value)) {
        message_add(
            $sysadmin_users,
            sprintf(
                '%s #028: %s',
                $lang['upgrade_script'],
                str_replace(['%name%', '%value%'], [$name, $value], $lang['upgrade_028_notify_config_not_set'])
            ),
            "{$baseurl}/pages/admin/admin_system_config.php",
            null,
            MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,
            MESSAGE_DEFAULT_TTL_SECONDS
        );
    }
}
die(PHP_EOL.'Process stopped in file ' . __FILE__ . ' at line ' . __LINE__.PHP_EOL); #todo: drop once done

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Migrating the Annotate plugin records as base annotations');
$annotations = ps_query(
    sprintf(
        'SELECT an.ref AS `resource`, n.ref, %s, %s FROM annotate_notes AS an LEFT JOIN node AS n ON an.node = n.ref',
        columns_in('annotate_notes', 'an', 'annotate'),
        columns_in('node', 'n')
    )
);
foreach ($annotations as $annotation) {
    set_sysvar(
        SYSVAR_UPGRADE_PROGRESS_SCRIPT,
        "Processing annotation for resource #{$annotation['resource']} with node #{$annotation['node']}"
    );

    // Parse the old "username: note" syntax
    $parsed_annotation = explode(': ', (string) $annotation['name']);
    if (count($parsed_annotation) !== 2) {
        // todo: reconsider this logic
        debug("Invalid annotation note (node) syntax - {$annotation['name']}");
        continue;
    }
    [$an_username, $an_note] = $parsed_annotation;

    logScript("- Note = {$annotation['name']}");
    logScript("- an_username = {$an_username}");
    logScript("- an_note = {$an_note}");

    $an_userref = get_user_by_username($an_username);
    logScript('- user ID = ' . json_encode($an_userref));
    if ($an_userref === false) {
        // No matching user found - maybe username has changed. Keep original note
        $an_userref = 0;
        $an_note = $annotation['name'];
    }

    db_begin_transaction('upgrade_28');
    ps_query(
        'INSERT INTO annotation (`resource`, resource_type_field, user, x, y, width, height, `page`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            'i', $annotation['resource'],
            'i', $annotation['resource_type_field'],
            'i', $an_userref,
            'd', (int) $annotation['left_pos'] / (int) $annotation['preview_width'],
            'd', (int) $annotation['top_pos'] / (int) $annotation['preview_height'],
            'd', (int) $annotation['width'] / (int) $annotation['preview_width'],
            'd', (int) $annotation['height'] / (int) $annotation['preview_height'],
            'i', (int) $annotation['page'] > 1 ? $annotation['page'] : null,
        ]
    );
    $new_annotation_ref = sql_insert_id();

    // Add/update annotation_node record
    ps_query(
        'INSERT IGNORE INTO annotation_node (annotation, node) VALUES (?, ?)',
        ['i', $new_annotation_ref, 'i', $annotation['node']]
    );
    set_node($annotation['node'], $annotation['resource_type_field'], $an_note, $annotation['parent'], $annotation['order_by']);
    db_end_transaction('upgrade_28');
}

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Uninstalling the Annotate plugin');
deactivate_plugin('annotate');

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Finished migrating the Annotate plugin to the base annotations!');
