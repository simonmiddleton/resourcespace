<?php

/* Note to tinkerers: To create your own custom authentication function, simply replace the function below
   with one of your own design. It needs to return false if the user is not authenticated,
   or an associative array if the user is ok. The array looks like so:
        Array
        (
           [username] => jdoe
           [displayname] => John Doe
           [group] => Marketing
           [email] => doe@acmewidget.com
        )

	The group returned here will be matched up to RS groups using the matching table configured by the user.
	If there is no match, the fallback user group will be used.
*/

function simpleldap_authenticate($username,$password){
    if (!function_exists('ldap_connect')){return false;}
    // given a username and password, return false if not authenticated, or 
    // associative array of displayname, username, e-mail, group if valid
    global $simpleldap;
    // ldap escape username
    $ldap_username = (function_exists('ldap_escape')) ? ldap_escape($username, '', LDAP_ESCAPE_DN) : $username;
	
	debug("LDAP - Connecting to LDAP server: " . $simpleldap['ldapserver'] . " on port " . $simpleldap['port']);
	if($simpleldap['port']==636)
		{
		$ds = ldap_connect('ldaps://' . $simpleldap['ldapserver'] . ':636');
		}
	else
		{
		$ds = ldap_connect( $simpleldap['ldapserver'],$simpleldap['port'] );
		}
	
	if($ds){
		debug("LDAP - Connected to LDAP server ");
		}
		
	ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
	
	if(!isset($simpleldap['ldaptype']) || $simpleldap['ldaptype']==1)  // AD - need to set this
		{
		ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
		}

	//must always check that password length > 0
	if (!(strlen($password) > 0 && strlen($username) > 0)){
		return false;
		}
	
	if(!isset($simpleldap['ldaptype']) || $simpleldap['ldaptype']==1)  // AD - need to set this
		{
		$binddomains=explode(";",$simpleldap['domain']);
		foreach ($binddomains as $binddomain)
			{
			debug("LDAP - Attempting to bind to LDAP server as : " . $username . "@" .  $binddomain);
			$login = @ldap_bind( $ds, "$ldap_username@" . $binddomain, $password );
			if (!$login){continue;}else{$userdomain=$binddomain;break;}
			}
		if (!$login){debug("LDAP - failed to bind to LDAP server");	return false; }
		}
	else
		{
		$binddns=explode(";",$simpleldap['basedn']);
		foreach ($binddns as $binddn)
			{
			$binduserstring = $simpleldap['loginfield'] . "=" . $ldap_username . "," . $binddn;
			debug("LDAP - Attempting to bind to LDAP server as : " . $binduserstring . ": " . $password);
			$login = @ldap_bind( $ds, $binduserstring, $password);
			if (!$login)
				{
				debug("LDAP bind failed: " . $binddn);
				continue;
				}
			else
				{
				$bindsuccess=true;
				break;
				}
			}
		if (!$login)
			{
			debug("LDAP - failed to bind to LDAP server");
			return false;
			}
		$userdomain=$simpleldap['domain'];
		}
		
	$email_attribute=$simpleldap['email_attribute'];
	$phone_attribute=$simpleldap['phone_attribute'];
	$ldapgroupfield=$simpleldap['ldapgroupfield'];
	$attributes = array("displayname",$ldapgroupfield,$email_attribute,$phone_attribute);
	$loginfield=$simpleldap['loginfield'];
	$filter = "(&(objectClass=person)(". $loginfield . "=" . $ldap_username . "))";
	 
	$searchdns=explode(";",$simpleldap['basedn']);
	$dn=array();
	$ldapconnections=array();
	foreach($searchdns as $searchdn)
		{
		debug("LDAP - preparing search DN: " . $searchdn);
		$dn[]=$searchdn;
		}
	for($x=0;$x<count($dn);$x++)
		{
		 if($simpleldap['port']==636)
	                {
        	        $ldapconnections[$x] = ldap_connect('ldaps://' . $simpleldap['ldapserver'] . ':636');
                	}
	        else
        	        {
        	        $ldapconnections[$x] = ldap_connect($simpleldap['ldapserver'],$simpleldap['port']);
                	}
		
		if(!isset($simpleldap['ldaptype']) || $simpleldap['ldaptype']==1) 
			{
			$binduserstring = $ldap_username . "@" . $userdomain;
			}
		else
			{
			$binduserstring = $simpleldap['loginfield'] . "=" . $ldap_username . "," . $simpleldap['basedn'];
			}
		debug("LDAP - binding as " . $binduserstring);
		if(!(@ldap_bind($ldapconnections[$x], $binduserstring, $password )))
			{
			debug("LDAP BIND failed: " . $binduserstring);
			continue;
			}
		
		debug("LDAP - searching " . $dn[$x] . " as " . $binduserstring);
		
    }
	debug("LDAP - performing search: filter=" . $filter);
	debug("LDAP - retrieving attributes: " . implode(",",$attributes));
	$result = ldap_search($ldapconnections, $dn, $filter, $attributes);
	
	//exit(print_r($result));
	foreach ($result as $value) 
		{ 
		debug("LDAP - search returned value " . $value);
		debug("LDAP - found " . ldap_count_entries($ds,$value) . " entries");
    if(ldap_count_entries($ds,$value)>0)
			{ 
			$search = $value; 
			break; 
			}
		} 
	if (isset($search))
		{$entries = ldap_get_entries($ds, $search);}
	else
		{
		debug("LDAP - search returned no values");
		return false;
		}
		
		
	
	if($entries["count"] > 0){

		if (isset($entries[0]['displayname']) && count($entries[0]['displayname']) > 0){
			$displayname = $entries[0]['displayname'][0];
		} else {
			$displayname = '';
		}

		//$ldap_groupfield = $simpleldap[$ldapgroupfield];
 
		$department = '';
		debug("LDAP - checking for group attribute - " . $ldapgroupfield);
			
		//$entry = ldap_first_entry($ds, $search);
		//var_dump($entries);		

		$usermemberof=array();

		if (isset($entries[0][$ldapgroupfield]) && count($entries[0][$ldapgroupfield]) > 0)
			{
			debug("LDAP - found group attribute - checking against configured mappings");
			$usermemberofgroups=$entries[0][$ldapgroupfield];
			
			$deptresult = sql_query('select ldapgroup, rsgroup from simpleldap_groupmap order by priority asc');

			// Go through each configured ldap->RS group mapping, adding each to the array of groups that user is a member of. Update $department with each match so we end up with the highest priority dept
			foreach ($deptresult as $thedeptresult)
				{
				$deptname=$thedeptresult['ldapgroup'];
                $deptmap=$thedeptresult['rsgroup'];
                $knowndept[strtolower($deptname)] = $deptmap;
                if (
                    (isset($deptmap) && !empty($deptmap))
                    && in_array(strtolower($deptname), array_map('strtolower', $usermemberofgroups)))
					{
					$department=$deptname;
					$usermemberof[]=$deptname;
					}				
				}
			// Go through all mappings and add any unknown groups to the list of mappings so that it can be easily used (LDAP group names can be hard to remember)
			foreach ($usermemberofgroups as $usermemberofgroup)
				{
				if(!isset($knowndept[strtolower($usermemberofgroup)])) // This group is not in the current list
					{
					if (!is_numeric($usermemberofgroup))
						{
						// ignore numbers; this is a kludgey way to deal with the fact
						// that some ldap servers seem to return a result count as the first value
						$newdept = escape_check($usermemberofgroup);
						$usermemberof[]=$newdept;
						sql_query("replace into simpleldap_groupmap (ldapgroup, rsgroup) values (\"$newdept\",NULL)");
						} 
					}
				}
			}
		//Extract email info
		if ((isset($entries[0][$email_attribute])) && count($entries[0][$email_attribute]) > 0)
			{
			$email = $entries[0][$email_attribute][0];
			}
		else
			{
			$email = $username . '@' . $simpleldap['emailsuffix'];
			}
			
		//Extract phone info
		if (isset($entries[0][$phone_attribute]) && count($entries[0][$phone_attribute]) > 0)
			{
			$phone = $entries[0][$phone_attribute][0];
			}
		else
			{
			$phone = 'Unknown';
			}
				
		
		$return['domain'] = $userdomain;
		$return['username'] = $username;
		$return['binduser'] = $binduserstring;
		$return['displayname'] = $displayname;
		$return['group'] = $department;
		$return['email'] = $email;
		$return['phone'] = $phone;
		$return['memberof'] = $usermemberof;
		return $return;

	}


	ldap_unbind($ds);



}
