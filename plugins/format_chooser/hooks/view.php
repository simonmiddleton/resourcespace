<?php

function show_table_headers($showprice)
	{
	global $lang;
	if(!hook("replacedownloadspacetableheaders")){
	?><tr><td><?php echo $lang["fileinformation"]?></td>
	<td><?php echo $lang["filetype"]?></td>
	<?php if ($showprice) { ?><td><?php echo $lang["price"] ?></td><?php } ?>
	<td class="textcenter"><?php echo $lang["options"]?></td>
	</tr>
	<?php
	} # end hook("replacedownloadspacetableheaders")
	}

function HookFormat_chooserViewReplacedownloadoptions()
    {
    global $resource, $ref, $counter, $headline, $lang, $download_multisize, $showprice, $save_as, 
           $hide_restricted_download_sizes, $format_chooser_output_formats, $baseurl_short, $search, $offset, $k, 
           $order_by, $sort, $archive, $baseurl, $urlparams, $terms_download,$download_usage;

	// Disable for e-commerce
	if (is_ecommerce_user()) { return false; }

    $inputFormat = $resource['file_extension'];
    $origpath = get_resource_path($ref,true,'',false,$resource['file_extension']);

    if ($resource["has_image"] != 1
        || !$download_multisize
        || $save_as
        || !supportsInputFormat($inputFormat)
        || !file_exists($origpath)
        )
        return false;

    $defaultFormat = getDefaultOutputFormat($inputFormat);
    $tableHeadersDrawn = false;
    $block_original_size=false;

    ?><table cellpadding="0" cellspacing="0" id="ResourceDownloadOptions"><?php
    hook("formatchooserbeforedownloads");
    $sizes = get_image_sizes($ref, false, $resource['file_extension'], false);
    $downloadCount = 0;
    $originalSize = array();
    # Show original file download
	for ($n = 0; $n < count($sizes); $n++)
        {
        $downloadthissize = resource_download_allowed($ref, $sizes[$n]["id"], $resource["resource_type"]);
        $counter++;
        if ($sizes[$n]['id'] != '')
            {
            if ($downloadthissize
                ||
                (!$hide_restricted_download_sizes && !$downloadthissize && checkperm("q"))
                )
                {
                $downloadCount++;
                }
            continue;
            }

		# Is this the original file? Set that the user can download the original file
		# so the request box does not appear.
		$fulldownload = false;
        if ($sizes[$n]["id"] == "")
            {
            $fulldownload = true;
            $fullaccess = $downloadthissize;
            }

		$originalSize = $sizes[$n];

		$headline = $lang['collection_download_original'];      
        if ($hide_restricted_download_sizes && !$downloadthissize && !checkperm("q"))
            {
            continue;
            }

		if (!$tableHeadersDrawn)
			{
			show_table_headers($showprice);
			$tableHeadersDrawn = true;
			}

		?><tr class="DownloadDBlend" id="DownloadBox<?php echo $n?>">
		<td class="DownloadFileName"><h2><?php echo $headline?></h2><p><?php
		echo $sizes[$n]["filesize"];
		if (is_numeric($sizes[$n]["width"]))
			echo preg_replace('/^<p>/', ', ', get_size_info($sizes[$n]), 1);

		?></p><td class="DownloadFileFormat"><?php echo str_replace_formatted_placeholder("%extension", $resource["file_extension"], $lang["field-fileextension"]) ?></td><?php

		if ($showprice)
			{
			?><td><?php echo get_display_price($ref, $sizes[$n]) ?></td><?php
			}
		add_download_column($ref, $sizes[$n], $downloadthissize);
		}

    if(!isset($originalSize) || $originalSize ===array())
        {
        $originalSize = array();
        $fileinfo = get_original_imagesize($ref,$origpath,$resource['file_extension']);
        $originalSize['file_size'] = $fileinfo[0];
        $originalSize['width']     = $fileinfo[1];
        $originalSize['height']    = $fileinfo[2];
        $originalSize['id']='';
        $block_original_size=true;
        }

	# Add drop down for all other sizes
	$closestSize = 0;
	if ($downloadCount > 0)
		{
		if (!$tableHeadersDrawn)
			show_table_headers($showprice);

		?><tr class="DownloadDBlend">
		<td class="DownloadFileSizePicker"><select id="size"><?php
        $sizes = get_all_image_sizes();
        $restrictedsizes = array();

		# Filter out all sizes that are larger than our image size, but not the closest one
		for ($n = 0; $n < count($sizes); $n++)
			{
			if (intval($sizes[$n]['width']) >= intval($originalSize['width'])
					&& intval($sizes[$n]['height']) >= intval($originalSize['height'])
					&& ($closestSize == 0 || $closestSize > (int)$sizes[$n]['width']))
				$closestSize = (int)$sizes[$n]['width'];
			}
        $all_sizes=$sizes;			
        for ($n = 0; $n < count($all_sizes); $n++)
            {
            if (intval($sizes[$n]['width']) != $closestSize
                    && intval($sizes[$n]['width']) > intval($originalSize['width'])
                    && intval($sizes[$n]['height']) > intval($originalSize['height']))
                unset($sizes[$n]);
            }
        foreach ($sizes as $n => $size)
            {
            # Only add choice if allowed
            $downloadthissize = resource_download_allowed($ref, $size["id"], $resource["resource_type"]);

            // Skip size if not allowed to download resource because user is denied access to it (for this resource type & size combo)
            if(!$downloadthissize && resource_has_access_denied_by_RT_size($resource['resource_type'], $size['id']))
                {
                continue;
                }

            if($size["id"] == "hpr" && strtolower($resource["file_extension"]) == "jpg" && isset($fullaccess) && !$fullaccess)
                {
                $downloadthissize = false;   
                }

            if ($hide_restricted_download_sizes && !$downloadthissize && !checkperm("q"))
                {
                // No option to request restricted sizes
                continue;
                }

            if(!$downloadthissize)
                {
                // This size is restricted - store to use in script array so download button can change 
                $restrictedsizes[] = $sizes[$n]["id"];
                }

			$name = $size['name'];
			if ($size['width'] == $closestSize)
                {
                if($block_original_size){continue;}
				$name = $lang['format_chooser_original_size'];
                }
            ?><option value="<?php echo $n ?>"><?php echo $name ?></option><?php
            }

		?></select><p id="sizeInfo"></p></td><?php
		if ($showprice)
			{
			?><td>-</td><?php
			}
		?><td class="DownloadFileFormatPicker" style="vertical-align: top;"><select id="format"><?php

		foreach ($format_chooser_output_formats as $format)
			{
			?><option value="<?php echo $format ?>" <?php if ($format == $defaultFormat) {
				?>selected="selected"<?php } ?>><?php echo str_replace_formatted_placeholder("%extension", $format, $lang["field-fileextension"]) ?></option><?php
			}

		?></select><?php showProfileChooser(); ?></td>
            <td class="DownloadButton">
                <a id="convertDownload" onclick="return CentralSpaceLoad(this, true);"><?php echo $lang['action-download']; ?></a>
            </td>
        </tr><?php
		}

	hook("formatchooseraftertable");
	if ($downloadCount > 0)
		{
        $originalSize_for_size_info = $originalSize['width'] !== '?' && $originalSize['height'] !== '?' ? $originalSize : null;
		?><script type="text/javascript">
			// Store size info in JavaScript array
			var sizeInfo = {
				<?php
				foreach ($sizes as $n => $size)
					{
                    # Calculate new dimensions based on original file's dimensions and configured width and height
                    $size_image_dimensions = calculate_image_dimensions($origpath, $size['width'], $size['height']);
                    if ($size['width'] == $closestSize) {
						$size = $originalSize; }
                    # Apply newly calculated width and height dimensions to the sizeInfo array
                    $size_to_output=$size;
                    $size_to_output['width']=$size_image_dimensions['new_width'];
                    $size_to_output['height']=$size_image_dimensions['new_height'];
                    echo $n ?>: {
                    'info': '<?php echo get_size_info($size_to_output, $originalSize_for_size_info ?: null) ?>',
                    'id': '<?php echo $size['id'] ?>',
                    'restricted': '<?php echo in_array($sizes[$n]["id"],$restrictedsizes) ? "1" : "0" ?>'
				},
				<?php } ?>
			};

			function updateSizeInfo() {
				var selected = jQuery('select#size').find(":selected").val();
				jQuery('#sizeInfo').html(sizeInfo[selected]['info']);
			}

			function updateDownloadLink() {
				var index = jQuery('select#size').find(":selected").val();
				var selectedFormat = jQuery('select#format').find(":selected").val();
                if(sizeInfo[index]["restricted"] != 0)
                    {
                    request_url = "<?php echo generateURL("{$baseurl}/pages/resource_request.php", $urlparams); ?>";
                    jQuery("a#convertDownload").attr("href", request_url);
                    jQuery("a#convertDownload").text("<?php echo $lang["action-request"]?>");
                    return;
                    }

                jQuery("a#convertDownload").text("<?php echo $lang["action-download"]?>");


				var profile = jQuery('select#profile').find(":selected").val();
				if (profile)
					profile = "&profile=" + profile;
				else
					profile = '';

                var basePage = "<?php echo generateURL("{$baseurl}/pages/download_progress.php", $urlparams); ?>";
                    basePage += "&ext=" + selectedFormat.toLowerCase();
                    basePage += profile;
                    basePage += "&size=" + sizeInfo[index]["id"];


                var terms_download = <?php echo ($terms_download ? "true" : "false"); ?>;
                if(terms_download)
                    {
                    var terms_url = "<?php echo generateURL("{$baseurl}/pages/terms.php", $urlparams); ?>";
                        terms_url += "&url=" + encodeURIComponent(basePage);

                    jQuery("a#convertDownload").attr("href", terms_url);
                    return;
					}

				var download_usage = <?php echo ($download_usage ? "true" : "false"); ?>;
				if (download_usage)
					{
                    var usage_url = "<?php echo generateURL("{$baseurl}/pages/download_usage.php", $urlparams); ?>";
                        usage_url += "&ext=" + selectedFormat.toLowerCase();
                        usage_url += profile;
                        usage_url += "&size=" + sizeInfo[index]["id"];
                        jQuery("a#convertDownload").attr("href", usage_url);
                	return;	
					}	

                jQuery("a#convertDownload").attr("href", "#");
                jQuery("a#convertDownload").attr("onclick", "directDownload('" + basePage + "')");

                return;
			}

			jQuery(document).ready(function() {
				updateSizeInfo();
				updateDownloadLink();
			});
			jQuery('select#size').change(function() {
				updateSizeInfo();
				updateDownloadLink();
			});
			jQuery('select#format').change(function() {
				updateDownloadLink();
			});
			jQuery('select#profile').change(function() {
				updateDownloadLink();
			});
		</script>
		<?php
		}
    global $altfiles, $alt_order_by, $alt_sort, $access,$alt_types_organize,$alternative_file_previews,$alternative_file_previews_mouseover,$userrequestmode,$alt_files_visible_when_restricted;
    $altfiles=get_alternative_files($ref,$alt_order_by,$alt_sort);
    # Alternative files listing
    include __DIR__ . "/../../../pages/view_alternative_files.php";
    # --- end of alternative files listing
	?></table><?php
	return true;
	}

?>
