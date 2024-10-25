<?php
include "../../include/boot.php";
command_line_only();

$restrict=$argv[1] ?? false;

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

$bad_params=0;
$calamity=0;
$calamities=[];
$bad_params_list=[];

// Things that do not translate
$ignore=["map_hydda_group", "_dupe", "minute-abbreviated", "hour-abbreviated", "map_tf_group", "map_esridelorme", "posixldapauth_rdn", "to-page", "emu_upload_emu_field_label", "all__emailcollectionexternal", "upload_share_email_template", "all__emaillogindetails", "all__emailnotifyresourcessubmitted", "all__emailresourcerequest", "all__emailbulk", "all__emailresource", "system_notification_email", "all__emailcollection", "all__emailnotifyresourcesunsubmitted", "all__emailresearchrequestassigned", "all__emailnotifyuploadsharenew", "email_link_expires_date", "map_esri_group", "geodragmodepan", "map_stamen_group"];
  
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

    // Fetch a list of valid parameters using en.php as the base
    $base=file_get_contents($basefile);
    preg_match_all("/\[([a-zA-Z0-9_]*)\]/",$base,$params_correct);

    foreach ($languages as $language=>$lang_name)
        {
        if (in_array($language,array("en","en-US"))) {continue;}
	if ($restrict!==false && $restrict!=$language) {continue;}

        // Process a language
        $lang=array();$langfile="../../" . $plugin_path . "languages/" . $language . ".php";

        // Create file if it doesn't exist.
        if (!file_exists($langfile)) {file_put_contents($langfile,"<?php\n\n");}

        // Include to get the lang array for this language
        include $langfile;

        // Add plugin title and description from plugin YAML so it gets a translation too
        $lang_en_extended=$lang_en;
        if ($plugin!="")
            {
            $yaml_path="../../" . $plugin_path . $plugin . ".yaml";
            if (file_exists($yaml_path))
                {
                $yaml=get_plugin_yaml($yaml_path);
                if (isset($yaml["title"])) {$lang_en_extended["plugin-" . $plugin . "-title"]=$yaml["title"];}
                if (isset($yaml["desc"]))  {$lang_en_extended["plugin-" . $plugin . "-desc"]=$yaml["desc"];}
                }
            }


        // Work out what we're missing.
        $missing=array_diff(array_keys($lang_en_extended),array_keys($lang));

        $count=0;
        foreach ($missing as $mkey)
            {
            if (!is_string($lang_en_extended[$mkey])) {continue;}
            if (strlen(trim($lang_en_extended[$mkey]))<=1) {continue;}
            if (in_array($mkey,$ignore)) {continue;}
                
            $count++;
            echo $plugin_path . " " . $count . "/" . count($missing) . ": Processing $mkey (" . $lang_en_extended[$mkey] . ") for language $language\n\n";flush();ob_flush();
            
            $messages=array();
            $messages[]=array("role"=>"system","content"=>"Your task is to convert language strings used by the digital asset management software ResourceSpace from English to " . $lang_name . ". Ensure that the translation accurately reflects the intended meaning of the string in the context of digital asset management software, including any relevant objects/terminology used in ResourceSpace such as resources, collections, metadata, tags, users, groups, workflows and downloads.
 Where there is not an obvious translation in the DAM context, use a general context or make a best guess. Don't add the period character at the start or end of the translation if one isn't at the start or end of the value being translated.
Text within square brackets indicates a system parameter that MUST NOT itself be translated so for example '[list]' must remain '[list]' even if translating to Swedish. If a phrase does not require translation (e.g. because it is a Proper Noun with no local translation) simply return the phrase untranslated and consider this to be a valid translation.
In the event that you cannot provide a translation (with the exception of the cases listed above) return the word CALAMITY followed by the reason the translation could not be provided. Do not output anything that is not either a valid translation or the word CALAMITY with the reason.");
            $messages[]=array("role"=>"user","content"=>"Please translate: " . $lang_en_extended[$mkey]);
            
            $result=generateChatCompletions($openai_key,"gpt-4o-mini",0,2048,$messages);
    echo "\n";print_r($result);echo "\n\n";

            // Check there are no bad parameters in the results by comparing with the master list.
            preg_match_all("/\[([a-zA-Z0-9_]*)\]/",$result,$params);
            $wrong=array_diff($params[0],$params_correct[0]);
            if (count($wrong)>0)
                {
                echo "Bad parameters were generated: ";print_r($wrong);$bad_params++;
                $bad_params_list[]=$mkey;
                continue;
                }

            // Append it to the appropriate file.
            if (is_string($result) && strlen($result)>0 && strpos(strtolower($result),"calamity")===false && strpos(strtolower($result),"[error]")===false)
                {
                $f=fopen($langfile,"a");fwrite($f,"\n\$lang[\"" . $mkey . "\"]=" . var_export($result,true) . ";");fclose($f);
                }
            else   
                {
                $calamity++;$calamities[]=$mkey;
                }
            }
        }
    }
echo "\n\nTranslations that contained bad parameters and were skipped=$bad_params\nCould not translate=$calamity\n\n";
