<?php
set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Start download_filename_format configuration upgrade...');

/*
Reference only, the deprecated configs defaults (now in config.deprecated), grouped by how they relate:

$prefix_resource_id_to_filename=true;
$prefix_filename_string="RS";

$original_filenames_when_downloading=true;

$download_filename_id_only = false;
$download_id_only_with_size = false;

// $download_filename_field=8;
$download_filenames_without_size = false;
*/
$build_download_filename_format = function(): string
    {
    $format_parts = [
        $GLOBALS['prefix_filename_string'],
    ];

    $filename_parts = [];
    $add_separator = $resource_added = false;
    if ($GLOBALS['original_filenames_when_downloading'])
        {
        $add_separator = true;
        $filename_parts[] = '%filename';
        if(!$GLOBALS['download_filenames_without_size'])
            {
            $filename_parts[] = '%size';
            }
        $filename_parts[] = '.%extension';
        }
    elseif ($GLOBALS['download_filename_id_only'])
        {
        $resource_added = true;
        $filename_parts[] = '%resource';
        if($GLOBALS['download_id_only_with_size'])
            {
            $filename_parts[] = '%size';
            }
        $filename_parts[] = '.%extension';
        }
    elseif (isset($GLOBALS['download_filename_field']))
        {
        $add_separator = true;
        $filename_parts[] = "%field{$GLOBALS['download_filename_field']}";
        if(!$GLOBALS['download_filenames_without_size'])
            {
            $filename_parts[] = '%size';
            }
        $filename_parts[] = '.%extension';
        }
    else
        {
        if(!$GLOBALS['download_filenames_without_size'])
            {
            $filename_parts[] = '%size';
            }
        $filename_parts[] = '%alternative';
        $filename_parts[] = '.%extension';
        }

    if (!$resource_added && $GLOBALS['prefix_resource_id_to_filename'])
        {
        $format_parts[] = '%resource' . ($add_separator ? '_' : '');
        }
    
    return implode('', array_merge($format_parts, $filename_parts));
    };

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Processing system wide config...');
$system_wide_cfg_msg = [];
$system_wide_dld_filename_format = $build_download_filename_format();
if (set_config_option(null, 'download_filename_format', $system_wide_dld_filename_format))
    {
    set_sysvar(
        SYSVAR_UPGRADE_PROGRESS_SCRIPT,
        "Set download_filename_format configuration: {$system_wide_dld_filename_format}"
    );
    }
else
    {
    $system_wide_cfg_msg[] = str_replace(
        '%format%',
        $system_wide_dld_filename_format,
        $lang['upgrade_026_error_unable_to_set_config_system_wide']
    );
    }

// Override only the options that have been deprecated
$deprecated_options = [
    'prefix_resource_id_to_filename' => $GLOBALS['prefix_resource_id_to_filename'],
    'prefix_filename_string' => $GLOBALS['prefix_filename_string'],
    'original_filenames_when_downloading' => $GLOBALS['original_filenames_when_downloading'],
    'download_filename_id_only' => $GLOBALS['download_filename_id_only'],
    'download_id_only_with_size' => $GLOBALS['download_id_only_with_size'],
    'download_filenames_without_size' => $GLOBALS['download_filenames_without_size'],
];
if (isset($GLOBALS['download_filename_field']))
    {
    $deprecated_options['download_filename_field'] = $GLOBALS['download_filename_field'];
    }

$process_config_overrides = function(array $rows, string $what)
    use ($lang, $build_download_filename_format, $deprecated_options): array
    {
    $messages = [];
    foreach($rows as $row)
        {
        set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, sprintf("%s: %s", ucfirst($what), $row['name']));
        $config_options = trim((string) $row['config_options']);
        if ($config_options === '')
            {
            continue;
            }

        override_rs_variables_by_eval($deprecated_options, $config_options);
        $messages[] = str_replace(
            ['%entity%', '%format%'],
            [
                sprintf("%s (%s)", $row['name'], mb_strtolower($what)),
                $build_download_filename_format()
            ],
            $lang['upgrade_026_notification']
        );
        }
    return $messages;
    };

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Processing user group config overrides...');
$ug_msg = $process_config_overrides(get_usergroups(), $lang['user_group']);

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Processing resource type config overrides...');
$rt_msg = $process_config_overrides(get_resource_types('', true, true, false), $lang['property-resource_type']);

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Notify admins');
$upgrade_26_admin_messages = array_filter(array_merge($system_wide_cfg_msg, $ug_msg, $rt_msg));
$notification_users = array_column(get_notification_users('SYSTEM_ADMIN'), 'ref');
foreach ($upgrade_26_admin_messages as $msg)
    {
    message_add(
        $notification_users,
        "{$lang['upgrade_script']} #026: {$msg}",
        '',
        null,
        MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,
        MESSAGE_DEFAULT_TTL_SECONDS
    );
    }

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Finished download_filename_format configuration upgrade!');
