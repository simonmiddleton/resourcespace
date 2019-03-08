<?php
function HookRse_workflowSearchSearchparameterhandler()
    {
    global $archive, $archive_choices;

    // This applies only when doing a Simple Search
    if(getval('search_using_allowed_workflow_states', 0, true) == 0)
        {
        return;
        }

    // When doing an Advanced Search, this is not needed!
    if(
        is_array($archive_choices)
        && count(array_filter($archive_choices, function($value) { return $value != ''; })) > 0)
        {
        return;
        }

    $workflow_states = rse_workflow_get_archive_states();

    $simple_search_states = array();
    foreach($workflow_states as $workflow_state_ref => $workflow_state_detail)
        {
        if($workflow_state_detail['simple_search_flag'] == 0)
            {
            continue;
            }

        $simple_search_states[] = $workflow_state_ref;
        }

    if(count($simple_search_states) > 0)
        {
        $simple_search_states = implode(',', $simple_search_states);
        $archive = $simple_search_states;
        }

    return;
    }