<?php
include "../include/db.php";

include "../include/authenticate.php";

$ref=getvalescaped("ref","",true);
$alt=getvalescaped("alternative","",true);

$search=getvalescaped("search","");
$offset=getvalescaped("offset","",true);
$order_by=getvalescaped("order_by","");
$archive=getvalescaped("archive","",true);
$restypes=getvalescaped("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}
$starsearch=getvalescaped("starsearch","");
$modal = (getval("modal", "") == "true");

$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);
$curpos=getvalescaped("curpos","");
$go=getval("go","");

$urlparams= array(
    'resource' => $ref,
    'ref' => $ref,
    'search' => $search,
    'order_by' => $order_by,
    'offset' => $offset,
    'restypes' => $restypes,
    'starsearch' => $starsearch,
    'archive' => $archive,
    'default_sort_direction' => $default_sort_direction,
    'sort' => $sort,
    'curpos' => $curpos,
    "modal" => ($modal ? "true" : ""),
);

# Fetch resource data.
$resource=get_resource_data($ref);

$editaccess = get_edit_access($ref,$resource["archive"], false,$resource);

# Not allowed to edit this resource?
if (!($editaccess || checkperm('A')) && $ref>0) {exit ("Permission denied.");}

if($resource["lock_user"] > 0 && $resource["lock_user"] != $userref)
    {
    $error = get_resource_lock_message($resource["lock_user"]);
    http_response_code(403);
    exit($error);
    }

hook("pageevaluation");

# Handle deleting a file
if (getval("filedelete","")!="" && enforcePostRequest(getval("ajax", false)))
	{
	$filedelete=explode(',',getvalescaped('filedelete',''));
	foreach ($filedelete as $filedel){
		if (is_numeric($filedel) && $filedel!='on'){// safety checks
			delete_alternative_file($ref,$filedel);
		}
	}
	}

$alt_order_by="";$alt_sort="";
if ($alt_types_organize){$alt_order_by="alt_type";$alt_sort="asc";} 
$files=get_alternative_files($ref,$alt_order_by,$alt_sort);

include "../include/header.php";
?>
<script type="text/javascript">
function clickDelete(){
	var files = [];
    jQuery('#altlistitems input:checked').not("#toggleall").each(function() {
            files.push(this.value);
        });
	document.getElementById('filedelete').value=files.toString();
	document.getElementById('fileform').submit();
}
function toggleAll(){
jQuery("#toggleall").click(function() {
        var checkBoxes = jQuery("input[name=altcheckbox\\[\\]]");
        checkBoxes.prop("checked", jQuery("#toggleall").prop("checked"));
});  
}
</script>
<div class="BasicsBox">
<?php
if (getval("context",false) == 'Modal'){$previous_page_modal = true;}
else {$previous_page_modal = false;}
if(!$modal)
    {
    ?>
    <p>
    <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo generateurl($baseurl . "/pages/edit.php",$urlparams); ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoeditmetadata"]?></a><br / >
    <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo generateurl($baseurl . "/pages/view.php",$urlparams); ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a>
    </p>
    <?php
    }
elseif($previous_page_modal)
    {
    $urlparams["context"]='Modal';
    ?>
    <p>
    <a onClick="return ModalLoad(this,true);" href="<?php echo generateurl($baseurl . "/pages/edit.php",$urlparams); ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoeditmetadata"]?></a><br / >
    <a onClick="return ModalLoad(this,true);" href="<?php echo generateurl($baseurl . "/pages/view.php",$urlparams); ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a>
    </p>
    <?php
    }
    ?>
    <div class="RecordHeader">
        <div class="BackToResultsContainer">
            <div class="backtoresults"> 
            <?php
            if($modal)
                {
                ?>
                <a class="maxLink fa fa-expand" href="<?php echo generateURL($baseurl_short . "pages/alternative_files.php", $urlparams, array("modal" => "")); ?>" onclick="return CentralSpaceLoad(this);"></a>
                &nbsp;<a href="#" class="closeLink fa fa-times" onclick="ModalClose();"></a>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
<?php
if($alternative_file_resource_preview)
    {
    if(file_exists(get_resource_path($resource['ref'], true, 'col', false)))
        {
        ?>
        <img src="<?php echo get_resource_path($resource['ref'], false, 'col', false); ?>"/>
        <?php
        } 
    }

if($alternative_file_resource_title && isset($resource['field'.$view_title_field]))
    {
    echo "<h2>" . htmlspecialchars(i18n_get_translated($resource['field'.$view_title_field])) . "</h2><br/>";
    }
    ?>

<h1><?php echo $lang["managealternativefilestitle"]; render_help_link('user/alternative-files');?></h1>

<?php if (count($files)>0){?><a href="#" id="deletechecked" onclick="if (confirm('<?php echo $lang["confirm-deletion"]?>')) {clickDelete();} return false;"><?php echo LINK_CARET ?><?php echo $lang["action-deletechecked"]?></a><?php } ?>
<form method=post id="fileform" action="<?php echo generateurl($baseurl . "/pages/alternative_files.php",$urlparams); ?>">
<input type=hidden name="filedelete" id="filedelete" value="">
<?php generateFormToken("fileform"); ?>
<div class="Listview"  id="altlistitems">
	
<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
<!--Title row-->	
<tr class="ListviewTitleStyle">
<td><?php if (count($files)>0){?><input type="checkbox" class="checkbox" onclick="toggleAll();" id="toggleall" /><?php } ?></td>
<td><?php echo $lang["name"]?></td>
<td><?php echo $lang["description"]?></td>
<td><?php echo $lang["filetype"]?></td>
<td><?php echo $lang["filesize"]?></td>
<td><?php echo $lang["date"]?></td>
<?php if(count($alt_types) > 1){ ?><td><?php echo $lang["alternatetype"]?></td><?php } ?>
<td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
</tr>

<?php
hook("alt_files_before_list");
for ($n=0;$n<count($files);$n++)
	{
	?>
	<!--List Item-->
	<tr <?php if($files[$n]["ref"]==$alt){echo "class='Highlight' ";} ?>>
	<td><input type="checkbox" class="checkbox" name="altcheckbox[]" value="<?php echo $files[$n]["ref"];?>" /></td>
	<td><?php echo htmlspecialchars($files[$n]["name"])?></td>	
	<td><?php echo htmlspecialchars($files[$n]["description"])?>&nbsp;</td>	
	<td><?php echo ($files[$n]["file_extension"]==""?$lang["notuploaded"]:htmlspecialchars(str_replace_formatted_placeholder("%extension", $files[$n]["file_extension"], $lang["cell-fileoftype"]))); ?></td>	
	<td><?php echo formatfilesize($files[$n]["file_size"])?></td>	
	<td><?php echo nicedate($files[$n]["creation_date"],true)?></td>
	<?php if(count($alt_types) > 1){ ?><td><?php echo $files[$n]["alt_type"] ?></td><?php } ?>
	<td><div class="ListTools">
	
	<a href="#" onclick="
        if (confirm('<?php echo $lang["filedeleteconfirm"]?>'))
            {
            document.getElementById('filedelete').value='<?php echo $files[$n]["ref"]?>';
            <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Post(document.getElementById('fileform'), true);
            }
        return false;
    "><?php echo LINK_CARET ?><?php echo $lang["action-delete"]?></a>

	&nbsp;<a onclick="return <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Load(this, true);" href="<?php echo generateurl($baseurl . "/pages/alternative_file.php",$urlparams,array("ref"=>$files[$n]["ref"])); ?>"><?php echo LINK_CARET ?><?php echo $lang["action-edit"]?></a>

    <?php if($editaccess && (file_exists(get_resource_path($ref , true, '', true, 'jpg', true, 1, false, '', $files[$n]["ref"], true)) || file_exists(get_resource_path($ref , true, 'hpr', true, 'jpg', true, 1, false, '', $files[$n]["ref"], true))))
        {
        echo "<a href=\"#\" onclick=\"previewform=jQuery('#previewform');jQuery('#upload_pre_alt').val('" . $files[$n]["ref"] . "');return " . ($modal ? "Modal" : "CentralSpace") . "Post(previewform, true);\">" . LINK_CARET . $lang["useaspreviewimage"] . "</a>";
        } 
    
    hook("refreshinfo"); ?>
	</td>
	
	</tr>
	<?php
	}
?>
</table>
</div>
<p>
	<a onclick="return CentralSpaceLoad(this, true);" href="<?php echo generateurl($baseurl . "/pages/upload_plupload.php",$urlparams,array('alternative'=>$ref)); ?>"><?php echo LINK_CARET ?><?php echo $lang["alternativebatchupload"] ?></a>
</p>
</form>

<form method=post id="previewform" name="previewform" action="<?php echo generateurl($baseurl . "/pages/upload_preview.php",$urlparams) ; ?>">
    <?php generateFormToken("previewform"); ?>
    <input type=hidden name="ref", id="upload_ref" value="<?php echo htmlspecialchars($ref); ?>"/>
    <input type=hidden name="previewref", id="upload_pre_ref" value="<?php echo htmlspecialchars($ref); ?>"/>
    <input type=hidden name="previewalt", id="upload_pre_alt" value=""/>
</form>
</div> <!-- end of basicbox -->
<script type="text/javascript">
jQuery('#altlistitems').tshift(); // make the select all checkbox work
jQuery('#altlistitems input[type=checkbox]').click(function(){   
	if(jQuery(this).not(':checked').length) { // clear checkall
		jQuery("#toggleall").prop("checked",false);
	} 
}); 

</script>

<?php
include "../include/footer.php";