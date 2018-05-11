<?php
function updateAcceptedCookiesUse($user_id, $option)
    {
    if(!is_numeric($user_id))
        {
        return false;
        }

    $user_id          = escape_check($user_id);
    $accepted_cookies = "'" . escape_check($option) . "'";

    if(!is_numeric($option))
        {
        $accepted_cookies = 'NULL';
        }

    $sql = "UPDATE user SET accepted_cookies = {$accepted_cookies} WHERE ref = '{$user_id}'";
    sql_query($sql);

    return true;
    }