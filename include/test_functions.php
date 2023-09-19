<?php

function resource_random_jpg($resource,$width, $height)
    {
    // Set random colours 
    $bg_r =  mt_rand(0,255);
    $bg_g = mt_rand(0,255);
    $bg_b = mt_rand(0,255);

    // Create image
    $test_image = imagecreate($width, $height);
    $bg_col = imagecolorallocate($test_image, $bg_r,$bg_g,$bg_b);
    $text_r = $bg_r < 128 ? $bg_r + 100 : $bg_r - 100;
    $text_g = $bg_g < 128 ? $bg_g + 100 : $bg_g - 100;
    $text_b = $bg_b < 128 ? $bg_b + 100 : $bg_b - 100;
    $text_col = imagecolorallocate($test_image, $text_r, $text_g, $text_b);
    imagestring($test_image, 5, 20, 15,  'Image ' . $resource, $text_col);

    $path = get_resource_path($resource,true,'',true,'jpg');
    imagejpeg($test_image, $path,50);
    create_previews($resource,false,'jpg');
    }

/**
 * Generate a random image which can be used during testing (e.g to upload, or create previews for)
 * 
 * @param array $info Set image parameters:
 * - text -> Image content text
 * - filename (optional) 
 * - width (optional) 
 * - height (optional) 
 * - bg[red|green|blue] -> Background colour (RGB), e.g $info['bg']['red'] = 234;
 */
function create_random_image(array $info): array
    {
    $width = $info['width'] ?? 150;
    $height = $info['height'] ?? 50;
    $filename = $info['filename'] ?? generateSecureKey(32);

    // Background colour
    $bg_r =  $info['bg']['red'] ?? mt_rand(0, 255);
    $bg_g = $info['bg']['green'] ?? mt_rand(0, 255);
    $bg_b = $info['bg']['blue'] ?? mt_rand(0, 255);

    // Text colour
    $text_r = $bg_r < 128 ? $bg_r + 100 : $bg_r - 100;
    $text_g = $bg_g < 128 ? $bg_g + 100 : $bg_g - 100;
    $text_b = $bg_b < 128 ? $bg_b + 100 : $bg_b - 100;

    // Generate image
    $img = imagecreate($width, $height);
    imagecolorallocate($img, $bg_r, $bg_g, $bg_b);
    imagestring($img, 5, 20, 15, $info['text'], imagecolorallocate($img, $text_r, $text_g, $text_b));
    $path = get_temp_dir() . DIRECTORY_SEPARATOR . "{$filename}.jpg";
    if (imagejpeg($img, $path, 70))
        {
        return [
            'path' => $path,
        ];
        }

    return [];
    }