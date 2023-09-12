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
    $actions_resource_requests,$actions_account_requests, $view_title_field, $actions_on, $messages_actions_usergroup, $actions_notify_states;

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
        $editable_resource_query=get_editable_resource_sql();

        $actionsql->sql .="SELECT creation_date as date,ref, created_by as user, " 
           .$generated_title_field." as description, 'resourcereview' as type FROM (" . $editable_resource_query->sql . ") resources" ;
        $actionsql->parameters=array_merge($actionsql->parameters, $editable_resource_query->parameters);
        }
    if(checkperm("R") && $actions_resource_requests && (!$filtered || 'resourcerequest'==$type))
        {
        # This get_requests call now returns a query object with two properties; sql string and parameters array
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
    }


/**
 * Get recent user actions, optionally for all users
 *
 * @param  int  $minutes     Return actions that were created in the last minutes
 * @param  bool $allusers    Return actions for all users?  If false, or if the current user does not have
 *                           the 'a' permission and the current script is not running from CLI then only the currently logged on
 *                           user's actions will be returned
 * 
 * 
 * @return array            An array with the user id as the index and the following arrays of sub elements.
 *                          Included columns are as per get_user_actions()
 *                          - resourcerequest  -  array of resource requests 
 *                          - userrequest - array of user requests
 *                          - resourcereview - array of resources to reviewdescription)
 */
function get_user_actions_recent(int $minutes, bool $allusers) : array
    {
    debug_function_call(__FUNCTION__,func_get_args());
    global $view_title_field, $userref;

    $newactions = [];
    
    // Find all resources that have changed archive state in the given number of minutes
    if(is_int_loose($view_title_field)) 
        {
        $generated_title_field = "field".$view_title_field;
        }
    else
        {
        $generated_title_field = "r.ref";
        }
    $sql = "SELECT r.ref, r.archive, rl.user, MAX(rl.date) AS date, $generated_title_field AS description, 'resourcereview' AS type 
              FROM resource_log rl 
         LEFT JOIN resource r ON rl.resource=r.ref
             WHERE rl.type = 's' 
               AND TIMESTAMPDIFF(MINUTE,date,NOW())<?
          GROUP BY r.ref,rl.ref
          ORDER BY rl.ref DESC";

    $params = ["i",$minutes];
    $newactions["resourcereview"] = ps_query($sql,$params);

    // Find all resource requests created in the given number of minutes
    $sql = "SELECT r.ref, r.user, r.created AS date, r.expires, r.comments AS description, r.assigned_to, 'resourcerequest' as type
              FROM request r
             WHERE status = 0
               AND TIMESTAMPDIFF(MINUTE,created,NOW())<?
          ORDER BY r.ref ASC";
    $params = ["i",$minutes];
    $newactions["resourcerequest"] = ps_query($sql,$params);

    // Find all account requests created in the last XX minutes
    $sql = "SELECT ref, ref as user, created AS date, comments AS description, usergroup, 'userrequest' as type
              FROM user
             WHERE approved =  0
               AND TIMESTAMPDIFF(MINUTE,created,NOW())<?
          ORDER BY ref ASC";
    $params = ["i",$minutes];
    $newactions["userrequest"] = ps_query($sql,$params);

    // TODO Add hook and update plugin to find all proposed changes submitted in the last XX minutes
    $userrecent = [];
    if($allusers)
        {
        $action_notify_users = get_users_by_preference("user_pref_new_action_emails","1");
        foreach($action_notify_users as $action_notify_user)
            {
            $userrecent[$action_notify_user] = actions_filter_by_user($action_notify_user,$newactions);
            }
        }
    else
        {
        $userrecent[$userref] = actions_filter_by_user($userref,$newactions);
        }

    return $userrecent;
    }

/**
 * Filter actions in the provided array to return only those applicable to the given user
 *
 * @param int       $actionuser User ref to get actions for
 * @param array     $actions    Array of actions as returned by get_user_actions_recent()
 * 
 * @return array    Subset of actions for the given user as would be provided by get_user_actions()
 * 
 */
function actions_filter_by_user(int $actionuser,array $actions) : array
    {
    debug_function_call(__FUNCTION__,func_get_args());
    global $userref, $actions_resource_requests, $actions_account_requests, $actions_approve_hide_groups; 

    $return = [];
    if(!isset($userref) || $actionuser != $userref)
        {
        $actionuserdata = get_user($actionuser);
        setup_user($actionuserdata);
        }

    foreach($actions as $actiontype=>$typeactions)
        {
        switch($actiontype)
            {
            case "resourcereview":
                get_config_option($userref,'actions_resource_review', $actions_resource_review, true);
                if($actions_resource_review == false)
                    {
                    $arrnotifystates = [];
                    }
                else
                    {
                    get_config_option($userref,"actions_notify_states", $notifystates,"");
                    $arrnotifystates = explode(",",$notifystates);
                    }
                foreach($typeactions as $typeaction)
                    {
                    if(in_array($typeaction["archive"],$arrnotifystates) && get_edit_access($typeaction["ref"]))
                        {
                        $return["resourcereview"][] = $typeaction;
                        }
                    }
                break;
            case "resourcerequest":
                if($actions_resource_requests)
                    {                   
                    foreach($typeactions as $typeaction)
                        {                        
                        if(resource_request_visible($typeaction))
                            {
                            $return["resourcerequest"][] = $typeaction;
                            }
                        }
                    }
                break;
            case "userrequest":                
                if(checkperm("u") && $actions_account_requests)
                    {
                    foreach($typeactions as $typeaction)
                        {
                        if(checkperm_user_edit($typeaction["ref"]))
                            {
                            $return["userrequest"][] = $typeaction;
                            }
                        }
                    }
                break;
            default;
                break;
            }
        }
    return $return;
    }
