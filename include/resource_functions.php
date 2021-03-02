<?php
# Resource functions
# Functions to create, edit and index resources

$GLOBALS['get_resource_path_fpcache'] = array();
/**
* Get resource path/ resource URL/ download URL for this resource
* 
* IMPORTANT: the download URL should always be used client side (public)
*            whilst filstore path is private for internal use only
* 
* @uses sql_value()
* @uses get_alternative_file()
* @uses get_resource_data()
* 
* @param integer $ref              Resource ID 
* @param boolean $getfilepath      Set to TRUE to get the filestore (physical) path
* @param string  $size             Specify which size of the resource should be returned. Use '' for original file
* @param boolean $generate         Generate folder if not found
* @param string  $extension        Extension of the file we are looking for. For original file, this would be the file
*                                  extension, otherwise use the preview extension (e.g image preview will have JPG
*                                  while video preview can have MP4 or others)
* @param boolean $scramble         Set to TRUE to get the scrambled folder (requires scramble key for it to work)
* @param integer $page             For documents, use the page number we are trying to get the preview of.
* @param boolean $watermarked      Get the watermark version?
* @param string  $file_modified    Specify when the file was last modified
* @param integer $alternative      ID of the alternative file
* @param boolean $includemodified  Show when the file was last modified
* 
* @return string
*/
function get_resource_path(
    $ref,
    $getfilepath,
    $size = '',
    $generate = true,
    $extension = 'jpg',
    $scramble = true,
    $page = 1,
    $watermarked = false,
    $file_modified = '',
    $alternative = -1,
    $includemodified = true
)
    {
    # returns the correct path to resource $ref of size $size ($size==empty string is original resource)
    # If one or more of the folders do not exist, and $generate=true, then they are generated
    if(!preg_match('/^[a-zA-Z0-9]+$/', $extension))
        {
        $extension = 'jpg';
        }
    if($extension=='icc')
        {
        # use the preview path
        $size='pre';
        }

    $override = hook(
        'get_resource_path_override',
        '',
        array($ref, $getfilepath, $size, $generate, $extension, $scramble, $page, $watermarked, $file_modified, $alternative, $includemodified)
    );

    if(is_string($override))
        {
        return $override;
        }

    global $storagedir, $originals_separate_storage, $fstemplate_alt_threshold, $fstemplate_alt_storagedir,
           $fstemplate_alt_storageurl, $fstemplate_alt_scramblekey, $scramble_key, $hide_real_filepath,
           $migrating_scrambled, $scramble_key_old, $filestore_evenspread, $filestore_migrate;

    // Return URL pointing to download.php. download.php will call again get_resource_path() to ask for the physical path
    if(!$getfilepath && $hide_real_filepath)
        {
        global $baseurl, $k, $get_resource_path_extra_download_query_string_params;

        if(
            !isset($get_resource_path_extra_download_query_string_params)
            || is_null($get_resource_path_extra_download_query_string_params)
            || !is_array($get_resource_path_extra_download_query_string_params)
        )
            {
            $get_resource_path_extra_download_query_string_params = array();
            }

        return generateURL(
            "{$baseurl}/pages/download.php",
            array(
                'ref'         => $ref,
                'size'        => $size,
                'ext'         => $extension,
                'page'        => $page,
                'alternative' => $alternative,
                'k'           => $k,
                'noattach'    => 'true',
            ),
            $get_resource_path_extra_download_query_string_params);
        }

    if ($size=="")
        {
        # For the full size, check to see if the full path is set and if so return that.
        global $get_resource_path_fpcache;
        truncate_cache_arrays();

        if (!isset($get_resource_path_fpcache[$ref])) {$get_resource_path_fpcache[$ref]=sql_value("select file_path value from resource where ref='" . escape_check($ref) . "'","");}
        $fp=$get_resource_path_fpcache[$ref];
        
        # Test to see if this nosize file is of the extension asked for, else skip the file_path and return a $storagedir path. 
        # If using staticsync, file path will be set already, but we still want the $storagedir path for a nosize preview jpg.
        # Also, returning the original filename when a nosize 'jpg' is looked for is no good, since preview_preprocessing.php deletes $target.
        
        $test_ext = explode(".",$fp);$test_ext=trim(strtolower($test_ext[count($test_ext)-1]));
        
        if (($test_ext == $extension || $alternative > 0) && strlen($fp)>0 && (strpos($fp,"/")!==false || strlen($fp)>1))
            {               
            if ($getfilepath)
                {
                global $syncdir; 
                $syncdirmodified=hook("modifysyncdir","all",array($ref, $fp, $alternative)); if ($syncdirmodified!=""){return $syncdirmodified;}
                if(!($alternative>0))
                    {return $syncdir . "/" . $fp;}
                elseif(!$generate)
                    {
                    // Alternative file and using staticsync. Would not be generating path if checking for an existing file.
                    // Check if file is present in syncdir, else continue to get the $storagedir location
                    $altfile = get_alternative_file($ref,$alternative);
                    if($altfile["file_extension"]==$extension && file_exists($altfile["file_name"]))
                        {return $altfile["file_name"];}
                    }
                }
            else 
                {
                global $baseurl_short, $k;
                return $baseurl_short . "pages/download.php?ref={$ref}&size={$size}&ext={$extension}&noattach=true&k={$k}&page={$page}&alternative={$alternative}"; 
                }
            }
        }

    // Create a scrambled path using the scramble key
    // It should be very difficult or impossible to work out the scramble key, and therefore access
    // other resources, based on the scrambled path of a single resource.
    if($scramble && isset($scramble_key) && '' != $scramble_key)
        {
        $skey = $scramble_key;

        // FSTemplate support - for trial system templates
        if(0 < $fstemplate_alt_threshold && $ref < $fstemplate_alt_threshold && -1 == $alternative)
            {
            $skey = $fstemplate_alt_scramblekey;
            }

        $scramblepath = substr(md5("{$ref}_{$skey}"), 0, 15);
        }
    
    
    if ($extension=="") {$extension="jpg";}
    
    $folder="";
    #if (!file_exists(dirname(__FILE__) . $folder)) {mkdir(dirname(__FILE__) . $folder,0777);}
    
    # Original separation support
    if($originals_separate_storage)
        {
        global $originals_separate_storage_ffmpegalts_as_previews;
        if($alternative>0 && $originals_separate_storage_ffmpegalts_as_previews)
            {
            $alt_data=sql_query('select * from resource_alt_files where ref=' . $alternative);
            if(!empty($alt_data))
                {
                // determin if this file was created from $ffmpeg_alternatives
                $ffmpeg_alt=alt_is_ffmpeg_alternative($alt_data[0]);
                if($ffmpeg_alt)
                    {
                    $path_suffix="/resized/";
                    }
                else
                    {
                    $path_suffix="/original/";
                    }
                }
            else
                {
                $path_suffix="/original/";
                }
            }
        elseif($size=="")
            {
            # Original file (core file or alternative)
            $path_suffix="/original/";
            }
        else
            {
            # Preview or thumb
            $path_suffix="/resized/";
            }
        }
    else
        {
        // If getting the physical path, use the appropriate directory separator. For URL, it can only use forward 
        // slashes (/). For more information, see RFC 3986 (https://tools.ietf.org/html/rfc3986)
        $path_suffix = ($getfilepath ? DIRECTORY_SEPARATOR : "/");
        }

    for ($n=0;$n<strlen($ref);$n++)
        {
        // If using $filestore_evenspread then the path is generated using the least significant figure first instead of the greatest significant figure
        $refpos = $filestore_evenspread ? -($n+1) : $n;
        $folder .= substr($ref,$refpos,1);

        if ($scramble && isset($scramblepath) && ($n==(strlen($ref)-1)))
            {
            $folder.="_" . $scramblepath;
            }  

        $folder.="/";
        if ((!(file_exists($storagedir . $path_suffix . $folder))) && $generate)
            {
            @mkdir($storagedir . $path_suffix . $folder,0777,true);
            chmod($storagedir . $path_suffix . $folder,0777);
            }
        }
        
    # Add the page to the filename for everything except page 1.
    if ($page==1) {$p="";} else {$p="_" . $page;}
    
    # Add the alternative file ID to the filename if provided
    if ($alternative>0) {$a="_alt_" . $alternative;} else {$a="";}
    
    # Add the watermarked url too
    if ($watermarked) {$p.="_wm";}
    
    $sdir=$storagedir;
    
    # FSTemplate support - for trial system templates
    if ($fstemplate_alt_threshold>0 && $ref<$fstemplate_alt_threshold && $alternative==-1)
        {
        $sdir=$fstemplate_alt_storagedir;
        }
    # switch the size back so the icc profile name matches the original name and find the original extension
    $icc=false;
    if ($extension=='icc')
        {
        $size='';
        $icc=true;
        $extension=sql_value("select file_extension value from resource where ref='" . escape_check($ref) . "'", 'jpg');
        }
            
        
    $filefolder=$sdir . $path_suffix . $folder;
    
    # Fetching the file path? Add the full path to the file
    if ($getfilepath)
        {
        $folder=$filefolder; 
        }
    else
        {
        global $storageurl;$surl=$storageurl;
        
        # FSTemplate support - for trial system templates
        if ($fstemplate_alt_threshold>0 && $ref<$fstemplate_alt_threshold && $alternative==-1)
            {
            $surl=$fstemplate_alt_storageurl;
            }
        
        $folder=$surl . $path_suffix . $folder;
        }
    if ($scramble && isset($skey))
        {
        $file_old=$filefolder . $ref . $size . $p . $a . "." . $extension;
        $file_new=$filefolder . $ref . $size . $p . $a . "_" . substr(md5($ref . $size . $p . $a . $skey),0,15) . "." . $extension;
        $file=$folder . $ref . $size . $p . $a . "_" . substr(md5($ref . $size . $p . $a . $skey),0,15) . "." . $extension;
        if (file_exists($file_old))
            {
            rename($file_old, $file_new);
            }
        }
    else
        {
        $file=$folder . $ref . $size . $p . $a . "." . $extension;
        }
        
    if($icc)
        {
        $file.='.icc';
        }

    # Append modified date/time to the URL so the cached copy is not used if the file is changed.
    if (!$getfilepath && $includemodified)
        {        
        if ($file_modified=="")
            {
            # Work out the value from the file on disk
            $disk_path=get_resource_path($ref,true,$size,false,$extension,$scramble,$page,$watermarked,'',$alternative,false);
            if (file_exists($disk_path))
                {  
                $file .= "?v=" . urlencode(filemtime($disk_path));
                }
            }
        else
            {
            # Use the provided value
            $file .= "?v=" . urlencode($file_modified);
            }
        }

    if (($scramble && isset($migrating_scrambled) && $migrating_scrambled) || ($filestore_migrate && $filestore_evenspread))
        {
        // Check if there is a file at the path using no/previous scramble key or with $filestore_evenspread=false;
        // Most will normally have been moved using pages/tools/xfer_scrambled.php or pages/tools/filestore_migrate.php
        
        // Flag to set whether we are migrating to even out filestore distibution or because of scramble key change
        $redistribute_mode = $filestore_migrate;

        // Get the new paths without migrating to prevent infinite recursion
        $migrating_scrambled = false;
        $filestore_migrate = false;
        $newpath = $getfilepath ? $file : get_resource_path($ref,true,$size,true,$extension,true,$page,false,'',$alternative);
        
        // Use old settings to get old path before migration and migrate if found
        if($redistribute_mode)
            {
            $filestore_evenspread = false;
            }
        else
            {
            $scramble_key_saved = $scramble_key;
            $scramble_key = isset($scramble_key_old) ? $scramble_key_old : "";
            }        
        $oldfilepath=get_resource_path($ref,true,$size,false,$extension,true,$page,false,'',$alternative);
        if (file_exists($oldfilepath))
            {
            if(!file_exists(dirname($newpath)))
                {
                mkdir(dirname($newpath),0777,true);
                }
            rename ($oldfilepath,$newpath);
            }
        
        // Reset key/evenspread value
        if($redistribute_mode)
            {
            $filestore_evenspread = true;
            $filestore_migrate = true;
            }
        else
            {
            $scramble_key = $scramble_key_saved;
            $migrating_scrambled = true;
            }
        }
    
    return $file;
    }


$GLOBALS['get_resource_data_cache'] = array();
function get_resource_data($ref,$cache=true)
    {
    if ((string)(int)$ref != (string)$ref)
        {
        return false;
        }
    # Returns basic resource data (from the resource table alone) for resource $ref.
    # For 'dynamic' field data, see get_resource_field_data
    global $default_resource_type, $get_resource_data_cache,$always_record_resource_creator;
    if ($cache && isset($get_resource_data_cache[$ref])) {return $get_resource_data_cache[$ref];}
    truncate_cache_arrays();
    $resource=sql_query("select *,mapzoom,lock_user from resource where ref='" . escape_check($ref) . "'");
    if (count($resource)==0) 
        {
        if ($ref>=0)
            {
            return false;
            }
        else
            {
            # For upload templates (negative reference numbers), generate a new resource if upload permission.
            if (!(checkperm("c") || checkperm("d"))) {return false;}
            elseif(!hook('replace_upload_template_creation', '', array($ref)))
                {
                if (isset($always_record_resource_creator) && $always_record_resource_creator)
                    {
                    global $userref;
                    $user = $userref;
                    }
                else {$user = -1;}

                $default_archive_state = escape_check(get_default_archive_state());
                $wait = sql_query("insert into resource (ref,resource_type,created_by, archive) values ('" . escape_check($ref) . "','$default_resource_type','$user', '{$default_archive_state}')");
                $resource = sql_query("select *,mapzoom,lock_user from resource where ref='" . escape_check($ref) . "'");
                }
            }
        }
    
    if (isset($resource[0]))
        {
        $get_resource_data_cache[$ref]=$resource[0];
        return $resource[0];
        }
    else
        {
        return false;
        }
    }



/**
 * get_resource_data_batch - get data from resource table for all resource IDs
 *
 * @param  mixed $refs - array of resource IDs
 * @return array
 */
function get_resource_data_batch($refs)
    {
    global $get_resource_data_cache;
    truncate_cache_arrays();
    $resids = array_filter($refs,function($id){return (string)(int)$id==(string)$id;});
    $resdata=sql_query("SELECT *,mapzoom,lock_user FROM resource WHERE ref IN ('" . implode("','",$resids)  . "')");
    // Create array with resource ID as index
    $resource_data = array();
    foreach($resdata as $resdatarow)
       {
       $resource_data[$resdatarow["ref"]] = $resdatarow;
       $get_resource_data_cache[$resdatarow["ref"]] = $resdatarow;
       }
    return $resource_data;
    }

/**
* Updates $resource with the name/value pairs in $data - this relates to the resource table column, not metadata.
*
* @param  int  $resource   ID of resource
* @param  array  $data     Array of data to be applied to resource
* 
* @return boolean
*/
function put_resource_data($resource,$data)
    {   
    global $edit_contributed_by;

    // Check access
    if (!get_edit_access($resource)) {return false;}
    
    // Define safe columns
    $safe_columns=array("resource_type","creation_date","rating","user_rating","archive","access","mapzoom","modified","geo_lat","geo_long");

    // Permit the created by column to be changed also
    if (checkperm("v") && $edit_contributed_by) {$safe_columns[]="created_by";}
    
    $sql="";
    foreach ($data as $column=>$value)
        {
        if (!in_array($column,$safe_columns)) {return false;} // Attempted to update a column outside of the expected set
        if ($sql!="") {$sql.=",";}
        $sql.=$column . "='" . escape_check($value) . "'";
        }
    if ($sql=="") {return false;} // Nothing to do.
    sql_query("update resource set $sql where ref='" . escape_check($resource) . "'");
    return true;
    }


function create_resource($resource_type,$archive=999,$user=-1)
    {
    # Create a new resource.
    global $always_record_resource_creator,$index_contributed_by, $k;

    if(!is_numeric($archive))
        {
        return false;   
        }

    $alltypes=get_resource_types();    
    if(!in_array($resource_type,array_column($alltypes,"ref")))
        {
        return false;    
        }
    
    if ($archive==999)
        {
        # Work out an appropriate default state
        for ($n=-2;$n<3;$n++)
            {
            if (checkperm("e" . $n))
                {
                $archive = $n;
                break;
                }
            }
        }

	if($user == -1
        && (
            $archive == -2
            || $archive == -1
            || (isset($always_record_resource_creator) && $always_record_resource_creator)
        )
    )
		{
		# Work out user ref - note: only for content in status -2 and -1 (user submitted / pending review).
		global $userref;
		$user = $userref;
		}

	sql_query("insert into resource(resource_type,creation_date,archive,created_by) values ('$resource_type',now(),'" . escape_check($archive) . "','$user')");
	
	$insert=sql_insert_id();
	
	# set defaults for resource here (in case there are edit filters that depend on them)
	set_resource_defaults($insert);	
	
	# Autocomplete any blank fields.
	autocomplete_blank_fields($insert, true);

	# Always index the resource ID as a keyword
	remove_keyword_mappings($insert, $insert, -1);
	add_keyword_mappings($insert, $insert, -1);

	# Log this			
    daily_stat("Create resource",$insert);

    resource_log($insert, LOG_CODE_CREATED, 0);
    if(upload_share_active())
        {
        resource_log($insert, LOG_CODE_EXTERNAL_UPLOAD, 0,'','',$k . ' ('  . get_ip() . ')');
        }
	
	# Also index contributed by field, unless disabled
	if ($index_contributed_by)
		{
		$resource=get_resource_data($insert);
		$userinfo=get_user($resource["created_by"]);
		add_keyword_mappings($insert,$userinfo["username"] . " " . $userinfo["fullname"],-1);
		}

	# Copying a resource of the 'pending review' state? Notify, if configured.
	if ($archive==-1)
		{
		notify_user_contributed_submitted(array($insert));
		}

	return $insert;
	}
    

function update_hitcount($ref)
    {
    global $resource_hit_count_on_downloads;
    
    # update hit count if not tracking downloads only
    if (!$resource_hit_count_on_downloads) 
        { 
        # greatest() is used so the value is taken from the hit_count column in the event that new_hit_count is zero to support installations that did not previously have a new_hit_count column (i.e. upgrade compatability).
        sql_query("update resource set new_hit_count=greatest(hit_count,new_hit_count)+1 where ref='$ref'",false,-1,true,0);
        }
    }   

function save_resource_data($ref,$multi,$autosave_field="")
	{
	# Save all submitted data for resource $ref.
	# Also re-index all keywords from indexable fields.
	global $lang, $auto_order_checkbox, $userresourcedefaults, $multilingual_text_fields,
           $languages, $language, $user_resources_approved_email, $FIXED_LIST_FIELD_TYPES,
           $DATE_FIELD_TYPES, $date_validator, $range_separator, $reset_date_field, $reset_date_upload_template,
           $edit_contributed_by, $new_checksums, $upload_review_mode, $blank_edit_template, $is_template, $NODE_FIELDS,
           $userref;

	hook("befsaveresourcedata", "", array($ref));
    // Ability to avoid editing conflicts by checking checksums.
    // NOTE: this should NOT apply to upload.
    $check_edit_checksums = true;

    // Save resource defaults (functionality available for upload only)
    // Call it here so that if users have access to the field and want 
    // to override it, they can do so
    if(0 > $ref)
        {
        set_resource_defaults($ref);

        $check_edit_checksums = false;
        }

	# Loop through the field data and save (if necessary)
	$errors=array();
	$fields=get_resource_field_data($ref,$multi, !hook("customgetresourceperms"));    
	$expiry_field_edited=false;
    $resource_data=get_resource_data($ref);
    
    if($resource_data["lock_user"] > 0 && $resource_data["lock_user"] != $userref)
        {
        $errors[] = get_resource_lock_message($resource_data["lock_user"]);
        return $errors;
        }
		
	# Load the configuration for the selected resource type. Allows for alternative notification addresses, etc.
	resource_type_config_override($resource_data["resource_type"]);                
    
	# Set up arrays of node ids to add/remove. We can't remove all nodes as user may not have access
	$nodes_to_add    = array();
	$nodes_to_remove = array();   

    // All the nodes passed for editing. Some of them were already a value
    // of the fields while others have been added/removed
    $user_set_values = getval('nodes', array());
	
	
	// Initialise array to store new checksums that client needs after autosave, without which subsequent edits will fail
	$new_checksums = array();		
	
	for ($n=0;$n<count($fields);$n++)
		{
        if(!(
            checkperm('F' . $fields[$n]['ref'])
            || (checkperm("F*") && !checkperm('F-' . $fields[$n]['ref']))
            // If we hide on upload the field, there is no need to check values passed from the UI as there shouldn't be any
            || ((0 > $ref || $upload_review_mode) && $fields[$n]['hide_when_uploading'])
            )
            && ('' == $autosave_field || $autosave_field == $fields[$n]['ref']
                || (is_array($autosave_field) && in_array($fields[$n]['ref'], $autosave_field))
            )
		)
            {
            // Fixed list  fields use node IDs directly
            if(in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES))
                {
                debug("save_resource_data(): Checking nodes to add/ remove for field {$fields[$n]['ref']} - {$fields[$n]['title']}");

                $val = '';

                // Get currently selected nodes for this field 
                $current_field_nodes = get_resource_nodes($ref, $fields[$n]['ref']); 
				
				// Check if resource field data has been changed between form being loaded and submitted				
				$post_cs = getval("field_" . $fields[$n]['ref'] . "_checksum","");
				$current_cs = md5(implode(",",$current_field_nodes));				
				if($check_edit_checksums && $post_cs != "" && $post_cs != $current_cs)
					{
					$errors[$fields[$n]["ref"]] = i18n_get_translated($fields[$n]['title']) . ': ' . $lang["save-conflict-error"];
					continue;
					};
			
                debug("save_resource_data(): Current nodes for resource " . $ref . ": " . implode(",",$current_field_nodes));
                
				// Work out nodes submitted by user
                $ui_selected_node_values = array();
				
                if(isset($user_set_values[$fields[$n]['ref']])
                    && !is_array($user_set_values[$fields[$n]['ref']])
                    && '' != $user_set_values[$fields[$n]['ref']]
                    && is_numeric($user_set_values[$fields[$n]['ref']]))
                    {
                    $ui_selected_node_values[] = $user_set_values[$fields[$n]['ref']];
                    }
                else if(isset($user_set_values[$fields[$n]['ref']])
                    && is_array($user_set_values[$fields[$n]['ref']]))
                    {
                    $ui_selected_node_values = $user_set_values[$fields[$n]['ref']];
					}

                // Check nodes are valid for this field
                $fieldnodes   = get_nodes($fields[$n]['ref'], '', (FIELD_TYPE_CATEGORY_TREE == $fields[$n]['type']));
                $node_options = array_column($fieldnodes, 'name', 'ref');
                $validnodes   = array_column($fieldnodes, 'ref');

				$ui_selected_node_values=array_intersect($ui_selected_node_values,$validnodes);	
				natsort($ui_selected_node_values);
				
                $added_nodes = array_diff($ui_selected_node_values, $current_field_nodes);

                debug("save_resource_data(): Adding nodes to resource " . $ref . ": " . implode(",",$added_nodes));
                $nodes_to_add = array_merge($nodes_to_add, $added_nodes);
                $removed_nodes = array_diff($current_field_nodes,$ui_selected_node_values);    

                debug("save_resource_data(): Removed nodes from resource " . $ref . ": " . implode(",",$removed_nodes));           
                $nodes_to_remove = array_merge($nodes_to_remove, $removed_nodes);
								
				if(count($added_nodes)>0 || count($removed_nodes)>0)
					{  
                    # If this is a 'joined' field it still needs to add it to the resource column
                    $joins=get_resource_table_joins();
                    if (in_array($fields[$n]["ref"],$joins))
                        {
					    $new_nodevals = array();
                        // Build new value:
                        foreach($ui_selected_node_values as $ui_selected_node_value)
                            {
                            $new_nodevals[] = $node_options[$ui_selected_node_value];
                            }
                        $new_nodes_val = implode(",", $new_nodevals);
                        sql_query("update resource set field".$fields[$n]["ref"]."='".escape_check(truncate_join_field_value(strip_leading_comma($new_nodes_val)))."' where ref='$ref'");
                        }
					}

                // Required fields that didn't change get the current value
                if(1 == $fields[$n]['required'] && '' == $val)
                    {
                    // Build existing value:
                    foreach($current_field_nodes as $current_field_node)
                        {
                        $val .= ",{$node_options[$current_field_node]}";
                        }
                    }

                $new_checksums[$fields[$n]['ref']] = md5(implode(',', $ui_selected_node_values));
                }
			else
				{
				if($fields[$n]['type']==FIELD_TYPE_DATE_RANGE)
					{
					# date range type
					# each value will be a node so we end up with a pair of nodes to represent the start and end dates

					$daterangenodes=array();
					$newval="";
					
					if(($date_edtf=getvalescaped("field_" . $fields[$n]["ref"] . "_edtf",""))!=="")
						{
						// We have been passed the range in EDTF format, check it is in the correct format
						$rangeregex="/^(\d{4})(-\d{2})?(-\d{2})?\/(\d{4})(-\d{2})?(-\d{2})?/";
						if(!preg_match($rangeregex,$date_edtf,$matches))
							{
							$errors[$fields[$n]["ref"]] = $lang["information-regexp_fail"] . " : " . $date_edtf;
							continue;
							}
                        if(is_numeric($fields[$n]["linked_data_field"]))
                            {
                            // Update the linked field with the raw EDTF string submitted
                            update_field($ref,$fields[$n]["linked_data_field"],$date_edtf);
                            }
						$rangedates = explode("/",$date_edtf);
						$rangestart=str_pad($rangedates[0],  10, "-00");
						$rangeendparts=explode("-",$rangedates[1]);
                        $rangeendyear=$rangeendparts[0];
                        $rangeendmonth=isset($rangeendparts[1])?$rangeendparts[1]:12;
                        $rangeendday=isset($rangeendparts[2])?$rangeendparts[2]:cal_days_in_month(CAL_GREGORIAN, $rangeendmonth, $rangeendyear);
						$rangeend=$rangeendyear . "-" . $rangeendmonth . "-" . $rangeendday;
                        
						$newval = $rangestart . $range_separator . $rangeend;
						$daterangenodes[]=set_node(null, $fields[$n]["ref"], $rangestart, null, null,true);
						$daterangenodes[]=set_node(null, $fields[$n]["ref"], $rangeend, null, null,true);
						}
					else
						{
						// Range has been passed via normal inputs, construct the value from the date/time dropdowns
						$date_parts=array("_start_","_end_");
						
						foreach($date_parts as $date_part)
							{
							$val = getvalescaped("field_" . $fields[$n]["ref"] . $date_part . "year","");
							if (intval($val)<=0) 
								{
								$val="";
								}
							elseif (($field=getvalescaped("field_" . $fields[$n]["ref"] . $date_part . "month",""))!="") 
								{
								$val.="-" . $field;
								if (($field=getvalescaped("field_" . $fields[$n]["ref"] . $date_part . "day",""))!="") 
									{
									$val.="-" . $field;
									}
								 else 
									{
									$val.="-00";
									}
								}
							else 
								{
								$val.="-00-00";
								}
							$newval.= ($newval!=""?$range_separator:"") . $val;
							if($val!=="")
								{
								$daterangenodes[]=set_node(null, $fields[$n]["ref"], $val, null, null,true);
								}
							}
                        }

                        natsort($daterangenodes);
                        
                        // Get currently selected nodes for this field 
						$current_field_nodes = get_resource_nodes($ref, $fields[$n]['ref'], false, SORT_ASC);
                                            
						// Check if resource field data has been changed between form being loaded and submitted				
						$post_cs = getval("field_" . $fields[$n]['ref'] . "_checksum","");
						$current_cs = md5(implode(",",$current_field_nodes));						
						if($check_edit_checksums && $post_cs != "" && $post_cs != $current_cs)
							{
							$errors[$fields[$n]["ref"]] = i18n_get_translated($fields[$n]['title']) . ': ' . $lang["save-conflict-error"];
							continue;
							};
                        
                        if($daterangenodes !== $current_field_nodes)
                            {
                            $added_nodes = array_diff($daterangenodes, $current_field_nodes);
                            debug("save_resource_data(): Adding nodes to resource " . $ref . ": " . implode(",",$added_nodes));
                            $nodes_to_add = array_merge($nodes_to_add, $added_nodes);
                            $removed_nodes = array_diff($current_field_nodes,$daterangenodes);  
                            debug("save_resource_data(): Removed nodes from resource " . $ref . ": " . implode(",",$removed_nodes));           
                            $nodes_to_remove = array_merge($nodes_to_remove, $removed_nodes);
                            
                            $val = $newval;
                            # If this is a 'joined' field it still needs to be added to the resource column
                            $joins=get_resource_table_joins();
                            if (in_array($fields[$n]["ref"],$joins))
                                {
                                if(substr($val,0,1)==","){$val=substr($val,1);}
                                sql_query("update resource set field".$fields[$n]["ref"]."='".escape_check(truncate_join_field_value(substr($newval,1)))."' where ref='$ref'");
                                }
					        $new_checksums[$fields[$n]['ref']] = md5(implode(",",$daterangenodes));
                            }
                    }
                elseif(in_array($fields[$n]['type'], $DATE_FIELD_TYPES))
                    {
                    # date type, construct the value from the date/time dropdowns to be used in DB
                    $val=sanitize_date_field_input($fields[$n]["ref"], false);

                    if ($date_validator && $val != "")
                        {
                        # date type, construct the value from the date/time dropdowns to be used in date validator
                        $check_date_val=sanitize_date_field_input($fields[$n]["ref"], true);

                        $valid_date = str_replace("%field%", $fields[$n]['name'], check_date_format($check_date_val));
                        $valid_date = str_replace("%row% ", "", $valid_date);
                        if ($valid_date && !$valid_date == "") 
                            {
                            $errors[$fields[$n]["ref"]] = $valid_date;
                            continue;
                            }
                        }

                    // Upload template: always reset to today's date, if configured and field is hidden
                    if(0 > $ref 
                        && $reset_date_upload_template
                        && $reset_date_field == $fields[$n]['ref']
                        && $fields[$n]['hide_when_uploading']
                    )
                        {
                        $val = date('Y-m-d H:i');
                        }
					
					
					// Check if resource field data has been changed between form being loaded and submitted				
					$post_cs = getval("field_" . $fields[$n]['ref'] . "_checksum","");
					$current_cs = md5($fields[$n]['value']);			
					if($check_edit_checksums && $post_cs != "" && $post_cs != $current_cs)
						{
						$errors[$fields[$n]["ref"]] = i18n_get_translated($fields[$n]['title']) . ': ' . $lang["save-conflict-error"];
						continue;
						};
					
					$new_checksums[$fields[$n]['ref']] = md5($val);
                    }
				elseif ($multilingual_text_fields && ($fields[$n]["type"]==0 || $fields[$n]["type"]==1 || $fields[$n]["type"]==5))
					{
					# Construct a multilingual string from the submitted translations
					$val=getvalescaped("field_" . $fields[$n]["ref"],"");
					$rawval = getval("field_" . $fields[$n]["ref"],"");
					$val="~" . $language . ":" . $val;
					reset ($languages);
					foreach ($languages as $langkey => $langname)
						{
						if ($language!=$langkey)
							{
							$val.="~" . $langkey . ":" . getvalescaped("multilingual_" . $n . "_" . $langkey,"");
							}
						}
						
					// Check if resource field data has been changed between form being loaded and submitted				
					$post_cs = getval("field_" . $fields[$n]['ref'] . "_checksum","");
					$current_cs = md5(trim(preg_replace('/\s\s+/', ' ', $fields[$n]['value'])));
					if($check_edit_checksums && $post_cs != "" && $post_cs != $current_cs)
						{
						$errors[$fields[$n]["ref"]] = i18n_get_translated($fields[$n]['title']) . ': ' . $lang["save-conflict-error"];
						continue;
						};
					
					$new_checksums[$fields[$n]['ref']] = md5(trim(preg_replace('/\s\s+/', ' ', $rawval)));
					}
				else
					{
					# Set the value exactly as sent.
					$val=getvalescaped("field_" . $fields[$n]["ref"],"");
					$rawval = getval("field_" . $fields[$n]["ref"],"");
					// Check if resource field data has been changed between form being loaded and submitted				
					$post_cs = getval("field_" . $fields[$n]['ref'] . "_checksum","");
					$current_cs = md5(trim(preg_replace('/\s\s+/', ' ', $fields[$n]['value'])));
                    if($check_edit_checksums && $post_cs != "" && $post_cs != $current_cs)
						{
						$errors[$fields[$n]["ref"]] = i18n_get_translated($fields[$n]['title']) . ': ' . $lang["save-conflict-error"];
						continue;
						};
					$new_checksums[$fields[$n]['ref']] = md5(trim(preg_replace('/\s\s+/', ' ', $rawval)));
					} 
				# Check for regular expression match
				if (trim(strlen($fields[$n]["regexp_filter"]))>=1 && strlen($val)>0)
					{
					if(preg_match("#^" . $fields[$n]["regexp_filter"] . "$#",$val,$matches)<=0)
						{
						global $lang;
						debug($lang["information-regexp_fail"] . ": -" . "reg exp: " . $fields[$n]["regexp_filter"] . ". Value passed: " . $val);
						if (getval("autosave","")!="")
							{
							exit();
							}
						$errors[$fields[$n]["ref"]]=$lang["information-regexp_fail"] . " : " . $val;
						continue;
						}
					}
				$modified_val=hook("modifiedsavedfieldvalue",'',array($fields,$n,$val));
				if(!empty($modified_val))
					{
					$val=$modified_val;
					$new_checksums[$fields[$n]['ref']] = md5(trim(preg_replace('/\s\s+/', ' ', $val)));
					}
												
                $error = hook("additionalvalcheck", "all", array($fields, $fields[$n]));
                if($error)
                    {
                    $errors[$fields[$n]["ref"]]=$error;
                    continue;
                    }
				} // End of if not a fixed list (node) field

            if(
                $fields[$n]['required'] == 1
                && check_display_condition($n, $fields[$n], $fields, false)
                && (
                    // No nodes submitted
                    (in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES) && count($ui_selected_node_values) == 0)
                    // No value submitted
                    || (!in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES) && strip_leading_comma($val) == '')
                )
                && (
                    // Existing resource, but not in upload review mode with blank template and existing value (e.g. for resource default)
                    ($ref > 0 && !($upload_review_mode && $blank_edit_template && $fields[$n]['value'] != ''))
                    // Template with blank template and existing value
                    || ($ref < 0 && !($blank_edit_template && $fields[$n]["value"] !== ''))
                )
                // Not a metadata template
                && !$is_template
            )
                {
                # Register an error only if the required field was actually displayed
                if (is_field_displayed($fields[$n]))
                   {
                   $errors[$fields[$n]['ref']] = i18n_get_translated($fields[$n]['title']) . ": {$lang['requiredfield']}";
                   }
                continue;
                }

            // If all good so far, then save the data
			if(
                !in_array($fields[$n]['type'], $NODE_FIELDS)
                && str_replace("\r\n", "\n", $fields[$n]['value']) !== str_replace("\r\n", "\n", unescape($val))
            )
				{
				$oldval=$fields[$n]["value"];

				# This value is different from the value we have on record.

				# Write this edit to the log (including the diff) (unescaped is safe because the diff is processed later)
				resource_log($ref,LOG_CODE_EDITED,$fields[$n]["ref"],"",$fields[$n]["value"],unescape($val));

				# Expiry field? Set that expiry date(s) have changed so the expiry notification flag will be reset later in this function.
				if ($fields[$n]["type"]==FIELD_TYPE_EXPIRY_DATE) {$expiry_field_edited=true;}

				# If 'resource_column' is set, then we need to add this to a query to back-update
				# the related columns on the resource table
				$resource_column=$fields[$n]["resource_column"];	

				# Purge existing data and keyword mappings, decrease keyword hitcounts.
				sql_query("delete from resource_data where resource='$ref' and resource_type_field='" . $fields[$n]["ref"] . "'");
				
				# Insert new data and keyword mappings, increase keyword hitcounts.
				if(escape_check($val)!=='')
					{
					sql_query("insert into resource_data(resource,resource_type_field,value) values('$ref','" . $fields[$n]["ref"] . "','" . escape_check($val) ."')");
					}
				
				if ($fields[$n]["type"]==3 && substr($oldval,0,1) != ',')
					{
					# Prepend a comma when indexing dropdowns
					$oldval="," . $oldval;
					}
				
				if ($fields[$n]["keywords_index"]==1)
					{
					# Date field? These need indexing differently.
					$is_date=($fields[$n]["type"]==4 || $fields[$n]["type"]==6);

					$is_html=($fields[$n]["type"]==8);					
					
					remove_keyword_mappings($ref, i18n_get_indexable($oldval), $fields[$n]["ref"], $fields[$n]["partial_index"],$is_date,'','',$is_html);
					add_keyword_mappings($ref, i18n_get_indexable($val), $fields[$n]["ref"], $fields[$n]["partial_index"],$is_date,'','',$is_html);
					}
                else
                    {
                    // Remove all entries from resource_keyword for this field, useful if setting is changed and changed back leaving stale data
                    remove_all_keyword_mappings_for_field($ref,$fields[$n]["ref"]);
                    }
				
                # If this is a 'joined' field we need to add it to the resource column
                $joins=get_resource_table_joins();
                if (in_array($fields[$n]["ref"],$joins))
                    {
                    if(substr($val,0,1)==","){$val=substr($val,1);}
                    sql_query("update resource set field".$fields[$n]["ref"]."='".escape_check(truncate_join_field_value($val))."' where ref='$ref'");
                    }
                }
            # Add any onchange code
            if($fields[$n]["onchange_macro"]!="")
                {
                eval($fields[$n]["onchange_macro"]);
                }
			}
		}

    // When editing a resource, prevent applying the change to the resource if there are any errors
    if(count($errors) > 0 && $ref > 0)
        {
        return $errors;
        }
      
   # Save related resource field if value for Related input field is autosaved, or if form has been submitted by user
    if (($autosave_field=="" || $autosave_field=="Related") && isset($_POST["related"]))
        {
         # save related resources field
         sql_query("DELETE FROM resource_related WHERE resource='$ref' OR related='$ref'"); # remove existing related items
         $related=explode(",",getvalescaped("related",""));
         # Make sure all submitted values are numeric
         $to_relate = array_filter($related,"is_int_loose");
         if(count($to_relate)>0)
            {
            update_related_resource($ref,$to_relate,true);
            }
        }

    if ($autosave_field=="")
        {
        # Additional tasks when editing all fields (i.e. not autosaving)
        
        # Always index the resource ID as a keyword
        remove_keyword_mappings($ref, $ref, -1);
        add_keyword_mappings($ref, $ref, -1);
        
        # Also index the resource type name, unless disabled
        global $index_resource_type;
        if ($index_resource_type)
                {
                $restypename=sql_value("select name value from resource_type where ref in (select resource_type from resource where ref='" . escape_check($ref) . "')","", "schema");
                remove_all_keyword_mappings_for_field($ref,-2);
                add_keyword_mappings($ref,$restypename,-2);
                }
       }

    // Update resource_node table
    db_begin_transaction("update_resource_node");
    if(count($nodes_to_remove)>0)
        {
        delete_resource_nodes($ref,$nodes_to_remove, false);
        }

    if(count($nodes_to_add)>0)
        {
        add_resource_nodes($ref,$nodes_to_add, false, false);
        }
    
    log_node_changes($ref,$nodes_to_add,$nodes_to_remove);
    db_end_transaction("update_resource_node");

    // Autocomplete any blank fields without overwriting any existing metadata

    $autocomplete_fields = autocomplete_blank_fields($ref, false, true);
 
    foreach($autocomplete_fields as $ref => $value)
        {
        $new_checksums[$ref] = md5($value);
        }
        
    // Initialise an array of updates for the resource table
    $resource_update_sql = array();
    $resource_update_log_sql = array();
    if($edit_contributed_by)
            {
            $created_by = $resource_data['created_by'];
            $new_created_by = getvalescaped("created_by",0,true);
            if((getvalescaped("created_by",0,true) > 0) && $new_created_by != $created_by)
                {
                # Also update created_by
                $resource_update_sql[] = "created_by='" . $new_created_by . "'";
                $olduser=get_user($created_by);
                $newuser=get_user($new_created_by);
				$resource_update_log_sql[] = array("ref"=>$ref,"type"=>LOG_CODE_CREATED_BY_CHANGED,"field"=>0,"notes"=>"","from"=>$created_by . " (" . ($olduser["fullname"]=="" ? $olduser["username"] : $olduser["fullname"])  . ")","to"=>$new_created_by . " (" . ($newuser["fullname"]=="" ? $newuser["username"] : $newuser["fullname"])  . ")");
                }
            }
            
    # Expiry field(s) edited? Reset the notification flag so that warnings are sent again when the date is reached.
	$expirysql="";
	if ($expiry_field_edited)
        {
        $resource_update_sql[] = "expiry_notification_sent='0'";
        }
    
    if (!hook('forbidsavearchive', '', array($errors)))
		{
		# Also update archive status and access level
		$oldaccess=$resource_data['access'];
		$access=getvalescaped("access",$oldaccess,true);
        
		$oldarchive=$resource_data['archive'];
		$setarchivestate=getvalescaped("status",$oldarchive,true);
		if($setarchivestate!=$oldarchive && !checkperm("e" . $setarchivestate)) // don't allow change if user has no permission to change archive state
			{
			$setarchivestate=$oldarchive;
			}
			
        // Only if changed
        if(($autosave_field=="" || $autosave_field=="Status") && $setarchivestate != $oldarchive)
            {
            // Check if resource status has already been changed between form being loaded and submitted
            if(getval("status_checksum","") != "" && getval("status_checksum","") != $oldarchive)
                {
                $errors["status"] = $lang["status"] . ': ' . $lang["save-conflict-error"];
                }
            else
                {
                // update archive status if different (doesn't matter whether it is a user template or a genuine resource)
                if($setarchivestate != $oldarchive)
                    {
                    update_archive_status($ref,$setarchivestate,array($oldarchive));
                    }

				$new_checksums["status"] = $setarchivestate;
                }
			}
            
        if(($autosave_field=="" || $autosave_field=="Access") && $access != $oldaccess)
            {
            // Check if resource access has already been changed between form being loaded and submitted
            if(getval("access_checksum","") != "" && getval("access_checksum","") != $oldaccess)
                {
                $errors["access"] = $lang["access"] . ': ' . $lang["save-conflict-error"];
                }
            else
                {
                $resource_update_sql[] = "access = '" . escape_check($access) . "'";
                if($access != $oldaccess && 0 < $ref)
                    {
                    $resource_update_log_sql[] = array(
                        'ref'   => $ref,
                        'type'  => 'a',
                        'field' => 0,
                        'notes' => '',
                        'from'  => $oldaccess,
                        'to'    => $access);
                    }
    
                if ($oldaccess==3 && $access!=3)
                    {
                    # Moving out of the custom state. Delete any usergroup specific access.
                    # This can delete any 'manual' usergroup grants also as the user will have seen this as part of the custom access.
                    delete_resource_custom_access_usergroups($ref);
                    }
                
				$new_checksums["access"] = $access;
                }
			}
		}
        
    if(count($resource_update_sql)>0)
        {
        sql_query("update resource set " . implode(",",$resource_update_sql) . " where ref='$ref'");
        foreach($resource_update_log_sql as $log_sql)
            {
            resource_log($log_sql["ref"],$log_sql["type"],$log_sql["field"],$log_sql["notes"],$log_sql["from"],$log_sql["to"]);   
            }
        }
        
	# For access level 3 (custom) - also save custom permissions
	if (getvalescaped("access",0)==3) {save_resource_custom_access($ref);}

	
    // Plugins can do extra actions once all fields have been saved and return errors back if needed
    $plg_errors = hook('aftersaveresourcedata', '', array($ref, $nodes_to_add, $nodes_to_remove, $autosave_field, $fields));
    if(is_array($plg_errors) && !empty($plg_errors))
        {
        $errors = array_merge($errors, $plg_errors);
        }

	if (count($errors)==0) {daily_stat("Resource edit", $ref); return true;} else {return $errors;}
	}
	


/**
* Set resource defaults. Optional, a list of field IDs can be passed on to only update certain fields.
* IMPORTANT: this function will always set the resource defaults if any are found. The "client code" 
*            is where developers decide whether this should happen
* 
* @global string $userresourcedefaults  Resource defaults rules value based on user group a user belongs to
* 
* @param integer $ref             Resource ID
* @param array   $specific_fields Specific field ID(s) to update
* 
* @return boolean
*/
function set_resource_defaults($ref, array $specific_fields = array())
    {
    global $userresourcedefaults;

    if('' == $userresourcedefaults)
        {
        return false;
        }

    foreach(explode(';', $userresourcedefaults) as $rule)
        {
        $rule_detail         = explode('=', $rule);
        $field_shortname     = escape_check($rule_detail[0]);
        $field_default_value = $rule_detail[1];

        // Find field(s) - multiple fields can be returned to support several fields with the same name
        $fields = sql_array("SELECT ref AS `value` FROM resource_type_field WHERE name = '{$field_shortname}'", "schema");

        if(0 === count($fields))
            {
            continue;
            }

        // Sometimes we may want to set resource defaults only to specific fields so we ignore anything else
        if(0 < count($specific_fields))
            {
            $fields = array_intersect($fields, $specific_fields);
            }

        foreach($fields as $field_ref)
            {
            update_field($ref, $field_ref, $field_default_value);
            }
        }

    return true;
    }

function save_resource_data_multi($collection,$editsearch = array())
    {
    global $auto_order_checkbox,$auto_order_checkbox_case_insensitive,  $FIXED_LIST_FIELD_TYPES,$DATE_FIELD_TYPES,
    $range_separator, $edit_contributed_by, $TEXT_FIELD_TYPES, $userref, $lang;

    # Save all submitted data for collection $collection or a search result set, this is for the 'edit multiple resources' feature

    $errors = array();
    if($collection == 0 && isset($editsearch["search"]))
        {
        // Editing a result set, not a collection
        $edititems  = do_search($editsearch["search"],$editsearch["restypes"],'resourceid',$editsearch["archive"],-1,'ASC',false,0,false,false,'',false,false, true, true);
        $list       = array_column($edititems,"ref");
        }
    else
        {
        # Save all submitted data for collection $collection, 
        $list   = get_collection_resources($collection);
        }
    
    // Check that user can edit all resources, edit access and not locked by another user
    $noeditaccess = array();
    $lockedresources = array();
    foreach($list as $listresource)
        {
        $resource_data[$listresource]  = get_resource_data($listresource, true);
        if(!get_edit_access($listresource,$resource_data[$listresource]["archive"], $resource_data[$listresource]))
            {
            $noeditaccess[] = $listresource;
            }
        if($resource_data[$listresource]["lock_user"] > 0 && $resource_data[$listresource]["lock_user"] != $userref)
            {
            $lockedresources[] = $listresource;
            }
        }

    if(count($noeditaccess) > 0)
        {
        $errors[] = $lang["error-edit_noaccess_resources"] . implode(",",$noeditaccess);
        }
    if (count($lockedresources) > 0)
        {
        $errors[] = $lang["error-edit_locked_resources"] . implode(",",$lockedresources);            
        }

    if(count($errors) > 0)
        {
        return $errors;
        }

    $tmp    = hook("altercollist", "", array("save_resource_data_multi", $list));
    if(is_array($tmp))
        {
        if(count($tmp) > 0)
            {
            $list = $tmp;
            }
        else
            {
            return true;
            }
        }

	$ref                 = $list[0];
	$fields              = get_resource_field_data($ref,true);
	$expiry_field_edited = false;

    // All the nodes passed for editing. Some of them were already a value
    // of the fields while others have been added/ removed
    $user_set_values = getval('nodes', array());
    
    // set up arays to add to all resources to make query more efficient when only appending or removing options
    $all_nodes_to_add    = array();
    $all_nodes_to_remove = array();

    $successfully_edited_resources = array();

	for ($n=0;$n<count($fields);$n++)
		{
		if('' != getval('editthis_field_' . $fields[$n]['ref'], '') || hook('save_resource_data_multi_field_decision', '', array($fields[$n]['ref'])))
			{
			$nodes_to_add    = array();
            $nodes_to_remove    = array();
            if(in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES))
                {
                // Set up arrays of node ids selected and we will later resolve these to add/remove. Don't remove all nodes since user may not have access
                $ui_selected_node_values = array();
                if(isset($user_set_values[$fields[$n]['ref']])
                    && !is_array($user_set_values[$fields[$n]['ref']])
                    && '' != $user_set_values[$fields[$n]['ref']]
                    && is_numeric($user_set_values[$fields[$n]['ref']]))
                    {
                    $ui_selected_node_values[] = $user_set_values[$fields[$n]['ref']];
                    }
                else if(isset($user_set_values[$fields[$n]['ref']])
                    && is_array($user_set_values[$fields[$n]['ref']]))
                    {
                    $ui_selected_node_values = $user_set_values[$fields[$n]['ref']];
                    }

                // Check nodes are valid for this field
                $fieldnodes   = get_nodes($fields[$n]['ref'], '', (FIELD_TYPE_CATEGORY_TREE == $fields[$n]['type']));
                $node_options = array_column($fieldnodes, 'name', 'ref');
                $valid_nodes  = array_column($fieldnodes, 'ref');

                // Store selected/deselected values in array
				$ui_selected_node_values=array_intersect($ui_selected_node_values,$valid_nodes);   
				$ui_deselected_node_values = array_diff($valid_nodes, $ui_selected_node_values);

                // Append option(s) mode?
                if (getval("modeselect_" . $fields[$n]["ref"],"")=="AP")
                   {
                   $nodes_to_add = $ui_selected_node_values;
                   }
                elseif (getval("modeselect_" . $fields[$n]["ref"],"")=="RM")
                    {
                    // Remove option(s) mode
                    $nodes_to_remove = $ui_selected_node_values;                    
                    debug("Removing nodes: " .  implode(",",$nodes_to_remove));
                    }
                else
                    {
                    // Replace option(s) mode
                    $nodes_to_add  = $ui_selected_node_values;
                    $nodes_to_remove = $ui_deselected_node_values;
                    }

                $all_nodes_to_add    = array_merge($all_nodes_to_add,$nodes_to_add);                
                $all_nodes_to_remove = array_merge($all_nodes_to_remove,$nodes_to_remove);
                
                // Loop through all the resources and check current node values so we can check if we need to log this as a chsnge
                for ($m=0;$m<count($list);$m++)
                    {
                    $ref            = $list[$m];
                    $value_changed  = false;

                    $current_field_nodes = get_resource_nodes($ref, $fields[$n]['ref']);                    
                    debug('Current nodes: ' . implode(',',$current_field_nodes));

                    $added_nodes = array_diff($nodes_to_add,$current_field_nodes);
                    debug('Adding nodes: ' . implode(',',$added_nodes));

                    $removed_nodes = array_intersect($nodes_to_remove,$current_field_nodes);
                    debug('Removed nodes: ' . implode(',',$removed_nodes));
    
                    // Work out what new nodes for this resource  will be
                    $new_nodes = array_diff(array_merge($current_field_nodes, $added_nodes), $removed_nodes);      
                    debug('New nodes: ' . implode(',',$new_nodes));

                    if(count($added_nodes)>0 || count($removed_nodes)>0){$value_changed  = true;}
                    
                   	if($value_changed)
						{
						$existing_nodes_value = '';
						$new_nodes_val        = '';

                        $successfully_edited_resources[] = $ref;

						// Build new value:
						foreach($new_nodes as $new_node)
							{
							$new_nodes_val .= ",{$node_options[$new_node]}";
							}
						// Build existing value:
						foreach($current_field_nodes as $current_field_node)
							{
							$existing_nodes_value .= ",{$node_options[$current_field_node]}";
							}
                        $val = $new_nodes_val;

                        log_node_changes($ref,$new_nodes,$removed_nodes);

                        // If this is a 'joined' field it still needs to add it to the resource column
                        $joins = get_resource_table_joins();
                        if(in_array($fields[$n]['ref'], $joins))
                            {
                            if(',' == substr($val, 0, 1))
                                {
                                $val = substr($val, 1);
                                }

                            sql_query("UPDATE resource SET field{$fields[$n]['ref']} = '" . escape_check(truncate_join_field_value(substr($new_nodes_val, 1)))."' WHERE ref = '{$ref}'");
                            }
						}
                    }
                } // End of fixed list field section
			elseif($fields[$n]['type']==FIELD_TYPE_DATE_RANGE)
                {
                # date range type
                # each value will be a node so we end up with a pair of nodes to represent the start and end dates

                $daterangenodes=array();
                $newval="";
                
                if(($date_edtf=getvalescaped("field_" . $fields[$n]["ref"] . "_edtf",""))!=="")
                    {
                    // We have been passed the range in EDTF format, check it is in the correct format
                    $rangeregex="/^(\d{4})(-\d{2})?(-\d{2})?\/(\d{4})(-\d{2})?(-\d{2})?/";
                    if(!preg_match($rangeregex,$date_edtf,$matches))
                        {
                        $errors[$fields[$n]["ref"]]=$lang["information-regexp_fail"] . " : " . $val;
                        continue;
                        }
                    if(is_numeric($fields[$n]["linked_data_field"]))
                        {
                        // Update the linked field with the raw EDTF string submitted
                        update_field($ref,$fields[$n]["linked_data_field"],$date_edtf);
                        }
                    $rangedates = explode("/",$date_edtf);
                    $rangestart=str_pad($rangedates[0],  10, "-00");
                    $rangeendparts=explode("-",$rangedates[1]);
                    $rangeendyear=$rangeendparts[0];
                    $rangeendmonth=isset($rangeendparts[1])?$rangeendparts[1]:12;
                    $rangeendday=isset($rangeendparts[2])?$rangeendparts[2]:cal_days_in_month(CAL_GREGORIAN, $rangeendmonth, $rangeendyear);
                    $rangeend=$rangeendyear . "-" . $rangeendmonth . "-" . $rangeendday;
                    
                    $newval = $rangestart . $range_separator . $rangeend;
                    $daterangenodes[]=set_node(null, $fields[$n]["ref"], $rangestart, null, null,true);
                    $daterangenodes[]=set_node(null, $fields[$n]["ref"], $rangeend, null, null,true);
                    }
                else
                    {
                    // Range has been passed via normal inputs, construct the value from the date/time dropdowns
                    $date_parts=array("_start_","_end_");
                    
                    foreach($date_parts as $date_part)
                        {
                        $val = getvalescaped("field_" . $fields[$n]["ref"] . $date_part . "year","");
                        if (intval($val)<=0) 
                            {
                            $val="";
                            }
                        elseif (($field=getvalescaped("field_" . $fields[$n]["ref"] . $date_part . "month",""))!="") 
                            {
                            $val.="-" . $field;
                            if (($field=getvalescaped("field_" . $fields[$n]["ref"] . $date_part . "day",""))!="") 
                                {
                                $val.="-" . $field;
                                }
                                else 
                                {
                                $val.="-00";
                                }
                            }
                        else 
                            {
                            $val.="-00-00";
                            }
                        $newval.= ($newval!=""?$range_separator:"") . $val;if($val!=="")
                            {
                            $daterangenodes[]=set_node(null, $fields[$n]["ref"], $val, null, null,true);
                            }
                        }
                    }
                    // Get currently selected nodes for this field 
                    $current_field_nodes = get_resource_nodes($ref, $fields[$n]['ref']);
                    
                    $added_nodes = array_diff($daterangenodes, $current_field_nodes);
                    debug("save_resource_data(): Adding nodes to resource " . $ref . ": " . implode(",",$added_nodes));
                    $nodes_to_add = array_merge($nodes_to_add, $added_nodes);
                    
                    $removed_nodes = array_diff($current_field_nodes,$daterangenodes);  
                    debug("save_resource_data(): Removed nodes from resource " . $ref . ": " . implode(",",$removed_nodes));           
                    $nodes_to_remove = array_merge($nodes_to_remove, $removed_nodes);
                    
                    if(count($added_nodes)>0 || count($removed_nodes)>0)
                        {
                        // Log this change, nodes will actually be added later
                        log_node_changes($ref,$added_nodes,$removed_nodes);

                        foreach ($list as $key => $ref) 
                            {
                            $successfully_edited_resources[] = $ref;
                            }
                            
                        $val = $newval;
                        # If this is a 'joined' field it still needs to add it to the resource column
                        $joins=get_resource_table_joins();
                        if (in_array($fields[$n]["ref"],$joins))
                            {
                            if(substr($val,0,1)==","){$val=substr($val,1);}
                            sql_query("update resource set field".$fields[$n]["ref"]."='".escape_check(truncate_join_field_value(substr($newval,1)))."' where ref='$ref'");
                                }
                        }
                $all_nodes_to_add    = array_merge($all_nodes_to_add,$nodes_to_add);                
                $all_nodes_to_remove = array_merge($all_nodes_to_remove,$nodes_to_remove);
                }
            else
                {
                if(in_array($fields[$n]['type'], $DATE_FIELD_TYPES))
					{
                    # date/expiry date type, construct the value from the date dropdowns
                    $val=sprintf("%04d", getvalescaped("field_" . $fields[$n]["ref"] . "-y",""));
                    if ((int)$val<=0) 
                        {
                        $val="";
                        }
                    elseif (($field=getvalescaped("field_" . $fields[$n]["ref"] . "-m",""))!="") 
                        {
                        $val.="-" . $field;
                        if (($field=getvalescaped("field_" . $fields[$n]["ref"] . "-d",""))!="") 
                            {
                            $val.="-" . $field;
                            if (($field=getval("field_" . $fields[$n]["ref"] . "-h",""))!="")
                                {
                                $val.=" " . $field . ":";
                                if (($field=getvalescaped("field_" . $fields[$n]["ref"] . "-i",""))!="") 
                                    {
                                        $val.=$field;
                                    } 
                                else 
                                    {
                                        $val.="00";
                                    }
                                }
                            else 
                                {
                                $val.=" 00:00";
                                }
                            }
                        else 
                            {
                            $val.="-00 00:00";
                            }
                        }
                    else 
                        {
                        $val.="-00-00 00:00";
                        }
                    }
                else
                    {
                    $val=getvalescaped("field_" . $fields[$n]["ref"],"");
                    }
    
                $origval = $val;

                # Loop through all the resources and save.
                for ($m=0;$m<count($list);$m++)
                    {
                    $ref            = $list[$m];
                    $resource_sql   = '';
                    $value_changed  = false;  
                    if(
                        (
                            // Not applicable for global fields or archive only fields
                            !in_array($fields[$n]["resource_type"], array(0, 999))
                            && $resource_data[$ref]["resource_type"] != $fields[$n]["resource_type"]
                        )
                        || ($fields[$n]["resource_type"] == 999 && $resource_data[$ref]["archive"] != 2)
                    )
                        {
                        continue;
                        }

                    # Work out existing field value.
                    $existing = sql_value("SELECT `value` FROM resource_data WHERE resource = '".escape_check($ref)."' AND resource_type_field = '".escape_check($fields[$n]['ref'])."'", "");
                        
                    if (getval("modeselect_" . $fields[$n]["ref"],"")=="FR")
                        {
                        # Find and replace mode? Perform the find and replace.
                        
                        $findstring=getval("find_" . $fields[$n]["ref"],"");
                        $replacestring=getval("replace_" . $fields[$n]["ref"],"");
                        
                        $val=str_replace($findstring,$replacestring,$existing);
                                                
                        if (html_entity_decode($existing, ENT_QUOTES | ENT_HTML401) != $existing)
                            {
                            // Need to replace html characters with html characters
                            // CkEditor converts some characters to the HTML entity code, in order to use and replace these, we need the
                            // $rich_field_characters array below so the stored in the database value e.g. &#39; corresponds to "'"
                            // that the user typed in the search and replace box
                            // This array could possibly be expanded to include more such conversions
                            
                            $rich_field_characters_replace = array("'","’");
                            $rich_field_characters_sub = array("&#39;","&rsquo;");
                            
                            // Set up array of strings to match as we may have a number of variations in the existing value
                            $html_entity_strings = array();
                            $html_entity_strings[] = str_replace($rich_field_characters_replace, $rich_field_characters_sub, htmlspecialchars($findstring));
                            $html_entity_strings[] = str_replace($rich_field_characters_replace, $rich_field_characters_sub, htmlentities($findstring)); 
                            $html_entity_strings[] = htmlentities($findstring);
                            $html_entity_strings[] = htmlspecialchars($findstring);                            
                            
                            // Just need one replace string
                            $replacestring = htmlspecialchars($replacestring);
                                                        
                            $val=str_replace($html_entity_strings, $replacestring, $val);
                            }
                        }
                        
                    
                    # Append text/option(s) mode?
                    elseif (getval("modeselect_" . $fields[$n]["ref"],"")=="AP")
                        {
                        $val=append_field_value($fields[$n],$origval,$existing);
                        }                        
                        
                    # Prepend text/option(s) mode?
                    elseif (getval("modeselect_" . $fields[$n]["ref"],"")=="PP")
                        {
                        global $filename_field;
                        if ($fields[$n]["ref"]==$filename_field)
                            {
                            $val=rtrim($origval,"_")."_".trim($existing); // use an underscore if editing filename.
                            }
                        else {
                            # Automatically append a space when appending text types.
                            $val=$origval . " " . $existing;
                            }
                        }
                    elseif (getval("modeselect_" . $fields[$n]["ref"],"")=="RM")
                        {
                        # Remove text/option(s) mode
                        $val=str_replace($origval,"",$existing);
                        if($fields[$n]["required"] && strip_leading_comma($val)=="")
                            {
                            // Required field and  no value now set, revert to existing and add to array of failed edits
                            $val=$existing;
                            if(!isset($errors[$fields[$n]["ref"]]))
                                {$errors[$fields[$n]["ref"]]=$lang["requiredfield"] . ". " . $lang["error_batch_edit_resources"] . ": " ;}
                            $errors[$fields[$n]["ref"]] .=  $ref;
                            if($m<count($list)-1){$errors[$fields[$n]["ref"]] .= ",";}
                            
                            }
                        }
                    elseif (getval("modeselect_" . $fields[$n]["ref"],"")=="CF")
                        {
                        # Copy text from another text field
                        $copyfrom = getval("copy_from_field_" . $fields[$n]["ref"],0,true);
                        $copyfromfield = get_resource_type_field($copyfrom);
                        if(!in_array($fields[$n]["type"],$TEXT_FIELD_TYPES))
                            {
                            // Not a valid option for this field
                            debug("Copy data from field " . $copyfrom . " to field " . $fields[$n]["ref"] . " requires target field to be of a text type");
                            continue;    
                            }
                        $val = get_data_by_field($ref,$copyfrom);
                        if($fields[$n]["required"] && strip_leading_comma($val)=="")
                            {
                            // Required field and  no value now set, revert to existing and add to array of failed edits
                            $val=$existing;
                            if(!isset($errors[$fields[$n]["ref"]]))
                                {$errors[$fields[$n]["ref"]]=$lang["requiredfield"] . ". " . $lang["error_batch_edit_resources"] . ": " ;}
                            $errors[$fields[$n]["ref"]] .=  $ref;
                            if($m<count($list)-1){$errors[$fields[$n]["ref"]] .= ",";}                            
                            }
                        }
    
                    # Possibility to hook in and alter the value - additional mode support
                    $hookval = hook('save_resource_data_multi_extra_modes', '', array($ref, $fields[$n]));
                    if($hookval !== false)
                        {
                        $val = $hookval;
                        }                    
    
                    if ($val !== $existing || $value_changed)
                        {
                        # This value is different from the value we have on record.                        
                        # Write this edit to the log.
                        resource_log($ref,LOG_CODE_MULTI_EDITED,$fields[$n]["ref"],"",$existing,$val);
                        $successfully_edited_resources[] = $ref;

                        # Expiry field? Set that expiry date(s) have changed so the expiry notification flag will be reset later in this function.
                        if ($fields[$n]["type"]==6) {$expiry_field_edited=true;}
                    
                        # If this is a 'joined' field we need to add it to the resource column
                        $joins=get_resource_table_joins();
                        if (in_array($fields[$n]["ref"],$joins)){
                            sql_query("update resource set field".$fields[$n]["ref"]."='".escape_check(truncate_join_field_value($val))."' where ref='$ref'");
                        }		
                            
                        # Purge existing data and keyword mappings, decrease keyword hitcounts.
                        sql_query("delete from resource_data where resource='$ref' and resource_type_field='" . $fields[$n]["ref"] . "'");
                        
                        # Insert new data and keyword mappings, increase keyword hitcounts.
                        if(escape_check($val)!=='')
                            {
                            sql_query("insert into resource_data(resource,resource_type_field,value) values('$ref','" . $fields[$n]["ref"] . "','" . escape_check($val) . "')");
                            }
            
                        $oldval=$existing;
                        $newval=$val;
                        
                        if ($fields[$n]["keywords_index"]==1)
                            {
                            # Date field? These need indexing differently.
                            $is_date=($fields[$n]["type"]==4 || $fields[$n]["type"]==6); 
    
                            $is_html=($fields[$n]["type"]==8);
    
                            remove_keyword_mappings($ref,i18n_get_indexable($oldval),$fields[$n]["ref"],$fields[$n]["partial_index"],$is_date,'','',$is_html);
                            add_keyword_mappings($ref,i18n_get_indexable($newval),$fields[$n]["ref"],$fields[$n]["partial_index"],$is_date,'','',$is_html);
                            }

                        // If this is a 'joined' field we need to add it to the resource column
                        $joins = get_resource_table_joins();
                        if(in_array($fields[$n]['ref'], $joins))
                            {
                            if(',' == substr($val, 0, 1))
                                {
                                $val = substr($val, 1);
                                }

                            sql_query("UPDATE resource SET field{$fields[$n]['ref']} = '" . escape_check(truncate_join_field_value($val)) . "' WHERE ref = '{$ref}'");
                            }
                        
                        # Add any onchange code
                        if($fields[$n]["onchange_macro"]!="")
                            {
                            eval($fields[$n]["onchange_macro"]);    
                            }
                        }
                    }
                }  // End of non-node editing section            
			} // End of if edit this field
		} // End of foreach field loop

    // Add/remove nodes for all resources (we have already created log for this)
    if(count($all_nodes_to_add)>0)
        {
        add_resource_nodes_multi($list, $all_nodes_to_add, false);
        }
    if(count($all_nodes_to_remove)>0)
        {
        delete_resource_nodes_multi($list,$all_nodes_to_remove);   
        }

    // Also save related resources field
    if(getval("editthis_related","")!="")
        {
        $related = explode(',', getvalescaped('related', ''));

        // Make sure all submitted values are numeric
        $ok = array();
        for($n = 0; $n < count($related); $n++)
            {
            if(is_numeric(trim($related[$n])))
                {
                $ok[] = trim($related[$n]);
                }
            }

        // Clear out all relationships between related resources in this collection
        sql_query("
                DELETE rr
                  FROM resource_related AS rr
            INNER JOIN collection_resource AS cr ON rr.resource = cr.resource
                 WHERE cr.collection = '{$collection}'
        ");

        for($m = 0; $m < count($list); $m++)
            {
            $ref = $list[$m];

            if(0 < count($ok))
                {
                sql_query("INSERT INTO resource_related(resource, related) VALUES ($ref, " . join("),(" . $ref . ",",$ok) . ")");
                $successfully_edited_resources[] = $ref;
                }
            }
        }
	
	# Also update archive status
	global $user_resources_approved_email,$email_notify;	
	if (getval("editthis_status","")!="")
		{
		$notifyrefs=array();
		$usernotifyrefs=array();
		for ($m=0;$m<count($list);$m++)
			{
			$ref=$list[$m];                        
                        
            if (!hook('forbidsavearchive', '', array($errors)))
                {
                $oldarchive=sql_value("select archive value from resource where ref='$ref'","");
                $setarchivestate=getvalescaped("status",$oldarchive,true); // We used to get the 'archive' value but this conflicts with the archiveused for searching
                $successfully_edited_resources[] = $ref;

                $set_archive_state_hook = hook("save_resource_data_multi_set_archive_state", "", array($ref, $oldarchive));
                if($set_archive_state_hook !== false && is_numeric($set_archive_state_hook))
                    {
                    $setarchivestate = $set_archive_state_hook;
                    }

                if($setarchivestate!=$oldarchive && !checkperm("e" . $setarchivestate)) // don't allow change if user has no permission to change archive state
                    {
                    $setarchivestate=$oldarchive;
                    }
                    
                if ($setarchivestate!=$oldarchive) // Only if changed
                    {
                    update_archive_status($ref,$setarchivestate,array($oldarchive));
                    }
                }                                                			
			}
        }
        
	# Expiry field(s) edited? Reset the notification flag so that warnings are sent again when the date is reached.
	if ($expiry_field_edited)
		{
		if (count($list)>0)
			{
            $successfully_edited_resources[] = $ref;
			sql_query("update resource set expiry_notification_sent=0 where ref in (" . join(",",$list) . ")");
			}

        foreach ($list as $key => $ref) 
            {
            $successfully_edited_resources[] = $ref;
            }
		}
	
	# Also update access level
	if (getval("editthis_created_by","")!="" && $edit_contributed_by)
        {
        for ($m=0;$m<count($list);$m++)
			{
			$ref=$list[$m];
            $created_by = sql_value("select created_by value from resource where ref='$ref'",""); 
            $new_created_by = getvalescaped("created_by",0,true);
            if((getvalescaped("created_by",0,true) > 0) && $new_created_by != $created_by)
                {
                sql_query("update resource set created_by='" . $new_created_by . "'  where ref='$ref'"); 
                $olduser=get_user($created_by,true);
                $newuser=get_user($new_created_by,true);
                resource_log($ref,LOG_CODE_CREATED_BY_CHANGED,0,"",$created_by . " (" . ($olduser["fullname"]=="" ? $olduser["username"] : $olduser["fullname"])  . ")",$new_created_by . " (" . ($newuser["fullname"]=="" ? $newuser["username"] : $newuser["fullname"])  . ")");
                $successfully_edited_resources[] = $ref;
                }
            }
        }    

    # Also update access level
	if (getval("editthis_access","")!="")
		{
		for ($m=0;$m<count($list);$m++)
			{
			$ref=$list[$m];
			$access=getvalescaped("access",0);
			$oldaccess=sql_value("select access value from resource where ref='$ref'","");
			if ($access!=$oldaccess)
				{
				sql_query("update resource set access='$access' where ref='$ref'");				
                if ($oldaccess==3)
                    {
                    # Moving out of custom access - delete custom usergroup access.
                    delete_resource_custom_access_usergroups($ref);
                    }
				resource_log($ref,LOG_CODE_ACCESS_CHANGED,0,"",$oldaccess,$access);
                $successfully_edited_resources[] = $ref;
				}
			
			# For access level 3 (custom) - also save custom permissions
			if ($access==3) {save_resource_custom_access($ref);}
			}
		}
	
	# Update resource type?
	if (getval("editresourcetype","")!="")
		{
		for ($m=0;$m<count($list);$m++)
			{
			$ref=$list[$m];
			update_resource_type($ref,getvalescaped("resource_type",""));
            $successfully_edited_resources[] = $ref;
			}
		}
		
	# Update location?
	if (getval("editlocation","")!="")
		{
		$location=explode(",",getvalescaped("location",""));
		if (count($list)>0) 
			{
			if (count($location)==2)
				{
				$geo_lat=(float)$location[0];
				$geo_long=(float)$location[1];
				sql_query("update resource set geo_lat=$geo_lat,geo_long=$geo_long where ref in (" . join(",",$list) . ")");
				}
			elseif (getvalescaped("location","")=="")
				{
				sql_query("update resource set geo_lat=null,geo_long=null where ref in (" . join(",",$list) . ")");
				}

            foreach ($list as $key => $ref) 
                {
                $successfully_edited_resources[] = $ref;
                }
			}
		}

	# Update mapzoom?
	if (getval("editmapzoom","")!="")
		{
		$mapzoom=getvalescaped("mapzoom","");
		if (count($list)>0)
			{
			if ($mapzoom!="")
				{
				sql_query("update resource set mapzoom=$mapzoom where ref in (" . join(",",$list) . ")");
				}
			else
				{
				sql_query("update resource set mapzoom=null where ref in (" . join(",",$list) . ")");
				}

            foreach ($list as $key => $ref) 
                {
                $successfully_edited_resources[] = $ref;
                }
			}
		}

	hook("saveextraresourcedata","",array($list));

    // Plugins can do extra actions once all fields have been saved and return errors back if needed.
    // NOTE: Ensure the list of arguments is matching with aftersaveresourcedata hook in save_resource_data()
    $plg_errors = hook('aftersaveresourcedata', '', array($list, $all_nodes_to_add, $all_nodes_to_remove, '', array()));
    if(is_array($plg_errors) && !empty($plg_errors))
        {
        $errors = array_merge($errors, $plg_errors);
        }

    if(!empty($successfully_edited_resources))
        {
        $successfully_edited_resources = array_unique($successfully_edited_resources);

        foreach ($successfully_edited_resources as $key => $ref) 
            {
            daily_stat("Resource edit", $ref);
            }            
        }

    if (count($errors)==0) {return true;} else {return $errors;}
    
	}


function append_field_value($field_data,$new_value,$existing_value)
	{
	if ($field_data["type"]!=2 && $field_data["type"]!=3 && $field_data["type"]!=9 && $field_data["type"]!=12 && substr($new_value,0,1)!=",")
		{
		# Automatically append a space when appending text types.
		$val=$existing_value . " " . $new_value;
		}
	else
		{
		# Verify a comma exists at the beginning of the value
		if(substr($new_value,0,1)!=",")
			{
			$new_value=",".$new_value;
            }
		
		$val=(trim($existing_value)!=","?$existing_value:"") . $new_value;
		
		}
	return $val;
	}

function remove_keyword_mappings($ref,$string,$resource_type_field,$partial_index=false,$is_date=false,$optional_column='',$optional_value='',$is_html=false)
	{
	# Removes one instance of each keyword->resource mapping for each occurrence of that
	# keyword in $string.
	# This is used to remove keyword mappings when a field has changed.
	# We also decrease the hit count for each keyword.
	if (trim($string)=="") {return false;}
	$keywords=split_keywords($string,true,$partial_index,$is_date,$is_html);

	add_verbatim_keywords($keywords, $string, $resource_type_field);		// add in any verbatim keywords (found using regex).

	for ($n=0;$n<count($keywords);$n++)
		{
        unset ($kwpos);
		if (is_array($keywords[$n])){
			$kwpos=$keywords[$n]['position'];
			$keywords[$n]=$keywords[$n]['keyword'];
		}        
		$kw=$keywords[$n]; 
        if (!isset($kwpos)){$kwpos=$n;}
		remove_keyword_from_resource($ref,$keywords[$n],$resource_type_field,$optional_column='',$optional_value='',false, $kwpos);
		}	
	}


function remove_keyword_from_resource($ref,$keyword,$resource_type_field,$optional_column='',$optional_value='',$normalized=false, $position='')
    {
    if(!$normalized)
        {
		global $unnormalized_index;
        $kworig=$keyword;
        $keyword=normalize_keyword($keyword);
        if($keyword!=$kworig && $unnormalized_index)
			{
			// $keyword has been changed by normalizing, also remove the original value
			remove_keyword_from_resource($ref,$kworig,$resource_type_field,$optional_column='',$optional_value='',true);
			}
        }		
	
        $keyref=resolve_keyword($keyword,true, false);
	if ($optional_column<>'' && $optional_value<>'')	# Check if any optional column value passed and include this condition
		{
		sql_query("delete from resource_keyword where resource='$ref' and keyword='$keyref' and resource_type_field='$resource_type_field'" . (($position!="")?" and position='" . $position ."'":"") . " and $optional_column= $optional_value");
		}
	else{
		sql_query("delete from resource_keyword where resource='$ref' and keyword='$keyref' and resource_type_field='$resource_type_field'" . (($position!="")?" and position='" . $position ."'":""));
		}
	sql_query("update keyword set hit_count=hit_count-1 where ref='$keyref' limit 1");
			
    }



function add_keyword_mappings(int $ref,$string,$resource_type_field,$partial_index=false,$is_date=false,$optional_column='',$optional_value='',$is_html=false)
    {
    /* For each instance of a keyword in $string, add a keyword->resource mapping.
    * Create keywords that do not yet exist.
    * Increase the hit count of each keyword that matches.
    * Store the position and field the string was entered against for advanced searching.
    */
    if(trim($string) == '')
        {
        return false;
        }

    $keywords = split_keywords($string, true, $partial_index, $is_date, $is_html);
    add_verbatim_keywords($keywords, $string, $resource_type_field); // add in any verbatim keywords (found using regex).

    for($n = 0; $n < count($keywords); $n++)
        {
        unset($kwpos);
        if(is_array($keywords[$n]))
            {
            $kwpos        = $keywords[$n]['position'];
            $keywords[$n] = $keywords[$n]['keyword'];
            }

        $kw = $keywords[$n];
        if(!isset($kwpos))
            {
            $kwpos = $n;
            }

        add_keyword_to_resource($ref, $kw, $resource_type_field, $kwpos, $optional_column, $optional_value, false);
        }

    }


/**
 * Create a resource / keyword mapping
 *
 * @param  int      $ref                   ID of resource
 * @param  string   $keyword               Keyword to be added
 * @param  int      $resource_type_field   ID of resource type field
 * @param  int      $position
 * @param  string   $optional_column
 * @param  string   $optional_value
 * @param  boolean  $normalized            Normalize the keyword?
 * @param  boolean  $stemmed               Use stemming?
 * 
 * @return void
 */
function add_keyword_to_resource(int $ref,$keyword,$resource_type_field,$position,$optional_column='',$optional_value='',$normalized=false,$stemmed=false)
    {
    global $unnormalized_index,$stemming,$noadd,$use_mysqli_prepared;
    
    debug("add_keyword_to_resource: resource:" . $ref . ", keyword: " . $keyword);
    if(!$normalized)
        {
        $kworig=$keyword;
        $keyword=normalize_keyword($keyword);
        if($keyword!=$kworig && $unnormalized_index)
            {
            // $keyword has been changed by normalizing, also index the original value
            add_keyword_to_resource($ref,$kworig,$resource_type_field,$position,$optional_column,$optional_value,true,$stemmed);
            }
        }
        
    if (!$stemmed && $stemming && function_exists("GetStem"))
        {
        $kworig=$keyword;
        $keyword=GetStem($keyword);debug("Using stem " . $keyword . " for keyword " . $kworig);
        if($keyword!=$kworig)
            {
            // $keyword has been changed by stemming, also index the original value
            add_keyword_to_resource($ref,$kworig,$resource_type_field,$position,$optional_column,$optional_value,$normalized,true);
            }
        }
	
    if (!(in_array($keyword,$noadd)))
            {
            $keyref=resolve_keyword($keyword,true,false,false); // 3rd param set to false as already normalized. Do not stem this keyword as stem has already been added in this function
            debug("Indexing keyword $keyword - keyref is " . $keyref . ", already stemmed? is " . ($stemmed?"TRUE":"FALSE"));

            $stm_bind_data = array('iiii', $ref, $keyref, $position, $resource_type_field);
            $stm_prep_values = "?,?,?,?";

            $sql_extra_select = "";
            $sql_extra_value = "";
            if($optional_column != '' && $optional_value != '')
                {
                $sql_extra_select = ", `{$optional_column}`";
                $sql_extra_value = ", '" . escape_check($optional_value) . "'";

                $stm_prep_values .= ",?";
                $stm_bind_data[0] .= "s";
                $stm_bind_data[] = $optional_value;
                }

            # create mapping, increase hit count.
            if(isset($use_mysqli_prepared) && $use_mysqli_prepared)
                {
                sql_query_prepared("INSERT INTO `resource_keyword`(`resource`,`keyword`,`position`,`resource_type_field` {$sql_extra_select}) VALUES ($stm_prep_values)",
                    $stm_bind_data);
                }
            else
                {
                $ref = escape_check($ref);
                $keyref = escape_check($keyref);
                $position = escape_check($position);
                $resource_type_field = escape_check($resource_type_field);
                sql_query("INSERT INTO resource_keyword(resource, keyword, position, resource_type_field {$sql_extra_select})
                                VALUES ('$ref', '$keyref', '$position', '$resource_type_field' {$sql_extra_value})");
                }

            sql_query("update keyword set hit_count=hit_count+1 where ref='$keyref'");
            
            # Log this
            daily_stat("Keyword added to resource",$keyref);
            }  	
    }
    
/**
 * Remove all entries from resource_keyword for this field, useful if setting is changed and changed back leaving stale data
 *
 * @param  int  $resource              ID of resource
 * @param  int  $resource_type_field   ID of resource type field
 * 
 * @return void
 */
function remove_all_keyword_mappings_for_field($resource,$resource_type_field)
    {
    sql_query("delete from resource_keyword where resource='" . escape_check($resource) . "' and resource_type_field='" . escape_check($resource_type_field) . "'");
    }


/**
* Updates resource field. Works out the previous value, so this is
* not efficient if we already know what this previous value is (hence
* it is not used for edit where multiple fields are saved)
* 
* @param integer $resource Resource ID
* @param integer $field    Field ID
* @param string  $value    The new value
* @param array   &$errors  Any errors that may occur during update
* 
* @return boolean
*/
function update_field($resource, $field, $value, array &$errors = array(), $log=true)
    {
    global $FIXED_LIST_FIELD_TYPES, $NODE_FIELDS, $category_tree_add_parents, $username,$userref;
    
    $resource_data = get_resource_data($resource);
    if ($resource_data["lock_user"] > 0 && $resource_data["lock_user"] != $userref)
        {
        $errors[] = get_resource_lock_message($resource_data["lock_user"]);
        return false;
        }

    // accept shortnames in addition to field refs
    if(!is_numeric($field))
        {
        $field = sql_value("SELECT ref AS `value` FROM resource_type_field WHERE name = '" . escape_check($field) . "'", '', "schema");
        }

    // Fetch some information about the field
    $fieldinfo = sql_query("SELECT ref, keywords_index, resource_column, partial_index, type, onchange_macro FROM resource_type_field WHERE ref = '$field'", "schema");

    if(0 == count($fieldinfo))
        {
        $errors[] = "No field information about field ID '{$field}'";

        return false;
        }
    else
        {
        $fieldinfo = $fieldinfo[0];
        }

    $fieldoptions = get_nodes($field, null, ($fieldinfo['type'] == FIELD_TYPE_CATEGORY_TREE));
    $newvalues    = trim_array(explode(',', $value));

    // Set up arrays of node ids to add/remove. 
    if(in_array($fieldinfo['type'], $NODE_FIELDS))
        {
        $errors[] = "WARNING: Updates for fixed list fields should not use update_field. Use add_resource_nodes or add_resource_nodes_multi instead. Field: '{$field}'";
        $nodes_to_add    = array();
        $nodes_to_remove = array();
        }
        
    # If this is a date range field we need to add values to the field options
    if($fieldinfo['type'] == FIELD_TYPE_DATE_RANGE)
        {
        $newvalues = array_map('trim', explode('/', $value));
        $currentoptions = array();

        foreach($newvalues as $newvalue)
            {
            # Check if each new value exists in current options list
            if('' != $newvalue && !in_array($newvalue, $currentoptions))
                {
                # Append the option and update the field
                $newnode          = set_node(null, $field, escape_check(trim($newvalue)), null, null, true);
                $nodes_to_add[]   = $newnode;
                $currentoptions[] = trim($newvalue);

                debug("update_field: field option added: '" . trim($newvalue) . "'<br />");
                }
            }
        }
    elseif($fieldinfo['type'] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && !checkperm('bdk' . $field))
        {
        # If this is a dynamic keyword we need to add it to the field options
        $currentoptions = array();
        foreach($fieldoptions as $fieldoption)
            {
            $fieldoptiontranslations = explode('~', $fieldoption['name']);
            
            if(count($fieldoptiontranslations) < 2)
                {
                $currentoptions[]=trim($fieldoption['name']); # Not a translatable field
                //debug("update_field: current field option: '" . trim($fieldoption['name']) . "'<br />");
                }
            else
                {
                $default="";
                for ($n=1;$n<count($fieldoptiontranslations);$n++)
                    {
                    # Not a translated string, return as-is
                    if (substr($fieldoptiontranslations[$n],2,1)!=":" && substr($fieldoptiontranslations[$n],5,1)!=":" && substr($fieldoptiontranslations[$n],0,1)!=":")
                        {
                        $currentoptions[]=trim($fieldoption['name']);
                        debug("update_field: current field option: '" . $fieldoption['name'] . "'<br />");
                        }
                    else
                        {
                        # Support both 2 character and 5 character language codes (for example en, en-US).
                        $p=strpos($fieldoptiontranslations[$n],':');                         
                        $currentoptions[]=trim(substr($fieldoptiontranslations[$n],$p+1));
                        debug("update_field: current field option: '" . trim(substr($fieldoptiontranslations[$n],$p+1)) . "'<br />");
                        }
                    }
                }
            }

        foreach($newvalues as $newvalue)
            {
            # Check if each new value exists in current options list
            if('' != $newvalue && !in_array($newvalue, $currentoptions))
                {
                # Append the option and update the field
                $newnode          = set_node(null, $field, escape_check(trim($newvalue)), null, null, true);
                $nodes_to_add[]   = $newnode;
                $currentoptions[] = trim($newvalue);

                debug("update_field: field option added: '" . trim($newvalue) . "'<br />");
                }
            }
        }

    # Fetch previous value
    $existing = sql_value("select value from resource_data where resource='$resource' and resource_type_field='$field'","");

    if (in_array($fieldinfo['type'], $NODE_FIELDS))
        {
        $newvalues_translated = $newvalues;
        $newvalues = array();
        foreach($fieldoptions as $nodedata)
            {
            $translate_newvalues = array_walk(
                $newvalues_translated,
                function (&$value, $index)
                    {
                    $value = mb_strtolower(i18n_get_translated($value));
                    }
            );
            // Add to array of nodes, unless it has been added to array already as a parent for a previous node
            if (in_array(mb_strtolower(i18n_get_translated($nodedata["name"])), $newvalues_translated) && !in_array($nodedata["ref"], $nodes_to_add)) 
                {
                $nodes_to_add[] = $nodedata["ref"];
                // We need to add all parent nodes for category trees
                if($fieldinfo['type']==FIELD_TYPE_CATEGORY_TREE && $category_tree_add_parents) 
                    {
                    $parent_nodes=get_parent_nodes($nodedata["ref"]);
                    foreach($parent_nodes as $parent_node_ref=>$parent_node_name)
                        {
                        $nodes_to_add[]=$parent_node_ref;
                        if (!in_array(mb_strtolower(i18n_get_translated($parent_node_name)), $newvalues_translated))
                            {
                            $value = $parent_node_name . "," . $value; 
                            }
                        }
                    }
                }
            else
                {
                $nodes_to_remove[] = $nodedata["ref"];
                }
            // Assure no duplication added
            if(!in_array($value, $newvalues))
                {
                $newvalues[] = $value;
                }
            }

        $current_field_nodes = get_resource_nodes($resource,$field);
        $added_nodes = array_diff($nodes_to_add,$current_field_nodes);
        $removed_nodes = array_intersect($nodes_to_remove,$current_field_nodes);

        # Update resource_node table
        db_begin_transaction("update_field_{$field}");
        if(count($removed_nodes)>0)
            {
            delete_resource_nodes($resource,$nodes_to_remove, false);
            }

        if(count($added_nodes)>0)
            {
            add_resource_nodes($resource,$added_nodes, false, false);
            }
        if(count($added_nodes)>0 || count($removed_nodes)>0)
            {
            log_node_changes($resource,$added_nodes,$removed_nodes);
            }

        db_end_transaction("update_field_{$field}");
        $value = implode(",",$newvalues);
        }
    else
        {
        if ($fieldinfo["keywords_index"])
            {
            $is_html=($fieldinfo["type"]==8);	
            # If there's a previous value, remove the index for those keywords
            if (strlen($existing)>0)
                {
                remove_keyword_mappings($resource,i18n_get_indexable($existing),$field,$fieldinfo["partial_index"],false,'','',$is_html);
                }

            // Index the new value
            add_keyword_mappings($resource,i18n_get_indexable($value),$field,$fieldinfo["partial_index"],false,'','',$is_html);
            }

        # Delete the old value (if any) and add a new value.
        sql_query("delete from resource_data where resource='$resource' and resource_type_field='$field'");

        $value = escape_check($value);

        # write to resource_data if not an empty value
        if($value !== '')
            {
            sql_query("insert into resource_data(resource,resource_type_field,value) values ('$resource','$field','$value')");
            }
        }

    # If this is a 'joined' field we need to add it to the resource column
    $joins = get_resource_table_joins();

   if(in_array($fieldinfo['ref'],$joins))
		{
		if ($value!="null")
			{
			global $resource_field_column_limit;
			$truncated_value = truncate_join_field_value($value);
            // Remove backslashes from the end of the truncated value
            if(substr($truncated_value, -1) === '\\')
                {
                $truncated_value = substr($truncated_value, 0, strlen($truncated_value) - 1);
				}
			}
		else
			{
			$truncated_value="null";
			}		
		sql_query("update resource set field".$field."=" . (($value=="")?"NULL":"'" . escape_check($truncated_value) . "'") ." where ref='" . escape_check($resource) . "'");
		}			
	
    # Add any onchange code
    if($fieldinfo["onchange_macro"]!="")
        {
        eval($fieldinfo["onchange_macro"]);    
        }
    
    // Log this update
    if ($log && $value != $existing)
        {
        resource_log($resource,LOG_CODE_EDITED,$field,"",$existing,unescape($value));
        }
    
    # Allow plugins to perform additional actions.
    hook("update_field","",array($resource,$field,$value,$existing));
    return true;
    }

function email_resource($resource,$resourcename,$fromusername,$userlist,$message,$access=-1,$expires="",$useremail="",$from_name="",$cc="",$list_recipients=false, $open_internal_access=false, $useraccess=2,$group="")
	{
	# Attempt to resolve all users in the string $userlist to user references.

	global $baseurl,$email_from,$applicationname,$lang,$userref,$usergroup,$attach_user_smart_groups;
	
	if ($useremail==""){$useremail=$email_from;}
	if ($group=="") {$group=$usergroup;}
        
	# remove any line breaks that may have been entered
	$userlist=str_replace("\\r\\n",",",$userlist);

	if (trim($userlist)=="") {return ($lang["mustspecifyoneusername"]);}
	$userlist=resolve_userlist_groups($userlist);
	if($attach_user_smart_groups && strpos($userlist,$lang["groupsmart"] . ": ")!==false)
		{
		$userlist_with_groups=$userlist;
		$groups_users=resolve_userlist_groups_smart($userlist,true);
		if($groups_users!='')
			{
			if($userlist!="")
				{
				$userlist=remove_groups_smart_from_userlist($userlist);
				if($userlist!="")
					{
					$userlist.=",";
					}
				}
			$userlist.=$groups_users;
			}
		}
	
	$ulist=trim_array(explode(",",$userlist));
	$ulist=array_filter($ulist);
	$ulist=array_values($ulist);

	$emails=array();
	$key_required=array();

    $emails_keys = resolve_user_emails($ulist);

    if(0 === count($emails_keys))
        {
        return $lang['email_error_user_list_not_valid'];
        }

    $unames       = $emails_keys['unames'];
    $emails       = $emails_keys['emails'];
    $key_required = $emails_keys['key_required'];

	# Send an e-mail to each resolved user / e-mail address
	$subject="$applicationname: $resourcename";
	if ($fromusername==""){$fromusername=$applicationname;} // fromusername is used for describing the sender's name inside the email
	if ($from_name==""){$from_name=$applicationname;} // from_name is for the email headers, and needs to match the email address (app name or user name)
	
	$message=str_replace(array("\\n","\\r","\\"),array("\n","\r",""),$message);

#	Commented 'no message' line out as formatted oddly, and unnecessary.
#	if ($message==""){$message=$lang['nomessage'];}
	$resolve_open_access=false;
	
	for ($n=0;$n<count($emails);$n++)
		{
		$key="";
		# Do we need to add an external access key for this user (e-mail specified rather than username)?
		if ($key_required[$n])
			{
			$k=generate_resource_access_key($resource,$userref,$access,$expires,$emails[$n],$group);
			$key="&k=". $k;
			}
                elseif ($useraccess==0 && $open_internal_access && !$resolve_open_access)
                    {debug("smart_groups: going to resolve open access");
					# get this all done at once
					resolve_open_access((isset($userlist_with_groups)?$userlist_with_groups:$userlist),$resource,$expires);
					$resolve_open_access=true;
                    }
		
		# make vars available to template
		global $watermark;       
		$templatevars['thumbnail']=get_resource_path($resource,true,"thm",false,"jpg",$scramble=-1,$page=1,($watermark)?(($access==1)?true:false):false);
		if (!file_exists($templatevars['thumbnail'])){
			$resourcedata=get_resource_data($resource);
			$templatevars['thumbnail']="../gfx/".get_nopreview_icon($resourcedata["resource_type"],$resourcedata["file_extension"],false);
		}
		$templatevars['url']=$baseurl . "/?r=" . $resource . $key;
		$templatevars['fromusername']=$fromusername;
		$templatevars['message']=$message;
		$templatevars['resourcename']=$resourcename;
		$templatevars['from_name']=$from_name;
		if(isset($k)){
			if($expires==""){
				$templatevars['expires_date']=$lang["email_link_expires_never"];
				$templatevars['expires_days']=$lang["email_link_expires_never"];
			}
			else{
				$day_count=round((strtotime($expires)-strtotime('now'))/(60*60*24));
				$templatevars['expires_date']=$lang['email_link_expires_date'].nicedate($expires);
				$templatevars['expires_days']=$lang['email_link_expires_days'].$day_count;
				if($day_count>1){
					$templatevars['expires_days'].=" ".$lang['expire_days'].".";
				}
				else{
					$templatevars['expires_days'].=" ".$lang['expire_day'].".";
				}
			}
		}
		else{
			# Set empty expiration tempaltevars
			$templatevars['expires_date']='';
			$templatevars['expires_days']='';
		}
		
		# Build message and send.
		if (count($emails) > 1 && $list_recipients===true) {
			$body = $lang["list-recipients"] ."\n". implode("\n",$emails) ."\n\n";
			$templatevars['list-recipients']=$lang["list-recipients"] ."\n". implode("\n",$emails) ."\n\n";
		}
		else {
			$body = "";
		}
		$body.=$templatevars['fromusername']." ". $lang["hasemailedyouaresource"]."\n\n" . $templatevars['message']."\n\n" . $lang["clicktoviewresource"] . "\n\n" . $templatevars['url'];
		send_mail($emails[$n],$subject,$body,$fromusername,$useremail,"emailresource",$templatevars,$from_name,$cc);
		
		# log this
		resource_log($resource,LOG_CODE_EMAILED,"",$notes=$unames[$n]);
		
		}
	hook("additional_email_resource","",array($resource,$resourcename,$fromusername,$userlist,$message,$access,$expires,$useremail,$from_name,$cc,$templatevars));
	# Return an empty string (all OK).
	return "";
	}

function delete_resource($ref)
	{
    global $userref;
	# Delete the resource, all related entries in tables and all files on disk
	$ref      = escape_check($ref);
	$resource = get_resource_data($ref);
        
	if (!$resource
        ||
            (
                (
                checkperm("D")
                ||
                (isset($allow_resource_deletion) && !$allow_resource_deletion)
                ||
                !get_edit_access($ref,$resource["archive"], false,$resource)
                ||
                (isset($userref) && $resource["lock_user"] > 0 && $resource["lock_user"] != $userref)
                )
            &&
                !hook('check_single_delete')
            &&
                PHP_SAPI != 'cli'
            )
        )
        {return false;} 
	
	$current_state=$resource['archive'];
	
	global $resource_deletion_state, $staticsync_allow_syncdir_deletion, $storagedir;
	if (isset($resource_deletion_state) && $current_state!=$resource_deletion_state) # Really delete if already in the 'deleted' state.
		{
		# $resource_deletion_state is set. Do not delete this resource, instead move it to the specified state.
		update_archive_status($ref, $resource_deletion_state, $current_state);

        # log this so that administrator can tell who requested deletion
        resource_log($ref,LOG_CODE_DELETED,'');
		
		# Remove the resource from any collections
		sql_query("delete from collection_resource where resource='$ref'");
			
		return true;
		}
	
    # FStemplate support - do not allow samples from the template to be deleted
    if (resource_file_readonly($ref)) {return false;}
    
    	
	# Is transcoding
	if ($resource['is_transcoding']==1) {return false;} # Can't delete when transcoding

	# Delete files first
	$extensions = array();
	$extensions[]=$resource['file_extension']?$resource['file_extension']:"jpg";
	$extensions[]=$resource['preview_extension']?$resource['preview_extension']:"jpg";
	$extensions[]=$GLOBALS['ffmpeg_preview_extension'];
	$extensions[]='icc'; // also remove any extracted icc profiles
	$extensions=array_unique($extensions);
	
	foreach ($extensions as $extension)
		{
		$sizes=get_image_sizes($ref,true,$extension);
		foreach ($sizes as $size)
			{
			if (file_exists($size['path']) && ($staticsync_allow_syncdir_deletion || false !== strpos ($size['path'],$storagedir))) // Only delete if file is in filestore
				{
                $pathtofile = realpath($size['path']); // avoid passing relative path to unlink function to prevent error on removal of file.
                unlink($pathtofile);
                }
			}
		}
	
	# Delete any alternative files
	$alternatives=get_alternative_files($ref);
	for ($n=0;$n<count($alternatives);$n++)
		{
		delete_alternative_file($ref,$alternatives[$n]['ref']);
		}

	
	//attempt to remove directory
	$resource_path = get_resource_path($ref, true, "pre", true);

	$dirpath = dirname($resource_path);
	@rcRmdir ($dirpath); // try to delete directory, but if we do not have permission fail silently for now
    
	# Log the deletion of this resource for any collection it was in. 
	$in_collections=sql_query("select * from collection_resource where resource = '$ref'");
	if (count($in_collections)>0){
		for($n=0;$n<count($in_collections);$n++)
			{
			collection_log($in_collections[$n]['collection'],'d',$in_collections[$n]['resource']);
			}
		}

	hook("beforedeleteresourcefromdb","",array($ref));

	# Delete all database entries
    clear_resource_data($ref);
	sql_query("delete from resource where ref='$ref'");
    sql_query("delete from collection_resource where resource='$ref'");
    sql_query("delete from resource_custom_access where resource='$ref'");
    sql_query("delete from external_access_keys where resource='$ref'");
	sql_query("delete from resource_alt_files where resource='$ref'");
    sql_query(
        "    DELETE an
               FROM annotation_node AS an
         INNER JOIN annotation AS a ON a.ref = an.annotation
              WHERE a.resource = '{$ref}'"
    );
    sql_query("DELETE FROM annotation WHERE resource = '{$ref}'");
	hook("afterdeleteresource");
	
	return true;
	}
    

/**
* Returns field data from resource_type_field for the given field
* 
* @uses escape_check()
* @uses sql_query()
* 
* @param integer $field Resource type field ID
* 
* @return boolean|array
*/
function get_resource_type_field($field)
    {
    $field = escape_check($field);
    $rtf_query="SELECT ref,
                name,
                title,
                type,
                order_by,
                keywords_index,
                partial_index,
                resource_type,
                resource_column,
                display_field,
                use_for_similar,
                iptc_equiv,
                display_template,
                tab_name,
                required,
                smart_theme_name,
                exiftool_field,
                advanced_search,
                simple_search,
                help_text,
                display_as_dropdown,
                external_user_access,
                autocomplete_macro,
                hide_when_uploading,
                hide_when_restricted,
                value_filter,
                exiftool_filter,
                omit_when_copying,
                tooltip_text,
                regexp_filter,
                sync_field,
                display_condition,
                onchange_macro,
                field_constraint,
                linked_data_field,
                automatic_nodes_ordering,
                fits_field,
                personal_data,
                include_in_csv_export,
                browse_bar,
                active,
                full_width,
                read_only" . hook('add_resource_type_field_column') . "
           FROM resource_type_field
          WHERE ref = '{$field}'
    ";
    $modified_rtf_query=hook('modify_rtf_query','', array($field, $rtf_query));
    if($modified_rtf_query!==false){
        $rtf_query=$modified_rtf_query;
    }
    $return = sql_query($rtf_query, "schema");

    if(0 == count($return))
        {
        return false;
        }
    else
        {
        return $return[0];
        }
    }

/**
 * get_resource_field_data
 *
 * @param  int  $ref                Resource ID
 * @param  bool $multi              Get all fields? False by default (only fields that apply to the given resource type)
 * @param  bool $use_permissions    Honour user permissions e.g. field access. TRUE by default
 * @param  int  $originalref        Original resource ID to get data for. NULL by default
 * @param  bool $external_access    Only get data permitted to view externally. FALSE by default
 * @param  bool $ord_by             Use field order_by setting. FALSE by default (order is by resource type first)
 * @param  bool $forcsv             Get data for CSV export (uses \ separator for category tree nodes). FALSE by default
 * @return array|boolean
 */
function get_resource_field_data($ref,$multi=false,$use_permissions=true,$originalref=NULL,$external_access=false,$ord_by=false, $forcsv = false)
    {
    # Returns field data and field properties (resource_type_field and resource_data tables)
    # for this resource, for display in an edit / view form.
    # Standard field titles are translated using $lang.  Custom field titles are i18n translated.

    global $view_title_field;

    # Find the resource type.
    if (is_null($originalref)) {$originalref = $ref;} # When a template has been selected, only show fields for the type of the original resource ref, not the template (which shows fields for all types)
    $rtype = sql_value("select resource_type value FROM resource WHERE ref='" . escape_check($originalref) . "'",0);
    $rtype = ($rtype == "") ? 0 : $rtype;

    # If using metadata templates, 
    $templatesql = "";
    global $metadata_template_resource_type, $NODE_FIELDS;
    if (isset($metadata_template_resource_type) && $metadata_template_resource_type==$rtype) {
        # Show all resource fields, just as with editing multiple resources.
        $multi = true;
    }

    $return           = array();
    $order_by_sql     = ($ord_by ? 'order_by, resource_type, ref' : 'resource_type, order_by, ref');
    
    
    // Remove Category tree fields as these need special handling

    $node_fields_exclude = implode(',', $NODE_FIELDS);
    $node_fields    = array_diff($NODE_FIELDS,array(FIELD_TYPE_CATEGORY_TREE));
    $node_fields_list = implode(',', $node_fields);

    $fieldsSQL = "
             SELECT d.value,
                    f1.ref resource_type_field,
                    f1.*,
                    f1.required AS frequired,
                    f1.ref AS fref,
                    f1.field_constraint,
                    f1.automatic_nodes_ordering,
                    f1.personal_data,
                    f1.include_in_csv_export,
                    f1.full_width
               FROM resource_type_field AS f1
          LEFT JOIN resource_data d
                 ON d.resource_type_field = f1.ref AND d.resource = '" . escape_check($ref) . "'
              WHERE (
                            f1.active=1 and
                            f1.type NOT IN ({$node_fields_exclude})
                        AND (" . ($multi ? "1 = 1" : "f1.resource_type = 0 OR f1.resource_type = 999 OR f1.resource_type = '{$rtype}'") . ")
                    )

              UNION

             SELECT group_concat(if(rn.resource = '" . escape_check($ref) . "', n.name, NULL)) AS `value`,
                    f2.ref resource_type_field,
                    f2.*,
                    f2.required AS frequired,
                    f2.ref AS fref,
                    f2.field_constraint,
                    f2.automatic_nodes_ordering,
                    f2.personal_data,
                    f2.include_in_csv_export,
                    f2.full_width
               FROM resource_type_field AS f2
          LEFT JOIN node AS n ON n.resource_type_field = f2.ref
          LEFT JOIN resource_node AS rn ON rn.node = n.ref AND rn.resource = '" . escape_check($ref) . "'
              WHERE (
                            f2.active=1 and
                            f2.type IN ({$node_fields_list})
                        AND (" . ($multi ? "1 = 1" : "f2.resource_type = 0 OR f2.resource_type = 999 OR f2.resource_type = '{$rtype}'") . ")
                    )
           GROUP BY ref
           ORDER BY {$order_by_sql}
    ";

    if(!$ord_by)
        {
        debug('GENERAL/GET_RESOURCE_FIELD_DATA: use perms: ' . !$use_permissions);
        }

    $fields = sql_query($fieldsSQL);

    # Build an array of valid types and only return fields of this type. Translate field titles. 
    $validtypes = sql_array('SELECT ref AS `value` FROM resource_type','schema');

    # Support archive and global.
    $validtypes[] = 0;
    $validtypes[] = 999;

    // Add category tree values, reflecting tree structure
    $tree_resource_types = array('0',$rtype);
    if ($multi)
        {
        // All resource types checked as this is a metadata template.
        $tree_resource_types = $validtypes;
        }

    $tree_fields = get_resource_type_fields($tree_resource_types,"ref","asc",'',array(FIELD_TYPE_CATEGORY_TREE));
    foreach($tree_fields as $tree_field)
        {
        $addfield= $tree_field;

        $treenodes = get_resource_nodes($ref, $tree_field["ref"], true, SORT_ASC);
        $treetext_arr = get_tree_strings($treenodes);
        // Quoting each element is required for csv export
        $valstring = $forcsv ? ("\"" . implode("\",\"",$treetext_arr) . "\"") : implode(",",$treetext_arr);
        $addfield["value"] = count($treetext_arr) > 0 ? $valstring : "";
        $addfield["resource_type_field"] = $tree_field["ref"];
        $addfield["fref"] = $tree_field["ref"];
        $fields[] = $addfield;
        }
        
    if (empty($fields))
        {
        return false;
        }
    
    foreach($fields as $fkey => $field)
        {
        $fieldorder_by[$fkey]   = $field["order_by"]; 
        $fieldrestype[$fkey]    = $field["resource_type"]; 
        $fieldref[$fkey]        = $field["ref"]; 
        }
    if($ord_by)
        {
        array_multisort($fieldorder_by, SORT_ASC, $fieldrestype, SORT_ASC, $fieldref, SORT_ASC, $fields);
        }
    else
        {
        array_multisort($fieldrestype, SORT_ASC, $fieldorder_by, SORT_ASC, $fieldref, SORT_ASC, $fields);
        }

    // Resource types can be configured to not have global fields in which case we only present the user fields valid for
    // this resource type
    $inherit_global_fields = (bool) sql_value("SELECT inherit_global_fields AS `value` FROM resource_type WHERE ref = '{$rtype}'", true, "schema");
    if(!$inherit_global_fields && !$multi)
        {
        $validtypes = array($rtype);

        # Add title field even if $inherit_global_fields = false
        for ($n = 0; $n < count($fields); $n++)
            {
            if  (
                $fields[$n]['ref'] == $view_title_field  #Check field against $title_field for default title reference
                && 
                metadata_field_view_access($fields[$n]["fref"]) #Check permissions to access title field
            )
                {
                $return[] = $fields[$n];
                break;
                }
            }
        }

    for ($n = 0; $n < count($fields); $n++)
        {
        if  (
                (!$use_permissions
                || 
                ($ref<0 && checkperm("P" . $fields[$n]["fref"])) // Upload only edit access to this field
                ||
                (metadata_field_view_access($fields[$n]["fref"]) &&  !checkperm("T" . $fields[$n]["resource_type"]))
                )
            &&
                in_array($fields[$n]["resource_type"],$validtypes)
            &&
                (!($external_access && !$fields[$n]["external_user_access"]))
        )
            {    
            debug("field".$fields[$n]["title"]."=".$fields[$n]["value"]);
            $fields[$n]["title"] = lang_or_i18n_get_translated($fields[$n]["title"], "fieldtitle-"); 
            $return[] = $fields[$n];
            }
        }   
    # Remove duplicate fields e.g. $view_title_field included when $inherit_global_fields is false and also as a valid field. 
    $return = array_unique($return, SORT_REGULAR);
    
    # Return reindexed array
    $return = array_values($return);
    
    return $return;
    }

/**
 * get_resource_field_data_batch - Get all resource data for the given resources
 * 
 * Returns a multidimensional array with resource IDs as top level keys, then fields (order determined by $ord_by setting) 
 * IMPORTANT: This differs from get_resource_field_data() in that only fields containing data will be returned.
 * 
 * e.g. 
 * Array
 * (
 *     [119912] => array
 *         (
 *          [0] => Array
 *              (
 *              [resource] => 119912
 *              [value] => This is the title of resource 119912
 *              [resource_type_field] => 8
 *              [ref] => 8
 *              [name] => title
 *              [title] => Title
 *              [field_constraint] => 0
 *              [type] => 1))
 *              ....
 * 
 *
 * @param array $resources (either an array of resource ids or an array returned from search results)
 * @param  bool $use_permissions    Honour user permissions e.g. field access. TRUE by default
 * @param  bool $external_access    Only get data permitted to view externally. FALSE by default
 * @param  bool $ord_by             Use field order_by setting. FALSE by default (order is by resource type first)
 * @param  mixed $exportoptions     Array of options as below
 *                                   "csvexport" (bool) - get data for CSV export (uses \ separator for category tree nodes)
 *                                   "personal"  (bool) - include data in fields marked as personal
 *                                   "alldata"   (bool) - include data in all fields, including technical metadata   
 * 
 * @return array                    Array of resource data organised by resource then metadata field ID
 */
function get_resource_field_data_batch($resources,$use_permissions=true,$external_access=false,$ord_by=false, $exportoptions = array())
    {
    # Returns field data and field properties (resource_type_field and resource_data tables)
    # for all the resource references in the array $refs.
    # This will use a single SQL query and is therefore a much more efficient way of gathering
    # resource data for a list of resources (e.g. search result display for a page of resources).
    if (count($resources)==0) {return array();} # return an empty array if no resources specified (for empty result sets)

    global $view_title_field, $NODE_FIELDS;

    $csvexport = isset($exportoptions["csvexport"]) ? $exportoptions["csvexport"] : false;
    $personal = isset($exportoptions["personal"]) ? $exportoptions["personal"] : false;
    $alldata = isset($exportoptions["alldata"]) ? $exportoptions["alldata"] : false;

    $restype = array();
    if(isset($resources[0]["resource_type"]))
        {
        // This is an array of search results so we already have the resource types
        $restype = array_column($resources,"resource_type","ref");
        $resourceids = array_filter(array_column($resources,"ref"),function($v){return (string)(int)$v == (string)$v;});
        $refsin = implode("','",$resourceids);
        $getresources = $resources;
        }
    else
        {
        $resources = array_filter($resources,function($v){return (string)(int)$v == $v;});
        $resourceids = $resources;
        $refsin = implode("','",$resources);
        $allresourcedata = sql_query("SELECT ref, resource_type FROm resource WHERE ref IN ('" . $refsin . "') ");
        foreach($allresourcedata as $resourcedata)
            {
            $restype[$resourcedata["ref"]] = $resourcedata["resource_type"];
            }
        $getresources = array();
        foreach($resources as $resource)
            {
            $getresources[]["ref"] = $resource;
            }
        }

    $order_by_sql     = ($ord_by ? 'resource, order_by, resource_type, ref' : 'resource, resource_type, order_by, ref');    
    $node_fields_exclude = implode(',', $NODE_FIELDS);
    // Remove Category tree fields as these need special handling
    $node_fields    = array_diff($NODE_FIELDS,array(FIELD_TYPE_CATEGORY_TREE));
    $node_fields_list = implode(',', $node_fields);

    $fieldsSQL = "
             SELECT d.resource,
                    d.value,
                    f1.ref resource_type_field,
                    f1.*,
                    f1.required AS frequired,
                    f1.ref AS fref,
                    f1.field_constraint,
                    f1.automatic_nodes_ordering,
                    f1.personal_data,
                    f1.include_in_csv_export,
                    f1.full_width
               FROM resource_data d
          LEFT JOIN resource_type_field AS f1
                 ON d.resource_type_field = f1.ref
              WHERE d.resource IN ('" . $refsin . "')
                AND (
                        f1.active=1 and
                        f1.type NOT IN ({$node_fields_exclude})
                    )

              UNION

             SELECT rn.resource,
                    group_concat(if(n.name IS NOT NULL, n.name, NULL)) AS `value`,
                    f2.ref resource_type_field,
                    f2.*,
                    f2.required AS frequired,
                    f2.ref AS fref,
                    f2.field_constraint,
                    f2.automatic_nodes_ordering,
                    f2.personal_data,
                    f2.include_in_csv_export,
                    f2.full_width
               FROM resource_node rn
          LEFT JOIN node n ON n.ref=rn.node
          LEFT JOIN resource_type_field f2 ON f2.ref=n.resource_type_field
              WHERE rn.resource IN ('" . $refsin . "')
                AND (
                        f2.active=1 and
                        f2.type IN ({$node_fields_list})
                    )
           GROUP BY resource, ref
    ";

    $fields = sql_query($fieldsSQL);

    // Add category tree values, reflecting tree structure
    $tree_fields = get_resource_type_fields("","ref","asc",'',array(FIELD_TYPE_CATEGORY_TREE));
    $alltreenodes = get_resource_nodes_batch($resourceids, array_column($tree_fields,"ref"), true);
    foreach($tree_fields as $tree_field)
        {
        $addfield = $tree_field;
        foreach($getresources as $getresource)
            {
            if(isset($alltreenodes[$getresource["ref"]][$tree_field["ref"]]) && is_array($alltreenodes[$getresource["ref"]][$tree_field["ref"]]))
                {
                $treetext_arr = get_tree_strings($alltreenodes[$getresource["ref"]][$tree_field["ref"]]);
                // Quoting each element is required for csv export
                $valstring = $csvexport ? ("\"" . implode("\",\"",$treetext_arr) . "\"") : implode(",",$treetext_arr);
                }
            else
                {
                $treetext_arr = array();
                }
            $addfield["resource"] = $getresource["ref"];
            $addfield["value"] = count($treetext_arr) > 0 ? $valstring : "";
            $addfield["resource_type_field"] = $tree_field["ref"];
            $addfield["fref"] = $tree_field["ref"];
            $fields[] = $addfield;
            }
        }

    if (empty($fields))
        {
        return array();
        }
    // Convert to array with resource ID as index
    $res=0;
    $allresdata=array();
    $validtypes = array();
    # Support archive and global.
    $inherit_global_fields = array();
    for ($n=0;$n<count($fields);$n++)
        {
        $rowadded = false;
        if (!isset($allresdata[$fields[$n]["resource"]]))
            {
            $allresdata[$fields[$n]["resource"]]=array();
            }

        // Get valid field values for resource type
        if(!isset($validtypes[$fields[$n]["ref"]]))
            {
            $rtype = $restype[$fields[$n]["resource"]];
            $validtypes[$fields[$n]["ref"]] = array();
            $validtypes[$fields[$n]["ref"]][] = $rtype;
            $validtypes[$fields[$n]["ref"]][] = 999;

            // Resource types can be configured to not have global fields in which case we only present the user fields valid for
            // this resource type
            if(!isset($inherit_global_fields[$fields[$n]["ref"]]))
                {
                $inherit_global_fields[$fields[$n]["ref"]] = (bool) sql_value("SELECT inherit_global_fields AS `value` FROM resource_type WHERE ref = '{$rtype}'", true, "schema");
                }
            if($inherit_global_fields[$fields[$n]["ref"]])
                {
                $validtypes[$fields[$n]["ref"]][] = 0;
                }
            }

            // Add data to array
            if  (
                    (!$use_permissions
                    || 
                    ($fields[$n]["resource"]<0 && checkperm("P" . $fields[$n]["fref"])) // Upload only edit access to this field
                    ||
                    (metadata_field_view_access($fields[$n]["fref"]) &&  !checkperm("T" . $fields[$n]["resource_type"]))
                    )
                &&
                    in_array($fields[$n]["resource_type"],$validtypes[$fields[$n]["ref"]])
                &&
                    (!($external_access && !$fields[$n]["external_user_access"]))
                && 
                   (!$personal || $fields[$n]["personal_data"])
                && 
                   ($alldata || $fields[$n]["include_in_csv_export"])
                )
                {
                $fields[$n]["title"] = lang_or_i18n_get_translated($fields[$n]["title"], "fieldtitle-"); 
                $allresdata[$fields[$n]["resource"]][$fields[$n]["ref"]] = $fields[$n];
                $rowadded = true;
                }

        # Add title field even if $inherit_global_fields = false
        if  (!$rowadded && 
                $fields[$n]['ref'] == $view_title_field  #Check field against $title_field for default title reference
                && 
                metadata_field_view_access($fields[$n]["fref"]) #Check permissions to access title field
                )
            {
            $allresdata[$fields[$n]["resource"]][$fields[$n]["ref"]] = $fields[$n];
            }
        }
    $fields = array();
    foreach($allresdata as $resourceid => $resdata)
        {
        $fieldorder_by = array();
        $fieldrestype = array();
        $fieldref = array();
        foreach($resdata as $fkey => $field)
            {
            $fieldorder_by[$fkey]   = $field["order_by"]; 
            $fieldrestype[$fkey]    = $field["resource_type"]; 
            $fieldref[$fkey]        = $field["ref"];
            }
        if($ord_by)
            {
            array_multisort($fieldorder_by, SORT_ASC, $fieldrestype, SORT_ASC, $fieldref, SORT_ASC, $allresdata[$resourceid]);
            }
        else
            {
            array_multisort($fieldrestype, SORT_ASC, $fieldorder_by, SORT_ASC, $fieldref, SORT_ASC, $allresdata[$resourceid]);
            }
        }
    return $allresdata;
    }
    
function get_resource_types($types = "", $translate = true)
    {
    # Returns a list of resource types. The standard resource types are translated using $lang. Custom resource types are i18n translated.
    // support getting info for a comma-delimited list of restypes (as in a search)
    if ($types==""){$sql="";} else
        {
        # Ensure $types are suitably quoted and escaped
        $cleantypes="";
        $s=explode(",",$types);
        foreach ($s as $type)
            {
            if (is_numeric(str_replace("'","",$type))) # Process numeric types only, to avoid inclusion of collection-based filters (mycol, public, etc.)
                {
                if (strpos($type,"'")===false) {$type="'" . $type . "'";}
                if ($cleantypes!="") {$cleantypes.=",";}
                $cleantypes.=$type;
                }
            }
        $sql=" where ref in ($cleantypes) ";
        }
    
    $r=sql_query("select * from resource_type $sql order by order_by,ref","schema");
    $return=array();
    # Translate names (if $translate==true) and check permissions
    for ($n=0;$n<count($r);$n++)
        {
        if (!checkperm('T' . $r[$n]['ref']))
            {
            if ($translate==true) {$r[$n]["name"]=lang_or_i18n_get_translated($r[$n]["name"], "resourcetype-");} # Translate name
            $return[]=$r[$n]; # Add to return array
            }
        }
    return $return;
    }

function get_resource_top_keywords($resource,$count)
    {
    # Return the top $count keywords (by hitcount) used by $resource.
    # This section is for the 'Find Similar' search.
    # These are now derived from a join of node and resource_node for fixed keyword lists and resource_data for free text fields
    # Currently the date fields are not used for this feature
        
    $return=array();
    
    $keywords = sql_query("select distinct rd.value keyword,f.ref field,f.resource_type from resource_data rd,resource_type_field f where rd.resource='$resource' and f.ref=rd.resource_type_field and f.type in (0,1,5,8,13) and f.keywords_index=1 and f.use_for_similar=1 and length(rd.value)>0 limit $count");
    
    $fixed_dynamic_keywords = sql_query("select distinct n.ref, n.name, n.resource_type_field from node n inner join resource_node rn on n.ref=rn.node where (rn.resource='$resource' and n.resource_type_field in (select rtf.ref from resource_type_field rtf where use_for_similar=1) ) order by new_hit_count desc limit $count");
    
    $combined = array_merge($keywords,$fixed_dynamic_keywords);
    
    foreach ( $combined as $keyword )
        {
        # If isset($keyword['keyword']) this means that the value is coming free text in general    
        if ( isset($keyword['keyword']) )
            {
            # Apply permissions and strip out any results the user does not have access to.
            if (metadata_field_view_access($keyword["field"]) && !checkperm("T" . $keyword["resource_type"]))
                {
                $r =  $keyword["keyword"] ;
                }   
            }
            
        else
            {
            # In this case the keyword is coming from nodes
            # Apply permissions and strip out any results the user does not have access to.
            if (metadata_field_view_access($keyword["resource_type_field"]) && !checkperm("T" . $resource))
                {
                $r =  $keyword["name"] ;   
                }
            }

        if(isset($r) && trim($r) != '')
            {  
            if (substr($r,0,1)==","){$r=substr($r,1);}
            $s=split_keywords($r);
            # Splitting keywords can result in break words being included in these results
            # These should be removed here otherwise they will show as keywords themselves which is incorrect
            global $noadd; 
            foreach ($s as $a)
                {
                if(!empty($a) && !in_array($a,$noadd))
                    {
                    $return[]=$a;
                    }
                }
            }
        }   
            
    return $return;
    }


function clear_resource_data($resource)
    {
    # Clears stored data for a resource.
    sql_query("delete from resource_data where resource='$resource'");
	sql_query("delete from resource_dimensions where resource='$resource'");
	sql_query("delete from resource_keyword where resource='$resource'");
	sql_query("delete from resource_related where resource='$resource' or related='$resource'");
    delete_all_resource_nodes($resource); 
    
    // Clear all 'joined' fields
    $joins=get_resource_table_joins();
    if(count($joins) > 0)
        {
        $joins_sql = "";
        foreach ($joins as $join)
            {
            $joins_sql .= (($joins_sql!="")?",":"") . "field" . escape_check($join) . "=NULL";
            }
        sql_query("UPDATE resource SET $joins_sql WHERE ref='$resource'");
        }
        
    return true;
    }

function get_max_resource_ref()
	{
	# Returns the highest resource reference in use.
	return sql_value("select max(ref) value from resource",0);
	}

/**
 * Returns an array of resource references in the range $lower to $upper.
 *
 * @param  int  $lower    ID of resource, lower in range
 * @param  int  $higher   ID of resource, upper in range
 * 
 * @return array
 */
function get_resource_ref_range($lower,$higher)
	{
	return sql_array("select ref value from resource where ref>='$lower' and ref<='$higher' and archive=0 order by ref",0);
	}

/**
*  Create a new resource, copying all data from the resource with reference $from.
*  Note this copies only the data and not any attached file. It's very unlikely the
*  same file would be in the system twice, however users may want to clone an existing resource
*  to avoid reentering data if the resource is very similar.
*  If $resource_type if specified then the resource type for the new resource will be set to $resource_type
*  rather than simply copied from the $from resource.
*
* @param  int    $from            ID of resource
* @param  mixed  $resource_type   ID of resource type
* 
* @return boolean|integer
*/
function copy_resource($from,$resource_type=-1)
	{
    debug("copy_resource: copy_resource(\$from = {$from}, \$resource_type = {$resource_type})");
    global $userref;
    global $always_record_resource_creator, $upload_then_edit;
    
	# Check that the resource exists
	if (sql_value("select count(*) value from resource where ref='". escape_check($from) . "'",0)==0) {return false;}
	
	# copy joined fields to the resource column
	$joins=get_resource_table_joins();

	// Filter the joined columns so we only have the ones relevant to this resource type
	$query = sprintf('
			    SELECT rtf.ref AS value
			      FROM resource_type_field AS rtf
			INNER JOIN resource AS r ON (rtf.resource_type != r.resource_type AND rtf.resource_type != 0)
			     WHERE r.ref = "%s";
		',
		$from
	);
	$irrelevant_rtype_fields = sql_array($query);
	$irrelevant_rtype_fields = array_values(array_intersect($joins, $irrelevant_rtype_fields));
	$filtered_joins = array_values(array_diff($joins, $irrelevant_rtype_fields));

	$joins_sql="";
	foreach ($filtered_joins as $join){
		$joins_sql.=",field$join ";
	}
	
	$add="";
	$archive=sql_value("select archive value from resource where ref='". escape_check($from) . "'",0);
	
    if ($archive == "") // Needed if user does not have a user template 
        {
        $archive =0;
        }
    
    # Determine if the user has access to the source archive status
    if (!checkperm("e" . $archive))
		{
		# Find the right permission mode to use
		for ($n=-2;$n<3;$n++)
			{
			if (checkperm("e" . $n)) {$archive=$n;break;}
			}
		}
        
	# First copy the resources row
	sql_query("insert into resource($add resource_type,creation_date,rating,archive,access,created_by $joins_sql) select $add" . (($resource_type==-1)?"resource_type":("'" . $resource_type . "'")) . ",now(),rating,'" . $archive . "',access,created_by $joins_sql from resource where ref='" . escape_check($from) . "';");
	$to=sql_insert_id();
	
	# Set that this resource was created by this user. 
	# This needs to be done if either:
	# 1) The user does not have direct 'resource create' permissions and is therefore contributing using My Contributions directly into the active state
	# 2) The user is contributiting via My Contributions to the standard User Contributed pre-active states.
	if ((!checkperm("c")) || $archive<0 || (isset($always_record_resource_creator) && $always_record_resource_creator))
		{
		# Update the user record
		sql_query("update resource set created_by='$userref' where ref='$to'");

		# Also add the user's username and full name to the keywords index so the resource is searchable using this name.
		global $username,$userfullname;
		add_keyword_mappings($to,$username . " " . $userfullname,-1);
		}

	# Now copy all data
	copyResourceDataValues($from,$to,$resource_type);
	
    # Copy nodes
    copy_resource_nodes($from,$to);
	
	# Copy relationships
    copyRelatedResources($from, $to);

	# Copy access
	sql_query("insert into resource_custom_access(resource,usergroup,access) select '$to',usergroup,access from resource_custom_access where resource='". escape_check($from) . "'");

    // Set any resource defaults
    // Expected behaviour: set resource defaults only on upload and when
    // there is no edit access OR no existing value
    if(0 > $from || $upload_then_edit)
        {
        $fields_to_set_resource_defaults = array();
        $fields_data                     = get_resource_field_data($from, false, false);

        // Set resource defaults only to fields
        foreach($fields_data as $field_data)
            {
            if('' != trim($field_data['value']) && !($upload_then_edit && $from < 0))
                {
                continue;
                }

            $fields_to_set_resource_defaults[] = $field_data['ref'];
            }

        if(0 < count($fields_to_set_resource_defaults))
            {
            set_resource_defaults($to, $fields_to_set_resource_defaults);
            }
        }

	// Autocomplete any blank fields without overwriting any existing metadata
	autocomplete_blank_fields($to, false);

	# Reindex the resource so the resource_keyword entries are created
	reindex_resource($to);
	
	# Copying a resource of the 'pending review' state? Notify, if configured.
	global $send_collection_to_admin;
	if ($archive==-1 && !$send_collection_to_admin)
		{
		notify_user_contributed_submitted(array($to));
		}
	
	# Log this			
	daily_stat("Create resource",$to);
	resource_log($to,LOG_CODE_CREATED,0);

	hook("afternewresource", "", array($to));
	
	return $to;
	}
    

/**
 * Log resource activity
 *
 * 
 * @param   int     $resource - resource ref                            -- resource_log.resource
 * @param   string  $type - log code defined in include/definitions.php -- resource_log.type
 * @param   int     $field - resource type field                        -- resource_log.resource_type_field
 * @param   string  $notes - text notes                                 -- resource_log.notes
 * @param   string  $fromvalue - original value                         -- resource_log.previous_value
 * @param   string  $tovalue - new value
 * @param   int     $usage                                              -- resource_log.usageoption
 * @param   string  $purchase_size                                      -- resource_log.purchase_size
 * @param   float   $purchase_price                                     -- resource_log.purchase_price
 * 
 * @return int (or false)
 */

function resource_log($resource, $type, $field, $notes="", $fromvalue="", $tovalue="", $usage=-1, $purchase_size="", $purchase_price=0.00)
    {
    global $userref,$k,$lang,$resource_log_previous_ref, $internal_share_access;

    // Param type checks
    $param_str = array($type,$notes,$fromvalue,$tovalue,$purchase_size);
    $param_num = array($resource,$usage,$purchase_price);
 
    foreach($param_str as $par)
        {
        if (!is_string($par))
            {
            return false;
            } 
        }
 
    foreach($param_num as $par)
        {
        if (!is_numeric($par))
            {
            return false;
            } 
        }

    // check that $usage is valid value for int type db field
    // https://dev.mysql.com/doc/refman/8.0/en/integer-types.html
    $options_db_int = [ 'options' => [ 'min_range' => -2147483648,   'max_range' => 2147483647] ];
    if (!filter_var($usage, FILTER_VALIDATE_INT, $options_db_int) && $usage != 0)
        {
        return false;
        }
 
    // check that purchase_price is valid for decimal 10,2 field
    $options_db_purchase_price = [ 'options' => [  'regexp' => "/^[0-9]{0,10}\.?[0-9]{0,2}$/"   ]  ];
    if (filter_var($purchase_price, FILTER_VALIDATE_REGEXP, $options_db_purchase_price) == "")
        {
        return false;
        } 
        
    // check that purchase_size is valid for varchar(10) field
    $options_db_purchase_size = [ 'options' => [ 'regexp' => "/^[\w\W]{0,10}$/"]  ];
    if ($purchase_size != "" && filter_var($purchase_size, FILTER_VALIDATE_REGEXP, $options_db_purchase_size) == "" )
        {
        return false;
        } 
            
    // If it is worthy of logging, update the modified date in the resource table
    update_timestamp($resource);
    
    if(($resource === RESOURCE_LOG_APPEND_PREVIOUS && !isset($resource_log_previous_ref)) || ($resource !== RESOURCE_LOG_APPEND_PREVIOUS && $resource < 0))
        {
        return false;
        }

	if ($fromvalue===$tovalue)
		{
        $diff="";
		}
    else
        {
        switch ($type)
            {
            case LOG_CODE_STATUS_CHANGED:
                $diff = $lang["status" . $fromvalue] . " -> " . $lang["status" . $tovalue];
                break;

            case LOG_CODE_ACCESS_CHANGED:
                $diff = $lang["access" . $fromvalue] . " -> " . $lang["access" . $tovalue];
                break;

            // do not do a diff, just dump out whole new value (this is so we can cleanly append transform output)
            case LOG_CODE_TRANSFORMED:
            case LOG_CODE_NODE_REVERT:
            case LOG_CODE_EXTERNAL_UPLOAD:
            case LOG_CODE_CREATED:
                $diff = $tovalue;
                break;

            default:                
                $diff = log_diff($fromvalue, $tovalue);
            }
        }

    // Avoid out of memory errors such as when working with large PDF files
    if(mb_strlen($diff) > 10000)
        {
        $diff = mb_strcut($diff, 0, 10000);
        }

	$modifiedlogtype=hook("modifylogtype","",array($type));
	if ($modifiedlogtype)
        {
        $type = $modifiedlogtype;
        }
	
	$modifiedlognotes=hook("modifylognotes","",array($notes,$type,$resource));
	if($modifiedlognotes)
        {
        $notes = $modifiedlognotes;
        }

    if ($resource === RESOURCE_LOG_APPEND_PREVIOUS)
        {
        sql_query("UPDATE `resource_log` SET `diff`=left(concat(`diff`,'\n','" . escape_check($diff) . "'),60000) WHERE `ref`=" . $resource_log_previous_ref);
        return $resource_log_previous_ref;
        }
    else
        {
        sql_query("INSERT INTO `resource_log` (`date`, `user`, `resource`, `type`, `resource_type_field`, `notes`, `diff`, `usageoption`, `purchase_size`, " .
            "`purchase_price`, `access_key`, `previous_value`) VALUES (now()," .
            (($userref != "") ? "'" . escape_check($userref) . "'" : "null") . ",'" . escape_check($resource) . "','" . escape_check($type) . "'," . (($field=="" || !is_numeric($field)) ? "null" : "'" . escape_check($field) . "'") . ",'" . escape_check($notes) . "','" .
            escape_check($diff) . "','" . escape_check($usage) . "','" . escape_check($purchase_size) . "','" . escape_check($purchase_price) . "'," . ((isset($k) && !$internal_share_access) ? "'{$k}'" : "null") . ",'" . escape_check($fromvalue) . "')");
        $log_ref = sql_insert_id();
        $resource_log_previous_ref = $log_ref;
        return $log_ref;
        }
	}

/**
 * Get resource log records. The standard field titles are translated using $lang. Custom field titles are i18n translated.
 *
 * @param  int    $resource    Resource ID
 * @param  int    $fetchrows   If $fetchrows is set we don't have to loop through all the returned rows. @see sql_query()
 * @param  array  $filters     List of filters to include in the where clause. The key of the array is linked to the 
 *                             available columns in the sql statement so they must match!
 * 
 * @return array
 */
function get_resource_log($resource, $fetchrows = -1, array $filters = array())
    {
    // Logs can sometimes contain confidential information and the user 
    // looking at them must have admin permissions set
    if(!checkperm('v'))
        {
        return array();
        }

    $extrafields = hook('get_resource_log_extra_fields');
    if(!$extrafields)
        {
        $extrafields = '';
        }

    $sql_filters = "";
    foreach($filters as $column => $filter_value)
        {
        $sql_filters .= sprintf(" AND %s = '%s'",
            escape_check($column),
            escape_check($filter_value)
        );
        }
    $sql_filters = ltrim($sql_filters);

    $log = sql_query(
                "SELECT r.ref,
                        r.date,
                        u.username,
                        u.fullname,
                        r.type,
                        rtf.type AS resource_type_field,
                        f.title,
                        r.notes,
                        r.diff,
                        r.usageoption,
                        r.purchase_price,
                        r.purchase_size,
                        ps.name AS size,
                        r.access_key,
                        ekeys_u.fullname AS shared_by {$extrafields}
                   FROM resource_log AS r 
        LEFT OUTER JOIN user AS u ON u.ref = r.user
        LEFT OUTER JOIN resource_type_field AS f ON f.ref = r.resource_type_field
        LEFT OUTER JOIN external_access_keys AS ekeys ON r.access_key = ekeys.access_key AND r.resource = ekeys.resource
        LEFT OUTER JOIN user AS ekeys_u ON ekeys.user = ekeys_u.ref
              LEFT JOIN preview_size AS ps ON r.purchase_size = ps.id
        LEFT OUTER JOIN resource_type_field AS rtf ON r.resource_type_field = rtf.ref
                  WHERE r.resource = '{$resource}'
                        {$sql_filters}
               GROUP BY r.ref
               ORDER BY r.ref DESC",
        false,
        $fetchrows);

    for($n = 0; $n < count($log); $n++)
        {
        if($fetchrows != -1 && $log[$n] == 0)
            {
            continue;
            }

        $log[$n]['title'] = lang_or_i18n_get_translated($log[$n]['title'], 'fieldtitle-');
        }

    return $log;
    }

function get_resource_type_name($type)
	{
	global $lang;
	if ($type==999) {return $lang["archive"];}
	return lang_or_i18n_get_translated(sql_value("select name value from resource_type where ref='" . escape_check($type) . "'","", "schema"),"resourcetype-");
	}
	
function get_resource_custom_access($resource)
    {
    /*Return a list of usergroups with the custom access level for resource $resource (if set).
    The standard usergroup names are translated using $lang. Custom usergroup names are i18n translated.*/
    $sql = '';
    if(checkperm('E'))
        {
        // Restrict to this group and children groups only.
        global $usergroup, $usergroupparent;

        $sql = "WHERE g.parent = '{$usergroup}' OR g.ref = '{$usergroup}' OR g.ref = '{$usergroupparent}'";
        }

    $resource_custom_access = sql_query("
                   SELECT g.ref,
                          g.name,
                          g.permissions,
                          c.access
                     FROM usergroup AS g
          LEFT OUTER JOIN resource_custom_access AS c ON g.ref = c.usergroup AND c.resource = '{$resource}'
                     $sql
                 GROUP BY g.ref
                 ORDER BY (g.permissions LIKE '%v%') DESC, g.name
     ");

    for($n = 0; $n < count($resource_custom_access); $n++)
        {
        $resource_custom_access[$n]['name'] = lang_or_i18n_get_translated($resource_custom_access[$n]['name'], 'usergroup-');
        }

    return $resource_custom_access;
    }

function get_resource_custom_access_users_usergroups($resource)
    {
    # Returns only matching custom_access rows, with users and groups expanded
    return sql_query("
                 SELECT g.name usergroup,
                        u.username user,
                        c.access,
                        c.user_expires AS expires
                   FROM resource_custom_access AS c
        LEFT OUTER JOIN usergroup AS g ON g.ref = c.usergroup
        LEFT OUTER JOIN user AS u ON u.ref = c.user
                  WHERE c.resource = '{$resource}'
               ORDER BY g.name, u.username
    ");
    }
    
    
function save_resource_custom_access($resource)
	{
	$groups=get_resource_custom_access($resource);
	sql_query("delete from resource_custom_access where resource='$resource' and usergroup is not null");
	for ($n=0;$n<count($groups);$n++)
		{
		$usergroup=$groups[$n]["ref"];
		$access=getvalescaped("custom_" . $usergroup,0);
		sql_query("insert into resource_custom_access(resource,usergroup,access) values ('$resource','$usergroup','$access')");
		}
	}
	
function get_custom_access($resource,$usergroup,$return_default=true)
	{
	global $custom_access,$default_customaccess;
	if ($custom_access==false) {return 0;} # Custom access disabled? Always return 'open' access for resources marked as custom.

	$result=sql_value("select access value from resource_custom_access where resource='" . escape_check($resource) . "' and usergroup='$usergroup'",'');
	if($result=='' && $return_default)
		{
		return $default_customaccess;
		}
	return $result;
	}


/**
* Determine the featured collections and public collections a resource is associated with.
* 
* @param integer $ref Resource ref
* 
* @return array
*/
function get_themes_by_resource($ref)
    {
    global $lang;

    $sql = sprintf(
                "SELECT c.ref, c.`name`, c.`type`, u.fullname
                   FROM collection_resource AS cr
                   JOIN collection AS c ON cr.collection = c.ref AND cr.resource = '%s' AND c.`type` IN (%s)
        LEFT OUTER JOIN user AS u ON c.user = u.ref
                  %s # access control filter (ok if empty - it means we don't want permission checks or there's nothing to filter out)",
        escape_check($ref),
        COLLECTION_TYPE_FEATURED . ", " . COLLECTION_TYPE_PUBLIC,
        trim(featured_collections_permissions_filter_sql("WHERE", "c.ref"))
    );

    $results = sql_query($sql);
    $branch_path_fct = function($carry, $item) { return sprintf("%s / %s", $carry, strip_prefix_chars(i18n_get_translated($item["name"]),"*")); };

    foreach($results as $i => $col)
        {
        $path = sprintf("%s: %s", $lang["public"], i18n_get_translated($col["name"]));

        if($col["type"] == COLLECTION_TYPE_FEATURED)
            {
            $branch_path = get_featured_collection_category_branch_by_leaf($col["ref"], array());
            $branch_path_str = array_reduce($branch_path, $branch_path_fct, "");
            $path = mb_substr($branch_path_str, 2, mb_strlen($branch_path_str));
            }
        
        $results[$i]["path"] = trim($path);
        }

    // Order by resulting path
    usort($results, function($a, $b) { return strnatcasecmp($a["path"], $b["path"]); });

    return $results;
    }

function update_resource_type($ref,$type)
	{
    if (checkperm("XU" . $type))
        {
        return false;
        }
        
	sql_query("update resource set resource_type='$type' where ref='" . escape_check($ref) . "'");
	
	# Clear data that is no longer needed (data/keywords set for other types).
	sql_query("delete from resource_data where resource='" . escape_check($ref) . "' and resource_type_field not in (select ref from resource_type_field where resource_type='$type' or resource_type=999 or resource_type=0)");
	sql_query("delete from resource_keyword where resource='" . escape_check($ref) . "' and resource_type_field>0 and resource_type_field not in (select ref from resource_type_field where resource_type='$type' or resource_type=999 or resource_type=0)");
	sql_query("delete from resource_node where resource='" . escape_check($ref) . "' and node>0 and node not in (select n.ref from node n left join resource_type_field rf on n.resource_type_field=rf.ref where rf.resource_type='$type' or rf.resource_type=999 or resource_type=0)");	
    
    # Also index the resource type name, unless disabled
    global $index_resource_type;
    if ($index_resource_type)
            {
            $restypename=sql_value("select name value from resource_type where ref='" . escape_check($type) . "'","","schema");
            remove_all_keyword_mappings_for_field($ref,-2);
            add_keyword_mappings($ref,$restypename,-2);
            }
                
    return true;    	
	}
	
	

/**
* Returns a list of exiftool fields, which are basically fields with an 'exiftool field' set.
* 
* @param integer resource_type
* 
* @return array
*/
function get_exiftool_fields($resource_type)
    {
    $resource_type = escape_check($resource_type);

    return sql_query("
           SELECT f.ref,
                  f.type,
                  f.exiftool_field,
                  f.exiftool_filter,
                  group_concat(n.name) AS options,
                  f.name,
                  f.read_only
             FROM resource_type_field AS f
        LEFT JOIN node AS n ON f.ref = n.resource_type_field
            WHERE length(exiftool_field) > 0
              AND (resource_type = '$resource_type' OR resource_type = '0')
         GROUP BY f.ref
         ORDER BY exiftool_field", "schema");
    }

/**
* Create a temporary copy of the file in the tmp folder (ie. the usual filestore/tmp/)
* 
* @uses get_temp_dir()
* 
* @param  string  $path      File path
* @param  string  $uniqid    If a uniqid is provided, create a folder within tmp. See get_temp_dir() for more information.
* @param  string  $filename  Filename of the new file
* 
* @return boolean|string  Returns FALSE or the file path of the temporary file
*/
function createTempFile($path, $uniqid, $filename)
    {
    if(!file_exists($path) || !is_readable($path))
        {
        return false;
        }

    $tmp_dir = get_temp_dir(false, $uniqid);

    if(trim($filename) == '')
        {
        $file_path_info = pathinfo($path);
        $filename = md5(mt_rand()) . "_{$file_path_info['basename']}";
        }

    $tmpfile = "{$tmp_dir}/{$filename}";

    copy($path, $tmpfile);

    return $tmpfile;
    }

/**
* Strips metadata from file
* 
* @uses get_utility_path()
* @uses run_command()
* 
* @param string  $file_path  Physical path to file that will have metadata stripped. Use NULL to just get the exiftool
*                            command returned instead of running the command on the file
* 
* @return boolean|string  Returns TRUE or the Exiftool command for stripping metadata
*/
function stripMetadata($file_path)
    {
    debug_function_call('stripMetadata', func_get_args());
    $exiftool_fullpath = get_utility_path('exiftool');

    if($exiftool_fullpath === false)
        {
        trigger_error('stripMetadata function requires Exiftool utility!');
        }

    $command = "{$exiftool_fullpath} -m -overwrite_original -E -gps:all= -EXIF:all= -XMP:all= -IPTC:all=";

    if(is_null($file_path))
        {
        return $command;
        }

    if(!file_exists($file_path) || !is_writable($file_path))
        {
        return false;
        }

    $file_path = escapeshellarg($file_path);
    run_command("{$command} {$file_path}");

    return true;
    }

function write_metadata($path, $ref, $uniqid="")
	{
    debug_function_call('write_metadata', func_get_args());
	// copys the file to tmp and runs exiftool on it	
	// uniqid tells the tmp file to be placed in an isolated folder within tmp
	global $exiftool_remove_existing, $storagedir, $exiftool_write, $exiftool_write_option, $exiftool_no_process, $mysql_charset, $exiftool_write_omit_utf8_conversion;

    # Fetch file extension and resource type.
	$resource_data=get_resource_data($ref);
	$extension=$resource_data["file_extension"];
	$resource_type=$resource_data["resource_type"];

	$exiftool_fullpath = get_utility_path("exiftool");

    # Check if an attempt to write the metadata shall be performed.
	if(false != $exiftool_fullpath && $exiftool_write && $exiftool_write_option && !in_array($extension, $exiftool_no_process))
		{
        debug("[write_metadata()][ref={$ref}] Attempting to write metadata...");
        // Trust Exiftool's list of writable formats 
        $writable_formats = run_command("{$exiftool_fullpath} -listwf");
        $writable_formats = str_replace("\n", "", $writable_formats);
        $writable_formats_array = explode(" ", $writable_formats);
        if(!in_array(strtoupper($extension), $writable_formats_array))
            {
            debug("[write_metadata()][ref={$ref}] Extension '{$extension}' not in writable_formats_array - " . json_encode($writable_formats_array));
            return false;
            }

		$tmpfile = createTempFile($path, $uniqid, '');
		if($tmpfile === false)
            {
            debug("[write_metadata()][ref={$ref}] Unable to create temp file!");
            return false;
            }

        # Add the call to exiftool and some generic arguments to the command string.
        # Argument -overwrite_original: Now that we have already copied the original file, we can use exiftool's overwrite_original on the tmpfile.
        # Argument -E: Escape values for HTML. Used for handling foreign characters in shells not using UTF-8.
        # Arguments -EXIF:all= -XMP:all= -IPTC:all=: Remove the metadata in the tag groups EXIF, XMP and IPTC.
		$command = $exiftool_fullpath . " -m -overwrite_original -E ";
        if($exiftool_remove_existing)
            {
            $command = stripMetadata(null) . ' ';
            debug("[write_metadata()][ref={$ref}] Removing existing metadata. Command: ". json_encode($command));
            }

        $metadata_all=get_resource_field_data($ref, false,true,NULL,getval("k","")!=""); // Using get_resource_field_data means we honour field permissions
        $read_only_fields = array_column(array_filter($metadata_all, function($value) {
            return ((bool) $value['read_only'] == true);
        }), 'ref');

        $write_to=array();
        foreach($metadata_all as $metadata_item)
            {
            if(trim($metadata_item["exiftool_field"]) != "" && !in_array($metadata_item['ref'], $read_only_fields))
                {
                $write_to[] = $metadata_item;
                }
            }

        $writtenfields=array(); // Need to check if we are writing to an embedded field from more than one RS field, in which case subsequent values need to be appended, not replaced
           
        for($i = 0; $i<count($write_to); $i++) # Loop through all the found fields.
	    {
            $fieldtype = $write_to[$i]['type'];
            $writevalue = $write_to[$i]['value'];
            # Formatting and cleaning of the value to be written - depending on the RS field type.
            switch ($fieldtype)
                {
                case 2:
                case 3:
                case 9:
                case 12:
                    # Check box list, drop down, radio buttons or dynamic keyword list: remove initial comma if present
                    if (substr($writevalue, 0, 1)==",") {$writevalue = substr($writevalue, 1);}
                    break;                   
                case 4:
                case 6:
                case 10:
                    # Date / Expiry Date: write datetype fields in exiftool preferred format
                    if($writevalue!='')
                        {
                        $writevalue_to_time=strtotime($writevalue);
                        if($writevalue_to_time!='')
                            {
                            $writevalue = date("Y:m:d H:i:sP", strtotime($writevalue));
                            }
                        }				
                    break;
                    # Other types, already set
                }
            $filtervalue=hook("additionalmetadatafilter", "", Array($write_to[$i]["exiftool_field"], $writevalue));
            if ($filtervalue) $writevalue=$filtervalue;
            # Add the tag name(s) and the value to the command string.
            $group_tags = explode(",", $write_to[$i]['exiftool_field']); # Each 'exiftool field' may contain more than one tag.
            foreach ($group_tags as $group_tag)
                {                
                $group_tag = strtolower($group_tag); # E.g. IPTC:Keywords -> iptc:keywords
                if (strpos($group_tag,":")===false) {$tag = $group_tag;} # E.g. subject -> subject
                else {$tag = substr($group_tag, strpos($group_tag,":")+1);} # E.g. iptc:keywords -> keywords
                
                $exifappend=false; // Need to replace values by default
                if(isset($writtenfields[$group_tag])) 
                        { 
                        // This embedded field is already being updated, we need to append values from this field                          
                        $exifappend=true;
                        debug("write_metadata - more than one field mappped to the tag '" . $group_tag . "'. Enabling append mode for this tag. ");
                        }
                        
                switch ($tag)
                    {
                    case "filesize":
                        # Do nothing, no point to try to write the filesize.
                        break;
                    case "filename":
                        # Do nothing, no point to try to write the filename either as ResourceSpace controls this.
                        break;
                    case "directory":
                        # Do nothing, we don't want metadata to control this
                        break;
                    case "keywords":                  
                        # Keywords shall be written one at a time and not all together.
						if(!isset($writtenfields["keywords"])){$writtenfields["keywords"]="";} 
						$keywords = explode(",", $writevalue); # "keyword1,keyword2, keyword3" (with or without spaces)
						if (implode("", $keywords) != "")
                        	{
                        	# Only write non-empty keywords/ may be more than one field mapped to keywords so we don't want to overwrite with blank
	                        foreach ($keywords as $keyword)
	                            {
                                $keyword = trim($keyword);
	                            if ($keyword != "")
	                            	{
                                    debug("[write_metadata()][ref={$ref}] Writing keyword '{$keyword}'");
									$writtenfields[$group_tag].="," . $keyword;
										 
									# Convert the data to UTF-8 if not already.
									if (!$exiftool_write_omit_utf8_conversion && (!isset($mysql_charset) || (isset($mysql_charset) && strtolower($mysql_charset)!="utf8"))){$keyword = mb_convert_encoding($keyword, mb_detect_encoding($keyword), 'UTF-8');}
									$command.= escapeshellarg("-" . $group_tag . "-=" . htmlentities($keyword, ENT_QUOTES, "UTF-8")) . " "; // In case value is already embedded, need to manually remove it to prevent duplication
									$command.= escapeshellarg("-" . $group_tag . "+=" . htmlentities($keyword, ENT_QUOTES, "UTF-8")) . " ";
									}
	                            }
	                        }
                        break;
                    default:
                        if($exifappend && ($writevalue=="" || ($writevalue!="" && strpos($writtenfields[$group_tag],$writevalue)!==false)))
                            {                                                            
                            // The new value is blank or already included in what is being written, skip to next group tag
                            continue 2; # @see https://www.php.net/manual/en/control-structures.continue.php note
                            }                               
                        $writtenfields[$group_tag]=$writevalue;
                        debug("[write_metadata()][ref={$ref}] Updating tag '{$group_tag}' with value '{$writevalue}'");
                        # Write as is, convert the data to UTF-8 if not already.
                        
                        global $strip_rich_field_tags;
                        if (!$exiftool_write_omit_utf8_conversion && (!isset($mysql_charset) || (isset($mysql_charset) && strtolower($mysql_charset)!="utf8"))){$writevalue = mb_convert_encoding($writevalue, mb_detect_encoding($writevalue), 'UTF-8');}
                            if ($strip_rich_field_tags)
                            {
                                $command.= escapeshellarg("-" . $group_tag . "=" . trim(strip_tags(i18n_get_translated($writevalue,false)))) . " ";
                            }
                            else
                            {
                                $command.= escapeshellarg("-" . $group_tag . "=" . htmlentities(i18n_get_translated($writevalue), ENT_QUOTES, "UTF-8")) . " ";
                            }
                    }
                }
            }
            
            # Add the filename to the command string.
            $command.= " " . escapeshellarg($tmpfile);
            
            # Perform the actual writing - execute the command string.
            $output = run_command($command);
        return $tmpfile;
       }
    else
        {
        debug("[write_metadata()][ref={$ref}] Did not perform - write metadata!");
        return false;
        }
    }

/**
* Delete Exif temp file
*
* @param  string  $tmpfile   Exif temp file to be deleted
*
*/    
function delete_exif_tmpfile($tmpfile)
    {
    if(file_exists($tmpfile)){unlink ($tmpfile);}
    }

function update_resource($r, $path, $type, $title, $ingest=false, $createPreviews=true, $extension='',$after_upload_processing=false)
    {
    # Update the resource with the file at the given path
	# Note that the file will be used at it's present location and will not be copied.
    global $syncdir, $staticsync_prefer_embedded_title, $view_title_field, $filename_field, $upload_then_process, $offline_job_queue, $lang,
        $extracted_text_field, $offline_job_queue, $offline_job_in_progress, $autorotate_ingest, $enable_thumbnail_creation_on_upload,
        $userref, $lang, $upload_then_process_holding_state;

    if($upload_then_process && !$offline_job_queue)
        {
        $upload_then_process=false;
        }

	# Work out extension based on path
	if($extension=='')
		{
		$extension=pathinfo($path, PATHINFO_EXTENSION);
		}

    if($extension!=='')
    	{
    	$extension=trim(strtolower($extension));
		}

    if(!$upload_then_process || !$after_upload_processing)
        {
        update_resource_type($r, $type);

        # file_path should only really be set to indicate a staticsync location. Otherwise, it should just be left blank.
        if ($ingest){$file_path="";} else {$file_path=escape_check($path);}

        # Store extension/data in the database
        sql_query("update resource set archive=0,file_path='".$file_path."',file_extension='$extension',preview_extension='$extension',file_modified=now() where ref='$r'");

        # Store original filename in field, if set
        if (!$ingest)
            {
            # This file remains in situ; store the full path in file_path to indicate that the file is stored remotely.
            if (isset($filename_field))
                {

                $s=explode("/",$path);
                $filename=end($s);

                update_field($r,$filename_field,$filename);
                }
            }
        else
            {
            # This file is being ingested. Store only the filename.
            $s=explode("/",$path);
            $filename=end($s);

            if (isset($filename_field))
                {
                update_field($r,$filename_field,$filename);
                }

            # Move the file
            if(!hook('update_resource_replace_ingest','',array($r, $path, $extension)))
                {
                $destination=get_resource_path($r,true,"",true,$extension);
                $result=rename($syncdir . "/" . $path,$destination);
                if ($result===false)
                    {
                    # The rename failed. The file is possibly still being copied or uploaded and must be ignored on this pass.
                    # Delete the resouce just created and return false.
                    delete_resource($r);
                    return false;
                    }
                chmod($destination,0777);
                }
            }
        }

    if(!$upload_then_process || $after_upload_processing)
        {
	    # generate title and extract embedded metadata
	    # order depends on which title should be the default (embedded or generated)
	    if ($staticsync_prefer_embedded_title)
		    {
            if ($view_title_field!==$filename_field)
                {
                update_field($r,$view_title_field,$title);
                }
            extract_exif_comment($r,$extension);
            }
        else
            {
            extract_exif_comment($r,$extension);
            if ($view_title_field!==$filename_field)
                {
                update_field($r,$view_title_field,$title);
                }
            }
		
        # Extract text from documents (e.g. PDF, DOC)
        if (isset($extracted_text_field) && !(isset($unoconv_path) && in_array($extension,$unoconv_extensions))) 
            {
            if($offline_job_queue && !$offline_job_in_progress)
                {
                $extract_text_job_data = array(
                    'ref'       => $r,
                    'extension' => $extension,
                );

                job_queue_add('extract_text', $extract_text_job_data);
                }
            else
                {
                extract_text($r, $extension);
                }
            }
		
        # Ensure folder is created, then create previews.
        get_resource_path($r,false,"pre",true,$extension);

        if ($createPreviews)
            {
            # Attempt autorotation
            if($ingest && $autorotate_ingest){AutoRotateImage($destination);}

            # Generate previews/thumbnails (if configured i.e if not completed by offline process 'create_previews.php')
            if($enable_thumbnail_creation_on_upload)
                {
                create_previews($r, false, $extension, false, false, -1, false, $ingest);
                }
            else if(!$enable_thumbnail_creation_on_upload && $offline_job_queue)
                {
                $create_previews_job_data = array(
                    'resource' => $r,
                    'thumbonly' => false,
                    'extension' => $extension,
                    'previewonly' => false,
                    'previewbased' => false,
                    'alternative' => -1,
                    'ignoremaxsize' => false,
                    'ingested' => $ingest
                );
                $create_previews_job_success_text = str_replace('%RESOURCE', $r, $lang['jq_create_previews_success_text']);
                $create_previews_job_failure_text = str_replace('%RESOURCE', $r, $lang['jq_create_previews_failure_text']);

                job_queue_add('create_previews', $create_previews_job_data, '', '', $create_previews_job_success_text, $create_previews_job_failure_text);
                }
            }
        }
        
        if($upload_then_process && !$after_upload_processing)
            {
            # Add this to the job queue for offline processing            
            $job_data=array();
            $job_data["r"]=$r;
            $job_data["title"]=$title;
            $job_data["ingest"]=$ingest;
            $job_data["createPreviews"]=$createPreviews;
        
            if(isset($upload_then_process_holding_state))
                {
                $job_data["archive"]=sql_value("SELECT archive value from resource where ref={$ref}", "");
                update_archive_status($ref, $upload_then_process_holding_state);
                }
        
            $job_code=$r . md5($job_data["r"] . strtotime('now'));
            $job_success_lang="update_resource success " . str_replace(array('%ref', '%title'), array($r, $filename), $lang["ref-title"]);
            $job_failure_lang="update_resource fail " . ": " . str_replace(array('%ref', '%title'), array($r, $filename), $lang["ref-title"]);
            $jobadded=job_queue_add("update_resource", $job_data, $userref, '', $job_success_lang, $job_failure_lang, $job_code);             
            }
        
	hook('after_update_resource', '', array("resourceId" => $r ));
	# Pass back the newly created resource ID.
	return $r;
	}

function import_resource($path,$type,$title,$ingest=false,$createPreviews=true, $extension='')
	{
    global $syncdir;
    // Import the resource at the given path
    // This is used by staticsync.php and Camillo's SOAP API
    // Note that the file will be used at it's present location and will not be copied.

    $r=create_resource($type);
    // Log this in case the original location is not stored anywhere else
    resource_log(RESOURCE_LOG_APPEND_PREVIOUS,LOG_CODE_CREATED,'','','', $syncdir . DIRECTORY_SEPARATOR . $path);
    return update_resource($r, $path, $type, $title, $ingest, $createPreviews, $extension);
    }

function get_alternative_files($resource,$order_by="",$sort="",$type="")
	{
	# Returns a list of alternative files for the given resource
	if ($order_by!="" && $sort!=""){
		$ordersort=$order_by." ".$sort.",";
	} else {
		$ordersort="";
	}
    $extrasql=hook("get_alternative_files_extra_sql","",array($resource));
    
    # Filter by type, if provided.
    if ($type!="") {$extrasql.= " and alt_type='" . escape_check($type) . "'";}

	return sql_query("select ref,name,description,file_name,file_extension,file_size,creation_date,alt_type from resource_alt_files where resource='".escape_check($resource)."' $extrasql order by ".escape_check($ordersort)." name asc, file_size desc");
	}

/**
* Add alternative file
* 
* @param integer $resource
* @param string  $name
* @param string  $description
* @param string  $file_name
* @param string  $file_extension
* @param integer $file_size
* @param string  $alt_type
* 
* @return integer
*/
function add_alternative_file($resource,$name,$description="",$file_name="",$file_extension="",$file_size=0,$alt_type='')
	{
    debug_function_call("add_alternative_file", func_get_args());

    $name = trim_filename($name);
    $file_name = trim_filename($file_name);

	sql_query("insert into resource_alt_files(resource,name,creation_date,description,file_name,file_extension,file_size,alt_type) values ('" . escape_check($resource) . "','" . escape_check($name) . "',now(),'" . escape_check($description) . "','" . escape_check($file_name) . "','" . escape_check($file_extension) . "','" . escape_check($file_size) . "','" . escape_check($alt_type) . "')");
	return sql_insert_id();
	}
	
function delete_alternative_file($resource,$ref)
	{
	# Delete any uploaded file.
	$info=get_alternative_file($resource,$ref);
	$path=get_resource_path($resource, true, "", true, $info["file_extension"], -1, 1, false, "", $ref);
	if (file_exists($path)) {unlink($path);}
	
        // run through all possible extensions/sizes
	$extensions = array();
	$extensions[]=$info['file_extension']?$info['file_extension']:"jpg";
	$extensions[]=isset($info['preview_extension'])?$info['preview_extension']:"jpg";
	$extensions[]=$GLOBALS['ffmpeg_preview_extension'];
        $extensions[]='jpg'; // always look for jpegs, just in case
	$extensions[]='icc'; // always look for extracted icc profiles
	$extensions=array_unique($extensions);
        $sizes = sql_array('select id value from preview_size');
	
        // in some cases, a jpeg original is generated for non-jpeg files like PDFs. Delete if it exists.
        $path=get_resource_path($resource, true,'', true, 'jpg', -1, 1, false, "", $ref);
        if (file_exists($path)) {
            unlink($path);
        }

        // in some cases, a mp3 original is generated for non-mp3 files like WAVs. Delete if it exists.
        $path=get_resource_path($resource, true,'', true, 'mp3', -1, 1, false, "", $ref);
        if (file_exists($path)) {
            unlink($path);
        }

        foreach ($extensions as $extension){
            foreach ($sizes as $size){
                $page = 1;
                $lastpage = 0;
                while ($page <> $lastpage){
                    $lastpage = $page;
                    $path=get_resource_path($resource, true, $size, true, $extension, -1, $page, false, "", $ref);
                    if (file_exists($path)) {
                        unlink($path);
                        $page++;
                    }
                }
            }
        }
        
	# Delete the database row
	sql_query("delete from resource_alt_files where resource='" . escape_check($resource) . "' and ref='" . escape_check($ref) . "'");
	
	# Log the deletion
	resource_log($resource,LOG_CODE_DELETED_ALTERNATIVE,'');
	
	# Update disk usage
	update_disk_usage($resource);
	}
	
function get_alternative_file($resource,$ref)
	{
    $resource = escape_check($resource);
    $ref = escape_check($ref);
	# Returns the row for the requested alternative file
	$return=sql_query("select ref,name,description,file_name,file_extension,file_size,creation_date,alt_type from resource_alt_files where resource='$resource' and ref='$ref'");
	if (count($return)==0) {return false;} else {return $return[0];}
	}
	
function save_alternative_file($resource,$ref)
	{
	# Saves the 'alternative file' edit form back to the database
	$sql="";
	
	# Save data back to the database.
	sql_query("update resource_alt_files set name='" . getvalescaped("name","") . "',description='" . getvalescaped("description","") . "',alt_type='" . getvalescaped("alt_type","") . "' $sql where resource='$resource' and ref='$ref'");
    	}
	
function user_rating_save($userref,$ref,$rating)
	{
	# Save a user rating for a given resource
	$resource=get_resource_data($ref);
	
	# Recalculate the averate rating
	$total=$resource["user_rating_total"]; if ($total=="") {$total=0;}
	$count=$resource["user_rating_count"]; if ($count=="") {$count=0;}
	
	# modify behavior to allow only one current rating per user (which can be re-edited)
	global $user_rating_only_once;
	if ($user_rating_only_once){
		$ratings=array();
		$ratings=sql_query("select user,rating from user_rating where ref='$ref'");
		
		#Calculate ratings total and get current rating for user if available
		$total=0;
		$current="";
		for ($n=0;$n<count($ratings);$n++){
			$total+=$ratings[$n]['rating'];
			
			if ($ratings[$n]['user']==$userref){
				$current=$ratings[$n]['rating'];
				}
			}
		# Calculate Count
		$count=count($ratings);
		
		# if user has a current rating, subtract the old rating and add the new one.
		if ($current!=""){
			$total=$total-$current+$rating;
			if ($rating == 0) {  //rating remove feature
				sql_query("delete from user_rating where user='$userref' and ref='$ref'");
				$count--;
			} else {
				sql_query("update user_rating set rating='$rating' where user='$userref' and ref='$ref'");
			}
		}
		
		# if user does not have a current rating, add it 
		else {
			if ($rating != 0) {  //rating remove feature
				$total=$total+$rating;
				$count++;
				sql_query("insert into user_rating (user,ref,rating) values ('$userref','$ref','$rating')");
			}
		}

	}	
	else {
		# If not using $user_rating_only_once, Increment the total and count 
		$total+=$rating;
		$count++;
	}
	
	if ($count==0){
		# avoid division by zero
		$average=$total;
	} else {
	# work out a new average.
	$average=ceil($total/$count);
	}	
	
	# Save to the database
	sql_query("update resource set user_rating='$average',user_rating_total='$total',user_rating_count='$count' where ref='$ref'");
		
	}


/**
 * Get contributed by user formatted for inclusion in notifications
 *
 * @param  int     $ref         ID of resource
 * @param  string  $htmlbreak   HTML break type
 * 
 * @return string
 */
function process_notify_user_contributed_submitted($ref,$htmlbreak)
	{
	global $use_phpmailer,$baseurl, $lang;
	$url="";
	$url=$baseurl . "/?r=" . $ref;
	
	if ($use_phpmailer){$url="<a href'$url'>$url</a>";}
	
	// Get the user (or username) of the contributor:
	$query = "SELECT user.username, user.fullname FROM resource INNER JOIN user ON user.ref = resource.created_by WHERE resource.ref ='".$ref."'";
	$result = sql_query($query);
	$user = '';
	if(count($result) == 0)
        {
        $user = $lang["notavailableshort"];
        }
    elseif(trim($result[0]['fullname']) != '') 
		{
		$user = $result[0]['fullname'];
		} 
	else 
		{
		$user = $result[0]['username'];
		}
	return $htmlbreak . $user . ': ' . $url;
	}

/**
 * Send notifications when resources are moved from "User Contributed - Pending Submission" to "User Contributed - Pending Review"
 *
 * @param  array|int  $refs         ID of resource(s)
 * @param  int        $collection   ID of collection
 * 
 * @return boolean|void
 */
function notify_user_contributed_submitted($refs,$collection=0)
	{
	global $notify_user_contributed_submitted,$applicationname,$email_notify,$baseurl,$lang,$use_phpmailer;
	if (!$notify_user_contributed_submitted) {return false;} # Only if configured.
	$htmlbreak="\r\n";
	if ($use_phpmailer){$htmlbreak="<br /><br />";}
	
	$list="";
	if(is_array($refs))
		{
		for ($n=0;$n<count($refs);$n++)
			{
			$list .= process_notify_user_contributed_submitted($refs[$n],$htmlbreak);
			}
		}
	else
		{
		$list=process_notify_user_contributed_submitted($refs,$htmlbreak);
		}
		
	$list.=$htmlbreak;	
	
    if($collection != 0) 
        {
        $templatevars['url'] = $baseurl . "/pages/search.php?search=!collection" . $collection;
        }
    elseif(is_array($refs) && count($refs) < 200)
        {
        $templatevars['url'] = $baseurl . "/pages/search.php?search=!list" . implode(":",$refs);
        }
    else
        {
        $templatevars['url'] = $baseurl . "/pages/search.php?search=!contributions" . $userref . "&archive=-1";
        }
	
	$templatevars['list']=$list;
	$message=$lang["userresourcessubmitted"] . "\n\n". $templatevars['list'] . "\n\n" . $lang["viewall"] . "\n\n" . $templatevars['url'];
	$notificationmessage=$lang["userresourcessubmittednotification"];
	$notify_users=get_notification_users(array("e-1","e0")); 
	$message_users=array();
	foreach($notify_users as $notify_user)
			{
			get_config_option($notify_user['ref'],'user_pref_resource_notifications', $send_message);		  
            if($send_message==false){continue;}		
			
			get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
			if($send_email && $notify_user["email"]!="")
				{
				send_mail($notify_user["email"],$applicationname . ": " . $lang["status-1"],$message,"","","emailnotifyresourcessubmitted",$templatevars);
				}        
			else
				{
				$message_users[]=$notify_user["ref"];
				}
			}
	if (count($message_users)>0)
		{
		global $userref;
		if($collection!=0)
			{
			message_add($message_users,$notificationmessage,$templatevars['url'],$userref,MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,MESSAGE_DEFAULT_TTL_SECONDS,SUBMITTED_COLLECTION,$collection);
			}
		else
			{
			message_add($message_users,$notificationmessage,$templatevars['url'],$userref,MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,MESSAGE_DEFAULT_TTL_SECONDS,SUBMITTED_RESOURCE,(is_array($refs)?$refs[0]:$refs));
			}
		}
    }
    
/**
* Send notifications when resources are moved from "User Contributed - Pending Review" to "User Contributed - Pending Submission"
*
* @param  array|int  $refs    ID of resource(s)
* @param  mixed $collection   ID of collection
* 
* @return boolean
*/
function notify_user_contributed_unsubmitted($refs,$collection=0)
	{
	// Send notifications when resources are moved from "User Contributed - Pending Review"	to "User Contributed - Pending Submission"
	global $notify_user_contributed_unsubmitted,$applicationname,$email_notify,$baseurl,$lang,$use_phpmailer;
	if (!$notify_user_contributed_unsubmitted) {return false;} # Only if configured.
	
	$htmlbreak="\r\n";
	if ($use_phpmailer){$htmlbreak="<br /><br />";}
	
	$list="";
	if(is_array($refs))
		{
		for ($n=0;$n<count($refs);$n++)
			{
			$url="";	
			$url=$baseurl . "/?r=" . $refs[$n];
			
			if ($use_phpmailer){$url="<a href=\"$url\">$url</a>";}
			
			$list.=$htmlbreak . $url . "\n\n";
			}
		}
	else
		{
		$url="";	
		$url=$baseurl . "/?r=" . $refs;
		if ($use_phpmailer){$url="<a href=\"$url\">$url</a>";}
		$list.=$htmlbreak . $url . "\n\n";
		}
	
	$list.=$htmlbreak;		
	$templatevars['list']=$list;
	
	if($collection != 0) 
        {
        $templatevars['url'] = $baseurl . "/pages/search.php?search=!collection" . $collection;
        }
    elseif(is_array($refs) && count($refs) < 200)
        {
        $templatevars['url'] = $baseurl . "/pages/search.php?search=!list" . implode(":",$refs);
        }
    else
        {
        $templatevars['url'] = $baseurl . "/pages/search.php?search=!contributions" . $userref . "&archive=-2";
        }
        
	$message=$lang["userresourcesunsubmitted"]."\n\n". $templatevars['list'] . $lang["viewall"] . "\n\n" . $templatevars['url'];

	$notificationmessage=$lang["userresourcesunsubmittednotification"];
	$notify_users=get_notification_users(array("e-1","e0")); 
	$message_users=array();
	foreach($notify_users as $notify_user)
			{
			get_config_option($notify_user['ref'],'user_pref_resource_notifications', $send_message);		  
            if($send_message==false){continue;}		
			
			get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
			if($send_email && $notify_user["email"]!="")
				{
				send_mail($notify_user["email"],$applicationname . ": " . $lang["status-2"],$message,"","","emailnotifyresourcesunsubmitted",$templatevars);
				}        
			else
				{
				$message_users[]=$notify_user["ref"];
				}
			}
	if (count($message_users)>0)
		{
		global $userref;
        message_add($message_users,$notificationmessage,$templatevars['url']);
		}
	
	# Clear any outstanding notifications relating to submission of these resources
	message_remove_related(SUBMITTED_RESOURCE,$refs);
	if($collection!=0)
		{
		message_remove_related(SUBMITTED_COLLECTION,$collection);
		}
	}		
	

/**
*  A standard field title is translated using $lang.  A custom field title is i18n translated.
* 
* @param integer $field Resource type field ID
* 
* @return boolean|array Returns FALSE or record data (array)
*/
function get_field($field)
    {
    $field_escaped = escape_check($field);
    $r = sql_query("
        SELECT ref,
               name,
               title,
               type,
               order_by,
               keywords_index,
               partial_index,
               resource_type,
               resource_column,
               display_field,
               use_for_similar,
               iptc_equiv,
               display_template,
               tab_name,
               required,
               smart_theme_name,
               exiftool_field,
               advanced_search,
               simple_search,
               help_text,
               display_as_dropdown,
               automatic_nodes_ordering
          FROM resource_type_field
         WHERE ref = '{$field_escaped}'
     ", "schema");

    # Translates the field title if the searched field is found.
    if(0 == count($r))
        {
        return false;
        }
    else
        {
        $r[0]["title"] = lang_or_i18n_get_translated($r[0]["title"], "fieldtitle-");
        return $r[0];
        }
    }
		
function get_keyword_from_option($option)
	{
	# For the given field option, return the keyword that will be indexed.
	$keywords=split_keywords("," . $option);

	global $stemming;
	if($stemming && function_exists('GetStem')) {
		$keywords[1] = GetStem($keywords[1]);
	}

	return $keywords[1];
	}

function get_resource_access($resource)
	{
    global $customgroupaccess,$customuseraccess, $internal_share_access, $k,$uploader_view_override, $userref,
        $prevent_open_access_on_edit_for_active, $search_filter_nodes, $open_access_for_contributor,
        $userref,$usergroup, $usersearchfilter, $search_filter_strict, $search_all_workflow_states,
        $userderestrictfilter, $userdata;
	# $resource may be a resource_data array from a search, in which case, many of the permissions checks are already done.	
		
	# Returns the access that the currently logged-in user has to $resource.
	# Return values:
	# 0 = Full Access (download all sizes)
	# 1 = Restricted Access (download only those sizes that are set to allow restricted downloads)
	# 2 = Confidential (no access)
	
	# Load the 'global' access level set on the resource
	# In the case of a search, resource type and global,group and user access are passed through to this point, to avoid multiple unnecessary get_resource_data queries.
	# passthru signifies that this is the case, so that blank values in group or user access mean that there is no data to be found, so don't check again .
	$passthru="no";

	// get_resource_data doesn't contain permissions, so fix for the case that such an array could be passed into this function unintentionally.
	if (is_array($resource) && !isset($resource['group_access']) && !isset($resource['user_access'])){$resource=$resource['ref'];}
	
	if (!is_array($resource))
        {
        $resourcedata=get_resource_data($resource,true);
        if(!$resourcedata)
            { return RESOURCE_ACCESS_INVALID_REQUEST; }
        }
	else
        {
        $resourcedata=$resource;
        $passthru="yes";
        }
                
	$ref=$resourcedata['ref'];
	$access=$resourcedata["access"];
	$resource_type=$resourcedata['resource_type'];
	
	// Set a couple of flags now that we can check later on if we need to check whether sharing is permitted based on whether access has been specifically granted to user/group
    $customgroupaccess=false;
	$customuseraccess=false;
	
	if('' != $k)
		{

		# External access - check how this was shared.
		$extaccess = sql_value("SELECT access `value` FROM external_access_keys WHERE resource = '{$ref}' AND access_key = '" . escape_check($k) . "' AND (expires IS NULL OR expires > NOW())", -1);

		if(-1 != $extaccess && (!$internal_share_access || ($internal_share_access && $extaccess < $access)))
            {
            return (int) $extaccess;
            }
		}
	
	if (checkperm("z" . $resourcedata['archive']) && !($uploader_view_override && $resourcedata['created_by'] == $userref))
		{
		// User has no access to this archive state 
		return 2;
		}
	
	if (checkperm("v"))
		{
		# Permission to access all resources
		# Always return 0
		return 0; 
		}	

	if ($access==3)
		{
		$customgroupaccess=true;
		# Load custom access level
		if ($passthru=="no"){ 
			$access=get_custom_access($resource,$usergroup);
			} 
		else {
			$access=$resource['group_access'];
		}
	}

	if ($access == 1 && get_edit_access($ref,$resourcedata['archive'],false,$resourcedata) && !$prevent_open_access_on_edit_for_active)
		{
		# If access is restricted and user has edit access, grant open access.
		$access = 0;
		}

	if ($open_access_for_contributor && $resourcedata['created_by'] == $userref)
		{
		# If user has contributed resource, grant open access and ignore any further filters.
		return 0;
		}

	# Check for user-specific and group-specific access (overrides any other restriction)
	
	// We need to check for custom access either when access is set to be custom or
	// when the user group has restricted access to all resource types or specific resource types
	// are restricted
    if ($access!=0 || !checkperm('g') || checkperm('X' . $resource_type) || checkperm("rws{$resourcedata['archive']}"))
        {
        if ($passthru=="no")
            {
            $userspecific=get_custom_access_user($resource,$userref);
            $groupspecific=get_custom_access($resource,$usergroup,false);	
            } 
        else
            {
            $userspecific=$resourcedata['user_access'];
            $groupspecific=$resourcedata['group_access'];
            }
        }
	
	if (isset($userspecific) && $userspecific!="")
		{
		$customuseraccess=true;
		return (int) $userspecific;
		}
	if (isset($groupspecific) && $groupspecific!="")
		{
		$customgroupaccess=true;
		return (int) $groupspecific;
		}
        
	if (checkperm('T'.$resource_type))
		{
		// this resource type is always confidential/hidden for this user group
		return 2;
		}
		
	if ((trim($usersearchfilter)!="") && $search_filter_strict)
        {
		# A search filter has been set. Perform filter processing to establish if the user can view this resource.		
        # Apply filters by searching for the resource, utilising the existing filter matching in do_search to avoid duplication of logic.

        $search_all_workflow_states_cache = $search_all_workflow_states;
        $search_all_workflow_states = TRUE;
        $results=do_search("!resource" . $ref);
        $search_all_workflow_states = $search_all_workflow_states_cache;
        if (count($results)==0) {return 2;} # Not found in results, so deny
        }

    /*
    Restricted access to all available resources
    OR Restricted access to resources in a particular workflow state
    OR Restricted access to resources of a particular resource type
    UNLESS user/ group has been granted custom (override) access
    */
    if (
        $access == 0
        && ((!checkperm("g") || checkperm("rws{$resourcedata['archive']}") || checkperm('X'.$resource_type))
        && !$customgroupaccess
        && !$customuseraccess)
        )
        {
        $access = 1;
        }

	// Check for a derestrict filter, this allows exceptions for users without the 'g' permission who normally have restricted accesss to all available resources)
	if ($access==1 && !checkperm("g") && !checkperm("rws{$resourcedata['archive']}") && !checkperm('X'.$resource_type) && trim($userderestrictfilter) != "")
		{
        if($search_filter_nodes 
            && strlen(trim($userderestrictfilter)) > 0
            && !is_numeric($userderestrictfilter)
            && trim($userdata[0]["derestrict_filter"]) != ""
            && $userdata[0]["derestrict_filter_id"] != -1
        )
            {
            // Migrate unless marked not to due to failure (flag will be reset if group is edited)
            $migrateresult = migrate_filter($userderestrictfilter);
            $notification_users = get_notification_users();
            global $userdata, $lang, $baseurl;
            if(is_numeric($migrateresult))
                {
                // Successfully migrated - now use the new filter
                sql_query("UPDATE usergroup SET derestrict_filter_id='" . $migrateresult . "' WHERE ref='" . $usergroup . "'");
                debug("FILTER MIGRATION: Migrated derestrict_filter_id filter - '" . $userderestrictfilter . "' filter id#" . $migrateresult);
                $userderestrictfilter = $migrateresult;
                }
            elseif(is_array($migrateresult))
                {
                debug("FILTER MIGRATION: Error migrating filter: '" . $userderestrictfilter . "' - " . implode('\n' ,$migrateresult));
                // Error - set flag so as not to reattempt migration and notify admins of failure
                sql_query("UPDATE usergroup SET derestrict_filter_id='-1' WHERE ref='" . $usergroup . "'");
                message_add(array_column($notification_users,"ref"), $lang["filter_migration"] . " - " . $lang["filter_migrate_error"] . ": <br />" . implode('\n' ,$migrateresult),generateURL($baseurl . "/pages/admin/admin_group_management_edit.php",array("ref"=>$usergroup)));
                }
            }

        if($search_filter_nodes && is_numeric($userderestrictfilter) && $userderestrictfilter > 0)
            {
            $matchedfilter = filter_check($userderestrictfilter, get_resource_nodes($ref));
            }
        else
            {
            # Old style filter 
            if(!isset($metadata))
                {
                #  load metadata if not already loaded
                $metadata=get_resource_field_data($ref,false,false);
                }

            $matchedfilter=false;
            for ($n=0;$n<count($metadata);$n++)
                {
                $name=$metadata[$n]["name"];
                $value=$metadata[$n]["value"];
                if ($name!="")
                    {
                    $match=filter_match($userderestrictfilter,$name,$value);
                    if ($match==1) {$matchedfilter=false;break;}
                    if ($match==2) {$matchedfilter=true;} 
                    }
                }
            }
        if($matchedfilter)
            {
            $access=0;
            $customgroupaccess = true;
            }
        }
		
	return (int) $access;
	}

	
function get_custom_access_user($resource,$user)
	{
	return sql_value("select access value from resource_custom_access where resource='$resource' and user='$user' and (user_expires is null or user_expires>now())",false);
	}

function edit_resource_external_access($key,$access=-1,$expires="",$group="",$sharepwd="")
	{
	global $userref,$usergroup, $scramble_key;
	if ($group=="" || !checkperm("x")) {$group=$usergroup;} # Default to sharing with the permission of the current usergroup if not specified OR no access to alternative group selection.
	if ($key==""){return false;}
	# Update the expiration and acccess
	sql_query("update external_access_keys set access='$access', expires=" . (($expires=="")?"null":"'" . $expires . "'") . ",date=now(),usergroup='$group'" . (($sharepwd != "(unchanged)") ? ", password_hash='" . (($sharepwd == "") ? "" : hash('sha256', $key . $sharepwd . $scramble_key)) . "'" : "") . " where access_key='$key'");
    hook('edit_resource_external_access','',array($key,$access,$expires,$group));
    return true;
	}

/**
 * For the given resource and size, can the current user download it?
 * resource type and access may already be available in the case of search, so pass them along to get_resource_access to avoid extra queries
 * $resource can be a resource-specific search result array.
 *
 * @param  int     $resource        ID of resource
 * @param  string  $size            ID of size
 * @param  int     $resource_type   ID of resource type
 * @param  int     $alternative     Use alternative?
 * 
 * @return boolean
 */
function resource_download_allowed($resource,$size,$resource_type,$alternative=-1)
	{
	global $userref, $usergroup, $user_dl_limit, $user_dl_days, $noattach;
	$access=get_resource_access($resource);

    if (checkperm('T' . $resource_type . "_" . $size))
        {
        return false;
        }

	if (checkperm('X' . $resource_type . "_" . $size) && $alternative==-1)
		{
		# Block access to this resource type / size? Not if an alternative file
		# Only if no specific user access override (i.e. they have successfully requested this size).
		$usercustomaccess = get_custom_access_user($resource,$userref);
		$usergroupcustomaccess = get_custom_access($resource,$usergroup);
		if (($usercustomaccess === false || !($usercustomaccess==='0')) && ($usergroupcustomaccess === false || !($usergroupcustomaccess==='0'))) {return false;}
        }
    
    if(($size == "" || $size == "hpr" || getval("noattach","") == "")  && intval($user_dl_limit) > 0)
        {
        $download_limit_check = get_user_downloads($userref,$user_dl_days);
        if($download_limit_check >= $user_dl_limit)
            {
            return false;
            }
        }

	# Full access
	if ($access==0)
		{
		return true;
		}

	# Special case for purchased downloads.
	global $userref;
	if (isset($userref))
		{
		$complete=sql_value("select cr.purchase_complete value from collection_resource cr join collection c on cr.collection=c.ref where c.user='$userref' and cr.resource='$resource' and cr.purchase_size='" . escape_check($size) . "'",0);
		if ($complete==1) {return true;}
		}

    # Restricted
    if(1 == $access)
        {
        // The system should always allow these sizes to be downloaded as these are needed for search results and it makes
        // sense to allow them if a request for one of them is received. For example when $hide_real_filepath is enabled.
        // 'videojs' represents the preview loaded by videojs viewer.
        $sizes_always_allowed = array('col', 'thm', 'pre', 'snapshot','videojs');

        if('' == $size)
            {
            # Original file - access depends on the 'restricted_full_download' config setting.
            global $restricted_full_download;
            return $restricted_full_download;
            }
        else if('' != $size && in_array($size, $sizes_always_allowed))
            {
            return true;
            }
        else
            {
            # Return the restricted access setting for this resource type.
            return (sql_value("select allow_restricted value from preview_size where id='" . escape_check($size) . "'",0)==1);
            }
        }

	# Confidential
	if ($access==2)
		{
		return false;
		}
	
	}


function get_edit_access($resource,$status=-999,$metadata=false,&$resourcedata="")
	{
	# For the provided resource and metadata, does the current user have edit access to this resource?
    # Checks the edit permissions (e0, e-1 etc.) and also the group edit filter which filters edit access based on resource metadata.
	
    global $userref,$usergroup, $usereditfilter,$edit_access_for_contributor,
    $search_filter_nodes, $userpermissions, $lang, $baseurl, $userdata, $edit_only_own_contributions;
    $plugincustomeditaccess = hook('customediteaccess','',array($resource,$status,$resourcedata));

    if($plugincustomeditaccess)
        {
        return ('false' === $plugincustomeditaccess ? false : true);
        }

	if (!is_array($resourcedata) || !isset($resourcedata['resource_type'])) # Resource data  may not be passed 
		{
		$resourcedata=get_resource_data($resource);		
        }
    if(!is_array($resourcedata) || count($resourcedata) == 0)
        {
        return false;
        }
	if ($status==-999) # Archive status may not be passed 
		{$status=$resourcedata["archive"];}
		
    if ($resource==0-$userref) {return true;} # Can always edit their own user template.

    # If $edit_access_for_contributor is true in config then users can always edit their own resources.
    if ($edit_access_for_contributor && $userref==$resourcedata["created_by"]) {return true;}

    if($edit_only_own_contributions && $userref != $resourcedata["created_by"])
        {
        return false;
        }
        
    # Must have edit permission to this resource first and foremost, before checking the filter.
    if ((!checkperm("e" . $status) && !checkperm("ert" . $resourcedata['resource_type']))
        ||
        (checkperm("XE" . $resourcedata['resource_type']))
        ||
        (checkperm("XE") && !checkperm("XE-" . $resourcedata['resource_type']))
        )
        {
        return false;
        }
    
    # Cannot edit if z permission
    if (checkperm("z" . $status)) {return false;}

    # Cannot edit if accessing upload share and resource not in the collection associated witrh their session
    $external_upload = upload_share_active();
    if($external_upload && !in_array($resource,get_collection_resources($external_upload)))
        {
        return false;
        }

    # Cannot edit if pending status (<0) and neither admin ('t') nor created by currentuser 
    #             and does not have force edit access to the resource type
    if (    $status<0 && !( checkperm("t") || $resourcedata['created_by'] == $userref ) 
         && !checkperm("ert" . $resourcedata['resource_type'])
       )
        {
        return false;
        } 

    $gotmatch=false;

    if($search_filter_nodes 
        && strlen(trim($usereditfilter)) > 0
        && !is_numeric($usereditfilter)
        && trim($userdata[0]["edit_filter"]) != ""
        && $userdata[0]["edit_filter_id"] != -1
        )
        {
        // Migrate unless marked not to due to failure (flag will be reset if group is edited)
        $migrateeditfilter = edit_filter_to_restype_permission($usereditfilter, $usergroup, $userpermissions, true);
        $migrateresult = migrate_filter($migrateeditfilter); 
        $notification_users = get_notification_users();
        if(is_numeric($migrateresult))
            {
            // Successfully migrated - now use the new filter
            sql_query("UPDATE usergroup SET edit_filter_id='" . $migrateresult . "' WHERE ref='" . $usergroup . "'");
            debug("FILTER MIGRATION: Migrated edit filter - '" . $usereditfilter . "' filter id#" . $migrateresult);
            $usereditfilter = $migrateresult;
            }
        elseif(is_array($migrateresult))
            {
            debug("FILTER MIGRATION: Error migrating filter: '" . $usereditfilter . "' - " . implode('\n' ,$migrateresult));
            // Error - set flag so as not to reattempt migration and notify admins of failure
            sql_query("UPDATE usergroup SET edit_filter_id='0' WHERE ref='" . $usergroup . "'");
            message_add(array_column($notification_users,"ref"), $lang["filter_migration"] . " - " . $lang["filter_migrate_error"] . ": <br />" . implode('\n' ,$migrateresult),generateURL($baseurl . "/pages/admin/admin_group_management_edit.php",array("ref"=>$usergroup)));
            }
        }
    
    if (trim($usereditfilter)=="" || ($status<0 && $resourcedata['created_by'] == $userref)) # No filter set, or resource was contributed by user and is still in a User Contributed state in which case the edit filter should not be applied.
		{
		$gotmatch = true;
		}
    elseif($search_filter_nodes && is_numeric($usereditfilter) && $usereditfilter > 0)
        {
        $gotmatch = filter_check($usereditfilter, get_resource_nodes($resource));
        }
    else
		{
		# An old style edit filter has been set. Perform edit filter processing to establish if the user can edit this resource.
        # Always load metadata, because the provided metadata may be missing fields due to permissions.
		$metadata=get_resource_field_data($resource,false,false);
				
		for ($n=0;$n<count($metadata);$n++)
			{
			$name=$metadata[$n]["name"];
			$value=$metadata[$n]["value"];			
			if ($name!="")
				{
				$match=filter_match(trim($usereditfilter),$name,$value);
				if ($match==1) {return false;} # The match for this field was incorrect, always fail in this event.
				if ($match==2) {$gotmatch=true;} # The match for this field was correct.
				}
			}

		# Also check resource type, if specified.
		if (strpos($usereditfilter,"resource_type")!==false)
			{
			$resource_type=$resourcedata['resource_type'];

			$match=filter_match(trim($usereditfilter),"resource_type",$resource_type);
			if ($match==1) {return false;} # Resource type was specified but the value did not match. Disallow edit access.
			if ($match==2) {$gotmatch=true;}
			}
		}

    if ($gotmatch) 
        {
        $gotmatch = !hook("denyafterusereditfilter");
        }
    
    if(checkperm("ert" . $resourcedata['resource_type']))
        {
        return true;
        }

    return $gotmatch;
    }

/**
* In the given filter string, does name/value match?
* Returns:
* 0 = no match for name
* 1 = matched name but value was not present
* 2 = matched name and value was correct
*
* @param  string  $filter   Sring to for which filtering is to be applied
* @param  string  $name     Name to match
* @param  string  $value    Value to match
* 
* @return int
*/
function filter_match($filter,$name,$value)
	{
    $s=explode(";",$filter);
	foreach ($s as $condition)
		{
		$s=explode("=",$condition);
		# Support for "NOT" matching. Return results only where the specified value or values are NOT set.
		$checkname=$s[0];$filter_not=false;
		if (substr($checkname,-1)=="!")
			{
			$filter_not=true;
			$checkname=substr($checkname,0,-1);# Strip off the exclamation mark.
			}
		if ($checkname==$name)
			{
			$checkvalues=$s[1];
			
			$s=explode("|",strtoupper($checkvalues));
			$v=trim_array(explode(",",strtoupper($value)));
			foreach ($s as $checkvalue)
				{
				if (in_array($checkvalue,$v))
					{
					return $filter_not ? 1 : 2;
					}
				}
			return $filter_not ? 2 : 1;
			}
		}
	return 0;
	}

/**
* Check changes made to a metadata field and create a nice user friendly summary
* 
* @uses Diff::compare()
* @uses Diff::toString()
* 
* @param string $fromvalue
* @param string $tovalue
* 
* @return string
*/
function log_diff($fromvalue, $tovalue)
    {
    $return = '';
    debug_function_call("log_diff",func_get_args());
    
    // Trim values as it can cause out of memory errors with class.Diff.php e.g. when saving extracted text or creating previews for large PDF files
    if(strlen($fromvalue)>10000)
        {
        $fromvalue = mb_substr($fromvalue,10000);
        }    
    if(strlen($tovalue)>10000)
        {
        $tovalue = mb_substr($tovalue,10000);
        }
    
    // Remove any database escaping
    $fromvalue = str_replace("\\", '', $fromvalue);
    $tovalue   = str_replace("\\", '', $tovalue);

    // Work a different way for fixed lists
    if(',' == substr($fromvalue, 0, 1) || ',' == substr($tovalue, 0, 1))
        {
        $fromvalue = array_filter(explode(',', $fromvalue));
        $tovalue   = array_filter(explode(',', $tovalue));

        // Empty arrays if either side is blank.
        if (count($fromvalue)==0) {$fromvalue=array();}
        if (count($tovalue)==0)   {$tovalue=array();}
            
        // Get diffs
        $inserts = array_diff($tovalue, $fromvalue);
        $deletes = array_diff($fromvalue, $tovalue);

        // Process array diffs into meaningful strings
        if(0 < count($deletes))
            {
            $return .= '- ' . join("\n- " , $deletes);
            }

        if(0 < count($inserts))
            {
            if('' != $return)
                {
                $return .= "\n";
                }

            $return .= '+ ' . join("\n+ ", $inserts);
            }

        return $return;
        }

    // Simple return when either side is blank (the user is adding or removing all the text)
    if ($fromvalue=="") {return "+ " . $tovalue;}
    if ($tovalue=="") {return "- " . $fromvalue;}
    
    // For standard strings, use Diff library
    require_once dirname(__FILE__) . '/../lib/Diff/class.Diff.php';
    $return = Diff::toString(Diff::compare($fromvalue, $tovalue));

    return $return;
    }
	


function get_metadata_templates()
	{
	# Returns a list of all metadata templates; i.e. resources that have been set to the resource type specified via '$metadata_template_resource_type'.
	global $metadata_template_resource_type,$metadata_template_title_field;
	return sql_query("select ref,field$metadata_template_title_field from resource where ref>0 and resource_type='$metadata_template_resource_type' order by field$metadata_template_title_field");
	}
 
function get_resource_collections($ref)
	{
	global $userref, $anonymous_user, $username;
	if (checkperm('b') || (isset($anonymous_login) && $username==$anonymous_login))
		{return array();}
	# Returns a list of collections that a resource is used in for the $view_resource_collections option
	$sql="";
   
    # Include themes in my collections? 
    # Only filter out themes if $themes_in_my_collections is set to false in config.php
   	global $themes_in_my_collections;
   	if (!$themes_in_my_collections)
   		{
   		if ($sql!="") {$sql.=" and ";}
   		$sql.="(length(c.theme)=0 or c.theme is null) ";
   		}
	if ($sql!="") {$sql="where " . $sql;}
   
	$return=sql_query ("select * from 
	(select c.*,u.username,u.fullname,count(r.resource) count from user u join collection c on u.ref=c.user and c.user='$userref' left outer join collection_resource r on c.ref=r.collection group by c.ref
	union
	select c.*,u.username,u.fullname,count(r.resource) count from user_collection uc join collection c on uc.collection=c.ref and uc.user='$userref' and c.user<>'$userref' left outer join collection_resource r on c.ref=r.collection left join user u on c.user=u.ref group by c.ref) clist where clist.ref in (select collection from collection_resource cr where cr.resource=$ref)");
	
	return $return;
	}
	
function download_summary($resource)
	{
	# Returns a summary of downloads by usage type
	return sql_query("select usageoption,count(*) c from resource_log where resource='$resource' and type='D' group by usageoption order by usageoption");
	}

/*
* Check if watermark is required. 
*
* @param string $download_key  Optional - download key used when $terms_download and $watermark_open are enabled
* @param string $resource      Optional - resource ID to check download key is valid for
* 
* * @return boolean
*/
function check_use_watermark($download_key = "", $resource="")
    {
    debug_function_call("check_use_watermark", func_get_args());
	# This function checks whether or not to use watermarks
    # Note that access status must be available prior to calls to this function    

    global $access,$k,$watermark,$watermark_open,$pagename,$watermark_open_search, $terms_download;

    # Cannot watermark without a watermark
    if(!isset($watermark))
        {
        return false; 
        }

    # Cannot watermark unless permission "w" is present       
    if(!checkperm('w'))
        { 
        return false; 
        }

    # Watermark is present and permission "w" is present

    # Watermark if access is restricted
    if($access == 1)
        { 
        return true; 
        }

    # Watermark if open override is present 
    if(    $watermark_open  
        && (    ($pagename == "preview") 
             || ($pagename == "view") 
             || ($pagename == "search" && $watermark_open_search)
             || ($pagename == "download" && $terms_download && !download_link_check_key($download_key, $resource))
           ) )
        { 
        return true; 
        } 

    # Watermark not necessary
    return false;
}


/**
* Fill in any blank fields for the resource
* 
* @uses escape_check()
* @uses sql_value()
* @uses sql_query()
* @uses update_field()
* @uses get_resource_nodes()
* 
* @param integer $resource  Resource ID
* @param boolean $force_run  Allow code to force running this function and update the fields even if there is data.
* @param boolean $return_changes  When true an array of fields changed by autocomplete is returned.
* For example:
* - when creating a resource, autocomplete_blank_fields() should always be triggered regardless if user has data in its user template.
* - when copying resource/ extracting embedded metadata, autocomplete_blank_fields() should not overwrite if there is data 
* for that field as at this point you probably have the expected data for your field.
* 
* @return boolean|array Success/fail or array of changes made
*/
function autocomplete_blank_fields($resource, $force_run, $return_changes = false)
    {
    global $FIXED_LIST_FIELD_TYPES, $lang;

    if((string)(int)$resource != (string)$resource)
        {
        return false;
        }

    $resource_escaped = escape_check($resource);
    $resource_type = sql_value("SELECT resource_type AS `value` FROM resource WHERE ref = '{$resource_escaped}'", 0);

    $fields = sql_query("
        SELECT ref,
               type,
               autocomplete_macro
          FROM resource_type_field
         WHERE (resource_type = 0 || resource_type = '{$resource_type}')
           AND length(autocomplete_macro) > 0
    ", "schema");

    $fields_updated = array();

    foreach($fields as $field)
        {
        if(in_array($field['type'], $FIXED_LIST_FIELD_TYPES))
            {
            if(count(get_resource_nodes($resource, $field['ref'], true)) > 0)
                {
                continue;
                }
            $value = "";
            }
        else
            {
            $value = sql_value("SELECT `value` FROM resource_data WHERE resource = '{$resource_escaped}' AND resource_type_field = '{$field['ref']}'", '');
            }

        $run_autocomplete_macro = $force_run || hook('run_autocomplete_macro');
        if(strlen(trim($value)) == 0 || $run_autocomplete_macro)
            {
            # Empty value. Autocomplete and set.
            $value = eval($field['autocomplete_macro']);
            if(in_array($field['type'], $FIXED_LIST_FIELD_TYPES))
                {
                $autovals = str_getcsv($value);
                $autonodes = array();
                foreach($autovals as $autoval)
                    {
                    $nodeid = get_node_id($autoval,$field['ref']);
                    if($nodeid !== false)
                        {
                        $autonodes[] = $nodeid;
                        }
                    }
                natsort($autonodes);
                add_resource_nodes($resource,$autonodes,false,false);
                log_node_changes($resource,$autonodes,array(),$lang["autocomplete_log_note"]);
                $fields_updated[$field['ref']] = implode(",",$autonodes);
                }
            else
                {
                update_field($resource, $field['ref'], $value);
                $fields_updated[$field['ref']] = $value;
                }
            }
        }

    if ($return_changes)
        {
        return $fields_updated;
        }
    return true;
    }

function reindex_resource($ref)
	{
	global $index_contributed_by, $index_resource_type,$FIXED_LIST_FIELD_TYPES;
	# Reindex a resource. Delete all resource_keyword rows and create new ones.
	
	# Delete existing keywords
	sql_query("DELETE FROM resource_keyword WHERE resource = '{$ref}'");

	# Index fields
	$data=get_resource_field_data($ref,false,false); # Fetch all fields and do not use permissions.
	for ($m=0;$m<count($data);$m++)
		{
		if ($data[$m]["keywords_index"]==1 && !in_array($data[$m]["type"],$FIXED_LIST_FIELD_TYPES))
			{
			#echo $data[$m]["value"];
			$value=$data[$m]["value"];
			if ($data[$m]["type"]==3 || $data[$m]["type"]==2)
				{
				# Prepend a comma when indexing dropdowns
				$value="," . $value;
				}
			
			# Date field? These need indexing differently.
			$is_date=($data[$m]["type"]==4 || $data[$m]["type"]==6);

			$is_html=($data[$m]["type"]==8);					
			add_keyword_mappings($ref,i18n_get_indexable($value),$data[$m]["ref"],$data[$m]["partial_index"],$is_date,'','',$is_html);		
			}
		}
	
	# Also index contributed by field, unless disabled
	if ($index_contributed_by)
		{
		$resource=get_resource_data($ref);
		$userinfo=get_user($resource["created_by"]);
		add_keyword_mappings($ref,$userinfo["username"] . " " . $userinfo["fullname"],-1);
		}

        # Also index the resource type name, unless disabled
	if ($index_resource_type)
		{
		$restypename=sql_value("select name value from resource_type where ref in (select resource_type from resource where ref='" . escape_check($ref) . "')","");
		add_keyword_mappings($ref,$restypename,-2);
		}
                
	# Always index the resource ID as a keyword
	add_keyword_mappings($ref, $ref, -1);
	
	hook("afterreindexresource","all",array($ref));
	}


function get_page_count($resource,$alternative=-1)
    {
    # gets page count for multipage previews from resource_dimensions table.
    # also handle alternative file multipage previews by switching $resource array if necessary
    # $alternative specifies an actual alternative file
    $ref=$resource['ref'];
    $ref_escaped = escape_check($ref);
    $alternative_escaped = escape_check($alternative);

    if ($alternative!=-1)
        {
        $pagecount=sql_value("select page_count value from resource_alt_files where ref='{$alternative_escaped}'","");
        $resource=get_alternative_file($ref,$alternative);
        }
    else
        {
        $pagecount=sql_value("select page_count value from resource_dimensions where resource='{$ref_escaped}'","");
        }
    if (!empty($pagecount)) { return $pagecount; }
    # or, populate this column with exiftool or image magick (for installations with many pdfs already
	# previewed and indexed, this allows pagecount updates on the fly when needed):
    # use exiftool. 
	if ($resource['file_extension']=="pdf" && $alternative==-1)
		{
		$file=get_resource_path($ref,true,"",false,"pdf");
		}
	else if ($alternative==-1)
		{
		# some unoconv files are not pdfs but this needs to use the auto-alt file
		$alt_ref=sql_value("select ref value from resource_alt_files where resource='{$ref_escaped}' and unoconv=1","");
		$file=get_resource_path($ref,true,"",false,"pdf",-1,1,false,"",$alt_ref);
		}
	else
		{
		$file=get_resource_path($ref,true,"",false,"pdf",-1,1,false,"",$alternative);
		}

	# locate exiftool
    $exiftool_fullpath = get_utility_path("exiftool");
    if ($exiftool_fullpath==false)
		{
		# Try with ImageMagick instead
		$command = get_utility_path("im-identify") . ' -format %n ' . escapeshellarg($file);
		$pages = trim(run_command($command));
		}
    else
        {
        $command = $exiftool_fullpath;
    	
        $command= escapeshellarg($command) . " -sss -pagecount " . escapeshellarg($file);
        $output=run_command($command);
        $pages=str_replace("Page Count","",$output);
        $pages=str_replace(":","",$pages);
        $pages=trim($pages);
		}

	if (!is_numeric($pages)){ $pages = 1; } // default to 1 page if we didn't get anything back

	if ($alternative!=-1)
		{
		sql_query("update resource_alt_files set page_count='$pages' where ref='{$alternative_escaped}'");
		}
	else
		{
		sql_query("update resource_dimensions set page_count='$pages' where resource='{$ref_escaped}'");
		}
	return $pages;
	}


function update_disk_usage($resource)
	{
	# we're also going to record the size of the primary resource here before we do the entire folder
	$ext = sql_value("SELECT file_extension value FROM resource where ref = '" . escape_check($resource) . "' AND file_path IS NULL",'jpg');
	$path = get_resource_path($resource,true,'',false,$ext);
	if (file_exists($path)){
		$rsize = filesize_unlimited($path);
	} else {
		$rsize = 0;
	}

	# Scan the appropriate filestore folder and update the disk usage fields on the resource table. Use the thm size so that we don't get a Staticsync location
	$dir=dirname(get_resource_path($resource,true,"thm",false));
	if (!file_exists($dir)) {return false;} # Folder does not yet exist.
	$d = dir($dir); 
	$total=0;
	while ($f = $d->read())
		{
		if ($f!=".." && $f!=".")
			{
			$s=filesize_unlimited($dir . "/" .$f);
			#echo "<br/>-". $f . " : " . $s;
			$total+=$s;
			}
		}
	#echo "<br/>total=" . $total;
	sql_query("update resource set disk_usage='$total',disk_usage_last_updated=now(),file_size='$rsize' where ref='" . escape_check($resource) . "'");
	return true;
	}

/**
 * Update disk usage for all resources that have not yet been updated or have not been updated in the past 30 days.
 * Limit to a reasonable amount so that this process is spread over several cron intervals for large data sets.
 *
 * @return boolean|void
 */
function update_disk_usage_cron()
	{
    $lastrun = get_sysvar('last_update_disk_usage_cron', '1970-01-01');
    # Don't run if already run in last 24 hours.
    if (time()-strtotime($lastrun) < 24*60*60)
        {
        echo " - Skipping update_disk_usage_cron  - last run: " . $lastrun . "<br />\n";
        return false;
        }

	$resources=sql_array("select ref value from resource where ref>0 and disk_usage_last_updated is null or datediff(now(),disk_usage_last_updated)>30 limit 20000");
	foreach ($resources as $resource)
		{
		update_disk_usage($resource);
        }
    
    set_sysvar("last_update_disk_usage_cron",date("Y-m-d H:i:s"));
	}

/**
 * Returns the total disk space used by all resources on the system
 *
 * @return int  
 */
function get_total_disk_usage()
    {
    global $fstemplate_alt_threshold;
    return sql_value("select ifnull(sum(disk_usage),0) value from resource where ref>'$fstemplate_alt_threshold'",0);
    }

function overquota()
	{
	# Return true if the system is over quota
	global $disksize;
	if (isset($disksize))
		{
		# Disk quota functionality. Calculate the usage by the $storagedir folder only rather than the whole disk.
		# Unix only due to reliance on 'du' command
		
		$avail=$disksize * 1000 * 1000 * 1000;
		$used=get_total_disk_usage();
		
		$free=$avail-$used;
		if ($free<=0) {return true;}
		}
	return false;
	}

function notify_user_resources_approved($refs)
	{
	// Send a notification mail to the user when resources have been approved
	global $applicationname,$baseurl,$lang;	
	debug("Emailing user notifications of resource approvals");	
	$htmlbreak="\r\n";
	global $use_phpmailer,$userref,$templatevars;
	if ($use_phpmailer){$htmlbreak="<br /><br />";}
	$notifyusers=array();
	
    if(!is_array($refs))
        {
        $refs=array($refs);    
        }
	for ($n=0;$n<count($refs);$n++)
		{
		$ref=$refs[$n];
		$contributed=sql_value("select created_by value from resource where ref='$ref'",0);
		if($contributed!=0 && $contributed!=$userref)
			{
			if(!isset($notifyusers[$contributed])) // Add new array entry if not already present
				{
				$notifyusers[$contributed]=array();
				$notifyusers[$contributed]["list"]="";
				$notifyusers[$contributed]["resources"]=array();
				$notifyusers[$contributed]["url"]=$baseurl . "/pages/search.php?search=!contributions" . $contributed . "&archive=0";
				}		
			$notifyusers[$contributed]["resources"][]=$ref;
			$url=$baseurl . "/?r=" . $refs[$n];		
			if ($use_phpmailer){$url="<a href=\"$url\">$url</a>";}
			$notifyusers[$contributed]["list"].=$htmlbreak . $url . "\n\n";
			}		
		}
	foreach($notifyusers as $key=>$notifyuser)	
		{
		$templatevars['list']=$notifyuser["list"];
		$templatevars['url']=$notifyuser["url"];			
		$message=$lang["userresourcesapproved"] . "\n\n". $templatevars['list'] . "\n\n" . $lang["viewcontributedsubittedl"] . "\n\n" . $notifyuser["url"];
		$notificationmessage=$lang["userresourcesapproved"];
		
		// Does the user want these messages?
		get_config_option($key,'user_pref_resource_notifications', $send_message);		  
        if($send_message==false){continue;}		
       
		// Does the user want an email or notification?
		get_config_option($key,'email_user_notifications', $send_email);    
		if($send_email)
			{
			$notify_user=sql_value("select email value from user where ref='$key'","");
			if($notify_user!='')
				{
				send_mail($notify_user,$applicationname . ": " . $lang["approved"],$message,"","","emailnotifyresourcesapproved",$templatevars);
				}
			}        
		else
			{
			global $userref;
			message_add($key,$notificationmessage,$notifyuser["url"]);
			}
		}
	}
	
		

function get_original_imagesize($ref="",$path="", $extension="jpg", $forcefromfile=false)
	{
	$fileinfo=array();
	if($ref=="" || $path==""){return false;}
	global $imagemagick_path, $imagemagick_calculate_sizes;
	$file=$path;
    $ref_escaped = escape_check($ref);
	$o_size=sql_query("select * from resource_dimensions where resource='{$ref_escaped}'");
	if(!empty($o_size))
		{
		if(count($o_size)>1)
			{
			# delete all the records and start fresh. This is a band-aid should there be multiple records as a result of using api_search
			sql_query("delete from resource_dimensions where resource='{$ref_escaped}'");
			$o_size=false;
			$forcefromfile=true;
			}
		else
			{
			$o_size=$o_size[0];
			}
		}
	else
		{
		$o_size=false;
		}
		
	if($o_size!==false && !$forcefromfile){
		
		$fileinfo[0]=$o_size['file_size'];
		$fileinfo[1]=$o_size['width'];
		$fileinfo[2]=$o_size['height'];
		return $fileinfo;
	}
	
	$filesize=filesize_unlimited($file);
	
	# imagemagick_calculate_sizes is normally turned off 
	if (isset($imagemagick_path) && $imagemagick_calculate_sizes)
		{
		# Use ImageMagick to calculate the size
		
		$prefix = '';
		# Camera RAW images need prefix
		if (preg_match('/^(dng|nef|x3f|cr2|crw|mrw|orf|raf|dcr)$/i', $extension, $rawext)) { $prefix = $rawext[0] .':'; }

		# Locate imagemagick.
		$identify_fullpath = get_utility_path("im-identify");
		if ($identify_fullpath==false) {exit("Could not find ImageMagick 'identify' utility at location '$imagemagick_path'.");}	
		# Get image's dimensions.
		$identcommand = $identify_fullpath . ' -format %wx%h '. escapeshellarg($prefix . $file) .'[0]';
		$identoutput=run_command($identcommand);
		preg_match('/^([0-9]+)x([0-9]+)$/ims',$identoutput,$smatches);
		@list(,$sw,$sh) = $smatches;
		if (($sw!='') && ($sh!=''))
			{
			if(!$o_size)
				{
				sql_query("insert into resource_dimensions (resource, width, height, file_size) values('{$ref_escaped}', '". escape_check($sw) ."', '". escape_check($sh) ."', '" . escape_check((int)$filesize) . "')");
				}
			else
				{
				sql_query("update resource_dimensions set width='". escape_check($sw) ."', height='". escape_check($sh) ."', file_size='" . escape_check($filesize) . "' where resource='{$ref_escaped}'");
				}
			}
		}	
	else 
		{
		# check if this is a raw file.	
		$rawfile = false;
		if (preg_match('/^(dng|nef|x3f|cr2|crw|mrw|orf|raf|dcr)$/i', $extension, $rawext)){$rawfile=true;}
			
		# Use GD to calculate the size
		if (!((@list($sw,$sh) = @getimagesize($file))===false)&& !$rawfile)
			{
			if(!$o_size)
				{	
				sql_query("insert into resource_dimensions (resource, width, height, file_size) values('{$ref_escaped}', '". escape_check($sw) ."', '". escape_check($sh) ."', '" . escape_check((int)$filesize) . "')");
				}
			else
				{
				sql_query("update resource_dimensions set width='". escape_check($sw) ."', height='". escape_check($sh) ."', file_size='" . escape_check($filesize) . "' where resource='{$ref_escaped}'");
				}
			}
		else
			{

			# Assume size cannot be calculated.
			$sw="?";$sh="?";

			global $ffmpeg_supported_extensions;
			if (in_array(strtolower($extension), $ffmpeg_supported_extensions) && function_exists('json_decode'))
			    {
			    $file=get_resource_path($ref,true,"",false,$extension);
			    $ffprobe_array=get_video_info($file);
                
			    # Different versions of ffprobe store the dimensions in different parts of the json output. Test both.
			    if (!empty($ffprobe_array['width'] )) { $sw = intval($ffprobe_array['width']);  }
			    if (!empty($ffprobe_array['height'])) { $sh = intval($ffprobe_array['height']); }
			    if (isset($ffprobe_array['streams']) && is_array($ffprobe_array['streams']))
					{
					foreach( $ffprobe_array['streams'] as $stream )
						{
						if (!empty($stream['codec_type']) && $stream['codec_type'] === 'video')
							{
							$sw = intval($stream['width']);
							$sh = intval($stream['height']);
							break;
							}
						}
					}
				}

			if ($sw!=='?' && $sh!=='?')
			    {
			    # Size could be calculated after all
			    if(!$o_size)
					{
					sql_query("insert into resource_dimensions (resource, width, height, file_size) values('{$ref_escaped}', '". escape_check($sw) ."', '". escape_check($sh) ."', '" . escape_check((int)$filesize) . "')");
					}
				else
					{
					sql_query("update resource_dimensions set width='". escape_check($sw) ."', height='". escape_check($sh) ."', file_size='" . escape_check($filesize) . "' where resource='{$ref_escaped}'");
					}
			    }
			else
			    {

			    # Size cannot be calculated.
			    $sw="?";$sh="?";
				if(!$o_size)
					{
					# Insert a dummy row to prevent recalculation on every view.
					sql_query("insert into resource_dimensions (resource, width, height, file_size) values('{$ref_escaped}','0', '0', '" . escape_check((int)$filesize) . "')");
					}
				else
					{
					sql_query("update resource_dimensions set width='0', height='0', file_size='" . escape_check($filesize) . "' where resource='{$ref_escaped}'");
					}
				}
			}
		}
		
		
		$fileinfo[0]=$filesize;
		$fileinfo[1]=$sw;
		$fileinfo[2]=$sh;
		return $fileinfo;
	
	}
        
function generate_resource_access_key($resource,$userref,$access,$expires,$email,$group="",$sharepwd="")
        {
        if(checkperm("noex"))
            {
            // Shouldn't ever happen, but catch in case not already checked
            return false;
            }
                
        global $userref,$usergroup, $scramble_key;
		if ($group=="" || !checkperm("x")) {$group=$usergroup;} # Default to sharing with the permission of the current usergroup if not specified OR no access to alternative group selection.
        $k=substr(md5(time()),0,10);
		sql_query("insert into external_access_keys(resource,access_key,user,access,expires,email,date,usergroup,password_hash) values ('$resource','$k','$userref','$access'," . (($expires=="")?"null":"'" . $expires . "'"). ",'" . escape_check($email) . "',now(),'$group'," . (($sharepwd != "" && $sharepwd != "(unchanged)") ? "'" . hash('sha256', $k . $sharepwd . $scramble_key) . "'": "null") . ");");
		hook("generate_resource_access_key","",array($resource,$k,$userref,$email,$access,$expires,$group));
        return $k;
        }

function get_resource_external_access($resource)
	{
	# Return all external access given to a resource 
    # Users, emails and dates could be multiple for a given access key, an in this case they are returned comma-separated.
    global $userref;

    # Restrict to only their shares unless they have the elevated 'v' permission
    $condition="";
    if (!checkperm("v")) {$condition="AND user='" . escape_check($userref) . "'";}
    
    return sql_query("select access_key,group_concat(DISTINCT user ORDER BY user SEPARATOR ', ') users,group_concat(DISTINCT email ORDER BY email SEPARATOR ', ') emails,max(date) maxdate,max(lastused) lastused,access,expires,collection,usergroup, password_hash from external_access_keys where resource='$resource' $condition group by access_key,access,expires,collection,usergroup order by maxdate");
	}

        
function delete_resource_access_key($resource,$access_key)
    {
    global $lang;
    sql_query("delete from external_access_keys where access_key='$access_key' and resource='$resource'");
    resource_log($resource,LOG_CODE_DELETED_ACCESS_KEY,'', '',str_replace('%access_key', $access_key, $lang['access_key_deleted']),'');
    }

function resource_type_config_override($resource_type)
    {
    # Pull in the necessary config for a given resource type
    # As this could be called many times, e.g. during search result display, only execute if the passed resourcetype is different from the previous.
    global $resource_type_config_override_last,$resource_type_config_override_snapshot, $ffmpeg_alternatives;

    # If the resource type has changed or if this is the first resource....
    if (!isset($resource_type_config_override_last) || $resource_type_config_override_last!=$resource_type)
        {
        # Look for config and execute.
        $config_options=sql_value("select config_options value from resource_type where ref='" . escape_check($resource_type) . "'","","schema");
        if ($config_options!="")
            {
            # Switch to global context and execute.
            extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
            eval($config_options);
            }
        $resource_type_config_override_last=$resource_type;
        }
    }

/**
* Update the archive state of resource(s) and log this
* 
* @param integer|array $resource_id - Resource unique ref -or- array of Resource refs
* @param integer $archive - Destination archive state
* @param integer|array $existingstates -  existing archive state _or_ array of corresponding existing archive states
* @param integer $collection - optional id of collection containing resources
* 
* @return void
*/
function update_archive_status($resource, $archive, $existingstates = array(), $collection  = 0)
    {
    global $userref, $user_resources_approved_email;

    if(!is_array($resource))
        {
        $resource = array($resource);
        }

    if(!is_array($existingstates))
        {
        $existingstates = array($existingstates);
        }

    $count = count($resource);

    for($n = 0; $n < $count; $n++)
        {
        if(!is_numeric($resource[$n]))
            {
            continue;
            }

        resource_log($resource[$n], LOG_CODE_STATUS_CHANGED, 0, '', isset($existingstates[$n]) ? $existingstates[$n] : '', $archive);    
        }

    # Prevent any attempt to update with non-numeric archive state
    if (!is_numeric($archive))
        {
        debug("update_archive_status FAILED - resources=(" . implode(",",$resource) . "), archive: " . $archive . ", existingstates:(" . implode(",",$existingstates) . "), collection: " . $collection);
        return;
        }

    sql_query("UPDATE resource SET archive = '" . escape_check($archive) .  "' WHERE ref IN ('" . implode("', '", $resource) . "')");
    hook('after_update_archive_status', '', array($resource, $archive,$existingstates));
    // Send notifications
    debug("update_archive_status - resources=(" . implode(",",$resource) . "), archive: " . $archive . ", existingstates:(" . implode(",",$existingstates) . "), collection: " . $collection);
    switch ($archive)
        {
        case '0':
            if (isset($existingstates[0]) && $existingstates[0] == -1 && $user_resources_approved_email)
                {
                notify_user_resources_approved($resource);
                # Clear any outstanding notifications relating to submission of these resources
                message_remove_related(SUBMITTED_RESOURCE,$resource);
                if($collection != 0)
                    {
                    message_remove_related(SUBMITTED_COLLECTION,$collection);
                    }
                }
            break;
        
        case '-1':
            if (isset($existingstates[0]) && $existingstates[0] == -2)
                {
                notify_user_contributed_submitted($resource, $collection);
                }
            break;
    
        case '-2':
            if (isset($existingstates[0]) && $existingstates[0] == -1)
                {
                notify_user_contributed_unsubmitted($resource);
                }
            # Clear any outstanding notifications relating to submission of these resources
            message_remove_related(SUBMITTED_RESOURCE,$resource);
            if($collection != 0)
                {
                message_remove_related(SUBMITTED_COLLECTION,$collection);
                }
            break;
        }
    
    return;
    }


function delete_resources_in_collection($collection) {

	global $resource_deletion_state,$userref,$lang;

	// Always find all resources in deleted state and delete them permanently:
	// Note: when resource_deletion_state is null it will find all resources in collection and delete them permanently
	$query = sprintf("
				SELECT ref AS value
				  FROM resource
			INNER JOIN collection_resource ON collection_resource.resource = resource.ref AND collection_resource.collection = '%s'
				 %s;
	",
		$collection,
		isset($resource_deletion_state) ? "WHERE archive = '" . $resource_deletion_state . "'" : ''
	);

	$resources_in_deleted_state = array();
	$resources_in_deleted_state = sql_array($query);

	if(!empty($resources_in_deleted_state)) {
		foreach ($resources_in_deleted_state as $resource_in_deleted_state) {
			delete_resource($resource_in_deleted_state);
		}
		collection_log($collection,'D', '', 'Resource ' . $resource_in_deleted_state . ' deleted permanently.');
	}
    

	// Create a comma separated list of all resources remaining in this collection:
	$resources = sql_query("SELECT cr.resource, r.archive FROM collection_resource cr LEFT JOIN resource r on r.ref=cr.resource WHERE cr.collection = '" . $collection . "';");
	$r_refs = array_column($resources,"resource");
    $r_states = array_column($resources,"archive");
	
	// If all resources had their state the same as resource_deletion_state, stop here:
	// Note: when resource_deletion_state is null it will always stop here
	if(empty($resources)) {
		return TRUE;
	}

	// Delete (ie. move to resource_deletion_state set in config):
	if(isset($resource_deletion_state))
        {
		update_archive_status($r_refs,$resource_deletion_state,$r_states);
		collection_log($collection,'D', '', str_replace("%ARCHIVE",$resource_deletion_state,$lang['log-deleted_all']));
		sql_query("DELETE FROM collection_resource  WHERE resource IN ('" . implode("','",$r_refs) . "')");
        }

	return TRUE;
    }
    
/**
 * Update related resources - add new related resource(s) or delete existing
 *
 * @param  int      $ref        ID of primary resource
 * @param  int|array  related   Resource ID or array of resource IDs to link to current resource
 * @param  boolean  $add        Add relationship? If false this will delete the specified relationships
 * 
 * @return boolean
 */
function update_related_resource($ref,$related,$add=true)
	{	
    if (!is_int_loose($ref) || (!is_int_loose($related) && !is_array($related)))
        {
        return false;
        }
    if(is_array($related))
        {
        $related = array_filter($related,"is_int_loose");
        }
    else
        {
        $related = array((int)$related);
        }

    // Check edit access
    $access = get_edit_access($ref);
    if(!$access)
        {
        return false;
        }
    foreach($related as $relate)
        {
        $access = get_edit_access($relate);
        if(!$access)
            {
            return false;
            }
        }
	$currentlyrelated=sql_query("SELECT resource, related 
                                   FROM resource_related 
                                  WHERE (resource='$ref' AND related IN ('" . implode("','",$related) . "'))
                                     OR (resource IN ('" . implode("','",$related) . "') AND related='$ref')");  
    
    // Create array of all related resources
    $currentlyrelated_arr = array_unique(array_merge(
        array_column($currentlyrelated,"related"),
        array_column($currentlyrelated,"resource")
        ));

    if(count($currentlyrelated_arr) > 0 && !$add)
		{
		// Relationships exist and we want to remove
		sql_query("DELETE FROM resource_related
                         WHERE (resource='$ref' AND related IN ('" . implode("','",$related) . "'))
                            OR (resource IN ('" . implode("','",$related) . "') AND related='$ref')");
		}
	else
		{
        $newrelated = array();
        foreach($related as $torelate)
            {
            if(!in_array($torelate, $currentlyrelated_arr) && $torelate != $ref)
                {
                $newrelated[] = $torelate;
                }
            }
        if(count($newrelated) > 0)
            {
		    sql_query("INSERT INTO resource_related (resource,related)
                            VALUES ('" . $ref . "','" . 
                                   implode("'),('" . $ref . "','",$newrelated) .
                                   "')");
            }
		}
	return true;
	}

/**
 * Check if sharing of resource is permitted
 *
 * @param  int  $ref      ID of resource
 * @param  int  $access   Level of resource access  (0 - Open  1 - Restricted  2 - Confidential)
 * 
 * @return boolean
 */
function can_share_resource($ref, $access="")
	{
	global $allow_share, $restricted_share, $customgroupaccess,$customuseraccess, $allow_custom_access_share;
	if($access=="" || !isset($customgroupaccess)){$access=get_resource_access($ref);}
	
	if(!$allow_share || $access==2 || ($access==1 && !$restricted_share))
		{return false;} // return false asap
	
	if ($restricted_share){return true;} // If sharing of restricted resources is permitted we should allow sharing whether access is open or restricted
	
	// User is not permitted to share if open access has been specifically granted for an otherwise restrcited resource to the user/group.	
	if(!$allow_custom_access_share && ($customgroupaccess || $customuseraccess)){return false;} 
	
	// Must have open access and sharing is permitted
	return true;	
	}

/**
* Delete all usergroup specific access to resource $ref
*
* @param  int  $ref   ID of resource
* 
*/
function delete_resource_custom_access_usergroups($ref)
        {
        sql_query("delete from resource_custom_access where resource='" . escape_check($ref) . "' and usergroup is not null");
        }

/**
* Truncate the field for insertion into the main resource table field
* 
* @param string $value
* 
* @return string
*/
function truncate_join_field_value($value)
    {
    global $resource_field_column_limit, $server_charset;

    $encoding = 'UTF-8';

    if(isset($server_charset) && '' != $server_charset)
        {
        $encoding = $server_charset;
        }

    $truncated_value = mb_substr($value, 0, $resource_field_column_limit, $encoding);

    if($resource_field_column_limit >= strlen($truncated_value))
        {
        return $truncated_value;
        }

    $more_limit = $resource_field_column_limit;
    while($resource_field_column_limit < strlen($truncated_value))
        {
        $truncated_value = mb_substr($value, 0, --$more_limit, $encoding);
        }

    return $truncated_value;
    }


/**
* Check whether a resource (of a video type) has any snapshots created.
* Snapshots are being created using config option $ffmpeg_snapshot_frames
* 
* @uses get_resource_path()
* 
* @global array $get_resource_path_extra_download_query_string_params Array of query string params
*                                                                     as expected by generateURL()
* 
* @param integer $resource_id Resource unique ref
* @param boolean $file_path   Specify whether the return value should be the file path. Default is FALSE
* @param boolean $count_only  Set to true if we are only interested in how many snapshots we have. Default is FALSE
* 
* @return array|integer Array of all file paths found or number of files found
*/
function get_video_snapshots($resource_id, $file_path = false, $count_only = false)
    {
    global $get_resource_path_extra_download_query_string_params, $hide_real_filepath;

    $snapshots_found = array();
 
    $template_path            = get_resource_path($resource_id, true,  'snapshot', false, 'jpg', -1, 1, false, '');
    $template_webpath         = get_resource_path($resource_id, false, 'snapshot', false, 'jpg', -1, 1, false, '');

    $i = 1;
    do
        {
	$path=str_replace("snapshot","snapshot_" . $i,$template_path);
	if($hide_real_filepath){
		$webpath=$template_webpath . "&snapshot_frame=" . $i;
	}
	else{
		$webpath=str_replace("snapshot","snapshot_" . $i,$template_webpath);
	}

        $snapshot_found  = file_exists($path);

        if($snapshot_found)
            {
            $snapshots_found[$i] = ($file_path ? $path : $webpath);
            }

        $i++;
        }
    while(true === $snapshot_found);

    return (!$count_only ? $snapshots_found : count($snapshots_found));
    }

function resource_file_readonly($ref)
    {
    # Even if the user has edit access to a resource, the main file may be read only.
    global $fstemplate_alt_threshold;
    return ($fstemplate_alt_threshold>0 && $ref<$fstemplate_alt_threshold);
    }
	
function delete_resource_custom_user_access($resource,$user)
    {
    sql_query("delete from resource_custom_access where resource='$resource' and user='$user'");
    }

    
function get_video_info($file)
    {
    $ffprobe_fullpath = get_utility_path("ffprobe");
    $ffprobe_output=run_command($ffprobe_fullpath . " -v 0 " . escapeshellarg($file) . " -show_streams -of json");
    $ffprobe_array=json_decode($ffprobe_output, true);
    return ($ffprobe_array);
    }


/**
* Provides the ability to copy any metadata field data from one resource to another.
* 
* @param integer $from Resource we are copying data from
* @param integer $to   The Resource ID that needs updating
* 
* @return boolean
*/
function copyAllDataToResource($from, $to, $resourcedata = false)
    {
    if((int)(string)$from !== (int)$from || (int)(string)$to !== (int)$to)
        {
        return false;
        }
        
    if(!$resourcedata)
        {
        $resourcedata = get_resource_data($to);
        }
        
    if(!get_edit_access($to,$resourcedata["archive"],false,$resourcedata))
        {
        return false;
        }
        
    copyResourceDataValues($from, $to);
    copy_resource_nodes($from, $to);
    
    # Update 'joined' fields in resource table 
    $joins=get_resource_table_joins();
    $joinsql = "UPDATE resource AS target LEFT JOIN resource AS source ON source.ref='{$from}' SET ";
    $joinfields = "";
    foreach($joins as $joinfield)
        {
        if($joinfields != "")
            {
            $joinfields .= ",";
            }
        $joinfields .= "target.field{$joinfield} = source.field{$joinfield}";
        
        }
    $joinsql = $joinsql . $joinfields . " WHERE target.ref='{$to}'";
    sql_query($joinsql);
    return true;
    }


/**
* Copy resource data from one resource to another one.
* 
* @uses escape_check()
* @uses sql_array()
* @uses sql_query()
* 
* @param integer $from Resource we are copying data from
* @param integer $ref  Resource we are copying data to
* 
* @return void
*/    
function copyResourceDataValues($from, $to, $resource_type = "")
    {
    $from            = escape_check($from);    
    $to              = escape_check($to);
    $omit_fields_sql = '';

    // When copying normal resources from one to another, check for fields that should be excluded
    // NOTE: this does not apply to user template resources (negative ID resource)
    if($from > 0)
        {
        $omitfields      = sql_array("SELECT ref AS `value` FROM resource_type_field WHERE omit_when_copying = 1", "schema");
        $omit_fields_sql = "AND rd.resource_type_field NOT IN ('" . implode("','", $omitfields) . "')";
        }
    
    $resource_type_sql = "AND (rtf.resource_type = r.resource_type OR rtf.resource_type = 999 OR rtf.resource_type = 0)";
    // Don't consider resource types if saving metadata template as fields from all types should be copied.
    global $metadata_template_resource_type;
    if (isset($metadata_template_resource_type) && $resource_type==$metadata_template_resource_type)
        {
        $resource_type_sql = "";
        }

    sql_query("
        INSERT INTO resource_data(resource, resource_type_field, value)
             SELECT '{$to}',
                    rd.resource_type_field,
                    rd.value
               FROM resource_data AS rd
               JOIN resource AS r ON rd.resource = r.ref
               JOIN resource_type_field AS rtf ON rd.resource_type_field = rtf.ref
               {$resource_type_sql}
              WHERE rd.resource = '{$from}'
                {$omit_fields_sql}
    ");

    return;
    }
    
/**
* Update resource data for 'locked' fields from last edited resource. Used for upload_then_edit
* 
* @uses get_resource_data()
* @uses update_resource_type()
* @uses update_archive_status()
* @uses resource_log()
* @uses checkperm()
* @uses escape_check()
* @uses sql_query()
* @uses checkperm()
* 
* @param array $resource - existing resource data
* @param array $locked_fields - array of locked data columns (may also include field ids which are handled by copy_locked_fields) 
* @param integer $lastedited   - last edited resource to copy data from
* @param boolean $save - if true, save data to database (as opposed to just updating the $resource array e.g. for edit page)
* 
* @return array $resource - modified resource data array 
*/ 
function copy_locked_data($resource, $locked_fields, $lastedited, $save=false)
    {
    global $custom_access;
    
    debug("copy_locked_data resource " . $resource["ref"] . " lastedited: " . $lastedited);
    
    // Get details of the last resource edited and use these for this resource if field is 'locked'
    $lastresource = get_resource_data($lastedited,false);
    $lockable_columns = array("resource_type","archive","access");
    
    if(in_array("resource_type",$locked_fields) && $resource["resource_type"] != $lastresource["resource_type"])
        {
        $resource["resource_type"] = $lastresource["resource_type"];
        if ($save && !checkperm("XU" . $lastresource["resource_type"]))
            {
            update_resource_type($resource["ref"],$lastresource["resource_type"]);   
            }
        }
    
    if(in_array("archive",$locked_fields) && $resource["archive"] != $lastresource["archive"])
        {
        $resource["archive"] = $lastresource["archive"];
        if ($save && checkperm("e" . $lastresource["archive"]))
            {
            update_archive_status($resource["ref"],$lastresource["archive"],$resource["archive"]);
            }
        }
        
    if(in_array("access",$locked_fields) && $resource["access"] != $lastresource["access"])
        {
        $newaccess = $lastresource["access"];
        if ($save)
            {
            $ea[0]=!checkperm('ea0');
            $ea[1]=!checkperm('ea1');
            $ea[2]=checkperm("v")?(!checkperm('ea2')?true:false):false;
            $ea[3]=$custom_access?!checkperm('ea3'):false;
            if($ea[$newaccess])
                {
                sql_query("update resource set access='" . $newaccess . "' where ref=' " . $resource["ref"] . "'");
				
                if ($newaccess==3)
                        {
                        # Copy custom access
                        sql_query("insert into resource_custom_access (resource,usergroup,user,access) select '" . $resource["ref"] . "', usergroup,user,access from resource_custom_access where resource = '" . $lastresource["ref"] . "'");
		                }
				resource_log($resource["ref"],LOG_CODE_ACCESS_CHANGED,0,"",$resource["access"],$newaccess);
				}
			}
        $resource["access"] = $newaccess;
        }
        
    return $resource;
    }
    
/**
* Update resource metadata for 'locked' fields from last edited resource.
* NB: $fields and $all_selected_nodes are passed by reference
* 
* @uses get_resource_type_field()
* @uses get_resource_nodes() 
* @uses add_resource_nodes()
* @uses delete_resource_nodes()* 
* @uses get_resource_field_data()
* @uses update_field()
* @uses escape_check()
* @uses sql_query()
* 
* @param integer $ref - resource id being updated
* @param array $fields - resource $fields array
* @param array $all_selected_nodes - array of existing resource nodes
* @param array $locked_fields - array of locked data columns (may also include  resource table columns  - handled by copy_locked_data) 
* @param integer $lastedited   - last edited resource to copy data from
* @param boolean $save - save data to database (as opposed to just updating the $fields array e.g. for edit page)
* 
* @return void
*/     
function copy_locked_fields($ref, &$fields,&$all_selected_nodes,$locked_fields,$lastedited, $save=false)
    {
    debug("copy_locked_fields resource " . $ref . " lastedited: " . $lastedited);
    global $FIXED_LIST_FIELD_TYPES, $tabs_on_edit;
    foreach($locked_fields as $locked_field)
            {
            if(!is_numeric($locked_field))
                {
                // These are handled by copy_locked_data
                continue;
                }
            
            // Check if this field is listed in the $fields array - if resource type has changed it may not be present
            $key = array_search($locked_field, array_column($fields, 'ref'));
            if($key!==false)
                {
                $fieldtype = $fields[$key]["type"];
                }    
            else
                {
                $lockfieldinfo = get_resource_type_field($locked_field);
                $fieldtype = $lockfieldinfo["type"];
                }                
            
            if(in_array($fieldtype, $FIXED_LIST_FIELD_TYPES))
                {
                // Replace nodes for this field
                $field_nodes = get_nodes($locked_field, NULL, $fieldtype == FIELD_TYPE_CATEGORY_TREE);
                $field_node_refs = array_column($field_nodes,"ref");
                $stripped_nodes = array_diff ($all_selected_nodes, $field_node_refs);
                $locked_nodes = get_resource_nodes($lastedited, $locked_field);
                $all_selected_nodes = array_merge($stripped_nodes, $locked_nodes);

                if($save)
                    {
                    debug("- adding locked field nodes for resource " . $ref . ", field id: " . $locked_field);
                    delete_resource_nodes($ref,$field_node_refs);
                    if(count($locked_nodes) > 0)
                        {
                        add_resource_nodes($ref, $locked_nodes, false);
                        }

                    # If this is a 'joined' field it still needs to add it to the resource column
                    $joins=get_resource_table_joins();
                    if (in_array($locked_field,$joins))
                        {
                        $node_vals = array();
                        // Build new value:
                        foreach($locked_nodes as $locked_node)
                            {
                            foreach ($field_nodes as $key => $val) 
                                {
                                if ($val['ref'] === $locked_node) 
                                    {
                                    array_push($node_vals, $field_nodes[$key]["name"]);
                                    }
                                }
                            $resource_type_field=$field_nodes[$key]["resource_type_field"];
                            $values_string = implode(",",$node_vals);
                            sql_query("update resource set field".$resource_type_field."='".escape_check(truncate_join_field_value(strip_leading_comma($values_string)))."' where ref='".escape_check($ref)."'");
                            }
                        } 
                    }
                }
            else
                {
                debug(" - checking field values for last resource " . $lastedited . " field id: " . $locked_field);
                if(!isset($last_fields))
                    {
                    $last_fields = get_resource_field_data($lastedited,!hook("customgetresourceperms"),NULL,"",$tabs_on_edit);
                    }
                
                $addkey = array_search($locked_field, array_column($last_fields, 'ref'));
                if($key!==false)
                    {
                    // Field is already present - just update the value
                debug(" - updating field value for resource " . $lastedited . " field id: " . $locked_field);
                    $fields[$key]["value"] = $last_fields[$addkey]["value"];
                    }
                else
                    {
                    // Add the field to the $fields array   
                debug(" - adding field value for resource " . $lastedited . " field id:" . $locked_field);
                    $fields[] = $last_fields[$addkey];
                    }
                if($save)
                    {
                    debug("- adding locked field value for resource " . $ref . ", field id: " . $locked_field);
                    update_field($ref,$locked_field,$last_fields[$addkey]["value"]);
                    }
                }
            }
    }

/**
* Copy  related resources from one resource to another
* 
* @uses sql_query()
* 
* @param integer $from Resource we are copying related resources from
* @param integer $ref  Resource we are copying related resources to
* 
* @return void
*/    
function copyRelatedResources($from, $to)
    {
	sql_query("insert into resource_related(resource,related) SELECT '$to',related FROM resource_related WHERE resource='$from' AND related <> '$to'");
    }

    
function process_edit_form($ref, $resource)
	{
    global $multiple, $lang, $embedded_data_user_select, $embedded_data_user_select_fields, $data_only_resource_types,
    $check_edit_checksums, $uploadparams, $resource_type_force_selection, $relate_on_upload, $enable_related_resources, 
    $is_template, $upload_collection_name_required, $upload_review_mode, $userref, $userref, $collection_add, $baseurl_short,
    $no_exif, $autorotate;

	# save data
    # When auto saving, pass forward the field so only this is saved.
    $autosave_field=getvalescaped("autosave_field","");
     
    # Upload template: Change resource type
    $resource_type=getvalescaped("resource_type","");
    if ($resource_type!="" && $resource_type!=$resource["resource_type"] && !checkperm("XU{$resource_type}") && $autosave_field=="")     // only if resource type specified and user has permission for that resource type
        {
        // Check if resource type has been changed between form being loaded and submitted				
        $post_cs = getval("resource_type_checksum","");
        $current_cs = $resource["resource_type"];			
        if($check_edit_checksums && $post_cs != "" && $post_cs != $current_cs)
            {
            $save_errors = array("resource_type"=>$lang["resourcetype"] . ": " . $lang["save-conflict-error"]);
            }
        else
            {
            update_resource_type($ref,$resource_type);
            }
        }   	
    $resource=get_resource_data($ref,false); # Reload resource data.
   
    if(in_array($resource['resource_type'], $data_only_resource_types))
        {
        $single=true;
        }
    else
        {
        unset($uploadparams['forcesingle']);
        unset($uploadparams['noupload']);
        }

    if(!isset($save_errors))
        {
        # Perform the save
        $save_errors=save_resource_data($ref,$multiple,$autosave_field);
        }

    if($relate_on_upload && $enable_related_resources && getval("relateonupload", "") != "")
        {
        $uploadparams['relateonupload'] = 'yes';
        }

    if($ref < 0 && $resource_type_force_selection && $resource_type=="")
        {
        if (!is_array($save_errors)){$save_errors=array();} 
        $save_errors['resource_type'] = $lang["resourcetype"] . ": " . $lang["requiredfield"];
        }
      
    if ($upload_collection_name_required)
        {
        if (getvalescaped("entercolname","")=="" && getval("collection_add","")=="new")
              { 
              if (!is_array($save_errors)){$save_errors=array();} 
              $save_errors['collectionname'] = $lang["collectionname"] . ": " .$lang["requiredfield"];
              }
       }

    return $save_errors;
  }

/*
* Update the modified column in the resource table
*  
* @param integer $resource   	Resource to be updated
* 
* @return void
*/	
function update_timestamp($resource)
    {
    if(!is_numeric($resource))
        {
        return false;
        }
    sql_query("UPDATE resource SET modified=NOW() WHERE ref='" . $resource . "'");
    }    

/**
* Get resource file extension from the database or use JPG, for download
* 
* @uses hook()
* 
* @param array  $resource
* @param string $size      Preview size as defined in the system
* 
* @return string
*/
function get_extension(array $resource, $size)
    {
    global $job_ext;
    if($size == '')
        {
        $size = 'original';
        }

    // Offline collection download job may have requested a specific file extension
    $pextension = $size == 'original' ? $resource['file_extension'] : ((isset($job_ext) && trim($job_ext) != "") ? $job_ext : 'jpg');

    $replace_extension = hook('replacedownloadextension', '', array($resource, $pextension));
    if(trim($replace_extension) !== '')
        {
        return $replace_extension;
        }

    return $pextension;
    }


   
/**
* Obtain details of the last resource edited in the given array of resource ids
*
* @param array $resources   Array of resource IDs
*
* @return array | false     Array containing details of last edit (resource ID, timestamp and username of user who performed edit)
*/    
function get_last_resource_edit_array($resources = array())
    {
    if(count($resources) == 0)
        {
        return false;
        }

    $plugin_last_resource_edit = hook('override_last_resource_edit_array');
    if($plugin_last_resource_edit === true)
        {
    	return false;
        }
        
    $lastmodified  = sql_query("SELECT r.ref, r.modified FROM resource r WHERE r.ref IN ('" . implode("','",$resources). "') ORDER BY r.modified DESC");
    $lastuserdetails = sql_query("SELECT u.username, u.fullname, rl.date FROM resource_log rl LEFT JOIN user u on u.ref=rl.user WHERE rl.resource ='" . $lastmodified[0]["ref"] . "' AND rl.type='e'");
    if(count($lastuserdetails) == 0)
        {
        return false;
        }
        
    $timestamp = max($lastuserdetails[0]["date"],$lastmodified[0]["modified"]);
        
    $lastusername = (trim($lastuserdetails[0]["fullname"]) != "") ? $lastuserdetails[0]["fullname"] : $lastuserdetails[0]["username"];
    return array("ref" => $lastmodified[0]["ref"],"time" => $timestamp, "user" => $lastusername);
    }
   
/**
* Get the default archive state for new resources 
*
* @param integer    $requestedstate     (optional) ID of requested archive state
*
* @return integer   ID of valid user requested archive state, may differ from that requested
*/    
function get_default_archive_state($requestedstate = "")
    {
    global $override_status_default;

    if ((string)(int)$requestedstate == (string)$requestedstate && checkperm("e" . $requestedstate))
        {
        return $requestedstate;
        }
    
    $modified_defaultstatus = hook("modifydefaultstatusmode");
    if ($modified_defaultstatus !== false)
        {
        # Set the modified default status
        return $modified_defaultstatus;
        }
    elseif ($override_status_default !== false)
        {
        # Set the default status if set in config.
        return $override_status_default;
        }
    elseif (checkperm("c") && checkperm("e0"))
        {
        # Set status to Active
        return 0;
        }
    elseif (checkperm("d") && !checkperm('e-2') && checkperm('e-1'))
        {
        # Set status to 'pending review' if the user has only edit access to Pending review
        return -1;
        }
    else
        {
        return -2;
        }
     }



/**
* Save the original file being replaced, as an alternative file 
*
* @param integer    $ref      (required) ID of original resource
* @return boolean             true = file saved successfully; false = file not saved
*/    

function save_original_file_as_alternative($ref)
    {
    debug("save_original_file function called for resource ref: " . (int)$ref);
    if (!$ref)
        {
        debug("ERROR: Unable to save original file as alternative - no resource id passed");
        return false;
        }

    /*
    global vars
    * @param boolean $alternative_file_previews                  Generate thumbs/previews for alternative files?
    * @param boolean $alternative_file_previews_batch            Generate thumbs/previews for alternative files?
    * @param array   $lang 
    */

    global $lang, $alternative_file_previews, $alternative_file_previews_batch, $filename_field;

    // Values may be passed in POST or GET data from upload_plupload.php
    $replace_resource_original_alt_filename = getvalescaped('replace_resource_original_alt_filename', ''); // alternative filename
    $filename_field_use                     = getval('filename_field', $filename_field); // GET variable - field to use for filename

    // Make the original into an alternative, need resource data so we can get filepath/extension
    $origdata     = get_resource_data($ref);
    $origfilename = get_data_by_field($ref, $filename_field_use);

    $newaltname        = str_replace('%EXTENSION', strtoupper($origdata['file_extension']), $lang['replace_resource_original_description']);
    $newaltdescription = nicedate(date('Y-m-d H:i'), true);

    if('' != $replace_resource_original_alt_filename)
        {
        $newaltname = $replace_resource_original_alt_filename;
        }        

    $newaref = add_alternative_file($ref, $newaltname, $newaltdescription, escape_check($origfilename), $origdata['file_extension'], $origdata['file_size']);

    $origpath=get_resource_path($ref, true, "", true, $origdata["file_extension"]);
    $newaltpath=get_resource_path($ref, true, "", true, $origdata["file_extension"], -1, 1, false, "", $newaref);
    # Move the old file to the alternative file location
    $result=rename($origpath, $newaltpath);								

    if ($alternative_file_previews)
        {
        // Move the old previews to new paths
        $ps=sql_query("select * from preview_size");
        for ($n=0;$n<count($ps);$n++)
            {
            # Find the original 
            $orig_preview_path=get_resource_path($ref, true, $ps[$n]["id"],false, "");
            if (file_exists($orig_preview_path))
                {
                # Copy the old preview file to the alternative preview file location, not moved as original may still be required
                $alt_preview_path=get_resource_path($ref, true, $ps[$n]["id"], true, "", -1, 1, false, "", $newaref);
                copy($orig_preview_path, $alt_preview_path);			
                }
            # Also for the watermarked versions.
            $wmpath=get_resource_path($ref,true,$ps[$n]["id"],false,"jpg",-1,1,true );
            if (file_exists($wmpath))
                {
                # Move the old preview file to the alternative preview file location
                $alt_preview_wmpath=get_resource_path($ref, true, $ps[$n]["id"], true, "", -1, 1, true, "", $newaref);
                copy($wmpath, $alt_preview_wmpath);			
                }
            }
        }
    debug("save_original_file_as_alternative() completed");
    return true;
    }


/**
* Replace the primary resource file with the file located at the path specified
*
* @param integer    $ref    Resource ID to replace
*
* @return boolean
*/

function replace_resource_file($ref, $file_location, $no_exif=false, $autorotate=false, $keep_original=true)
    {
    global $replace_resource_preserve_option, $notify_on_resource_change_days, $lang, $userref;
    debug("replace_resource_file(ref=" . $ref . ", file_location=" . $file_location . ", no_exif=" . ($no_exif ? "TRUE" : "FALSE") . " , keep_original=" . ($keep_original ? "TRUE" : "FALSE"));
    
    $resource = get_resource_data($ref);
    if (!get_edit_access($ref,$resource["archive"],false,$resource)
        ||
        ($resource["lock_user"] > 0 && $resource["lock_user"] != $userref)
        )
        {
        return false;
        }

    // save original file as an alternative file
    if($replace_resource_preserve_option && $keep_original)
        {
        $savedasalt = save_original_file_as_alternative($ref); 
        if(!$savedasalt) 
            {
            return false;
            }
        }

    if (filter_var($file_location, FILTER_VALIDATE_URL))
        {
        $uploadstatus = upload_file_by_url($ref,$no_exif,false,$autorotate,$file_location);
        if(!$uploadstatus)
            {
            debug("replace_resource_file - upload_file_by_url() failed");
            return false;
            }
        }
    else
        {
        $uploadstatus = upload_file($ref,$no_exif,false,$autorotate,$file_location,false,false);
        if(!$uploadstatus)
            {
            debug("replace_resource_file - upload_file() failed");
            return false;
            }
        }

    resource_log($ref,LOG_CODE_REPLACED,'','','');
    daily_stat('Resource upload', $ref);
    hook("additional_replace_existing");        
						
    if($notify_on_resource_change_days != 0)
        {								
        // we don't need to wait for this.
        ob_flush();flush();	
        notify_resource_change($ref);
        }

    return true;
    }

/**
* Return all sizes available for a specific resource. Multi page resources should have each page size included as well 
* in the output.
* 
* @uses get_resource_access()
* @uses get_resource_data()
* @uses get_image_sizes()
* @uses get_page_count()
* @uses get_resource_path()
* 
* @param integer $ref Resource ID
* 
* @return array
*/
function get_resource_all_image_sizes($ref)
    {
    if(get_resource_access($ref) !== 0)
        {
        return array();
        }

    $resource_data = get_resource_data($ref, true);
    if($resource_data["file_extension"] == "" || $resource_data["preview_extension"] == "")
        {
        return array();
        }

    $extensions = array($resource_data["file_extension"], $resource_data["preview_extension"]);
    $all_image_sizes = array();

    foreach($extensions as $extension)
        {
        $available_sizes_by_extension = get_image_sizes($ref, true, $extension, true);

        foreach($available_sizes_by_extension as $size_data)
            {
            $size_id = trim($size_data["id"]) === "" ? "original" : $size_data["id"];

            if(array_key_exists($size_id, $all_image_sizes))
                {
                continue;
                }

            $key = "{$size_id}_{$size_data["extension"]}";
            $all_image_sizes[$key]["size_code"] = $size_id;
            $all_image_sizes[$key]["extension"] = $size_data["extension"];
            $all_image_sizes[$key]["path"] = $size_data["path"];
            $all_image_sizes[$key]["url"] = $size_data["url"];

            // Screen size can have multi page previews so if this is one of those cases, get rest of the pages before 
            // moving on to the next available size
            if($size_id == "scr" && ($page_count = get_page_count($resource_data)) && $page_count > 1)
                {
                // First page is always the normal scr size preview, so just tag it as such.
                $all_image_sizes[$key]["multi_page"] = true;
                $all_image_sizes[$key]["page"] = 1;

                for($page = 2; $page <= $page_count; $page++)
                    {
                    $path = get_resource_path($ref, true, "scr", false, $extension, true, $page);
                    if(!file_exists($path))
                        {
                        continue;
                        }

                    $url = get_resource_path($ref, false, "scr", false, $extension, true, $page);

                    $key = "{$size_id}_{$size_data["extension"]}_{$page}";
                    $all_image_sizes[$key]["size_code"] = $size_id;
                    $all_image_sizes[$key]["extension"] = $size_data["extension"];
                    $all_image_sizes[$key]["multi_page"] = true;
                    $all_image_sizes[$key]["page"] = $page;
                    $all_image_sizes[$key]["path"] = $path;
                    $all_image_sizes[$key]["url"] = $url;
                    }
                }
            }
        }

    return array_values($all_image_sizes);
    }

function sanitize_date_field_input($date, $validate=false)
    {
    $year   = sprintf("%04d", getvalescaped("field_" . $date . "-y",""));
    $month  = getval("field_" . $date . "-m","");
    $day    = getval("field_" . $date . "-d","");
    $hour   = getval("field_" . $date . "-h","");
    $minute = getval("field_" . $date . "-i","");
    
    // Construct value, replacing missing parts with placeholders
    $val  = ($year != "" && $year != "0000") ? $year : "year";
    $val .= "-" . ($month != "" ? $month : "month");
    $val .= "-" . ($day != "" ? $day : "day");
    $val .= " " . ($hour != "" ? $hour : "hh");
    $val .= ":" . ($minute != "" ? $minute : "mm");
    if($validate) 
        {
        # Format dates for the date validator e.g. 2020, 2020-month-29 by stripping unused placeholders
        $removedates = array("year-month-day","-month-day","-day"," hh:mm");
        $val = str_replace($removedates,"",$val);
        }
    else
        {
        # Format for database entry e.g. 2020-00-00, 2020-00-29, if nothing is set replace with a null string
        $removedates = array("year-month-day hh:mm","year","month","day"," hh:mm","hh","mm");
        $subdates = array("","0000","00","00","","00","");
        $val = str_replace($removedates,$subdates,$val);
        }

    return $val;
    }

/**
* Create a temporary download key for a specific user or key and resource combination
* Used when both $watermark_open and $terms_download are enabled 
*
* @param string $id                 Key identifier e.g. user ID or external access key
* @param integer $resource          Resource ID
* 
* @return string
*/
function download_link_generate_key($id,$resource)
    {
    global $scramble_key, $usersession;
    $remote_ip = get_ip();
    return $id . ":" . hash('sha256',$id . $usersession . $scramble_key . $resource . $remote_ip);
    }

/**
* Check the download key for a specific user/resource combination
* 
* @param string  $download_key      Download key
* @param integer $resource          Resource ID
* 
* @return string
*/
function download_link_check_key($download_key, $resource)
    {
    $download_link_parts = explode(":", $download_key);

    if(count($download_link_parts) != 2)
        {
        return false;
        }
    
    $download_link_id   = $download_link_parts[0];
    $keycheck = download_link_generate_key($download_link_id,$resource);
    if($keycheck != $download_key)
        {
        return false;
        }

    return true;
    }

/**
* Check if a given set of nodes meets the conditions set for the provided filter
* NOte that all resource_nodes for a resource should be passed to check if a filter is matched
*  
* @param integer    $ref        Filter ID
* @param array      $nodes      Array of nodes
* 
* @return boolean
*/
function filter_check($filterid,$nodes)
    {
    $filterdata         = get_filter($filterid);
    $filterrules        = get_filter_rules($filterid);
    $filtercondition    = $filterdata["filter_condition"];

    // Used for RS_FILTER_ALL type
    $filtersfailed  = 0;
    $filtersok      = 0;

    foreach($filterrules as $filterrule)
        {
        // Check if any nodes are present that shouldn't be, or nodes not present that need to be 
        $badnodes   = array_intersect($filterrule["nodes_off"],$nodes);
        $goodnodes  = array_intersect($filterrule["nodes_on"],$nodes); 
        $rulemet    = count($badnodes) == 0 && (count($filterrule["nodes_on"]) == 0 || count($goodnodes) > 0);
        // Can return now if filter successfully matched and RS_FILTER_ANY or RS_FILTER_NONE,
        // or if filter not matched and RS_FILTER_ALL
        if($rulemet)
            {
            if($filtercondition == RS_FILTER_ANY)
                {
                return true;
                }
            elseif($filtercondition == RS_FILTER_NONE)
                {
                return false;
                }
            $filtersok++;
            }
        else
            {
            if($filtercondition == RS_FILTER_ALL)
                {
                return false;
                }            
            $filtersfailed++;
            }
        // Need to check subsequent rules if RS_FILTER_ALL and filter rule met        
        }
        
    if($filtercondition == RS_FILTER_ALL && $filtersfailed == 0 && $filtersok == count($filterrules))
        {
        return true;
        }

    return false;
    }


function update_resource_keyword_hitcount($resource,$search)
    {
    # For the specified $resource, increment the hitcount for each matching keyword in $search
    # This is done into a temporary column first (new_hit_count) so existing results are not affected.
    # copy_hitcount_to_live() is then executed at a set interval to make this data live.
    $keywords=split_keywords($search);
    $keys=array();
    for ($n=0;$n<count($keywords);$n++)
        {
        $keyword=$keywords[$n];
        if (strpos($keyword,":")!==false)
            {
            $k=explode(":",$keyword);
            $keyword=$k[1];
            }
        $found=resolve_keyword($keyword);
        if ($found!==false) {$keys[]=resolve_keyword($keyword);}
        }   
    if (count($keys)>0)
        {
        // Get all nodes matching these keywords
        $nodes = get_nodes_from_keywords($keys);
        update_resource_node_hitcount($resource,$nodes);
        sql_query("update resource_keyword set new_hit_count=new_hit_count+1 where resource='$resource' and keyword in (" . join(",",$keys) . ")",false,-1,true,0);
        }
    }
        
function copy_hitcount_to_live()
    {
    # Copy the temporary hit count used for relevance matching to the live column so it's activated (see comment for
    # update_resource_keyword_hitcount())
    sql_query("update resource_keyword set hit_count=new_hit_count");
    
    # Also update the resource table
    # greatest() is used so the value is taken from the hit_count column in the event that new_hit_count is zero to support installations that did not previously have a new_hit_count column (i.e. upgrade compatability)
    sql_query("update resource set hit_count=greatest(hit_count,new_hit_count)");
    
    # Also now update resource_node_hitcount())
    sql_query("update resource_node set hit_count=new_hit_count");
    }

/**
 * Returns a table of available image sizes for resource $ref. The standard image sizes are translated using $lang. Custom image sizes are i18n translated.
 * The original image file assumes the name of the 'nearest size (up)' in the table
 *
 * @param  int      $ref            ID of resource
 * @param  boolean  $internal       
 * @param  string   $extension      File extension of image
 * @param  boolean  $onlyifexists
 * 
 * @return array    $return
 */
function get_image_sizes($ref,$internal=false,$extension="jpg",$onlyifexists=true)
    {
    global $imagemagick_calculate_sizes;

    # Work out resource type
    $resource_type=sql_value("select resource_type value from resource where ref='$ref'","");

    # add the original image
    $return=array();
    $lastname=sql_value("select name value from preview_size where width=(select max(width) from preview_size)",""); # Start with the highest resolution.
    $lastpreview=0;$lastrestricted=0;
    $path2=get_resource_path($ref,true,'',false,$extension);

    if (file_exists($path2) && !checkperm("T" . $resource_type . "_"))
    { 
        $returnline=array();
        $returnline["name"]=lang_or_i18n_get_translated($lastname, "imagesize-");
        $returnline["allow_preview"]=$lastpreview;
        $returnline["allow_restricted"]=$lastrestricted;
        $returnline["path"]=$path2;
        $returnline["url"] = get_resource_path($ref, false, "", false, $extension);
        $returnline["id"]="";
        $dimensions = sql_query("select width,height,file_size,resolution,unit from resource_dimensions where resource='" . escape_check($ref) . "'");
        
        if (count($dimensions))
            {
            $sw = $dimensions[0]['width']; if ($sw==0) {$sw="?";}
            $sh = $dimensions[0]['height']; if ($sh==0) {$sh="?";}
            $filesize=$dimensions[0]['file_size'];
            # resolution and unit are not necessarily available, set to empty string if so.
            $resolution = ($dimensions[0]['resolution'])?$dimensions[0]['resolution']:"";
            $unit = ($dimensions[0]['unit'])?$dimensions[0]['unit']:"";
            }
        else
            {
            $fileinfo=get_original_imagesize($ref,$path2,$extension);
            $filesize = $fileinfo[0];
            $sw = $fileinfo[1];
            $sh = $fileinfo[2];
            }
        if (!is_numeric($filesize)) {$returnline["filesize"]="?";$returnline["filedown"]="?";}
        else {$returnline["filedown"]=ceil($filesize/50000) . " seconds @ broadband";$returnline["filesize"]=formatfilesize($filesize);}
        $returnline["width"]=$sw;           
        $returnline["height"]=$sh;
        $returnline["extension"]=$extension;
        (isset($resolution))?$returnline["resolution"]=$resolution:$returnline["resolution"]="";
        (isset($unit))?$returnline["unit"]=$unit:$returnline["unit"]="";
        $return[]=$returnline;
    }
    # loop through all image sizes
    $sizes=sql_query("select * from preview_size order by width desc");
    
    for ($n=0;$n<count($sizes);$n++)
        {
        $path=get_resource_path($ref,true,$sizes[$n]["id"],false,"jpg");

        $file_exists = file_exists($path);
        if (($file_exists || (!$onlyifexists)) && !checkperm("T" . $resource_type . "_" . $sizes[$n]["id"]))
            {
            if (($sizes[$n]["internal"]==0) || ($internal))
                {
                $returnline=array();
                $returnline["name"]=lang_or_i18n_get_translated($sizes[$n]["name"], "imagesize-");
                $returnline["allow_preview"]=$sizes[$n]["allow_preview"];

                # The ability to restrict download size by user group and resource type.
                if (checkperm("X" . $resource_type . "_" . $sizes[$n]["id"]))
                    {
                    # Permission set. Always restrict this download if this resource is restricted.
                    $returnline["allow_restricted"]=false;
                    }
                else
                    {
                    # Take the restriction from the settings for this download size.
                    $returnline["allow_restricted"]=$sizes[$n]["allow_restricted"];
                    }
                $returnline["path"]=$path;
                $returnline["url"] = get_resource_path($ref, false, $sizes[$n]["id"], false, "jpg");
                $returnline["id"]=$sizes[$n]["id"];
                if ($file_exists)
                    {
                    $filesize = filesize_unlimited($path);
                    list($sw,$sh) = getimagesize($path);  
                    }
                else
                    {
                    $filesize=0;
                    $sw=0;
                    $sh=0;
                    }

                if ($filesize===false) {$returnline["filesize"]="?";$returnline["filedown"]="?";}
                else {$returnline["filedown"]=ceil($filesize/50000) . " seconds @ broadband";$filesize=formatfilesize($filesize);}
                $returnline["filesize"]=$filesize;          
                $returnline["width"]=$sw;           
                $returnline["height"]=$sh;
                $returnline["extension"]='jpg';
                $return[]=$returnline;
                }
            }
        $lastname=lang_or_i18n_get_translated($sizes[$n]["name"], "imagesize-");
        $lastpreview=$sizes[$n]["allow_preview"];
        $lastrestricted=$sizes[$n]["allow_restricted"];
        }
    return $return;
    }


/**
 * Get quality value for a given preview size.
 *
 * @param  string  $size   ID of preview size
 * 
 * @return int
 */
function get_preview_quality($size)
    {
    global $imagemagick_quality,$preview_quality_unique;
    $preview_quality=$imagemagick_quality; // default
    if($preview_quality_unique)
        {
        debug("convert: select quality value from preview_size where id='$size'");
        $quality_val=sql_value("select quality value from preview_size where id='{$size}'",'');
        if($quality_val!='')
            {
            $preview_quality=$quality_val;
            }
        }
    debug("convert: preview quality for $size=$preview_quality");
    return $preview_quality;
    }
    
/**
* Return an array of resource references that are related to resource $ref
*
* @param  int  $ref   ID of resource
* 
* @return array
*/
function get_related_resources($ref)
    {
    return sql_array("select related value from resource_related where resource='" . escape_check($ref) . "' union select resource value from resource_related where related='" . escape_check($ref) . "'");
    }


function get_field_options($ref,$nodeinfo = false)
    {
    # For the field with reference $ref, return a sorted array of options. Optionally use the node IDs as array keys
    if(!is_numeric($ref))
        {
        $ref = sql_value("select ref value from resource_type_field where name='" . escape_check($ref) . "'","", "schema");
        }
        
    $options = get_nodes($ref, null, true);
    
    # Translate options, 
    for ($m=0;$m<count($options);$m++)
        {
        $options[$m]["name"] = i18n_get_translated($options[$m]["name"]);
        unset($options[$m]["resource_type_field"]); // Not needed
        }

    if($nodeinfo)
        {
        // Add full path for category trees to differentiate nodes with the same name
        $fieldinfo = get_resource_type_field($ref);
        if($fieldinfo["type"] == FIELD_TYPE_CATEGORY_TREE)
            {
            $node_options = get_tree_strings($options, true);
            for ($m=0;$m<count($options);$m++)
                {
                $options[$m]["path"] = isset($node_options[$options[$m]["ref"]]) ? $node_options[$options[$m]["ref"]] : "";
                }
            }
        }

    if(!$nodeinfo)
        {
        $options = array_column($options,"name");
        global $auto_order_checkbox,$auto_order_checkbox_case_insensitive;
        if ($auto_order_checkbox)
            {
            if($auto_order_checkbox_case_insensitive)
                {
                natcasesort($options);
                $return=array_values($options);
                }
            else
                {sort($options);}
            }
        }
        
    return $options;
    }


/**
* Get the resource data value for a field and a specific resource
* or get the specified field for all resources in the system
* 
* @param integer        $resource Resource ID. Use NULL to retrieve all resources 
*                                 records for the specified field
* @param integer|string $field    Resource type field ID. Can also be a shortname.
* 
* @return string|array
*/
function get_data_by_field($resource, $field)
    {
    global $rt_fieldtype_cache, $NODE_FIELDS;

    $return              = '';
    $resource_type_field = escape_check($field);

    $sql_select   = 'SELECT *';
    $sql_from     = 'FROM resource_data AS rd';
    $sql_join     = '';
    // $sql_join     = 'LEFT JOIN resource AS r ON rd.resource = r.ref';
    $sql_where    = 'WHERE';
    $sql_order_by = '';
    $sql_limit    = '';

        // Update cache
    if(!isset($rt_fieldtype_cache[$field]))
        {
        $rt_fieldtype_cache[$field] = sql_value("SELECT type AS `value` FROM resource_type_field WHERE ref = '{$resource_type_field}' OR name = '{$resource_type_field}'", null, "schema");
        }

    if (!in_array($rt_fieldtype_cache[$field], $NODE_FIELDS))
        {
        // Let's first check how we deal with the field value we've got
        // Integer values => search for a specific ID
        // String values => search by using a shortname
        if(is_numeric($field))
            {
            $sql_select = 'SELECT rd.`value`';
            $sql_where .= " rd.resource = '{$resource}'";
            $sql_where .= " AND rd.resource_type_field = '{$resource_type_field}'";
            }
        else
            {
            $sql_select = 'SELECT rd.`value`';
            $sql_where .= " rd.resource = '{$resource}'";
            $sql_where .= " AND rd.resource_type_field = (SELECT ref FROM resource_type_field WHERE name = '{$resource_type_field}' LIMIT 1)";
            }
        
        $results = sql_query("{$sql_select} {$sql_from} {$sql_join} {$sql_where} {$sql_order_by} {$sql_limit}");
        if(0 !== count($results))
            {
            $return = !is_null($resource) ? $results[0]['value'] : $return;
            }
        // Default values: '' when we are looking for a specific resource and empty array when looking through all resources
        else
            {
            $return = !is_null($resource) ? $return : array();
            }

        if(!is_array($return) && 8 == $rt_fieldtype_cache[$field])
            {
            $return = strip_tags($return);
            $return = str_replace('&nbsp;', ' ', $return);
            }
        }
    else
        {
        $nodes = get_resource_nodes($resource, $resource_type_field, TRUE);
        $return = implode(', ', array_column($nodes, 'name'));    
        }
    return $return;   
    }

function get_all_image_sizes($internal=false,$restricted=false)
    {
        # Returns all image sizes available.
        # Standard image sizes are translated using $lang.  Custom image sizes are i18n translated.
        $condition=($internal)?"":"WHERE internal!=1";
        if($restricted){$condition .= ($condition!=""?" AND ":" WHERE ") . " allow_restricted=1";}
        
        # Executes query.
        $r = sql_query("select * from preview_size " . $condition . " order by width asc");
    
        # Translates image sizes in the newly created array.
        $return = array();
        for ($n = 0;$n<count($r);$n++) {
            $r[$n]["name"] = lang_or_i18n_get_translated($r[$n]["name"], "imagesize-");
            $return[] = $r[$n];
        }
        return $return;
    
    }
        
function image_size_restricted_access($id)
    {
    # Returns true if the indicated size is allowed for a restricted user.
    return sql_value("select allow_restricted value from preview_size where id='$id'",false);
    }


/**
* Returns a list of fields with refs matching the supplied field refs.
* 
* @param array $field_refs Array of field refs
* 
* @return array
*/
function get_fields($field_refs)
    {
    if(!is_array($field_refs))
        {
        trigger_error("\$field_refs passed to get_fields() is not an array.");
        }

    $fields=sql_query("
        SELECT *,
               ref,
               name,
               title,
               type,
               order_by,
               keywords_index,
               partial_index,
               resource_type,
               resource_column,
               display_field,
               use_for_similar,
               iptc_equiv,
               display_template,
               tab_name,
               required,
               smart_theme_name,
               exiftool_field,
               advanced_search,
               simple_search,
               help_text,
               display_as_dropdown,
               tooltip_text,
               display_condition,
               onchange_macro
          FROM resource_type_field
         WHERE ref IN ('" . join("','",$field_refs) . "')
      ORDER BY order_by", "schema");

    $return = array();
    foreach($fields as $field)
        {
        if(metadata_field_view_access($field['ref']))
            {
            $return[] = $field;
            }
        }

    /*for($n = 0; $n < count($fields); $n++)
        {
        if(metadata_field_view_access($fields[$n]["ref"]))
            {
            $return[]=$fields[$n];
            }
        }*/

    return $return;
    }

function get_hidden_indexed_fields()
    {
    # Return an array of indexed fields to which the current user does not have access
    # Used by do_search to ommit fields when searching.
    $hidden=array();
    global $hidden_fields_cache;
    if (is_array($hidden_fields_cache)){
        return $hidden_fields_cache;
    } else { 
        $fields=sql_query("select ref,active from resource_type_field where length(name)>0","schema");
        # Apply field permissions
        for ($n=0;$n<count($fields);$n++)
            {
            if ($fields[$n]["active"]==1 && metadata_field_view_access($fields[$n]["ref"]))
                {
                # Visible field
                }
            else
                {
                # Hidden field
                $hidden[]=$fields[$n]["ref"];
                }
            }
        $hidden_fields_cache=$hidden;
        return $hidden;
        }
    }


function get_OR_fields()
    {
    # Returns a list of fields that should retain semicolon separation of keywords in a search string
    global $orfields_cache;
    if (is_array($orfields_cache)){
        return $orfields_cache;
    } else {
        $fields=sql_query("select name from resource_type_field where type=7 or type=2 or type=3 and length(name)>0 order by order_by", "schema");
        $orfields=array();
        foreach ($fields as $field){
            $orfields[]=$field['name'];
        }
        $orfields_cache=$orfields;
        return $orfields;
        }
    }

/**
* Returns the path (relative to the gfx folder) of a suitable folder to represent
* a resource with the given resource type or extension
* Extension matches are tried first, followed by resource type matches
* Finally, if there are no matches then the 'type1' image will be used.
* set contactsheet to true to cd up one more level.
* 
* @param integer $resource_type
* @param string  $extension
* @param boolean $col_size
* 
* @return string
*/
function get_nopreview_icon($resource_type, $extension, $col_size)
    {
    global $language;
    
    $col=($col_size?"_col":"");
    $folder=dirname(dirname(__FILE__)) . "/gfx/";
    $extension=strtolower($extension);

    # Metadata template? Always use icon for 'mdtr', although typically no file will be attached.
    global $metadata_template_resource_type;
    if (isset($metadata_template_resource_type) && $metadata_template_resource_type==$resource_type) {$extension="mdtr";}

    # Try a plugin
    $try=hook('plugin_nopreview_icon','',array($resource_type,$col, $extension));
    if (false !== $try && file_exists($folder . $try))
        {
        return $try;
        }

    # Try extension (language specific)
    $try="no_preview/extension/" . $extension . $col . "_" . $language . ".png";
    if (file_exists($folder . $try))
        {
        return $try;
        }
    # Try extension (default)
    $try="no_preview/extension/" . $extension . $col . ".png";
    if (file_exists($folder . $try))
        {
        return $try;
        }
    
    # --- Legacy ---
    # Support the old location for resource type and GIF format (root of gfx folder)
    # Some installations use custom types in this location.
    $try="type" . $resource_type . $col . ".gif";
    if (file_exists($folder . $try))
        {
        return $try;
        }


    # Try resource type (language specific)
    $try="no_preview/resource_type/type" . $resource_type . $col . "_" . $language . ".png";
    if (file_exists($folder . $try))
        {
        return $try;
        }
    # Try resource type (default)
    $try="no_preview/resource_type/type" . $resource_type . $col . ".png";
    if (file_exists($folder . $try))
        {
        return $try;
        }
    
    
    # Fall back to the 'no preview' icon used for type 1.
    return "no_preview/resource_type/type1" . $col . ".png";
    }

function purchase_set_size($collection,$resource,$size,$price)
    {
    // Set the selected size for an item in a collection. This is used later on when the items are downloaded.
    sql_query("update collection_resource set purchase_size='" . escape_check($size) . "',purchase_price='" . escape_check($price) . "' where collection='$collection' and resource='$resource'");
    return true;
    }

function payment_set_complete($collection,$emailconfirmation="")
    {
    global $applicationname,$baseurl,$userref,$username,$useremail,$userfullname,$email_notify,$lang,$currency_symbol;
    // Mark items in the collection as paid so they can be downloaded.
    sql_query("update collection_resource set purchase_complete=1 where collection='$collection'");
    
    // For each resource, add an entry to the log to show it has been purchased.
    $resources=sql_query("select * from collection_resource where collection='$collection'");
    $summary="<style>.InfoTable td {padding:5px;}</style><table border=\"1\" class=\"InfoTable\"><tr><td><strong>" . $lang["property-reference"] . "</strong></td><td><strong>" . $lang["size"] . "</strong></td><td><strong>" . $lang["price"] . "</strong></td></tr>";
    foreach ($resources as $resource)
        {
        $purchasesize=$resource["purchase_size"];
        if ($purchasesize==""){$purchasesize=$lang["original"];}
        resource_log($resource["resource"],LOG_CODE_PAID,0,"","","",0,$resource["purchase_size"],$resource["purchase_price"]);
        $summary.="<tr><td>" . $resource["resource"] . "</td><td>" . $purchasesize . "</td><td>" . $currency_symbol . $resource["purchase_price"] . "</td></tr>";
        }
    $summary.="</table>";
    // Send email or notification to admin
    $message=$lang["purchase_complete_email_admin_body"] . "<br />" . $lang["username"] . ": " . $username . "(" . $userfullname . ")<br />" . $summary . "<br /><br />$baseurl/?c=" . $collection . "<br />";
    $notificationmessage=$lang["purchase_complete_email_admin_body"] . "\r\n" . $lang["username"] . ": " . $username . "(" . $userfullname . ")";
    $notify_users=get_notification_users("RESOURCE_ACCESS"); 
    $message_users=array();
    foreach($notify_users as $notify_user)
            {
            get_config_option($notify_user['ref'],'user_pref_resource_access_notifications', $send_message);          
            if($send_message==false){continue;}     
            
            get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
            if($send_email && $notify_user["email"]!="")
                {
                send_mail($notify_user["email"],$applicationname . ": " . $lang["purchase_complete_email_admin"],$message);
                }        
            else
                {
                $message_users[]=$notify_user["ref"];
                }
            }
            
    if (count($message_users)>0)
        {       
        message_add($message_users,$notificationmessage,$baseurl . "/?c=" . $collection,$userref);
        }   
    
    // Send email to user (not a notification as may need to be kept for reference)
    $confirmation_address=($emailconfirmation!="")?$emailconfirmation:$useremail;   
    $userconfirmmessage= $lang["purchase_complete_email_user_body"] . $summary . "<br /><br />$baseurl/?c=" . $collection . "<br />";
    send_mail($useremail,$applicationname . ": " . $lang["purchase_complete_email_user"] ,$userconfirmmessage);
    
    // Rename so that can be viewed on my purchases page
    sql_query("update collection set name= '" . date("Y-m-d H:i") . "' where ref='$collection'");
    
    return true;

    }


/**
 * Get references of resource type fields that are indexed
 *
 * @return array
 */
function get_indexed_resource_type_fields()
    {
    return sql_array("select ref as value from resource_type_field where keywords_index=1","schema");
    }


/**
* Gets all metadata fields, optionally for a specified array of resource types 
*
* @param  array    $restypes           Optional array of resource types to check
* @param  string   $field_order_by     Order by column
* @param  string   $field_sort         Sort order
* @param  string   $find               Parameter value to search for
* @param  array    $fieldtypes         List of field types to include
* @param  boolean  $include_inactive   Should inactive resources be checked, default is false
* 
* @return array
*/
function get_resource_type_fields($restypes="", $field_order_by="ref", $field_sort="asc", $find="", $fieldtypes = array(), $include_inactive=false)
    {
    $conditionsql="";
    if(is_array($restypes))
        {
        $conditionsql = " WHERE resource_type IN (" . implode(",",$restypes) . ")";
        }
    if ($include_inactive==false)
        {
        if($conditionsql != "")
            {
            $conditionsql .= " AND active=1 ";
            }
        else
            {
            $conditionsql .= " WHERE active=1 ";
            }
        }
    if($find!="")
        {
        $find=escape_check($find);
        if($conditionsql != "")
            {
            $conditionsql .= " AND ( ";
            }
        else
            {
            $conditionsql .= " WHERE ( ";
            }
        $conditionsql.=" name LIKE '%" . $find . "%' OR title LIKE '%" . $find . "%' OR tab_name LIKE '%" . $find . "%' OR exiftool_field LIKE '%" . $find . "%' OR help_text LIKE '%" . $find . "%' OR ref LIKE '%" . $find . "%' OR tooltip_text LIKE '%" . $find . "%' OR display_template LIKE '%" . $find . "%')";
        }
    
    $newfieldtypes = array_filter($fieldtypes,function($v){return (string)(int)$v == $v;}); 
    
    if(count($newfieldtypes) > 0)
        {
        if($conditionsql != "")
			{
			$conditionsql .= " AND ( ";
			}
		else
			{
			$conditionsql .= " WHERE ( ";
			}
        $conditionsql .= " type IN ('" . implode("','",$newfieldtypes) . "'))";
		}
    // Allow for sorting, enabled for use by System Setup pages
    //if(!in_array($field_order_by,array("ref","name","tab_name","type","order_by","keywords_index","resource_type","display_field","required"))){$field_order_by="ref";}       
        
    $allfields = sql_query("
        SELECT ref,
               name,
               title,
               type,
               order_by,
               keywords_index,
               partial_index,
               resource_type,
               resource_column,
               display_field,
               use_for_similar,
               iptc_equiv,
               display_template,
               tab_name,
               required,
               smart_theme_name,
               exiftool_field,
               advanced_search,
               simple_search,
               help_text,
               display_as_dropdown,
               external_user_access,
               autocomplete_macro,
               hide_when_uploading,
               hide_when_restricted,
               value_filter,
               exiftool_filter,
               omit_when_copying,
               tooltip_text,
               regexp_filter,
               sync_field,
               display_condition,
               onchange_macro,
               field_constraint,
               linked_data_field,
               automatic_nodes_ordering,
               fits_field,
               personal_data,
               include_in_csv_export,
               browse_bar,
               active,
               read_only,
               full_width
          FROM resource_type_field" . $conditionsql . " ORDER BY active desc," . escape_check($field_order_by) . " " . escape_check($field_sort), "schema");

    return $allfields;
    }


    function notify_resource_change($resource)
    {
    debug("notify_resource_change " . $resource);
    global $notify_on_resource_change_days;
    // Check to see if we need to notify users of this change
    if($notify_on_resource_change_days==0 || !is_int($notify_on_resource_change_days))
        {
        return false;
        }
        
    debug("notify_resource_change - checking for users that have downloaded this resource " . $resource);
    $download_users=sql_query("select distinct u.ref, u.email from resource_log rl left join user u on rl.user=u.ref where rl.type='d' and rl.resource=$resource and datediff(now(),date)<'$notify_on_resource_change_days'","");
    $message_users=array();
    if(count($download_users)>0)
        {
        global $applicationname, $lang, $baseurl;
        foreach ($download_users as $download_user)
            {
            if($download_user['ref']==""){continue;}
            get_config_option($download_user['ref'],'user_pref_resource_notifications', $send_message);       
            if($send_message==false){continue;}     
            
            get_config_option($download_user['ref'],'email_user_notifications', $send_email);
            get_config_option($download_user['ref'],'email_and_user_notifications', $send_email_and_notify);
            if($send_email_and_notify)
                {
                $message_users[]=$download_user["ref"];
                if($download_user["email"]!="")
                    {
                    send_mail($download_user['email'],$applicationname . ": " . $lang["notify_resource_change_email_subject"],str_replace(array("[days]","[url]"),array($notify_on_resource_change_days,$baseurl . "/?r=" . $resource),$lang["notify_resource_change_email"]),"","",'notify_resource_change_email',array("days"=>$notify_on_resource_change_days,"url"=>$baseurl . "/?r=" . $resource));
                    }
                }
            else if($send_email && $download_user["email"]!="")
                {
                send_mail($download_user['email'],$applicationname . ": " . $lang["notify_resource_change_email_subject"],str_replace(array("[days]","[url]"),array($notify_on_resource_change_days,$baseurl . "/?r=" . $resource),$lang["notify_resource_change_email"]),"","",'notify_resource_change_email',array("days"=>$notify_on_resource_change_days,"url"=>$baseurl . "/?r=" . $resource));
                }
            else
                {
                $message_users[]=$download_user["ref"];
                }
            }
        if (count($message_users)>0)
            {
            message_add($message_users,str_replace(array("[days]","[url]"),array($notify_on_resource_change_days,$baseurl . "/?r=" . $resource),$lang["notify_resource_change_notification"]),$baseurl . "/?r=" . $resource);
            }
        }
    }

# Takes a string and add verbatim regex matches to the keywords list on found matches (for that field)
# It solves the problem, for example, indexing an entire "nnn.nnn.nnn" string value when '.' are used as a keyword separator.
# Uses config option $resource_field_verbatim_keyword_regex[resource type field] = '/regex/'
# Also changes "field:<value>" type searches to "field:,<value>" for full matching for field types such as "Check box list" (config option to specify this)
function add_verbatim_keywords(&$keywords, $string, $resource_type_field, $called_from_search=false)
    {
    global $resource_field_verbatim_keyword_regex,$resource_field_checkbox_match_full;

    // add ",<string>" if specified resource_type_field is found within $resource_field_checkbox_match_full array.
    if( !$called_from_search &&
        isset($resource_field_checkbox_match_full) &&
        is_array($resource_field_checkbox_match_full) &&
        in_array($resource_type_field,$resource_field_checkbox_match_full))
        {
        preg_match_all('/,[^,]+/', $string, $matches);
        if (isset($matches[0][0]))
            {
            foreach ($matches[0] as $match)
                {
                $match=strtolower($match);
                array_push($keywords,$match);
                }
            }
        }

    // normal verbatim expansion of keywords as defined in config.php
    if (!empty($resource_field_verbatim_keyword_regex[$resource_type_field]))
        {
        preg_match_all($resource_field_verbatim_keyword_regex[$resource_type_field], $string, $matches);
        foreach ($matches as $match)
            {
            foreach ($match as $sub_match)
                {
                array_push($keywords, $sub_match);        // note that the keywords array is passed in by reference.
                }
            }
        }

    // when searching change "field:<string>" to "field:,<string>" if specified resource_type_field is found within $resource_field_checkbox_match_full array.
    if ($called_from_search &&
        isset($resource_field_checkbox_match_full) &&
        is_array($resource_field_checkbox_match_full) &&
        in_array($resource_type_field,$resource_field_checkbox_match_full))
        {
        $found_name = sql_value("SELECT `name` AS 'value' FROM `resource_type_field` WHERE `ref`='{$resource_type_field}'", "");
        preg_match_all('/' . $found_name . ':([^,]+)/', $string, $matches);
        if (isset($matches[1][0]))
            {
            foreach ($matches[1] as $match)
                {
                $match=strtolower($match);
                $remove = "{$found_name}:{$match}";
                if (in_array($remove,$keywords))
                    {
                    unset($keywords[array_search($remove,$keywords)]);
                    }
                array_push($keywords, "{$found_name}:,{$match}");
                }
            }
        }
    }


        
/**
 * Check the current user's edit access to given metadata field ID
 *
 * @param  int $field
 * @return bool 
 */
function metadata_field_edit_access($field)
    {
    return (!checkperm("F*") || checkperm("F-" . $field))&& !checkperm("F" . $field);
    }

/**
 * Work out the filename to use when downloading the specified resource file with the given settings
 *
 * @param  int $ref Resource ID
 * @param  string $size size code
 * @param  int $alternative Alternative file ID 
 * @param  string $ext File extension
 * @return string  Filename to use
 */
function get_download_filename($ref,$size,$alternative,$ext)
    {
    # Constructs a filename for download
    global $original_filenames_when_downloading,$download_filenames_without_size,$download_id_only_with_size,
    $download_filename_id_only,$download_filename_field,$prefix_resource_id_to_filename,$filename_field,
    $prefix_filename_string, $filename,$server_charset;
    
    $filename = (($download_filenames_without_size || $size == "") ? "" : "_" . $size . "") . ($alternative>0 ? "_" . $alternative : "") . "." . $ext;
    
    if ($original_filenames_when_downloading)
        {
        # Use the original filename.
        if ($alternative>0)
            {
            # Fetch from the resource_alt_files alternatives table (this is an alternative file)
            $origfile=get_alternative_file($ref,$alternative);
            $origfile=$origfile["file_name"];
            }
        else
            {
            # Fetch from field data or standard table   
            $origfile=get_data_by_field($ref,$filename_field);  
            }
        if (strlen($origfile)>0)
            {
            # do an extra check to see if the original filename might have uppercase extension that can be preserved.   
            $pathparts=pathinfo($origfile);
            if (isset($pathparts['extension'])){
                if (strtolower($pathparts['extension'])==$ext){$ext=$pathparts['extension'];}   
            } 
            
            # Use the original filename if one has been set.
            # Strip any path information (e.g. if the staticsync.php is used).
            # append preview size to base name if not the original
            if($size != '' && !$download_filenames_without_size)
                {
                $filename = strip_extension(mb_basename($origfile),true) . '-' . $size . '.' . $ext;
                }
            else
                {
                $filename = strip_extension(mb_basename($origfile),true) . '.' . $ext;
                }
            }
        }

    elseif ($download_filename_id_only)
        {
        if(!hook('customdownloadidonly', '', array($ref, $ext, $alternative)))
            {
            $filename=$ref . "." . $ext;

            if($size != '' && $download_id_only_with_size)
                {
                $filename = $ref . '-' . $size . '.' . $ext;
                }            
            }
        }
    
    elseif (isset($download_filename_field))
        {
        $newfilename=get_data_by_field($ref,$download_filename_field);
        if ($newfilename)
            {
            $filename = trim(nl2br(strip_tags($newfilename)));
            if($size != "" && !$download_filenames_without_size)
                {
                $filename = strip_extension(mb_basename(substr($filename, 0, 200)),true) . '-' . $size . '.' . $ext;
                }
            else
                {
                $filename = strip_extension(mb_basename(substr($filename, 0, 200)),true) . '.' . $ext;
                }
            }
        }

    if($prefix_resource_id_to_filename)
        {
        $filename = $ref . (substr($filename,0,1) == "." ? "" : '_') . $filename;
        }
    
    if(isset($prefix_filename_string) && trim($prefix_filename_string) != '')
        {
        $filename = $prefix_filename_string . $filename;
        }

    # Remove critical characters from filename
    $altfilename=hook("downloadfilenamealt");
    if(!($altfilename)) $filename = preg_replace('/:/', '_', $filename);
    else $filename=$altfilename;

    # Convert $filename to the charset used on the server.
    if (!isset($server_charset)) {$to_charset = 'UTF-8';}
    else
        {
        if ($server_charset!="") {$to_charset = $server_charset;}
        else {$to_charset = 'UTF-8';}
        }
    $filename = mb_convert_encoding($filename, $to_charset, 'UTF-8');

    hook("downloadfilename");
    return $filename;
    }


/**
* Get resource type ID based on extension
* $mappings = array(resource_type_id => array(allowed_extensions));
* 
* Example of mapping array:
* $mappings = array(2 => array('pdf', 'doc', 'docx', 'epub', 'ppt', 'pptx', 'odt', 'ods', 'tpl'));
* 
* @param string  $extension                        Extension we search by (ie. "mp4")
* @param array   $resource_type_extension_mapping  Maps between resource types and extensions
* @param integer $default                          The default value to use in case we can't find it the mappings
* 
* @return integer  Resource type ID
*/
function get_resource_type_from_extension($extension, array $resource_type_extension_mapping, $default)
    {
    $resource_types = sql_array("SELECT ref AS value FROM resource_type");
    foreach($resource_type_extension_mapping as $resource_type_id => $allowed_extensions)
        {
        if (!checkperm('T' . $resource_type_id))
            {
            if(in_array(strtolower($extension), $allowed_extensions))
                {
                    if(in_array($resource_type_id, $resource_types))
                    {
                    return $resource_type_id;
                    }
                }
            }
        }
    if(in_array($default, $resource_types))
        {
        return $default;
        }
    else
        {
        // default resource type does not exist so use the first available type
        sort($resource_types,SORT_NUMERIC);
        return $resource_types[0];
        }
    }

/**
* Helper function for Preview tools feature. Checks all necessary permissions or options
* in order to tell the system whether PreviewTools panel should be displayed
* 
* @param boolean $edit_access Does user have the permissions to edit this resource
* 
* @return boolean
*/
function canSeePreviewTools($edit_access)
    {
    global $annotate_enabled, $image_preview_zoom;

    return
        (
           ($annotate_enabled && $edit_access)
        || $image_preview_zoom
        );
    }


/**
* Helper function for Preview tools feature. Checks if a config option that manipulates the preview image (on view page)
* is the only one enababled.
* 
* IMPORTANT: When adding new preview tool options, make sure to check if you need to add a new type check (at the 
* moment it only checks for boolean config options and anything else is seen as enabled).
* 
* @param string $config_option Preview tool config option name to check
* 
* @return boolean False means there are other preview tool options enabled.
*/
function checkPreviewToolsOptionUniqueness($config_option)
    {
    $count_options_enabled = 0;
    $preview_tool_options = array(
        'annotate_enabled',
        'image_preview_zoom'
    );

    foreach($preview_tool_options as $preview_tools_option)
        {
        if($preview_tools_option === $config_option)
            {
            continue;
            }

        if(!isset($GLOBALS[$preview_tools_option]))
            {
            continue;
            }

        $check_option = $GLOBALS[$preview_tools_option];

        if(is_bool($check_option) && !$check_option)
            {
            continue;
            }

        $count_options_enabled++;
        }

    return (0 === $count_options_enabled ? true : false);
    }

/**
* Determine if a video alternative was created from $ffmpeg_alternatives settings.
* Places in this file because get_resource_path relies on it
* 
* @param array $alternative Record line from resource_alt_files
* 
* @return boolean True means alternative was created from $ffmpeg_alternatives settings
*/
function alt_is_ffmpeg_alternative($alternative)
    {
    global $ffmpeg_alternatives;
    
    $alt_is_ffmpeg_alternative=false;
    
    if(isset($ffmpeg_alternatives) && !empty($ffmpeg_alternatives))
        {
        foreach($ffmpeg_alternatives as $alt_setting)
            {
            if($alternative['name']==$alt_setting['name'] && $alternative['file_name']==$alt_setting['filename'] . '.' . $alt_setting['extension'])
                {
                $alt_is_ffmpeg_alternative=true;
                return $alt_is_ffmpeg_alternative;
                }
            }
        }
    return $alt_is_ffmpeg_alternative;
    }


/**
* Create a new resource type field with the specified name of the required type
* 
* @param string $name - name of new field 
* @param integer $restype - resource type - resource type that field applies to (0 = global)
* @param integer $type - field type - refer to include/definitions.php
* @param string $shortname - shortname of new field 
* @param boolean $index - should new field be indexed? 
* 
* @return boolean|integer - ref of new field, false if unsuccessful
*/
function create_resource_type_field($name, $restype = 0, $type = FIELD_TYPE_TEXT_BOX_SINGLE_LINE, $shortname = "", $index=false)
    {
    if((trim($name)=="") || !is_numeric($type) || !is_numeric($restype))
        {
        return false;
        }

    if(trim($shortname) == "")
        {
        $shortname = mb_substr(mb_strtolower(str_replace("_","",safe_file_name($name))),0,20);
        }

    $duplicate = (boolean) sql_value(sprintf(
        "SELECT count(ref) AS `value` FROM resource_type_field WHERE `name` = '%s'",
        escape_check($shortname)), 0, "schema");

    sql_query(sprintf("INSERT INTO resource_type_field (title, resource_type, type, `name`, keywords_index) VALUES ('%s', '%s', '%s', '%s', %s)",
        escape_check($name),
        escape_check($restype),
        escape_check($type),
        escape_check($shortname),
        ($index ? "1" : "0")
    ));
    $new = sql_insert_id();

    if($duplicate)
        {
        sql_query(sprintf("UPDATE resource_type_field SET `name` = '%s' WHERE ref = '%s'", escape_check($shortname . $new), $new));
        }

    log_activity(null, LOG_CODE_CREATED, $name, 'resource_type_field', 'title', $new, null, '');

    clear_query_cache("schema");

    return $new;
    }


/**
* Check if user has view access to metadata field
* 
* @uses checkperm()
* 
* @param integer $field Field ref
* 
* @return boolean
*/
function metadata_field_view_access($field)
    {
    return (
        (PHP_SAPI == 'cli' && !defined("RS_TEST_MODE"))
        || ((checkperm("f*") || checkperm("f" . $field)) && !checkperm("f-" . $field)));
    }


/**
* Utility to get all workflow states available in the system.
* 
* IMPORTANT: No permissions are being honoured on purpose! If you need to honour permissions @see get_editable_states()
* 
* @uses global additional_archive_states
* 
* @return array
*/
function get_workflow_states()
    {
    global $additional_archive_states;

    $default_workflow_states = range(-2, 3);
    $workflow_states = array_merge($default_workflow_states, $additional_archive_states);

    return $workflow_states;
    }



/**
* Delete the specified metadata field. Also delets any node or resource_data rows associated with that field
* 
* @param integer $ref Metadata field id (ref from resource_type_field)
* @param array $varnames Array of variable names
*
* @return boolean|string Returns true on success or text on failure describing error
*/
function delete_resource_type_field($ref)
    {
    global $lang, $corefields, $core_field_refs;

    if('cli' != php_sapi_name() && !checkperm('a'))
        {
        return $lang["error-permissiondenied"];
        }

    $fieldvars = array();
    foreach ($corefields as $scope=>$scopevars)
        {
        foreach($scopevars as $varname)
            {
            global $$varname;
            if(isset($$varname) && (is_array($$varname) && in_array($ref,$$varname) || ((int)$$varname==$ref)))
                {
                $fieldvars[] = $varname . ($scope != "BASE" ? " (" . $scope . ")" : "");
                }
            }
        }

    // Prevent deleting a "core" field required by other parts of the system (e.g plugins)
    $core_field_scopes = [];
    foreach($core_field_refs as $scope => $core_refs)
        {
        if(in_array($ref, $core_refs) && !in_array($scope, $core_field_scopes))
            {
            $core_field_scopes[] = $scope;
            }
        }

    if(count($fieldvars) > 0)
        {
        return $lang["admin_delete_field_error"] . "<br />\$" . implode(", \$",$fieldvars);
        }
    else if(!empty($core_field_scopes))
        {
        return sprintf('%s%s', $lang["admin_delete_field_error_scopes"], implode(', ', $core_field_scopes));
        }


    $fieldinfo = get_resource_type_field($ref);

    $ref = escape_check($ref);
    
    // Delete the resource type field
    sql_query("DELETE FROM resource_type_field WHERE ref='$ref'");

    // Remove all data	    
    sql_query("DELETE FROM resource_data WHERE resource_type_field='$ref'");

    // Remove all nodes and keywords or resources. Always remove nodes last otherwise foreign keys will not work
    sql_query("DELETE rn.* FROM resource_node rn LEFT JOIN node n ON n.ref=rn.node WHERE n.resource_type_field='$ref'");
    sql_query("DELETE nk.* FROM node_keyword AS nk LEFT JOIN node AS n ON n.ref = nk.node WHERE n.resource_type_field = '$ref'");
    sql_query("DELETE FROM node WHERE resource_type_field='$ref'");

    // Remove all keywords	    
    sql_query("DELETE FROM resource_keyword where resource_type_field='$ref'");

    hook("after_delete_resource_type_field");

    log_activity('Deleted metadata field "' . $fieldinfo["title"] . '" (' . $fieldinfo["ref"] . ')',LOG_CODE_DELETED,null,'resource_type_field',null,$ref);

    clear_query_cache("schema");

    return true;
    }

/**
 * Function to return a list of tab names retrieved from $fields array containing metadata fields
 * 
 * if there is at least one field with a value for tab_name, then if there is at another field that does not have a tab_name value, it is assigned the value "Default"
 * 
 * @param   array   fields  array of metadata fields to display
 * @global  array   lang    array of config-defined language strings
 * 
 * @return  array   $fields_tab_names   array of unique tab names contained in the $fields array
 */
function tab_names($fields)
    {
    global $lang; // language strings

    $fields_tab_names = array();
    $tabs_set = false; // by default no tabs set
    
    // loop through fields array and identify whether to use tabs
    foreach ($fields as $field)
        {
        $field["tab_name"] != "" ? $tabs_set = true : $tabs_set = $tabs_set;
        }
        
    // loop through fields and create list of tab names, including default string if any fields present with empty string values for tab_name  
    foreach ($fields as $field)
        {   
        if ($tabs_set === true)
            {
            $fieldtabname = $field["tab_name"] != "" ? $field["tab_name"] : $lang["default"];
            }
        else
            {
            $fieldtabname = "";
            }
        $fields_tab_names[] = $fieldtabname;
        }
    
    // get list of unique tab names
    $fields_tab_names = array_values(array_unique($fields_tab_names));

    // return list of tab names
    return $fields_tab_names;
    }


function get_resource_table_joins(){

    global 
    $sort_fields,
    $thumbs_display_fields,
    $list_display_fields,
    $data_joins,
    $metadata_template_title_field,
    $view_title_field,
    $date_field,
    $config_sheetlist_fields,
    $config_sheetthumb_fields,
    $config_sheetsingle_fields;

    $joins=array_merge(
    $sort_fields,
    $thumbs_display_fields,
    $list_display_fields,
    $data_joins,
    $config_sheetlist_fields,
    $config_sheetthumb_fields,
    $config_sheetsingle_fields,
        array(
        $metadata_template_title_field,
        $view_title_field,
        $date_field)
    );
    $additional_joins=hook("additionaljoins");
    if ($additional_joins) $joins=array_merge($joins,$additional_joins);
    $joins=array_unique($joins);
    $n=0;
    foreach ($joins as $join){
        if ($join!=""){
            $return[$n]=$join;
            $n++;
            }
        }
    return $return;
    }

/*
* Update the lock status of the current resource
* 
* @param  int       $ref            Resource ID
* @param  int       $lockaction     Lock action (1 = Lock, 0 = Unlock)
* @param  int       $newlockuser    User ID to set lock for. Will default to current user if not passed
* @param  boolean   $accesschecked  Has access to the resource already been checked (false by default)?
* 
* @return boolean   Success/failure
*/
function update_resource_lock($ref,$lockaction,$newlockuser=null,$accesschecked = false)
    {
    global $userref;
    
    if(((string)(int)$ref != (string)$ref)
     || $ref <= 0
     || !in_array($lockaction,array(0,1))
        )
        {
        return false;
        }

    if(is_null($newlockuser) || ((string)(int)$newlockuser != (string)$newlockuser))
        {
        $newlockuser = $userref;
        }

    if(!$accesschecked)
        {
        $resource_data  = get_resource_data($resource);
        $lockeduser     =  $resource_data["lock_user"];
        $edit_access    = get_edit_access($resource,false,$resource_data);
        if(!checkperm("a")
            &&
            $lockeduser != $userref
            &&
            !($edit_access && $lockeduser == 0 && !checkperm("nolock"))
            )
            {
            return false;
            }
        }

    sql_query("UPDATE resource SET lock_user='" . ($lockaction ? $newlockuser : "0") . "' WHERE ref='" . (int)$ref . "'");
    resource_log($ref,($lockaction ? LOG_CODE_LOCKED : LOG_CODE_UNLOCKED),0);
    return true;
    }

/*
* Get a message to indicate the lock status 
* 
* @param  int       id of the locking user
* 
* @return string    Text to display
*/
function get_resource_lock_message($lockuser)
    {
    global $lang, $userref;
    // Check if user can see details of locking user
    $visible_users = get_users(0,"","u.username",true);
    if($lockuser == 0)
        {
        return "";
        }
    elseif($lockuser == $userref)
        {
        return $lang["status_locked_self"];
        }
    elseif(in_array($lockuser,array_column($visible_users,"ref")))
        {
        $lock_user_data = get_user($lockuser);
        $lock_username = $lock_user_data["fullname"] != "" ? $lock_user_data["fullname"] : $lock_user_data["username"];
        return str_replace("%%USER%%", $lock_username, $lang["status_locked_by"]);
        }
    else
        {
        return $lang["error_locked_other_user"];
        }
    }

/**
 * Get details of external shares
 *
 * @param  array $filteropts    Array of options to filter shares returned
 *                              "share_group"       - (int) Usergroup ref 'shared as'
 *                              "share_user"        - (int) user ID of share creator
 *                              "share_order_by"    - (string) order by column 
 *                              "share_sort"        - (string) sortorder (ASC or DESC)
 *                              "share_type"        - (int) 0=view, 1=upload
 *                              "share_collection"  - (int) Collection ID
 *                              "share_resource"    - (int) Resource ID
 *                              "access_key"        - (string) Access key
 * @return array
 */
function get_external_shares(array $filteropts)
    {
    global $userref;

    $validfilterops = array(
        "share_group",
        "share_user",
        "share_order_by",
        "share_sort",
        "share_type",
        "share_collection",
        "share_resource",
        "access_key",
    );
    foreach($validfilterops as $validfilterop)
        {
        if(isset($filteropts[$validfilterop]))
            {
            $$validfilterop = $filteropts[$validfilterop];
            }
        else
            {
            $$validfilterop = NULL;
            }
        }

    $valid_orderby = array("collection","user", "sharedas", "expires", "date", "email", "lastused", "access_key", "upload");
    if(!in_array($share_order_by, $valid_orderby))
        {
        $share_order_by = "expires";
        }
    $share_sort = strtoupper($share_sort) == "ASC" ? "ASC" : "DESC";

    $conditions = array();
    if((int)$share_user > 0 && ($share_user == $userref || checkperm_user_edit($share_user))
        )
        {
        $conditions[] = "eak.user ='" . (int)$share_user . "'";
        }
    elseif(!checkperm('a'))
        {
        $usercondition = "eak.user ='" . (int)$userref . "'";
        if(checkperm("ex"))
            {
            // Can also see shares that never expire
            $usercondition = " (expires IS NULL OR " . $usercondition . ")";
            }
        $conditions[] =$usercondition;
        }

    if(!is_null($share_group) && (int)$share_group > 0  && checkperm('a'))
        {
        $conditions[] = "eak.usergroup ='" . (int)$share_group . "'";
        }
    
    if(!is_null($access_key))
        {
        $conditions[] = "eak.access_key ='" . escape_check($access_key) . "'";
        }

    if((int)$share_type === 0)
        {
        $conditions[] = "(eak.upload=0 OR eak.upload IS NULL)";
        }
    elseif((int)$share_type === 1)
        {
        $conditions[] = "eak.upload=1";
        }
    if((int)$share_collection > 0)
        {
        $conditions[] = "eak.collection ='" . (int)$share_collection . "'";
        }
    if((int)$share_resource > 0)
        {
        $conditions[] = "eak.resource ='" . (int)$share_resource . "'";
        }

    $conditional_sql="";
    if (count($conditions)>0){$conditional_sql=" WHERE " . implode(" AND ",$conditions);}

    $external_access_keys_query = 
        "SELECT access_key,
                ifnull(collection,'-') collection,
                CASE 
                    WHEN collection IS NULL THEN resource
                    ELSE '-'
                END AS 'resource',
                user,
                eak.email,
                min(date) date,
                MAX(date) maxdate,
                max(lastused) lastused,
                eak.access,
                eak.expires,
                eak.usergroup,
                eak.password_hash,
                eak.upload,
                ug.name sharedas,
                u.fullname,
                u.username
           FROM external_access_keys eak
      LEFT JOIN user u ON u.ref=eak.user 
      LEFT JOIN usergroup ug ON ug.ref=eak.usergroup " .
                $conditional_sql .
     " GROUP BY access_key, collection
       ORDER BY eak." . escape_check($share_order_by) . " " . $share_sort;

    $external_shares = sql_query($external_access_keys_query);
    return $external_shares;
    }
