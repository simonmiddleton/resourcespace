<?php

/**
 * Retrieve a list of user actions for the My Actions area.
 *
 * @param  boolean $countonly Return the count of actions instead of the actions themselves
 * @param  string $type Filter the actions based on action type
 *     The available inputs are:
 *         resourcereview
 *         resourcerequest
 *         userrequest
 * @param  string $order_by
 * @param  string $sort
 * 
 * @return mixed Count or array of actions
 */
function get_user_actions($countonly=false,$type="",$order_by="date",$sort="DESC")
	{
    global $actions_notify_states, $actions_resource_types_hide, $default_display, $list_display_fields, $search_all_workflow_states,$actions_approve_hide_groups,
    
    $actions_resource_review,$actions_resource_requests,$actions_account_requests, $view_title_field, $actions_on,$messages_actions_usergroup;
        
    $actionsql="";    
    $filtered = $type!="";
    
    if(!$actions_on){return array();}
        
    if($actions_resource_review && (!$filtered || 'resourcereview'==$type))
        {
        $search_all_workflow_states = false;
        $default_display	= $list_display_fields;
        $editable_resource_sql=get_editable_resource_sql($countonly);
        $actionsql .="SELECT creation_date as date,ref, created_by as 
        user, field" . $view_title_field . " as description,'resourcereview' as type FROM (" . $editable_resource_sql . ") resources" ;
        }
    if(checkperm("R") && $actions_resource_requests && (!$filtered || 'resourcerequest'==$type))
        {
        $request_sql = get_requests(true,true,true);       
        $actionsql .= (($actionsql!="")?" UNION ":"") . "SELECT created 
        as date,ref, user, substring(comments,21) as description,'resourcerequest' as type FROM (" . $request_sql . ") requests";
        }
    if(checkperm("u") && $actions_account_requests && (!$filtered || 'userrequest'==$type))
        {
        $availgroups=get_usergroups(true);
        $get_groups=implode(",",array_diff(array_column($availgroups,"ref"),explode(",",$actions_approve_hide_groups)));
        $account_requests_sql = 
        get_users($get_groups,"","u.created",true,-1,0,true,"u.ref,u.created,u.fullname,u.email,u.username, u.comments"); 
        $actionsql .= (($actionsql!="")?" UNION ":"") . "SELECT created 
        as date,ref,ref as user,comments as description,'userrequest' as type FROM (" . $account_requests_sql . ") users";
        }
        
    $hookactionsql = hook("addtoactions");
    
    if($hookactionsql != false){$actionsql = (($actionsql!="")?$actionsql . " UNION ":"") . $hookactionsql;}
    
    if($actionsql==""){return $countonly?0:array();}
    
    if ($countonly)
        {return sql_value("SELECT COUNT(*) value FROM (" . $actionsql . ") allactions",0);}
    else
        {$actionsql = "SELECT date, allactions.ref,user.fullname as 
			user,"
			 . ($messages_actions_usergroup?"usergroup.name as 
			usergroup,":"") . 
			" description, 
			type FROM (" . $actionsql . ")  allactions LEFT JOIN user ON 
			allactions.user=user.ref"
			 . ($messages_actions_usergroup?" LEFT JOIN usergroup ON 
			user.usergroup=usergroup.ref":"") .
			" ORDER BY " . $order_by . " " . $sort;}
       
    return sql_query($actionsql);  
    }
    
/**
 * Return an SQL statement to find all editable resources in $actions_notify_states.
 *
 * @return string
 */
function get_editable_resource_sql()
	{
	global $actions_notify_states, $actions_resource_types_hide, $default_display, $list_display_fields, $search_all_workflow_states;
    $default_display	= $list_display_fields;
    $search_all_workflow_states = false;
    $rtypes=get_resource_types();
    $searchable_restypes=implode(",",array_diff(array_column($rtypes,"ref"),explode(",",$actions_resource_types_hide)));
	$editable_resource_sql=do_search("",$searchable_restypes,'resourceid',$actions_notify_states,-1,'desc',false,0,false,false,'',false,false,false,true,true);
    return $editable_resource_sql;
	}
    
