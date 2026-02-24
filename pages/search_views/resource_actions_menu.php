<?php

//Add to/Remove from collection
//Share
//Collection comment
//Edit
//Delete

$draw_menu = false;

$add_collection_item = false;
$add_collection_item_hidden = false;
$remove_collection_item = false;
$remove_collection_item_hidden = false;
$share_item = false;
$collection_comment_item = false;
$edit_item = false;
$delete_item = false;
$delete_perm_item = false;

// Remove from collection
if (!checkperm('b') && ($k == '' || $internal_share_access)) {

    $remove_collection_item = true;

    if (
        isset($usercollection_resources)
        && is_array($usercollection_resources)
        && !in_array($ref, $usercollection_resources)
    ) {
        $remove_collection_item_hidden = true;
    }


    //$onclick = 'toggle_addremove_to_collection_icon(this);';
    //echo remove_from_collection_link($ref, implode(' ', $col_link_class), $onclick, 0, $resource_view_title) . '</a>';
}

// Add to collection
if (
    $pagename != "collections"
    && !checkperm('b')
    && !in_array($result[$n]['resource_type'], $collection_block_restypes)
    && ('' == $k || $internal_share_access)
) {
    $add_collection_item = true;

    if (
        isset($usercollection_resources)
        && is_array($usercollection_resources)
        && in_array($ref, $usercollection_resources)
    ) {
        $add_collection_item_hidden = true;
    }

    //$onclick = 'toggle_addremove_to_collection_icon(this);';
    //echo add_to_collection_link($ref, $onclick, '', implode(' ', $col_link_class), $resource_view_title) . '</a>';
}

$draw_menu = !$add_collection_item_hidden && !$remove_collection_item_hidden;

// Share
if ($thumbs_share && $allow_share && ($k == "" || $internal_share_access)) {
    $share_item = true;
    $draw_menu = true;
}

// Collection comment
if (
    ($k == "" || $internal_share_access)
    && $collection_commenting
    && (substr($search, 0, 11) == "!collection")
) {
    $collection_comment_item = true;
    $draw_menu = true;
}

// Edit
// The permissions check here is intentionally more basic. It doesn't check edit_filter as this would be computationally intensive
// when displaying many resources. As such this is a convenience feature for users that have system-wide edit access to the given
// access level.
if (
    $thumbs_edit
    &&
    (
    checkperm("e" . $result[$n]["archive"])
    || ($edit_access_for_contributor && $userref == $result[$n]["created_by"])
    )
    && $allow_share
    && ($k == "" || $internal_share_access)
) {
    $edit_item = true;
    $draw_menu = true;
}

if (!checkperm("D") || hook('check_single_delete')) {
    if (isset($resource_deletion_state) && $result[$n]["archive"] == $resource_deletion_state) {
        $delete_perm_item = true;
        $draw_menu = true;
    } else {
        $delete_item = true;
        $draw_menu = true;
    }
}
                                                    
    

//hook('add_to_resource_tools', '', array($ref));










if ($draw_menu) { ?>

<div class="resource-card-action-menu" aria-haspopup="menu" aria-expanded="false">
    <i class="icon-ellipsis-vertical" aria-hidden="true"></i>
</div>
<div class="flyout-menu effect-style-elevation-level-1-shadow" role="menu" aria-hidden="true">
    <div class="menu-items" role="menu" aria-hidden="true">
        <?php if ($add_collection_item) { ?>
        <div class="menu-item <?php echo $add_collection_item_hidden ? "DisplayNone" : ""; ?>" role="menuitem" data-action="add">
            <i class="icon-circle-plus"></i>
            <span>Add to current collection</span>
        </div>
        <?php } ?>
        <?php if ($remove_collection_item) { ?>
        <div class="menu-item <?php echo $remove_collection_item_hidden ? "DisplayNone" : ""; ?>" role="menuitem" data-action="remove">
            <i class="icon-circle-minus"></i>
            <span>Remove from current collection</span>
        </div>
        <?php } ?>
        <?php if ($collection_comment_item) { ?>
        <div class="menu-item" 
            role="menuitem" 
            data-action="collection-comment"
            data-url="<?php echo generateURL($baseurl_short . 'pages/collection_comment.php', ['ref' => $ref, 'collection' => trim(substr($search, 11))]); ?>"
            onclick="return ModalLoad(this.dataset.url, true);" 
            title="<?php echo escape($lang["addorviewcomments"] . (($resource_view_title != "") ? " - " . $resource_view_title : "")) ?>">
                <i class="icon-message-circle"></i>
                <span>Collection comment</span>
        </div>
        <?php } ?>
        <?php if ($share_item) { ?>
        <div class="menu-item" 
            role="menuitem" 
            data-action="share"
            data-url="<?php echo generateURL($baseurl_short . 'pages/resource_share.php', ['ref' => $ref,'search' => $search,'offset' => $offset,'order_by' => $order_by,'sort' => $sort,'archive' => $archive,'k' => $k]); ?>"
            onclick="return CentralSpaceLoad(this.dataset.url, true);"
            title="<?php echo escape($lang["share-resource"] . (($resource_view_title != "") ? " - " . $resource_view_title : "")) ?>">
                <i class="icon-share"></i>
                <span>Share</span>
        </div>
        <?php } ?>
        <?php if ($edit_item) { ?>
        <div class="menu-item"
            role="menuitem"
            data-action="edit"
            data-url="<?php echo str_replace("view.php", "edit.php", $url); ?>"
            onclick='return <?php echo $resource_view_modal ? "Modal" : "CentralSpace"; ?>Load(this.dataset.url, true);'
            title="<?php echo escape($lang["action-editmetadata"] . (($resource_view_title != "") ? " - " . $resource_view_title : "")) ?>">
                <i class="icon-pencil"></i>
                <span>Edit</span>
        </div>
        <?php } ?>
        <?php if ($delete_item || $delete_perm_item) { ?>
        <div class="menu-item menu-item-danger" role="menuitem" data-action="delete">
            <i class="icon-trash-2"></i>
            <span><?php echo $delete_perm_item ? "Delete permanently" : "Delete"; ?></span>
        </div>
        <div class="menu-confirm <?php echo $delete_perm_item ? "is-permanent" : ""; ?>">
            <div class="menu-confirm-inner">
                <p class="menu-confirm-text"><?php echo $delete_perm_item ? "Are you sure? This action cannot be reversed." : "Are you sure you wish to delete this resource?"; ?></p>
                <?php                
                $urlparams = array_merge($searchparams, ['text' => 'deleted', 'refreshcollection' => 'true']);
                $redirect_url = generateURL($baseurl_short . "pages/done.php", $urlparams); 
                ?>                
                <button 
                    class="menu-confirm-btn" 
                    type="button" 
                    data-action="confirm-delete"
                    onclick="api(
                            'delete_resource',
                            {'resource':'<?php echo (int) $ref?>'},
                            function(response){
                                ModalLoad('<?php echo $redirect_url ?>',true);
                            },
                            <?php echo escape(generate_csrf_js_object('delete_resource')); ?>
                        );">
                    <?php echo $delete_perm_item ? "Yes, delete permanently" : "Yes, delete resource"; ?>
                </button>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

<?php
}
?>
