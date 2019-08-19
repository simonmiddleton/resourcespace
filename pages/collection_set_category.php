<?php
include "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php"; 
if(checkperm("b") || !checkperm("h") || !$enable_themes) {exit ("Permission denied.");} // Must have collections functionality and permission to publish featured collections
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

if (getval("submitted","")!="" && enforcePostRequest(false))
	{
	$categories  = array();
	for($n=0;$n<$theme_category_levels;$n++)
		{
		if ($n==0){$themeindex = "";} else {$themeindex = $n+1;}
		$categories[$n]=getvalescaped("theme$themeindex","");
		if (getval("newtheme$themeindex","") != "")
			{
			$categories[$n]=trim(getvalescaped("newtheme$themeindex",""));
			}
		}
	collection_set_themes($ref, $categories);
	if (getval("redirect","")!="")
		{
		if (getval("addlevel","")=="yes"){
			redirect ($baseurl_short."pages/collection_set_category.php?ref=".$ref."&addlevel=yes");
			}
		else
			{
			redirect($baseurl_short . 'pages/search.php?search=!collection' . $ref);
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
<h1><?php echo $lang["collection_set_theme_category_title"];render_help_link("user/themes-public-collections");?></h1>
<p><?php echo text("introtext")?></p>
<form method=post id="collectionform" action="<?php echo $baseurl_short?>pages/collection_set_category.php">
    <?php generateFormToken("collectionform"); ?>
	<input type=hidden name=ref value="<?php echo htmlspecialchars($ref) ?>">
	<input type="hidden" name="redirect" id="redirect" value="yes" >
	<input type=hidden name="submitted" value="true">
	<div class="Question">
		<label for="name"><?php echo $lang["collection"]?></label>
		<div class="Fixed"><?php echo htmlspecialchars(i18n_get_collection_name($collection, $index="name")); ?></div >
		<div class="clearerleft"> </div>
	</div>
	<?php
	
	include __DIR__ . '/../include/collection_theme_select.php';
	?>	
	
	<div class="QuestionSubmit">
		<label for="buttons"> </label>			
		<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
	</div>
</form>
</div>

<?php		
include "../include/footer.php";
?>
