<?php

function HookResourceconnectDownload_progressDownloadfinishlinks()
    {
    # Remove nav links for large preview.
    global $is_resourceconnect;
    if (isset($is_resourceconnect))
        {
        ?>
        <a href="#" onclick="window.close()"><i class="fa fa-times-circle"></i> Close tab</a>
        <?php
        return true;
        }
    }
    