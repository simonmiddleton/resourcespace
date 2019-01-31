<?php
include_once __DIR__ . "/../../include/db.php";
include_once __DIR__ . "/../../include/general.php";
if(isset($anonymous_login))
    {
    if(is_array($anonymous_login))
        {
        foreach($anonymous_login as $user)
            {
            $anonref = sql_value("SELECT ref value FROM user WHERE username='" . $user . "'",0);
            echo "Deleting old anonymous collections for user " . $user ."(ref: " . $anonref . ")\r\n";
            $dcols = delete_old_collections($anonref,7);
            echo "Deleted " . $dcols . " collections for user " . $user ."\r\n";
            }
        }
    else
        {
        $anonref = sql_value("SELECT ref value FROM user WHERE username='" . $anonymous_login . "'",0);
        echo "Deleting old anonymous collections for user " . $anonymous_login ."(ref: " . $anonref . ")\r\n";
        $dcols = delete_old_collections($anonref,7);
        echo "Deleted " . $dcols . " collections for user " . $anonymous_login ."\r\n";
        }
    }

