<?php

function HookTransformViewAfterresourceactions (){
    global $ref,$access,$lang,$resource,$cropper_allowed_extensions,$baseurl_short,$resourcetoolsGT,$search,$offset,
    $order_by,$sort,$k,$imagemagick_path;

    if(!isset($imagemagick_path))
        {
        return false;
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
        return true;
    }

}

?>
