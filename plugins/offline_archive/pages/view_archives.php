<?php

include '../../../include/db.php';
include '../../../include/authenticate.php';
if (!checkperm('i'))
    {
    exit ($lang['error-permissiondenied']);
    }
$completedarchives=sql_query("select archive_code, archive_date, archive_status from offline_archive order by archive_date desc");
$offline_archive_fieldshort=sql_value("select name as value from resource_type_field where ref='$offline_archive_archivefield'","");


include '../../../include/header.php';

?>
<div class='BasicsBox'>

<div>
<a href="<?php echo $baseurl ?>/plugins/offline_archive/pages/administer_archive.php" onClick="return CentralSpaceLoad(this,true);" ><?php echo LINK_CARET . $lang["offline_archive_administer_archive"] ?></a>
</div>


<p>
<h1><?php echo $lang["offline_archive_view_completed"] ?></h1>
</p>

<div class="Listview">
	<table id="offline_archive_table" class="ListviewStyle offline_archive_table" border="0" cellspacing="0" cellpadding="0">
		<?php 
		echo '<tr class="ListviewTitleStyle">
			<td style="width:150px">';
		echo $lang['offline_archive_archive_ref'];
		echo '</td>
			<td>';
		echo $lang['offline_archive_archive_status'];
		echo '</td>
			<td>';
		echo $lang['offline_archive_archive_date'];
		echo '</td>
			<td>';
		echo $lang['offline_archive_view_associated'];
		echo '</td>
		</tr>';	
			
		foreach ($completedarchives as $completedarchive)
			{
			echo '<tr>';
			echo '<td>';
			echo $completedarchive['archive_code'];
			echo '</td><td>';
			echo $lang["offline_archive_statustype"][$completedarchive['archive_status']];
			echo '</td><td>';
			echo $completedarchive['archive_date'];
			echo '</td><td>';
			echo '<a href="' . $baseurl . '/pages/search.php?search=' . $offline_archive_fieldshort . '%3A' . urlencode($completedarchive['archive_code']) . '&archive=2" onClick="return CentralSpaceLoad(this,true);">' . LINK_CARET .  $lang["offline_archive_view_associated"] . '&nbsp;&nbsp;</a>'; 
			echo '</td>';	
			echo "</tr>";
			}
		?>
	</table>
</div>
</div>
<?php
include '../../../include/footer.php';