<?php

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

?>
