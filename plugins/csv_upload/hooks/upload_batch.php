<?php

function HookCsv_uploadUpload_batchBeforeuploadform()
{
    global $baseurl,$lang;

    if (checkperm("c")) {
        ?>
        <p style="float:right;margin:10px;">
            <a href="<?php echo $baseurl ?>/plugins/csv_upload/pages/csv_upload.php" onClick="CentralSpaceLoad(this,true);return false;">
                <?php echo UPLOAD_ICON . escape($lang["csv_upload_nav_link"]); ?>
            </a>
        </p>
        <?php
    }
}
