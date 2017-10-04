<?php

function HookGrant_editAllCustomediteaccess()
	{
	global $ref,$userref;
	$access=sql_value("select resource value from grant_edit where resource='$ref' and user='$userref' and (expiry is null or expiry>=NOW())","");
	if($access!=""){return true;}
	return false;
	}

function HookGrant_editAllModifysearcheditable($editable_filter, $user)
	{
    if(!is_numeric($user))
        {
        return false;
        }
    if(($editable_filter) != "")
        {
        $editable_filter .= " OR ";   
        }
    $editable_filter .= " r.ref IN (select resource from grant_edit where user='$user' and (expiry is null or expiry>=NOW()))";
    return $editable_filter;
    }
