<?php
/*
 * API v2 functions
 *
 * Montala Limited, July 2016
 *
 */

function get_api_key($user)
    {
    // Return a private scramble key for this user.
    global $api_scramble_key;
    return hash("sha256", $user . $api_scramble_key);
    }

function check_api_key($username,$querystring,$sign)
    {
    // Check a query is signed correctly.
    
    // Fetch user ID and API key
    $user=get_user_by_username($username); if ($user===false) {return false;}
    $private_key=get_api_key($user);
    
    # Sign the querystring ourselves and check it matches.
    
    # First remove the sign parameter as this would not have been present when signed on the client.
    $s=strpos($querystring,"&sign=");
    if ($s===false) {return false;}
    $querystring=substr($querystring,0,$s);
    
    # Calculate the expected signature.
    $expected=hash("sha256",$private_key . $querystring);
    
    # Was it what we expected?
    return $expected==$sign;
    }

function execute_api_call($query)
    {
    // Execute the specified API function.
    $params=array();parse_str($query,$params);        
    if (!array_key_exists("function",$params)) {return false;}
    $function=$params["function"];
    if (!function_exists("api_" . $function)) {return false;}
    
    $eval="return api_" . $function . "(";
    $n=1;while (true)
        {
        if (array_key_exists("param" . $n,$params))
            {
            if ($n>1) {$eval.=",";}
            $eval.="\"" . $params["param" . $n] . "\"";
            $n++;
            }
        else
            {
            break;
            }
        }
    $eval.=");";
    return json_encode(eval($eval));
    }
    

/* -------------------------------------------------------------------------------------
 *
 * Start of API bridge functions... expose built in functions via wrappers.
 *
 * This allows us to exclude certain parameters for security reasons (such as $use_permissions) and also to
 * map to more API-appropriate parameters and output if necessary.
 * 
 */

function api_do_search($search,$restypes="",$order_by="relevance",$archive=0,$fetchrows=-1,$sort="desc")
   {
   # Search capability.
   # Note the subset of the available parameters. We definitely don't want to allow override of permissions or filters.
   return do_search($search,$restypes,$order_by,$archive,$fetchrows,$sort);
   }

function api_get_resource_field_data($resource)
   {
   # Get all field data for a resource
   return get_resource_field_data($resource);
   }

function api_create_resource($resource_type,$archive=999)
    {
    # Create a new resource
    return create_resource($resource_type,$archive);
    }

function api_update_field($resource,$field,$value)
    {
    # Update a metadata field
    return update_field($resource,$field,$value);
    }

function api_delete_resource($resource)
    {
    return delete_resource($resource);        
    }

function api_copy_resource($from,$resource_type=-1)
    {
    return copy_resource($from,$resource_type);            
    }

function api_get_resource_log($resource, $fetchrows=-1)
    {
    return get_resource_log($resource, $fetchrows);
    }
    
function api_update_resource_type($resource,$type)
	{
    return update_resource_type($resource,$type);
    }

function api_get_resource_path($ref,$getfilepath,$size,$generate=true,$extension="jpg",$scramble=-1,$page=1,$watermarked=false,$file_modified="",$alternative=-1,$includemodified=true)
    {
    return get_resource_path($ref,$getfilepath,$size,$generate,$extension,$scramble,$page,$watermarked,$file_modified,$alternative,$includemodified);
    }
    
function api_get_resource_data($resource)
    {
    return get_resource_data($resource);
    }

function api_get_alternative_files($resource,$order_by="",$sort="")
    {
    return get_alternative_files($resource,$order_by,$sort);
    }
    
function api_get_resource_types($types = "", $translate = true)
    {
    return get_resource_types($types, $translate);
    }

function api_add_alternative_file($resource,$name,$description="",$file_name="",$file_extension="",$file_size=0,$alt_type='')
	{
    return add_alternative_file($resource,$name,$description,$file_name,$file_extension,$file_size,$alt_type);
	}
	
function api_delete_alternative_file($resource,$ref)
	{
	return delete_alternative_file($resource,$ref);
    }




