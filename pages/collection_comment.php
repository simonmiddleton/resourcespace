<?php
include "../include/db.php";

# External access support (authenticate only if no key provided, or if invalid access key provided)
$k=getval("k","");if (($k=="") || (!check_access_key(getval("ref","",true),$k))) {include "../include/authenticate.php";}

$ref=getval("ref","",true);
$collection=getval("collection","",true);

# Fetch collection data
$cinfo=get_collection($collection);
if($cinfo === false)
    {
    exit($lang['error-collectionnotfound']);
    }

$commentdata=get_collection_resource_comment($ref,$collection);
if($commentdata === false)
    {
    exit($lang['resourcenotfound']);
    }
$comment=$commentdata["comment"];
$rating=$commentdata["rating"];

# Check access
if (!collection_readable($collection)) {exit("Access denied.");}

if (getval("submitted","")!="" && enforcePostRequest(false))
	{
	# Save comment
	$comment=trim(getval("comment",""));
	$rating=trim(getval("rating",""));
	# Clear cache for immediate display of thumbnail change.
	clear_query_cache("themeimage");
	save_collection_resource_comment($ref,$collection,$comment,$rating);
	if ($k=="")
		{
		redirect ($baseurl_short."pages/search.php?refreshcollectionframe=true&search=!collection" . $collection);
		}
	else
		{
		# Stay on this page for external access users (no access to search)
		refresh_collection_frame();
		}
	}


include "../include/header.php";
?>
<div class="BasicsBox">
<h1><?php echo $lang["collectioncomments"]?></h1>
<p><?php echo $lang["collectioncommentsinfo"];render_help_link("user/resource_commenting");?></p>
<?php 
$imagepath = get_resource_path($ref,true,"col",false,"jpg");
$imageurl = get_resource_path($ref,false,"col",false,"jpg");
if (file_exists($imagepath)){?>
<div class="Question">
<label for="image"><?php echo $lang["preview"]?></label><img src="<?php echo $imageurl?>?nc=<?php echo time()?>" alt="" class="Picture" />
<div class="clearerleft"> </div>
</div>
<?php } ?>

<?php if (!hook("replacecollectioncommentform")) { ?>

<form method="post" action="<?php echo $baseurl_short?>pages/collection_comment.php"  onSubmit="return CentralSpacePost(this, true, false, false);">
    <?php generateFormToken("collection_comment"); ?>
<input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref) ?>">
<input type="hidden" name="k" value="<?php echo htmlspecialchars($k) ?>">
<input type="hidden" name="collection" value="<?php echo htmlspecialchars($collection) ?>">
<input type=hidden name="submitted" value="true">
<div class="Question">
<label for="name"><?php echo $lang["comment"]?></label><textarea class="stdwidth" style="width:450px;" rows=20 cols=80 name="comment" id="comment"><?php echo htmlspecialchars($comment)?></textarea>
<div class="clearerleft"> </div>
</div>

<div class="Question">
<label for="name"><?php echo $lang["rating"]?></label><select class="stdwidth" name="rating">
<option value="" <?php if ($rating=="") { ?>selected<?php } ?>></option>
<?php for ($n=1;$n<=5;$n++) { ?>
<option value="<?php echo $n?>" <?php if ($rating==$n) { ?>selected<?php } ?>><?php echo str_pad("",$n,"*")?></option>
<?php } ?>
</select>
<div class="clearerleft"> </div>
</div>
<?php
if($cinfo["type"] == COLLECTION_TYPE_FEATURED && checkperm("h"))
    {
    ?>
    <div class="Question">
    <label for="use_as_theme_thumbnail"><?php echo $lang["useasthemethumbnail"]?></label>
    <input name="use_as_theme_thumbnail" id="use_as_theme_thumbnail" type="checkbox" value="yes" <?php if ($commentdata["use_as_theme_thumbnail"]==1) { ?>checked<?php } ?>>
    <div class="clearerleft"> </div>
    </div>
    <?php
    }
    ?>
<div class="QuestionSubmit">
<label for="buttons"> </label>			
<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
</div>
</form>

<?php } ?> <!--End Replacecollectioncommentform hook-->

</div>

<?php		
include "../include/footer.php";

