<?php

function HookResourceconnectCollectionsThumblistextra()
	{
	global $usercollection, $lang, $baseurl;
	$thumbs=sql_query("select * from resourceconnect_collection_resources where collection='$usercollection' order by date_added asc");

	foreach ($thumbs as $thumb)
		{	
		?>
		<!--Resource Panel--> 
		<div class="CollectionPanelShell"> 

		<table border="0" class="CollectionResourceAlign"><tr><td>
		<div class="overlay-link-container"><i class="overlay-link fas fa-link"></i></div>
		<a href="<?php echo $baseurl ?>/plugins/resourceconnect/pages/view.php?url=<?php echo urlencode($thumb["url"]) ?>&k=<?php echo getval("k","") ?>&col=<?php echo $usercollection ?>" onClick="return ModalLoad(this,true);"><div class="overlay-link-container"><i class="overlay-link fas fa-link"></i></div><img border=0 src="<?php echo $thumb["thumb"] ?>" class="CollectImageBorder" /></a></td> 
		</tr></table>

		<div class="CollectionPanelInfo"><a href="<?php echo $baseurl ?>/plugins/resourceconnect/pages/view.php?url=<?php echo urlencode($thumb["url"]) ?>&k=<?php echo getval("k","") ?>&col=<?php echo $usercollection ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo tidy_trim(i18n_get_translated($thumb["title"]),15) ?></a>&nbsp;</div> 

		<div class="CollectionPanelTools"> 
		<a class="removeFromCollection fa fa-minus-circle" href="<?php echo $baseurl ?>/pages/collections.php?resourceconnect_remove=<?php echo $thumb["ref"] ?>&nc=<?php echo time() ?>" onClick="return CollectionDivLoad(this,false);"> </a></div>
        
		</div>
		<?php
		}
	}

function HookResourceconnectCollectionsProcessusercommand()
	{
	if (getval("resourceconnect_remove","")!="")
		{
		sql_query("delete from resourceconnect_collection_resources where ref='" . getvalescaped("resourceconnect_remove","") . "'");
		}

	}
