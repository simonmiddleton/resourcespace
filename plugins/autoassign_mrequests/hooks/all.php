<?php
include_once(dirname(__FILE__) . '/../include/autoassign_general.php');

function HookAutoassign_mrequestsAllAutoassign_individual_requests($user_ref, $collection_ref, $message, $manage_collection_request)
    {
    global $manage_request_admin, $assigned_to_user, $notify_manage_request_admin, $request_query;

    // Do not process this any further as this only handles individual resource requests
    if($manage_collection_request)
        {
        return true;
        }

    $resources              = get_collection_resources($collection_ref);
    $resource_data          = get_resource_field_data($resources[0]); // in this case it should only have one resource
    $mapped_fields          = get_mapped_fields();
    $assigned_administrator = 0;
    $resource_nodes         = get_resource_nodes($resources[0]);

    // Process each non node based metadata field value pair for the resource being requested, looking for an assignee
    foreach ($resource_data as $r_data)
        {
        // Is the metadata field the subject of a mapping
        if(in_array($r_data['ref'], $mapped_fields))
            {
            // Return the assignee for the mapping if one exists for the field value pair 
            get_mapped_user_by_field($r_data['ref'], $r_data['value'])!=0?$assigned_administrator=get_mapped_user_by_field($r_data['ref'], $r_data['value']):0;
            // If an assignee was found then terminate loop otherwise move on to next field value pair
            If($assigned_administrator !== 0) 
                {
                break;
                } 
            }
        }

    $node_fields_list = array();
    $current_node = array();

    foreach ($resource_nodes as $node)
        {
        get_node($node, $current_node);
        array_push($node_fields_list, $current_node);
        }

    // Process each node based metadata field value pair for the resource being requested, looking for an assignee
    foreach ($node_fields_list as $r_node)
        {
        // Is the metadata field the subject of a mapping
        if(in_array($r_node['resource_type_field'], $mapped_fields))
            {
            // Return the assignee for the mapping if one exists for the field value pair 
            get_mapped_user_by_field($r_node['resource_type_field'], $r_node['name'])!=0?$assigned_administrator=get_mapped_user_by_field($r_node['resource_type_field'], $r_node['name']):0;
            // If an assignee was found then terminate loop otherwise move on to next field value pair
            If($assigned_administrator !== 0)
                {
                break;
                } 
            }
        }

    if($assigned_administrator === 0)
        {
        return false;
        }

    $request_query = new PreparedStatementQuery();
    $request_query->sql = "INSERT INTO request(user, collection, created, request_mode, `status`, comments, assigned_to)
                                  VALUES (?, ?, NOW(), 1, 0, ?, ?)";
    $request_query->parameters = array("i",$user_ref, "i",$collection_ref, "s",$message, "i",$assigned_administrator);

    $assigned_to_user = get_user($assigned_administrator);
    $notify_manage_request_admin = true;

    // If we've got this far, make sure auto assigning managed requests based on resource types won't overwrite this
    $manage_request_admin=array();  // Initialise the global array instead of attempting to unset it which does not work

    return true;
    }

function HookAutoassign_mrequestsAllAutoassign_collection_requests($user_ref, $collection_data, $message, $manage_collection_request)
    {
    global $manage_request_admin, $assigned_to_user, $admin_mail_template, $lang, $baseurl, $applicationname, 
           $request_query, $notify_manage_request_admin;

    // Do not process this any further as this should only handle collection requests
    if(!$manage_collection_request)
        {
        return false;
        }

    $resources                             = get_collection_resources($collection_data['ref']);
    $mapped_fields                         = get_mapped_fields();
    $collection_resources_by_assigned_user = array();
    $collections                           = array();

    // Build the collections map between assigned user and resources the collection should contain
    foreach ($resources as $resource)
        {
        $resource_data          = get_resource_field_data($resource);
        $assigned_administrator = 0;
        $resource_not_assigned  = true;

        foreach ($resource_data as $r_data)
            {
            if(in_array($r_data['ref'], $mapped_fields))
                {
                $assigned_administrator = get_mapped_user_by_field($r_data['ref'], $r_data['value']);

                if($assigned_administrator === 0)
                    {
                    $collection_resources_by_assigned_user['not_managed'][] = $resource;
                    }
                else
                    {
                    $collection_resources_by_assigned_user[$assigned_administrator][] = $resource;
                    }

                $resource_not_assigned = false;
                break;
                }
            }

        if($resource_not_assigned && !isset($manage_request_admin))
            {
            $collection_resources_by_assigned_user['not_managed'][] = $resource;
            }
        }

    // Create collections based on who is supposed to handle the request
    foreach ($collection_resources_by_assigned_user as $assigned_user_id => $collection_resources)
        {
        if($assigned_user_id === 'not_managed')
            {
            $collections['not_managed'] = create_collection($user_ref, $collection_data['name'] . ' request for unmanaged resources');
            foreach ($collection_resources as $collection_resource_id)
                {
                add_resource_to_collection($collection_resource_id, $collections['not_managed']);
                }
            continue;
            }
        
        $user = get_user($assigned_user_id);
        $collections[$assigned_user_id] = create_collection($user_ref, $collection_data['name'] . ' request - managed by ' . $user['email']);
        foreach ($collection_resources as $collection_resource_id)
            {
            add_resource_to_collection($collection_resource_id, $collections[$assigned_user_id]);
            }

        // Attach assigned admin to this collection
        add_collection($user['ref'], $collections[$assigned_user_id]);
        }

    if(!empty($collections))
        {
        foreach ($collections as $assigned_to => $collection_id)
            {
            $request_query = new PreparedStatementQuery();
            $request_query->sql = "INSERT INTO request(user, collection, created, request_mode, `status`, comments, assigned_to)
                                    VALUES (?, ?, NOW(), 1, 0, ?, ?)";
            $request_query->parameters = array("i",$user_ref, "i",$collection_id, "s",$message, "i",$assigned_to);

            $subject = $applicationname . ': ' . $lang['requestassignedtoyou'];
            $message = $lang['requestassignedtoyoumail'];
            if($assigned_to === 'not_managed' || !$assigned_to_user)
                {
                $assigned_to = get_notification_users("RESOURCE_ACCESS");

                # Note: The refactored sprintf code had an $assigned_to parameter, but no corresponding '%s' placeholder
                #       This has been dropped from refactored code

                $request_query->sql = "INSERT INTO request(user, collection, created, request_mode, `status`, comments)
                VALUES (?, ?, NOW(), 1, 0, ?)";
                $request_query->parameters = array("i",$user_ref, "i",$collection_id, "s",$message);
                $subject = $applicationname . ": " . $lang["user_made_request"];
                $message = $lang['user_made_request'];
                }

            ps_query($request_query->sql, $request_query->parameters);
            $request = sql_insert_id();

            // Send message
            $request_url = $baseurl . "/?q=" . $request;
            $templatevars['request_id']    = $request;
            $templatevars['requesturl']    = $request_url;
            $templatevars['requestreason'] = $message;
            $eventdata = [
                "type"  => MANAGED_REQUEST,
                "ref"   => $request,
                ];
            send_user_notification($assigned_to,"resource_request",$eventdata,$subject,$message,$request_url,$admin_mail_template,$templatevars);
            }
        $notify_manage_request_admin = false;
        }

    // If we've got this far, make sure auto assigning managed requests based on resource types won't overwrite this
    $manage_request_admin=array();  // Initialise the global array instead of attempting to unset it which does not work

    return true;
    }

function HookAutoassign_mrequestsAllBypass_end_managed_collection_request($manage_individual_requests, $collection_id, $request_query, $message, $templatevars, $assigned_to_user, $admin_mail_template, $user_mail_template)
    {
    global $applicationname, $baseurl, $email_from, $resource_type_request_emails_and_email_notify, $lang, $username, $resource_type_request_users, $userref,$manage_request_admin, $notify_manage_request_admin, $resource_type_request_emails, $request_senduserupdates;

    // Collection level requests have already been created and e-mails sent so skip this step
    if(!$manage_individual_requests)
        {
        // Because we are bypassing the end of managed_collection_request function we need to return true
        return true;
        }

    // If we don't have an assigned user, it probably means system is misconfigured so go ahead and run this normally via
    // RS own logic for dealing with requests.
    if(is_null($assigned_to_user))
        {
        return false;
        }

    // Create resource level request using SQL which was setup earlier in resource level hook or regular processing
    ps_query($request_query->sql, $request_query->parameters);
    $request = sql_insert_id();
    $eventdata = [
        "type"  => MANAGED_REQUEST,
        "ref"   => $request,
        ];
    $request_url = $baseurl . "/?q=" . $request;

    $templatevars['request_id']    = $request;
    $templatevars['requesturl']    = $request_url;
    $templatevars['requestreason'] = $message;

    // Attach assigned admin to this collection
    add_collection($assigned_to_user['ref'], $collection_id);

    if($notify_manage_request_admin)
        {
        send_user_notification([$assigned_to_user['ref']],"resource_request",$eventdata,$applicationname . ': ' . $lang['requestassignedtoyou'],$lang['requestassignedtoyoumail'] . ": " . $message,$request_url,$admin_mail_template,$templatevars);
        $notification_sent = true;
        }

    $notify_users = [];
    $notify_emails = [];
    # Check if alternative request email notification address is set, only valid if collection contains resources of the same type
    if(isset($resource_type_request_emails))
        {
        $parameters=array("i",$collection_id);    
        $requestrestypes=ps_array("select r.resource_type as value from collection_resource cr left join resource r on cr.resource=r.ref where cr.collection=?", $parameters);
        $requestrestypes=array_unique($requestrestypes);
        if(count($requestrestypes)==1 && isset($resource_type_request_emails[$requestrestypes[0]]))
            {
            // Is this a system user? If so we can send a notification instead of an email
            $emailusers = get_user_by_email($resource_type_request_emails[$requestrestypes[0]]);

            if(is_array($emailusers) && count($emailusers) > 0)
                {
                $notify_users = array_merge($notify_users,$emailusers);
                }
            else
                {
                $notify_emails[]=$resource_type_request_emails[$requestrestypes[0]];
                }
            }
        }

    if(!$notification_sent && (!isset($resource_type_request_emails) || $resource_type_request_emails_and_email_notify))
        {
        $admin_notify_users=get_notification_users("RESOURCE_ACCESS");
        $notify_users = array_merge($resource_type_request_users,$admin_notify_users);

        # Send the e-mails and/or notification messages   
        $admin_notify_message=$lang["user_made_request"] . "<br /><br />" . $lang["username"] . ": " . $username . "<br />$message<br /><br />";
        $admin_notify_message.=$lang["clicktoviewresource"] . "<br />" . $templatevars["requesturl"];
        foreach($notify_emails as $notify_email)
            {
            // These are not system users so emails must be sent
            send_mail($notify_email,$applicationname . ": " . $lang["requestcollection"] . " - $collection_id",$admin_notify_message,$email_from,$email_from,$admin_mail_template,$templatevars);
            }
        send_user_notification($notify_users,"resource_request",$eventdata,$applicationname . ': ' . $lang['user_made_request'],$admin_notify_message,$request_url,$admin_mail_template,$templatevars);
        }
   
    if ($request_senduserupdates)
        {
        $userconfirm_notification = $lang["requestsenttext"] . "<br /><br />" . $message;

        $templatevars['url']    = $baseurl . "/?c=" . $collection_id;
        $userconfirmmessage = $userconfirm_notification . "<br /><br />" . $lang["clicktoviewresource"] . "<br />$baseurl/?c=$collection_id";
        send_user_notification([$userref],"",[],$applicationname . ": " . $lang["requestsent"] . " - " . $collection_id,$userconfirmmessage,$templatevars['url'],$user_mail_template,$templatevars);
        }
    return true;
    }

function HookAutoassign_mrequestsAllExport_add_tables()
    {
    return array("assign_request_map"=>array());
    }