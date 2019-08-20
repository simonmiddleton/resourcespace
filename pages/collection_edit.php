<?php
include "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php";
if(checkperm("b"))
    {exit ("Permission denied.");}
include_once "../include/collections_functions.php";
include "../include/resource_functions.php";
include "../include/search_functions.php"; 

$ref=getvalescaped("ref","",true);
$copycollectionremoveall=getvalescaped("copycollectionremoveall","");
$offset=getval("offset",0);
$find=getvalescaped("find","");
$col_order_by=getvalescaped("col_order_by","name");
$sort=getval("sort","ASC");

# Does this user have edit access to collections? Variable will be found in functions below.  
$multi_edit=allow_multi_edit($ref);

# Check access
if (!collection_writeable($ref)) 
	{exit($lang["no_access_to_collection"]);}


# Fetch collection data
$collection=get_collection($ref);
if ($collection===false) 
	{
	$error=$lang['error-collectionnotfound'];
	error_alert($error);
	exit();
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
    $coldata["name"]            = getval("name","");
    $coldata["allow_changes"]   = getval("allow_changes","") != "" ? 1 : 0;
    //$public = getvalescaped('public', 0, true);
    $coldata["public"]          = getval('public', 0, true);
    $coldata["keywords"]        = getval("keywords","");
    $coldata["description"]     = getval("description","");
    hook('saveadditionalfields');

    for($n=1;$n<=$theme_category_levels;$n++)
        {
        if ($n==1)
            {
            $themeindex = "";
            }
        else
            {
            $themeindex = $n;
            }
        $themename = getval("theme$themeindex","");
        $coldata["theme" . $themeindex] = $themename;
        
        if (getval("newtheme$themeindex","")!="")
            {
            $coldata["theme". $themeindex] = trim(getval("newtheme$themeindex",""));
            }    
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
	save_collection($ref, $coldata);
	if (getval("redirect","")!="")
		{
		if (getval("addlevel","")=="yes"){
			redirect ($baseurl_short."pages/collection_edit.php?ref=".$ref."&addlevel=yes");
			}		
		else if ((getval("theme","")!="") || (getval("newtheme","")!=""))
			{
			redirect ($baseurl_short."pages/themes.php?manage=true");
			}
		else
			{
			redirect($baseurl_short . 'pages/collection_manage.php?offset=' . $offset . '&col_order_by=' . $col_order_by . '&sort=' . $sort . '&find=' . urlencode($find) . '&reload=true');
			}
		}
	else
		{
		# No redirect, we stay on this page. Reload the collection info.
		$collection=get_collection($ref);
        }
	}
	
include "../include/header.php";
?>
<div class="BasicsBox">
<h1><?php echo $lang["editcollection"]?></h1>
<p><?php echo text("introtext");render_help_link("user/edit-collection");?></p>
<form method=post id="collectionform" action="<?php echo $baseurl_short?>pages/collection_edit.php">
    <?php generateFormToken("collectionform"); ?>
	<input type="hidden" name="redirect" id="redirect" value="yes" >
	<input type=hidden name=ref value="<?php echo htmlspecialchars($ref) ?>">
	<input type=hidden name="submitted" value="true">
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
			<select id="public" name="public" class="shrtwidth" onchange="document.getElementById('redirect').value='';document.getElementById('collectionform').submit();">
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
	if ($collection["public"]==0 || (($collection['public']==1 && !$themes_in_my_collections && $collection['theme']=='') || ($collection['public']==1 && $themes_in_my_collections) )) 
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
	
	if ($collection['public']==1)
		{
		include __DIR__ . '/../include/collection_theme_select.php';
		}
		
	if (checkperm("h") && $collection['public']==1 && !$home_dash)
		{
		# Option to publish to the home page.
		?>
		<div class="Question">
		<label for="allow_changes"><?php echo $lang["theme_home_promote"]?></label>
		<input type="checkbox" id="home_page_publish" name="home_page_publish" value="1" <?php if ($collection["home_page_publish"]==1) { ?>checked<?php } ?> onClick="document.getElementById('redirect').value='';document.getElementById('collectionform').submit();">
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

	if (isset($collection['savedsearch'])&& $collection['savedsearch']==null)
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
?>
