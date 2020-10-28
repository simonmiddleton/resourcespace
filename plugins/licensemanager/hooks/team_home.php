<?php

function HookLicensemanagerTeam_homeCustomteamfunction()
    {
    global $lang,$baseurl_short;
    ?>
    <li><a href="<?php echo $baseurl_short?>plugins/licensemanager/pages/list.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-scroll"></i><br /><?php echo $lang["managelicenses"]?></a></li>
    <?php
    }