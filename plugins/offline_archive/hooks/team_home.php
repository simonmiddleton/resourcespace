<?php

function HookOffline_archiveTeam_homeCustomteamfunction()
	{
	global $baseurl, $lang;
	
    if (checkperm("i"))
		{
		
		?><li><i aria-hidden="true" class="fa fa-fw fa-archive"></i>&nbsp;<a href="<?php echo $baseurl ?>/plugins/offline_archive/pages/administer_archive.php"><?php echo $lang["offline_archive_administer_archive"]?></a></li>
		<?php
		}
		?>
	<?php
	}




