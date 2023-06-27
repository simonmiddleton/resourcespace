<?php
include "../include/db.php";
include "../include/authenticate.php"; if (!checkperm("a")) {exit("Access denied.");}
include "../include/header.php";
?>

<div class="BasicsBox"> 
    <h1><?php echo $lang["installationcheck"];render_help_link("systemadmin/install_overview");?></h1>
    <?php
    renderBreadcrumbs([
        ['title' => $lang["systemsetup"], 'href'  => $baseurl_short . "pages/admin/admin_home.php", 'menu' => true],
        ['title' => $lang["installationcheck"]]
    ]);
    ?>
    <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/check.php">
        <?php echo '<i aria-hidden="true" class="fa fa-sync-alt"></i>&nbsp;' . $lang["repeatinstallationcheck"]?>
    </a>
    <br/><br/>
    <table class="InfoTable">
<?php


# Check ResourceSpace Build
$build = '';
if (substr($productversion,0,3) == 'SVN')
    {
    $p_version = 'Trunk (SVN)';
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
$p_version = substr($productversion,0,3) == 'SVN' ? 'SVN ' . $build : $productversion;

?><tr><td nowrap="true"><?php echo str_replace("?", "ResourceSpace", $lang["softwareversion"]); ?></td><td><?php echo $p_version?></td><td><br /></td></tr><?php

# Check PHP version
$phpversion=phpversion();
$phpinifile=php_ini_loaded_file();
if ($phpversion<'4.4') {$result=$lang["status-fail"] . ": " . str_replace("?", "4.4", $lang["shouldbeversion"]);} else {$result=$lang["status-ok"];}
?><tr><td><?php echo str_replace("?", "PHP", $lang["softwareversion"]); ?></td><td><?php echo $phpversion .'&ensp;&ensp;' . str_replace("%file", $phpinifile, $lang["config_file"]);?></td><td><b><?php echo $result?></b></td></tr><?php

# Check MySQL version
$mysqlversion_num = mysqli_get_server_version($db["read_write"]);
$mysqlversion     = mysqli_get_server_info($db["read_write"]);
if($mysqlversion_num < 50000)
    {
    $result = $lang["status-fail"] . ": " . str_replace("?", "5", $lang["shouldbeversion"]);
    }
else
    {
    $result = $lang["status-ok"];
    }
$encoding = mysqli_character_set_name($db["read_write"]);
$encoding_str = str_replace("%encoding", $encoding, $lang["client-encoding"]);
$db_encoding = ps_value("
    SELECT default_character_set_name AS `value`
      FROM information_schema.SCHEMATA
     WHERE `schema_name` = ?;", array("s",$mysql_db),$lang["unknown"]);
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

# Check flag set if code needs signing
if (get_sysvar("code_sign_required")=="YES") {$result=$lang["status-fail"];$result2=$lang["code_sign_required_warning"];} else {$result=$lang["status-ok"];$result2="";}
?><tr><td><?php echo $lang["code_sign_required"]; ?></td><td><?php echo $result2 ?></td><td><b><?php echo $result?></b></td></tr><?php

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
$cfb = check_filestore_browseability();
?>
<tr>
    <td colspan="2"><?php echo $lang["blockedbrowsingoffilestore"]; ?> (<a href="<?php echo $cfb['filestore_url']; ?>" target="_blank"><?php echo htmlspecialchars($cfb['filestore_url']); ?></a>)</td>
    <td>
        <b><?php echo htmlspecialchars($cfb['index_disabled'] ? $cfb['status'] : "{$cfb['status']}: {$cfb['info']}"); ?></b>
    </td>
</tr><?php

# Check sql logging configured correctly
if($mysql_log_transactions)
    {
    echo "<tr><td colspan='2'>" . $lang["writeaccess_sql_log"] . " (" . $mysql_log_location . ")</td><td><b>" . (is_writable($mysql_log_location) ? $lang["status-ok"] : $lang["status-fail"]) . "</b></td></tr>";
    }
# Check debug logging configured correctly
if($debug_log)
    {
    echo "<tr><td colspan='2'>" . $lang["writeaccess_debug_log"] . " (" . $debug_log_location . ")</td><td><b>" . (is_writable($debug_log_location) ? $lang["status-ok"] : $lang["status-fail"]) . "</b></td></tr>";
    }


# Check if we are running 32 bit PHP. If so, no large file support.
if (!php_is_64bit()){
	$result = $lang['large_file_warning_32_bit'];
} else {
	$result=$lang["status-ok"];
}
?><tr><td colspan='2'><?php echo $lang['large_file_support_64_bit']; ?></td><td><b><?php echo $result?></b></td></tr><?php

// Check system utilities
foreach(RS_SYSTEM_UTILITIES as $sysu_name => $sysu)
    {
    // Skip utilities which are a sub program (e.g ImageMagick has convert, identify, composite etc., checking for convert 
    // is enough) -or- are not required and configured
    if(!$sysu['show_on_check_page'] || (!$sysu['required'] && !isset($GLOBALS[$sysu['path_var_name']])))
        {
        continue;
        }

    display_utility_status($sysu_name);
    }

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

# Check PHP timezone identical to server (MySQL will use the server one) so we need to ensure they are the same
$php_tz = date_default_timezone_get();
$mysql_tz = ps_value("SELECT IF(@@session.time_zone = 'SYSTEM', @@system_time_zone, @@session.time_zone) AS `value`", array(), '');
$tz_check_fail_msg = str_replace(array('%phptz%', '%mysqltz%'), array($php_tz, $mysql_tz), $lang['server_timezone_check_fail']);
$timezone_check = "{$lang['status-warning']}: {$tz_check_fail_msg}";
if(strtoupper($php_tz) == strtoupper($mysql_tz))
    {
    $timezone_check = $lang['status-ok'];
    }
?>
<tr>
    <td colspan="2"><?php echo $lang['server_timezone_check']; ?></td>
    <td><b><?php echo $timezone_check; ?></b></td>
</tr>
<tr>
<td><?php echo $lang["lastscheduledtaskexection"] ?></td>
<td><?php $last_cron=ps_value("select datediff(now(),value) value from sysvars where name='last_cron'",array(),$lang["status-never"]);echo $last_cron ?></td>
<td><?php if ($last_cron>2 || $last_cron==$lang["status-never"]) { ?><b><?php echo $lang["status-warning"] ?></b><br/><?php echo $lang["executecronphp"] ?><?php } else {?><b><?php echo $lang["status-ok"] ?></b><?php } ?></td>
</tr>

<?php
// Check required PHP extensions 
$extensions_required = SYSTEM_REQUIRED_PHP_MODULES;

ksort($extensions_required, SORT_STRING);
foreach($extensions_required as $module=> $required_fn)
    {?>
    <tr>
        <td colspan="2">php-<?php echo $module ?></td>
        <td><b><?php 
        if (function_exists($required_fn)){echo $lang['status-ok'];}
        else {echo ($lang['server_' . $module . '_check_fail']??$lang['status-fail']);}?></b></td>
    </tr>
    <?php
    }

hook("addinstallationcheck");

?>
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


function get_utility_version(string $utilityname)
    {
    global $lang;

    $utilityname = strtolower(trim($utilityname));

    // Is this a known utility? If not, mark it as such. 
    if(!isset(RS_SYSTEM_UTILITIES[$utilityname]))
        {
        return ['name' => $utilityname, 'version' => '', 'success' => false, 'error' => $lang['unknown']];
        }

    $utility = RS_SYSTEM_UTILITIES[$utilityname];
    $utility_fullpath = get_utility_path($utilityname, $path);
    $name = $utility['display_name'] ?? $utilityname;

    # Check path.
    if ($path==null)
        {
        # There was no complete path to check - the utility is not installed.
        $error_msg = $lang["status-notinstalled"];
        return array("name" => $name, "version" => "", "success" => false, "error" => $error_msg);
        }
    if ($utility_fullpath === false)
        {
        # There was a path but it was incorrect - the utility couldn't be found.
        $error_msg = $lang["status-fail"] . ":<br />" . str_replace("?", $path, $lang["softwarenotfound"]);
        return array("name" => $name, "version" => "", "success" => false, "error" => $error_msg);
        }

    # Look up the argument to use to get the version.
    $version_argument = $utility['version_check']['argument'] ?? '' ?: '-version';

    # Check execution and find out version.
    $version_command = $utility_fullpath . " " . $version_argument;
    $utilities_with_version_on_STDERR = ['python', 'antiword', 'pdftotext'];
    $version = run_command($version_command, in_array($utilityname, $utilities_with_version_on_STDERR));
    $version_check = call_user_func_array(
        $utility['version_check']['callback']['fct_name'],
        array_merge([$version, $utility], $utility['version_check']['callback']['args'])
    );
    $name = $version_check['utility']['display_name'] ?? $name;
    $expected = $version_check['found'];

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
        $version_line = $utilityname === 'antiword' ? $s[3] : $s[0];
        return array("name" => $name, "version" => $version_line, "success" => true, "error" => "");
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