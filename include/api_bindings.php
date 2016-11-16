<?php
/*
 * API v2 : Bindings to built in functions
 *
 * Montala Limited, July 2016
 *
 * This allows us to exclude certain parameters for security reasons (such as $use_permissions) and also to
 * map to more API-appropriate parameters and output if necessary.
 *
 * For documentation please see: http://www.resourcespace.com/knowledge-base/api/
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

function api_get_resource_path($ref,$getfilepath,$size,$generate=true,$extension="jpg",$page=1,$watermarked=false,$alternative=-1)
    {
    return get_resource_path($ref,$getfilepath,$size,$generate,$extension,-1,$page,$watermarked,"",$alternative,false);
    }
    
function api_get_resource_data($resource)
    {
    return get_resource_data($resource);
    }

function api_get_alternative_files($resource,$order_by="",$sort="")
    {
    return get_alternative_files($resource,$order_by,$sort);
    }
    
function api_get_resource_types()
    {
    return get_resource_types("", true);
    }

function api_add_alternative_file($resource,$name,$description="",$file_name="",$file_extension="",$file_size=0,$alt_type='')
	{
    return add_alternative_file($resource,$name,$description,$file_name,$file_extension,$file_size,$alt_type);
	}
	
function api_delete_alternative_file($resource,$ref)
	{
	return delete_alternative_file($resource,$ref);
    }

function api_upload_file($ref,$no_exif=false,$revert=false,$autorotate=false,$file_path="")
    {
    return upload_file($ref,$no_exif,$revert,$autorotate,$file_path);
    }
    
function api_upload_file_by_url($ref,$no_exif=false,$revert=false,$autorotate=false,$url="")
    {
    return upload_file_by_url($ref,$no_exif,$revert,$autorotate,$url);
    }

function api_get_related_resources($ref)
    {
    return get_related_resources($ref);
    }

function api_get_field_options($ref)
    {
    return get_field_options($ref);
    }
    
function api_get_user_collections()
	{
    global $userref;
    return get_user_collections($userref);
    }
    
function api_add_resource_to_collection($resource,$collection)
    {
    return add_resource_to_collection($resource,$collection);
    }
    
function api_remove_resource_from_collection($resource,$collection)
    {
    return remove_resource_from_collection($resource,$collection);                  
    }
    
function api_create_collection($name)
	{
    global $userref;
    return create_collection($userref,$name);
    }
    
function api_delete_collection($ref)
    {
    return delete_collection($ref);
    }
    
function api_search_public_collections($search="", $order_by="name", $sort="ASC", $exclude_themes=true, $exclude_public=false)
    {
    return search_public_collections($search, $order_by, $sort, $exclude_themes, $exclude_public);
    }
    

    
    