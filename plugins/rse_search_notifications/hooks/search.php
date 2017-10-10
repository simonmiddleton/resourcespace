<?php

include_once __DIR__ . '/../include/search_notifications_functions.php';

function HookRse_search_notificationsSearchafterResulthints()
	{
	global $lang, $search, $restypes, $archive, $watched_searches_url,$k;
	if($k=="")
		{
		?><a href="<?php echo $watched_searches_url; ?>?callback=add&search=<?php echo $search; ?>&restypes=<?php echo $restypes; ?>&archive=<?php echo $archive; ?>" onClick="return CentralSpaceLoad(this,true);"><?php
			echo $lang['search_notifications_notify_me'];
		?></a>
		<?php
		}
	}
