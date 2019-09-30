<?php

function HookVideo_spliceCollectionsCollectiontool()
	{
	# Link in collections bar (maximised)
	global $usercollection,$lang,$baseurl;
	?>
    <li><a href="<?php echo $baseurl ?>/plugins/video_splice/pages/splice.php?collection=<?php echo $usercollection ?>"><?php echo LINK_CARET . $lang["action-splice"]?></a></li>
	<?php
	}

function HookVideo_spliceCollectionsCollectiontoolmin()
	{
	# Link in collections bar (minimised)
	global $usercollection,$lang,$baseurl;
	?>
    <li><a href="<?php echo $baseurl ?>/plugins/video_splice/pages/splice.php?collection=<?php echo $usercollection ?>"><?php echo $lang["action-splice"]?></a></li>
	<?php
	}



?>
