<?php
function HookFacesManage_jobsAddjobtriggerpage()
{

    // Make sure this check takes place before $GLOBALS["hook_return_value"] can be unset by subsequent calls to hook()
    if(isset($GLOBALS["hook_return_value"]) && is_array($GLOBALS["hook_return_value"])) {
        // @see hook() for an explanation about the hook_return_value global
        $existing_scripts = $GLOBALS["hook_return_value"];
    } else {
        $existing_scripts = [];
    }

    $scripts = [
        0 => ['name' => 'Detect Faces (Insight Faces plugin)', 'script_name' => 'faces_detect', 'plugin' => 'faces'],
        1 => ['name' => 'Tag Faces (Insight Faces plugin)', 'script_name' => 'faces_tag', 'plugin' => 'faces'],
    ];

    return array_merge($existing_scripts, $scripts);
}