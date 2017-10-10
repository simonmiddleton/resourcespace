<?php
function HookRse_workflowAllInitialise()
     {
	 include_once dirname(__FILE__)."/../include/rse_workflow_functions.php";
	 include_once dirname(__FILE__)."/../../../include/language_functions.php";
     # Deny access to specific pages if RSE_KEY is not enabled and a valid key is not found.
     global $pagename, $additional_archive_states, $fixed_archive_states;
    
    # Update $archive_states and associated $lang variables with entries from database
	$wfstates=rse_workflow_get_archive_states();
	
	global $lang;
	foreach($wfstates as $wfstateref=>$wfstate)
		{
		if (!$wfstate['fixed'])
			{
			$additional_archive_states[]=$wfstateref;
            }
        else
            {
            // Save for later so we know which are editable
            $fixed_archive_states[] = $wfstateref;
            }
        $lang["status" . $wfstateref] =  i18n_get_translated($wfstate["name"]);
		}
    natsort($additional_archive_states);		 
    }
