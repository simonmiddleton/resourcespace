<?php
include __DIR__ . '/../../../../include/boot.php';
include_once __DIR__ . '/../../include/winauth_functions.php';

if(isset($_SERVER["AUTH_USER"]) && $_SERVER["AUTH_USER"] != "")
    {
    echo str_replace("[username]",$_SERVER["AUTH_USER"],$lang["winauth_user_info"]);
    }
else
    {
    echo escape($lang["winauth_not_logged_in"]);
    }
