<?php
include '../../../include/boot.php';
command_line_only();

// Configuration
$drupalBaseUrl = "http://localhost/drupal/web";
$oauthTokenUrl = $drupalBaseUrl . "/oauth/token";
$username = "api";
$password = "api";
$clientId = "your_client_id";
$clientSecret = "your_client_secret";


$filePath=get_resource_path(21069,true,'',false,'jpg');
$filename = basename($filePath);

// 1. Get an OAuth2 Access Token using password grant
$accessToken = getAccessToken($oauthTokenUrl, $clientId, $clientSecret, $username, $password);
if (!$accessToken) {
    die("Failed to retrieve access token.\n");
}

// 2. Upload the file using the binary upload endpoint
// Adjust this endpoint if your media type and field differ
$uploadUrl = $drupalBaseUrl . "/jsonapi/media/image/field_media_image";

$fileResponse = uploadFileBinary($uploadUrl, $filePath, $accessToken);
if (!isset($fileResponse['data']['id'])) {
    die("File upload failed: " . print_r($fileResponse, true) . "\n");
}

$fileUuid = $fileResponse['data']['id'];

// 3. Create a media entity referencing the uploaded file
$mediaUrl = $drupalBaseUrl . "/jsonapi/media/image";
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

$mediaResponse = sendJsonApiRequest($mediaUrl, $mediaPayload, $accessToken, "POST");
if (!isset($mediaResponse['data']['id'])) {
    die("Failed to create media: " . print_r($mediaResponse, true) . "\n");
}

echo "Media created successfully with UUID: " . $mediaResponse['data']['id'] . "\n";


/**
 * Obtain an OAuth2 access token using the password grant.
 *
 * @param string $tokenUrl
 * @param string $clientId
 * @param string $clientSecret
 * @param string $username
 * @param string $password
 * @return string|false Access token or false on failure
 */
function getAccessToken($tokenUrl, $clientId, $clientSecret, $username, $password) {
    $ch = curl_init($tokenUrl);

    $data = [
        "grant_type" => "password",
        "client_id" => $clientId,
        "client_secret" => $clientSecret,
        "username" => $username,
        "password" => $password
    ];

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die("cURL error: " . curl_error($ch));
    }
    curl_close($ch);

    $decoded = json_decode($response, true);
    if (isset($decoded['access_token'])) {
        return $decoded['access_token'];
    }
    else   
    { print_r($response); }

    return false;
}

/**
 * Upload a file using the binary upload JSON:API endpoint.
 *
 * @param string $url
 * @param string $filePath
 * @param string $accessToken
 * @return array Decoded JSON response
 */
function uploadFileBinary($url, $filePath, $accessToken) {
    $ch = curl_init($url);

    $filename = basename($filePath);
    $headers = [
        "Content-Type: application/octet-stream",
        "Content-Disposition: file; filename=\"{$filename}\"",
        "Authorization: Bearer {$accessToken}",
        "Accept: application/vnd.api+json"
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filePath));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

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

/**
 * Send a generic JSON:API request with a JSON payload.
 *
 * @param string $url
 * @param array $payload
 * @param string $accessToken
 * @param string $method
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

    $response = curl_exec($ch);
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
