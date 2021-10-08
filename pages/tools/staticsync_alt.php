<?php
// WARNING - DEPRECATED
// Functionality provided by this file has been moved into staticsync.php as of version 9.3. Page kept here for reference
// Any custom options set here should be moved to config.php i.e.
// $staticsync_alt_suffix_array
// $staticsync_alt_suffixes
// $numeric_alt_suffixes
// $file_minimum_age 

if('cli' != PHP_SAPI)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Command line execution only');
    }

include dirname(__FILE__) . "/../../include/db.php";

include dirname(__FILE__) . "/../../include/image_processing.php";

if (file_exists(dirname(__FILE__) . "/staticsync_local_functions.php")){
	include(dirname(__FILE__) . "/staticsync_local_functions.php");
}

if ($staticsync_ingest){
	echo date('Y-m-d H:i:s    ');
	echo "Staticsync is running in ingest mode.\n";
} else {
	echo date('Y-m-d H:i:s    ');
	echo "Staticsync is running in sync mode.\n";
}

$staticsync_alt_suffix_array = array('_alt','_verso','_DNG','_VERSO','_ALT','_dng','_orig','_ORIG','_tp','_TP','_tpv','_TPV','_cov','_COV','_ex','_EX','_scr','_SCR');
$staticsync_alt_suffixes = true;
$numeric_alt_suffixes = 8;
$file_minimum_age = 120; // don't touch files that aren't at least this many seconds old


if ($numeric_alt_suffixes > 0){
	// add numeric suffixes to alt suffix list if we've been told to do that.
	$newsuffixarray = array();
	foreach ($staticsync_alt_suffix_array as $thesuffix){
		array_push($newsuffixarray,$thesuffix);
		for ($i = 1; $i < $numeric_alt_suffixes; $i++){
			array_push($newsuffixarray,$thesuffix.$i);
		}
	}
	$staticsync_alt_suffix_array = $newsuffixarray;
}

// create a timestamp for this run to help someone find all the files later
$staticsync_run_timestamp = "SSTS" . time();
echo date('Y-m-d H:i:s    ');
echo "Timestamp for this run is $staticsync_run_timestamp\n";

set_time_limit(60*60*40);

if(isset($staticsync_userref))
    {
    # If a user is specified, log them in.
    $userref=$staticsync_userref;
    $userdata=get_user($userref);
    setup_user($userdata);
    }

if ($argc == 2)
    {
    if ( in_array($argv[1], array('--help', '-help', '-h', '-?')) )
        {
        echo "To clear the lock after a failed run, ";
        echo "pass in '--clearlock', '-clearlock', '-c' or '--c'." . PHP_EOL;
        exit("Bye!");
        }
    else if ( in_array($argv[1], array('--clearlock', '-clearlock', '-c', '--c')) )
        {
        if ( is_process_lock("staticsync") )
            {
            clear_process_lock("staticsync");
            }
        }
    else
        {
        exit("Unknown argv: " . $argv[1]);
        }
    }
	
# Check for a process lock
if (is_process_lock("staticsync")) {
echo date('Y-m-d H:i:s    ');
	echo "Process lock found. Deferring.";
	exit("Process lock is in place. Deferring.");
	}
set_process_lock("staticsync");

echo date('Y-m-d H:i:s    ');
echo "Preloading data...";
$max=350;
$count=0;

$done=sql_array("select file_path value from resource where archive=0 and length(file_path)>0 and file_path like '%/%'");

# Load all modification times into an array for speed
$modtimes=array();
$rd=sql_query("select ref,file_modified,file_path from resource where archive=0 and length(file_path)>0");
for ($n=0;$n<count($rd);$n++)
	{
	$modtimes[$rd[$n]["file_path"]]=$rd[$n]["file_modified"];
	}

$lastsync=sql_value("select value from sysvars where name='lastsync'","");
if (strlen($lastsync)>0) {$lastsync=strtotime($lastsync);} else {$lastsync="";}


echo "...done. Looking for changes...";

# Pre-load the category tree, if configured.
if (isset($staticsync_mapped_category_tree))
	{
	$fielddata=get_resource_type_field($staticsync_mapped_category_tree);
	migrate_resource_type_field_check($fielddata);
	$tree = get_nodes($staticsync_mapped_category_tree, NULL, true);
   	}


function touch_category_tree_level($path_parts)
	{
	# For each level of the mapped category tree field, ensure that the matching path_parts path exists
	global $staticsync_mapped_category_tree,$tree;

	$parent_search='';
	$nodename="";
	$order_by =10;
	
	for ($n=0;$n<count($path_parts);$n++)
		{
		# The node name should contain all the subsequent parts of the path
        if ($n > 0) { $nodename .= "~"; }
        $nodename .= $path_parts[$n];
		
		# Look for this node in the tree.		
		$found = false;		
        foreach($tree as $treenode)
            {
			if($treenode["parent"]==$parent_search)
                {
				if ($treenode["name"]==$nodename)
					{
					# A match!
					$found = true;
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
            echo "Not found: " . $nodename . " @ level " . $n  . PHP_EOL;
            # Add this node
            $newnode=set_node(NULL, $staticsync_mapped_category_tree, $nodename, $parent_search, $order_by);
			$tree[]=array("ref"=>$newnode,"parent"=>$parent_search,"name"=>$nodename,"order_by"=>$order_by);
            $parent_search = $newnode; # Search for this as the parent node on the pass for the next level.
            }
		}	
    // Return the last found node ref, we will use this in phase 2 of nodes work to save node ref instead of string
    return $parent_search;
	}


function ProcessFolder($folder)
	{
    global $syncdir,$nogo,$max,$count,$done,$modtimes,$lastsync, $ffmpeg_preview_extension;
    global $staticsync_autotheme, $staticsync_extension_mapping_default, $staticsync_extension_mapping;
    global $staticsync_mapped_category_tree,$staticsync_title_includes_path, $staticsync_ingest;
    global $staticsync_mapfolders,$staticsync_alternatives_suffix,$staticsync_alt_suffixes;
    global $staticsync_alt_suffix_array,$file_minimum_age,$staticsync_run_timestamp, $view_title_field, $filename_field;
    global $resource_deletion_state, $alternativefiles, $staticsync_revive_state, $enable_thumbnail_creation_on_upload,
           $FIXED_LIST_FIELD_TYPES, $staticsync_extension_mapping_append_values, $staticsync_extension_mapping_append_values_fields, $view_title_field, $filename_field,
           $staticsync_whitelist_folders,$staticsync_ingest_force,$errors, $category_tree_add_parents;
	
	$collection=0;
	
	echo "Processing Folder: $folder\n";
	
	# List all files in this folder.
	$dh=opendir($folder);
 	echo date('Y-m-d H:i:s    ');
	echo "Reading from $folder\n";
	while (($file = readdir($dh)) !== false)
		{
                // because of alternative processing, some files may disappear during the run
                // that's ok - just ignore it and move on
                if (!file_exists($folder . "/" . $file)){
	  		echo date('Y-m-d H:i:s    ');
			echo "File $file missing. Moving on.\n";
			continue;
		}



		$filetype=filetype($folder . "/" . $file);
		$fullpath=$folder . "/" . $file;
		$shortpath=str_replace($syncdir . "/","",$fullpath);
		
		if ($staticsync_mapped_category_tree)
			{
			$path_parts=explode("/",$shortpath);
			array_pop($path_parts);
			touch_category_tree_level($path_parts);
			}	
		
		# -----FOLDERS-------------
		if ((($filetype=="dir") || $filetype=="link") && ($file!=".") && ($file!="..") && (strpos($nogo,"[" . $file . "]")===false) && strpos($file,$staticsync_alternatives_suffix)===false)
			{
			# Recurse
			#echo "\n$file : " . filemtime($folder . "/" . $file) . " > " . $lastsync;
			if (true || (strlen($lastsync)=="") || (filemtime($folder . "/" . $file)>($lastsync-26000)))
				{
				ProcessFolder($folder . "/" . $file);
				}
			}
			
		# -------FILES---------------
		if (($filetype=="file") && (substr($file,0,1)!=".") && (strtolower($file)!="thumbs.db") && !ss_is_alt($file))
			{

                    // we want to make sure we don't touch files that are too new
                    // so check this

                        if (time() -  filectime($folder . "/" . $file) < $file_minimum_age){
			    echo date('Y-m-d H:i:s    ');
                            echo "   $file too new -- skipping .\n";
                            //echo filectime($folder . "/" . $file) . " " . time() . "\n";
                            continue;
                        }

			# Already exists?
			if (!in_array($shortpath,$done))
				{
				$count++;if ($count>$max) {return(true);}
				echo date('Y-m-d H:i:s    ');
				echo "Processing file: $fullpath\n";
				
				if ($collection==0 && $staticsync_autotheme)
					{
					# Make a new collection for this folder.
					$e=explode("/",$shortpath);
					$theme=ucwords($e[0]);
					$name=(count($e)==1?"":$e[count($e)-2]);
					echo date('Y-m-d H:i:s    ');
					echo "\nCollection $name, theme=$theme";
					$collection=sql_value("select ref value from collection where name='" . escape_check($name) . "' and theme='" . escape_check($theme) . "'",0);
					if ($collection==0)
						{
						sql_query("insert into collection (name,created,public,theme,allow_changes) values ('" . escape_check($name) . "',now(),1,'" . escape_check($theme) . "',0)");
						$collection=sql_insert_id();
						}
					}

				# Work out extension
				$extension=explode(".",$file);$extension=trim(strtolower($extension[count($extension)-1]));

                                // if coming from collections or la folders, assume these are the resource types
                                if (stristr(strtolower($fullpath),'collection services/curatorial')){
                                    $type = 5;
                                } elseif (stristr(strtolower($fullpath),'collection services/conservation')){
                                    $type = 5;
                                } elseif (stristr(strtolower($fullpath),'collection services/library_archives')){
                                    $type = 6;
                                } else {

                                # Work out a resource type based on the extension.
				$type=$staticsync_extension_mapping_default;
				reset ($staticsync_extension_mapping);
				foreach ($staticsync_extension_mapping as $rt=>$extensions)
					{
                                        if ($rt == 5 or $rt == 6){continue;} // we already eliminated those
					if (in_array($extension,$extensions)) {$type=$rt;}
					}
                                }
				
				# Formulate a title
				if ($staticsync_title_includes_path && $view_title_field!==$filename_field)
					{
					$title=str_ireplace("." . $extension,"",str_replace("/"," - ",$shortpath));
					$title=ucfirst(str_replace("_"," ",$title));
					}
				else
					{
					$title=str_ireplace("." . $extension,"",$file);
					}
				
				# Import this file
                $r = import_resource($shortpath, $type, $title, $staticsync_ingest,$enable_thumbnail_creation_on_upload, $extension);
				if ($r!==false)
					{
					# Add to mapped category tree (if configured)
                    if (isset($staticsync_mapped_category_tree))
                        {
                        $basepath = '';
                        # Save tree position to category tree field

                        # For each node level, expand it back to the root so the full path is stored.
                        for ($n=0;$n<count($path_parts);$n++)
                            {
                            if ($basepath != '') 
                                { 
                                $basepath .= "~";
                                }
                            $basepath .= $path_parts[$n];
                            $path_parts[$n] = $basepath;
                            }

                        # Save tree position to category tree field                        
                        update_field($r, $staticsync_mapped_category_tree, "," . join(",", $path_parts));
                        } 			
					
					# StaticSync path / metadata mapping
					# Extract metadata from the file path as per $staticsync_mapfolders in config.php
					if (isset($staticsync_mapfolders))
						{
                        $field_nodes    = array();
                        foreach ($staticsync_mapfolders as $mapfolder)
                            {
                            $match=$mapfolder["match"];
                            $field=$mapfolder["field"];
                            $level=$mapfolder["level"];
							
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
                                                $newnode = set_node(null, $field, trim($value), null, null);
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
                                                if(in_array($field['type'],array(0,1,4,5,6,8)))
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

                                        // add the timestamp from this run to the keywords field to help retrieve this batch later
                                        $currentkeywords = sql_value("select value from resource_data where resource = '$r' and resource_type_field = '1'","");
					if (strlen($currentkeywords) > 0){
						$currentkeywords .= ',';
					}
					update_field($r,1,$currentkeywords.$staticsync_run_timestamp);

					if (function_exists('staticsync_local_functions')){
						// if local cleanup functions have been defined, run them
						staticsync_local_functions($r);
					}

					# Add any alternative files
					$altpath=$fullpath . $staticsync_alternatives_suffix;
					if ($staticsync_ingest && file_exists($altpath))
						{
						$adh=opendir($altpath);
						while (($altfile = readdir($adh)) !== false)
							{
							$filetype=filetype($altpath . "/" . $altfile);
							if (($filetype=="file") && (substr($file,0,1)!=".") && (strtolower($file)!="thumbs.db"))
								{
								# Create alternative file
								global $lang;
								
								# Find extension
								$ext=explode(".",$altfile);$ext=$ext[count($ext)-1];
								
								$aref = add_alternative_file($r, $altfile, strtoupper($ext) . " " . $lang["file"], $altfile, $ext, filesize_unlimited($altpath . "/" . $altfile));
								$path=get_resource_path($r, true, "", true, $ext, -1, 1, false, "", $aref);
								rename ($altpath . "/" . $altfile,$path); # Move alternative file
								}
							}	
						}
					
                                        
                                        # check for alt files that match suffix list
					if ($staticsync_alt_suffixes){

                                            $ss_nametocheck = substr($file,0,strlen($file)-strlen($extension)-1);
                                            //review all files still in directory and see if they are alt files matching this one
                                            	$althandle=opendir($folder);
                                                while (($altcandidate = readdir($althandle)) !== false){
                                                    if (($filetype=="file") && (substr($file,0,1)!=".") && (strtolower($file)!="thumbs.db")){
                                                        # Find extension
                                                        $ext=explode(".",$altcandidate);$ext=$ext[count($ext)-1];
                                                        $altcandidate_name = substr($altcandidate,0,strlen($altcandidate)-strlen($ext)-1);
                                                        $altcandidate_validated = false;
                                                        foreach ($staticsync_alt_suffix_array as $sssuffix){
                                                            if ($altcandidate_name == $ss_nametocheck.$sssuffix){
                                                                $altcandidate_validated = true;
								$thisfilesuffix = $sssuffix;
                                                                break;
                                                            }
                                                        }
                                                        if ($altcandidate_validated){
                                                            echo date('Y-m-d H:i:s    ');
							    echo "    Attaching $altcandidate as alternative.\n";
                                                            $filetype=filetype($folder."/".$altcandidate);
                                                            # Create alternative file
                                                            global $lang;
							
								if (preg_match("/^_VERSO[0-9]*/i",$thisfilesuffix)){
									$alt_title = "Verso";
								} elseif(preg_match("/^_DNG[0-9]*/i",$thisfilesuffix)){
									$alt_title = "DNG";
								} elseif(preg_match("/^_ORIG[0-9]*/i",$thisfilesuffix)){
									$alt_title = "Original Scan";
								} elseif(preg_match("/^_TPV[0-9]*/i",$thisfilesuffix)){
									$alt_title = "Title Page Verso";
								} elseif(preg_match("/^_TP[0-9]*/i",$thisfilesuffix)){
									$alt_title = "Title Page";
								} elseif(preg_match("/^_COV[0-9]*/i",$thisfilesuffix)){
									$alt_title = "Cover";
								} elseif(preg_match("/^_SCR[0-9]*/i",$thisfilesuffix)){
									$alt_title = "Inscription";
								} elseif(preg_match("/^_EX[0-9]*/i",$thisfilesuffix)){
									$alt_title = "Enclosure";
								} else {
									$alt_title = $altcandidate;
								}

                                                            $aref = add_alternative_file($r, $alt_title, strtoupper($ext) . " " . $lang["file"], $altcandidate, $ext, filesize_unlimited($folder."/".$altcandidate));
                                                            $path=get_resource_path($r, true, "", true, $ext, -1, 1, false, "", $aref);
                                                            rename ($folder."/".$altcandidate,$path); # Move alternative file

                                                            global $alternative_file_previews;
                                                            if ($alternative_file_previews)
                                                                    {
                                                                    create_previews($r,false,$ext,false,false,$aref);
                                                                    }

                                                        }
                                                    }
                                                }		
                                        }
                                                
					# Add to collection
					if ($staticsync_autotheme)
						{
						sql_query("insert into collection_resource(collection,resource,date_added) values ('$collection','$r',now())");
						}

                                        // fix permissions
                                                
                                        // get directory to fix
                                           global $scramble_key;
                                           $permfixfolder = "/hne/rs/filestore/";
                                           for ($n=0;$n<strlen($r);$n++){
                                            $permfixfolder.=substr($r,$n,1);
                                            if ($n==(strlen($r)-1)) {$permfixfolder.="_" . substr(md5($r . "_" . $scramble_key),0,15);}
                                            $permfixfolder.="/";
                                          }


                                        exec("/bin/chown -R wwwrun $permfixfolder");
                                        exec("/bin/chgrp -R www $permfixfolder");


					}
				else
					{
					# Import failed - file still being uploaded?
					echo date('Y-m-d H:i:s    ');
					echo " *** Skipping file - it was not possible to move the file (still being imported/uploaded?) \n";
					}
				}
			else
				{
				# check modified date and update previews if necessary
				$filemod=filemtime($fullpath);
				if (array_key_exists($shortpath,$modtimes) && ($filemod>strtotime($modtimes[$shortpath])))
					{
					# File has been modified since we last created previews. Create again.
					$rd=sql_query("select ref,has_image,file_modified,file_extension from resource where file_path='" . (escape_check($shortpath)) . "'");
					if (count($rd)>0)
						{
						$rd=$rd[0];
						$rref=$rd["ref"];

						echo date('Y-m-d H:i:s    ');
						echo "Resource $rref has changed, regenerating previews: $fullpath\n";
						create_previews($rref,false,$rd["file_extension"]);
						sql_query("update resource set file_modified=now() where ref='$rref'");
						}
					}
				}
			}	
		}
	}


# Recurse through the folder structure.
ProcessFolder($syncdir);

echo date('Y-m-d H:i:s    ');
echo "...done.\n\n";

if (!$staticsync_ingest)
	{
	# If not ingesting files, look for deleted files in the sync folder and archive the appropriate file from ResourceSpace.
	echo "\nLooking for deleted files...";
	# For all resources with filepaths, check they still exist and archive if not.
	$rf=sql_query("select ref,file_path from resource where archive=0 and length(file_path)>0 and file_path like '%/%'");
	for ($n=0;$n<count($rf);$n++)
		{
		$fp=$syncdir . "/" . $rf[$n]["file_path"];
		if (!file_exists($fp))
			{
			echo "File no longer exists: " . $rf[$n]["ref"] . " (" . $fp . ")\n";
			# Set to archived.
			sql_query("update resource set archive=2 where ref='" . $rf[$n]["ref"] . "'");
			sql_query("delete from collection_resource where resource='" . $rf[$n]["ref"] . "'");
			}
		}
	# Remove any themes that are now empty as a result of deleted files.
	sql_query("delete from collection where theme is not null and length(theme)>0 and (select count(*) from collection_resource cr where cr.collection=collection.ref)=0;");
	
	# also set dates where none set by going back through filename until a year is found, then going forward and looking for month/year.
	/*
	$rf=sql_query("select ref,file_path from resource where archive=0 and length(file_path)>0 and (length(creation_date)=0 or creation_date is null)");
	for ($n=0;$n<count($rf);$n++)
		{
		}
	*/
	echo "...Complete\n";
	}

sql_query("update sysvars set value=now() where name='lastsync'");

clear_process_lock("staticsync");


function ss_is_alt($file){
    global $staticsync_alt_suffixes;
    // if this feature is not enabled, a file is never an alt file
    if(!$staticsync_alt_suffixes){ return false;}
    global $staticsync_alt_suffix_array;

    // strip extension
    $extension=explode(".",$file);
    $extension=trim(strtolower($extension[count($extension)-1]));
    $strippedfile = substr($file,0,strlen($file)-strlen($extension)-1);

    foreach ($staticsync_alt_suffix_array as $thesuffix){
        if (preg_match("/.+$thesuffix\$/", $strippedfile)){
            return true;
            //echo $file . "would return true\n";
            //exit;
        }
    }
    return false;
    //echo $strippedfile . "would return false\n";
    //exit;
}

?>
