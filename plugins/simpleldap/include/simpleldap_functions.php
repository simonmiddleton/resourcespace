<?php

function simpleldap_authenticate($username,$password)
    {
    if (!function_exists('ldap_connect')){return false;}
    // given a username and password, return false if not authenticated, or 
    // associative array of displayname, username, e-mail, group if valid
    global $simpleldap;

    if(isset($simpleldap['LDAPTLS_REQCERT_never']) && $simpleldap['LDAPTLS_REQCERT_never'])
        {
        putenv('LDAPTLS_REQCERT=never');
        }

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

    if($ds)
        {
        debug("LDAP - Connected to LDAP server ");
        }
    else
        {
        debug("LDAP - Unable to connect to LDAP server " . $simpleldap['ldapserver'] . " on port " . $simpleldap['port']);        
        return false;
        }
        
    ldap_set_option($ds, LDAP_OPT_NETWORK_TIMEOUT, 2); 
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
            $GLOBALS["use_error_exception"] = true;
            try 
                {
                $login = ldap_bind( $ds, "$ldap_username@" . $binddomain, $password );
                }
            catch(Exception $e)
                {
                debug("ERROR: LDAP bind failed " . $e->getMessage());
                $login=false;
                }
            unset($GLOBALS["use_error_exception"]);
            if (!$login)
                {
                continue;
                }
             else
                {
                $userdomain=$binddomain;
                break;
                }
            }
        if (!$login)
            {
            debug("LDAP - failed to bind to LDAP server : " . $username . "@" .  $binddomain);
            return false;
            }
        }
    else
        {
        $binddns=explode(";",$simpleldap['basedn']);
        foreach ($binddns as $binddn)
            {
            $binduserstring = $simpleldap['loginfield'] . "=" . $ldap_username . "," . $binddn;
            debug("LDAP - Attempting to bind to LDAP server as : " . $binduserstring);
            $GLOBALS["use_error_exception"] = true;
            try 
                {
                $login = ldap_bind( $ds, $binduserstring, $password);
                }
            catch(Exception $e)
                {
                debug("ERROR: LDAP bind failed " . $e->getMessage());
                $login=false;
                }
            unset($GLOBALS["use_error_exception"]);
            
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
        
    $email_attribute = mb_strtolower($simpleldap['email_attribute']);
    $phone_attribute = mb_strtolower($simpleldap['phone_attribute']);

	$ldapgroupfield=$simpleldap['ldapgroupfield'];
	$attributes = array("displayname",$ldapgroupfield,$email_attribute,$phone_attribute);
	$loginfield=$simpleldap['loginfield'];
	$filter = "(&(objectClass=person)(". $loginfield . "=" . ldap_escape($ldap_username,'',LDAP_ESCAPE_FILTER) . "))";

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
        
        ldap_set_option($ds, LDAP_OPT_NETWORK_TIMEOUT, 2); 
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

        if(!isset($simpleldap['ldaptype']) || $simpleldap['ldaptype']==1)  // AD - need to set this
            {
            ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
            }

        $GLOBALS["use_error_exception"] = true;
        try 
            {
            $login = ldap_bind($ldapconnections[$x], $binduserstring, $password );
            }
        catch(Exception $e)
            {
            debug("ERROR: LDAP bind failed " . $e->getMessage());
            $login=false;
            }
        unset($GLOBALS["use_error_exception"]);
        
        debug("LDAP - binding as " . $binduserstring);
        if(!$login)
            {
            debug("LDAP BIND failed: " . $binduserstring);
            continue;
            }
        
        debug("LDAP - searching " . $dn[$x] . " as " . $binduserstring);        
        }
    debug("LDAP - performing search: filter=" . $filter);
    debug("LDAP - retrieving attributes: " . implode(",",$attributes));

    $result = ldap_search($ldapconnections, $dn, $filter, $attributes);
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
        {
        $entries = ldap_get_entries($ds, $search);
        }
    else
        {
        debug("LDAP - search returned no values");
        return false;
        }

    if($entries["count"] > 0)
        {
        if (isset($entries[0]['displayname']) && count($entries[0]['displayname']) > 0)
            {
            $displayname = simpleldap_to_utf8($entries[0]['displayname'][0]);
            }
        else
            {
            $displayname = '';
		    }

        $department = '';
        debug("LDAP - checking for group attribute - " . $ldapgroupfield);

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
                    && in_array(strtolower($deptname), array_map('strtolower', $usermemberofgroups))
                    )
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
                        $newdept = escape_check(simpleldap_to_utf8($usermemberofgroup));
                        $usermemberof[]=$newdept;
                        sql_query("replace into simpleldap_groupmap (ldapgroup, rsgroup) values (\"$newdept\",NULL)");
                        } 
                    }
                }
            }
        //Extract email info
        if ((isset($entries[0][$email_attribute])) && count($entries[0][$email_attribute]) > 0)
            {
            $email = simpleldap_to_utf8($entries[0][$email_attribute][0]);
            }
        else
            {
            $email = $username . '@' . $simpleldap['emailsuffix'];
            }
			
        //Extract phone info
        if (isset($entries[0][$phone_attribute]) && count($entries[0][$phone_attribute]) > 0)
            {
            $phone = simpleldap_to_utf8($entries[0][$phone_attribute][0]);
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


/**
* Helper function to convert received data from LDAP server to UTF-8
* 
* @param string $str String to convert to UTF8
* 
* @return string
*/
function simpleldap_to_utf8($str)
    {
    global $simpleldap;

    if(!is_string($str) || !isset($simpleldap['ldap_encoding']) || trim($simpleldap['ldap_encoding']) == "")
        {
        return $str;
        }

    $converted_str = iconv($simpleldap['ldap_encoding'], "UTF-8", $str);

    return ($converted_str !== false ? $converted_str : $str);
    }