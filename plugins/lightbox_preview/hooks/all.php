<?php

include_once dirname(__FILE__) . "/../include/utility.php";

function HookLightbox_previewAllAdditionalheaderjs()
	{
	global $baseurl_short, $css_reload_key;
	echo '<script src="' . $baseurl_short . 'lib/lightbox/js/lightbox.min.js" type="text/javascript" ></script>';
	echo '<link type="text/css" href="' . $baseurl_short . 'lib/lightbox/css/lightbox.min.css?css_reload_key=' . $css_reload_key . '" rel="stylesheet" />';

	?>
	<script>
	function closeModalOnLightBoxEnable()
		{
		setTimeout(function() {
			if(jQuery('#lightbox').is(':visible'))
				{
				ModalClose();
				}
		}, 10);
		}

	jQuery(document).ready(function()
        {
        lightbox.option({
			'resizeDuration': 300,
			'imageFadeDuration': 300,
			'fadeDuration': 300,
			'alwaysShowNavOnTouchDevices': true})
        });
	</script>
	<?php
	}

function HookLightbox_previewAllReplacefullscreenpreviewicon()
	{
	global $baseurl_short, $ref, $result, $n, $k, $search, $offset, $sort, $order_by, $archive,
			$lang, $showkeypreview, $value, $view_title_field;

	$url = getPreviewURL($result[$n]);
	if ($url === false)
		return false;
	$showkeypreview = true;

	# Replace the link to add the 'previewlink' ID
	?>
		<span class="IconPreview"><a aria-hidden="true" class="fa fa-expand" id= "previewlink<?php echo htmlspecialchars($order_by) . $ref?>" href="<?php
			echo $baseurl_short?>pages/preview.php?from=search&ref=<?php
			echo urlencode($ref)?>&ext=<?php echo $result[$n]["preview_extension"]?>&search=<?php
			echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php
			echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php
			echo urlencode($archive)?>&k=<?php echo urlencode($k)?>" title="<?php
			echo $lang["fullscreenpreview"]?>"></a></span>
			
	<?php
	addLightBox('#previewlink' . htmlspecialchars($order_by) . $ref, $url, $result[$n]["field".$view_title_field], htmlspecialchars($order_by));
	return true;
	}

?>