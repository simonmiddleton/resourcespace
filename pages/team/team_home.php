<?php
/**
 * Team center home page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";

include "../../include/authenticate.php";if (!checkperm("t")) {exit ("Permission denied.");}

if ($send_statistics) {send_statistics();}

$overquota=overquota();


# Work out free space / usage for display
if (!file_exists($storagedir)) {mkdir($storagedir,0777);}

if (isset($disksize)) # Use disk quota rather than real disk size
	{
	$avail=$disksize * 1000 * 1000 * 1000;
	$used=get_total_disk_usage();
	$free=$avail-$used;
	}
else
	{		
	$avail=disk_total_space($storagedir);
	$free=disk_free_space($storagedir);
	$used=$avail-$free;
	}
if ($free<0) {$free=0;}
		
include "../../include/header.php";
?>


<div class="BasicsBox"> 
  <h1><?php echo $lang["teamcentre"];?></h1>
  <?php if (getval("modal","")=="") 
    { 
    ?><p><?php echo text("introtext");render_help_link('resourceadmin/quick-start-guide');?></p><?php 
    }?>
  
	<div class="<?php echo ($tilenav?"TileNav":"VerticalNav TileReflow") ?>">
	<ul>
	
	<?php if (checkperm("c")) { 
		if ($overquota)
			{
			?><li><i aria-hidden="true" class="fa fa-fw fa-files-o"></i><br /><?php echo $lang["manageresources"]?> : <strong><?php echo $lang["manageresources-overquota"]?></strong></li><?php
			}
		else
			{
			?><li><a href="<?php echo $baseurl_short?>pages/team/team_resource.php"
				<?php if (getval("modal","")!="")
				  {
				  # If a modal, open in the same modal
				  ?>
				  onClick="return ModalLoad(this,true,true,'right');"
				  <?php
				  }
				else
				  { ?>
				  onClick="return CentralSpaceLoad(this,true);"
				  <?php
				  }
				?>
			
			><i aria-hidden="true" class="fa fa-fw fa-files-o"></i><br /><?php echo $lang["manageresources"]?></a></li><?php
			}
 		}
 	?>
				
	<?php if (checkperm("R")) { ?><li><a href="<?php echo $baseurl_short ?>pages/team/team_request.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-shopping-cart"></i><br /><?php echo $lang["managerequestsorders"]?>
        <?php
        $condition = "";
        if (checkperm("Rb")) {$condition = "and assigned_to='" . $userref . "'";} # Only show pending for this user?
        $pending = sql_value("select count(*) value from request where status = 0 $condition",0);
        if ($pending>0)
		  {
		  ?>
		  &nbsp;<span class="Pill"><?php echo $pending ?></span>
		  <?php
		  }
		?>
        </a>
    </li><?php } ?>

    <?php if (checkperm("r") && $research_request) { ?><li><a href="<?php echo $baseurl_short?>pages/team/team_research.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-question-circle"></i><br /><?php echo $lang["manageresearchrequests"]?><br>
    <?php
        $unassigned = sql_value("select count(*) value from research_request where status = 0",0);
        if ($unassigned > 0)
            {
            ?>&nbsp;<span class="Pill"><?php echo $unassigned ?></span><?php
            }
        ?>
        </a> 
    </li><?php }

    if(checkperm('u'))
        {
        ?>
        <li><a href="<?php echo $baseurl_short; ?>pages/team/team_user.php" onClick="return CentralSpaceLoad(this, true);"><i aria-hidden="true" class="fa fa-fw fa-users"></i><br /><?php echo $lang['manageusers']; ?></a></li>
        <?php
        }

    // Manage dash tiles
    if(
        $home_dash
        && (
            // All user tiles
            ((checkperm('h') && !checkperm('hdta')) || (checkperm('dta') && !checkperm('h')))
            // User group tiles
            || (checkperm('h') && checkperm('hdt_ug'))
        )
    )
        {
        ?>
        <li><a href="<?php echo $baseurl_short; ?>pages/team/team_dash_admin.php" onClick="return CentralSpaceLoad(this, true);"><i aria-hidden="true" class="fa fa-fw fa-th"></i><br /><?php echo $lang['manage_dash_tiles']; ?></a></li>
        <?php
        }

    // Manage external shares
    if(checkperm('ex') || checkperm('a'))
        {
        ?>
        <li><a href="<?php echo $baseurl_short; ?>pages/manage_external_shares.php" onClick="return CentralSpaceLoad(this, true);"><i aria-hidden="true" class="fa fa-fw fa-share-alt"></i><br /><?php echo $lang['manage_external_shares']; ?></a></li>
        <?php
        }
        ?>

    <li><a href="<?php echo $baseurl_short?>pages/team/team_analytics.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-pie-chart"></i><br /><?php echo $lang["rse_analytics"]?></a></li>
    
    <li><a href="<?php echo $baseurl_short?>pages/team/team_report.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-table"></i><br /><?php echo $lang["viewreports"]?></a></li>

    <?php if (checkperm("m")) { ?><li><a href="<?php echo $baseurl_short?>pages/team/team_mail.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-envelope"></i><br /><?php echo $lang["sendbulkmail"]?></a></li><?php } ?>

    	<?php hook("customteamfunction")?>

	<?php
	# Include a link to the System Setup area for those with the appropriate permissions.
	if (checkperm("a")) { ?>

	<li><a href="<?php echo $baseurl_short?>pages/admin/admin_home.php"
	<?php if (getval("modal","")!="")
	  {
	  # If a modal, open in the same modal
	  ?>
	  onClick="return ModalLoad(this,true,true,'right');"
	  <?php
	  }
	else
	  { ?>
	  onClick="return CentralSpaceLoad(this,true);"
	  <?php
	  }
	?>
	><i aria-hidden="true" class="fa fa-fw fa-cog"></i><br /><?php echo $lang["systemsetup"]?></a></li>
	<?php hook("customteamfunctionadmin")?>
	<?php } ?>

	</ul>
	</div>
	
<p class="clearerleft"><i aria-hidden="true" class="fa fa-fw fa-hdd-o"></i> <?php echo $lang["diskusage"]?>: <b><?php echo round(($avail?$used/$avail:0)*100,0)?>%</b>
&nbsp;&nbsp;&nbsp;<span class="sub"><?php echo formatfilesize($used)?> / <?php echo formatfilesize($avail)?></span>
</p>

</div>

<?php
include "../../include/footer.php";
?>
