<?php
# Research functions
# Functions to accomodate research requests

function send_research_request(array $rr_cfields)
	{
	# Insert a search request into the requests table.
	
	# Resolve resource types
	$rt="";
	$types=get_resource_types();
	for ($n=0;$n<count($types);$n++) {
		if (getval("resource" . $types[$n]["ref"],"")!="") {
			if ($rt!="") {
				$rt.=", ";
			} 
			$rt.=$types[$n]["ref"];
		}
	}
	
	global $userref, $custom_researchrequest_fields;
	$as_user=getval("as_user",$userref,true); # If userref submitted, use that, else use this user
	$rr_name=getval("name","");
	$rr_description=getval("description","");
	$parameters=array("i",$as_user, "s",$rr_name, "s",$rr_description);

	$rr_deadline = getval("deadline","");
	if($rr_deadline=="")
		{
	 	$rr_deadline=NULL;
		}
	$rr_contact = getval("contact","");
	$rr_email = getval("email","");
	$rr_finaluse = getval("finaluse","");
	$parameters=array_merge($parameters,array("s",$rr_deadline, "s",$rr_contact, "s",$rr_email, "s",$rr_finaluse));

	# $rt
	$rr_noresources = getval("noresources","");
	if($rr_noresources=="")
		{
		$rr_noresources=NULL;
		}
	$rr_shape = getval("shape","");
	$parameters=array_merge($parameters,array("s",$rt, "i",$rr_noresources, "s",$rr_shape));

    /**
    * @var string JSON representation of custom research request fields after removing the generated HTML properties we 
    *             needed during form processing
    * @see gen_custom_fields_html_props()
    */
    $rr_cfields_json = json_encode(array_map(function($v) { unset($v["html_properties"]); return $v; }, $rr_cfields), JSON_UNESCAPED_UNICODE);
    if(json_last_error() !== JSON_ERROR_NONE)
        {
        trigger_error(json_last_error_msg());
        }
    $rr_cfields_json_sql = ($rr_cfields_json == "" ? "" : "'".$rr_cfields_json."'");
	$parameters=array_merge($parameters,array("s",$rr_cfields_json_sql));

	ps_query("insert into research_request(created,user,name,description,deadline,contact,email,finaluse,resource_types,noresources,shape, custom_fields_json)
				values (now(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $parameters);
	
	# E-mails a resource request (posted) to the team
	global $applicationname,$email_from,$baseurl,$email_notify,$username,$userfullname,$useremail,$lang, $admin_resource_access_notifications;
	
	$templatevars['ref']=sql_insert_id();
	$templatevars['teamresearchurl']=$baseurl."/pages/team/team_research_edit.php?ref=" . $templatevars['ref'];
	$templatevars['username']=$username;
	$templatevars['userfullname']=$userfullname;
	$templatevars['useremail']=getvalescaped("email",$useremail); # Use provided e-mail (for anonymous access) or drop back to user email.
	$templatevars['url']=$baseurl."/pages/team/team_research_edit.php?ref=".$templatevars['ref'];
	
	$message="'$username' ($userfullname - $useremail) " . $lang["haspostedresearchrequest"] . ".\n\n";
	$notification_message = $message;
	$message.=$templatevars['teamresearchurl'];
	hook("modifyresearchrequestemail");
	
	$research_notify_emails=array();
	$research_notify_users = array();
	$notify_users=get_notification_users("RESEARCH_ADMIN");
	foreach($notify_users as $notify_user)
		{
		get_config_option($notify_user['ref'],'user_pref_resource_access_notifications', $send_message, $admin_resource_access_notifications);		  
		if($send_message==false){continue;}		
		
		get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
		if($send_email && $notify_user["email"]!="")
			{
			$research_notify_emails[] = $notify_user['email'];				
			}        
		else
			{
			$research_notify_users[]=$notify_user["ref"];
			}
		}
	
    foreach($research_notify_emails as $research_notify_email)
		{
		send_mail($research_notify_email,$applicationname . ": " . $lang["newresearchrequestwaiting"],$message,$useremail,"","emailnewresearchrequestwaiting",$templatevars);
		}
	
	if (count($research_notify_users)>0)
		{
		global $userref;
        message_add($research_notify_users,$notification_message,$templatevars["teamresearchurl"]);
		}
	}

function get_research_requests($find="",$order_by="name",$sort="ASC")
	{
	$searchsql="";
	$use_order_by = "";
	$use_sort = "";
	$parameters=array();
	if ($find!="") {
		$searchsql="WHERE name like ? or description like ? or contact like ? or ref=?"; 
		$parameters=array("s","%{$find}%", "s","%{$find}%", "s","%{$find}%", "i",(int)$find);
	}
	if (in_array($order_by, array("ref","name","created","status","assigned_to","collection")))
		{
		$use_order_by = $order_by;		
		}
	if (in_array($sort, array("ASC","DESC")))
		{
		$use_sort = $sort;		
		}
	return ps_query("select *,(select username from user u where u.ref=r.user) username, 
		(select username from user u where u.ref=r.assigned_to) assigned_username from research_request r 
		$searchsql 
		order by $use_order_by $use_sort", $parameters);
	}

function get_research_request($ref)
	{
	$rr_sql="SELECT rr.ref,rr.name,rr.description,rr.deadline,rr.email,rr.contact,rr.finaluse,rr.resource_types,rr.noresources,rr.shape,
					rr.created,rr.user,rr.assigned_to,rr.status,rr.collection,rr.custom_fields_json,
					(select u.username from user u where u.ref=rr.user) username, 
					(select u.username from user u where u.ref=rr.assigned_to) assigned_username from research_request rr where rr.ref=?";
	$rr_parameters=array("i",$ref);

	$return=ps_query($rr_sql, $rr_parameters);
	if (count($return) == 0)
	    {
	    return false;
	    }
	return $return[0];
	}

function save_research_request($ref)
	{
	# Save
	global $baseurl,$email_from,$applicationname,$lang;
	
	$parameters=array("i",$ref);

	if (getval("delete","")!="")
		{
		# Delete this request.
		ps_query("delete from research_request where ref=? limit 1", $parameters);
		return true;
		}

	# Check the status, if changed e-mail the originator
	$currentrequest=ps_query("select status, assigned_to, collection from research_request where ref=?", $parameters);

	$oldstatus=(count($currentrequest)>0)?$currentrequest[0]["status"]:0;
	$newstatus=getval("status",0);
	$collection=(count($currentrequest)>0)?$currentrequest[0]["collection"]:0;
	$oldassigned_to=(count($currentrequest)>0)?$currentrequest[0]["assigned_to"]:0;
	$assigned_to=getval("assigned_to",0);
	
	$templatevars['url']=$baseurl . "/?c=" . $collection;
	$templatevars['teamresearchurl']=$baseurl."/pages/team/team_research_edit.php?ref=" . $ref;	
	
	if ($oldstatus!=$newstatus)
		{
		$requesting_user=ps_query("select u.email, u.ref from user u,research_request r where u.ref=r.user and r.ref=?", $parameters);
		$requesting_user = $requesting_user[0];
		$message="";
		if ($newstatus==1) 
			{
			$message=$lang["researchrequestassignedmessage"];$subject=$lang["researchrequestassigned"];
			$notification_message = $message;
			$message.=$templatevars['url'];
			get_config_option($requesting_user['ref'],'email_user_notifications', $send_email);    
			if($send_email && $requesting_user["email"]!="")
				{
				send_mail ($requesting_user['email'],$applicationname . ": " . $subject,$message,"","","emailresearchrequestassigned",$templatevars);
				}        
			else
				{
				message_add($requesting_user["ref"],$notification_message,(($collection!=0)?$templatevars["url"]:"#"));
				}
				
			# Log this
			daily_stat("Assigned research request",0);
			}
		if ($newstatus==2)
			{
			$message=$lang["researchrequestcompletemessage"] . "\n\n" . $lang["clicklinkviewcollection"] . "\n\n" . $templatevars['url'];$subject=$lang["researchrequestcomplete"];
			$notification_message = $message;
			get_config_option($requesting_user['ref'],'email_user_notifications', $send_email);    
			if($send_email && $requesting_user["email"]!="")
				{
				send_mail ($requesting_user['email'],$applicationname . ": " . $subject,$message,"","","emailresearchrequestcomplete",$templatevars);
				}        
			else
				{
				message_add($requesting_user["ref"],$notification_message,(($collection!=0)?$templatevars["url"]:"#"));
				}
			
			# Log this			
			daily_stat("Processed research request",0);
			}
		}
		
	if ($oldassigned_to!=$assigned_to)
		{
		$message = $lang["researchrequestassigned"];
		$subject = $lang["researchrequestassigned"];
		$assigned_message = $message;
		$message .= $templatevars['teamresearchurl'];
		$assigned_to_user=get_user($assigned_to);
		get_config_option($assigned_to,'email_user_notifications', $send_email);    
		if($send_email && $assigned_to_user["email"]!="")
			{
			send_mail ($assigned_to_user['email'],$applicationname . ": " . $subject,$assigned_message,"","","emailresearchrequestassigned",$templatevars);
			}        
		else
			{
			message_add($assigned_to,$assigned_message,$templatevars['teamresearchurl']);
			}
		}
	
	$parameters=array("i",$newstatus, "i",$assigned_to, "i",$ref);

	ps_query("update research_request set status=?, assigned_to=? where ref=?", $parameters);
	
	# Copy existing collection
	$rr_copyexisting=getval("copyexisting","");
	$rr_copyexistingref=getval("copyexistingref","");
	if ($rr_copyexisting !="" && is_numeric($collection))
		{
		$parameters=array("i",$collection, "i",$rr_copyexistingref, "i",$collection);
		ps_query("insert into collection_resource(collection,resource) 
		           select ?, resource from collection_resource 
				   where collection=? and resource not in (select resource from collection_resource where collection=?)", $parameters);
		}
	}


function get_research_request_collection($ref)
	{
	$parameters=array("i",$ref);
	$return=ps_value("select collection value from research_request where ref=?",$parameters,0);
	if (($return==0) || (strlen($return)==0)) {return false;} else {return $return;}
	}

function set_research_collection($research,$collection)
	{
	$parameters=array("i",$collection, "i",$research);
	ps_query("update research_request set collection=? where ref=?", $parameters);
	}
