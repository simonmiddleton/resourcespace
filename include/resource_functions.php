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
* @uses ps_value()
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
    if(!preg_match('/^[a-zA-Z0-9]+$/',(string) $extension))
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
    if(!$getfilepath && $hide_real_filepath && !in_array($size,array("col","thm","pre")))
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

        if (!isset($get_resource_path_fpcache[$ref])) {$get_resource_path_fpcache[$ref]=ps_value("select file_path value from resource where ref=?",array("i",$ref),"");}
        $fp=$get_resource_path_fpcache[$ref]??"";

        # Test to see if this nosize file is of the extension asked for, else skip the file_path and return a $storagedir path.
        # If using staticsync, file path will be set already, but we still want the $storagedir path for a nosize preview jpg.
        # Also, returning the original filename when a nosize 'jpg' is looked for is no good, since preview_preprocessing.php deletes $target.
        $fp = $fp ?? "";
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
            $alt_data=ps_query('select ref,resource,name,description,file_name,file_extension,file_size,creation_date,unoconv,alt_type,page_count from resource_alt_files where ref=?',array("i",$alternative));
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
        $extension=ps_value("select file_extension value from resource where ref=?", array("i",$ref), 'jpg');
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

    # Build a string that will return the 'join' columns (not actually joins but cached truncated metadata stored at the resource level)
    $joins=get_resource_table_joins();
    $join_fields="";foreach ($joins as $j) {$join_fields.=",field" . $j;}

    $resource=ps_query("select ref,title,resource_type,has_image,is_transcoding,hit_count,new_hit_count,creation_date,rating,user_rating,user_rating_count,user_rating_total,country,file_extension,preview_extension,image_red,image_green,image_blue,thumb_width,thumb_height,archive,access,colour_key,created_by,file_path,file_modified,file_checksum,request_count,expiry_notification_sent,preview_tweaks,geo_lat,geo_long,mapzoom,disk_usage,disk_usage_last_updated,file_size,preview_attempts,modified,last_verified,integrity_fail,lock_user" . $join_fields . " from resource where ref=?",array("i",$ref));
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
                
                $default_archive_state = get_default_archive_state();
                $wait = ps_query("insert into resource (ref,resource_type,created_by, archive) values (?,?,?,?)",array("i",$ref,"i",$default_resource_type,"i",$user,"i",$default_archive_state));

                $resource = ps_query("select ref,title,resource_type,has_image,is_transcoding,hit_count,new_hit_count,creation_date,rating,user_rating,user_rating_count,user_rating_total,country,file_extension,preview_extension,image_red,image_green,image_blue,thumb_width,thumb_height,archive,access,colour_key,created_by,file_path,file_modified,file_checksum,request_count,expiry_notification_sent,preview_tweaks,geo_lat,geo_long,mapzoom,disk_usage,disk_usage_last_updated,file_size,preview_attempts,modified,last_verified,integrity_fail,lock_user" . $join_fields . " from resource where ref=?",array("i",$ref));
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

    if (count($resids) === 0)
        {
        return array();
        }

    # Build a string that will return the 'join' columns (not actually joins but cached truncated metadata stored at the resource level)
    $joins=get_resource_table_joins();
    $join_fields="";foreach ($joins as $j) {$join_fields.=",field" . $j;}
   
    $resdata=ps_query("SELECT ref,title,resource_type,has_image,is_transcoding,hit_count,new_hit_count,creation_date,rating,user_rating,user_rating_count,user_rating_total,country,file_extension,preview_extension,image_red,image_green,image_blue,thumb_width,thumb_height,archive,access,colour_key,created_by,file_path,file_modified,file_checksum,request_count,expiry_notification_sent,preview_tweaks,geo_lat,geo_long,mapzoom,disk_usage,disk_usage_last_updated,file_size,preview_attempts,modified,last_verified,integrity_fail,lock_user" . $join_fields . " FROM resource WHERE ref IN (" . ps_param_insert(count($resids)). ")",ps_param_fill($resids,"i"));
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
    $safe_column_types=array("i","s","d","i","i","i","d","s","s","s");

    // Permit the created by column to be changed also
    if (checkperm("v") && $edit_contributed_by) {$safe_columns[]="created_by";}

    $sql="";$params=array();
    foreach ($data as $column=>$value)
        {
        if (!in_array($column,$safe_columns)) {return false;} // Attempted to update a column outside of the expected set
        if ($sql!="") {$sql.=",";}
        $sql.=$column . "=?";
        $params[]=$safe_column_types[array_search($column,$safe_columns)]; // Fetch type to use
        $params[]=$value;
        }
    if ($sql=="") {return false;} // Nothing to do.
    $params[]="i";$params[]=$resource;
    ps_query("update resource set $sql where ref=?",$params);
    return true;
    }


function create_resource($resource_type,$archive=999,$user=-1)
    {
    # Create a new resource.
    global $k;

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

    if($user == -1)
        {
        global $userref;
        $user = $userref;
        }

    ps_query("INSERT INTO resource(resource_type,creation_date,archive,created_by) VALUES (?,NOW(),?,?)",["i",$resource_type,"i",$archive,"i",$user]);

    $insert=sql_insert_id();

    # set defaults for resource here (in case there are edit filters that depend on them)
    set_resource_defaults($insert);

    hook('resourcecreate', '', array($insert, $resource_type));

    # Autocomplete any blank fields.
    autocomplete_blank_fields($insert, true);

    # Log this
    daily_stat("Create resource",$insert);

    resource_log($insert, LOG_CODE_CREATED, 0);
    if(upload_share_active())
        {
        resource_log($insert, LOG_CODE_EXTERNAL_UPLOAD, 0,'','',$k . ' ('  . get_ip() . ')');
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
        ps_query("update resource set new_hit_count=greatest(hit_count,new_hit_count)+1 where ref=?",array("i",$ref),false,-1,true,0);
        }
    }

function save_resource_data($ref,$multi,$autosave_field="")
    {
    # Save all submitted data for resource $ref.
    # Also re-index all keywords from indexable fields.
    global $lang, $multilingual_text_fields,
           $languages, $language, $FIXED_LIST_FIELD_TYPES,
           $DATE_FIELD_TYPES, $date_validator, $range_separator, $reset_date_field, $reset_date_upload_template,
           $edit_contributed_by, $new_checksums, $upload_review_mode, $blank_edit_template, $is_template, $NODE_FIELDS,
           $userref, $NODE_MIGRATED_FIELD_TYPES;

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
    $nodes_to_add       = [];
    $nodes_to_remove    = [];
    $nodes_check_delete = [];

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

                if(count($added_nodes) > 0 || count($removed_nodes) > 0)
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
                        if ((1 == $fields[$n]['required'] && "" != $new_nodes_val) || 0 == $fields[$n]['required']) # If joined field is required we shouldn't be able to clear it.
                            {
                            update_resource_field_column($ref,$fields[$n]["ref"],$new_nodes_val);
                            }
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
                } // End of if in $FIXED_LIST_FIELD_TYPES
            else
                {
                if($fields[$n]['type']==FIELD_TYPE_DATE_RANGE)
                    {
                    # date range type
                    # each value will be a node so we end up with a pair of nodes to represent the start and end dates

                    $daterangenodes=array();
                    $newval="";

                    if(($date_edtf=getval("field_" . $fields[$n]["ref"] . "_edtf",""))!=="")
                        {
                        // We have been passed the range in EDTF format, check it is in the correct format
                        $rangeregex="/^(\d{4})(-\d{2})?(-\d{2})?\/(\d{4})(-\d{2})?(-\d{2})?/";
                        if(!preg_match($rangeregex,$date_edtf,$matches))
                            {
                            $errors[$fields[$n]["ref"]] = $lang["information-regexp_fail"] . " : " . $date_edtf;
                            continue;
                            }
                        if(is_int_loose($fields[$n]["linked_data_field"]))
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
                        $daterangenodes[]=set_node(null, $fields[$n]["ref"], $rangestart, null, null);
                        $daterangenodes[]=set_node(null, $fields[$n]["ref"], $rangeend, null, null);
                        }
                    else
                        {
                        // Range has been passed via normal inputs, construct the value from the date/time dropdowns
                        $date_parts=array("_start_","_end_");

                        foreach($date_parts as $date_part)
                            {
                            $val = getval("field_" . $fields[$n]["ref"] . $date_part . "year","");
                            if (intval($val)<=0)
                                {
                                $val="";
                                }
                            elseif (($field=getval("field_" . $fields[$n]["ref"] . $date_part . "month",""))!="")
                                {
                                $val.="-" . $field;
                                if (($field=getval("field_" . $fields[$n]["ref"] . $date_part . "day",""))!="")
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
                                $daterangenodes[]=set_node(null, $fields[$n]["ref"], $val, null, null);
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
                                update_resource_field_column($ref,$fields[$n]["ref"],$newval);
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
                elseif (
                    $multilingual_text_fields
                    && (
                        $fields[$n]["type"]==FIELD_TYPE_TEXT_BOX_SINGLE_LINE
                        || $fields[$n]["type"]==FIELD_TYPE_TEXT_BOX_MULTI_LINE
                        || $fields[$n]["type"]==FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE
                        )
                        )
                    {
                    # Construct a multilingual string from the submitted translations
                    $val = getval("field_" . $fields[$n]["ref"],"");
                    $rawval = getval("field_" . $fields[$n]["ref"],"");
                    $val="~" . $language . ":" . $val;
                    reset ($languages);
                    foreach ($languages as $langkey => $langname)
                        {
                        if ($language!=$langkey)
                            {
                            $val.="~" . $langkey . ":" . getval("multilingual_" . $n . "_" . $langkey,"");
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
                    $val=getval("field_" . $fields[$n]["ref"],"");
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
                    global $regexp_slash_replace;
                    if(preg_match("#^" . str_replace($regexp_slash_replace, '\\',$fields[$n]["regexp_filter"]) . "$#",$val,$matches)<=0)
                        {
                        global $lang;
                        debug($lang["information-regexp_fail"] . ": -" . "reg exp: " . str_replace($regexp_slash_replace, '\\',$fields[$n]["regexp_filter"]) . ". Value passed: " . $val);
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
                } // End of if not a fixed list field
            if(
                $fields[$n]['required'] == 1
                && check_display_condition($n, $fields[$n], $fields, false)
                && (
                    // No nodes submitted
                    (in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES) && count($ui_selected_node_values) == 0)
                    // No value submitted
                    || (!in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES) && trim(strip_leading_comma($val)) == '')
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
                $field_visibility_status=getval("field_".$fields[$n]['ref']."_displayed","");
                # Register an error only if the required field was actually displayed
                if (is_field_displayed($fields[$n]) && $field_visibility_status=="block")
                   {
                   $errors[$fields[$n]['ref']] = i18n_get_translated($fields[$n]['title']) . ": {$lang['requiredfield']}";
                   }
                continue;
                }

            // If all good so far, then save the data
            if(
                in_array($fields[$n]['type'],$NODE_MIGRATED_FIELD_TYPES)
                &&
                str_replace("\r\n", "\n", $fields[$n]['value']) !== str_replace("\r\n", "\n", unescape($val))
                )
                {
                # This value is different from the value we have on record.
                # Expiry field? Set that expiry date(s) have changed so the expiry notification flag will be reset later in this function.
                if ($fields[$n]["type"]==FIELD_TYPE_EXPIRY_DATE)
                    {
                    $expiry_field_edited=true;
                    }

                $use_node = NULL;
                if(trim($fields[$n]["nodes"]) != "")
                    {
                    // Remove any existing node IDs for this non-fixed list field (there should only be one) unless used by other resources.
                    $current_field_nodes = array_filter(explode(",",$fields[$n]["nodes"]),"is_int_loose");

                    foreach($current_field_nodes as $current_field_node)
                        {
                        $inuse = get_nodes_use_count([$current_field_node]);
                        $inusecount = $inuse[$current_field_node] ?? 0;
                        if ($current_field_node > 0 && $inusecount == 1)
                            {
                            // Reuse same node
                            $use_node = $current_field_node;
                            }
                        else
                            {
                            // Remove node from resource and create new node
                            $nodes_to_remove[] = $current_field_node;
                            $nodes_check_delete[] = $current_field_node;
                            }
                        }
                    }

                # Add new node unlesss empty string
                if($val == '')
                    {
                    // Remove and delete node
                    $nodes_to_remove[] = $current_field_node;
                    $nodes_check_delete[] = $current_field_node;
                    }
                else
                    {
                    $newnode = set_node($use_node, $fields[$n]["ref"], $val, null, null);
                    $nodes_to_add[] = ((int)$use_node > 0) ? $use_node : $newnode;
                    }

                # If this is a 'joined' field we need to add it to the resource column
                $joins=get_resource_table_joins();
                if (in_array($fields[$n]["ref"],$joins))
                    {
                    update_resource_field_column($ref,$fields[$n]["ref"],$val);
                    }
                }
            # Add any onchange code
            if($fields[$n]["onchange_macro"]!="")
                {
                eval(eval_check_signed($fields[$n]["onchange_macro"]));
                }
			} # End of if "allowed to edit field conditions"
		} # End of for $fields

    // When editing a resource, prevent applying the change to the resource if there are any errors
    if(count($errors) > 0 && $ref > 0)
        {
        return $errors;
        }

   # Save related resource field if value for Related input field is autosaved, or if form has been submitted by user
    if (($autosave_field=="" || $autosave_field=="Related") && isset($_POST["related"]))
        {
        # save related resources field
        ps_query("DELETE FROM resource_related WHERE resource = ? OR related = ?",["i",$ref,"i",$ref]); # remove existing related items
        $related=explode(",",getval("related",""));
        # Trim whitespace from each entry
        foreach ($related as &$relatedentry)
            {
            $relatedentry = trim($relatedentry);
            }
        # Make sure all submitted values are numeric
        $to_relate = array_filter($related,"is_int_loose");
        if(count($to_relate)>0)
            {
            update_related_resource($ref,$to_relate,true);
            }
        }

    // Update resource_node table
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

    if(count($nodes_check_delete)>0)
        {
        // This has to be after call to log_node_changes() or nodes cannot be resolved
        check_delete_nodes($nodes_check_delete);
        }

    db_end_transaction("update_resource_node");

    // Autocomplete any blank fields without overwriting any existing metadata
    $autocomplete_fields = autocomplete_blank_fields($ref, false, true);
    foreach($autocomplete_fields as $autocomplete_field_ref => $autocomplete_field_value)
        {
        $new_checksums[$autocomplete_field_ref] = md5($autocomplete_field_value);
        }

    // Initialise an array of updates for the resource table
    $resource_update_sql = array();
    $resource_update_params = array();
    $resource_update_log_sql = array();
    if($edit_contributed_by)
        {
        $created_by = $resource_data['created_by'];
        $new_created_by = getval("created_by",0,true);
        if((getval("created_by",0,true) > 0) && $new_created_by != $created_by)
            {
            # Also update created_by
            $resource_update_sql[] = "created_by= ?";
            $resource_update_params[]="i";$resource_update_params[]=$new_created_by;
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
		$access=getval("access",$oldaccess,true);

		$oldarchive=$resource_data['archive'];
		$setarchivestate=getval("status",$oldarchive,true);
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
                $resource_update_sql[] = "access= ?";
                $resource_update_params[]="i";$resource_update_params[]=$access;
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
        $sql = "UPDATE resource SET " . implode(",",$resource_update_sql) . " WHERE ref=?";
        $sqlparams = array_merge($resource_update_params,["i",$ref]);
        ps_query($sql,$sqlparams);
        foreach($resource_update_log_sql as $log_sql)
            {
            resource_log($log_sql["ref"],$log_sql["type"],$log_sql["field"],$log_sql["notes"],$log_sql["from"],$log_sql["to"]);
            }
        }

    # Save any custom permissions
    if (getval("access",0)==RESOURCE_ACCESS_CUSTOM_GROUP)
        {
        save_resource_custom_access($ref);
        }

    // Plugins can do extra actions once all fields have been saved and return errors back if needed
    $plg_errors = hook('aftersaveresourcedata', '', array($ref, $nodes_to_add, $nodes_to_remove, $autosave_field, $fields));
    if(is_array($plg_errors) && !empty($plg_errors))
        {
        $errors = array_merge($errors, $plg_errors);
        }

    if (count($errors)==0)
        {
        daily_stat("Resource edit", $ref);
        return true;
        }
    return $errors;
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
        $rule_detail         = explode('=', trim($rule));
        $field_shortname     = $rule_detail[0];
        $field_default_value = $rule_detail[1];

        // Find field(s) - multiple fields can be returned to support several fields with the same name
        $fields = ps_array("SELECT ref AS `value` FROM resource_type_field WHERE name = ?",["s",$field_shortname],"schema");

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
    global $FIXED_LIST_FIELD_TYPES,$DATE_FIELD_TYPES, $range_separator, $date_validator, $edit_contributed_by, $TEXT_FIELD_TYPES, $userref, $lang, $multilingual_text_fields, $languages, $language;

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
            // Append option(s) mode?
            $mode = getval("modeselect_" . $fields[$n]["ref"],"");
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

                if ($mode=="AP")
                   {
                   $nodes_to_add = $ui_selected_node_values;
                   }
                elseif ($mode=="RM")
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

                // Loop through all the resources and check current node values so we can check if we need to log this as a change
                for ($m=0;$m<count($list);$m++)
                    {
                    $ref            = $list[$m];
                    $value_changed  = false;

                    $current_field_nodes = get_resource_nodes($ref, $fields[$n]['ref']);
                    debug('Current nodes for resource #' . $ref . ' : ' . implode(',',$current_field_nodes));

                    $added_nodes = array_diff($nodes_to_add,$current_field_nodes);
                    debug('Adding nodes to resource #' . $ref . ' : ' . implode(',',$added_nodes));

                    $removed_nodes = array_intersect($nodes_to_remove,$current_field_nodes);
                    debug('Removed nodes from resource #' . $ref . ' : ' . implode(',',$removed_nodes));

                    // Work out what new nodes for this resource  will be
                    $new_nodes = array_diff(array_merge($current_field_nodes, $added_nodes), $removed_nodes);
                    debug('New nodes: ' . implode(',',$new_nodes));

                    if(count($added_nodes)>0 || count($removed_nodes)>0)
                        {
                        $value_changed  = true;
                        }

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

                        log_node_changes($ref,$added_nodes,$removed_nodes);

                        // If this is a 'joined' field it still needs to add it to the resource column
                        $joins = get_resource_table_joins();
                        if(in_array($fields[$n]['ref'], $joins))
                            {
                            update_resource_field_column($ref,$fields[$n]["ref"],$new_nodes_val);
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

                if(($date_edtf=getval("field_" . $fields[$n]["ref"] . "_edtf",""))!=="")
                    {
                    // We have been passed the range in EDTF format, check it is in the correct format
                    $rangeregex="/^(\d{4})(-\d{2})?(-\d{2})?\/(\d{4})(-\d{2})?(-\d{2})?/";
                    if(!preg_match($rangeregex,$date_edtf,$matches))
                        {
                        $errors[$fields[$n]["ref"]]=$lang["information-regexp_fail"] . " : " . $rangeregex;
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
                    $daterangenodes[]=set_node(null, $fields[$n]["ref"], $rangestart, null, null);
                    $daterangenodes[]=set_node(null, $fields[$n]["ref"], $rangeend, null, null);
                    }
                else
                    {
                    // Range has been passed via normal inputs, construct the value from the date/time dropdowns
                    $date_parts=array("_start_","_end_");

                    foreach($date_parts as $date_part)
                        {
                        $val = getval("field_" . $fields[$n]["ref"] . $date_part . "year","");
                        if (intval($val)<=0)
                            {
                            $val="";
                            }
                        elseif (($field=getval("field_" . $fields[$n]["ref"] . $date_part . "month",""))!="")
                            {
                            $val.="-" . $field;
                            if (($field=getval("field_" . $fields[$n]["ref"] . $date_part . "day",""))!="")
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
                            $daterangenodes[]=set_node(null, $fields[$n]["ref"], $val, null, null);
                            }
                        }
                    }
                    // Get currently selected nodes for this field
                    $current_field_nodes = get_resource_nodes($ref, $fields[$n]['ref']);

                    $added_nodes = array_diff($daterangenodes, $current_field_nodes);
                    debug("save_resource_data_multi(): Adding nodes to resource " . $ref . ": " . implode(",",$added_nodes));
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
                            update_resource_field_column($ref,$fields[$n]["ref"],$newval);
                            }
                        }
                }
            else
                {
                if(in_array($fields[$n]['type'], $DATE_FIELD_TYPES))
					{
                    # date/expiry date type, construct the value from the date dropdowns
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
                    }
                elseif (
                        $multilingual_text_fields
                    && (
                        $fields[$n]["type"]==FIELD_TYPE_TEXT_BOX_SINGLE_LINE
                        || $fields[$n]["type"]==FIELD_TYPE_TEXT_BOX_MULTI_LINE
                        || $fields[$n]["type"]==FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE
                        )
                    )
                    {
                    # Construct a multilingual string from the submitted translations
                    $val = getval("field_" . $fields[$n]["ref"],"");
                    $val="~" . $language . ":" . $val;
                    reset ($languages);
                    foreach ($languages as $langkey => $langname)
                        {
                        if ($language!=$langkey)
                            {
                            $val.="~" . $langkey . ":" . getval("multilingual_" . $n . "_" . $langkey,"");
                            }
                        }
                    }
                else
                    {
                    $val=getval("field_" . $fields[$n]["ref"],"");
                    }

                $origval = $val;

                # Loop through all the resources and save.
                for ($m=0;$m<count($list);$m++)
                    {
                    $ref            = $list[$m];
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
                    $existing = get_data_by_field($ref,$fields[$n]['ref']);
                    if ($mode=="FR")
                        {
                        # Find and replace mode? Perform the find and replace.

                        $findstring     = getval("find_" . $fields[$n]["ref"],"");
                        $replacestring  = getval("replace_" . $fields[$n]["ref"],"");
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
                    elseif ($mode=="AP" && in_array($fields[$n]["type"],$TEXT_FIELD_TYPES))
                        {
                        $val = $existing . " " . $origval;
                        }

                    # Prepend text/option(s) mode?
                    elseif ($mode=="PP" && in_array($fields[$n]["type"],$TEXT_FIELD_TYPES))
                        {
                        global $filename_field;
                        if ($fields[$n]["ref"]==$filename_field)
                            {
                            $val=rtrim($origval,"_")."_".trim($existing); // use an underscore if editing filename.
                            }
                        else {
                            # Automatically append a space when appending text types.
                            $val = $origval . " " . $existing;
                            }
                        }
                    elseif ($mode=="RM")
                        {
                        # Remove text/option(s) mode
                        $val = str_replace($origval,"",$existing);
                        if($fields[$n]["required"] && strip_leading_comma($val)=="")
                            {
                            // Required field and  no value now set, revert to existing and add to array of failed edits
                            $val=$existing;
                            if(!isset($errors[$fields[$n]["ref"]]))
                                {
                                $errors[$fields[$n]["ref"]]=$lang["requiredfield"] . ". " . $lang["error_batch_edit_resources"] . ": " ;
                                }
                            $errors[$fields[$n]["ref"]] .=  $ref;
                            if($m<count($list)-1)
                                {
                                $errors[$fields[$n]["ref"]] .= ",";
                                }
                            }
                        }
                    elseif ($mode=="CF")
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
                            // Required field and no value now set, revert to existing and add to array of failed edits
                            $val=$existing;
                            if(!isset($errors[$fields[$n]["ref"]]))
                                {$errors[$fields[$n]["ref"]]=$lang["requiredfield"] . ". " . $lang["error_batch_edit_resources"] . ": " ;}
                            $errors[$fields[$n]["ref"]] .=  $ref;
                            if($m<count($list)-1)
                                {
                                $errors[$fields[$n]["ref"]] .= ",";
                                }
                            continue;
                            }
                        }

                    # Possibility to hook in and alter the value - additional mode support
                    $hookval = hook('save_resource_data_multi_extra_modes', '', array($ref, $fields[$n]));
                    if($hookval !== false)
                        {
                        $val = $hookval;
                        }

                    # Check for regular expression match
                    if (trim(strlen($fields[$n]["regexp_filter"]))>=1 && strlen($val)>0)
                        {
                        global $regexp_slash_replace;
                        if(preg_match("#^" . str_replace($regexp_slash_replace, '\\',$fields[$n]["regexp_filter"]) . "$#",$val,$matches)<=0)
                            {
                            global $lang;
                            debug($lang["information-regexp_fail"] . ": -" . "reg exp: " . str_replace($regexp_slash_replace, '\\',$fields[$n]["regexp_filter"]) . ". Value passed: " . $val);
                            $errors[$fields[$n]["ref"]]=$lang["information-regexp_fail"] . " : " . $val;
                            continue;
                            }
                        }

                    if ($val !== $existing || $value_changed)
                        {
                        // This value is different from the value we have on record.
                        // Write this edit to the log.
                        resource_log($ref,LOG_CODE_MULTI_EDITED,$fields[$n]["ref"],"",$existing,$val);
                        $successfully_edited_resources[] = $ref;

                        # Expiry field? Set that expiry date(s) have changed so the expiry notification flag will be reset later in this function.
                        if ($fields[$n]["type"]==FIELD_TYPE_EXPIRY_DATE)
                            {
                            $expiry_field_edited=true;
                            }
                        if(trim($existing) != "")
                            {
                            // Remove any existing node IDs for this non-fixed list field (there should only be one).
                            $current_field_nodes = get_resource_nodes($ref,$fields[$n]["ref"]);
                            $nodes_to_remove = array_merge($nodes_to_remove,$current_field_nodes);
                            }
                        // Add new node
                        if($val !=='')
                            {
                            $newnode = set_node(null, $fields[$n]["ref"], $val, null, null);
                            if(in_array($mode,["PP","AP","FR","CF","RM"]))
                                {
                                // Don't add to $nodes_to_add if appending/prepending as the value may differ for each resource
                                add_resource_nodes($ref,[$newnode]);
                                }
                            else
                                {
                                $nodes_to_add[] = $newnode;
                                }
                            }

                        // If this is a 'joined' field we need to add it to the resource column
                        $joins=get_resource_table_joins();
                        if (in_array($fields[$n]["ref"],$joins))
                            {
                            update_resource_field_column($ref,$fields[$n]["ref"],$val);
                            }

                        $newval=$val;

                        // Add any onchange code
                        if($fields[$n]["onchange_macro"]!="")
                            {
                            eval(eval_check_signed($fields[$n]["onchange_macro"]));
                            }
                        }
                    }
                }  // End of non-node editing section
            $all_nodes_to_add    = array_unique(array_merge($all_nodes_to_add,$nodes_to_add));
            $all_nodes_to_remove = array_unique(array_merge($all_nodes_to_remove,$nodes_to_remove));
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
        $related = explode(',', getval('related', ''));

        // Make sure all submitted values are numeric
        $resources_to_relate = array();
        for($n = 0; $n < count($related); $n++)
            {
            if(is_numeric(trim($related[$n])))
                {
                $resources_to_relate[] = trim($related[$n]);
                }
            }

        // Clear out all relationships between related resources in this collection
        ps_query("
                DELETE rr
                  FROM resource_related AS rr
            INNER JOIN collection_resource AS cr ON rr.resource = cr.resource
                 WHERE cr.collection = ?",
                 ["i",$collection]
                );

        for($m = 0; $m < count($list); $m++)
            {
            $ref = $list[$m];
            // Only add new relationships
            $existing_relations = ps_array("SELECT related value FROM resource_related WHERE resource = ?", array("i", $ref));

            // Don't relate a resource to itself
            $for_relate_sql = array();
            $for_relate_parameters = array();
            foreach ($resources_to_relate as $resource_to_relate)
                {
                if ($ref != $resource_to_relate && !in_array($resource_to_relate, $existing_relations))
                    {
                    $for_relate_sql = array_merge($for_relate_sql, array('(?, ?)'));
                    $for_relate_parameters = array_merge($for_relate_parameters, array("i", $ref, "i", $resource_to_relate));
                    }
                }

            if(0 < count($for_relate_sql))
                {
                ps_query("INSERT INTO resource_related (resource, related) VALUES " . implode(",", $for_relate_sql), $for_relate_parameters);
                $successfully_edited_resources[] = $ref;
                }
            }
        }

	# Also update archive status
	if (getval("editthis_status","")!="")
		{
		$notifyrefs=array();
		$usernotifyrefs=array();
		for ($m=0;$m<count($list);$m++)
			{
			$ref=$list[$m];

            if (!hook('forbidsavearchive', '', array($errors)))
                {
                $oldarchive = ps_value("SELECT archive value FROM resource WHERE ref = ?" ,["i",$ref],"");
                $setarchivestate = getval("status",$oldarchive,true); // Originally used to get the 'archive' value but this conflicts with the archive used for searching
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
			ps_query("UPDATE resource SET expiry_notification_sent=0 WHERE ref IN (" . ps_param_insert(count($list)) . ")",ps_param_fill($list,"i"));
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
            $created_by = ps_value("select created_by value from resource where ref=?",array("i",$ref),""); 
            $new_created_by = getval("created_by",0,true);
            if((getval("created_by",0,true) > 0) && $new_created_by != $created_by)
                {
                ps_query("update resource set created_by=? where ref=?",array("i",$new_created_by,"i",$ref)); 
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
			$access=getval("access",0);
			$oldaccess=ps_value("select access value from resource where ref=?",array("i",$ref),"");
			if ($access!=$oldaccess)
				{
				ps_query("update resource set access=? where ref=?",array("i",$access,"i",$ref));				
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
            update_resource_type($ref,getval("resource_type",""));
            $successfully_edited_resources[] = $ref;
            }
        }

    # Update location?
    if (getval("editlocation","")!="")
        {
        $location=explode(",",getval("location",""));
        if (count($list)>0)
            {
            if (count($location)==2)
                {
                $geo_lat=(float)$location[0];
                $geo_long=(float)$location[1];
                ps_query("UPDATE resource SET geo_lat = ?,geo_long = ? WHERE ref IN (" . ps_param_insert(count($list)) . ")",array_merge(["d",$geo_lat,"d",$geo_long],ps_param_fill($list,"i")));
                }
            elseif (getval("location","")=="")
                {
                ps_query("UPDATE resource SET geo_lat=NULL,geo_long=NULL WHERE ref IN (" . ps_param_insert(count($list)) . ")",ps_param_fill($list,"i"));
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
        $mapzoom=getval("mapzoom","");
        if (count($list)>0)
            {
            if ($mapzoom!="")
                {
                ps_query("UPDATE resource SET mapzoom = ? WHERE ref IN (" . ps_param_insert(count($list)) . ")",array_merge(["i",$mapzoom], ps_param_fill($list,"i")));
                }
            else
                {
                ps_query("UPDATE resource SET mapzoom=NULL WHERE ref IN (" . ps_param_insert(count($list)) . ")",ps_param_fill($list,"i"));
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

    // Check if any nodes can be deleted
    check_delete_nodes($all_nodes_to_remove);

    if (count($errors)==0)
        {
        return true;
        }
    else
        {
        return $errors;
        }
    }


/**
* Updates resource field. Works out the previous value, so this is
* not efficient if we already know what this previous value is (hence
* it is not used for edit where multiple fields are saved)
*
* @param integer $resource      Resource ID
* @param integer $field         Field ID
* @param string  $value         The new value
* @param array   &$errors       Any errors that may occur during update
* @param boolean $log           Log this change in the resource log?
* @param boolean $nodevalues    Set to TRUE to process the value as a comma separated list of node IDs
*
* @return boolean
*/
function update_field($resource, $field, $value, array &$errors = array(), $log=true, $nodevalues=false)
    {
    global $category_tree_add_parents, $userref, $NODE_MIGRATED_FIELD_TYPES, $DATE_FIELD_TYPES;

    $resource_data = get_resource_data($resource);
    if ($resource_data["lock_user"] > 0 && $resource_data["lock_user"] != $userref)
        {
        $errors[] = get_resource_lock_message($resource_data["lock_user"]);
        return false;
        }

    // accept shortnames in addition to field refs
    if(!is_int_loose($field))
        {
        $field = ps_value("SELECT ref AS `value` FROM resource_type_field WHERE name = ?", ["s",$field], '', "schema");
        }

    // Fetch some information about the field
    $fieldinfo = get_resource_type_field($field);

    if(!$fieldinfo)
        {
        $errors[] = "No field information about field ID '{$field}'";
        return false;
        }

    if (!in_array($fieldinfo['resource_type'], array(0, $resource_data['resource_type'])))
        {
        $errors[] = "Field is not valid for this resource type";
        return false;
        }

    $value = trim($value);
    if($value === '' && $fieldinfo['required'])
        {
        return false;
        }

    if (!in_array($fieldinfo['type'], $NODE_MIGRATED_FIELD_TYPES))
        {
        // Standard node fields
        // Set up arrays of node ids to add/remove and all new nodes.
        $nodes_to_add    = array();
        $nodes_to_remove = array();
        $newnodes        = array();
        $existingnodes   = array();
        $fieldnodes      = get_nodes($field,null,$fieldinfo['type'] == FIELD_TYPE_CATEGORY_TREE);
        $node_options = array_column($fieldnodes, 'name', 'ref');

        // Get all the new values into an array
        if (strlen($value) > 0 && (($value[0] == "'" && $value[strlen($value)-1] == "'")
            ||
            ($value[0] == "\"" && $value[strlen($value)-1] == "\""))
            )
            {
            // Quoted value - don't attempt to split on comma.
            $newvalues[] = substr($value,1,-1);
            }
        else
            {
            $newvalues = trim_array(str_getcsv($value));
            }

        // Get currently selected nodes for this field
        $current_field_nodes = get_resource_nodes($resource, $field, true);
        $current_field_noderefs = array_column($current_field_nodes,"ref");
        // Build 'existing' value
        if($fieldinfo['type']==FIELD_TYPE_CATEGORY_TREE)
            {
            $treetext_arr = get_tree_strings($current_field_nodes);
            $existing = implode(",",$treetext_arr);
            }
        else
            {
            foreach($current_field_noderefs as $current_field_node)
                {
                $existingnodes[] = $node_options[$current_field_node];
                }
            $existing = implode(",",$existingnodes);
            }
        if($nodevalues)
            {
            // An array of node IDs has been passed, we can use these directly
            $sent_nodes = explode(",",$value);
            if(in_array($fieldinfo['type'],[FIELD_TYPE_RADIO_BUTTONS,FIELD_TYPE_DROP_DOWN_LIST]) && count($sent_nodes) > 1)
                {
                // Only a single value allowed
                return false;
                }

            foreach($fieldnodes as $fieldnode)
                {
                // Add to array of nodes, unless it has been added to array already as a parent for a previous node
                if (in_array($fieldnode["ref"],$sent_nodes) && !in_array($fieldnode["ref"],$nodes_to_add))
                    {
                    if(!in_array($fieldnode["ref"],$current_field_noderefs))
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
                else if(in_array($fieldnode["ref"],$current_field_noderefs) && !in_array($fieldnode["name"],$sent_nodes))
                    {
                    $nodes_to_remove[] = $fieldnode["ref"];
                    }
                }
            if(count($newnodes) != count($sent_nodes))
                {
                // Unable to find all node values that were passed
                return false;
                }
            // Build array of new values
            foreach($newnodes as $newnode)
                {
                $newvalues[] = $node_options[$newnode];
                }
            }
        else
            {
            // Not node IDs - value has been passed as normal string value
            if($fieldinfo['type'] == FIELD_TYPE_DATE_RANGE)
                {
                // If this is a date range field we need to add values to the field options
                $newvalues = array_map('trim', explode('/', $value));
                $currentoptions = array();

                foreach($newvalues as $newvalue)
                    {
                    # Check if each new value exists in current options list
                    if('' != $newvalue && !in_array($newvalue, $currentoptions))
                        {
                        # Append the option and update the field
                        $newnode          = set_node(null, $field, trim($newvalue), null, null);
                        $nodes_to_add[]   = $newnode;
                        $currentoptions[] = trim($newvalue);

                        debug("update_field: field option added: '" . trim($newvalue));
                        }
                    }
                }
            elseif($fieldinfo['type'] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && !checkperm('bdk' . $field))
                {
                // If this is a dynamic keyword field need to add any new entries to the field nodes
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
                        $newnode            = set_node(null, $field, trim($newvalue), null, null);
                        $nodes_to_add[]     = $newnode;
                        $currentoptions[]   = trim($newvalue);
                        $fieldnodes[]       = array("ref" => $newnode,"name" => trim($newvalue));
                        debug("update_field: field option added: '" . trim($newvalue));
                        }
                    }
                }

            $newvalues_translated = $newvalues;
            array_walk(
                $newvalues_translated,
                function (&$value, $index)
                    {
                    $value = mb_strtolower(i18n_get_translated($value));
                    }
                );
            foreach($fieldnodes as $fieldnode)
                {
                // Add to array of nodes, unless it has been added to array already as a parent for a previous node
                if (in_array(mb_strtolower(i18n_get_translated($fieldnode["name"])), $newvalues_translated)
                    && !in_array($fieldnode["ref"], $nodes_to_add)
                    )
                    {
                    $nodes_to_add[] = $fieldnode["ref"];
                    // We need to add all parent nodes for category trees
                    if($fieldinfo['type']==FIELD_TYPE_CATEGORY_TREE && $category_tree_add_parents)
                        {
                        $parent_nodes=get_parent_nodes($fieldnode["ref"]);
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
                elseif(!in_array($fieldnode["ref"], $nodes_to_add))
                    {
                    $nodes_to_remove[] = $fieldnode["ref"];
                    }
                }

            if(count($nodes_to_add) > 0 || count($nodes_to_remove) > 0)
                {
                # Work out what nodes need to be added/removed/kept
                $nodes_to_add       = array_unique($nodes_to_add);
                $nodes_to_remove    = array_unique($nodes_to_remove);
                $added_nodes        = array_diff($nodes_to_add,$current_field_noderefs);
                $removed_nodes      = array_intersect($nodes_to_remove,$current_field_noderefs);
                $keep_nodes         = array_diff($current_field_noderefs,$removed_nodes);
                $all_new_nodes      = array_merge($added_nodes,$keep_nodes);

                if(in_array($fieldinfo['type'],[FIELD_TYPE_RADIO_BUTTONS,FIELD_TYPE_DROP_DOWN_LIST])
                    &&
                    (count($added_nodes) + count($current_field_nodes) - count($removed_nodes)) > 1)
                    {
                    // Only a single value allowed
                    return false;
                    }


                // Update resource_node table and log
                db_begin_transaction("update_field_{$field}");
                if(count($nodes_to_remove)>0)
                    {
                    delete_resource_nodes($resource,$nodes_to_remove,false);
                    }
                if(count($nodes_to_add)>0)
                    {
                    add_resource_nodes($resource,$nodes_to_add, false,false);
                    }

                // Update log
                if($log && count($nodes_to_add)>0 || count($nodes_to_remove)>0)
                    {
                    log_node_changes($resource,$added_nodes,$removed_nodes);
                    }

                db_end_transaction("update_field_{$field}");
                if($fieldinfo['type']==FIELD_TYPE_CATEGORY_TREE)
                    {
                    $all_new_nodes_full = get_nodes_by_refs($all_new_nodes);
                    $treetext_arr       = get_tree_strings($all_new_nodes_full);
                    $value              = implode(",",$treetext_arr);
                    }
                else
                    {
                    $value = implode(",",$newvalues);
                    }
                }
            }
        }
    else
        {
        # Fetch previous value
        $existing_resource_node = get_resource_nodes($resource, $field, true)[0] ?? [];
        $existing = $existing_resource_node["name"] ?? "";
        if ($value === $existing)
            {
            // Nothing to do
            return true;
            }

        $curnode = $existing_resource_node["ref"] ?? 0 ;
        if ($curnode > 0 && get_nodes_use_count([$curnode]) == 1)
            {
            // Reuse same node
            $savenode = set_node($curnode,$field,$value,NULL,0);
            }
        else
            {
            // Remove node from resource and create new node
            delete_resource_nodes($resource,[$curnode]);
            $savenode = set_node(NULL,$field,$value,NULL,0);
            add_resource_nodes($resource,[$savenode], true, true);
            }
        }

    # If this is a 'joined' field we need to add it to the resource column
    $joins = get_resource_table_joins();
    if(in_array($fieldinfo['ref'],$joins))
        {
        update_resource_field_column($resource,$field,$value);
        }

    # Add any onchange code
    if($fieldinfo["onchange_macro"]!="")
        {
        eval(eval_check_signed($fieldinfo["onchange_macro"]));
        }

    # Allow plugins to perform additional actions.
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
        if(isset($k))
            {
            if($expires=="")
                {
                $templatevars['expires_date']=$lang["email_link_expires_never"];
                $templatevars['expires_days']=$lang["email_link_expires_never"];
                }
            else
                {
                $day_count=round((strtotime($expires)-strtotime('now'))/(60*60*24));
                $templatevars['expires_date']=$lang['email_link_expires_date'].nicedate($expires);
                $templatevars['expires_days']=$lang['email_link_expires_days'].$day_count;
                if($day_count>1)
                    {
                    $templatevars['expires_days'].=" ".$lang['expire_days'].".";
                    }
                else
                    {
                    $templatevars['expires_days'].=" ".$lang['expire_day'].".";
                    }
                }
            }
        else
            {
            # Set empty expiration templatevars
            $templatevars['expires_date']='';
            $templatevars['expires_days']='';
            }
        
        # Build message and send.
        if (count($emails) > 1 && $list_recipients===true)
            {
            $body = $lang["list-recipients"] ."\n". implode("\n",$emails) ."\n\n";
            $templatevars['list-recipients']=$lang["list-recipients"] ."\n". implode("\n",$emails) ."\n\n";
            }
        else
            {
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
		ps_query("delete from collection_resource where resource=?",array("i",$ref));

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

    hook('delete_resource_extra', '', array($resource));

	# Delete any alternative files
	$alternatives=get_alternative_files($ref);
	for ($n=0;$n<count($alternatives);$n++)
		{
		delete_alternative_file($ref,$alternatives[$n]['ref']);
		}


	//attempt to remove directory
	$resource_path = get_resource_path($ref, true, "pre", true);

	$dirpath = dirname($resource_path);
    hook('delete_resource_path_extra', '', array($dirpath));
	@rcRmdir ($dirpath); // try to delete directory, but if we do not have permission fail silently for now

	# Log the deletion of this resource for any collection it was in.
	$in_collections=ps_query("select collection,resource from collection_resource where resource = ?",array("i",$ref));
	if (count($in_collections)>0){
		for($n=0;$n<count($in_collections);$n++)
			{
			collection_log($in_collections[$n]['collection'],'d',$in_collections[$n]['resource']);
			}
		}

	hook("beforedeleteresourcefromdb","",array($ref));

	# Delete all database entries
    clear_resource_data($ref);
    resource_log($ref,LOG_CODE_DELETED_PERMANENTLY,'');
	ps_query("delete from resource where ref=?",array("i",$ref));
    ps_query("delete from collection_resource where resource=?",array("i",$ref));
    ps_query("delete from resource_custom_access where resource=?",array("i",$ref));
    ps_query("delete from external_access_keys where resource=?",array("i",$ref));
	ps_query("delete from resource_alt_files where resource=?",array("i",$ref));
    ps_query(
        "    DELETE an
               FROM annotation_node AS an
         INNER JOIN annotation AS a ON a.ref = an.annotation
              WHERE a.resource = ?",array("i",$ref)
    );
    ps_query("DELETE FROM annotation WHERE resource = ?",array("i",$ref));
	hook("afterdeleteresource");

	return true;
	}


/**
* Returns field data from resource_type_field for the given field
*
* @uses ps_query()
*
* @param integer $field Resource type field ID
*
* @return boolean|array
*/
function get_resource_type_field($field)
    {
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
                tab,
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
          WHERE ref = ?
    ";
    $return = ps_query($rtf_query, array("i",$field), "schema");

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

    global $view_title_field, $metadata_template_resource_type, $NODE_FIELDS, $FIXED_LIST_FIELD_TYPES;

    # Find the resource type.
    if (is_null($originalref)) {$originalref = $ref;} # When a template has been selected, only show fields for the type of the original resource ref, not the template (which shows fields for all types)
    $rtype = ps_value("select resource_type value FROM resource WHERE ref=?",array("i",$originalref),0);
    $rtype = ($rtype == "") ? 0 : $rtype;

    # If using metadata templates,
    $templatesql = "";
    if (isset($metadata_template_resource_type) && $metadata_template_resource_type==$rtype) {
        # Show all resource fields, just as with editing multiple resources.
        $multi = true;
    }

    $return           = array();
    $order_by_sql     = ($ord_by ? 'f.order_by, f.resource_type, f.ref' : 'f.resource_type, f.order_by, f.ref');

    // Remove Category tree fields as these need special handling
    $node_fields    = array_diff($NODE_FIELDS,array(FIELD_TYPE_CATEGORY_TREE));

    $restypesql = "";
    $restype_params = [];
    if(!$multi)
        {
        $restypesql = "AND f.resource_type = 0 OR f.resource_type = 999 OR f.resource_type = ?";
        $restype_params[] = "i";$restype_params[] = $rtype;
        }

    $field_data_sql = "
             SELECT group_concat(n.name SEPARATOR ', ') AS `value`,
                    group_concat(n.ref) AS `nodes`,
                    f.ref,
                    f.ref resource_type_field,
                    f.ref AS fref,
                    f.name,
                    f.title,
                    f.field_constraint,
                    f.type,
                    f.order_by,
                    f.keywords_index,
                    f.partial_index,
                    f.resource_type,
                    f.resource_column,
                    f.display_field,
                    f.use_for_similar,
                    f.iptc_equiv,
                    f.display_template,
                    f.tab,
                    f.required,
                    f.required AS frequired,
                    f.smart_theme_name,
                    f.exiftool_field,
                    f.advanced_search,
                    f.simple_search,
                    f.help_text,
                    f.display_as_dropdown,
                    f.external_user_access,
                    f.autocomplete_macro,
                    f.hide_when_uploading,
                    f.hide_when_restricted,
                    f.value_filter,
                    f.exiftool_filter,
                    f.omit_when_copying,
                    f.tooltip_text,
                    f.regexp_filter,
                    f.sync_field,
                    f.display_condition,
                    f.onchange_macro,
                    f.linked_data_field,
                    f.automatic_nodes_ordering,
                    f.fits_field,
                    f.personal_data,
                    f.include_in_csv_export,
                    f.browse_bar,
                    f.read_only,
                    f.active,
                    f.full_width
               FROM resource_type_field f
          LEFT JOIN (SELECT ref, name, resource_type_field FROM node WHERE ref IN (SELECT node FROM resource_node WHERE resource = ?) ORDER BY order_by) AS n
                    ON n.resource_type_field = f.ref
              WHERE (f.active=1 AND f.type IN (" . ps_param_insert(count($node_fields)) . ") " . $restypesql . ")
              GROUP BY f.ref
              ORDER BY {$order_by_sql}";

    $field_data_params = array_merge(["i", $ref], ps_param_fill($node_fields,"i"),$restype_params);
    if(!$ord_by)
        {
        debug('GENERAL/GET_RESOURCE_FIELD_DATA: use perms: ' . !$use_permissions);
        }

    $fields = ps_query($field_data_sql,$field_data_params);
    # Build an array of valid types and only return fields of this type. Translate field titles.
    $validtypes = ps_array('SELECT ref AS `value` FROM resource_type',[],'schema');

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
    $inherit_global_fields = (bool) ps_value("SELECT inherit_global_fields AS `value` FROM resource_type WHERE ref = ?", array("i",$rtype), true, "schema");
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

            // Sort nodes
            if(in_array($fields[$n]['type'],$FIXED_LIST_FIELD_TYPES) 
                && $fields[$n]['type'] != FIELD_TYPE_CATEGORY_TREE 
                && trim($fields[$n]['nodes']??"") != "" 
                && (bool)$fields[$n]['automatic_nodes_ordering'])
                { 
                $fieldnoderefs = explode(",",$fields[$n]['nodes']);
                $fieldnodes = get_nodes_by_refs($fieldnoderefs);
                $ordered_nodes = array_column(reorder_nodes($fieldnodes),"name");
                $fields[$n]['value'] = implode(", ",array_map("i18n_get_translated",$ordered_nodes));
                }

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
        $getresources = $resources;
        }
    else
        {
        $resources = array_filter($resources,function($v){return (string)(int)$v == $v;});
        $resourceids = $resources;
        $allresourcedata = ps_query("SELECT ref, resource_type FROM resource WHERE ref IN (" . ps_param_insert(count($resourceids)) . ")", ps_param_fill($resourceids,"i"));
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

    // Remove Category tree fields as these need special handling
    $node_fields    = array_diff($NODE_FIELDS,array(FIELD_TYPE_CATEGORY_TREE));

    $field_data_sql = "
             SELECT rn.resource,
                    group_concat(n.name) AS `value`,
                    f.ref,
                    f.ref resource_type_field,
                    f.ref AS fref,
                    f.name,
                    f.title,
                    f.type,
                    f.order_by,
                    f.keywords_index,
                    f.partial_index,
                    f.resource_type,
                    f.display_field,
                    f.use_for_similar,
                    f.display_template,
                    f.tab,
                    f.smart_theme_name,
                    f.advanced_search,
                    f.simple_search,
                    f.help_text,
                    f.display_as_dropdown,
                    f.external_user_access,
                    f.autocomplete_macro,
                    f.hide_when_uploading,
                    f.value_filter,
                    f.exiftool_filter,
                    f.hide_when_restricted,
                    f.omit_when_copying,
                    f.tooltip_text,
                    f.regexp_filter,
                    f.sync_field,
                    f.display_condition,
                    f.onchange_macro,
                    f.field_constraint,
                    f.linked_data_field,
                    f.fits_field,
                    f.browse_bar,
                    f.read_only,
                    f.active,
                    f.required AS frequired,
                    f.automatic_nodes_ordering,
                    f.personal_data,
                    f.include_in_csv_export,
                    f.full_width
               FROM resource_node rn
          LEFT JOIN node n ON n.ref=rn.node
          LEFT JOIN resource_type_field f ON f.ref=n.resource_type_field
              WHERE rn.resource IN (" . ps_param_insert(count($resourceids)) . ")
                    AND (f.active=1 AND f.type IN (" . ps_param_insert(count($node_fields)) . "))
              GROUP BY resource, f.ref";

    $field_data_params = array_merge(ps_param_fill($resourceids,"i"), ps_param_fill($node_fields,"i"));

    $fields = ps_query($field_data_sql,$field_data_params);

    // Add category tree values, reflecting tree structure
    $tree_fields = get_resource_type_fields("","ref","asc",'',array(FIELD_TYPE_CATEGORY_TREE));

    // Construct an array of the selected tree nodes across all resource ids
    $selected_treenodes = get_resource_nodes_batch($resourceids, array_column($tree_fields,"ref"), true);

    foreach($tree_fields as $tree_field)
        {
        // We need to determine the tree strings for all nodes belonging to the tree field
        $tree_field_nodes = get_nodes($tree_field["ref"],'', true); # where '' is parent and true is recursive
        // Each tree field option is the canonical path to a node
        $tree_field_options = get_tree_strings($tree_field_nodes, true); # where true is full path

        $addfield = $tree_field;
        // Now for each resource, build an array consisting of all of the paths for the selected nodes
        foreach($getresources as $getresource)
            {
            $treetext_arr = array();
            $valstring = "";
            // Are there any selected tree nodes on the resource?
            if(isset($selected_treenodes[$getresource["ref"]][$tree_field["ref"]]) && is_array($selected_treenodes[$getresource["ref"]][$tree_field["ref"]]))
                {
                // So for each selected tree node, add its corresponding path to the path array
                foreach($selected_treenodes[$getresource["ref"]][$tree_field["ref"]] as $selected_resource_treenode)
                    {
                    $treetext_arr[]=$tree_field_options[$selected_resource_treenode["ref"]];
                    }
                sort($treetext_arr);
                // Quoting each element is required for csv export
                $valstring = $csvexport ? ("\"" . implode("\",\"",$treetext_arr) . "\"") : implode(",",$treetext_arr);
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
                $inherit_global_fields[$fields[$n]["ref"]] = (bool) ps_value("SELECT inherit_global_fields AS `value` FROM resource_type WHERE ref = ?", array("i",$rtype), true, "schema");
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
    $parameters=array();
    if ($types==""){$sql="";} else
        {
        # Ensure $types are suitably quoted and escaped
        $cleantypes="";
        $s=explode(",",$types);

        foreach ($s as $type)
            {
            if (is_numeric(str_replace("'","",$type))) # Process numeric types only, to avoid inclusion of collection-based filters (mycol, public, etc.)
                {
                if ($cleantypes!="") {$cleantypes.=",";}
                $cleantypes.="?";
                $parameters[]="i";$parameters[]=$type;
                }
            }
        $sql=" WHERE ref IN ($cleantypes) ";
        }

    $r=ps_query("SELECT ref,
                        name,
                        allowed_extensions,
                        order_by,
                        config_options,
                        tab_name,
                        push_metadata,
                        inherit_global_fields,
                        colour,
                        icon
                   FROM resource_type
                        $sql
               ORDER BY order_by,
                        ref",
                        $parameters,
                        "schema");

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
    # Currently the date fields are not used for this feature

    $return=array();

    $keywords = ps_query("SELECT DISTINCT n.ref, n.name, n.resource_type_field FROM node n INNER JOIN resource_node rn ON n.ref=rn.node WHERE (rn.resource = ? AND n.resource_type_field IN (SELECT rtf.ref FROM resource_type_field rtf WHERE use_for_similar=1) ) ORDER BY new_hit_count DESC LIMIT $count",["i",$resource]);

    foreach ($keywords as $keyword )
        {
        # Apply permissions and strip out any results the user does not have access to.
        if (metadata_field_view_access($keyword["resource_type_field"]) && !checkperm("T" . $resource))
            {
            $r =  $keyword["name"] ;
            }

        if(isset($r) && trim($r) != '')
            {
            if (substr($r,0,1)==","){$r=substr($r,1);}
            $s=split_keywords(i18n_get_translated($r));
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
	ps_query("DELETE FROM resource_dimensions WHERE resource = ?", ["i",$resource]);
	ps_query("DELETE FROM resource_related WHERE resource = ? OR related = ?", ["i",$resource,"i",$resource]);
    delete_all_resource_nodes($resource);

    // Clear all 'joined' fields
    $joins=get_resource_table_joins();
    if(count($joins) > 0)
        {
        $joins_sql = "";
        foreach ($joins as $join)
            {
            $joins_sql .= (($joins_sql!="")?",":"") . "field" . (int)$join . "=NULL";
            }
        ps_query("UPDATE resource SET $joins_sql WHERE ref = ?", ["i",$resource]);
        }
    return true;
    }

function get_max_resource_ref()
    {
    # Returns the highest resource reference in use.
    return ps_value("SELECT MAX(ref) value FROM resource",[],0);
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
	return ps_array("select ref value from resource where ref>=? and ref<=? and archive=0 order by ref",array("i",$lower,"i",$higher),0);
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
    if (ps_value("SELECT COUNT(*) value FROM resource WHERE ref = ?",["i",$from],0)==0)
        {
        return false;
        }

    # copy joined fields to the resource column
    $joins=get_resource_table_joins();

    // Filter the joined columns so we only have the ones relevant to this resource type
    $query = 'SELECT rtf.ref AS value
                    FROM resource_type_field AS rtf
            INNER JOIN resource AS r ON (rtf.resource_type != r.resource_type AND rtf.resource_type != 0)
                    WHERE r.ref = ?;';

    $irrelevant_rtype_fields = ps_array($query,["i",$from]);
    $irrelevant_rtype_fields = array_values(array_intersect($joins, $irrelevant_rtype_fields));
    $filtered_joins = array_values(array_diff($joins, $irrelevant_rtype_fields));

    $joins_sql="";
    foreach ($filtered_joins as $join){
        $joins_sql.=",field$join ";
    }

    $archive=ps_value("SELECT archive value FROM resource WHERE ref = ?",["i",$from],0);

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

    $sql = ''; $params = [];
    if($resource_type == -1){$sql = 'resource_type';}
    else{$sql = '?'; $params = ['i', $resource_type];}

    # First copy the resources row
    ps_query("insert into resource(resource_type,creation_date,rating,archive,access,created_by $joins_sql) select {$sql},now(),rating, ?,access,created_by $joins_sql from resource where ref= ?", array_merge($params, ['i', $archive, 'i', $from]));
    $to=sql_insert_id();

    # Set that this resource was created by this user.
    # This needs to be done if either:
    # 1) The user does not have direct 'resource create' permissions and is therefore contributing using My Contributions directly into the active state
    # 2) The user is contributiting via My Contributions to the standard User Contributed pre-active states.
    if ((!checkperm("c")) || $archive<0 || (isset($always_record_resource_creator) && $always_record_resource_creator))
        {
        # Update the user record
        ps_query("update resource set created_by=? where ref=?",array("i",$userref,"i",$to));
        }

    # Copy Metadata
    copyAllDataToResource($from,$to);

    # Copy relationships
    copyRelatedResources($from, $to);

	# Copy access
	ps_query("insert into resource_custom_access(resource,usergroup,access) select ?,usergroup,access from resource_custom_access where resource=?",array("i",$to,"i",$from));

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
            if('' != trim($field_data['value']??"") && !($upload_then_edit && $from < 0))
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
 * @param   int        $resource - resource ref                            -- resource_log.resource
 * @param   string     $type - log code defined in include/definitions.php -- resource_log.type
 * @param   int        $field - resource type field                        -- resource_log.resource_type_field
 * @param   string     $notes - text notes                                 -- resource_log.notes
 * @param   mixed      $fromvalue - original value (int or string)         -- resource_log.previous_value
 * @param   mixed      $tovalue - new value (int or string)
 * @param   int        $usage                                              -- resource_log.usageoption
 * @param   string     $purchase_size                                      -- resource_log.purchase_size
 * @param   float      $purchase_price                                     -- resource_log.purchase_price
 *
 * @return int (or false)
 */

function resource_log($resource, $type, $field, $notes="", $fromvalue="", $tovalue="", $usage=-1, $purchase_size="", $purchase_price=0.00)
    {
    global $userref,$k,$lang,$resource_log_previous_ref, $internal_share_access;

    // Param type checks
    $param_str = array($type,$notes,$purchase_size);
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
        ps_query("UPDATE `resource_log` SET `diff`=left(concat(`diff`,'\n',?),60000) WHERE `ref`=?",array("s",$diff,"i",$resource_log_previous_ref));
        return $resource_log_previous_ref;
        }
    else
        {
        ps_query("INSERT INTO `resource_log` (`date`, `user`, `resource`, `type`, `resource_type_field`, `notes`, `diff`, `usageoption`, `purchase_size`,`purchase_price`, `access_key`, `previous_value`) VALUES (now(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)",
            [
            'i', (($userref != "") ? $userref : null),
            'i', $resource, 
            's', $type,
            'i', (($field=="" || !is_numeric($field)) ? null : $field),
            's', $notes,
            's', $diff, 
            'i', $usage,
            's', $purchase_size,
            'i', $purchase_price,
            'i', ((isset($k) && !$internal_share_access) ? mb_strcut($k, 0, 50): null),
            's', $fromvalue
            ]
        );
        $log_ref = sql_insert_id();
        $resource_log_previous_ref = $log_ref;
        return $log_ref;
        }
	}

/**
 * Get resource log records. The standard field titles are translated using $lang. Custom field titles are i18n translated.
 *
 * @param  int    $resource    Resource ID - set to NULL and specify r.ref=>[id] in the $filters array to retrieve a specific log entry by log ref
 * @param  int    $fetchrows   If $fetchrows is set we don't have to loop through all the returned rows. @see ps_query()
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

    // Create filter SQL
    $filterarr = array(); $params = [];
    if(is_int_loose($resource))
        {
        $filterarr[] = "r.resource= ?";
        $params = array_merge($params, ['i', $resource]);
        }
    foreach($filters as $column => $filter_value)
        {
        $filterarr[] = trim($column) . "= ?";
        $params = array_merge($params, ['s', $filter_value]);
        }
    $sql_filter = "WHERE " . implode(" AND ", $filterarr);

    $log = ps_query(
                "SELECT r.ref,
                        r.resource,
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
                        {$sql_filter}
               GROUP BY r.ref
               ORDER BY r.ref DESC", $params,
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
	return lang_or_i18n_get_translated(ps_value("select name value from resource_type where ref=?",array("i",$type), "schema"),"resourcetype-");
	}

function get_resource_custom_access($resource)
    {
    /*Return a list of usergroups with the custom access level for resource $resource (if set).
    The standard usergroup names are translated using $lang. Custom usergroup names are i18n translated.*/
    $sql = ''; $params = [];
    if(checkperm('E'))
        {
        // Restrict to this group and children groups only.
        global $usergroup, $usergroupparent;
        $sql = "WHERE g.parent = '{$usergroup}' OR g.ref = '{$usergroup}' OR g.ref = '{$usergroupparent}'";
        $params = ['i', $usergroup, 'i', $usergroup, 'i', $usergroupparent];
        }

    $resource_custom_access = ps_query("
                   SELECT g.ref,
                          g.name,
                          g.permissions,
                          c.access
                     FROM usergroup AS g
          LEFT OUTER JOIN resource_custom_access AS c ON g.ref = c.usergroup AND c.resource = ?
                     $sql
                 GROUP BY g.ref
                 ORDER BY (g.permissions LIKE '%v%') DESC, g.name
     ", array_merge(['i', $resource], $params));

    for($n = 0; $n < count($resource_custom_access); $n++)
        {
        $resource_custom_access[$n]['name'] = lang_or_i18n_get_translated($resource_custom_access[$n]['name'], 'usergroup-');
        }

    return $resource_custom_access;
    }

function get_resource_custom_access_users_usergroups($resource)
    {
    # Returns only matching custom_access rows, with users and groups expanded
    return ps_query("
                 SELECT g.name usergroup,
                        u.username user,
                        c.access,
                        c.user_expires AS expires
                   FROM resource_custom_access AS c
        LEFT OUTER JOIN usergroup AS g ON g.ref = c.usergroup
        LEFT OUTER JOIN user AS u ON u.ref = c.user
                  WHERE c.resource = ?
               ORDER BY g.name, u.username
    ", ['i', $resource]);
    }


function save_resource_custom_access($resource)
	{
	$groups=get_resource_custom_access($resource);
	ps_query("delete from resource_custom_access where resource=? and usergroup is not null",array("i",$resource));
	for ($n=0;$n<count($groups);$n++)
		{
		$usergroup=$groups[$n]["ref"];
		$access=getval("custom_" . $usergroup,0);
		ps_query("insert into resource_custom_access(resource,usergroup,access) values (?,?,?)", array("i",$resource,"i",$usergroup,"i",$access));
		}
	}

function get_custom_access($resource,$usergroup,$return_default=true)
	{
	global $custom_access,$default_customaccess;
	if ($custom_access==false) {return 0;} # Custom access disabled? Always return 'open' access for resources marked as custom.

	$result=ps_value("select access value from resource_custom_access where resource=? and usergroup=?",array("i",$resource,"i",$usergroup),'');
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

    $sql = "SELECT c.ref, c.`name`, c.`type`, u.fullname FROM collection_resource AS cr
            JOIN collection AS c ON cr.collection = c.ref AND cr.resource = ? AND c.`type` IN (?, ?)
            LEFT OUTER JOIN user AS u ON c.user = u.ref
            ". trim(featured_collections_permissions_filter_sql("WHERE", "c.ref",true)) ." # access control filter (ok if empty - it means we don't want permission checks or there's nothing to filter out)";
        

    $results = ps_query($sql, ['i', $ref, 'i', COLLECTION_TYPE_FEATURED, 'i', COLLECTION_TYPE_PUBLIC]);
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


    ps_query("UPDATE resource SET resource_type = ? WHERE ref = ?",["i",$type,"i",$ref]);

    # Clear data that is no longer needed (data/keywords set for other types).
    ps_query("DELETE FROM resource_node WHERE resource = ? and node>0 AND node NOT IN (SELECT n.ref FROM node n LEFT JOIN resource_type_field rf ON n.resource_type_field=rf.ref WHERE rf.resource_type = ? OR rf.resource_type=999 OR resource_type=0)",["i",$ref,"i",$type]);

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
    return ps_query("
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
              AND (resource_type = ? OR resource_type = '0')
         GROUP BY f.ref
         ORDER BY exiftool_field", array("i",$resource_type),"schema");
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

    $copy_hook = hook('createtempfile_copy', '', array($path, $tmpfile));
    if($copy_hook == false)
        {
        copy($path, $tmpfile);
        }
    else
        {
        $tmpfile = $copy_hook;
        }

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
        if(!in_array(strtoupper((string) $extension), $writable_formats_array))
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
            if(trim($metadata_item["exiftool_field"]??"") != "" && !in_array($metadata_item['ref'], $read_only_fields))
                {
                $write_to[] = $metadata_item;
                }
            }

        $writtenfields=array(); // Need to check if we are writing to an embedded field from more than one RS field, in which case subsequent values need to be appended, not replaced

        for($i = 0; $i<count($write_to); $i++) # Loop through all the found fields.
	    {
            $fieldtype = $write_to[$i]['type'];
            $writevalue = $write_to[$i]['value']??"";
            # Formatting and cleaning of the value to be written - depending on the RS field type.
            switch ($fieldtype)
                {
                case 2:
                case 3:
                case 9:
                case 12:
                    # Check box list, drop down, radio buttons or dynamic keyword list: remove initial comma if present
                    $writevalue = strip_leading_comma($writevalue);
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
            $filtervalue=hook("additionalmetadatafilter", "", [$write_to[$i]["exiftool_field"], $writevalue]);
            if ($filtervalue) $writevalue=$filtervalue;
            # Add the tag name(s) and the value to the command string.
            $group_tags = explode(",", $write_to[$i]['exiftool_field']); # Each 'exiftool field' may contain more than one tag.
            foreach ($group_tags as $group_tag)
                {
                $group_tag = strtolower($group_tag); # E.g. IPTC:Keywords -> iptc:keywords
                if (strpos($group_tag,":")===false) {$tag = $group_tag;} # E.g. subject -> subject
                else {$tag = substr($group_tag, strpos($group_tag,":")+1);} # E.g. iptc:keywords -> keywords
                if(strpos($group_tag,"-") !== false && stripos($group_tag,"xmp") !== false)
                    {
                    // Remove the XMP namespace for XMP data if included
                    $group_tag = substr($group_tag,0,(strpos($group_tag,"-")));
                    }
                $exifappend=false; // Need to replace values by default
                if(isset($writtenfields[$group_tag]))
                    {
                    // This embedded field is already being updated, we need to append values from this field
                    $exifappend=true;
                    debug("write_metadata - more than one field mapped to the tag '" . $group_tag . "'. Enabling append mode for this tag. ");
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
                        if(substr($group_tag,0,3) != "xmp")
                            {
                            # Only IPTC Keywords are a list type - these are written one at a time and not all together.
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
                                        $writtenfields["keywords"].="," . $keyword;
                                        # Convert the data to UTF-8 if not already.
                                        if (!$exiftool_write_omit_utf8_conversion && (!isset($mysql_charset) || (isset($mysql_charset) && strtolower($mysql_charset)!="utf8"))){$keyword = mb_convert_encoding($keyword, mb_detect_encoding($keyword), 'UTF-8');}
                                        $command.= escapeshellarg("-" . $group_tag . "-=" . htmlentities($keyword, ENT_QUOTES, "UTF-8")) . " "; // In case value is already embedded, need to manually remove it to prevent duplication
                                        $command.= escapeshellarg("-" . $group_tag . "+=" . htmlentities($keyword, ENT_QUOTES, "UTF-8")) . " ";
                                        }
                                    }
                                }
                            break; // The break is in here so that Non-IPTC keywords continue to be handled by default
                            }
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
                        if (!$exiftool_write_omit_utf8_conversion 
                            && (!isset($mysql_charset) 
                                || (isset($mysql_charset) && strtolower($mysql_charset)!="utf8")))
                            {
                            $writevalue = mb_convert_encoding($writevalue, mb_detect_encoding($writevalue), 'UTF-8');
                            }
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
        $userref, $lang, $upload_then_process_holding_state,$unoconv_extensions;

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
        if ($ingest){$file_path="";} else {$file_path=$path;}

        # Store extension/data in the database
        ps_query("update resource set archive=0,file_path=?,file_extension=?,preview_extension=?,file_modified=now() where ref=?",array("s",$file_path,"s",$extension,"s",$extension,"i",$r));

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
                $job_data["archive"]=ps_value("SELECT archive value from resource where ref=?", array("i",$r), "");
                update_archive_status($r, $upload_then_process_holding_state);
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
	if ($order_by!="" && $sort!=""
        && in_array(strtoupper($order_by),array("ALT_TYPE"))
        && in_array(strtoupper($sort),array("ASC","DESC")) )
        {
		$ordersort=$order_by." ".$sort.",";
	    }
    else
        {
		$ordersort="";
	    }

    # The following hook now returns a query object
    $extrasql=hook("get_alternative_files_extra_sql","",array($resource));

    if (!$extrasql)
        {
        # Hook inactive, ensure we have an empty query object
        $extrasql = new PreparedStatementQuery();
        }

    # Filter by type, if provided.
    if ($type!="")
        {
        $extrasql->sql.=" AND alt_type=?";
        $extrasql->parameters=array_merge($extrasql->parameters,array("s",$type));
        }

    $alt_files_sql="SELECT ref,name,description,file_name,file_extension,file_size,creation_date,alt_type
                    FROM resource_alt_files where resource=? ". $extrasql->sql .
                   " order by ".$ordersort." name asc, file_size desc";

    $alt_files_parameters=array_merge(array("i",$resource), $extrasql->parameters);

	return ps_query($alt_files_sql,$alt_files_parameters);
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

	ps_query("INSERT INTO resource_alt_files(resource,name,creation_date,description,file_name,file_extension,file_size,alt_type) VALUES (?, ?,now(), ?, ?, ?, ?, ?)",
        [
        'i', $resource,
        's', $name,
        's', $description,
        's', $file_name,
        's', $file_extension,
        'i', $file_size,
        'i', $alt_type
        ]
    );
	return sql_insert_id();
	}

function delete_alternative_file($resource,$ref)
	{
	# Delete any uploaded file.
	$info=get_alternative_file($resource,$ref);
	$path=get_resource_path($resource, true, "", true, $info["file_extension"], -1, 1, false, "", $ref);
    hook('delete_alternative_file_extra', '', array($path));
	if (file_exists($path)) {unlink($path);}

        // run through all possible extensions/sizes
	$extensions = array();
	$extensions[]=$info['file_extension']?$info['file_extension']:"jpg";
	$extensions[]=isset($info['preview_extension'])?$info['preview_extension']:"jpg";
	$extensions[]=$GLOBALS['ffmpeg_preview_extension'];
        $extensions[]='jpg'; // always look for jpegs, just in case
	$extensions[]='icc'; // always look for extracted icc profiles
	$extensions=array_unique($extensions);
        $sizes = ps_array('select id value from preview_size',array());

        // in some cases, a jpeg original is generated for non-jpeg files like PDFs. Delete if it exists.
        $path=get_resource_path($resource, true,'', true, 'jpg', -1, 1, false, "", $ref);
        if (file_exists($path)) {
            unlink($path);
        }

        hook('delete alternative_jpg_extra', '', array($path));

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
                    hook('delete_alternative_file_loop', '', array($path));
                    if (file_exists($path)) {
                        unlink($path);
                        $page++;
                    }
                }
            }
        }
        hook('delete_alternative_mp3_extra', '', array($path));

	# Delete the database row
	ps_query("delete from resource_alt_files where resource=? and ref=?", array("i",$resource,"i",$ref));

	# Log the deletion
	resource_log($resource,LOG_CODE_DELETED_ALTERNATIVE,'');

	# Update disk usage
	update_disk_usage($resource);

    return true;
	}

function get_alternative_file($resource,$ref)
	{
	# Returns the row for the requested alternative file
	$return=ps_query("select ref,name,description,file_name,file_extension,file_size,creation_date,alt_type from resource_alt_files where resource=? and ref=?",array("i",$resource,"i",$ref));
	if (count($return)==0) {return false;} else {return $return[0];}
	}

function save_alternative_file($resource,$ref)
	{
	# Saves the 'alternative file' edit form back to the database
	ps_query("update resource_alt_files set name=?,description=?,alt_type=? where resource=? and ref=?",
    array("s",$name,"s",$description,"s",$alt_type,"i",$resource,"i",$ref));
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
	if ($user_rating_only_once)
        {
		$ratings=ps_query("select user,rating from user_rating where ref=?",array("i",$ref));

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
				ps_query("delete from user_rating where user=? and ref=?",array("i",$userref,"i",$ref));
				$count--;
			} else {
				ps_query("update user_rating set rating=? where user=? and ref=?",array("i",$rating,"i",$userref,"i",$ref));
			}
		}

		# if user does not have a current rating, add it
		else
            {
			if ($rating != 0)
                { // rating remove feature
				$total=$total+$rating;
				$count++;
				ps_query("insert into user_rating (user,ref,rating) values (?,?,?)",array("i",$userref,"i",$ref,"i",$rating));
			    }   
		    }
    	}
	else
        {
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
	ps_query("update resource set user_rating=?,user_rating_total=?,user_rating_count=? where ref='$ref'",array("d",$average,"i",$total,"i",$count));
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
    $r = ps_query("
        SELECT " . columns_in("resource_type_field") . "
          FROM resource_type_field
         WHERE ref = ?
     ", ['i', $field], "schema");

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
        $prevent_open_access_on_edit_for_active, $open_access_for_contributor,
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
		$extaccess = ps_value("SELECT access `value` FROM external_access_keys WHERE resource = ? AND access_key = ? AND (expires IS NULL OR expires > NOW())", array("i",$ref,"s",$k), -1);

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

	if ((trim($usersearchfilter??"")!="") && $search_filter_strict)
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
        if( strlen(trim($userderestrictfilter)) > 0
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
                ps_query("UPDATE usergroup SET derestrict_filter_id=? WHERE ref=?",array("i",$migrateresult,"i",$usergroup));
                debug("FILTER MIGRATION: Migrated derestrict_filter_id filter - '" . $userderestrictfilter . "' filter id#" . $migrateresult);
                $userderestrictfilter = $migrateresult;
                }
            elseif(is_array($migrateresult))
                {
                debug("FILTER MIGRATION: Error migrating filter: '" . $userderestrictfilter . "' - " . implode('\n' ,$migrateresult));
                // Error - set flag so as not to reattempt migration and notify admins of failure
                ps_query("UPDATE usergroup SET derestrict_filter_id='-1' WHERE ref=?",array("i",$usergroup));
                message_add(array_column($notification_users,"ref"), $lang["filter_migration"] . " - " . $lang["filter_migrate_error"] . ": <br/>" . implode('\n' ,$migrateresult),generateURL($baseurl . "/pages/admin/admin_group_management_edit.php",array("ref"=>$usergroup)));
                }
            }

        if(is_int_loose($userderestrictfilter) && $userderestrictfilter > 0)
            {
            $matchedfilter = filter_check($userderestrictfilter, get_resource_nodes($ref));
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
	return ps_value("select access value from resource_custom_access where resource=? and user=? and (user_expires is null or user_expires>now())",array("i",$resource,"i",$user),false);
	}

function edit_resource_external_access($key,$access=-1,$expires="",$group="",$sharepwd="")
	{
	global $userref,$usergroup, $scramble_key;
	if ($group=="" || !checkperm("x")) {$group=$usergroup;} # Default to sharing with the permission of the current usergroup if not specified OR no access to alternative group selection.
	if ($key==""){return false;}
    if ($sharepwd != "(unchanged)")
        {
        $sql = "password_hash= ?,";
        $params = ['s', (($sharepwd == "") ? "" : hash('sha256', $key . $sharepwd . $scramble_key))];
        }
        else{$sql = "";}
	# Update the expiration and acccess
	ps_query("update external_access_keys set {$sql} access= ?, expires= ?,date=now(),usergroup= ? where access_key='$key'",
        array_merge($params, [
        'i', $access,
        's', (($expires=="")?null: $expires),
        'i', $group,
        ])
    );
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
	global $userref, $usergroup, $user_dl_limit, $user_dl_days, $noattach, $sizes_always_allowed;

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
		$complete=ps_value("select cr.purchase_complete value from collection_resource cr join collection c on cr.collection=c.ref where c.user=? and cr.resource=? and cr.purchase_size=?",array("i",$userref,"i",$resource,"s",$size), 0);
		if ($complete==1) {return true;}
		}

    # Restricted
    if(1 == $access)
        {
        // The system should always allow these sizes to be downloaded as these are needed for search results and it makes
        // sense to allow them if a request for one of them is received. For example when $hide_real_filepath is enabled.
        // 'videojs' represents the preview loaded by videojs viewer.

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
            return (ps_value("select allow_restricted value from preview_size where id=?",array("i",$size),0)==1);
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
    $userpermissions, $lang, $baseurl, $userdata, $edit_only_own_contributions;
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

    if(strlen(trim((string) $usereditfilter)) > 0
        && !is_numeric($usereditfilter)
        && trim($userdata[0]["edit_filter"]) != ""
        && $userdata[0]["edit_filter_id"] != -1
        )
        {
        // Migrate unless marked not to due to failure (flag will be reset if group is edited)
        $migrateeditfilter = edit_filter_to_restype_permission($usereditfilter, $usergroup, $userpermissions, true);
        if(trim($usereditfilter) !== "")
            {
            $migrateresult = migrate_filter($migrateeditfilter);
            }
        else
            {
            $migrateresult = 0; // filter was only for resource type, not failed but no need to migrate again
            }

        $notification_users = get_notification_users();
        if(is_numeric($migrateresult))
            {
            // Successfully migrated - now use the new filter
            ps_query("UPDATE usergroup SET edit_filter_id=? WHERE ref=?",array("i",$migrateresult,"i",$usergroup));
            debug("FILTER MIGRATION: Migrated edit filter - '" . $usereditfilter . "' filter id#" . $migrateresult);
            $usereditfilter = $migrateresult;
            }
        elseif(is_array($migrateresult))
            {
            debug("FILTER MIGRATION: Error migrating filter: '" . $usereditfilter . "' - " . implode('\n' ,$migrateresult));
            // Error - set flag so as not to reattempt migration and notify admins of failure
            ps_query("UPDATE usergroup SET edit_filter_id='0' WHERE ref=?",array("i",$usergroup));
            message_add(array_column($notification_users,"ref"), $lang["filter_migration"] . " - " . $lang["filter_migrate_error"] . ": <br/>" . implode('\n' ,$migrateresult),generateURL($baseurl . "/pages/admin/admin_group_management_edit.php",array("ref"=>$usergroup)));
            }
        }

    if (trim((string) $usereditfilter)=="" || ($status<0 && $resourcedata['created_by'] == $userref)) # No filter set, or resource was contributed by user and is still in a User Contributed state in which case the edit filter should not be applied.
        {
        $gotmatch = true;
        }
    elseif(is_int_loose($usereditfilter) && $usereditfilter > 0)
        {
        $gotmatch = filter_check($usereditfilter, get_resource_nodes($resource));
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
	return ps_query("select ref,field$metadata_template_title_field from resource where ref>0 and resource_type= ? order by field$metadata_template_title_field", ['i', $metadata_template_resource_type]);
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

	$return=ps_query ("select * from
	(select c.*,u.username,u.fullname,count(r.resource) count from user u join collection c on u.ref=c.user and c.user=? left outer join collection_resource r on c.ref=r.collection group by c.ref
	union
	select c.*,u.username,u.fullname,count(r.resource) count from user_collection uc join collection c on uc.collection=c.ref and uc.user=? and c.user<>? left outer join collection_resource r on c.ref=r.collection left join user u on c.user=u.ref group by c.ref) clist where clist.ref in (select collection from collection_resource cr where cr.resource=?)",array(
        "i",$userref,
        "i",$userref,
        "i",$userref,
        "i",$ref    
        ));

	return $return;
	}

function download_summary($resource)
	{
	# Returns a summary of downloads by usage type
	return ps_query("select usageoption,count(*) c from resource_log where resource=? and type='D' group by usageoption order by usageoption",array("i",$resource));
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
* @uses ps_value()
* @uses ps_query()
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

    $resource_type = ps_value("SELECT resource_type AS `value` FROM resource WHERE ref = ?", ["i",$resource], 0);

    $fields = ps_query(
        "SELECT rtf.ref, rtf.type, rtf.autocomplete_macro
          FROM resource_type_field rtf
          LEFT JOIN resource_type rt ON rt.ref = ?
          WHERE length(rtf.autocomplete_macro) > 0
          AND (   (rtf.resource_type<>0 AND rtf.resource_type = rt.ref)
               OR (rtf.resource_type=0  AND rt.inherit_global_fields=1)
              )",array("i",$resource_type),"schema");

    $fields_updated = array();

    foreach($fields as $field)
        {
        $run_autocomplete_macro = $force_run || hook('run_autocomplete_macro');
        # The autocomplete macro will run if the existing value is blank, or if forced to always run
        if(count(get_resource_nodes($resource, $field['ref'], true)) == 0 || $run_autocomplete_macro)
            {
            # Autocomplete and update using the returned value
            $value = eval(eval_check_signed($field['autocomplete_macro']));
            if(in_array($field['type'], $FIXED_LIST_FIELD_TYPES))
                {
                # Multiple values are comma separated
                $autovals = str_getcsv($value);
                $autonodes = array();
                # Establish an array of nodes from the values
                foreach($autovals as $autoval)
                    {
                    $nodeid = get_node_id($autoval,$field['ref']);
                    if($nodeid !== false)
                        {
                        $autonodes[] = $nodeid;
                        }
                    }
                # Add nodes if any were established
                if (count($autonodes) > 0)
                    {
                    natsort($autonodes);
                    add_resource_nodes($resource,$autonodes,false,false);
                    log_node_changes($resource,$autonodes,array(),$lang["autocomplete_log_note"]);
                    $fields_updated[$field['ref']] = implode(",",$autonodes);
                    }
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

function get_page_count($resource,$alternative=-1)
    {
    # gets page count for multipage previews from resource_dimensions table.
    # also handle alternative file multipage previews by switching $resource array if necessary
    # $alternative specifies an actual alternative file
    $ref=$resource['ref'];

    if ($alternative!=-1)
        {
        $pagecount=ps_value("select page_count value from resource_alt_files where ref=?",array("i",$alternative),"");
        $resource=get_alternative_file($ref,$alternative);
        }
    else
        {
        $pagecount=ps_value("select page_count value from resource_dimensions where resource=?", array("i",$ref), "");
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
		$alt_ref=ps_value("select ref value from resource_alt_files where resource=? and unoconv=1",array("i",$ref), "");
		$file=get_resource_path($ref,true,"",false,"pdf",-1,1,false,"",$alt_ref);
		}
	else
		{
		$file=get_resource_path($ref,true,"",false,"pdf",-1,1,false,"",$alternative);
		}

    if (file_exists($file))
        {
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
        if (!is_numeric($pages))
            {
            $pages = 1; // default to 1 page if we didn't get anything back
            }
        }
    else
        {
        $pages = 1;
        }

	if ($alternative!=-1)
		{
		ps_query("update resource_alt_files set page_count=? where ref=?",array("i",$pages,"i",$alternative));
		}
	else
		{
		ps_query("update resource_dimensions set page_count=? where resource=?",array("i",$pages,"i",$ref));
		}
	return $pages;
	}


function update_disk_usage($resource)
	{
	# we're also going to record the size of the primary resource here before we do the entire folder
	$ext = ps_value("SELECT file_extension value FROM resource where ref = ? AND file_path IS NULL",array("i",$resource), 'jpg');
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
			$total+=$s;
			}
		}
	ps_query("update resource set disk_usage=?,disk_usage_last_updated=now(),file_size=? where ref=?",array("i",$total,"i",$rsize,"i",$resource));
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
        echo " - Skipping update_disk_usage_cron  - last run: " . $lastrun . "<br/>\n";
        return false;
        }

    $resources=ps_array(
        "SELECT ref value
            FROM resource
        WHERE ref>0
            AND disk_usage_last_updated IS null
                OR datediff(now(),disk_usage_last_updated)>30
        ORDER BY disk_usage_last_updated ASC
        LIMIT 20000",
        []);
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
    return ps_value("select ifnull(sum(disk_usage),0) value from resource where ref>?",array("i",$fstemplate_alt_threshold), 0);
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

/**
 * Get size of specified image file
 *
 * @param  int $ref             Resource ID
 * @param  string $path         File path
 * @param  string $extension    File extension
 * @param  bool $forcefromfile  Get info from file instead of database cache
 *
 * @return array|bool           Fil size info. Returns false if not available
 */
function get_original_imagesize($ref="",$path="", $extension="jpg", $forcefromfile=false)
	{
	$fileinfo=array();
	if($ref=="" || $path==""){return false;}
	global $imagemagick_path, $imagemagick_calculate_sizes;

    if(!file_exists($path))
        {
        return false;
        }

    $file=$path;

    // check for valid image
    if (function_exists('mime_content_type'))
        {
        $mime_content_type = mime_content_type($file);
        }
    else
        {
        $mime_content_type = get_mime_type($file);
        }
    $is_image = strpos($mime_content_type, "image/");
    if ($is_image === false)
        {
        return false;
        }

    $o_size=ps_query("SELECT " . columns_in("resource_dimensions") . " FROM resource_dimensions WHERE resource=?",array("i",$ref));
    if(!empty($o_size))
        {
        if(count($o_size)>1)
            {
            # delete all the records and start fresh. This is a band-aid should there be multiple records as a result of using api_search
            ps_query("DELETE FROM resource_dimensions WHERE resource=?",array("i",$ref));
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

    if($o_size!==false && !$forcefromfile && $o_size['file_size'] > 0)
        {
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
                ps_query("insert into resource_dimensions (resource, width, height, file_size) values(?, ?, ?, ?)",array("i",$ref,"i",$sw,"i",$sh,"i",(int)$filesize));
                }
            else
                {
                ps_query("update resource_dimensions set width=?, height=?, file_size=? where resource=?'",array("i",$sw,"i",$sh,"i",(int)$filesize,"i",$ref));
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
                ps_query("insert into resource_dimensions (resource, width, height, file_size) values(?, ?, ?, ?)",array("i",$ref,"i",$sw,"i",$sh,"i",(int)$filesize));
                }
            else
                {
                ps_query("update resource_dimensions set width=?, height=?, file_size=? where resource=?",array("i",$sw,"i",$sh,"i",(int)$filesize,"i",$ref));
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
                    ps_query("INSERT INTO resource_dimensions (resource, width, height, file_size) VALUES (?, ?, ?, ?)",array("i",$ref,"i",$sw,"i",$sh,"i",(int)$filesize));
                    }
                else
                    {
                    ps_query("UPDATE resource_dimensions SET width=?, height=?, file_size=? WHERE resource=?", array("i",$sw,"i",$sh,"i",$filesize,"i",$ref));
                    }
                }
            else
			    {

			    # Size cannot be calculated.
                $sw="?";$sh="?";
                if(!$o_size)
                    {
                    # Insert a dummy row to prevent recalculation on every view.
                    ps_query("INSERT INTO resource_dimensions (resource, width, height, file_size) VALUES (?,'0', '0', ?)",array("i",$ref,"i",$filesize));
                    }
                else
                    {
                    ps_query("UPDATE resource_dimensions SET width='0', height='0', file_size=? WHERE resource=?",array("i",$filesize,"i",$ref));
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
		ps_query("insert into external_access_keys(resource,access_key,user,access,expires,email,date,usergroup,password_hash) values (?, ?, ?, ?, ?, ?,now(), ?, ?);",
            [
            'i', $resource,
            's', $k,
            'i', $userref,
            'i', $access,
            's', (($expires=="")? null : $expires),
            's', $email,
            'i', $group,
            's', (($sharepwd != "" && $sharepwd != "(unchanged)") ? "'" . hash('sha256', $k . $sharepwd . $scramble_key) : null)
            ]    
        );
		hook("generate_resource_access_key","",array($resource,$k,$userref,$email,$access,$expires,$group));
        return $k;
        }

function get_resource_external_access($resource)
	{
	# Return all external access given to a resource
    # Users, emails and dates could be multiple for a given access key, an in this case they are returned comma-separated.
    global $userref;

    # Build parameters for the query
    $params=array("i",$resource);

    # Restrict to only their shares unless they have the elevated 'v' permission
    $condition="";
    if (!checkperm("v"))
        {
        $condition="AND user=?";
        $params[]="i";$params[]=$userref;
        }

    return ps_query("select access_key,group_concat(DISTINCT user ORDER BY user SEPARATOR ', ') users,group_concat(DISTINCT email ORDER BY email SEPARATOR ', ') emails,max(date) maxdate,max(lastused) lastused,access,expires,collection,usergroup, password_hash from external_access_keys where resource=? $condition group by access_key,access,expires,collection,usergroup order by maxdate",$params);
	}


function delete_resource_access_key($resource,$access_key)
    {
    global $lang;
    ps_query("delete from external_access_keys where access_key=? and resource=?",array("s",$access_key,"i",$resource));
    resource_log($resource,LOG_CODE_DELETED_ACCESS_KEY,'', '',str_replace('%access_key', $access_key, $lang['access_key_deleted']),'');
    }

function resource_type_config_override($resource_type, $only_onchange=true)
    {
    # Pull in the necessary config for a given resource type
    # As this could be called many times, e.g. during search result display
    # By default (only_onchange) only execute the override if the passed resourcetype is different from the previous
    global $resource_type_config_override_last,$resource_type_config_override_snapshot, $ffmpeg_alternatives;

    $config_override_required=false;
    # If the overrides are only to be executed on change of resource type
    if ($only_onchange)
        {
        # If the resource type has changed or if this is the first resource....
        if (!isset($resource_type_config_override_last) || $resource_type_config_override_last!=$resource_type)
            {
            $config_override_required=true;
            $resource_type_config_override_last=$resource_type;
            }
        }
    else
        # The overrides are to be executed for every resource
        {
        $config_override_required=true;
        }

    if ($config_override_required)
        {
        # Look for config and execute.
        $config_options=ps_value("select config_options value from resource_type where ref=?",array("i",$resource_type), "","schema");
        if ($config_options!="")
            {
            # Switch to global context and execute.
            extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
            eval(eval_check_signed($config_options));
            debug_track_vars('end@resource_type_config_override', get_defined_vars());
            }
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

    ps_query("UPDATE resource SET archive = ? WHERE ref IN (" . ps_param_insert(count($resource)) . ")",array_merge(["i",$archive],ps_param_fill($resource,"i")));
    hook('after_update_archive_status', '', array($resource, $archive,$existingstates));
    // Send notifications
    debug("update_archive_status - resources=(" . implode(",",$resource) . "), archive: " . $archive . ", existingstates:(" . implode(",",$existingstates) . "), collection: " . $collection);
    return;
    }


function delete_resources_in_collection($collection)
    {
    global $resource_deletion_state,$userref,$lang;

	// Always find all resources in deleted state and delete them permanently:
	// Note: when resource_deletion_state is null it will find all resources in collection and delete them permanently
    $query = "SELECT ref AS value FROM resource INNER JOIN collection_resource ON collection_resource.resource = resource.ref AND collection_resource.collection = ?";
    $params=array("i",$collection);

    if(isset($resource_deletion_state))
        {
        $query .= " WHERE archive = ?";
        $params[] = "i";$params[] = $resource_deletion_state;
        }

	$resources_in_deleted_state = ps_array($query,$params);

	if(!empty($resources_in_deleted_state))
        {
		foreach ($resources_in_deleted_state as $resource_in_deleted_state)
            {
			delete_resource($resource_in_deleted_state);
            collection_log($collection,'D', '', 'Resource ' . $resource_in_deleted_state . ' deleted permanently.');
		    }
	    }

	// Create a comma separated list of all resources remaining in this collection:
	$resources = ps_query("SELECT cr.resource, r.archive FROM collection_resource cr LEFT JOIN resource r on r.ref=cr.resource WHERE cr.collection = ?",["i",$collection]);
	$r_refs = array_column($resources,"resource");
    $r_states = array_column($resources,"archive");

	// If all resources had their state the same as resource_deletion_state, stop here:
	// Note: when resource_deletion_state is null it will always stop here
	if(empty($resources))
        {
		return TRUE;
	    }

    // Delete (ie. move to resource_deletion_state set in config):
    if(isset($resource_deletion_state))
        {
        update_archive_status($r_refs,$resource_deletion_state,$r_states);
        foreach($r_refs as $ref){resource_log($ref,LOG_CODE_DELETED,'');}
        collection_log($collection,'D', '', str_replace("%ARCHIVE",$resource_deletion_state,$lang['log-deleted_all']));
        ps_query(
            "DELETE FROM collection_resource  WHERE resource IN (" . ps_param_insert(count($r_refs)) . ")",
            ps_param_fill($r_refs,"i")
        );

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


    // This params array can be used for both SELECT and DELETE
	$relatedparams = array_merge(["i",$ref],ps_param_fill($related,"i"),ps_param_fill($related,"i"),["i",$ref]);

    $query = "SELECT resource, related FROM resource_related  WHERE (resource = ? AND related IN (" . ps_param_insert(count($related)) . "))
      OR (resource IN (" . ps_param_insert(count($related)) . ") AND related = ?)";
    $currentlyrelated = ps_query($query,$relatedparams);

    // Create array of all related resources
    $currentlyrelated_arr = array_unique(array_merge(
        array_column($currentlyrelated,"related"),
        array_column($currentlyrelated,"resource")
        ));

    if(count($currentlyrelated_arr) > 0 && !$add)
		{
		// Relationships exist and we want to remove
        $query = "DELETE FROM resource_related  WHERE (resource = ? AND related IN (" . ps_param_insert(count($related)) . "))
        OR (resource IN (" . ps_param_insert(count($related)) . ") AND related = ?)";
        ps_query($query,$relatedparams);
		}
    else if($add)
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
            ps_query("INSERT INTO resource_related (resource,related)
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
    ps_query("delete from resource_custom_access where resource=? and usergroup is not null",array("i",$ref));
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
function get_video_snapshots($resource_id, $file_path = false, $count_only = false, $includemodified = false)
    {
    global $get_resource_path_extra_download_query_string_params, $hide_real_filepath;

    $snapshots_found = array();

    $template_path            = get_resource_path($resource_id, true,  'snapshot', false, 'jpg', -1, 1, false, '');
    $template_webpath         = get_resource_path($resource_id, false, 'snapshot', false, 'jpg', -1, 1, false, '');

    $i = 1;
    do
        {
        $path=str_replace("snapshot","snapshot_" . $i,$template_path);
        if($hide_real_filepath)
            {
            $webpath=$template_webpath . "&snapshot_frame=" . $i;
            }
        else
            {
            $webpath=str_replace("snapshot","snapshot_" . $i,$template_webpath);
            if ($includemodified && file_exists($path))
                {
                $webpath .= "?v=" . urlencode(filemtime($path));
                }
            }

        $snapshot_found  = file_exists($path);

        if($snapshot_found)
            {
            $snapshots_found[$i] = ($file_path ? $path : $webpath);
            }

        $i++;
        }
    while (true === $snapshot_found);

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
    ps_query("delete from resource_custom_access where resource=? and user=?",array("i",$resource,"i",$user));
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
    global $userref;

    if((int)(string)$from !== (int)$from || (int)(string)$to !== (int)$to)
        {
        return false;
        }

    if(!$resourcedata)
        {
        $resourcedata = get_resource_data($to);
        }

    # Permission check isn't required if copying data from the user's upload template as with edit then upload mode.
    if ($from != 0 - $userref)
        {
        if(!get_edit_access($to,$resourcedata["archive"],false,$resourcedata))
            {
            return false;
            }
        }

    copy_resource_nodes($from, $to);

    # Update 'joined' fields in resource table
    $joins=get_resource_table_joins();
    $joinsql = "UPDATE resource AS target LEFT JOIN resource AS source ON source.ref=? SET ";
    $joinfields = "";
    foreach($joins as $joinfield)
        {
        if($joinfields != "")
            {
            $joinfields .= ",";
            }
        $joinfield=(int)$joinfield; // Ensure integer for inclusion in SQL.
        $joinfields .= "target.field{$joinfield} = source.field{$joinfield}";
        }
    $joinsql = $joinsql . $joinfields . " WHERE target.ref=?";
    ps_query($joinsql,array("i",$from,"i",$to));
    return true;
    }

/**
* Update resource data for 'locked' fields from last edited resource. Used for upload_then_edit
* Update resource data for 'locked' fields from last edited resource. Used for upload_then_edit
*
* @uses get_resource_data()
* @uses update_resource_type()
* @uses update_archive_status()
* @uses resource_log()
* @uses checkperm()
* @uses ps_query()
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
                ps_query("update resource set access=? where ref=?",array("i",$newaccess,"i",$resource["ref"]));

                if ($newaccess==3)
                        {
                        # Copy custom access
                        ps_query("insert into resource_custom_access (resource,usergroup,user,access) select ?, usergroup,user,access from resource_custom_access where resource = ?",array("i",$resource["ref"],"i",$lastresource["ref"]));
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
* @uses ps_query()
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
                            update_resource_field_column($ref,$resource_type_field,$values_string);
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
* @uses ps_query()
*
* @param integer $from Resource we are copying related resources from
* @param integer $ref  Resource we are copying related resources to
*
* @return void
*/
function copyRelatedResources($from, $to)
    {
	ps_query("insert into resource_related(resource,related) SELECT ?,related FROM resource_related WHERE resource=? AND related <> ?",array("i",$to,"i",$from,"i",$to));
    }


function process_edit_form($ref, $resource)
	{
    global $multiple, $lang, $embedded_data_user_select, $embedded_data_user_select_fields, $data_only_resource_types,
    $check_edit_checksums, $uploadparams, $resource_type_force_selection, $relate_on_upload, $enable_related_resources,
    $is_template, $upload_collection_name_required, $upload_review_mode, $userref, $userref, $collection_add, $baseurl_short,
    $no_exif, $autorotate;

	# save data
    # When auto saving, pass forward the field so only this is saved.
    $autosave_field=getval("autosave_field","");

    # Upload template: Change resource type
    $resource_type=getval("resource_type","");
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
        if (getval("entercolname","")=="" && getval("collection_add","")=="new")
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
    ps_query("UPDATE resource SET modified=NOW() WHERE ref=?",array("i",$resource));
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

    $lastmodified  = ps_query("SELECT r.ref, r.modified FROM resource r WHERE r.ref IN (" . ps_param_insert(count($resources)) . ") ORDER BY r.modified DESC",ps_param_fill($resources,"i"));
    $lastuserdetails = ps_query("SELECT u.username, u.fullname, rl.date FROM resource_log rl LEFT JOIN user u on u.ref=rl.user WHERE rl.resource = ? AND rl.type='e'",array("i",$lastmodified[0]["ref"]));
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
* @return boolean | int       int = id of new alternative file; false = file not saved
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

    // Values may be passed in POST or GET data from upload_batch.php
    $replace_resource_original_alt_filename = getval('replace_resource_original_alt_filename', ''); // alternative filename
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

    $newaref = add_alternative_file($ref, $newaltname, $newaltdescription, $origfilename, $origdata['file_extension'], $origdata['file_size']);

    $origpath=get_resource_path($ref, true, "", true, $origdata["file_extension"]);
    $newaltpath=get_resource_path($ref, true, "", true, $origdata["file_extension"], -1, 1, false, "", $newaref);
    # Move the old file to the alternative file location
    if(!hook('save_original_alternative_extra', '', array('origpath' => $origpath, 'newaltpath' => $newaltpath)))
        {
        $result = rename($origpath, $newaltpath);
        }

    if ($alternative_file_previews)
        {
        // Move the old previews to new paths
        $ps=ps_query("select * from preview_size",array());
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
    return $newaref;
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

    hook('replace_resource_file_extra', '', array($resource));
    $log_ref = resource_log($ref,LOG_CODE_REPLACED,'','','');
    daily_stat('Resource upload', $ref);
    hook("additional_replace_existing","",array($ref,$log_ref));

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
    $year   = sprintf("%04d", getval("field_" . $date . "-y",""));
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


function update_node_hitcount_from_search($resource,$search)
    {
    // For the specified $resource, increment the hitcount for each node that has been used in $search
    // This is done into a temporary column first (new_hit_count) so existing results are not affected.
    // copy_hitcount_to_live() is then executed at a set interval to make this data live.
    // Note that from v10 the resource_keyword table is no longer used
    $nodes = [];
    $notsearchednodes = [];
    resolve_given_nodes($search, $nodes, $notsearchednodes);
    if (count($nodes)>0)
        {
        update_resource_node_hitcount($resource,array_column($nodes,0));
        }
    }
    

function copy_hitcount_to_live()
    {
    # Also update the resource table
    # Also update the resource table
    # greatest() is used so the value is taken from the hit_count column in the event that new_hit_count is zero to support installations that did not previously have a new_hit_count column (i.e. upgrade compatability)
    ps_query("update resource set hit_count=greatest(hit_count,new_hit_count)");

    # Also now update resource_node_hitcount())
    ps_query("update resource_node set hit_count=new_hit_count");
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
function get_image_sizes(int $ref,$internal=false,$extension="jpg",$onlyifexists=true)
    {
    global $imagemagick_calculate_sizes;

    # Work out resource type
    $resource_data = get_resource_data($ref);
    $resource_type = $resource_data["resource_type"];

    # add the original image
    $return=array();
    $lastname=ps_value("select name value from preview_size where width=(select max(width) from preview_size)",array(), ""); # Start with the highest resolution.
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
        $dimensions = ps_query("select width,height,file_size,resolution,unit from resource_dimensions where resource=?",array("i",$ref));

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
            if($fileinfo !== false)
                {
                $filesize = $fileinfo[0];
                $sw= $fileinfo[1];
                $sh = $fileinfo[2];
                }
            else
                {
                $filesize = $resource_data["file_size"];
                $sw = 0;
                $sh = 0;
                }
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
    $sizes=ps_query("select " . columns_in("preview_size") . " from preview_size order by width desc");

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
                if ($file_exists && filesize_unlimited($path) > 0)
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
        $quality_val=ps_value("select quality value from preview_size where id=?",array("s",$size), '');
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
    return ps_array("select related value from resource_related where resource=? union select resource value from resource_related where related=?",array("i",$ref,"i",$ref));
    }


function get_field_options($ref,$nodeinfo = false)
    {
    # For the field with reference $ref, return a sorted array of options. Optionally use the node IDs as array keys
    if(!is_numeric($ref))
        {
        $ref = ps_value("select ref value from resource_type_field where name=?'",array("s",$ref), "", "schema"); // $ref is a string in this case
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
    global $rt_fieldtype_cache;

    $return              = '';
    $resource_type_field = $field;
    // Update cache
    if(!isset($rt_fieldtype_cache[$field]))
        {
        $rt_fieldtype_cache[$field] = ps_value("SELECT type AS `value` FROM resource_type_field WHERE ref = ? OR name = ?", ["i",$resource_type_field,"i",$resource_type_field],null, "schema");
        }
    $resnodes = get_resource_nodes($resource, $resource_type_field, TRUE);

    if($rt_fieldtype_cache[$field] == FIELD_TYPE_CATEGORY_TREE)
        {
        $return = get_tree_strings($resnodes,false);
        }
    else
        {
        $return = implode(', ', array_column($resnodes, 'name'));
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
        $r = ps_query("select ref,id,width,height,padtosize,name,internal,allow_preview,allow_restricted,quality from preview_size " . $condition . " order by width asc"); // $condition does not contain any user entered params and is safe for inclusion

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
    return ps_value("select allow_restricted value from preview_size where id=?",array("s",$id), false);
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

    if (count($field_refs) === 0)
        {
        return array();
        }

    $fields=ps_query("
        SELECT 
               ref,
               name,
               title,
               field_constraint,
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
               linked_data_field,
               automatic_nodes_ordering,
               fits_field,
               personal_data,
               include_in_csv_export,
               browse_bar,
               read_only,
               active,
               full_width
          FROM resource_type_field
         WHERE ref IN (" . ps_param_insert(count($field_refs)) . ")
      ORDER BY order_by", ps_param_fill($field_refs,"i"),"schema");

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
        $fields=ps_query("select ref,active from resource_type_field where length(name)>0",array(),"schema");
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
        $fields=ps_query("select name from resource_type_field where type=7 or type=2 or type=3 and length(name)>0 order by order_by", array(), "schema");
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
    $extension=strtolower((string) $extension);

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
    ps_query("update collection_resource set purchase_size=?,purchase_price=? where collection=? and resource=?", array("s",$size,"d",$price,"i",$collection,"i",$resource));
    return true;
    }

/**
 * Update ecommerce user's basket to indicate it has been purchased after PayPal callback (invoice users will pactually ay later with manual invoicing)
 *
 * @param  int $collection
 * @param  string $emailconfirmation - LEGACY MUNUSED
 * @return boolean
 */
function payment_set_complete($collection)
    {
    global $applicationname,$baseurl,$userref,$username,$useremail,$userfullname,$lang,$currency_symbol;
    // Mark items in the collection as paid so they can be downloaded.
    ps_query("UPDATE collection_resource SET purchase_complete=1 WHERE collection=?",["i",$collection]);

    // For each resource, add an entry to the log to show it has been purchased.
    $resources=ps_query("SELECT * FROM collection_resource WHERE collection=?",array("i",$collection));

    // Construct summary, separating lang entries from fixed text
    $summaryparts = [];
    $summaryparts[] = "<style>.InfoTable td {padding:5px;}</style><table border=\"1\" class=\"InfoTable\"><tr><td><strong>";
    
    $summaryparts[] = "lang_property-reference";
    $summaryparts[] = "</strong></td><td><strong>";
    $summaryparts[] = "lang_size";
    $summaryparts[] = "</strong></td><td><strong>";
    $summaryparts[] = "lang_price";
    $summaryparts[] = "</strong></td></tr>";

    foreach ($resources as $resource)
        {
        $purchasesize=$resource["purchase_size"];
        if ($purchasesize=="")
            {
            $purchasesize=$lang["original"];
            }
        resource_log($resource["resource"],LOG_CODE_PAID,0,"","","",0,$resource["purchase_size"],$resource["purchase_price"]);
        
        $summaryparts[] = "<tr><td>" . $resource["resource"] . "</td><td>";
        $summaryparts[] = ($purchasesize=="" ? "lang_original" : $purchasesize);
        $summaryparts[] = "</td><td>" . $currency_symbol . $resource["purchase_price"] . "</td></tr>";
        }
    $summaryparts[] = "</table>";

    // Construct message components
    $notify_users=get_notification_users("RESOURCE_ACCESS");
    $notifymessage = new ResourceSpaceUserNotification();
    $notifymessage->set_text("lang_purchase_complete_email_admin_body");
    $notifymessage->append_text("<br/><br/>");
    $notifymessage->append_text("lang_username");
    $notifymessage->append_text(": " . $username . " (" . $userfullname . ")<br/><br/>");    
    foreach($summaryparts as $summarypart)
        {
        $notifymessage->append_text($summarypart);
        }    
    $notifymessage->user_preference = "user_pref_resource_access_notifications";
    $notifymessage->set_subject("lang_purchase_complete_email_admin");
    $notifymessage->url = $baseurl . "/?c=" . $collection;
    send_user_notification($notify_users,$notifymessage);
    
    // Send email to user (not a notification as may need to be kept for reference)
    $userconfirmmessage = new ResourceSpaceUserNotification();
    $userconfirmmessage->set_text("lang_purchase_complete_email_user_body");
    $userconfirmmessage->append_text("<br/><br/>");
    foreach($summaryparts as $summarypart)
        {
        $userconfirmmessage->append_text($summarypart);
        }        
    $userconfirmmessage->set_subject("lang_purchase_complete_email_user");
    $userconfirmmessage->url = $baseurl . "/?c=" . $collection;

    send_user_notification([$userref],$userconfirmmessage,true);
        
    // Rename so that can be viewed on my purchases page
    ps_query("UPDATE collection SET name = ? WHERE ref = ?",["s",date("Y-m-d H:i"),"i",$collection]);

    return true;
    }


/**
 * Get references of resource type fields that are indexed
 *
 * @return array
 */
function get_indexed_resource_type_fields()
    {
    return ps_array("select ref as value from resource_type_field where keywords_index=1",array(),"schema");
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

    $fields = array_column(ps_query('DESCRIBE resource_type_field'), 'Field');
    $valid_sorts = ['asc', 'ascending', 'desc', 'descending'];
    if(!in_array($field_order_by,  $fields)){$field_order_by = 'ref';}
    if(!in_array(strtolower($field_sort), $valid_sorts)){$field_sort = 'asc';}

    $conditionsql=""; $params = [];
    if(is_array($restypes))
        {
        $conditionsql = " WHERE resource_type IN (". ps_param_insert(count($restypes)) .")";
        $params = ps_param_fill($restypes, 'i');
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
        if($conditionsql != "")
            {
            $conditionsql .= " AND ( ";
            }
        else
            {
            $conditionsql .= " WHERE ( ";
            }
        $conditionsql.=" name LIKE '%?%' OR title LIKE '%?%' OR tab_name LIKE '%?%' OR exiftool_field LIKE '%?%' OR help_text LIKE '%?%' OR ref LIKE '%?%' OR tooltip_text LIKE '%?%' OR display_template LIKE '%?%')";
        $params = array_merge($params, ['s', $find, 's', $find, 's', $find, 's', $find, 's', $find, 's', $find, 's', $find, 's', $find]);
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
        $conditionsql .= " type IN (". ps_param_insert(count($newfieldtypes)) ."))";
        $params = array_merge($params, ps_param_fill($newfieldtypes, 'i'));
		}
    // Allow for sorting, enabled for use by System Setup pages
    //if(!in_array($field_order_by,array("ref","name","tab_name","type","order_by","keywords_index","resource_type","display_field","required"))){$field_order_by="ref";}

    $allfields = ps_query("
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
               tab,
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
          FROM resource_type_field" . $conditionsql . " ORDER BY active desc," . $field_order_by . " " . $field_sort, $params, "schema"); // TO DO - JN to ensure order by params locked to expected as per comment on r20006



    // Sort by translated strings if sorting by title
    if(strtolower($field_order_by) == "title")
        {
        $sortflag = strtolower($field_sort) == "asc" ? SORT_ASC : SORT_DESC;
        foreach($allfields as $field)
            {
            $translations[] = i18n_get_translated($field["title"]);
            }
        array_multisort($translations,$sortflag,SORT_STRING,$allfields);
        }

    return $allfields;
    }


function notify_resource_change($resource)
    {
    debug("notify_resource_change " . $resource);
    global $notify_on_resource_change_days, $baseurl;
    // Check to see if we need to notify users of this change
    if($notify_on_resource_change_days==0 || !is_int($notify_on_resource_change_days))
        {
        return false;
        }

    debug("notify_resource_change - checking for users that have downloaded this resource " . $resource);
    $download_users=ps_query("SELECT DISTINCT u.ref, u.email FROM resource_log rl LEFT JOIN user u ON rl.user=u.ref WHERE rl.type='d' AND rl.resource=? AND DATEDIFF(NOW(),date)<?",["i",$resource,"i",$notify_on_resource_change_days],"");
    $message_users=array();
    if(count($download_users)>0)
        {
        $notifymessage = new ResourceSpaceUserNotification();
        $notifymessage->set_subject("lang_notify_resource_change_email_subject");
        $notifymessage->set_text("lang_notify_resource_change_email",["[days]","[url]"],[$notify_on_resource_change_days,$baseurl . "/?r=" . $resource]);
        $notifymessage->preference = "user_pref_resource_notifications";
        $notifymessage->url = $baseurl . "/?r=" . $resource;
        $notifymessage->template = 'notify_resource_change_email';
        $notifymessage->templatevars = ["days"=>$notify_on_resource_change_days,"url"=>$baseurl . "/?r=" . $resource];
        send_user_notification($download_users,$notifymessage);
        }
    }

# Takes a string and add verbatim regex matches to the keywords list on found matches (for that field)
# It solves the problem, for example, indexing an entire "nnn.nnn.nnn" string value when '.' are used as a keyword separator.
# Uses config option $resource_field_verbatim_keyword_regex[resource type field] = '/regex/'
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
        $found_name = ps_value("SELECT `name` AS 'value' FROM `resource_type_field` WHERE `ref`=?", array("i",$resource_type_field), "");
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
            $filename=$origfile["name"];

            //Try to use the name that the user has set for the file and if not then default to the original filename. 
            if(strpos($filename, '.') != false && substr($filename, strrpos($filename,'.')+1) == $ext)
                {
                $origfile=$filename;
                }
            elseif(strpos($filename, '.') != false && substr($filename, strrpos($filename,'.')+1) != $ext)
                {
                $origfile=remove_extension($filename) . '.' . $ext;
                }
            elseif($ext != '' && strpos($filename, '.') == false)
                {
                $origfile=$filename . '.' . $ext;
                }
            else
                {
                $origfile=$origfile["file_name"];
                }
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
        $newfilename=safe_file_name( get_data_by_field($ref,$download_filename_field) );
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
    $resource_types = ps_array("SELECT ref AS value FROM resource_type",array());
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
* is the only one enabled.
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
        $shortname = mb_substr(mb_strtolower(str_replace(array("_", "-"), "", safe_file_name($name))), 0, 20);
        }

    $duplicate = (boolean) ps_value("SELECT count(ref) AS `value` FROM resource_type_field WHERE `name` = ?", array("s",$shortname), 0, "schema");

    ps_query("INSERT INTO resource_type_field (title, resource_type, type, `name`, keywords_index) VALUES (?, ?, ?, ?, ?)",
    array("s",$name,"i",$restype,"i",$type,"s",$shortname,"i",($index ? "1" : "0")));
    $new = sql_insert_id();

    if($duplicate)
        {
        ps_query("UPDATE resource_type_field SET `name` = ? WHERE ref = ?", array("s",$shortname . $new,"i",$new));
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
            if(isset($$varname) && ((is_array($$varname) && in_array($ref,$$varname)) || (int)$$varname==$ref))
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
        return $lang["admin_delete_field_error"] . "<br/>\$" . implode(", \$",$fieldvars);
        }
    else if(!empty($core_field_scopes))
        {
        return sprintf('%s%s', $lang["admin_delete_field_error_scopes"], implode(', ', $core_field_scopes));
        }


    $fieldinfo = get_resource_type_field($ref);

    // Delete the resource type field
    ps_query("DELETE FROM resource_type_field WHERE ref=?",["i",$ref]);

    // Remove all nodes and keywords or resources. Always remove nodes last otherwise foreign keys will not work
    // Remove all nodes and keywords or resources. Always remove nodes last otherwise foreign keys will not work
    ps_query("DELETE rn.* FROM resource_node rn LEFT JOIN node n ON n.ref=rn.node WHERE n.resource_type_field = ?",["i",$ref]);
    ps_query("DELETE nk.* FROM node_keyword AS nk LEFT JOIN node AS n ON n.ref = nk.node WHERE n.resource_type_field = ?",["i",$ref]);
    ps_query("DELETE FROM node WHERE resource_type_field = ?",["i",$ref]);

    hook("after_delete_resource_type_field");
    hook("after_delete_resource_type_field");

    log_activity('Deleted metadata field "' . $fieldinfo["title"] . '" (' . $fieldinfo["ref"] . ')',LOG_CODE_DELETED,null,'resource_type_field',null,$ref);

    clear_query_cache("schema");

    return true;
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
        $resource_data  = get_resource_data($ref);
        $lockeduser     =  $resource_data["lock_user"];
        $edit_access    = get_edit_access($ref,false,$resource_data);
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

    ps_query("UPDATE resource SET lock_user=? WHERE ref=?",array("i",($lockaction ? $newlockuser : "0"),"i",(int)$ref));
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
 *                              "ignore_permissions"- (bool) Show all shares, irrespective of permissions
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
        "ignore_permissions",
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
    $share_sort = strtoupper((string) $share_sort) == "ASC" ? "ASC" : "DESC";

    $conditions = array(); $params = [];
    if((int)$share_user > 0 && ($share_user == $userref || checkperm_user_edit($share_user))
        )
        {
        $conditions[] = "eak.user = ?";
        $params = ['i', (int)$share_user];
        }
    elseif(!checkperm('a') && !$ignore_permissions)
        {
        $usercondition = "eak.user = ?";
        $params = array_merge($params, ['i', (int)$userref]);
        if(checkperm("ex"))
            {
            // Can also see shares that never expire
            $usercondition = " (expires IS NULL OR " . $usercondition . ")";
            }
        $conditions[] =$usercondition;
        }

    if(!is_null($share_group) && (int)$share_group > 0  && checkperm('a'))
        {
        $conditions[] = "eak.usergroup = ?";
        $params = array_merge($params, ['i', (int)$share_group]);
        }

    if(!is_null($access_key))
        {
        $conditions[] = "eak.access_key = ?";
        $params = array_merge($params, ['s', $access_key]);
        }

    if((int)$share_type === 0)
        {
        $conditions[] = "(eak.upload=0 OR eak.upload IS NULL)";
        }
    elseif((int)$share_type === 1)
        {
        $conditions[] = "eak.upload=1";
        }
    if(is_int_loose($share_collection) && $share_collection != 0)
        {
        $conditions[] = "eak.collection = ?";
        $params = array_merge($params, ['i', (int)$share_collection]);
        }
    if((int)$share_resource > 0)
        {
        $conditions[] = "eak.resource = ?";
        $params = array_merge($params, ['i', (int)$share_resource]);
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
       ORDER BY " . $share_order_by . " " . $share_sort;

    $external_shares = ps_query($external_access_keys_query, $params);
    return $external_shares;
    }

/**
* Get video duration
*
* @uses run_command()
*
* @param  string $file_path    Path to video file
*
* @return float
*/
function get_video_duration(string $file_path)
    {
        // Escape file_path
        $file_path_escaped = escapeshellarg($file_path);

        if(!empty(run_command("exiftool -duration {$file_path_escaped}")))
            {
            $duration_tag = run_command("exiftool -n -duration {$file_path_escaped}");
            $duration = str_replace(" s", "", substr($duration_tag, strpos($duration_tag, ":") + 2));
            return floatval($duration);
            }
        else
            {
            return 0;
            }
    }

/**
 * Relate all resources in the passed array with each other
 *
 * @param  array $related Array of resource IDs
 * @return boolean
 */
function relate_all_resources(array $related = [])
    {
    $error = false;
    array_filter($related,"is_int_loose");
    foreach($related as $ref)
        {
        $other_refs = array_diff($related,array($ref));
        $success = update_related_resource($ref,$other_refs,true);
        if(!$success)
            {
            $error = true;
            }
        }
    return !$error;
    }

/**
 * Apply new order to metadata fields
 *
 * @param  array $neworder  Field IDs in new order
 *
 * @return void
 */
function update_resource_type_field_order($neworder)
	{
	global $lang;
	if (!is_array($neworder)) {
		exit ("Error: invalid input to update_resource_type_field_order function.");
	}

	$updatesql= "update resource_type_field set order_by=(case ref ";
	$counter = 10;$params=array();
	foreach ($neworder as $restype){
		$updatesql.= "when ? then ? ";
        $params[]="i";$params[]=$restype;
        $params[]="i";$params[]=$counter;
        $counter = $counter + 10;
	}
	$updatesql.= "else order_by END)";
	ps_query($updatesql,$params);
	clear_query_cache("schema");
	log_activity($lang['resourcetypefieldreordered'],LOG_CODE_REORDERED,implode(', ',$neworder),'resource_type_field','order_by');
	}

/**
 * Apply a new order to resource types
 *
 * @param  array $neworder  Resource type IDs in new order
 *
 * @return void
 */
function update_resource_type_order($neworder)
	{
	global $lang;
	if (!is_array($neworder)) {
		exit ("Error: invalid input to update_resource_type_field_order function.");
	}

	$updatesql= "update resource_type set order_by=(case ref ";
	$counter = 10;
    $params=array();

	foreach ($neworder as $restype){
		$updatesql.= "when ? then ? ";
        $params[]="i";$params[]=$restype;
        $params[]="i";$params[]=$counter;
		$counter = $counter + 10;
	}
	$updatesql.= "else order_by END)";
	ps_query($updatesql,$params);
	clear_query_cache("schema");
	log_activity($lang['resourcetypereordered'],LOG_CODE_REORDERED,implode(', ',$neworder),'resource_type','order_by');
	}

/**
 * Check if file can be rendered in browser via download.php
 *
 * @param  string $path Path to file
 *
 * @return bool
 */
function allow_in_browser($path)
    {
    if(!file_exists($path) || is_dir($path))
        {
        return false;
        }
    // Permitted mime types can only be overridden by plugins
    $permitted_mime[] = "application/pdf";
    $permitted_mime[] = "image/jpeg";
    $permitted_mime[] = "image/png";
    $permitted_mime[] = "image/gif";
    $permitted_mime[] = "audio/mpeg";
    $permitted_mime[] = "video/mp4";
    $permitted_mime[] = "text/plain";
    $permitted_mime[] = "text/csv";
    $allow = hook('allow_in_browser',"",[$permitted_mime]);
    if(is_array($allow))
        {
        $permitted_mime = $allow;
        }

    if (function_exists('mime_content_type'))
        {
        $type = mime_content_type($path);
        }
    else
        {
        $type = get_mime_type($path);
        }
    if($type == "application/octet-stream")
        {
        # Not properly detected, try and get mime type via exiftool if possible
        $exiftool_fullpath = get_utility_path("exiftool");
        if ($exiftool_fullpath!=false)
            {
            $command=$exiftool_fullpath . " -s -s -s -t -mimetype %PATH";
            $cmd_args['%PATH'] = $path;
            $type = run_command($command, false, $cmd_args);
            }
        }
    if(in_array($type,$permitted_mime))
        {
        return true;
        }
    return false;
    }

/**
* Update the value of the fieldXX field on resource table
*
* @param integer $resource  - Resource ID
* @param integer $field     - Metadata field ID
* @param string  $value     - Value

* @return boolean
*/
function update_resource_field_column($resource,$field,$value)
    {
    if(!is_int_loose($resource) || !is_int_loose($field))
        {
        return false;
        }
    $sql = "UPDATE resource SET `field" . $field . "` = ? WHERE ref = ?";
    $params = ["s",truncate_join_field_value($value),"i",$resource];
    ps_query($sql,$params);
    return true;
    }