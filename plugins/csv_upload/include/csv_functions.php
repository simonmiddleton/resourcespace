<?php

include_once (dirname(__FILE__)."/../../../include/metadata_functions.php");

function csv_upload_process($filename,&$meta,$resource_types,&$messages,$max_error_count=100,$processcsv=false,$csv_set_options)
	{
    // Ensure /r line endings (such as those created in MS Excel) are handled correctly
	$save_auto_detect_line_endings = ini_set("auto_detect_line_endings", "1");  
    global $FIXED_LIST_FIELD_TYPES, $DATE_FIELD_TYPES, $NODE_FIELDS, $userref,
    $category_tree_add_parents, $mysql_verbatim_queries;

    $file=fopen($filename,'r');
    $line_count=0;
    $headers = fgetcsv($file);
    $error_count=0;

    // Get list of possible resources to replace
    if($csv_set_options["update_existing"])
        {
        if($csv_set_options["csv_update_col"] && $csv_set_options["csv_update_col_id"] > 0)
            {
            $replaceresources = do_search("!collection" . (int)$csv_set_options["csv_update_col_id"],'','ref','',-1,'asc',false,0,false,false,'',false,false,true,true);
            }
        else
            {
            // Limit resources to replace to those that user can edit
            $replaceresources = do_search('','','ref','',-1,'asc',false,0,false,false,'',false,false,true,true);
            }
            
        if(!is_array($replaceresources))
            {
            array_push ($messages,"Error: No editable resources found");
            return false;
            }

        $replaceresources = array_column($replaceresources,"ref");
        }

	# ----- start of header row checks -----
	if($csv_set_options["add_to_collection"] > 0)
		{
		global $usercollection;
		$add_to_collection=true;
		}
	else
        {
        $add_to_collection=false;
        }

	# ----- end of header row checks, process each of the rows checking data -----
    $restypefields = get_resource_type_fields();

    foreach($restypefields as $field)
        {
        $allfields[$field["ref"]] = $field;
        $allfields[$field["ref"]]["options"] =  in_array($field["type"],$NODE_FIELDS) ? get_field_options($field["ref"],true) : array();
        $allfields[$field["ref"]]["resource_types"] = array();
        foreach($resource_types as $resource_type => $resource_type_info)
            {
            if($field["resource_type"] == $resource_type
                ||
                $field["resource_type"] == 0 && $resource_type_info["inherit_global_fields"] == 1
                )
                {
                $allfields[$field["ref"]]["resource_types"][] = $resource_type;
                }
            }
        }
	array_push ($messages,"Processing " . count($csv_set_options["fieldmapping"]) . " metadata columns");

	while ((($line=fgetcsv($file))!==false) && ($error_count<$max_error_count || $max_error_count==0))
		{
        $line_count++;
		if (!$processcsv && count($line) != count($headers))	// check that the current row has the correct number of columns
			{
			array_push ($messages,"Error: Incorrect number of columns(" . count($line) . ") found on line " . $line_count . " (should be " . count($header) . ")");
			$error_count++;
			continue;
			}

        $processed_columns = array();

        // Get the required resource type - needed before processing data so resources can be created
        if($csv_set_options["resource_type_column"] != "")
            {
            $resource_type_set = $line[$csv_set_options["resource_type_column"]];
            if(trim($resource_type_set) == "")
                {
                if($csv_set_options["update_existing"])
                    {
                    // Don't change the resource type
                    $resource_type_set = 0;
                    }
                else
                    {
                    // Use the default
                    $resource_type_set = $csv_set_options["resource_type_default"];
                    }
                }
            elseif((string)(int)$resource_type_set != (string)$resource_type_set)
                {
                // Not an integer - Check for text matching resource type
                foreach($resource_types as $resource_type)
                    {
                    if(mb_strtolower($resource_type["name"]) == mb_strtolower($resource_type_set))
                        {
                        $resource_type_set = $resource_type["ref"];
                        break;
                        }
                    }
                }

            // Check that this is a valid resource type    
            if(trim($resource_type_set) != "" && !in_array($resource_type_set,array_keys($resource_types)))
                {
                array_push ($messages,"Warning: Invalid resource type (" . $line[$csv_set_options["resource_type_column"]] . ") specified in line " . count($line));
                $error_count++;
                $resource_type_set = $csv_set_options["resource_type_default"];
                }

            $processed_columns[] = $csv_set_options["resource_type_column"];
            }
        elseif($csv_set_options["update_existing"])
            {
            // Don't change the resource type
            $resource_type_set = 0;
            }
        else
            {
            // Use the default
            $resource_type_set = $csv_set_options["resource_type_default"];
            }

        // echo "Resource type: " . $resource_type_set . "<br>";

        // Check that required fields are present for new resources
        if(!$csv_set_options["update_existing"])
            {
            $missing_fields=array();
            foreach ($meta[$resource_type_set] as $field_name=>$field_attributes)
                {
                if ($field_attributes['required'] && array_search($field_attributes["remote_ref"], $csv_set_options["fieldmapping"])===false)
                    {
                    $meta[$resource_type_set][$field_name]['missing']=true;
                    array_push($missing_fields, $meta[$resource_type_set][$field_name]['nicename']);
                    }
                }
            if (count($missing_fields) == 0)
                {
                if(!$processcsv)
                    {
                    array_push($messages,"Info (line #" . $line_count . "): Found correct field headers for resource_type " . $resource_type_set . " (" . $resource_types[$resource_type_set]["name"] . ")");
                    }
                }
            else
                {
                array_push($messages,"Warning: (line #" . $line_count . ") resource_type " . $resource_type_set . " (" . $resource_types[$resource_type_set]["name"] . ") has missing field headers (" . implode(",",$missing_fields) . ") and will be ignored");
                }
            }

        // Find existing or create new resources to be updated
        if($csv_set_options["update_existing"])
            {
            if($csv_set_options["id_column_match"] == 0)
                {
                // Matching on resource ID
                $resource_id = (int)$line[$csv_set_options["id_column"]];
                if(!in_array($resource_id,$replaceresources))
                    {
                    array_push ($messages,"Error: Invalid resource (" . $resource_id . ")specified in line " . count($line));
			        $error_count++;
			        continue;
                    }
                $resourcerefs = array($resource_id);
                }
            else
                {
                // Matching on field value
                $match_field = $allfields[$csv_set_options["id_column_match"]];
                $match_val = $line[$csv_set_options["id_column"]];
                if(trim($match_val) == "")
                    {
                    array_push ($messages,"Error: Invalid resource identifier specified in line " . count($line));
                    $error_count++;
                    continue;
                    }
                $matchsearch = "\"" . $match_field["name"] . ":" . $match_val . "\"";
                $allmatches = do_search($matchsearch,'','ref','',-1,'asc',false,0,false,false,'',false,false,true,true);
                
                if(!is_array($allmatches))
                    {
                    // May be trying to match on file path in which case see if we can match with forward slashes rather than backslashes
                    $matchsearch = "\"" . $match_field["name"] . ":" . str_replace("\\","/",$match_val) . "\"";
                    $allmatches = do_search($matchsearch,'','ref','',-1,'asc',false,0,false,false,'',false,false,true,true);
                    }

                if(!is_array($allmatches))
                    {
                    array_push ($messages,"Error: No matching resources found matching the identifier " . $match_val . " specified in line " . count($line));
                    $error_count++;
                    continue;
                    }

                $validmatches = array_values(array_intersect(array_column($allmatches,"ref"),$replaceresources));
                if(count($validmatches) == 0)
                    {
                    array_push ($messages,"Error: No matching resources found matching the identifier " . $match_val . " specified in line " . count($line));
                    $error_count++;
                    continue;
                    }
                elseif(count($validmatches) == 1)
                    {
                    array_push ($messages,"Found resource ID : " . $validmatches[0] . " matching the identifier " . $match_val . " specified in line " . count($line));
                    $resourcerefs = $validmatches;
                    }
                elseif($csv_set_options["multiple_match"])
                    {
                    array_push ($messages,"Processing multiple matching resources (" . implode(",",$validmatches) . ") found matching the identifier " . $match_val . " specified in line " . count($line));
                    $resourcerefs = $validmatches;
                    }
                else
                    {
                    array_push ($messages,"Error: Multiple matching resources (" . implode(",",$validmatches) . ") found matching the identifier " . $match_val . " specified in line " . count($line));
                    $error_count++;
                    continue;
                    }
                }
            }
        else
            {
            // Get status to set
            if($csv_set_options["status_column"] != "")
                {
                $setstatus = $line[$csv_set_options["status_column"]];
                $processed_columns[] = (int)$csv_set_options["status_column"];
                }
            else
                {
                $setstatus = $csv_set_options["status_default"];
                }

            // Get access to set
            if($csv_set_options["access_column"] != "")
                {
                $setaccess = $line[$csv_set_options["access_column"]];
                $processed_columns[] = $csv_set_options["access_column"];
                }
            else
                {
                $setaccess = (int)$csv_set_options["access_default"];
                }

            if($processcsv)
                {
                // Create the new resource
                $newref = create_resource($resource_type_set, $setstatus);
                sql_query("update resource set access='" . $setaccess . "' where ref='$newref'");
                array_push ($messages,"Created new resource: #" . $newref . " (" . $resource_types[$resource_type_set]["name"] . ")");
                if($add_to_collection)
                    {
                    add_resource_to_collection($newref,$usercollection);
                    }
                }
            else
                {
                if(!isset($newref))
                    {
                    $lastref = sql_value("SELECT MAX(ref) value FROM resource",0);
                    $newref  = $lastref + 1;
                    }
                else
                    {
                    $newref  = $newref + 1;
                    }
                array_push ($messages," - create new resource: # " . $newref . " (" . $resource_types[$resource_type_set]["name"] . ")");
                }
            $resourcerefs = array($newref);
            }
        
        array_push($messages," Line " . $line_count . ": " . ($processcsv ? "Updating" : "Update") . " resources: " . implode(",",$resourcerefs));
        
		$cell_count=-1;
		$workflow_states = get_editable_states($userref);

        // Update resource type if required
        if($csv_set_options["update_existing"] && $resource_type_set != 0)
            {
            foreach($resourcerefs as $resource_id)
                {
                array_push($messages," - " . ($processcsv ? "Updating" : "Update") . " resource type for resource id #" . $resource_id . " to " . $resource_type_set);
                if($processcsv)
                    {
                    update_resource_type($resource_id,$resource_type_set);
                    }
                }
            }

        // Now process the actual data
		foreach ($headers as $column_id => $field_name)	
			{
            // Skip columns already processed as special columns e.g. resource type, id etc.
            // or if not included in mappings
            // or if field not applicable to resource type
            if(in_array($column_id,$processed_columns) 
                ||
                !isset($csv_set_options["fieldmapping"][$column_id])
                ||
                $csv_set_options["fieldmapping"][$column_id] == -1
                ||
                    (
                    $resource_type_set != 0
                    &&
                    isset($allfields[$csv_set_options["fieldmapping"][$column_id]])
                    &&
                    !in_array($resource_type_set,$allfields[$csv_set_options["fieldmapping"][$column_id]]["resource_types"])
                    )
                )
                {
                $cell_count++;

                //array_push($messages, "skipping column  " . $column_id . " as it does not apply to this resource type");
                continue;
                }


            $fieldid        = $csv_set_options["fieldmapping"][$column_id];
            //echo "Checking column id : " . $column_id  . " field id #" . $fieldid . "<br/>";
            $field_def      = $allfields[$fieldid];
			$field_name     = $field_def['name'];
            $field_type 	= $field_def['type'];
            $required 		= $field_def['required'];

            if ($field_type == FIELD_TYPE_CATEGORY_TREE)
                {
                // For category trees user must be using the same language as the CSV
                $currentoptions = array();
                $field_nodes   = get_nodes($fieldid,'', (FIELD_TYPE_CATEGORY_TREE == $field_type));
                $node_options = get_tree_strings($field_nodes, true);
                $node_trans_arr[$fieldid] = array();
                foreach($node_options as $noderef => $nodestring)
                    {
                    $node_trans_arr[$fieldid][$noderef] = array($nodestring);
                    $currentoptions[] = mb_strtolower($nodestring);
                    }

                }
            elseif (in_array($field_type,$NODE_FIELDS))
                {
                // Get all current field options, including translations
                $currentoptions = array();
                $field_nodes   = get_nodes($fieldid,'', (FIELD_TYPE_CATEGORY_TREE == $field_type));
                $node_trans_arr[$fieldid] = array();
                $node_options = array_column($field_nodes, 'name', 'ref');
                foreach($field_nodes as $field_node)
                    {
                    // Create array to hold all translations for a node so that any translation can match the correct node
                    $node_trans_arr[$fieldid][$field_node["ref"]] = array();
                    $nodetranslations = explode('~', $field_node["name"]);

                    if(count($nodetranslations) < 2)
                        {
                        $currentoptions[]=mb_strtolower(trim($field_node['name'])); # Not a translatable field
                        $node_trans_arr[$fieldid][$field_node["ref"]][] = trim($field_node['name']);
                        }
                    else
                        {
                        for ($n=1;$n<count($nodetranslations);$n++)
                            {
                            if (substr($nodetranslations[$n],2,1)!=":" && substr($nodetranslations[$n],5,1)!=":" && substr($nodetranslations[$n],0,1)!=":")
                                {
                                # Not a translated string, return as-is
                                $currentoptions[]=mb_strtolower(trim($field_node['name']));
                                $node_trans_arr[$fieldid][$field_node["ref"]][] = trim($field_node['name']);
                                }
                            else
                                {
                                # Support both 2 character and 5 character language codes (for example en, en-US)
                                $p=strpos($nodetranslations[$n],':');                        
                                $currentoptions[]=mb_strtolower(trim(substr($nodetranslations[$n],$p+1)));
                                $node_trans_arr[$fieldid][$field_node["ref"]][] = trim(substr($nodetranslations[$n],$p+1));
                                }
                            }
                        }
                    }
                }

            $cell_value=trim($line[$column_id]);		// important! we trim values, as options may contain a space after the comma
			//echo "Found value for " . $field_name . ": " . $cell_value . "<br>";

            $cell_count++;

            // Raise error if it's a required field and has an empty or null value
            if (in_array($cell_value,  array(null,"") ))
                {
                // raise error if required field
                if ($required) 
                    {
                    array_push($messages, "Error: \"{$field_name}\" is a required field - empty value - line {$line_count}");
                    $error_count++;
                    continue;
                    }
                }
            
            // Extra check to replace backslashes with forward slashes
            // This is required because file paths are often used as resource identifier but back slashes
            // cannot be stored in resource_data by default

            if(!$mysql_verbatim_queries && mb_strpos($cell_value,"\\") !== false)
                {
                $cell_value = str_replace("\\","/",$cell_value);
                }

            // Check for multiple options
            // cell value may be a series of values, but not for radio or drop down types
            if(in_array($field_type, $NODE_FIELDS) && !in_array($field_type,array(FIELD_TYPE_DROP_DOWN_LIST,FIELD_TYPE_RADIO_BUTTONS))) 
                    {
                    // Replace curly quotes with standard quotes and use split_keywords() to get separate entries
                    $cell_value_array = str_getcsv($cell_value);
                    }
                elseif(trim($cell_value) != "")
                    {
                    // Make single value into a dummy array
                    $cell_value_array=array(trim($cell_value));
                    }
                else
                    {
                    $cell_value_array=array();
                    }

            $update_dynamic_field=false;

            # validate option against multiple option list 
            foreach ($cell_value_array as $cell_value_item)
                {
                $cell_value_item = trim($cell_value_item); # strip whitespace from beginning and end of string
                if($cell_value_item == "")
                    {
                    continue;
                    }

                #if the field type has options and the value is not in the current option list:
                if (in_array($field_type,$NODE_FIELDS))
                    {
                    // Check nodes are valid for this field, remove quotes 
                    //echo "Checking for '" . htmlspecialchars($cell_value_item) . "' in ('" . implode("','",$currentoptions) . "')<br/>";

                    if('' != $cell_value_item && !in_array(mb_strtolower($cell_value_item), $currentoptions))
                        {
                        switch ($field_type)
                            {
                            case (FIELD_TYPE_DYNAMIC_KEYWORDS_LIST) :
                                # add option
                                if(checkperm("bdk" . $fieldid))
                                    {
                                    $error_count++;
                                    array_push($messages, "No permission to add option " . $cell_value_item . " to field: " . $field_name);
                                    continue 2;
                                    }
                                // Update the field with the new option
                                if($processcsv)
                                    {
                                    $new_node = set_node(null, $fieldid, $cell_value_item, null, null, false);
                                    }
                                else 
                                    {
                                    $lastref = sql_value("SELECT MAX(ref) value FROM node;",0);
                                    $new_node  = isset($new_node) ? $new_node + 1 : $lastref + 1;
                                    array_push($messages," - " . ($processcsv ? "Added" : "Add") . " new field option to field " . $field_name .  " as node " . $new_node . ", value:'" . $cell_value_item . "'");
                                    }
                                
                                $node_trans_arr[$fieldid][$new_node] = array($cell_value_item);
                                $node_options[$new_node] = $cell_value_item;
                            break;

                            case (FIELD_TYPE_DATE_RANGE):
                                # date range has format date/date
                                $rangeregex="/^(\d{4})(-\d{2})?(-\d{2})?\/(\d{4})(-\d{2})?(-\d{2})?/";
                                if(!preg_match($rangeregex,$cell_value_item,$matches))
                                    {
                                    # raise error - invalid date format
                                    $error_count++;
                                    array_push($messages, " - Invalid date range format - use EDTF format");
                                    continue 2;
                                    }    
                            break;

                            default:
                                # field doesn't allow options to be added so raise error
                                $error_count++;
                                array_push($messages, "Error: \"{$field_name}\" - the value \"{$cell_value_item}\" is not in the metadata field option list - line {$line_count}");
                                continue 2;
                            }
                        }
                    }
                # validate date field excluding date range field  - $DATE_FIELD_TYPES global var in definitions.php
                elseif(in_array($field_type, $DATE_FIELD_TYPES) and $field_type != FIELD_TYPE_DATE_RANGE)
                    {
                    # valid date if empty string returned
                    $valid_date = check_date_format($cell_value_item);
                    if ($valid_date != "")
                        {
                        # raise error - invalid date format
                        $error_count++;
                        array_push($messages, str_replace(array("%row%", "%field%"), array($line_count,  $field_name), $valid_date ));
                        continue 2;
                        }
                    }
                }

            // Set values if processing
            foreach($resourcerefs as $resource_id)
                {
                $nodes_to_add       = array();
                $nodes_to_remove    = array();
                if ($processcsv)
                    {
                    array_push($messages, " - Updated field '" . $fieldid . "' (" . $field_def['title'] . ") with value '" . $cell_value . "'");
                    if($field_def['type']==FIELD_TYPE_DATE_RANGE)
                        {
                        # each value will be a node so we end up with a pair of nodes to represent the start and end dates
                        if(is_numeric($field_def["linked_data_field"]))
                            {
                            // Update the linked field with the raw EDTF string submitted
                            update_field($resource_id,$field_def["linked_data_field"],$cell_value);
                            }
                        // Get currently selected nodes for this  
                        $current_field_nodes = $csv_set_options["update_existing"] ? get_resource_nodes($resource_id, $fieldid) : array();                       

                        if($cell_value == "")
                            {
                            $nodes_to_remove = $current_field_nodes;
                            break;
                            }
                        if(strpos($cell_value,",") !== false)
                            {
                            $rangedates = explode(",",$cell_value);
                            }
                        else
                            {    
                            $rangedates = explode("/",$cell_value);
                            }
						$rangestart=str_pad($rangedates[0],  10, "-00");
						$rangeendparts=explode("-",$rangedates[1]);
                        $rangeendyear=$rangeendparts[0];
                        $rangeendmonth=isset($rangeendparts[1])?$rangeendparts[1]:12;
                        $rangeendday=isset($rangeendparts[2])?$rangeendparts[2]:cal_days_in_month(CAL_GREGORIAN, $rangeendmonth, $rangeendyear);
						$rangeend=$rangeendyear . "-" . $rangeendmonth . "-" . $rangeendday;

                        $daterangenodes     = array();
                        $daterangestartnode = set_node(null, $fieldid, $rangestart, null, null,true);
                        $daterangeendnode   = set_node(null, $fieldid, $rangeend, null, null,true);

                        // get latest list of nodes, in case new nodes added with set_node() above
                        $field_nodes   = get_nodes($fieldid,'', (FIELD_TYPE_CATEGORY_TREE == $field_type));
                        $node_options = array_column($field_nodes, 'name', 'ref');

                        $node_trans_arr[$fieldid][$daterangestartnode]  = $rangestart;
                        $node_trans_arr[$fieldid][$daterangeendnode]    = $rangeend;
                        $daterangenodes = array($daterangestartnode,$daterangeendnode);

                        $nodes_to_add = array_diff($daterangenodes, $current_field_nodes);
                        $nodes_to_remove = array_diff($current_field_nodes,$daterangenodes);
						}
                    elseif (in_array($field_type,$NODE_FIELDS))
                        {


                        // Get currently selected nodes for this field 
                        $setnodes = array();
                        $current_field_nodes = $csv_set_options["update_existing"] ? get_resource_nodes($resource_id, $fieldid) : array();
                        if(count($cell_value_array) > 0)
                            {
                            foreach($node_trans_arr[$fieldid] as $node_id => $translations)
                                {
                                foreach($translations as $translation)
                                    {
                                    // echo "Checking for '" . htmlspecialchars($node_translation) . "' in ('" . implode("','",$cell_value_array) . "')<br/>";
                                    // Add to array of nodes, unless it has been added to array already as a parent for a previous node
                                    if (in_array($translation, $cell_value_array))
                                        {
                                        //echo "Found node " . $node_id . "<br/>";
                                        $setnodes[] = $node_id;
                                        // We need to add all parent nodes for category trees
                                        if($field_def['type']==FIELD_TYPE_CATEGORY_TREE && $category_tree_add_parents) 
                                            {
                                            $parent_nodes = get_parent_nodes($node_id);
                                            foreach($parent_nodes as $parent_node_ref=>$parent_node_name)
                                                {
                                                $setnodes[] = $parent_node_ref;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        $nodes_to_add = array_diff($setnodes, $current_field_nodes);
						$nodes_to_remove = array_diff($current_field_nodes,$setnodes);
                        }
                    else
                        {
                        update_field($resource_id, $fieldid, $cell_value);
                        }

                    if(count($nodes_to_add) > 0 || count($nodes_to_remove) > 0)
                        {
                        $new_nodes_val = "";
                        delete_resource_nodes($resource_id, $nodes_to_remove);
                        if(count($nodes_to_add))
                            {
                            add_resource_nodes($resource_id, $nodes_to_add, false);
                            }

                        // Build new value
                        foreach($nodes_to_add as $node_to_add)
                            {
                            $new_nodes_val .= "," . $node_options[$node_to_add];
                            }     

                        // If this is a 'joined' field it still needs to add it to the resource column
                        $joins = get_resource_table_joins();
                        if(in_array($fieldid, $joins))
                            {
                            sql_query("UPDATE resource SET field{$fieldid} = '" . escape_check(truncate_join_field_value(substr($new_nodes_val, 1)))."' WHERE ref = '{$resource_id}'");
                            }
                        }
                    }
                elseif($cell_value != "")
                    {
                    array_push($messages," - " . ($processcsv ? "Updating" : "Update") . " resource " . $resource_id . ", field '" . $field_name . "' with value '" . $cell_value . "'");
                    }
                } // End of foreach resourcerefs

            ob_flush();
			}	// end of cell loop
		}  // end of loop through lines
	
	fclose($file);

    // add an error if there are no lines of data to process (i.e. just the header)
	if (0 == $line_count && !$processcsv)
		{
		array_push($messages,"Error: No lines of data found in file");
		}

	if ($error_count > 0 && !$processcsv)
		{
		if ($max_error_count > 0 && $error_count>=$max_error_count)
			{
			array_push($messages,"Warning: Showing first {$max_error_count} data validation errors only - more may exist");
			}
		ini_set("auto_detect_line_endings", $save_auto_detect_line_endings);
		return false;
        }

	array_push($messages,"Info: data successfully " . ($processcsv ? "processed" : "validated"));

	ini_set("auto_detect_line_endings", $save_auto_detect_line_endings);
	return true;
    }


function csv_upload_get_info($filename, &$messages)
	{
	$save_auto_detect_line_endings = ini_set("auto_detect_line_endings", "1");

    global $lang;

	$file=fopen($filename,'r');
	$line_count=0;

	if (($headers = fgetcsv($file))==false)
		{
		array_push($messages, $lang["csv_upload_error_no_header"]);
		fclose($file);
		ini_set("auto_detect_line_endings", $save_auto_detect_line_endings);
		return false;		
        }

    // Create array to hold sample data to show to user
    $headercount = count($headers);
    $csv_data = array();
    for($n=0;$n<$headercount;$n++)
        {
        $csv_data[$n]["header"] = $headers[$n];
        $csv_data[$n]["values"] = array();
        }

    $row = 0;
    $founddata = array();
    while ((($data= fgetcsv($file)) != false) && count($founddata) < $headercount)
        {
        for($c=0;$c<$headercount;$c++)
            {
            if(isset($data[$c]) && trim($data[$c]) != "")
                {
                $csv_data[$c]["values"][$row] = mb_substr($data[$c],0,30) . (mb_strlen($data[$c]) > 30 ? "..." : "");
                $founddata[$c] = true;
                }
            }
        $row++;
        }

    return $csv_data;
    }