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
    global $default_display, $list_display_fields, $search_all_workflow_states,$actions_approve_hide_groups,$userref,
    $actions_resource_requests,$actions_account_requests, $view_title_field, $actions_on,$messages_actions_usergroup, $actions_notify_states;

    // Make sure all states are excluded if they had the legacy option $actions_resource_review set to false.
    get_config_option($userref,'actions_resource_review', $actions_resource_review, true);
    if($actions_resource_review == false)
        {
        $actions_notify_states = "";
        }
    $actionsql = new PreparedStatementQuery();
    $filtered = $type!="";

    if(!$actions_on){return array();}

    if((!$filtered || 'resourcereview'==$type) && trim($actions_notify_states) != "")
        {
        $search_all_workflow_states = false;
        $default_display	= $list_display_fields;

        if(is_int_loose($view_title_field)) 
            {
            $generated_title_field = "field".$view_title_field;
            }
        else
            {
            $generated_title_field = "''";
            }

        # Function get_editable_resource_sql() now returns a query object
        $editable_resource_query=get_editable_resource_sql($countonly);

        $actionsql->sql .="SELECT creation_date as date,ref, created_by as user, " 
           .$generated_title_field." as description, 'resourcereview' as type FROM (" . $editable_resource_query->sql . ") resources" ;
        $actionsql->parameters=array_merge($actionsql->parameters, $editable_resource_query->parameters);
        }
    if(checkperm("R") && $actions_resource_requests && (!$filtered || 'resourcerequest'==$type))
        {
        # This get_requests call now returns a query object with two properties; sql string and paramaters array
        $request_query = get_requests(true,true,true);      
        $actionsql->sql .= (($actionsql->sql != "")?" UNION ":"") . "SELECT created
        as date,ref, user, substring(comments,21) as description,'resourcerequest' as type FROM (" . $request_query->sql . ") requests";
        $actionsql->parameters=array_merge($actionsql->parameters, $request_query->parameters);
        }
    if(checkperm("u") && $actions_account_requests && (!$filtered || 'userrequest'==$type))
        {
        $availgroups=get_usergroups(true);
        $get_groups=implode(",",array_diff(array_column($availgroups,"ref"),explode(",",$actions_approve_hide_groups)));

        $account_requests_query = get_users($get_groups,"","u.created",true,-1,0,true,"u.ref,u.created,u.fullname,u.email,u.username, u.comments"); 

        $actionsql->sql .= (($actionsql->sql != "")?" UNION ":"") . "SELECT created 
            as date,ref,ref as user,comments as description,'userrequest' as type FROM (" . $account_requests_query->sql . ") users";
        $actionsql->parameters=array_merge($actionsql->parameters, $account_requests_query->parameters);
    }

    # Following hook now returns a query object
    $hookactionsql = hook("addtoactions");

    if($hookactionsql != false)
        {
        if ($actionsql->sql!="") 
            {
            $actionsql->sql.=" UNION ";
            }   
        $actionsql->sql.=$hookactionsql->sql;
        $actionsql->parameters=array_merge($actionsql->parameters, $hookactionsql->parameters);
        }

    if($actionsql->sql == ""){return $countonly?0:array();}

    if ($countonly)
        {return ps_value("SELECT COUNT(*) value FROM (" . $actionsql->sql . ") allactions",$actionsql->parameters,0);}
    else
        {
        $final_action_sql = $actionsql;
        $final_action_sql->sql = "SELECT date, allactions.ref,user.fullname as
        user,"
            . ($messages_actions_usergroup?"usergroup.name as usergroup,":"") .
        " description, 
        type FROM (" . $actionsql->sql . ")  allactions LEFT JOIN user ON 
        allactions.user=user.ref"
            . ($messages_actions_usergroup?" LEFT JOIN usergroup ON
        user.usergroup=usergroup.ref":"") .
        " ORDER BY " . $order_by . " " . $sort;
        }
    return ps_query($final_action_sql->sql, $final_action_sql->parameters);
    }

/**
 * Return an SQL statement to find all editable resources in $actions_notify_states.
 *
 * @return mixed
 */
function get_editable_resource_sql()
    {
    global $actions_notify_states, $actions_resource_types_hide, $default_display, $list_display_fields, $search_all_workflow_states;
    $default_display	= $list_display_fields;
    $search_all_workflow_states = false;
    $rtypes=get_resource_types();
    $searchable_restypes=implode(",",array_diff(array_column($rtypes,"ref"),explode(",",$actions_resource_types_hide)));

    $editable_resource_query= new PreparedStatementQuery();
    $editable_resource_query=do_search("",$searchable_restypes,'resourceid',$actions_notify_states,-1,'desc',false,0,false,false,'',false,false,false,true,true);

    return $editable_resource_query;
    return $editable_resource_query;
    }

