<?php

function HookConsentmanagerTeam_homeCustomteamfunction()
    {
    global $lang,$baseurl_short;
    if (!checkperm("a") && !checkperm("cm")) {return false;}
    ?>
    <li><a href="<?php echo $baseurl_short?>plugins/consentmanager/pages/list.php" onClick="return CentralSpaceLoad(this,true);"><i aria-hidden="true" class="fa fa-fw fa-user-check"></i><br /><?php echo $lang["manageconsent"]?></a></li>
    <?php
    }