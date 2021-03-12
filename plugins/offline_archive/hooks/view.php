<?php
function HookOffline_archiveViewResourceactions()
	{
	global $resource,$baseurl,$lang,$ref,$search,$offset,$order_by,$sort,$archive;	
	
	if ($resource["archive"]==2)
		{
		if(checkperm("i") && $resource["pending_restore"]!=1)
			{
			?>
			<li><i aria-hidden="true" class="fa fa-fw fa-archive"></i>&nbsp;<a href="<?php echo $baseurl ?>/plugins/offline_archive/pages/restore.php?resources=<?php echo $ref ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang['offline_archive_restore_resource'] ?></a></li>
			<?php			
			}
		else
			{
			?>
			<li><i aria-hidden="true" class="fa fa-fw fa-archive"></i>&nbsp;<a href="<?php echo $baseurl ?>/plugins/offline_archive/pages/restore_request.php?ref=<?php echo $ref ?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang['offline_archive_request_restore'] ?></a></li>
			<?php
			}
		}
	return false;
	}

function HookOffline_archiveViewRenderbeforeresourceview()
	{
	global $resource,$ref,$lang;
	if ($resource["archive"]==2)
		{
		$checkrestorepending=sql_value("select pending_restore as value from resource where ref='$ref'",0);
		if($checkrestorepending==1)
			{
			echo "<div class=\"PageInformal\">" . $lang['offline_archive_restore_pending'] . "</div>";
			}
		}	
	return false;
	}
	
