<?php

function HookGrant_editAllCustomediteaccess($ref)
	{
	global $userref;
	$access=sql_value("SELECT resource value FROM grant_edit WHERE resource='$ref' AND user='$userref' AND (expiry IS null OR expiry>=NOW())","");
	if($access!=""){return true;}
	return false;
	}

function HookGrant_editAllModifysearcheditable($editable_filter, $user)
	{
    if(!is_numeric($user) || $editable_filter == "")
        {
        // There is no restriction on editing so granting edit access is moot
        return false;
        }
    $editable_filter = " ( " . $editable_filter . " OR (r.ref IN (SELECT resource FROM grant_edit WHERE user='$user' AND (expiry IS null OR expiry>=NOW()))))";    
	return $editable_filter;
	}
