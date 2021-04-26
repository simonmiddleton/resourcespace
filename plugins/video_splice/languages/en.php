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

$lang["splice"]="Splice";
$lang["intro-splice"]="Splices several video resources together to form one combined video resource.";
$lang["drag_and_drop_to_rearrange"]="Drag and drop to rearrange the video clips.";
$lang["action-splice"]="Splice";
$lang["error-no-ffmpegpreviewfile"]="Error: Video %resourceid does not have an %filetype file attached as it's main file. Was it the output of a crop operation?"; # %resourceid, %filetype will be replaced
$lang["cropped_from_resource"]="(cropped from %resourceinfo)"; # %resourceinfo will be replaced
$lang["merged_from_resources"]="Merged from %resources"; # %resources will be replaced
