<?php

function HookAccessibilityAllAfterheaderajax()
{
?>
	<script type="text/javascript">
		jQuery(document).ready(function(){

			var homepanel_sizes = [];

			// Get all heights of the homepanels on the screen:
			jQuery('.HomePanelIN').each(function(index, value) {
				homepanel_sizes.push(jQuery(value).height());
			});

			// Get the highest height of homepanels:
			var highest_homepanel = Math.max.apply(Math, homepanel_sizes);

			jQuery('.HomePanelIN').each(function(index, value) {
				jQuery(value).height(highest_homepanel);
			});

		});
	</script>
<?php
}

function HookAccessibilityAllAdditionalheaderjs() {
	// Have the same functionality when ajax is not involved:
	HookAccessibilityAllAfterheaderajax();
}

function HookAccessibilityAllThumbstextheight() {
	// Required for the larger 18px font size
	global $field_height, $resource_panel_icons_height, $resource_id_height, $resource_type_icon_height;
	
	$field_height = 35;
	$resource_panel_icons_height = 35;
	$resource_id_height = 25;
	$resource_type_icon_height = 26;
}