<?php
require_once "../include/db.php";
require_once "../include/authenticate.php";if (!checkperm("d")&&!(checkperm('c') && checkperm('e0'))) {exit ("Permission denied.");}

include "../include/header.php";
?>


<div class="BasicsBox"> 
  <h1><?php echo $lang["mycontributions"]?></h1>
  <p><?php echo text("introtext");render_help_link("user/uploading");?></p>

	<div class="VerticalNav">
	<ul>

	<li><i class="fa fa-fw fa-upload"></i> <a onClick="return CentralSpaceLoad(this,true);"
	<?php
				#We need to point to the right upload sequence based on $upload_then_edit
				if ($upload_then_edit==1){?>
						href="<?php echo $baseurl_short?>pages/upload_batch.php">
				<?php }
				else {?>
						href="<?php echo $baseurl_short?>pages/edit.php?ref=-<?php echo urlencode($userref) ?>&uploader=batch"><?php 
				}?>
	<?php echo $lang["addresourcebatchbrowser"];?></a>
    </li>
<?php
foreach(get_workflow_states() as $workflow_state)
    {
    if(($show_user_contributed_resources && $workflow_state != 0) && checkperm("z{$workflow_state}"))
        {
        continue;
        }

    $ws_a_href = generateURL(
        "{$baseurl_short}pages/search.php",
        array(
            'search' => "!contributions{$userref}",
            'archive' => $workflow_state,
        ));
    $ws_a_text = str_replace('%workflow_state_name', $lang["status{$workflow_state}"], $lang["view_my_contributions_ws"]);
    
    $icon = $workflowicons[$workflow_state] ?? (WORKFLOW_DEFAULT_ICONS[$workflow_state] ?? WORKFLOW_DEFAULT_ICON);   
    ?>
    <li><a href="<?php echo $ws_a_href; ?>" onClick="return CentralSpaceLoad(this, true);"><i class="fa-fw <?php echo escape_quoted_data($icon); ?>"></i>&nbsp;<?php echo htmlspecialchars($ws_a_text); ?></a></li>
    <?php
    }

    hook('custommycontributionlink');
    ?>
	</ul>
	</div>
	
  </div>
<?php
include "../include/footer.php";