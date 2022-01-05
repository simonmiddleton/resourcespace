<?php
# Request functions
# Functions to accomodate resource requests and orders (requests with payment)

/**
 * Retrieve a resource request record
 *
 * @param  integer $request The request record ID
 * @return mixed False if not found, the resource record (associative array) if found
 */
function get_request($request)
    {
    $parameters=array("i",$request);
    $result=ps_query("select u.username,u.fullname,u.email,r.user,r.collection,r.created,r.request_mode,r.status,r.comments,r.expires,r.assigned_to,
                        r.reason,r.reasonapproved,u2.username assigned_to_username 
                from request r 
                left outer join user u on r.user=u.ref 
                left outer join user u2 on r.assigned_to=u2.ref where r.ref=?", $parameters);
    if (count($result)==0)
        {
        return false;
        }
    else
        {
        return $result[0];
        }
    }

/**
 * Fetch a list of all requests for a user
 *
 * @param  boolean $excludecompleted  Exclude requests that have already been completed
 * @param  mixed $returnsql Return the SQL for the execution rather than the results
 * @return void
 */
function get_user_requests($excludecompleted=false,$returnsql=false)
    {
    global $userref;
    if (!is_numeric($userref)){ return false; }

    $query_spec = new PreparedStatementQuery();
    $query_spec->parameters=array("i",$userref);
    $query_spec->sql="select u.username,u.fullname,r.*,if(collection.ref is null,'0',collection.ref) collection_id, 
            (select count(*) from collection_resource cr where cr.collection=r.collection) c 
        from request r 
        left outer join user u on r.user=u.ref 
        left join collection on r.collection = collection.ref 
        where r.user = ?" . ($excludecompleted?" AND status<>2":"") . " order by ref desc";
    return $returnsql ? $query_spec : ps_query($query_spec->sql, $query_spec->parameters);
    }
    
/**
 * Handle the posted request form, when saving a request in the admin area.
 *
 * @param  integer $request The request record ID
 * @return boolean  Was this successful?
 */
function save_request($request)
    {
    # Use the posted form to update the request
    global $applicationname,$baseurl,$lang,$request_senduserupdates,$admin_resource_access_notifications,$userref;
        
    $status=getvalescaped("status","",true);
    $expires=getvalescaped("expires","");
    $currentrequest=get_request($request);
    $oldstatus=$currentrequest["status"];
    $assigned_to=getvalescaped("assigned_to","");
    $reason=getvalescaped("reason","");
    $reasonapproved=getvalescaped("reasonapproved","");

    $approved_declined=false;
    
    # --------------------- User Assignment ------------------------
    # Process an assignment change if this user can assign requests to other users
    if ($currentrequest["assigned_to"]!=$assigned_to && checkperm("Ra"))
        {
        if ($assigned_to==0)
            {
            # Cancel assignment
            ps_query("update request set assigned_to=null where ref=?", array("i",$request));
            }
        else
            {
            # Update and notify user
            ps_query("update request set assigned_to=? where ref=?",array("i",$assigned_to, "i",$request));
            $message=$lang["requestassignedtoyoumail"] . "\n\n$baseurl/?q=" . $request . "\n";
            
            get_config_option($assigned_to,'user_pref_resource_access_notifications', $send_message, true);       
            if($send_message)
                {
                get_config_option($assigned_to,'email_user_notifications', $send_email);
                $assigned_to_user=get_user($assigned_to);
                if($send_email && filter_var($assigned_to_user["email"], FILTER_VALIDATE_EMAIL))
                    {               
                    send_mail($assigned_to_user["email"],$applicationname . ": " . $lang["requestassignedtoyou"],$message);
                    }
                else
                    {
                    message_add($assigned_to,$message,$baseurl . "/?q=" . $request);
                    }
                }
            
            get_config_option($currentrequest["user"],'user_pref_resource_access_notifications', $send_message, $admin_resource_access_notifications);        
            if($send_message)
                {
                $userconfirmmessage=str_replace("%",$assigned_to_user["fullname"] . " (" . $assigned_to_user["email"] . ")" ,$lang["requestassignedtouser"]);
                if ($request_senduserupdates)
                    {
                    get_config_option($currentrequest["user"],'email_user_notifications', $send_email);
                    if($send_email && filter_var($currentrequest["email"], FILTER_VALIDATE_EMAIL))
                        {    
                        send_mail($currentrequest["email"],$applicationname . ": " . $lang["requestupdated"] . " - $request",$userconfirmmessage);
                        }
                    else
                        {
                        message_add($currentrequest["user"],$lang["requestupdated"] . " - " . $request . "<br />" . $userconfirmmessage,$baseurl . "/?c=" . $currentrequest["collection"]);
                        }
                        
                    }
                }
            }
        }
    
    
    # Has either the status or the expiry date changed?
    if (($oldstatus!=$status || $expires!=$currentrequest["expires"]) && $status==1)
        {
        # --------------- APPROVED -------------
        # Send approval e-mail
        // $reasonapproved=str_replace(array("\\r","\\n"),"\n",$reasonapproved);$reasonapproved=str_replace("\n\n","\n",$reasonapproved); # Fix line breaks.
        $approved_declined = true;
        $reasonapproved = unescape($reasonapproved);
        $message=$lang["requestapprovedmail"] . "\n\n" . $lang["approvalreason"]. ": " . $reasonapproved . "\n\n" ;
        $message.="$baseurl/?c=" . $currentrequest["collection"] . "\n";
        if ($expires!="")
            {
            # Add expiry time to message.
            $message.=$lang["requestapprovedexpires"] . " " . nicedate($expires) . "\n\n";
            }
                   
        get_config_option($currentrequest["user"],'email_user_notifications', $send_email);
        if($send_email && filter_var($currentrequest["email"], FILTER_VALIDATE_EMAIL))
            {
            $templatevars['url'] = $baseurl."/?c=" . $currentrequest["collection"]; 
            send_mail($currentrequest["email"],$applicationname . ": " . $lang["requestcollection"] . " - " . $lang["resourcerequeststatus1"],$message);
            }
        else
            {
            message_add($currentrequest["user"],$message,$baseurl . "/?c=" . $currentrequest["collection"]);
            }
               
        
        # Mark resources as full access for this user
        foreach (get_collection_resources($currentrequest["collection"]) as $resource)
            {
            open_access_to_user($currentrequest["user"],$resource,$expires);
            }
            
        # Clear any outstanding notifications about this request that may have been sent to other admins
        message_remove_related(MANAGED_REQUEST,$request);
        }

    if ($oldstatus!=$status && $status==2)  
        {
        # --------------- DECLINED -------------
        # Send declined e-mail
        $approved_declined = true;
        $reason = unescape($reason);
        $message=$lang["requestdeclinedmail"] . "\n\n" . $lang["declinereason"] . ": ". $reason . "\n\n$baseurl/?c=" . $currentrequest["collection"] . "\n";
               
        get_config_option($currentrequest["user"],'email_user_notifications', $send_email);
        if($send_email && filter_var($currentrequest["email"], FILTER_VALIDATE_EMAIL))
            {
            send_mail($currentrequest["email"],$applicationname . ": " . $lang["requestcollection"] . " - " . $lang["resourcerequeststatus2"],$message);
            }
        else
            {
            message_add($currentrequest["user"],$message,$baseurl . "/?c=" . $currentrequest["collection"]);
            }

        # Remove access that my have been granted by an inadvertant 'approved' command.
        foreach (get_collection_resources($currentrequest["collection"]) as $resource)
            {
            remove_access_to_user($currentrequest["user"],$resource);
            }
            
        # Clear any outstanding notifications about this request that may have been sent to other admins
        message_remove_related(MANAGED_REQUEST,$request);
        }

    if ($oldstatus!=$status && $status==0)
        {
        # --------------- PENDING -------------
        # Moved back to pending. Delete any permissions set by a previous 'approve'.
        foreach (get_collection_resources($currentrequest["collection"]) as $resource)
            {
            remove_access_to_user($currentrequest["user"],$resource);
            }
        }

    # Save status
    $expires_parm=($expires=="" ? null : $expires);
    $parameters=array("i",$status, "s",$expires_parm, "s",$reason, "s",$reasonapproved, "i",$request);
    ps_query("update request set status=?, expires=?, reason=?, reasonapproved=? where ref=?", $parameters);

    # Set user that approved or declined the request
    if ($approved_declined)
        {
        $parameters=array("i",$userref, "i",$request);
        ps_query("update request set approved_declined_by=? where ref=?", $parameters);
        }

    if (getval("delete","")!="")
        {
        # Delete the request - this is done AFTER any e-mails have been sent out so this can be used on approval.
        ps_query("delete from request where ref=?",array("i",$request));
        
        # Clear any outstanding notifications about this request that may have been sent to other admins
        message_remove_related(MANAGED_REQUEST,$request);
        
        return true;        
        }

    }
    
    
/**
 * Fetch a list of requests assigned to the logged in user
 *
 * @param  boolean $excludecompleted    Exclude completed requests?
 * @param  boolean $excludeassigned     Exclude assigned requests? (e.g. if the user is able to assign unassigned requests)
 * @param  boolean $returnsql           Return SQL query object instead of the results?
 * @return mixed                        Resulting array of requests or an SQL query object
 */
function get_requests($excludecompleted=false,$excludeassigned=false,$returnsql=false)
    {
    global $userref;
    $condition="";
    
    $parameters=array();

    # Include requests assigned to the user if the user can accept requests (permission "Rb")
    if (checkperm("Rb")) 
        {
        $condition="WHERE r.assigned_to=?";
        $parameters=array("i",$userref);
        }
    # Include all requests if the user can assign requests (permission "Ra")
    if (checkperm("Ra")) 
        {
        $condition="";
        $parameters=array();
        # Excluding assigned requests only makes sense if user is able to assign requests 
        if ($excludeassigned) 
            {
            $condition = "WHERE r.assigned_to IS null"; 
            }
        }
    # Exclude completed requests if necessary
    if ($excludecompleted) 
        {
        $condition .= (($condition!="") ? " AND" : "WHERE") . " r.status=0";
        }
        
    $sql="SELECT u.username,u.fullname,r.*,
          (SELECT count(*) FROM collection_resource cr WHERE cr.collection=r.collection) c,
          u2.username assigned_to_username 
          FROM request r 
          LEFT OUTER JOIN user u ON r.user=u.ref LEFT OUTER JOIN user u2 ON r.assigned_to=u2.ref $condition  ORDER BY status,ref desc";
    
    $request_query = new PreparedStatementQuery($sql, $parameters);

    if ($returnsql) 
        {
        return $request_query;
        }
    else 
        {
        return ps_query($request_query->sql,$request_query->parameters);
        }
    }
  
/**
 * Email a collection request to the team responsible for dealing with requests. Request mode 0 only (non managed).
 *
 * @param  integer $ref 
 * @param  mixed $details
 * @param  mixed $external_email
 * @return void
 */
function email_collection_request($ref,$details,$external_email)
    {
    global $applicationname,$email_from,$baseurl,$email_notify,$username,$useremail,$lang,$request_senduserupdates,$userref,$resource_type_request_emails,$resource_request_reason_required,$admin_resource_access_notifications, $always_email_from_user,$collection_empty_on_submit;
    
    if (trim($details)=="" && $resource_request_reason_required) {return false;}
    
    $message="";
    #if (isset($username) && trim($username)!="") {$message.=$lang["username"] . ": " . $username . "\n";}
    
    $templatevars['url']=$baseurl."/?c=".$ref;
    $collectiondata=get_collection($ref);
    if (isset($collectiondata["name"])){
    $templatevars["title"]=$collectiondata["name"];}
    
    # Create a copy of the collection which is the one sent to the team. This is so that the admin
    # user can e-mail back an external URL to the collection if necessary, to 'unlock' full (open) access.
    # The user cannot then gain access to further resources by adding them to their original collection as the
    # shared collection is a copy.
    # A complicated scenario that is best avoided using 'managed requests'.
    $newcopy=create_collection(-1,$lang["requestcollection"]);
    copy_collection($ref,$newcopy);
    
    // Make sure a collection does not include resources that may have been hidden from the user due
    // to archive state, resource type or access changes and that they are not aware they are requesting.
    // Without this a full copy can confuse the request administrator
    $col_visible = do_search("!collection" . $ref,'','','',-1,'desc',false,0,false,false,'',false,false,true);
    $colresources = get_collection_resources($ref);    
    foreach($colresources as $colresource)
        {
        if(!in_array($colresource,array_column($col_visible,"ref")))
            {
            remove_resource_from_collection($colresource,$newcopy,false);
            }
        }
    
    if($collection_empty_on_submit)
        {
        remove_all_resources_from_collection($ref);    
        }
        
    $ref=$newcopy;
    
    $templatevars["requesturl"]=$baseurl."/?c=".$ref;
    
    $templatevars['username']=$username . " (" . $useremail . ")";
    $userdata=get_user($userref);
    if($userdata===false){return false;} # Unable to get user credentials
    $templatevars["fullname"]=$userdata["fullname"];
    
    reset ($_POST);
    foreach ($_POST as $key=>$value)
        {
        if (strpos($key,"_label")!==false)
            {
            # Add custom field
            $setting=trim($_POST[str_replace("_label","",$key)]);
            if ($setting!="")
                {
                $message.=$value . ": " . $_POST[str_replace("_label","",$key)] . "\n\n";
                }
            }
        }
    if (trim($details)!="") {$message.=$lang["requestreason"] . ": " . newlines($details) . "\n\n";}
    
    # Add custom fields
    $c="";
    global $custom_request_fields,$custom_request_required;
    if (isset($custom_request_fields))
        {
        $custom=explode(",",$custom_request_fields);
    
        # Required fields?
        if (isset($custom_request_required)) {$required=explode(",",$custom_request_required);}
    
        for ($n=0;$n<count($custom);$n++)
            {
            if (isset($required) && in_array($custom[$n],$required) && getval("custom" . $n,"")=="")
                {
                return false; # Required field was not set.
                }
            
            $message.=i18n_get_translated($custom[$n]) . ": " . getval("custom" . $n,"") . "\n\n";
            }
        }
        
    $amendedmessage=hook('amend_request_message','', array($userref, $ref, isset($collectiondata) ? $collectiondata : array(), $message, isset($collectiondata)));
    if($amendedmessage)
        {
        $message=$amendedmessage;
        }
    
    $templatevars["requestreason"]=$message;
    
    $userconfirmmessage = $lang["requestsenttext"] . "\n\n$message";
    $message=$lang["user_made_request"] . "\n\n" . $lang["username"] . ": " . $username . "\n$message";
    $notification_message=$message;
    $message.=$lang["viewcollection"] . ":\n" .  $templatevars['url'];
    
    $admin_notify_emails=array();    
    $admin_notify_users=array();
    # Check if alternative request email notification address is set, only valid if collection contains resources of the same type 
    if(isset($resource_type_request_emails))
        {
        $requestrestypes=ps_array("SELECT r.resource_type as value from collection_resource cr 
                                    left join resource r on cr.resource=r.ref where cr.collection=?",array("i",$ref));
        $requestrestypes=array_unique($requestrestypes);
        if(count($requestrestypes)==1 && isset($resource_type_request_emails[$requestrestypes[0]]))
            {
            $admin_notify_emails[]=$resource_type_request_emails[$requestrestypes[0]];
            }
        }
    else
        {
        $notify_users=get_notification_users("RESOURCE_ACCESS");
        foreach($notify_users as $notify_user)
            {
            get_config_option($notify_user['ref'],'user_pref_resource_access_notifications', $send_message, $admin_resource_access_notifications);        
            if($send_message==false){continue;}     
            
            get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
            if($send_email && $notify_user["email"]!="")
                {
                $admin_notify_emails[] = $notify_user['email'];             
                }        
            else
                {
                $admin_notify_users[]=$notify_user["ref"];
                }
            }
        }
    foreach($admin_notify_emails as $admin_notify_email)
        {
        send_mail($admin_notify_email,$applicationname . ": " . $lang["requestcollection"] . " - $ref",$message,($always_email_from_user)?$useremail:$email_from,($always_email_from_user)?$useremail:$email_from,"emailcollectionrequest",$templatevars);
        }
    
    if (count($admin_notify_users)>0)
        {
        message_add($admin_notify_users,$notification_message,$templatevars["requesturl"],$userref, MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,MESSAGE_DEFAULT_TTL_SECONDS,COLLECTION_REQUEST, $ref);
        }
    
    # $userref and $useremail will be that of the internal requestor    
    # - We need to send the $userconfirmmessage to the internal requestor saying that their request has been submitted    
    if (isset($userref) && $request_senduserupdates)
        {
        get_config_option($userref,'email_user_notifications', $send_email);    
        if($send_email && filter_var($useremail, FILTER_VALIDATE_EMAIL))
            {
            send_mail($useremail,$applicationname . ": " . $lang["requestsent"] . " - $ref",$userconfirmmessage,$email_from,$email_notify,"emailusercollectionrequest",$templatevars);
            }        
        else
            {
            message_add($userref,$userconfirmmessage, $templatevars['url']);
            }
        }

    # $userref and $useremail will be null for external requestor
    # - We can only send an email to the email address provided on the external request 
    if (!isset($userref) && filter_var($external_email, FILTER_VALIDATE_EMAIL))
        {
        send_mail($external_email,$applicationname . ": " . $lang["requestsent"] . " - $ref",$userconfirmmessage,$email_from,NULL,"emailusercollectionrequest",$templatevars);
        }    
    
    # Increment the request counter for each resource in the requested collection
    ps_query("UPDATE resource set request_count=request_count+1
               where ref in(select cr.resource from collection_resource cr where cr.collection=? and cr.resource = ref)",array("i",$ref));
    
    return true;
    }

/**
 * Request mode 1 - quests are managed via the administrative interface. Sends an e-mail but also logs the request in the request table.
 *
 * @param  mixed $ref   
 * @param  mixed $details
 * @param  mixed $ref_is_resource
 * 
 * @return boolean
 */
function managed_collection_request($ref,$details,$ref_is_resource=false)
    {   
    global $applicationname,$email_from,$baseurl,$email_notify,$username,$useremail,$userref,$lang,$request_senduserupdates,$watermark,$filename_field,
        $view_title_field,$access,$resource_type_request_emails, $resource_type_request_emails_and_email_notify, $manage_request_admin, 
        $resource_request_reason_required, $admin_resource_access_notifications, $always_email_from_user, $collection_empty_on_submit;

    if (trim($details)=="" && $resource_request_reason_required) {return false;}

    # Has a resource reference (instead of a collection reference) been passed?
    # Manage requests only work with collections. Create a collection containing only this resource.
    if ($ref_is_resource)
        {
        $admin_mail_template="emailresourcerequest";
        $user_mail_template="emailuserresourcerequest";
        
        $resourcedata=get_resource_data($ref);
        $templatevars['thumbnail']=get_resource_path($ref,true,"thm",false,"jpg",$scramble=-1,$page=1,($watermark)?(($access==1)?true:false):false);

        # Allow alternative configuration settings for this resource type
        resource_type_config_override($resourcedata['resource_type']);
        
        if (!file_exists($templatevars['thumbnail'])){
        $templatevars['thumbnail']="../gfx/".get_nopreview_icon($resourcedata["resource_type"],$resourcedata["file_extension"],false);
        }
        $templatevars['url']=$baseurl."/?r=".$ref;
        if (isset($filename_field)){
        $templatevars["filename"]=$lang["fieldtitle-original_filename"] . ": " . get_data_by_field($ref,$filename_field);}
        if (isset($resourcedata["field" . $view_title_field])){
        $templatevars["title"]=$resourcedata["field" . $view_title_field];}
        
        $c=create_collection($userref,$lang["request"] . " " . date("ymdHis"));
        add_resource_to_collection($ref,$c,true);
        $ref=$c; # Proceed as normal
        }
    else {
        
        # Create a copy of the collection to attach to the request so that subsequent collection changes do not affect the request
        $c=create_collection($userref,$lang["request"] . " " . date("ymdHis"));
        copy_collection($ref,$c);
        
        // Make sure a collection does not include resources that may have been hidden from the user due
        // to archive state, resource type or access changes and that they are not aware they are requesting.
        // Without this a full copy can confuse the request administrator
        $col_visible = do_search("!collection" . $ref,'','','',-1,'desc',false,0,false,false,'',false,false,true);
        $colresources = get_collection_resources($ref);
        
        foreach($colresources as $colresource)
            {
            if(!in_array($colresource,array_column($col_visible,"ref")))
                {
                remove_resource_from_collection($colresource,$c,false);
                }
            }
        
        if($collection_empty_on_submit)
            {
            remove_all_resources_from_collection($ref);    
            }
        
        $ref=$c; # Proceed as normal        
        
        $admin_mail_template="emailcollectionrequest";
        $user_mail_template="emailusercollectionrequest";
    
        $collectiondata=get_collection($ref);
        $templatevars['url']=$baseurl."/?c=".$ref;
        if (isset($collectiondata["name"])){
        $templatevars["title"]=$collectiondata["name"];}
        }

    # Formulate e-mail text
    $templatevars['username']=$username;
    $templatevars["useremail"]=$useremail;
    $userdata=get_user($userref);
    $templatevars["fullname"]=$userdata["fullname"];
    
    $message="";
    reset ($_POST);
    foreach ($_POST as $key=>$value)
        {
        if (strpos($key,"_label")!==false)
            {
            # Add custom field
            $setting=trim($_POST[str_replace("_label","",$key)]);
            if ($setting!="")
                {
                $message.=$value . ": " . $setting . "\n\n";
                }
            }
        }
    if (trim($details)!="") {$message.=$lang["requestreason"] . ": " . newlines($details);}
    
    # Add custom fields
    $c="";
    global $custom_request_fields,$custom_request_required;
    if (isset($custom_request_fields))
        {
        $custom=explode(",",$custom_request_fields);
    
        # Required fields?
        if (isset($custom_request_required)) {$required=explode(",",$custom_request_required);}
    
        for ($n=0;$n<count($custom);$n++)
            {
            if (isset($required) && in_array($custom[$n],$required) && getval("custom" . $n,"")=="")
                {
                return false; # Required field was not set.
                }
            
            $message.="\n\n" . i18n_get_translated($custom[$n]) . ": " . getval("custom" . $n,"") . "\n\n";
            }
        }
    
    $amendedmessage=hook('amend_request_message','', array($userref, $ref, isset($collectiondata) ? $collectiondata : array(), $message, isset($collectiondata)));
    if($amendedmessage)
        {
        $message=$amendedmessage;
        }
        
    # Setup the principal create request SQL
    $request_query = new PreparedStatementQuery();

    $request_query->sql = "INSERT INTO request(user, collection, created, request_mode, status, comments) 
                            VALUES (?, ?, NOW(), 1, 0, ?)";
    $request_query->parameters = array("i",$userref, "i",$ref, "s",$message);

    // Set flag to send default notifications unless we override e.g. by $manage_request_admin 
    $send_default_notifications = true;
            
    global $notify_manage_request_admin, $assigned_to_user, $admin_resource_access_notifications;
    
    $notify_manage_request_admin = false;
    $notification_sent = false;

    // The following hook determines the assigned administrator
    // If there isn't one, then the principal request_query setup earlier remains as-is without an assigned_to
    // If there is one then the hook replaces the request_query with one which has an assigned_to 
    hook('autoassign_individual_requests', '', array($userref, $ref, $message, isset($collectiondata)));

    // Regular Processing: autoassign using the resource type - one resource was requested and no plugin is preventing this from running
    if($ref_is_resource && !is_null($manage_request_admin) && is_array($manage_request_admin) && !empty($manage_request_admin))
        {
        $admin_notify_user = 0;
        $request_resource_type = $resourcedata["resource_type"];
        if(array_key_exists($request_resource_type, $manage_request_admin)) 
            {
            $admin_notify_user=$manage_request_admin[$request_resource_type];

            $request_query->sql = "INSERT INTO request(user, collection, created, request_mode, status, comments, assigned_to)
                                    VALUES (?, ?, NOW(), 1, 0, ?, ?)";
            $request_query->parameters = array("i",$userref, "i",$ref, "s",$message, "i",$admin_notify_user);

            // Setup assigned to user for bypass hook later on    
            if($admin_notify_user !== 0) 
                {
                $assigned_to_user = get_user($admin_notify_user);
                $notify_manage_request_admin = true;
                }
            }
        }   

    hook('autoassign_collection_requests', '', array($userref, isset($collectiondata) ? $collectiondata : array(), $message, isset($collectiondata)));

    // Regular Processing: autoassign using the resource type - collection request and no plugin is preventing this from running
    if(isset($collectiondata) && !is_null($manage_request_admin) && is_array($manage_request_admin) && !empty($manage_request_admin))
        {
        $all_r_types = get_resource_types();

        $resources = get_collection_resources($collectiondata['ref']);
        $resources = ($resources === false ? array() : $resources);
    
        // Get distinct resource types found in this collection:
        $collection_resources_by_type = array();
        foreach ($resources as $resource_id) 
            {
            $resource_data = get_resource_data($resource_id);
            // Create a list of resource IDs based on type to separate them into different collections:
            $collection_resources_by_type[$resource_data['resource_type']][] = $resource_id;
            }

        // Split into collections based on resource type:
        foreach ($collection_resources_by_type as $collection_type => $collection_resources)
            {
            // Store all resources of unmanaged type in one collection which will be sent to the system administrator:
            if(!isset($manage_request_admin[$collection_type]))
                {
                $collections['not_managed'] = create_collection($userref, $collectiondata['name'] . " " . date("ymdHis"));
                foreach ($collection_resources as $collection_resource_id) 
                    {
                    add_resource_to_collection($collection_resource_id, $collections['not_managed']);
                    }
                continue;
                }
                
            $r_type_index = array_search($collection_type, array_column($all_r_types, 'ref'));
            if ($r_type_index===false)
                {
                continue;
                }

            $collections[$collection_type] = create_collection($userref, $collectiondata['name'] . ' : ' . $all_r_types[$r_type_index]["name"] . " " . date("ymdHis"));
            foreach ($collection_resources as $collection_resource_id)
                {
                add_resource_to_collection($collection_resource_id, $collections[$collection_type]);
                }
            }
        if(isset($collections) && count($collections) > 0)
            { 
            foreach ($collections as $request_resource_type => $collection_id)
                {
                $assigned_to = '';
                $assigned_to_users=array();
                $assigned_to_user_emails=array();
                if(array_key_exists($request_resource_type, $manage_request_admin))
                    {
                    $assigned_to = $manage_request_admin[$request_resource_type];
                    get_config_option($assigned_to,'email_user_notifications', $send_email);
                    if($send_email)
                        {
                        // We need the user's email address
                        $assigned_to_user = get_user($manage_request_admin[$request_resource_type]);
                        $assigned_to_user_emails[] = $assigned_to_user['email'];
                        }
                    else
                        {
                        // We will be sending a notification message 
                        $assigned_to_users[] = get_user($assigned_to);
                        }
                    }
                else
                    {
                    // No specific user allocated, get all users, adding $email_notify address if this does not belong to a system user
                    $assigned_to_users=get_notification_users("RESOURCE_ACCESS"); 
                    $email_notify_is_user=false;
                    foreach ($assigned_to_users as $assigned_to_user)
                        {
                        if($assigned_to_user['email']==$email_notify)
                            {
                            $email_notify_is_user=true;
                            }
                        }
                    if(!$email_notify_is_user){$assigned_to_user_emails[] = $email_notify;}                        
                    }
                    
                if(trim($assigned_to) != '')
                    {
                    $request_query = "INSERT INTO request(user, collection, created, request_mode, `status`, comments, assigned_to)
                                            VALUES (?, ?, NOW(), 1, 0, ?, ?);";
                    $parameters=array("i", $userref, "i",$collection_id, "s",$message, "i",$assigned_to);
                    }
                else
                    {
                    $request_query = "INSERT INTO request(user, collection, created, request_mode, `status`, comments)
                                           VALUES (?, ?, NOW(), 1, 0, ?);";
                    $parameters=array("i", $userref, "i",$collection_id, "s",$message);
                    }

                ps_query($request_query, $parameters);
                $request = sql_insert_id();
                
                // Send the mail
                $email_message = $lang['requestassignedtoyoumail'] . "\n\n" . $baseurl . "/?q=" . $request . "\n";
                $assigned_to_notify_users=array();
                foreach ($assigned_to_users as $assigned_to_user)
                    {            
                    get_config_option($assigned_to_user["ref"],'email_user_notifications', $send_email);                    
                    if($send_email && filter_var($assigned_to_user["email"], FILTER_VALIDATE_EMAIL))
                        {  
                        $assigned_to_user_emails[] = $assigned_to_user['email'];
                        }
                    else
                        {
                        $assigned_to_notify_users[] = $assigned_to_user['ref'];
                        }
                    }
                    
                foreach ($assigned_to_user_emails as $assigned_to_user_email)
                    {
                    send_mail($assigned_to_user_email, $applicationname . ': ' . $lang['requestassignedtoyou'], $email_message);
                    }
                if (count($assigned_to_notify_users) > 0)
                    {
                    message_add($assigned_to_notify_users,$lang['requestassignedtoyou'],$baseurl . "/?q=" . $request,$userref,MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,MESSAGE_DEFAULT_TTL_SECONDS,MANAGED_REQUEST, $request);
                    }    
                unset($email_message);
                } # End for each collection

            $notify_manage_request_admin = false;
            $notification_sent = true;
            $send_default_notifications = false;
            }
        else
            {
            # No collections
            return false;
            }

        } // End of default manager (regular processing)
    else
        {
        if(hook('bypass_end_managed_collection_request', '', array(!isset($collectiondata), $ref, $request_query, $message, $templatevars, $assigned_to_user, $admin_mail_template, $user_mail_template)))
            {
            return true;
            }
        else
            // Back to regular processing
            {
            ps_query($request_query->sql, $request_query->parameters);
            $request=sql_insert_id();
            }
        }

    hook("afterrequestcreate", "", array($request));

    if($send_default_notifications)
        {
        # Automatically notify the admin who was assigned the request if we set this earlier:
         $templatevars["request_id"]=$request;
        $templatevars["requesturl"]=$baseurl."/?q=".$request;
        $templatevars["requestreason"]=$message;
        if($notify_manage_request_admin)
            {
            $notification_message = $lang['requestassignedtoyoumail'];
            $request_url = $baseurl . "/?q=" . $request;
            $admin_message = $notification_message . "\n\n" . $request_url . "\n";
            get_config_option($assigned_to_user['ref'],'email_user_notifications', $send_email, false);  // Don't get default as we may get the requesting user's preference
            if($send_email)
                {
                $assigned_to_user = get_user($admin_notify_user);
                send_mail($assigned_to_user['email'], $applicationname . ': ' . $lang['requestassignedtoyou'], $admin_message);
                }        
            else
                {   
                message_add($admin_notify_user,$notification_message, $request_url,$userref,MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,MESSAGE_DEFAULT_TTL_SECONDS,MANAGED_REQUEST, $request);
                }
            $notification_sent = true;
            }
            
        $admin_notify_emails=array();    
        $admin_notify_users=array();
        # Check if alternative request email notification address is set, only valid if collection contains resources of the same type
        if(isset($resource_type_request_emails)  )
            {
            $requestrestypes=ps_array("SELECT r.resource_type as value from collection_resource cr 
                                        left join resource r on cr.resource=r.ref where cr.collection=?", array("i",$ref));
            $requestrestypes=array_unique($requestrestypes);

            if(count($requestrestypes)==1 && isset($resource_type_request_emails[$requestrestypes[0]]))
                {
                $admin_notify_emails[]=$resource_type_request_emails[$requestrestypes[0]];
                }
            
            }
        
        if(!$notification_sent && (!isset($resource_type_request_emails) || $resource_type_request_emails_and_email_notify))
    
            {
            $notify_users=get_notification_users("RESOURCE_ACCESS");
            foreach($notify_users as $notify_user)
                {
                get_config_option($notify_user['ref'],'user_pref_resource_access_notifications', $send_message, $admin_resource_access_notifications);        
                if($send_message==false){continue;}     
                
                get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
                if($send_email && filter_var($notify_user["email"], FILTER_VALIDATE_EMAIL))
                    {
                    $admin_notify_emails[] = $notify_user['email']; 
                    }        
                else
                    {
                    $admin_notify_users[]=$notify_user["ref"];
                    }
                }
            }
        
        # Send the e-mails and/or notification messages   
        $admin_notify_message=$lang["user_made_request"]. "<br /><br />" . $lang["username"] . ": " . $username . "<br />$message<br /><br />";
        $notification_message = $lang["user_made_request"]. "<br />" . $lang["username"] . ": " . $username . "<br />" . $message;
        $admin_notify_message.=$lang["clicktoviewresource"] . "<br />" . $templatevars["requesturl"];
        foreach($admin_notify_emails as $admin_notify_email)
            {
            send_mail($admin_notify_email,$applicationname . ": " . $lang["requestcollection"] . " - $ref",$admin_notify_message,($always_email_from_user)?$useremail:$email_from,($always_email_from_user)?$useremail:$email_from,$admin_mail_template,$templatevars);
            }
        if (count($admin_notify_users)>0)
            {
            global $userref;        
            message_add($admin_notify_users,$notification_message,$templatevars["requesturl"],$userref,MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,MESSAGE_DEFAULT_TTL_SECONDS,MANAGED_REQUEST,$request);
            }
        }
    
    if ($request_senduserupdates)
        {
        $userconfirm_notification = $lang["requestsenttext"] . "<br /><br />" . $message;
        $userconfirmmessage = $userconfirm_notification . "<br /><br />" . $lang["clicktoviewresource"] . "<br />$baseurl/?c=$ref";
        get_config_option($userref,'email_user_notifications', $send_email);    
        if($send_email && filter_var($useremail, FILTER_VALIDATE_EMAIL))
            {
            send_mail($useremail,$applicationname . ": " . $lang["requestsent"] . " - $ref",$userconfirmmessage,$email_from,"",$user_mail_template,$templatevars,$applicationname);
            }        
        else
            {  
            global $userref;
            message_add($userref,$userconfirm_notification, $templatevars['url']);
            }
        }    
    
    # Increment the request counter for each resource in the requested collection
    ps_query("UPDATE resource set request_count=request_count+1  
               where ref in(select cr.resource from collection_resource cr where cr.collection=? and cr.resource = ref)", array("i",$ref));

    return true;
    }


/**
 * E-mails a basic resource request for a single resource (posted) to the team (not a managed request)
 *
 * @param  mixed $ref   The resource ID
 * @param  mixed $details   The request details provided by the user
 * @return void
 */
function email_resource_request($ref,$details)
    {
    global $applicationname,$email_from,$baseurl,$email_notify,$username,$useremail,$userref,$lang,$request_senduserupdates,$watermark,$filename_field,$view_title_field,$access,$resource_type_request_emails,$resource_request_reason_required, $admin_resource_access_notifications, $user_dl_limit, $user_dl_days, $k, $user_is_anon;
    
    if(intval($user_dl_limit) > 0)
        {
        $download_limit_check = get_user_downloads($userref,$user_dl_days);
        if($download_limit_check >= $user_dl_limit)
            {
            $details = str_replace(array("%%DOWNLOADED%%","%%LIMIT%%"),array($download_limit_check,$user_dl_limit),$lang['download_limit_request_text']) . "\n" . $details;
            }
        }

    $resourcedata=get_resource_data($ref);
    $templatevars['thumbnail']=get_resource_path($ref,true,"thm",false,"jpg",$scramble=-1,$page=1,($watermark)?(($access==1)?true:false):false);
    if (!file_exists($templatevars['thumbnail'])){
        $templatevars['thumbnail']="../gfx/".get_nopreview_icon($resourcedata["resource_type"],$resourcedata["file_extension"],false);
    }

    if (isset($filename_field)){
    $templatevars["filename"]=$lang["fieldtitle-original_filename"] . ": " . get_data_by_field($ref,$filename_field);}
    if (isset($resourcedata["field" . $view_title_field])){
    $templatevars["title"]=$resourcedata["field" . $view_title_field];}
    $templatevars['username']=$username . " (" . $useremail . ")";
    $templatevars['formfullname']=getval("fullname","");
    $templatevars['formemail']=getval("email","");
    $templatevars['formtelephone']=getval("contact","");
    $templatevars['url']=$baseurl."/?r=".$ref;
    $templatevars["requesturl"]=$templatevars['url'];
    
    
    // for anon user access use form vars
    if ($k!="" || $user_is_anon)
        {
        $templatevars["fullname"] = getvalescaped("fullname","");
        $useremail = getvalescaped("email","");
        }
    else 
        {
        $userdata=get_user($userref);
        $templatevars["fullname"]= isset($userdata["fullname"]) ? $userdata["fullname"] : ""; 
        }

    $htmlbreak="";
    global $use_phpmailer;
    if ($use_phpmailer){$htmlbreak="<br /><br />";}
    
    $list="";
    reset ($_POST);
    foreach ($_POST as $key=>$value)
        {
        if (strpos($key,"_label")!==false)
            {
            # Add custom field  
            $data="";
            $data=$_POST[str_replace("_label","",$key)];
            $list.=$htmlbreak. $value . ": " . $data."\n";
            }
        }
    $list.=$htmlbreak;      
    $templatevars['list']=$list;

    $templatevars['details']=stripslashes($details);
    $adddetails="";
    if ($templatevars['details']!=""){$adddetails=$lang["requestreason"] . ": " . newlines($templatevars['details'])."\n\n";} elseif ($resource_request_reason_required) {return false;}
    
    # Add custom fields
    $c="";
    global $custom_request_fields,$custom_request_required;
    if (isset($custom_request_fields))
        {
        $custom=explode(",",$custom_request_fields);
    
        # Required fields?
        if (isset($custom_request_required)) {$required=explode(",",$custom_request_required);}
    
        for ($n=0;$n<count($custom);$n++)
            {
            if (isset($required) && in_array($custom[$n],$required) && getval("custom" . $n,"")=="")
                {
                return false; # Required field was not set.
                }
            
            $c.=i18n_get_translated($custom[$n]) . ": " . getval("custom" . $n,"") . "\n\n";
            }
        }
    $templatevars["requestreason"]=$lang["requestreason"] . ": " . $templatevars['details']. $c ."";
    
    $message=$lang["user_made_request"] . "<br /><br />";
    $message.= isset($username)? $lang["username"] . ": " . $username . " (" . $useremail . ")<br />":"";
    $message.= (!empty($templatevars["formfullname"]))? $lang["fullname"].": ".$templatevars["formfullname"]."<br />":"";
    $message.= (!empty($templatevars["formemail"]))? $lang["email"].": ".$templatevars["formemail"]."<br />":"";
    $message.= (!empty($templatevars["formtelephone"]))? $lang["contacttelephone"].": ".$templatevars["formtelephone"]."<br />":"";
    $notification_message = $message . $adddetails . $c ; 
    $message.= $adddetails. $c . "<br /><br />" . $lang["clicktoviewresource"] . "<br />". $templatevars['url'];
    

    $admin_notify_emails=array();    
    $admin_notify_users=array();
    # Check if alternative request email notification address is set
    if(isset($resource_type_request_emails))
        {
        if(isset($resource_type_request_emails[$resourcedata["resource_type"]]))
            {
            $admin_notify_emails[]=$resource_type_request_emails[$resourcedata["resource_type"]];
            }
        } 
    else
        {
        $admin_notify_emails[]=$email_notify;
        $notify_users=get_notification_users("RESOURCE_ACCESS");
        foreach($notify_users as $notify_user)
            {
            get_config_option($notify_user['ref'],'user_pref_resource_access_notifications', $send_message, $admin_resource_access_notifications);        
            if($send_message==false){continue;}     
            
            get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
            if($send_email && filter_var($notify_user["email"], FILTER_VALIDATE_EMAIL))
                {
                $admin_notify_emails[] = $notify_user['email'];             
                }        
            else
                {
                $admin_notify_users[]=$notify_user["ref"];
                }
            }
        }
        
    foreach($admin_notify_emails as $admin_notify_email)
        {
        send_mail($admin_notify_email,$applicationname . ": " . $lang["requestresource"] . " - $ref",$message,$useremail,$useremail,"emailresourcerequest",$templatevars);
        }
    
    if (count($admin_notify_users)>0)
        {
        global $userref;
        message_add($admin_notify_users,$notification_message,$templatevars["requesturl"]);
        }
              
    if ($request_senduserupdates)
        { 
        $k=(getval("k","")!="")? "&k=".getval("k",""):"";
        $userconfirmmessage = $lang["requestsenttext"] . "<br /><br />" . $lang["requestreason"] . ": " . $templatevars['details'] . $c . "<br /><br />" . $lang["clicktoviewresource"] . "\n$baseurl/?r=$ref".$k;
        if(isset($userref))
            {
            get_config_option($userref,'email_user_notifications', $send_email);    
            if($send_email && filter_var($useremail, FILTER_VALIDATE_EMAIL))
                {
                send_mail($useremail,$applicationname . ": " . $lang["requestsent"] . " - $ref",$userconfirmmessage,$email_from,$email_notify,"emailusercollectionrequest",$templatevars);
                }        
            else
                {               
                message_add($userref,$userconfirmmessage, $templatevars['url']);
                }
            }
        else
            {
            $sender =  (!empty($useremail)) ? $useremail : ((!empty($templatevars["formemail"]))? $templatevars["formemail"] : "");
            
            if($sender!="" && filter_var($sender, FILTER_VALIDATE_EMAIL)){send_mail($sender,$applicationname . ": " . $lang["requestsent"] . " - $ref",$userconfirmmessage,$email_from,$email_notify,"emailuserresourcerequest",$templatevars);}  
            }
        }
    
    # Increment the request counter
    ps_query("update resource set request_count=request_count+1 where ref=?", array("i",$ref));
    }


/**
* Get collection of valid custom fields. A valid fields has at least the expected field properties
* 
* IMPORTANT: these fields are not metadata fields - they are configured through config options such as custom_researchrequest_fields
* 
* @param  array  $fields  List of custom fields. Often this will simply be the global configuration option (e.g custom_researchrequest_fields)
* 
* @return array
*/
function get_valid_custom_fields(array $fields)
    {
    return array_filter($fields, function($field)
        {
        global $lang, $FIXED_LIST_FIELD_TYPES;

        $expected_field_properties = array("id", "title", "type", "required");
        $available_properties      = array_keys($field);
        $missing_required_fields   = array_diff(
            $expected_field_properties,
            array_intersect($expected_field_properties, $available_properties));

        if(count($missing_required_fields) > 0)
            {
            debug("get_valid_custom_fields: custom field misconfigured. Missing properties: "
                . implode(", ", array_values($missing_required_fields)));
            return false;
            }

        // options property required for fixed list fields type
        if(in_array($field["type"], $FIXED_LIST_FIELD_TYPES) && !array_key_exists("options", $field))
            {
            debug("get_valid_custom_fields: custom fixed list field misconfigured. Missing the 'options' property!");
            return false;
            }

        return true;
        });
    }


/**
* Generate HTML properties for custom fields. These properties can then be used by other functions
* like render_custom_fields or process_custom_fields_submission
* 
* @param  array  $fields  List of custom fields as returned by get_valid_custom_fields(). Note: At this point code 
* assumes fields have been validated
* 
* @return array Returns collection items with the extra "html_properties" key
*/
function gen_custom_fields_html_props(array $fields)
    {
    return array_map(function($field)
        {
        $field["html_properties"] = array(
            "id"   => "custom_field_{$field["id"]}",
            "name" => (!empty($field["options"]) ? "custom_field_{$field["id"]}[]" : "custom_field_{$field["id"]}"),
        );
        return $field;
        }, $fields);
    }


/**
* Process posted custom fields
* 
* @param  array    $fields     List of custom fields
* @param  boolean  $submitted  Processing submitted fields?
* 
* @return array Returns collection of items with the extra "html_properties" key
*/
function process_custom_fields_submission(array $fields, $submitted)
    {
    return array_map(function($field) use ($submitted)
        {
        global $lang, $FIXED_LIST_FIELD_TYPES;

        $field["value"] = trim(getval($field["html_properties"]["name"], ""));

        if(in_array($field["type"], $FIXED_LIST_FIELD_TYPES))
            {
            // The HTML id and name are basically identical (@see gen_custom_fields_html_props() ). If field is of fixed 
            // list type, then the name prop will be appended with "[]". For this reason, when we call getval() we need 
            // to use the elements' ID instead.
            $submitted_data = getval($field["html_properties"]["id"], array());

            // Find the selected options
            $field["selected_options"] = array_filter($field["options"], function($option) use ($field, $submitted_data)
                {
                $computed_value = md5("{$field["html_properties"]["id"]}_{$option}");
                if(in_array($computed_value, $submitted_data))
                    {
                    return true;
                    }

                return false;
                });

            $field["value"] = implode(", ", $field["selected_options"]);
            }

        if($submitted && $field["required"] && $field["value"] == "")
            {
            $field["error"] = str_replace("%field", i18n_get_translated($field["title"]), $lang["researchrequest_custom_field_required"]);
            return $field;
            }

        return $field;
        }, gen_custom_fields_html_props(get_valid_custom_fields($fields)));
    }