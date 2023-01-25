<?php

function generateCompletions($apiKey, $model, $prompt, $num_completions = 1, $temperature = 0, $max_tokens = 2048) {
    // Set the endpoint URL
    global $openai_gpt_api_endpoint,$openai_gpt_api_key;

    // Encode the completion options as JSON
    // $completion_options_json = json_encode($completion_options);
    
    // Set the headers for the request
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $openai_gpt_api_key",
    ];

    // Set the data to send with the request
    $data = [
        "model" => $model,
        "prompt" => $prompt,
        "temperature" => $temperature,
        "max_tokens" => $max_tokens,
    ];
    
    // Initialize cURL
    $ch = curl_init($openai_gpt_api_endpoint);
    
    // Set the options for the request
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    
    // Send the request and get the response
    $response = curl_exec($ch);


    // Decode the response as JSON
    $response_data = json_decode($response, true);
    
    // Return the completions
    // return $response_data["data"];
    if (isset($response_data["choices"][0]["text"])) {return $response_data["choices"][0]["text"];}
    }