<?php
function HookConsentmanagerAdmin_group_permissionsAdditionalperms()
    {
    global $lang,$permissions;    
	?>
  	<tr class="ListviewTitleStyle">
	  <td colspan=3 class="permheader"><?php echo $lang["consent_management"] ?></td>
	</tr>
	<?php
    DrawOption("cm", $lang["consent_manager_access"],false,false,(in_array("a",$permissions))); // Grey out if "a" selected);
    }
