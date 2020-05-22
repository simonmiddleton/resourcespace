<?php
function HookRse_workflowSearchRender_search_actions_add_option(array $options, array $urlparams)
    {
    $wf_actions_options = rse_workflow_compile_actions($urlparams);

    if(isset($GLOBALS["hook_return_value"]) && is_array($GLOBALS["hook_return_value"]))
        {
        // @see hook() for an explanation about the hook_return_value global
        $options = $GLOBALS["hook_return_value"];
        }

    return array_merge($options, $wf_actions_options);
    }