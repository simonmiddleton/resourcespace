<?php

/**
 * Send the new field value to the Open AI API in order to update the linked field 
 *
 * @param int       $resource           Resource ID
 * @param int       $target_field       Metadata field ref of the linked field (openai_gpt_output_field)
 * @param array     $fieldinfo          Array of resource_type_field data for field currently being updated
 * @param string    $value              New value of the field currently being processed (comma separated for node names)
 * 
 * @return bool     True if uipdat successful, false if invalid field or no data returned
 * 
 */
function  openai_gpt_update_linked_field($resource,$target_field,$fieldinfo,$value)
    {
    global $valid_ai_field_types, $FIXED_LIST_FIELD_TYPES,$openai_gpt_api_key, $openai_gpt_api_model,
    $openai_gpt_api_max_tokens,$openai_gpt_api_temperature, $language, $defaultlanguage;

    $target_field_info = get_resource_type_field($target_field);
    if(!in_array($target_field_info["type"],$valid_ai_field_types))
        {
        return false;
        }    
    
    $prompt = $fieldinfo["openai_gpt_prompt"] . (in_array($target_field_info["type"],$FIXED_LIST_FIELD_TYPES) ?  ", comma separated" : "") . ":";

    if(in_array($fieldinfo["type"],$FIXED_LIST_FIELD_TYPES) && substr($value,0,1) == "~")
        {
        // Remove i18n values and use default system language
        $allvals = explode(",",$value);
        $saved_language = $language;
        $language = $defaultlanguage;
        $allvals = array_map("i18n_get_translated",$allvals);
        $language = $saved_language;
        $prompt .= implode(",",$allvals);
        }
    else
        {
        $prompt .= $value;
        }

    if(isset($openai_response_cache[md5($prompt)]))
        {
        return $openai_response_cache[md5($prompt)];
        }

    debug("openai_gpt - sending request prompt '" . $prompt . "'");
    $openai_response = openai_gpt_generate_completions($openai_gpt_api_key,$openai_gpt_api_model,$prompt,$openai_gpt_api_temperature,$openai_gpt_api_max_tokens);
    
    if(trim($openai_response) != "")
        {
        // update_field() will separate on comma separated values
        $result = update_field($resource,$target_field,trim($openai_response));
        return $result;
        }
    return false;
    }

/**
 * Call the Open AI API
 *
 * Refer to https://beta.openai.com/docs/api-reference for detailed explanation
 * 
 * @param string    $apiKey             API key 
 * @param string    $model              Model name e.g. "text-davinci-003"
 * @param string    $prompt             Text prompt to generate response from API
 *                                      e.g . "For the following city, list the country it is within:"
 * @param float     $temperature        Value between 0 and 1 - higher values means model will take more risks. Default 0.
 * @param int       $max_tokens         The maximum number of completions to generate, default 2048
 * 
 * @return string   The first API response text output
 * 
 */
function openai_gpt_generate_completions($apiKey, $model, $prompt, $temperature = 0, $max_tokens = 2048)
    {
    // Set the endpoint URL
    global $openai_gpt_api_endpoint,  $openai_response_cache;
    
    if(isset($openai_response_cache[md5($openai_gpt_api_endpoint . $prompt)]))
        {
        return $openai_response_cache[md5($openai_gpt_api_endpoint . $prompt)];
        }
    

    // Set the headers for the request
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey",
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
    debug("openai_gpt_generate_completions decoded response : " . print_r($response_data,true));

    if(isset($response_data["error"][0]["message"]))
        {
        debug("openai_gpt_generate_completions API error - type:" . $response_data["error"][0]["type"] . ", message: " . $response_data["error"][0]["message"]);
        $openai_response_cache[md5($openai_gpt_api_endpoint . $prompt)] = false;
        return false;
        }

    // Return the text from the completions
    if (isset($response_data["choices"][0]["text"]))
        {
        $return = $response_data["choices"][0]["text"];
        $openai_response_cache[md5($openai_gpt_api_endpoint . $prompt)] = $return;
        return $return;
        }
    return false;
    }