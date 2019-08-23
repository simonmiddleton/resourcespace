<?php
function HookAdobe_linkDownloadProvideusercredentials()
    {
    global $user_select_sql, $scramble_key, $ref, $session_hash, $usercredentialsprovided;
    $adb_link_key = getval("adb_key","");
    $adb_link_parts = explode(":", $adb_link_key);
    
    if(count($adb_link_parts) != 2)
        {
        return false;
        }
      
    $ref = getvalescaped('ref', '', true);
    $adb_link_user = $adb_link_parts[0];
    $adb_link_hash = $adb_link_parts[1];
    $keycheck = adobe_link_genkey($adb_link_user,$ref);
    if(!is_numeric($adb_link_user) || $keycheck != $adb_link_hash)
        {
        debug("adobe_link: Invalid key passed to download.php for user ref: " . $adb_link_user . ", resource ID#" . $ref . ". Received: '" . $adb_link_hash . "' .Expected: '" . $keycheck . "'");
        return false;
        }

    $user_select_sql="and u.ref='" . escape_check($adb_link_user) . " '";
    $anonymous_login = $adb_link_user;
    $session_hash = "";
    $usercredentialsprovided = true;
    return true;
    }

// Added to bypass cookies_notification check
function HookAdobe_linkDownloadCookies_notification_bypass()
    {
    return true;
    }
