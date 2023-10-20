<?php

include_once dirname(__FILE__) . "/../include/utility.php";

function HookFormat_chooserAllGetdownloadurl($ref, $size, $ext, $page = 1, $alternative = -1, $usage = -1, $usagecomment = "")
	{
	global $baseurl_short,$imagemagick_preserve_profiles, $format_chooser_input_formats, $format_chooser_output_formats, $k;

	// Disable for e-commerce
	if (is_ecommerce_user()) { return false; }

		// Check whether download file extension matches
    if(!in_array(strtoupper($ext),$format_chooser_output_formats))
        {return false;}

    $url_qs = [
        'ref' => $ref,
        'size' => $size,
        'k' => $k,
        'ext' => $ext,
        'page' => $page,
        'alt' => $alternative,
        'usage' => $usage,
        'usagecomment' => $usagecomment,
    ];
    $resource_data = get_resource_data($ref);

    // Check whether original resource file extension matches    
    $original_ext = $resource_data['file_extension'] ?? '';
    if(!in_array(strtoupper($original_ext),$format_chooser_input_formats))
        {return false;}
    
    $profile = getval('profile' , null);
	if (!empty($profile))
        {
        $url_qs['profile'] = $profile;
        }
	else
		{
		$path = get_resource_path($ref, true, $size, false, $ext, -1, $page,$size=="scr" && checkperm("w") && $alternative==-1, '', $alternative);
        // We can use the existing previews unless we need to preserve the colour profiles,
        // these are likely to have been removed from scr size and below.
        // Alternative files not being converted can also use the existing file
        if (file_exists($path) 
            && (!$imagemagick_preserve_profiles 
                || in_array($size,array("hpr","lpr")) 
                || $alternative !== -1
                )
            )
		return false;
		}
    
    return generateURL($baseurl_short . 'plugins/format_chooser/pages/convert.php', $url_qs);
	}

// Following moved from collection_download to work for offline jobs

function HookFormat_chooserAllReplaceuseoriginal()
	{
	global $format_chooser_output_formats, $format_chooser_profiles, $lang, $use_zip_extension;

	// Disable for e-commerce
	if (is_ecommerce_user()) { return false; }

	$disabled = '';
	$submitted = getval('submitted', null);
	if (!empty($submitted))
		$disabled = ' disabled="disabled"';

	# Replace the existing ajax_download() with our own that disables our widgets, too
	if ($use_zip_extension)
		{
		?><script>
			var originalDownloadFunction = ajax_download;
			ajax_download = function(download_offline) {
				originalDownloadFunction(download_offline,tar);
				jQuery('#downloadformat').attr('disabled', 'disabled');
				jQuery('#profile').attr('disabled', 'disabled');
			}
		</script><?php
		}

	?><div class="Question">
	<input type=hidden name="useoriginal" value="yes" />
	<label for="downloadformat"><?php echo $lang["downloadformat"]?></label>
	<select name="ext" class="stdwidth" id="downloadformat"<?php echo $disabled ?>>
		<option value="" selected="selected"><?php echo $lang['format_chooser_keep_format'] ?></option>
	<?php
	foreach ($format_chooser_output_formats as $format)
		{
		?><option value="<?php echo $format ?>"><?php echo str_replace_formatted_placeholder("%extension", $format, $lang["field-fileextension"]) ?></option><?php
		}
	?></select>
	<div class="clearerleft"> </div></div><?php
	if (!empty($format_chooser_profiles))
		{
		?>
		<div class="Question">
		<label for="profile"><?php echo $lang['format_chooser_choose_profile']?></label>
		<?php showProfileChooser('stdwidth') ?>
		<div class="clearerleft"> </div></div><?php
		}
	return true;
	}

function HookFormat_chooserAllSize_is_available($resource, $path, $size)
	{

	// Disable for e-commerce
	if (is_ecommerce_user()) { return false; }

	if (!supportsInputFormat($resource['file_extension']))
		{
		# Let the caller decide whether the file is available
		return false;
		}

	$sizes = get_all_image_sizes();

	# Filter out the largest one
	$maxSize = null;
	$maxWidth = 0;
	for ($n = 0; $n < count($sizes); $n++)
		{
		if ($maxWidth < (int)$sizes[$n]['width'])
			{
			$maxWidth = (int)$sizes[$n]['width'];
			$maxSize = $sizes[$n]['id'];
			}
		}
	return $size!=$maxSize;
	}

function HookFormat_chooserAllReplacedownloadextension($resource, $extension)
	{
	global $format_chooser_output_formats, $job_ext;

	// Disable for e-commerce
	if (is_ecommerce_user()) { return false; }

	$inputFormat = $resource['file_extension'];

	if (!supportsInputFormat($inputFormat))
		{
		# Download the original file for this resource
		return $inputFormat;
        }
        
    $reqext = (isset($job_ext) && $job_ext != "") ? $job_ext : getval("ext",getDefaultOutputFormat($inputFormat)); 
    $ext = strtoupper($reqext);
    if (empty($ext) || !in_array($ext, $format_chooser_output_formats))
        {
        return false;
        }

	return strtolower($ext);
	}

function HookFormat_chooserAllReplacedownloadfile($resource, $size, $ext,
		$fileExists)
	{

	// Disable for e-commerce
	if (is_ecommerce_user()) { return false; }

	if (!supportsInputFormat($resource['file_extension']))
		{
		# Do not replace files we do not support
		return false;
		}

	$profile = getProfileFileName(getval('profile', null));
	if ($profile === null && $fileExists)
		{
		# Just serve the original file
		return false;
		}

	$baseDirectory = get_temp_dir() . '/format_chooser';
	
	if (!file_exists($baseDirectory))
	    {
	    mkdir($baseDirectory);
	    }

	$target = $baseDirectory . '/' . get_download_filename($resource['ref'],$size,-1,$ext);

	$format = getImageFormat($size);
	$width = (int)$format['width'];
	$height = (int)$format['height'];

	set_time_limit(0);
	convertImage($resource, 1, -1, $target, $width, $height, $profile);
	return $target;
	}

function HookFormat_chooserAllCollection_download_modify_job($job_data=array())
    {

	// Disable for e-commerce
	if (is_ecommerce_user()) { return false; }

    $ext = getval("ext","");
    if(trim($ext) != "")
        {
        // Add requested extension to offline job data
        $job_data["ext"] = $ext;
        return $job_data;
        }

    return false;
    }