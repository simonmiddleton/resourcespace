<?php

include "../../include/db.php";
include "../../include/authenticate.php";
if(!checkperm("a")){exit($lang["error-permissiondenied"]);}

$restype_order_by=getval("restype_order_by","ref");
$restype_sort=strtolower(getval("restype_sort","asc") == "asc") ? "asc" : "desc";

$url_params = array("restype_order_by"=>$restype_order_by,"restype_sort"=>$restype_sort);
$url=generateURL($baseurl . "/pages/admin/admin_resource_types.php",$url_params);

$sql_restype_order_by=$restype_order_by=="order_by"?"CAST(order_by AS UNSIGNED)":$restype_order_by;

$backurl=getval("backurl","");
if($backurl=="")
    {
    $backurl=$baseurl . "/pages/admin/admin_home.php";
    }
    
$newtype=getval("newtype","");
if ($newtype!="" && enforcePostRequest(false))
	{
	$new = create_resource_type($newtype);
    redirect($baseurl_short."pages/admin/admin_resource_type_edit.php?ref=" . $new);
	}

$resource_types=get_resource_types();

foreach($resource_types as &$resource_type)
    {
    $resource_type["fieldcount"] = count($resource_type["resource_type_fields"]);
    }

// Sort resource types
if(isset($resource_types[0][$restype_order_by]))
    {
    usort($resource_types, function ($a, $b) use ($restype_order_by, $restype_sort) {return ($restype_sort == "asc" ? $a[$restype_order_by] <=> $b[$restype_order_by] : $b[$restype_order_by] <=> $a[$restype_order_by]);});
    }

include "../../include/header.php";

function addColumnHeader($orderName, $labelKey)
    {
    global $baseurl, $url, $restype_order_by, $restype_sort, $find, $lang;

    if ($restype_order_by == $orderName)
        {
        $arrow = '<span class="' . strtoupper($restype_sort) . '"></span>';
        $linksort = ($restype_sort=="asc") ? 'desc' : 'asc';
        }
    else
        {
        $arrow = '';
        $linksort = 'asc';
        }

    ?><td><a href="<?php echo $baseurl ?>/pages/admin/admin_resource_types.php?restype_order_by=<?php echo htmlspecialchars($orderName) ?>&restype_sort=<?php echo $linksort;
            ?>&find=<?php echo urlencode((string)$find)?>&backurl=<?php echo urlencode((string)$url) ?>" onClick="return CentralSpaceLoad(this);"><?php
            echo htmlspecialchars($lang[$labelKey]) . $arrow ?></a></td>

        <?php

        }
?>	

<div class="BasicsBox">
<h1><?php echo htmlspecialchars($lang["resource_types_manage"]); ?></h1>
<?php
	$links_trail = array(
	    array(
	        'title' => $lang["systemsetup"],
	        'href'  => $baseurl_short . "pages/admin/admin_home.php",
			'menu' =>  true
	    ),
	    array(
	        'title' => $lang["resource_types_manage"],
			'help'  => "resourceadmin/resource-types"
	    )
	);

	renderBreadcrumbs($links_trail);
?>
  
  <?php
  $introtext=text("introtext");
  if($introtext!=""){ echo "<p>" . htmlspecialchars(text("introtext")) . "</p>";}
  
$allow_reorder=false;
// Allow sorting if we are ordering a single resource type, or if $use_order_by_tab_view is true (which means order_by values are across all resource types) and we can see all fields
if($restype_order_by=="order_by"){$allow_reorder=true;}

if(!$allow_reorder)
  {?>
  <a href="<?php echo $baseurl . "/pages/admin/admin_resource_types.php?restype_order_by=order_by&restype_sort=asc" ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo htmlspecialchars($lang["admin_resource_type_reorder_mode"]) ?></a></p>  
  <?php
  }
  ?>

<div class="FormError" id="PageError"
  <?php
  if (!isset($error_text)) { ?> style="display:none;"> <?php }
  else { echo ">" . htmlspecialchars($error_text) ; } ?>
</div>

<div class="Listview ListviewTight">
<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
<tr class="ListviewTitleStyle">

<?php
addColumnHeader('ref', 'property-reference');
addColumnHeader('name', 'property-name');
addColumnHeader('fieldcount', 'admin_resource_type_field_count');
?>

<td><div class="ListTools"><?php echo htmlspecialchars($lang["tools"]) ?></div></td>
</tr>
<tbody id="resource_type_table_body">
<?php
for ($n=0;$n<count($resource_types);$n++)
    {
    ?>
    <tr class="resource_type_row" id="restype_sort_<?php echo $resource_types[$n]["ref"];?>" >
        <td>
            <?php echo $resource_types[$n]["ref"];?>
        </td>	
        <td>
            <div class="ListTitle">
                    <a href="<?php echo $baseurl_short?>pages/admin/admin_resource_type_edit.php?ref=<?php echo $resource_types[$n]["ref"]?>&backurl=<?php echo urlencode($url) ?>" onClick="return CentralSpaceLoad(this,true);">
                    <?php echo htmlspecialchars(i18n_get_translated($resource_types[$n]["name"]));?>
                    </a>
                </a>
            </div>
        </td>
        <td>
            <div class="ListTitle">
                <?php
                if($resource_types[$n]["resource_type_fields"]!="")
                    {
                    ?>
                    <a href="<?php echo $baseurl_short?>pages/admin/admin_resource_type_fields.php?restypefilter=<?php echo $resource_types[$n]["ref"] . "&backurl=" . urlencode($url) ?>" onClick="return CentralSpaceLoad(this,true);">
                    <?php echo $resource_types[$n]["fieldcount"] ?>
                    </a>
                    <?php
                    }
                else
                    {
                    echo "0";
                    }?>
            </div>
        </td>
        <td>
            <div class="ListTools">
                <?php 
                if($restype_order_by=="order_by")
                        {
                        ?>		
                        <a href="javascript:void(0)" class="movelink movedownlink" <?php if($n==count($resource_types)-1){ ?> disabled <?php } ?>><?php echo LINK_CARET . htmlspecialchars($lang['action-move-down']) ?></a>
                        <a href="javascript:void(0)" class="movelink moveuplink" <?php if($n==0){ ?> disabled <?php } ?>><?php echo LINK_CARET . htmlspecialchars($lang['action-move-up'])?></a>
                        <?php
                        }
                    ?>
                <a href="<?php echo $baseurl ?>/pages/admin/admin_resource_type_edit.php?ref=<?php echo $resource_types[$n]["ref"]?>&backurl=<?php echo urlencode($url) ?>" onClick="return CentralSpaceLoad(this,true);"><i class="fas fa-edit"></i>&nbsp;<?php echo $lang["action-edit"]?> </a>

                <a href="<?php echo $baseurl ?>/pages/admin/admin_resource_type_fields.php?restypefilter=<?php echo $resource_types[$n]["ref"] . "&backurl=" . urlencode($url) ?>" onClick="return CentralSpaceLoad(this,true);"><i class="fas fa-bars"></i>&nbsp;<?php echo htmlspecialchars($lang["metadatafields"]) ?> </a>
                
            </div>
        </td>
    </tr>
    <?php
    }
?>
</tbody>
</table>
</div>
</div>

<div class="BasicsBox">
    <form method="post" action="<?php echo $baseurl_short?>pages/admin/admin_resource_types.php"  onSubmit="return CentralSpacePost(this,true);" >
        <?php generateFormToken("admin_resource_types"); ?>
        <div class="Question">
            <label for="newtype"><?php echo htmlspecialchars($lang["admin_resource_type_create"]) ?></label>
            <div class="tickset">
                <div class="Inline"><input type=text name="newtype" id="newtype" maxlength="100" class="shrtwidth" /></div>
                <div class="Inline"><input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo htmlspecialchars($lang["create"]) ?>&nbsp;&nbsp;" /></div>
            </div>
            <div class="clearerleft"> </div>
        </div>
        <input type="hidden" name="save" id="save" value="yes"/>
    </form>
</div>

<script>
  
function ReorderResourceTypes(idsInOrder)
    {
    var newOrder = [];
    jQuery.each(idsInOrder, function() {
        newOrder.push(this.substring(13));
        }); 

    jQuery.ajax({
        type: 'POST',
        url: '<?php echo $baseurl_short?>pages/admin/ajax/update_resource_type_order.php?reorder=true',
    data: {
        order:JSON.stringify(newOrder),
        <?php echo generateAjaxToken('ReorderResourceTypes'); ?>
    },
        success: function() {
            jQuery('.movedownlink:last').prop("disabled",true);
            jQuery('.moveuplink:first').prop("disabled",true);
            jQuery('.movedownlink:not(:last)').prop("disabled",false);
            jQuery('.moveuplink:not(:first)').prop("disabled",false);
            }
        });
    }

function enableRestypesort(){
    var fixHelperModified = function(e, tr) {
            var $originals = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function(index)
            {
            jQuery(this).width($originals.eq(index).width())
            });
            return $helper;
        };

        //jQuery('.resource_type_row').draggable({ axis: "y" });
        //jQuery('.resource_type_row').droppable();
        
        jQuery('#resource_type_table_body').sortable({
                items: ".resource_type_row",
                axis: "y",
                cursor: 'move',
                opacity: 0.6, 
                stop: function(event, ui) {
                    //alert("HERE");
                    <?php
                    if($allow_reorder)
                    {
                    ?>
                    var idsInOrder = jQuery('#resource_type_table_body').sortable("toArray");
                    //alert(idsInOrder);
                    ReorderResourceTypes(idsInOrder);
                    <?php
                    }
                else
                    {
                    $errormessage=$lang["admin_resource_type_reorder_information_tab_order"];
                    ?>
                    jQuery('#PageError').html("<?php echo htmlspecialchars($errormessage) ?>").show();
                    jQuery( "#resource_type_table_body" ).sortable( "cancel" );
                    <?php
                    }
                    ?>

                    
                    },
                helper: fixHelperModified
                
            }).disableSelection();
	}
	
enableRestypesort();

jQuery(".moveuplink").click(function(e) {
    if (jQuery(this).prop('disabled')) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
        jQuery(this).parents(".resource_type_row").insertBefore(jQuery(this).parents(".resource_type_row").prev());
        var idsInOrder = jQuery('#resource_type_table_body').sortable("toArray");
        ReorderResourceTypes(idsInOrder);
        
    });
   
jQuery(".movedownlink").click(function(e) {
    if (jQuery(this).prop('disabled')) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
        jQuery(this).parents(".resource_type_row").insertAfter(jQuery(this).parents(".resource_type_row").next());
        var idsInOrder = jQuery('#resource_type_table_body').sortable("toArray");
        ReorderResourceTypes(idsInOrder);
    });
	
</script>

<?php
include "../../include/footer.php";