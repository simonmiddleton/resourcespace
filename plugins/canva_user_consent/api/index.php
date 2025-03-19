<?php

include "../../../include/boot.php";
header('Content-Type: application/json');
include_once "../../../include/login_functions.php";
include_once '../../../include/api_functions.php';
include_once dirname(__FILE__) . "/../include/canva_user_consent_functions.php";

global $plugins;
if (!in_array("canva_user_consent", $plugins)) {
    header("Status: 403 plugin not activated");
    exit($lang["error-plugin-not-activated"]);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => false, "message" => "Method not allowed"]);
    exit;
}

if (isset($_POST['auth'])) {
    $auth_code = $_POST['auth'];
    $is_logout = $_POST['isLogout'];
    $tokenParts = explode('.', $auth_code);

    if (count($tokenParts) !== 3) {
        echo json_encode(["status" => false, "message" => "Invalid token","isLogout" => $is_logout]);
        exit;
    }
    // Decode the header and payload (which are JSON)
    $payload = json_decode(base64UrlDecode($tokenParts[1]), true);
    $userId = $payload['userId'];

    // Retrieve the user by canva_id using the provided auth code
    $data = get_key_by_canva_userid($userId, $is_logout);
    http_response_code(200); // Set status code to 200 OK
    if ($data) {
        echo json_encode(["status" => true, "message" => "Auth success", 'data' => $data]);
    } else {
        echo json_encode(["status" => false, "message" => "Auth check"]);
    }
} else {
    // Set status code to 400 Bad Request if auth code is missing
    http_response_code(400);
    echo json_encode(["status" => false, "message" => "Please provide canva authcode ID"]);
}
