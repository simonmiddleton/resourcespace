<?php
global $baseurl,$baseurl_short,$enable_related_resources, $edit_access, $title_field;
$view_title_field = $title_field;

if ($enable_related_resources)
	{
    $use_watermark = check_use_watermark();
	$relatedresources = do_search('!related' . $ref);
	$related_restypes = array();

	for ($n = 0; $n < count($relatedresources); $n++)
		{
		$related_restypes[] = $relatedresources[$n]['resource_type'];
		}

	$related_restypes = array_unique($related_restypes);
	$relatedtypes_shown = array();
	$related_resources_shown = 0;

	global $related_type_show_with_data, $related_type_upload_link, $userref;
	if(isset($related_type_show_with_data))
		{		
		foreach($related_type_show_with_data as $rtype)
			{
			# Is this a resource type that needs to be displayed? Don't show resources of the same type as this is not the standard configuration
			if ($resource['resource_type'] == $rtype || !in_array($rtype, $related_type_show_with_data) || (!in_array($rtype, $related_restypes) && !$related_type_upload_link))
				{
				continue;
				}

			// Show only related resource types that match this tab:
			$resource_type_tab_ref = ps_value('SELECT tab AS value FROM resource_type WHERE ref = ?', ['i', $rtype], '', 'schema');
			if($tab_ref !== $resource_type_tab_ref)
				{
				continue;
				}
			
			
			$restypename=ps_value("select name as value from resource_type where ref = ?",array("i",$rtype),"", "schema");
			$restypename = lang_or_i18n_get_translated($restypename, "resourcetype-", "-2");
				
			if(isset($related_type_thumbnail_view) && in_array($rtype,$related_type_thumbnail_view))
				{
				foreach($relatedresources as $relatedresource)
					{
					if($relatedresource['resource_type'] == $rtype)
						{
						?>
						<div class="ResourcePanelShellSmall" id="RelatedResource_<?php echo $relatedresource["ref"]?>">
							<a class="ImageWrapperSmall" href="<?php echo $baseurl_short ?>pages/view.php?ref=<?php echo $relatedresource["ref"]?>" title="<?php echo htmlspecialchars(i18n_get_translated(($relatedresource["field".$view_title_field]))) ?>" onClick="return ModalLoad(this,true);">
							<?php if ($relatedresource["has_image"]==1)
								{
								$thm_url = get_resource_path($relatedresource["ref"],false,"col",false,$relatedresource["preview_extension"],-1,1,$use_watermark,$relatedresource["file_modified"]);
								}
							else
								{
								$thm_url = $baseurl_short . "gfx/" . get_nopreview_icon($relatedresource["resource_type"],$relatedresource["file_extension"],false);
								$relatedresource["thumb_height"] = 75;
								$relatedresource["thumb_width"] = 75;
								}
							render_resource_image($relatedresource, $thm_url, "collection");
							?>
							</a>
							
						<?php
						
						if($edit_access)
							{
							echo "<div class=\"ResourcePanelInfo\" ><a href=\"#\" onClick=\"if(confirm('" . $lang["related_resource_confirm_delete"] . "')){relateresources(" . $ref . "," . $relatedresource["ref"] . ",'remove');jQuery('#RelatedResource_" . $relatedresource["ref"] . "').remove();}return false;\" >" . LINK_CARET . $lang["action-remove"] . "</a></div>";
							}?>
						</div>
						<?php
						}
					}
				if($related_type_upload_link && $edit_access)
					{
					echo "<div class=\"clearerleft\" ></div>";
					echo "<a class=\"ResourcePanelSmallIcons\" href=\"" . $baseurl_short . "pages/edit.php?ref=-" . $userref . "&uploader=batch&resource_type=" . $rtype ."&submitted=true&relateto=" . $ref . "&collection_add=&redirecturl=" . urlencode($baseurl . "/?r=" . $ref) . "\">" . LINK_CARET . $lang["upload"] . "</a>";
					}
				}
			else
				{
				// Standard table view
				?>
				<div class="clearerleft"></div>
				<div class="item" id="RelatedResourceData">
				<?php
				if(in_array($rtype, $related_restypes) || ($related_type_upload_link && $edit_access))
					{
					?>
					<div class="Listview ListviewTight" >
						<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
						<tbody>
							<tr class="ListviewTitleStyle">
								<td><h3><?php echo $restypename; ?></h3></td>		
								<td><div class="ListTools"></div></td>                                    
							</tr>
							<?php
								foreach($relatedresources as $relatedresource)
									{
                                    $related_resource_ref = (int) $relatedresource['ref'];

									if($relatedresource['resource_type'] == $rtype)
										{
										$relatedtitle = (string) $relatedresource["field{$view_title_field}"];
										echo "<tr id=\"relatedresource{$related_resource_ref}\" class=\"RelatedResourceRow\">";
										echo "<td class=\"link\"><a href=\"{$baseurl_short}pages/view.php?ref={$related_resource_ref}\"  onClick=\"return ModalLoad(this,true);\" >" . htmlspecialchars($relatedtitle) . "</a></td>";
										echo "<td>";
										if($edit_access)
											{
											echo "<div class=\"ListTools\" ><a href=\"#\" onClick=\"if(confirm('" . escape_quoted_data($lang["related_resource_confirm_delete"]) . "')){relateresources(" . (int) $ref . ", $related_resource_ref,'remove');}return false;\" >" . LINK_CARET . htmlspecialchars($lang["action-remove"]) . "</a></div>";
											}
										echo "</td>";	
										echo "</tr>";	
										$related_resources_shown++;
										}
									}
	
								if($related_type_upload_link && $edit_access)
									{
									echo "<tr><td></td><td><div class=\"ListTools\"><a href=\"" . $baseurl_short . "pages/edit.php?ref=-" . $userref . "&uploader=batch&resource_type=" . $rtype ."&submitted=true&relateto=" . $ref . "&collection_add=&redirecturl=" . urlencode($baseurl . "/?r=" . $ref) . "\">" . LINK_CARET . $lang["upload"] . "</a></div></td>";
									}
							?>
						</tbody>
						</table>
					</div>
					<?php
					} ?>
				</div><!-- end of RelatedResourceData -->
				<?php
				}
			
			// We have displayed these, don't show them again later
			$relatedtypes_shown[]=$rtype;
			}
		}
	}
?>
