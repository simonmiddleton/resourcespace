<?php
# Resource functions
# Functions to create, edit and index resources

include_once __DIR__ . '/definitions.php';		// includes log code definitions for resource_log() callers.

function create_resource($resource_type,$archive=999,$user=-1)
    {
    # Create a new resource.
    global $always_record_resource_creator,$index_contributed_by;

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

	if ($archive==-2 || $archive==-1 || (isset($always_record_resource_creator) and $always_record_resource_creator))
		{
		# Work out user ref - note: only for content in status -2 and -1 (user submitted / pending review).
		global $userref;
		$user=$userref;
		}
    else
        {$user=-1;}
        
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
	
function save_resource_data($ref,$multi,$autosave_field="")
	{
	# Save all submitted data for resource $ref.
	# Also re-index all keywords from indexable fields.
	global $lang, $auto_order_checkbox, $userresourcedefaults, $multilingual_text_fields,
           $languages, $language, $user_resources_approved_email, $FIXED_LIST_FIELD_TYPES,
           $DATE_FIELD_TYPES, $range_separator, $reset_date_field, $reset_date_upload_template,
           $edit_contributed_by, $new_checksums, $upload_review_mode, $blank_edit_template, $is_template;

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
                        $new_nodes_val = implode($new_nodevals,",");
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
						
						$added_nodes = array_diff($daterangenodes, $current_field_nodes);
						debug("save_resource_data(): Adding nodes to resource " . $ref . ": " . implode(",",$added_nodes));
						$nodes_to_add = array_merge($nodes_to_add, $added_nodes);
						$removed_nodes = array_diff($current_field_nodes,$daterangenodes);  
						debug("save_resource_data(): Removed nodes from resource " . $ref . ": " . implode(",",$removed_nodes));           
						$nodes_to_remove = array_merge($nodes_to_remove, $removed_nodes);
						
						if(count($added_nodes)>0 || count($removed_nodes)>0)
							{  
							$val = $newval;
							# If this is a 'joined' field it still needs to be added to the resource column
							$joins=get_resource_table_joins();
							if (in_array($fields[$n]["ref"],$joins))
								{
								if(substr($val,0,1)==","){$val=substr($val,1);}
								sql_query("update resource set field".$fields[$n]["ref"]."='".escape_check(truncate_join_field_value(substr($newval,1)))."' where ref='$ref'");
								 }
							}
					
					$new_checksums[$fields[$n]['ref']] = md5(implode(",",$daterangenodes));
                    }
				elseif(in_array($fields[$n]['type'], $DATE_FIELD_TYPES))
					{
                    # date type, construct the value from the date/time dropdowns
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

                    // Upload template: always reset to today's date, if configured and field is hidden
                    if(0 > $ref 
                        && $reset_date_upload_template
                        && $reset_date_field == $fields[$n]['ref']
                        && $fields[$n]['hide_when_uploading']
                    )
                        {
                        $val = date('Y-m-d H:i:s');
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
												
				$error=hook("additionalvalcheck", "all", array($fields, $fields[$n]));
				if ($error) 
					{
					global $lang;
					global $lang;
					if (getval("autosave","")!="")
						{
						exit($error);
						}
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
                !in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES)
                && str_replace("\r\n", "\n", $fields[$n]['value']) !== str_replace("\r\n", "\n", unescape($val))
            )
				{
				$oldval=$fields[$n]["value"];

				# This value is different from the value we have on record.

				# Write this edit to the log (including the diff) (unescaped is safe because the diff is processed later)
				resource_log($ref,'e',$fields[$n]["ref"],"",$fields[$n]["value"],unescape($val));

				# Expiry field? Set that expiry date(s) have changed so the expiry notification flag will be reset later in this function.
				if ($fields[$n]["type"]==6) {$expiry_field_edited=true;}

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
                    $restypename=sql_value("select name value from resource_type where ref in (select resource_type from resource where ref='" . escape_check($ref) . "')","");
                    remove_all_keyword_mappings_for_field($ref,-2);
                    add_keyword_mappings($ref,$restypename,-2);
                    }
            
            # Also save related resources field
            sql_query("delete from resource_related where resource='$ref' or related='$ref'"); # remove existing related items
            $related=explode(",",getvalescaped("related",""));
            # Make sure all submitted values are numeric
            $ok=array();for ($n=0;$n<count($related);$n++) {if (is_numeric(trim($related[$n]))) {$ok[]=trim($related[$n]);}}
            if (count($ok)>0) {sql_query("insert into resource_related(resource,related) values ($ref," . join("),(" . $ref . ",",$ok) . ")");}
            }

    // Update resource_node table
    db_begin_transaction();
    delete_resource_nodes($ref, $nodes_to_remove);
    if(0 < count($nodes_to_add))
        {
        add_resource_nodes($ref, $nodes_to_add, false);
        }
    db_end_transaction();

    // Autocomplete any blank fields without overwriting any existing metadata
    autocomplete_blank_fields($ref, false);

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

	
    hook('aftersaveresourcedata', '', array($ref, $nodes_to_add, $nodes_to_remove, $autosave_field));

	if (count($errors)==0) {return true;} else {return $errors;}
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
        $fields = sql_array("SELECT ref AS `value` FROM resource_type_field WHERE name = '{$field_shortname}'");

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


if (!function_exists("save_resource_data_multi")){
function save_resource_data_multi($collection,$editsearch = array())
    {
    global $auto_order_checkbox,$auto_order_checkbox_case_insensitive,  $FIXED_LIST_FIELD_TYPES,$DATE_FIELD_TYPES,
    $range_separator, $edit_contributed_by, $TEXT_FIELD_TYPES;

    # Save all submitted data for collection $collection or a search result set, this is for the 'edit multiple resources' feature
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

    $errors = array();
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

                        resource_log($ref, LOG_CODE_EDITED, $fields[$n]["ref"], '', $existing_nodes_value, $new_nodes_val);

                        $val = $new_nodes_val;

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
							resource_log($ref, LOG_CODE_EDITED, $fields[$n]["ref"], '', $fields[$n]["value"], $newval);
							
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
				elseif(in_array($fields[$n]['type'], $DATE_FIELD_TYPES))
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
                    $resource_data  = get_resource_data($ref, true);

                    if(
                        (
                            // Not applicable for global fields or archive only fields
                            !in_array($fields[$n]["resource_type"], array(0, 999))
                            && $resource_data["resource_type"] != $fields[$n]["resource_type"]
                        )
                        || ($fields[$n]["resource_type"] == 999 && $resource_data["archive"] != 2)
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
                            global $lang;
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
                            global $lang;
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
                        resource_log($ref,'m',$fields[$n]["ref"],"",$existing,$val);
            
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
                        
                        if (in_array($fields[$n]["type"],$FIXED_LIST_FIELD_TYPES))
                            {
                            # Prepend a comma when indexing dropdowns and checkboxes
                            $newval=  strlen($val)>0 && $val[0]==',' ? $val : ',' . $val;
                            $oldval=  strlen($oldval)>0 && $oldval[0]==',' ? $oldval : ',' . $oldval;
                            }
                        
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
                # Also update archive status   
                $oldarchive=sql_value("select archive value from resource where ref='$ref'","");
                $setarchivestate=getvalescaped("status",$oldarchive,true); // We used to get the 'archive' value but this conflicts with the archiveused for searching                                
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
			sql_query("update resource set expiry_notification_sent=0 where ref in (" . join(",",$list) . ")");
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
				resource_log($ref,"a",0,"",$oldaccess,$access);
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
			}
		}

	hook("saveextraresourcedata","",array($list));
	
    hook('aftersaveresourcedata', '', array($list, $all_nodes_to_add, $all_nodes_to_remove, $autosave_field=''));

    if (count($errors)==0) {return true;} else {return $errors;}
    
	}
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

if (!function_exists("remove_keyword_mappings")){
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



if(!function_exists('add_keyword_mappings')) {
function add_keyword_mappings($ref,$string,$resource_type_field,$partial_index=false,$is_date=false,$optional_column='',$optional_value='',$is_html=false)
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
}

function add_keyword_to_resource($ref,$keyword,$resource_type_field,$position,$optional_column='',$optional_value='',$normalized=false,$stemmed=false)
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
            
            # create mapping, increase hit count.
            if ($optional_column<>'' && $optional_value<>'')	# Check if any optional column value passed and add this
                {
                sql_query("insert into resource_keyword(resource,keyword,position,resource_type_field,$optional_column) values ('$ref','$keyref','$position','$resource_type_field','$optional_value')");
                }
            else  
                {
                if(isset($use_mysqli_prepared) && $use_mysqli_prepared)
                    {
                    sql_query_prepared('INSERT INTO `resource_keyword`(`resource`,`keyword`,`position`,`resource_type_field`) VALUES (?,?,?,?)',
                        array('iiii',$ref,$keyref,$position,$resource_type_field));
                    }
                else
                    {
                    sql_query("insert into resource_keyword(resource,keyword,position,resource_type_field) values ('$ref','$keyref','$position','$resource_type_field')");
                    }
                }

            sql_query("update keyword set hit_count=hit_count+1 where ref='$keyref'");
            
            # Log this
            daily_stat("Keyword added to resource",$keyref);
            }  	
    }
    
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
    global $FIXED_LIST_FIELD_TYPES, $NODE_FIELDS, $category_tree_add_parents;
    
    // accept shortnames in addition to field refs
    if(!is_numeric($field))
        {
        $field = sql_value("SELECT ref AS `value` FROM resource_type_field WHERE name = '" . escape_check($field) . "'", '');
        }

    // Fetch some information about the field
    $fieldinfo = sql_query("SELECT ref, keywords_index, resource_column, partial_index, type, onchange_macro FROM resource_type_field WHERE ref = '$field'");

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


    # If this is a dynamic keyword we need to add it to the field options
    if($fieldinfo['type'] == 9 && !checkperm('bdk' . $field))
        {
        $currentoptions = array();

        foreach($fieldoptions as $fieldoption)
            {
            $fieldoptiontranslations = explode('~', $fieldoption['name']);
            
            if(count($fieldoptiontranslations) < 2)
                {
                $currentoptions[]=trim($fieldoption['name']); # Not a translatable field
                debug("update_field: current field option: '" . trim($fieldoption['name']) . "'<br />");
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
        foreach($fieldoptions as $nodedata)
            {
            $newvalues_translated = $newvalues;
            $translate_newvalues = array_walk(
                $newvalues_translated,
                function (&$value, $index)
                    {
                    $value = i18n_get_translated($value);
                    }
            );

            // Add to array of nodes, unless it has been added to array already as a parent for a previous node
            if (in_array(i18n_get_translated($nodedata["name"]), $newvalues_translated) && !in_array($nodedata["ref"], $nodes_to_add)) 
                {
                $nodes_to_add[] = $nodedata["ref"];
                // We need to add all parent nodes for category trees
                if($fieldinfo['type']==FIELD_TYPE_CATEGORY_TREE && $category_tree_add_parents) 
                    {
                    $parent_nodes=get_parent_nodes($nodedata["ref"]);
                    foreach($parent_nodes as $parent_node_ref=>$parent_node_name)
                        {
                        $nodes_to_add[]=$parent_node_ref;
                        if (!in_array(i18n_get_translated($parent_node_name), $newvalues_translated))
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
            }

        # Update resource_node table
        db_begin_transaction();
        delete_resource_nodes($resource,$nodes_to_remove);

        if(count($nodes_to_add)>0)
            {
            add_resource_nodes($resource,$nodes_to_add, false);
            }
        db_end_transaction();
        }

    if ($fieldinfo["keywords_index"])
        {
        $is_html=($fieldinfo["type"]==8);	
        # If there's a previous value, remove the index for those keywords
        $existing=sql_value("select value from resource_data where resource='$resource' and resource_type_field='$field'","");
        if (strlen($existing)>0)
            {
            remove_keyword_mappings($resource,i18n_get_indexable($existing),$field,$fieldinfo["partial_index"],false,'','',$is_html);
            }

        if (in_array($fieldinfo['type'], $FIXED_LIST_FIELD_TYPES) && substr($value,0,1) <> ',')
            {
            $value = ','.$value;
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
		sql_query("update resource set field".$field."=" . (($value=="")?"NULL":"'" . $truncated_value . "'") ." where ref='$resource'");
		}			
	
    # Add any onchange code
    if($fieldinfo["onchange_macro"]!="")
        {
        eval($fieldinfo["onchange_macro"]);    
        }
    
    // Log this update
    if ($log && $value != $existing)
        {
        resource_log($resource,'e',$field,"",$existing,unescape($value));
        }
    
    # Allow plugins to perform additional actions.
    hook("update_field","",array($resource,$field,$value,$existing));
    return true;
    }

if (!function_exists("email_resource")){	
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
		resource_log($resource,"E","",$notes=$unames[$n]);
		
		}
	hook("additional_email_resource","",array($resource,$resourcename,$fromusername,$userlist,$message,$access,$expires,$useremail,$from_name,$cc,$templatevars));
	# Return an empty string (all OK).
	return "";
	}
}

function delete_resource($ref)
	{
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
        resource_log($ref,'x','');
		
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
				 {unlink($size['path']);}
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
		if (!function_exists("collection_log")){include_once ("collections_functions.php");}
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

function get_resource_ref_range($lower,$higher)
	{
	# Returns an array of resource references in the range $lower to $upper.
	return sql_array("select ref value from resource where ref>='$lower' and ref<='$higher' and archive=0 order by ref",0);
	}
	
function copy_resource($from,$resource_type=-1)
	{
    debug("copy_resource: copy_resource(\$from = {$from}, \$resource_type = {$resource_type})");
	# Create a new resource, copying all data from the resource with reference $from.
	# Note this copies only the data and not any attached file. It's very unlikely the
	# same file would be in the system twice, however users may want to clone an existing resource
	# to avoid reentering data if the resource is very similar.
	# If $resource_type if specified then the resource type for the new resource will be set to $resource_type
	# rather than simply copied from the $from resource.
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
	copyResourceDataValues($from,$to);
	
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
	resource_log($to,'c',0);

	hook("afternewresource", "", array($to));
	
	return $to;
	}
	
function resource_log($resource, $type, $field, $notes="", $fromvalue="", $tovalue="", $usage=-1, $purchase_size="", $purchase_price=0)
	{
	global $userref,$k,$lang,$resource_log_previous_ref, $internal_share_access;
    
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
                $diff = $tovalue;
                break;

            case LOG_CODE_NODE_REVERT:
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
            (($userref != "") ? "'" . escape_check($userref) . "'" : "null") . ",'" . escape_check($resource) . "','" . escape_check($type) . "'," . (($field=="") ? "null" : "'" . escape_check($field) . "'") . ",'" . escape_check($notes) . "','" .
            escape_check($diff) . "','" . escape_check($usage) . "','" . escape_check($purchase_size) . "','" . escape_check($purchase_price) . "'," . ((isset($k) && !$internal_share_access) ? "'{$k}'" : "null") . ",'" . escape_check($fromvalue) . "')");
        $log_ref = sql_insert_id();
        $resource_log_previous_ref = $log_ref;
        return $log_ref;
        }
	}

function get_resource_log($resource, $fetchrows = -1)
    {
    // Logs can sometimes contain confidential information and the user 
    // looking at them must have admin permissions set
    if(!checkperm('v'))
        {
        return array();
        }

    // Returns the log for a given resource.
    // The standard field titles are translated using $lang. Custom field titles are i18n translated.
    $extrafields = hook('get_resource_log_extra_fields');

    if(!$extrafields)
        {
        $extrafields = '';
        }

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
                        ekeys_u.fullname AS shared_by{$extrafields}
                   FROM resource_log AS r 
        LEFT OUTER JOIN user AS u ON u.ref = r.user
        LEFT OUTER JOIN resource_type_field AS f ON f.ref = r.resource_type_field
        LEFT OUTER JOIN external_access_keys AS ekeys ON r.access_key = ekeys.access_key AND r.resource = ekeys.resource
        LEFT OUTER JOIN user AS ekeys_u ON ekeys.user = ekeys_u.ref
              LEFT JOIN preview_size AS ps ON r.purchase_size = ps.id
        LEFT OUTER JOIN resource_type_field AS rtf ON r.resource_type_field = rtf.ref
                  WHERE r.resource = '{$resource}'
               GROUP BY r.ref
               ORDER BY r.date DESC",
        false,
        $fetchrows);

    for($n = 0; $n < count($log); $n++)
        {
        $log[$n]['title'] = lang_or_i18n_get_translated($log[$n]['title'], 'fieldtitle-');
        }

    return $log;
    }

function get_resource_type_name($type)
	{
	global $lang;
	if ($type==999) {return $lang["archive"];}
	return lang_or_i18n_get_translated(sql_value("select name value from resource_type where ref='$type'",""),"resourcetype-");
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
	
function get_themes_by_resource($ref)
	{
	global $theme_category_levels;

	$themestring="";
	for($n=1;$n<=$theme_category_levels;$n++){
		if ($n==1){$themeindex="";}else{$themeindex=$n;}
		$themestring.=",c.theme".$themeindex;
	}

	$themes=sql_query("select c.ref $themestring ,c.name,u.fullname from collection_resource cr join collection c on cr.collection=c.ref and cr.resource='$ref' and c.public=1 left outer join user u on c.user=u.ref order by length(theme) desc");
	# Combine the theme categories into one string so multiple category levels display correctly.
	$return=array();

	for ($n=0;$n<count($themes);$n++)
		{
		if (checkperm("j*") || checkperm("j" . $themes[$n]["theme"]))
			{
			$theme="";
			for ($x=1;$x<=$theme_category_levels;$x++){
				if ($x==1){$themeindex="";}else{$themeindex=$x;}
				if ($themes[$n]["theme".$themeindex]==""){break;}
				if ($themeindex!=""){$theme.=" / ";}

				if ($themes[$n]["theme".$themeindex]!="") {
					$theme.=$themes[$n]["theme".$themeindex];
				}
			}
			$themes[$n]["theme"]=$theme;
			$return[]=$themes[$n];
			}
		}
      
	return $return;
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
            $restypename=sql_value("select name value from resource_type where ref='" . escape_check($type) . "'","");
            remove_all_keyword_mappings_for_field($ref,-2);
            add_keyword_mappings($ref,$restypename,-2);
            }
                
    return true;    	
	}
	
function relate_to_array($ref,$array)	
	{
	# Relates a resource to each in a simple array of ref numbers
		sql_query("delete from resource_related where resource='$ref' or related='$ref'");  
		sql_query("insert into resource_related(resource,related) values ($ref," . join("),(" . $ref . ",",$array) . ")");
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
         ORDER BY exiftool_field");
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
        // Trust Exiftool's list of writable formats 
        $writable_formats = run_command("{$exiftool_fullpath} -listwf");
        $writable_formats = str_replace("\n", "", $writable_formats);
        $writable_formats_array = explode(" ", $writable_formats);
        if(!in_array(strtoupper($extension), $writable_formats_array))
            {
            return false;
            }

		$tmpfile = createTempFile($path, $uniqid, '');
		if($tmpfile === false)
            {
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
            }

        //$write_to = get_exiftool_fields($resource_type); # Returns an array of exiftool fields for the particular resource type, which are basically fields with an 'exiftool field' set.
        $metadata_all=get_resource_field_data($ref, false,true,-1,getval("k","")!=""); // Using get_resource_field_data means we honour field permissions
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
									debug("write_metadata - writing keyword:" . $keyword);
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
                        debug ("write_metadata - updating tag " . $group_tag);
                        # Write as is, convert the data to UTF-8 if not already.
                        
                        global $strip_rich_field_tags;
                        if (!$exiftool_write_omit_utf8_conversion && (!isset($mysql_charset) || (isset($mysql_charset) && strtolower($mysql_charset)!="utf8"))){$writevalue = mb_convert_encoding($writevalue, mb_detect_encoding($writevalue), 'UTF-8');}
                            if ($strip_rich_field_tags)
                            {
                                $command.= escapeshellarg("-" . $group_tag . "=" . trim(strip_tags($writevalue))) . " ";
                            }
                            else
                            {
                                $command.= escapeshellarg("-" . $group_tag . "=" . htmlentities($writevalue, ENT_QUOTES, "UTF-8")) . " ";
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
        return false;
        }
    }

function delete_exif_tmpfile($tmpfile)
{
	if(file_exists($tmpfile)){unlink ($tmpfile);}
}

function update_resource($r, $path, $type, $title, $ingest=false, $createPreviews=true, $extension='',$after_upload_processing=false)
	{
	# Update the resource with the file at the given path
	# Note that the file will be used at it's present location and will not be copied.
	global $syncdir, $staticsync_prefer_embedded_title, $view_title_field, $filename_field, $upload_then_process, $offline_job_queue;

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
            global $filename_field;
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

            global $filename_field;
            if (isset($filename_field))
                {
                update_field($r,$filename_field,$filename);
                }

            # Move the file
            if(!hook('update_resource_replace_ingest','',array($r, $path, $extension)))
                {
                global $syncdir;
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
        global $extracted_text_field;
        if (isset($extracted_text_field) && !(isset($unoconv_path) && in_array($extension,$unoconv_extensions))) 
            {
            global $offline_job_queue, $offline_job_in_progress;
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
            global $autorotate_ingest;
            if($ingest && $autorotate_ingest){AutoRotateImage($destination);}
            # Generate previews/thumbnails (if configured i.e if not completed by offline process 'create_previews.php')
            global $enable_thumbnail_creation_on_upload;
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
            global $userref, $lang;
            
            $job_data=array();
            $job_data["r"]=$r;
            $job_data["title"]=$title;
            $job_data["ingest"]=$ingest;
            $job_data["createPreviews"]=$createPreviews;
        
            global $upload_then_process_holding_state;
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
	# Import the resource at the given path
	# This is used by staticsync.php and Camillo's SOAP API
	# Note that the file will be used at it's present location and will not be copied.

	# Create resource
	$r=create_resource($type);
        return update_resource($r, $path, $type, $title, $ingest, $createPreviews, $extension);
	}

function get_alternative_files($resource,$order_by="",$sort="")
	{
	# Returns a list of alternative files for the given resource
	if ($order_by!="" && $sort!=""){
		$ordersort=$order_by." ".$sort.",";
	} else {
		$ordersort="";
	}
	$extrasql=hook("get_alternative_files_extra_sql","",array($resource));
	return sql_query("select ref,name,description,file_name,file_extension,file_size,creation_date,alt_type from resource_alt_files where resource='".escape_check($resource)."' $extrasql order by ".escape_check($ordersort)." name asc, file_size desc");
	}
	
function add_alternative_file($resource,$name,$description="",$file_name="",$file_extension="",$file_size=0,$alt_type='')
	{
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
	resource_log($resource,'y','');
	
	# Update disk usage
	update_disk_usage($resource);
	}
	
function get_alternative_file($resource,$ref)
	{
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
	
if (!function_exists("user_rating_save")){	
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
}

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

function notify_user_contributed_submitted($refs,$collection=0)
	{
	// Send notifications when resources are moved from "User Contributed - Pending Submission" to "User Contributed - Pending Review"
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
	
function get_field($field)
    {
    # A standard field title is translated using $lang.  A custom field title is i18n translated.

    # Executes query.
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
         WHERE ref = '{$field}'
     ");

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

function get_field_options_with_stats($field)
	{
	# For a given field, list all options with usage stats.
	# This is for the 'manage field options' page.

	//$rawoptions=sql_value("select options value from resource_type_field where ref='$field'","");
	//$options=trim_array(explode(",",i18n_get_translated($rawoptions)));
    //$rawoptions=trim_array(explode(",",$rawoptions));

    $rawoptions=array();
    node_field_options_override($rawoptions,$field);

	# For the given field, fetch a stats count for each keyword.
	$usage=sql_query("
		  SELECT rk.resource_type_field,
		         k.keyword,
		         count(DISTINCT rk.resource) c
		    FROM resource_keyword rk
		    JOIN keyword k ON rk.keyword = k.ref
		   WHERE rk.resource > 0
		     AND resource_type_field = '$field'
		GROUP BY k.keyword;
	");
	
	$return=array();
	for ($n=0;$n<count($options);$n++)
		{
		if($options[$n]!=''){
			# Find the option in the usage array and extract the count
			$count=0;
			for ($m=0;$m<count($usage);$m++)
				{
				$keyword=get_keyword_from_option($options[$n]);
				if ($keyword==$usage[$m]["keyword"]) {$count=$usage[$m]["c"];}
				}
				
			$return[]=array("option"=>$options[$n],"rawoption"=>$rawoptions[$n],"count"=>$count);
			}
		}
	return $return;
	}
	
function save_field_options($field)
	{
	# Save the field options after editing.
	global $languages,$defaultlanguage;
	
	$fielddata=get_field($field);
	$options=get_nodes($field);
	//$options=trim_array(explode(",",$fielddata["options"]));

	for ($n=0;$n<count($options);$n++)
		{
		hook("before_save_field_options","",array($field,$options,$n));
		if (getval("submit_field_" . $n,"")!="")
			{
			# This option/language combination is being renamed.

			# Construct a new option from the posted languages
			$new="";$count=0;
			foreach ($languages as $langcode=>$langname)
				{
				$val=getvalescaped("field_" . $langcode . "_" . $n,"");
				if ($val!="") {$new.="~" . $langcode . ":" . $val;$count++;}
				}
			# Only one language, do not use language syntax.
			if ($count==1) {$new=getvalescaped("field_" . $defaultlanguage . "_" . $n,"");}
			
			# Construct a new options value by creating a new array replacing the item in position $n
			$newoptions=array_merge(array_slice($options,0,$n),array($new),array_slice($options,$n+1));

            foreach ($newoptions as $no)
                {
                set_node(null,$field,$no,null,null);
                }

			# Loop through all matching resources.
			# The matches list uses 'like' so could potentially return values that do not have this option set. However each value list split out and analysed separately.
			$matching=sql_query("select resource,value from resource_data where resource_type_field='$field' and value like '%" . escape_check($options[$n]) . "%'");
			for ($m=0;$m<count($matching);$m++)
				{
				$ref=$matching[$m]["resource"];
								
				$set=trim_array(explode(",",$matching[$m]["value"]));
				
				# Construct a new value omitting the old and adding the new.
				$newval=array();
				for ($s=0;$s<count($set);$s++)
					{
					if ($set[$s]!==$options[$n]) {$newval[]=$set[$s];}
					}
				$newval[]=$new; # Set the new value on the end of this string
				$newval=join(",",$newval);
				
				#echo "Old value = '" . $matching[$m]["value"] . "', new value = '" . $newval . "'";
				
				if ($matching[$m]["value"]!== $newval)
					{
					# Value has changed. Update.

					# Delete existing keywords index for this field.
					sql_query("delete from resource_keyword where resource='$ref' and resource_type_field='$field'");
					
					# Store value and reindex
					update_field($ref,$field,$newval);
					}
				}
			
			}


		if (getval("delete_field_" . $n,"")!="")
			{
			# This field option is being deleted.
			
			# Construct a new options value by creating a new array ommitting the item in position $n
			$new=array_merge(array_slice($options,0,$n),array_slice($options,$n+1));
			
            foreach ($new as $new_option)
                {
                set_node(null,$field,escape_check(trim($new_option)),null,null);
                }
			
			# Loop through all matching resources.
			# The matches list uses 'like' so could potentially return values that do not have this option set. However each value list split out and analysed separately.
			$matching=sql_query("select resource,value from resource_data where resource_type_field='$field' and value like '%" . escape_check($options[$n]) . "%'");
			for ($m=0;$m<count($matching);$m++)
				{
				$ref=$matching[$m]["resource"];
								
				$set=trim_array(explode(",",$matching[$m]["value"]));
				$new=array();
				for ($s=0;$s<count($set);$s++)
					{
					if ($set[$s]!==$options[$n]) {$new[]=$set[$s];}
					}
				$new=join(",",$new);
				
				if ($matching[$m]["value"]!== $new)
					{
					# Value has changed. Update.

					# Delete existing keywords index for this field.
					sql_query("delete from resource_keyword where resource='$ref' and resource_type_field='$field'");
					
					# Store value and reindex
					update_field($ref,$field,$new);
					}
				}
			}
		}
	}
	
function get_resources_matching_keyword($keyword,$field)
	{
	# Returns an array of resource references for resources matching the given keyword string.
	$keyref=resolve_keyword($keyword);
	return sql_array("select distinct resource value from resource_keyword where keyword='$keyref' and resource_type_field='$field'");
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
	
function add_field_option($field,$option)
	{
    set_node(null,$field,escape_check(trim($option)),null,null);
	return true;
	}

if (!function_exists("get_resource_access")){	
function get_resource_access($resource)
	{
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
    global $customgroupaccess,$customuseraccess;
	$customgroupaccess=false;
	$customuseraccess=false;
	
	global $k;
	if('' != $k)
		{
        global $internal_share_access;

		# External access - check how this was shared.
		$extaccess = sql_value("SELECT access `value` FROM external_access_keys WHERE resource = '{$ref}' AND access_key = '" . escape_check($k) . "' AND (expires IS NULL OR expires > NOW())", -1);

		if(-1 != $extaccess && (!$internal_share_access || ($internal_share_access && $extaccess < $access)))
            {
            return $extaccess;
            }
		}
	
	global $uploader_view_override, $userref;
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
			global $usergroup;
			$access=get_custom_access($resource,$usergroup);
			} 
		else {
			$access=$resource['group_access'];
		}
	}

	global $prevent_open_access_on_edit_for_active;
	if ($access == 1 && get_edit_access($ref,$resourcedata['archive'],false,$resourcedata) && !$prevent_open_access_on_edit_for_active)
		{
		# If access is restricted and user has edit access, grant open access.
		$access = 0;
		}

	global $open_access_for_contributor;
	if ($open_access_for_contributor && $resourcedata['created_by'] == $userref)
		{
		# If user has contributed resource, grant open access and ignore any further filters.
		return 0;
		}

	# Check for user-specific and group-specific access (overrides any other restriction)
	global $userref,$usergroup;

	// We need to check for custom access either when access is set to be custom or
	// when the user group has restricted access to all resource types or specific resource types
	// are restricted
    if ($access!=0 || !checkperm('g') || checkperm('X' . $resource_type))
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
		return $userspecific;
		}
	if (isset($groupspecific) && $groupspecific!="")
		{
		$customgroupaccess=true;
		return $groupspecific;
		}
        
	if (checkperm('T'.$resource_type))
		{
		// this resource type is always confidential/hidden for this user group
		return 2;
		}
		
	global $usersearchfilter, $search_filter_strict; 
	if ((trim($usersearchfilter)!="") && $search_filter_strict)
		{
		# A search filter has been set. Perform filter processing to establish if the user can view this resource.		
                # Apply filters by searching for the resource, utilising the existing filter matching in do_search to avoid duplication of logic.

                global $search_all_workflow_states;
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

	// Check for a derestrict filter, this allows exeptions for users without the 'g' permission who normally have restricted accesss to all available resources)
	global $userderestrictfilter;
	if ($access==1 && !checkperm("g") && !checkperm("rws{$resourcedata['archive']}") && !checkperm('X'.$resource_type) && trim($userderestrictfilter) != "")
		{
		# A filter has been set to derestrict access when certain metadata criteria are met
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
			
		if($matchedfilter){$access=0;}
        }
		
	return $access;	
	}
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

if (!function_exists("resource_download_allowed")){
function resource_download_allowed($resource,$size,$resource_type,$alternative=-1)
	{
	
	# For the given resource and size, can the current user download it?
	# resource type and access may already be available in the case of search, so pass them along to get_resource_access to avoid extra queries
	# $resource can be a resource-specific search result array.
	$access=get_resource_access($resource);

    if (checkperm('T' . $resource_type . "_" . $size))
        {
        return false;
        }

	if (checkperm('X' . $resource_type . "_" . $size) && $alternative==-1)
		{
		# Block access to this resource type / size? Not if an alternative file
		# Only if no specific user access override (i.e. they have successfully requested this size).
		global $userref, $usergroup;
		$usercustomaccess = get_custom_access_user($resource,$userref);
		$usergroupcustomaccess = get_custom_access($resource,$usergroup);
		if (($usercustomaccess === false || !($usercustomaccess==='0')) && ($usergroupcustomaccess === false || !($usergroupcustomaccess==='0'))) {return false;}
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
        $sizes_always_allowed = array('col', 'thm', 'pre', 'snapshot');

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
}

function get_edit_access($resource,$status=-999,$metadata=false,&$resourcedata="")
	{
	# For the provided resource and metadata, does the current user have edit access to this resource?
	# Checks the edit permissions (e0, e-1 etc.) and also the group edit filter which filters edit access based on resource metadata.
	
	global $userref,$usereditfilter,$edit_access_for_contributor;

    $plugincustomeditaccess = hook('customediteaccess','',array($resource,$status,$resourcedata));

    if($plugincustomeditaccess)
        {
        return ('false' === $plugincustomeditaccess ? false : true);
        }

	if (!is_array($resourcedata) || !isset($resourcedata['resource_type'])) # Resource data  may not be passed 
		{
		$resourcedata=get_resource_data($resource);		
		}	
	if ($status==-999) # Archive status may not be passed 
		{$status=$resourcedata["archive"];}
		
	if ($resource==0-$userref) {return true;} # Can always edit their own user template.

    # If $edit_access_for_contributor is true in config then users can always edit their own resources.
    if ($edit_access_for_contributor && $userref==$resourcedata["created_by"]) {return true;}
        
    # Must have edit permission to this resource first and foremost, before checking the filter.
    if (!checkperm("e" . $status)) {return false;} 
    
    # Cannot edit if z permission
    if (checkperm("z" . $status)) {return false;}

    # Cannot edit if pending status (<0) and neither admin ('t') nor created by currentuser 
    #             and does not have force edit access to the resource type
    if (    $status<0 && !( checkperm("t") || $resourcedata['created_by'] == $userref ) 
         && !checkperm("ert" . $resourcedata['resource_type'])
       )
        {
        return false;
        } 
	
	$gotmatch=false;
	if (trim($usereditfilter)=="" || ($status<0 && $resourcedata['created_by'] == $userref)) # No filter set, or resource was contributed by user and is still in a User Contributed state in which case the edit filter should not be applied.
		{
		$gotmatch = true;
		}
	else
		{
		# An edit filter has been set. Perform edit filter processing to establish if the user can edit this resource.
		
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
	
	if ($gotmatch) {
	  $gotmatch = !hook("denyafterusereditfilter");
	}
	
	# Default after all filter operations, allow edit.
	return $gotmatch;
	}


function filter_match($filter,$name,$value)
	{
	# In the given filter string, does name/value match?
	# Returns:
	# 0 = no match for name
	# 1 = matched name but value was not present
	# 2 = matched name and value was correct
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
        $fromvalue = explode(',', i18n_get_translated($fromvalue));
        $tovalue   = explode(',', i18n_get_translated($tovalue));

        // Empty arrays if either side is blank.
        if (count($fromvalue)==1 && trim($fromvalue[0])=="") {$fromvalue=array("");}
        if (count($tovalue)==1   && trim($tovalue[0])=="")   {$tovalue=array("");}
            
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
	
	
function check_use_watermark(){
	# access status must be available prior to this.
	# This function checks whether to use watermarks or not.
	# Three cases:
	# if access is restricted and the group has "w"
	# if $watermark_open is true and the group has "w"
	# if $watermark is set and it's an external share.
	global $access,$k,$watermark,$watermark_open,$pagename,$watermark_open_search;
	if (($watermark_open && ($pagename == "preview" || $pagename == "view" || ($pagename == "search" && $watermark_open_search)) || $access==1) && (checkperm('w') || ($k!="" && isset($watermark)))){return true;} else {return false;}
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
* @param boolean $force_run Allow code to force running this function and update the fields even if there is data.
* For example:
* - when creating a resource, autocomplete_blank_fields() should always be triggered regardless if user has data in its user template.
* - when copying resource/ extracting embedded metadata, autocomplete_blank_fields() should not overwrite if there is data 
* for that field as at this point you probably have the expected data for your field.
* 
* @return void
*/
function autocomplete_blank_fields($resource, $force_run)
    {
    global $FIXED_LIST_FIELD_TYPES;

    $resource_escaped = escape_check($resource);

    $resource_type = sql_value("SELECT resource_type AS `value` FROM resource WHERE ref = '{$resource_escaped}'", 0);

    $fields = sql_query("
        SELECT ref,
               type,
               autocomplete_macro
          FROM resource_type_field
         WHERE (resource_type = 0 || resource_type = '{$resource_type}')
           AND length(autocomplete_macro) > 0
    ");

    foreach($fields as $field)
        {
        $value = sql_value("SELECT `value` FROM resource_data WHERE resource = '{$resource_escaped}' AND resource_type_field = '{$field['ref']}'", '');

        if(in_array($field['type'], $FIXED_LIST_FIELD_TYPES))
            {
            if(count(get_resource_nodes($resource, $field['ref'], true)) == 0)
                {
                $value = '';
                }
            }

        $run_autocomplete_macro = $force_run || hook('run_autocomplete_macro');

        if(strlen(trim($value)) == 0 || $run_autocomplete_macro)
            {
            # Empty value. Autocomplete and set.
            $value = eval($field['autocomplete_macro']);
            update_field($resource, $field['ref'], $value);
            }
        }
    }


function get_resource_files($ref,$includeorphan=false){
    // returns array of all files associated with a resource
    // if $includeorphan set to true, will also return all files in the
    // resource dir even if the system doesn't understand why they're there.

    $filearray = array();
    $file_checklist = array();

    global $config_windows;
    if ($config_windows){ $sep = "\\"; } else { $sep = "/"; }


    $sizearray = sql_array("select id value from preview_size",false);
    $original_ext = sql_value("select file_extension value from resource where ref = '".escape_check($ref)."'",'');

    $rootpath=dirname(get_resource_path($ref,true,"pre",true));

    // get listing of all files in resource dir to compare mark off as we find them
    if (is_dir($rootpath)) {
    if ($dh = opendir($rootpath)) {
            while (($file = readdir($dh)) !== false) {
                if (!($file == '.' || $file == '..')){
                    $file_checklist[$rootpath.$sep.$file] = 1;
                }
            }
            closedir($dh);
        }
    }

    // first get the resource itself
    $original = get_resource_path($ref,true,'',false,$original_ext);
    if (file_exists($original)){
	    array_push($filearray,$original);
	    unset($file_checklist[$original]);
    }

    // in some cases, the system also generates a jpeg equivalent of the original, so check for that
    $original = get_resource_path($ref,true,'',false,'jpg');
    if (file_exists($original)){
	    array_push($filearray,$original);
    	unset($file_checklist[$original]);
    }

    // in some cases, the system also generates an mp3 equivalent of the original, so check for that
    $original = get_resource_path($ref,true,'',false,'mp3');
    if (file_exists($original)){
    	array_push($filearray,$original);
    	unset($file_checklist[$original]);
    }

    // in some cases, the system also generates an extracted icc profile, so check for that
    $original = get_resource_path($ref,true,'',false,'icc');
    if (file_exists($original)){
    	array_push($filearray,$original);
    	unset($file_checklist[$original]);
    }


    # check for pages
    $page = 1;
    $misscount = 0;
    // just to be safe, we'll try at least 4 pages ahead to make sure none got skipped
    while($misscount < 4){
        $thepath = get_resource_path($ref,true,"scr",false,'jpg',-1,$page,"","","");
        if (file_exists($thepath)){
            array_push($filearray,$thepath);
            unset($file_checklist[$thepath]);
            $page++;
        } else {
            $misscount++;
            $page++;
        }
    }        

    // now look for other sizes
    foreach($sizearray as $size){
        $thepath = get_resource_path($ref,true,$size,false,'jpg');
        if (file_exists($thepath)){
            array_push($filearray,$thepath);
            unset($file_checklist[$thepath]);
        }
    }


    // get alternative files
    $altfiles = get_alternative_files($ref);
    foreach($altfiles as $altfile){
        // first get original
        $alt_ext = sql_value("select file_extension value from resource_alt_files where ref = '" . $altfile['ref'] . "'",'');
        $thepath = get_resource_path($ref,true,'',false,$alt_ext,-1,1,false,"",$altfile["ref"]);
        if (file_exists($thepath)){
            array_push($filearray,$thepath);
            unset($file_checklist[$thepath]);
        }


        // now check for previews
        foreach($sizearray as $size){
            $thepath = get_resource_path($ref,true,$size,false,"jpg",-1,1,false,"",$altfile["ref"]);
            if (file_exists($thepath)){
                array_push($filearray,$thepath);
                unset($file_checklist[$thepath]);
            }
        }

        # check for pages
        $page = 1;
        while($page <> 0){
            $thepath = get_resource_path($ref,true,"scr",false,'jpg',-1,$page,"","",$altfile['ref']);
            if (file_exists($thepath)){
                array_push($filearray,$thepath);
                unset($file_checklist[$thepath]);
                $page++;
            } else {
                $page = 0;
            }
        }
        // in some cases, the system also generates a jpeg equivalent of the original, so check for that
        $original = get_resource_path($ref,true,'',false,'jpg',-1,1,'','',$altfile['ref']);
	if (file_exists($original)){
	        array_push($filearray,$original);
        	unset($file_checklist[$original]);
    	}

        // in some cases, the system also generates a mp3 equivalent of the original, so check for that
        $original = get_resource_path($ref,true,'',false,'mp3',-1,1,'','',$altfile['ref']);
	if (file_exists($original)){
	        array_push($filearray,$original);
       		unset($file_checklist[$original]);
	}

        // in some cases, the system also generates an extracted icc profile, so check for that
        $original = get_resource_path($ref,true,'',false,'icc',-1,1,'','',$altfile['ref']);
	if (file_exists($original)){
	        array_push($filearray,$original);
       		unset($file_checklist[$original]);
	}
    }


    // check for ffmpeg previews
    global $ffmpeg_preview_extension;
    $flvfile=get_resource_path($ref,true,"pre",false,$ffmpeg_preview_extension);
    if (file_exists($flvfile)){
        array_push($filearray,$flvfile);
        unset($file_checklist[$flvfile]);
    }


    if (count($file_checklist)>0){
	foreach (array_keys($file_checklist) as $thefile){
		debug("ResourceSpace: Orphaned file, resource $ref: $thefile");
	        if ($includeorphan) {
			array_push($filearray,$thefile);
		}
       }
    }
    return array_unique($filearray);
}

if (!function_exists("reindex_resource")){
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

function update_disk_usage_cron()
	{
	# Update disk usage for all resources that have not yet been updated or have not been updated in the past 30 days.
    # Limit to a reasonable amount so that this process is spread over several cron intervals for large data sets.
    
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

function get_total_disk_usage()
	{
	# Returns sum of all resource disk usage
	global $fstemplate_alt_threshold;
	return sql_value("select sum(disk_usage) value from resource where ref>'$fstemplate_alt_threshold'",0);
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
	
	$o_size=sql_query("select * from resource_dimensions where resource={$ref}");
	if(!empty($o_size))
		{
		if(count($o_size)>1)
			{
			# delete all the records and start fresh. This is a band-aid should there be multiple records as a result of using api_search
			sql_query("delete from resource_dimensions where resource={$ref}");
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
				sql_query("insert into resource_dimensions (resource, width, height, file_size) values('". $ref ."', '". $sw ."', '". $sh ."', '" . $filesize . "')");
				}
			else
				{
				sql_query("update resource_dimensions set width='". $sw ."', height='". $sh ."', file_size='" . $filesize . "' where resource={$ref}");
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
				sql_query("insert into resource_dimensions (resource, width, height, file_size) values('". $ref ."', '". $sw ."', '". $sh ."', '" . $filesize . "')");
				}
			else
				{
				sql_query("update resource_dimensions set width='". $sw ."', height='". $sh ."', file_size='" . $filesize . "' where resource={$ref}");
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
					sql_query("insert into resource_dimensions (resource, width, height, file_size) values('". $ref ."', '". $sw ."', '". $sh ."', '" . $filesize . "')");
					}
				else
					{
					sql_query("update resource_dimensions set width='". $sw ."', height='". $sh ."', file_size='" . $filesize . "' where resource={$ref}");
					}
			    }
			else
			    {

			    # Size cannot be calculated.
			    $sw="?";$sh="?";
				if(!$o_size)
					{
					# Insert a dummy row to prevent recalculation on every view.
					sql_query("insert into resource_dimensions (resource, width, height, file_size) values('". $ref ."','0', '0', '" . $filesize . "')");
					}
				else
					{
					sql_query("update resource_dimensions set width='0', height='0', file_size='" . $filesize . "' where resource={$ref}");
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

if(!function_exists("get_resource_external_access")){
function get_resource_external_access($resource)
	{
	# Return all external access given to a resource 
	# Users, emails and dates could be multiple for a given access key, an in this case they are returned comma-separated.
	return sql_query("select access_key,group_concat(DISTINCT user ORDER BY user SEPARATOR ', ') users,group_concat(DISTINCT email ORDER BY email SEPARATOR ', ') emails,max(date) maxdate,max(lastused) lastused,access,expires,collection,usergroup, password_hash from external_access_keys where resource='$resource' group by access_key,access,expires,collection,usergroup order by maxdate");
	}
}
        
function delete_resource_access_key($resource,$access_key)
    {
    sql_query("delete from external_access_keys where access_key='$access_key' and resource='$resource'");
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
        $config_options=sql_value("select config_options value from resource_type where ref='" . escape_check($resource_type) . "'","");
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

        resource_log($resource[$n], 's', 0, '', isset($existingstates[$n]) ? $existingstates[$n] : '', $archive);    
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
function update_related_resource($ref,$related,$add=true)
	{	
	if (!is_int($ref) || !is_int($related)){return false;}
	$currentlyrelated=sql_value("select count(resource) value from resource_related where (resource='$ref' and related='$related') or (resource='$related' and related='$ref')",0);  
	if($currentlyrelated!=0 && !$add)
		{
		// Relationship exists and we want to remove
		sql_query("delete from resource_related where (resource='$ref' and related='$related') or (resource='$related' and related='$ref')");  
		}
	elseif ($currentlyrelated==0 && $add)
		{
		// Relationship does not exist and we want to add
		sql_query("insert into resource_related(resource,related) values ('$ref','$related')");
		}
	return true;
	}

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

function delete_resource_custom_access_usergroups($ref)
        {
        # delete all usergroup specific access to resource $ref
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
function copyResourceDataValues($from, $to)
    {
    $from            = escape_check($from);    
    $to              = escape_check($to);
    $omit_fields_sql = '';

    // When copying normal resources from one to another, check for fields that should be excluded
    // NOTE: this does not apply to user template resources (negative ID resource)
    if($from > 0)
        {
        $omitfields      = sql_array("SELECT ref AS `value` FROM resource_type_field WHERE omit_when_copying = 1", 0);
        $omit_fields_sql = "AND rd.resource_type_field NOT IN ('" . implode("','", $omitfields) . "')";
        }

    sql_query("
        INSERT INTO resource_data(resource, resource_type_field, value)
             SELECT '{$to}',
                    rd.resource_type_field,
                    rd.value
               FROM resource_data AS rd
               JOIN resource AS r ON rd.resource = r.ref
               JOIN resource_type_field AS rtf ON rd.resource_type_field = rtf.ref
                    AND (
                            rtf.resource_type = r.resource_type
                            OR rtf.resource_type = 999
                            OR rtf.resource_type = 0
                        )
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
				resource_log($resource["ref"],"a",0,"",$resource["access"],$newaccess);
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
                # Gets the checked values (like 'Keywords - Subject') and puts them in the specific resource table columns (like 'field73' (Keywords - Subject))
                $specific_field = 'field' . $locked_field;
                $current_field_for_last_edited = sql_query("SELECT $specific_field AS thefield FROM resource WHERE ref = '$lastedited'");
                $the_field_data = $current_field_for_last_edited[0]['thefield'];
                sql_query("UPDATE resource SET $specific_field = '$the_field_data' WHERE ref = '$ref'");
                
                // Replace nodes for this field
                $field_nodes = get_nodes($locked_field, NULL, $fieldtype == FIELD_TYPE_CATEGORY_TREE);
                $field_node_refs = array_column($field_nodes,"ref");
                $stripped_nodes = array_diff ($all_selected_nodes, $field_node_refs);
                $locked_nodes = get_resource_nodes($lastedited, $locked_field);
                $all_selected_nodes = array_merge($stripped_nodes, $locked_nodes);
                if($save == true)
                    {
                    debug("- adding locked field nodes for resource " . $ref . ", field id: " . $locked_field);
                    delete_resource_nodes($ref,$field_node_refs);
                    if(count($locked_nodes) > 0)
                        {
                        add_resource_nodes($ref, $locked_nodes, false);
                        }
                    }
                }
            else
                {
                debug(" - checking field values for last resource " . $lastedited . " field id: " . $locked_field);
                if(!isset($last_fields))
                    {
                    $last_fields = get_resource_field_data($lastedited,!hook("customgetresourceperms"),-1,"",$tabs_on_edit);
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
                if($save == true)
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
            $show_error=true;
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
        $show_error=true;
        }
      
    if ($upload_collection_name_required)
        {
        if (getvalescaped("entercolname","")=="" && getval("collection_add","")=="new")
              { 
              if (!is_array($save_errors)){$save_errors=array();} 
              $save_errors['collectionname'] = $lang["collectionname"] . ": " .$lang["requiredfield"];
              $show_error=true;
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
    if($size == '')
        {
        $size = 'original';
        }

    $pextension = ($size == 'original' ? $resource['file_extension'] : 'jpg');

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
    elseif ($override_status_default)
        {
        # Set the default status if set in config.
        return $override_status_default;
        }
    elseif (checkperm("c"))
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

    if ($ref < 1)
        {
        debug("ERROR: Unable to save original file as alternative - resource id incorrect");
        die('{"jsonrpc" : "2.0", "error" : {"code": 111, "message": "Failed to save original file as alternative file. "}, "id" : "id"}');                
        }

    /*
    global vars
    * @param boolean $alternative_file_previews                  Generate thumbs/previews for alternative files?
    * @param boolean $alternative_file_previews_batch            Generate thumbs/previews for alternative files?
    * @param array   $lang 
    */

    global $lang, $alternative_file_previews, $alternative_file_previews_batch;

    // GET variables
    $replace_resource_original_alt_filename = getvalescaped('replace_resource_original_alt_filename', ''); // alternative filename
    $alternative                            = getvalescaped('alternative', ''); # Batch upload alternative files    
    $filename_field                         = getval('filename_field', ''); // GET variable - field to use for filename

    // Make the original into an alternative, need resource data so we can get filepath/extension
    $origdata     = get_resource_data($ref);
    $origfilename = get_data_by_field($ref, $filename_field);

    $newaltname        = str_replace('%EXTENSION', strtoupper($origdata['file_extension']), $lang['replace_resource_original_description']);
    $newaltdescription = nicedate(date('Y-m-d H:i'), true);

    if('' != $replace_resource_original_alt_filename)
        {
        $newaltname = $replace_resource_original_alt_filename;
        $newaltdescription = '';
        }

    $newaref = add_alternative_file($ref, $newaltname, $newaltdescription, escape_check($origfilename), $origdata['file_extension'], $origdata['file_size']);

    $origpath=get_resource_path($ref, true, "", true, $origdata["file_extension"]);
    $newaltpath=get_resource_path($ref, true, "", true, $origdata["file_extension"], -1, 1, false, "", $newaref);

    # Move the old file to the alternative file location
    $result=rename($origpath, $newaltpath);		# true on success; false on failure to rename file						

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
                    # Move the old preview file to the alternative preview file location
                    $alt_preview_path=get_resource_path($ref, true, $ps[$n]["id"], true, "", -1, 1, false, "", $newaref);
                    rename($orig_preview_path, $alt_preview_path);			
                    }
                # Also for the watermarked versions.
                $wmpath=get_resource_path($ref,true,$ps[$n]["id"],false,"jpg",-1,1,true,"",$alternative);
                if (file_exists($wmpath))
                    {
                    # Move the old preview file to the alternative preview file location
                    $alt_preview_wmpath=get_resource_path($ref, true, $ps[$n]["id"], true, "", -1, 1, true, "", $newaref);
                    rename($wmpath, $alt_preview_wmpath);			
                    }
            }
        }


            
    return $result;
    }
