<?php
include "../../include/db.php";
command_line_only();

function generateChatCompletions($apiKey, $model, $temperature = 0, $max_tokens = 2048, $messages=array(), $uid="") {
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
        CURLOPT_TIMEOUT => 10,
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

$plugins=scandir("../../plugins"); array_shift($plugins);array_shift($plugins); // Discard first two which are "." and ".."
$plugins[]=""; // Add an extra row to signify the base languages (not in a plugin)
$plugins=array_reverse($plugins);

foreach ($plugins as $plugin)
    {
    $plugin_path="";
    if ($plugin!="") {$plugin_path="plugins/" . $plugin . "/";}

    // Get a baseline 
    $lang=array();
    $basefile="../../" . $plugin_path . "languages/en.php";
    if (!file_exists($basefile)) {continue;} // This plugin does not have any translations.
    include $basefile; 
    $lang_en=$lang;

    foreach ($languages as $language=>$lang_name)
        {
        if (in_array($language,array("en","en-US"))) {continue;}
        
        // Process a language
        $lang=array();$langfile="../../" . $plugin_path . "languages/" . $language . ".php";

        // Create file if it doesn't exist.
        if (!file_exists($langfile)) {file_put_contents($langfile,"<?php\n\n");}

        // Include to get the lang array for this language
        include $langfile;

        // Work out what we're missing.
        $missing=array_diff(array_keys($lang_en),array_keys($lang));

        $count=0;
        foreach ($missing as $mkey)
            {
            if (!is_string($lang_en[$mkey])) {continue;}
                
            $count++;
            echo $plugin_path . " " . $count . "/" . count($missing) . ": Processing $mkey (" . $lang_en[$mkey] . ") for language $language\n\n";flush();ob_flush();
            
            $messages=array();
            $messages[]=array("role"=>"system","content"=>"Your task is to convert language strings used by the digital asset management software ResourceSpace from English to " . $lang_name . ". Ensure that the translation accurately reflects the intended meaning of the string in the context of digital asset management software, including any relevant objects/terminology used in ResourceSpace such as resources, collections, metadata, tags, users, groups, workflows and downloads. Where the context is unclear, make a best guess. In the event that you cannot provide a translation, return the word CALAMITY.");
            $messages[]=array("role"=>"user","content"=>"Please translate: " . $lang_en[$mkey]);
            
            $result=generateChatCompletions($openai_key,"gpt-3.5-turbo",0,2048,$messages);
    echo "\n";print_r($result);echo "\n\n";
            // Append it to the appropriate file.
            if (is_string($result) && strlen($result)>0 && strpos(strtolower($result),"calamity")===false && strpos(strtolower($result),"[error]")===false)
                {
                $f=fopen($langfile,"a");fwrite($f,"\n\$lang[\"" . $mkey . "\"]=" . var_export($result,true) . ";");fclose($f);
                }
            }
        }
    }
