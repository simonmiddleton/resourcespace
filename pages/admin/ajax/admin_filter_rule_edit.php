<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";

$ruleid = getval("ref","");

if (!checkperm("a") || !(((string)(int)$ruleid == (string)$ruleid) || $ruleid == "new"))
	{
	exit($lang["error-permissiondenied"]);
	}

if($ruleid != "new")
    {
    $filter_rule = get_filter_rule($ruleid);
    if($filter_rule == false)
        {
        exit ($lang["error"]);
        }
    }
else
    {
    $filter_rule = array();
    $filterid = getval("filter",0,true);
    }

    
$allfields = get_resource_type_fields();
$saveparams = array();
$saveparams["ref"] = $filterid;
$saveurl = generateURL($baseurl . "pages/admin/admin_filter_rule.php",$saveparams);
    
?>

<script src="<?php echo $baseurl_short ?>lib/chosen/chosen.jquery.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo $baseurl_short ?>lib/chosen/chosen.min.css">

<div class="BasicsBox">
    <h1><?php echo $lang["filter_rule_edit"] ?></h1>
    <h2><?php echo $lang["filter_rule_edit_text"] ?></h2>
    <form id="filter_edit_form" name="filter_edit_form" method="post" action="">
    <input type="hidden" name="filter_rule" value="<?php echo $ruleid; ?>" />
    <input type="hidden" name="filter" value="<?php echo $filterid; ?>" />
    <?php generateFormToken("filter_rule_edit"); ?>

    <div class="Question">
        <select name="filter_rule_field" id="filter_rule_field" style="width:300px" onChange="updateFieldOptions();">
            <?php
            foreach($allfields as $field)
                {
                if(in_array($field["type"],$FIXED_LIST_FIELD_TYPES))
                    {
                    echo "<option value='" . $field["ref"] . "' >" . $field["title"] . "</option>\n";
                    }
                }
            ?>
        </select>

        <select name="filter_rule_condition" id="filter_rule_condition" style="width:150px">
            <option value="0" selected ><?php echo $lang["filter_is_not_in"]; ?></option>
            <option value="1" ><?php echo $lang["filter_is_in"]; ?></option>
        </select>
        
        <select name='filter_rule_nodes[]' id='filter_rule_nodes' class='filter_rule_select' multiple='multiple' size='7' style='width:420px'>
        </select>
        
    </div>

    <div class="Question">
        <label for="conditionadd"></label>
        <a onclick="return addFilterRuleItem();return false;" ><i aria-hidden="true" class="fa fa-plus-circle"></i>&nbsp;Add condition</input></a>
    <div class="clearerleft"></div>
    </div>


    <div class="Question">
        <label for="ruleadd"></label>
        <input name="ruleadd" type="submit" onclick="return CentralSpacePost('<?php echo $saveurl; ?>');" value="&nbsp;&nbsp;<?php echo $lang["filter_rule_add"]; ?>&nbsp;&nbsp;">
    <div class="clearerleft"></div>
    </div>


    </form>
</div> <!-- End of Basicsbox -->


<script>

function addFilterRuleItem()
    {
    jQuery()
    }

function updateFieldOptions()
    {
    jQuery('#filter_rule_nodes').html("");
    jQuery('#filter_rule_nodes').chosen('destroy')

    selectedField = jQuery('#filter_rule_field').val();
    console.log('field' + selectedField + ' selected');

     var post_data = {
        ajax: true,
        field: selectedField,
        <?php echo generateAjaxToken("filter_rule_field"); ?>
    };
    options_url = baseurl_short + 'pages/ajax/field_options.php';

    jQuery.post(options_url, post_data, function(response) {
            if(response.success === true)
                {
                nodeoptions = response.options;
                var nodeselect = document.getElementById("filter_rule_nodes");
                // jQuery('#filter_rule_nodes');
                for(var i in nodeoptions) {
                    console.log('Adding option ' + nodeoptions[i]);
                    nodeselect.add(new Option(nodeoptions[i],i));
                    }
                
                jQuery("#filter_rule_nodes").chosen();

                //console.log(nodeoptions);
                }
        }, 'json');


    //jQuery('#filter_rule_nodes').


    }

//jQuery("#field_options_").chosen();

</script>