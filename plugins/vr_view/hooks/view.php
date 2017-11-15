<?php

function HookVr_viewViewReplacepreviewlink()
    {
	global $resource, $baseurl_short, $urlparams, $modal, $lang;
    $use_vr_view = VrViewUseVR($resource);
    
    if(!$use_vr_view)
        {
        return false;
        }
    ?>
    <div id="previewimagewrapper">
        <a id="previewimagelink"
           class="enterLink"
           href="<?php echo generateURL($baseurl_short . "pages/preview.php", $urlparams, array("ext"=>$resource["preview_extension"])) . "&" . hook("previewextraurl") ?>"
           title="<?php echo $lang["fullscreenpreview"]; ?>"
           style="position:relative;"
           onclick="return ModalLoad(this,false,true,top);">
	<?php
    return true;
    }

    
function HookVr_viewViewRenderinnerresourcepreview()
    {
    global $resource, $ffmpeg_supported_extensions, $vr_view_restypes;
    if(VrViewUseVR($resource))
        {
        // Set this to prevent standard video preview of 360 video
        $resource['is_transcoding'] = 1;
        }
        
    // Return false so that preview support continues as normal without video. This saves bandwidth being used rendering a pointless preview
    return false;
    }
