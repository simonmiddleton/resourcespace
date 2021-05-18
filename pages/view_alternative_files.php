<?php
# Alternative files listing
$alt_access=hook("altfilesaccess");

if ($access==0 || $alt_files_visible_when_restricted) $alt_access=true; # open access (not restricted)

if ($alt_access) 
	{
	global $use_larger_layout;
	$alt_order_by="";$alt_sort="";
	if ($alt_types_organize){$alt_order_by="alt_type";$alt_sort="asc";}
	if(!isset($altfiles))
		{$altfiles=get_alternative_files($ref,$alt_order_by,$alt_sort);}
	hook("processaltfiles");
	$last_alt_type="-";
	for ($n=0;$n<count($altfiles);$n++)
		{
		$alt_type=$altfiles[$n]['alt_type'];
		if ($alt_types_organize){
			if ($alt_type!=$last_alt_type){
				$alt_type_header=$alt_type;
				if ($alt_type_header==""){$alt_type_header=$lang["alternativefiles"];}
				hook("viewbeforealtheader");
				?>
				<tr class="DownloadDBlend">
				<td colspan="3" id="altfileheader"><h2><?php echo $alt_type_header?></h2></td>
				</tr>
				<?php
			}
			$last_alt_type=$alt_type;
		}	
		else if ($n==0)
			{
			hook("viewbeforealtheader");
			?>
			<tr>
			<td colspan="3" id="altfileheader"><?php echo $lang["alternativefiles"]?></td>
			</tr>
			<?php
			}

        $alt_thm = '';
        $alt_pre = '';
        if($alternative_file_previews)
            {
            $use_watermark = check_use_watermark();

            if(file_exists(get_resource_path($ref, true, 'col', false, 'jpg', true, 1, $use_watermark, '', $altfiles[$n]['ref'])))
                {
                # Get web path for thumb (pass creation date to help cache refresh)
                $alt_thm = get_resource_path($ref, false, 'col', false, 'jpg', true, 1, $use_watermark, $altfiles[$n]['creation_date'], $altfiles[$n]['ref']);
                }

            if(file_exists(get_resource_path($ref, true, 'pre', false, 'jpg', true, 1, $use_watermark, '', $altfiles[$n]['ref'])))
                {
                # Get web path for preview (pass creation date to help cache refresh)
                $alt_pre = get_resource_path($ref, false, 'pre', false, 'jpg', true, 1, $use_watermark, $altfiles[$n]['creation_date'], $altfiles[$n]['ref']);
                }
            }
            ?>
		<tr class="DownloadDBlend" <?php if ($alt_pre!="" && $alternative_file_previews_mouseover) { ?>onMouseOver="orig_preview=jQuery('#previewimage').attr('src');orig_height=jQuery('#previewimage').height();jQuery('#previewimage').attr('src','<?php echo $alt_pre ?>');jQuery('#previewimage').height(orig_height);" onMouseOut="jQuery('#previewimage').attr('src',orig_preview);"<?php } ?>>
		<td class="DownloadFileName AlternativeFile"<?php echo $use_larger_layout ? ' colspan="2"' : ''; ?>>
		<?php if(!hook("renderaltthumb")): ?>
		<?php if ($alt_thm!="") { ?><a href="<?php echo $baseurl_short?>pages/preview.php?ref=<?php echo urlencode($ref)?>&alternative=<?php echo $altfiles[$n]["ref"]?>&k=<?php echo urlencode($k)?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>&<?php echo hook("previewextraurl") ?>"><img src="<?php echo $alt_thm?>" class="AltThumb"></a><?php } ?>
		<?php endif; ?>
		<h2 class="breakall"><?php echo htmlspecialchars($altfiles[$n]["name"])?></h2>
		<p><?php echo htmlspecialchars($altfiles[$n]["description"])?></p>
		</td>
        <?php hook('view_altfiles_table', '', array($altfiles[$n])); ?>
		<td class="DownloadFileSize"><?php echo formatfilesize($altfiles[$n]["file_size"])?></td>
		
		<?php if ($userrequestmode==2 || $userrequestmode==3) { ?><td></td><?php } # Blank spacer column if displaying a price above (basket mode).
		?>
		
		<?php if ($access==0 && resource_download_allowed($ref,"",$resource["resource_type"],$altfiles[$n]["ref"])){?>
		<td <?php hook("modifydownloadbutton") ?> class="DownloadButton">
		<?php 		
		if ($terms_download || $save_as)
			{
			if(!hook("downloadbuttonreplace"))
				{
				?><a <?php if (!hook("downloadlink","",array("ref=" . $ref . "&alternative=" . $altfiles[$n]["ref"] . "&k=" . $k . "&ext=" . $altfiles[$n]["file_extension"]))) { ?>href="<?php echo generateURL($baseurl . "/pages/terms.php",$urlparams,array("url"=> generateURL($baseurl_short . "pages/download_progress.php",$urlparams,array("alternative"=>$altfiles[$n]["ref"],"ext"=> $altfiles[$n]["file_extension"])))); ?>"<?php } ?> onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["action-download"] ?></a><?php 
				}
			}

			elseif ($download_usage)
			// download usage form displayed - load into main window
				{ 
				?>
					<a <?php 
					if (!hook("downloadlink","",array("ref=" . $ref . "&alternative=" . $altfiles[$n]["ref"] . "&k=" . $k . "&ext=" . $altfiles[$n]["file_extension"]))) 
					    {
					    ?>href="<?php echo generateURL($baseurl . '/pages/download_usage.php',$urlparams,array('ext'=>$altfiles[$n]['file_extension'],'alternative'=>$altfiles[$n]['ref'])) . '"';
					    }	
					?>><?php echo $lang["action-download"]?></a><?php
				}	
		else 
		    { 
			?> <a
		    <?php 
			if (!hook("downloadlink","",array("ref=" . $ref . "&alternative=" . $altfiles[$n]["ref"] . "&k=" . $k . "&ext=" . $altfiles[$n]["file_extension"]))) 
			    {
			    echo 'href="#" onclick="directDownload(\'' . generateURL($baseurl . '/pages/download_progress.php',$urlparams,array('ext'=>$altfiles[$n]['file_extension'],'alternative'=>$altfiles[$n]['ref'])) ?>')"<?php
			    }	
			    ?>><?php echo $lang["action-download"]?></a><?php
            } ?></td></td>
		<?php } elseif (checkperm("q")) { ?>
		<td class="DownloadButton"><?php
			if ($request_adds_to_collection && ($k=="" || $internal_share_access) && !checkperm('b')) // We can't add to a collection if we are accessing an external share, unless we are a logged in user
				{
				echo add_to_collection_link($ref,$search,"alert('" . addslashes($lang["requestaddedtocollection"]) . "');");
				}
			else
				{
				?><a href="<?php echo generateURL($baseurl . "/pages/resource_request.php",$urlparams) ?>" onClick="return CentralSpaceLoad(this,true);"><?php
				}
			echo $lang["action-request"]?></a></td>
		<?php } 
		else
		    {
		    ?><td class="DownloadButton DownloadDisabled"><?php echo $lang["access1"]?></td><?php
		    } ?>
		</tr>
		<?php	
		}
        hook("morealtdownload");
	}
# --- end of alternative files listing