<?php
function HookAdobe_linkUpload_pluploadProvideusercredentials()
    {
    global $user_select_sql, $scramble_key, $ref, $session_hash, $usercredentialsprovided;
    return adobe_link_check_credentials();
    }
    
// Added to bypass cookies_notification check
function HookAdobe_linkUpload_pluploadCookies_notification_bypass()
    {
    return true;
    }