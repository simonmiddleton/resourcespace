<?php

include '../../../include/db.php';
include '../../../include/authenticate.php';
if (!checkperm('i'))
    {
    exit ($lang['error-permissiondenied']);
    }

$title_field="field".$view_title_field;
$pendingresources=sql_query("select ref, $title_field, file_size from resource where archive='1'");
$totalpendingsize=0;

// Handle create archive form post
if (getvalescaped("create_archive","")!="" && getvalescaped("archive_name","")!="")
	{
	$archive_name=getvalescaped("archive_name","") . date("Ymd", time());
	sql_query("insert into offline_archive (archive_code, archive_date, archive_status) values ('$archive_name',now(),0)");
	$resulttext=$lang['offline_archive_archive_created'] . ": " . $archive_name;	
	}

include '../../../include/header.php';
?>

<script>
jQuery(document).ready(function()
	{
	jQuery('#create_archive_form').submit(function() {
		if (jQuery('#archive_name').val()=="")
			{
			alert('<?php echo $lang['offline_archive_entername'] ?>');
			//jQuery('#archive_name').slideDown();
			jQuery('#archive_name').focus();
			return false;
			}
		});	
	});

</script>

<div class="BasicsBox">

    <?php
    if (isset($resulttext))
        {
        echo "<div class=\"offlinearchiveSaveStatus\">" . $resulttext . "</div>";
        }
        
    ?>
    <p>
        <a href="<?php echo $baseurl ?>/plugins/offline_archive/pages/administer_archive.php" onClick="return CentralSpaceLoad(this,true);" ><?php echo LINK_CARET . $lang["offline_archive_administer_archive"] ?></a>
    </p>
        
    <p>
    <h1><?php echo $lang["offline_archive_view_pending"] ?></h1>
    </p>

    <form id="create_archive_form" id="offline_archive_form" name="offline_archive_form" method="post" action="<?php echo $baseurl ?>/plugins/offline_archive/pages/view_pending.php">
        <div class="Question">		
            <label for="archive_name"><?php echo $lang['offline_archive_name'] ?></label>
            <input class="stdwidth" name="archive_name" id="archive_name" type="text" />
            <div class="clearer"> </div>
        </div>
        <div class="QuestionSubmit">	
            <input name="create_archive" id="create_archive" type="submit" value="&nbsp;&nbsp;<?php echo $lang["offline_archive_createnew"]; ?>&nbsp;&nbsp;">
            <?php generateFormToken("offline_archive_form"); ?>
        </div>
    </form>	

    <div>
    <?php
    echo '<p><a href="' . $baseurl . '/pages/search.php?search=&archive=1" onClick="return CentralSpaceLoad(this,true);">' . LINK_CARET . $lang["offline_archive_view_as_search"] . '&nbsp;&nbsp;</a></p>'; 
    ?>
    </div>

    <div class="Listview">
        <table id="offline_archive_table" class="ListviewStyle offline_archive_table" border="0" cellspacing="0" cellpadding="0">
            <?php 
            echo '<tr class="ListviewTitleStyle">';
            echo '<td style="width:150px">';
            echo $lang['property-reference'];
            echo '</td><td>';
            echo $lang['property-title'];
            echo '</td><td style="width:150px">';
            echo $lang['fieldtitle-file_size']; 
            echo '</td>';	
            echo "</tr>";	
                
            foreach ($pendingresources as $pendingresource)
                {
                echo '<tr onclick="window.location=\'' . $baseurl . '/?r=' . $pendingresource['ref'] . '\';">
                <td>
                ' . $pendingresource['ref'] . '</td>
                <td>';
                echo $pendingresource[$title_field];
                echo '</td>
                <td>';
                echo formatfilesize($pendingresource['file_size']); 
                echo '</td>
                </tr>';
                $totalpendingsize=$totalpendingsize + $pendingresource['file_size'];
                }
            echo '<tr>';
            echo '<td>';
            echo '<b>' . $lang['total'] . '</b>';
            echo '</td><td>';
            echo '</td><td>';
            echo '<b>' . formatfilesize($totalpendingsize) . '</b>'; 
            echo '</td>';	
            echo "</tr>"
            ?>
        </table>
    </div>
</div> <!-- End of BasicsBox -->
<?php
include '../../../include/footer.php';