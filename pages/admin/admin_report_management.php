<?php

include "../../include/db.php";

include "../../include/authenticate.php";

if (!checkperm("a"))
	{
	exit ("Permission denied.");
	}

include "../../include/header.php";

$find=getval("find","");
$order_by=getval("orderby","name");

$url_params = array("find" => $find, "orderby" => $order_by);
$url=generateURL($baseurl . "/pages/admin/admin_report_management.php", $url_params);

$reports=sql_query("select ref, name from report" . ($find=="" ? "" : " where ref like '%{$find}%' or name like '%{$find}%'") . " order by {$order_by}");

?><div class="BasicsBox"> 
	
	<?php
	$links_trail = array(
	    array(
	        'title' => $lang["systemsetup"],
	        'href'  => $baseurl_short . "pages/admin/admin_home.php"
	    ),
	    array(
	        'title' => $lang["page-title_report_management"],
	    )
	);

	renderBreadcrumbs($links_trail);
	?>
	
	<p><?php echo $lang['page-subtitle_report_management_edit'];render_help_link("resourceadmin/reports-and-statistics"); ?></p>

	<!--code for copy report link -->
	<!-- form #copy_report -->
<form method="post"  
	id="copy_report" 
	action="admin_report_management_edit.php"
	onsubmit="return CentralSpacePost(this, true);">
	<input type="hidden" name="copyreport" value="true">
	<input type="hidden" name="ref" value="">
	<?php generateFormToken("copy_report"); ?>
</form>
<!-- javascript - submits form #copy_report -->
<script>
function copyReport(ref)
	{
	frm = document.forms["copy_report"];
	frm.ref.value=ref;
	frm.submit();	
	}
	</script>
<!-- end code for copy report link -->	

<?php
function addColumnHeader($orderName, $labelKey)
	{
	global $baseurl, $order_by, $find, $lang;

	if ($order_by == $orderName)
		$image = '<span class="ASC"></span>';
	else if ($order_by == $orderName . ' desc')
		$image = '<span class="DESC"></span>';
	else
		$image = '';

	?><td>
	<a href="<?php echo $baseurl ?>/pages/admin/admin_report_management.php?<?php
	if ($find!="") { ?>&find=<?php echo $find; }
	?>&orderby=<?php echo $orderName . ($order_by==$orderName ? '+desc' : ''); ?>"
	   onClick="return CentralSpaceLoad(this);"><?php echo $lang[$labelKey] . $image ?></a>
	</td>
	<?php
}

	?>
	<div class="Listview">
		<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
			<tr class="ListviewTitleStyle">
				<?php addColumnHeader("ref", "property-reference"); ?>
				<?php addColumnHeader("name", "property-name"); ?>
				<td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
			</tr>
<?php
		foreach ($reports as $report)
			{
            $edit_url_extra = array();
            $edit_url_extra = ($find == "" ? $edit_url_extra : array_merge($edit_url_extra, array("find" => $find)));
            $edit_url_extra = ($order_by == "name" ? $edit_url_extra : array_merge($edit_url_extra, array("orderby" => $order_by)));
            $edit_url = generateURL("{$baseurl_short}pages/admin/admin_report_management_edit.php", array("ref" => $report["ref"]), $edit_url_extra);
			$view_url="{$baseurl_short}pages/team/team_report.php?report={$report['ref']}&backurl=" . urlencode($url);
            $a_href = (!(!db_use_multiple_connection_modes() && $execution_lockout) ? $edit_url : $view_url);
            ?>
            <tr>
				<td>
					<a href="<?php echo $a_href; ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo str_highlight ($report["ref"],$find,STR_HIGHLIGHT_SIMPLE); ?></a>
				</td>					
				<td>
					<a href="<?php echo $a_href; ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo str_highlight ($report["name"],$find,STR_HIGHLIGHT_SIMPLE); ?></a>
				</td>
				<td>
					<div class="ListView" align="right">
						<?php echo LINK_CARET ?><a href="<?php echo $view_url; ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["action-view"]?></a>
						<?php
                        if(db_use_multiple_connection_modes() || !$execution_lockout)
                            {
                            echo LINK_CARET; ?><a href="<?php echo $edit_url; ?>" onclick="return CentralSpaceLoad(this, true);"><?php echo $lang["action-edit"]; ?></a>
                            <?php echo LINK_CARET; ?><a  href="javascript:copyReport('<?php echo $report["ref"]; ?>')"><?php echo $lang["copy"]; ?></a>
                            <?php
                            }
                            ?>
					</div>
				</td>
			</tr>
<?php
			}
?>		</table>
	</div>
</div>		<!-- end of BasicsBox -->

<div class="BasicsBox">
	<form method="post" action="<?php echo $baseurl_short?>pages/admin/admin_report_management.php" onSubmit="return CentralSpacePost(this,false);">
        <?php generateFormToken("admin_report_management_find"); ?>
		<div class="Question">
			<label for="find"><?php echo $lang["property-search_filter"] ?></label>
			<input name="find" type="text" class="medwidth" value="<?php echo $find; ?>">
			<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["searchbutton"]; ?>&nbsp;&nbsp;">
			<div class="clearerleft"></div>
		</div>
<?php
	if ($find!="")
		{
?>		<div class="QuestionSubmit">
			<label for="buttonsave"></label>
			<input name="buttonsave" type="button" onclick="CentralSpaceLoad('admin_report_management.php',false);"
				   value="&nbsp;&nbsp;<?php echo $lang["clearbutton"]; ?>&nbsp;&nbsp;">
		</div>
<?php
		}
?>	</form>
</div>

<div class="BasicsBox">
	<form method="post" action="<?php echo $baseurl_short; ?>pages/admin/admin_report_management_edit.php" onSubmit="return CentralSpacePost(this,false);">
        <?php generateFormToken("admin_report_management"); ?>
    	<div class="Question">
			<label for="name"><?php echo $lang['action-title_create_report_called']; ?></label>
			<div class="tickset">
				<div class="Inline">
					<input name="newreportname" type="text" value="" class="shrtwidth">
				</div>
				<div class="Inline">
					<input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["create"]; ?>&nbsp;&nbsp;" onclick="return (this.form.elements[0].value!='');">
				</div>
			</div>
			<div class="clearerleft"></div>
		</div>
		<?php
		if ($order_by)
			{
			?><input type="hidden" name="orderby" value="<?php echo $order_by; ?>">
			<?php
			}
		if ($find)
			{
			?><input type="hidden" name="find" value="<?php echo $find; ?>">
			<?php
			}
		?>
	</form>
</div>


<?php
include "../../include/footer.php";

