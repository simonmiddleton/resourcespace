<?php
/**
 * Resource management team center page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";

include "../../include/authenticate.php";if (!checkperm("t")) {exit ("Permission denied.");}

include "../../include/header.php";

?>

<div class="BasicsBox"> 

  <h1><?php echo $lang["manageresources"]?></h1>

	<div class="VerticalNav">
	<ul>
	
	<?php if (checkperm("c")): // Check if user can create resources ?>

        <?php if($upload_methods['single_upload']):// Test if Add Single Resource is allowed. ?>
			<li><i aria-hidden="true" class="fa fa-fw fa-file"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/edit.php?ref=-<?php echo $userref?>&amp;noupload=true" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["addresource"]?></a></li>
		<?php endif // Test if Add Single Resource is allowed. ?>

		<?php if($upload_methods['in_browser_upload']): // Test if Add Resource Batch - In Browser is allowed. 
			$url = ($upload_then_edit) ? "upload_plupload.php" : "edit.php?ref=-$userref&amp;uploader=plupload";
		?>
            <li><i aria-hidden="true" class="fa fa-fw fa-upload"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/<?php echo $url?>" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["addresourcebatchbrowser"]?></a></li>
        <?php endif // Test if Add Resource Batch - In Browser is allowed. ?>

		<?php 
		hook("addteamresourcetool");
		
		$no_exif = '';
		if(!$metadata_read_default) {
			$no_exif = '&no_exif=yes';
		}
		?>

		<li><i aria-hidden="true" class="fa fa-fw fa-files-o"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/upload_replace_batch.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["replaceresourcebatch"]?></a></li>    

		<li><i aria-hidden="true" class="fa fa-fw fa-clone"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/team/team_copy.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["copyresource"]?></a></li>
		
		<?php if (checkperm("e-2")) { ?>
		<li><i aria-hidden="true" class="fa fa-fw fa-user-plus"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/search.php?search=&archive=-2&resetrestypes=true" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["viewuserpendingsubmission"]?></a></li>
		<?php } ?>

		<?php if (checkperm("e-1")) { ?>
		<li><i aria-hidden="true" class="fa fa-fw fa-user-plus"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/search.php?search=&archive=-1&resetrestypes=true" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["viewuserpending"]?></a></li>
	<?php } ?>
		
		<?php if (checkperm("e-2")) { ?>
		<li><i aria-hidden="true" class="fa fa-fw fa-user-plus"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/search.php?search=!contributions<?php echo $userref?>&archive=-2&resetrestypes=true" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["viewcontributedps"]?></a></li>
		<?php } ?>
		
		<?php
		# If deleting resources is configured AND the deletion state is '3' (deleted) AND the user has permission to edit resources in this state, then show a link to list deleted resources.
		if (isset($resource_deletion_state) && $resource_deletion_state==3 && checkperm("e3")) { ?><li><i aria-hidden="true" class="fa fa-fw fa-trash"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/search.php?search=&archive=3&resetrestypes=true" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["viewdeletedresources"]?></a></li>
		<?php } ?>

		<?php
			if ($file_checksums){
				// File checksums must be enabled for duplicate searching to work
				// also, rememember that it only works for resources that have a checksum
				// so if you're using offline generation of checksum hashes, make sure they have been updated
				// before running this search.
			?>
			<li><i aria-hidden="true" class="fa fa-fw fa-files-o"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode("!duplicates")?>" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["viewduplicates"]?></a></li>
		<?php } // end if checksums and temp tables turned on ?>

		<li><i aria-hidden="true" class="fa fa-fw fa-filter"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode("!unused")?>" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["viewuncollectedresources"]?></a></li>
		
		<li><i aria-hidden="true" class="fas fa-exclamation-triangle"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/search.php?search=!integrityfail" onClick="return CentralSpaceLoad(this,true);" title="<?php echo $lang["team_resource_integrity_fail_info"]?>"><?php echo $lang["team_resource_integrity_fail"]?></a></li>
		
        <?php if (checkperm("i")) { ?><li><i aria-hidden="true" class="fa fa-fw fa-archive"></i>&nbsp;<a href="<?php echo $baseurl?>/pages/team/team_archive.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["managearchiveresources"]?></a></li><?php } ?>
			
		<?php if (checkperm("k")): // Check if user can manage keywords and fields ?>
			<li><i aria-hidden="true" class="fa fa-fw fa-link"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/team/team_related_keywords.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["managerelatedkeywords"]?></a></li>
		<?php endif // Check if user can manage keywords and fields ?>

	<?php endif // Check if user can create resources ?>

	</ul>
	</div>

  </div>

<?php
include "../../include/footer.php";
?>
