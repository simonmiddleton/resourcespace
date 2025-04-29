<?php

include_once dirname(__FILE__, 5) . '/include/boot.php';
include_once dirname(__FILE__, 3) . '/include/clip_functions.php';

command_line_only();

// Disable output buffering
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

// blank file
file_put_contents("taggable_nouns.tagdb", "");

$words = explode("\n", file_get_contents("taggable_nouns.txt"));
foreach ($words as $word) {
    $word = trim($word);
    echo str_pad($word, 15);
    $vector = get_vector(true, $word, -1);
    $vector = array_map('floatval', $vector); // ensure float values

  // Write vector to file.
    file_put_contents("taggable_nouns.tagdb", $word . " " . implode(' ', array_map('strval', $vector)) . "\n", FILE_APPEND | LOCK_EX);

    echo vector_visualise($vector) . "\n";
}
