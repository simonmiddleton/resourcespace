<?php 
command_line_only();


// --- Set up
// $run_id = test_generate_random_ID(10);
$original_state = $GLOBALS;

$resource_jpg_file = create_resource(1, 0);
$resource_mp4_file = create_resource(3, 0);

$file_jpg = create_random_image([
    'text' => "Resource #$resource_jpg_file",
    'width' => 300,
    'height' => 150,
]);
if (isset($file_jpg['error']))
    {
    echo "{$file_jpg['error']} - ";
    return false;
    }

$file_mp4 = create_random_video([
    'text' => "Resource #$resource_mp4_file",
]);
if (isset($file_mp4['error']))
    {
    // This is considered an environment issue, not a code one!
    echo "INFO: {$file_mp4['error']} - ";
    return true;
    }

// Upload files (don't create previews, out of scope)
$enable_thumbnail_creation_on_upload = false;
upload_file($resource_jpg_file, false, false, false, $file_jpg['path'], false, true);
upload_file($resource_mp4_file, false, false, false, $file_mp4['path'], false, true);
// --- End of Set up

// var_dump(pathinfo($file_jpg['path'], PATHINFO_BASENAME));die;

// $dldFilename = get_download_filename($resource_mp4_file, '', 0, 'jpg');
// var_dump($dldFilename);


$use_cases = [
    [
        'name' => 'todo...',
        'input' => ['ref' => $resource_jpg_file, 'size' => '', 'alternative' => 0, 'ext' => 'jpg'],
        'expected' => "RS{$resource_jpg_file}_".pathinfo($file_jpg['path'], PATHINFO_BASENAME),
    ],
];
foreach($use_cases as $use_case)
    {
    // Set up the use case environment
    if(isset($use_case['setup']))
        {
        $use_case['setup']();
        }

    $result = get_download_filename(
        $use_case['input']['ref'],
        $use_case['input']['size'],
        $use_case['input']['alternative'],
        $use_case['input']['ext']
    );

    if($use_case['expected'] !== $result)
        {
        echo "Use case: {$use_case['name']} - ";
        return false;
        }
    }



// Tear down
$GLOBALS['enable_thumbnail_creation_on_upload'] = $original_state['enable_thumbnail_creation_on_upload'];
unset($run_id, $original_state, $resource_jpg_file, $resource_mp4_file, $file_jpg, $file_mp4);

return true;