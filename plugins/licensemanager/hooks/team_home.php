<?php

function HookLicensemanagerTeam_homeCustomteamfunction()
    {
    global $lang,$baseurl_short;
    ?>
    <li><i aria-hidden="true" class="fa fa-fw fa-scroll"></i>&nbsp;<a href="<?php echo $baseurl_short?>plugins/licensemanager/pages/list.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["managelicenses"]?></a></li>
    <?php
    }