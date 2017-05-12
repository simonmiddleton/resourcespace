<?php
if('cli' != PHP_SAPI)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied - Command line only!');
    }

include __DIR__ . '/../../include/db.php';
include_once __DIR__ . '/../../include/general.php';
include_once __DIR__ . '/../../include/resource_functions.php';

ob_end_clean();
restore_error_handler();

echo PHP_EOL;

// Init
$convert_fullpath             = get_utility_path('im-convert');
$python_fullpath              = get_utility_path('python');
$faceRecognizerTrainer_path   = __DIR__ . '/../../lib/facial_recognition/faceRecognizerTrainer.py';
$facial_recognition_tag_field = (int) escape_check($facial_recognition_tag_field);
$allow_training               = false;
$no_previews_found_counter    = 0;
$prepared_trainer_data        = '';

if('' === $facial_recognition_face_recognizer_models_location)
    {
    echo 'Error: No location set for FaceRecognizer data!' . PHP_EOL;
    exit(1);
    }

if(false === $convert_fullpath)
    {
    echo 'Error: Could not find ImageMagick "convert" utility!' . PHP_EOL;
    exit(1);
    }

if(false === $python_fullpath)
    {
    echo 'Error: Could not find Python!' . PHP_EOL;
    exit(1);
    }


// Step 1: Preparing the data
$annotations = sql_query(
       "SELECT a.resource,
               a.x,
               a.y,
               a.width,
               a.height,
               n.ref AS node_id,
               (SELECT preview_extension FROM resource AS r WHERE r.ref = a.resource) AS resource_preview_ext
          FROM annotation_node AS an
    INNER JOIN annotation AS a ON a.ref = an.annotation
    INNER JOIN node AS n ON n.ref = an.node AND n.resource_type_field = a.resource_type_field
         WHERE a.resource_type_field = '{$facial_recognition_tag_field}'
      ORDER BY n.ref ASC"
);

foreach($annotations as $annotation)
    {
    $preview_image_path  = get_resource_path($annotation['resource'], true, 'pre', true, $annotation['resource_preview_ext']);

    if(!file_exists($preview_image_path))
        {
        echo "Could not find the preview image at '{$preview_image_path}'" . PHP_EOL;
        $no_previews_found_counter++;
        continue;
        }

    $prepared_image_path = get_resource_path(
        $annotation['resource'],
        true,
        FACIAL_RECOGNITION_CROP_SIZE_PREFIX . $annotation['node_id'],
        true,
        'pgm');

    // Use existing prepared image if one is found.
    // A line of prepared data is expected to be as /path/to/prepared/file.ext;label where label MUST be an integer 
    if(file_exists($prepared_image_path))
        {
        $prepared_trainer_data .= "{$prepared_image_path};{$annotation['node_id']}" . PHP_EOL;
        continue;
        }

    echo "Preparing image for resource ID {$annotation['resource']} and node ID {$annotation['node_id']}" . PHP_EOL;

    $command                     = $convert_fullpath;
    $preview_image_path_escaped  = escapeshellarg($preview_image_path);
    $prepared_image_path_escaped = escapeshellarg($prepared_image_path);

    list($preview_image_width, $preview_image_height) = getimagesize($preview_image_path);

    $x      = escapeshellarg(round($annotation['x'] * $preview_image_width, 0));
    $y      = escapeshellarg(round($annotation['y'] * $preview_image_height, 0));
    $width  = escapeshellarg(round($annotation['width'] * $preview_image_width, 0));
    $height = escapeshellarg(round($annotation['height'] * $preview_image_height, 0));

    $command        .= " {$preview_image_path_escaped} -colorspace gray -depth 8";
    $command        .= " -crop {$width}x{$height}+{$x}+{$y}";
    $command        .= " -resize 90x90\>";
    $command        .= " +repage {$prepared_image_path_escaped}";
    $command_output  = run_command($command);

    $prepared_trainer_data .= "{$prepared_image_path};{$annotation['node_id']}" . PHP_EOL;

    resource_log($annotation['resource'], LOG_CODE_TRANSFORMED, '', '', '', $lang['facial_recognition_prepare_image_log']);
    } // end of foreach($annotations as $annotation)


// Do not proceed with the training if no previews could be found or no annotations are found
if($no_previews_found_counter === count($annotations))
    {
    exit(1);
    }

$prepared_data_path = "{$facial_recognition_face_recognizer_models_location}/prepared_data.csv";

// Save prepared data to a CSV file
$prepared_data_file = fopen($prepared_data_path, 'w+b');

if(false === $prepared_data_file)
    {
    exit(1);
    }

fwrite($prepared_data_file, $prepared_trainer_data);
fclose($prepared_data_file);


// Step 2: Training FaceRecognizer
if(!file_exists($prepared_data_path))
    {
    echo 'Error: Could not find the prepared data CSV file for FaceRecognizer trainer!' . PHP_EOL;
    exit(1);
    }

$command        = "{$python_fullpath} {$faceRecognizerTrainer_path} {$prepared_data_path}";
$command_output = run_command($command);

echo $command_output;
echo PHP_EOL;