<?php

/**
 * Return total successful image edits for the past 30 days.
 *
 * @return  array   Array of data for processing in get_system_status().
 */
function HookOpenai_image_editAllExtra_checks() : array
    {
    $message['openai_image_edit'] = [
        'status' => 'OK',
        'info' => ps_value("select sum(`count`) value from daily_stat where
            activity_type='OpenAI Image Edit'
        and (`year`=year(now()) or (month(now())=1 and `year`=year(now())-1))
        and (`month`=month(now()) or `month`=month(now())-1 or (month(now())=1 and `month`=12))
        and datediff(now(), concat(`year`,'-',lpad(`month`,2,'0'),'-',lpad(`day`,2,'0')))<=30
            ", [], 0) // Note - limit to this month and last month before the concat to get the exact period; ensures not performing the concat on a large set of data.
    ];
    return $message;
    }