<?php
include "../../include/db.php";
command_line_only();

function generateChatCompletions($apiKey, $model, $temperature = 0, $max_tokens = 2048, $messages, $uid="") {
    // Set the endpoint URL
    $endpoint = "https://api.openai.com/v1/chat/completions";
    
    // Encode the completion options as JSON
    // $completion_options_json = json_encode($completion_options);
    
    // Set the headers for the request
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey",
    ];

    // Set the data to send with the request
    $data = [
        "model" => $model,
        "messages" => $messages,
        "temperature" => $temperature,
        "max_tokens" => $max_tokens,
        "user" => $uid,
    ];
    
    // Initialize cURL
    $ch = curl_init($endpoint);
    
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
    //return print_r($response_data,true);
    if (isset($response_data["choices"][0]["message"]["content"])) {return $response_data["choices"][0]["message"]["content"];}
    // Response was not expected. Return the raw response.
    return print_r($response_data,true);
    }

// Get a baseline with no plugins etc.
$lang=array();
include "../../languages/en.php"; 
$lang_en=$lang;
krsort($languages);

foreach ($languages as $language=>$lang_name)
    {
    if (in_array($language,array("en","en-US"))) {continue;}
    
    // Process a language
    $lang=array();$langfile="../../languages/" . $language . ".php";
    include $langfile;

    $missing=array_diff(array_keys($lang_en),array_keys($lang));

    foreach ($missing as $mkey)
        {
        if (!is_string($lang_en[$mkey])) {continue;}
            
        echo "Processing $mkey (" . $lang_en[$mkey] . ") for language $language\n\n";flush();ob_flush();
        $messages=array();
        $messages[]=array("role"=>"system","content"=>"Your task is to convert language strings used by the digital asset management software ResourceSpace from English to " . $lang_name . ". Ensure that the translation accurately reflects the intended meaning of the string in the context of digital asset management software, including any relevant objects/terminology used in ResourceSpace such as resources, collections, metadata, tags, users, groups, workflows and downloads. Providing a translation that is appropriate in the given context will help ensure the software is easy to use and understand for non-native speakers. In the event that you cannot provide a translation, return the word CALAMITY.");
        $messages[]=array("role"=>"user","content"=>"Please translate: " . $lang_en[$mkey]);
        
        $result=generateChatCompletions($openai_key,"gpt-3.5-turbo",0,2048,$messages);
echo "\n";print_r($result);echo "\n\n";
        // Append it to the appropriate file.
        if (is_string($result) && strlen($result)>0 && strpos(strtolower($result),"calamity")===false)
            {
            $f=fopen($langfile,"a");fwrite($f,"\n\$lang[\"" . $mkey . "\"]=" . var_export($result,true) . ";");fclose($f);
            }
        }

    }
