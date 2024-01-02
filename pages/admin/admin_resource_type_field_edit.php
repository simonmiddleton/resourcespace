<?php
/**
 * User edit form display page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */

include "../../include/db.php";
include "../../include/authenticate.php"; 
if (!checkperm("a"))
    {
    exit ("Permission denied.");
    }

$ref=getval("ref","",true);

$find=getval("find","");
$restypefilter=getval("restypefilter","",true);
$field_order_by=getval("field_order_by","ref");
$field_sort=getval("field_sort","asc");
$newfield = getval("newfield","") != "";
$ajax = getval('ajax', '');
$url_params = array("ref"=>$ref,
        "restypefilter"=>$restypefilter,
        "$field_order_by"=>$field_order_by,
        "field_sort"=>$field_sort,
        "find" =>$find);
    
$backurl=getval("backurl","");
if($backurl=="")
    {
    $backurl = generateURL($baseurl . "/pages/admin/admin_resource_type_fields.php",$url_params);
    }
else
	{
	$back_url_params=parse_url($backurl, PHP_URL_QUERY);
	# the first parameter of the back url is needed here but isn't captured
	$back_url_params=explode('&', $back_url_params);
	
	foreach($back_url_params as $param)
		{
		$param_parts=explode('=', $param);
		switch($param_parts[0])
			{
			case 'restypefilter':
				$restypefilter=$param_parts[1];
				break;
			case 'field_order_by':
				$field_order_by=$param_parts[1];
				break;
			case 'field_sort':
				$field_sort=$param_parts[1];
				break;
			case 'find':
				$find=$param_parts[1];
				break;
			}
		}
	
	}
	

$url=generateURL($baseurl . "/pages/admin/admin_resource_type_field_edit.php",$url_params);

// Define array of field properties containing title and associated lang help text, with a flag to indicate if it is a boolean value that we will save from POST data and boolean to indicate will be set with any 'synced' fields

// example field :-
// "name of table column"=>array(
// 0: <language string for the friendly name of this property>,
// 1: <lang string for the help text explaining what this property means>,
// 2: <value to denote the field type(0=text,1=boolean,2=text area),
// 3: < boolean value to indicate whether this is a field that is synchronised? 0=No 1=Yes > 
// )
// IMPORTANT - Make sure advanced field properties are listed after the 'partial_index' so that these will be hidden from users by default

$fieldcolumns = get_resource_type_field_columns();

$type_change = false;

if(getval("save","")!="" && getval("delete","")=="" && enforcePostRequest(false))
	{
    $return = save_resource_type_field($ref,$fieldcolumns,$_POST);
	}

$confirm_delete=false;	
if (getval("delete","")!="" && enforcePostRequest($ajax))
	{
    $confirmdelete=getval("confirmdelete","");
    # Check for resources of this  type
    $affected_resources=ps_array("SELECT resource value FROM resource_node rn LEFT JOIN node n ON n.ref = rn.node WHERE n.resource_type_field = ?",["i",$ref]);
        
    $affected_resources_count=count($affected_resources);
    if($affected_resources_count==0 || $confirmdelete!="")
        {    
        $result = delete_resource_type_field($ref);
        if($result === true)
            {
            if($ajax)
                {
                echo json_encode(
                    array(
                        'deleted' => $ref
                    )
                );
                exit();
                }
            else
                {
                redirect(generateURL($baseurl . "/pages/admin/admin_resource_type_fields.php",$url_params,array("ref"=>"","deleted"=>urlencode($ref))));
                }
            }
        elseif(is_string($result))
            {
            if($ajax)
                {
                echo json_encode(
                    array(
                        'message' => $result
                    )
                );
                exit();
                }
            else
                {
                $error_text = $result;
                }            
            }        
        }
    else
        {	    
        // User needs to confirm deletion as data will be lost
        $error_text=str_replace("%%AFFECTEDRESOURCES%%",$affected_resources_count,$lang["admin_delete_field_confirm"]);
        $error_text.="<br /><a target=\"_blank\" href=\"" . $baseurl  . "/pages/search.php?search=!hasdata" . $ref . "\">" . $lang["show_resources"] . "</a>";
        
        $confirm_delete=true;
        }
	}
	
# Fetch  data
$allfields=get_resource_type_fields();
$fielddata=get_resource_type_field($ref);
$existingrestypes = $fielddata["resource_types"] ? explode(",",(string)$fielddata["resource_types"]) : [];

include "../../include/header.php";
?>
<script>
var fixed_list_fields = [<?php echo implode(",",$FIXED_LIST_FIELD_TYPES) ?>];
var text_fields       = [<?php echo implode(",",$TEXT_FIELD_TYPES) ?>];
var current_type      = <?php echo ('' != $fielddata['type'] ? $fielddata['type'] : 0); ?>;

<?php if (isset($migrate_data))
	{
	?>
	jQuery(document).ready(function()
		{
		window.location.href = '<?php echo $baseurl ?>/pages/tools/migrate_data_to_fixed.php?field=<?php echo $ref ?>';
		});
	<?php
	}
?>
</script>
<div class="BasicsBox">
<h1><?php echo $lang["admin_resource_type_field"] . ": " . htmlspecialchars(i18n_get_translated($fielddata["title"])); ?></h1>
<?php
    $links_trail = array(
        array(
            'title' => $lang["systemsetup"],
            'href'  => $baseurl_short . "pages/admin/admin_home.php",
            'menu' =>  true
        ),
        array(
            'title' => $lang["admin_resource_type_fields"],
            'href'  => $backurl
        ),
        array(
            'title' => $lang["admin_resource_type_field"] . ": " . i18n_get_translated($fielddata["title"]),
            'help'  => "resourceadmin/configure-metadata-field"
        )
    );

    renderBreadcrumbs($links_trail);
?>

<form method="post" class="FormWide" action="<?php echo $baseurl_short?>pages/admin/admin_resource_type_field_edit.php?ref=<?php echo (int)$fielddata["ref"] . "&restypefilter=" . (int)$restypefilter . "&field_order_by=" . urlencode($field_order_by) . "&field_sort=" . $field_sort ."&find=" . urlencode($find); ?>" onSubmit="return CentralSpacePost(this,true);">
    <?php generateFormToken("admin_resource_type_field_edit"); ?>
<input type="hidden" name="ref" value="<?php echo urlencode($ref) ?>">

<input type="hidden" name="newfield" value="<?php echo ($newfield)?"TRUE":""; ?>">


<?php
if (isset($error_text)) { ?><div class="PageInformal"><?php echo $error_text?></div><?php }
if (isset($saved_text)) { ?><div class="PageInformal"><span class="fa fa-fw fa-check"></span>&nbsp;<?php echo $saved_text?></div> <?php }


if($confirm_delete)
    {
    ?>
    <input name="confirmdelete" id="confirmdelete" type="hidden" value="">
    <div class="textcenter">
	<input name="delete" type="button" value="<?php echo escape($lang["action-delete"])?>" onClick="jQuery('#field_edit_delete').val('yes');jQuery('#confirmdelete').val('yes');this.form.submit();" />
	<input type="button" class="button" onClick="CentralSpaceLoad('<?php generateURL($baseurl_short . "/pages/admin/admin_resource_type_field_edit.php",$url_params,array("ref"=>"")); ?>',true);return false;" value="&nbsp;&nbsp;<?php echo $lang["cancel"] ?>&nbsp;&nbsp;" >
    </div>
     <?php	
    }
else
    {
    ?>
    <div class="Question"><label><?php echo $lang["property-field_id"] ?></label>
    <div class="Fixed"><?php echo  $fielddata["ref"] ?></div>
    <div class="clearerleft"> </div>
    </div>
    
    <?php
    $system_date_field = $ref==$date_field?true:false;
    foreach ($fieldcolumns as $column=>$column_detail)		
        {
        if(!hook("admin_field_replace_question","admin_resource_type_field_edit",[$ref,$column,$column_detail, $fielddata]))
            {
            if ($column=="partial_index") // Start the hidden advanced section here
                {?>
                <h2 id="showhiddenfields" class="CollapsibleSectionHead collapsed" ><?php echo $lang["admin_advanced_field_properties"] ?></h2>
                <div class="CollapsibleSection" id="admin_hidden_field_properties" >	 
                <?php
                }
            admin_resource_type_field_option($column,$column_detail[0],$column_detail[1],$column_detail[2],$fielddata[$column],$fielddata["type"],$system_date_field);
            }
        }
    ?>
    </div><!-- End of hidden advanced section -->    
    
    <div class="QuestionSubmit">	
    <input name="save" type="submit" value="&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;&nbsp;&nbsp;" />&nbsp;&nbsp;
    <input type="button" class="button" onClick="CentralSpaceLoad('<?php echo $baseurl . "/pages/admin/admin_copy_field.php?ref=" . $ref . "&backurl=" . $url ?>',true);return false;" value="&nbsp;&nbsp;<?php echo $lang["copy-field"] ?>&nbsp;&nbsp;" >
    <input name="migrate_data" id="migrate_data" type="hidden" value="">

	<?php if ($fielddata["active"]==0) { ?>
    <input name="delete" type="button" value="<?php echo escape($lang["action-delete"])?>" onClick="if(confirm('<?php echo escape($lang["confirm-deletion"]) ?>')){jQuery('#field_edit_delete').val('yes');this.form.submit();}else{jQuery('#delete').val('');}" />
	<?php } ?>
	
    </div>
    <?php
    }?>

<input type="hidden" name="save" id="field_edit_save" value="yes"/>
<input type="hidden" name="delete" id="field_edit_delete" value=""/>
</form>


</div><!-- End of Basics Box -->

<script>
   registerCollapsibleSections();
</script>



<?php


include "../../include/footer.php";
