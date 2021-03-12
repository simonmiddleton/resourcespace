<?php 
ini_set('zlib.output_compression','off'); // disable PHP output compression since it breaks collection downloading
include "../include/db.php";

# External access support (authenticate only if no key provided, or if invalid access key provided)
$k=getvalescaped("k","");if (($k=="") || (!check_access_key_collection(getvalescaped("collection","",true),$k))) {include "../include/authenticate.php";}include_once '../include/csv_export_functions.php';
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
$include_csv_file = getval('include_csv_file', '');

if($k != "" || (isset($anonymous_login) && $username == $anonymous_login))
    {
    // Disable offline jobs as there is currently no way to notify the user upon job completion
    $offline_job_queue = false;
    }

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
		if (empty($results)) {exit($lang["nothing_to_download"]);}
		$disk_usage=$results[0]["total_disk_usage"];
		if($disk_usage >= $collection_download_tar_size*1024*1024)
			{
			$collection_download_tar_option=true;
			}
		}
	}
	
$settings_id=(isset($collection_download_settings) && count($collection_download_settings)>1)?getvalescaped("settings",""):0;
$uniqid=getval("id",uniqid("Col" . $collection));

$usage = getvalescaped('usage', '-1');
$usagecomment = getvalescaped('usagecomment', '');

// set the time limit to unlimited, default 300 is not sufficient here.
set_time_limit(0);

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
$available_sizes = array();
$count_data_only_types = 0;

#build the available sizes array
for ($n=0;$n<count($result);$n++)
	{
	$ref=$result[$n]["ref"];
	# Load access level (0,1,2) for this resource
	$access=get_resource_access($result[$n]);
	
    # Get all possible sizes for this resource. If largest available has been requested then include internal or user could end up with no file depite being able to see the preview
	$sizes=get_all_image_sizes($size=="largest",$access>=1);

	#check availability of original file 
    $p=get_resource_path($ref,true,"",false,$result[$n]["file_extension"]);
	if (file_exists($p) && (($access==0) || ($access==1 && $restricted_full_download)) && resource_download_allowed($ref,'',$result[$n]['resource_type']))
		{
        $available_sizes['original'][]=$ref;
		}

	# check for the availability of each size and load it to the available_sizes array
	foreach ($sizes as $sizeinfo)
		{
		$size_id=$sizeinfo['id'];
		$size_extension = get_extension($result[$n], $size_id);
		$p=get_resource_path($ref,true,$size_id,false,$size_extension);

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
    
if(isset($user_dl_limit) && intval($user_dl_limit) > 0)
    {
    $download_limit_check = get_user_downloads($userref,$user_dl_days);
    if($download_limit_check + count($result) > $user_dl_limit)
        {
        $dlsummary = $download_limit_check . "/" . $user_dl_limit;
        $errormessage = $lang["download_limit_collection_error"] . " " . str_replace(array("%%DOWNLOADED%%","%%LIMIT%%"),array($download_limit_check,$user_dl_limit),$lang['download_limit_summary']);
        if(getval("ajax","") != "")
            {
            error_alert(htmlspecialchars($errormessage), true,200);
            }
        else
            {
            include "../include/header.php";
            $onload_message = array("title" => $lang["error"],"text" => $errormessage);
            include "../include/footer.php";
            }
        exit();
        }
    }

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

    if(!$collection_download_tar && $offline_job_queue)
        {
        foreach ($result as $key => $resdata)
            {
             // Only need to store resource IDS, not full search data
            $jobresult[$key] = array("ref" => $resdata["ref"]);
            }

        $collection_download_job_data = array(
            'collection'            => $collection,
            'collectiondata'        => $collectiondata,
            'result'                => $jobresult,
            'size'                  => $size,
            'exiftool_write_option' => $exiftool_write_option,
            'useoriginal'           => $useoriginal,
            'id'                    => $id,
            'includetext'           => $includetext,
            'count_data_only_types' => $count_data_only_types,
            'usage'                 => $usage,
            'usagecomment'          => $usagecomment,
            'settings_id'           => $settings_id,
            'include_csv_file'      => $include_csv_file
        );

        $modified_job_data = hook("collection_download_modify_job","",array($collection_download_job_data));
        if(is_array($modified_job_data))
            {
            $collection_download_job_data = $modified_job_data;
            }

        job_queue_add(
            'collection_download',
            $collection_download_job_data,
            '',
            '',
            $lang["oj-collection-download-success-text"],
            $lang["oj-collection-download-failure-text"]);

        exit();
        }

	# Estimate the total volume of files to zip
	$totalsize=0;
	for ($n=0;$n<count($result);$n++)
		{
        $ref = $result[$n]['ref'];
        
        if($size=="largest")
            {
            foreach($available_sizes as $available_size => $resources)
                {
                if(in_array($ref,$resources))
                    {   
                    $usesize = $available_size;
                    if($available_size == 'original')
                        {
                        $usesize = "";
                        // Has access to the original so no need to check previews
                        break;
                        }
                    }
                }
            }
        else
            {
            $usesize = ($size == 'original') ? "" : $size;
            }        

        $use_watermark=check_use_watermark();
            

        $pextension = get_extension($result[$n], $usesize);
		
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
    if(!ctype_alnum($id)){exit($lang["error"]);}
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
		collection_download_get_archive_file($archiver, $settings_id, $usertempdir, $collection, $size, $zip, $zipfile);
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
        if ($access==0 || $access==1)
            {			
            if($size=="largest")
                {
                foreach($available_sizes as $available_size => $resources)
                    {
                    if(in_array($ref,$resources))
                        {   
                        $usesize = $available_size;
                        if($available_size == 'original')
                            {
                            // Has access to the original so no need to check previews
                            $usesize = "";
                            break;
                            }
                        }
                    }
                }
            else
                {
                $usesize = ($size == 'original') ? "" : $size;
                }      
            
            $pextension = get_extension($result[$n], $usesize);
            $p=get_resource_path($ref,true,$usesize,false,$pextension,-1,1,$use_watermark);

			# Determine whether target exists
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

			# Process the file if it exists, and (if restricted access) that the user has access to the requested size
			if ((($target_exists && $access==0) ||
				($target_exists && $access==1 &&
					(image_size_restricted_access($size) || ($usesize=='' && $restricted_full_download))) 
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
				$filename=get_download_filename($ref,$usesize,0,$pextension);
				collection_download_use_original_filenames_when_downloading($filename, $ref, $collection_download_tar, $filenames,$id);

                if (hook("downloadfilenamealt")) $filename=hook("downloadfilenamealt");

                collection_download_process_text_file($ref, $collection, $filename);

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

                collection_download_log_resource_ready($tmpfile, $deletion_array, $ref);
				}
			}

		}
    // Collection contains data_only resource types
    if(0 < $count_data_only_types)
        {
        collection_download_process_data_only_types($result, $id, $collection_download_tar, $usertempdir, $zip, $path, $deletion_array);
        }
    else if('' == $path)
        {
        exit($lang['nothing_to_download']);
        }

    collection_download_process_summary_notes(
        $result,
        $available_sizes,
        $text,
        $subbed_original_resources,
        $used_resources,
        $id,
        $collection,
        $collectiondata,
        $collection_download_tar,
        $usertempdir,
        $filename,
        $path,
        $deletion_array,
        $size,
        $zip);

    if($include_csv_file == 'yes')
        {
        collection_download_process_csv_metadata_file(
            $result,
            $id,
            $collection,
            $collection_download_tar,
            $use_zip_extension,
            $zip,
            $path,
            $deletion_array);
        }

	collection_download_process_command_to_file($use_zip_extension, $collection_download_tar, $id, $collection, $size, $path);

	if($collection_download_tar)
		{$suffix = '.tar';}
	elseif ($archiver)
		$suffix = '.' . $collection_download_settings[$settings_id]['extension'];
	else
		$suffix = '.zip';

	collection_download_process_collection_download_name($filename, $collection, $size, $suffix, $collectiondata);
		
    collection_download_process_archive_command($collection_download_tar, $zip, $filename, $usertempdir, $archiver, $settings_id, $zipfile);

    collection_download_clean_temp_files($deletion_array);

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
        try {
            rmdir(get_temp_dir(false,$id));
            }
        catch(Exception $e)
            {
            debug("collection_download: Attempt delete temp folder failed. Reason: {$e->getMessage()}");
            }
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

function ajax_download(download_offline, tar)
	{
    console.debug('ajax_download(download_offline = %o, tar = %o)', download_offline, tar);
    var ifrm = document.getElementById('downloadiframe');
    ifrm.src = "<?php echo $baseurl_short?>pages/collection_download.php?submitted=true&"+jQuery('#myform').serialize();

    if(download_offline && !tar)
        {
        styledalert('<?php echo $lang['collection_download']; ?>', '<?php echo $lang['jq_notify_user_preparing_archive']; ?>');
        document.getElementById('downloadbuttondiv').style.display='none';
        return false;
        }

	document.getElementById('downloadbuttondiv').style.display='none';	
	document.getElementById('progress').innerHTML='<br /><br /><?php echo $lang["collectiondownloadinprogress"];?>';
	document.getElementById('progress3').style.display='none';
	document.getElementById('progressdiv').style.display='block';

	// Disable form controls -- this needs to happen after serializing the form or else they are ignored
	jQuery('#downloadsize').prop('disabled', true);
	jQuery('#use_original').prop('disabled', true);
	jQuery('#text').prop('disabled', true);
	jQuery('#archivesettings').prop('disabled', true);

	if(tar)
        {
        document.getElementById('progress2').innerHTML="<?php echo $lang['collection_download_tar_started']?>";
        document.getElementById('progress').style.display="none";
        }
    else
        {
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


if (array_key_exists('original',$available_sizes))
	display_size_option('original', $lang['original'], true);
	display_size_option('largest', $lang['imagesize-largest'], true);

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

<script>var tar = <?php echo ($collection_download_tar_option ? 'true' : 'false'); ?>;</script>
<div class="Question"  <?php if(!$collection_download_tar){echo "style=\"display:none;\"";} ?>>
	<label for="tardownload"><?php echo $lang["collection_download_format"]?></label>
	<div class="tickset">
	<select name="tardownload" class="stdwidth" id="tardownload" onChange="if(jQuery(this).val()=='off'){tar=false;jQuery('#exiftool_question').slideDown();jQuery('#archivesettings_question').slideDown();}else{tar=true;jQuery('#exiftool_question').slideUp();jQuery('#archivesettings_question').slideUp();}">
		   <option value="off"><?php echo $lang["collection_download_no_tar"]; ?></option>
		   <option value="on" <?php if($collection_download_tar_option) {echo "selected";} ?> ><?php echo$lang["collection_download_use_tar"]; ?></option>	   
	</select>
	
	<div class="clearerleft"></div></div><br />
	<div class="clearerleft"></div>
	<label for="tarinfo"></label>
	<div class="FormHelpInner tickset"><?php echo $lang["collection_download_tar_info"]  . "<br />" . $lang["collection_download_tar_applink"]?></div>
	
	<div class="clearerleft"></div>
</div>
	
<div class="QuestionSubmit" id="downloadbuttondiv"> 
	<label for="download"> </label>
	<input type="submit"
           onclick="ajax_download(<?php echo ($offline_job_queue ? 'true' : 'false'); ?>, tar); return false;"
           value="&nbsp;&nbsp;<?php echo $lang["action-download"]?>&nbsp;&nbsp;" />
	
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

