<?php
include_once dirname(__FILE__) . "/../../include/db.php";
include_once dirname(__FILE__) . "/../../include/image_processing.php";

$cli_short_options = 'hc';
$cli_long_options  = array(
    'help',
    'send-notifications',
    'suppress-output',
    'clearlock'
);

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) != 'cli')
    {
    exit("Command line execution only.");
    }

$send_notification  = false;
$suppress_output    = (isset($staticsync_suppress_output) && $staticsync_suppress_output) ? true : false;

// CLI options check
foreach(getopt($cli_short_options, $cli_long_options) as $option_name => $option_value)
    {
    if(in_array($option_name, array('h', 'help')))
        {
        echo "To clear the lock after a failed run, ";
        echo "pass in '--clearlock'" . PHP_EOL;
        echo 'If you have the configs [$file_checksums=true; $file_upload_block_duplicates=true;] set and would like to have duplicate resource information sent as a notification please run php staticsync.php --send-notifications' . PHP_EOL;
        exit(1);
        }
    if (in_array($option_name, array('clearlock', 'c')) )
        {
        if (is_process_lock("staticsync") )
            {
            clear_process_lock("staticsync");
            }
        }

    if('send-notifications' == $option_name)
        {
        $send_notification = true;
        }
    if('suppress-output' == $option_name)
        {
        $suppress_output = true;
        }    
    }

if(isset($staticsync_userref))
    {
    # If a user is specified, log them in.
    $userref=$staticsync_userref;
    $userdata=get_user($userref);
    $userdata = array($userdata);
    setup_user($userdata[0]);
    }

ob_end_clean();
if($suppress_output)
    {
    ob_start();
    }

set_time_limit(60*60*40);

# Check for a process lock
if (is_process_lock("staticsync")) 
    {
    echo 'Process lock is in place. Deferring.' . PHP_EOL;
    echo 'To clear the lock after a failed run use --clearlock flag.' . PHP_EOL;
    exit();
    }
set_process_lock("staticsync");

// Strip trailing slash if it has been left in
$syncdir=rtrim($syncdir,"/");

echo "Preloading data... ";

// Set options that don't make sense here
$merge_filename_with_title=false;

$count = 0;
$done=array();
$errors = array();
$syncedresources = sql_query("SELECT ref, file_path, file_modified, archive FROM resource WHERE LENGTH(file_path)>0 AND file_path LIKE '%/%'");
foreach($syncedresources as $syncedresource)
    {
    $done[$syncedresource["file_path"]]["ref"]=$syncedresource["ref"];
    $done[$syncedresource["file_path"]]["modified"]=$syncedresource["file_modified"];
    $done[$syncedresource["file_path"]]["archive"]=$syncedresource["archive"];
    }
    
// Set up an array to monitor processing of new alternative files
$alternativefiles=array();

if (isset($numeric_alt_suffixes) && $numeric_alt_suffixes > 0)
    {
	// Add numeric suffixes to $staticsync_alt_suffix_array to support additional suffixes
	$newsuffixarray = array();
    foreach ($staticsync_alt_suffix_array as $suffix => $description)
        {
        $newsuffixarray[$suffix] = $description;
        for ($i = 1; $i < $numeric_alt_suffixes; $i++)
            {
            $newsuffixarray[$suffix . $i] = $description . " (" . $i . ")";
		    }
	    }
	$staticsync_alt_suffix_array = $newsuffixarray;
    }

// Add all the synced alternative files to the list of completed
if(isset($staticsync_alternative_file_text) && (!$staticsync_ingest || $staticsync_ingest_force))
    {
    // Add any staticsynced alternative files to the array so we don't process them unnecessarily
    $syncedalternatives = sql_query("SELECT ref, file_name, resource, creation_date FROM resource_alt_files WHERE file_name like '%" . escape_check($syncdir) . "%'");
    foreach($syncedalternatives as $syncedalternative)
        {
        $shortpath=str_replace($syncdir . '/', '', $syncedalternative["file_name"]);      
        $done[$shortpath]["ref"]=$syncedalternative["resource"];
        $done[$shortpath]["modified"]=$syncedalternative["creation_date"];
        $done[$shortpath]["alternative"]=$syncedalternative["ref"];
        }
    }
    
    

$lastsync = sql_value("SELECT value FROM sysvars WHERE name='lastsync'","");
$lastsync = (strlen($lastsync) > 0) ? strtotime($lastsync) : '';

echo "done." . PHP_EOL;
echo "Looking for changes..." . PHP_EOL;

# Pre-load the category tree, if configured.
if (isset($staticsync_mapped_category_tree))
    {
    $treefield=get_resource_type_field($staticsync_mapped_category_tree);
    migrate_resource_type_field_check($treefield);
    $tree = get_nodes($staticsync_mapped_category_tree,'',TRUE);
    }

function touch_category_tree_level($path_parts)
    {
    # For each level of the mapped category tree field, ensure that the matching path_parts path exists
    global $staticsync_mapped_category_tree, $tree;

    $parent_search = '';
    $nodename      = '';
	$order_by =10;
    $treenodes = array();
    for ($n=0;$n<count($path_parts);$n++)
        {
        $nodename = $path_parts[$n];
        
        echo " - Looking for folder '" . $nodename . "' @ level " . $n  . " in linked metadata field... ";
        # Look for this node in the tree.       
        $found = false;
        foreach($tree as $treenode)
            {
			if($treenode["parent"]==$parent_search)
                {
        		if ($treenode["name"]==$nodename)
					{
					# A match!
					echo "FOUND" . PHP_EOL;
					$found = true;
                    $treenodes[]=$treenode["ref"];
					$parent_search = $treenode["ref"]; # Search for this as the parent node on the pass for the next level.
					}
				else
					{
					if($order_by<=$treenode["order_by"])
						{$order_by=$order_by+10;}
					}
                }			
            }
        if (!$found)
            {
            echo "NOT FOUND. Updating tree field" .PHP_EOL;
            # Add this node
            $newnode=set_node(NULL, $staticsync_mapped_category_tree, $nodename, $parent_search, $order_by);
       	    $tree[]=array("ref"=>$newnode,"parent"=>$parent_search,"name"=>$nodename,"order_by"=>$order_by);
            $parent_search = $newnode; # Search for this as the parent node on the pass for the next level.
            $treenodes[]=$newnode;
            }
        }
    // Return the matching path nodes
    return $treenodes;
    }

function ProcessFolder($folder)
    {
    global $lang, $syncdir, $nogo, $staticsync_max_files, $count, $done, $lastsync, $ffmpeg_preview_extension, 
           $staticsync_autotheme, $staticsync_extension_mapping_default, $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS,
           $staticsync_extension_mapping, $staticsync_mapped_category_tree, $staticsync_title_includes_path, 
           $staticsync_ingest, $staticsync_mapfolders, $staticsync_alternatives_suffix,
           $staticsync_defaultstate, $additional_archive_states, $staticsync_extension_mapping_append_values,
           $staticsync_deleted_state, $staticsync_alternative_file_text, $staticsync_filepath_to_field, 
           $resource_deletion_state, $alternativefiles, $staticsync_revive_state, $enable_thumbnail_creation_on_upload,
           $FIXED_LIST_FIELD_TYPES, $staticsync_extension_mapping_append_values_fields, $view_title_field, $filename_field,
           $staticsync_whitelist_folders,$staticsync_ingest_force,$errors, $category_tree_add_parents,
           $staticsync_alt_suffixes, $staticsync_alt_suffix_array, $staticsync_file_minimum_age, $userref;
    
    $collection = 0;
    $treeprocessed=false;
    
    if(!file_exists($folder))
        {
        echo "Sync folder does not exist: " . $folder . PHP_EOL;
        return false;
        }
    echo "Processing Folder: " . $folder . PHP_EOL;
    
    # List all files in this folder.
    $dh = opendir($folder);
    while (($file = readdir($dh)) !== false)
        {
        if($file == '.' || $file == '..')
            {
            continue;
            }

        $fullpath = "{$folder}/{$file}";
        if(!is_readable($fullpath))
            {
            echo "Warning: File '{$fullpath}' is unreadable!" . PHP_EOL;
            continue;
            }

        $filetype        = filetype($fullpath);
        $shortpath       = str_replace($syncdir . '/', '', $fullpath);
            
        if ($staticsync_mapped_category_tree && !$treeprocessed)
            {
            $path_parts = explode("/", $shortpath);
            array_pop($path_parts);
            $treenodes=touch_category_tree_level($path_parts);
            $treeprocessed=true;
            }

        # -----FOLDERS-------------
        if(
            ($filetype == 'dir' || $filetype == 'link')
            && count($staticsync_whitelist_folders) > 0
            && !isPathWhitelisted($shortpath, $staticsync_whitelist_folders)
        )
            {
            // Folders which are not whitelisted will not be processed any further
            continue;
            }

        if(
            ($filetype == 'dir' || $filetype == 'link')
            && strpos($nogo, "[{$file}]") === false
            && strpos($file, $staticsync_alternatives_suffix) === false
        )
            {
            // Recurse
            ProcessFolder("{$folder}/{$file}");
            }

        # -------FILES---------------
        if (($filetype == "file") && (substr($file,0,1) != ".") && (strtolower($file) != "thumbs.db"))
            {
            if (isset($staticsync_file_minimum_age) && (time() -  filectime($folder . "/" . $file) < $staticsync_file_minimum_age))
                {
                // Don't process this file yet as it is too new
                echo $file . " is too new (" . (time() -  filectime($folder . "/" . $file)) . " seconds), skipping\n";
                continue;
                }

            # Work out extension
            $fileparts  = pathinfo($file);
            $extension  = isset($fileparts["extension"]) ? $fileparts["extension"] : '';
            $filename   = $fileparts["filename"];

            if(isset($staticsync_alternative_file_text) && strpos($file,$staticsync_alternative_file_text)!==false && !$staticsync_ingest_force)
                {
                // Set a flag so we can process this later in case we don't process this along with a primary resource file (it may be a new alternative file for an existing resource)
                $alternativefiles[]=$syncdir . '/' . $shortpath;
                continue;
                }
            elseif(isset($staticsync_alt_suffixes) && $staticsync_alt_suffixes && is_array($staticsync_alt_suffix_array))
                {
                // Check if this is a file with a suffix defined in the $staticsync_alt_suffixes array and then process at the end
                foreach($staticsync_alt_suffix_array as $altsfx=>$altname)
                    {
                    $altsfxlen = mb_strlen($altsfx);
                    $checksfx = substr($filename,-$altsfxlen) == $altsfx;
                    // $ss_nametocheck = substr($file,0,strlen($file)-strlen($extension)-1);
                    if($checksfx == $altsfx)
                        {
                        echo "Adding to \$alternativefiles array " . $file . "\n";
                        $alternativefiles[]=$syncdir . '/' . $shortpath;
                        continue 2;
                        }
                    }
                }

			$modified_extension = hook('staticsync_modify_extension', 'staticsync', array($fullpath, $shortpath, $extension));
			if ($modified_extension !== false) { $extension = $modified_extension; }

            global $banned_extensions, $file_checksums, $file_upload_block_duplicates, $file_checksums_50k;
            # Check to see if extension is banned, do not add if it is banned
            if(array_search($extension, $banned_extensions) !== false)
                {
                continue;
                }

            if ($count > $staticsync_max_files) { return(true); }

            # Already exists or deleted/archived in which case we won't proceed?
            if (!isset($done[$shortpath]))
                {
                // Extra check to make sure we don't end up with duplicates
                $existing=sql_value("SELECT ref value FROM resource WHERE file_path = '" . escape_check($shortpath) . "'",0);
                if($existing>0 || hook('staticsync_plugin_add_to_done'))
                    {
                    $done[$shortpath]["processed"]=true;
                    $done[$shortpath]["modified"]=date('Y-m-d H:i:s',time());
                    continue;
                    }
                # Check for duplicate files
                if($file_upload_block_duplicates)
                    {
                    # Generate the ID
                    if ($file_checksums_50k)
                        {
                        # Fetch the string used to generate the unique ID
                        $use=filesize_unlimited($fullpath) . "_" . file_get_contents($fullpath,null,null,0,50000);
                        $checksum=md5($use);
                        }
                    else
                        {
                        $checksum=md5_file($fullpath);
                        }  
                    $duplicates=sql_array("select ref value from resource where file_checksum='$checksum'");
                    if(count($duplicates)>0)
                        {
                        $message = str_replace("%resourceref%", implode(",",$duplicates), str_replace("%filename%", $fullpath, $lang['error-duplicatesfound']));
                        debug("STATICSYNC ERROR- " . $message);
                        $errors[] = $message;
                        continue;                
                        }
                    }
                $count++;

                echo "Processing file: $fullpath" . PHP_EOL;

                if ($collection == 0 && $staticsync_autotheme)
                    {
                    # Make a new collection for this folder.
                    $e = explode("/", $shortpath);
                    $fallback_fc_categ_name = ucwords($e[0]);
                    $name = (count($e) == 1) ? '' : $e[count($e)-2];
                    echo "Collection '{$name}'" . PHP_EOL;

                    // The real featured collection will always be the last directory in the path
                    $proposed_fc_categories = array_diff($e, array_slice($e, -2));
                    echo "Proposed Featured Collection Categories: " . join(" / ", $proposed_fc_categories) . PHP_EOL;

                    // Build the tree first, if needed
                    $proposed_branch_path = array();
                    for($b = 0; $b < count($proposed_fc_categories); $b++)
                        {
                        $parent = ($b == 0 ? 0 : $proposed_branch_path[($b - 1)]);
                        $fc_categ_name = ucwords($proposed_fc_categories[$b]);

                        $fc_categ_ref_sql = sprintf(
                              "SELECT DISTINCT ref AS `value`
                                 FROM collection AS c
                            LEFT JOIN collection_resource AS cr ON c.ref = cr.collection
                                WHERE `type` = %s
                                  AND parent %s
                                  AND `name` = '%s'
                             GROUP BY c.ref
                               HAVING count(DISTINCT cr.resource) = 0",
                            COLLECTION_TYPE_FEATURED,
                            sql_is_null_or_eq_val($parent, $parent == 0),
                            escape_check($fc_categ_name)
                        );
                        $fc_categ_ref = sql_value($fc_categ_ref_sql, 0);

                        if($fc_categ_ref == 0)
                            {
                            echo "Creating new Featured Collection category named '{$fc_categ_name}'" . PHP_EOL;
                            $fc_categ_ref = create_collection($userref, $fc_categ_name);
                            echo "Created '{$fc_categ_name}' with ref #{$fc_categ_ref}" . PHP_EOL;

                            $updated_fc_category = save_collection(
                                $fc_categ_ref,
                                array(
                                    "featured_collections_changes" => array(
                                        "update_parent" => $parent,
                                        "force_featured_collection_type" => true,
                                        "thumbnail_selection_method" => $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"],
                                    )   
                                ));
                            if($updated_fc_category === false)
                                {
                                echo "Unable to update '{$fc_categ_name}' with ref #{$fc_categ_ref} to a Featured Collection Category" . PHP_EOL;
                                }
                            }

                        $proposed_branch_path[] = $fc_categ_ref;
                        }

                    $collection_parent = array_pop($proposed_branch_path);
                    if(is_null($collection_parent))
                        {
                        // We don't have enough folders to create categories so the first one will do (legacy logic)
                        $collection_parent = create_collection($userref, $fallback_fc_categ_name);
                        save_collection(
                            $collection_parent,
                            array(
                                "featured_collections_changes" => array(
                                    "update_parent" => 0,
                                    "force_featured_collection_type" => true,
                                    "thumbnail_selection_method" => $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"],
                                )   
                            ));
                        }
                    echo "Collection parent should be ref #{$collection_parent}" . PHP_EOL;

                    $collection = sql_value(
                        sprintf(
                              "SELECT DISTINCT ref AS `value`
                                 FROM collection AS c
                            LEFT JOIN collection_resource AS cr ON c.ref = cr.collection
                                WHERE `type` = %s
                                  AND parent %s
                                  AND `name` = '%s'
                             GROUP BY c.ref
                               HAVING count(DISTINCT cr.resource) > 0",
                            COLLECTION_TYPE_FEATURED,
                            sql_is_null_or_eq_val($collection_parent, $collection_parent == 0),
                            escape_check(ucwords($name))
                        ),
                        0);

                    if($collection == 0)
                        {
                        $collection = create_collection($userref, ucwords($name));
                        echo "Created '{$name}' with ref #{$collection}" . PHP_EOL;

                        $updated_fc_category = save_collection(
                            $collection,
                            array(
                                "featured_collections_changes" => array(
                                    "update_parent" => $collection_parent,
                                    "force_featured_collection_type" => true,
                                    "thumbnail_selection_method" => $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"],
                                )   
                            ));
                        if($updated_fc_category === false)
                            {
                            echo "Unable to update '{$name}' with ref #{$collection} to be a Featured Collection under parent ref #{$collection_parent}" . PHP_EOL;
                            }
                        }
                    }

                # Work out a resource type based on the extension.
                $type = $staticsync_extension_mapping_default;
                reset($staticsync_extension_mapping);
                foreach ($staticsync_extension_mapping as $rt => $extensions)
                    {
                    if (in_array($extension,$extensions)) { $type = $rt; }
                    }
                $modified_type = hook('modify_type', 'staticsync', array( $type ));
                if (is_numeric($modified_type)) { $type = $modified_type; }

                # Formulate a title
                if ($staticsync_title_includes_path && $view_title_field!==$filename_field)
                    {
                    $title_find = array('/',   '_', ".$extension" );
                    $title_repl = array(' - ', ' ', '');
                    $title      = ucfirst(str_ireplace($title_find, $title_repl, $shortpath));
                    }
                else
                    {
                    $title = str_ireplace(".$extension", '', $file);
                    }
                $modified_title = hook('modify_title', 'staticsync', array( $title ));
                if ($modified_title !== false) { $title = $modified_title; }

                # Import this file
                $r = import_resource($shortpath, $type, $title, $staticsync_ingest,$enable_thumbnail_creation_on_upload, $extension);
                if ($r !== false)
                    {
                    # Add to mapped category tree (if configured)
                    if (isset($staticsync_mapped_category_tree) && isset($treenodes) && count($treenodes) > 0)
                        {
                        // Add path nodes to resource
                        add_resource_nodes($r,$treenodes);
                        }           

                    # default access level. This may be overridden by metadata mapping.
                    $accessval = 0;

                    # StaticSync path / metadata mapping
                    # Extract metadata from the file path as per $staticsync_mapfolders in config.php
                    if (isset($staticsync_mapfolders))
                        {
                        $field_nodes    = array();
                        foreach ($staticsync_mapfolders as $mapfolder)
                            {
                            $match = $mapfolder["match"];
                            $field = $mapfolder["field"];
                            $level = $mapfolder["level"];

                            if (strpos("/" . $shortpath, $match) !== false)
                                {
                                # Match. Extract metadata.
                                $path_parts = explode("/", $shortpath);
                                if ($level < count($path_parts))
                                    {
                                    // special cases first.
                                    if ($field == 'access')
                                        {
                                        # access level is a special case
                                        # first determine if the value matches a defined access level

                                        $value = $path_parts[$level-1];

                                        for ($n=0; $n<3; $n++){
                                            # if we get an exact match or a match except for case
                                            if ($value == $lang["access" . $n] || strtoupper($value) == strtoupper($lang['access' . $n]))
                                                {
                                                $accessval = $n;
                                                echo "Will set access level to " . $lang['access' . $n] . " ($n)" . PHP_EOL;
                                                }
                                            }

                                        }
                                    else if ($field == 'archive')
										{
										# archive level is a special case
										# first determine if the value matches a defined archive level
										
										$value = $mapfolder["archive"];
										$archive_array=array_merge(array(-2,-1,0,1,2,3),$additional_archive_states);
										
										if(in_array($value,$archive_array))
											{
											$archiveval = $value;
											echo "Will set archive level to " . $lang['status' . $value] . " ($archiveval)". PHP_EOL;
											}
										
										}
                                    else 
                                        {
                                        # Save the value
                                        $value = $path_parts[$level-1];
                                        $modifiedval = hook('staticsync_mapvalue','',array($r, $value));
                                        if($modifiedval)
                                            {
                                            $value = $modifiedval;
                                            }

                                        $field_info=get_resource_type_field($field);
                                        if(in_array($field_info['type'], $FIXED_LIST_FIELD_TYPES))
                                            {
                                            $fieldnodes = get_nodes($field, NULL, $field_info['type'] == FIELD_TYPE_CATEGORY_TREE);

                                            if(in_array($value, array_column($fieldnodes,"name")) || ($field_info['type']==FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && !checkperm('bdk' . $field)))
                                                {
                                                // Add this to array of nodes to add
                                                $newnode = set_node(null, $field, trim($value), null, null, true);
                                                echo "Adding node" . trim($value) . "\n";
                                                
                                                $newnodes = array($newnode);
                                                if($field_info['type']==FIELD_TYPE_CATEGORY_TREE && $category_tree_add_parents) 
                                                    {
                                                    // We also need to add all parent nodes for category trees
                                                    $parent_nodes = get_parent_nodes($newnode);
                                                    $newnodes = array_merge($newnodes,array_keys($parent_nodes));
                                                    }

                                                if($staticsync_extension_mapping_append_values && !in_array($field_info['type'],array(FIELD_TYPE_DROP_DOWN_LIST,FIELD_TYPE_RADIO_BUTTONS)) && (!isset($staticsync_extension_mapping_append_values_fields) || in_array($field_info['ref'], $staticsync_extension_mapping_append_values_fields)))
                                                    {
                                                    // The $staticsync_extension_mapping_append_values variable actually refers to folder->metadata mapping, not the file extension
                                                    $curnodes = get_resource_nodes($r,$field);
                                                    $field_nodes[$field]   = array_merge($curnodes,$newnodes);
                                                    }
                                                else
                                                    {
                                                    // We have got a new value for this field and we are not appending values,
                                                    // replace any existing value the array 
                                                    $field_nodes[$field]   = $newnodes;
                                                    }
                                                }                                            
                                            }
                                        else
                                            {
                                            if($staticsync_extension_mapping_append_values && (!isset($staticsync_extension_mapping_append_values_fields) || in_array($field_info['ref'], $staticsync_extension_mapping_append_values_fields)))
                                                {
                                                $given_value=$value;
                                                // append the values if possible...not used on dropdown, date, category tree, datetime, or radio buttons
                                                if(in_array($field_info['type'],array(0,1,4,5,6,8)))
                                                    {
                                                    $old_value=sql_value("select value value from resource_data where resource=$r and resource_type_field=$field","");
                                                    $value=append_field_value($field_info,$value,$old_value);
                                                    }
                                                }
                                            update_field ($r, $field, $value);

                                            if($staticsync_extension_mapping_append_values && (!isset($staticsync_extension_mapping_append_values_fields) || in_array($field_info['ref'], $staticsync_extension_mapping_append_values_fields)) && isset($given_value))
                                                {
                                                $value=$given_value;
                                                }
                                            }

                                            // If this is a 'joined' field add it to the resource column
                                            $joins = get_resource_table_joins();
                                            if(in_array($field_info['ref'], $joins))
                                                {
                                                sql_query("UPDATE resource SET field{$field_info['ref']} = '" . escape_check(truncate_join_field_value($value)) . "' WHERE ref = '{$r}'");
                                                }
                                        
                                        echo " - Extracted metadata from path: $value for field id # " . $field_info['ref'] . PHP_EOL;
                                        }
                                    }
                                }
                            }
                        if(count($field_nodes)>0)
                            {
                            $nodes_to_add = array();
                            foreach($field_nodes as $field_id=>$nodeids)
                                {
                                $nodes_to_add = array_merge($nodes_to_add,$nodeids);
                                }
                            add_resource_nodes($r,$nodes_to_add);
                            }
                        }
                        
                    if(isset($staticsync_filepath_to_field))
						{
						update_field($r,$staticsync_filepath_to_field,$shortpath);
						}

                    # update access level
                    sql_query("UPDATE resource SET access = '$accessval',archive='$staticsync_defaultstate' " . ((!$enable_thumbnail_creation_on_upload)?", has_image=0, preview_attempts=0 ":"") . " WHERE ref = '$r'");

                    # Add any alternative files
                    $altpath = $fullpath . $staticsync_alternatives_suffix;
                    if ($staticsync_ingest && file_exists($altpath))
                        {
                        $adh = opendir($altpath);
                        while (($altfile = readdir($adh)) !== false)
                            {
                            $filetype = filetype($altpath . "/" . $altfile);
                            if (($filetype == "file") && (substr($file,0,1) != ".") && (strtolower($file) != "thumbs.db"))
                                {
                                # Create alternative file
                                # Find extension
                                $ext = explode(".", $altfile);
                                $ext = $ext[count($ext)-1];
                                
                                $description = str_replace("?", strtoupper($ext), $lang["originalfileoftype"]);
                                $file_size   = filesize_unlimited($altpath . "/" . $altfile);
                                
                                $aref = add_alternative_file($r, $altfile, $description, $altfile, $ext, $file_size);
                                $path = get_resource_path($r, true, '', true, $ext, -1, 1, false, '', $aref);
                                rename($altpath . "/" . $altfile,$path); # Move alternative file
                                }
                            }   
                        }
					elseif(isset($staticsync_alternative_file_text))
						{
						$basefilename=str_ireplace(".$extension", '', $file);
                        $altfilematch = "/{$basefilename}{$staticsync_alternative_file_text}(.*)\.(.*)/";

						echo "Searching for alternative files for base file: " . $basefilename , PHP_EOL; 
						echo "checking " . $altfilematch . PHP_EOL;

                        $folder_files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));
                        $altfiles = new RegexIterator($folder_files, $altfilematch, RecursiveRegexIterator::MATCH);
                        foreach($altfiles as $altfile)
                            {
                            staticsync_process_alt($altfile->getPathname(), $r);
                            echo "Processed alternative: " . $shortpath . PHP_EOL;
                            }
                        }

                    # Add to collection
                    if ($staticsync_autotheme)
                        {
                        // Featured collection categories cannot contain resources. At this stage we need to distinguish
                        // between categories and collections by checking for children collections.
                        if(!is_featured_collection_category_by_children($collection))
                            {
                            $test = sql_query("SELECT * FROM collection_resource WHERE collection='$collection' AND resource='$r'");
                            if(count($test) == 0)
                                {
                                sql_query("INSERT INTO collection_resource (collection, resource, date_added) VALUES ('$collection', '$r', NOW())");
                                }
                            }
                        else
                            {
                            echo "Error: Unable to add resource to a featured collection category!" . PHP_EOL;
                            exit(1);
                            }
                        }

                    $done[$shortpath]["ref"]=$r;
                    $done[$shortpath]["processed"]=true;
                    $done[$shortpath]["modified"]=date('Y-m-d H:i:s',time());
                    update_disk_usage($r);
                    }
                else
                    {
                    # Import failed - file still being uploaded?
                    echo " *** Skipping file - it was not possible to move the file (still being imported/uploaded?)" . PHP_EOL;
                    }
                }
            elseif($staticsync_ingest_force)
                {
                // If the resource has a path but $ingest is true then the $ingest has been changed, need to copy original file into filestore
                global $get_resource_path_fpcache;
                $existing = $done[$shortpath]["ref"];
                $alternative = isset($done[$shortpath]["alternative"]) ? $done[$shortpath]["alternative"] : -1;
                
                echo "File already imported - $shortpath (resource #$existing, alternative #$alternative). Ingesting.." . PHP_EOL;
               
                $get_resource_path_fpcache[$existing] = ""; // Forces get_resource_path to ignore the syncdir file_path
                //$extension=pathinfo($shortpath, PATHINFO_EXTENSION);
                $destination=get_resource_path($existing,true,"",true,$extension,-1,1,false,"",$alternative);
                $result=rename($syncdir . "/" . $shortpath,$destination);
                if ($result===false)
                    {
                    # The rename failed. Log an error and continue}
                    $errors[] = "Unable to move resource " . $existing . " from " . $syncdir . DIRECTORY_SEPARATOR . $shortpath  . " to " . $destination;
					}
                else
                    {
                    chmod($destination,0777);
                    if($alternative == -1)
                        {
                        sql_query("UPDATE resource SET file_path=NULL WHERE ref = '{$existing}'");
                        }
                    else
                        {
                        sql_query("UPDATE resource_alt_files SET file_name = '" . escape_check($file) . "' WHERE resource = '{$existing}' AND ref='{$alternative}'");                            
                        }
                    }
                }
            elseif (!isset($done[$shortpath]["archive"]) // Check modified times and and update previews if no existing archive state is set,
                    || (isset($resource_deletion_state) && $done[$shortpath]["archive"]!=$resource_deletion_state) // or if resource is not in system deleted state,
                    || (isset($staticsync_revive_state) && $done[$shortpath]["archive"]==$staticsync_deleted_state)) // or resource is currently in staticsync deleted state and needs to be reinstated
                {
                if(!file_exists($fullpath))
                    {
                    echo "Warning: File '{$fullpath}' does not exist anymore!";
                    continue;
                    }

                $filemod = filemtime($fullpath);
                if (isset($done[$shortpath]["modified"]) && $filemod > strtotime($done[$shortpath]["modified"]) || (isset($staticsync_revive_state) && $done[$shortpath]["archive"]==$staticsync_deleted_state))
                    {
                    
                    $count++;
                    # File has been modified since we last created previews. Create again.
                    $rd = sql_query("SELECT ref, has_image, file_modified, file_extension, archive FROM resource 
                                        WHERE file_path='" . escape_check($shortpath) . "'");
                    if (count($rd) > 0)
                        {
                        $rd   = $rd[0];
                        $rref = $rd["ref"];

                        echo "Resource $rref has changed, regenerating previews: $fullpath" . PHP_EOL;
                        extract_exif_comment($rref,$rd["file_extension"]);

                        # extract text from documents (e.g. PDF, DOC).
                        global $extracted_text_field;
                        if(isset($extracted_text_field))
                            {
                            if(isset($unoconv_path) && in_array($extension, $unoconv_extensions))
                                {
                                // omit, since the unoconv process will do it during preview creation below
                                }
                            else
                                {
                                global $offline_job_queue, $offline_job_in_progress;
                                if($offline_job_queue && !$offline_job_in_progress)
                                    {
                                    $extract_text_job_data = array(
                                        'ref'       => $rref,
                                        'extension' => $extension,
                                    );

                                    job_queue_add('extract_text', $extract_text_job_data);
                                    }
                                else
                                    {
                                    extract_text($rref, $extension);
                                    }
                                }
                            }

                        # Store original filename in field, if set
                        global $filename_field;
                        if (isset($filename_field))
                            {
                            update_field($rref,$filename_field,$file);  
                            }
                        if($enable_thumbnail_creation_on_upload)
                            {
                            create_previews($rref, false, $rd["file_extension"], false, false, -1, false, $staticsync_ingest);
                            }
                        sql_query("UPDATE resource SET file_modified=NOW() " . ((isset($staticsync_revive_state) && ($rd["archive"]==$staticsync_deleted_state))?", archive='" . $staticsync_revive_state . "'":"") . ((!$enable_thumbnail_creation_on_upload)?", has_image=0, preview_attempts=0 ":"") . " WHERE ref='$rref'");

                        if(isset($staticsync_revive_state) && ($rd["archive"]==$staticsync_deleted_state))
                            {
                            # Log this
                            resource_log($rref,LOG_CODE_STATUS_CHANGED,'','',$staticsync_deleted_state,$staticsync_revive_state);
                            }
                        }
                    }
                }
            }   
        }
        closedir($dh);
    }
    
function staticsync_process_alt($alternativefile, $ref="", $alternative="")
    {
    // Process an alternative file
    global $staticsync_alternative_file_text, $syncdir, $lang, $staticsync_ingest, $alternative_file_previews,
    $done, $filename_field, $view_title_field, $staticsync_title_includes_path, $staticsync_alt_suffixes, $staticsync_alt_suffix_array;
	
    $shortpath = str_replace($syncdir . '/', '', $alternativefile);
	if(!isset($done[$shortpath]))
		{
        $alt_parts=pathinfo($alternativefile);

        if (substr($alt_parts['filename'],0,1) == ".")
            {
            return false;
            }
        
        if(isset($staticsync_alternative_file_text) && strpos($alternativefile,$staticsync_alternative_file_text) !== false)
		    {
            $altfilenameparts = explode($staticsync_alternative_file_text,$alt_parts['filename']);
            $altbasename=$altfilenameparts[0];
            $altdesc = $altfilenameparts[1];
            $altname = str_replace("?", strtoupper($alt_parts["extension"]), $lang["fileoftype"]);
            }
        elseif(isset($staticsync_alt_suffixes) && $staticsync_alt_suffixes && is_array($staticsync_alt_suffix_array))
            {
            // Check for files with a suffix defined in the $staticsync_alt_suffixes array
            foreach($staticsync_alt_suffix_array as $altsfx=>$altname)
                {
                $altsfxlen = mb_strlen($altsfx);
                if(substr($alt_parts['filename'],-$altsfxlen) == $altsfx)
                    {
                    $altbasename = substr($alt_parts['filename'],0,-$altsfxlen);
                    $altdesc = strtoupper($alt_parts['extension']) . " " . $lang["file"];
                    break;
                    }
                }
            }
        
		if($ref=="")
			{
			// We need to find which resource this alternative file relates to
			echo "Searching for primary resource related to " . $alternativefile . "  in " . $alt_parts['dirname'] . '/' . $altbasename . "." .  PHP_EOL;
			foreach($done as $syncedfile=>$synceddetails)
				{
                $syncedfile_parts=pathinfo($syncedfile);
                if(strpos($syncdir . '/' . $syncedfile,$alt_parts['dirname'] . '/' . $altbasename . ".")!==false
                || (isset($altsfx) && $syncdir . '/' . $syncedfile_parts["filename"] . $altsfx . "." . $syncedfile_parts["extension"] ==  $alternativefile))
					{
					// This synced file has the same base name as the resource
					$ref= $synceddetails["ref"];
					break;
					}
				}
			}
        
        if($ref=="")
            {
            //Primary resource file may have been ingested on a previous run - try to locate it
            $ingested = sql_array("SELECT resource value FROM resource_data WHERE resource_type_field=" . $filename_field . " AND value LIKE '" . $altbasename . "%'");
            
            if(count($ingested) < 1)
                {
                echo "No primary resource found for " . $alternativefile . ". Skipping file" . PHP_EOL;
                debug("staticsync - No primary resource found for " . $alternativefile . ". Skipping file");
                return false;
                }
            
            if(count($ingested) == 1)
                {
                echo "Found matching resource: " . $ingested[0] . PHP_EOL;
                $ref = $ingested[0];
                }
            else
                {
                if($staticsync_title_includes_path)
                    {
                    $title_find = array('/',   '_');
                    $title_repl = array(' - ', ' ');
                    $parentpath = ucfirst(str_ireplace($title_find, $title_repl, $shortpath));

                    echo "This file has path: " . $parentpath . PHP_EOL;
                    foreach($ingested as $ingestedref)
                        {
                        $ingestedpath = get_data_by_field($ingestedref, $view_title_field);
                        echo "Found resource with same name. Path: " . $ingestedpath . PHP_EOL;
                        if(strpos($parentpath,$ingestedpath) !== false)
                            {
                           echo "Found matching resource: " . $ingestedref . PHP_EOL;
                            $ref = $ingestedref;
                            break;
                            }
                        }
                    }
                if($ref=="")
                    {
                    echo "Multiple possible primary resources found for " . $alternativefile . ". (Resource IDs: " . implode(",",$ingested) . "). Skipping file" . PHP_EOL;
                    debug("staticsync - Multiple possible primary resources found for " . $alternativefile . ". (Resource IDs: " . implode(",",$ingested) . "). Skipping file");
                    return false;
                    }
                }
            }
         
        echo "Processing alternative file - '" . $alternativefile . "' for resource #" . $ref . PHP_EOL;
		
		if($alternative=="")
			{
            // Create a new alternative file
            $alt["file_size"]   = filesize_unlimited($alternativefile);
            $alt["extension"] = $alt_parts["extension"];                            
            $alt["altdescription"]  = $altdesc;
            $alt["name"]            = $altname;            

			$alt["ref"] = add_alternative_file($ref, $alt["name"], $alt["altdescription"], $alternativefile, $alt["extension"], $alt["file_size"]);
            $alternative = $alt["ref"];
			
			echo "Created a new alternative file - '" . $alt["ref"] . "' for resource #" . $ref . PHP_EOL;
            debug("Staticsync - Created a new alternative file - '" . $alt["ref"] . "' for resource #" . $ref);
			$alt["path"] = get_resource_path($ref, true, '', false, $alt["extension"], -1, 1, false, '',  $alt["ref"]);
			echo "- alternative file path - " . $alt["path"] . PHP_EOL;
            debug("Staticsync - alternative file path - " . $alt["path"]);
			$alt["basefilename"] = $altbasename;
			if($staticsync_ingest)
				{
				echo "- moving file to " . $alt["path"] . PHP_EOL;
				rename($alternativefile,$alt["path"]); # Move alternative file
				}
			if ($alternative_file_previews)
				{create_previews($ref,false,$alt["extension"],false,false,$alt["ref"],false, $staticsync_ingest);}
			hook("staticsync_after_alt", '',array($ref,$alt));
			echo "Added alternative file ref:"  . $alt["ref"] . ", name: " . $alt["name"] . ". " . "(" . $alt["altdescription"] . ") Size: " . $alt["file_size"] . PHP_EOL;
            debug("Staticsync - added alternative file ref:"  . $alt["ref"] . ", name: " . $alt["name"] . ". " . "(" . $alt["altdescription"] . ") Size: " . $alt["file_size"]);
            $done[$shortpath]["processed"]=true;
			}  
		}
    elseif($alternative!="" && $alternative_file_previews)
        {
        // An existing alternative file has changed, update previews if required
        debug("Alternative file changed, recreating previews");
		create_previews($ref, false,  pathinfo($alternativefile, PATHINFO_EXTENSION), false, false, $alternative, false, $staticsync_ingest);
        sql_query("UPDATE resource_alt_files SET creation_date=NOW() WHERE ref='$alternative'"); 
        $done[$shortpath]["processed"]=true;           
        }	
	echo "Completed path : " . $shortpath . PHP_EOL;
	$done[$shortpath]["ref"]=$ref;
    $done[$shortpath]["alternative"]=$alternative;
    set_process_lock("staticsync"); // Update the lock so we know it is still processing resources
    }

# Recurse through the folder structure.
ProcessFolder($syncdir);

debug("StaticSync: \$done = " . json_encode($done));

// Look for alternative files that may have not been processed
foreach($alternativefiles as $alternativefile)
    {
    $shortpath = str_replace($syncdir . "/", '', $alternativefile);
    echo "Processing alternative file " . $shortpath . PHP_EOL;
    debug("Staticsync -  Processing altfile " . $shortpath);

    if(array_key_exists($shortpath, $done) && isset($done[$shortpath]["alternative"]) && $done[$shortpath]["alternative"] > 0)
        {
        echo "Alternative '{$shortpath}' has already been processed. Skipping" . PHP_EOL;
        continue;
        }

    if(!file_exists($alternativefile))
        {
        echo "Warning: File '{$alternativefile}' does not exist anymore!";
        continue;
        }

    if (!isset($done[$shortpath]))
        {
        staticsync_process_alt($alternativefile);        
        }
    elseif($alternative_file_previews)
        {
        // File already synced but check if it has been modified as may need to update previews
        $altfilemod = filemtime($alternativefile);
        if (isset($done[$shortpath]["modified"]) && $altfilemod > strtotime($done[$shortpath]["modified"]))
            {
            // Update the alternative file
            staticsync_process_alt($alternativefile,$done[$shortpath]["resource"],$done[$shortpath]["alternative"]);
            }
        }
    }

echo "...done." . PHP_EOL;

if (!$staticsync_ingest)
    {
    # If not ingesting files, look for deleted files in the sync folder and archive the appropriate file from ResourceSpace.
    echo "Looking for deleted files..." . PHP_EOL;
    # For all resources with filepaths, check they still exist and archive if not.
    //$resources_to_archive = sql_query("SELECT ref,file_path FROM resource WHERE archive=0 AND LENGTH(file_path)>0 AND file_path LIKE '%/%'");
    $resources_to_archive =array();
    $n=0;
    foreach($done as $syncedfile=>$synceddetails)    
        {
        if(!isset($synceddetails["processed"]) && isset($synceddetails["archive"]) && !(isset($staticsync_ignore_deletion_states) && in_array($synceddetails["archive"],$staticsync_ignore_deletion_states)) && $synceddetails["archive"]!=$staticsync_deleted_state || isset($synceddetails["alternative"]))
            {
            $resources_to_archive[$n]["file_path"]=$syncedfile;
            $resources_to_archive[$n]["ref"]=$synceddetails["ref"];
            $resources_to_archive[$n]["archive"]=isset($synceddetails["archive"])?$synceddetails["archive"]:"";
            if(isset($synceddetails["alternative"]))
                {$resources_to_archive[$n]["alternative"]=$synceddetails["alternative"];}
            $n++;
            }
        }
        
    # ***for modified syncdir directories:
    $syncdonemodified = hook("modifysyncdonerf");
    if (!empty($syncdonemodified)) { $resources_to_archive = $syncdonemodified; }

    // Get all the featured collections (including categories) that hold these resources
    $fc_branches = get_featured_collections_by_resources(array_column($resources_to_archive, "ref"));

    foreach ($resources_to_archive as $rf)
        {
        $fp = $syncdir . '/' . $rf["file_path"];
        if (isset($rf['syncdir']) && $rf['syncdir'] != '')
               {
               # ***for modified syncdir directories:
               $fp = $rf['syncdir'].$rf["file_path"];
               }
         
        if ($fp!="" && !file_exists($fp))
            {
			// Additional check - make sure the archive state hasn't changed since the start of the script
			$cas=sql_value("SELECT archive value FROM resource where ref='{$rf["ref"]}'",0);
			if(isset($staticsync_ignore_deletion_states) && !in_array($cas,$staticsync_ignore_deletion_states))
				{
				if(!isset($rf["alternative"]))
					{
					echo "File no longer exists: " . $rf["ref"] . " " . $fp . PHP_EOL;
					# Set to archived, unless state hasn't changed since script started.
					if (isset($staticsync_deleted_state))
						{
						sql_query("UPDATE resource SET archive='" . $staticsync_deleted_state . "' WHERE ref='{$rf["ref"]}'");
						}
					else
						{
						delete_resource($rf["ref"]);
						}
					if(isset($resource_deletion_state) && $staticsync_deleted_state==$resource_deletion_state)
						{
						// Only remove from collections if we are really deleting this. Some configurations may have a separate state or synced resources may be temporarily absent
						sql_query("DELETE FROM collection_resource WHERE resource='{$rf["ref"]}'");
						}
					# Log this
					resource_log($rf['ref'],LOG_CODE_STATUS_CHANGED,'','',$rf["archive"],$staticsync_deleted_state);
					} 
				else
					{
					echo "Alternative file no longer exists: resource " . $rf["ref"] . " alt:" . $rf["alternative"] . " " . $fp . PHP_EOL;
					sql_query("DELETE FROM resource_alt_files WHERE ref='" . $rf["alternative"] . "'");
					}
				}
            }
        }

    # Remove any themes that are now empty as a result of deleted files.
    foreach($fc_branches as $fc_branch)
        {
        // Reverse the branch path to start from the leaf node. This way, when you reach the category you won't have any
        // children nodes (ie a normal FC) left (if it will be the case) and we'll be able to delete the FC category.
        $reversed_branch_path = array_reverse($fc_branch);
        foreach($reversed_branch_path as $fc)
            {
            if(!can_delete_featured_collection($fc["ref"]))
                {
                continue;
                }

            if(delete_collection($fc["ref"]) === false)
                {
                echo "Unable to delete featured collection #{$fc["ref"]}" . PHP_EOL;
                }
            else
                {
                echo "Deleted featured collection #{$fc["ref"]}" . PHP_EOL;
                }
            }
        }
    }

if(count($errors) > 0)
    {
    echo PHP_EOL . "ERRORS: -" . PHP_EOL;
    echo implode(PHP_EOL,$errors) . PHP_EOL;
    if ($send_notification)
        {
        $notify_users = get_notification_users("SYSTEM_ADMIN");
        foreach($notify_users as $notify_user)
            {
            $admin_notify_users[]=$notify_user["ref"];
            }
        $message = "STATICSYNC ERRORS FOUND: - " . PHP_EOL . implode(PHP_EOL,$errors);
        message_add($admin_notify_users,$message);
        }
    }
        
echo "...Complete" . PHP_EOL;

if($suppress_output)
    {
    ob_clean();
    }

sql_query("UPDATE sysvars SET value=now() WHERE name='lastsync'");

clear_process_lock("staticsync");

?>

