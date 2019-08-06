<?php 
# Work out image to use.
if(isset($watermark))
    {
    $use_watermark = check_use_watermark();
    }
else
    {
    $use_watermark = false;	
    }

$thm_url = get_resource_path(
    $ref,
    false,
    'pre',
    false,
    $result[$n]['preview_extension'],
    true,
    1,
    $use_watermark,
    $result[$n]['file_modified']
);

if(isset($result[$n]['thm_url']))
    {
    $thm_url = $result[$n]['thm_url'];
    } # Option to override thumbnail image in results, e.g. by plugin using process_Search_results hook above
    ?>
    <a
        id="ResourceStrip<?php echo $ref ?>"
        class="ImageStripLink"
        href="<?php echo $url; ?>"  
        onClick="return <?php echo ($resource_view_modal ? 'Modal' : 'CentralSpace'); ?>Load(this, true);" 
        title=""
        ><?php 
            if(1 == $result[$n]['has_image'])
            {
            ?><img 
            src="<?php echo $thm_url; ?>" 
            class="ImageBorder ImageStrip" 
            alt=""
            /><?php }
            else 
                { ?><img class="ImageStrip" 
                    border=0 
                    src="<?php echo $baseurl_short; ?>gfx/<?php echo get_nopreview_icon($result[$n]['resource_type'], $result[$n]['file_extension'], false); ?>" 

                /><?php 
                } ?></a> 