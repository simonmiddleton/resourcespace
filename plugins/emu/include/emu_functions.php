<?php
function check_script_last_ran(&$emu_script_last_ran = '')
    {
    global $lang, $emu_script_failure_notify_days;

    $emu_script_last_ran = $lang['status-never'];

    $script_last_ran                   = sql_value('SELECT `value` FROM sysvars WHERE name = "last_emu_import"', '');
    $emu_script_failure_notify_seconds = intval($emu_script_failure_notify_days) * 24 * 60 * 60;

    if('' != $script_last_ran && time() >= (strtotime($script_last_ran) + $emu_script_failure_notify_seconds))
        {
        $emu_script_last_ran = date('l F jS Y @ H:m:s', strtotime($script_last_ran));

        return true;
        }

    return false;
    }