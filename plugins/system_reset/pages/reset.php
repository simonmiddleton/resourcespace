<?php
include "../../../include/db.php";

include "../../../include/authenticate.php";

# Check access
if (!checkperm("a")) {exit("Access denied");} # Should never arrive at this page without admin access

function rrmdir($dir)
    { 
    global $homeanim_folder,$storagedir;
    $slideshow_dir = substr($homeanim_folder,strrpos($homeanim_folder,"/")+1);
    // Recursively remove a folder. Slideshow and system folders are retained.
    if (is_dir($dir))
        { 
        $objects = scandir($dir);
        foreach ($objects as $object)
            { 
            if ($object != "." && $object != "..")
                { 
                if (is_dir($dir. "/" .$object) && !is_link($dir."/".$object))
                    {
                    rrmdir($dir. "/" .$object);
                    }
                else
                    {
                    unlink($dir. "/" .$object); 
                    }
                } 
            }
        if ($dir != $storagedir . "/system" && $dir != $storagedir . "/system/".$slideshow_dir)
            {
            debug("system_reset: remove directory " . $dir);
            }
        }
    }

if (getval("submitted","")!="")
	{
    // Allow longer to execute
    set_time_limit(60*60*2);

    // Delete all files.
    $folders=scandir($storagedir);
    foreach ($folders as $folder)
        {
        if ($folder!="." && $folder!="..")
            {
            rrmdir($storagedir . "/" . $folder);
            }
        }

    // It's unlikely we have permission to drop the whole DB so we drop the tables one by one. Omit user so a default user can be created.
    $tables=sql_query("show tables");
    foreach ($tables as $table)
        {
        $table=(array_values($table)[0]); # Get table name
        if ($table!="user")
            {
            sql_query("drop table " . $table,false,-1,false);
            }
        }
    
    // Create a default user 
    sql_query("delete from user",false,-1,false);
    sql_query("insert into user (username,password,usergroup) values ('admin','admin',3);",false,-1,false);

    // Back to login screen
    sleep (5); // Wait for any background DB creation to finish.
    redirect("/login.php");
	}

		
include "../../../include/header.php";
?>
<div class="BasicsBox">

<h1><?php echo $lang["system_reset"] ?></h1>

<p><?php echo $lang["system_reset_warning"] ?></p>

<form method="post" action="<?php echo $baseurl_short?>plugins/system_reset/pages/reset.php" onSubmit="return confirm('<?php echo $lang["system_reset_confirm"] ?>');">
<?php generateFormToken("system_reset"); ?>
<input type=hidden name="submitted" value="true">

<div class="QuestionSubmit">
<label for="buttons"><?php echo $lang["proceed"] ?></label>			
<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["system_reset_delete_all"]?>&nbsp;&nbsp;" />
</div>
</form>
</div>

<?php		
include "../../../include/footer.php";
?>
