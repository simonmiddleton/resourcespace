<?php

include_once dirname(__FILE__) . "/../include/utility.php";

function HookFormat_chooserAllGetdownloadurl($ref, $size, $ext, $page = 1, $alternative = -1)
	{
	global $baseurl_short,$imagemagick_preserve_profiles;

	$profile = getvalescaped('profile' , null);
	if (!empty($profile))
		$profile = '&profile=' . $profile;
	else
		{
		$path = get_resource_path($ref, true, $size, false, $ext, -1, $page,$size=="scr" && checkperm("w") && $alternative==-1, '', $alternative);
		if (file_exists($path) && (!$imagemagick_preserve_profiles || in_array($size,array("hpr","lpr")))) // We can use the existing previews unless we need to preserve the colour profiles (these are likely to have been removed from scr size and below) 
		return false;
		}

	return $baseurl_short . 'plugins/format_chooser/pages/convert.php?ref=' . $ref . '&size='
			. $size . '&ext=' . $ext . $profile . '&page=' . $page . '&alt=' . $alternative;
	}

?>
