<?php
include_once "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php";

$fieldname = getvalescaped("fieldname","");
$fieldref = getvalescaped("fieldref",0,true);

if (!(checkperm("f{$fieldref}") || (checkperm('f*') && !checkperm("f-{$fieldref}"))))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit();
    }

?>

<div class="BasicsBox">
    <div class="HelpHeader">
        <div class="backtoresults">
            <a href="#" class="closeLink fa fa-times" id="cat_tree_close" title="<?php echo $lang["close"] ?>"></a>
        </div>
        <h1><?php echo $lang["fieldtype-category_tree"]; ?></h1>
    </div>
    <div id="cat_tree_selection">
    </div>
    <script type = "text/javascript">
        // Show tree and move into Modal
        jQuery('#cattree_<?php echo $fieldname;?>').show().insertAfter('#cat_tree_selection');
        // Hide and move tree back then close Modal
        jQuery('#modal_overlay').attr('onclick','').unbind('click');
        jQuery('#modal_overlay, #cat_tree_close').click(function()
            {
            document.getElementById('cattree_<?php echo $fieldname;?>').style.display='none';
            jQuery('#cattree_<?php echo $fieldname;?>').insertAfter('#nodes_searched_<?php echo $fieldref; ?>_statusbox');
            ModalClose();
            return false;
            });
    </script>
</div>

<?