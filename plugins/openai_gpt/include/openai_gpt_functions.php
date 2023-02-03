<?php

/**
 * Send the new field value to the OpenAI API in order to update the linked field 
 *
 * @param int       $resource           Resource ID
 * @param array     $target_field       Target metadata field (array
 * @param array     $values             Array of strings from the field currently being processed
 * 
 * @return bool     True if update successful, false if invalid field or no data returned
 * 
 */
function openai_gpt_update_field($resource,$target_field,$values)
    {
    global $valid_ai_field_types, $FIXED_LIST_FIELD_TYPES,$language, $defaultlanguage,
    $openai_gpt_prompt_prefix, $openai_gpt_prompt_return_json, $openai_gpt_processed, $openai_gpt_api_key,$openai_gpt_model,$openai_gpt_temperature ,$openai_gpt_max_tokens, $openai_gpt_max_data_length;

    // Don't update if not a valid field type
    if(!in_array($target_field["type"],$valid_ai_field_types) 
        || 
        count($values) == 0
        || isset($openai_gpt_processed[$resource . "_" . $target_field["ref"]])
        )
        {
        return false;
        }

    debug("openai_gpt_update_field() - resource # " . $resource . ", target field #" . $target_field["ref"]);

    // Remove any i18n variants and use default system language
    $prompt_values = [];
    $saved_language = $language;
    $language = $defaultlanguage;
    foreach($values as $value)
        {
        if(substr($value,0,1) == "~")
            {
            $prompt_values[] = mb_strcut(i18n_get_translated($value),0,$openai_gpt_max_data_length);
            }
        else
            {
            $prompt_values[] = mb_strcut($value,0,$openai_gpt_max_data_length);
            }
        }
    $language = $saved_language;

    $prompt = $openai_gpt_prompt_prefix . $target_field["openai_gpt_prompt"] . (in_array($target_field["type"],$FIXED_LIST_FIELD_TYPES) ? " " . $openai_gpt_prompt_return_json : "") . json_encode($prompt_values);

    if(isset($openai_response_cache[md5($prompt)]))
        {
        return $openai_response_cache[md5($prompt)];
        }

    debug("openai_gpt - sending request prompt '" . $prompt . "'");    
    $openai_response = openai_gpt_generate_completions($openai_gpt_api_key,$openai_gpt_model,$prompt,$openai_gpt_temperature,$openai_gpt_max_tokens);

    if(trim($openai_response) != "")
        {
        debug("openai_gpt_generate_completions text response : " . $openai_response);
        if(in_array($target_field["type"],$FIXED_LIST_FIELD_TYPES))
            {
            $apivalues = json_decode(trim($openai_response),true);
            if(json_last_error() !== JSON_ERROR_NONE || !is_array($apivalues))
                {
                debug("openai_gpt error - invalid JSON text response received from API: " . json_last_error_msg() . " " . trim($openai_response));               
                }
            // The returned array elements may be associative or contain sub arrays - convert to list of strings
            $newstrings = [];
            foreach($apivalues as $attribute=>&$value)
                {
                if(is_array($value))
                    {
                    $value = json_encode($value);
                    }                
                $newstrings[] = is_int_loose($attribute) ? $value : $attribute . " : " . $value;
                }            
            // update_field() will separate on NODE_NAME_STRING_SEPARATOR
            $newvalue = implode(NODE_NAME_STRING_SEPARATOR,$newstrings);          
            }
        else
            {
            $newvalue = $openai_response;
            }
        // Set a flag to prevent any opossibility of infinite recursion within update_field()
        $openai_gpt_processed[$resource . "_" . $target_field["ref"]] = true;

        $result = update_field($resource,$target_field["ref"],$newvalue);
        return $result;
        }
    debug("openai_gpt error - empty response received from API: '" . trim($openai_response) . "'");
    return false;
    }

/**
 * Call the OpenAI API
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
    debug("openai_gpt_generate_completions() \$model = '" . $model . "', \$prompt = '" . $prompt . "' \$temperature = '" . $temperature . "', \$max_tokens = " . $max_tokens);

    // Set the endpoint URL
    global $openai_gpt_endpoint,  $openai_response_cache;
    
    if(isset($openai_response_cache[md5($openai_gpt_endpoint . $prompt)]))
        {
        return $openai_response_cache[md5($openai_gpt_endpoint . $prompt)];
        }

    // $temperature must be between 0 and 1
    $temperature = floatval($temperature);
    if($temperature>1 || $temperature<0)
        {
        debug("openai_gpt invalid temperature value set : '" . $temperature . "'");
        $temperature = 0;
        }
    if(trim($apiKey) == "")
        {
        debug("openai_gpt error - missing API key");
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
        "max_tokens" => (int)$max_tokens,
    ];

    // Initialize cURL
    $ch = curl_init($openai_gpt_endpoint);
    
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
    debug("openai_gpt_generate_completions original response : " . print_r($response,true));
    $response_data = json_decode($response, true);

    if(json_last_error() !== JSON_ERROR_NONE)
        {
        debug("openai_gpt error - invalid JSON response received from API: " . json_last_error_msg() . " " . trim($response));
        $openai_response_cache[md5($openai_gpt_endpoint . $prompt)] = false;
        return false;
        }

    $error = $response_data["error"] ?? ($response_data["error"][0] ?? []); 
    if(!empty($error))
        {
        debug("openai_gpt_generate_completions API error - type:" . $error["type"] . ", message: " . $error["message"]);
        $openai_response_cache[md5($openai_gpt_endpoint . $prompt)] = false;
        return false;
        }

    // Return the text from the completions
    if (isset($response_data["choices"][0]["text"]))
        {
        $return = $response_data["choices"][0]["text"];
        $openai_response_cache[md5($openai_gpt_endpoint . $prompt)] = $return;
        return $return;
        }
    return false;
    }

function openai_gpt_get_dependent_fields($field)
    {
    $ai_gpt_input_fields = ps_query("SELECT " . columns_in("resource_type_field") . " FROM resource_type_field WHERE openai_gpt_input_field = ?",["i",$field]);
    return $ai_gpt_input_fields;
    }