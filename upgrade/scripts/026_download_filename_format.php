<?php
if(PHP_SAPI == 'cli')
    {
    include_once __DIR__ . "/../../include/db.php";
    }
set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Start download_filename_format configuration upgrade...');

/*
Reference only, the deprecated configs defaults, grouped by how they relate:

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

// $original_filenames_when_downloading=false;
// $download_filename_id_only = true;
// $download_filename_field=223;

$upgrade_26_admin_messages = [];
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
    $upgrade_26_admin_messages[] = "Unable to set system wide config option 'download_filename_format' to '{$system_wide_dld_filename_format}'. Please do it manually.";
    }
logScript("System wide config generated: $system_wide_dld_filename_format");

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


set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Processing user group config overrides...');
$user_groups = get_usergroups();
foreach ($user_groups as $user_group)
    {
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "User group: {$user_group['name']}");
    $ug_config_options = trim((string) $user_group['config_options']);
    if ($ug_config_options === '')
        {
        continue;
        }

    override_rs_variables_by_eval($deprecated_options, $ug_config_options);
    $ug_dld_filename_format = $build_download_filename_format();
    logScript("Format for {$user_group['name']} is $ug_dld_filename_format");


    $msg = str_replace(
        ['%entity%', '%format%'],
        [
            "{$user_group['name']} ({$lang['user_group']})",
            $build_download_filename_format()
        ],
        $lang['upgrade_026_notification']
    );

    echo $msg;
    }


// $notification_users = array_column(get_notification_users('SYSTEM_ADMIN'), 'ref');




set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Finished download_filename_format configuration upgrade!');
die;

#######

if (
    isset($video_tracks_plugin_config['video_tracks_output_formats_saved'])
    && !empty($video_tracks_plugin_config['video_tracks_output_formats_saved'])
)
    {
    register_plugin_language('video_tracks');
    $config_to_copy = plugin_decode_complex_configs($video_tracks_plugin_config['video_tracks_output_formats_saved']);
    $notification_users = array_column(get_notification_users('SYSTEM_ADMIN'), 'ref');

    $msg = str_replace(
        [
            '%nl%',
            '%output_formats_config%'
        ],
        [
            PHP_EOL,
            sprintf('$video_tracks_output_formats = %s;%s', var_export($config_to_copy, true), PHP_EOL)
        ],
        $lang['video_tracks_upgrade_msg_deprecated_output_format']
    );

    message_add(
        $notification_users,
        "{$lang['upgrade_script']} #024: {$msg}",
        '',
        null,
        MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,
        MESSAGE_DEFAULT_TTL_SECONDS
    );
    }