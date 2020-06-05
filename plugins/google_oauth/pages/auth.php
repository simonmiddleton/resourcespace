<?php
include dirname(__DIR__) . '/../../include/db.php';
include_once dirname(__DIR__) . '/../../include/login_functions.php';

$client = new Google_Client();
$client->setClientId($google_oauth_client_id);
$client->setClientSecret($google_oauth_client_secret);
$client->setRedirectUri(GOOGLE_OAUTH_REDIRECT_URI);
$client->addScope(array(Google_Service_Oauth2::USERINFO_PROFILE, Google_Service_Oauth2::USERINFO_EMAIL));

$code = getvalescaped('code', '');

if('' == $code && !google_oauth_is_authenticated())
    {
    header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
    exit();
    }
// We should not get to this page if we are authenticated and we don't have a new code to request new token
else if('' == $code && google_oauth_is_authenticated())
    {
    header('Location: ' . filter_var($baseurl, FILTER_SANITIZE_URL));
    exit();
    }

$access_token = $client->fetchAccessTokenWithAuthCode($code);

if(isset($access_token['error']))
    {
    echo $access_token['error_description'];
    die();
    }

$token_payload = $client->verifyIdToken($access_token['id_token']);

if(false === $token_payload)
    {
    echo 'Invalid token!';
    die();
    }

// If we've got so far, we are logged in via Google
$fullname               = ('' != trim($token_payload['name']) ? escape_check($token_payload['name']) : '');
$email                  = ('' != trim($token_payload['email']) ? escape_check($token_payload['email']) : '');
$google_oauth_id_token  = ('' != trim($access_token['id_token']) ? escape_check($access_token['id_token']) : '');
$google_oauth_sub_claim = ('' != trim($token_payload['sub']) ? escape_check($token_payload['sub']) : '');
$userref                = escape_check(google_oauth_getRsUserRefBySubClaim($token_payload['sub']));

// Existing user
if(0 < $userref)
    {
    $user_data = validate_user("u.ref = '{$userref}'");

    if(false === $user_data)
        {
        echo 'Invalid user!';
        die();
        }

    $username      = $user_data[0]['username'];
    $password      = make_password();
    $password_hash = hash('sha256', md5("RS{$username}{$password}"));

    sql_query("UPDATE user 
                  SET password = '{$password_hash}',
                      fullname = '{$fullname}',
                      email = '{$email}',
                      google_oauth_id_token = '{$google_oauth_id_token}'
                WHERE ref = '{$userref}'");
    }
// Create a new user if one could not be found
else
    {
    $username      = 'google_oauth_' . sql_value("SELECT max(ref) + 1 AS `value` FROM user", 0);
    $password      = make_password();
    $password_hash = hash('sha256', md5("RS{$username}{$password}"));

    $userref    = new_user($username);
    $user_group = escape_check($google_oauth_default_user_group);
    $comments   = escape_check($lang['google_oauth_user_comment']);

    sql_query("UPDATE user 
                  SET origin = 'google_oauth',
                      password = '{$password_hash}',
                      fullname = '{$fullname}',
                      email = '{$email}',
                      usergroup = '{$user_group}',
                      comments = '{$comments}',
                      google_oauth_id_token = '{$google_oauth_id_token}',
                      google_oauth_sub_claim = '{$google_oauth_sub_claim}'
                WHERE ref = '{$userref}'");
    }

// Generate a session hash
$session_hash = generate_session_hash($password_hash);
$login_data   = perform_login();

rs_setcookie(GOOGLE_OAUTH_COOKIE_NAME, $session_hash, 1, '/', '', 'https' == substr($baseurl, 0, 5), true);

header('Location: ' . filter_var($baseurl, FILTER_SANITIZE_URL));
exit();