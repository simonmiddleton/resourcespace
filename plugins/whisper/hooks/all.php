<?php
include_once dirname(__FILE__, 2) . '/include/whisper_functions.php';

/**
 * Add whisper processing to the cron.
 *
 * @return void
 */
function HookWhisperAllCron()
{
    whisper_process_unprocessed();
}
