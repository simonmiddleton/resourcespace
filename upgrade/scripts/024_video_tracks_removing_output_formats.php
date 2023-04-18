<?php
command_line_only();
if(!is_plugin_activated('video_tracks'))
    {
    set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'SKipping...Video Tracks plugin is disabled!');
    return;
    }

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Start Video Tracks plugin configuration upgrade...');

$video_tracks_plugin_config = get_plugin_config('video_tracks');

if (
    isset($video_tracks_plugin_config['video_tracks_output_formats_saved'])
    && !empty($video_tracks_plugin_config['video_tracks_output_formats_saved'])
)
    {
    $config_to_copy = plugin_decode_complex_configs($video_tracks_plugin_config['video_tracks_output_formats_saved']);
    $notification_users = array_column(get_notification_users('SYSTEM_ADMIN'), 'ref');

    $msg  = 'IMPORTANT! The Video Tracks plugin has deprecated the output formats settings.';
    $msg .= ' They can only be set in config.php.';
    $msg .= ' The plugin will not work as intended until the configuration option has been copied over.';
    $msg .= ' Please copy the following:-' . PHP_EOL;
    $msg .= '####' . PHP_EOL;
    $msg .= sprintf('$video_tracks_output_formats = %s;%s', var_export($config_to_copy, true), PHP_EOL);
    $msg .= '####' . PHP_EOL;

    message_add(
        $notification_users,
        "Upgrade script #024: {$msg}",
        '',
        null,
        MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,
        MESSAGE_DEFAULT_TTL_SECONDS
    );
    }

unset(
    $video_tracks_plugin_config['video_tracks_output_formats_saved'],
    $video_tracks_plugin_config['video_tracks_export_folder']
);
set_plugin_config('video_tracks', $video_tracks_plugin_config);

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, 'Finished Video Tracks plugin configuration upgrade!');