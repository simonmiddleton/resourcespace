<?php
function HookGoogle_oauthAllAfterregisterplugin()
    {
    global $google_oauth_dependencies_ready, $google_oauth_lib_autoload;

    if(file_exists($google_oauth_lib_autoload))
        {
        require_once $google_oauth_lib_autoload;
        $google_oauth_dependencies_ready = true;
        }

    return;
    }


function HookGoogle_oauthAllPreheaderoutput()
    {
    global $baseurl, $pagename, $google_oauth_standard_login, $google_oauth_use_standard_login_by_default;

    if(google_oauth_is_authenticated())
        {
        // Make sure we don't ask the user to type in a password when deleting resources, since we don't have it!
        global $delete_requires_password;

        $delete_requires_password = false;
        
        return;
        }

    // If authenticated do nothing and return
    if(isset($_COOKIE['user']))
        {
        return;
        }

    // Go to login page if system allows us to get to it
    $query_string = (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
    if(
        !google_oauth_is_authenticated()
        && $google_oauth_standard_login
        // We either use standard RS login by default or the user made a direct request to login because redirects
        // usually contain a url param
        && ($google_oauth_use_standard_login_by_default || ('login' == $pagename && '' == $query_string))
    )
        {
        return;
        }

    // Allow external shares to bypass Google SSO?
    global $google_oauth_xshares_bypass_sso;
    $k = getvalescaped('k', '');
    if(
        $google_oauth_xshares_bypass_sso
        && '' != $k 
        && (
            // Hard to determine at this stage what we consider a collection/ resource ID so we
            // use the most general ones
            check_access_key_collection(str_replace('!collection', '', getvalescaped('search', '')), $k)
            || check_access_key(getvalescaped('ref', ''), $k)
        )
    )
        {
        return;
        }

    // Go ahead and authenticate before continuing any further
    google_oauth_authenticate();

    return;
    }


function HookGoogle_oauthAllProvideusercredentials()
    {
    global $baseurl, $session_hash;

    // If authenticated do nothing and return
    if(isset($_COOKIE['user']))
        {
        if(isset($_COOKIE[GOOGLE_OAUTH_COOKIE_NAME]))
            {
            google_oauth_signout();
            }

        return true;
        }

    if(google_oauth_is_authenticated())
        {
        $session_hash = isset($_COOKIE[GOOGLE_OAUTH_COOKIE_NAME]) ? $_COOKIE[GOOGLE_OAUTH_COOKIE_NAME] : '';

        return true;
        }

    return false;
    }


function HookGoogle_oauthAllCheckuserloggedin()
    {
    return google_oauth_is_authenticated();
    }