<?php
// Dependencies are checked in HookGoogle_oauthAllAfterregisterplugin()
$google_oauth_dependencies_ready = false;

# Download the Google PHP API client to /lib/google_api_php_client_2.2.0/ or other path as specified below:
$google_oauth_lib_autoload = dirname(__DIR__) . '/../../lib/google_api_php_client_2.2.0/vendor/autoload.php';

include_once __DIR__ . '/../include/google_oauth_functions.php';


global $baseurl;
define('GOOGLE_OAUTH_REDIRECT_URI', "{$baseurl}/plugins/google_oauth/pages/auth.php");
define('GOOGLE_OAUTH_COOKIE_NAME', 'GOASES');


$google_oauth_client_id     = '';
$google_oauth_client_secret = '';

$google_oauth_default_user_group = 2;

// Allow external shares to bypass SSO. For security reasons it should be FALSE by default!
$google_oauth_xshares_bypass_sso = false;

// Allow users to log in with RS accounts as well as using Google accounts?
$google_oauth_standard_login = true;

// Prefer standard login (ie. system redirects to login.php by default) -  users decide how they login (RS/ Google account)
$google_oauth_use_standard_login_by_default = true;
