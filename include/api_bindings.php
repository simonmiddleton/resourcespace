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
    $fetchrows = ($fetchrows > 0 ? $fetchrows : -1);

    # Search capability.
    # Note the subset of the available parameters. We definitely don't want to allow override of permissions or filters.
    $results = do_search($search,$restypes,$order_by,$archive,$fetchrows,$sort);

    if (!is_array($results))
        {
        return array();
        }
   
    for ($n = 0; $n < count($results); $n++)
        {
        if (is_array($results[$n]))
            {
            $results[$n] = array_map("i18n_get_translated",$results[$n]);
            }
        }
    return $results;
    }
   
function api_search_get_previews($search,$restypes="",$order_by="relevance",$archive=0,$fetchrows=-1,$sort="desc",$recent_search_daylimit="",$getsizes="",$previewext="jpg")
    {
    # Extension to search capability that also returns the URLs of preview file sizes requested using the $getsizes parameter that match the requested extension.
    $getsizes=explode(",",$getsizes);
    $results = search_get_previews($search,$restypes,$order_by,$archive,$fetchrows,$sort,false,0,false,false,$recent_search_daylimit,false,false,false,false,false,$getsizes,$previewext);
    $resultcount= count ($results);
    for($n=0;$n<$resultcount;$n++)
        {
        if(is_array($results[$n]))
            {
            $results[$n] = array_map("i18n_get_translated",$results[$n]);
            }
        }
    return $results;
    }
  
function api_get_resource_field_data($resource)
    {
    # Get all field data for a resource
    $results = get_resource_field_data($resource);
    $resultcount= count ($results);
        {
        for($n=0;$n<$resultcount;$n++)
            {
            $results[$n] = array_map("i18n_get_translated",$results[$n]);
            }
        }
    return $results;
    }

function api_create_resource($resource_type,$archive=999,$url="",$no_exif=false,$revert=false,$autorotate=false,$metadata="")
    {
    $no_exif    = filter_var($no_exif, FILTER_VALIDATE_BOOLEAN);
    $revert     = filter_var($revert, FILTER_VALIDATE_BOOLEAN);
    $autorotate = filter_var($autorotate, FILTER_VALIDATE_BOOLEAN);

    # Create a new resource
    $ref=create_resource($resource_type,$archive);
    
    # Also allow upload URL in the same pass (API specific, to reduce calls)
    if ($url!="")
        {     
        $return=upload_file_by_url($ref,$no_exif,$revert,$autorotate,$url);
        if ($return===false) {return false;}
        }
        
    # Also allow metadata to be passed here.
    if ($metadata!="")
        {
        $metadata=json_decode($metadata);
        foreach ($metadata as $field=>$value)
            {
            update_field($ref,$field,$value);
            }
        }
    
    return $ref;
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

function api_get_resource_path($ref, $getfilepath, $size, $generate=true, $extension="jpg", $page=1, $watermarked=false, $alternative=-1)
    {
    # Set defaults
    if ($alternative=="") {$alternative=-1;}
    if ($page=="") {$page=1;}

    $refs = json_decode($ref, true);
    if(is_array($refs))
        {
        $return = array();

        foreach($refs as $ref)
            {
            if(!is_numeric($ref))
                {
                continue;
                }

            $return[$ref] = get_resource_path($ref, filter_var($getfilepath, FILTER_VALIDATE_BOOLEAN), $size, $generate, $extension, -1, $page, $watermarked, '', $alternative, false);
            }

        return $return;
        }

    return get_resource_path($ref, filter_var($getfilepath, FILTER_VALIDATE_BOOLEAN), $size, $generate, $extension, -1, $page, $watermarked, "", $alternative, false);
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

function api_add_alternative_file($resource, $name, $description = '', $file_name = '', $file_extension = '', $file_size = 0, $alt_type = '', $file = '')
    {
    global $disable_alternative_files;

    if($disable_alternative_files || (0 < $resource && (!get_resource_access($resource) || checkperm('A'))))
        {
        return false;
        }

    // Just insert record in the database
    if('' == trim($file))
        {
        return add_alternative_file($resource, $name, $description, $file_name, $file_extension, $file_size, $alt_type);
        }

    // A file has been specified so add it as alternative
    $alternative_ref     = add_alternative_file($resource, $name, $description, $file_name, $file_extension, $file_size, $alt_type);
    $rs_alternative_path = get_resource_path($resource, true, '', true, $file_extension, -1, 1, false, '', $alternative_ref);

    if(!copy($file, $rs_alternative_path))
        {
        return false;
        }

    chmod($rs_alternative_path, 0777);

    $file_size = @filesize_unlimited($rs_alternative_path);

    $resource = escape_check($resource);

    sql_query("UPDATE resource_alt_files SET file_size='{$file_size}', creation_date = NOW() WHERE resource = '{$resource}' AND ref = '{$alternative_ref}'");

    global $alternative_file_previews_batch;
    if($alternative_file_previews_batch)
        {
        create_previews($resource, false, $file_extension, false, false, $alternative_ref);
        }

    return $alternative_ref;
    }

function api_delete_alternative_file($resource,$ref)
	{
	return delete_alternative_file($resource,$ref);
    }

function api_upload_file($ref,$no_exif=false,$revert=false,$autorotate=false,$file_path="")
    {
    $no_exif    = filter_var($no_exif, FILTER_VALIDATE_BOOLEAN);
    $revert     = filter_var($revert, FILTER_VALIDATE_BOOLEAN);
    $autorotate = filter_var($autorotate, FILTER_VALIDATE_BOOLEAN);

    return upload_file($ref,$no_exif,$revert,$autorotate,$file_path);
    }
    
function api_upload_file_by_url($ref,$no_exif=false,$revert=false,$autorotate=false,$url="")
    {
    $no_exif    = filter_var($no_exif, FILTER_VALIDATE_BOOLEAN);
    $revert     = filter_var($revert, FILTER_VALIDATE_BOOLEAN);
    $autorotate = filter_var($autorotate, FILTER_VALIDATE_BOOLEAN);

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
    $exclude_themes = filter_var($exclude_themes, FILTER_VALIDATE_BOOLEAN);
    $exclude_public = filter_var($exclude_public, FILTER_VALIDATE_BOOLEAN);
    $results = search_public_collections($search, $order_by, $sort, $exclude_themes, $exclude_public);
    $resultcount= count ($results);
        {
        for($n=0;$n<$resultcount;$n++)
            {
            if(is_array($results[$n]))
                {
                $results[$n] = array_map("i18n_get_translated",$results[$n]);
                }
            }
        }
    return $results;
    }
