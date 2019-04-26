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
        
    if(!checkperm('s'))
        {
        return array();
        }
        
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
     if(!checkperm('s'))
        {
        return array();
        }
    $getsizes=explode(",",$getsizes);
    $results = search_get_previews($search,$restypes,$order_by,$archive,$fetchrows,$sort,false,0,false,false,$recent_search_daylimit,false,false,false,false,false,$getsizes,$previewext);
    
    if (!is_array($results))
        {
        return array();
        }
        
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
    if (!(checkperm("c") || checkperm("d")) || checkperm("XU" . $resource_type))
        {
        return false;
        }

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

function api_update_field($resource,$field,$value,$nodevalues=false)
    {
    // Provides simple way to update field by passing in simple string values for text fields,
    // comma separated values for fixed list (node) fields, using double quotes
    // to enclose strings and backslash as escape character
    // Uses update_field and add_resource_nodes/delete_resource_nodes
    
    global $FIXED_LIST_FIELD_TYPES, $category_tree_add_parents, $resource_field_column_limit;
    
    $resourcedata=get_resource_data($resource,true);
    $editaccess = get_edit_access($resource,$resourcedata['archive'],false,$resourcedata);
    
    if(!is_numeric($field))
        {
        // Name may have been passed    
        $field = sql_value("select ref value from resource_type_field where name='" . escape_check($field) . "'","");
        }
        
    if(!$editaccess || !metadata_field_edit_access($field))
        {return false;}        
    
    $fieldinfo = get_resource_type_field($field);
    
    if(!$fieldinfo)
        {
        return false;
        }
    
    if(in_array($fieldinfo['type'], $FIXED_LIST_FIELD_TYPES))
        {
        $fieldnodes = get_nodes($field,null,$fieldinfo['type'] == FIELD_TYPE_CATEGORY_TREE);
        
        // Set up arrays of node ids to add/remove and all new nodes. 
        $nodes_to_add    = array();
        $nodes_to_remove = array();
        $newnodes        = array();
        
        // Get currently selected nodes for this field 
        $current_field_nodes = get_resource_nodes($resource, $field);
        
        if($nodevalues)
            {
            // An array of node IDs has been passed, we can use these directly
            $sent_nodes = explode(",",$value);
            foreach($fieldnodes as $fieldnode)
                {
                // Add to array of nodes, unless it has been added to array already as a parent for a previous node
                if (in_array($fieldnode["ref"],$sent_nodes) && !in_array($fieldnode["ref"],$nodes_to_add))
                    {
                    if(!in_array($fieldnode["ref"],$current_field_nodes))
                        {
                        $nodes_to_add[] = $fieldnode["ref"];
                        if($fieldinfo['type']==FIELD_TYPE_CATEGORY_TREE && $category_tree_add_parents) 
                            {
                            // Add all parent nodes for category trees
                            $parent_nodes=get_parent_nodes($fieldnode["ref"]);
                            foreach($parent_nodes as $parent_node_ref=>$parent_node_name)
                                {
                                $nodes_to_add[]=$parent_node_ref;
                                }
                            }
                        }
                    $newnodes[] = $fieldnode["ref"];
                    }
                else if(in_array($fieldnode["ref"],$current_field_nodes) && !in_array($fieldnode["name"],$sent_nodes))
                    {
                    $nodes_to_remove[] = $fieldnode["ref"];
                    }
                }
            }
        else
            {
            // Get all the new values into an array
            $newvalues    = trim_array(str_getcsv($value));
               
            # If this is a dynamic keyword field need to add any new entries to the field nodes
            if($fieldinfo['type'] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && !checkperm('bdk' . $field))
                {
                $currentoptions = array();    
                foreach($fieldnodes as $fieldnode)
                    {
                    $fieldoptiontranslations = explode('~', $fieldnode['name']);
                    if(count($fieldoptiontranslations) < 2)
                        {
                        $currentoptions[]=trim($fieldnode['name']); # Not a translatable field
                        debug("update_field: current field option: '" . trim($fieldnode['name']));
                        }
                    else
                        {
                        $default="";
                        for ($n=1;$n<count($fieldoptiontranslations);$n++)
                            {
                            # Not a translated string, return as-is
                            if (substr($fieldoptiontranslations[$n],2,1)!=":" && substr($fieldoptiontranslations[$n],5,1)!=":" && substr($fieldoptiontranslations[$n],0,1)!=":")
                                {
                                $currentoptions[]=trim($fieldnode['name']);
                                debug("update_field: current field option: '" . $fieldnode['name']);
                                }
                            else
                                {
                                # Support both 2 character and 5 character language codes (for example en, en-US).
                                $p=strpos($fieldoptiontranslations[$n],':');                         
                                $currentoptions[]=trim(substr($fieldoptiontranslations[$n],$p+1));
                                debug("update_field: current field option: '" . trim(substr($fieldoptiontranslations[$n],$p+1)));
                                }
                            }
                        }
                    }
    
                foreach($newvalues as $newvalue)
                    {
                    # Check if each new value exists in current options list
                    if(!in_array($newvalue, $currentoptions) && $newvalue != '')
                        {
                        # Append the option and update the field
                        $newnode          = set_node(null, $field, escape_check(trim($newvalue)), null, null, true);
                        $nodes_to_add[]   = $newnode;
                        $currentoptions[] = trim($newvalue);
                        $fieldnodes[]  = array("ref" => $newnode,"name" => trim($newvalue)); 
                        $newnodes[] = $newnode;
                        debug("update_field: field option added: '" . trim($newvalue));
                        }
                    }
                }
                        
            foreach($fieldnodes as $fieldnode)
                {
                // Add to array of nodes, unless it has been added to array already as a parent for a previous node
                if (in_array($fieldnode["name"],$newvalues) && !in_array($fieldnode["ref"],$nodes_to_add))
                    {
                    if(!in_array($fieldnode["ref"],$current_field_nodes))
                        {
                        $nodes_to_add[] = $fieldnode["ref"];
                        if($fieldinfo['type']==FIELD_TYPE_CATEGORY_TREE && $category_tree_add_parents) 
                            {
                            // Add all parent nodes for category trees
                            $parent_nodes=get_parent_nodes($fieldnode["ref"]);
                            foreach($parent_nodes as $parent_node_ref=>$parent_node_name)
                                {
                                $nodes_to_add[]=$parent_node_ref;
                                }
                            }
                        }                    
                    $newnodes[] = $fieldnode["ref"];
                    }
                else if(in_array($fieldnode["ref"],$current_field_nodes) && !in_array($fieldnode["name"],$newvalues))
                    {
                    $nodes_to_remove[] = $fieldnode["ref"];
                    }
                }
            }

        if(count($nodes_to_add) > 0 || count($nodes_to_remove) > 0)
            {
            # Update resource_node table
            db_begin_transaction();
            delete_resource_nodes($resource,$nodes_to_remove);
            if(count($nodes_to_add)>0)
                {
                add_resource_nodes($resource,$nodes_to_add, false);
                }
            db_end_transaction();
            
            // Update log
            // First use the node array to getnames with node id as key
            $node_options = array_column($fieldnodes, 'name', 'ref');
                
            // Build existing value for log:
            $curr_nodes = array_intersect_key($node_options,array_flip($current_field_nodes));    
            $curr_nodes_str  = "," . implode(",",$curr_nodes);
            
            # If this is a 'joined' field we need to add it to the resource column
            $joins = get_resource_table_joins();
            if(in_array($fieldinfo['ref'],$joins))
                {
                // Build new value for resource table:
                $new_nodes = array_intersect_key($node_options,array_flip($newnodes));  
                $new_nodes_str = implode(",",$new_nodes);
                $truncated_value = truncate_join_field_value($new_nodes_str);

                // Remove backslashes from the end of the truncated value
                if(substr($truncated_value, -1) === '\\')
                    {
                    $truncated_value = substr($truncated_value, 0, strlen($truncated_value) - 1);
                    }	

                sql_query("UPDATE resource SET field".$field."='" . $truncated_value . "' WHERE ref='$resource'");
                }
            }

        return true;
        }
    else
        {
        return update_field($resource,$field,$value);
        }
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

function api_get_resource_path($ref, $getfilepath, $size="", $generate=true, $extension="jpg", $page=1, $watermarked=false, $alternative=-1)
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
            $resource = get_resource_data($ref);
            if(!is_numeric($ref) || !resource_download_allowed($ref,$size,$resource["resource_type"],$alternative))
                {
                $return[$ref] = "";
                continue;
                }
            $return[$ref] = get_resource_path($ref, filter_var($getfilepath, FILTER_VALIDATE_BOOLEAN), $size, $generate, $extension, -1, $page, $watermarked, '', $alternative, false);
            }
        return $return;
        }
        
    $resource = get_resource_data($ref);
    if(!is_numeric($ref) || !resource_download_allowed($ref,$size,$resource["resource_type"],$alternative))
        {
        return false;
        }
            
    return get_resource_path($ref, filter_var($getfilepath, FILTER_VALIDATE_BOOLEAN), $size, $generate, $extension, -1, $page, $watermarked, "", $alternative, false);
    }
    
function api_get_resource_data($resource)
    {
    $resdata = get_resource_data($resource);
    
    // Check access
    $access = get_resource_access($resource);
    if($access == 2)
        {return false;}
    
    // Remove column data from inaccessible fields
    $joins = get_resource_table_joins();
    foreach($joins as $datajoin)
        {
        $joinfield = get_resource_type_field($datajoin);
        if((!metadata_field_view_access($datajoin)) || ($access == 1 && $joinfield["hide_when_restricted"] == 1))
            {
            unset($resdata["field" . $datajoin]);
            }
        }
        
    return $resdata;
    }

function api_get_alternative_files($resource,$order_by="",$sort="")
    {
    global $disable_alternative_files, $alt_files_visible_when_restricted;
    $access = get_resource_access($resource);
    if($disable_alternative_files || ($access!=0 && !($access==1 && $alt_files_visible_when_restricted)))
        {return false;}
    return get_alternative_files($resource,$order_by,$sort);
    }
    
function api_get_resource_types()
    {
    return get_resource_types("", true);
    }

function api_add_alternative_file($resource, $name, $description = '', $file_name = '', $file_extension = '', $file_size = 0, $alt_type = '', $file = '')
    {
    global $disable_alternative_files;

    if($disable_alternative_files || (0 < $resource && (!(get_edit_access($resource) || checkperm('A')))))
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
    global $disable_alternative_files;
    if($disable_alternative_files || (0 < $resource && (!(get_edit_access($resource) || checkperm('A')))))
        {
        return false;
        }
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
    global $enable_related_resources;
    $access = get_resource_access($ref);
    if(!$enable_related_resources || $access == 2)
        {
        return array();
        }
    return get_related_resources($ref);
    }

function api_get_field_options($ref, $nodeinfo = false)
    {
    if(!is_numeric($ref))
        {
        // Name may have been passed    
        $ref = sql_value("select ref value from resource_type_field where name='" . escape_check($ref) . "'","");
        }
        
    if(!metadata_field_view_access($ref))
        {return false;}
        
    return get_field_options($ref, $nodeinfo);
    }
    
function api_get_user_collections()
	{
    global $userref;
    if (checkperm("b"))
        {
        return array();
        }
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
    global $userref, $collection_allow_creation;
    if (checkperm("b") || !$collection_allow_creation)
        {
        return false;
        }
    
    return create_collection($userref,$name);
    }
    
function api_delete_collection($ref)
    {
    if (checkperm("b") || !collection_writeable($ref))
        {return false;}
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
    
function api_set_node($ref, $resource_type_field, $name, $parent = '', $order_by = 0,$returnexisting = false)
    {
    global $FIXED_LIST_FIELD_TYPES;
        
    $fieldinfo = get_resource_type_field($resource_type_field);
    if(
       !in_array($fieldinfo['type'], $FIXED_LIST_FIELD_TYPES)
        ||
       !(checkperm('a') || checkperm('k') || ($fieldinfo['type'] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && !checkperm('bdk' . $resource_type_field)))
       )
        {
        // API user doesn't have permission to add new nodes
        return false;
        }
    if(strtoupper($ref) == 'NULL'){$ref = null;}
    if(strtoupper($parent) == 'NULL'){$parent = null;}
    return set_node($ref, $resource_type_field, $name, $parent, $order_by,$returnexisting = false);  
    }

function api_add_resource_nodes($resource,$nodestring)
    {
    // This is only for super admins
    if(!checkperm('a'))
        {return false;}        
    $nodes = explode(",",$nodestring);
    return add_resource_nodes($resource,$nodes);
    }
    
 function api_add_resource_nodes_multi($resources,$nodestring)
    {
    // This is only for super admins
    if(!checkperm('a'))
        {return false;}        
    $resourcearr = explode(",",$resources);
    $nodes = explode(",",$nodestring);
    return add_resource_nodes_multi($resourcearr,$nodes);
    }
    
function api_resource_log_last_rows($minref = 0, $days = 7, $maxrecords = 0)
    {
    return resource_log_last_rows($minref, $days, $maxrecords);
    }    

