<?php
# English
# Language File for the video_tracks Plugin
# -------
#
#
$lang["video_tracks_title"]="Video tracks configuration";
$lang["video_tracks_intro"]="This plugin allows the use of alternative subtitle and audio track files to create custom video files";
$lang["video_tracks_convert_vtt"]="Automatically convert subrip subtitle files (.srt) to VTT to allow display in video previews?";
$lang["video_tracks_audio_extensions"] = "List of alternative file audio extensions (comma separated) that can be used for soundtrack";
$lang["video_tracks_subtitle_extensions"] = "List of permitted subtitle file extensions (comma separated). Must be supported by ffmpeg";
$lang["video_tracks_permitted_video_extensions"] = "Show the custom video option for these file extensions";
$lang["video_tracks_create_video_link"] ="Generate custom video";
$lang["video_tracks_select_output"] ="Choose format";
$lang["video_tracks_select_subtitle"] ="Subtitles";
$lang["video_tracks_select_audio"] ="Audio";
$lang["video_tracks_invalid_resource"] ="Invalid resource";
$lang["video_tracks_invalid_option"] ="Invalid options selected";
$lang["video_tracks_save_to"] = "Save to";
$lang["video_tracks_save_alternative"] = "Alternative file";
$lang["video_tracks_save_export"] = "Export folder";
$lang["video_tracks_generate"] = "Generate";
$lang["video_tracks_process_size_limit"] = "Maximum size of resource file that will be processed immediately (MB). Larger files will be processed offline and the user notified upon completion";
$lang["video_tracks_offline_notice"]="Your request has been queued. You will be notified when the new file has been generated";
$lang["video_tracks_export_file_created"] = "Your custom video file has been created.";
$lang["video_tracks_export_file_failed"] = "Creation of the output file failed.";
$lang["video_tracks_export_file_description"] = "Custom video file";

// Labels:
$lang['video_tracks_generate_label'] = 'Generate';
$lang['video_tracks_custom_video_formats_label'] = 'Custom formats';
$lang['video_tracks_use_for_custom_video_formats_of_original_label'] = 'Allow the available file output options to be used to create custom video formats for the original file?';
$lang['video_tracks_transcode_now_or_notify_me_label'] = 'Check to start the transcode now. If unchecked you will receive a notification when the file is ready';
$lang['video_tracks_transcode_now_label'] = 'Transcode now';