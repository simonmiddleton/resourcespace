<?php
# Disable inactive users
if (is_numeric($inactive_user_disable_days) && $inactive_user_disable_days > 0)
    {
    sql_query("UPDATE user SET approved=2 
            WHERE
                (created is null OR created<date_sub(now(), interval $inactive_user_disable_days day)) 
            AND
                (last_active is null OR last_active<date_sub(now(), interval $inactive_user_disable_days day))
            AND approved=1"); 
    echo " - " . sql_affected_rows() . " users disabled" . PHP_EOL;
    }