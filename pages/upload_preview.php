<?php
include "../include/db.php";

include "../include/authenticate.php"; 
if ($disable_upload_preview || (checkperm("F*") && !$custompermshowfile)){exit ("Permission denied.");}
include "../include/image_processing.php";

$ref=getvalescaped("ref","",true);
$status="";
$error = false;
$resource=get_resource_data($ref);
# Not allowed to edit this resource?
if (!get_edit_access($ref,$resource["archive"],false,$resource)) {
		$error=$lang['error-permissiondenied'];
		error_alert($error);
		exit();
        }
        
if($resource["lock_user"] > 0 && $resource["lock_user"] != $userref)
    {
    $error = get_resource_lock_message($resource["lock_user"]);
    http_response_code(403);
    exit($error);
    }

# fetch the current search 
$search=getvalescaped("search","");
$order_by=getvalescaped("order_by","relevance");
$offset=getvalescaped("offset",0,true);
$restypes=getvalescaped("restypes","");
$starsearch=getvalescaped("starsearch","");
if (strpos($search,"!")!==false) {$restypes="";}
$archive=getvalescaped("archive","");
$per_page=getvalescaped("per_page",0,true);
$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);
$previewresource=getval("previewref",0,true);
$previewresourcealt=getval("previewalt",-1,true);

$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);
$curpos=getvalescaped("curpos","");
$go=getval("go","");

$urlparams= array(
    'ref'				        => $ref,
    'search'			        => $search,
    'order_by'			        => $order_by,
    'offset'			        => $offset,
    'restypes'			        => $restypes,
    'starsearch'		        => $starsearch,
    'archive'			        => $archive,
    'default_sort_direction'    => $default_sort_direction,
    'sort'				        => $sort,
    'curpos'			        => $curpos,
    'refreshcollectionframe'    => 'true'
);


#handle posts
if (array_key_exists("userfile",$_FILES) && enforcePostRequest(false))
    {
	$status=upload_preview($ref);
    if($status !== false)
        {
        redirect(generateurl($baseurl . "/pages/view.php", $urlparams));
        exit();
        }
    $error = true;
    }
elseif($previewresource > 0 && enforcePostRequest(false))
    {
    $status=replace_preview_from_resource($ref,$previewresource,$previewresourcealt);
    if($status !== false)
        {
        redirect(generateurl($baseurl . "/pages/view.php", $urlparams));
        exit();
        }
    $error = true;
    }
    
include "../include/header.php";
?>

<div class="BasicsBox"> 
<h1><?php echo $lang["uploadpreview"];render_help_link("user/edit-resource-preview");?></h1>
<p><?php echo text("introtext")?></p>
<script language="JavaScript">
// Check allowed extensions:
function check(filename) {
	var allowedExtensions='jpg,jpeg';
	var ext = filename.substr(filename.lastIndexOf('.'));
	ext =ext.substr(1).toLowerCase();
	if (allowedExtensions.indexOf(ext)==-1){ return false;} else {return true;}
}
</script>
<form method="post" class="form" enctype="multipart/form-data" action="upload_preview.php">
<?php generateFormToken("upload_preview"); ?>
<input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref)?>">
<br/>
<?php if ($status!="") { ?><?php echo $status?><?php } ?>
<div id="invalid" <?php if (!$error) {echo "style=\"display:none;\"";} ?> class="FormIncorrect"><?php echo str_replace_formatted_placeholder("%extensions", "JPG", $lang['invalidextension_mustbe-extensions']); ?></div>
<div class="Question">
<label for="userfile"><?php echo $lang["clickbrowsetolocate"]?></label>
<input type=file name=userfile id=userfile>
<div class="clearerleft"> </div>
</div>

<div class="QuestionSubmit">
<label for="buttons"> </label>			
<input name="save" type="submit" onclick="if (!check(this.form.userfile.value)){document.getElementById('invalid').style.display='block';return false;}else {document.getElementById('invalid').style.display='none';}" value="&nbsp;&nbsp;<?php echo $lang["upload_file"]?>&nbsp;&nbsp;" />
</div>

<p><a onClick="return ModalLoad(this,true);" href="view.php?ref=<?php echo urlencode($ref)?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a></p>

</form>
</div>

<?php
include "../include/footer.php";
?>
