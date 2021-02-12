<?php
/*
 * User Dash - Tile Interface
 * Page for building tiles for the homepage dash interface
 *
 */

include "../include/db.php";

$k=getvalescaped("k","");
include "../include/authenticate.php";
include_once "../include/dash_functions.php";

if(!checkPermission_dashcreate()){exit($lang["error-permissiondenied"]);}
global $baseurl,$baseurl_short,$userref,$managed_home_dash;

if($managed_home_dash && !(checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h"))){exit($lang["error-permissiondenied"]);}
$error=false;
$message =false;

/* 
 * Process Submitted Tile 
 */
$submitdashtile=getvalescaped("submitdashtile",FALSE);
if($submitdashtile && enforcePostRequest(false))
	{
	$buildurl = getvalescaped("url","");
    $tlsize   = ('double' === getvalescaped('tlsize', '') ? 'double' : '');

	if ($buildurl=="")
		{
        $new_buildurl_tltype        = getvalescaped('tltype', '');
        $new_buildurl_tlstyle       = getvalescaped('tlstyle', '');
        $new_buildurl_tlstylecolour = urlencode(getvalescaped('tlstylecolour', ''));

		# No URL provided - build a URL (standard title types).
		$buildurl = "pages/ajax/dash_tile.php?tltype={$new_buildurl_tltype}&tlsize={$tlsize}&tlstyle={$new_buildurl_tlstyle}";

        if('' != $new_buildurl_tltype && allow_tile_colour_change($new_buildurl_tltype) && '' != $new_buildurl_tlstylecolour)
            {
            $buildurl .= "&tlstylecolour={$new_buildurl_tlstylecolour}";
            }

        $promoted_image = getvalescaped('promoted_image', '');
        if('' != trim($promoted_image))
            {
            $buildurl .= '&promimg=' . $promoted_image;
            }
		}

    /*
    tile_audience can be:
    - false for "only me"
    - true for "all users"
    - specific_user_groups
    */
    $tile_audience        = getvalescaped('tile_audience', '');
    $specific_user_groups = getvalescaped('specific_user_groups', array());

	if(checkPermission_dashadmin())
        {
        switch($tile_audience)
            {
            case 'true':
            case 'specific_user_groups':
                $all_users = true;
                break;

            case 'false':
                $all_users = false;
                break;
            }
        }
	else
        {
        $all_users = false;
        }

	$title=getvalescaped("title","");
	$text=getvalescaped("freetext","");
	$default_order_by=getvalescaped("default_order_by","UNSET");
	$reload_interval=getvalescaped("reload_interval_secs","");
	$resource_count=getvalescaped("resource_count",FALSE);

	$link=str_replace("&amp;","&",getvalescaped("link",""));
	if(strpos($link,$baseurl_short)===0) 
		{
		$length = strlen($baseurl_short);
		$link = substr_replace($link,"",0,$length);
		}
	$link= preg_replace("/^\//","",$link);


	#Check for update rather than new
	$updatetile = getvalescaped("editdashtile",FALSE);
	if($updatetile && is_numeric($updatetile))
		{
		$tile = get_tile($updatetile);
		$buildstring = explode('?',$tile["url"]);
		parse_str(str_replace("&amp;","&",$buildstring[1]),$buildstring);

		#Change of tilestyle?
		$tile_style     = getvalescaped('tlstyle', FALSE);
		$promoted_image = getvalescaped('promoted_image', FALSE);
        $tlstylecolour  = urlencode(getvalescaped('tlstylecolour', ''));

		if($tile_style)
			{
			$buildurl = str_replace("tlstyle=".$buildstring["tlstyle"],"tlstyle=".$tile_style,$tile["url"]);

            // If style changed and we can no longer support tile colours, remove it from url
            if(!allow_tile_colour_change($buildstring['tltype'], $tile_style) && isset($buildstring['tlstylecolour']))
                {
                $buildurl = str_replace("&tlstylecolour={$buildstring['tlstylecolour']}", '', $buildurl);
                }

            // Style changed and we support tile colours
            if(allow_tile_colour_change($buildstring['tltype'], $tile_style) && '' != trim($tlstylecolour))
                {
                if(isset($buildstring['tlstylecolour']))
                    {
                    $buildurl = str_replace('tlstylecolour=' . urlencode($buildstring['tlstylecolour']), "tlstylecolour={$tlstylecolour}", $buildurl);
                    }
                else
                    {
                    $buildurl .= "&tlstylecolour={$tlstylecolour}";
                    }
                }
			}
        else
            {
            // Allow changing colours for tile types that don't have a style (e.g ftxt)
            if(allow_tile_colour_change($buildstring['tltype']) && '' != trim($tlstylecolour))
                {
                if(isset($buildstring['tlstylecolour']))
                    {
                    $buildurl = str_replace("tlstylecolour=" . urlencode($buildstring['tlstylecolour']), "tlstylecolour={$tlstylecolour}", $buildurl);
                    }
                else
                    {
                    $buildurl .= "&tlstylecolour={$tlstylecolour}";
                    }
                }
            }

		if($promoted_image)
			{
			$buildurl = str_replace("promimg=".$buildstring["promimg"],"promimg=".$promoted_image,$buildurl);
			}

        if(isset($buildstring['tlsize']))
            {
            $buildurl = str_replace("tlsize={$buildstring['tlsize']}", "tlsize={$tlsize}", $buildurl);
            }

		if(($tile["all_users"] || $all_users ) && checkPermission_dashadmin())
            {
            log_activity($lang['manage_all_dash'], LOG_CODE_EDITED, $title . ($text == '' ? '' : " ({$text})"),'dash_tile',null,$tile['ref']);
            $current_specific_user_groups = get_tile_user_groups($tile['ref']);
			update_dash_tile($tile,$buildurl,$link,$title,$reload_interval,$all_users,$tile_audience,$current_specific_user_groups,$specific_user_groups,$default_order_by,$resource_count,$text);
            }
		else if(!$tile["all_users"] && !$all_users) # Not an all_users tile
			{
			$newtile = create_dash_tile($buildurl,$link,$title,$reload_interval,$all_users,$default_order_by,$resource_count,$text);
			sql_query("UPDATE user_dash_tile SET dash_tile = ".$newtile." WHERE dash_tile=".$tile["ref"]." AND user =".$userref);
			cleanup_dash_tiles();
			}
		}
	else
		{
        #CREATE NEW
        # check for existing tile with same values
        $existing_tile_ref = existing_dash_tile($buildurl, $link,$title,$text,(int) $reload_interval,(int) $all_users,(int) $resource_count);
        if ($existing_tile_ref > 0 && !empty($specific_user_groups))
            {
            $message = str_replace("[existing_tile_ref]", $existing_tile_ref,$lang["existingdashtilefound-2"]) ;  
            }

		$tile = create_dash_tile($buildurl, $link, $title, $reload_interval, $all_users, $default_order_by, $resource_count, $text, 1, $specific_user_groups);
        if($all_users || (!$all_users && !empty($specific_user_groups)))
            {
            log_activity($lang['manage_all_dash'], LOG_CODE_CREATED, $title . ($text == '' ? '' : " ({$text})"),'dash_tile',null,$tile);
            }
        else
            {
            $existing = add_user_dash_tile($userref,$tile,$default_order_by);
            if(isset($existing[0]))
                {
                $error=$lang["existingdashtilefound"];
                }
            }


		}
		
	/* SAVE SUCCESSFUL? */
	if(!$error && !$message)
		{
		hook('before_dash_tile_save_redirect');
		redirect($baseurl);
		exit();
		}
	include "../include/header.php";
	?>
	<h1><?php echo $lang["createnewdashtile"];render_help_link("user/create-dash-tile");?></h1>
	<?php 
	if($error)
		{?>
		<p class="FormError" style="margin-left:5px;">
		<?php echo $error;?>
		</p>
		<?php
		}?>

<?php 
	if($message)
		{?>
		<p style="margin-left:5px;">
		<?php echo $message;?>
		</p>
		<?php
		if(strpos($link,"pages/")===0)
		    {
			$length = strlen("pages/");
			$link = substr_replace($link,"",0,$length);
		    }
		}?>

	<a href="<?php echo $link;?>"><?php echo LINK_CARET ?><?php echo $lang["returntopreviouspage"];?></a>
	<?php
	include "../include/footer.php";
	exit();
	}



/* 
 * For displaying a selector for the different styles of tile.
 * Styles are config controlled.
 */
function tileStyle($tile_type, $existing = null, $tile_colour = '')
	{
	global $lang,$tile_styles,$promoted_resource,$resource_count;

    if(count($tile_styles[$tile_type]) < 2)
        {
        // If this tile type allows for changing its colour, show it
        if(allow_tile_colour_change($tile_type))
            {
            if(allow_tile_colour_change($tile_type))
                {
                foreach($tile_styles[$tile_type] as $style)
                    {
                    if(allow_tile_colour_change($tile_type, $style))
                        {
                        render_dash_tile_colour_chooser($style, $tile_colour);
                        }
                    }
                }
            }

        return false;
        }
	?>
	<div class="Question">
		<label for="tltype"><?php echo $lang["dashtilestyle"];?></label> 
		<table>
			<tbody>
				<tr>
					<?php
					$check=true;
					foreach($tile_styles[$tile_type] as $style)
						{ ?>
						<td width="10" valign="middle" >
							<input 
								type="radio" 
								class="tlstyle" 
								id="tile_style_<?php echo $style;?>" 
								name="tlstyle" 
								value="<?php echo $style;?>" 
								<?php 
								if(isset($existing) && $style==$existing)
									{
									echo "checked";
									}
								else if(!isset($existing) && $check)
									{
									echo "checked";
									$check=false;
									} 
								?>
							/>
						</td>
						<td align="left" valign="middle" >
							<label class="customFieldLabel" for="tile_style_<?php echo $style;?>"><?php echo $lang["tile_".$style];?></label>
						</td>
						<?php
						}?>
				</tr>
			</tbody>
		</table>
		<div class="clearerleft"> </div>
        <?php
        if(allow_tile_colour_change($tile_type))
            {
            foreach($tile_styles[$tile_type] as $style)
                {
                if(allow_tile_colour_change($tile_type, $style))
                    {
                    render_dash_tile_colour_chooser($style, $tile_colour);
                    }
                }
            }
        ?>
	</div>
    <?php
	}

/* 
 * Tile Form Entry
 */
$create=getvalescaped("create",FALSE);
$edit=getvalescaped("edit",FALSE);
$validpage = false;
if($create)
	{
	$tile_type                    = getvalescaped("tltype","");
    $tile_style                   = getvalescaped('tlstyle', "");
	$tile_nostyle                 = getvalescaped("nostyleoptions",FALSE);
	$allusers                     = getvalescaped("all_users",FALSE);
	$url                          = getvalescaped("url","");
	$modifylink                   = getvalescaped("modifylink",FALSE);
	$freetext                     = getvalescaped("freetext",FALSE);
	$notitle                      = getvalescaped("notitle",FALSE);
	$link                         = getvalescaped("link","");
	$title                        = getvalescaped("title","");
    $current_specific_user_groups = (isset($specific_user_groups) ? $specific_user_groups : array());
    $tlsize                       = ('double' === getvalescaped('tlsize', '') ? 'double' : '');

    // Promoted resources can be available for search tiles (srch) and feature collection tiles (fcthm)
    $promoted_resource = (getval('promoted_resource', "") == "true");

    if(!allow_tile_colour_change($tile_type, $tile_style))
        {
        $tile_nostyle = true;
        }

	if($tile_type=="srch")
		{
		$srch=getvalescaped("link","");
		$order_by=getvalescaped("order_by","");
		$sort=getvalescaped("sort","");
		$archive=getvalescaped("archive","");
		$daylimit=getvalescaped("daylimit","");
		$restypes=getvalescaped("restypes","");
		$title=getvalescaped("title","");
		$resource_count=getvalescaped("resource_count",0,TRUE);

        unset($tile_style);

		$link=$srch."&order_by=" . urlencode($order_by) . "&sort=" . urlencode($sort) . "&archive=" . urlencode($archive) . "&daylimit=" . urlencode($daylimit) . "&k=" . urlencode($k) . "&restypes=" . urlencode($restypes);
		$title=preg_replace("/^.*search=/", "", $srch);
		
		if(substr($title,0,11)=="!collection")
			{
			$col= get_collection(preg_replace("/^!collection/", "", $title));
			$promoted_resource = true;
			$title=$col["name"];
			}
		else if(substr($title,0,7)=="!recent")
			{$title=$lang["recent"];}
		else if(substr($title,0,5)=="!last")
			{
			$last = preg_replace("/^!last/", "", $title);
			$title= ($last!="") ? $lang["last"]." ".$last : $lang["recent"];
			}
		else
		{
			$title_node = preg_replace("/^.*search=/", "", $srch);
			$returned_title = array();
			if (count(resolve_nodes_from_string($title_node))!=0)
				{
                $resolved_nodes = resolve_nodes_from_string($title_node);
                $tmp_title      = get_node($resolved_nodes[0], $returned_title);
                $title          = $returned_title['name'];
				}
		}
		
		}

	$pagetitle = $lang["createnewdashtile"];
	$formextra = '<input type="hidden" name="submitdashtile" value="true" />';
	$validpage=true;
	$submittext = $lang["create"];
	}
else if($edit)
	{
	#edit contains the dash_tile record ref
	$tile = get_tile($edit);

	$allusers=$tile["all_users"];
	$url=$tile["url"];
	$link=$tile["link"];
	$title=$tile["title"];
	$resource_count=$tile["resource_count"];
    $current_specific_user_groups = get_tile_user_groups($edit);
	
	#Get field data
	$buildstring = explode('?',$tile["url"]);
    if(isset($buildstring[1])) 
        {
        parse_str(str_replace("&amp;","&",$buildstring[1]),$buildstring);
        }
	
	if(isset($buildstring["tltype"]))
		{
		$tile_type=$buildstring["tltype"];
		$tile_nostyle = isset($buildstring["tlstyle"])? FALSE : TRUE;
		$tile_style=$buildstring["tlstyle"];

        $tile_style_colour = '';
        if(allow_tile_colour_change($tile_type) && isset($buildstring['tlstylecolour']))
            {
            $tile_style_colour = $buildstring['tlstylecolour'];
            }
		}
	else
		{
		$tile_type="";
		$tile_nostyle = true;
		}

	if (!isset($tile_style)) 
		{
		$tile_style = "";
		}
		
	# Show freetext field if the tile style is not analytics
	if ($tile_style != 'analytics') 
    	{
    	$freetext = empty($tile["txt"])? "true" : $tile["txt"];
    	}
    else 
    	{
    	$freetext = false;
    	}
	
	$promoted_resource=isset($buildstring["promimg"])? $buildstring["promimg"] : FALSE;

    $tlsize = (isset($buildstring['tlsize']) && 'double' === $buildstring['tlsize'] ? $buildstring['tlsize'] : '');

	$modifylink = ($tile_type=="ftxt") ? TRUE: FALSE;
	
	$notitle = isset($buildstring["nottitle"])? TRUE : FALSE;

	$pagetitle = $lang["editdashtile"];
	$formextra = '<input type="hidden" name="submitdashtile" value="true" />';
	$formextra .= '<input type="hidden" name="editdashtile" value="'.$tile["ref"].'" />';
	$validpage = true;
	$submittext = $lang["save"];
	}

/* Start Display*/
include "../include/header.php";

if(!$validpage)
	{
	echo "<h2>".$lang["error"]."</h2>";
	echo "<p>".$lang["error-dashactionmissing"]."</p>";
	include "../include/footer.php";
	exit;
	}
?>
<div class="BasicsBox">
<h1><?php echo $pagetitle;render_help_link("user/create-dash-tile");?></h1>
<form id="create_dash" name="create_dash" method="post">
	<input type="hidden" name="tltype" value="<?php echo htmlspecialchars($tile_type)?>" />
	<input type="hidden" name="url" value="<?php echo htmlspecialchars($url); ?>" />
    <?php generateFormToken("create_dash"); ?>

    <div class="Question">
        <label>
        <?php echo $lang["preview"] ?>
        </label>
        <br />
        <div class="HomePanel DashTile">
        <div id="previewdashtile" class="dashtilepreview HomePanelIN HomePanelDynamicDash <?php echo ($dash_tile_shadows)? "TileContentShadow":"";?>">
        </div>
        </div>
        <div class="clearerleft"></div>
    </div>
    
    
	<?php
	echo $formextra;

	if($modifylink)
		{
		?>
		<div class="Question">
			<label for="link"><?php echo $lang["dashtilelink"];?></label> 
			<input type="text" name="link" value="<?php echo htmlspecialchars($link);?>"/>
			<div class="clearerleft"></div>
		</div>
		<?php
		}
	else
		{?>
		<input type="hidden" name="link" id="previewlink" value="<?php echo htmlspecialchars($link);?>" />
		<?php
		}
	if(!$notitle)
		{ ?>
		<div class="Question">
			<label for="title"><?php echo $lang["dashtiletitle"];?></label> 
			<input type="text" id="previewtitle" name="title" value="<?php echo htmlspecialchars(ucfirst ($title));?>"/>
			<div class="clearerleft"></div>
		</div>
		<?php
		}
	else
		{ ?>
		<input type="hidden" name="notitle" value="1" />
		<?php
		}

	if($freetext)
		{ 
		if($freetext=="true")
			{$freetext="";}
		?>
		<div class="Question">
			<label for="freetext"><?php echo $lang["dashtiletext"];?></label> 
			<input type="text" id="previewtext" name="freetext" value="<?php echo htmlspecialchars(ucfirst($freetext));?>"/>
			<div class="clearerleft"></div>
		</div>
		<?php
		}

if('' != $tile_type && $tile_type !== "conf")
    {
    ?>
    <!-- Dash tile size selector -->
    <div class="Question">
        <label for="tlsize"><?php echo $lang['size']; ?></label>
        <select id="DashTileSize" class="stdwidth" name="tlsize" onchange="updateDashTilePreview();">
            <option value=""><?php echo $lang['single_width']; ?></option>
            <option value="double"<?php echo ('double' === $tlsize ? ' selected' : ''); ?>><?php echo $lang['double_width']; ?></option>
        </select>
        <div class="clearerleft"></div>
    </div>
    <?php
    }

	if (!$tile_nostyle)
		{
		if(isset($tile_style))
			{
			tileStyle($tile_type, $tile_style, $tile_style_colour);
			}
		else
			{
			tileStyle($tile_type);
			}
		}

    if($create && 'ftxt' == $tile_type && allow_tile_colour_change($tile_type))
        {
        render_dash_tile_colour_chooser('ftxt', '');
        }

	if($tile_type=="srch")
		{?>
		<div class="Question" id="showresourcecount" >
			<label for="tltype"><?php echo $lang["showresourcecount"];?></label> 
			<table>
				<tbody>
					<tr>
						<td width="10" valign="middle" >
							<input type="checkbox" id="resource_count" name="resource_count" value="1" <?php echo $resource_count? "checked":"";?>/>
						</td>
					</tr>
				</tbody>
			</table>
			<div class="clearerleft"> </div>
		</div>
		<script>
			jQuery(".tlstyle").change(function(){
				checked=jQuery(".tlstyle:checked").val();
				if(checked=="thmbs" || checked=="multi" || checked=="blank") {
					jQuery("#showresourcecount").show();
				}
				else {
					jQuery("#showresourcecount").hide();
				}
			});
		</script>
		<?php
		}

    // Show promoted resource selector
    if($promoted_resource && allowPromotedResources($tile_type) && !hook('replace_promoted_resource_selector'))
        {
        $resources = array();

        if('srch' == $tile_type)
            {
            $search_string = explode('?',$link);
            parse_str(str_replace("&amp;","&",$search_string[1]),$search_string);
            $search = isset($search_string["search"]) ? $search_string["search"] :"";
            $restypes = isset($search_string["restypes"]) ? $search_string["restypes"] : "";
            $order_by= isset($search_string["order_by"]) ? $search_string["order_by"] : "";
            $archive = isset($search_string["archive"]) ? $search_string["archive"] : "";
            $sort = isset($search_string["sort"]) ? $search_string["sort"] : "";
            $resources = do_search($search,$restypes,$order_by,$archive,-1,$sort);
            }
        else if('fcthm' == $tile_type)
            {
            $link_parts = explode('?', $link);
            parse_str(str_replace('&amp;', '&', $link_parts[1]), $link_parts);

            $parent = (isset($link_parts["parent"]) ? (int) validate_collection_parent(array("parent" => (int) $link_parts["parent"])) : 0);
            $parent_col_data = get_collection($parent);
            $parent_col_data = (is_array($parent_col_data) ? $parent_col_data : array());

            $resources = dash_tile_featured_collection_get_resources($parent_col_data, array());
            // The resource manually selected for a category doesn't have to be part of the branch (or any FCs). Add it 
            // to the list of resources as if it is.
            if(
                !empty($parent_col_data)
                && $parent_col_data["thumbnail_selection_method"] == $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["manual"]
                && $parent_col_data["bg_img_resource_ref"] > 0 && get_resource_access($parent_col_data["bg_img_resource_ref"]) == RESOURCE_ACCESS_FULL
            )
                {
                $resources[] = array(
                    "ref" => $parent_col_data["bg_img_resource_ref"],
                    "field{$view_title_field}" => get_data_by_field($parent_col_data["bg_img_resource_ref"], $view_title_field));
                }

            if(!is_numeric($promoted_resource))
                {
                $promoted_resource = dash_tile_featured_collection_get_resources($parent_col_data, array("limit" => 1, "use_thumbnail_selection_method" => true));
                $promoted_resource = (!empty($promoted_resource) ? $promoted_resource[0]["ref"] : 0);
                }
            }
            ?>
        <div class="Question" id="promotedresource">
            <label for="promoted_image"><?php echo $lang['dashtileimage']; ?></label>
            <select class="stdwidth" id="previewimage" name="promoted_image">
            <?php 
            foreach($resources as $resource)
                {
                ?>
                <option value="<?php echo htmlspecialchars($resource["ref"]); ?>"
                    <?php echo $promoted_resource === $resource['ref'] ? 'selected="selected"' : ''; ?>
                ><?php
                    echo str_replace(
                        array('%ref','%title'),
                        array(
                            $resource['ref'],
                            i18n_get_translated($resource['field' . $view_title_field])
                        ),
                        $lang['ref-title']
                    );
                ?></option>
                <?php
                }
                ?>
            </select>
            <div class="clearerleft"> </div>
        </div>
        <script>
        if(!jQuery('#tile_style_thmbs').prop('checked'))
            {
            jQuery('#promotedresource').hide();
            }

        jQuery('.tlstyle').change(function()
            {
                checked = jQuery('.tlstyle:checked').val();

                if(checked == 'thmbs')
                    {
                    jQuery('#promotedresource').show();
                    }
                else
                    {
                    jQuery('#promotedresource').hide();
                    }
            });
        </script>
        <?php
        }
	hook('beforedashtileadmin');
	if(checkPermission_dashadmin())
		{
        ?>
		<div class="Question">
			<label for="tile_audience"><?php echo $lang['who_should_see_dash_tile']; ?></label> 
			<table>
				<tbody>
					<tr>
						<td width="10" valign="middle" >
							<input type="radio" id="all_users_false" name="tile_audience" value="false" <?php echo $allusers ? '' : 'checked'; ?> />
						</td>
						<td align="left" valign="middle" >
							<label class="customFieldLabel" for="all_users_false"><?php echo $lang['dash_tile_audience_me']; ?></label>
						</td>
                        <td width="10" valign="middle" >
                            <input type="radio" id="all_users_true" name="tile_audience" value="true" <?php echo ($allusers && empty($current_specific_user_groups)) ? 'checked' : ''; ?> />
                        </td>
                        <td align="left" valign="middle" >
                            <label class="customFieldLabel" for="all_users_true"><?php echo $lang['dash_tile_audience_all_users']; ?></label>
                        </td>
						<td width="10" valign="middle" >
							<input type="radio" id="dash_tile_audience_user_group" name="tile_audience" value="specific_user_groups" <?php echo ($allusers && !empty($current_specific_user_groups)) ? 'checked' : ''; ?> />
						</td>
						<td align="left" valign="middle" >
							<label class="customFieldLabel" for="dash_tile_audience_user_group"><?php echo $lang['dash_tile_audience_user_group']; ?></label>
						</td>
					</tr>
				</tbody>
			</table>
			<span style='margin-top:10px;float:left;display:none;font-style:italic;' class='FormHelp clearerleft' id='all_userseditchange'><?php echo $lang["dasheditchangeall_users"];?></span>
			<div class="clearerleft"> </div>
			<?php 
			if($edit && $allusers && !$managed_home_dash)
				{ ?>
				<script>
					jQuery("input:radio[name='tile_audience']").change(function(){
						if(jQuery(this).prop("checked") && jQuery(this).val()=='false') {
							jQuery("#all_userseditchange").show();
						} else {
							jQuery("#all_userseditchange").hide();
						}
					});

				</script>
				<?php
				}
			?>
            </div>
		<?php 
        render_user_group_checkbox_select('specific_user_groups', $current_specific_user_groups, 'padding-left: 310px; display: none;');
		}
        ?>
	<div class="QuestionSubmit">
		 <div class="Inline"><input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo $submittext;?>&nbsp;&nbsp;" /></div>
		<div class="clearerleft"> </div>
	</div>
    <script>
    jQuery(document).ready(function() {
        if(jQuery('#dash_tile_audience_user_group').prop('checked'))
            {
            jQuery('#specific_user_groups').show();
            }
    });

    jQuery('input:radio[name="tile_audience"]').change(function() {
        if(jQuery(this).prop('checked') && jQuery(this).val() == 'specific_user_groups')
            {
            jQuery('#specific_user_groups').show();
            }
        else
            {
            jQuery('#specific_user_groups').hide();
            }
    });
    </script>
</form>



<script>
	function updateDashTilePreview() {
		var prevstyle = jQuery(".tlstyle:checked").val();
		var width = 250;
		var height = 180;
		var pretitle = encodeURIComponent(jQuery("#previewtitle").val());
		var pretxt = encodeURIComponent(jQuery("#previewtext").val());
		var prelink= encodeURIComponent(jQuery("#previewlink").val());
		var tile = "&tllink="+prelink+"&tltitle="+pretitle+"&tltxt="+pretxt;
        var tlsize = encodeURIComponent(jQuery('#DashTileSize :selected').val());

        // Some tile types don't have style
        if(typeof prevstyle === 'undefined')
            {
            prevstyle = '<?php echo isset($tile_style) ? $tile_style : ""; ?>';
            }
		<?php
		if($tile_type=="srch")
			{?>	
			var count = jQuery("#resource_count").is(':checked');
			if(count)
				{count=1;}
			else
				{count=0;}
			tile= tile+"&tlrcount="+encodeURIComponent(count);
			<?php
			}

        if($promoted_resource && allowPromotedResources($tile_type))
            {
            ?>
            tile = tile + '&promimg=' + encodeURIComponent(jQuery('#previewimage').val()); 
            <?php
            }

		#Preview URL
		if (empty($url) || strpos($url,"pages/ajax/dash_tile.php")!==FALSE)
			{$previewurl=$baseurl_short."pages/ajax/dash_tile_preview.php";}
		else
			{$previewurl=$baseurl_short.$url;}
		?>

        // Change size if needed:
        jQuery('#previewdashtile').removeClass('DoubleWidthDashTile');
        if('double' == jQuery('#DashTileSize :selected').val()
            || (typeof event !== 'undefined' && event.type == 'change' && 'double' == jQuery(event.target).val())
        )
            {
            jQuery('#previewdashtile').addClass('DoubleWidthDashTile');
            width = 515;
            }
            
		jQuery("#previewdashtile").load("<?php echo $previewurl; ?>?tltype=<?php echo urlencode($tile_type)?>&tlsize=" + tlsize + "&tlstyle="+prevstyle+"&tlwidth="+width+"&tlheight="+height+tile);
	}

	updateDashTilePreview();
	jQuery("#previewtitle").change(updateDashTilePreview);
	jQuery("#previewtext").change(updateDashTilePreview);
	jQuery("#resource_count").change(updateDashTilePreview);
	jQuery(".tlstyle").change(updateDashTilePreview);
    jQuery("#promotedresource").change(updateDashTilePreview);
</script>

</div><!-- End of BasicsBox -->
<?php
include "../include/footer.php";