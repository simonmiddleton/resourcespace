<?php
include "../include/db.php";

// The collection_add parameter can have the following values:-
//  'new'       Add to new collection
//  'false'     Do not add to collection
//  'undefined' Not passed in, so replace it with the current user collection
//  is_numeric  Use this collection  
$collection_add = getvalescaped('collection_add', 'false');
$external_upload = upload_share_active();
if($collection_add =='false' && $external_upload)
    {
    $collection_add = $external_upload;
    }

// External share support
$k = getvalescaped('k','');
if ($k=="" || (!check_access_key_collection($collection_add,$k)))
    {
    include "../include/authenticate.php";
    if (! (checkperm("c") || checkperm("d")))
        {
        exit ("Permission denied.");
        }
    }

include "../include/image_processing.php";

$overquota                              = overquota();
$status                                 = '';
$resource_type                          = getvalescaped('resource_type', '');
$collectionname                         = getvalescaped('entercolname', '');
$search                                 = getvalescaped('search', '');
$offset                                 = getvalescaped('offset', '', true);
$order_by                               = getvalescaped('order_by', '');
$no_exif_raw                            = getval('no_exif', $metadata_read_default ? '' : 'yes');
$no_exif                                = $no_exif_raw == "yes" || $no_exif_raw =="1" ? true : false;
$autorotate                             = getval('autorotate','') == 'true';
// This is the archive state for searching, NOT the archive state to be set from the form POST
$archive                                = getvalescaped('archive', '', true);

$setarchivestate                        = getvalescaped('status', '', true);
// Validate this workflow state is permitted or set the default if nothing passed 
$setarchivestate                        = get_default_archive_state($setarchivestate);
$alternative                            = getvalescaped('alternative', ''); # Batch upload alternative files
$replace                                = getvalescaped('replace', ''); # Replace Resource Batch
$batch_replace_min                      = getval("batch_replace_min",0,true); # Replace Resource Batch - minimum ID of resource to replace
$batch_replace_max                      = getval("batch_replace_max",0,true); # Replace Resource Batch - maximum ID
$batch_replace_col                      = getval("batch_replace_col",0,true); # Replace Resource Batch - collection to replace

$replace_resource                       = getvalescaped('replace_resource', ''); # Option to replace existing resource file
$replace_resource_original_alt_filename = getvalescaped('replace_resource_original_alt_filename', '');
$single                                 = getval("single","") != "" || getval("forcesingle","") != "";
$upload_here                            = (getval('upload_here', '') != '' ? true : false);

$chunk       = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
$chunks      = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
$plfilename  = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
$queue_index = isset($_REQUEST['queue_index']) ? intval($_REQUEST['queue_index']) : 0;

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
$all_resource_types = get_resource_types();
if($resource_type == "")
	{
	foreach($all_resource_types as $restype)
		{
		if (!checkperm("XU" . $restype["ref"]))
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
if($collection_add == "new" && (!$upload_then_edit || ($queue_index == 0 && $chunk == $chunks-1)))
	{
	# The user has chosen Create New Collection from the dropdown.
	if ($collectionname=="")
        {
        $collectionname = "Upload " . date("YmdHis"); # Do not translate this string, the collection name is translated when displayed!
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
	}
    
if($external_upload)
    {
    $rs_session = get_rs_session_id(true);
    $ci=get_session_collections($rs_session,$userref,true);
    if (count($ci)==0)
        {
        $usercollection = create_collection($userref,"New uploads",1,1,0,false,array("type" => COLLECTION_SHARE_UPLOAD));
        }
    else
        {
        $usercollection = $ci[0];
        }
    $upload_review_col = $usercollection;
    $redirecturl = generateURL(
        "{$baseurl}/pages/edit.php",
        array('upload_review_mode' => true,
              'collection' => $usercollection,
              'k' => $k)
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

    if($queue_index == 0)
        {        
        // Clear out review collection before new uploads are added to prevent inadvertent edits of old uploads
        remove_all_resources_from_collection(0-$userref);
        }
    $redirecturl_extra_params = array();

	# Set the redirect after upload to the start of the edit process
    if($alternative != "") 
        {
        $redirecturl = generateURL(
            "{$baseurl}/pages/view.php",
            array(
                'ref' => $alternative
            ),
            $redirecturl_extra_params);	
        }
    else
        {
        $redirecturl = generateURL(
            "{$baseurl}/pages/edit.php",
            array(
                'upload_review_mode' => true
            ),
            $redirecturl_extra_params);	
        }

	# Clear the user template
	clear_resource_data(0-$userref);
	}

$modify_redirecturl=hook('modify_redirecturl');
if($modify_redirecturl!==false)
	{
	$redirecturl=$modify_redirecturl;
	}

# Fallback to current user collection if nothing was passed in
if($collection_add=='undefined')
    {
    $collection_add=$usercollection;    
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
    'single'                                 => ($single ? "true" : "false"),
    'status'                                 => $setarchivestate,
    'k'                                      => $k,
);

global $merge_filename_with_title, $merge_filename_with_title_default;
if($merge_filename_with_title) {

    $merge_filename_with_title_option = urlencode(getval('merge_filename_with_title_option', $merge_filename_with_title_default));
    $merge_filename_with_title_include_extensions = urlencode(getval('merge_filename_with_title_include_extensions', ''));
    $merge_filename_with_title_spacer = urlencode(getval('merge_filename_with_title_spacer', ''));
    
    if(strtolower($merge_filename_with_title_option) != '') {
        $uploadparams['merge_filename_with_title_option'] =  $merge_filename_with_title_option;
    }
    
    if($merge_filename_with_title_include_extensions != '') {
        $uploadparams['merge_filename_with_title_include_extensions']=$merge_filename_with_title_include_extensions;
    }

    if($merge_filename_with_title_spacer != '') {
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
    $uploadurl_extra_params = array(
        'upload_here' => $upload_here,
        'search' => $search,
        'resource_type' => $resource_type,
        'status' => $setarchivestate,
        );
    }

$uploadurl = generateURL("{$baseurl}/pages/upload_plupload.php", $uploadparams, $uploadurl_extra_params) . hook('addtopluploadurl');

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

if(!upload_share_active())
    {
    refresh_collection_frame($usercollection);
    }
elseif (is_numeric($collection_add))
	{
	# Switch to the selected collection (existing or newly created) and refresh the frame.
 	set_user_collection($userref,$collection_add);
 	refresh_collection_frame($collection_add);
 	}	

if($send_collection_to_admin && $archive == -1 && getvalescaped('ajax' , 'false') == true && getvalescaped('ajax_action' , '') == 'send_collection_to_admin') 
	{
    $collection_id = getvalescaped('collection' , '');
	if($collection_id == '')
		{
        exit();
		}

    // Create a copy of the collection for admin:
    $admin_copy = create_collection(-1, $lang['send_collection_to_admin_emailedcollectionname']);
    copy_collection($collection_id, $admin_copy);
    $collection_id = $admin_copy;

    // Get the user (or username) of the contributor:
    $user = get_user($userref);
    if(isset($user) && trim($user['fullname']) != '') {
        $user = $user['fullname'];
    } else {
        $user = $user['username'];
    }

    // Get details about the collection:
    $collection = get_collection($collection_id);
    $collection_name = $collection['name'];
    $resources_in_collection = count(get_collection_resources($collection_id));

    // Build mail and send it:
    $subject = $applicationname . ': ' . $lang['send_collection_to_admin_emailsubject'] . $user;

    $message = $user . $lang['send_collection_to_admin_usercontributedcollection'] . "\n\n";
    $message .= $baseurl . '/pages/search.php?search=!collection' . $collection_id . "\n\n";
    $message .= $lang['send_collection_to_admin_additionalinformation'] . "\n\n";
    $message .= $lang['send_collection_to_admin_collectionname'] . $collection_name . "\n\n";
    $message .= $lang['send_collection_to_admin_numberofresources'] . $resources_in_collection . "\n\n";
	
	$notification_message = $lang['send_collection_to_admin_emailsubject'] . " " . $user;
	$notification_url = $baseurl . '/?c=' . $collection_id;
	$admin_notify_emails = array();
	$admin_notify_users = array();
	$notify_users=get_notification_users(array("e-1","e0")); 
	foreach($notify_users as $notify_user)
		{
		get_config_option($notify_user['ref'],'user_pref_resource_access_notifications', $send_message, $admin_resource_access_notifications);		  
		if($send_message==false){continue;}		
		
		get_config_option($notify_user['ref'],'email_user_notifications', $send_email);    
		if($send_email && $notify_user["email"]!="")
			{
			$admin_notify_emails[] = $notify_user['email'];				
			}        
		else
			{
			$admin_notify_users[]=$notify_user["ref"];
			}
		}
	foreach($admin_notify_emails as $admin_notify_email)
		{
		send_mail($admin_notify_email, $subject, $message, '', '');
    	}
	
	if (count($admin_notify_users)>0)
		{
		global $userref;
        message_add($admin_notify_users,$notification_message,$notification_url, $userref, MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN,MESSAGE_DEFAULT_TTL_SECONDS,SUBMITTED_COLLECTION, $collection_id);
		}
    exit();
	}

global $php_path,$relate_on_upload,$enable_related_resources;
if($relate_on_upload && $enable_related_resources && getval("uploaded_refs", "") != "" && enforcePostRequest(getval("ajax", false)))
    {
    $resource_refs = getval("uploaded_refs", "");
    $valid_refs    = array();

    foreach($resource_refs as $resource_ref)
        {
        if(!is_numeric($resource_ref))
            {
            exit("NUMERIC values ONLY");
            }

        $valid_refs[] = $resource_ref;
        }

    $stringlist = implode(',', $valid_refs);

    if($stringlist !== "")
        {
        exec($php_path . "/php " . dirname(__FILE__)."/tools/relate_resources.php \"" . $stringlist. "\" " . escapeshellarg($_SERVER["HTTP_HOST"]) . " > /dev/null 2>&1 &");
        exit("Resource Relation Started: " . $stringlist);
        }
    }

#handle posts
if ($_FILES)
	{
	/**
	 * upload.php
	 *
	 * Copyright 2009, Moxiecode Systems AB
	 * Released under GPL License.
	 *
	 * License: http://www.plupload.com/license
	 * Contributing: http://www.plupload.com/contributing
	 */
        
    // HTTP headers for no cache etc
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	// Settings
    if(upload_share_active())
        {
        $session_hash = $rs_session;
        }
	$targetDir = get_temp_dir() . DIRECTORY_SEPARATOR . "plupload" . DIRECTORY_SEPARATOR . $session_hash;

	$cleanupTargetDir = true; // Remove old files
	$maxFileAge = 5 * 3600; // Temp file age in seconds
	set_time_limit($php_time_limit);
    debug("PLUPLOAD - receiving file from user " . $username . ",  filename " . $plfilename . ", chunk " . $chunk . " of " . $chunks);
        
	# Work out the extension
	$extension=explode(".",$plfilename);
	$extension=trim(strtolower($extension[count($extension)-1]));

	# Banned extension?
	global $banned_extensions;
	if (in_array($extension,$banned_extensions) || ($allowed_extensions!="" && !in_array($extension,explode(",",$allowed_extensions))))
		{
        debug("PLUPLOAD - invalid file extension received from user " . $username . ",  filename " . $plfilename . ", chunk " . $chunk . " of " . $chunks);
       	die('{"jsonrpc" : "2.0", "error" : {"code": 105, "message": "Banned file extension."}, "id" : "id"}');
		}
	hook('additional_plupload_checks');
	// Clean the filename for security reasons
	if($replace){$origuploadedfilename=escape_check($plfilename);}
	$plfilename = preg_replace('/[^\w\.-]+/', '_', $plfilename);
	
	// Make sure the fileName is unique but only if chunking is disabled
	if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $plfilename)) {
		$ext = strrpos($plfilename, '.');
		$plfilename_a = substr($plfilename, 0, $ext);
		$plfilename_b = substr($plfilename, $ext);

		$count = 1;
		while (file_exists($targetDir . DIRECTORY_SEPARATOR . $plfilename_a . '_' . $count . $plfilename_b))
			$count++;

		$plfilename = $plfilename_a . '_' . $count . $plfilename_b; 
	}

	$plfilepath = $targetDir . DIRECTORY_SEPARATOR . $plfilename;
	
	// Create target dir
	if (!file_exists($targetDir))
            {
	    debug("PLUPLOAD - creating temporary folder " . $plfilepath . " for file received from user " . $username . ",  filename " . $plfilename . ", chunk " . ($chunk+1) . " of " . $chunks);       		
            @mkdir($targetDir,0777,true);
            }

	// Remove old temp files	
	if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir)))
            {
		while (($file = readdir($dir)) !== false) {
			$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

			// Remove temp file if it is older than the max age and is not the current file
			if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$plfilepath}.part")) {
				@unlink($tmpfilePath);
			}
		}

		closedir($dir);
            }
        else
            {
            debug("PLUPLOAD - failed to open temporary folder " . $targetDir . " for file received from user " . $username . ",  filename " . $plfilename . ", chunk " . ($chunk+1)  . " of " . $chunks);       		
            die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }

    // Check the chunk and file have not been processed before for this filename
    $plupload_processed_filepath = $targetDir . DIRECTORY_SEPARATOR . 'processing_' . $plfilename . '.txt';
    if(!$plupload_allow_duplicates_in_a_row && file_exists($plupload_processed_filepath))
        {
        // Get current chunk, queue index and filename so we can know if we processed it before or not
        $processed_file_content = file_get_contents($plupload_processed_filepath);
        $processed_file_content = explode(',', $processed_file_content);
        
        if ($chunk != 0){
        // If this chunk-file-filename has been processed, don't process it again
        if($chunk == $processed_file_content[0] && $queue_index == $processed_file_content[1])
            {
            debug("PLUPLOAD - Duplicate chunk [" . $chunk . "] of file " . $plfilename . " found at index [" . $queue_index . "] in the upload queue");
            die('{"jsonrpc" : "2.0", "error" : {"code": 110, "message": "Duplicate chunk [' . $chunk . '] of file ' . $plfilename . ' found at index [' . $queue_index . '] in the upload queue"}, "id" : "id"}');
            }
        }}

	// Look for the content type header
	if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
		$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

	if (isset($_SERVER["CONTENT_TYPE"]))
		$contentType = $_SERVER["CONTENT_TYPE"];

	// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
	if (strpos($contentType, "multipart") !== false) {
                debug("PLUPLOAD - handling non-multipart upload file received from user " . $username . ",  filename " . $plfilename . ", chunk " . ($chunk+1) . " of " . $chunks);       		
            	if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']))
                    {
                    // Open temp file
                    $out = fopen("{$plfilepath}.part", $chunk == 0 ? "wb" : "ab");
                    if ($out)
                        {
                        debug("PLUPLOAD - adding data from " . $_FILES['file']['tmp_name'] . " to " . $plfilepath . ".part. file received from user " . $username . ",  filename " . $plfilename . ", chunk " . ($chunk+1)  . " of " . $chunks);
                       
                        // Read binary input stream and append it to temp file
                        $in = fopen($_FILES['file']['tmp_name'], "rb");

                        if ($in) {
                                while ($buff = fread($in, 4096))
                                        fwrite($out, $buff);
                        } else
                                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                        fclose($in);
                        fclose($out);
                        @unlink($_FILES['file']['tmp_name']);

                        // Write in the processed file (keep track of the last processed chunk)
                        $processed_file_handle      = fopen($plupload_processed_filepath, 'w');
                        $processed_file_new_content = "{$chunk},{$queue_index}";
                        fwrite($processed_file_handle, $processed_file_new_content);
                        fclose($processed_file_handle);
                        }
                    else
                        {
                        debug("PLUPLOAD ERROR- failed  to open temp file " . $plfilepath . ".part. file received from user " . $username . ",  filename " . $plfilename . ", chunk " . ($chunk+1)  . " of " . $chunks);
                        die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
                        }
                    }
                else
                    {
		            debug("PLUPLOAD ERROR- failed  to find temp file " . $_FILES['file']['tmp_name'] . " file received from user " . $username . ",  filename " . $plfilename . ", chunk " . ($chunk+1)  . " of " . $chunks);
                    die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
                    }
	} else {
		// Open temp file
		$out = fopen("{$plfilepath}.part", $chunk == 0 ? "wb" : "ab");
		if ($out)
                    {
                    debug("PLUPLOAD - adding data to " . $plfilepath . ".part. file received from user " . $username . ",  filename " . $plfilename . ", chunk " . ($chunk+1)  . " of " . $chunks);
                       
                    // Read binary input stream and append it to temp file
                    $in = fopen("php://input", "rb");

                    if ($in) {
                            while ($buff = fread($in, 4096))
                                    fwrite($out, $buff);
                    } else
                            die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

                    fclose($in);
                    fclose($out);

                    // Write in the processed file (keep track of the last processed chunk)
                    $processed_file_handle      = fopen($plupload_processed_filepath, 'w');
                    $processed_file_new_content = "{$chunk},{$queue_index}";
                    fwrite($processed_file_handle, $processed_file_new_content);
                    fclose($processed_file_handle);
                    }
                else
                    {
                    debug("PLUPLOAD ERROR- failed  to open temp file " . $plfilepath . ".part. file received from user " . $username . ",  filename " . $plfilename . ", chunk " . ($chunk+1) . " of " . $chunks);
                    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
                    }
        }

	// Check if file has been uploaded
	if (!$chunks || $chunk == $chunks - 1)
        {
        debug("PLUPLOAD - processing completed upload of file received from user " . $username . ",  filename " . $plfilename . ", chunk " . ($chunk+1) . " of " . $chunks);

        // Strip the temp .part suffix off 
        rename("{$plfilepath}.part", $plfilepath);

        # Additional ResourceSpace upload code

        # Check for duplicate files if required
        $duplicates=check_duplicate_checksum($plfilepath,$replace_resource);
        if(count($duplicates)>0)
            {
            debug("PLUPLOAD ERROR- duplicate file matches resources" . implode(",",$duplicates));
            die('{"jsonrpc" : "2.0", "error" : {"code": 108, "message": "Duplicate file upload, file matches resources: ' . implode(",",$duplicates) . '", "duplicates": "' . implode(",",$duplicates) . '"}, "id" : "id", "collection" : "' . $collection_add . '" }'); 
            }

        $plupload_upload_location=$plfilepath;
        if(!hook("initialuploadprocessing"))
            {			
            if ($alternative!="")
                {
                # Upload an alternative file 
                $resource_data = get_resource_data($alternative);
                if($resource_data["lock_user"] > 0 && $resource_data["lock_user"] != $userref)
                    {
                    $error = get_resource_lock_message($resource_data["lock_user"]);
                    die('{"jsonrpc" : "2.0", "error" : {"code": 111, "message": "' . $error  . '"}, "id" : "id"}');
                    }

                # Add a new alternative file
                $aref=add_alternative_file($alternative,$plfilename);
                
                # Find the path for this resource.
                $path=get_resource_path($alternative, true, "", true, $extension, -1, 1, false, "", $aref);
                
                # Move the sent file to the alternative file location
                
                # PLUpload - file was sent chunked and reassembled - use the reassembled file location
                $result=rename($plfilepath, $path);

                if ($result===false)
                    {
                    die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Failed to move uploaded file. Please check the size of the file you are trying to upload."}, "id" : "id"}');
                    }

                chmod($path,0777);
                $file_size = @filesize_unlimited($path);
                
                # Save alternative file data.
                sql_query("update resource_alt_files set file_name='" . escape_check($plfilename) . "',file_extension='" . escape_check($extension) . "',file_size='" . $file_size . "',creation_date=now() where resource='$alternative' and ref='$aref'");
                
                if ($alternative_file_previews)
                    {
                    create_previews($alternative,false,$extension,false,false,$aref);
                    }

                hook('after_alt_upload','',array($alternative,array("ref"=>$aref,"file_size"=>$file_size,"extension"=>$extension,"name"=>$plfilename,"altdescription"=>"","path"=>$path,"basefilename"=>str_ireplace("." . $extension, '', $plfilename))));

                // Check to see if we need to notify users of this change							
                if($notify_on_resource_change_days!=0)
                    {								
                    // we don't need to wait for this..
                    ob_flush();flush();
                    notify_resource_change($alternative);
                    }

                // Remove chunk tracking file as upload successful
                if(file_exists($plupload_processed_filepath))
                    {
                    unlink($plupload_processed_filepath);
                    }

                # Update disk usage
                update_disk_usage($alternative);

                die(
                    json_encode(
                        array(
                            'jsonrpc' => '2.0',
                            'message' => htmlspecialchars($lang["alternative_file_created"]),
                            'id'      => htmlspecialchars($alternative)
                        )
                    )
                );
                }

            if ($replace=="" && $replace_resource=="")
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

                # check that $ref is not false - possible return value with create_resource()
                if(!$ref)
                    {
                    die('{"jsonrpc" : "2.0", "error" : {"code": 125, "message": "Failed to create resource with given resource type: ' . $resource_type . '"}}');    
                    }
                

                // Check valid requested state by calling function that checks permissions
                update_archive_status($ref, $setarchivestate);
                
                if($upload_then_edit && $upload_here)
                    {
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
                
                $relateto = getvalescaped("relateto","",true);   
                if($relateto!="" && !upload_share_active())
                    {
                    // This has been added from a related resource upload link
                    sql_query("insert into resource_related(resource,related) values ($relateto,$ref)");
                    }

                // For upload_then_edit mode ONLY, we decide the resource type based on the extension. User
                // can later change this at the edit stage
                // IMPORTANT: we change resource type only if user has access to it
                if($upload_then_edit && !$resource_type_force_selection)
                    {
                    $resource_type_from_extension = get_resource_type_from_extension(
                        pathinfo($plupload_upload_location, PATHINFO_EXTENSION),
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
                
                $status=upload_file($ref,($no_exif=="yes" && getval("exif_override","")==""),false,$autorotate,$plupload_upload_location);

                if($status && $auto_generated_resource_title_format != '' && !$upload_then_edit)
                    {
                    $new_auto_generated_title = '';
                    $ref_escaped = escape_check($ref);

                    if(strpos($auto_generated_resource_title_format, '%title') !== false)
                        {
                        $view_title_field_escaped = escape_check($view_title_field);

                        $resource_detail = sql_query ("
                            SELECT r.ref, r.file_extension, rd.value
                            FROM resource r
                            LEFT JOIN resource_data AS rd ON r.ref = rd.resource
                            AND rd.resource_type_field = '{$view_title_field_escaped}'
                            WHERE r.ref = '{$ref_escaped}'
                                        ");

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
                        $resource_detail = sql_query ("
                                SELECT r.ref, r.file_extension
                                FROM resource r
                                WHERE r.ref = '{$ref_escaped}'");

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

                if(file_exists($plupload_processed_filepath))
                    {
                    unlink($plupload_processed_filepath);
                    }
                    
                $wait = hook('afterpluploadfile', '', array($ref, $extension));
                die('{"jsonrpc" : "2.0", "message" : "' . $lang["created"] . '", "id" : "' . htmlspecialchars($ref) . '", "collection" : "' . $collection_add . '" }');
                }
            else if ($replace=="" && $replace_resource!="")
                {
                // Replacing an existing resource file
                // Extract data unless user has selected not to extract exif data and there are no per field options set
                $no_exif = ('yes' == $no_exif) && '' == getval('exif_override', '');
                $keep_original = getval('keep_original', '') != '';
                $success = replace_resource_file($replace_resource,$plupload_upload_location,$no_exif,$autorotate,$keep_original);
                if (!$success)
                    {
                    die('{"jsonrpc" : "2.0", "error" : {"code": 109, "message": "Failed to replace resource file"}, "id" : "' . htmlspecialchars($replace_resource) . '"}');
                    }
                if(file_exists($plupload_processed_filepath))
                    {
                    unlink($plupload_processed_filepath);
                    }

                die('{"jsonrpc" : "2.0", "message" : "' . $lang["replacefile"] . '", "id" : "' . htmlspecialchars($replace_resource) . '"}');
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
                    $replace_resources = sql_array("SELECT ref value FROM resource WHERE ref >= '" . $batch_replace_min . "' " . (($batch_replace_max > 0) ? " AND ref <= '" . $batch_replace_max . "'" : "") . " ORDER BY ref ASC",0);
                    debug("batch_replace upload: replacing files for resource IDs. Min ID: " . $batch_replace_min  . (($batch_replace_max > 0) ? " Max ID: " . $batch_replace_max : ""));
                    }
                else
                    {
                    $replace_resources = get_collection_resources($batch_replace_col);
                    debug("batch_replace upload: replacing resources within collection " . $batch_replace_col . " only");
                    }
                    
                $filename_field=getvalescaped("filename_field",0,true);
                if($filename_field != 0)
                    {
                    $target_resource=sql_array("select resource value from resource_data where resource_type_field='$filename_field' and value='$origuploadedfilename' AND resource>'$fstemplate_alt_threshold'","");
                    $target_resource=array_values(array_intersect($target_resource,$replace_resources));
                    if(count($target_resource)==1  && !resource_file_readonly($target_resource[0]))
                        {
                        // A single resource has been found with the same filename                                    
                        $success = replace_resource_file($target_resource[0],$plupload_upload_location,$no_exif,$autorotate,$keep_original);
                        if (!$success)
                            {
                            die('{"jsonrpc" : "2.0", "error" : {"code": 109, "message": "Failed to replace resource file"}, "id" : "' . htmlspecialchars($target_resource[0]) . '"}');
                            }
                        unlink($plupload_upload_location);
                        if(file_exists($plupload_processed_filepath))
                            {
                            unlink($plupload_processed_filepath);
                            }

                        die('{"jsonrpc" : "2.0", "message" : "' . $lang["upload_success"] . ' - ' . $lang["replacefile"] . '", "id" : "' . htmlspecialchars($target_resource[0]) . '"}');
                        }
                    elseif(count($target_resource)==0)
                        {
                        // No resource found with the same filename
                        header('Content-Type: application/json');
                        unlink($plupload_upload_location);
                        if(file_exists($plupload_processed_filepath))
                            {
                            unlink($plupload_processed_filepath);
                            }
                        die('{"jsonrpc" : "2.0", "error" : {"code": 106, "message": "ERROR - no valid resource to replace found with filename ' . $origuploadedfilename . '"}, "id" : "id"}');
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
                                $success = replace_resource_file($replaced,$plupload_upload_location,$no_exif,$autorotate,$keep_original);
                                if (!$success)
                                    {
                                    die('{"jsonrpc" : "2.0", "error" : {"code": 109, "message": "Failed to replace resource file"}, "id" : "' . htmlspecialchars($replaced) . '"}');
                                    }
                                if(file_exists($plupload_processed_filepath))
                                    {
                                    unlink($plupload_processed_filepath);
                                    }
                                $status = upload_file($replaced, ('yes' == $no_exif && '' == getval('exif_override', '')), false, $autorotate, $plupload_upload_location);
                                }
                            unlink($plfilepath);
                            die('{"jsonrpc" : "2.0", "message" : "' . $lang["replacefile"] . '", "id" : "' . $resourcelist . '"}');
                            }
                        else
                            {
                            // Multiple resources found with the same filename
                            header('Content-Type: application/json');
                            unlink($plupload_upload_location);
                            if(file_exists($plupload_processed_filepath))
                                {
                                unlink($plupload_processed_filepath);
                                }
                            die('{"jsonrpc" : "2.0", "error" : {"code": 107, "message": "ERROR - multiple valid resources found with filename ' . $origuploadedfilename . '. Resource IDs : ' . $resourcelist . '"}, "id" : "id" }');
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
                            
                            $success = replace_resource_file($ref,$plupload_upload_location,$no_exif,$autorotate,$keep_original);
                            if (!$success)
                                {
                                die('{"jsonrpc" : "2.0", "error" : {"code": 109, "message": "Failed to replace resource file"}, "id" : "' . htmlspecialchars($ref) . '"}');
                                }

                            die('{"jsonrpc" : "2.0", "message" : "' . $lang["replacefile"] . '", "id" : "' . htmlspecialchars($ref) . '"}');
                            }
                        else
                            {
                            // No resource found with the same filename
                            debug("batch_replace upload: No valid resource id for filename " . $origuploadedfilename);
                            header('Content-Type: application/json');
                            unlink($plfilepath);

                            if(file_exists($plupload_processed_filepath))
                                {
                                unlink($plupload_processed_filepath);
                                }
                            die('{"jsonrpc" : "2.0", "error" : {"code": 106, "message": "ERROR - no valid resource ID matching filename ' . $origuploadedfilename . '"}, "id" : "id"}');
                            }
                        }
                    exit();
                    }
                }
            }
        }

    // Return JSON-RPC response
    die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
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
    redirect($baseurl_short."pages/edit.php?refreshcollectionframe=true&ref=" . $ref."&search=".urlencode($search)."&offset=".$offset."&order_by=".$order_by."&sort=".$sort."&archive=".$archive);
	}

$headerinsert.="
<link type='text/css' href='$baseurl/css/smoothness/jquery-ui.min.css?css_reload_key=$css_reload_key' rel='stylesheet' />";

include "../include/header.php";
?>


<script>

<?php
echo "show_upload_log=" . (($show_upload_log)?"true;":"false;");

?>
var resource_keys=[];
var processed_resource_keys=[];
var relate_on_upload = <?php echo ($store_uploadedrefs ||($relate_on_upload && $enable_related_resources && getval("relateonupload","")==="yes")) ? " true" : "false"; ?>;
// Set flag allowing for blocking auto redirect after upload if errors are encountered at upload
upRedirBlock = false;
if(typeof newcol != 'undefined')
    {
    delete(newcol);
    }

// A mapping used by subsequent file uploads of alternatives to know to which resource to add the files as alternatives
// Used when the original file and its alternatives are uploaded in a batch to a collection
var resource_ids_for_alternatives = [];

// Create a container so we can reference it later
function uploaderReference ()
    {
    this.object = null;
    }




plupload.addFileFilter('valid_filename', function(check_filename, file, cb) 
    {
    var fname = file.name;

    var pattern = "[\"<>`=&]"; // regex pattern to escape characters in file name
    // escape file name
    var fname_escaped = escape_HTML(fname, pattern);
    file.name = fname_escaped;

    if ((fname.match(pattern) != null) && check_filename == true) 
        {
        styledalert("Resource cannot be uploaded", "Reason: invalid characters in filename" );

        this.trigger('Error', 
            {
            code : self.FILE_NAME_ERROR,
            message : ("File name error."),
            file : file
            });
            cb(false);
        } else 
        {
        cb(true);
        } 
    });

plup = new uploaderReference

var pluploadconfig = {
        // General settings
        runtimes : '<?php echo $plupload_runtimes ?>',
        url: '<?php echo $uploadurl; ?>',
         <?php if ($plupload_chunk_size!="")
                {?>
                chunk_size: '<?php echo $plupload_chunk_size; ?>',
                <?php
                }
        if (isset($plupload_max_file_size)) echo "max_file_size: '$plupload_max_file_size',"; ?>
        multiple_queues: true,
        max_retries: <?php echo $plupload_max_retries; ?>,
		<?php if ($plupload_widget){?>
		views: {
            list: true,
            thumbs: <?php if ($plupload_widget_thumbnails){?>true<?php } else {?>false<?php }?>, // Show thumbs
            active: <?php if ($plupload_widget_thumbnails){?>'thumbs'<?php } else { ?>'list'<?php } ?>
        },
        rename:true,
		<?php } ?>
        <?php if ($replace_resource > 0 || $single){?>
        multi_selection:false,
        rename: true,
        <?php }
        if ($allowed_extensions!=""){
                // Specify what files can be browsed for
                $allowed_extensions=str_replace(", ",",",$allowed_extensions);
                $allowedlist=explode(",",trim($allowed_extensions));
                sort($allowedlist);
                $allowed_extensions=implode(",",$allowedlist);
                $allowed_extension_filters = ",title: '" . $lang["allowedextensions"] . "', extensions : '$allowed_extensions'";
            } else {
                $allowed_extension_filters = "";
            }  ?>
        filters : {valid_filename: true <?php echo $allowed_extension_filters ?>},
        // Flash settings
        flash_swf_url: '../lib/plupload_2.1.8/Moxie.swf',

        // Silverlight settings
        silverlight_xap_url : '../lib/plupload_2.1.8/Moxie.xap',
        dragdrop: true,  
        logopened: false,      
        
        preinit: {
                PostInit: function(uploader) {

                    plup.object = uploader;                    
                    <?php hook('upload_uploader_defined'); ?>
        
                        //Show link to java if chunking not supported
                        if(!uploader.features.chunks){jQuery('#plupload_support').slideDown();}
                
                        <?php if ($plupload_autostart){?>
                                        uploader.bind('FilesAdded', function(up, files) {
                                                uploader.start();
                                        }); 
                        <?php	}
                
                         if ($replace_resource > 0){?>
                                        uploader.bind('FilesAdded', function(up, files) {
                                                if (uploader.files.length > 1) {
                                                        uploader.removeFile(up.files[1]);
                                                }
                                        });
                        <?php }
                        else { 
                            $help_link = render_help_link("user/uploading",true);
                            ?>
                                //Show diff instructions if supports drag and drop
                                if(!uploader.files.length && uploader.features.dragdrop && uploader.settings.dragdrop)	{jQuery('#plupload_instructions').html('<p><?php echo escape_check($lang["intro-plupload_dragdrop"]) . $help_link?></p>');}
                        <?php }?>
                        
                        uploader.bind('FileUploaded', function(up, file, info) {
                            console.log("bind FileUploaded...");
                                // Process response
                                 try
                                    {
                                    uploadresponse = JSON.parse(info.response);
                                    if (info.response.indexOf("collection") > 0)
                                        {
                                        newcol = uploadresponse.collection;                                            
                                        CollectionDivLoad("<?php echo $baseurl . '/pages/collections.php?collection=" + newcol + "&nowarn=true&nc=' . time() ?>");
                                        }
                                    if (info.response.indexOf("error") > 0)
                                        {
                                        uploaderrormessage = uploadresponse.error.code + " " + uploadresponse.error.message;
                                        if(uploadresponse.error.code==108)
                                            {
                                            styledalert('<?php echo $lang["error"]?>','<?php echo $lang["duplicateresourcefound"]?>');   
                                            message = '<?php echo $lang['error-duplicatesfound']?>';
                                            jQuery("#upload_log").append("\r\n" + message.replace('%resourceref%', uploadresponse.error.duplicates).replace('%filename%', file.name));
                                            if(!uploader.settings.logopened)
                                                {
                                                    jQuery("#UploadLogSectionHead").click();
                                                    uploader.settings.logopened = true;
                                                }
                                            }
                                        else if(uploadresponse.error.code==109)
                                            {
                                            message = uploadresponse.error.message +  ' ' + uploadresponse.id;
                                            styledalert('<?php echo $lang["error"] ?> ' + uploadresponse.error.code, message);   
                                            jQuery("#upload_log").append("\r\n" + message);
                                            if(!uploader.settings.logopened)
                                                {
                                                jQuery("#UploadLogSectionHead").click();
                                                uploader.settings.logopened = true;
                                                }
                                            }
                                        else
                                            {
                                            styledalert('<?php echo $lang["error"]?> ' + uploadresponse.error.code, uploadresponse.error.message);
                                            jQuery("#upload_log").append("\r\n" + uploadresponse.error.message + " [" + uploadresponse.error.code + "]");
                                            }    
                                        upRedirBlock = true;
                                        }
                                    else
                                        {
                                        jQuery("#upload_log").append("\r\n" + file.name + " - " + uploadresponse.message + " " + uploadresponse.id);
                                        if(resource_keys===processed_resource_keys){resource_keys=[];}
                                        resource_keys.push(uploadresponse.id.replace( /^\D+/g, ''));
                                        }
                                    }
                                catch(e)
                                    {
                                    upRedirBlock = true;
                                    uploaderrormessage = 'Server side error! Please check the log and contact the system administrator!';
                                    jQuery("#upload_log").append("\r\n" + jQuery('<html>' + info.response.replace(/\s\s/g," ") + '</html>').text());
                                    styledalert("<?php echo $lang['error']; ?>", uploaderrormessage );
                                    }             
                                
                                // When uploading a batch of files and their alternatives, keep track of the resource ID
                                // and the filename it is associated with
                                <?php
                                if($attach_alternatives_found_to_resources)
                                    {
                                    ?>
                                    var alternative_suffix   = '<?php echo trim($upload_alternatives_suffix); ?>';
                                    var uploaded_resource_response = JSON.parse(info.response);
                                    var uploaded_resource_id = uploaded_resource_response['id'];
                                    var filename             = file.name;
                                    var filename_ext         = getFilePathExtension(filename);

                                    if(filename_ext != '')
                                        {
                                        filename = filename.substr(0, file.name.lastIndexOf('.' + filename_ext));
                                        }
                                    
                                    // Add resource ID - filename map only for original resources
                                    if(filename.lastIndexOf(alternative_suffix) === -1)
                                        {
                                        resource_ids_for_alternatives[uploaded_resource_id] = filename;
                                        }

                                    <?php
                                    }?>
                                        
                                //Update collection div if uploading to active collection
                                <?php if ($usercollection==$collection_add) { ?>
                                        CollectionDivLoad("<?php echo $baseurl . '/pages/collections.php?nowarn=true&nc=' . time() ?>");
                                        <?php } ?>
                                <?php hook("afterfileuploaded");?> 
                                });
                
                
                        // Add flag so that upload_plupload.php can tell if this is the last file.
                        uploader.bind('BeforeUpload', function(up, files) {
                            
                            <?php
                            if ($upload_then_edit && $replace == "" && $replace_resource == "" && !upload_share_active())
                                {?>
                                if(typeof newcol == 'undefined')
                                    {
                                    newcol = jQuery('#collection_add').val();
                                    }
                                
                                newcolname = jQuery('#entercolname').val();
                                uploader.settings.url = ReplaceUrlParameter(uploader.settings.url,'collection_add',newcol);
                                uploader.settings.url = ReplaceUrlParameter(uploader.settings.url,'entercolname',newcolname);
                                <?php
                                } ?>
                            // Add index of file in queue so we can know which file is being processed
                            uploader.settings.url = ReplaceUrlParameter(uploader.settings.url,'queue_index',uploader.total.uploaded);
							if(uploader.total.uploaded == uploader.files.length-1)
                                {
                                uploader.settings.url = ReplaceUrlParameter(uploader.settings.url,'lastqueued','true');
                                }
							else
								{
								uploader.settings.url = ReplaceUrlParameter(uploader.settings.url,'lastqueued','');                                	
								}
                            jQuery('.pluploadform input').prop('disabled','true'); 
                            jQuery('.pluploadform select').prop('disabled','true');   
                            <?php hook('beforeupload_end'); ?>
                        });       
                
                          <?php 
						  
                            if($send_collection_to_admin) { ?>
                                uploader.bind('UploadComplete', function(up, files) {

                                    jQuery.ajax({
                                        type: 'POST',
                                        url: '<?php echo $baseurl_short; ?>pages/upload_plupload.php',
                                        data: {
                                            ajax: 'true',
                                            ajax_action: 'send_collection_to_admin',
                                            collection: '<?php echo $collection_add; ?>',
                                            archive: '<?php echo $setarchivestate; ?>',
                                            <?php echo generateAjaxToken('UploadComplete'); ?>
                                        }
                                    });
                                    console.log('A copy of the collection ID <?php echo $collection_add; ?> has been sent via e-mail to admin.');
                                });
                            
                            <?php } ?>
                            
                            uploader.bind('UploadComplete', function(up, files) {
                                // if relateonupload input field checked, or relate_on_upload == true
                                if(relate_on_upload || jQuery("#relateonupload").is(":checked"))
                                    {
                                    jQuery.post("<?php echo $baseurl_short; ?>pages/upload_plupload.php",
                                            {
                                            uploaded_refs: resource_keys,
                                            queue_index: 1,
                                            <?php echo generateAjaxToken("plupload-UploadComplete"); ?>
                                            }
                                        );
                                    }
                                processed_resource_keys=resource_keys;
                            });                           
					<?php	  
				    if ($redirecturl!=""){?>
                                  //remove the completed files once complete
                                  uploader.bind('UploadComplete', function(up, files) {
                                  if(!upRedirBlock)
                                      {
                                      CentralSpaceLoad('<?php echo $redirecturl ?>',true);
                                      }
                                  upRedirBlock = false; 
                                  });
                                
                          <?php }                          
                          
				elseif ($replace_resource>0){?>
                                  uploader.bind('UploadComplete', function(up, files) {
                                        jQuery('.plupload_done').slideUp('2000', function() {
                                                        uploader.splice();
                                                        window.location.href='<?php echo $baseurl_short?>pages/view.php?ref=<?php echo $replace_resource; ?>';
                                                        
                                        });
                                  });
                                  
                          <?php }
				elseif (($plupload_clearqueue && checkperm("d")) && !$replace){?>
                                  uploader.bind('UploadComplete', function(up, files) {
                                        jQuery('.plupload_done').slideUp('2000', function() {
                                                        uploader.splice();
                                                        <?php
                                                        $redirect_url_params = array(
                                                            'search'   => '!contributions' . $userref,
                                                            'order_by' => 'resourceid',
                                                            'sort'     => 'DESC',
                                                            'archive'  => $setarchivestate
                                                        );

                                                        if ($setarchivestate == -2 && $pending_submission_prompt_review && checkperm("e-1")) {$redirect_url_params["promptsubmit"] = 'true';}
                                                        if ($collection_add !='false'){$redirect_url_params['collection_add'] = $collection_add;}

                                                        $redirect_url = generateURL($baseurl_short . 'pages/search.php',$redirect_url_params);
                                                        ?>
                                                        window.location.href='<?php echo $redirect_url; ?>';
                                                        
                                        });
                                  });
                                  
                          <?php }

				elseif (($plupload_clearqueue && !checkperm("d")) || $replace ){?>
                          //remove the completed files once complete
                          uploader.bind('UploadComplete', function(up, files) {
                                                  jQuery('.plupload_done').slideUp('2000', function() {
                                                         <?php if (!$plupload_show_failed)
                                                                {
                                                                ?>
                                                                uploader.splice();
                                                                <?php
                                                                }
                                                            else
                                                                {
                                                                ?>
                                                                
                                                                for (var i in files) {
                                                                    if (files[i].status!=plupload.FAILED)
                                                                        {
                                                                        uploader.removeFile(files[i]);
                                                                        }
                                                                    }
                                                                <?php
                                                                }
                                                            ?>
                                                  });
												 // Reset the lastqueued flag in case more files are added now
												 uploader.settings.url = ReplaceUrlParameter(uploader.settings.url,'lastqueued','');
                                                 
                                                jQuery('.pluploadform input').prop('disabled',''); 
                                                jQuery('.pluploadform select').prop('disabled','');
                          });
                          <?php }

if($attach_alternatives_found_to_resources)
    {
    ?>
    uploader.bind('FilesAdded', function (up, files)
        {
        console.log("bind FilesAdded...");
        if(up.files.length <= 1)
            {
            return true;
            }

        var alternative_suffix  = '<?php echo trim($upload_alternatives_suffix); ?>';
        var original_file_found;

        for(i = 0; i < up.files.length; i++)
            {
            filename = up.files[i].name.substr(0, up.files[i].name.lastIndexOf('.' + getFilePathExtension(up.files[i].name)));

            if(filename.lastIndexOf(alternative_suffix) === -1)
                {
                original_file_found = up.files[i];

                break;
                }
            };

        // One original file must be detected and it must be the first one in the queue
        if(typeof original_file_found !== 'undefined' && up.files.indexOf(original_file_found))
            {
            styledalert("<?php echo $lang['error']; ?>", "<?php echo $lang['error_upload_resource_alternatives_batch']; ?>");
            up.stop();

            return false;
            }
        });

    uploader.bind('BeforeUpload', function (up, file)
        {
        console.log("bind BeforeUpload...");
        var alternative_suffix = '<?php echo trim($upload_alternatives_suffix); ?>';

        if(alternative_suffix == '')
            {
            return true;
            }

        filename = file.name.substr(0, file.name.lastIndexOf('.' + getFilePathExtension(file.name)));
        console.log("filename = " + filename);

        // Check if original file, in which case we stop here
        if(filename.lastIndexOf(alternative_suffix) === -1)
            {
            console.log("Dealing with an original file. We stop here!");
            uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'alternative', '');
            return true;
            }

        // Below this point we only deal with alternatives for which we have a resource ID to use
        original_filename = filename.substr(0, filename.lastIndexOf(alternative_suffix));
        resource_id       = resource_ids_for_alternatives.indexOf(original_filename);

        if(resource_id === -1)
            {
            styledalert("<?php echo $lang['error']; ?>", "<?php echo $lang['error_upload_resource_not_found']; ?>");
            up.stop();

            return false;
            }

        // If we've got so far, it means we can upload this file as an alternative for this resource ID
        uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'alternative', resource_id);
        });

    uploader.bind('UploadComplete', function (up, files)
        {
        console.log("bind UploadComplete...");
        // Clean-up so user can go through a second batch
        uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'alternative', '');
        resource_ids_for_alternatives = [];
        });
    <?php
    }
    ?>

                          // Client side form validation
                        jQuery('form.pluploadform').submit(function(e) {
                                
                        // Files in queue upload them first
                        if (uploader.files.length > 0) {
                            // When all files are uploaded submit form
                            uploader.bind('StateChanged', function() {
                                if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
                                    jQuery('form.pluploadform')[0].submit();
                                }
                            });
                                
                            uploader.start();
                            } else {
                                alert('You must queue at least one file.');
                            }
                    
                            return false;
                         });

                        //Change URL if exif box status changes
                        jQuery('#no_exif').on('change', function ()
                            {
                            console.log('Changing exif');
                            if(jQuery(this).is(':checked'))
                                {
                                uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'no_exif', 'yes');
                                }
                            else
                                {
                                uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'no_exif', '');
                                }
                            });

						<?php
						if($replace_resource_preserve_option)
								{
								?>
                                //Change URL if keep_original box status changes
                                jQuery('#keep_original').change(function() {
                                    if(jQuery(this).is(':checked'))
                                        {
                                        uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'keep_original', 'yes');
                                        uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'replace_resource_original_alt_filename', jQuery('#replace_resource_original_alt_filename').val());
                                        }
                                    else
                                        {
                                        uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'keep_original', '');
                                        uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'replace_resource_original_alt_filename', '');
                                        }
                                });

                                jQuery('#replace_resource_original_alt_filename').change(function() {
                                    uploader.settings.url = ReplaceUrlParameter(uploader.settings.url, 'replace_resource_original_alt_filename', jQuery(this).val());
                                });
								<?php
								}
								?>
						
						}
                    },
    // Use multipart_params to send additional data to upload_plupload.php rather 
    // than use the query strings in the URL
    multipart_params: {
        <?php echo generateAjaxToken("upload_plupload"); ?>
    }
}; // End of pluploader config
                
        
jQuery(document).ready(function () {            
    registerCollapsibleSections();
    jQuery("#pluploader").plupload<?php if(!$plupload_widget) { ?>Queue<?php } ?>(pluploadconfig);
});

<?php
# If adding to a collection that has been externally shared, show a warning.
if (is_numeric($collection_add) && count(get_collection_external_access($collection_add))>0 && !upload_share_active())
    {
    # Show warning.
    ?>alert("<?php echo $lang["sharedcollectionaddwarningupload"]?>");<?php
    }   
?>
    
		
</script>

<?php
	# Add language support if available
	if (file_exists("../lib/plupload_2.1.8/i18n/" . $language . ".js"))
		{
		echo "<script type=\"text/javascript\" src=\"" . $baseurl_short . "lib/plupload_2.1.8/i18n/" . $language . ".js?" . $css_reload_key . "\"></script>";
		}
		?>
		
<div class="BasicsBox" >
        
 <?php if ($overquota) 
   {
   ?><h1><?php echo $lang["diskerror"]?></h1><p><?php echo $lang["overquota"] ?></p> <?php 
   include "../include/footer.php";
   exit();
   }

 if  ($alternative!=""){?><p>
<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/alternative_files.php?ref=<?php echo urlencode($alternative)?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtomanagealternativefiles"]?></a></p><?php } ?>

<?php if ($replace_resource!="") { ?>
    <p>
        <a onClick="return ModalLoad(this,true);" href="<?php echo $baseurl_short?>pages/view.php?ref=<?php echo urlencode($replace_resource) ?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>">
            <?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?>
        </a>
    </p>
<?php } ?>

<?php if ($alternative!=""){$resource=get_resource_data($alternative);
	if ($alternative_file_resource_preview){ 
		$imgpath=get_resource_path($resource['ref'],true,"col",false);
		if (file_exists($imgpath)){ ?><img src="<?php echo get_resource_path($resource['ref'],false,"col",false);?>"/><?php }
	}
	if ($alternative_file_resource_title){ 
		echo "<h2>".$resource['field'.$view_title_field]."</h2><br/>";
	}
}

# Define the titles:
if ($replace!="") 
	{
	# Replace Resource Batch
	$titleh1 = $lang["replaceresourcebatch"];
	$intro = $lang["intro-plupload_upload-replace_resource"];
	}
elseif ($replace_resource!="")
	{
	# Replace file
	$titleh1 = $lang["replacefile"];
	$intro = $lang["intro-plupload_upload-replace_resource"];
	}
elseif ($alternative!="")
	{
	# Batch upload alternative files 
	$titleh1 = $lang["alternativebatchupload"];
	$intro = $lang["intro-plupload"];
	}
elseif (upload_share_active())
    {
    $collectiondata = get_collection($collection_add);
    $titleh1 = $lang["addresourcebatchbrowser"] . ": " . i18n_get_collection_name($collectiondata);
	$intro = $lang["intro-plupload"];
    $before_intro = $lang["intro-plupload_external_share"];
    }
else
	{
	# Add Resource Batch - In Browser 
	$titleh1 = $lang["addresourcebatchbrowser"];
	$intro = $lang["intro-plupload"];
    }
    
?>
<?php hook("upload_page_top"); ?>
<div class="BasicsBox titlediv">
    <?php if (!hook("replacepluploadtitle")){?><h1><?php echo $titleh1; ?></h1><?php } 

    if(is_numeric($collection_add) && can_share_upload_link($collection_add))
        {
        $share_up_url = generateurl($baseurl_short . "pages/share_upload.php",array("share_collection"=>$collection_add));
        echo "<div id='share-upload_link' class='sharelink'><a href='" . $share_up_url . "' onclick='return CentralSpaceLoad(this,true);'>" . $lang["action-share-upload-link"] . "</a></div>";
        }
    ?>
</div>
<div class="clearerleft"></div>
<?php
if(isset($before_intro))
    {
    echo "<p>" . $before_intro . "</p>";
    }
?>
<div id="plupload_instructions"><p><?php echo $intro;render_help_link("user/uploading");?></p></div>
<?php

if (isset($plupload_max_file_size))
	{
	if (is_numeric($plupload_max_file_size))
		$sizeText = formatfilesize($plupload_max_file_size);
	else
		$sizeText = formatfilesize(filesize2bytes($plupload_max_file_size));
	echo ' '.sprintf($lang['plupload-maxfilesize'], $sizeText);
	}

hook("additionaluploadtext");

if ($allowed_extensions!="" && $alternative==''){
    $allowed_extensions=str_replace(", ",",",$allowed_extensions);
    $list=explode(",",trim($allowed_extensions));
    sort($list);
    $allowed_extensions=implode(",",$list);
    ?><p><?php echo str_replace_formatted_placeholder("%extensions", str_replace(",",", ",$allowed_extensions), $lang['allowedextensions-extensions'])?></p><?php } ?>

<div class="BasicsBox">
        <div id="pluploader"></div>
</div>	
<?php
hook ("beforepluploadform");
if(     ($replace_resource != '' 
        ||
        $replace != '' 
        || 
        $upload_then_edit)
    && 
        !(isset($alternative) && (int) $alternative > 0) 
    && 
        (display_upload_options() || $replace_resource_preserve_option)
    && 
        !upload_share_active()
    )
    {
    // Show options on the upload page if in 'upload_then_edit' mode or replacing a resource
    ?>
    <div class="BasicsBox">
    <h2 class="CollapsibleSectionHead collapsed" onClick="UICenterScrollBottom();" id="UploadOptionsSectionHead"><?php echo $lang["upload-options"]; ?></h2>
    <div class="CollapsibleSection" id="UploadOptionsSection">
    <form id="UploadPluploadForm" class="pluploadform FormWide" action="<?php echo $baseurl_short?>pages/upload_plupload.php">
    <?php
    generateFormToken("upload_plupload");
    
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
        //$upload_page_options = true;
        include '../include/edit_upload_options.php';
        }
        
    /* Show the import embedded metadata checkbox when uploading a missing file or replacing a file.
    In the other upload workflows this checkbox is shown in a previous page. */
    if (!hook("replacemetadatacheckbox")) 
        {
        if ((getvalescaped("upload_a_file","")!="" || getvalescaped("replace_resource","")!=""  || getvalescaped("replace","")!="") && $metadata_read)
            { ?>
            <div class="Question">
                <label for="no_exif"><?php echo $lang["no_exif"]?></label><input type=checkbox <?php if ($no_exif){?>checked<?php } ?> id="no_exif" name="no_exif" value="yes">
                <div class="clearerleft"> </div>
            </div>
            <?php
            }
        }
    ?>
    </form>
    </div><!-- End of UploadOptionsSection -->
    <?php
    } // End of upload options
hook('plupload_before_status');
if ($status!="") { ?><?php echo $status?><?php } ?>
</div>

<?php 
if ($show_upload_log)
    {
    ?>
    <div class="BasicsBox">
    <h2 class="CollapsibleSectionHead collapsed" id="UploadLogSectionHead" onClick="UICenterScrollBottom();"><?php echo $lang["log"]; ?></h2>
    <div class="CollapsibleSection" id="UploadLogSection">
        <textarea id="upload_log" rows=10 cols=100 style="width: 100%; border: solid 1px;" ><?php echo  $lang["plupload_log_intro"] . date("d M y @ H:i"); ?></textarea>
    </div> <!-- End of UploadLogSection -->
    </div>
    <?php
    }
    ?>    
</div>



<?php

hook("upload_page_bottom");
include "../include/footer.php";
