<?php

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
