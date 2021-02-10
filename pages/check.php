<?php
include "../include/db.php";
include "../include/authenticate.php"; if (!checkperm("a")) {exit("Access denied.");}
include "../include/header.php";
?>

<div class="BasicsBox"> 
    <h1><?php echo $lang["installationcheck"];render_help_link("systemadmin/install_overview");?></h1>
    <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/check.php">
        <?php echo '<i aria-hidden="true" class="fa fa-sync-alt"></i>&nbsp;' . $lang["repeatinstallationcheck"]?>
    </a>
    <br/><br/>
    <table class="InfoTable">
<?php


# Check ResourceSpace Build
$build = '';
if ($productversion == 'SVN')
    {
    $p_version = 'Trunk (SVN)'; # Should not be translated as this information is sent to the bug tracker.
    //Try to run svn info to determine revision number
    $out = array();
    exec('svn info ../', $out);
    foreach($out as $outline)
        {
        $matches = array();
        if (preg_match('/^Revision: (\d+)/i', $outline, $matches)!=0)
            {
            $build .= "r" . $matches[1];
            }
        $matches = array();
        if (preg_match('/^Relative URL: (.*)/i', $outline, $matches)!=0)
            {
            $build = str_replace("^","",$matches[1]) . " " . $build;
            }
        elseif (strpos($outline, "URL: ") === 0)
            {
            $urlparts = explode("/",$outline);
            $build = end($urlparts) . " ";
            }
        } 
    }

# ResourceSpace version
$p_version = $productversion == 'SVN'?'Subversion ' . $build:$productversion; # Should not be translated as this information is sent to the bug tracker.

?><tr><td nowrap="true"><?php echo str_replace("?", "ResourceSpace", $lang["softwareversion"]); ?></td><td><?php echo $p_version?></td><td><br /></td></tr><?php

# Check PHP version
$phpversion=phpversion();
$phpinifile=php_ini_loaded_file();
if ($phpversion<'4.4') {$result=$lang["status-fail"] . ": " . str_replace("?", "4.4", $lang["shouldbeversion"]);} else {$result=$lang["status-ok"];}
?><tr><td><?php echo str_replace("?", "PHP", $lang["softwareversion"]); ?></td><td><?php echo $phpversion .'&ensp;&ensp;' . str_replace("%file", $phpinifile, $lang["config_file"]);?></td><td><b><?php echo $result?></b></td></tr><?php

# Check MySQL version
$mysqlversion = mysqli_get_server_info($db["read_write"]);
if($mysqlversion < '5')
    {
    $result = $lang["status-fail"] . ": " . str_replace("?", "5", $lang["shouldbeversion"]);
    }
else
    {
    $result = $lang["status-ok"];
    }
$encoding = mysqli_character_set_name($db["read_write"]);
$encoding_str = str_replace("%encoding", $encoding, $lang["client-encoding"]);
$db_encoding = sql_value("
    SELECT default_character_set_name AS `value`
      FROM information_schema.SCHEMATA
     WHERE `schema_name` = '" . escape_check($mysql_db) . "';", $lang["unknown"]);
$db_encoding_str = str_replace("%encoding", $db_encoding, $lang["db-default-encoding"]);
$encoding_output = "{$mysqlversion}&ensp;&ensp;{$encoding_str} {$db_encoding_str}";
?>
<tr>
    <td><?php echo str_replace("?", "MySQL", $lang["softwareversion"]); ?></td>
    <td><?php echo $encoding_output; ?></td>
    <td><b><?php echo $result; ?></b></td>
</tr><?php

# Check GD installed
if (function_exists("gd_info"))
	{
	$gdinfo=gd_info();
	if (is_array($gdinfo))
		{
		$version=$gdinfo["GD Version"];
		$result=$lang["status-ok"];
		}
	else
		{
		$version=$lang["status-notinstalled"];
		$result=$lang["status-fail"];
		}
	}
else
	{
	$version=$lang["status-notinstalled"];
	$result=$lang["status-fail"];
	}
?><tr><td><?php echo str_replace("?", "GD", $lang["softwareversion"]); ?></td><td><?php echo $version?></td><td><b><?php echo $result?></b></td></tr><?php

# Check ini values for memory_limit, post_max_size, upload_max_filesize
$memory_limit=ini_get("memory_limit");
if (ResolveKB($memory_limit)<(200*1024)) {$result=$lang["status-warning"] . ": " . str_replace("?", "200M", $lang["shouldbeormore"]);} else {$result=$lang["status-ok"];}
?><tr><td><?php echo str_replace("?", "memory_limit", $lang["phpinivalue"]); ?></td><td><?php echo $memory_limit?></td><td><b><?php echo $result?></b></td></tr><?php

$post_max_size=ini_get("post_max_size");
if (ResolveKB($post_max_size)<(100*1024)) {$result=$lang["status-warning"] . ": " . str_replace("?", "100M", $lang["shouldbeormore"]);} else {$result=$lang["status-ok"];}
?><tr><td><?php echo str_replace("?", "post_max_size", $lang["phpinivalue"]); ?></td><td><?php echo $post_max_size?></td><td><b><?php echo $result?></b></td></tr><?php

$upload_max_filesize=ini_get("upload_max_filesize");
if (ResolveKB($upload_max_filesize)<(100*1024)) {$result=$lang["status-warning"] . ": " . str_replace("?", "100M", $lang["shouldbeormore"]);} else {$result=$lang["status-ok"];}
?><tr><td><?php echo str_replace("?", "upload_max_filesize", $lang["phpinivalue"]); ?></td><td><?php echo $upload_max_filesize?></td><td><b><?php echo $result?></b></td></tr><?php

# Check write access to filestore
$success=is_writable($storagedir);
if ($success===false) {$result=$lang["status-fail"] . ": " . $storagedir . $lang["nowriteaccesstofilestore"];} else {$result=$lang["status-ok"];}
?><tr><td colspan="2"><?php echo $lang["writeaccesstofilestore"] . $storagedir ?></td><td><b><?php echo $result?></b></td></tr><?php

# Check write access to homeanim (if transform plugin is installed)
if (in_array("transform",$plugins)){
$success=is_writable(dirname(__FILE__) . "/../".$homeanim_folder);
if ($success===false) {$result=$lang["status-fail"] . ": " . $homeanim_folder . $lang["nowriteaccesstohomeanim"];} else {$result=$lang["status-ok"];}
?><tr><td colspan="2"><?php echo $lang["writeaccesstohomeanim"] . $homeanim_folder ?></td><td><b><?php echo $result?></b></td></tr>
<?php } 

# Check filestore folder browseability
$filestoreurl = isset($storageurl) ? $storageurl : $baseurl . "/filestore";
if(function_exists('curl_init'))
    {
    $ch=curl_init();
    $checktimeout=5;
    curl_setopt($ch, CURLOPT_URL, $filestoreurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $checktimeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $checktimeout);
    $output=curl_exec($ch);
    curl_close($ch);
    if (strpos($output,"Index of")===false)
        {
        $result=$lang["status-ok"];
        }
    else
        {
        $result=$lang["status-fail"] . ": " . $lang["noblockedbrowsingoffilestore"];
        }
    }
else
    {
    $result=$lang["unknown"] . ": " . str_replace("%%EXTENSION%%","curl",$lang["php_extension_not_enabled"]);
    }
?><tr><td colspan="2"><?php echo $lang["blockedbrowsingoffilestore"] ?> (<a href="<?php echo $filestoreurl ?>" target="_blank"><?php echo $filestoreurl ?></a>)</td><td><b><?php echo $result?></b></td></tr><?php


# Check if we are running 32 bit PHP. If so, no large file support.
if (!php_is_64bit()){
	$result = $lang['large_file_warning_32_bit'];
} else {
	$result=$lang["status-ok"];
}
?><tr><td colspan='2'><?php echo $lang['large_file_support_64_bit']; ?></td><td><b><?php echo $result?></b></td></tr><?php

# Check ImageMagick/GraphicsMagick
display_utility_status("im-convert");

# Check FFmpeg
display_utility_status("ffmpeg");

# Check Ghostscript
display_utility_status("ghostscript");

# Check Exif extension
if (function_exists('exif_read_data')) 
	{
	$result=$lang["status-ok"];
	}
else
	{
	$version=$lang["status-notinstalled"];
	$result=$lang["status-fail"];
	}
?><tr><td colspan="2"><?php echo $lang["exif_extension"]?></td><td><b><?php echo $result?></b></td></tr><?php

# Check ExifTool
display_utility_status("exiftool");

# Check archiver
if (!$use_zip_extension){
if ($collection_download || isset($zipcommand)) # Only check if it is going to be used.
    {
    $archiver_fullpath = get_utility_path("archiver", $path);

    if ($path==null && !isset($zipcommand))
        {
        $result = $lang["status-notinstalled"];
        }
    elseif ($collection_download && $archiver_fullpath!=false)
        {
        $result = $lang["status-ok"];
        if (isset($zipcommand)) {$result.= "<br/>" . $lang["zipcommand_overridden"];}
        }
    elseif (isset($zipcommand))
        {
        $result = $lang["status-warning"] . ": " . $lang["zipcommand_deprecated"];
        }
    else
        {
        $result = $lang["status-fail"] . ": " . str_replace("?", $path, $lang["softwarenotfound"]);
        }
    ?><tr><td colspan="2"><?php echo $lang["archiver_utility"] ?></td><td><b><?php echo $result?></b></td></tr><?php
    }
}

# Check zip extension
if ($use_zip_extension){
display_extension_status("zip");
}

# Check PHP timezone identical to server (MySQL will use the server one) so we need to ensure they are the same
$php_tz = date_default_timezone_get();
$mysql_tz = sql_value("SELECT IF(@@session.time_zone = 'SYSTEM', @@system_time_zone, @@session.time_zone) AS `value`", '');
$tz_check_fail_msg = str_replace(array('%phptz%', '%mysqltz%'), array($php_tz, $mysql_tz), $lang['server_timezone_check_fail']);
$timezone_check = "{$lang['status-warning']}: {$tz_check_fail_msg}";
if($php_tz == $mysql_tz)
    {
    $timezone_check = $lang['status-ok'];
    }
?>
<tr>
    <td colspan="2"><?php echo $lang['server_timezone_check']; ?></td>
    <td><b><?php echo $timezone_check; ?></b></td>
</tr>
<?php

hook("addinstallationcheck");?>

<tr>
<td><?php echo $lang["lastscheduledtaskexection"] ?></td>
<td><?php $last_cron=sql_value("select datediff(now(),value) value from sysvars where name='last_cron'",$lang["status-never"]);echo $last_cron ?></td>
<td><?php if ($last_cron>2 || $last_cron==$lang["status-never"]) { ?><b><?php echo $lang["status-warning"] ?></b><br/><?php echo $lang["executecronphp"] ?><?php } else {?><b><?php echo $lang["status-ok"] ?></b><?php } ?></td>
</tr>

<tr>
<td><?php echo $lang["phpextensions"] ?></td>
<?php $extensions=get_loaded_extensions();sort($extensions);?>
<td><?php echo implode(" ",$extensions); ?></td>
<td></td>
</tr>

</table>
</div>

<?php
include "../include/footer.php";

function display_utility_status($utilityname)
    {
    global $lang;
    $utility = get_utility_version($utilityname);

    if ($utility["success"]==true)
        {
        $result = $lang["status-ok"];
        }
    else
        {
        $result = $utility["error"];
        }

    ?><tr><td <?php if ($utility["success"]==false) { ?>colspan="2"<?php } ?>><?php echo $utility["name"] ?></td>
    <?php if ($utility["success"]==true) { ?><td><?php echo $utility["version"] ?></td><?php } ?>
    <td><b><?php echo $result?></b></td></tr><?php
    }
   
function display_extension_status($extension)
    {
    global $lang;

    if (extension_loaded($extension))
        {
        $result = $lang["status-ok"];
        }
    else
        {
        $result = $lang["status-fail"];
        }

    ?><tr><td colspan="2">php-<?php echo $extension ?></td>
    <td><b><?php echo $result?></b></td></tr><?php
    }    

function get_utility_displayname($utilityname)
    {

    # Define the display name of a utility.
    switch (strtolower($utilityname))
        {
        case "im-convert":
           return "ImageMagick/GraphicsMagick";
           break;
        case "ghostscript":
            return "Ghostscript";
            break;
        case "ffmpeg":
            return "FFmpeg";
            break;
        case "exiftool":
            return "ExifTool";
            break;
        case "antiword":
            return "Antiword";
            break;
        case "pdftotext":
            return "pdftotext";
            break;
        case "blender":
            return "Blender";
            break;
        case "archiver":
            return "Archiver";
            break;
        default:
            return $utilityname;
        }
    }

function get_utility_version($utilityname)
    {
    global $lang;

    # Get utility path.
    $utility_fullpath = get_utility_path($utilityname, $path);

    # Get utility display name.
    $name = get_utility_displayname($utilityname);

    # Check path.
    if ($path==null)
        {
        # There was no complete path to check - the utility is not installed.
        $error_msg = $lang["status-notinstalled"];
        return array("name" => $name, "version" => "", "success" => false, "error" => $error_msg);
        }
    if ($utility_fullpath==false)
        {
        # There was a path but it was incorrect - the utility couldn't be found.
        $error_msg = $lang["status-fail"] . ":<br />" . str_replace("?", $path, $lang["softwarenotfound"]);
        return array("name" => $name, "version" => "", "success" => false, "error" => $error_msg);
        }

    # Look up the argument to use to get the version.
    switch (strtolower($utilityname))
        {
        case "exiftool":
            $version_argument = "-ver";
            break;
        default:
            $version_argument = "-version";
        }

    # Check execution and find out version.
    $version_command = $utility_fullpath . " " . $version_argument;
    $version = run_command($version_command);

    switch (strtolower($utilityname))
        {
        case "im-convert":
           if (strpos($version, "ImageMagick")!==false) {$name = "ImageMagick";}
           if (strpos($version, "GraphicsMagick")!==false) {$name = "GraphicsMagick";}
           if ($name=="ImageMagick" || $name=="GraphicsMagick") {$expected = true;}
           else {$expected = false;}
           break;
        case "ghostscript":
            if (strpos(strtolower($version), "ghostscript")===false) {$expected = false;}
            else {$expected = true;}
            break;
        case "ffmpeg":
            if (strpos(strtolower($version), "ffmpeg")===false && strpos(strtolower($version), "avconv")===false ) {$expected = false;}
            else {$expected = true;}
            break;
        case "exiftool":
            if(preg_match("/^([0-9]+)+\.([0-9]+)/", $version) === 1)
                {
                // E.g. 8.84
                // Note: if there is a warning like "10.11 [Warning: Library version is 10.10]" this should also be seen as expected.
                $expected = true;
                }
            else
                {
                $expected = false;
                }
            break;
        }

    if ($expected==false)
        {
        # There was a correct path but the version check failed - unexpected output when executing the command.
        $error_msg = $lang["status-fail"] . ":<br />" . str_replace(array("%command", "%output"), array($version_command, $version), $lang["execution_failed"]);
        return array("name" => $name, "version" => "", "success" => false, "error" => $error_msg);
        }
    else    
        {
        # There was a working path and the output was the expected - the version is returned.
        $s = explode("\n", $version);
        return array("name" => $name, "version" => $s[0], "success" => true, "error" => "");
        }
    }

function php_is_64bit() {
	$int = "9223372036854775807";
	$int = intval($int);
	if ($int == 9223372036854775807) {
  	/* 64bit */
  	return true;
	}
	elseif ($int == 2147483647) {
	  /* 32bit */
	  return false;
	}
	else {
	  /* error */
	  return "error";
	} 

}

?>
