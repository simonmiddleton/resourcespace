<?php

function HookResourceconnectCollectionsThumblistextra()
	{
	global $usercollection, $lang, $baseurl;
	$thumbs=ps_query("select ref,collection,date_added,title,thumb,large_thumb,xl_thumb,url,source_ref from resourceconnect_collection_resources where collection=? order by date_added asc",array("i",$usercollection));

	foreach ($thumbs as $thumb)
		{
        $identifier = (empty($thumb['source_ref'])) ? $thumb['ref'] : $thumb['source_ref']; 
		?>
		<!--Resource Panel--> 
		<div class="CollectionPanelShell" data-identifier="<?php echo htmlspecialchars($identifier);?>">

		<table border="0" class="CollectionResourceAlign"><tr><td>
		<div class="overlay-link-container"><i class="overlay-link fas fa-link"></i></div>
		<a href="<?php echo $baseurl ?>/plugins/resourceconnect/pages/view.php?url=<?php echo urlencode($thumb["url"]) ?>&k=<?php echo urlencode(getval("k","")) ?>&col=<?php echo $usercollection ?>" onClick="return ModalLoad(this,true);"><div class="overlay-link-container"><i class="overlay-link fas fa-link"></i></div><img border=0 src="<?php echo $thumb["thumb"] ?>" class="CollectImageBorder" /></a></td> 
		</tr></table>

		<div class="CollectionPanelInfo"><a href="<?php echo $baseurl ?>/plugins/resourceconnect/pages/view.php?url=<?php echo urlencode($thumb["url"]) ?>&k=<?php echo urlencode(getval("k","")) ?>&col=<?php echo $usercollection ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo tidy_trim(i18n_get_translated($thumb["title"]),15) ?></a>&nbsp;</div> 

		<div class="CollectionPanelTools"> 
		<a class="removeFromCollection fa fa-minus-circle" href="<?php echo generateURL($baseurl . '/pages/collections.php', ['resourceconnect_remove_ref' => $thumb['ref'],'resourceconnect_remove' => $thumb["source_ref"], 'resourceconnect_remove_col' => $usercollection, 'nc' => time()]); ?>" onClick="return CollectionDivLoad(this,false);"> </a></div>
        
		</div>
		<?php
		}
	}

function HookResourceconnectCollectionsProcessusercommand()
	{
	if (getval("resourceconnect_remove","")!="" && getval('resourceconnect_remove_col', '') != '')
		{
		ps_query("DELETE FROM resourceconnect_collection_resources WHERE source_ref = ? AND collection = ?", ['i',getval("resourceconnect_remove",""), 'i',getval('resourceconnect_remove_col', '')]);
		}
    elseif(getval('resourceconnect_remove_ref', '') !== '')
        {
        ps_query('DELETE FROM resourceconnect_collection_resources WHERE ref = ?', ['i', getval('resourceconnect_remove_ref','')]);
        }
	}
