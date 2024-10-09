<?php

function HookOpenai_image_editViewAfterresourceactions () {
    global $ref,$access,$lang,$resource,$cropper_allowed_extensions,$baseurl_short,$search,$offset,
    $order_by,$sort,$k,$imagemagick_path;

    if (
        $access==0
        && (int) $resource['has_image'] !== RESOURCE_PREVIEWS_NONE
    ) {
        $urlparams = array(
            "ref"       =>  $ref
        );
        $crop_url = generateURL($baseurl_short . 'plugins/openai_image_edit/pages/edit.php',$urlparams);
        ?>
        <li><a onClick='return CentralSpaceLoad(this,true);' href='<?php echo $crop_url;?>'>
        <?php echo "<i class='fa fa-fw fa-magic'></i>&nbsp;" . escape($lang['openai_image_edit__edit_with_ai']); ?>
        </a></li>
        <?php
        return true;
    }

}

?>
