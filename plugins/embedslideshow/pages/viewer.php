<?php
$suppress_headers=true; # Suppress headers including the XFRAME limitation so that this page can be remotely embedded.

include "../../../include/db.php";


include_once "../languages/en.php"; # Because this may not be included automatically, i.e. if the plugin is not available to all groups.

# Get variables and check key is valid.
$ref        = getvalescaped('ref', '');
$key        = getvalescaped('k', '');
$size       = getvalescaped('size', 'pre');
$transition = (int)getvalescaped('transition', 4);
$showtext   = getvalescaped('showtext', '0');

$player_width        = getvalescaped('width', '');
$player_height        = getvalescaped('height', '');
if (!is_numeric($player_width) || !is_numeric($player_height))
    {
    exit("Height and width parameters must be numeric.");
    }
$player_height = $player_height - 48;
$player_ratio = $player_width / $player_height;
	
# Check key is valid
if (!check_access_key_collection($ref,$key))
	{
	exit($lang["embedslideshow_notavailable"]);
	}
	
# Load watermark settings
$use_watermark=check_use_watermark();

?>
<html>
<head>
<link href="../css/embedslideshow.css?css_reload_key=<?php echo $css_reload_key; ?>" rel="stylesheet" type="text/css" media="screen,projection,print" /> 
<link rel="stylesheet" href="<?php echo $baseurl?>/lib/fontawesome/css/all.min.css?css_reload_key=<?php echo $css_reload_key?>">
<link rel="stylesheet" href="<?php echo $baseurl?>/lib/fontawesome/css/v4-shims.min.css?css_reload_key=<?php echo $css_reload_key?>">
<link id="global_font_link" href="<?php echo $baseurl?>/css/fonts/<?php echo $global_font ?>.css?css_reload_key=<?php echo $css_reload_key?>" rel="stylesheet" type="text/css" />
<script src="../../..<?php echo $jquery_path ?>?css_reload_key=<?php echo $css_reload_key; ?>" type="text/javascript"></script>
</head>
<body>

<div class="embedslideshow_player">
<div class="embedslideshow_preview" id="embedslideshow_preview" style="position: relative;width:<?php echo $player_width?>px;height:<?php echo $player_height?>px;">

<script type="text/javascript">
var embedslideshow_page=1;
var embedslideshow_x_offsets =  new Array();
var embedslideshow_y_offsets =  new Array();
<?php if ($transition>0) { ?>
var embedslideshow_auto=true;
<?php } else { ?>
var embedslideshow_auto=false;
<?php } ?>
var timer;
</script>

<?php
$page=1;
$resources=do_search("!collection" . $ref);
foreach ($resources as $resource)
	{
		
	$file_path=get_resource_path($resource["ref"],true,$size,false,$resource["preview_extension"],-1,1,$use_watermark);
	if (file_exists($file_path))
		{
		$preview_path=get_resource_path($resource["ref"],false,$size,false,$resource["preview_extension"],-1,1,$use_watermark);		
		}
	else
	
		{
		# Fall back to 'pre' size
		$preview_path=get_resource_path($resource["ref"],false,"pre",false,$resource["preview_extension"],-1,1,$use_watermark);
		}
		

	
	# sets height and width to display 
	if((isset($resource["thumb_width"])&&$resource["thumb_width"]<1) || (isset($resource["thumb_height"])&&$resource["thumb_height"] <1)) {continue;/*No Preview Available*/}
	$ratio=$resource["thumb_width"]/$resource["thumb_height"];
	
	if ($ratio > $player_ratio) // Base on the width unless we have been asked to scale to specific width
		{
		# Landscape image, width is the largest - scale the height
		$width = $player_width - 8;
		$height=floor($width / $ratio);
		}
	else
		{
		$height = $player_height;
		$width = floor($height* $ratio);
		}		
	?>
	<a class="embedslideshow_preview_inner" id="embedslideshow_preview<?php echo $page ?>" style="display:none;" href="#" onClick="embedslideshow_auto=false;embedslideshow_ShowPage(<?php echo ($page + 1) ?>,false,false);return false;">
        <img border="0" width=<?php echo $width ?> height=<?php echo $height; ?> src="<?php echo $preview_path ?>">
    </a>
	<?php 
	global $embedslideshow_textfield,$embedslideshow_resourcedatatextfield;
	if($embedslideshow_textfield && $showtext) 
		{ 
		$resource_data = get_data_by_field($resource["ref"],$embedslideshow_resourcedatatextfield);
		if($resource_data !="") 
			{
			?>
			<span class="embedslideshow_text" id="embedslideshow_previewtext<?php echo $page ?>"><?php echo $resource_data;?></span>
			<?php
			}		
		} ?>
	<script type="text/javascript">
	embedslideshow_x_offsets[<?php echo $page ?>]=<?php echo ($ratio < $player_ratio)?(ceil(($player_width-$width)/2)+4):8; ?>;
	embedslideshow_y_offsets[<?php echo $page ?>]=<?php echo ($ratio > $player_ratio)?(ceil(($player_height-$height)/2)+4):8; ?>;
	</script>
	<?php
	$page++;
	}
$maxpages=$page-1;
?>


</div>

<ul class="embedslideshow_controls_standard">	

<?php if ($width>100) { ?>
<li class="embedslideshow_begn" Style="cursor: pointer;" onClick="embedslideshow_auto=false;embedslideshow_ShowPage(1,false,false);return false;"><i class="fas fa-step-backward"></i></li>
<?php } ?>

<li class="embedslideshow_prev" Style="cursor: pointer;" onClick="embedslideshow_auto=false;embedslideshow_ShowPage(embedslideshow_page-1,false,false);return false;"><i class="fas fa-backward"></i></li>

<?php if ($width>100) { ?>
<li class="embedslideshow_auto" id="embedslideshow_auto" Style="cursor: pointer;" onClick="embedslideshow_auto=!embedslideshow_auto;if (embedslideshow_auto) {embedslideshow_ShowPage(embedslideshow_page+1,false,false);$('#embedslideshow_auto').fadeTo(100,1);} else {clearTimeout(timer);$('#embedslideshow_auto').fadeTo(100,0.4);}return false;"><i class="fas fa-pause"></i></li>
<?php if ($transition==0) { ?>
<script type="text/javascript">
$('#embedslideshow_auto').fadeTo(100,0.4);
</script>
<?php } ?>

<?php } ?>

<li class="embedslideshow_next" Style="cursor: pointer;" onClick="embedslideshow_auto=false;embedslideshow_ShowPage(embedslideshow_page+1,false,false);return false;"><i class="fas fa-forward"></i></li>

<?php if ($width>100) { ?>
<li class="embedslideshow_end" Style="cursor: pointer;" onClick="embedslideshow_auto=false;embedslideshow_ShowPage(embedslideshow_pages.length-1,false,false);return false;"><i class="fas fa-step-forward"></i></li>
<?php } ?>

<?php if ($width>200) {
# Jump controls - only if enough room to display them
 ?>
<li class="embedslideshow_jump" Style="cursor: pointer;" onClick="embedslideshow_auto=false;embedslideshow_ShowPage(document.getElementById('embedslideshow_page_box').value,false,true);return false;"><span><?php echo $lang["jump"] ?></span></li>
<li class="embedslideshow_jump-box"> <input type="text" id="embedslideshow_page_box" size="1" /> / <span id="page-count">#</span> </li>
<?php } ?>
</ul>



<script type="text/javascript">

function embedslideshow_ShowPage(page_set,from_auto,jump)
	{
	if (!embedslideshow_auto && from_auto) {return false;} // Auto switched off but timer still running. Terminate.
	
	if (embedslideshow_page==page_set && jump) {alert("<?php echo $lang["embedslideshow_alreadyonpage"]?>");return false;}
	
	// Fade out pause button if manually clicked
	if (!embedslideshow_auto)
		{
		jQuery('#embedslideshow_auto').fadeTo(100,0.4);
		}
		
	// Faster fade time when manually clicked
	if (embedslideshow_auto) {var embedslideshow_fadetime=1000;} else {var embedslideshow_fadetime=200;}
	
	// Fade out current page
	jQuery('#embedslideshow_preview' + embedslideshow_page).fadeOut(embedslideshow_fadetime);
	jQuery('#embedslideshow_previewtext' + embedslideshow_page).fadeOut(embedslideshow_fadetime);
		
	embedslideshow_page=page_set;
	if (embedslideshow_page>(<?php echo $maxpages ?>)) {embedslideshow_page=1;} // back to first page
	if (embedslideshow_page<1) {embedslideshow_page=<?php echo $maxpages ?>;} // to last page
	
	//document.getElementById("embedslideshow_preview").innerHTML=embedslideshow_pages[embedslideshow_page];

	// Center in space
	jQuery('#embedslideshow_preview' + embedslideshow_page).css('top',embedslideshow_y_offsets[embedslideshow_page] + 'px');
	jQuery('#embedslideshow_preview' + embedslideshow_page).css('left',embedslideshow_x_offsets[embedslideshow_page] + 'px');
	jQuery('.embedslideshow_text').css('left',embedslideshow_x_offsets[embedslideshow_page] + 'px');
	
		
	// Fade in new page
	jQuery('#embedslideshow_preview' + embedslideshow_page).fadeIn(embedslideshow_fadetime);
	jQuery('#embedslideshow_previewtext' + embedslideshow_page).fadeIn(embedslideshow_fadetime);

  	
	if (embedslideshow_auto) {timer = setTimeout("embedslideshow_ShowPage(embedslideshow_page+1,true,false);",<?php echo ($transition==0?4000:$transition * 1000) ?>);} else {clearTimeout(timer);}
	
	document.getElementById('embedslideshow_page_box').value=embedslideshow_page;
	}


embedslideshow_ShowPage(1,false,false);

// publishes total page count after forward slash next to actual page
function totalPages(){
	document.getElementById('page-count').innerHTML = <?php echo $maxpages ?>;
}
totalPages();

</script>

</div>
</body></html>
