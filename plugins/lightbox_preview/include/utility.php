<?php

function getPreviewURLForType($resource, $type, $alternative = -1, $page = 1)
    {
    global $baseurl, $use_watermark, $ffmpeg_preview_extension;

    if ($resource['file_extension'] == $ffmpeg_preview_extension)
        {
        return false;
        }
    else if(resource_has_access_denied_by_RT_size($resource['resource_type'], $type))
        {
        return $baseurl . '/gfx/' . get_nopreview_icon($resource['resource_type'], $resource['file_extension'], false);
        }

    $path = get_resource_path(
        $resource['ref'],
        true,
        $type,
        false,
        $resource['preview_extension'],
        -1,
        $page,
        $use_watermark,
        '',
        $alternative
    );

    if(!file_exists($path))
        {
        return false;
        }

    return get_resource_path(
        $resource['ref'],
        false,
        $type,
        false,
        $resource['preview_extension'],
        -1,
        $page,
        $use_watermark,
        '',
        $alternative);
    }

function getPreviewURL($resource, $alternative = -1, $page = 1)
    {
    if ($resource['has_image'] != 1)
        return false;

    // Try 'scr' first
    $url = getPreviewURLForType($resource, 'scr', $alternative, $page);
    if ($url == false || !resource_download_allowed($resource['ref'],'scr',$resource['resource_type'],$alternative))
        {
        // and then 'pre'
        $url = getPreviewURLForType($resource, 'pre', $alternative, $page);
        }

    return $url;
    }

function addLightBox($selector, $url = "", $title = "", $set = "")
    {
    ?>
    <script>
    jQuery(document).ready(function() {
        jQuery('<?php echo $selector ?>')
                <?php if ($url != "")
                    { ?>
                    .attr('href', '<?php echo $url ?>')
                    <?php }
                ?>
                .attr('data-title', "<?php echo escape(str_replace(array("\r","\n","\\")," ", strip_tags(i18n_get_translated($title)))); ?>")
                .attr('data-lightbox', 'lightbox<?php if ($set != "") {echo $set;} ?>');
    });
    </script>
    <?php
    }

?>
