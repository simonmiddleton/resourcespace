<?php
/*

Populate ResourceSpace with random images from Wikimedia Commmons to aid testing at scale.

*/

include_once dirname(__FILE__) . '/../../include/boot.php';
include_once dirname(__FILE__) . '/../../include/image_processing.php';

command_line_only();
setup_command_line_user();


// Settings
$resource_type = 1; // Adjust to your desired type
$title_field = 8;   // Adjust to your 'Title' field ref
$image_size = 400; // Image size in pixels to request


// Disable output buffering
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);



function download_file_with_user_agent($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Replace with your own project/contact info per Wikimedia policy
    curl_setopt($ch, CURLOPT_USERAGENT, 'ResourceSpaceCommonsImport/1.0 (info@resourcespace.com)');

    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code !== 200 || $data === false) {
        echo "  CURL error or non-200 response ($http_code) from $url\n";
        return false;
    }

    curl_close($ch);
    return $data;
}


// Get number of images to import from CLI argument
if ($argc < 2 || !is_numeric($argv[1]) || (int)$argv[1] < 1) {
    echo "Usage: php commons_import.php <number_of_images>\n";
    exit(1);
}

$number_to_import = (int)$argv[1];



echo "Importing $number_to_import images from Wikimedia Commons...\n";

// Function to fetch random image metadata from Wikimedia Commons
function get_random_commons_images($count)
{
    global $image_size;

    $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
        'action' => 'query',
        'generator' => 'random',
        'grnnamespace' => 6, // File namespace
        'grnlimit' => $count,
        'prop' => 'imageinfo',
        'iiprop' => 'url',
        'iiurlwidth' => $image_size,            // Request 400px wide image
        'iiurlheight' => $image_size,           // (optional) limit height too
        'format' => 'json'
    ]);

    $response = file_get_contents($url);
    if ($response === false) {
        die("Error: Failed to fetch from Wikimedia Commons API.\n");
    }

    $data = json_decode($response, true);
    if (!isset($data['query']['pages'])) {
        die("Error: No images found in API response.\n");
    }

    $images = [];
    foreach ($data['query']['pages'] as $page) {
        if (!isset($page['imageinfo'][0]['thumburl'])) {
            continue; // No thumbnail, skip
        }
        $images[] = [
            'title' => $page['title'],
            'url' => $page['imageinfo'][0]['thumburl']
        ];
    }

    return $images;
}

$imported_count = 0;
$batch_size = 10;

while ($imported_count < $number_to_import) {
    $remaining = $number_to_import - $imported_count;
    $fetch_count = min($batch_size, $remaining);

    $images = get_random_commons_images($fetch_count);

    foreach ($images as $img) {
        echo "Processing: {$img['title']}\n";

        // Skip non-JPEGs based on file extension
        $ext = strtolower(pathinfo(parse_url($img['url'], PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg'])) {
            echo "  Skipping non-JPEG file (.$ext).\n";
            continue;
        }

        // Download the image
        $tmp_file = get_temp_dir() . '/' . uniqid('commons_') . '.jpg';
        $image_data = download_file_with_user_agent($img['url']);
        if ($image_data === false) {
            echo "  Failed to download image.\n";
            continue;
        }

        file_put_contents($tmp_file, $image_data);

        // Create new resource
        $ref = create_resource($resource_type, 0); 
        if ($ref <= 0) {
            echo "  Failed to create resource.\n";
            unlink($tmp_file);
            continue;
        }

        // Set title
        $clean_title = preg_replace('/^File:/', '', $img['title']);
        $clean_title = preg_replace('/\.[^.]+$/', '', $clean_title);
        update_field($ref, $title_field, $clean_title);

        echo "  Uploading from $tmp_file\n";

        // Upload file to resource
        $result = upload_file($ref, true, false, false, $tmp_file, false);
        if ($result !== false) {
            echo "  Resource $ref created and image uploaded.\n";
            $imported_count++;
            echo "  Imported so far: $imported_count / $number_to_import\n";
        } else {
            echo "  Failed to upload image to resource $ref.\n";
            delete_resource($ref);
        }

        if (file_exists($tmp_file)) {unlink($tmp_file);}

        if ($imported_count >= $number_to_import) {
            break; // Exit early if we hit the target mid-batch
        }
    }
}

echo "✅ Import complete: $imported_count JPEG images added.\n";
