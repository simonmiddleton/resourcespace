<?php hook('add_to_resource_tools', '', array($ref)); ?>

<!-- Edit icon -->
<?php
// The permissions check here is intentionally more basic. It doesn't check edit_filter as this would be computationally intensive
// when displaying many resources. As such this is a convenience feature for users that have system-wide edit access to the given
// access level.
if (!hook("iconedit") && (checkperm("e" . $result[$n]["archive"]) || ($edit_access_for_contributor && $userref==$result[$n]["created_by"]))) { 
    if ($allow_share && ($k=="" || $internal_share_access)) { ?>
        <a class="fa fa-pencil"
            href="<?php echo str_replace("view.php", "edit.php", $url) ?>"  
            onClick="return <?php echo ($resource_view_modal ? "Modal" : "CentralSpace") ?>Load(this, true);" 
            title="<?php echo escape($lang["action-editmetadata"] . (($resource_view_title != "") ? " - " . $resource_view_title : "")) ?>">
        </a><?php
    }
} ?>	

<!-- Collection comment icon -->
<?php 
if ($k == "" || $internal_share_access) {
    // $collection_reorder_caption is an older config, although no longer in config.default.php some older systems may still have it set.
    if (((isset($collection_reorder_caption) && $collection_reorder_caption) || $collection_commenting) && (substr($search, 0, 11) == "!collection")) { ?>
        <a class="fa fa-comment"
            href="<?php echo generateURL(
                $baseurl_short . 'pages/collection_comment.php',
                [
                    'ref' => $ref,
                    'collection' => trim(substr($search, 11))
                ]) ?>"
            onClick="return ModalLoad(this,true);" 
            title="<?php echo escape($lang["addorviewcomments"] . (($resource_view_title != "") ? " - " . $resource_view_title : "")) ?>">
        </a>
        </span><?php 
    } 
}
hook("largesearchicon");
?>

<!-- Preview icon -->
<?php 
if (!hook("replacefullscreenpreviewicon")) {
    if ($result[$n]["has_image"]==1) { ?>
        <a class="fa fa-expand"
            onClick="return CentralSpaceLoad(this,true);"
            href="<?php echo generateURL(
                $baseurl_short . 'pages/preview.php',
                [
                    'from' => 'search',
                    'ref' => $ref,
                    'ext' => $result[$n]['preview_extension'],
                    'search' => $search,
                    'offset' => $offset,
                    'order_by' => $order_by,
                    'sort' => $sort,
                    'archive' => $archive,
                    'k' => $k
                ]) ?>"
            title="<?php echo escape($lang["fullscreenpreview"] . (($resource_view_title != "") ? " - " . $resource_view_title : "")) ?>">
        </a><?php 
    }
} /* end hook replacefullscreenpreviewicon */?>

<!-- Share icon -->
<?php 
if (!hook("iconemail")) { 
    if ($allow_share && ($k == "" || $internal_share_access)) { ?>
        <a class="fa fa-share-alt"
            href="<?php echo generateURL(
                $baseurl_short . 'pages/resource_share.php',
                [
                    'ref' => $ref,
                    'search' => $search,
                    'offset' => $offset,
                    'order_by' => $order_by,
                    'sort' => $sort,
                    'archive' => $archive,
                    'k' => $k
                ]) ?>"
            onClick="return CentralSpaceLoad(this,true);"  
            title="<?php echo escape($lang["share-resource"] . (($resource_view_title != "") ? " - " . $resource_view_title : "")) ?>">
        </a>
        <?php 
    }
} ?>

<!-- Remove from collection icon -->
<?php 
$basket = $userrequestmode == 2 || $userrequestmode == 3;

if (!checkperm('b') && ($k == '' || $internal_share_access)) {
    $col_link_class = ['fa-minus-circle'];

    if (
        isset($usercollection_resources)
        && is_array($usercollection_resources)
        && !in_array($ref, $usercollection_resources)
    ) {
        $col_link_class[] = 'DisplayNone';
    }

    $onclick = 'toggle_addremove_to_collection_icon(this);';
    echo remove_from_collection_link($ref, implode(' ', array_merge(['fa'], $col_link_class)), $onclick, $basket, $resource_view_title) . '</a>';
    }
?>

<!-- Add to collection icon -->
<?php
if (!hook('iconcollect') && $pagename!="collections") {
    if (!checkperm('b') && ('' == $k || $internal_share_access) && !in_array($result[$n]['resource_type'], $collection_block_restypes)) {
        $col_link_class = ($basket ? ['fa-shopping-cart'] : ['fa-plus-circle']);

        if (
            isset($usercollection_resources)
            && is_array($usercollection_resources)
            && in_array($ref, $usercollection_resources)
        ) {
            $col_link_class[] = 'DisplayNone';
        }

        $onclick = 'toggle_addremove_to_collection_icon(this);';
        echo add_to_collection_link($ref, $onclick, '', implode(' ', array_merge(['fa'], $col_link_class)), $resource_view_title) . '</a>';
        }
    } # end hook iconcollect
?>

<div class="clearer"></div>
