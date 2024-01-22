<?php
include_once dirname(__DIR__, 3) . '/include/image_processing.php';

/**
 * Returns the default output file format to use given an optional input format.
 */
function getDefaultOutputFormat($inputFormat = null)
	{
	global $format_chooser_default_output_format, $format_chooser_output_formats;

	if (!empty($format_chooser_default_output_format))
		return $format_chooser_default_output_format;

	$inputFormat = strtoupper((string) $inputFormat);

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
    $inputFormat = strtoupper((string) $inputFormat);
	return in_array($inputFormat, $format_chooser_input_formats);
	}

/**
 * Returns the size record from the database specified by its ID.
 */
function getImageFormat($size)
	{
	if (empty($size))
		return array('width' => 0, 'height' => 0);

    $sizes = get_all_image_sizes();
    $found_idx = array_search($size, array_column($sizes, 'id'));
    if($found_idx === false)
        {
        die('Unknown size: "' . $size . '"');
        }

    return [
        'width' => $sizes[$found_idx]['width'],
        'height' => $sizes[$found_idx]['height'],
    ];
	}

/**
 * Converts the file of the given resource to the new target file with the specified size. The
 * target file format is determined from the suffix of the target file.
 * The original colorspace of the image is retained. If $width and $height are zero, the image
 * keeps its original size.
 */
function convertImage($resource, $page, $alternative, $target, $width, $height, $profile)
	{
    global $exiftool_write, $exiftool_write_option, $username, $scramble_key, $preview_no_flatten_extensions;

	$requested_extension = $resource['file_extension'];
	# If downloading alternative file, lookup its file extension before preparing resource path as it may differ from the resource.
	if ($alternative > 0)
	    {
	    $alt_file = get_alternative_file($resource['ref'], $alternative);
	    $requested_extension = $alt_file['file_extension'];
	    }

	$originalPath = get_resource_path($resource['ref'], true, '', false, $requested_extension, -1, $page, false, '', $alternative);

    if($exiftool_write && $exiftool_write_option)
        {
	    $randstring=md5(rand() . microtime());
	    $target_temp_id = $resource['ref'] . "_" . md5($username . $randstring . $scramble_key);
		$path = write_metadata($originalPath, $resource['ref'], "format_chooser/" . $target_temp_id);
        //$temp_path for removal later to assure not removing original path
        $temp_path = get_temp_dir(false,"format_chooser/" . $resource['ref'] . "_" . md5($username . $randstring . $scramble_key));
        }
    else
	    {
	    $path = $originalPath;
	    }

    $transform_actions = [
        'tfactions' => [],
        'resize' => ['width' => $width, 'height' => $height],
        'auto_orient' => null,
    ];


	// Preserve transparency like background for conversion from eps files (transparency is not supported in jpg file type).		
	if ($resource['file_extension'] == "eps")		
        {
        $transform_actions['transparent'] = '';
		}
	
    // Handle alpha/ matte channels
    $target_extension = pathinfo($target, PATHINFO_EXTENSION);
    if(!in_array(strtolower($target_extension), $preview_no_flatten_extensions))
        {
        $transform_actions['background'] = 'white';
        }

	if($profile === '')
		{
        $transform_actions['profile'][] = ['strip' => true, 'path' => '*'];
		}
	elseif(!empty($profile))
		{
		// Find out if the image does already have a profile
		$identify = get_utility_path("im-identify");
		$identify .= ' -verbose "' . $path . '"';
		$info = run_command($identify);

        $basePath = dirname(__FILE__, 4) . '/';
        if(preg_match("/Profile-icc:/", $info) != 1)
            {
            $transform_actions['profile'][] = ['strip' => false, 'path' => $basePath . 'iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc'];
            }

        $transform_actions['profile'][] = ['strip' => false, 'path' => $basePath . $profile];
		}

    if(!transform_file($path, $target, $transform_actions))
        {
        die('Unable to transform file!');
        }

    //remove temp once completed
    if(isset($temp_path))
        {
        rcRmdir($temp_path);
        }
	}

function sendFile($filename, string $download_filename, $usage = -1, $usagecomment = "")
	{
	$suffix = pathinfo($filename, PATHINFO_EXTENSION);
	$size = filesize_unlimited($filename);

    global $baseurl, $username, $scramble_key, $exiftool_write;

    list($resource_ref, $download_key) = explode('_', pathinfo($filename, PATHINFO_FILENAME));
    $user_downloads_path = sprintf('%s/%s_%s.%s',
        get_temp_dir(false, 'user_downloads'),
        $resource_ref,
        md5($username . $download_key . $scramble_key),
        $suffix
    );
    copy($filename, $user_downloads_path);

    $user_download_url = generateURL(
        $baseurl . '/pages/download.php',
        [
            'userfile'      => pathinfo($filename, PATHINFO_BASENAME),
            'filename'      => strip_extension($download_filename, false),
            'usage'         => $usage,
            'usagecomment'  => $usagecomment,
			'k'             => getval('k', ''),
			'ref'           => getval('ref', ''),
			'exif_write'    => ($exiftool_write ? 'true' : '')
        ]
    );
    redirect($user_download_url);
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
