<?php

function HookOffline_archiveTeam_homeCustomteamfunction()
	{
	global $baseurl, $lang;
	
    if (checkperm("i"))
		{		
		?><li><a href="<?php echo $baseurl ?>/plugins/offline_archive/pages/administer_archive.php" onclick="return CentralSpaceLoad(this,true)";><i aria-hidden="true" class="fa fa-fw fa-archive"></i><?php echo escape($lang["offline_archive_administer_archive"]) ?></a></li>
		<?php
		}
		?>
	<?php
	}




