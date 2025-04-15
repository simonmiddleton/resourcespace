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

    if (!$is_text)
        {
        $post_fields = [
            'image' => new CURLFile($input)
        ];
        }
    else
        {
        $post_fields = [
            'text' => $input
        ];
        }

    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($http_code !== 200 || empty($response))
    {
        echo "❌ Resource $ref: error from CLIP service (HTTP $http_code)\n";
        return false;
    }

    $vector = json_decode($response, true);
    if(!is_array($vector) || count($vector) !== 512)
    {
        echo "❌ Resource $ref: invalid vector returned\n";
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