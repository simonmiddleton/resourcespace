<?php

function HookPropose_changesAllAddtoactions()
    {
	global $actions_propose_changes;
	if($actions_propose_changes)
		{
        $alleditable_query = new PreparedStatementQuery();  
    
        # TODO Adjust return from do_search() after it is ported to return an object
        # FROM: $alleditable_query->sql=do_search(
        # TO: $alleditable_query=do_search(
		$alleditable_query->sql=do_search("","","",0,-1,"",false,0,false,false,"",false,true, true, true,true);
        
        $alleditable_changes_query= new PreparedStatementQuery();  
        
		$alleditable_changes_query->sql="SELECT pc.date,pc.resource AS ref, pc.user as user,group_concat(f.title) AS description, 'proposed_change' AS type 
                        FROM propose_changes_data pc JOIN resource_type_field f ON pc.resource_type_field=f.ref 
                        WHERE pc.resource in (select ref from (" . $alleditable_query->sql . ") editable) GROUP BY pc.resource";
        $alleditable_changes_query->parameters = array_merge($alleditable_query->parameters, $alleditable_changes_query->parameters);
		return $alleditable_changes_query;  
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
