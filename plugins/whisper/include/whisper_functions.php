<?php
// Included from preview_preprocessing.php
use Montala\ResourceSpace\CommandPlaceholderArg;

function whisper_process_unprocessed() {
    // Process all resources that haven't had text extracted yet.
    global $whisper_extensions;

    // Ensure only one instance of this.
    if (is_process_lock(__FUNCTION__)) {
        logScript("Whisper: Process lock is in place");
        return false;
    }
    set_process_lock(__FUNCTION__);

    $extensions=explode(",",$whisper_extensions);

    $resources = ps_array("SELECT ref value FROM resource WHERE file_extension in (" .     ps_param_insert(count($extensions)) . ") and (whisper_processed is null or whisper_processed=0) ORDER BY ref desc",ps_param_fill($extensions,"s"));

    logScript("Whisper: " . count($resources) . " resources to process.");
    foreach ($resources as $resource) {
        whisper_process($resource);
    }

    clear_process_lock(__FUNCTION__);
    return count($resources);
}

function whisper_process($resource)
    {
    global $lang, $whisper_prompt, $whisper_field, $whisper_subtitles;

    logScript("Whisper: processing resource " . $resource);
    $data=get_resource_data($resource);
    $extension=$data["file_extension"];
    $file_path=get_resource_path($resource,true,'',false,$extension);
    if (!file_exists($file_path))   {
        logScript("Whisper: Could not find file for resource $resource, path was $file_path");
    }
    
    // File exists, process to the required audio format.
    $ffmpeg_fullpath = get_utility_path("ffmpeg");
    $folder=dirname($file_path);
    $audio_file=$folder . "/whisper.wav";
    $shell_exec_cmd = "$ffmpeg_fullpath -y -loglevel error -i %%FILE%% -ac 1 -ar 16000 -c:a pcm_s16le %%TARGETFILE%%";
        $shell_exec_params = [
            "%%FILE%%" => new CommandPlaceholderArg($file_path, 'is_safe_basename'),
            "%%TARGETFILE%%" => new CommandPlaceholderArg($audio_file, 'is_safe_basename'),
        ];
    logScript("Whisper: Starting audio proxy creation...");
    run_command($shell_exec_cmd, false, $shell_exec_params);
    if (!file_exists($audio_file)) {
        logScript("Whisper: Audio proxy was not created");
        return false;
    }
    logScript("Whisper: Audio proxy created at " . $audio_file);

    // Audio file created. Proceed to prcess using Whisper.
    $whisper_prompt=preg_replace('/[^a-zA-Z0-9 \.\,\?\!\:\'\"\-\(\)]/', '', $whisper_prompt); // Sanitise prompt.
    $shell_exec_cmd = "whisper %%FILE%% --output_dir %%DIR%% --initial_prompt %%PROMPT%%";
        $shell_exec_params = [
            "%%FILE%%" => new CommandPlaceholderArg($audio_file, 'is_safe_basename'),
            "%%DIR%%" => new CommandPlaceholderArg($folder, [CommandPlaceholderArg::class, 'alwaysValid']), // Dir is always safe
            "%%PROMPT%%" => new CommandPlaceholderArg($whisper_prompt, [CommandPlaceholderArg::class, 'alwaysValid']) // Sanitised above
        ];
    logScript("Whisper: Starting Whisper...");
    run_command($shell_exec_cmd, false, $shell_exec_params);
    $text_file=dirname($file_path) . "/whisper.txt";
    if (!file_exists($text_file)) {
        logScript("Whisper: Text file was not created.");
        return false;
    } 
    $text=file_get_contents($text_file);
    logScript("Whisper: Text file created at " . $text_file . ", " . strlen($text) . " characters.");

    update_field($resource,$whisper_field,$text);
    logScript("Whisper: Updated field with value.");

    $subtitle_path=$folder . "/whisper.srt";
    if ($whisper_subtitles && file_exists($subtitle_path)) {
        $alt=add_alternative_file($resource,$lang["whisper_subtitles_name"],"SRT file",$resource . ".srt","srt",filesize($subtitle_path));
        logScript("Whisper: Alternative file added with ref " . $alt);
        $alt_path=get_resource_path($resource,true,'',true, "srt", true, 1, false, '', $alt);
        logScript("Whisper: Alternative file path is " . $alt_path);
        copy($subtitle_path,$alt_path);
    }

    // Set processed
    ps_query("update resource set whisper_processed=1 where ref=?",["i",$resource]);
    return true;
}
