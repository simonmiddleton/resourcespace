<?php
function HookRse_workflowSearchRender_search_actions_add_option(array $options, array $urlparams)
    {
    global $internal_share_access;

    $k = trim((isset($urlparams["k"]) ? $urlparams["k"] : ""));

    if($k != "" && $internal_share_access === false)
        {
        return false;
        }

    $wf_actions_options = rse_workflow_compile_actions($urlparams);

    if(isset($GLOBALS["hook_return_value"]) && is_array($GLOBALS["hook_return_value"]))
        {
        // @see hook() for an explanation about the hook_return_value global
        $options = $GLOBALS["hook_return_value"];
        }

    return array_merge($options, $wf_actions_options);
    }