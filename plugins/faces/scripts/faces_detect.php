<?php
include_once dirname(__FILE__, 4) . '/include/boot.php';
include_once dirname(__FILE__, 2) . '/include/faces_functions.php';

command_line_only();

// Get all resources that haven't had faces processed yet
$resources = ps_query("SELECT ref, file_extension FROM resource WHERE (faces_processed is null or faces_processed=0) ORDER BY ref desc");

foreach ($resources as $resource)
    {
    $ref = $resource['ref'];
    $ext = $resource['file_extension'];
    $file_path = get_resource_path($ref, true, 'scr', false, "jpg");

    flush();ob_flush();
    
    if (!file_exists($file_path))
        {
        // Try full size JPEG as a fallback (for small images only where SCR wasn't generated)
        $file_path = get_resource_path($ref, true, '', false, "jpg");
        }

    if (!file_exists($file_path))
        {
        logScript("File not found for resource $ref ($file_path)");
        continue;
        }

    // Prepare file for POST
    $curl = curl_init();
    $cfile = new CURLFile($file_path);
    $postfields = ['file' => $cfile];

    curl_setopt_array($curl, [
        CURLOPT_URL => $faces_service_endpoint . "/extract_faces",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postfields,
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_code !== 200)
        {
        logScript("Face service returned HTTP $http_code for resource $ref");
        continue;
        }

    $faces = json_decode($response, true);
    if (!is_array($faces))
        {
        logScript("Invalid JSON from face service for resource $ref");
        continue;
        }

    foreach ($faces as $face)
        {
        $bbox = isset($face['bbox']) ? json_encode($face['bbox']) : null;
        $score = isset($face['det_score']) ? (float)$face['det_score'] : null;
        $vector = isset($face['embedding']) ? pack("f*", ...$face['embedding']) : null;

        if ($vector === null)
            {
            continue;
            }

        ps_query("INSERT INTO resource_face (resource, bbox, det_score, vector_blob, created) VALUES (?, ?, ?, ?, NOW())",
            ['i', $ref, 's', $bbox, 'd', $score, 's', $vector]);
        }

    // Mark resource as processed
    ps_query("UPDATE resource SET faces_processed = 1 WHERE ref = ?", ["i", $ref]);
    logScript("Processed resource $ref and found " . count($faces) . " faces.");
    }

