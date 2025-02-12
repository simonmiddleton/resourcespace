<?php
include '../../../include/boot.php';
command_line_only();

// Configuration
$drupalBaseUrl = 'http://localhost/drupal/web'; // Replace with your Drupal site URL
$accessToken = 'zClqAEkm/oNVq/QrsPcc/7/DYqDDLRB5uiEU+yxB8Mk='; // Replace with your Drupal API key

// Configuration
$fileResourceUrl = $drupalBaseUrl . "/jsonapi/file/file";
$mediaResourceUrl = $drupalBaseUrl . "/jsonapi/media/image";

$filePath=get_resource_path(21069,true,'',false,'jpg');
$filename = basename($filePath);

// Read and base64-encode the file.
$fileData = base64_encode(file_get_contents($filePath));

// 1. Upload the file to create a file entity.
$filePayload = [
  "data" => [
    "type" => "file--file",
    "attributes" => [
      "data" => $fileData,
      "filename" => $filename,
      "uri" => "public://" . microtime() . ".jpg",
      "filemime" => "image/jpeg",
    ]
  ]
];

$fileResponse = sendJsonApiRequest($fileResourceUrl, $filePayload, $accessToken, "POST");

if (!isset($fileResponse['data']['id'])) {
    die("Failed to upload file: " . print_r($fileResponse, true));
}

// The file UUID from the newly created file--file entity
$fileUuid = $fileResponse['data']['id'];

// 2. Create a media entity referencing the file
// Adjust "name" and field reference "field_media_image" to match your Drupal configuration.
$mediaPayload = [
  "data" => [
    "type" => "media--image",
    "attributes" => [
      "name" => "My Uploaded Media"
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

$mediaResponse = sendJsonApiRequest($mediaResourceUrl, $mediaPayload, $accessToken, "POST");

if (!isset($mediaResponse['data']['id'])) {
    die("Failed to create media: " . print_r($mediaResponse, true));
}

echo "Media created successfully with UUID: " . $mediaResponse['data']['id'];


/**
 * Helper function to send a JSON:API request via cURL
 *
 * @param string $url
 * @param array $payload
 * @param string $accessToken
 * @param string $method
 *
 * @return array Decoded JSON response
 */
function sendJsonApiRequest($url, $payload, $accessToken, $method = "POST") {
    $ch = curl_init($url);

    $headers = [
        "Content-Type: application/vnd.api+json",
        "Accept: application/vnd.api+json",
        "Authorization: Bearer $accessToken"
    ];

    $dataString = json_encode($payload);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // If you're dealing with HTTPS and have certificate issues, you may need:
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);

    if (curl_errno($ch)) {
        die("cURL error: " . curl_error($ch));
    }

    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($decoded === null) {
        die("Invalid JSON response: " . $response);
    }

    return $decoded;
}
