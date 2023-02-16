<?php
include_once "../include/db.php";

// External share support
$k = getval('k','');
$upload_collection = getval('upload_share_active',''); 
if ($k=="" || (!check_access_key_collection($upload_collection,$k)))
    {  
    include "../include/authenticate.php";
    }
include_once "../include/image_processing.php";
# Editing resource or collection of resources (multiple)?
$ref=getval("ref","",true);
if(getval("create","")!="" && $ref==0 && $userref>0){$ref=0-$userref;} // Saves manual link creation having to work out user template ref
$use=$ref;

# Fetch search details (for next/back browsing and forwarding of search params)
$search=getval("search","");
$order_by=getval("order_by","relevance");
$offset=getval("offset",0,true);
$restypes=getval("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}
$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);
$modal = (getval("modal", "") == "true");
$single=getval("single","") != "" || getval("forcesingle","") != "";
$disablenavlinks=getval("disablenav","")=="true";
$uploader = getval("uploader","");
$collection = getval('collection', 0, true);
$resetform = (getval("resetform", false) !== false);
$ajax = filter_var(getval("ajax", false), FILTER_VALIDATE_BOOLEAN);
$archive=getval("archive",0); // This is the archive state for searching, NOT the archive state to be set from the form POST which we get later
$external_upload = upload_share_active();

if($camera_autorotation)
    {
    // If enabled and specified in URL then override the default
    $autorotate = getval("autorotate","");

    if($autorotate == "")
        {
        $autorotate = (isset($autorotation_preference) ? $autorotation_preference : $camera_autorotation_checked);
        }
    else
        {
        $autorotate = true;
        }
    }
else
    {
    $autorotate = false;
    }
    
$collection_add = getval('collection_add', '');
if($embedded_data_user_select)
  {
  $no_exif=getval("exif_option","");
  }
else
  {
  $no_exif=getval("no_exif","");
  }
$upload_here = (getval('upload_here', '') != '' ? true : false);

$uploadparams = array();
$uploadparams["relateto"] = getval("relateto","");
$uploadparams["redirecturl"] =  getval("redirecturl","");
$uploadparams["collection_add"] =  $collection_add;
$uploadparams["metadatatemplate"] = getval("metadatatemplate","");
$uploadparams["no_exif"] = $no_exif;
$uploadparams["autorotate"] = $autorotate;
$uploadparams["entercolname"] = getval("entercolname","");
$uploadparams["k"] = $k;

# Upload review mode will be true if we are coming from upload_batch and then editing (config $upload_then_edit)
#   or if it's a special collection search where the collection is the negated user reference meaning its resources are to be edited 
$upload_review_mode=(getval("upload_review_mode","")!="" || $search=="!collection-" . $userref);
$lastedited = getval('lastedited',0,true);
$lockable_fields = $upload_review_lock_metadata && $upload_review_mode;
$locked_fields = (!$resetform && getval("lockedfields","") != "") ? trim_array(explode(",",getval("lockedfields",""))) : array();
$save_auto_next = getval("save_auto_next","") != "";

if ($upload_review_mode)
    {
    # Set the collection and ref if not already set.
    if($external_upload !== false) 
        {
        $rs_session = get_rs_session_id(true);
        $ci=get_session_collections($rs_session);
        if (count($ci)==0)
            {
            error_alert($lang["error_no_resources_edit"]);
            exit();
            }
        else
            {
            $collection=$ci[0];
            }            
        } 
    else
        {
        $collection=0-$userref;
        }

    # Make sure review collection is clear of any resources moved out of users archive status permissions by other users
    if ($edit_access_for_contributor == false)
        {
        collection_cleanup_inaccessible_resources($collection);
        }
    # Start reviewing at the first resource. Need to search all worflow states and remove filters as no data has been set yet
    $search_all_workflow_states_cache = $search_all_workflow_states;
    $usersearchfilter_cache = $usersearchfilter;
    $search_all_workflow_states = TRUE;
    $check_edit_checksums = false;
    $usersearchfilter = "";
    $upload_review_collection_order_by = ($upload_review_mode_review_by_resourceid ? 'resourceid' : $default_collection_sort);
    $review_collection_contents = do_search("!collection{$collection}", '', $upload_review_collection_order_by);
    # Revert save settings
    $search_all_workflow_states = $search_all_workflow_states_cache;
    $usersearchfilter = $usersearchfilter_cache;
    # Set the resource to the first ref number. If the collection is empty then tagging is complete. Go to the recently added page.
    if (isset($review_collection_contents[0]["ref"]))
        {
        $ref=$review_collection_contents[0]["ref"];
        $use=$ref;
        }
    else 
        {
        if($external_upload)
            {
            debug("external upload - no resources to review");
            // Delete the temporary upload_collection
            delete_collection($collection);
            external_upload_notify($external_upload, $k, $collection);
            $url = generateURL($baseurl . "/pages/done.php",array("text" => "upload_share_complete", "k"=> $k, "collection"=>$external_upload));
            }
        else
            {
            // Redirect to recent user uploads
            $defaultarchivestate = get_default_archive_state();
            $redirectparams = array(
                "search"=>"!contributions" . $userref,
                "order_by"=>"resourceid",
                "sort"=>"DESC",
                "archive"=>$defaultarchivestate,
                "refreshcollectionframe"=>"true",
                "resetlockedfields"=>"true",
                "collection_add"=>$collection_add
                );
                
            if ($defaultarchivestate == -2 && $pending_submission_prompt_review && checkperm("e-1"))
                {
                $redirectparams["promptsubmit"] = 'true';
                }
            
            $url = generateURL($baseurl . "/pages/search.php",$redirectparams);
            }
        redirect($url);
        exit();
        }
    }

// Reset form (step 1 in upload) should clear all form data, including user template. The desired intention of the user is to clear it and 
// have no old metadata values.
if($resetform && $ref < 0 && !$upload_review_mode)
    {
    clear_resource_data($ref);
    }

// Ability to avoid editing conflicts by checking checksums.
// NOTE: this should NOT apply to upload.
$check_edit_checksums = true;
if($ref < 0 || $upload_review_mode)
    {
    $check_edit_checksums = false;
    }

global $merge_filename_with_title, $merge_filename_with_title_default;
if($merge_filename_with_title && $ref < 0)
    {
    $merge_filename_with_title_option = getval('merge_filename_with_title_option', $merge_filename_with_title_default);
    $merge_filename_with_title_include_extensions = getval('merge_filename_with_title_include_extensions', '');
    $merge_filename_with_title_spacer = getval('merge_filename_with_title_spacer', '');

    if(strtolower($merge_filename_with_title_option) != '') 
        {
        $uploadparams["merge_filename_with_title_option"] = $merge_filename_with_title_option;
        }
    if($merge_filename_with_title_include_extensions != '')
        {
        $uploadparams["merge_filename_with_title_include_extensions"] = $merge_filename_with_title_include_extensions;
        }
    if($merge_filename_with_title_spacer != '')
        {
        $uploadparams["merge_filename_with_title_spacer"] = $merge_filename_with_title_spacer;
        }
    }

global $tabs_on_edit;
$collapsible_sections=true;
if($tabs_on_edit || $upload_review_mode){$collapsible_sections=false;}

$errors=array(); # The results of the save operation (e.g. required field messages)

# Disable auto save for upload forms - it's not appropriate.
if ($ref<0 || $upload_review_mode) { $edit_autosave=false; }

# next / previous resource browsing
$go=getval("go","");
if ($go!="")
    {
    # Re-run the search and locate the next and previous records.
    $modified_result_set=hook("modifypagingresult"); 
    if ($modified_result_set)
        {
        $result=$modified_result_set;
        }
    else
        {    
        $result=do_search($search,$restypes,$order_by,$archive,240+$offset+1,$sort);
        }
    if (is_array($result))
        {
        # Locate this resource
        $pos=-1;
        $result_count = count($result);
        for ($n=0;$n<$result_count;$n++)
            {
            if (isset($result[$n]["ref"]) && $result[$n]["ref"]==$ref) {$pos=$n;}
            }
        if ($pos!=-1)
            {
            if (($go=="previous") && ($pos>0)) 
                {
                $ref=$result[$pos-1]["ref"];
                }
            if (($go=="next") && ($pos<($n-1)))
                {
                $ref=$result[$pos+1]["ref"];
                if (($pos+1)>=($offset+72))
                    {
                    $offset=$pos+1;
                    }
                } # move to next page if we've advanced far enough
            }
        else
            {
            ?>
            <script type="text/javascript">
            alert("<?php echo $lang["resourcenotinresults"] ?>");
            </script>
            <?php
            }
        $use = $ref;
        }
   }
$editsearch = getval("editsearchresults","") != "";
$edit_selection_collection_resources = ($editsearch && $collection == $USER_SELECTION_COLLECTION);
if($editsearch)
    {
    debug("edit.php: editing multiple items...");
    debug("edit.php: \$search = {$search}");
    debug("edit.php: \$collection = {$collection}");
    debug("edit.php: \$editsearch = " . ($editsearch ? 'true' : 'false'));

    $multiple = true;
    $edit_autosave = false; # Do not allow auto saving for batch editing.

    # Check all resources are editable
    
    # Editable_only=false (so returns resources whether editable or not)
    $searchitems = do_search($search, $restypes, 'resourceid', $archive, -1, $sort, false, 0, false, false, '', false, false, true, false);
    if (!is_array($searchitems)){$searchitems = array();}
    $all_resources_count = count($searchitems);
    $all_resource_refs=array_column($searchitems,"ref");

    # Editable_only=true (so returns editable resources only)
    $edititems   = do_search($search, $restypes, 'resourceid', $archive, -1, $sort, false, 0, false, false, '', false, false, true, true);
    if (!is_array($edititems)){$edititems = array();}
    $editable_resources_count = count($edititems);
    $editable_resource_refs=array_column($edititems,"ref");

    # If not all resources are editable then the batch edit may not be approprate
    if($editable_resources_count != $all_resources_count)
        {
        # Counts differ meaning there are non-editable resources
        $non_editable_resource_refs=array_diff($all_resource_refs,$editable_resource_refs);

        # Is grant edit present for all non-editables?
        foreach($non_editable_resource_refs as $non_editable_ref) 
            {
            if ( !hook('customediteaccess','',array($non_editable_ref)) ) 
                {
                $error = $lang['error-editpermissiondenied'];
                error_alert($error);
                exit();
                }
            }

        # All non_editables have grant edit
        # Don't exit as batch edit is OK
        }

    # The $items array is used later, so must be updated with all items
    $items = $all_resource_refs;

    # Establish a list of resource types which will be involved in this edit
    $items_resource_types = array_unique(array_column($edititems,"resource_type"));
    if(in_array('2',(array_column($edititems,'archive')))){$items_resource_types[]=999;}

    $last_resource_edit = get_last_resource_edit_array($items); 

    # This is a multiple item edit (even if there is only one item in the list), so use the first resource as the template
    $ref = array_values($items)[0];
    $use = $ref;
    }
else
    {
    $multiple=false;
    }

# Fetch resource data.
$resource=get_resource_data($ref);

$metadatatemplate = !$resetform ? (getval(
    'metadatatemplate',
    ($metadata_template_default_option == 0 ? 0 : $metadata_template_default_option),
    true
    )) : $metadata_template_default_option;

if($resetform)
    {
    $metadatatemplate =  $metadata_template_default_option;
    }
    
if ($lockable_fields && $lastedited > 0)
    {
    // Update resource data with locked resource data from last edited resource
    $resource = copy_locked_data($resource, $locked_fields, $lastedited);
    }

if($ref < 0 && $resource_type_force_selection)
  {
  $resource_type = "";
  $resource["resource_type"] = "";
  }

// Create metadata resource record without uploading a file e.g. template, text only resource.
$create_record_only = getval("recordonly", "") != "";

// Set initial value for noupload
$noupload = getval("noupload","") != "" || in_array($resource['resource_type'], $data_only_resource_types);

# Allow to specify resource type from url for new resources
$resource_type=getval("resource_type","");
if ($ref<0 && !$create_record_only && $resource_type != "" && $resource_type!=$resource["resource_type"] && !checkperm("XU{$resource_type}"))     // only if new resource specified and user has permission for that resource type
    {
    update_resource_type($ref,intval($resource_type));
    $resource["resource_type"] = $resource_type;
    // Change the noupload as resource type has changed from that requested originally
    $noupload = in_array($resource['resource_type'], $data_only_resource_types);
    }

if($noupload)
    {
    $single=true;
    $uploadparams["single"] = "true";
    }
else
    {
    $uploadparams["forcesingle"] = '';
    $uploadparams["noupload"] = '';
    }

$uploadparams["resource_type"] = $resource['resource_type'];   

// Resource archive (ie user template - negative resource ID) can be default only when user actually gets to set it otherwise
// makes no sense in using it and we should let the system decide based on configuration and permissions what it should use.
$default_setarchivestate = ($show_status_and_access_on_upload || $resource['ref'] > 0 ? $resource['archive'] : '');
if ($resetform)
    {
    $setarchivestate = $default_setarchivestate;
    }
else
    {
    $setarchivestate = getval('status', $default_setarchivestate, TRUE);
    }
// Validate this is permitted
$setarchivestate = get_default_archive_state($setarchivestate);

$uploadparams["status"] = $setarchivestate;

if (in_array(getval("access", RESOURCE_ACCESS_INVALID_REQUEST, true), RESOURCE_ACCESS_TYPES) && !$resetform)
    {
    // Preserve selected access values including custom access if form validation returns a missed required field.
    $access_submitted = (int) getval("access", 2, true);
    if ($access_submitted == 3)
        {
        $submitted_access_groups = array();
        $custom_access_groups = get_resource_custom_access($ref);
        for ($n = 0; $n < count($custom_access_groups); $n++)
                {
                $access_usergroup = $custom_access_groups[$n]["ref"];
                $custom_access_level = getval("custom_" . $access_usergroup, 0);
                $submitted_access_groups[$access_usergroup] = (int) $custom_access_level;
                }
        }
    }

# Allow alternative configuration settings for this resource type.
resource_type_config_override($resource["resource_type"]);

# File readonly?
$resource_file_readonly=resource_file_readonly($ref);
# If upload template, check if the user has upload permission.
if ($ref<0 && !(checkperm("c") || checkperm("d")))
    {
    $error=$lang['error-permissiondenied'];
    error_alert($error);
    exit();
    }

# Check edit permission.
if (!get_edit_access($ref,$resource["archive"],false,$resource))
    {
    # The user is not allowed to edit this resource or the resource doesn't exist.
    $error=$lang['error-permissiondenied'];
    error_alert($error,!$modal);
    exit();
    }

if($resource["lock_user"] > 0 && $resource["lock_user"] != $userref)
    {
    $error = get_resource_lock_message($resource["lock_user"]);
    if(getval("autosave","")!="")
        {
        // Send JSON with error back
        http_response_code(403);
        exit($error);
        }
    else
        {
        error_alert($error,!$modal);
        exit();
        }
    }

if (getval("regen","")!="" && enforcePostRequest($ajax))
    {
    hook('edit_recreate_previews_extra', '', array($ref));
    ps_query("update resource set preview_attempts=0 WHERE ref= ?" , ['i', $ref]);
    create_previews($ref,false,$resource["file_extension"]);
    }

if (getval("regenexif","")!="" && enforcePostRequest($ajax))
    {
    extract_exif_comment($ref);
    }

# Establish if this is a metadata template resource, so we can switch off certain unnecessary features
$is_template=(isset($metadata_template_resource_type) && $resource["resource_type"]==$metadata_template_resource_type);

# If config option $blank_edit_template is set and form has not yet been submitted, blank the form for user edit templates.
if(0 > $ref && $blank_edit_template && '' == getval('submitted', ''))
    {
    clear_resource_data($ref);
    }

// If using metadata templates, make sure user templates are cleared but not when form is being submitted
if(0 > $ref && '' == getval('submitted', '') && isset($metadata_template_resource_type) && !$multiple)
    {
    clear_resource_data($ref);
    }

// Upload template: always reset to today's date (if configured).
if(0 > $ref && $reset_date_upload_template && isset($reset_date_field) && '' == getval('submitted', ''))
    {
    update_field($ref, $reset_date_field, date('Y-m-d H:i'));
    }

# check for upload disabled due to space limitations...
if ($ref<0 && isset($disk_quota_limit_size_warning_noupload))
    {
    # check free space
    if (isset($disksize))
        {
            # Use disk quota rather than real disk size
        $avail=$disksize*(1024*1024*1024);
        $used=get_total_disk_usage();
        $free=$avail-$used;
        }
    else
        {		
        $avail=disk_total_space($storagedir);
        $free=disk_free_space($storagedir);
        $used=$avail-$free;
        }
        
    # convert limit
    $limit=$disk_quota_limit_size_warning_noupload*1024*1024*1024;

    # compare against size setting
    if($free<=$limit)
        {
        # shut down uploading by redirecting to explanation page
        $explain=$baseurl_short."pages/no_uploads.php";
        redirect($explain);
        }
    }

// Check if upload should be disabled because the filestore location is indexed and browseable
if($ref < 0)
    {
    $cfb = check_filestore_browseability();
    if(!$cfb['index_disabled'])
        {
        exit(error_alert($lang['error_generic_misconfiguration'], true, 200)); 
        }
    }

$urlparams= array(
	'ref'				=> $ref,
    'search'			=> $search,
    'order_by'			=> $order_by,
    'offset'			=> $offset,
    'restypes'			=> $restypes,
    'archive'			=> $archive,
    'default_sort_direction' => $default_sort_direction,
    'sort'				=> $sort,
    'uploader'          => $uploader,
    'single'            => ($single ? "true" : ""),
    'collection'        => $collection,
    "collection_add"    => $collection_add,
    'editsearchresults' => ($editsearch ? "true" : ""),
    'k'                 => $k,
);

check_order_by_in_table_joins($order_by);

hook("editbeforeheader");

if(($embedded_data_user_select && getval("exif_option","")=="custom") || isset($embedded_data_user_select_fields))  
    {
    $exif_override=false;
    foreach($_POST as $postname=>$postvar)
        {
        if (strpos($postname,"exif_option_")!==false)
            {
            $uploadparams [$postname] = $postvar;
            $exif_override=true;
            }
        }
        
    if($exif_override)
        {
        $uploadparams["exif_override"] = "true";
        }
    }
        
# -----------------------------------
#           PERFORM SAVE
# -----------------------------------

if ((getval("autosave","")!="") || (getval("tweak","")=="" && getval("submitted","")!="" && !$resetform && getval("copyfrom","")==""))
    {
    hook("editbeforesave"); 
	if(!$multiple)
        {
        if(($ref < 0 || $upload_review_mode) && !$is_template && $metadata_template_mandatory && $metadatatemplate == 0)
            {
            $save_errors['metadatatemplate'] = $lang["usemetadatatemplate"] . ": " . $lang["requiredfield"];
            $show_error=true;
            }
        else
            {
            $save_errors = process_edit_form($ref, $resource);
            }
        
        if (($save_errors === true || $is_template) && getval("tweak","")=="")
            {
            if ($ref > 0 && getval("save","") != "" && enforcePostRequest($ajax))
                {
                if ($upload_review_mode)
                    {
                    # Drop this resource from the collection and either save all subsequent resources, or redirect thus picking the next resource.
                    if($external_upload)
                        {
                        remove_resource_from_collection($ref,$collection);  
                        }
                    else
                        {
                        remove_resource_from_collection($ref,0-$userref);
                        refresh_collection_frame();
                        }
                    
                    // If the metadata template has been locked it needs to be passed in the redirect
                    if(in_array("metadatatemplate",$locked_fields))
                        {
                        $urlparams['metadatatemplate'] = $metadatatemplate;
                        }
                            
                    if($save_auto_next)
                        {
                        // Process all remaining resources in the collection
                        $autosave_errors = false; 
                        $lastedited = $ref;
                        $restypearr = get_resource_types();
                        $resource_types = array();
                        // Sort into array with ids as keys
                        foreach($restypearr as $restype)
                            {
                            $resource_types[$restype["ref"]] = $restype;
                            }

                        $review_collection_contents_count = count($review_collection_contents);
                        for($n=1;$n<$review_collection_contents_count;$n++)
                            {
                            $auto_errors = array();
                            $ref = $review_collection_contents[$n]["ref"];
                            # Fetch resource data.
                            $resource=get_resource_data($ref);

                            // If the metadata template has been locked, copy the metadata from this first
                            if(in_array("metadatatemplate",$locked_fields) && $metadatatemplate > 0)
                                {
                                copyAllDataToResource($metadatatemplate, $ref,$resource);
                                }
                                
                            // Load resource metadata
                            $fields=get_resource_field_data($ref,false,!hook("customgetresourceperms"),NULL,"",$tabs_on_edit);
                            $all_selected_nodes = get_resource_nodes($ref);
                            
                            // Update resource data with locked resource data from last edited resource
                            $resource = copy_locked_data($resource, $locked_fields, $lastedited, true);
                                 
                            // Update $fields and all_selected_nodes with details of the last resource edited for locked fields
                            // NOTE: $fields and $all_selected_nodes are passed by reference
                            copy_locked_fields($ref,$fields,$all_selected_nodes,$locked_fields,$lastedited, true);
                            
                            // Autocomplete any blank fields without overwriting any existing metadata
                            autocomplete_blank_fields($ref, false);

                            // Update related resources if required
                            if(in_array("related_resources",$locked_fields))
                                {
                                copyRelatedResources($lastedited, $ref);
                                }
                                
                            // Check for any missing fields
                            $exemptfields =array();
                            foreach($fields as $field)
                                {
                                $fielderror = false;
                                if($field['required'] == 1
                                    && $field['hide_when_uploading'] != 1
                                    && !checkperm('F' . $field["ref"])
                                    &&  (
                                        $field["resource_type"] == $resource["resource_type"]
                                        ||
                                        ($field["resource_type"] == 0 && (bool)$resource_types[$resource["resource_type"]]["inherit_global_fields"])
                                        )
                                    )
                                    {
                                    $displaycondition = check_display_condition(0, $field, $fields, false); 
                                    if($displaycondition)
                                        {
                                        if(in_array($field['type'], $FIXED_LIST_FIELD_TYPES))
                                            {
                                            $field_nodes = get_resource_nodes($ref, $field['ref']);                        
                                            if(count($field_nodes) == 0)
                                                {
                                                $fielderror = true;  
                                                }
                                            }
                                        else
                                            {
                                            if (trim(strip_leading_comma($field["value"]) == ''))
                                                {
                                                $fielderror = true;  
                                                }
                                            }
                                        }
                                    }
                                    
                                # Also check for regular expression match
                                if (strlen(trim((string)$field["regexp_filter"]))>=1)
                                    {
                                    global $regexp_slash_replace;
                                    if(preg_match("#^" . str_replace($regexp_slash_replace, '\\', $field["regexp_filter"]) . "$#", (string) $field["value"], $matches) <= 0)
                                        {
                                        $fielderror = true;
                                        }
                                    }
                                
                                if($fielderror)
                                    {
                                    $auto_errors[$field['ref']] = i18n_get_translated($field['title']) . ": {$lang['requiredfield']}";
                                    }
                                }
                            
                            // If no errors, remove from collection and continue
                            if(count($auto_errors) == 0)
                                {
                                debug("edit: autosaved resource " . $ref . ", removing from collection " . (string)(0-$userref));
                                remove_resource_from_collection($ref,0-$userref);
                                }
                            else
                                {
                                debug("edit: autosave errors saving resource: " . $ref);
                                $autosave_errors = true;                   
                                }
                            }
                        if($autosave_errors)
                            {
                            // Redirect to upload_review_mode in order to finish editing remaining resources that errored, include submit to generate required error message
                            ?>
                            <script>CentralSpaceLoad('<?php echo generateURL($baseurl_short . "pages/edit.php",$urlparams, array("upload_review_mode"=>"true","lastedited"=>$lastedited,"showextraerrors"=>json_encode($auto_errors))); ?>',true);</script>
                            <?php
                            exit();
                            }
                        else
                            {
                            // All saved, redirect to recent user uploads to the set archive state
                            if($external_upload)
                                {
                                debug("external upload - finished reviewing resources");
                                // Delete the temporary upload_collection
                                delete_collection($collection);
                                // Send notification to creator of upload 
                                external_upload_notify($external_upload, $k, $collection);
                                $url = generateURL($baseurl . "/pages/done.php",array("text" => "upload_share_complete", "k"=> $k,"collection"=>$external_upload));
                                }
                            else
                                {
                                $redirectparams = array(
                                    "search"=>"!contributions" . $userref,
                                    "order_by"=>"resourceid",
                                    "sort"=>"DESC",
                                    "archive"=>$setarchivestate,
                                    "refreshcollectionframe"=>"true",
                                    "resetlockedfields"=>"true",
                                    "collection_add"=>$collection_add
                                    );
                                if ($setarchivestate == -2 && $pending_submission_prompt_review && checkperm("e-1"))
                                    {
                                    $redirectparams["promptsubmit"] = 'true';
                                    }
                                
                                $url = generateURL($baseurl . "/pages/search.php",$redirectparams);
                                }
                            ?>
                            <script>CentralSpaceLoad('<?php echo $url; ?>',true);</script>
                            <?php
                            exit();
                            }
                        }
                    elseif (!hook('uploadreviewabortnext'))
                        {
                        // Redirect to next resource
                        ?>
                        <script>CentralSpaceLoad('<?php echo generateURL($baseurl_short . "pages/edit.php",$urlparams, array("upload_review_mode"=>"true","lastedited"=>$ref)); ?>',true);</script>
                        <?php
                        exit();
                        }
                  }
                if (!hook('redirectaftersave'))
                  {
                  $urlparams += ["modal" => "true"];
                  redirect(generateURL($baseurl_short . "pages/view.php",$urlparams, array("refreshcollectionframe"=>"true")));
                  }
                }
            else
                {
                // Upload template
                if (getval("save","")!="")
                    {
                    # Save button pressed? Move to next step.
                    if ($single) // Test if single upload (archived or not).
                        {
                        // If noupload is set - create resource without uploading stage
                        if (getval("noupload","") != "")
                            {
                            $ref=copy_resource(0-$userref,$resource_type,$lang["createdfromteamcentre"]);
                            $urlparams["ref"] = $ref;
                            $hidden_collection = false;
                            // Create new collection if necessary
                            if($collection_add=="new") 
                                {
                                if($uploadparams["entercolname"] == "")
                                    {
                                    $uploadparams["entercolname"] = "Upload " . offset_user_local_timezone(date('YmdHis'), 'YmdHis');
                                    $hidden_collection = true;
                                    }
                                $collection_add = create_collection($userref,$uploadparams["entercolname"]);
                                }
                            if(is_numeric($collection_add))
                                {
                                add_resource_to_collection($ref, $collection_add,false,"",$resource_type);
                                set_user_collection($userref, $collection_add);
                                if ($hidden_collection)
                                    {
                                    show_hide_collection($collection_add, false, $userref);
                                    }
                                }
                            redirect(generateURL($baseurl_short . "pages/view.php",$urlparams, array("refreshcollectionframe"=>"true")));
                            exit();
                            }
                            if (!hook('redirectaftersavetemplate')) {redirect(generateURL($baseurl_short . "pages/upload_batch.php",array_merge($urlparams,$uploadparams)) . hook("addtouploadurl"));}
                        }
                    else
                        {
                        // Default
                        if (!hook('redirectaftersavetemplate')) {redirect(generateURL($baseurl_short . "pages/upload_batch.php",array_merge($urlparams,$uploadparams)) . hook("addtouploadurl"));}
                        }
                    }
                }
            }
        elseif (getval("save","")!="")
            {  
            $show_error=true;
            }
        # If auto-saving, no need to continue as it will only add to bandwidth usage to send the whole edit page back to the client. Send a simple 'SAVED' message instead.
        if (getval("autosave","") != "" && enforcePostRequest($ajax))
            {
            $return=array();
            if(!is_array($save_errors))
                {
                $return["result"] = "SAVED";
                if(isset($new_checksums))
                    {
                    $return["checksums"] = array();
                    foreach($new_checksums as $fieldref=>$checksum)
                        {
                        $return["checksums"][$fieldref] = $checksum;
                        }
                    }
                }
            else
                {
                $return["result"] = "ERROR";
                $return["errors"] = $save_errors;
                }
            echo json_encode($return);
            exit();
            }
            
        }
    else    
        {
		// Save multiple resources
        // Check if any of the resources have been edited since between the form being loaded and submitted				
        $form_lastedit = getval("last_resource_edit",date("Y-m-d H:i:s"));
        if($last_resource_edit !== false && ($form_lastedit < $last_resource_edit["time"] && getval("ignoreconflict","") == ""))
            {
            $cfmsg = htmlspecialchars(str_replace("%%USERNAME%%", $last_resource_edit["user"] , $lang["save-conflict-multiple"]));
            $cfmsg .= "<br /><br /><a href='" .$baseurl_short . "?r=" . $last_resource_edit["ref"] . "' target='_blank' onClick='return ModalLoad(this);'>" . htmlspecialchars($lang["action-view"]) . "</a>";
            ?>
            <script>
            jQuery("#modal_dialog").html("<?php echo $cfmsg; ?>");
            jQuery("#modal_dialog").dialog({
                title:'<?php echo $lang["save-conflict-title"] ?>',
                modal: true,
                width: 400,
                resizable: false,
                buttons: {
                    "<?php echo $lang['save'] ?>": function()
                        {
                        jQuery('#ignoreconflict').val("true");
                        CentralSpacePost(document.getElementById('mainform'),true);
                        jQuery(this).dialog('close');
                        },
                    "cancel" : function() {
                        jQuery(this).dialog('close');
                        }
                    }
                });
            </script>
            <?php
            http_response_code(409);
            exit();
            }
        else
            {
            enforcePostRequest($ajax);

            if($editsearch)
                {
                $editsearch = array();
                $editsearch["search"]   = $search;
                $editsearch["restypes"] = $restypes;
                $editsearch["archive"]  = $archive;
                $save_errors=save_resource_data_multi(0,$editsearch,$_POST);

                // When editing a search for the COLLECTION_TYPE_SELECTION we want to close the modal and reload the page
                if(!is_array($save_errors) && $edit_selection_collection_resources)
                    {
                    ?>
                    <script>
                    // Create a temp form to prevent clear_selection_collection being a query string param and use CentralSpacePost
                    // to reload the search underneath batch edit modal. The search must reload with ajax on to ensure header not loaded again.
                    var temp_form = document.createElement("form");
                    temp_form.setAttribute("method", "post");
                    temp_form.setAttribute("action", window.location.href);

                    // Instruct search page not to clear the selection collection
                    var i = document.createElement("input");
                    i.setAttribute("type", "hidden");
                    i.setAttribute("name", "clear_selection_collection");
                    i.setAttribute("value", "no");
                    temp_form.appendChild(i);
                    // Instruct search page not to load header
                    var ajx = document.createElement("input");
                    ajx.setAttribute("type", "hidden");
                    ajx.setAttribute("name", "ajax");
                    ajx.setAttribute("value", "true");
                    temp_form.appendChild(ajx);

                    <?php
                    if($CSRF_enabled)
                        {
                        ?>
                        var csrf = document.createElement("input");
                        csrf.setAttribute("type", "hidden");
                        csrf.setAttribute("name", "<?php echo $CSRF_token_identifier; ?>");
                        csrf.setAttribute("value", "<?php echo generateCSRFToken($usersession, "no_clear_selection_collection"); ?>");
                        temp_form.appendChild(csrf);
                        <?php
                        }
                        ?>

                    CentralSpacePost(temp_form, true, false, false);
                    </script>
                    <?php
                    exit();
                    }
                else if(!is_array($save_errors) && !hook("redirectaftermultisave"))
                    {
                    redirect(generateURL($baseurl_short . "pages/search.php",$urlparams));
                    }
                }
            else
                {
                $save_errors=save_resource_data_multi($collection, [],$_POST);
                if(!is_array($save_errors) && !hook("redirectaftermultisave"))
                    {
                    redirect(generateURL($baseurl_short . "pages/search.php",$urlparams,array("refreshcollectionframe"=>"true","search"=>"!collection" . $collection)));
                    }
                }
                
            }
		$show_error=true;
		}
    }
    
if (getval("tweak","")!="" && !$resource_file_readonly && enforcePostRequest($ajax))
   {
   $tweak=getval("tweak","");
   switch($tweak)
      {
      case "rotateclock":
         tweak_preview_images($ref, 270, 0, $resource["preview_extension"], -1, $resource['file_extension']);
         break;
      case "rotateanti":
         tweak_preview_images($ref, 90, 0, $resource["preview_extension"], -1, $resource['file_extension']);
         break;
      case "gammaplus":
         tweak_preview_images($ref, 0, 1.3, $resource["preview_extension"]);
         break;
      case "gammaminus":
         tweak_preview_images($ref, 0, 0.7, $resource["preview_extension"]);
         break;
      case "restore":
		delete_previews($resource);
        ps_query("update resource set has_image=0, preview_attempts=0 WHERE ref= ?", ['i', $ref]);
        if ($enable_thumbnail_creation_on_upload && !(isset($preview_generate_max_file_size) && $resource["file_size"] > filesize2bytes($preview_generate_max_file_size.'MB')) || 
        (isset($preview_generate_max_file_size) && $resource["file_size"] < filesize2bytes($preview_generate_max_file_size.'MB')))   
            {
            hook('edit_previews_recreate_extra', '', array($ref)); 
            create_previews($ref,false,$resource["file_extension"],false,false,-1,true);
            refresh_collection_frame();
            }
            else if((!$enable_thumbnail_creation_on_upload || (isset($preview_generate_max_file_size) && $resource["file_size"] > filesize2bytes($preview_generate_max_file_size.'MB'))) && $offline_job_queue)
            {
            $create_previews_job_data = array(
                'resource' => $ref,
                'thumbonly' => false,
                'extension' => $resource["file_extension"],
                'previewonly' => false,
                'previewbased' => false,
                'alternative' => -1,
                'ignoremaxsize' => true,
            );
            $create_previews_job_success_text = str_replace('%RESOURCE', $ref, $lang['jq_create_previews_success_text']);
            $create_previews_job_failure_text = str_replace('%RESOURCE', $ref, $lang['jq_create_previews_failure_text']);

            job_queue_add('create_previews', $create_previews_job_data, '', '', $create_previews_job_success_text, $create_previews_job_failure_text);
            $onload_message["text"] = $lang["recreatepreviews_pending"];
            }
        else
            {
            ps_query("update resource set preview_attempts=0, has_image=0 where ref= ?", ['i', $ref]);
            $onload_message["text"] = $lang["recreatepreviews_pending"];
            }
        break;
      }
   hook("moretweakingaction", "", array($tweak, $ref, $resource));
   # Reload resource data.
   $resource=get_resource_data($ref,false);
   }

# If requested, refresh the collection frame (for redirects from saves)
if (getval("refreshcollectionframe","")!="")
    {
    refresh_collection_frame();
    }


// Manually set any errors that need to be shown e.g. after saving with locked values
$showextraerrors = getval("showextraerrors","");
if ($showextraerrors != "")
    {
    $save_errors=json_decode($showextraerrors,true);
    if(is_array($save_errors))
        {
        $show_error = true;
        }
    else
        {
        $save_errors = [];
        }
    }

include "../include/header.php";
?>
<script>
<?php
if ($lockable_fields)
    {
    echo "lockedfields = " . (count($locked_fields) > 0 ? json_encode($locked_fields) : "new Array()") . ";";
    }?>

jQuery(document).ready(function()
{
   <?php
   if($ctrls_to_save)
     {?>
        jQuery(document).bind('keydown',function (e)
        {
          if (!(e.which == 115 && (e.ctrlKey || e.metaKey)) && !(e.which == 83 && (e.ctrlKey || e.metaKey)) && !(e.which == 19) )
          {
            return true;
         }
         else
         {
            event.preventDefault();
            if(jQuery('#mainform'))
            {
               jQuery('.AutoSaveStatus').html('<?php echo urlencode($lang["saving"]) ?>');
               jQuery('.AutoSaveStatus').show();
               jQuery.post(jQuery('#mainform').attr('action') + '&autosave=true',jQuery('#mainform').serialize(),

                  function(data)
                  {
				  saveresult=JSON.parse(data)
				  if (saveresult['result']=="SAVED")
					{
                    jQuery('.AutoSaveStatus').html('<?php echo urlencode($lang["saved"]) ?>');
                    jQuery('.AutoSaveStatus').fadeOut('slow');
					if (typeof(saveresult['checksums']) !== undefined)
						{
						for (var i in saveresult['checksums']) 
							{
                            if (jQuery.isNumeric(i))
                              {
                              jQuery("#field_" + i + "_checksum").val(saveresult['checksums'][i]);
                              }
                            else
                              {
                              jQuery('#' + i + '_checksum').val(saveresult['checksums'][i]);
                              }
							}
						}
					}
				  else
					{
					saveerrors = '<?php echo urlencode($lang["error_generic"]); ?>';
					if (typeof(saveresult['errors']) !== undefined)
						{
						saveerrors = "";
						for (var i in saveresult['errors']) 
							{
							saveerrors += saveresult['errors'][i] + "<br />";
							}
						}
					jQuery('.AutoSaveStatus').html('<?php echo urlencode($lang["save-error"]) ?>');
					jQuery('.AutoSaveStatus').fadeOut('slow');
					styledalert('<?php echo urlencode($lang["error"]) ?>',saveerrors,450);
					}
               });
            }
            return false;
         }
      });
<?php
}?>

});
<?php hook("editadditionaljs");

# Function to automatically save the form on field changes, if configured.
 if ($edit_autosave)
    { ?>
    preventautosave=false;
    
    
    // Disable autosave on enter keypress as form will be submitted by this keypress anyway which can result in duplicate data
    
    jQuery("#CentralSpace").on("keydown", ":input:not(textarea):input:not(text)", function(e) 
        {
        if (e.which == 13) 
            {
            preventautosave = true;
            e.preventDefault();
            }
            else
            {
            preventautosave = false;    
            }
        });
        

    function AutoSave(field, stop_recurrence)
        {
        stop_recurrence = typeof stop_recurrence === 'undefined' ? false : stop_recurrence;

        // If user has edited a field (autosave on) but then clicks straight on Save, this will prevent double save which can
        // lead to edit conflicts.
        if(!preventautosave && !stop_recurrence)
            {
            setTimeout(function()
                {
                AutoSave(field, true);
                }, 150);

            return false;
            }

        if(preventautosave || typeof jQuery('#mainform').attr('action') == 'undefined')
            {
            return false;
            }

        jQuery('#AutoSaveStatus' + field).html('<?php echo escape_quoted_data($lang["saving"]); ?>');
        jQuery('#AutoSaveStatus' + field).show();
        
        formdata = jQuery('#mainform').serialize();
        // Clear checksum to prevent edit conflicts for this field if they perform multiple subsequent edits
        jQuery("#field_" + field + "_checksum").val('');
        jQuery.post(jQuery('#mainform').attr('action') + '&autosave=true&autosave_field=' + field,formdata,
            function(data)
                {
                saveresult=JSON.parse(data);
                if (saveresult['result']=="SAVED")
                    {
                    jQuery('#AutoSaveStatus' + field).html('<?php echo escape_quoted_data($lang["saved"]); ?>');
                    jQuery('#AutoSaveStatus' + field).fadeOut('slow');
                    if (typeof(saveresult['checksums']) !== undefined)
                        {
                        for (var i in saveresult['checksums']) 
                            {
                            if (jQuery.isNumeric(i))
                                 {
                                 jQuery("#field_" + i + "_checksum").val(saveresult['checksums'][i]);
                                 }
                               else
                                 {
                                 jQuery('#' + i + '_checksum').val(saveresult['checksums'][i]);
                                 }
                            }
                        }					
                    }
                else
                    {   
                    saveerrors = '<?php echo urlencode($lang["error_generic"]); ?>';
                    if (typeof(saveresult['errors']) !== undefined)
                        {
                        saveerrors = "";
                        for (var i in saveresult['errors']) 
                            {
                            saveerrors += saveresult['errors'][i] + "<br />";
                            }
                        }
                    jQuery('#AutoSaveStatus' + field).html('<?php echo $lang["save-error"] ?>');
                    jQuery('#AutoSaveStatus' + field).fadeOut('slow');
                    styledalert('<?php echo $lang["error"] ?>',saveerrors);
                    }
                })
                .fail(function(response) {
                    jQuery('#AutoSaveStatus' + field).html('<?php echo $lang["save-error"] ?>');
                    jQuery('#AutoSaveStatus' + field).fadeOut('slow');
                    styledalert('<?php echo $lang["error"] ?>',response.responseText);
                    });
	}
<?php } ?>
</script>

<?php
if($ref < 0)
    {
    // Include upload_params in form action url
    if($noupload)
        {
        $uploadparams["noupload"] = "true";
        }
    else
        {
        $uploadparams["forcesingle"] = "";
        $uploadparams["noupload"] = "";
        }
    if ($create_record_only)
        {
        $uploadparams["recordonly"] = "true";
        }
    $form_action = generateURL($baseurl_short . "pages/edit.php",array_merge($urlparams,$uploadparams));
    }
else
    {
    $form_action = generateURL($baseurl_short . "pages/edit.php", $urlparams);
    }
?>

<form method="post"
      action="<?php echo $form_action; ?>"
      id="mainform"
      onsubmit="
        preventautosave = true;
        return <?php echo ($modal ? 'Modal' : 'CentralSpace'); ?>Post(this, true);
      ">
    <?php generateFormToken("mainform"); ?>
    <input type="hidden" name="upload_review_mode" value="<?php echo ($upload_review_mode?"true":"")?>" />
    <div class="BasicsBox BasicsBoxEdit">
    <div class="BasicsBoxLeft">
        <input type="hidden" name="submitted" value="true">
    <?php 
    if ($multiple) 
        {?>
        <input type="hidden" name="last_resource_edit" value="<?php echo $last_resource_edit ? $last_resource_edit["time"] :  0 ; ?>">
        <input type="hidden" id="ignoreconflict" name="ignoreconflict" value="">

        <h1 id="editmultipleresources"><?php echo $lang["editmultipleresources"]?></h1>
        <p style="padding-bottom:20px;"><?php $qty = count($items);
        echo ($qty==1 ? $lang["resources_selected-1"] : str_replace("%number", $qty, $lang["resources_selected-2"])) . ". ";
        # The script doesn't allow editing of empty collections, no need to handle that case here.
        echo text("multiple");
        ?> </p> <?php
        } 
   elseif ($ref>0)
      {
      if (!hook('replacebacklink') && !$upload_review_mode) 
        {
        if (getval("context",false) == 'Modal'){$previous_page_modal = true;}
        else {$previous_page_modal = false;}
        if(!$modal)
            {?>
            <p><a href="<?php echo generateURL($baseurl_short . "pages/view.php",$urlparams); ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a></p>
            <?php
            }
        elseif ($previous_page_modal)
            {
            ?>
            <p><a href="<?php echo generateURL($baseurl_short . "pages/view.php",$urlparams); ?>" onClick="return ModalLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a></p>
            <?php
            }
        }
        if (!hook("replaceeditheader")) 
            { ?>
            <div class="RecordHeader">
            <?php
            # Draw nav
            if (!$multiple  && $ref>0  && !hook("dontshoweditnav")) { EditNav(); }
            
            if (!$upload_review_mode) { ?>
            <h1 id="editresource"><?php echo $lang["action-editmetadata"];render_help_link("user/editing-resources");?></h1>
            <?php } else { ?>
            <h1 id="editresource"><?php echo $lang["refinemetadata"];render_help_link("user/editing-resources");?></h1>
            <?php } ?>
            
            </div><!-- end of RecordHeader -->
            <?php
            }
            
        if (!$upload_review_mode)
            { ?>
            <div class="Question" id="resource_ref_div" style="border-top:none;">
            <label><?php echo $lang["resourceid"]?></label>
            <div class="Fixed"><?php echo urlencode($ref) ?></div>
            <div class="clearerleft"> </div>
            </div>
            <?php
            }
        
    hook("beforeimagecorrection");

    if (!checkperm("F*") && !$resource_file_readonly && !$upload_review_mode)
        { ?>
        <div class="Question" id="question_imagecorrection">
            <label><?php echo $lang["imagecorrection"]?><br/><?php echo $lang["previewthumbonly"]?></label>
            <select class="stdwidth" name="tweak" id="tweak" onchange="add_hidden_modal_input('mainform', <?php echo ($modal ? "true" : "false"); ?>); <?php echo ($modal?"Modal":"CentralSpace") ?>Post(document.getElementById('mainform'),true);">
            <option value=""><?php echo $lang["select"]?></option>
            <?php if ($resource["has_image"]==1)
                {
                # On some PHP installations, the imagerotate() function is wrong and images are turned incorrectly.
                # A local configuration setting allows this to be rectified
                if (!$image_rotate_reverse_options)
                    {
                    ?>
                    <option value="rotateclock"><?php echo $lang["rotateclockwise"]?></option>
                    <option value="rotateanti"><?php echo $lang["rotateanticlockwise"]?></option>
                    <?php
                    }
                else
                    {
                    ?>
                    <option value="rotateanti"><?php echo $lang["rotateclockwise"]?></option>
                    <option value="rotateclock"><?php echo $lang["rotateanticlockwise"]?></option>
                    <?php
                    }
                if ($tweak_allow_gamma)
                    {?>
                    <option value="gammaplus"><?php echo $lang["increasegamma"]?></option>
                    <option value="gammaminus"><?php echo $lang["decreasegamma"]?></option>
                    <?php
                    }?>
                <option value="restore"><?php echo $lang["recreatepreviews"]?></option>
                <?php
                }
            else
                {?>
                <option value="restore"><?php echo $lang["retrypreviews"]?></option>
                <?php
                } 
            hook("moretweakingopt"); ?>
            </select>
            <div class="clearerleft"> </div>
        </div><?php
        } 
    }
else
    { # Upload template: (writes to resource with ID [negative user ref])
    if (!hook("replaceeditheader"))
        {
        # Define the title h1:
        if ($single)
            {
            if (getval("status","")=="2")
                {
                $titleh1 = $lang["newarchiveresource"]; # Add Single Archived Resource
                }
            else
                {
                $titleh1 = $lang["addresource"]; # Add Single Resource
                }
            }
        else
            {
            // Default - batch upload
            $titleh1 = $lang["addresourcebatchbrowser"];
            }?>        
        <h1><?php echo $titleh1 ?></h1>
        <p><?php echo $lang["intro-batch_edit"];render_help_link("user/uploading");?></p>
        <?php
        }
    }

hook("editbefresmetadata"); ?>
<?php if (!hook("replaceedittype"))
    {
    if(!$multiple)
        {
        ?>
        <div class="Question <?php if($lockable_fields && in_array("resource_type",$locked_fields)){echo "lockedQuestion ";}if(isset($save_errors) && is_array($save_errors) && array_key_exists('resource_type',$save_errors)) { echo 'FieldSaveError'; } ?>" id="question_resourcetype">
            <label for="resourcetype"><?php echo $lang["resourcetype"] . (($ref < 0 && $resource_type_force_selection) ? " <sup>*</sup>" : "" );
            if ($lockable_fields)
                {
                renderLockButton('resource_type', $locked_fields);
                }?>
        </label>
        <?php if ($check_edit_checksums)
            {
            $resource=get_resource_data($ref);
            ?>
            <input id='resource_type_checksum' name='resource_type_checksum' type='hidden' value='<?php echo $resource['resource_type']; ?>'>
            <?php
            }
            ?>

            <select name="resource_type" id="resourcetype" class="stdwidth" 
                    onChange="<?php if ($ref>0) { ?>if (confirm('<?php echo $lang["editresourcetypewarning"]; ?>')){ add_hidden_modal_input('mainform', <?php echo ($modal ? "true" : "false"); ?>);<?php } ?><?php echo ($modal?"Modal":"CentralSpace") ?>Post(document.getElementById('mainform'),true);<?php if ($ref>0) { ?>}else {return}<?php } ?>">
            <?php
            $types                = get_resource_types();
            $shown_resource_types = array();
            if($ref < 0 && $resource_type_force_selection && $resource_type=="") // $resource_type is obtained from getval
            {
            echo "<option value='' selected>" . $lang["select"] . "</option>";
            }
            
            for($n = 0; $n < count($types); $n++)
                {
                if(trim((string) $types[$n]['allowed_extensions']) != "")
                    {
                    $allowed_extensions = explode(",",strtolower($types[$n]['allowed_extensions'])); // As MIME types
                    }
                else
                    {
                    array();
                    }
                // skip showing a resource type that we do not to have permission to change to 
                // (unless it is currently set to that). Applies to upload only
                if((0 > $ref || $upload_review_mode)
                    && 
                        (checkperm("XU{$types[$n]['ref']}") || in_array($types[$n]['ref'], $hide_resource_types))
                        ||
                        (checkperm("XE") && !checkperm("XE-" . $types[$n]['ref']))
                        ||
                        (trim((string) $resource["file_extension"]) != ""
                            && isset($allowed_extensions)
                            && count($allowed_extensions) > 0 
                            && !in_array(allowed_type_mime(strtolower($resource["file_extension"])), $allowed_extensions))
                    &&
                        $resource['resource_type'] != $types[$n]['ref']
                    )
                    {
                    continue;
                    }

                $shown_resource_types[] = $types[$n]['ref'];
                ?>
                <option value="<?php echo $types[$n]['ref']; ?>"
                    <?php
                    if(($resource['resource_type'] == $types[$n]['ref'] && getval("resource_type","") == "") || getval("resource_type","") == $types[$n]['ref'])
                        {
                        $selected_type = $types[$n]['ref'];
                        ?>selected<?php
                        }
                        ?>
                ><?php echo htmlspecialchars($types[$n]["name"])?></option>
                <?php
                }

            // make sure the user template resource (edit template) has the correct resource type when they upload so they can see the correct specific fields
            if('' == getval('submitted', ''))
                {
                if(!isset($selected_type))
                    {
                    // Display error if no resource type can be found - resource specific metadata cannot be loaded.    
                    if (empty($shown_resource_types))
                        {
                        error_alert($lang['resource_type_not_found'], false);
                        exit();
                        }
                    $selected_type = $shown_resource_types[0];
                    }

                $resource['resource_type'] = $selected_type;
                }
                ?>
            </select>
            <div class="clearerleft"></div>
        </div>
        <?php
        }
    else
        {
        # Multiple method of changing resource type.
        ?>
        <h2 <?php echo ($collapsible_sections)?"class=\"CollapsibleSectionHead\"":""?>><?php echo $lang["resourcetype"] ?></h2>
        <div <?php echo ($collapsible_sections)?"class=\"CollapsibleSection\"":""?> id="ResourceTypeSection<?php if ($ref==-1) echo "Upload"; ?>">
        <div class="Question">
            <input name="editresourcetype" id="editresourcetype" type="checkbox" value="yes" onClick="var q=document.getElementById('editresourcetype_question');if (this.checked) {q.style.display='block';alert('<?php echo $lang["editallresourcetypewarning"] ?>');} else {q.style.display='none';}">
            &nbsp;
            <label for="editresourcetype"><?php echo $lang["resourcetype"] ?></label>
        </div>
        <div class="Question" style="display:none;" id="editresourcetype_question">
            <label for="resourcetype"><?php echo $lang["resourcetype"]?></label>
            <select name="resource_type" id="resourcetype" class="stdwidth">
                <?php
                $types = get_resource_types();
                for($n = 0; $n < count($types); $n++)
                    {
                    if(in_array($types[$n]['ref'], $hide_resource_types))
                        {
                        continue;
                        }
                    ?>
                    <option value="<?php echo $types[$n]["ref"]?>" <?php if ($resource["resource_type"]==$types[$n]["ref"]) {?>selected<?php } ?>><?php echo htmlspecialchars($types[$n]["name"])?></option>
                    <?php
                    }
                ?>
            </select>
            <div class="clearerleft"></div>
        </div>
        <?php
        }
    } # end hook("replaceedittype")

# For new users check that they have access to the default resource type, setting from the available types if they don't to ensure metadata fields load correctly.
if (!empty($shown_resource_types) && !in_array($uploadparams["resource_type"],$shown_resource_types) && isset($selected_type))
    {
    $resource_type = $selected_type;
    update_resource_type($ref,intval($resource_type));
    $resource["resource_type"] = $resource_type;
    $uploadparams["resource_type"] = $resource_type;
    }

$lastrt=-1;

if(isset($metadata_template_resource_type) && isset($metadata_template_title_field) && $metadata_template_title_field !== false && !$multiple && ($ref < 0 || $upload_review_mode))
    {
    // Show metadata templates here
    $templates = get_metadata_templates();

    $first_option_conditions = ($metadata_template_default_option == 0 && $metadatatemplate == 0);
    ?>
    <div class="Question <?php if($lockable_fields && in_array("metadatatemplate",$locked_fields)){echo "lockedQuestion ";} if(isset($save_errors) && is_array($save_errors) && array_key_exists('metadatatemplate',$save_errors)) { echo 'FieldSaveError'; } ?>" id="question_metadatatemplate">
        <label for="metadatatemplate"><?php echo $lang['usemetadatatemplate'];
        if (!$is_template && $metadata_template_mandatory)
            {
            echo "<sup>*</sup>";
            }
        if ($lockable_fields)
            {
            renderLockButton('metadatatemplate', $locked_fields);
            }?>
        </label>
        <select name="metadatatemplate" class="stdwidth" onchange="MetadataTemplateOptionChanged(jQuery(this).val());">
            <option value=""<?php echo $first_option_conditions ? ' selected' : ''; ?>><?php echo $first_option_conditions ? $lang['select'] : $lang['undometadatatemplate']; ?></option>
        <?php
        foreach($templates as $template)
            {
            $template_selected = '';

            if(
                ($metadatatemplate == 0 && $metadata_template_default_option == $template['ref'])
                || ($metadatatemplate > 0 && $template['ref'] == $metadatatemplate)
            )
                {
                $template_selected = ' selected';
                }
                ?>
            <option value="<?php echo $template["ref"] ?>" <?php echo $template_selected; ?>><?php echo htmlspecialchars($template["field{$metadata_template_title_field}"]); ?></option>
            <?php   
            }
            ?>
        </select>
        <script>
        function MetadataTemplateOptionChanged(value)
            {
            $confirm_message = "<?php echo $lang['usemetadatatemplatesure']?>";
            $resetform = false;
            if(value == '')
                {
                $confirm_message = "<?php echo $lang['removemetadatatemplatesure'] ?>";
                $resetform = true;
                }

            if(confirm($confirm_message))
                {
                if($resetform)
                    {
                    // Undo template selection <=> clear out the form
                    jQuery('#mainform').append(
                    jQuery('<input type="hidden">').attr(
                        {
                        name: 'resetform',
                        value: 'true'
                        })
                    );
                    }

                return CentralSpacePost(document.getElementById('mainform'), true);
                }
            }
        </script>
        <div class="clearerleft"></div>
    </div><!-- end of question_metadatatemplate --> 
    <?php
    }

if($embedded_data_user_select && $ref<0 && !$multiple)
 {?>
<div class="Question" id="question_exif">
 <label for="exif_option"><?php echo $lang["embedded_metadata"]?></label>
 <table id="" cellpadding="3" cellspacing="3" style="display: block;">                    
   <tbody>
     <tr>        
       <td width="10" valign="middle">
         <input type="radio" id="exif_extract" name="exif_option" value="extract" onClick="jQuery('.ExifOptions').hide();" <?php if($metadata_read_default) echo "checked" ?>>
      </td>
      <td align="left" valign="middle">
         <label class="customFieldLabel" for="exif_extract"><?php echo $lang["embedded_metadata_extract_option"] ?></label>
      </td>


      <td width="10" valign="middle">
         <input type="radio" id="no_exif" name="exif_option" value="yes" onClick="jQuery('.ExifOptions').hide();" <?php if(!$metadata_read_default) echo "checked" ?>>
      </td>
      <td align="left" valign="middle">
         <label class="customFieldLabel" for="no_exif"><?php echo $lang["embedded_metadata_donot_extract_option"] ?></label>
      </td>


      <td width="10" valign="middle">
         <input type="radio" id="exif_append" name="exif_option" value="append" onClick="jQuery('.ExifOptions').hide();">
      </td>
      <td align="left" valign="middle">
         <label class="customFieldLabel" for="exif_append"><?php echo $lang["embedded_metadata_append_option"] ?></label>
      </td>


      <td width="10" valign="middle">
         <input type="radio" id="exif_prepend" name="exif_option" value="prepend" onClick="jQuery('.ExifOptions').hide();">
      </td>
      <td align="left" valign="middle">
         <label class="customFieldLabel" for="exif_prepend"><?php echo $lang["embedded_metadata_prepend_option"] ?></label>
      </td>

      <td width="10" valign="middle">
         <input type="radio" id="exif_custom" name="exif_option" value="custom" onClick="jQuery('.ExifOptions').show();">
      </td>
      <td align="left" valign="middle">
         <label class="customFieldLabel" for="exif_custom"><?php echo $lang["embedded_metadata_custom_option"] ?></label>
      </td>

   </tr>
</tbody>
</table>



<div class="clearerleft"> </div>
</div>
<?php   
}

# Resource aliasing.
# 'Copy from' or 'Metadata template' been supplied? Load data from this resource instead.
$originalref=$use;
$original_fields=array();
$original_nodes=array();

if (getval("copyfrom","")!="")
  {
  # Copy from function
  $copyfrom=getval("copyfrom","");
  $copyfrom_access=get_resource_access($copyfrom);

  # Check access level
  if ($copyfrom_access!=2) # Do not allow confidential resources (or at least, confidential to that user) to be copied from
    {
    $use=$copyfrom;
    $original_fields=get_resource_field_data($ref,$multiple,true,NULL,"",$tabs_on_edit);
    $original_nodes = get_resource_nodes($ref);
    }
  }

if(($ref < 0 || $upload_review_mode) && isset($metadata_template_resource_type)  && !$multiple && $metadatatemplate != 0)
    {
    $use             = $metadatatemplate;
    $original_fields = get_resource_field_data($ref, $multiple, true, NULL, '', $tabs_on_edit);
    $original_nodes  = get_resource_nodes($ref);
    copyAllDataToResource($use, $ref);
    }

# Load resource data

if ($ref < 0 && !$upload_review_mode)
    {
    set_resource_defaults($ref);  # Get resource defaults for edit then upload mode.
    }

$fields=get_resource_field_data($use,$multiple,!hook("customgetresourceperms"),$originalref,"",$tabs_on_edit);

# Only include fields whose resource type is global or is present in the resource(s) being edited
if ($multiple) 
    {
    $fields_to_include = array();
    foreach ($fields as $field_candidate) 
        {
        if( ($field_candidate["resource_type"] == 0) || (in_array($field_candidate["resource_type"],$items_resource_types) ) ) 
            {
            $fields_to_include[]=$field_candidate;
            }
        }    
    $fields=$fields_to_include;
    }

$all_selected_nodes = get_resource_nodes($use);

if($upload_here)
    {
    $all_selected_nodes = get_upload_here_selected_nodes($search, $all_selected_nodes);
    }

if ($lockable_fields && count($locked_fields) > 0 && $lastedited > 0)
    {
    // Update $fields and all_selected_nodes with details of the last resource edited for locked fields
    // $fields and $all_selected_nodes are passed by reference and so changed by this
    copy_locked_fields($ref,$fields,$all_selected_nodes,$locked_fields,$lastedited);
    }

# if this is a metadata template, set the metadata template title field at the top
if (($ref < 0 || $upload_review_mode) && isset($metadata_template_resource_type)&&(isset($metadata_template_title_field)) && $resource["resource_type"]==$metadata_template_resource_type){
    # recreate fields array, first with metadata template field
  $x=0;
  $fields_count = count($fields);
  for ($n=0;$n<$fields_count;$n++){
    if ($fields[$n]["resource_type"]==$metadata_template_resource_type){
      $newfields[$x]=$fields[$n];
      ++$x;
   }
}
    # then add the others
$fields_count = count($fields);
for ($n=0;$n<$fields_count;$n++){
 if ($fields[$n]["resource_type"]!=$metadata_template_resource_type){
   $newfields[$x]=$fields[$n];
   ++$x;
}
}
$fields=$newfields;
}

$required_fields_exempt=array(); # new array to contain required fields that have not met the display condition

# Work out if any fields are displayed, and if so, enable copy from feature (+others)
$display_any_fields=false;
$tabname="";
$tabcount=0;
$fields_count = count($fields);
for ($n=0;$n<$fields_count;$n++)
  {
   if (is_field_displayed($fields[$n]))
     {
     $display_any_fields=true;
     break;
    }
}
 
# "copy data from" feature
if ($display_any_fields && $enable_copy_data_from && !$upload_review_mode)
    { ?>
 <div class="Question" id="question_copyfrom">
    <label for="copyfrom"><?php echo $lang["batchcopyfrom"]?></label>
    <input class="stdwidth" type="text" name="copyfrom" id="copyfrom" value="" style="width:80px;">
    <input type= "hidden" name="modal" id="modalcopyfrom" value="<?php echo ($modal?"true":"false") ?>">
    <input type="submit" id="copyfromsubmit" name="copyfromsubmit" value="<?php echo $lang["copy"]?>" onClick="return CentralSpacePost(document.getElementById('mainform'),true,<?php echo $modal ?>);">
    <input type="submit" name="save" value="<?php echo $lang['save']; ?>">
    <div class="clearerleft"> </div>
 </div><!-- end of question_copyfrom -->
 <?php
}
if($multiple)// this is closing a div that can be omitted via hook("replaceedittype")
	{
	?>
	</div><!-- end collapisble ResourceTypeSection -->
	<?php
	}
hook('editbeforesectionhead');

global $collapsible_sections;
 
 if ($display_any_fields)
 {
 ?>

<?php if (!$upload_review_mode) { ?>
<br />
<br />
<?php hook('addcollapsiblesection'); 
if (($edit_upload_options_at_top || $upload_review_mode) && display_upload_options()){include '../include/edit_upload_options.php';}
?>
<h2  <?php if($collapsible_sections){echo'class="CollapsibleSectionHead"';}?> id="ResourceMetadataSectionHead"><?php echo $lang["resourcemetadata"]?></h2><?php
 } 

?><div <?php if($collapsible_sections){echo'class="CollapsibleSection"';}?> id="ResourceMetadataSection<?php if ($ref<0) echo "Upload"; ?>"><?php
}

# Check code signing flag and display warning if present
if (get_sysvar("code_sign_required")=="YES")
    {
    ?><div class="Question"><div class="FormError"><?php echo $lang["code_sign_required_warning"]; ?></div></div><?php
    }


$tabModalityClass = ($modal ? " MetaTabIsModal-" : " MetaTabIsNotModal-"). (int) $ref;
$modalTrueFalse = ($modal ? "true" : "false");

if($tabs_on_edit)
    {
    // -----------------------  Tab calculation -----------------
    $system_tabs = get_tab_name_options();
    $tabs_fields_assoc = [];

    // Clean the tabs by removing the ones that would end up being empty
    foreach(array_keys($system_tabs) as $tab_ref)
        {
        foreach($fields as $field_idx => $field_data)
            {
            // Ensure tab IDs are always numbers. Fields unassigned will end up on the "Default" list (ref #1)
            $fields[$field_idx]['tab'] = $field_data['tab'] = (int) $field_data['tab'] ?: 1;

            if(!is_field_displayed($field_data))
                {
                continue;
                }

            // Check tab assignment
            if($tab_ref > 0 && $tab_ref === $field_data['tab'])
                {
                $tabs_fields_assoc[$tab_ref][$field_idx] = $field_data['ref'];
                }
            // Fields with invalid tab IDs will end up on the "Default" list (ref #1)
            else if(!isset($tabs_fields_assoc[1][$field_idx]) && !isset($system_tabs[$field_data['tab']]))
                {
                // Override the fields' tab value in order for it to be rendered on the correct tab
                $fields[$field_idx]['tab'] = 1;
                $tabs_fields_assoc[1][$field_idx] = $field_data['ref'];
                }
            }
        }
    $tab_names = array_intersect_key($system_tabs, $tabs_fields_assoc);

    // Sort fields based on the order of the tabs they belong to. Maintain the overall order of the fields.
    $fields_tab_ordered = [];
    foreach($tabs_fields_assoc as $fields_idx_ref)
        {
        $subset_fields_data = array_intersect_key($fields, $fields_idx_ref);
        $fields_tab_ordered = array_merge($fields_tab_ordered, $subset_fields_data);
        }
    $fields = $fields_tab_ordered;
    // -----------------------  END: Tab calculation -----------------

    #  -----------------------------  Draw tabs ---------------------------
    $tabcount = 0;
    $tabtophtml = '';
    foreach($tab_names as $tab_name)
        {
        if($tabcount === 0)
            {
            $tabtophtml .= '<div id="BasicsBoxTabs" class="BasicsBox"><div class="TabBar">';
            }
        $tabtophtml .= sprintf(
            '<div id="%stabswitch%s-%s" class="Tab %s">',
            $modal ? 'Modal' : '',
            $tabcount,
            $ref,
            $tabcount === 0 ? 'TabSelected' : ''
        );
        $tabtophtml .= sprintf(
            '<a href="#" onclick="SelectMetaTab(%s, %s, %s); return false;">%s</a></div>',
            $ref,
            $tabcount,
            $modalTrueFalse,
            htmlspecialchars($tab_name)
        );
        ++$tabcount;
        }

    // Tabs on edit configuration will always show at least the Default (ref #1) tab.
    if($tabcount > 0)
        {
        $StyledTabbedPanel_class = $tabcount > 0 ? ' StyledTabbedPanel': '';

        echo $tabtophtml;
        ?>
        </div><!-- end of TabBar -->
        <div id="tabbedpanelfirst" class="TabbedPanel<?php echo $tabModalityClass . $StyledTabbedPanel_class; ?>">
            <div class="clearerleft"></div>
            <div class="TabPanelInner">
        <?php
        }
    }

    #  -----------------------------  Draw fields ---------------------------
    $tabcount = 0;
    $last_tab_drawn = 0;
    foreach($fields as $n => $field)
        {
        if(!(is_field_displayed($field) && !in_array($field['resource_type'], $hide_resource_types)))
            {
            continue;
            }

        // Draw a new tab panel?
        $newtab = $tabs_on_edit && $field['tab'] !== $last_tab_drawn;  
        if($newtab)
            {
            $new_TabbedPanel_id = sprintf('%stab%s-%s', $modal ? 'Modal' : '', $tabcount, (int) $ref);
            ?>
            <div class="clearerleft"></div>
            <?php
            // Display the custom formatted data (ie customer template) $extra at the bottom of this tab panel.
            if(isset($extra)) { echo $extra; }
            ?>
                </div><!-- end of TabPanelInner -->
            </div><!-- end of TabbedPanel -->
            <div id="<?php echo $new_TabbedPanel_id; ?>" class="TabbedPanel <?php echo $tabModalityClass; ?> StyledTabbedPanel" style="display:none;">
                <div class="TabPanelInner">
            <?php
            ++$tabcount;
            $extra = '';
            $last_tab_drawn = $field['tab'];
            }

        node_field_options_override($field);
        display_field($n, $field, $newtab, $modal);
        }

    if ($tabs_on_edit && $tabcount>0)
        {
        ?>
        <div class="clearerleft"> </div>
     </div><!-- end of TabPanelInner -->
  </div><!-- end of TabbedPanel -->
</div><!-- end of Tabs BasicsBox -->
        <?php
        }


# Add required_fields_exempt so it is submitted with POST
echo " <input type=hidden name=\"exemptfields\" id=\"exemptfields\" value=\"" . implode(",",$required_fields_exempt) . "\">";   

# Work out the correct archive status.
if ($ref < 0 && !$show_status_and_access_on_upload) 
    {
    # Upload template and not displaying status. Hide the dropdown and set the default status.
    ?>
    <input type=hidden name="status" id="status" value="<?php echo htmlspecialchars($setarchivestate)?>"><?php
    }

?>
</div><!-- end of ResourceMetadataSection -->
<?php

# Status / Access / Related Resources
if (    (
        ($ref > 0 && $upload_review_mode && eval($show_status_and_access_on_upload_perm) )  # If editing a resource after upload
        ||
        ($ref < 0 && eval($show_status_and_access_on_upload_perm) ) # If editing a resource template
        ||
        ($ref > 0 && !$upload_review_mode) # If regular resource edit
        || !hook("editstatushide") 
        )
   && 
        upload_share_active() == false
   ) # If grant_edit plugin isn't overriding
{
  if(!hook("replacestatusandrelationshipsheader"))
  {
    if ($ref>0 || $show_status_and_access_on_upload===true || $show_access_on_upload===true)
    {
            if ($enable_related_resources && ($multiple || $ref>0)) # Showing relationships
            {
              ?><h2 <?php echo ($collapsible_sections)?"class=\"CollapsibleSectionHead\"":""?> id="StatusRelationshipsSectionHead"><?php echo $lang["statusandrelationships"]?></h2><div <?php echo ($collapsible_sections)?"class=\"CollapsibleSection\"":""?> id="StatusRelationshipsSection<?php if ($ref==-1) echo "Upload"; ?>"><?php
           }
           else
           {
                ?><h2 <?php echo ($collapsible_sections)?"class=\"CollapsibleSectionHead\"":""?>><?php echo $lang["status"]?></h2><div <?php echo ($collapsible_sections)?"class=\"CollapsibleSection\"":""?> id="StatusSection<?php if ($ref==-1) echo "Upload"; ?>"><?php # Not showing relationships
             }
          }

       } /* end hook replacestatusandrelationshipsheader */

       hook("statreladdtopfields");

# Status
if ($ref>0 || $show_status_and_access_on_upload===true)
   {
   if(!hook("replacestatusselector"))
      {
      if ($multiple)
         { ?>
         <div class="Question" id="editmultiple_status"><input name="editthis_status" id="editthis_status" value="yes" type="checkbox" onClick="var q=document.getElementById('question_status');if (q.style.display!='block') {q.style.display='block';} else {q.style.display='none';}">&nbsp;<label id="editthis_status_label" for="editthis<?php echo $n?>"><?php echo $lang["status"]?></label></div>
         <?php
         }

    hook("before_status_question");
    ?>
      <div class="Question <?php if($lockable_fields && in_array("archive",$locked_fields)){echo "lockedQuestion ";} if(isset($save_errors) && is_array($save_errors) && array_key_exists('status',$save_errors)) { echo 'FieldSaveError'; } ?>" id="question_status" <?php if ($multiple) {?>style="display:none;"<?php } ?>>
         <label for="status">
         <?php echo ($multiple ? "" : $lang["status"]);
         if ($lockable_fields)
            {
            renderLockButton('archive', $locked_fields);
            }?>
          </label><?php

         # Autosave display
         if ($edit_autosave || $ctrls_to_save)
            { ?>
            <div class="AutoSaveStatus" id="AutoSaveStatusStatus" style="display:none;"></div>
            <?php
            } 
		 if(!$multiple && getval("copyfrom","")=="" && $check_edit_checksums)
			{
			echo "<input id='status_checksum' name='status_checksum' type='hidden' value='" . $setarchivestate . "'>";
			}?>
         <select class="stdwidth" name="status" id="status" <?php if ($edit_autosave) {?>onChange="AutoSave('Status');"<?php } ?>><?php
         for ($n=-2;$n<=3;$n++)
            {
            if (checkperm("e" . $n) || $n==$setarchivestate) { ?><option value="<?php echo $n?>" <?php if ($setarchivestate==$n) { ?>selected<?php } ?>><?php echo $lang["status" . $n]?></option><?php }
            }
         foreach ($additional_archive_states as $additional_archive_state)
            {
            if (checkperm("e" . $additional_archive_state) || $additional_archive_state==$setarchivestate) { ?><option value="<?php echo $additional_archive_state?>" <?php if ($setarchivestate==$additional_archive_state) { ?>selected<?php } ?>><?php echo isset($lang["status" . $additional_archive_state])?$lang["status" . $additional_archive_state]:$additional_archive_state ?></option><?php }
            }?>
         </select>
         <div class="clearerleft"> </div>
      </div><?php
      } /* end hook replacestatusselector */
   }

    # Access
hook("beforeaccessselector");
if (!hook("replaceaccessselector"))
{
 if($ref<0 && $override_access_default!==false)
 {
   $resource["access"]=$override_access_default;
}

if ($ref<0 && (($show_status_and_access_on_upload== false && $show_access_on_upload == false) || ($show_access_on_upload == false || ($show_access_on_upload == true && !eval($show_access_on_upload_perm)))))            # Upload template and the status and access fields are configured to be hidden on uploads.
   {?>
   <input type=hidden name="access" value="<?php echo htmlspecialchars($resource["access"])?>"><?php
}
else
{
   if ($multiple) { ?><div class="Question"><input name="editthis_access" id="editthis_access" value="yes" type="checkbox" onClick="var q=document.getElementById('question_access');if (q.style.display!='block') {q.style.display='block';} else {q.style.display='none';}">&nbsp;<label for="editthis<?php echo $n?>"><?php echo $lang["access"]?></label></div><?php } ?>

   <div class="Question <?php if($lockable_fields && in_array("access",$locked_fields)){echo "lockedQuestion ";} if(isset($save_errors) && is_array($save_errors) && array_key_exists('access',$save_errors)) { echo 'FieldSaveError'; } ?>" id="question_access" <?php if ($multiple) {?>style="display:none;"<?php } ?>>
      <label for="access">
      <?php echo $lang["access"];
      if ($lockable_fields)
            {
            renderLockButton('access', $locked_fields);
            }
      ?></label><?php

            # Autosave display
      if ($edit_autosave || $ctrls_to_save) { ?><div class="AutoSaveStatus" id="AutoSaveStatusAccess" style="display:none;"></div><?php }

      $ea0=!checkperm('ea0');
      $ea1=!checkperm('ea1');
      $ea2=checkperm("v")?(!checkperm('ea2')?true:false):false;
      $ea3=$custom_access?!checkperm('ea3'):false;

      $access_stored_value = $resource["access"];  
      if (getval("submitted","") != "" && isset($access_submitted) && $access_submitted != $resource["access"])
          {
          $resource["access"] = $access_submitted; // Keep value chosen on form when a required field wasn't completed.
          }

      if(($ea0 && $resource["access"]==0) || ($ea1 && $resource["access"]==1) || ($ea2 && $resource["access"]==2) || ($ea3 && $resource["access"]==3))
      {
        if(!$multiple && getval("copyfrom","")=="" && $check_edit_checksums)
			{
			echo "<input id='access_checksum' name='access_checksum' type='hidden' value='" . $access_stored_value . "'>";
			}?>
        <select class="stdwidth" name="access" id="access" onChange="var c=document.getElementById('custom_access');<?php if ($resource["access"]==3) { ?>if (!confirm('<?php echo $lang["confirm_remove_custom_usergroup_access"] ?>')) {this.value=<?php echo $resource["access"] ?>;return false;}<?php } ?>if (this.value==3) {c.style.display='block';} else {c.style.display='none';}<?php if ($edit_autosave) {?>AutoSave('Access');<?php } ?>">
          <?php
                    if($ea0)    //0 - open
                    {$n=0;?><option value="<?php echo $n?>" <?php if ($resource["access"]==$n) { ?>selected<?php } ?>><?php echo $lang["access" . $n]?></option><?php }
                    if($ea1)    //1 - restricted
                    {$n=1;?><option value="<?php echo $n?>" <?php if ($resource["access"]==$n) { ?>selected<?php } ?>><?php echo $lang["access" . $n]?></option><?php }
                    if($ea2)    //2 - confidential
                    {$n=2;?><option value="<?php echo $n?>" <?php if ($resource["access"]==$n) { ?>selected<?php } ?>><?php echo $lang["access" . $n]?></option><?php }
                    if($ea3)    //3 - custom
                    {$n=3;?><option value="<?php echo $n?>" <?php if ($resource["access"]==$n) { ?>selected<?php } ?>><?php echo $lang["access" . $n]?></option><?php }
                    ?>
                 </select>
                 <?php
              }
              else
              {
                 ?>
                 <label class="stdwidth" id="access"><?php echo $lang["access" .$resource["access"]];?></label>
                 <?php
              }
              ?>
              <div class="clearerleft"> </div>
              <?php
              if($ea3 || $resource["access"]==3)
              {
                 ?>
                 <table id="custom_access" cellpadding=3 cellspacing=3 style="padding-left:150px;<?php if ($resource["access"]!=3) { ?>display:none;<?php } ?>"><?php
                 global $default_customaccess;
                 $customaccesssource = ($lockable_fields && in_array("access",$locked_fields) && $lastedited > 0) ? $lastedited : $ref;
                 $groups=get_resource_custom_access($customaccesssource);
                 $groups_count = count($groups);
                 for ($n=0;$n<$groups_count;$n++)
                 {
                   $access=$default_customaccess;
                   $editable= (!$ea3)?false:true;

                   if (isset($submitted_access_groups) && $submitted_access_groups[$groups[$n]['ref']] !== "" )
                       {
                       $access = $submitted_access_groups[$groups[$n]['ref']];
                       }
                   elseif ($groups[$n]["access"] !== '')
                       {
                       $access = $groups[$n]["access"];
                       }

                   $perms=explode(",",(string) $groups[$n]["permissions"]);
                   if (in_array("v",$perms) || $groups[$n]["ref"] == $usergroup) {$access=0;$editable=false;} ?>
                   <tr>
                      <td valign=middle nowrap><?php echo htmlspecialchars($groups[$n]["name"])?>&nbsp;&nbsp;</td>

                      <td width=10 valign=middle><input type=radio name="custom_<?php echo $groups[$n]["ref"]?>" value="0" <?php if (!$editable) { ?>disabled<?php } ?> <?php if ($access==0) { ?>checked <?php }
                      if ($edit_autosave) {?> onChange="AutoSave('Access');"<?php } ?>></td>

                      <td align=left valign=middle><?php echo $lang["access0"]?></td>

                      <td width=10 valign=middle><input type=radio name="custom_<?php echo $groups[$n]["ref"]?>" value="1" <?php if (!$editable) { ?>disabled<?php } ?> <?php if ($access==1) { ?>checked <?php }
                      if ($edit_autosave) {?> onChange="AutoSave('Access');"<?php } ?>></td>

                      <td align=left valign=middle><?php echo $lang["access1"]?></td>

                     <td width=10 valign=middle><input type=radio name="custom_<?php echo $groups[$n]["ref"]?>" value="2" <?php if (!$editable) { ?>disabled<?php } ?> <?php if ($access==2) { ?>checked <?php }
                     if ($edit_autosave) {?> onChange="AutoSave('Access');"<?php } ?>></td>

                     <td align=left valign=middle><?php echo $lang["access2"]?></td>

                  </tr><?php
               } ?>
            </table>
            <div class="clearerleft"> </div>
            <?php
         }
         ?>
         </div><?php
      }
   } /* end hook replaceaccessselector */

    # Related Resources
    if ($enable_related_resources && ($multiple || ($ref > 0 && !$upload_review_mode))) # Not when uploading
    {
       if ($multiple) { ?><div class="Question"><input name="editthis_related" id="editthis_related" value="yes" type="checkbox" onClick="var q=document.getElementById('question_related');if (q.style.display!='block') {q.style.display='block';} else {q.style.display='none';}">&nbsp;<label for="editthis_related"><?php echo $lang["relatedresources"]?></label></div><?php } ?>

       <div class="Question<?php if($lockable_fields && in_array("related_resources",$locked_fields)){echo " lockedQuestion ";} ?>" id="question_related" <?php if ($multiple) {?>style="display:none;"<?php } ?>>
          <label for="related"><?php echo $lang["relatedresources"];
           if ($lockable_fields)
            {
            renderLockButton('related_resources', $locked_fields);
            }?>
           </label><?php

        # Autosave display
        if ($edit_autosave  || $ctrls_to_save) { ?><div class="AutoSaveStatus" id="AutoSaveStatusRelated" style="display:none;"></div><?php } ?>

        <textarea class="stdwidth" rows=3 cols=50 name="related" id="related"<?php
        if ($edit_autosave) {?>onChange="AutoSave('Related');"<?php } ?>><?php
        
        if (!$editsearch)
            {
            $relatedref = ($lockable_fields && in_array("related_resources",$locked_fields) && $lastedited > 0) ? $lastedited : $ref;
            $related = get_related_resources($relatedref);

            echo ((getval("resetform","")!="")?"":join(", ", $related));
            }
        ?></textarea>

        <div class="clearerleft"> </div>
        </div><?php
       } 
    }
    
    // Edit the 'contributed by' value of the resource table
    if($ref > 0 && $edit_contributed_by)
      {
      $sharing_userlists = false;
      $single_user_select_field_id = "created_by";
	  $autocomplete_user_scope = "created_by";
      $single_user_select_field_value = $resource["created_by"];
      if ($edit_autosave) {$single_user_select_field_onchange = "AutoSave('created_by');"; }
      if ($multiple) { ?><div class="Question"><input name="editthis_created_by" id="editthis_created_by" value="yes" type="checkbox" onClick="var q=document.getElementById('question_created_by');if (q.style.display!='block') {q.style.display='block';} else {q.style.display='none';}">&nbsp;<label for="editthis_created_by>"><?php echo $lang["contributedby"] ?></label></div><?php } ?>
      <div class="Question" id="question_created_by" <?php if ($multiple) {?>style="display:none;"<?php } ?>>
        <label><?php echo $lang["contributedby"] ?></label><?php include __DIR__ . "/../include/user_select.php"; ?>
        <div class="clearerleft"> </div>
      </div>
      <?php
      }
    


    if($ref > 0 && !$upload_review_mode && $delete_resource_custom_access)
    {
       $query ='SELECT rca.user AS user_ref,
                IF(u.fullname IS NOT NULL, u.fullname, u.username) AS user
                FROM resource_custom_access AS rca
                INNER JOIN user AS u ON rca.user = u.ref
                WHERE resource = ?';
       $rca_users = ps_query($query, ['i', $ref]);
       
       $group_query =  'SELECT rca.usergroup AS usergroup_ref, u.name AS name
                        FROM resource_custom_access AS rca
                        INNER JOIN usergroup AS u ON rca.usergroup = u.ref
                        WHERE resource = ?';
       $rca_usergroups = ps_query($group_query, ['i', $ref]);

       ?>
    </div> <!-- end of previous collapsible section -->
    <h2 id="resource_custom_access" <?php echo ($collapsible_sections) ? ' class="CollapsibleSectionHead"' : ''; ?>><?php echo $lang["resource_custom_access"]?></h2>
    <div  id="ResourceCustomAccessSection" <?php echo ($collapsible_sections) ? 'class="CollapsibleSection"' : ''; ?>>
       <script type="text/javascript">
       function removeCustomAccess(ref,type) {
        jQuery.ajax({
          type: 'POST',
          url: '<?php echo $baseurl_short; ?>pages/ajax/remove_custom_access.php',
          data: {
            ajax: 'true',
            resource: <?php echo $ref; ?>,
            ref: ref,
            type: type,
            <?php echo generateAjaxToken("removeCustomAccess"); ?>
         },
         success: function() {
			 if(type=='user')
				{
				jQuery('#rca_user_' + ref).remove();
				}
			else if (type=='usergroup')
				{
				jQuery('#rca_usergroup_' + ref).remove();	
				}
         }
      });
     }
     </script>
     <div class="Question" id="question_resource_custom_access">
      <label for="res_custom_access"><?php echo $lang['remove_custom_access_users_groups']?></label>
      <!-- table here -->
      <table id="res_custom_access" cellpadding="3" cellspacing="3">
        <tbody>
          <?php
          foreach ($rca_users as $rca_user_info)
			{
             ?>
             <tr id="rca_user_<?php echo $rca_user_info['user_ref'] ?>">
               <td valign="middle" nowrap=""><?php echo $rca_user_info['user']; ?></td>
               <td valign="middle" nowrap="">&nbsp;</td>
               <td width="10" valign="middle">
                 <input type="hidden" name="remove_access_user_ref" value="<?php echo $rca_user_info['user_ref'] ?>">
                 <input type="submit" name="remove_access" value="Remove access" onClick="removeCustomAccess(<?php echo $rca_user_info['user_ref']; ?>,'user'); return false;">
              </td>
           </tr>
           <?php
			}
        foreach ($rca_usergroups as $rca_usergroup_info)
			{
             ?>
             <tr id="rca_group_<?php echo $rca_usergroup_info['usergroup_ref'] ?>">
               <td valign="middle" nowrap=""><?php echo $rca_usergroup_info['name']." (".$lang['group'].")"?></td>
               <td valign="middle" nowrap="">&nbsp;</td>
               <td width="10" valign="middle">
                 <input type="hidden" name="remove_access_usergroup_ref" value="<?php echo $rca_usergroup_info['usergroup_ref'] ?>">
                 <input type="submit" name="remove_access_group" value="Remove access" onClick="removeCustomAccess(<?php echo $rca_usergroup_info['usergroup_ref']; ?>,'usergroup'); return false;">
              </td>
           </tr>
           <?php
        }

                    // Add a default message if no users are attached
        if(count($rca_users) == 0 && count($rca_usergroups) == 0)
        {
          ?>
          <tr>
            <td><?php echo $lang['remove_custom_access_no_users_found']; ?></td>
         </tr>
         <?php
      }
      ?>
   </tbody>
</table>
<!-- end of table -->
<div class="clearerleft"> </div>
</div>
<?php
}

// Multiple method of changing geolocation.
if ($multiple && !$disable_geocoding)
    { ?>
    </div><h2 <?php echo ($collapsible_sections) ? " class=\"CollapsibleSectionHead\"" : ""?> id="location_title"><?php echo $lang["location-title"]; ?></h2><div <?php echo ($collapsible_sections) ? "class=\"CollapsibleSection\"" : ""?> id="LocationSection<?php if ($ref == "new") echo "Upload"; ?>">

    <div class="Question"><input name="editlocation" id="editlocation" type="checkbox" value="yes" onClick="var q=document.getElementById('editlocation_question');if (this.checked) {q.style.display='block';} else {q.style.display='none';}">&nbsp;<label for="editlocation"><?php echo $lang["location"]; ?></label></div>

    <div class="Question" style="display:none;" id="editlocation_question">
        <label for="location"><?php echo $lang["latlong"]; ?></label>
        <input type="text" name="location" id="location" class="stdwidth">
        <div class="clearerleft"> </div>
    </div>

    <div class="Question"><input name="editmapzoom" id="editmapzoom" type="checkbox" value="yes" onClick="var q=document.getElementById('editmapzoom_question');if (this.checked) {q.style.display='block';} else {q.style.display='none';}">&nbsp;<label for="editmapzoom"><?php echo $lang["mapzoom"]; ?></label></div>

    <div class="Question" style="display:none;" id="editmapzoom_question">
        <label for="mapzoom"><?php echo $lang["mapzoom"]; ?></label>
        <select name="mapzoom" id="mapzoom">
            <option value=""><?php echo $lang["select"]; ?></option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
            <option value="7">7</option>
            <option value="8">8</option>
            <option value="9">9</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>
            <option value="13">13</option>
            <option value="14">14</option>
            <option value="15">15</option>
            <option value="16">16</option>
            <option value="17">17</option>
            <option value="18">18</option>
        </select>
    </div>

    <div class="Question"><input name="editmaplocation" id="editmaplocation" type="checkbox" value="yes" onClick="var q=document.getElementById('editmaplocation_map');if (this.checked) {q.style.display='block'; map3.invalidateSize(true);} else {q.style.display='none';}">&nbsp;<label for="editmaplocation"><?php echo $lang['mapview']; ?></label></div>

    <!--Setup Leaflet map container with sizing-->
    <div id="editmaplocation_map" style="display:none; width: 99%; margin-top:0px; margin-bottom:0px; height:300px; border:1px solid black; float:none; overflow: hidden;">

    <script>
        var Leaflet1 = L.noConflict();

        <!--Setup and define the Leaflet map with the initial view using leaflet.js and L.Control.Zoomslider.js-->
        <?php set_geo_map_centerview(); ?>
        var map3 = new Leaflet1.map('editmaplocation_map').setView(mapcenterview,mapdefaultzoom);
        var defaultLayer = new Leaflet1.tileLayer.provider('<?php echo $map_default;?>', {
            attribution: 'Map data © <a href=\"http://openstreetmap.org\">OpenStreetMap</a> contributors'
        }).addTo(map3);

        <!--Limit geocoordinate values to six decimal places for display on marker hover-->
        function georound(num) {
            return +(Math.round(num + "e+6") + "e-6");
        }

        <!--Place a marker on the map when clicked-->
        var resourceMarker = [];
        currentZoom = map3.getZoom();
        map3.on('click', function(e) {
            geoLat = e.latlng.lat;
            geoLong = e.latlng.lng;
            currentZoom = map3.getZoom();
            console.log(geoLat, geoLong, currentZoom);

            <!--Clear existing marker when locating a new marker as we only want one marker for the resource-->
            if (resourceMarker != undefined) {
                map3.removeLayer(resourceMarker);
            };

            document.getElementById('location').value=georound(geoLat) + ', ' + georound(geoLong);
            document.getElementById('mapzoom').value=currentZoom;

            <!--Add a marker to show where you clicked on the map last and center the map on the marker-->
            resourceMarker = L.marker([geoLat, geoLong], {
                title: georound(geoLat) + ", " + georound(geoLong) + " (WGS84)"
            }).addTo(map3);
            map3.setView([geoLat, geoLong], currentZoom);
        });
    </script>
    </div>
    <div class="clearerleft"> </div> <?php
    hook("locationextras");
    }

if($disablenavlinks)
    { ?>
    <input type=hidden name="disablenav" value="true">
    <?php
    }

if(is_int_loose($collection_add))
    { 
    echo "<input type=hidden name='collection_add' value='" . htmlspecialchars($collection_add) . "'>";
    }
        
if (!$edit_upload_options_at_top && display_upload_options()){include '../include/edit_upload_options.php';}

if (!$external_upload && !$edit_upload_options_at_top)
    {
    ?></div><?php
    }

hook('appendcustomfields');

if ($edit_upload_options_at_top)
    {
    ?></div><?php
    }
?>
</div><!-- end of BasicsBoxLeft -->
<?php
if ($ref>0 && !$multiple)
    { ?>
<div class="BasicsBoxRight">
    <?php
    global $custompermshowfile;
        hook('custompermshowfile');
        if(!$is_template && !hook('replaceeditpreview'))
            { ?>
            <div class="Question QuestionStickyRight" id="question_file">
            <div class="FloatingPreviewContainer">
            <?php
            $bbr_preview_size = $edit_large_preview ? 'pre' : 'thm';
            if ($resource["has_image"]==1 && !resource_has_access_denied_by_RT_size($resource['resource_type'], $bbr_preview_size))
                { ?>
                <img id="preview" align="top" src="<?php echo get_resource_path($ref,false, $bbr_preview_size,false,$resource["preview_extension"],-1,1,false)?>" class="ImageBorder"/>
                <?php // check for watermarked version and show it if it exists
                if (checkperm("w"))
                    {
                    $wmpath=get_resource_path($ref,true, $bbr_preview_size,false,$resource["preview_extension"],-1,1,true);
                    if (file_exists($wmpath))
                        { ?>
                        <img style="display:none;" id="wmpreview" align="top" src="<?php echo get_resource_path($ref,false, $bbr_preview_size,false,$resource["preview_extension"],-1,1,true)?>" class="ImageBorder"/>
                        <?php 
                        }
                    } ?>
                <br />
                <?php
                }
            else
                {
                # Show the no-preview icon
                ?>
                <img src="<?php echo $baseurl_short ?>gfx/<?php echo get_nopreview_icon($resource["resource_type"],$resource["file_extension"],true)?>" />
                <br />
                <?php
                }
            if ($resource["file_extension"]!="") 
                { ?>           
                <strong>
                <?php 
                $orig_path = get_resource_path($ref,true,"",false,$resource["file_extension"]);
                if(file_exists($orig_path))
                    {
                    $filesize = filesize_unlimited($orig_path);
                    echo str_replace_formatted_placeholder("%extension", $resource["file_extension"], $lang["cell-fileoftype"]) . " (" . formatfilesize($filesize) . ")";
                    }                
                ?>
                </strong>
                <?php 
                if (checkperm("w") && $resource["has_image"]==1 && file_exists($wmpath))
                    {?> 
                    &nbsp;&nbsp;
                    <a href="#" onclick="jQuery('#wmpreview').toggle();jQuery('#preview').toggle();if (jQuery(this).text()=='<?php echo $lang['showwatermark']?>'){jQuery(this).text('<?php echo $lang['hidewatermark']?>');} else {jQuery(this).text('<?php echo $lang['showwatermark']?>');}"><?php echo $lang['showwatermark']?></a>
                    <?php 
                    }?>
                <br />
                <?php 
                }

            hook("afterfileoptions"); ?>
            </div>
            <div class="clearerleft"> </div>
        </div>
    <?php }
    ?>
</div><!-- end of BasicsBoxRight-->
<?php }
else
    {
    ?><div class="BasicsBoxRight"></div><?php
    }

if(!hook('replacesubmitbuttons'))
    {
    SaveAndClearButtons("NoPaddingSaveClear QuestionSticky",true,true);
    }

hook('aftereditcollapsiblesection');

?>

</div><!-- end of BasicsBox -->
</form>

<script>
// Helper script to assist with AJAX - when 'save' and 'reset' buttons are pressed, add a hidden value so the 'save'/'resetform' values are passed forward just as if those buttons had been clicked. jQuery doesn't do this for us.
 jQuery(".editsave").click(function(){
                jQuery("#mainform").append(
                    jQuery("<input type='hidden'>").attr( { 
                        name: "save", 
                        value: "true" }));}
                );
  jQuery(".resetform").click(function(){
                jQuery("#mainform").append(
                    jQuery("<input type='hidden'>").attr( { 
                        name: "resetform", 
                        value: "true" }));}
                );
   jQuery("#copyfromsubmit").click(function(){
                jQuery("#mainform").append(
                    jQuery("<input type='hidden'>").attr( { 
                        name: "copyfromsubmit", 
                        value: "true" }));}
                );
    jQuery(".save_auto_next").click(function(){
                jQuery("#mainform").append(
                    jQuery("<input type='hidden'>").attr( { 
                        name: "save_auto_next", 
                        value: "true" }));}
                );
</script>

<?php
if (isset($show_error) && isset($save_errors) && is_array($save_errors) && !hook('replacesaveerror'))
    {
    foreach ($save_errors as &$save_error) 
        {
        if(is_string($save_error))
            {
            $save_error=htmlspecialchars($save_error);
            }
        }
    ?>
    <script>
    preventautoscroll = true;
    // Find the first field that triggered the error:
    var error_fields;
    error_fields = document.getElementsByClassName('FieldSaveError');
    if(error_fields.length > 0)
        {
        error_fields[0].scrollIntoView();
        }
    styledalert('<?php echo $lang["error"]?>','<?php echo implode("<br />",$save_errors); ?>',450);
    </script>
    <?php
    }

hook("autolivejs");
?>

<script>
jQuery('document').ready(function()
    {
	/* Call SelectTab upon page load to select first tab*/
    SelectMetaTab(<?php echo $ref.",0,".$modalTrueFalse ?>);
    registerCollapsibleSections(true);

    // Move the preview image to the top of the page on smaller devices
    if (jQuery(document).width() < 850)
        {
        jQuery('#question_file').insertAfter(jQuery('.RecordHeader'));
        }
    });
</script>
<?php
include "../include/footer.php";
