<?php
// Uses Uppy and tus-php. For licenses refer to documentation/licenses/uppy.txt and documentation/licenses/tus-php.txt
use Predis\Protocol\Text\Handler\StatusResponse;

include "../include/db.php";

if(isset($_SERVER['HTTP_TUS_RESUMABLE'])
    && isset($_SERVER['HTTP_UPLOAD_METADATA'])
    )
    {
    // Uppy can only send the token in upload-metadata
    // Extract extra POST data
    $uppy_metadata_arr = explode(",",$_SERVER['HTTP_UPLOAD_METADATA']);
    foreach($uppy_metadata_arr as $uppy_metadata)
        {
        $data = explode(" ", $uppy_metadata);
        if(isset($data[0]) && isset($data[1]))
            {
            if(substr($data[0],0,3) == "rs_")
                {
                $key = substr($data[0],3); 
                if(!isset($_POST[$key]))
                    {
                    $val = base64_decode($data[1]); 
                    $_POST[$key] = $val;
                    }
                }
            }
        }
    // Force pagename as cannot handle Uppy files suffix
    $pagename = "upload_batch";
    }

// The collection_add parameter can have the following values:-
//  'new'       Add to new collection
//  'false'     Do not add to collection
//  'undefined' Not passed in, so replace it with the current user collection
//  is_numeric  Use this collection  
$collection_add = getval('collection_add', 'false');
$external_upload = upload_share_active();

if($collection_add =='false' && $external_upload)
    {
    $collection_add = $external_upload;
    }
// External share support
$k = getval('k','');
if ($k=="" || (!check_access_key_collection($collection_add,$k)))
    {
    include "../include/authenticate.php";
    if (! (checkperm("c") || checkperm("d")))
        {
        exit ("Permission denied.");
        }
    }

global $usersession;
// TUS handling
// Use PHP APCU cache if available as more robust
$cachestore = function_exists('apcu_fetch') ? "apcu" : "file";

if(isset($_SERVER['HTTP_TUS_RESUMABLE']))
    {
    // This code handles the actual TUS file upload from Uppy. Once the file is on the system RS takes over
    require_once __DIR__ . '/../lib/tus/vendor/autoload.php';
    \TusPhp\Config::set(__DIR__ . '/../include/tusconfig.php');
    $server   = new \TusPhp\Tus\Server($cachestore);
    $targetDir = get_temp_dir() . DIRECTORY_SEPARATOR . "tus" . DIRECTORY_SEPARATOR . md5($scramble_key . $usersession); 
    $server -> setUploadDir($targetDir);
    // Create target dir
    if (!file_exists($targetDir))
        {
        $GLOBALS["use_error_exception"] = true;
        try
            {
            mkdir($targetDir,0777,true);
            }
        catch (Exception $e)
            {
            // Ignore
            }
        unset($GLOBALS["use_error_exception"]);
        }

    $response = $server->serve();
    // Extra check added to ensure URL uses $baseurl. Required due to reported issues with some reverse proxy configurations
    $tuslocation = $response->headers->get('location');
    if (!empty($tuslocation) && (strpos($tuslocation, $baseurl) === false))
        {
        $suffix = strpos($tuslocation,"/pages/upload_batch.php");
        if($suffix !== false)
            {
            $tusbase = substr($tuslocation,0,$suffix);
            $rslocation = str_replace($tusbase,$baseurl,$tuslocation);
            debug("upload_batch. Correcting invalid upload URL from '" . $tuslocation . "' to '" . $rslocation . "'");
            $response->headers->set('location', $rslocation);
            }
        }
    $response->send();
    exit(0); // As this is the end of the TUS upload handler no further processing to be performed.
    }


include_once "../include/image_processing.php";

$overquota                              = overquota();
$resource_type                          = getval('resource_type', '');
$collectionname                         = getval('entercolname', '');
$search                                 = getval('search', '');
$offset                                 = getval('offset', '', true);
$order_by                               = getval('order_by', '');
$no_exif_raw                            = getval('no_exif', $metadata_read_default ? '' : 'yes');
$no_exif                                = $no_exif_raw == "yes" || $no_exif_raw =="1" ? true : false;
$autorotate                             = getval('autorotate','') == 'true';
// This is the archive state for searching, NOT the archive state to be set from the form POST
$archive                                = getval('archive', '', true);

$setarchivestate                        = getval('status', '', true);
// Validate this workflow state is permitted or set the default if nothing passed 
$setarchivestate                        = get_default_archive_state($setarchivestate);
$alternative                            = getval('alternative', ''); # Batch upload alternative files
$replace                                = getval('replace', ''); # Replace Resource Batch
$batch_replace_min                      = getval("batch_replace_min",0,true); # Replace Resource Batch - minimum ID of resource to replace
$batch_replace_max                      = getval("batch_replace_max",0,true); # Replace Resource Batch - maximum ID
$batch_replace_col                      = getval("batch_replace_col",0,true); # Replace Resource Batch - collection to replace

$replace_resource                       = getval('replace_resource', ''); # Option to replace existing resource file
$replace_resource_original_alt_filename = getval('replace_resource_original_alt_filename', '');
$single                                 = getval("single","") != "" || getval("forcesingle","") != "";
$upload_here                            = (getval('upload_here', '') != '' ? true : false);

// Set to process upload once file upload complete
$processupload                          = getval("processupload","") != "";

// When uploading, if there are any files in the queue that have similar names plus a suffix to distinguish between original
// and alternatives (see $upload_alternatives_suffix) then, attach the matching alternatives to the resource they belong to
$attach_alternatives_found_to_resources = (trim($upload_alternatives_suffix) != '') && (trim($alternative) == '');

$redirecturl = getval("redirecturl","");
if(strpos($redirecturl, $baseurl)!==0 && !hook("modifyredirecturl")){$redirecturl="";}

if ($replace_resource && (!get_edit_access($replace_resource) || resource_file_readonly($replace_resource)))
    {
    $replace_resource = false;
    }

if($upload_then_edit && $resource_type_force_selection && getval('posting', '') != '')
    {
    update_resource_type(0 - $userref, $resource_type);
    }

// If upload_then_edit we may not have a resource type, so we need to find the first resource type
// which does not have an XU? (restrict upload) permission  
// This will be the resource type used for the upload, but may be changed later when extension is known
// Resource types that can't be added to collections must be avoided for edit then upload mode to display the edit page for metadata entry.
$all_resource_types = get_resource_types();
if($resource_type == "")
	{
	foreach($all_resource_types as $restype)
		{
		if (!checkperm("XU" . $restype["ref"]) && !in_array($restype["ref"],$collection_block_restypes))
			{
			$resource_type = $restype["ref"];
			break;
			}
		}
    // It is possible for there to be no 'unrestricted for upload' resource types 
    // which means that the resource type used for the upload will be blank
	}

# Load the configuration for the selected resource type. Allows for alternative notification addresses, etc.
resource_type_config_override($resource_type);

$hidden_collection = false;
# Create a new collection?
if($collection_add == "new" && ($processupload  || !$upload_then_edit) && !$upload_force_mycollection)
	{
	# The user has chosen Create New Collection from the dropdown.
	if ($collectionname=="")
        {
        $collectionname = "Upload " . offset_user_local_timezone(date('YmdHis'), 'YmdHis'); # Do not translate this string, the collection name is translated when displayed!
        $hidden_collection = true;
        } 
	$collection_add=create_collection($userref,$collectionname);
	if (getval("public",'0') == 1)
		{
		collection_set_public($collection_add);
		}
    if ($hidden_collection)
        {
        show_hide_collection($collection_add, false, $userref);
        }
    else
        {
        set_user_collection($userref,$collection_add);
        }
	}
    
if($external_upload)
    {
    $rs_session = get_rs_session_id(true);
    $ci=get_session_collections($rs_session,$userref,true);
    if (count($ci)==0)
        {
        $usercollection = create_collection($userref,"New uploads",1,1,0,false,array("type" => COLLECTION_TYPE_SHARE_UPLOAD));
        }
    else
        {
        $usercollection = $ci[0];
        }
    $upload_review_col = $usercollection;
    rs_setcookie('lockedfields', '', 1);
    $redirecturl = generateURL(
        "{$baseurl}/pages/edit.php",
        array('upload_review_mode' => true,
              'collection' => $usercollection,
              'k' => $k
              )
        );     
    }
elseif ($upload_then_edit && $replace == "" && $replace_resource == "")
    {
    # Switch to the user's special upload collection.
    $upload_review_col = 0-$userref;
    $ci=get_collection($upload_review_col);
    if ($ci===false)
        {
        create_collection($userref,"New uploads",1,1,0-$userref);
        }

    if(!$processupload)
        {        
        // Clear out review collection before new uploads are added to prevent inadvertent edits of old uploads
        remove_all_resources_from_collection(0-$userref);
        }

    # Set the redirect after upload to the start of the edit process
    rs_setcookie('lockedfields', '', 1);
    $redirecturl = generateURL(
        "{$baseurl}/pages/edit.php",
        array(
            'upload_review_mode' => true,
            'collection_add' => $collection_add
        ));	

	# Clear the user template
	clear_resource_data(0-$userref);
	}

# If uploading alternative file, redirect to the resource rather than search results.
if($alternative != "") 
    {
    $redirecturl = generateURL("{$baseurl}/pages/view.php", array('ref' => $alternative));	
    }
    
$modify_redirecturl=hook('modify_redirecturl');
if($modify_redirecturl!==false)
	{
	$redirecturl=$modify_redirecturl;
	}

if($upload_force_mycollection)
    {
    $collection_add = get_default_user_collection(true);
    }
elseif($collection_add=='undefined')
    {
    # Fallback to current user collection if nothing was passed in
    $collection_add = $usercollection;    
    $uploadparams['collection_add']=$usercollection;
    }

if($camera_autorotation)
    {
    if(isset($autorotation_preference))
        {
        $autorotate = $autorotation_preference;
        } 
    elseif($upload_then_edit)
        {
        $autorotate = $camera_autorotation_checked;
        }    
    else
        {
        $autorotate =  getval('autorotate', '') != '';
        }
    }
else
    {
    $autorotate = false;
    }

$uploadparams= array(
    'replace'                                => $replace,
    'batch_replace_min'                      => $batch_replace_min,
    'batch_replace_max'                      => $batch_replace_max,
    'batch_replace_col'                      => $batch_replace_col,
    'alternative'                            => $alternative,
    'collection_add'                         => $collection_add,
    'resource_type'                          => $resource_type,
    'no_exif'                                => $no_exif,
    'autorotate'                             => $autorotate,
    'replace_resource'                       => $replace_resource,
    'archive'                                => $archive,
    'relateto'                               => getval('relateto', ''),
    'filename_field'                         => getval('filename_field', ''),
	'keep_original'	                         => $replace_resource_preserve_option && $replace_resource_preserve_default,
    'replace_resource_original_alt_filename' => $replace_resource_original_alt_filename,
    'single'                                 => ($single ? "true" : ""),
    'status'                                 => $setarchivestate,
    'k'                                      => $k,
);

$searchparams = get_search_params();

global $merge_filename_with_title, $merge_filename_with_title_default;
if($merge_filename_with_title)
    {
    $merge_filename_with_title_option = urlencode(getval('merge_filename_with_title_option', $merge_filename_with_title_default));
    $merge_filename_with_title_include_extensions = urlencode(getval('merge_filename_with_title_include_extensions', ''));
    $merge_filename_with_title_spacer = urlencode(getval('merge_filename_with_title_spacer', ''));

    if(strtolower($merge_filename_with_title_option) != '')
        {
        $uploadparams['merge_filename_with_title_option'] =  $merge_filename_with_title_option;
        }
    if($merge_filename_with_title_include_extensions != '')
        {
        $uploadparams['merge_filename_with_title_include_extensions']=$merge_filename_with_title_include_extensions;
        }
    if($merge_filename_with_title_spacer != '')
        {
        $uploadparams['merge_filename_with_title_spacer']= $merge_filename_with_title_spacer;
        }
    }

if($embedded_data_user_select || isset($embedded_data_user_select_fields))	
    {
    foreach($_GET as $getname=>$getval)
        {
        if (strpos($getname,"exif_option_")!==false)
            {
            $uploadparams[urlencode($getname)] = $getval;	
            }
        }
    if(getval("exif_override","")!="")
        {
        $uploadparams['exif_override']=true;
        }
    }

// If user wants to replace original file and make it an alternative one, make the default filename for the alternative
if($replace_resource_preserve_option && '' != $replace_resource)
    {
    $original_resource_data                          = get_resource_data($replace_resource);
    $default_replace_resource_original_alt_filename  = str_replace('%EXTENSION', strtoupper($original_resource_data['file_extension']), $lang['replace_resource_original_description']);
    $default_replace_resource_original_alt_filename .= nicedate(date('Y-m-d H:i'), true);

    $uploadparams['replace_resource_original_alt_filename'] = $default_replace_resource_original_alt_filename;
    }

$uploadurl_extra_params = array();

if($upload_here)
    {
    $uploadparams['upload_here'] = $upload_here;
    $uploadparams['search'] = $search;
    $uploadparams['resource_type'] = $resource_type;
    $uploadparams['status'] = $setarchivestate;
    }

$uploadurl = generateURL("{$baseurl}/pages/upload_batch.php", $uploadparams, $uploadurl_extra_params) . hook('addtopluploadurl');

$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);

$allowed_extensions="";
if(($upload_then_edit || $replace ) && !$alternative)
    {
    $all_allowed_extensions_holder = array();
    foreach ($all_resource_types as $type) 
        {
        if(get_allowed_extensions_by_type($type["ref"]) == "")
            {
            $all_allowed_extensions_holder = array();
            break;
            }
        else
            {
            $extensions = explode(",", get_allowed_extensions_by_type($type["ref"]));
            foreach ($extensions as $extension) 
                {
                if ($extension != "") 
                    {
                    array_push($all_allowed_extensions_holder, trim(strtolower($extension)));
                    }
                }
            }
        }
    $all_allowed_extensions_holder = array_unique($all_allowed_extensions_holder);
    $allowed_extensions = implode(",", $all_allowed_extensions_holder);
    }
else if ($resource_type!="" && !$alternative) 
    {
    $allowed_extensions=get_allowed_extensions_by_type($resource_type);
    }

//  Process completed upload
if ($processupload)
    {
    // HTTP headers for no cache etc
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    $targetDir = get_temp_dir() . DIRECTORY_SEPARATOR . "tus" . DIRECTORY_SEPARATOR . md5($scramble_key . $usersession);
    $upfilename = getval("file_name","");
    $cleanupTargetDir = true; // Remove old files
    $maxFileAge = 5 * 3600; // Temp file age in seconds
    set_time_limit($php_time_limit);
    debug("upload_batch - received file from user '" . $username . "',  filename: '" . $upfilename . "'");
        
    # Work out the extension
    $parts=explode(".",$upfilename);
    $origextension=trim($parts[count($parts)-1]);
    $extension=strtolower($origextension);
    if(count($parts) > 1){array_pop($parts);}
    $filenameonly = implode('.', $parts);

     // Clean the filename
    $origuploadedfilename= $upfilename;
    $encodedname = str_replace("/","RS_FORWARD_SLASH", base64_encode($filenameonly));
    $upfilepath = $targetDir . DIRECTORY_SEPARATOR . $encodedname . ((!empty($origextension)) ? ".{$origextension}" : '');

    hook('modify_upload_file','',[$upfilename,$upfilepath]);

    # Banned extension?
    global $banned_extensions;
    if (in_array($extension,$banned_extensions))
        {
        debug("upload_batch - invalid file extension received from user " . $username . ",  filename " . $upfilename);
        $result["status"] = false;
        $result["message"] = str_replace("%%FILETYPE%%",$upfilename,$lang["error_upload_invalid_file"]);
        $result["error"] = 105;
        unlink($upfilepath);
        die(json_encode($result));
        }

	hook('additional_plupload_checks');

    if($allowed_extensions != "")
        {
        // Check file extension and MIME type
        $filemime = get_mime_type($upfilepath);
        $allowed_extensions=str_replace(" ","",$allowed_extensions);
        $allowedmime = explode(",",trim($allowed_extensions));
        if(strpos($allowed_extensions,"/") === false) // List of file extensions. not MIME types
            {
            $allowedmime = array_map("allowed_type_mime",$allowedmime);
            } 
            
        if(!in_array($filemime,$allowedmime))
            {
            debug("upload_batch - invalid file received from user " . $username . ",  filename " . $upfilename . ", mime type: " . $filemime);
            $result["status"] = false;
            $result["message"] = str_replace("%%FILETYPE%%", $upfilename . " (" . $filemime . ")",$lang["error_upload_invalid_file"]);
            $result["error"] = 105;
            unlink($upfilepath);
            die(json_encode($result));
            }            
        }

    # Check for duplicate files if required
    $duplicates=check_duplicate_checksum($upfilepath,$replace_resource);
    if(count($duplicates)>0)
        {
        debug("upload_batch ERROR- duplicate file matches resources" . implode(",",$duplicates));
        $result["status"] = false;
        $result["message"] = str_replace("%%RESOURCES%%",implode(",",$duplicates),$lang["error_upload_duplicate_file"]);
        $result["error"] = 108;
        unlink($upfilepath);
        die(json_encode($result));
        }
    elseif(!hook("initialuploadprocessing"))
        {
        if ($alternative!="")
            {
            # Upload an alternative file 
            $resource_data = get_resource_data($alternative);
            if($resource_data["lock_user"] > 0 && $resource_data["lock_user"] != $userref)
                {
                $result["status"] = false;
                $result["message"] = get_resource_lock_message($resource_data["lock_user"]);
                $result["error"] = 111;
                $result["id"] = htmlspecialchars($ref);
                $result["collection"] = htmlspecialchars($collection_add);   
                }
            else
                {
                # Add a new alternative file
                $aref=add_alternative_file($alternative,$upfilename);
                
                # Find the path for this resource.
                $path=get_resource_path($alternative, true, "", true, $extension, -1, 1, false, "", $aref);
                
                # Move the sent file to the alternative file location
                $renamed=rename($upfilepath, $path);

                if ($renamed===false)
                    {
                    $result["status"] = false;
                    $result["message"] = $lang["error_upload_file_move_failed"];
                    $result["error"] = 104;
                    die(json_encode($result));
                    }
                else
                    {
                    chmod($path,0777);
                    $file_size = @filesize_unlimited($path);
                    
                    # Save alternative file data.
                    ps_query("update resource_alt_files set file_name=?,file_extension=?,file_size=?,creation_date=now() where resource=? and ref=?",array("s",$upfilename,"s",$extension,"i",$file_size,"i",$alternative,"i",$aref));
                    
                    if ($alternative_file_previews)
                        {
                        create_previews($alternative,false,$extension,false,false,$aref);
                        }

                    hook('after_alt_upload','',array($alternative,array("ref"=>$aref,"file_size"=>$file_size,"extension"=>$extension,"name"=>$upfilename,"altdescription"=>"","path"=>$path,"basefilename"=>str_ireplace("." . $extension, '', $upfilename))));

                    // Check to see if we need to notify users of this change							
                    if($notify_on_resource_change_days!=0)
                        {								
                        // we don't need to wait for this..
                        ob_flush();flush();
                        notify_resource_change($alternative);
                        }

                    # Update disk usage
                    update_disk_usage($alternative);
                    hook('upload_alternative_extra', '', array($path));

                    $result["status"] = true;
                    $result["message"] = $lang["alternative_file_created"];
                    $result["id"] = $alternative;  
                    $result["alternative"] = $aref;  
                    }
                }
            }
        elseif ($replace=="" && $replace_resource=="")
            {
            # Standard upload of a new resource
            # create ref via copy_resource() or other method
            $modified_ref=hook("modifyuploadref");
            if ($modified_ref!="")
                {
                $ref=$modified_ref;
                }
            elseif(!$upload_then_edit)
                {
                $ref=copy_resource(0-$userref); # Copy from user template   
                }

            // copy_resource() returns false if user doesn't have a resource template
            // Usually, this happens when a user had from the first time upload_then_edit mode on
            if($upload_then_edit || false === $ref)
                {
                $ref = create_resource($resource_type, $setarchivestate);
                }

            # Check that $ref is not false - possible return value with create_resource()
            if(!$ref)
                {
                $result["status"] = false;
                $result["message"] = "Failed to create resource with given resource type: ' . $resource_type . '";
                $result["error"] = 125;
                $result["id"] = htmlspecialchars($ref);
                $result["collection"] = htmlspecialchars($collection_add);           
                }
            else
                {
                // Check valid requested state by calling function that checks permissions
                update_archive_status($ref, $setarchivestate);
                
                if($upload_then_edit && $upload_here)
                    {
                    $search = urldecode($search);                    
                    if(!empty(get_upload_here_selected_nodes($search, array())))
                        {
                        add_resource_nodes($ref, get_upload_here_selected_nodes($search, array()), true);
                        }
                    }
                # Add to collection?
                if (is_numeric($collection_add))
                    {
                    add_resource_to_collection($ref,$collection_add,false,"",$resource_type);
                    }
                if ($upload_then_edit && $replace == "" && $replace_resource == "" && $collection_add != $upload_review_col)
                    {
                    # Also add to the user's special upload collection.
                    add_resource_to_collection($ref,$upload_review_col,false,"",$resource_type); 
                    }
                
                $relateto = getval("relateto","",true);   
                if($relateto!="" && !upload_share_active())
                    {
                    // This has been added from a related resource upload link
                    update_related_resource($relateto,$ref);
                    }

                // For upload_then_edit mode ONLY, set the resource type based on the extension. User
                // can later change this at the edit stage
                // IMPORTANT: Change resource type only if user has access to it
                if($upload_then_edit && !$resource_type_force_selection)
                    {
                    $resource_type_from_extension = get_resource_type_from_extension(
                        pathinfo($upfilepath, PATHINFO_EXTENSION),
                        $resource_type_extension_mapping,
                        $resource_type_extension_mapping_default
                    );

                    if(!checkperm("XU{$resource_type_from_extension}") && in_array($resource_type_from_extension,array_column($all_resource_types,"ref")))
                        {
                        update_resource_type($ref, $resource_type_from_extension);
                        // The resource type has been changed so clear the cached value
                        $GLOBALS['get_resource_data_cache'] = array();
                        }
                    }

                if($upload_then_edit && $reset_date_upload_template)
                    {
                    // If extracting embedded metadata than expect the date to be overriden as it would be if
                    // upload_then_edit = false
                    update_field($ref, $reset_date_field, date('Y-m-d H:i'));
                    }

                # Log this			
                daily_stat("Resource upload",$ref);
                
                $success=upload_file($ref,($no_exif=="yes" && getval("exif_override","")==""),false,$autorotate,$upfilepath);

                if($success && $auto_generated_resource_title_format != '' && !$upload_then_edit)
                    {
                    $new_auto_generated_title = '';

                    if(strpos($auto_generated_resource_title_format, '%title') !== false)
                        {
                        $resource_detail = ps_query ("
                            SELECT r.ref, r.file_extension, n.value
                              FROM resource r
                         LEFT JOIN resource_node rn ON rn.resource=r.ref 
                         LEFT JOIN node n ON N.ref=rn.node 
                             WHERE n.resource_type_field = ? AND r.ref= ?",
                                ["i",$view_title_field,"i",$ref]);

                        $new_auto_generated_title = str_replace(
                            array('%title', '%resource', '%extension'),
                            array(
                                $resource_detail[0]['value'],
                                $resource_detail[0]['ref'],
                                $resource_detail[0]['file_extension']
                            ),
                            $auto_generated_resource_title_format);
                        }
                    else
                        {
                        $resource_detail = ps_query ("
                            SELECT r.ref, r.file_extension FROM resource r WHERE r.ref = ?",
                            ["i",$ref]
                            );

                        $new_auto_generated_title = str_replace(
                            array('%resource', '%extension'),
                            array(
                                $resource_detail[0]['ref'],
                                $resource_detail[0]['file_extension']
                            ),
                            $auto_generated_resource_title_format);
                        }

                    if($new_auto_generated_title != '')
                        {
                        update_field($ref, $view_title_field, $new_auto_generated_title);
                        }
                    }
                hook('upload_original_extra', '', array($ref));
                    
                $after_upload_result = hook('afterpluploadfile', '', array($ref, $extension));
                
                if (is_array($after_upload_result))
                    {
                    $result["status"] = false;
                    $result["error"] = $after_upload_result["code"];
                    $result["message"] = $after_upload_result["message"];
                    }
                else
                    {
                    $result["status"] = true;
                    $result["message"] = $lang["created"];
                    $result["id"] = htmlspecialchars($ref);
                    $result["collection"] = htmlspecialchars($collection_add);
                    }
                }
            }
        else if ($replace=="" && $replace_resource!="")
            {
            // Replacing an existing resource file
            // Extract data unless user has selected not to extract exif data and there are no per field options set
            $no_exif = ('yes' == $no_exif) && '' == getval('exif_override', '');
            $keep_original = getval('keep_original', '') != '';
            $success = replace_resource_file($replace_resource,$upfilepath,$no_exif,$autorotate,$keep_original);
            if (!$success)
                {
                $result["status"] = false;
                $result["message"] = $lang["alternative_file_created"];
                $result["error"] = 109;
                $result["id"] = $replace_resource;
                }
            else
                {
                $result["status"] = true;
                $result["message"] = $lang["replacefile"];
                $result["error"] = 0;
                $result["id"] = $replace_resource;
                }
            }
        else
            {
            $no_exif = ('yes' == $no_exif) && '' == getval('exif_override', '');
            $keep_original = getval('keep_original', '') != '';
                
            if (!isset($batch_replace_col) || $batch_replace_col == 0)
                {
                $conditions = array();
                $batch_replace_min = max((int)($batch_replace_min),$fstemplate_alt_threshold);
                $firstref = max($fstemplate_alt_threshold, $batch_replace_min);                                
                $replace_resources = ps_array("SELECT ref value FROM resource WHERE ref >= '" . $batch_replace_min . "' " . (($batch_replace_max > 0) ? " AND ref <= '" . $batch_replace_max . "'" : "") . " ORDER BY ref ASC",array("i",$batch_replace_min,"i",$batch_replace_max,"i",$batch_replace_max),0);
                debug("batch_replace upload: replacing files for resource IDs. Min ID: " . $batch_replace_min  . (($batch_replace_max > 0) ? " Max ID: " . $batch_replace_max : ""));
                }
            else
                {
                $replace_resources = get_collection_resources($batch_replace_col);
                debug("batch_replace upload: replacing resources within collection " . $batch_replace_col . " only");
                }
                
            $filename_field=getval("filename_field",0,true);
            if($filename_field != 0)
                {
                $target_resource = ps_array(
                    'SELECT resource value
                       FROM resource_node AS rn
                       JOIN node AS n ON rn.node = n.ref
                      WHERE n.resource_type_field = ?
                        AND name = ?
                        AND resource > ?', 
                    [
                        'i', $filename_field, 
                        's', $origuploadedfilename, 
                        'i', $fstemplate_alt_threshold
                    ]
                );
                $target_resourceDebug = $target_resource;
                $target_resourceDebug_message1= "Target resource details - target_resource: " . (count($target_resource)>0 ? json_encode($target_resource) : "NONE") . " . resource_type_field: $filename_field . value: $origuploadedfilename . template_alt_threshold: $fstemplate_alt_threshold . collection: $batch_replace_col";
                debug($target_resourceDebug_message1);
                $target_resource=array_values(array_intersect($target_resource,$replace_resources));
                if(count($target_resource)==1  && !resource_file_readonly($target_resource[0]))
                    {
                    // A single resource has been found with the same filename                                    
                    $success = replace_resource_file($target_resource[0],$upfilepath,$no_exif,$autorotate,$keep_original);
                    if (!$success)
                        {
                        $result["status"] = false;
                        $result["message"] = $lang["error_upload_replace_file_fail"];
                        $result["error"] = 109;
                        $result["id"] = $target_resource[0];
                        }
                    else
                        {
                        $result["status"] = true;
                        $result["message"] = $lang["replacefile"];
                        $result["error"] = 0;
                        $result["id"] = $target_resource[0];
                        }
                    }
                elseif(count($target_resource)==0)
                    {
                    // No resource found with the same filename
                    $target_resourceDebug_message2 = "Target resource not found - target_resource: " . (count($target_resource)>0 ? json_encode($target_resource) : "NONE FOUND - should have been: " . (count($target_resourceDebug)>0 ? json_encode($target_resourceDebug): "NONE"))  . " . Replace in resources: " . json_encode($replace_resources);
                    debug($target_resourceDebug_message2);
                    $result["status"] = false;
                    $result["message"] = str_replace("%%FILENAME%%",$origuploadedfilename,$lang["error_upload_replace_no_matching_file"]);
                    $result["error"] = 106;
                    }
                else
                    {
                    // Multiple resources found with the same filename
                    // but we are going to replace them because $replace_batch_existing is set to true
                    $resourcelist=implode(",",$target_resource);
                    if ($replace_batch_existing)
                        {
                        foreach ($target_resource as $replaced)
                            {
                            $success = replace_resource_file($replaced,$upfilepath,$no_exif,$autorotate,$keep_original);
                            if (!$success)
                                {
                                $result["status"] = false;
                                $result["message"] = $lang["error_upload_replace_file_fail"];
                                $result["error"] = 109;
                                $result["id"] = $replaced;
                                }                                
                            $success = upload_file($replaced, ('yes' == $no_exif && '' == getval('exif_override', '')), false, $autorotate, $upfilepath);
                            }

                        $result["status"] = true;
                        $result["message"] = $lang["replacefile"];
                        $result["error"] = 0;
                        $result["id"] = $resourcelist;
                        }
                    else
                        {
                        // Multiple resources found with the same filename
                        $result["status"] = false;
                        $result["message"] = str_replace("%%FILENAME%%",$origuploadedfilename,$lang["error_upload_replace_multiple_matching_files"]);
                        $result["error"] = 107;
                        $result["id"] = $resourcelist;
                        }
                    }
                }
            else
                {
                # Overwrite an existing resource using the number from the filename.
                # Extract the number from the filename
                $origuploadedfilename=strtolower(str_replace(" ","_",$origuploadedfilename));
                $s=explode(".",$origuploadedfilename);
                

                # does the filename follow the format xxxxx.xxx?
                if(2 == count($s))
                    {
                    $ref = trim($s[0]);

                    // is the first part of the filename numeric?
                    if(is_numeric($ref) && in_array($ref,$replace_resources) && !resource_file_readonly($ref))
                        {
                        debug("batch_replace upload: replacing resource with id " . $ref);
                        daily_stat("Resource upload",$ref);

                        # Save the original file as an alternative file?                                            
                        $keep_original = getval('keep_original', '');
                        $save_original = ($keep_original == 1) ? save_original_file_as_alternative($ref) : true;
                        
                        $success = replace_resource_file($ref,$upfilepath,$no_exif,$autorotate,$keep_original);
                        if (!$success)
                            {
                            $result["status"] = false;          
                            $result["message"] = $lang["error_upload_replace_file_fail"];
                            $result["error"] = 109;
                            $result["id"] = $ref;
                            }
                        else
                            {      
                            $result["status"] = true;                   
                            $result["message"] = $lang["replacefile"];
                            $result["error"] = 0;
                            $result["id"] = $ref;
                            }
                        }
                    else
                        {
                        // No resource found with the same filename
                        debug("batch_replace upload: No valid resource id for filename " . $origuploadedfilename);
                        $result["status"] = false; 
                        $result["message"] = str_replace("%%FILENAME%%",$origuploadedfilename,$lang["error_upload_replace_no_matching_file"]);
                        $result["error"] = 106;
                        }
                    }
                }
            }
        }
    // Remove file now it has been handled
    if(file_exists($upfilepath))
        {
        unlink($upfilepath);
        }

    // Return JSON-RPC response
    exit(json_encode($result));
    }
elseif ($upload_no_file && getval("createblank","")!="")
	{
    $ref=copy_resource(0-$userref); 
                                
    if($ref === false)
        {
        // If user doesn't have a resource template (usually this happens when a user had from the first time upload_then_edit mode on), create resource using default values.
        $ref = create_resource($resource_type, $setarchivestate);
        }   
        
	// Add to collection?
	if (is_numeric($collection_add))
		{
		add_resource_to_collection($ref,$collection_add);
		}
    rs_setcookie('lockedfields', '', 1);
    $redirecturl = generateURL($baseurl_short . "pages/edit.php",$searchparams,array("ref"=>$ref,"refreshcollectionframe"=>"true"));
    redirect($redirecturl);
    exit();
	}

// Check if upload should be disabled because the filestore location is indexed and browseable
$cfb = check_filestore_browseability();
if(!$cfb['index_disabled'])
    {
    exit(error_alert($lang['error_generic_misconfiguration'], true, 200));
    }

$headerinsert.="
<link type='text/css' href='$baseurl/css/smoothness/jquery-ui.min.css?css_reload_key=$css_reload_key' rel='stylesheet' />";

include "../include/header.php";
?>

<script>
redirurl = '<?php echo $redirecturl ?>';
var resource_keys=[];
var processed_resource_keys=[];
var relate_on_upload = <?php echo ($store_uploadedrefs ||($relate_on_upload && $enable_related_resources && getval("relateonupload","")==="yes")) ? " true" : "false"; ?>;
// Set flag allowing for blocking auto redirect after upload if errors are encountered
upRedirBlock = false;
logopened = false;
// A mapping used by subsequent file uploads of alternatives to know to which resource to add the files as alternatives
// Used when the original file and its alternatives are uploaded in a batch to a collection
var resource_ids_for_alternatives = [];

// Array of file ids that errors have been logged for
errorslogged = [];

newcol = '<?php echo (int) $collection_add; ?>';
jQuery(document).ready(function () {
    // If page URL has not updated, set it so that we can resume in event of crash
    if(window.location.href != '<?php echo $uploadurl ?>' && typeof(top.history.pushState)=='function')
        {       
        top.history.pushState(document.title+'&&&'+jQuery('#CentralSpace').html(), applicationname, '<?php echo str_replace("&ajax=true","",$uploadurl) ?>');
        }

    <?php
    if(!$processupload && is_int_loose($collection_add))
        {             
        echo "CollectionDivLoad('" . $baseurl . "/pages/collections.php?collection="  . (int)$collection_add . "&nowarn=true&nc=" . time() . "');";
        }?>

    registerCollapsibleSections();

    uploadProgress = 0; // Count of number of files that have been uploaded
    rsprocessed = [];  // Array of file names that have been processed (AJAX POST has been sent)
    rscompleted = [];  // Array of file names that have been completed (AJAX POST has returned)
    process_alts = true // Flag to indicate whether upload of alternatives has started
    processerrors = []; // Keep track of upload errors
    retried = []; // Keep track of files that have been retried automatically
    allowcollectionreload = true;
    uppy = new Uppy.Core({
        debug: false,
        autoProceed: false,
        restrictions: {
            <?php
            if (isset($upload_max_file_size))
                {
                echo "maxFileSize: " . str_ireplace(array("kb","mb","gb"),array("000","000000","000000000"),$upload_max_file_size); 
                }
            if ($replace_resource > 0 || $single)
                {
                echo "maxNumberOfFiles: '1',"; 
                }
            if (isset($allowedmime))
                {
                // Specify what files can be browsed for
                $allowed_extension_filter = "'" . implode("','",$allowedmime) . "'";
                echo "allowedFileTypes: [" . $allowed_extension_filter . "],";
                }            
                ?>
            },

        locale: {
                strings: {
                    uploadComplete: '<?php echo htmlspecialchars($lang["upload_complete_processing"]); ?>',
                    browseFiles: '<?php echo $lang["upload_browse"] ?>',
                    uploadXFiles: '<?php echo $lang["upload_start"] ?>',
                    dropPaste: '<?php echo $lang["upload_droparea_text"] ?>',
                },
            },

        onBeforeUpload: (files) => {
            processafter = []; // Array of alternative files to process after primary files
            // Check if a new collection is required
            if(newcol == '' || newcol == 0)
                {
                newcol = jQuery('#collection_add').val();
                if(newcol == "new")
                    {
                    entercolname = jQuery('#entercolname').val();
                    console.debug("api create_collection(" + entercolname + ")");
                    api('create_collection',{'name': entercolname,'forupload': true}, function(response)
                        {
                        newcol = parseInt(response);
                        console.debug('Created collection #' + newcol);
                        redirurl =  ReplaceUrlParameter(redirurl, 'collection_add', newcol);
                        });
                    }
                }
             // Encode the file names
            const updatedFiles = {}
            Object.keys(files).forEach(fileid => {
            console.log(files[fileid]);
                updatedFiles[fileid] = {
                    ...files[fileid],
                  }
                //Extract and re add the file extension to allow for detection of file types
                parts = files[fileid].name.split('.');
                extension = parts.pop();
                safefilename = base64encode(`${parts.join('.')}`) + `.${extension}`;
                updatedFiles[fileid].meta.name = safefilename.replace(/\//g,'RS_FORWARD_SLASH'); // To fix issue with forward slashes in base64 string
                console.debug('file obj')
                console.debug(files[fileid].id)
                });    
                
            // Now upload the files
            count = Object.keys(files).length;
            jQuery('.uploadform input').prop('disabled','true'); 
            jQuery('.uploadform select').prop('disabled','true');
            }
        });
    
        uppy.setMeta({
            <?php 
            if($CSRF_enabled)
                {
                // Add CSRF token
                echo "rs_" . $CSRF_token_identifier . ": '" . generateCSRFToken($usersession, "upload_batch") . "',";
                }
            if($k != "")
                {
                // This is an external upload, add data so that we can authenticate Uppy uploads
                ?>
                rs_k: '<?php echo htmlspecialchars($k) ?>',
                rs_collection_add: '<?php echo (int)$collection_add ?>',
                <?php
                }?>
            });


    var Dashboard = Uppy.Dashboard;
    var Tus = Uppy.Tus;
    
    uppy.use(Dashboard, {
        id: 'Dashboard',
        target: '#uploader',
        trigger: '#uppy-select-files',
        inline: true,
        width: '100%',
        height: 450,
        thumbnailWidth: 125,
        showLinkToFileUploadResult: false,
        showProgressDetails: true,
        hideUploadButton: false,
        hideRetryButton: false,
        hidePauseResumeButton: false,
        hideCancelButton: false,
        hideProgressAfterFinish: false,
        note: null,
        closeModalOnClickOutside: false,
        closeAfterFinish: false,
        disableStatusBar: false,
        disableInformer: false,
        disableThumbnailGenerator: false,
        disablePageScrollWhenModalOpen: true,
        animateOpenClose: true,
        fileManagerSelectionType: 'files',
        proudlyDisplayPoweredByUppy: true,
        showSelectedFiles: true,
        showRemoveButtonAfterComplete: false,
        browserBackButtonClose: false,
        theme: 'light',
        doneButtonHandler: null,
        });
    
    uppy.use(Tus, {
        endpoint: '<?php echo $baseurl ?>/pages/upload_batch.php',
        resume: true,
        retryDelays: [0, 1000, 3000, 5000],
        withCredentials: true,
        overridePatchMethod: true,
        limit: <?php echo $upload_concurrent_limit; ?>,
        removeFingerprintOnSuccess: true,
        <?php
        if(trim($upload_chunk_size) != "")
            {
            echo "chunkSize: " . str_ireplace(array("kb","mb","gb"),array("000","000000","000000000"),$upload_chunk_size) . ",\n";
            }?>
        });

    uppy.on('complete', (result) => {
        console.debug("status count " + count);
        console.debug("status rsprocessed " + rsprocessed.length);
        // Process response and inform RS that upload has completed
        if(uploadProgress >= count)
            {
            console.debug("Processing uploaded resources");
            CentralSpaceShowProcessing();
            pageScrolltop(scrolltopElementCentral);
            }
        });

    uppy.on('upload-success', (file, response) => {
        uploadProgress++;
        console.debug('Completed uploading file ' + uploadProgress + ' out of ' + count + ' files');
        processFile(file);    
        // End of file uploaded code
        });

    uppy.on('upload-error', (file, error, response) => {
        console.debug(error);
        errmessage = error.message;
        if(errmessage.indexOf('response text') !== -1)
            {
            errmessage = errmessage.substring(errmessage.indexOf("response text")+15,errmessage.indexOf(', request id'));
            }
        if(errmessage.indexOf('410') !== -1)
            {
            errmessage += ' <?php echo $lang["error_suggest_apcu"]; ?>';
            }
        else if(errmessage == "")
            {
            errmessage += ' <?php echo $lang["upload_error_unknown"]; ?>';
            }
        <?php
        // Automatically retry any errors that may be caused by TUS file cache
        if($cachestore != "apcu")
            {
            ?>
            console.log("Failed upload of " + file.name);
            console.log(retried.indexOf(file.id));
            if(retried.indexOf(file.id) === -1)
                {            
                // Retry the upload
                console.log("Retrying the upload of " + file.name);
                retried.push(file.id);
                setTimeout(function () {
                    uppy.retryUpload(file.id);
                    }, 4000);
                }
            else
                {
                // Add to array of errored files
                if(processerrors.indexOf(file.id) === -1)
                    {
                    processerrors.push(file.id);
                    }
                }
            <?php
            }
        else
            {
            ?>
            // Add to array of errored files
            if(processerrors.indexOf(file.id) === -1)
                {
                processerrors.push(file.id);
                }
            <?php
            }
            ?>

        if(typeof errorslogged[file.id] == 'undefined' || errorslogged[file.id] != errmessage)
            {
            // Add error to log if not already done
            errorslogged[file.id] = errmessage;
            jQuery("#upload_log").append("\r\n'" + file.name + "' <?php echo $lang["error"]?>:" + errmessage);
            }
        else
            {
            upRedirBlock = true;
            }
        });

    }); // End of Uppy JS code

<?php
# If adding to a collection that has been externally shared, show a warning.
if (is_numeric($collection_add) && count(get_collection_external_access($collection_add))>0)
    {
    # Show warning.
    ?>alert("<?php echo $lang["sharedcollectionaddwarningupload"]?>");<?php
    }
?>

function processFile(file, forcepost)
    {
    // Send request to process the uploaded file after Uppy has completed
    console.debug("Processing file: " + file.name);
    postdata = {
        ajax: 'true',
        processupload: "true",
        file_name: file.name,
        <?php if($CSRF_enabled) 
            {
            echo generateAjaxToken("upload_batch") . ",\n";
            }
        foreach($uploadparams as $uploadparam=>$value)
            {
            echo $uploadparam . " : '" . urlencode($value) . "',\n";
            }?>
        };
    
    forceprocess = typeof forcepost != "undefined";

    <?php
    // == EXTRA DATA SECTION - Add any extra data to send after upload required here ==

    // When uploading a batch of files and their alternatives, ensure that alternatives are processed at the end.
    // Keep track of the resource ID  and the filename it is associated with if not an alternative 
    if (trim($upload_alternatives_suffix) != "")
        {?>
        var alternative_suffix = '<?php echo trim($upload_alternatives_suffix); ?>';
        filename = file.name.substr(0, file.name.lastIndexOf('.' + getFilePathExtension(file.name)));
        console.debug("filename = " + filename);
        console.log("forceprocess: " +  forceprocess);
        // Check if original file, in which case stop here
        if(filename.lastIndexOf(alternative_suffix) !== -1)
            {
            console.debug(file.name + " - matches the alternative file format");
            if (!forceprocess)
                {
                // Add to array to process later
                processafter.push(file);
                console.debug("Added " + file.name + " to process after array");
                if(processafter.length == count)
                    {
                    if(newcol > 0)
                        {
                        api('do_search', {'search' : '!collection' + newcol}, function(response){
                            if(response.length > 0)
                                {
                                response.forEach(function(resource){  
                                    {
                                    resource_filename = resource['field<?php echo htmlspecialchars($filename_field)?>']
                                    resource_ids_for_alternatives[resource['ref']] = resource_filename.substr(0, resource_filename.lastIndexOf('.' + resource['file_extension']));;
                                    }
                                })
                                }
                            //No non alt files uploaded so we can now process the alt files.
                            jQuery('#CentralSpace').trigger("ProcessedMain");
                        });
                        }              
                    }
                return false;
                }
            else
                {
                // Check if we have recorded a resource ID for a file with the same name minus the alternative suffix
                original_filename = filename.substr(0, filename.lastIndexOf(alternative_suffix));
                resource_id       = resource_ids_for_alternatives.indexOf(original_filename);
                if(resource_id != -1)
                    {
                    // Found the original, upload this file as an alternative for this resource ID
                    console.log("Found resource id for original file :  " + resource_id);
                    postdata['alternative'] = resource_id;
                    }
                else
                    {                    
                    processerrors.push(filename);
                    jQuery("#upload_log").append("\r\n'" + file.name + "': <?php echo $lang['error'] . ": " . $lang['error_upload_resource_not_found']; ?>");
                    upRedirBlock = true;
                    return postUploadActions(); 
                    }
                }
            }
        <?php
        }

    // EXTRA DATA: Check for keep_original and replace_resource_original_alt_filename
    if($replace_resource_preserve_option)
        {
        ?>
        // Check for keep_original
        keep_original = jQuery('#keep_original').is(':checked');
        if(keep_original)
            {
            postdata['keep_original'] = 1;
            }

        altname  = jQuery('#replace_resource_original_alt_filename').val();
        postdata['replace_resource_original_alt_filename'] = altname;
        <?php
        }?>

    // EXTRA DATA: New collection information
    if(typeof newcol !== 'undefined' && newcol != '' && newcol != 0)
        {
        postdata['collection_add'] = newcol;
        }
    
    // EXTRA DATA: no_exif whilst avoiding overwriting it if the element does not exist
    if(jQuery('#no_exif').length > 0)
        {
        postdata['no_exif'] = jQuery('#no_exif').is(':checked') ? "yes": "";
        }

    console.debug("newcol: " + newcol);
    entercolname = jQuery('#entercolname').val();
    console.debug("entercolname: " + entercolname);

    // Add the updated values 
    postdata['entercolname'] = entercolname;

    rsprocessed.push(file.name); // Add it to the processed array before AJAX call as processing is asynchronous
    jQuery.ajax({
        type: 'POST',
        url: '<?php echo $baseurl_short; ?>pages/upload_batch.php',
        data: postdata,
        success: function(data,type,xhr){
            // Process the response
            if(rscompleted.indexOf(file.id) === -1)
                {
                rscompleted.push(file.id);
                }

            try {
                uploadresponse = JSON.parse(data);        
                console.debug(uploadresponse);    
                }
            catch (e) {
                // Not valid JSON, possibly a PHP error
                uploadresponse = new Object();
                uploadresponse.status =false;
                uploadresponse.error = '';
                uploadresponse.message = file.name + ': <?php echo $lang['upload_error_unknown'] ; ?> ' + data;
                }    
            
            if (uploadresponse.status != true)
                {
                error = uploadresponse.error;
                upRedirBlock = true;
                if(uploadresponse.error==108)
                    {
                    message = '<?php echo $lang['error-duplicatesfound']?>';
                    jQuery("#upload_log").append("\r\n" + file.name + "&nbsp;" + uploadresponse.message);
                    if(!logopened)
                        {
                        jQuery("#UploadLogSectionHead").click();
                        logopened = true;
                        }
                    }
                else if(uploadresponse.error==109)
                    {
                    message = uploadresponse.message +  ' ' + uploadresponse.id;
                    styledalert('<?php echo $lang["error"] ?> ' + uploadresponse.error, message);   
                    jQuery("#upload_log").append("\r\n" + message);
                    if(!logopened)
                        {
                        jQuery("#UploadLogSectionHead").click();
                        logopened = true;
                        }
                    }
                else
                    {
                    styledalert('<?php echo $lang["error"]?> ' + uploadresponse.error, uploadresponse.message);
                    jQuery("#upload_log").append("\r\n" + uploadresponse.message + " [" + uploadresponse.error + "]");
                    }
                
                if(processerrors.indexOf(file.id) === -1)
                    {
                    processerrors.push(file.id);
                    }
                upRedirBlock = true;
                }
            else
                {
                // Successful upload - add to log 
                jQuery("#upload_log").append("\r\n" + file.name + " - " + uploadresponse.message + " " + uploadresponse.id);
                if(resource_keys===processed_resource_keys)
                    {
                    resource_keys=[];
                    }
                resource_keys.push(uploadresponse.id.replace( /^\D+/g, ''));
                if (typeof uploadresponse.collection != 'undefined' && uploadresponse.collection > 0)
                    {
                    newcol = uploadresponse.collection;                                            
                    }

                // When uploading a batch of files and their alternatives, keep track of the resource ID
                // and the filename it is associated with
                <?php
                if($attach_alternatives_found_to_resources)
                    {
                    ?>
                    var alternative_suffix   = '<?php echo trim($upload_alternatives_suffix); ?>';
                    var uploaded_resource_id = uploadresponse.id;
                    var filename             = file.name;
                    var filename_ext         = getFilePathExtension(filename);

                    if(filename_ext != '')
                        {
                        filename = filename.substr(0, file.name.lastIndexOf('.' + filename_ext));
                        }
                    
                    // Add resource ID - filename map only for original resources
                    if(filename.lastIndexOf(alternative_suffix) === -1)
                        {
                        console.debug("Added '"+ filename + "' (ID " + uploaded_resource_id + ") to resource_ids_for_alternatives array")
                        resource_ids_for_alternatives[uploaded_resource_id] = filename;
                        }
                    <?php
                    }?>
                }
            console.debug("Completed processing " + rscompleted.length + " files");
            console.debug("Failed to process " + processerrors.length + " files");
            console.debug("Alternatives to process: " + processafter.length);
            postUploadActions();
            },
        error: function(xhr, status, error)
            {
            if(rscompleted.indexOf(file.id) === -1)
                {
                rscompleted.push(file.id);
                }
            console.log("Error:  " + error);
            jQuery("#upload_log").append("\r\n" + file.name + ": " + error);
            styledalert('<?php echo $lang["error"]?> ', error);
            upRedirBlock = true;
            
            if(processerrors.indexOf(file.id) === -1)
                {
                processerrors.push(file.id);
                }
            }
        }); // End of post upload AJAX
    } // End of processFile()

jQuery('#CentralSpace').on("ProcessedMain",function(){
    // Now safe to process any alternative files that were held back waiting for the original files to upload
    console.debug("Processing " + processafter.length + " alternative files");
    processafter.forEach(function (file){
        if(rsprocessed.indexOf(file.name) == -1)
            {
            // This file hasn't been processed
            console.debug("Processing alternative file: " + file.name);
            processFile(file,true);
            }
        });
    });

function base64encode(str) {
  return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
      function toSolidBytes(match, p1) {
          return String.fromCharCode('0x' + p1);
  }));
}

function postUploadActions()
    {
    if(((rscompleted.length + processerrors.length + Object.keys(processafter).length) == count) && process_alts && processafter.length > 0)
        {
        // Trigger event to begin processing the alternative files
        console.debug("Processed primary files, triggering upload of alternatives");
        jQuery('#CentralSpace').trigger("ProcessedMain");
        process_alts=false;
        return;
        }
    else if(rscompleted.length + processerrors.length < count)
        {
        // More to do, update collection bar
        <?php if (isset($usercollection) && $usercollection==$collection_add)
            { 
            // Update collection div if uploading to active collection
            ?>
            // Prevent too frequent updates that can cause flickering
            if(allowcollectionreload)
                {
                allowcollectionreload = false;
                CollectionDivLoad("<?php echo $baseurl . '/pages/collections.php?nowarn=true&nc=' . time() ?>");
                }
            <?php
            }
        else
            {?> 
            if(allowcollectionreload)
                {
                allowcollectionreload = false;
                CollectionDivLoad("<?php echo $baseurl . '/pages/collections.php?collection=" + newcol + "&nowarn=true&nc=' . time() ?>");
                }
            <?php
            }?>

        setTimeout(function () {
            allowcollectionreload = true;
            }, 2000);
        return;
        }
    
    rscompleted = [];
    processerrors = [];

    CentralSpaceHideProcessing();
    // Upload has completed, perform post upload actions
    console.debug("Upload processing completed");
    CollectionDivLoad("<?php echo $baseurl . '/pages/collections.php?collection=" + newcol + "&nc=' . time() ?>");
    <?php
    if($send_collection_to_admin && $setarchivestate == -1 && !$external_upload) 
        {
        ?>
        api('send_collection_to_admin',{'collection': newcol}, function(response)
            {
            console.debug('A copy of collection #' + newcol + ' has been sent to for review.');
            });
        <?php
        }?>
        
    // if relateonupload input field checked, or relate_on_upload == true
    if(relate_on_upload || jQuery("#relateonupload").is(":checked"))
        {
        console.debug('Relating all resources');
        postdata = {
            'resources': resource_keys,
            }

        api('relate_all_resources',{'related': resource_keys}, function(response)
            {
            console.debug('Completed relating uploaded resources');
            });
        }

    <?php
    if ($redirecturl != "")
        {?>
        if(!upRedirBlock)
            {
            CentralSpaceLoad(redirurl,true);
            }
        <?php
        }
    elseif ($replace_resource>0)
        {?>
        if(!upRedirBlock)
            {
            CentralSpaceLoad('<?php echo $baseurl_short?>pages/view.php?ref=<?php echo $replace_resource; ?>',true);
            }
        <?php
        }
    elseif (($plupload_clearqueue && checkperm("d")) && !$replace)
        {
        $redirect_url_params = array(
            'search'   => '!contributions' . $userref,
            'order_by' => 'resourceid',
            'sort'     => 'DESC',
            'archive'  => $setarchivestate
            );

        if ($setarchivestate == -2 && $pending_submission_prompt_review && checkperm("e-1"))
            {
            $redirect_url_params["promptsubmit"] = 'true';
            }
        if ($collection_add !='false')
            {
            $redirect_url_params['collection_add'] = $collection_add;
            }

        $redirecturl = generateURL($baseurl_short . 'pages/search.php',$redirect_url_params);
        ?>
        if(!upRedirBlock)
            {
            CentralSpaceLoad('<?php echo $redirecturl ?>',true);
            }
        uppy.reset();
        <?php 
        }
    elseif($plupload_clearqueue)
        {
        echo "uppy.reset();";
        }?>

    if(upRedirBlock)
        {
        completedlang = '<?php echo htmlspecialchars($lang["upload_finished_processing"]); ?>';
        completedlang = completedlang.replace('%COUNT%',count);
        completedlang = completedlang.replace('%ERRORS%',processerrors.length);
        uppy.setOptions({
            locale: {
                strings: {
                        uploadComplete: completedlang,
                    },
                },
            });

        // Show popup with option to proceed 
        CentralSpaceHideProcessing();

        jQuery("#modal_dialog").html(completedlang);
        jQuery("#modal_dialog").dialog({
            title:'<?php echo $lang["error"]; ?>',
            modal: true,
            width: 400,
            resizable: false,
            dialogClass: 'no-close',
            buttons: {
                <?php
                if ($redirecturl != "")
                    {
                    echo "'" . $lang['upload_process_successful'] . "' : function() {
                        jQuery(this).dialog('close');
                        CentralSpaceLoad('" . $redirecturl . "',true);                        
                    },";
                    }
                echo "'" . $lang['upload_view_log'] . "' : function() { 
                        jQuery(this).dialog('close');
                        if(!jQuery('#UploadLogSection').is(':visible'))
                            {
                            jQuery('#UploadLogSectionHead').click();
                            }                        
                        jQuery('#upload_continue').show();    
                        pageScrolltop('#UploadLogSection');
                    },";
                    ?>
                }
            });
        }
    }
</script>
<div class="BasicsBox" >
<?php if ($overquota) 
{
echo "<h1>" . $lang["diskerror"] . "</h1><p>" . $lang["overquota"] . "</p>";
include "../include/footer.php";
exit();
}

if  ($alternative!="")
    {
    $alturl = generateURL($baseurl_short . 'pages/alternative_files.php',$searchparams,array("ref"=>$alternative));
    ?>
    <p>
        <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $alturl ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtomanagealternativefiles"]?></a>
    </p><?php 
    }        
elseif ($replace_resource!="")
    {
    $editurl = generateURL($baseurl_short . 'pages/edit.php',$searchparams,array("ref"=>$replace_resource));
    $viewurl = generateURL($baseurl_short . 'pages/view.php',$searchparams,array("ref"=>$replace_resource));
    ?>
    <p>
        <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $editurl ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoeditmetadata"]?></a>
    <br />
        <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $viewurl ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a>
    </p>
    <?php
    }

if ($alternative!="")
    {
    $resource=get_resource_data($alternative);
    if ($alternative_file_resource_preview)
        { 
        $imgpath=get_resource_path($resource['ref'],true,"col",false);
        if (file_exists($imgpath))
            {?>
            <img src="<?php echo get_resource_path($resource['ref'],false,"col",false);?>"/>
            <?php
            }
        }
    if ($alternative_file_resource_title)
        { 
        echo "<h2>" . htmlspecialchars($resource['field'.$view_title_field]) . "</h2><br/>";
        }
    }

# Define the titles:
if ($replace!="") 
    {
    # Replace Resource Batch
    $titleh1 = $lang["replaceresourcebatch"];
    $titleh2 = "";
    $intro = $lang["intro-plupload_upload-replace_resource"];
    }
elseif ($replace_resource!="")
    {
    # Replace file
    $titleh1 = $lang["replacefile"];
    $titleh2 = "";
    $intro = $lang["intro-plupload_upload-replace_resource"];
    }
elseif ($alternative!="")
    {
    # Batch upload alternative files 
    $titleh1 = $lang["alternativebatchupload"];
    $titleh2 = "";
    $intro = $lang["intro-plupload"];
    }
else
    {
    # Add Resource Batch - In Browser 
    $titleh1 = $lang["addresourcebatchbrowser"];
    $intro = $lang["intro-plupload"];
    }	

?>
<?php hook("upload_page_top"); ?>

<?php if (!hook("replacepluploadtitle")){?><h1><?php echo $titleh1 ?></h1><?php } ?>
<div id="upload_instructions"><p><?php echo $intro;render_help_link("user/uploading");?></p></div>
<?php

if (isset($upload_max_file_size))
    {
    if (is_numeric($upload_max_file_size))
        {
        $sizeText = formatfilesize($upload_max_file_size);
        }
    else
        {
        $sizeText = formatfilesize(filesize2bytes($upload_max_file_size));
        }
    echo ' '.sprintf($lang['plupload-maxfilesize'], $sizeText);
    }

hook("additionaluploadtext");

if (isset($allowedmime) && $alternative=='')
    {
    sort($allowedmime);
    $allowed_types=implode(",",$allowedmime);
    ?><p><?php echo str_replace_formatted_placeholder("%extensions", str_replace(",",", ",$allowed_types), $lang['allowedextensions-extensions'])?></p>
    <?php
    } ?>

<div class="BasicsBox">
        <div id="uploader" ></div>
</div>	
<?php
hook ("beforeuploadform");
if(($replace_resource != '' || $replace != '' || $upload_then_edit) && !(isset($alternative) && (int) $alternative > 0) && (display_upload_options() || $replace_resource_preserve_option))
    {
    // Show options on the upload page if in 'upload_then_edit' mode or replacing a resource
    ?>
    <h2 class="CollapsibleSectionHead collapsed" onClick="UICenterScrollBottom();" id="UploadOptionsSectionHead"><?php echo $lang["upload-options"]; ?></h2>
    <div class="CollapsibleSection" id="UploadOptionsSection">
    <form id="UploadForm" class="uploadform FormWide" action="<?php echo $baseurl_short?>pages/upload_batch.php">
    <?php
    generateFormToken("upload_batch");
    
    // Show the option to keep the existing file as alternative when replacing the resource
    if($replace_resource_preserve_option && ($replace_resource != '' || $replace != ''))
        {
        if(!isset($default_replace_resource_original_alt_filename))
            {
            $default_replace_resource_original_alt_filename = '';
            }
        ?>
        <div class="Question">
            <label for="keep_original"><?php echo $lang["replace_resource_preserve_original"]; ?></label>
            <input id="keep_original" type="checkbox" name="keep_original" <?php if($replace_resource_preserve_default) { ?>checked<?php } ?> value="yes">
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label for="replace_resource_original_alt_filename"><?php echo $lang['replace_resource_original_alt_filename']; ?></label>
            <input id="replace_resource_original_alt_filename" type="text" name="replace_resource_original_alt_filename" value="<?php echo $default_replace_resource_original_alt_filename; ?>">
            <div class="clearerleft"></div>
            <script>
            jQuery(document).ready(function () {
                if(jQuery('#keep_original').is(':checked'))
                    {
                    jQuery('#replace_resource_original_alt_filename').parent().show();
                    }
                else
                    {
                    jQuery('#replace_resource_original_alt_filename').parent().hide();
                    }
            });
    
            jQuery('#keep_original').change(function() {
                if(jQuery(this).is(':checked'))
                    {
                    jQuery('#replace_resource_original_alt_filename').parent().show();
                    }
                else
                    {
                    jQuery('#replace_resource_original_alt_filename').parent().hide();
                    }
            });
            </script>
        </div>
        <?php
        }
    elseif ($upload_then_edit && $replace == "" && $replace_resource == "")
        {
        include '../include/edit_upload_options.php';
        }
        
    /* Show the import embedded metadata checkbox when uploading a missing file or replacing a file.
    In the other upload workflows this checkbox is shown in a previous page. */
    if (!hook("replacemetadatacheckbox")) 
        {
        if ((getval("upload_a_file","")!="" || getval("replace_resource","")!=""  || getval("replace","")!="") && $metadata_read)
            { ?>
            <div class="Question">
                <label for="no_exif"><?php echo $lang["no_exif"]?></label><input type=checkbox <?php if ($no_exif){?>checked<?php } ?> id="no_exif" name="no_exif" value="yes">
                <div class="clearerleft"> </div>
            </div>
            <?php
            }
        }

    } // End of upload options
hook('plupload_before_status');
?>
</form>
</div><!-- End of UploadOptionsSection -->

<div class="BasicsBox" >
    <h2 class="CollapsibleSectionHead collapsed" id="UploadLogSectionHead" onClick="UICenterScrollBottom();"><?php echo $lang["log"]; ?></h2>
    <div class="CollapsibleSection" id="UploadLogSection">
        <textarea id="upload_log" rows=10 cols=100 style="width: 100%; border: solid 1px;" ><?php echo  $lang["plupload_log_intro"] . date("d M y @ H:i"); ?></textarea>
    </div> <!-- End of UploadLogSection -->
</div>
</div>

<!-- Continue button, hidden unless errors are encountered so that user can view log before continuing -->
<div class="BasicsBox" >
    <input name="continue" id="upload_continue" type="button" style="display: none;" value="&nbsp;&nbsp;<?php echo $lang['continue']; ?>&nbsp;&nbsp;" 
        onclick="return CentralSpaceLoad('<?php echo $redirecturl?>',true);">
</div>    
<?php

hook("upload_page_bottom");
include "../include/footer.php";
