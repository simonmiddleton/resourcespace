<?php
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";
$filterid = getval("filter",0,true);

if (!checkperm("a"))
	{
	exit ("Permission denied.");
	}

$backurl = getval("backurl","");
if($backurl == "")
    {
    $filterorder    = getval("filterorder","ref");
    $filtersort     = getval("filtersort", "ASC");
    $revsort        = ($filtersort == "ASC") ? "DESC" : "ASC";
    $filterfind     = getval("filterfind","");
    $filter_manage_url  = $baseurl . "/pages/admin/admin_filter_manage.php";

    $params = array(
        "filterfind"    => $filterfind,
        "filtersort"    => $filtersort, 
        "filterorder"   => $filterorder
        );

    $backurl = generateURL($filter_manage_url, $params);
    }
else
    {
    $backurl = urlencode($backurl);
    }

  
$delete_filter = getvalescaped('delete_filter', '');
$delete_filter_rule = getvalescaped('delete_filter_rule', '');
$filter_rule = getval("filter_rule","");
$filter_copy_from = getval("copy_from",0,true);

// Process ajax requests
if($delete_filter != "" && enforcePostRequest("admin_filter_edit"))
    {
    $result     = delete_filter($delete_filter);
    if($result === true)
        {
        if(getval("filter_manage_page","") != "")
            {
            $response   = array('deleted' => $result);
            exit(json_encode($response));
            }
        else
            {
            ?>
            <script>
            CentralSpaceLoad('<?php echo $backurl; ?>',true);
            </script>
            <?php
            exit();    
            }
        }
    else
        {
        $response   = array('deleted' => false);
        $response["errors"] = array();
        $response["errors"][] = $lang["filter_search_delete_error"] . ":- ";
        foreach($result["groups"] as $group)
            {
            $response["errors"][] = $lang["group"] . ": <a href='" . $baseurl_short . "/pages/admin/admin_group_management_edit.php?ref=" . $group . "' target='_blank' >" . $group . "</a>";
            }
           
        foreach($result["users"] as $user)
            {
            $response["errors"][] = $lang["user"] . ": <a href='" . $baseurl_short . "?u=" . $user . "' target='_blank' >" . $user . "</a>";
            }
        
        exit(json_encode($response));
        }
    }
if($delete_filter_rule != "" && enforcePostRequest("delete_filter_rule"))
    {
    $result     = delete_filter_rule($delete_filter_rule);
    $response   = array('success' => $result);
    exit(json_encode($response));
    }
elseif($filter_rule != "" && enforcePostRequest("filter_rule_edit"))
    {
    // Process saved rules
    $ruledata = getval("filter_rule_data","");
    save_filter_rule($filter_rule,$filterid,$ruledata);
    }
elseif($filterid != "" && getval("save","") != "" && enforcePostRequest("admin_filter_edit"))
    {
    // Save the filter
    $filter_name = getval("filter_name","");
    $filter_condition = getval("filter_condition",RS_FILTER_ALL, true);

    if ($filterid == 0 && $filter_copy_from != 0)
        {
        // Copy rules to new filter
        $newfilterid = copy_filter($filter_copy_from);
        $filterid = $newfilterid;
        }
    else
        {
        save_filter($filterid,$filter_name,$filter_condition);
        
        if(getval("filter_manage_page","") == "")
            {
            redirect($backurl);    
            } 
        }
    }

// Get all fields so we can resolve node field names
$allfields = get_resource_type_fields();
    
$filter = get_filter($filterid);
$filter_rules = get_filter_rules($filterid);

$filter_edit_url = generateURL($baseurl . "/pages/admin/admin_filter_edit.php",array("filter"=>$filterid));  
$rule_add_url = generateURL($baseurl . "/pages/admin/ajax/admin_filter_rule_edit.php",array("ref"=>"new","filter"=>$filterid));

// Convert filter so we can display it in a user friendly way
$rules=array();

foreach($filter_rules as $fr_id => $frule)
    {     
    foreach($frule["nodes_on"] as $rulenode)
        {  
        $nodeinfo = array();
        get_node($rulenode, $nodeinfo);
        if($nodeinfo == false)
            {
            debug("filter rule #" . $fr_id . " - node " . $rulenode . " not found ");
            // Node does not exist
            continue;
            }
            
        $field_index = array_search($nodeinfo["resource_type_field"], array_column($allfields, 'ref'));
        if($field_index !== false)
            {            
            if(!isset($rules[$fr_id]["fields"][$allfields[$field_index]["ref"]]))
                {
                $rules[$fr_id]["fields"][$allfields[$field_index]["ref"]]["fieldname"] = i18n_get_translated($allfields[$field_index]["name"]);
                $rules[$fr_id]["fields"][$allfields[$field_index]["ref"]]["values_on"] = array();
                }
            $rules[$fr_id]["fields"][$allfields[$field_index]["ref"]]["values_on"][] = i18n_get_translated($nodeinfo["name"]); 
            }
        else
            {
            echo "filter - node field " . $nodeinfo["resource_type_field"] . " for node:" . $rulenode . " not found ";
            }
        }
        
    foreach($frule["nodes_off"] as $rulenode)
        {  
        $nodeinfo = array();
        get_node($rulenode, $nodeinfo);
        if($nodeinfo == false)
            {
            debug("filter rule #" . $fr_id . " - node " . $rulenode . " not found ");
            // Node does not exist
            continue;
            }
            
        $field_index = array_search($nodeinfo["resource_type_field"], array_column($allfields, 'ref'));
        if($field_index !== false)
            {            
            if(!isset($rules[$fr_id]["fields"][$allfields[$field_index]["ref"]]))
                {
                $rules[$fr_id]["fields"][$allfields[$field_index]["ref"]]["fieldname"] = i18n_get_translated($allfields[$field_index]["name"]);
                $rules[$fr_id]["fields"][$allfields[$field_index]["ref"]]["values_off"] = array();
                }
            $rules[$fr_id]["fields"][$allfields[$field_index]["ref"]]["values_off"][] = i18n_get_translated($nodeinfo["name"]); 
            }
        else
            {
            echo "filter - node field " . $nodeinfo["resource_type_field"] . " for node:" . $rulenode . " not found ";
            }
        }
    }




include "../../include/header.php";
?>


<div id="CentralSpaceContainer">
    <div id="CentralSpace">
        <div class="BasicsBox">
        
            <p><a href="<?php echo $backurl; ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["filter_manage"]; ?></a></p>
             
            <h1><?php echo ($filterid == 0 ? $lang["filter_new"] : $lang["filter_edit"]) ?></h1>
            <h2><?php echo $lang["filter_edit_text"];render_help_link("systemadmin/search-filters");?></h2>
            <form id="filter_edit_form" name="filter_edit_form" method="post" class="FormWide" action="<?php echo $filter_edit_url; ?>">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filterid); ?>" />
                <input type="hidden" name="save" value="true" />
                <?php generateFormToken("admin_filter_edit"); ?>
                <div class="Question" id="filter_name_question">
                    <label for="filter_name"><?php echo $lang["filter_name"] ?></label>
                    <input class="stdwidth" type="text" name="filter_name" id="filter_name" value="<?php echo i18n_get_translated($filter["name"]) ?>" />
                    <div class="clearerleft"> </div>
                </div>
    
                <div class="Question" id="filter_condition_question" title="" style="height: 50px;">
                    <label><?php echo $lang["filter_condition_label"] ?></label>
                    <select class="stdwidth" name="filter_condition">
                        <?php
                        foreach(array(RS_FILTER_ALL => "filter_criteria_all",RS_FILTER_NONE => "filter_criteria_none",RS_FILTER_ANY => "filter_criteria_any") as $filter_condition => $description)
                            {
                            echo "<option value='" . $filter_condition . "' " . ($filter["filter_condition"] == $filter_condition ? "selected " : "")  . ">" . $lang[$description] . "</option>";
                            }?>
                    </select>  
                    <div class="clearerleft"> </div>
                </div>


                <div class="Question" id="fr_question">
                    <label for="fr_list"><?php echo $lang["filter_rules"]; ?></label>
                    <div id="fr_list" class="stdwidth">
                        <table class="OptionTable">
                        <?php
                        if(count($rules) == 0)
                            {
                            echo  $lang["filter_rules_none"];
                            }
                        else
                            {
                            foreach($rules as $ruleid => $ruleinfo)
                                {
                                $ruletext = array();
                                foreach($ruleinfo["fields"] as $rulefield)
                                    {
                                    //print_r($rulefield);
                                    if(isset($rulefield["values_on"]) && count($rulefield["values_on"]) > 0)
                                        {
                                        $ruletext[] = $rulefield["fieldname"] . " " . $lang["filter_is_in"] . " ('" . implode("'&nbsp;" . $lang["filter_or"] . "&nbsp;'", $rulefield["values_on"]) . "')";
                                        }
                                    if(isset($rulefield["values_off"]) && count($rulefield["values_off"]) > 0)
                                        {
                                        $ruletext[] = $rulefield["fieldname"] . " " . $lang["filter_is_not_in"] . " ('" . implode("'&nbsp;" . $lang["filter_or"] . "&nbsp;'", $rulefield["values_off"]) . "')";
                                        }
                                    }
                                    
                                //exit(print_r($ruleinfo));
                            
                                echo "<tr><td><div class='keywordselected tag_inline' id='filter_rule_" . $ruleid . "'>" . implode("&nbsp;" . $lang["filter_or"] . "&nbsp;",$ruletext) . "<a href='#' onclick ='deleteFilterRule(" . $ruleid . ");return false;'>[<i class='fa fa-remove'></i>]</a></div></td></tr>";
                                }
                            }
                            ?>
                        </table>
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
                    <input id="delete_filter" name="delete_filter" type="checkbox" value="<?php echo htmlspecialchars($filterid); ?>" >
                    <div class="clearerleft"></div>
                </div>
                        
                <div class="QuestionSubmit">
                    <label for="save"></label>
                    <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]; ?>&nbsp;&nbsp;" onClick="return CentralSpacePost(this.form,true);">
                </div>


            </form>
         </div> <!-- End of BasicsBox -->
    </div> <!-- End of CentralSpace -->
</div> <!-- End of CentralSpaceContainer -->


<script>
function addFilterRule()
    {
    ModalLoad('<?php echo $rule_add_url; ?>',true,true,'left');
    ModalCentre();
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
            else
                {
                styledalert('<?php echo $lang["error"]; ?>',response);
                }
        }, 'json');
        
        return false;
    }
</script>


<?php


include "../../include/footer.php";