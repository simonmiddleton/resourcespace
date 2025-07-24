<?php

include_once dirname(__FILE__, 4) . '/include/boot.php';
include_once dirname(__FILE__, 2) . '/include/whisper_functions.php';

// Disable output buffering
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

command_line_only();
whisper_process_unprocessed();
