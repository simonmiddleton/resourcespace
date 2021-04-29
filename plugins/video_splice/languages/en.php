<?php
# English
# Language File for the Video Splice Plugin
# -------
#
#
$lang["action-trim"]="Trim";
$lang["video-trim"]="Video Trim";
$lang["video-trim-warning"]="Trim Warning";
$lang["video-trim-warning-text"]="Your start or end trim point/s proceeds the video preview length.<br><br>The preview provided will not fully represent the final outcome, please consider increasing your video preview length and recreating preview files should you wish to preview the timmed outcome.";
$lang["video-trim_upload-type"]="Upload Type";
$lang["video-trim_new-response"]="New resource(s) created: Resource ID(s) [ %links] you can continue to make additional trims below.";
$lang["video-trim_alt-response"]="Alternative file(s) created for resource %ref: Alternative ID(s) [ %links] you can continue to make additional trims below.";

$lang["error-from_time_after_to_time"]="Error: 'from' time must be before 'to' time.";
$lang["from-time"]="From";
$lang["to-time"]="To";
$lang["hh"]="HH";
$lang["mm"]="MM";
$lang["ss"]="SS";

$lang["videospliceconfiguration"]="Video Splice Configuration";
$lang["specify_resource_type"]="Please specify which resource type the cut and splice functionality should appear for.";
$lang["video_resource_type"]="Video Resource Type";
$lang["specify_parent_field"]="Please specify which field should be used for the parent resource information when cutting/splicing.";
$lang["parent_resource_field"]="Parent Resource Information Field";

$lang["video-splice"]="Video Splice";
$lang["video-splice-intro"]="Splice several video resources together to form one combined video resource. Drag and drop the thumbnails below to reorder the video clips.";
$lang["action-splice"]="Splice";
$lang["video_splice_order"]="Video resources in order";
$lang["video_splice_select_output"]="Choose output format";
$lang["video_splice_select_resolution"]="Choose output resolution";
$lang["video_splice_select_frame_rate"]="Choose output frame rate";
$lang["video_splice_save_to"]="Save to";
$lang["video_splice_create_new"]="Create new resource";
$lang["video_splice_save_export"]="Export folder";
$lang['video_splices_transcode_now_or_notify_me_label']='Check to start the transcode now. If unchecked you will receive a notification when the file is ready';
$lang['video_splices_transcode_now_label']='Transcode now';
$lang["error-no-ffmpegpreviewfile"]="Error: Video %resourceid does not have an %filetype file attached as it's main file. Was it the output of a crop operation?"; # %resourceid, %filetype will be replaced
$lang["cropped_from_resource"]="(cropped from %resourceinfo)"; # %resourceinfo will be replaced
$lang["merged_from_resources"]="Merged from %resources"; # %resources will be replaced



// $lang["video_tracks_title"]="Video tracks configuration";
// $lang["video_tracks_intro"]="This plugin allows the use of alternative subtitle and audio track files to create custom video files";
// $lang["video_tracks_convert_vtt"]="Automatically convert subrip subtitle files (.srt) to VTT to allow display in video previews?";
// $lang["video_tracks_audio_extensions"]="List of alternative file audio extensions (comma separated) that can be used for soundtrack";
// $lang["video_tracks_subtitle_extensions"]="List of permitted subtitle file extensions (comma separated). Must be supported by ffmpeg";
// $lang["video_tracks_permitted_video_extensions"]="Show the custom video option for these file extensions";
// $lang["video_tracks_create_video_link"] ="Generate custom video";
// $lang["video_tracks_select_output"] ="Choose format";
// $lang["video_tracks_select_subtitle"] ="Subtitles";
// $lang["video_tracks_select_audio"] ="Audio";
// $lang["video_tracks_invalid_resource"] ="Invalid resource";
// $lang["video_tracks_invalid_option"] ="Invalid options selected";
// $lang["video_tracks_save_to"]="Save to";
// $lang["video_tracks_save_alternative"]="Alternative file";
// $lang["video_tracks_export_section"]="Export folder";
// $lang["video_tracks_save_export"]="Export folder";
// $lang["video_tracks_export_folder"]="Export folder to save generated videos";
// $lang["error_video_tracks_export_folder"]="Export folder is not writable by web server";
// $lang["video_tracks_generate"]="Generate";
// $lang["video_tracks_options"]="Available file output options. These should be tested on the server to ensure the syntax is correct for your installation of ffmpeg/avconv";
// $lang["video_tracks_command"]= "ffmpeg/avconv  command";
// $lang["video_tracks_option_name"]="Output format code";
// $lang["video_tracks_process_size_limit"]="Maximum size of resource file that will be processed immediately (MB). Larger files will be processed offline and the user notified upon completion";
// $lang["video_tracks_offline_notice"]="Your request has been queued. You will be notified when the new file has been generated";
// $lang["video_tracks_export_file_created"]="Your custom video file has been created.";
// $lang["video_tracks_export_file_failed"]="Creation of the output file failed.";
// $lang["video_tracks_export_file_description"]="Custom video file";
// $lang["video_tracks_download_export"]="When files are created in the export folder offline, add a link to notification messages allowing for download of exported files through web interface";
// $lang["video_tracks_config_blocked"]="Configuration of video output formats has been blocked. Please contact your system administrator";
// $lang["video_tracks_command_missing"]="Available file output options are incomplete. If this error persists please contact your system administrator.";

// // Labels:
// $lang['video_tracks_generate_label']='Generate';
// $lang['video_tracks_custom_video_formats_label']='Custom formats';
// $lang['video_tracks_use_for_custom_video_formats_of_original_label']='Allow the available file output options to be used to create custom video formats for the original file?';
// $lang['video_tracks_transcode_now_or_notify_me_label']='Check to start the transcode now. If unchecked you will receive a notification when the file is ready';
// $lang['video_tracks_transcode_now_label']='Transcode now';