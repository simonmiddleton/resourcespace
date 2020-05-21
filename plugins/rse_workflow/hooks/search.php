<?php
function HookRse_workflowSearchRender_search_actions_add_option(array $options, array $urlparams)
    {
    echo "<pre>";print_r($urlparams);echo "</pre>";

    rse_workflow_render_actions();





    if(isset($GLOBALS["hook_return_value"]) && is_array($GLOBALS["hook_return_value"]))
        {
        // @see hook() for an explanation about the hook_return_value global
        $options = $GLOBALS["hook_return_value"];
        }

    // $options_new = array_merge($options, $wf_actions_options);

    return $options;
    }