<?php
#
# Healthcheck.php
#
#
# Performs some basic system checks. Useful for remote monitoring of ResourceSpace installations.
#

include "../../include/db.php";
include_once "../../include/general.php";
include_once "../../include/resource_functions.php";

# Check database connectivity.
$check=sql_value("select count(*) value from resource_type",0);
if ($check<=0) exit("FAIL - SQL query produced unexpected result");

# Check write access to filestore
if (!is_writable($storagedir)) {exit("FAIL - $storagedir is not writeable.");}
$hash=md5(time());
$file=$storagedir . "/write_text.txt";
file_put_contents($file,$hash);$check=file_get_contents($file);unlink($file);
if ($check!==$hash) {exit("FAIL - test write to disk returned a different string ('$hash' vs '$check')");}

// Check write access to sql_log
if($mysql_log_transactions)
    {
    $mysql_log_dir=dirname($mysql_log_location);
    if(!is_writeable($mysql_log_dir) || (file_exists($mysql_log_location) && !is_writeable($mysql_log_location)))
	{
	exit("FAIL - invalid \$mysql_log_location specified in config file: " . $mysql_log_location); 
	}
    }
    
// Check write access to debug_log 
if($debug_log)
    {
    if (!isset($debug_log_location)){$debug_log_location=get_debug_log_dir() . "/debug.txt";}
    $debug_log_dir=dirname($debug_log_location);
    if(!is_writeable($debug_log_dir) || (file_exists($debug_log_location) && !is_writeable($debug_log_location)))
        {
        exit("FAIL - invalid \$debug_log_location specified in config file: " . $debug_log_location); 
        }        
    }

// All is well.

// Formulate a version number. Start with the one set in version.php, which is already changed on each release branch, and also when building a new release ZIP.
$version=" " . $productversion;

// Work out the Subversion revision if possible.
$svncommand = "svn info "  . __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;
$svninfo=run_command($svncommand);
$matches = array();

// If a branch, add on the branch name.
if (preg_match('/\nURL: .+\/branches\/(.+)\\n/', $svninfo, $matches)!=0)
    {
    $version .= " BRANCH " . $matches[1];
    }
	
// Add on the revision if we can find it.
// If 'svnversion' is available, run this as it will produce a better output with 'M' signifying local modifications.
$svncommand = "svnversion "  . __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;
$svnversion=run_command($svncommand);
if ($svnversion!="")
    {
    # 'svnversion' worked - use this value and also flag local mods using a detectable string.
    $version.=" r" . str_replace("M","(mods)",$svnversion);    
    }
elseif (preg_match('/\nRevision: (\d+)/i', $svninfo, $matches)!=0)
    {
    // No 'svnversion' command, but we found the revision in the results from 'svn info'.
    $version .= " r" . $matches[1];
    }

    
# Send the message (note the O and K are separate, so that if served as plain text the remote check doesn't erroneously report all well)
echo("O" . "K" . $version);

// Check that the cron process executed within the last 5 days (allows for a window of downtime, for migration, etc.).
$last_cron=strtotime(sql_value("select value from sysvars where name='last_cron'",""));
$diff_days=(time()-$last_cron) / (60 * 60 * 24);
if ($diff_days>5) 
    {
    echo "WARNING - cron was executed " . round($diff_days,0) . " days ago.";
    }

# Check free disk space is sufficient.
$avail=disk_total_space($storagedir);
$free=disk_free_space($storagedir);
if (($free/$avail)<0.05)
    {
    echo "WARNING - less than 5% disk space free.";
    } 

// Warning if quota set and nearing quota limit
if (isset($disksize))
	{
	$avail=$disksize*(1000*1000*1000); # Get quota in bytes
	$used=get_total_disk_usage();      # Total usage in bytes
    $percent=ceil(($used/$avail)*100);
    echo " " . $percent . "% used";
	if ($percent>=95) {echo " WARNING nearly full";}
	}

// Add active user count (last 7 day)
echo ", " . get_recent_users(7) . " recent users";


