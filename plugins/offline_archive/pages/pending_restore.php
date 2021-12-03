<?php

include '../../../include/db.php';
include '../../../include/authenticate.php';
if (!checkperm('i')) 
    {
    exit ($lang['error-permissiondenied']);
    }

// Handle removed resources
$removeref=getvalescaped("remove",0,true);
if($removeref > 0)	
	{
	sql_query("update resource set pending_restore=0 where ref ='" . $removeref . "'");
	resource_log($removeref,"",0,$lang['offline_archive_resource_log_restore_removed'],"","");
	$resulttext=$lang["offline_archive_resources_restore_cancel_confirmed"];
	}

$title_field="field".$view_title_field;
$pendingrestores=sql_query("select ref, $title_field, file_size from resource where pending_restore='1'");


include '../../../include/header.php';
?>
<div class='BasicsBox'>
<?php

if (isset($resulttext))
	{
	echo "<div class=\"projectSaveStatus\">" . $resulttext . "</div>";
	}
?>
<div>
<a href="<?php echo $baseurl ?>/plugins/offline_archive/pages/administer_archive.php" onClick="return CentralSpaceLoad(this,true);" ><?php echo  LINK_CARET . $lang["offline_archive_administer_archive"] ?></a>
</div>


<p>
<h1><?php echo $lang["offline_archive_restore_pending"] ?></h1>
</p>

<form id="cancel_restore_form" name="form1" method="post" action="<?php echo $baseurl ?>/plugins/offline_archive/pages/pending_restore.php">
<input type="hidden" name="remove" id="remove" value="">
<div class="Listview">
	<table id="offline_archive_table" class="ListviewStyle offline_archive_table" border="0" cellspacing="0" cellpadding="0">
		<?php 
		echo '<tr class="ListviewTitleStyle">';
		echo '<td style="width:150px">';
		echo $lang['property-reference'];
		echo '</td><td>';
		echo $lang['property-title'];
		echo '</td><td>';	
		echo $lang['offline_archive_archive_ref'];
		echo '</td>';
		echo '</td><td>';
		echo "</tr>";	
			
		foreach ($pendingrestores as $pendingrestore)
			{
			$ref=$pendingrestore['ref'];
			$archivecode=sql_value("select value from resource_data where resource='$ref' and resource_type_field='$offline_archive_archivefield'",'');
			echo '<tr>
			<td onclick="window.location=\'' . $baseurl . '/?r=' . $ref . '\';">
			' . $ref . '</td>
			<td onclick="window.location=\'' . $baseurl . '/?r=' . $ref . '\';">';
			echo $pendingrestore[$title_field];
			echo '</td>
			<td onclick="window.location=\'' . $baseurl . '/?r=' . $ref . '\';">';
			echo $archivecode;
			echo '</td>
				<td>';
			echo '<a href="#" onClick="jQuery(\'#remove\').val(\'' . $ref . '\');if(confirm(\'' . $lang["offline_archive_cancel_confirm"] . '\')){CentralSpacePost(document.getElementById(\'cancel_restore_form\'),true);};return false;">&gt;&nbsp;' . $lang["offline_archive_cancel_restore"] . '&nbsp;&nbsp;</a>'; 
			echo '</td>';	
			echo '</tr>';					
			}
		
	
		?>
	</table>
</div>

</form>	
</div>
<?php
include '../../../include/footer.php';