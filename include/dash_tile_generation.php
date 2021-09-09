<?php
/*
 * Dash Tile Generation Functions - Montala Ltd, Jethro Dew
 * These control the content for the different variations of tile type and tile style.
 * 
 */


/*
 * Tile serving
 *
 */
function tile_select($tile_type,$tile_style,$tile,$tile_id,$tile_width,$tile_height)
	{
	/*
	 * Preconfigured and the legacy tiles controlled by config.
	 */
	if($tile_type=="conf")
		{
		switch($tile_style)
			{
			case "thmsl": 	global $usertile;
							tile_config_themeselector($tile,$tile_id,$usertile,$tile_width,$tile_height);
							exit;
			case "custm":	tile_config_custom($tile,$tile_id,$tile_width,$tile_height);
							exit;
			case "pend": 	tile_config_pending($tile,$tile_id,$tile_width,$tile_height);
							exit;
			}
		}
	/*
	 * Free Text Tile
	 */
	if($tile_type=="ftxt")
		{
		tile_freetext($tile,$tile_id,$tile_width,$tile_height);
		exit;
		}

	/*
	 * Search Type tiles
	 */
	if($tile_type=="srch")
		{
		switch($tile_style)
			{
			case "thmbs":	$promoted_image=getvalescaped("promimg",false);
							tile_search_thumbs($tile,$tile_id,$tile_width,$tile_height,$promoted_image);
							exit;
			case "multi":	tile_search_multi($tile,$tile_id,$tile_width,$tile_height);
							exit;
			case "blank":	tile_search_blank($tile,$tile_id,$tile_width,$tile_height);
							exit;
			}
		}

    // Featured collection - themes specific tiles
    if('fcthm' == $tile_type)
        {
        switch($tile_style)
            {
            case 'thmbs':
                tile_featured_collection_thumbs($tile, $tile_id, $tile_width, $tile_height, getvalescaped('promimg', 0));
                break;

            case 'multi':
                tile_featured_collection_multi($tile, $tile_id, $tile_width, $tile_height, getvalescaped('promimg', 0));
                break;

            case 'blank':
            default:
                tile_featured_collection_blank($tile, $tile_id);
                break;
            }

        exit();
        }
	}

/*
 * Config controlled panels
 *
 */

function tile_config_themeselector($tile,$tile_id,$tile_width,$tile_height)
	{
	global $lang,$pagename,$baseurl_short, $theme_direct_jump;
    
    $url = "{$baseurl_short}pages/collections_featured.php";
    $fc_categories = get_featured_collection_categories(0, []);
	?>
	<div class="featuredcollectionselector HomePanel DashTile DashTileDraggable allUsers" tile="<?php echo $tile["ref"]?>" id="<?php echo str_replace("contents_","",$tile_id);?>" >
		<div id="<?php echo $tile_id?>" class="HomePanelThemes HomePanelDynamicDash HomePanelIN">
				<span class="theme-icon"></span>
				<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collections_featured.php"><h2><?php echo $lang["themes"]?></h2></a>
				<p>
                <?php
                if(!empty($fc_categories))
                    {
                    ?>
                    <select id="themeselect" onChange="CentralSpaceLoad(this.value,true);">
                    <option value=""><?php echo $lang["select"] ?></option>
                    <?php
                    foreach($fc_categories as $header)
                        {
                        ?>
                        <option value="<?php echo generateURL($url, array("parent" => $header["ref"])); ?>"><?php echo htmlspecialchars(i18n_get_translated($header["name"])); ?></option>
                        <?php
                        }
                    ?>
                    </select>
                    <?php
                    }

				if(!$theme_direct_jump)
					{
					?>
					<a id="themeviewall" onClick="return CentralSpaceLoad(this,true);" href="<?php echo $url; ?>"><?php echo LINK_CARET; ?><?php echo $lang["viewall"]; ?></a>
					<?php
					}
					?>
				</p>
		</div>
	</div>
	<script>
	 jQuery("a#<?php echo str_replace("contents_","",$tile_id);?>").replaceWith(jQuery(".featuredcollectionselector"));
	</script>
	<?php
	}

function tile_config_custom($tile,$tile_id,$tile_width,$tile_height) 
	{
	global $lang;
	?>
	<span class='search-icon'></span>
	<h2> <?php echo htmlspecialchars(i18n_get_translated($tile["title"])); ?></h2>
	<p><?php echo htmlspecialchars(i18n_get_translated($tile["txt"])); ?></p>
	<?php
	}
function tile_config_pending($tile,$tile_id,$tile_width,$tile_height)
	{
	global $lang, $search_all_workflow_states;
	$linkstring = explode('?',$tile["link"]);
	parse_str(str_replace("&amp;","&",$linkstring[1]),$linkstring);

    $search="";
    $count=1;
    $restypes = "";
    $order_by= "relevance";
    $archive = $linkstring["archive"];
    $sort = "";
    $search_all_workflow_states=false;
    $tile_search=do_search($search,$restypes,$order_by,$archive,$count,$sort,false,0,false,false,"",false,false,false,true);
    if(!is_array($tile_search))
        {
        $found_resources=false;
        $count=0;
        }
    else
        {
        $found_resources=true;
        $count=count($tile_search);
        }

	// Hide if no results
    if(!$found_resources || $count==0)
        { 
        global $usertile;

        $tile_element_id = isset($usertile) ? "user_tile{$usertile['ref']}" : "tile{$tile['ref']}";
        ?>
        <style>
        #<?php echo htmlspecialchars($tile_element_id); ?>
            {
            display:none;
            }
        </style>
        <?php

        return;
        }
        ?>
	<span class='collection-icon'></span>
	<?php
	if(!empty($tile['title']))
		{
		?>
		<h2 class="title"><?php echo htmlspecialchars(i18n_get_translated($tile['title'])); ?></h2>
		<?php
		}
	else if(!empty($tile['txt']) && isset($lang[strtolower($tile['txt'])]))
		{
		?>
		<h2 class="title notitle"><?php echo htmlspecialchars($lang[strtolower($tile['txt'])]); ?></h2>
		<?php
		}
	else if(!empty($tile['txt']) && !isset($lang[strtolower($tile['txt'])]))
		{
		?>
		<h2 class="title notitle"><?php echo htmlspecialchars($tile['txt']); ?></h2>
		<?php
		}
		
	if(!empty($tile['title']) && !empty($tile['txt']))
		{
		if(isset($lang[strtolower($tile['txt'])]))
			{
		?>
		<p><?php echo htmlspecialchars($lang[strtolower($tile['txt'])]); ?></p>
		<?php
			}
		else
			{
			?>
		<p><?php echo htmlspecialchars(i18n_get_translated($tile['txt'])); ?></p>
			<?php
			}
		}
	?>
	<p class="tile_corner_box">
		<span aria-hidden="true" class="fa fa-clone"></span>
		<?php echo $count; ?>
	</p>
	<?php
	}

/*
 * Freetext tile
 *
 */
function tile_freetext($tile,$tile_id,$tile_width,$tile_height) 
	{
	global $lang;
	?>
	<span class='help-icon'></span>
	<h2> <?php echo htmlspecialchars(i18n_get_translated($tile["title"])); ?></h2>
	<p><?php echo htmlspecialchars(i18n_get_translated($tile["txt"])); ?></p>
	<?php
	generate_dash_tile_toolbar($tile,$tile_id);
	}

/*
 * Search linked tiles
 *
 */
function tile_search_thumbs($tile,$tile_id,$tile_width,$tile_height,$promoted_image=false)
	{
	global $baseurl_short,$lang;
	$tile_type="srch";
	$tile_style="thmbs";
	$search_string = explode('?',$tile["link"]);
	parse_str(str_replace("&amp;","&",$search_string[1]),$search_string);
	$search = isset($search_string["search"]) ? $search_string["search"] :"";

	$icon = ""; 
	if(substr($search,0,11)=="!collection")
		{$icon="cube";}
	else if(substr($search,0,7)=="!recent" || substr($search,0,5)=="!last")
		{$icon="clock-o";}
	else{$icon="search";}

	if(!empty($tile["title"]))
		{ ?>
		<h2>
		<span class='fa fa-<?php echo $icon ?>'></span>
		<?php echo htmlspecialchars(i18n_get_translated($tile["title"]));?>
		</h2>
		<?php
		}
	else if(!empty($tile["txt"]))
		{ ?>
		<h2>
		<?php echo htmlspecialchars(i18n_get_translated($tile["txt"]));?>
		</h2>
		<?php
		}
 	
 	if(!empty($tile["title"]) && !empty($tile["txt"]))
		{ ?>
		<p>
		<?php echo htmlspecialchars(i18n_get_translated($tile["txt"]));?>
		</p>
		<?php
		}

    tltype_srch_generate_js_for_background_and_count($tile, $tile_id, (int) $tile_width, (int) $tile_height, (int) $promoted_image);
	generate_dash_tile_toolbar($tile,$tile_id);
	}

function tile_search_multi($tile,$tile_id,$tile_width,$tile_height)
	{
	global $baseurl_short,$lang;

	$tile_type="srch";
	$tile_style="multi";
	$search_string = explode('?',$tile["link"]);
	parse_str(str_replace("&amp;","&",$search_string[1]),$search_string);
	$search = isset($search_string["search"]) ? $search_string["search"] :"";
	
	$icon = ""; 
	if(substr($search,0,11)=="!collection")
		{$icon="cube";}
	else if(substr($search,0,7)=="!recent" || substr($search,0,5)=="!last")
		{$icon="clock-o";}
	else
		{$icon="search";}
	
	if(!empty($tile["title"]))
		{ ?>
		<h2>
		<span class='fa fa-<?php echo $icon ?>'></span>
		<?php echo htmlspecialchars(i18n_get_translated($tile["title"]));?>
		</h2>
		<?php
		}
	else if(!empty($tile["txt"]))
		{ ?>
		<h2>
		<span class='fa fa-<?php echo $icon ?>'></span>
		<?php echo htmlspecialchars(i18n_get_translated($tile["txt"]));?>
		</h2>
		<?php
		}

	if(!empty($tile["title"]) && !empty($tile["txt"]))
		{ ?>
		<p>
		<?php echo htmlspecialchars(i18n_get_translated($tile["txt"]));?>
		</p>
		<?php
		}

    tltype_srch_generate_js_for_background_and_count($tile, $tile_id, (int) $tile_width, (int) $tile_height, 0);
	generate_dash_tile_toolbar($tile,$tile_id);
	}

function tile_search_blank($tile,$tile_id,$tile_width,$tile_height)
	{
	global $baseurl_short,$lang;
	$tile_type="srch";
	$tile_style="blank";
	$search_string = explode('?',$tile["link"]);
	parse_str(str_replace("&amp;","&",$search_string[1]),$search_string);
	$search = isset($search_string["search"]) ? $search_string["search"] :"";
	
	$icon = ""; 
	if(substr($search,0,11)=="!collection")
		{$icon="cube";}
	else if(substr($search,0,7)=="!recent" || substr($search,0,5)=="!last")
		{$icon="clock-o";}
	else{$icon="search";}

	if(!empty($tile["title"]))
		{ ?>
		<h2>
		<span class='fa fa-<?php echo $icon ?>'></span>
		<?php echo htmlspecialchars(i18n_get_translated($tile["title"]));?>
		</h2>
		<?php
		}
	else if(!empty($tile["txt"]))
		{ ?>
		<h2>
		<span class='fa fa-<?php echo $icon ?>'></span>
		<?php echo htmlspecialchars(i18n_get_translated($tile["txt"]));?>
		</h2>
		<?php
		}
 	
 	if(!empty($tile["title"]) && !empty($tile["txt"]))
		{ ?>
		<p>
		<?php echo htmlspecialchars(i18n_get_translated($tile["txt"]));?>
		</p>
		<?php
		}

    tltype_srch_generate_js_for_background_and_count($tile, $tile_id, (int) $tile_width, (int) $tile_height, 0);
	generate_dash_tile_toolbar($tile,$tile_id);
	}


function tile_featured_collection_thumbs($tile, $tile_id, $tile_width, $tile_height, $promoted_image)
    {
    global $baseurl_short, $lang;

    if($promoted_image > 0)
        {
        $promoted_image_data = get_resource_data($promoted_image);
		
        if($promoted_image_data !== false)
            {
            $preview_resource = $promoted_image_data;
            }
        else
			{
			return false; // Promoted image could not be found.
			}

		$preview_resource_mod=hook('modify_promoted_image_preview_resource_data','',array($promoted_image));
		if($preview_resource_mod!==false)
			{
			$preview_resource=$preview_resource_mod;
			}
		
        $no_preview = false;
		
        $preview_path = get_resource_path($preview_resource['ref'], true, 'pre', false, 'jpg', -1, 1, false);
        if(file_exists($preview_path))
            {
            $preview_path = get_resource_path($preview_resource['ref'], false, 'pre', false, 'jpg', -1, 1, false);
            }
        else
            {
            $preview_path  = "{$baseurl_short}gfx/";
            $preview_path .= get_nopreview_icon($preview_resource['resource_type'], $preview_resource['file_extension'], false);
            $no_preview    = true;
            }
        ?>
        <img 
            src="<?php echo $preview_path; ?>" 
            <?php 
            if($no_preview)
                {
                ?>
                style="position:absolute; top:<?php echo ($tile_height - 128) / 2; ?>px;left:<?php echo ($tile_width - 128) / 2; ?>px;"
                <?php
                }
            else 
                {
                // fit image to tile size
                if(($preview_resource['thumb_width'] * 0.7) >= $preview_resource['thumb_height'])
                    {
                    $ratio = $preview_resource['thumb_height'] / $tile_height;
                    $width = $preview_resource['thumb_width'] / $ratio;

                    if($width < $tile_width)
                        {
                        echo 'width="100%" ';
                        }
                    else
                        {
                        echo 'height="100%" ';
                        }
                    }
                else
                    {
                    $ratio  = $preview_resource['thumb_width'] / $tile_width;
                    $height = $preview_resource['thumb_height'] / $ratio;

                    if($height < $tile_height)
                        {
                        echo 'height="100%" ';
                        }
                    else
                        {
                        echo 'width="100%" ';
                        }
                    }
                ?>
                style="position:absolute;top:0;left:0;"
                <?php
                }?>
            class="thmbs_tile_img"
        />
        <?php
        }
        ?>
    <h2>
        <span class='fa fa-folder'></span>
        <?php
        if('' != $tile['title'])
            {
            echo htmlspecialchars(i18n_get_translated($tile['title']));
            }
        else if('' != $tile['txt'])
            {
            echo htmlspecialchars(i18n_get_translated($tile['txt']));
            }
        ?>
    </h2>
    <?php
    if('' != $tile['title'] && '' != $tile['txt'])
        { 
        ?>
        <p><?php echo htmlspecialchars(i18n_get_translated($tile['txt'])); ?></p>
        <?php
        }

	generate_dash_tile_toolbar($tile,$tile_id);
    return;
    }


function tile_featured_collection_multi($tile, $tile_id, $tile_width,$tile_height,$promoted_image)
    {
    global $baseurl_short, $lang;

    $link_parts = explode('?', $tile['link']);
    parse_str(str_replace('&amp;', '&', $link_parts[1]), $link_parts);

    $parent = (isset($link_parts["parent"]) ? (int) validate_collection_parent(array("parent" => (int) $link_parts["parent"])) : 0);
    $resources = dash_tile_featured_collection_get_resources($parent, array("limit" => 4));

    if(count($resources)>0)
        {
        if(count($resources) == 1)
            {
            return tile_featured_collection_thumbs($tile,$tile_id,$tile_width,$tile_height, $resources[0]['ref']); 
            }
        $i = 0;
        foreach(array_rand($resources, min(count($resources), 4)) as $random_picked_resource_key)
            {
            $resource = $resources[$random_picked_resource_key];
    
            $shadow = true;
    
            $preview_path = get_resource_path($resource['ref'], true, 'pre', false, 'jpg', -1, 1, false);
            if(file_exists($preview_path))
                {
                $preview_path = get_resource_path($resource['ref'], false, 'pre', false, 'jpg', -1, 1, false);
                }
            else
                {
                $preview_path  = "{$baseurl_short}gfx/";
                $preview_path .= get_nopreview_icon($resource['resource_type'], $resource['file_extension'], false);
                $shadow        = false;
                }
    
            $tile_working_space = ('' == $tile['tlsize'] ? 140 : 280);
    
            $gap   = $tile_working_space / min(count($resources), 4);
            $space = $i * $gap;
            ?>
            <img style="position: absolute; top: 10px; left:<?php echo $space * 1.5; ?>px; height: 100%;<?php if($shadow) { ?>box-shadow: 0 0 25px #000;<?php } ?>;transform: rotate(<?php echo 20 - ($i * 12); ?>deg);" src="<?php echo $preview_path; ?>">
            <?php
            $i++;
            }
        }
        ?>
    <h2>
        <span class='fa fa-folder'></span>
        <?php
        if('' != $tile['title'])
            {
            echo htmlspecialchars(i18n_get_translated($tile['title']));
            }
        else if('' != $tile['txt'])
            {
            echo htmlspecialchars(i18n_get_translated($tile['txt']));
            }
        ?>
    </h2>
    <?php
    if('' != $tile['title'] && '' != $tile['txt'])
        { 
        ?>
        <p><?php echo htmlspecialchars(i18n_get_translated($tile['txt'])); ?></p>
        <?php
        }

	generate_dash_tile_toolbar($tile,$tile_id);
    return;
    }


function tile_featured_collection_blank($tile, $tile_id)
    {
    global $baseurl_short, $lang;
    ?>
    <h2>
        <span class='fa fa-folder'></span>
        <?php
        if('' != $tile['title'])
            {
            echo htmlspecialchars(i18n_get_translated($tile['title']));
            }
        else if('' != $tile['txt'])
            {
            echo htmlspecialchars(i18n_get_translated($tile['txt']));
            }
        ?>
    </h2>
    <?php
    if('' != $tile['title'] && '' != $tile['txt'])
        { 
        ?>
        <p><?php echo htmlspecialchars(i18n_get_translated($tile['txt'])); ?></p>
        <?php
        }

	generate_dash_tile_toolbar($tile,$tile_id);
    return;
    }