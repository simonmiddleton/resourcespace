<?php

function HookPropose_changesAllAddtoactions()
    {
	global $actions_propose_changes;
	if($actions_propose_changes)
		{
		$alleditablesql=do_search("","","",0,-1,"",false,0,false,false,"",false,true, true, true,true);    
		$changessql="SELECT pc.date,pc.resource AS ref, pc.user as user,group_concat(f.title) AS description, 'proposed_change' AS type FROM propose_changes_data pc JOIN resource_type_field f ON pc.resource_type_field=f.ref WHERE pc.resource in (select ref from (" . $alleditablesql . ") editable) GROUP BY pc.resource";
		return $changessql;  
		}
	return false;
    }
   
function HookPropose_changesAllShowfieldedit($field)
    {
    if(checkperm("f*") || checkperm("f" . $field))
        {
        return true;   
        }
    return false;
    }

function HookPropose_changesAllExport_add_tables()
    {
    return array("propose_changes_data"=>array("scramble"=>array("value")));
    }
