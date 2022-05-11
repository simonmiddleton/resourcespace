<?php

include "../../include/db.php";

include "../../include/authenticate.php";

if (!checkperm("a"))
    {
    exit ("Permission denied.");
    }


$find=getvalescaped("find","");
$offset=getvalescaped("offset",0,true);
if (array_key_exists("find",$_POST)) {$offset=0;} # reset page counter when posting
    
    
$restypefilter=getval("restypefilter","");
$restypesfilter=($restypefilter != "")?array((int)$restypefilter):"";
$field_order_by=getvalescaped("field_order_by","order_by");
$field_sort=getvalescaped("field_sort","asc");

$backurl=getvalescaped("backurl","");
if($backurl=="")
    {
    $backurl=$baseurl . "/pages/admin/admin_home.php";
    }

$allow_reorder=false;
// Allow sorting if we are ordering metadata fields for all resource types (ie Resource type == "All" and $restypefilter=="")
if($restypefilter=="")
    {
    $allow_reorder=true;
    }

include "../../include/header.php";


$url_params = array("restypefilter"=>$restypefilter,
            "field_order_by"=>$field_order_by,
            "field_sort"=>$field_sort,
            "find" =>$find);
$url=generateURL($baseurl . "/pages/admin/admin_resource_type_fields.php",$url_params);

// Common ResourceSpace URL params are used as an override when calling {@see generateURL()}
$common_rs_url_params = [
    'backurl' => $url,
];

if (getval("newfield","")!="" && enforcePostRequest(false))
    {
    $newfieldname = getvalescaped("newfield","");
    $newfieldtype = getval("field_type",0,true);    
    $newfieldrestype = getvalescaped("newfieldrestype",0,true);
    $new = create_resource_type_field($newfieldname, $newfieldrestype, $newfieldtype, "", true);
    redirect($baseurl_short . 'pages/admin/admin_resource_type_field_edit.php?ref=' . $new . '&newfield=true');
    }
    
    
    
function addColumnHeader($orderName, $labelKey)
    {
    global $baseurl, $group, $field_order_by, $field_sort, $find, $lang, $restypefilter, $url_params;

    if ($field_order_by == $orderName && $field_sort=="asc")
        $arrow = '<span class="DESC"></span>';
    else if ($field_order_by == $orderName && $field_sort=="desc")
        $arrow = '<span class="ASC"></span>';
    else
        $arrow = '';
        
    $newparams = array();
    $newparams["field_order_by"] = $orderName;
    $newparams["field_sort"] = ($field_sort=="desc" || $field_order_by=="order_by") ? 'asc' : 'desc';

    ?>
    <td><a href="<?php echo generateURL($baseurl . "/pages/admin/admin_resource_type_fields.php",$url_params,$newparams); ?>" onClick="return CentralSpaceLoad(this);"><?php
          echo $lang[$labelKey] . $arrow ?></a>
    </td>
    <?php
    }
      
    $links_trail = array(
        array(
            'title' => $lang["systemsetup"],
            'href'  => $baseurl_short . "pages/admin/admin_home.php"
        ),
        array(
            'title' => $lang["admin_resource_type_fields"],
            'help'  => "resourceadmin/configure-metadata-field"
        )
    );

  renderBreadcrumbs($links_trail);

  $introtext=text("introtext");
  if ($introtext!="")
    {
    echo "<p>" . text("introtext") . "</p>";
    }
 
$fields=get_resource_type_fields($restypesfilter, $field_order_by, $field_sort, $find, array(),true);
$resource_types=sql_query("select ref, name from resource_type order by order_by,ref", "schema");
$arr_restypes=array();
foreach($resource_types as $resource_type)
    {
    $arr_restypes[$resource_type["ref"]]=$resource_type["name"];
    }
$arr_restypes[0]=$lang["resourcetype-global_field"];
$arr_restypes[999]=$lang["resourcetype-archive_only"];

$results=count($fields);

?>


<div class="BasicsBox">



<div class="FormError" id="PageError"
  <?php
  if (!isset($error_text)) { ?> style="display:none;"> <?php }
  else { echo ">" . $error_text ; } ?>
</div>

<?php
 if($allow_reorder)
    {  ?>
<p><?php echo  $lang["admin_resource_type_field_reorder_information"] ?></p>   
<a href="<?php echo generateURL($baseurl . "/pages/admin/admin_resource_type_fields.php",$url_params,array("restypefilter" => (($use_order_by_tab_view) ? "" : $restypefilter),"field_order_by" => "order_by","fieldsort"=>"asc")); ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php if($use_order_by_tab_view){echo $lang["admin_resource_type_field_reorder_mode_all"];}else{echo $lang["admin_resource_type_field_reorder_mode"];}?></a></p>  
<?php
    } ?>

<form method="post" id="AdminResourceTypeFieldForm" onSubmit="return CentralSpacePost(this,true);"  action="<?php echo generateURL($baseurl . "/pages/admin/admin_resource_type_fields.php",$url_params); ?>" >
    <?php generateFormToken("AdminResourceTypeFieldForm"); ?>       
    <div class="Question">  
        <label for="restypefilter"><?php echo $lang["property-resource_type"]; ?></label>
        <div class="tickset">
          <div class="Inline">
          <select name="restypefilter" id="restypefilter" onChange="return CentralSpacePost(this.form,true);" >
            <option value=""<?php if ($restypefilter == "") { echo " selected"; } ?>><?php echo $lang["all"]; ?></option>
            <option value="0"<?php if ($restypefilter == "0") { echo " selected"; } ?>><?php echo $lang["resourcetype-global_field"]; ?></option>
            
            <?php
              for($n=0;$n<count($resource_types);$n++){
            ?>
            <option value="<?php echo $resource_types[$n]["ref"]; ?>"<?php if ($restypefilter == $resource_types[$n]["ref"]) { echo " selected"; } ?>><?php echo i18n_get_translated($resource_types[$n]["name"]); ?></option>
            <?php
              }
            ?>
            
            <option value="999"<?php if ($restypefilter == "999") { echo " selected"; } ?>><?php echo $lang["resourcetype-archive_only"]; ?></option>
            </select>
          </div>
        </div>
        <div class="clearerleft"> </div>
      </div>
</form>
    
    
<div class="Listview">
<table id="resource_type_field_table" border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
<tr class="ListviewTitleStyle">
<?php  

addColumnHeader('ref', 'property-reference');
addColumnHeader('title', 'property-title');
addColumnHeader('resource_type', 'property-resource_type');
if (!hook('replacenamecolumnheader'))
    addColumnHeader('name', 'property-shorthand_name');
addColumnHeader('type', 'property-field_type');
if (!hook('replacetabnamecolumnheader'))
    addColumnHeader('tab_name', 'property-tab_name');
?>
<td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
</tr>

<tbody id="resource_type_field_table_body">
<?php


for ($n=0;$n<count($fields);$n++)
    {
    ?>
    <tr class="resource_type_field_row <?php if ($fields[$n]["active"]==0) { ?>FieldDisabled<?php } ?>" id="field_sort_<?php echo $fields[$n]["ref"];?>">
        <td>
            <?php echo str_highlight ($fields[$n]["ref"],$find,STR_HIGHLIGHT_SIMPLE);?>
        </td>   
        <td>
            <div class="ListTitle">
                  <a href="<?php echo generateURL($baseurl . "/pages/admin/admin_resource_type_field_edit.php",$url_params, array("ref"=>$fields[$n]["ref"],"backurl"=>$url)); ?>" onClick="jQuery('#resource_type_field_table_body').sortable('cancel');return CentralSpaceLoad(this,true);"><span><?php echo str_highlight (i18n_get_translated($fields[$n]["title"]),$find,STR_HIGHLIGHT_SIMPLE);?></span></a>
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
            if($field_order_by=="order_by" && $allow_reorder)
                {
                ?>      
                <a href="javascript:void(0)" class="movelink movedownlink" <?php if($n==count($fields)-1){ ?> disabled <?php } ?>><?php echo LINK_CARET ?><?php echo $lang['action-move-down'] ?></a>
                <a href="javascript:void(0)" class="movelink moveuplink" <?php if($n==0){ ?> disabled <?php } ?>><?php echo LINK_CARET ?><?php echo $lang['action-move-up'] ?></a>
                <?php
                }
                ?>
            
            
                <a href="<?php echo generateURL("{$baseurl}/pages/admin/admin_copy_field.php", ['ref' => $fields[$n]["ref"]], $common_rs_url_params); ?>" onClick="CentralSpaceLoad(this,true)" ><?php echo LINK_CARET ?><?php echo $lang["copy"] ?></a>
                <a href="<?php echo generateURL("{$baseurl}/pages/admin/admin_resource_type_field_edit.php", ['ref' => $fields[$n]["ref"]], $common_rs_url_params); ?>" onClick="jQuery('#resource_type_field_table_body').sortable('cancel');return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["action-edit"]?> </a>
                <a href="<?php echo generateURL(
                    "{$baseurl}/pages/admin/admin_system_log.php",
                    [
                        'table' => 'resource_type_field',
                        'table_reference' => $fields[$n]['ref'],
                    ],
                    $common_rs_url_params
                ); ?>" onclick="return CentralSpaceLoad(this, true);"><?php echo LINK_CARET; ?><?php echo htmlspecialchars($lang["log"]); ?></a>
            </div>
        </td>
    </tr>
    <?php
    }
?>
</tbody>
</table>
</div>


<form method="post" id="AdminResourceTypeFieldForm2" onSubmit="return CentralSpacePost(this,true);"  action="<?php echo generateURL($baseurl . "/pages/admin/admin_resource_type_fields.php",$url_params); ?>" >
    <?php generateFormToken("AdminResourceTypeFieldForm2"); ?>
    <div class="Question">
            <label for="find"><?php echo $lang["find"]?></label>
            <div class="tickset">
             <div class="Inline"><input type=text name="find" id="find" value="<?php echo $find?>" maxlength="100" class="shrtwidth" /></div>
             <div class="Inline"><input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["searchbutton"]?>&nbsp;&nbsp;" /></div>
            <?php
            if ($find!="")
                {
                ?>
                <div class="Inline"><input name="resetform" class="resetform" type="submit" value="<?php echo $lang["clearbutton"]?>" onclick="CentralSpaceLoad('<?php echo generateURL($baseurl . "/pages/admin/admin_resource_type_fields.php",$url_params,array("find"=>"")); ?>',false);return false;" /></div>
                <?php
                }
            ?>
            </div>
            <div class="clearerleft"> </div>
        </div>
    
        <div class="Question">
            <label for="newfield"><?php echo $lang["admin_resource_type_field_create"]?></label>
            <div class="tickset">
             <input type="hidden" name="newfieldrestype" value="<?php echo htmlspecialchars($restypefilter) ?>""/>   
             <div class="Inline"><input type=text name="newfield" id="newtype" maxlength="100" class="shrtwidth" /></div>

            <div class="Inline"><select name="field_type" id="new_field_type_select" class="medwidth">
         
            <?php
            foreach($field_types as $field_type=>$field_type_description)
                {
                ?>
                <option value="<?php echo $field_type ?>"><?php echo $lang[$field_type_description] ; ?></option>
                <?php
                }
            ?>
            </select>
            </div>

             <div class="Inline"><input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["create"] ?>&nbsp;&nbsp;" /></div>
            </div>
            <div class="clearerleft"> </div>
        </div>
    </form>


 
</div><!-- End of BasicsBox -->

  

<script>
  
function ReorderResourceTypeFields(idsInOrder)
    {
    //alert(idsInOrder);
    var newOrder = [];
    jQuery.each(idsInOrder, function() {
        newOrder.push(this.substring(11));
        }); 
    
    jQuery.ajax({
        type: 'POST',
        url: '<?php echo generateURL($baseurl_short . "pages/admin/ajax/update_resource_type_field_order.php",$url_params,array("reorder"=>"true")); ?>',
        data: {
        order:JSON.stringify(newOrder),
        <?php echo generateAjaxToken('reorder_resource_type_fields');?>
        },
        success: function() {
        
        //jQuery('.movelink').show();
        jQuery('.movedownlink:last').prop( "disabled", true );
        jQuery('.moveuplink:first').prop( "disabled", true );
        jQuery('.movedownlink:not(:last)').prop( "disabled",false )
        jQuery('.moveuplink:not(:first)').prop( "disabled", false)
        //$( "input:not(:checked) + span" )
        //alert("SUCCESS");
        //var results = new RegExp('[\\?&amp;]' + 'search' + '=([^&amp;#]*)').exec(window.location.href);
        //var ref = new RegExp('[\\?&amp;]' + 'ref' + '=([^&amp;#]*)').exec(window.location.href);
        //if ((ref==null)&&(results!== null)&&('<?php echo urlencode("!collection" . $usercollection); ?>' === results[1])) CentralSpaceLoad('<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode("!collection" . $usercollection); ?>',true);
         }
      });       
    }

function enableFieldsort(){
    var fixHelperModified = function(e, tr) {
          var $originals = tr.children();
          var $helper = tr.clone();
          $helper.children().each(function(index)
          {
            jQuery(this).width($originals.eq(index).width())
          });
          return $helper;
      };

      //jQuery('.resource_type_field_row').draggable({ axis: "y" });
      //jQuery('.resource_type_field_row').droppable();
      
      jQuery('#resource_type_field_table_body').sortable({
              items: "tr",
              axis: "y",
              cursor: 'move',
              opacity: 0.6, 
              distance: 5,
              stop: function(event, ui) {
                  <?php
                  if($allow_reorder)
                    {
                    ?>
                    var idsInOrder = jQuery('#resource_type_field_table_body').sortable("toArray");
                    //alert(idsInOrder);
                    ReorderResourceTypeFields(idsInOrder);
                    <?php
                    }
                else
                    {
                    if($use_order_by_tab_view && $restypefilter!="")
                        {
                        $errormessage=$lang["admin_resource_type_field_reorder_information_tab_order"];
                        }
                    else if (!$use_order_by_tab_view && $restypefilter=="" && $field_order_by=="order_by" )
                        {
                        $errormessage=$lang["admin_resource_type_field_reorder_select_restype"];
                        ?>
                        hideinfo=true;
                        <?php                       
                        }
                    else
                        {
                        $errormessage=$lang["admin_resource_type_field_reorder_information_normal_order"];
                        } 
                    ?>
                    
                    jQuery('#PageError').html("<?php echo $errormessage ?>");
                    jQuery('#PageError').show();
                    if (hideinfo!==undefined)
                        {
                        jQuery('#PageInfo').hide();                    
                        }

                    jQuery( "#resource_type_field_table_body" ).sortable( "cancel" );
                    <?php
                    }
                    ?>

                  
                  },
              helper: fixHelperModified
             
            }).disableSelection();
    }
    
enableFieldsort();

jQuery(".moveuplink").click(function() {
    if (jQuery(this).prop('disabled')) {
          event.preventDefault();
          event.stopImmediatePropagation();
      }
      curvalue=parseInt(jQuery(this).parents(".resource_type_field_row").children('.order_by_value').html());
      parentvalue=parseInt(jQuery(this).parents(".resource_type_field_row").prev().children('.order_by_value').html());
      jQuery(this).parents(".resource_type_field_row").children('.order_by_value').html(curvalue-10);
      jQuery(this).parents(".resource_type_field_row").prev().children('.order_by_value').html(parentvalue+10);
      jQuery(this).parents(".resource_type_field_row").insertBefore(jQuery(this).parents(".resource_type_field_row").prev());
      var idsInOrder = jQuery('#resource_type_field_table_body').sortable("toArray");
      ReorderResourceTypeFields(idsInOrder);
        
    });
   
jQuery(".movedownlink").click(function() {
   if (jQuery(this).prop('disabled')) {
          event.preventDefault();
          event.stopImmediatePropagation();
      }
      curvalue=parseInt(jQuery(this).parents(".resource_type_field_row").children('.order_by_value').html());
      childvalue=parseInt(jQuery(this).parents(".resource_type_field_row").next().children('.order_by_value').html());
      jQuery(this).parents(".resource_type_field_row").children('.order_by_value').html(curvalue+10);
      jQuery(this).parents(".resource_type_field_row").next().children('.order_by_value').html(childvalue-10);
      jQuery(this).parents(".resource_type_field_row").insertAfter(jQuery(this).parents(".resource_type_field_row").next());
      var idsInOrder = jQuery('#resource_type_field_table_body').sortable("toArray");
      ReorderResourceTypeFields(idsInOrder);
    });
    
</script>
    
<?php
include "../../include/footer.php";
?>
