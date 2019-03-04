<?php
include ("../../include/db.php");
include_once ("../../include/general.php");
include ("../../include/authenticate.php");
include ("../../include/header.php");
?>

<div class="BasicsBox"> 
   
  <h1><?php echo $lang["systemsetup"]?></h1>
  <?php if (getval("modal","")=="") { ?><p><?php echo text("introtext")?></p><?php } ?>
  

  <div class="VerticalNav">
	<ul>
		<?php if (!hook('replacegroupadmin')) { ?>
		<li><i aria-hidden="true" class="fa fa-fw fa-users"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/admin/admin_group_management.php" onclick="return CentralSpaceLoad(this,true);" ><?php echo $lang['page-title_user_group_management']; ?></a></li>
		<?php } ?>
		<li><i aria-hidden="true" class="fa fa-fw fa-cubes"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/admin/admin_resource_types.php" onclick="return CentralSpaceLoad(this,true);"><?php echo $lang["resource_types_manage"] ?></a></li>
		<li><i aria-hidden="true" class="fa fa-fw fa-bars"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/admin/admin_resource_type_fields.php" onclick="return CentralSpaceLoad(this,true);"><?php echo $lang["admin_resource_type_fields"] ?></a></li>
        <?php if ($search_filter_nodes) { ?>
		<li><i aria-hidden="true" class="fa fa-fw fa-filter"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/admin/admin_filter_manage.php" onclick="return CentralSpaceLoad(this,true);"><?php echo $lang["filter_manage"] ?></a></li>
		<?php } ?>
		<li><i aria-hidden="true" class="fa fa-fw fa-table"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/admin/admin_report_management.php" onclick="return CentralSpaceLoad(this,true);"><?php echo $lang['page-title_report_management']; ?></a></li>
		
		<li><i aria-hidden="true" class="fa fa-fw fa-files-o"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/admin/admin_size_management.php" onclick="return CentralSpaceLoad(this,true);"><?php echo $lang["page-title_size_management"] ?></a></li>
		
		<?php if (checkperm("o")) { ?><li><i aria-hidden="true" class="fa fa-fw fa-pencil-square-o"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/admin/admin_content.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["managecontent"]?></a></li><?php } ?>
		
		<?php
        if($use_plugins_manager == true)
            {
            ?>
            <li><i aria-hidden="true" class="fa fa-fw fa-plug"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/team/team_plugins.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["pluginssetup"]?></a></li>
            <?php
            }

        if(checkperm('a'))
            {
            ?>
            <li><i aria-hidden="true" class="fa fa-fw fa-picture-o"></i>&nbsp;<a href="<?php echo $baseurl_short; ?>pages/admin/admin_manage_slideshow.php" onClick="return CentralSpaceLoad(this, true);"><?php echo $lang['manage_slideshow']; ?></a></li>
            <?php
            }

        if($team_centre_bug_report && !hook("custom_bug_report"))
            {
            ?>
            <li><i aria-hidden="true" class="fa fa-fw fa-bug"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/admin/admin_reportbug.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["reportbug"]?></a></li>
            <?php
            }

        if('' != $mysql_bin_path)
            {
            ?>
            <li><i aria-hidden="true" class="fa fa-fw fa-database"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/team/team_export.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["exportdata"]?></a></li>
            <?php
            }

		if (checkperm('a'))
			{
			
			if ($enable_remote_apis)
			  {
			  ?>
			  <li><i aria-hidden="true" class="fa fa-fw fa-stethoscope"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/api_test.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["api-test-tool"]?></a></li>
			  <?php
			  }
			?>
			<li><i aria-hidden="true" class="fa fa-fw fa-check-square"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/check.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["installationcheck"]?></a></li>
			<li><i aria-hidden="true" class="fa fa-fw fa-history"></i>&nbsp;<a href="<?php echo $baseurl_short; ?>pages/admin/admin_system_log.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["systemlog"]; ?></a></li>
			<li><i aria-hidden="true" class="fa fa-fw fa-terminal"></i>&nbsp;<a href="<?php echo $baseurl?>/pages/team/team_system_console.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["systemconsole"]?></a></li>
			<li><i aria-hidden="true" class="fa fa-fw fa-bolt"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/admin/admin_system_performance.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["system_performance"]?></a></li>
			<li><i aria-hidden="true" class="fa fa-fw fa-cog"></i>&nbsp;<a href="<?php echo $baseurl; ?>/pages/admin/admin_system_config.php" onClick="return CentralSpaceLoad(this, true);"><?php echo $lang['systemconfig']; ?></a></li>
			<?php
			}
hook("customadminfunction");
?>

	</ul>
	</div>
</div> <!-- End of BasicsBox -->


<?php


include("../../include/footer.php");
