<?php
include_once "../include/db.php";
include "../include/authenticate.php";
include_once "../include/dash_functions.php";

# Fetch promoted collections ready for display later
$home_collections=get_home_page_promoted_collections();
$welcometext=false;
global $welcome_text_picturepanel,$home_dash,$slideshow_big;

hook("homeheader");

include "../include/header.php";

if (!hook("replacehome")) { 
function loadWelcomeText() 
    {
    global $welcome_text_picturepanel,$no_welcometext,$home_dash,$productversion;
    if (!hook('homereplacewelcome') && !$no_welcometext)
        {
        ?>
        <div class="BasicsBox <?php echo $home_dash ? 'dashtext':''; ?>" id="HomeSiteText">
            <div id="HomeSiteTextInner">
                <h1>
                    <?php # Include version number
                    echo str_replace("[ver]",str_replace("SVN","",$productversion),text("welcometitle")) ?>
                </h1>
                <p><?php echo text("welcometext")?></p>
            </div>
            <?php hook('homeafterwelcometext') ?>
        </div>
        <?php 
        }
    }

if (!hook("replaceslideshow"))
	{
	global $slideshow_photo_delay;
    $slideshow_files_holder = get_slideshow_files_data();
    $slideshow_files = array();

    $homeimages = 0;
    foreach($slideshow_files_holder as $slideshow_file)
        {
        if((bool) $slideshow_file['homepage_show'] === false)
            {
            continue;
            }
        
        array_push($slideshow_files, $slideshow_file);
        $homeimages++;
        }

    if($slideshow_big && $homeimages > 0)
        {
        ?>
        <script>
        <?php 
        if ($themes_show_background_image) {
            ?>
            DeactivateSlideshow();
            <?php
            }
            ?>  

        var SlideshowImages = new Array();
        var SlideshowCurrent = -1;
        var SlideshowTimer = 0;
        <?php
        if($static_slideshow_image)
            {
            $randomimage = array_rand($slideshow_files);
            // We only want to use one of the available images	
            ?>
            var big_slideshow_timer = 0;
            RegisterSlideshowImage('<?php echo "{$baseurl_short}pages/download.php?slideshow={$slideshow_files[$randomimage]["ref"]}"; ?>','<?php echo (isset($slideshow_files[$randomimage]["link"])) ? $slideshow_files[$randomimage]["link"] : "" ?>',1);
            <?php
            }
        else
            {
            ?>
            var big_slideshow_timer = <?php echo $slideshow_photo_delay;?>;
            <?php
            foreach($slideshow_files as $slideshow_file_info)
                {
                if((bool) $slideshow_file_info['homepage_show'] === false)
                    {
                    continue;
                    }
                    ?>
                RegisterSlideshowImage('<?php echo "{$baseurl_short}pages/download.php?slideshow={$slideshow_file_info["ref"]}"; ?>','<?php echo (isset($slideshow_file_info["link"])) ? $slideshow_file_info["link"] : "" ?>');
                <?php
                }
            }
        ?>
        jQuery( document ).ready(function() 
            {
            /* Clear all old timers */
            ClearTimers();       
            ActivateSlideshow();
            });
        </script>
        <?php
        }
    elseif ($homeimages > 1 && !$slideshow_big) 
        { # Only add Javascript if more than one image.
        ?>
        <script>
        var num_photos=<?php echo $homeimages ?>;  // <---- number of photos (/images/slideshow?.jpg)
        var photo_delay= <?php echo $slideshow_photo_delay;?>;
        var link = new Array();
		var images = new Array();

        <?php
        foreach($slideshow_files as $slideshow_file_info)
            {
            echo "link.push(\"" .  (isset($slideshow_file_info["link"]) ? $slideshow_file_info["link"] : "#") . "\");\n";
            echo "images.push(" .  $slideshow_file_info["ref"] . ");\n";
            }
        ?>
    
        var cur_photo=1;
        var last_photo=0;
        var next_photo=1;
    
        flip=1;
    
        var image1=0;
        var image2=0;
    
        function nextPhoto()
            {
            if (!document.getElementById('image1')) {return false;} /* Photo slideshow no longer available (AJAX page move) */
            
              if (cur_photo==num_photos-1) {next_photo=0;} else {next_photo=cur_photo+1;}
              image1 = document.getElementById("image1");
              image2 = document.getElementById("photoholder");
              sslink = document.getElementById("slideshowlink");
              linktarget=link[cur_photo];
              if (flip==0)
                {
                // image1.style.visibility='hidden';
                //Effect.Fade(image1);
                jQuery('#image1').fadeOut(1000)
                window.setTimeout("image1.src=\'" + baseurl_short + "pages/download.php?slideshow=' + images[next_photo] + '\';if(linktarget!=''){jQuery('#slideshowlink').attr('href',linktarget);}else{jQuery('#slideshowlink').removeAttr('href');}",1000);
                flip=1;
                }
              else
                {
                jQuery('#image1').fadeIn(1000)
                window.setTimeout("image2.style.backgroundImage='url(' + baseurl_short + 'pages/download.php?slideshow=' + images[next_photo] +')';if(linktarget!=''){jQuery('#slideshowlink').attr('href',linktarget);}else{jQuery('#slideshowlink').removeAttr('href');}",1000);
                flip=0;
                }	  	
             
              last_photo=cur_photo;
              cur_photo=next_photo;
              timers.push(window.setTimeout("nextPhoto()", 1000 * photo_delay));
            }
    
        jQuery(document).ready( function ()
            { 
            /* Clear all old timers */
            ClearTimers();
            timers.push(window.setTimeout("nextPhoto()", 1000 * photo_delay));
            }
            );
            
        </script><?php 
        }
    if($slideshow_big) 
        {?>
        <style>
            #Footer {display:none;}
        </style>
        <?php
        }

	if ($small_slideshow && !$slideshow_big) 
		{ ?>
		<div id="SlideshowContainer">
			<div class="HomePicturePanel"
			<?php if(!hook("replaceeditslideshowwidth"))
				{
				if (isset($home_slideshow_width)) 
					{
					echo "style=\"";
					$slide_width = $home_slideshow_width + 2;
					echo"width:" .  (string)$slide_width ."px; ";
					echo "\" ";
					}
				}
			?>>
			
			<a id="slideshowlink"
			<?php
		
			if(isset($slideshow_files[0]["link"]))
				{
				echo "href=\"" . $slideshow_files[0]["link"] ."\" ";
				}
			
			?>
			>
			
			<div class="HomePicturePanelIN" id='photoholder' style="
			<?php if(!hook("replaceeditslideshowheight")){
			if (isset($home_slideshow_height)){		
				echo"height:" .  (string)$home_slideshow_height ."px; ";
				} 
			}
			?>
			background-image:url('<?php echo  "{$baseurl}/pages/download.php?slideshow=" . $slideshow_files[0]["ref"]; ?>');">
			
			<img src='<?php echo "{$baseurl}/pages/download.php?slideshow=" . ($homeimages>1?$slideshow_files[1]["ref"]:$slideshow_files[0]["ref"]); ?>' alt='' id='image1' style="display:none;<?php
			if (isset($home_slideshow_width)){
				echo"width:" .  $home_slideshow_width ."px; ";
				}
			if (isset($home_slideshow_height)){
				echo"height:" .  $home_slideshow_height ."px; ";
				} 
			?>">
			</div>
			</a>
			
			<?php
			hook("homebeforehomepicpanelend");
			?>
			</div>
			<?php
			global $welcome_text_picturepanel,$home_dash,$slideshow_big;
			if ($welcome_text_picturepanel || ($home_dash && !$slideshow_big))
				{
				loadWelcomeText();
				$welcometext=true;
				} ?>
		</div>
		<?php
		}
		// When not having the small slideshow and we also don't have big slideshow
		// we want welcome text on top of home panels
		if(!$small_slideshow)
			{
			loadWelcomeText();
			$welcometext = true;
			}
	} # End of hook replaceslideshow

	if($home_dash && $slideshow_big && !$welcometext){loadWelcomeText(); $welcometext=true;}
	hook("homebeforepanels");
	?>
	<div id="HomePanelContainer">
	<?php
	hook('homepanelcontainerstart');
	if($home_themeheaders && $enable_themes)
		{
		if($home_dash)
			{
			$title="themeselector";
			$all_users=1;
			$url="pages/ajax/dash_tile.php?tltype=conf&tlstyle=thmsl";
			$link="pages/collections_featured.php";
			$reload_interval=0;
			$resource_count=0;
			$default_order_by=0;
			$delete=0;
			if(!existing_tile($title,$all_users,$url,$link,$reload_interval,$resource_count))
				{
				create_dash_tile($url,$link,$title,$reload_interval,$all_users,$default_order_by,$resource_count,"",$delete);
				}
			}
		else
			{
            $url = "{$baseurl_short}pages/collections_featured.php";
            ?>
			<div class="HomePanel">
				<div class="HomePanelIN HomePanelThemes <?php if (count($home_collections)>0) { ?> HomePanelMatchPromotedHeight<?php } ?>">
				<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collections_featured.php">
				<h2 style="padding: 0px 15px 0 44px;margin-top: 26px;margin-left: 15px;"><?php echo $lang["themes"]?></h2></a>
				<p style="text-shadow: none;">
					<select id="themeselect" onChange="CentralSpaceLoad(this.value,true);">
					<option value=""><?php echo $lang["select"] ?></option>
					<?php
                    foreach(get_featured_collection_categories(0, array()) as $header)
						{
						?>
						<option value="<?php echo generateURL($url, array("parent" => $header["ref"])); ?>"><?php echo htmlspecialchars(i18n_get_translated($header["name"])); ?></option>
						<?php
						} ?>
					</select>
					<a id="themeviewall" onClick="return CentralSpaceLoad(this,true);" href="<?php echo $url; ?>"><?php echo LINK_CARET ?><?php echo $lang["viewall"] ?></a>
				</p>
				</div>
				
			</div>
			<?php
			}		
		}
	/* ------------ Customisable home page panels ------------------- */
	if (isset($custom_home_panels))
		{
		for ($n=0;$n<count($custom_home_panels);$n++)
			{
			if (!hook("panelperm")) 
				{ 
				if($home_dash)
					{
					# Check Tile tile exists in dash already
					$title=i18n_get_translated($custom_home_panels[$n]["title"]);
					$all_users=1;
					$url="pages/ajax/dash_tile.php?tltype=conf&tlstyle=custm";
					$link=$custom_home_panels[$n]["link"];
					if(strpos($custom_home_panels[$n]['link'], 'pages/') === false)
						{
						$link = 'pages/' . $custom_home_panels[$n]['link'];
						}
					if(strpos($custom_home_panels[$n]['link'], $baseurl . '/') !== false)
						{
						$link = $custom_home_panels[$n]['link'];
						$link = str_replace($baseurl . '/', '', $custom_home_panels[$n]['link']);
						}
					$text=$custom_home_panels[$n]["text"];
					$reload_interval=0;
					$resource_count=0;
					$default_order_by=6;
					$delete=0;
					if(!existing_tile($title,$all_users,$url,$link,$reload_interval,$resource_count,$text))
						{
						create_dash_tile($url,$link,$title,$reload_interval,$all_users,$default_order_by,$resource_count,$text,$delete);
						}
					}
				else
					{ ?>
					<a href="<?php echo $custom_home_panels[$n]["link"] ?>" <?php if (isset($custom_home_panels[$n]["additional"])){ echo $custom_home_panels[$n]["additional"];} ?> class="HomePanel">
						<div class="HomePanelIN<?php if (count($home_collections)>0) { ?> HomePanelMatchPromotedHeight<?php } ?>" <?php if ($custom_home_panels[$n]["text"]=="") {?>style="min-height:0;"<?php } ?>>
					<h2> <?php echo i18n_get_translated($custom_home_panels[$n]["title"]) ?></h2>
					<span><?php echo i18n_get_translated($custom_home_panels[$n]["text"]) ?></span>
					</div> 
					
					</a>
					<?php
					} // end hook 'panelperm'
				}
			}
		}

	if(!hook("homefeaturedcol"))
		{
		/* ------------ Collections promoted to the home page ------------------- */
		foreach ($home_collections as $home_collection)
			{
			if($home_dash)
				{
				# Check Tile tile exists in dash already
				if(empty($home_collection["home_page_text"]))
					{
					$home_collection["home_page_text"]=$home_collection["name"];
					}

				if(strlen($home_collection["home_page_text"])<=12)
					{
					$title = ucfirst(i18n_get_translated($home_collection["home_page_text"]));
					$text = "";
					}
				else
					{
					$text = ucfirst(i18n_get_translated($home_collection["home_page_text"]));
					$title = "";
					}
				
				$all_users=1;
				$url="pages/ajax/dash_tile.php?tltype=srch&tlstyle=thmbs";
				$link="/pages/search.php?search=!collection".$home_collection["ref"]."&order_by=relevance&sort=DESC";
				$reload_interval=0;
				$resource_count=0;
				$default_order_by=7;
				$delete=0;
				if(!existing_tile($title,$all_users,$url,$link,$reload_interval,$resource_count,$text))
					{
					create_dash_tile($url,$link,$title,$reload_interval,$all_users,$default_order_by,$resource_count,$text,$delete);
					//Turn off the promoted collection
					sql_query("UPDATE collection SET home_page_publish=0 WHERE ref=".$home_collection["ref"]);
					}
				}
			else
				{
				$defaultpreview=false;			
				if(file_exists(get_resource_path($home_collection["home_page_image"],true,"pre",false)))
					{
					$home_col_image=get_resource_path($home_collection["home_page_image"],false,"pre",false);
					}
				else
					{
					$defaultpreview=true;
					$home_col_image=$baseurl_short . "gfx/" . get_nopreview_icon($home_collection["resource_type"],$home_collection["file_extension"], false);
					}
				$tile_height=180;
				$tile_width=250;
				?>
				<a href="<?php echo $baseurl_short?>pages/search.php?search=!collection<?php echo $home_collection["ref"] ?>" onClick="return CentralSpaceLoad(this,true);" class="HomePanel HomePanelPromoted">
					<div id="HomePanelPromoted<?php echo $home_collection["ref"] ?>" class="HomePanelIN HomePanelPromotedIN" style="padding: 0;overflow: hidden;position: relative;height: 100%;width: 100%;min-height: 180px;">
							<img 
								src="<?php echo $home_col_image ?>" 
								<?php 
								if($defaultpreview)
									{
									?>
									style="position:absolute;top:<?php echo ($tile_height-128)/2 ?>px;left:<?php echo ($tile_width-128)/2 ?>px;"
									<?php
									}
								else 
									{
									#fit image to tile size
									if($home_collection["thumb_height"]>0 && $home_collection["thumb_width"]>0)
										{
										if(($home_collection["thumb_width"]*0.7)>=$home_collection["thumb_height"])
											{
											$ratio = $home_collection["thumb_height"] / $tile_height;
											$width = $home_collection["thumb_width"] / $ratio;
											if($width<$tile_width){echo "width='100%' ";}
											else {echo "height='100%' ";}
											}
										else
											{
											$ratio = $home_collection["thumb_width"] / $tile_width;
											$height = $home_collection["thumb_height"] / $ratio;
											if($height<$tile_height){echo "height='100%' ";}
											else {echo "width='100%' ";}
											}
										}
									?>
									style="position:absolute;top:0;left:0;"
									<?php
									}?>
								class="thmbs_tile_img"
							/>
				
							<span class="collection-icon"></span>
							<?php 
							if(!empty($home_collection["home_page_text"]))
								{ ?>
								<h3 class="title">
									<?php echo i18n_get_translated($home_collection["home_page_text"]); ?>
								</h3>
								<?php
								}
							else
								{ ?>
								<h2 class="title" style="float: none;position: relative;padding-left: 60px;padding-right: 15px;padding-top: 18px;text-transform: capitalize;text-shadow: #090909 1px 1px 8px;color: #fff;">
									<?php echo i18n_get_translated($home_collection["name"]); ?>
								</h2>
								<?php
								} ?>
					</div>
				
				</a>
				<?php
				}
			}
		} # end hook homefeaturedcol

	if($home_dash && checkPermission_dashmanage())
		{
        render_upgrade_available_tile($userref);
		get_user_dash($userref);	
		}
	else if($home_dash && !checkPermission_dashmanage())
		{
		get_managed_dash();
		}
	?>
	<div style="clear:both;"></div>
	</div> <!-- End HomePanelContainer -->
	
	<div class="clearerleft"></div>
	<?php
	if($small_slideshow && !$home_dash && !$welcometext){loadWelcomeText();}

} // End of ReplaceHome hook


// Launch KB modal if just logged in?
if (in_array($usergroup,$launch_kb_on_login_for_groups) && getval("login","")!="")
	{
	?>
	<script>
	window.setTimeout("ModalLoad('<?php echo $baseurl ?>/pages/help.php?initial=true',true);",2000);
	</script>
	<?php
	}

hook("homeafterpanels");


include "../include/footer.php";
?>
