<?php 
ini_set('zlib.output_compression','off'); // disable PHP output compression since it breaks collection downloading
include "../include/db.php";
include_once "../include/general.php";
include_once "../include/collections_functions.php";
# External access support (authenticate only if no key provided, or if invalid access key provided)
$k=getvalescaped("k","");if (($k=="") || (!check_access_key_collection(getvalescaped("collection","",true),$k))) {include "../include/authenticate.php";}
include "../include/search_functions.php";
include "../include/resource_functions.php";
include_once '../include/csv_export_functions.php';
include_once '../include/pdf_functions.php';
ob_end_clean();
$uniqid="";$id="";
$collection=getvalescaped("collection","",true);  if ($k!=""){$usercollection=$collection;}
$size=getvalescaped("size","");
$submitted=getvalescaped("submitted","");
$includetext=getvalescaped("text","false");
$useoriginal=getvalescaped("use_original","no");
$collectiondata=get_collection($collection);
$tardisabled=getvalescaped("tardownload","")=="off";

$collection_download_tar=true;

// Has tar been disabled or is it not available
if($collection_download_tar_size==0 || $config_windows || $tardisabled)
	{
	$collection_download_tar=false;
	}
else
	{
	if(!$collection_download_tar_option)
		{
		// Set tar as default above certain collection size
		$results=do_search("!collection" . $collection,"","relevance","",-1,"",false,0,false,true,"");
		$disk_usage=$results[0]["total_disk_usage"];
		if($disk_usage >= $collection_download_tar_size*1024*1024)
			{
			$collection_download_tar_option=true;
			}
		}
	}
	
$settings_id=(isset($collection_download_settings) && count($collection_download_settings)>1)?getvalescaped("settings",""):0;
$uniqid=getval("id",uniqid("Col".$collection."-"));

$usage = getvalescaped('usage', '-1');
$usagecomment = getvalescaped('usagecomment', '');
function findDuplicates($data,$dupval) {
$nb= 0;
foreach($data as $key => $val) {if ($val==$dupval) {$nb++;}}
return $nb;
}

// set the time limit to unlimited, default 300 is not sufficient here.
set_time_limit(0);

function update_zip_progress_file($note){
	global $progress_file;
	$fp = fopen($progress_file, 'w');		
	$filedata=$note;
	fwrite($fp, $filedata);
	fclose($fp);
}

$archiver_fullpath = get_utility_path("archiver");

if (!isset($zipcommand) && !$use_zip_extension)
    {
    if (!$collection_download) {exit($lang["download-of-collections-not-enabled"]);}
    if ($archiver_fullpath==false) {exit($lang["archiver-utility-not-found"]);}
    if (!isset($collection_download_settings)) {exit($lang["collection_download_settings-not-defined"]);}
    else if (!is_array($collection_download_settings)) {exit($lang["collection_download_settings-not-an-array"]);}
    if (!isset($archiver_listfile_argument)) {exit($lang["listfile-argument-not-defined"]);}
    }
    
$archiver = $collection_download && ($archiver_fullpath!=false) && (isset($archiver_listfile_argument)) && (isset($collection_download_settings) ? is_array($collection_download_settings) : false);

# initiate text file
if (($zipped_collection_textfile==true)&&($includetext=="true")) { 
    $text = i18n_get_collection_name($collectiondata) . "\r\n" .
    $lang["downloaded"] . " " . nicedate(date("Y-m-d H:i:s"), true, true) . "\r\n\r\n" .
    $lang["contents"] . ":\r\n\r\n";
}

# get collection
$result=do_search("!collection" . $collection);

$modified_result=hook("modifycollectiondownload");
if (is_array($modified_result)){$result=$modified_result;}

#this array will store all the available downloads.
$available_sizes=array();

# get file extension from database or use jpg.
function get_extension($resource, $size)
	{
	$pextension = $size == 'original' ? $resource["file_extension"] : 'jpg';
	$replace_extension = hook('replacedownloadextension', '', array($resource, $pextension));
	if (!empty($replace_extension))
		return $replace_extension;

	return $pextension;
	}

$count_data_only_types = 0;

#build the available sizes array
for ($n=0;$n<count($result);$n++)
	{
	$ref=$result[$n]["ref"];
	# Load access level (0,1,2) for this resource
	$access=get_resource_access($result[$n]);
	
	# get all possible sizes for this resource
	$sizes=get_all_image_sizes(false,$access>=1);

	#check availability of original file 
	$p=get_resource_path($ref,true,"",false,$result[$n]["file_extension"]);
	if (file_exists($p) && (($access==0) || ($access==1 && $restricted_full_download)) && resource_download_allowed($ref,'',$result[$n]['resource_type']))
		{
		$available_sizes['original'][]=$ref;
		}

	$pextension = get_extension($result[$n], $size);

	# check for the availability of each size and load it to the available_sizes array
	foreach ($sizes as $sizeinfo)
		{
		$size_id=$sizeinfo['id'];
		$p=get_resource_path($ref,true,$size_id,false,$pextension);

		if (resource_download_allowed($ref,$size_id,$result[$n]['resource_type']))
			{
			if (hook('size_is_available', '', array($result[$n], $p, $size_id)) || file_exists($p))
				$available_sizes[$size_id][]=$ref;
			}
		}

    if(in_array($result[$n]['resource_type'], $data_only_resource_types))
        {
        $count_data_only_types++;
        }
    }

#print_r($available_sizes);
if(0 == count($available_sizes) && 0 === $count_data_only_types)
	{
	?>
	<script type="text/javascript">
    	alert('<?php echo $lang["nodownloadcollection"];?>');
        history.go(-1);
    	</script>
	<?php
    	exit();
	}

$used_resources=array();
$subbed_original_resources = array();
if ($submitted != "")
	{
	if($exiftool_write && !$force_exiftool_write_metadata && !$collection_download_tar)
		{
		$exiftool_write_option = false;
		if('yes' == getvalescaped('write_metadata_on_download', ''))
			{
			$exiftool_write_option = true;
			}
		}
					
	# Estimate the total volume of files to zip
	$totalsize=0;
	for ($n=0;$n<count($result);$n++)
		{
		$usesize = ($size == 'original') ? "" : $usesize=$size;
		$use_watermark=check_use_watermark();
		
		# Find file to use
		$f=get_resource_path($ref,true,$usesize,false,$pextension,-1,1,$use_watermark);
		if (!file_exists($f))
			{
			# Selected size doesn't exist, use original file
			$f=get_resource_path($ref,true,'',false,$result[$n]['file_extension'],-1,1,$use_watermark);
			}
		if (file_exists($f))
			{
			$totalsize+=filesize_unlimited($f);
			}
		}
	if ($totalsize>$collection_download_max_size  && !$collection_download_tar)
		{
		?>
		<script>
		alert("<?php echo $lang["collection_download_too_large"] ?>");
		history.go(-1);
		</script>
		<?php
		exit();
		}
	
	$id=getvalescaped("id","");
	// Get a temporary directory for this download - $id should be unique
	$usertempdir=get_temp_dir(false,"rs_" . $userref . "_" . $id);
	
	// Clean up old user temp directories if they exist
	$tempdirbase=get_temp_dir(false);	
	$tempfoldercontents = new DirectoryIterator($tempdirbase);
	$folderstodelete=array();
	$delindex=0;
	foreach($tempfoldercontents as $objectindex => $object)
		{
		$tmpfilename = $object->getFilename();
		if ($object->isDir())
			{
			if((substr($tmpfilename,0,strlen("rs_" . $userref . "_"))=="rs_" . $userref . "_"  || substr($tmpfilename,0,3)== "Col") && time()-$object->getMTime()>24*60*60) 
			   {
			   debug ("Collection download - found old temp directory: " . $tmpfilename .  "  age (minutes): " . (time()-$object->getMTime())/60);
			   // This directory belongs to the user and is older than a day, delete it
			   $folderstodelete[]=$tempdirbase . DIRECTORY_SEPARATOR . $tmpfilename;				
			   }
			}
		elseif($purge_temp_folder_age!=0 && time()-$object->getMTime()>$purge_temp_folder_age*24*60*60)
			{
			unlink($tempdirbase . DIRECTORY_SEPARATOR . $tmpfilename); 				
			}
		
		}
	foreach ($folderstodelete as $foldertodelete)
		{
		debug ("Collection download - deleting directory " . $foldertodelete);
		@rcRmdir($foldertodelete);
		}
	$progress_file=$usertempdir . "/progress_file.txt";
	
	# Define the archive file.
	if(!$collection_download_tar)
		{
		if ($use_zip_extension){
			$zipfile = $usertempdir . "/zip.zip";
			$zip = new ZipArchive();
			$zip->open($zipfile, ZIPARCHIVE::CREATE);
		}
		else if ($archiver)
			{
			$zipfile = $usertempdir . "/".$lang["collectionidprefix"] . $collection . "-" . $size . "." . $collection_download_settings[$settings_id]["extension"];
		   }
		else
			{
			$zipfile = $usertempdir . "/".$lang["collectionidprefix"] . $collection . "-" . $size . ".zip";
		   }
		}

	$path="";
	$deletion_array=array();
	// set up an array to store the filenames as they are found (to analyze dupes)
	$filenames=array();	
	
	# Build a list of files to download
	for ($n=0;$n<count($result);$n++)
		{
		resource_type_config_override($result[$n]["resource_type"]);
		$copy=false; 
		$ref=$result[$n]["ref"];
		# Load access level
		$access=get_resource_access($result[$n]);
		$use_watermark=check_use_watermark();

		# Only download resources with proper access level
		if ($access==0 || $access=1)
			{
			$pextension = get_extension($result[$n], $size);
			$usesize = ($size == 'original') ? "" : $usesize=$size;
			$p=get_resource_path($ref,true,$usesize,false,$pextension,-1,1,$use_watermark);

			$subbed_original = false;
			$target_exists = file_exists($p);
			$replaced_file = false;

			$new_file = hook('replacedownloadfile', '', array($result[$n], $usesize, $pextension, $target_exists));
			if (!empty($new_file) && $p != $new_file)
				{
				$p = $new_file;
				$deletion_array[] = $p;
				$replaced_file = true;
				$target_exists = file_exists($p);
				}
			else if (!$target_exists && $useoriginal == 'yes'
					&& resource_download_allowed($ref,'',$result[$n]['resource_type']))
				{
				// this size doesn't exist, so we'll try using the original instead
				$p=get_resource_path($ref,true,'',false,$result[$n]['file_extension'],-1,1,$use_watermark);
				$pextension = $result[$n]['file_extension'];
				$subbed_original_resources[] = $ref;
				$subbed_original = true;
				$target_exists = file_exists($p);
				}

			# Check file exists and, if restricted access, that the user has access to the requested size.
			if ((($target_exists && $access==0) ||
				($target_exists && $access==1 &&
					(image_size_restricted_access($size) || ($usesize='' && $restricted_full_download))) 
					) && resource_download_allowed($ref,$usesize,$result[$n]['resource_type']))
				{
				$used_resources[]=$ref;
				$tmpfile = false;
				if($exiftool_write_option)
					{
					# when writing metadata, we take an extra security measure by copying the files to tmp
					$tmpfile = write_metadata($p, $ref, $id); // copies file
	
					if($tmpfile!==false && file_exists($tmpfile))
						{
						$p=$tmpfile; // file already in tmp, just rename it
						}
					else if (!$replaced_file)
						{
						$copy=true; // copy the file from filestore rather than renaming
						}
					}

				# if the tmpfile is made, from here on we are working with that. 
				
				# If using original filenames when downloading, copy the file to new location so the name is included.
				$filename = '';
				if ($original_filenames_when_downloading)	
					{
					# Retrieve the original file name		
					$filename=get_data_by_field($ref,$filename_field);	

					if (!empty($filename))
						{
						# Only perform the copy if an original filename is set.

						# now you've got original filename, but it may have an extension in a different letter case. 
						# The system needs to replace the extension to change it to jpg if necessary, but if the original file
						# is being downloaded, and it originally used a different case, then it should not come from the file_extension, 
						# but rather from the original filename itself.
						
						# do an extra check to see if the original filename might have uppercase extension that can be preserved.	
						# also, set extension to "" if the original filename didn't have an extension (exiftool identification of filetypes)
						$pathparts=pathinfo($filename);
						if (isset($pathparts['extension'])){
							if (strtolower($pathparts['extension'])==$pextension){$pextension=$pathparts['extension'];}
						} else {$pextension="jpg";}	
						if ($usesize!=""&&!$subbed_original){$append="-".$usesize;}else {$append="";}
						$basename_minus_extension=remove_extension($pathparts['basename']);
						$filename=$basename_minus_extension.$append.".".$pextension;

						if ($prefix_resource_id_to_filename) {$filename=$prefix_filename_string . $ref . "_" . $filename;}

						$fs=explode("/",$filename);$filename=$fs[count($fs)-1];

                        # Convert $filename to the charset used on the server.
                        if (!isset($server_charset)) {$to_charset = 'UTF-8';}
                        else
                            {
                            if ($server_charset!="") {$to_charset = $server_charset;}
                            else {$to_charset = 'UTF-8';}
                            }
                        $filename = mb_convert_encoding($filename, $to_charset, 'UTF-8');
						
						// check if a file has already been processed with this name
						if(in_array($filename, $filenames))
                            {
                            $path_parts = pathinfo($filename);
                            if(isset($path_parts['extension']) && isset($path_parts['filename']))
                                {
                                $filename_ext = $path_parts['extension'];
                                $filename_wo  = $path_parts['filename'];

                                // Run through function to guarantee unique filename
                                $filename = makeFilenameUnique($filenames, $filename_wo, $lang["_dupe"], $filename_ext);
                                }
                            }
                        
                        // Add the filename to the array so it can be checked in the next loop
                        $filenames[] = $filename;

                        # Copy to tmp (if exiftool failed) or rename this file
                        # this is for extra efficiency to reduce copying and disk usage
                        
                        if(!($collection_download_tar || $use_zip_extension))
                            {
                            // the copy or rename to the filename is not necessary using the zip extension since the archived filename can be specified.
                            $newpath = get_temp_dir(false,$id) . '/' . $filename;

                            if(!$copy && $exiftool_write_option)
                                {
                                rename($p, $newpath);
                                }
                            else
                                {
                                copy($p,$newpath);
                                }

                            # Add the temporary file to the post-archiving deletion list.
                            $deletion_array[] = $newpath;

                            # Set p so now we are working with this new file
                            $p = $newpath;
                            }
						}
					}
				if (empty($filename))
					{
					$filename=$prefix_filename_string . $ref . "_" . $size . "." . $pextension;
					}
                if (hook("downloadfilenamealt")) $filename=hook("downloadfilenamealt");
				#Add resource data/collection_resource data to text file
				if (($zipped_collection_textfile==true)&&($includetext=="true"))
					{ 
					if ($size==""){$sizetext="";}else{$sizetext="-".$size;}
					if ($subbed_original) { $sizetext = '(' . $lang['substituted_original'] . ')'; }
					if($k === '')
						{
						$fields = get_resource_field_data($ref);
						}
					else
						{
						// External shares should take into account fields that are not meant to show in that case
						$fields = get_resource_field_data($ref, false, true, -1, true);
						}
					$commentdata=get_collection_resource_comment($ref,$collection);
					if (count($fields)>0)
						{ 
						$text.= ($sizetext=="" ? "" : $sizetext) ." ". $filename. "\r\n-----------------------------------------------------------------\r\n";
						$text.= $lang["resourceid"] . ": " . $ref . "\r\n";
							for ($i=0;$i<count($fields);$i++){
								$value=$fields[$i]["value"];
								$title=str_replace("Keywords - ","",$fields[$i]["title"]);
								if ((trim($value)!="")&&(trim($value)!=",")){$text.= wordwrap("* " . $title . ": " . i18n_get_translated($value) . "\r\n", 65);}
							}
						if(trim($commentdata['comment'])!=""){$text.= wordwrap($lang["comment"] . ": " . $commentdata['comment'] . "\r\n", 65);}	
						if(trim($commentdata['rating'])!=""){$text.= wordwrap($lang["rating"] . ": " . $commentdata['rating'] . "\r\n", 65);}	
						$text.= "-----------------------------------------------------------------\r\n\r\n";	
						}
					}
				
				hook('modifydownloadfile');
								
				$path.=$p . "\r\n";	
				
                if($collection_download_tar)
                    {
                    $ln_link_name = $usertempdir . DIRECTORY_SEPARATOR . $filename;

                    /*
                    There is unexpected behaviour when a folder contains more than 70,000 symbolic links/ files in it.
                    By splitting result set in batches of 1000, this should address that problem. Up to 10,000 resources
                    in a collection proved to work ok and relatively faster when not splitting in subfolders.
                    */
                    if(count($result) > 10000)
                        {
                        // Generate folder name
                        $low_limit      = (floor($ref / 1000) * 1000) + 1;
                        $high_limit     = (ceil($ref / 1000) * 1000);
                        $symlink_folder = "{$usertempdir}" . DIRECTORY_SEPARATOR . "RS_{$low_limit}_to_{$high_limit}";

                        if(!is_dir($symlink_folder))
                            {
                            mkdir($symlink_folder, 0777, true);
                            }

                        $ln_link_name = $symlink_folder . DIRECTORY_SEPARATOR . $filename;
                        }

                    // Add link to file for use by tar to prevent full paths being included.
                    debug("collection_download adding symlink: {$p} - {$ln_link_name}");
                    @symlink($p, $ln_link_name);
                    }
				elseif ($use_zip_extension)
					{
					$zip->addFile($p,$filename);
					update_zip_progress_file("file ".$zip->numFiles);
					}
				else
					{
					update_zip_progress_file("file ".$n);
					}
				# build an array of paths so we can clean up any exiftool-modified files.
				
				if($tmpfile!==false && file_exists($tmpfile)){$deletion_array[]=$tmpfile;}
				daily_stat("Resource download",$ref);
				resource_log($ref,'d',0,$usagecomment,"","",$usage,$size);
				
				# update hit count if tracking downloads only
				if ($resource_hit_count_on_downloads)
					{ 
					# greatest() is used so the value is taken from the hit_count column in the event that new_hit_count is zero to support installations that did not previously have a new_hit_count column (i.e. upgrade compatability).
					sql_query("update resource set new_hit_count=greatest(hit_count,new_hit_count)+1 where ref='$ref'");
					} 
				
				}
			}

		}
    // Collection contains data_only resource types
    if(0 < $count_data_only_types)
        {
        for($n = 0; $n < count($result); $n++)
            {
            // Data-only type of resources should be generated and added in the archive
            if(in_array($result[$n]['resource_type'], $data_only_resource_types))
                {
                $template_path = get_pdf_template_path($result[$n]['resource_type']);
                $pdf_filename = 'RS_' . $result[$n]['ref'] . '_data_only.pdf';
                $pdf_file_path = get_temp_dir(false, $id) . '/' . $pdf_filename;

                // Go through fields and decide which ones we add to the template
                $placeholders = array(
                    'resource_type_name' => get_resource_type_name($result[$n]['resource_type'])
                );

                $metadata = get_resource_field_data($result[$n]['ref'], false, true, -1, '' != getval('k', ''));

                foreach($metadata as $metadata_field)
                    {
                    $metadata_field_value = trim(tidylist(i18n_get_translated($metadata_field['value'])));

                    // Skip if empty
                    if('' == $metadata_field_value)
                        {
                        continue;
                        }

                    $placeholders['metadatafield-' . $metadata_field['ref'] . ':title'] = $metadata_field['title'];
                    $placeholders['metadatafield-' . $metadata_field['ref'] . ':value'] = $metadata_field_value;
                    }
                generate_pdf($template_path, $pdf_file_path, $placeholders, true);

                // Go and add file to archive
               if($collection_download_tar)
                    {
                    // Add a link to pdf 
					symlink($pdf_file_path, $usertempdir . DIRECTORY_SEPARATOR  . $pdf_filename); 
					}
                elseif($use_zip_extension)
                    {
                    $zip->addFile($pdf_file_path, $pdf_filename);
                    }
                else
                    {
                    $path .= $pdf_file_path . "\r\n";
                    }
                $deletion_array[] = $pdf_file_path;

                continue;
                }

            daily_stat('Resource download', $result[$n]['ref']);
            resource_log($result[$n]['ref'], 'd', 0, $usagecomment, '', '', $usage);

            if($resource_hit_count_on_downloads)
                { 
                /*greatest() is used so the value is taken from the hit_count column in the event that new_hit_count is zero
                to support installations that did not previously have a new_hit_count column (i.e. upgrade compatability).*/
                sql_query("UPDATE resource SET new_hit_count = greatest(hit_count, new_hit_count) + 1 WHERE ref = '{$result[$n]['ref']}'");
                }
            }
        }
    else if('' == $path)
        {
        exit($lang['nothing_to_download']);
        }

    # Append summary notes about the completeness of the package, write the text file, add to archive, and schedule for deletion
    if (($zipped_collection_textfile==true)&&($includetext=="true")){
        $qty_sizes = count($available_sizes[$size]);
        $qty_total = count($result);
        $text.= $lang["status-note"] . ": " . $qty_sizes . " " . $lang["of"] . " " . $qty_total . " ";
        switch ($qty_total) {
        case 0:
            $text.= $lang["resource-0"] . " ";
            break;
        case 1:
            $text.= $lang["resource-1"] . " ";
            break;
        default:
            $text.= $lang["resource-2"] . " ";
            break;
        }

        switch ($qty_sizes) {
        case 0:
            $text.= $lang["were_available-0"] . " ";
            break;
        case 1:
            $text.= $lang["were_available-1"] . " ";
            break;
        default:
            $text.= $lang["were_available-2"] . " ";
            break;
        }
        $text.= $lang["forthispackage"] . ".\r\n\r\n";
    
        foreach ($result as $resource) {
	    if (in_array($resource['ref'],$subbed_original_resources)){
		$text.= $lang["didnotinclude"] . ": " . $resource['ref'];
		$text.= " (".$lang["substituted_original"] . ")";
		$text.= "\r\n";
	    } elseif (!in_array($resource['ref'],$used_resources)) {
                $text.= $lang["didnotinclude"] . ": " . $resource['ref'];
		$text.= "\r\n";
            }
        }

        $textfile = get_temp_dir(false,$id) . "/". $collection . "-" . safe_file_name(i18n_get_collection_name($collectiondata)) . $sizetext . ".txt";
        $fh = fopen($textfile, 'w') or die("can't open file");
        fwrite($fh, $text);
        fclose($fh);
		if($collection_download_tar)
			{
			debug("collection_download adding symlink: " . $p . " - " . $usertempdir . DIRECTORY_SEPARATOR . $filename);
			@symlink($textfile, $usertempdir . DIRECTORY_SEPARATOR . $collection . "-" . safe_file_name(i18n_get_collection_name($collectiondata)) . $sizetext . '.txt');
			}
		elseif ($use_zip_extension){
			$zip->addFile($textfile,$collection . "-" . safe_file_name(i18n_get_collection_name($collectiondata)) . $sizetext . ".txt");
        } else {
			$path.=$textfile . "\r\n";	
        }
        $deletion_array[]=$textfile;	
    }

    // Include the CSV file with the metadata of the resources found in this collection
	if(getvalescaped('include_csv_file', '') == 'yes')
		{
		$csv_file    = get_temp_dir(false, $id) . '/Col-' . $collection . '-metadata-export.csv';
		$csv_fh      = fopen($csv_file, 'w') OR die("can't open file");
		$csv_content = generateResourcesMetadataCSV($result);
		fwrite($csv_fh, $csv_content);
		fclose($csv_fh);

		// Add link to file for use by tar to prevent full paths being included.
		if($collection_download_tar)
			{
			debug("collection_download adding symlink: " . $p . " - " . $usertempdir . DIRECTORY_SEPARATOR . $filename);
			@symlink($csv_file, $usertempdir . DIRECTORY_SEPARATOR . 'Col-' . $collection . '-metadata-export.csv');
			}
		elseif($use_zip_extension)
			{
			$zip->addFile($csv_file, 'Col-' . $collection . '-metadata-export.csv');
			}
		else
			{
			$path .= $csv_file . "\r\n";
			}
		$deletion_array[] = $csv_file;
		}

	# Write command parameters to file.
	//update_progress_file("writing zip command");	
	if (!$use_zip_extension && !$collection_download_tar)
		{
		$cmdfile = get_temp_dir(false,$id) . "/zipcmd" . $collection . "-" . $size . ".txt";
		$fh = fopen($cmdfile, 'w') or die("can't open file");
		# Remove Windows line endings - fixes an issue with using tar command - somehow the file has got Windows line breaks
		if(!$config_windows) 
			{$path=preg_replace('/\r\n/', "\n", $path);}
		fwrite($fh, $path);
		fclose($fh);
		}

	if($collection_download_tar)
		{$suffix = '.tar';}
	elseif ($archiver)
		$suffix = '.' . $collection_download_settings[$settings_id]['extension'];
	else
		$suffix = '.zip';

	$filename = hook('changecollectiondownloadname', null, array($collection, $size, $suffix));
	if (empty($filename))
		{
		if ($use_collection_name_in_zip_name)
			{
			# Use collection name (if configured)
			$filename = $lang["collectionidprefix"] . $collection . "-"
					. safe_file_name(i18n_get_collection_name($collectiondata)) . "-" . $size
					. $suffix;
			}
		else
			{
			# Do not include the collection name in the filename (default)
			$filename = $lang["collectionidprefix"] . $collection . "-" . $size . $suffix;
			}
		}
		
    # Execute the archiver command.
    # If $collection_download is true the $collection_download_settings are used if defined, else the legacy $zipcommand is used.
    if ($use_zip_extension && !$collection_download_tar)
		{
		update_zip_progress_file("zipping");
		$wait=$zip->close();
		update_zip_progress_file("complete");
		sleep(1);
		}
	 else if ($collection_download_tar)
        {
		header("Content-type: application/tar");
		header("Content-disposition: attachment; filename=" . $filename );
		debug("collection_download tar command: tar -cv -C " . $usertempdir . " . ");
		passthru("find " . $usertempdir . ' -printf "%P\n" | tar -cv --no-recursion --dereference -C ' . $usertempdir . " -T -");
		exit();
        }
    else if ($archiver)
        {
		update_zip_progress_file("zipping");
        $wait=run_command($archiver_fullpath . " " . $collection_download_settings[$settings_id]["arguments"] . " " . escapeshellarg($zipfile) . " " . $archiver_listfile_argument . escapeshellarg($cmdfile));
        update_zip_progress_file("complete");
        }
    else if (!$use_zip_extension)
        {
		update_zip_progress_file("zipping");	
        if ($config_windows)
            # Add the command file, containing the filenames, as an argument.
            {
            $wait=exec("$zipcommand " . escapeshellarg($zipfile) . " @" . escapeshellarg($cmdfile));
            }
        else
            {
            # Pipe the command file, containing the filenames, to the executable.
            $wait=exec("$zipcommand " . escapeshellarg($zipfile) . " -@ < " . escapeshellarg($cmdfile));
            }
            update_zip_progress_file("complete");
        }

    # Archive created, schedule the command file for deletion.
	if (!$use_zip_extension){
		$deletion_array[]=$cmdfile;
	}
	
	# Remove temporary files.
	foreach($deletion_array as $tmpfile) {
		delete_exif_tmpfile($tmpfile);
	}

    # Get the file size of the archive.
    $filesize = @filesize_unlimited($zipfile);

	header("Content-Disposition: attachment; filename=" . $filename);
    if ($archiver) {header("Content-Type: " . $collection_download_settings[$settings_id]["mime"]);}
    else {
	header("Content-Type: application/zip");}
	if ($use_zip_extension){header("Content-Transfer-Encoding: binary");}
	header("Content-Length: " . $filesize);

	ignore_user_abort(true); // collection download has a problem with leaving junk files when this script is aborted client side. This seems to fix that by letting the process run its course.
	set_time_limit(0);

	if (!hook("replacefileoutput"))
		{
		# New method
		$sent = 0;
		$handle = fopen($zipfile, "r");
	
		// Now we need to loop through the file and echo out chunks of file data
		while($sent < $filesize)
			{
			echo fread($handle, $download_chunk_size);
			$sent += $download_chunk_size;
			}
		}
		
	# Remove archive.
	//unlink($zipfile);
	//unlink($progress_file);
	if ($use_zip_extension)
		{
		rmdir(get_temp_dir(false,$id));
		collection_log($collection,"Z","","-".$size);
		}
	hook('beforedownloadcollectionexit');
	exit();
	}
include "../include/header.php";

?>
<div class="BasicsBox">
<?php if($k!=""){
	?><p><a href="<?php echo $baseurl_short?>pages/search.php?search=!collection<?php echo $collection?>&k=<?php echo $k?>" onclick="return CentralSpaceLoad(this,true);">< <?php echo $lang['back']?></a></p><?php
}?>

<h1><?php echo $lang["downloadzip"]?></h1>
<?php
$intro=text("introtext");
if ($intro!="") { ?><p><?php echo $intro ?></p><?php } 
?>
<script>

function ajax_download()
	{	
	document.getElementById('downloadbuttondiv').style.display='none';	
	document.getElementById('progress').innerHTML='<br /><br /><?php echo $lang["collectiondownloadinprogress"];?>';
	document.getElementById('progress3').style.display='none';
	document.getElementById('progressdiv').style.display='block';

	var ifrm = document.getElementById('downloadiframe');
	
	ifrm.src = "<?php echo $baseurl_short?>pages/collection_download.php?submitted=true&"+jQuery('#myform').serialize();

	// Disable form controls -- this needs to happen after serializing the form or else they are ignored
	jQuery('#downloadsize').prop('disabled', true);
	jQuery('#use_original').prop('disabled', true);
	jQuery('#text').prop('disabled', true);
	jQuery('#archivesettings').prop('disabled', true);

	
	progress= jQuery("progress3").PeriodicalUpdater("<?php echo $baseurl_short?>pages/ajax/collection_download_progress.php?id=<?php echo urlencode($uniqid) ?>&user=<?php echo urlencode($userref) ?>", {
        method: 'post',          // method; get or post
        data: '',               //  e.g. {name: "John", greeting: "hello"}
        minTimeout: 500,       // starting value for the timeout in milliseconds
        maxTimeout: 2000,       // maximum length of time between requests
        multiplier: 1.5,          // the amount to expand the timeout by if the response hasn't changed (up to maxTimeout)
        type: 'text'           // response type - text, xml, json, etc.  
    }, function(remoteData, success, xhr, handle) {
         if (remoteData.indexOf("file")!=-1){
					var numfiles=remoteData.replace("file ","");
					if (numfiles==1){
						var message=numfiles+' <?php echo $lang['fileaddedtozip']?>';
					} else { 
						var message=numfiles+' <?php echo $lang['filesaddedtozip']?>';
					}	 
					var status=(numfiles/<?php echo count($result)?>*100)+"%";
					console.log(status);
					document.getElementById('progress2').innerHTML=message;
				}
				else if (remoteData=="complete"){ 
				   document.getElementById('progress2').innerHTML="<?php echo $lang['zipcomplete']?>";
                   document.getElementById('progress').style.display="none";
                   progress.stop();    
                }  
                else {
					// fix zip message or allow any
					console.log(remoteData);
					document.getElementById('progress2').innerHTML=remoteData.replace("zipping","<?php echo $lang['zipping']?>");
                }
     
    });
		
}


        


</script>

	<form id='myform' action="<?php echo $baseurl_short?>pages/collection_download.php?id=<?php echo urlencode($uniqid) ?>&submitted=true" method=post>
        <?php generateFormToken("myform"); ?>
<input type=hidden name="collection" value="<?php echo htmlspecialchars($collection) ?>">
<input type=hidden name="usage" value="<?php echo htmlspecialchars($usage); ?>">
<input type=hidden name="usagecomment" value="<?php echo htmlspecialchars($usagecomment); ?>">
<input type=hidden name="k" value="<?php echo htmlspecialchars($k) ?>">


	<input type=hidden name="id" value="<?php echo htmlspecialchars($uniqid) ?>">
	<iframe id="downloadiframe" <?php if (!$debug_direct_download){?>style="display:none;"<?php } ?>></iframe>


<?php 
hook("collectiondownloadmessage");

if (!hook('replacesizeoptions'))
	{
    if($count_data_only_types !== count($result))
        {
        ?>
        <div class="Question">
        <label for="downloadsize"><?php echo $lang["downloadsize"]?></label>
        <div class="tickset">
    <?php
	$maxaccess=collection_max_access($collection);
	$sizes=get_all_image_sizes(false,$maxaccess>=1);

	$available_sizes=array_reverse($available_sizes,true);

	# analyze available sizes and present options
?><select name="size" class="stdwidth" id="downloadsize"<?php if (!empty($submitted)) echo ' disabled="disabled"' ?>><?php

function display_size_option($sizeID, $sizeName, $fordropdown=true)
	{
	global $available_sizes, $lang, $result;
	if(!hook('replace_display_size_option','',array($sizeID, $sizeName, $fordropdown))){
    	if ($fordropdown)
			{
			?><option value="<?php echo htmlspecialchars($sizeID) ?>"><?php
			echo $sizeName;
			}
    	if(isset($available_sizes[$sizeID]))
			{
			$availableCount = count($available_sizes[$sizeID]);
			}
		else
			{
			$availableCount=0;
			}
		$resultCount = count($result);
		if ($availableCount != $resultCount)
			{
			echo " (" . $availableCount . " " . $lang["of"] . " " . $resultCount . " ";
			switch ($availableCount)
				{
				case 0:
					echo $lang["are_available-0"];
					break;
				case 1:
					echo $lang["are_available-1"];
					break;
				default:
					echo $lang["are_available-2"];
					break;
				}
			echo ")";
			}
			 if ($fordropdown)
				{
			?></option><?php
			}
		}
	}

if (array_key_exists('original',$available_sizes))
	display_size_option('original', $lang['original'], true);

foreach ($available_sizes as $key=>$value)
	{
    foreach($sizes as $size)
		{
		if ($size['id']==$key)
			{
			display_size_option($key, $size['name'], true);
			break;
			}
		}
    }
?></select>

<div class="clearerleft"> </div></div>
<div class="clearerleft"> </div></div><?php
	   }
    }
if (!hook('replaceuseoriginal'))
	{
    if($count_data_only_types !== count($result))
        {
        ?>
        <div class="Question">
        <label for="use_original"><?php echo $lang['use_original_if_size']; ?> <br /><?php

        display_size_option('original', $lang['original'], false);
        ?></label><input type=checkbox id="use_original" name="use_original" value="yes" >
        <div class="clearerleft"> </div></div>
        <?php
	   }
    }

if ($zipped_collection_textfile=="true") { ?>
<div class="Question">
<label for="text"><?php echo $lang["zippedcollectiontextfile"]?></label>
<select name="text" class="shrtwidth" id="text"<?php if (!empty($submitted)) echo ' disabled="disabled"' ?>>
<?php if($zipped_collection_textfile_default_no){
	?><option value="false"><?php echo $lang["no"]?></option>
	<option value="true"><?php echo $lang["yes"]?></option><?php
}
else{
	?><option value="true"><?php echo $lang["yes"]?></option>
	<option value="false"><?php echo $lang["no"]?></option><?php
}
?>	
</select>
<div class="clearerleft"></div>
</div>

<?php
}
 
# Archiver settings
if ($archiver && count($collection_download_settings)>1)
    { ?>
    <div class="Question" id="archivesettings_question" <?php if($collection_download_tar){echo "style=\"display:none\"";}?>>
    <label for="archivesettings"><?php echo $lang["archivesettings"]?></label>
    <div class="tickset">
    <select name="settings" class="stdwidth" id="archivesettings"<?php if (!empty($submitted)) echo ' disabled="disabled"' ?>><?php
    foreach ($collection_download_settings as $key=>$value)
        { ?>
        <option value="<?php echo htmlspecialchars($key) ?>"><?php echo lang_or_i18n_get_translated($value["name"],"archive-") ?></option><?php
        } ?>
    </select>
    <div class="clearerleft"></div></div><br />
    </div><?php
    }	?>

<!-- Add CSV file with the metadata of all the resources found in this colleciton -->
<div class="Question">
	<label for="include_csv_file"><?php echo $lang['csvAddMetadataCSVToArchive']; ?></label>
	<input type="checkbox" id="include_csv_file" name="include_csv_file" value="yes">
	<div class="clearerleft"></div>
</div>

<?php
if($exiftool_write && !$force_exiftool_write_metadata)
    {
    ?>
    <!-- Let user say (if allowed - ie. not enforced by system admin) whether metadata should be written to the file or not -->
    <div class="Question" id="exiftool_question" <?php if($collection_download_tar_option){echo "style=\"display:none;\"";} ?>>
        <label for="write_metadata_on_download"><?php echo $lang['collection_download__write_metadata_on_download_label']; ?></label>
        <input type="checkbox" id="write_metadata_on_download" name="write_metadata_on_download" value="yes" >
		<div class="clearerleft"></div>
    </div>
    <?php
    }
?>
	
<div class="Question"  <?php if(!$collection_download_tar){echo "style=\"display:none;\"";} ?>>
	<label for="tardownload"><?php echo $lang["collection_download_format"]?></label>
	<div class="tickset">
	<select name="tardownload" class="stdwidth" id="tardownload" onChange="if(jQuery(this).val()=='off'){ajax_on=true;jQuery('#exiftool_question').slideDown();jQuery('#archivesettings_question').slideDown();}else{ajax_on=false;jQuery('#exiftool_question').slideUp();jQuery('#archivesettings_question').slideUp();}">
		   <option value="off"><?php echo $lang["collection_download_no_tar"]; ?></option>
		   <option value="on" <?php if($collection_download_tar_option) {echo "selected";} ?> ><?php echo$lang["collection_download_use_tar"]; ?></option>	   
	</select>
	
	<div class="clearerleft"></div></div><br />
	<div class="clearerleft"></div>
	<label for="tarinfo"></label>
	<div class="Fixed"><?php echo $lang["collection_download_tar_info"]  . "<br />" . $lang["collection_download_tar_applink"]?></div>
	
	<div class="clearerleft"></div>
</div>
	
<div class="QuestionSubmit" id="downloadbuttondiv"> 
	<label for="download"> </label>
	<script>var ajax_on=<?php echo ($collection_download_tar)?"true":"false"; ?>;</script>
	<input type="submit" onclick="if(ajax_on){ajax_download();return false;}" value="&nbsp;&nbsp;<?php echo $lang["action-download"]?>&nbsp;&nbsp;" />
	
	<div class="clearerleft"> </div>
</div>

<div id="progress"></div>


<div class="Question" id="progressdiv" style="display:none;border-top:none;"> 
<label><?php echo $lang['progress']?></label>
<div class="Fixed" id="progress3" ></div>
<div class="Fixed" id="progress2" ></div>


<div class="clearerleft"></div></div>

</form>



</div>
<?php 
include "../include/footer.php";
?>

