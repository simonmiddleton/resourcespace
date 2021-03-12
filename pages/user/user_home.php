<?php

include "../../include/db.php";

include "../../include/authenticate.php";
include "../../include/header.php";

$introtext=text("introtext");
?>
<div class="BasicsBox"> 
  <h1><?php echo htmlspecialchars(($userfullname=="" ? $username : $userfullname)) ?></h1>
  
  <?php if (trim($introtext)!="") { ?>
  <p><?php echo $introtext ?></p>
  <?php } ?>
  
	<div class="<?php echo ($tilenav?"TileNav":"VerticalNav TileReflow") ?>">
	<ul>
	
    <li><a href="<?php echo $baseurl_short?>pages/user/user_profile_edit.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-user-circle"></i><br /><?php echo $lang["profile"]?></a></li>
	
	<?php if ($allow_password_change && !checkperm("p") && $userorigin=="") { ?>
        <li><a href="<?php echo $baseurl_short?>pages/user/user_change_password.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-key"></i><br /><?php echo $lang["changeyourpassword"]?></a></li>
        <?php } ?>
	
	<?php
      	if ($disable_languages==false && $show_language_chooser)
			{?>
			<li><a href="<?php echo $baseurl_short?>pages/change_language.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-language"></i><br /><?php echo $lang["languageselection"]?></a></li>
			<?php
			} ?>
		
		<?php if (!(!checkperm("d")&&!(checkperm('c') && checkperm('e0')))) { ?>
		<li><a href="<?php echo $baseurl_short?>pages/contribute.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-user-plus"></i><br /><?php echo $lang["mycontributions"]?></a></li>
		<?php 
        }

        if(!checkperm('b'))
            {
            ?>
            <li id="MyCollectionsUserMenuItem">
                <a href="<?php echo $baseurl_short; ?>pages/collection_manage.php" onClick="return CentralSpaceLoad(this, true);"><i aria-hidden="true" class="fa fa-fw fa-shopping-bag"></i><br /><?php echo $lang['mycollections']; ?></a>
            </li>
            <?php
            }

        if($actions_on)
            {
            ?>
            <li>
                <a href="<?php echo $baseurl_short; ?>pages/user/user_actions.php" onClick="return CentralSpaceLoad(this, true);"><i aria-hidden="true" class="fa fa-fw fa-check-square-o"></i><br /><?php echo $lang['actions_myactions']; ?>
                <span style="display: none;" class="ActionCountPill Pill"></span>
                </a>
            </li>
            <?php
            }
            ?>

        <script>message_poll();</script>
        <li id="MyMessagesUserMenuItem">
            <a href="<?php echo $baseurl_short; ?>pages/user/user_messages.php" onClick="return CentralSpaceLoad(this, true);"><i aria-hidden="true" class="fa fa-fw fa-envelope"></i><br /><?php echo $lang['mymessages']; ?>
            <span style="display: none;" class="MessageCountPill Pill"></span>
            </a>
        </li>

        <?php

        if($offline_job_queue)
            {
            $failedjobs = job_queue_get_jobs("",STATUS_ERROR, $userref);
            $failedjobcount = count($failedjobs);
            echo "<li><a href='" . $baseurl_short . "pages/manage_jobs.php?job_user=" . $userref  . "' onClick='return CentralSpaceLoad(this, true);'><i aria-hidden='true' class='fa fa-fw fa-tasks'></i><br />" . $lang['my_jobs'] . ($failedjobcount > 0 ? "&nbsp;<span class='FailedJobCountPill Pill'>" . $failedjobcount . "</span>":"") . "</a>";
            echo "</li>";
            }

        if($allow_share)
            {
            echo "<li><a href='" . $baseurl_short . "pages/manage_external_shares.php?share_user=" . $userref  . "' onClick='return CentralSpaceLoad(this, true);'><i aria-hidden='true' class='fa fa-share-alt'></i><br />" . $lang['my_shares'] . "</a>";
            echo "</li>";
            }
            
		if($home_dash && checkPermission_dashmanage())
			{ ?>
			<li><a href="<?php echo $baseurl_short?>pages/user/user_dash_admin.php"	onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-th"></i><br /><?php echo $lang["manage_own_dash"];?></a></li>
			<?php
			}
		if($user_preferences)
			{ ?>
			<li>
			    <a href="<?php echo $baseurl_short?>pages/user/user_preferences.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-cog"></i><br /><?php echo $lang["userpreferences"];?></a>
			</li>
			<?php
			} ?>

		<?php
			hook('user_home_additional_links');
	
		# Log out
		if(!isset($password_reset_mode) || !$password_reset_mode)
		{?>
		<li><a href="<?php echo $baseurl?>/login.php?logout=true&amp;nc=<?php echo time()?>"><i aria-hidden="true" class="fa fa-sign-out fa-fw"></i><br /><?php echo $lang["logout"]?></a></li>
		<?php
		}
	  ?>
		
	</ul>
	</div>

</div>

<?php
include "../../include/footer.php";
