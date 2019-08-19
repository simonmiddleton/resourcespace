<?php
include "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php";
include "../include/resource_functions.php";

$ref=getvalescaped("ref","",true);
$alt=getvalescaped("alternative","",true);

$search=getvalescaped("search","");
$offset=getvalescaped("offset","",true);
$order_by=getvalescaped("order_by","");
$archive=getvalescaped("archive","",true);
$restypes=getvalescaped("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}

$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);


# Fetch resource data.
$resource=get_resource_data($ref);

# Not allowed to edit this resource?
if ((!get_edit_access($ref,$resource["archive"], false,$resource) || checkperm('A')) && $ref>0) {exit ("Permission denied.");}

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
<p>
<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/edit.php?ref=<?php echo urlencode($ref) ?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo $sort?>&archive=<?php echo urlencode($archive)?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoeditresource"]?></a><br / >
<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/view.php?ref=<?php echo urlencode($ref)?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo $sort?>&archive=<?php echo urlencode($archive)?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a>
</p>
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
</div>

<form method=post id="fileform" action="<?php echo $baseurl_short?>pages/alternative_files.php?ref=<?php echo urlencode($ref) ?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo $sort?>&archive=<?php echo urlencode($archive)?>">
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
	
	<a href="#" onclick="if (confirm('<?php echo $lang["filedeleteconfirm"]?>')) {document.getElementById('filedelete').value='<?php echo $files[$n]["ref"]?>';document.getElementById('fileform').submit();} return false;"><?php echo LINK_CARET ?><?php echo $lang["action-delete"]?></a>

	&nbsp;<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/alternative_file.php?resource=<?php echo urlencode($ref)?>&ref=<?php echo $files[$n]["ref"]?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo $sort?>&archive=<?php echo urlencode($archive)?>"><?php echo LINK_CARET ?><?php echo $lang["action-edit"]?></a>

        <?php hook("refreshinfo"); ?>
	
	</td>
	
	</tr>
	<?php
	}
?>
</table>
</div>

	

<p>
	<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/upload_plupload.php?alternative=<?php echo urlencode($ref) ?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo $sort?>&archive=<?php echo urlencode($archive)?>"><?php echo LINK_CARET ?><?php echo $lang["alternativebatchupload"] ?></a>
	<?php
	if($upload_methods['fetch_from_local_folder'])
		{
		?>
		<br/>
		<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/team/team_batch_select.php?use_local=yes&collection_add=&entercolname=&autorotate=&alternative=<?php echo urlencode($ref) ?>&uploader=local&single=&local=true&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo $sort?>&archive=<?php echo urlencode($archive)?>"><?php echo LINK_CARET ?><?php echo $lang["alternativelocalupload"] ?></a>
		<?php
		}
	?>
</p>



</form>

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
?>
