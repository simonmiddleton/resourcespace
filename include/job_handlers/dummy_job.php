<?php

logScript("[dummy_job] Starting dummy job", $log_file);


$message_count = 1;

$total_messages = 200;

logScript("[dummy_job] $total_messages to process", $log_file);

while ($message_count <= $total_messages) {


    $progress = round(($message_count / $total_messages) * 100, 0);

    logScript("[dummy_job] Fake processing: $message_count...", $log_file);
    $delay = rand(0, 3);
    sleep($delay);
    logScript("[dummy_job] Finished processing $message_count and took $delay seconds", $log_file);
    logScript("[dummy_job] [PROGRESS] $progress%", $log_file);
    $message_count++;
}

logScript("[dummy_job] Ending dummy job", $log_file);

job_queue_update($jobref, $job_data, STATUS_COMPLETE);