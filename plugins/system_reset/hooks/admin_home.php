<?php

function HookSystem_resetAdmin_homeCustomadminfunction()
    {
    global $baseurl,$lang;
    ?><li><i aria-hidden="true" class="fa fa-fw fa-recycle"></i>&nbsp;<a href="<?php echo $baseurl ?>/plugins/system_reset/pages/reset.php"><?php echo $lang["system_reset"]?></a></li><?php
    }