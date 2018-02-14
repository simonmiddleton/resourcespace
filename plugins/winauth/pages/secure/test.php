<?php
include dirname(__FILE__) . '/../../../../include/db.php';
include_once dirname(__FILE__) . '/../../../../include/general.php';
include_once dirname(__FILE__) . '/../../include/winauth_functions.php';

if(isset($_SERVER["AUTH_USER"]) && $_SERVER["AUTH_USER"] != "")
    {
    echo str_replace("[username]",$_SERVER["AUTH_USER"],$lang["winauth_user_info"]);
    }
else
    {
    echo $lang["winauth_not_logged_in"];
    }
