<?php

function HookResourceconnectDownload_progressDownloadfinishlinks()
    {
   # Remove nav links for large preview.
    global $is_resourceconnect, $lang;
    if (isset($is_resourceconnect))
        {
        ?>
        <a href="javascript:history.go(-1)"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresults"]?></a>
        <?php
        return true;
        }
    }
    
