<?php
include "../include/db.php";

include "../include/authenticate.php";
if(checkperm("b") || $system_read_only)
    {exit ("Permission denied.");}

$ref=getvalescaped("ref","",true);
$copycollectionremoveall=getvalescaped("copycollectionremoveall","");
$offset=getval("offset",0,true);
$find=getvalescaped("find","");
$col_order_by=getvalescaped("col_order_by","name");
$sort=getval("sort","ASC");
$modal=getval("modal","")=="true";
$redirection_endpoint = trim(urldecode(getval("redirection_endpoint", "")));
$redirect = getval("redirect", "") != "";


# Does this user have edit access to collections? Variable will be found in functions below.  
$multi_edit=allow_multi_edit($ref);

# Check access
if (!collection_writeable($ref)) 
	{exit($lang["no_access_to_collection"]);}

$collection=get_collection($ref);

if ($collection===false) 
	{
	$error=$lang['error-collectionnotfound'];
	error_alert($error);
	exit();
	}

if(!in_array($collection["type"], array(COLLECTION_TYPE_STANDARD, COLLECTION_TYPE_PUBLIC, COLLECTION_TYPE_FEATURED)))
    {
    exit(error_alert($lang["error-permissiondenied"], true, 401));
    }
else if($collection["type"] == COLLECTION_TYPE_FEATURED && !featured_collection_check_access_control((int) $collection["ref"]))
    {
    exit(error_alert($lang["error-permissiondenied"], true, 403));
    }

$resources=do_search("!collection".$ref);
$colcount=count($resources);

# Collection copy functionality
$copy=getvalescaped("copy","");
if ($copy!="")
	{
	copy_collection($copy,$ref,$copycollectionremoveall!="");
	refresh_collection_frame();
	}

if (getval("submitted","")!="" && enforcePostRequest(false))
	{
	# Save collection data
    $coldata["name"] = getval("name","");
    $coldata["allow_changes"] = getval("allow_changes","") != "" ? 1 : 0;
    $coldata["public"] = getval('public', 0, true);
    $coldata["keywords"] = getval("keywords","");
    $coldata["description"] = getval("description","");
    $coldata["result_limit"] = getval("result_limit",0,true);
    $coldata["users"] = getval("users","");
    $coldata["deleteall"] = getval("deleteall","") != "";

    if($collection["public"] == 1 && getval("update_parent", "") == "true")
        {
        // Prepare coldata for save_collection() for posted featured collections (if any changes have been made)
        $current_branch_path = get_featured_collection_category_branch_by_leaf((int) $ref, array());
        $featured_collections_changes = process_posted_featured_collection_categories(0, $current_branch_path);
        if(!empty($featured_collections_changes))
            {
            $coldata["featured_collections_changes"] = $featured_collections_changes;
            }
        }

    // User selected a background image
    if($enable_themes && $themes_simple_images && $collection["type"] == COLLECTION_TYPE_FEATURED && checkperm("h"))
        {
        $thumbnail_selection_method = getval("thumbnail_selection_method", $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["no_image"], true);
        if(in_array($thumbnail_selection_method, $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS))
            {
            $coldata["featured_collections_changes"]["thumbnail_selection_method"] = $thumbnail_selection_method;

            $bg_img_resource_ref = getval("bg_img_resource_ref", 0, true);
            if(
                $thumbnail_selection_method == $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["manual"]
                && $bg_img_resource_ref > 0 && get_resource_access($bg_img_resource_ref) == RESOURCE_ACCESS_FULL
            )
                {
                $coldata["bg_img_resource_ref"] = $bg_img_resource_ref;
                }
            // If invalid bg_img_resource_ref or no full access to resource, then don't submit the change
            else if($thumbnail_selection_method == $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["manual"])
                {
                $reset_thumbnail_selection_method = (isset($collection['thumbnail_selection_method']) ? $collection['thumbnail_selection_method'] : $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["no_image"]);
                $coldata['featured_collections_changes']['thumbnail_selection_method'] = $reset_thumbnail_selection_method;
                $coldata['bg_img_resource_ref'] = 0;
                }
            }
        }
    elseif($collection["type"] == COLLECTION_TYPE_FEATURED && checkperm("h"))
        {
        $coldata['featured_collections_changes']['name'] = "";
        }

    if (checkperm("h"))
        {
        $coldata["home_page_publish"]   = (getval("home_page_publish","") != "") ? "1" : "0";
        $coldata["home_page_text"]      = getval("home_page_text","");
        if (getval("home_page_image","") != "")
            {
            $coldata["home_page_image"] = getval("home_page_image","");
            }
        }

    hook('saveadditionalfields'); # keep it close to save_collection(). Plugins should access any $coldata at this point

    if(
        (
            $coldata["public"] == 0
            || (
                isset($coldata["featured_collections_changes"]["update_parent"])
                && $coldata["featured_collections_changes"]["update_parent"] == 0
                && getval("force_featured_collection_type", "") != "true"
            )
        )
        && is_featured_collection_category_by_children($collection["ref"]))
        {
        $error = $lang["error_save_not_allowed_fc_has_children"];
        }

    if(!isset($error))
        {
        save_collection($ref, $coldata);

        if($redirect)
            {
            if($redirection_endpoint == "")
                {
                $redirection_endpoint = generateURL(
                    "{$baseurl_short}pages/collection_manage.php",
                    array(
                        "offset" => $offset,
                        "col_order_by" => $col_order_by,
                        "sort" => $sort,
                        "find" => $find,
                        "reload" => "true",
                    ));
                }
            if($modal)
                {
                ?>
                <script>
                ModalClose();
                CentralSpaceLoad('<?php echo htmlspecialchars($redirection_endpoint); ?>');
                </script>
                <?php
                }
            else
                {
                redirect($redirection_endpoint);
                }
            exit();
            }

        # No redirect, we stay on this page. Reload the collection info.
        $collection = get_collection($ref);
        }
    }

$form_action = generateURL("{$baseurl_short}pages/collection_edit.php", array("ref" => $collection["ref"]));
include "../include/header.php";
?>
<div class="BasicsBox">
<?php
if(isset($error))
    {
    render_top_page_error_style($error);
    }
?>
<h1><?php echo $lang["editcollection"]; render_help_link("user/edit-collection"); ?></h1>
<p><?php echo text("introtext"); ?></p>
<form method=post id="collectionform" action="<?php echo $form_action; ?>" onsubmit="return <?php echo ($modal ? "Modal" : "CentralSpace") ?>Post(this, false);">
    <?php generateFormToken("collectionform"); ?>
    <input type="hidden" name="modal" value="<?php echo $modal ? "true" : "false" ?>">
    <input type="hidden" name="redirection_endpoint" id="redirection_endpoint" value="<?php echo urlencode($redirection_endpoint); ?>">
	<input type="hidden" name="redirect" id="redirect" value="yes" >
	<input type=hidden name="submitted" value="true">
    <input type=hidden name="update_parent" value="false">
	<div class="Question">
		<label for="name"><?php echo $lang["name"]?></label>
		<input type=text class="stdwidth" name="name" id="name" value="<?php echo htmlspecialchars($collection["name"]) ?>" maxlength="100" <?php if ($collection["cant_delete"]==1) { ?>readonly=true<?php } ?>>
		<div class="clearerleft"> </div>
	</div>

	<?php hook('additionalfields');?>

    <div class="Question">
        <label for="description"><?php echo $lang["collection_description"]?></label>
        <textarea class="stdwidth" rows="4" name="description" id="description"><?php echo htmlspecialchars($collection["description"])?></textarea>
        <div class="clearerleft"> </div>
    </div>

	<div class="Question">
		<label for="keywords"><?php echo $lang["relatedkeywords"]?></label>
		<textarea class="stdwidth" rows="3" name="keywords" id="keywords" <?php if ($collection["cant_delete"]==1) { ?>readonly=true<?php } ?>><?php echo htmlspecialchars($collection["keywords"])?></textarea>
		<div class="clearerleft"> </div>
	</div>

	<div class="Question">
		<label><?php echo $lang["id"]?></label>
		<div class="Fixed"><?php echo htmlspecialchars($collection["ref"]) ?></div>
		<div class="clearerleft"> </div>
	</div>

	<?php 
	if ($collection["savedsearch"]!="") 
	{ 
	$result_limit=sql_value("select result_limit value from collection_savedsearch where collection='$ref'","");	
	?>
	<div class="Question">
		<label for="name"><?php echo $lang["smart_collection_result_limit"] ?></label>
		<input type=text class="stdwidth" name="result_limit" id="result_limit" value="<?php echo htmlspecialchars($result_limit) ?>" />
		<div class="clearerleft"> </div>
	</div>
	<?php 
	} ?>

	<div class="Question">
		<label for="public"><?php echo $lang["access"]?></label>
		<?php 
		if ($collection["cant_delete"]==1) 
			{ 
			# This is a user's My Collection, which cannot be made public or turned into a theme. Display a warning.
			?>
			<input type="hidden" id="public" name="public" value="0">
			<div class="Fixed"><?php echo $lang["mycollection_notpublic"] ?></div>
			<?php 
			} 
		else 
			{ ?>
			<select id="public" name="public" class="stdwidth" onchange="document.getElementById('redirect').value='';<?php echo ($modal ? "Modal" : "CentralSpace") ?>Post(document.getElementById('collectionform'));">
				<option value="0" <?php if ($collection["public"]!=1) {?>selected<?php } ?>><?php echo $lang["private"]?></option>
				<?php 
				if ($collection["cant_delete"]!=1 && ($enable_public_collections || checkperm("h"))) 
					{ ?>
					<option value="1" <?php if ($collection["public"]==1) {?>selected<?php } ?>><?php echo $lang["public"]?></option>
					<?php 
					} ?>
			</select>
			<?php 
			} ?>
		<div class="clearerleft"> </div>
	</div>
	<?php
    if(
        $collection["public"] == 0
        || (
            ($collection['type'] == COLLECTION_TYPE_PUBLIC && !$themes_in_my_collections)
            || ($collection['type'] == COLLECTION_TYPE_FEATURED && $themes_in_my_collections)
        )
    )
		{
		if (!hook("replaceuserselect"))
			{?>
			<div class="Question">
				<label for="users"><?php echo $lang["attachedusers"]?></label>
				<?php $userstring=htmlspecialchars($collection["users"]);
				
				if($attach_user_smart_groups)
					{
					if($userstring!='')
						{
						$userstring.=",";
						}
					$userstring.=htmlspecialchars($collection["groups"]);
					}
					
				include "../include/user_select.php"; ?>
				<div class="clearerleft"> </div>
			</div>
			<?php 
			} /* end hook replaceuserselect */
		} 
	
    if($enable_themes && $collection["public"] == 1 && checkperm("h"))
        {
        render_featured_collection_category_selector(
            0,
            array(
                "collection" => $collection,
                "depth" => 0,
                "current_branch_path" => get_featured_collection_category_branch_by_leaf((int) $collection["ref"], array()),
                "modal" => $modal,
            ));

        if($themes_simple_images && $collection["type"] == COLLECTION_TYPE_FEATURED)
            {
            $configurable_options = array(
                $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["no_image"] => $lang["select"],
                $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"] => $lang["background_most_popular_image"],
                $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_images"] => str_replace("%n", $theme_images_number, $lang["background_most_popular_images"]),
                $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["manual"] => $lang["background_manual_selection"],
            );

            render_dropdown_question(
                $lang["background_image"],
                "thumbnail_selection_method",
                $configurable_options,
                $collection["thumbnail_selection_method"],
                'class="stdwidth"',
                array(
                    "onchange" => "toggle_fc_bg_image_txt_input(this, " . $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["manual"] . ");",
                ));

            $display_bg_img_ref = ($collection["thumbnail_selection_method"] == $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["manual"] && $collection["bg_img_resource_ref"] > 0);
            $current_bg_img_ref = ($display_bg_img_ref ? $collection["bg_img_resource_ref"] : "");
            render_text_question(
                $lang["background_manual_selection_resource_label"],
                "bg_img_resource_ref",
                "",
                true,
                'class="stdwidth"',
                $current_bg_img_ref,
                array(
                    "div_class" => ($display_bg_img_ref ? array() : array("DisplayNone")),
                ));
            }
        }
		
	if (checkperm("h") && $collection['public']==1 && !$home_dash)
		{
		# Option to publish to the home page.
		?>
		<div class="Question">
		<label for="allow_changes"><?php echo $lang["theme_home_promote"]?></label>
		<input type="checkbox" id="home_page_publish" name="home_page_publish" value="1" <?php if ($collection["home_page_publish"]==1) { ?>checked<?php } ?> onClick="document.getElementById('redirect').value='';<?php echo ($modal ? "Modal" : "CentralSpace") ?>Post(document.getElementById('collectionform'));">
		<div class="clearerleft"> </div>
		</div>
		<?php
		if ($collection["home_page_publish"]&&!hook("hidehomepagepublishoptions"))
			{
			# Option ticked - collect extra data
			?>
			<div class="Question">
			<label for="home_page_text"><?php echo $lang["theme_home_page_text"]?></label>
			<textarea class="stdwidth" rows="3" name="home_page_text" id="home_page_text"><?php echo htmlspecialchars($collection["home_page_text"]==""?$collection["name"]:$collection["home_page_text"])?></textarea>
			<div class="clearerleft"> </div>
			</div>
			<div class="Question">
			<label for="home_page_image">
			<?php echo $lang["theme_home_page_image"]?></label>
			<select class="stdwidth" name="home_page_image" id="home_page_image">
			<?php foreach ($resources as $resource)
				{
				?>
				<option value="<?php echo htmlspecialchars($resource["ref"]) ?>" <?php if ($resource["ref"]==$collection["home_page_image"]) { ?>selected<?php } ?>><?php echo str_replace(array("%ref", "%title"), array($resource["ref"], i18n_get_translated($resource["field" . $view_title_field])), $lang["ref-title"]) ?></option>
				<?php
				}
			?>
			</select>
			<div class="clearerleft"> </div>
			</div>		
			<?php hook("morehomepagepublishoptions");
			}
		}

	if (!isset($collection['savedsearch']) || $collection['savedsearch']==null)
		{
		# disallowing share breaks smart collections 
		?>
		<div class="Question">
		<label for="allow_changes"><?php echo $lang["allowothersaddremove"]?></label>
		<input type="checkbox" id="allow_changes" name="allow_changes" <?php if ($collection["allow_changes"]==1) { ?>checked<?php } ?>>
		<div class="clearerleft"> </div>
		</div>
		<?php 
		} 
	else 
		{ 
		# allow changes by default
		?>
		<input type=hidden id="allow_changes" name="allow_changes" value="checked">
		<?php 
		}
    
	hook('additionalfields2');
    hook('colleditformbottom');
    
    if (file_exists("plugins/collection_edit.php"))
        {
        include "plugins/collection_edit.php";
        }
    ?>
	<div class="QuestionSubmit">
		<label for="buttons"> </label>			
		<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
	</div>
</form>
</div>
<?php
if(getval("reload","") == "true" && getval("ajax","") != "")
    {
    refresh_collection_frame();
    }    
    
include "../include/footer.php";