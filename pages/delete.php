<?php
include "../include/db.php";

include "../include/authenticate.php";

$ref=getvalescaped("ref","",true);

if ((isset($allow_resource_deletion) and !$allow_resource_deletion) or (checkperm('D') and !hook('check_single_delete'))){
	include "../include/header.php";
	echo "Error: Resource deletion is disabled.";
	exit;
} else {
$resource=get_resource_data($ref);

# fetch the current search 
$search=getvalescaped("search","");
$order_by=getvalescaped("order_by","relevance");
$offset=getvalescaped("offset",0,true);
$restypes=getvalescaped("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}
$archive=getvalescaped("archive","");

$modal=(getval("modal","")=="true");
$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);
$curpos=getvalescaped("curpos","");

$error="";

$urlparams= array(
    'resource' => $ref,
    'ref' => $ref,
    'search' => $search,
    'order_by' => $order_by,
    'offset' => $offset,
    'restypes' => $restypes,
    'archive' => $archive,
    'default_sort_direction' => $default_sort_direction,
    'sort' => $sort,
    'curpos' => $curpos,
    "modal" => ($modal ? "true" : "")
);

# Not allowed to edit this resource? They shouldn't have been able to get here.
if (!get_edit_access($ref,$resource["archive"],false,$resource)) {exit ("Permission denied.");}

if($resource["lock_user"] > 0 && $resource["lock_user"] != $userref)
    {
    $error = get_resource_lock_message($resource["lock_user"]);
    error_alert($error,!$modal);
    exit();
    }
    
hook("pageevaluation");

if (getval("save","")!="" && enforcePostRequest(getval("ajax", false)))
	{
	if ($delete_requires_password && hash('sha256', md5('RS' . $username . getvalescaped('password', ''))) != $userpassword)
		{
		$error=$lang["wrongpassword"];
		}
	else
		{
		hook("custompredeleteresource");

		delete_resource($ref);
		
		hook("custompostdeleteresource");
		
		echo "<script>
		ModalLoad('" . $baseurl_short . "pages/done.php?text=deleted&refreshcollection=true&search=" . urlencode($search) . "&offset=" . urlencode($offset) . "&order_by=" . urlencode($order_by) . "&sort=" . urlencode($sort) . "&archive=" . urlencode($archive) . "',true);
		</script>";
		exit();
		}
	}
include "../include/header.php";

if (isset($resource['is_transcoding']) && $resource['is_transcoding']==1)
	{
?>
<div class="BasicsBox"> 
  <h2>&nbsp;</h2>
  <h1><?php echo $lang["deleteresource"];render_help_link("user/deleting-resources");?></h1>
  <p class="FormIncorrect"><?php echo $lang["cantdeletewhiletranscoding"]?></p>
</div>
<?php	
	}
else
	{
?>
<div class="BasicsBox"> 
<?php
if (getval("context",false) == 'Modal'){$previous_page_modal = true;}
else {$previous_page_modal = false;}
if(!$modal)
    {
    ?>
    <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo generateurl($baseurl_short . "pages/view.php",$urlparams);?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a>
    <?php
    }
    elseif ($previous_page_modal)
    {
    ?>
    <a onClick="return ModalLoad(this,true);" href="<?php echo generateurl($baseurl_short . "pages/view.php",$urlparams);?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a>
    <?php
    }
?>
</div>

<div class="BasicsBox"> 
	
  <h1><?php echo $lang["deleteresource"];render_help_link("user/deleting-resources");?></h1>
  <p><?php if($delete_requires_password){text("introtext");}else{echo $lang["delete__nopassword"];} ?></p>
  
  <?php if ($resource["archive"]==3) { ?><p><strong><?php echo $lang["finaldeletion"] ?></strong></p><?php } ?>
  
	<form method="post" action="<?php echo $baseurl_short?>pages/delete.php?ref=<?php echo urlencode($ref) ?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset) ?>&order_by=<?php echo urlencode($order_by) ?>&sort=<?php echo urlencode($sort) ?>&archive=<?php echo urlencode($archive) ?>&amp;restypes=<?php echo urlencode($restypes); ?>">
	<input type=hidden name=ref value="<?php echo urlencode($ref) ?>">
    <?php generateFormToken("delete_resource"); ?>
	<div class="Question">
	<label><?php echo $lang["resourceid"]?></label>
	<div class="Fixed"><?php echo urlencode($ref) ?></div>
	<div class="clearerleft"> </div>
	</div>
	
	<?php if ($delete_requires_password) { ?>
	<div class="Question">
	<label for="password"><?php echo $lang["yourpassword"]?></label>
	<input type=password class="shrtwidth" name="password" id="password" />
	<div class="clearerleft"> </div>
	<?php if ($error!="") { ?><div class="FormError">!! <?php echo htmlspecialchars($error) ?> !!</div><?php } ?>
	</div>
	<?php }
	
	$cancelparams = array();

	$cancelparams["ref"] 		= $ref;
	$cancelparams["search"] 	= $search;
	$cancelparams["offset"] 	= $offset;
	$cancelparams["order_by"] 	= $order_by;
	$cancelparams["sort"] 		= $sort;
	$cancelparams["archive"] 	= $archive;
	
	$cancelurl = generateURL($baseurl_short . "pages/view.php",$cancelparams);
	?>
	
	<div class="QuestionSubmit">
	<input name="save" type="hidden" value="true" />
	<label for="buttons"> </label>			
	<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["deleteresource"]?>&nbsp;&nbsp;"  onclick="return ModalPost(this.form,true);"/>		
	<input name="cancel" type="button" value="&nbsp;&nbsp;<?php echo $lang["cancel"]?>&nbsp;&nbsp;"  onclick='return CentralSpaceLoad("<?php echo $cancelurl ?>",true);'/>
	</div>



	</form>
	
</div>

<?php
	}

} // end of block to prevent deletion if disabled
	
include "../include/footer.php";

?>
