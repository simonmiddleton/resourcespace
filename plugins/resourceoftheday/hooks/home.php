<?php

function HookResourceofthedayHomeReplaceslideshow ()
	{
	include_once dirname(__FILE__)."/../inc/rotd_functions.php";

	global $baseurl, $view_title_field;

	$rotd=get_resource_of_the_day();
	if ($rotd===false) {return false;} # No ROTD, return false to disable hook and display standard slide show.

    # Get preview width
    $sizes = get_image_sizes($rotd, true);
    
    // No need to continue if we don't have any sizes
    if(0 === count($sizes))
        {
        return false;
        }
	// default width value
	$width="auto";	

    foreach ($sizes as $size)
        {		
        if ($size["id"]=="pre" && isset($size["width"]) && is_numeric($size["width"]))
            {
            $width = $size["width"] . "px" ;
            break;
            } 
        }

    # Fetch title
    $title = get_data_by_field($rotd, $view_title_field);

	# Fetch caption
	$caption = get_data_by_field($rotd, 18);

	# Show resource!
	$pre=get_resource_path($rotd,false,"pre",false,"jpg");
	?>
	<div class="HomePicturePanel RecordPanel" style="width: <?php echo $width ?>; padding-left: 3px;">
	<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl?>/pages/view.php?ref=<?php echo $rotd ?>"><img class="ImageBorder" style="margin-bottom: 10px;" src="<?php echo $pre ?>" /></a>
	<br />
	<h2 ><?php echo i18n_get_translated(htmlspecialchars($title)) ?></h2>
	<?php echo $caption ?>
	</div>
	<?php
	
	return true;
	}


?>