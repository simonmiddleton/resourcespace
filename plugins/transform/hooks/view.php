<?php

function HookTransformViewAfterresourceactions (){
    global $ref,$access,$lang,$resource,$cropper_allowed_extensions,$baseurl_short,$resourcetoolsGT,$search,$offset,
    $order_by,$sort,$k,$cropper_transform_original;

    // fixme - for some reason this isn't pulling from config default for plugin even when set as global
    // hack below makes it work, but need to figure this out at some point
    // this is something to do with hook architecture -- think it is now fixed by above includes. But this
    // code isn't hurting anything, so leave it for now. -Dwiggins, 5/2010
    if (!isset($cropper_allowed_extensions)){
        $cropper_allowed_extensions = array('TIF','TIFF','JPG','JPEG','PNG','GIF','BMP','PSD'); // file formats that can be transformed
    } else {
        // in case these have been overriden, make sure these are all in uppercase.
        for($i=0;$i<count($cropper_allowed_extensions);$i++){
            $cropper_allowed_extensions[$i] = strtoupper($cropper_allowed_extensions[$i]);
        }   
    }

    if ($access==0 && $resource['has_image']==1 && in_array(strtoupper($resource['file_extension']),$cropper_allowed_extensions)){
        $urlparams = array(
            "ref"       =>  $ref,
            "search"    =>  $search,
            "offset"    =>  $offset,
            "order_by"  =>  $order_by,
            "sort"      =>  $sort,
            "k"         =>  $k
        );
        $crop_url = generateURL($baseurl_short . 'plugins/transform/pages/crop.php',$urlparams);
        ?>
        <li><a onClick='return CentralSpaceLoad(this,true);' href=<?php echo $crop_url;?>>
        <?php echo "<i class='fa fa-fw fa-crop'></i>&nbsp;" .$lang['imagetools'];?>
        </a></li>
        <?php
        if ($cropper_transform_original)
            {
            $urlparams["mode"] = "original"; 
            $crop_url = generateURL($baseurl_short . 'plugins/transform/pages/crop.php', $urlparams);
            ?>
            <li><a onClick='return CentralSpaceLoad(this,true);' href=<?php echo $crop_url;?>>
            <?php echo "<i class='fa fa-fw fa-crop'></i>&nbsp;" .$lang['transform_original'];?>
            </a></li>
            <?php
            }
        return true;
    }

}

?>
