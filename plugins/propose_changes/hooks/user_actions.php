<?php
function HookPropose_changesUser_actionsUpdateactiontypes($actiontypes)
    {
	global $actions_propose_changes;
	if($actions_propose_changes)
		{
		$addactiontypes=array("proposed_change");
		return array_merge($actiontypes,$addactiontypes);
		}
	return false;
    }
    
function HookPropose_changesUser_actionsActioneditlink($action)
    {
    global $baseurl_short;
    if($action["type"]=="proposed_change")
        {
        return array("editlink" => $baseurl_short . "plugins/propose_changes/pages/propose_changes.php","viewlink" => $baseurl_short . "pages/view.php");
        }
    return false;
    }
    
