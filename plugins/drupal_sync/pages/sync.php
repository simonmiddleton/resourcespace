<?php
include '../../../include/boot.php';
command_line_only();

// Configuration
$drupalBaseUrl = 'http://localhost/drupal/web'; // Replace with your Drupal site URL
$apiKey = 'zClqAEkm/oNVq/QrsPcc/7/DYqDDLRB5uiEU+yxB8Mk='; // Replace with your Drupal API key

// Function to upload a file to Drupal
function uploadFileToDrupal($filePath, $fileName, $drupalBaseUrl, $apiKey) {
    $url = $drupalBaseUrl . '/jsonapi/file/file';

    // Read binary file content
    $fileData = file_get_contents($filePath);

    // Initialize cURL for the file upload
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: ApiKey $apiKey",
        "Content-Disposition: file; filename=\"$fileName\""
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201) {
        $responseData = json_decode($response, true);
        return $responseData['data']['id']; // Return the file entity UUID
    } else {
        echo "File upload failed with HTTP code $httpCode: $response\n";
        return false;
    }
}

// Function to create a Media entity in Drupal
function createMediaEntity($fileUuid, $fileName, $drupalBaseUrl, $apiKey) {
    $url = $drupalBaseUrl . '/jsonapi/media/image/media-image';

    // Payload for creating the Media entity
    $data = [
        "data" => [
            "type" => "media--image",
            "attributes" => [
                "name" => $fileName
            ],
            "relationships" => [
                "field_media_image" => [
                    "data" => [
                        "type" => "file--file",
                        "id" => $fileUuid
                    ]
                ]
            ]
        ]
    ];

    // cURL request to create media
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: ApiKey $apiKey",
        "Content-Type: application/vnd.api+json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 201) {
        echo "Media entity created successfully: $fileName\n";
    } else {
        echo "Media creation failed with HTTP code $httpCode: $response\n";
    }
}

$filePath=get_resource_path(21069,true,'',false,'jpg');
echo $filePath;
$fileName="Image Name.jpg";

createMediaEntity(1, $fileName, $drupalBaseUrl, $apiKey);
exit();
// Step 1: Upload file to Drupal
$fileUuid = uploadFileToDrupal($filePath, $fileName, $drupalBaseUrl, $apiKey);

if ($fileUuid) {
    // Step 2: Create Media entity in Drupal
    createMediaEntity($fileUuid, $fileName, $drupalBaseUrl, $apiKey);
}
