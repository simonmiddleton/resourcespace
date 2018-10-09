<?php
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";

if (!checkperm("a"))
	{
	exit ("Permission denied.");
	}


$filter_edit_url  = $baseurl . "pages/admin/admin_filter_edit.php";

include "../../include/header.php";


?>


<div id="CentralSpaceContainer">
    <div id="CentralSpace">

        <div class="BasicsBox">


            <h1><?php echo $lang["filters_manage"] ?></h1>
            	
            <div class="Listview">
            <table id="filter_list_table" border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
            <tbody>
                <tr class="ListviewTitleStyle">
                    <td>property-reference'</td>
                        <td><a href="http://images.neilpc/pages/admin/admin_resource_type_fields.php?restypefilter=&amp;field_order_by=title&amp;field_sort=desc&amp;find=" onclick="return CentralSpaceLoad(this);">Name</a>
                    </td>
                    <td><div class="ListTools">Tools</div></td>
                </tr>

            <?php
            for ($n=0;$n<count($fields);$n++)
            {
            ?>
            <tr class="resource_type_field_row" id="field_sort_<?php echo $fields[$n]["ref"];?>">
                <td>
                    <?php echo str_highlight ($fields[$n]["ref"],$find,STR_HIGHLIGHT_SIMPLE);?>
                </td>   
                <td>
                    <div class="ListTitle">
                          <a href="<?php echo $baseurl . "/pages/admin/admin_resource_type_field_edit.php?ref=" . $fields[$n]["ref"] . "&restype=" . $restypefilter . "&field_order_by=" . $field_order_by . "&field_sort=" . $field_sort . "&find=" . urlencode($find) . "&backurl=" . urlencode($url) ?>" onClick="jQuery('#resource_type_field_table_body').sortable('cancel');return CentralSpaceLoad(this,true);"><span><?php echo str_highlight (i18n_get_translated($fields[$n]["title"]),$find,STR_HIGHLIGHT_SIMPLE);?></span></a>
                    </div>
                </td>
                <td>        
                    <?php if(isset($arr_restypes[$fields[$n]["resource_type"]])){echo i18n_get_translated($arr_restypes[$fields[$n]["resource_type"]]);} else {echo $fields[$n]["resource_type"];}?>
                </td>
            <?php if (!hook('replacenamecolumn')) {
                ?><td>
                    <?php echo str_highlight($fields[$n]["name"],$find,STR_HIGHLIGHT_SIMPLE);?>
                </td><?php
            }?>
                <td>        
                    <?php echo ($fields[$n]["type"]!="")?$lang[$field_types[$fields[$n]["type"]]]:$lang[$field_types[0]];  // if no value it is treated as type 0 (single line text) ?>
                </td>
            <?php if (!hook('replacetabnamecolumn')) {
                ?><td>
                    <?php echo str_highlight(i18n_get_translated($fields[$n]["tab_name"]),$find,STR_HIGHLIGHT_SIMPLE);?>
                </td><?php
            }?>
                <td>
                    <div class="ListTools">
                      
                      <?php 
                    if($field_order_by=="order_by")
                        {
                        ?>      
                        <a href="javascript:void(0)" class="movelink movedownlink" <?php if($n==count($fields)-1){ ?> disabled <?php } ?>><?php echo LINK_CARET ?><?php echo $lang['action-move-down'] ?></a>
                        <a href="javascript:void(0)" class="movelink moveuplink" <?php if($n==0){ ?> disabled <?php } ?>><?php echo LINK_CARET ?><?php echo $lang['action-move-up'] ?></a>
                        <?php
                        }
                        ?>
                    
                    
                        <a href="<?php echo $baseurl . "/pages/admin/admin_copy_field.php?ref=" . $fields[$n]["ref"] . "&backurl=" . $url?>" onClick="CentralSpaceLoad(this,true)" ><?php echo LINK_CARET ?><?php echo $lang["copy"] ?></a>
                        <a href="<?php echo $baseurl . "/pages/admin/admin_resource_type_field_edit.php?ref=" . $fields[$n]["ref"] . "&backurl=" . $url?>" onClick="jQuery('#resource_type_field_table_body').sortable('cancel');return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["action-edit"]?> </a>
                        <a href="#"
                           onClick='
                                event.preventDefault();
        
                                if(confirm("<?php echo $lang["confirm-deletion"]; ?>"))
                                    {
                                    var post_data = {
                                        ajax: true,
                                        ref: <?php echo urlencode($fields[$n]['ref']); ?>,
                                        delete: <?php echo urlencode($fields[$n]['ref']); ?>,
                                        confirmdelete: true,
                                        <?php echo generateAjaxToken('delete_metadata_field'); ?>
                                    };
        
                                    jQuery.post("<?php echo $baseurl; ?>/pages/admin/admin_resource_type_field_edit.php", post_data, function(response) {
                                        if(response.deleted)
                                            {
                                            var redirect_link = document.createElement("a");
                                            redirect_link.href = "<?php echo $baseurl; ?>/pages/admin/admin_resource_type_fields.php?deleted=" + response.deleted + "&restypefilter=<?php echo urlencode($restypefilter)?>&field_order_by=<?php echo urlencode($field_order_by)?>&field_sort=<?php echo urlencode($field_sort)?>&find=<?php echo urlencode($find)?>";
                                            CentralSpaceLoad(redirect_link, true);
                                            }
                                    }, "json"); 
        
                                    return false;
                                    }
                                else
                                    {
                                    return false;
                                    }
                            '><?php echo LINK_CARET ?><?php echo $lang["action-delete"] ?></a>
                         
                    </div>
                </td>
            </tr>
            <?php
            }?>


            </table>
            </div>
        
        </div> <!-- End of BasicsBox -->

    </div> <!-- End of CentralSpace -->

</div> <!-- End of CentralSpaceContainer -->


<?php


include("../../include/footer.php");