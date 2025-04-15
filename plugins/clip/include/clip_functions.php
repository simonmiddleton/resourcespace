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
function get_vector($is_text, $input, $ref)
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
