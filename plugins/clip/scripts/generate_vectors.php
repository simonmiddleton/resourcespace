<?php

include_once dirname(__FILE__, 4) . '/include/boot.php';
include_once dirname(__FILE__, 2) . '/include/clip_functions.php';

command_line_only();

// Disable output buffering
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

// Get the current database name
global $mysql_db;

// Limit the number of resources processed in one run (optional safety limit)
$limit = 10000;

// Get resources needing vector generation or update - look at the modified date vs. the creation date on the text vector, and also the image checksum on the vector vs the one on the resource record. This catches both metadata and image updates.
$sql = "
    SELECT r.ref value
    FROM resource r
    LEFT JOIN resource_clip_vector v_image ON v_image.is_text=0 and r.ref = v_image.resource
    /* LEFT JOIN resource_clip_vector v_text  ON v_text.is_text=1  and r.ref = v_text.resource */

    WHERE r.has_image = 1
      AND r.file_checksum IS NOT NULL
      AND 
        (
        (v_image.checksum IS NULL OR v_image.checksum != r.file_checksum)
        /* OR
        (v_text.checksum IS NULL OR v_text.created < r.modified) */
        )
      ORDER BY r.ref ASC
    LIMIT {$limit}";

$resources = ps_array($sql);

if (empty($resources)) {
    echo "No resources needing vector update.\n";
    exit;
}

foreach ($resources as $resource) {
    clip_generate_vector($resource);
}

echo "Done.\n";
