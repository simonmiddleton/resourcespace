<?php
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";
$filterid = getval("filter",0,true);

if (!checkperm("a") || $filterid == 0)
	{
	exit ("Permission denied.");
	}
    

$delete_filter_rule = getvalescaped('delete_filter_rule', '');

// Process ajax requests
if($delete_filter_rule != "" && enforcePostRequest("delete_filter_rule"))
    {
    delete_filter_rule($delete_filter_rule);
    $response   = array('success' => true);
    exit(json_encode($response));
    }

// Process saved rules
$filter_rule = getval("filter_rule","");
if($filter_rule != "" && enforcePostRequest("filter_rule_edit"))
    {
    // Save the filter rule
    $condition = getval("filter_rule_condition",1,true);
    save_filter_rule($filter_rule,$filterid,getval("filter_rule_nodes",array()),$condition);

    }

// Get all fields so we can resolve node field names
$allfields = get_resource_type_fields();
    
$filter = get_filter($filterid);
$filter_rules = get_filter_rules($filterid);

$rule_add_url = generateURL($baseurl . "/pages/admin/ajax/admin_filter_rule_edit.php",array("ref"=>"new","filter"=>$filterid));

//exit(print_r($filter_rules));

// Convert filter so we can display it in a user friendly way
$rules = array();
$n=0;
foreach($filter_rules as $filter_rule)
    {
    $rules[$n] = array();
    $rules[$n]["ref"]= $filter_rule["ref"];
    $rules[$n]["rule_condition"]= $filter_rule["rule_condition"];
    $rules[$n]["fields"] = array();
    $nodes = explode(",",$filter_rule["nodes"]);
    foreach($nodes as $rulenode)
        {   
        $nodeinfo = array();
        get_node($rulenode, $nodeinfo);
        if($nodeinfo == false)
            {
            echo "filter rule #" . $filter_rule["ref"] . " - node " . $rulenode . " not found ";
            // Node does not exist
            continue;
            }
            
        $field_index = array_search($nodeinfo["resource_type_field"], array_column($allfields, 'ref'));
        if($field_index !== false)
            {            
            //echo "filter - node field found: " . $allfields[$field_index]["name"];
            if(!isset($rules[$n]["fields"][$allfields[$field_index]["ref"]]))
                {
                //echo "filter - adding field " . $allfields[$field_index]["ref"] . " to array";
                $rules[$n]["fields"][$allfields[$field_index]["ref"]]["fieldname"] = i18n_get_translated($allfields[$field_index]["name"]);
                $rules[$n]["fields"][$allfields[$field_index]["ref"]]["values"] = array();
                }
            $rules[$n]["fields"][$allfields[$field_index]["ref"]]["values"][] = i18n_get_translated($nodeinfo["name"]); 
            }
        else
            {
            echo "filter - node field " . $nodeinfo["resource_type_field"] . " for node:" . $rulenode . " not found ";
            }
        }
    $n++;
    }


//exit(print_r($rules));


include "../../include/header.php";
?>


<div id="CentralSpaceContainer">
    <div id="CentralSpace">
        <div class="BasicsBox">
            <h1><?php echo $lang["filter_edit"] ?></h1>
            <h2><?php echo $lang["filter_edit_text"] ?></h2>
            <form id="filter_edit_form" name="filter_edit_form" method="post" action="">
            
                <div class="Question" id="filter_question_name">
                    <label for="filter_name"><?php echo $lang["filter_name"] ?></label>
                    <input class="stdwidth" type="text" name="filter_name" id="filter_name" value="<?php echo i18n_get_translated($filter["name"]) ?>" />
                    <div class="clearerleft"> </div>
                </div>
    
                <div class="Question" id="filter_question_criteria" title="" style="height: 50px;">
                    <label><?php echo $lang["filter_criteria_label"] ?></label>
                    <select>
                        <?php
                        foreach(array(RS_FILTER_ALL => "filter_criteria_all",RS_FILTER_NONE=>"filter_criteria_none",RS_FILTER_ANY=>"filter_criteria_any") as $filter_condition => $description)
                            {
                            echo "<option value='" . $filter_condition . "' " . ($filter["filter_condition"] == $filter_condition ? "selected " : "")  . ">" . $lang[$description] . "</option>";
                            }?>
                    </select>  
                    <div class="clearerleft"> </div>
                </div>


                <div class="Question" id="fr_question">
                    <label for = "fr_list"><?php echo $lang["filter_rules"]; ?></label>
                    <div id="fr_list">
                        <?php
                        
                        // TODO - show message - $lang["filter_rules_none"] if no filter rules
                        foreach($rules as $ruleinfo)
                            {
                            if($ruleinfo["rule_condition"] == 1)
                                {
                                $condtext = $lang["filter_is_in"];
                                $btext    = $lang["filter_or"];
                                }
                            else
                                {
                                $condtext = $lang["filter_is_not_in"];
                                $btext    = $lang["filter_and"];
                                }
                            
                            //exit(print_r($ruleinfo));
                            $ruletext = array();
                            foreach($ruleinfo["fields"] as $rulefield)
                                {
                                $ruletext[] = $rulefield["fieldname"] . " " . $condtext . " ('" . implode("' " . $lang["filter_or"] . " '", $rulefield["values"]) . "')";
                                }
                            echo "<div class='keywordselected' id='filter_rule_" . $ruleinfo["ref"]. "'>" . implode(" " . $btext . " ",$ruletext) . "<a href='#' onclick ='deleteFilterRule(" . $ruleinfo["ref"] . ");return false;'>[<i class='fa fa-remove'></i>]</a></div>";
                            }
                            ?>
                        
                    </div> <!-- End of fr_list -->
                <div class="clearerleft"> </div>
                </div><!-- End of fr_question -->


                <div class="Question">
                    <label for="ruleadd"></label>
                    <input name="ruleadd" type="button" onclick="addFilterRule();"value="&nbsp;&nbsp;<?php echo $lang["filter_rule_add"]; ?>&nbsp;&nbsp;">
                <div class="clearerleft"></div>
                </div>

                <div class="Question">
                    <label><?php echo $lang["action-delete"]?></label>
                    <input id="delete_filter" name="delete_filter" type="checkbox" value="yes" >
                    <div class="clearerleft"></div>
                </div>
                        
                <div class="QuestionSubmit">
                    <label for="buttonsave"></label>
                    <input name="buttonsave" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]; ?>&nbsp;&nbsp;">
                </div>


            </form>
         </div> <!-- End of BasicsBox -->
    </div> <!-- End of CentralSpace -->
</div> <!-- End of CentralSpaceContainer -->


<div class="BasicsBox" id="filter_rule_edit" style="display:none;">
    <h1><?php echo $lang["filter_rule_edit"] ?></h1>
    <h2><?php echo $lang["filter_rule_edit_text"] ?></h2>
    <form id="filter_rule_edit_form" name="filter_rule_edit_form" method="post" action="">
    
        <div class="Question" id="filter_question_name">
            <label for="field_96"><?php echo $lang["filter_rule_name"] ?></label>
            <input class="stdwidth" type="text" name="field_96" id="filter_rule" value="<?php echo $filter_rule["ref"] ?>" />
            <div class="clearerleft"> </div>
        </div>
    

    <div class="Question">
        <label>Field:</label>
        <select name="action_dates_deletefield" id="action_dates_deletefield" style="width:300px">
            <option value="" selected=""></option>
            <option value="78">Aspect ratio</option>    <option value="83">Audio bitrate</option>
            
        </select>

        <select name="conditionlogic" id="conditionlogic" style="width:150px">
            <option value="0" selected ><?php echo $lang["filter_is_not_in"]; ?></option>
            <option value="1" ><?php echo $lang["filter_is_in"]; ?></option>
        </select>


        <select name="condition2" id="condition2" style="width:300px">
            <option value="0" selected >Marketing</option>
            <option value="1" >Sales</option>
            <option value="1" >Support</option>
            <option value="1" >HR</option>
        </select>
        <a><i aria-hidden="true" class="fa fa-plus-circle"></i>&nbsp;Add condition</input></a>
    </div>


    <div class="Question">
        <label for="ruleadd"></label>
        <input name="ruleadd" type="submit" onclick="return addFilterRule();return false;"value="&nbsp;&nbsp;<?php echo $lang["filter_rule_add"]; ?>&nbsp;&nbsp;">
    <div class="clearerleft"></div>
    </div>


    </form>
</div> <!-- End of filter_rule_edit Basicsbox -->

<script>
function addFilterRule()
    {
    ModalLoad('<?php echo $rule_add_url; ?>',true,true,'left');
    ModalCentre();
    /*
    rule_edit_html = jQuery('#filter_rule_edit').html();
    jQuery('#modal').html(rule_edit_html);
    */
    return true;
    }
    
function deleteFilterRule(rule)
    {
    var post_data = {
        ajax: true,
        delete_filter_rule: rule,
        <?php echo generateAjaxToken("delete_filter_rule"); ?>
    };
    
    jQuery.post(window.location.href, post_data, function(response) {
            if(response.success === true)
                {
                jQuery('#filter_rule_' + rule).remove();
                }
        }, 'json');
        
        return false;
    }
</script>


<?php


include "../../include/footer.php";