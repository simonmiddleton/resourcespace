<?php

function HookResourcebotAllAdditionalheaderjs()
    {
    global $useremail;
    if (isset($useremail) && $useremail!="")
        {
        ?>
        <script src="https://hive.montala.com/ResourceBot/bot.php?uid=<?php echo md5($useremail) ?>" type="text/javascript"></script>
        <?php
        }
    return true;
    }
