<?php
include_once dirname(__FILE__, 4) . '/include/db.php';
include_once dirname(__FILE__, 4) . '/include/resource_functions.php';
command_line_only();


// Python CLIP service endpoint
$clip_service_url = 'http://localhost:8000/vector';

// Get the current database name
global $mysql_db;

// Limit the number of resources processed in one run (optional safety limit)
$limit = 1000;

// Get resources needing vector generation or update
$sql = "
    SELECT r.ref, r.file_extension, r.file_checksum
    FROM resource r
    LEFT JOIN resource_clip_vector v ON r.ref = v.resource
    WHERE r.has_image = 1
      AND r.file_checksum IS NOT NULL
      AND (v.checksum IS NULL OR v.checksum != r.file_checksum)
    ORDER BY r.ref ASC
    LIMIT {$limit}";

$resources = ps_query($sql);

if(empty($resources))
{
    echo "No resources needing vector update.\n";
    exit;
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

    // Send to Python CLIP service
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $clip_service_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $post_fields = [
        'db' => $mysql_db,
        'image' => new CURLFile($image_path)
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($http_code !== 200 || empty($response))
    {
        echo "❌ Resource $ref: error from CLIP service (HTTP $http_code)\n";
        continue;
    }

    $vector = json_decode($response, true);
    if(!is_array($vector) || count($vector) !== 512)
    {
        echo "❌ Resource $ref: invalid vector returned\n";
        continue;
    }

    // Store in DB
    ps_query("DELETE FROM resource_clip_vector WHERE resource = ?", ['i', $ref]);
    ps_query(
        "INSERT INTO resource_clip_vector (resource, vector, checksum) VALUES (?, ?, ?)",
        ['i', $ref, 's', json_encode($vector), 's', $checksum]
    );

    echo "✓ Vector stored for resource $ref\n";
}

echo "Done.\n";
