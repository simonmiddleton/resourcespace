<?php

function HookNewsTeam_homeCustomteamfunction()
	{
	global $baseurl, $lang;
	
    if (checkperm("o"))
		{
		
		?><li><a href="<?php echo $baseurl ?>/plugins/news/pages/news_edit.php"><i aria-hidden="true" class="fa fa-fw fa-newspaper-o"></i><br /><?php echo $lang["news_manage"]?></a></li>
		<?php
		}
		?>
	<?php
	}




