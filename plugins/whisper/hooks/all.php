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

/**
 * Prevent downloaded Whisper models from being deleted 
 * 
 * @return string
 */
function HookWhisperAllTemp_block_deletion()
{
    return $GLOBALS['whisper_model_dir'] ?? get_temp_dir() . "whisper";
}


