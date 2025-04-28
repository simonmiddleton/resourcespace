<?php

/**
 * Sends an image file or text input to the CLIP vector service and returns the resulting vector.
 *
 * @param bool   $is_text   Whether the input is text (true) or an image file (false).
 * @param string $input     Either the text string or the path to the image file.
 * @param int    $ref       The resource ID (used for logging/debugging purposes only).
 *
 * @return array|false      Returns a 512-float array representing the CLIP vector,
 *                          or false if the service failed or returned invalid data.
 */
function get_vector(bool $is_text, string $input, int $ref): array|false
{
    global $clip_service_url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $clip_service_url . "/vector");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if (!$is_text) {
        $post_fields = [
            'image' => new CURLFile($input)
        ];
    } else {
        $post_fields = [
            'text' => $input
        ];
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || empty($response)) {
        logScript("❌ Resource $ref: error from CLIP service (HTTP $http_code)");
        return false;
    }

    $vector = json_decode($response, true);
    if (!is_array($vector) || count($vector) !== 512) {
        logScript("❌ Resource $ref: invalid vector returned");
        return false;
    }

    return $vector;
}



/**
 * Generates a simple visual representation of a CLIP vector as a 64-character string. This helps
 * visualise differences between vectors in a human-readable form.
 *
 * @param array $vector  A 512-element array of float values (normalised CLIP vector).
 *
 * @return string        A 64-character string "fingerprint" visualising the vector content.
 */
function vector_visualise(array $vector): string
{
    $chars = str_split(" .:;i1tfLCG08@");
    $out = "";
    $chunks = array_chunk($vector, 8);

    foreach ($chunks as $chunk) {
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

/**
 * Generates and stores a CLIP vector for a given resource.
 *
 * This function:
 * - Loads the specified resource data.
 * - Attempts to locate a resized (pre) version of the resource image.
 * - Sends the image to the CLIP service to obtain a 512-float vector.
 * - Stores the resulting vector as a binary blob in the database.
 *
 * @param int $ref The resource ID for which to generate the vector.
 *
 * @return array|false Returns the ID of the stored vector row on success,
 *                     or false on failure (e.g., file missing or vector generation error).
 */
function clip_generate_vector($ref)
{
        $resource = get_resource_data($ref);
        $ext = "jpg";
        $size = "pre";
        $checksum = $resource['file_checksum'];

        $image_path = get_resource_path($ref, true, $size, false, $ext);

    if (!file_exists($image_path)) {
        logScript("⚠ Resource $ref: file not found at $image_path");
        return false;
    }

        // Calculate vectors - image
        $vector = get_vector(false, $image_path, $ref);
    if ($vector === false) {
        return false;
    }

        // Store vector in DB
        $vector = array_map('floatval', $vector); // ensure float values
        $blob = pack('f*', ...$vector);

        ps_query("DELETE FROM resource_clip_vector WHERE resource = ?", ['i', $ref]);
        ps_query(
            "INSERT INTO resource_clip_vector (resource, vector_blob, checksum, is_text) VALUES (?, ?, ?, false)",
            ['i', $ref, 's', $blob, 's', $checksum]
        ); // Note the blob must be inserted as 's' type as ps_query() does not correctly handle 'b' yet (send_long_data() is needed)
        $return = sql_insert_id();

        logScript("✓ Vector stored for resource $ref [" . vector_visualise($vector) . "] length: " . count($vector) . ", blob size: " . strlen($blob));
        return $return;
}
