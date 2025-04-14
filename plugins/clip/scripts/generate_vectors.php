<?php
include_once dirname(__FILE__, 4) . '/include/db.php';
include_once dirname(__FILE__, 4) . '/include/resource_functions.php';
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
    SELECT r.ref, r.file_extension, r.file_checksum
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

$resources = ps_query($sql);

if(empty($resources))
{
    echo "No resources needing vector update.\n";
    exit;
}

function vector_visualise(array $vector): string
{
    $chars = str_split(" .:;i1tfLCG08@"); 
    $out = "";
    $chunks = array_chunk($vector, 8);

    foreach ($chunks as $chunk)
    {
        $mean = array_sum($chunk) / count($chunk);
        $sum_squares = 0;
        foreach ($chunk as $v) {
            $sum_squares += pow($v - $mean, 2);
        }
        $std_dev = sqrt($sum_squares / count($chunk));

        // Clamp & scale std dev for visualisation (tune this if needed)
        $scaled = min(1.0, $std_dev * 5); // exaggerate a bit
        $index = (int)round($scaled * (count($chars) - 1));
        $out .= $chars[$index];
    }

    return $out;
}




foreach($resources as $resource)
{
    $ref = $resource['ref'];
    $ext = "jpg";
    $size = "pre";
    $checksum = $resource['file_checksum'];

    $image_path = get_resource_path($ref, true, $size, false, $ext);

    if(!file_exists($image_path))
    {
        echo "⚠ Resource $ref: file not found at $image_path\n";
        continue;
    }

    // Calculate vectors - image
    $vector=get_vector(false,$image_path,$ref);
    if ($vector===false) {continue;}

    // Calculate vectors - text
    /*
    $text_parts = [];
    foreach ($clip_text_search_fields as $fieldref) {
        $value = get_data_by_field($ref, $fieldref);
        if (!empty($value)) {
            $text_parts[] = $value;
        }
    }
    
    $text = implode(' ', $text_parts);
    // Remove all numbers (including standalone and in words) and tidy up spacing. Numbers are unlikely to add meaning.
    $text = preg_replace('/\d+/', '', $text);
    $text = trim(preg_replace('/\s+/', ' ', $text));
    $vector_text=get_vector(true,$text,$ref);
    if ($vector_text===false) {continue;}
    */

    // Store both vectors in DB
    $vector = array_map('floatval', $vector); // ensure float values
    $blob = pack('f*', ...$vector);

    ps_query("DELETE FROM resource_clip_vector WHERE resource = ?", ['i', $ref]);
    ps_query(
        "INSERT INTO resource_clip_vector (resource, vector_blob, checksum, is_text) VALUES (?, ?, ?, false)",
        ['i', $ref, 's', $blob, 's', $checksum]
    ); // Note the blob must be inserted as 's' type as ps_query() does not correctly handle 'b' yet (send_long_data() is needed)
    
    /*
    ps_query(
        "INSERT INTO resource_clip_vector (resource, vector, checksum, is_text) VALUES (?, ?, ?, true)",
        ['i', $ref, 's', json_encode($vector_text), 's', $checksum]
    );
    */

    echo "✓ Vector stored for resource $ref [" . vector_visualise($vector) . "] length: " . count($vector) . ", blob size: " . strlen($blob) . "\n";
}

echo "Done.\n";
