<?php
include "../../include/db.php";

include "../../include/authenticate.php";if (!checkperm("i")) {exit ("Permission denied.");}

include "../../include/header.php";
?>


<div class="BasicsBox"> 
    <?php
    $links_trail = array(
        array(
            'title' => $lang["teamcentre"],
            'href'  => $baseurl_short . "pages/team/team_home.php"
        ),
        array(
            'title' => $lang["managearchiveresources"],
            'help'  => "resourceadmin/archives"
        )
    );

    renderBreadcrumbs($links_trail);
    ?>
  
    <p><?php echo text("introtext");?></p>

	<div class="VerticalNav">
	<ul>
	<li><a href="<?php echo $baseurl_short?>pages/edit.php?ref=-<?php echo $userref?>&single=true&status=2" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET . $lang["newarchiveresource"]?></a></li>

	<li><a href="<?php echo $baseurl_short?>pages/search_advanced.php?archive=2" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET . $lang["searcharchivedresources"]?></a></li>

	<li><a href="<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode("!archivepending")?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET . $lang["viewresourcespendingarchive"]?></a></li>

	<?php hook("addlinktoteamarchive");?>

	</ul>
	</div>
	
  </div>

<?php
include "../../include/footer.php";
?>
