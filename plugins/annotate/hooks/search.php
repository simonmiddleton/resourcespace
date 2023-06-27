<?php
function HookAnnotateSearchIcons($collections = false)
    {
    global $baseurl, $k, $search, $archive, $sort, $offset, $order_by, $result, $n, $lang, $k, $annotate_public_view;

    if(!('' == $k) && !$annotate_public_view)
        {
        return false;
        }

    if(!is_array($result))
        {
        ?>
        <div class="clearerleft"></div>
        <div class="ResourcePanelInfo">
            <span class="IconUserRatingSpace"></span>
        </div>
        <?php 
        
        return true;
        }

    if(!$collections && isset($result[$n]) && isset($result[$n]['annotation_count']) && null != $result[$n]['annotation_count'] && 0 != $result[$n]['annotation_count'] && 'pdf' != $result[$n]['file_extension'])
        {
        ?>
        <div class="clearerleft"></div>
        <div class="ResourcePanelInfo">
            <span class="IconUserRatingSpace" style="width:0px;"></span>
            <img src="<?php echo $baseurl?>/plugins/annotate/lib/jquery/images/asterisk_yellow.png" height="10"/>
            <a href="<?php echo generateURL($baseurl . '/pages/view.php', ['annotate' => true, 'ref' => $result[$n]['ref'], 'k' => $k, 'search' => $search, 'offset' => $offset, 'order_by' => $order_by, 'sort' => $sort, 'archive' => $archive])?>" onClick="return CentralSpaceLoad(this,true);"><?php echo htmlspecialchars($result[$n]['annotation_count']==1 ? $lang["note-1"] : str_replace("%number", $result[$n]['annotation_count'], $lang["note-2"])); ?></a>
        </div>
        <?php 
        }
    else
        {
        ?>
        <div class="clearerleft"></div>
        <div class="ResourcePanelInfo">
            <span class="IconUserRatingSpace"></span>
            &nbsp;&nbsp;
        </div>
        <?php
        }
    }

function HookAnnotateSearchThumbs_resourceshell_height()
    {
    global $baseurl, $k, $search, $archive, $sort, $offset, $order_by, $result, $n, $lang, $k, $annotate_public_view, $thumbs_displayed_fields_height, $field_height;

    if(!('' != $k && !$annotate_public_view))
        {
        $thumbs_displayed_fields_height = $thumbs_displayed_fields_height + $field_height;
        }
    
    }
