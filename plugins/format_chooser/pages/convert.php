<?php

include '../../../include/db.php';
include_once dirname(__FILE__) . "/../include/utility.php";

$k=getvalescaped("k","");
$ref = getvalescaped('ref', 0, true);
$size = getvalescaped('size', '');
$page = getvalescaped('page', 1, true);
$alternative = getvalescaped('alt', -1, true);

$resource = get_resource_data($ref);
$width = getvalescaped('width', 0, true);
$height = getvalescaped('height', 0, true);

if('' == $k || !check_access_key($ref, $k)) //Check if available through external share
    {
    include dirname(__FILE__) . '/../../../include/authenticate.php';
    }

// Permissions check
$allowed = resource_download_allowed($ref, $size, $resource['resource_type'], $alternative);
debug("PLUGINS/FORMAT_CHOOSER/PAGES/CONVERT.PHP: \$allowed = " . ($allowed == true ? 'TRUE' : 'FALSE'));

if(!$allowed || $ref <= 0)
    {
    debug("PLUGINS/FORMAT_CHOOSER/PAGES/CONVERT.PHP: Permission denied!");
    # This download is not allowed. How did the user get here?
    exit('Permission denied');
    }

if ($width == 0 && $height == 0)
	{
	$format = getImageFormat($size);
	$width = (int)$format['width'];
	$height = (int)$format['height'];
	}

$ext = getvalescaped('ext', getDefaultOutputFormat());
$profile = getProfileFileName(getvalescaped('profile', null));

$target = sprintf('%s/%s_%s.%s',
    get_temp_dir(false, 'format_chooser'),
    $ref,
    md5($username . date('Ymd', time()) . $scramble_key),
    $ext
);

set_time_limit(0);

convertImage($resource, $page, $alternative, $target, $width, $height, $profile);

daily_stat('Resource download', $ref);
resource_log($ref, LOG_CODE_DOWNLOADED, 0,$lang['format_chooser'], '',  $size);

if(file_exists($target))
    {
    sendFile($target, get_download_filename($ref, $size, $alternative, $ext));
    unlink($target);
    }
