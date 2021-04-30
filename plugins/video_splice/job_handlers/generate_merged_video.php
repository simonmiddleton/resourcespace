<?php
/*
Job handler to run generate_merged_video() to combine videos together via the splice plugin

Requires the following job data:-
$job_data['videos'] - all the videos to merge
$job_data['target_video_command'] - the chosen video encoding command
$job_data['target_video_extension'] - the chosen video encoding extension
$job_data['target_audio'] - the chosen audio encoding
$job_data['target_width'] - the chosen video resolution width
$job_data['target_height'] - the chosen video resolution height
$job_data['target_frame_rate'] - the chosen video frame rate
*/

include __DIR__ . "/../include/splice_functions.php";

$videos = $job_data['videos'];
$video_splice_type = $job_data['video_splice_type'];
$target_video_command = $job_data['target_video_command'];
$target_video_extension = $job_data['target_video_extension'];
$target_audio = $job_data['target_audio'];
$target_width = $job_data['target_width'];
$target_height = $job_data['target_height'];
$target_frame_rate = $job_data['target_frame_rate'];
$description = $job_data['description'];

generate_merged_video(
    $videos,
    $video_splice_type,
    $target_video_command,
    $target_video_extension,
    $target_audio,
    $target_width,
    $target_height,
    $target_frame_rate,
    $description
    );  