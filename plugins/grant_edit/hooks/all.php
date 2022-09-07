<?php

function HookGrant_editAllCustomediteaccess($ref)
	{
	global $userref;
	$access = ps_value("SELECT resource value FROM grant_edit WHERE resource = ? AND user = ? AND (expiry IS null OR expiry >= NOW())", array("i",$ref,"i",$userref), "");
	if($access!=""){return true;}
	return false;
	}

function HookGrant_editAllModifysearcheditable($editable_filter, $user)
	{
    if(!is_numeric($user) || $editable_filter->sql == "")
        {
        // There is no restriction on editing so granting edit access is moot
        return false;
        }
    $editable_filter->sql = " ( " . $editable_filter->sql . " OR (r.ref IN (SELECT resource FROM grant_edit WHERE user = ? AND (expiry IS null OR expiry>=NOW()))))";
    $editable_filter->parameters = array_merge($editable_filter->parameters,["i",$user]); 
	return $editable_filter;
    }

function HookGrant_editAllExport_add_tables()
    {
    return array("grant_edit"=>array());
    }
