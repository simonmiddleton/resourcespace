<?php
function HookLicensemanagerAdmin_group_permissionsAdditionalperms()
    {
    global $lang,$permissions; 
	?>
  	<tr class="ListviewTitleStyle">
	  <td colspan=3 class="permheader"><?php echo $lang["license_management"] ?></td>
	</tr>
	<?php
    DrawOption("lm", $lang["license_manager_access"],false,false,(in_array("a",$permissions))); // Grey out if "a" selected
    }
