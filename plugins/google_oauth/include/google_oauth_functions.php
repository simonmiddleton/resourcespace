<?php
/**
* Initiate the authenticate with Google process
* 
* @return void
*/
function google_oauth_authenticate()
    {
    header('Location: ' . filter_var(GOOGLE_OAUTH_REDIRECT_URI, FILTER_SANITIZE_URL));
    exit();

    return;
    }


/**
* Checks if user is currently authenticated through Google
* 
* @uses validate_user()
* @uses escape_check()
* @uses sql_value()
* @uses Google_Client
* 
* @return boolean
*/
function google_oauth_is_authenticated()
    {
    global $google_oauth_client_id;

    if(!isset($_COOKIE[GOOGLE_OAUTH_COOKIE_NAME]))
        {
        return false;
        }

    $goauth_cookie = '' != $_COOKIE[GOOGLE_OAUTH_COOKIE_NAME] ? $_COOKIE[GOOGLE_OAUTH_COOKIE_NAME] : '';
    $user_data     = validate_user("session = '" . escape_check($goauth_cookie) . "'");

    if(false === $user_data)
        {
        return false;
        }

    $user_ref = isset($user_data[0]['ref']) ? $user_data[0]['ref'] : 0;
    $id_token = sql_value("SELECT google_oauth_id_token AS `value` FROM user WHERE ref = {$user_ref}", '');

    if('' == $id_token)
        {
        return false;
        }

    $client = new Google_Client();
    $client->setClientId($google_oauth_client_id);
    if($client->verifyIdToken($id_token))
        {
        return true;
        }

    return false;
    }


/**
* Log out users logged in through Google. Revoke the token as well.
* 
* @uses validate_user()
* @uses escape_check()
* @uses sql_value()
* @uses Google_Client
* @uses rs_setcookie()
* 
* @return void
*/
function google_oauth_signout()
    {
    global $google_oauth_client_id;

    $goauth_cookie = isset($_COOKIE[GOOGLE_OAUTH_COOKIE_NAME]) ? $_COOKIE[GOOGLE_OAUTH_COOKIE_NAME] : '';
    $user_data     = validate_user("session = '" . escape_check($goauth_cookie) . "'");

    if(false === $user_data)
        {
        return;
        }

    $user_ref = isset($user_data[0]['ref']) ? escape_check($user_data[0]['ref']) : 0;
    $id_token = sql_value("SELECT google_oauth_id_token AS `value` FROM user WHERE ref = {$user_ref}", '');

    $client = new Google_Client();
    $client->setClientId($google_oauth_client_id);
    $client->revokeToken($id_token);

    sql_query("UPDATE user SET session = NULL, google_oauth_id_token = NULL WHERE ref = '{$user_ref}'");

    rs_setcookie(GOOGLE_OAUTH_COOKIE_NAME, '', 0, '/');

    return;
    }


/**
* Get a user ref based on Google's sub claim.
* 
* @uses escape_check()
* @uses sql_value()
* 
* @param string $sub Google's sub claim
* 
* @return integer
*/
function google_oauth_getRsUserRefBySubClaim($sub)
    {
    $sub_escaped = escape_check($sub);

    return sql_value("SELECT ref AS `value` FROM user WHERE google_oauth_sub_claim = '{$sub_escaped}'", 0);
    }