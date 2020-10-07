<?php

/**
 * Returns the default output file format to use given an optional input format.
 */
function getDefaultOutputFormat($inputFormat = null)
	{
	global $format_chooser_default_output_format, $format_chooser_output_formats;

	if (!empty($format_chooser_default_output_format))
		return $format_chooser_default_output_format;

	$inputFormat = strtoupper($inputFormat);

	# Use resource format by default if none given
	if (empty($inputFormat) || !in_array($inputFormat, $format_chooser_output_formats))
		{
		if (in_array('JPG', $format_chooser_output_formats))
			return 'JPG';
		return $format_chooser_output_formats[0];
		}

	return $inputFormat;
	}

function supportsInputFormat($inputFormat)
	{
	global $format_chooser_input_formats;
	$inputFormat = strtoupper($inputFormat);

	return in_array($inputFormat, $format_chooser_input_formats);
	}

/**
 * Returns the size record from the database specified by its ID.
 */
function getImageFormat($size)
	{
	if (empty($size))
		return array('width' => 0, 'height' => 0);

	$results = sql_query("select * from preview_size where id='" . escape_check($size) . "'");
	if (empty($results))
		die('Unknown size: "' . $size . '"');
	return $results[0];
	}

/**
 * Converts the file of the given resource to the new target file with the specified size. The
 * target file format is determined from the suffix of the target file.
 * The original colorspace of the image is retained. If $width and $height are zero, the image
 * keeps its original size.
 */
function convertImage($resource, $page, $alternative, $target, $width, $height, $profile)
	{
	$command = get_utility_path("im-convert");
	if (!$command)
		die("Could not find ImageMagick 'convert' utility.");

	$originalPath = get_resource_path($resource['ref'], true, '', false,
			$resource['file_extension'], -1, $page, false, '', $alternative);

	// Preserve transparency like background for conversion from eps files (transparency is not supported in jpg file type).		
	if ($resource['file_extension'] == "eps")		
        {
		$command .= " \"$originalPath\"[0] -transparent -auto-orient";
		}
	else
	    {
	    $command .= " \"$originalPath\"[0] -auto-orient";
	    }
	
    // Handle alpha/ matte channels
    $extensions_no_alpha_off = array('png', 'gif', 'tif');
    $target_extension        = pathinfo($target, PATHINFO_EXTENSION);

    if(!in_array($target_extension, $extensions_no_alpha_off))
        {
        $command .= ' -background white -flatten';
        }

	if ($width != 0 && $height != 0)
		{
		# Apply resize ('>' means: never enlarge)
		$command .= " -resize \"$width";
		if ($height > 0)
			$command .= "x$height";
		$command .= '>"';
		}

	if($profile === '')
		{
		$command .= ' +profile *';
		}
	elseif(!empty($profile))
		{
		// Find out if the image does already have a profile
		$identify = get_utility_path("im-identify");
		$identify .= ' -verbose "' . $originalPath . '"';
		$info = run_command($command);
		$basePath = dirname(__FILE__) . '/../../../';
		if (preg_match("/Profile-icc:/", $info) != 1)
			$command .= ' -profile "' . $basePath . 'iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc"';
		$command .= ' -profile "' . $basePath . $profile . '"';
		}

	$command .= " \"$target\"";

	// echo '<pre>';print_r($command);echo '</pre>';die('<br>You died in ' . __FILE__ . ' @' . __LINE__);

	run_command($command);
	}

function sendFile($filename)
	{
	$suffix = pathinfo($filename, PATHINFO_EXTENSION);
	$size = filesize_unlimited($filename);

	header('Content-Transfer-Encoding: binary');
	header('Content-Disposition: attachment; filename="' . str_replace(array("\n","\r"),"",mb_basename($filename)) . '"');
	header('Content-Type: ' . get_mime_type($filename, $suffix));
	header('Content-Length: ' . $size);
	header("Content-Type: application/octet-stream");

	ob_end_flush();

	readfile($filename);
	}

function showProfileChooser($class = '', $disabled = false)
	{
	global $format_chooser_profiles, $lang;

	if (empty($format_chooser_profiles))
		return;

	?><select name="profile" id="profile" <?php if (!empty($class)) echo 'class="' . $class . '"';
			echo $disabled ? ' disabled="disabled"' : ''; ?>>
		<option value="" selected="selected"><?php
				echo $lang['format_chooser_keep_profile'] ?></option><?php

	$index = 0;
	foreach (array_keys($format_chooser_profiles) as $name)
		{
		if (empty($name))
			$name = $lang['format_chooser_remove_profile'];
		?><option value="<?php echo $index++ ?>"><?php echo i18n_get_translated($name) ?></option><?php
		}

	?></select><?php
	}

function getProfileFileName($profile)
	{
	global $format_chooser_profiles;

	if ($profile !== null && !empty($format_chooser_profiles))
		{
		$profiles = array_values($format_chooser_profiles);
		return $profiles[intval($profile)];
		}
	return null;
	}

?>
