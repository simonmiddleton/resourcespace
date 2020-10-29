<?php

include_once __DIR__ . '/../include/search_notifications_functions.php';

function HookRse_search_notificationsUser_homeUser_home_additional_links()
	{
	global $lang,$watched_searches_url;
	?>
<li><a href="<?php echo $watched_searches_url; ?>" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-eye"></i><br /><?php echo $lang["search_notifications_watched_searches"];?></a>
</li><?php
	}

