<?php

include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";
include "../../include/header.php";

$introtext=text("introtext");
?>
<div class="BasicsBox"> 
  <h1><?php echo htmlspecialchars(($userfullname=="" ? $username : $userfullname)) ?></h1>
  
  <?php if (trim($introtext)!="") { ?>
  <p><?php echo $introtext ?></p>
  <?php } ?>
  
	<div class="VerticalNav">
	<ul>
	
	<?php if ($allow_password_change && !checkperm("p") && $userorigin=="") { ?>
        <li><i aria-hidden="true" class="fa fa-fw fa-key"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/user/user_change_password.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["changeyourpassword"]?></a></li>
        <?php } ?>
	
	<?php
      	if ($disable_languages==false && $show_language_chooser)
			{?>
			<li><i aria-hidden="true" class="fa fa-fw fa-language"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/change_language.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["languageselection"]?></a></li>
			<?php
			} ?>
		
		<?php if (!(!checkperm("d")&&!(checkperm('c') && checkperm('e0')))) { ?>
		<li><i aria-hidden="true" class="fa fa-fw fa-user-plus"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/contribute.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["mycontributions"]?></a></li>
		<?php 
        }

        if(!checkperm('b'))
            {
            ?>
            <li id="MyCollectionsUserMenuItem">
                <i aria-hidden="true" class="fa fa-fw fa-shopping-bag"></i>
                <a href="<?php echo $baseurl_short; ?>pages/collection_manage.php" onClick="return CentralSpaceLoad(this, true);"><?php echo $lang['mycollections']; ?></a>
            </li>
            <?php
            }

        if($actions_on)
            {
            ?>
            <li>
                <i aria-hidden="true" class="fa fa-fw fa-check-square-o"></i>
                <a href="<?php echo $baseurl_short; ?>pages/user/user_actions.php" onClick="return CentralSpaceLoad(this, true);"><?php echo $lang['actions_myactions']; ?></a>
                <span style="display: none;" class="ActionCountPill Pill"></span>
            </li>
            <?php
            }
            ?>

        <script>message_poll();</script>
        <li id="MyMessagesUserMenuItem">
            <i aria-hidden="true" class="fa fa-fw fa-envelope"></i>
            <a href="<?php echo $baseurl_short; ?>pages/user/user_messages.php" onClick="return CentralSpaceLoad(this, true);"><?php echo $lang['mymessages']; ?></a>
            <span style="display: none;" class="MessageCountPill Pill"></span>
        </li>
		
		<?php
		if($home_dash && checkPermission_dashmanage())
			{ ?>
			<li><i aria-hidden="true" class="fa fa-fw fa-th"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/user/user_dash_admin.php"	onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["manage_own_dash"];?></a></li>
			<?php
			}
		if($user_preferences)
			{ ?>
			<li>
			    <i aria-hidden="true" class="fa fa-fw fa-cog"></i>&nbsp;<a href="<?php echo $baseurl_short?>pages/user/user_preferences.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["userpreferences"];?></a>
			</li>
			<?php
			} ?>

		<?php
			hook('user_home_additional_links');
	
		# Log out
		if(!isset($password_reset_mode) || !$password_reset_mode)
		{?>
		<hr />
		<li><a href="<?php echo $baseurl?>/login.php?logout=true&amp;nc=<?php echo time()?>"><i aria-hidden="true" class="fa fa-sign-out fa-fw"></i>&nbsp;<?php echo $lang["logout"]?></a></li>
		<?php
		}
	  ?>
		
	</ul>
	</div>

</div>

<?php
include "../../include/footer.php";
