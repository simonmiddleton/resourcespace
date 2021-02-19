<!-- Edit icon -->
<?php
// The permissions check here is intentionally more basic. It doesn't check edit_filter as this would be computationally intensive
// when displaying many resources. As such this is a convenience feature for users that have system-wide edit access to the given
// access level.
if($search_results_edit_icon && checkperm("e" . $result[$n]["archive"]) && !hook("iconedit")) 
        { 
        if ($allow_share && ($k=="" || $internal_share_access)) 
                { ?>
                        <a aria-hidden="true" class="fa fa-pencil"
                                href="<?php echo str_replace("view.php","edit.php",$url) ?>"  
                                onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" 
                                title="<?php echo $lang["action-editmetadata"]?>"
                        ></a>
                <?php
                }
        } ?>	
          

<!-- Collection comment icon -->
<?php 
if($k=="" || $internal_share_access)
        {
        // $collection_reorder_caption is an older config, although no longer in config.default.php some older systems may still have it set.
        if (((isset($collection_reorder_caption) && $collection_reorder_caption) || $collection_commenting) && (substr($search,0,11)=="!collection")) 
                { ?>
                        <a aria-hidden="true" class="fa fa-comment"
                                href="<?php echo $baseurl_short?>pages/collection_comment.php?ref=<?php echo urlencode($ref)?>&collection=<?php echo urlencode(trim(substr($search,11)))?>"
                                onClick="return ModalLoad(this,true);" 
                                title="<?php echo $lang["addorviewcomments"]?>"
                        ></a>
                </span>
                <?php 
                } 
        } 
hook("largesearchicon");
?>
   
<!-- Preview icon -->
<?php 
if (!hook("replacefullscreenpreviewicon"))
        {
        if ($result[$n]["has_image"]==1)
                { ?>
                        <a aria-hidden="true" class="fa fa-expand"
                                onClick="return CentralSpaceLoad(this,true);"
                                href="<?php echo $baseurl_short?>pages/preview.php?from=search&amp;ref=<?php echo urlencode($ref)?>&amp;ext=<?php echo $result[$n]["preview_extension"]?>&amp;search=<?php echo urlencode($search)?>&amp;offset=<?php echo urlencode($offset)?>&amp;order_by=<?php echo urlencode($order_by)?>&amp;sort=<?php echo urlencode($sort)?>&amp;archive=<?php echo urlencode($archive)?>&amp;k=<?php echo urlencode($k)?>" 
                                title="<?php echo $lang["fullscreenpreview"]?>"
                        ></a>
                <?php 
                }
        } /* end hook replacefullscreenpreviewicon */?>

<!-- Email icon -->
<?php 
if(!hook("iconemail")) 
        { 
        if ($allow_share && ($k=="" || $internal_share_access)) 
                { ?>
                        <a aria-hidden="true" class="fa fa-share-alt"
                                href="<?php echo $baseurl_short?>pages/resource_share.php?ref=<?php echo urlencode($ref)?>&amp;search=<?php echo urlencode($search)?>&amp;offset=<?php echo urlencode($offset)?>&amp;order_by=<?php echo urlencode($order_by)?>&amp;sort=<?php echo urlencode($sort)?>&amp;archive=<?php echo urlencode($archive)?>&amp;k=<?php echo urlencode($k)?>"  
                                onClick="return CentralSpaceLoad(this,true);"  
                                title="<?php echo $lang["share-resource"]?>"
                        ></a>
                <?php 
                }
        } ?>
        
<!-- Remove from collection icon -->
<?php 
if(!checkperm('b') && ($k == '' || $internal_share_access))
    {
    $col_link_class = ['fa-minus-circle'];
    if(
        isset($usercollection_resources)
        && is_array($usercollection_resources)
        && !in_array($ref, $usercollection_resources)
    )
        {
        $col_link_class[] = 'DisplayNone';
        }

    $onclick = 'toggle_addremove_to_collection_icon(this);';
    echo remove_from_collection_link($ref, $search, implode(' ', array_merge(['fa'], $col_link_class)), $onclick) . '</a>';
    }
    ?>
        
<!-- Add to collection icon -->
<?php
if(!hook('iconcollect') && $pagename!="collections")
    {
    if(!checkperm('b') && ('' == $k || $internal_share_access) && !in_array($result[$n]['resource_type'], $collection_block_restypes))
        {
        $col_link_class = (2 == $userrequestmode || 3 == $userrequestmode ? ['fa-shopping-cart'] : ['fa-plus-circle']);
        if(
            isset($usercollection_resources)
            && is_array($usercollection_resources)
            && in_array($ref, $usercollection_resources)
        )
            {
            $col_link_class[] = 'DisplayNone';
            }

        $onclick = 'toggle_addremove_to_collection_icon(this);';
        echo add_to_collection_link($ref, $search, $onclick, '', implode(' ', array_merge(['fa'], $col_link_class))) . '</a>';
        }
    } # end hook iconcollect
    ?>

<div class="clearer"></div>
