<?php
include "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php";
include_once "../include/resource_functions.php";
include_once "../include/search_functions.php";
include_once "../include/collections_functions.php";

$all_fields=get_resource_type_fields();

// TODO don't migrate if already migrated
$groups = sql_query("SELECT ref, name,search_filter FROM usergroup WHERE search_filter_id IS NULL OR search_filter_id=0");
$existingrules = sql_query("SELECT ref, name FROM filter");

foreach($groups as $group)
    {
    $filter = trim($group["search_filter"]);
    if($filter == "")
        {
        continue;
        }
    
    echo "Migrating filter rule for usergroup '" . $group["name"] . "'...<br />\r\n";
    echo " - Filter text: '" . $filter . "'<br />\r\n";
    
    // Check for existing rule
    $filterid = array_search($filter, array_column($existingrules, 'name'));
    if($filterid !== false)
        {
        echo " - Filter already migrated, setting ID to " . $existingrules[$filterid]["ref"] . "<br />\r\n";
        sql_query("UPDATE usergroup SET search_filter_id='" . $filterid . "' WHERE ref='" . $group["ref"] . "'");
        continue;
        }
    else
        {
        // Create filter. All migrated filters have AND rules
        sql_query("INSERT INTO filter (name, filter_condition) VALUES ('" . escape_check($filter) . "','" . RS_FILTER_ALL  . "')");
        $filterid = sql_insert_id();
        }
    
    // Add to array of existing rules
    $existingrules[] = array("ref" => $filterid,"name" => $filter);
        
    $filter_rules = explode(";",$filter);
    
    $n = 1;
    foreach($filter_rules as $filter_rule)
        {
        echo " -- Parsing filter rule #" . $n . " : '" . $filter_rule . "'<br />\r\n";
        $rule_parts = explode("=",$filter_rule);
        $rulefields = $rule_parts[0];
        $rulevalues = explode("|",trim($rule_parts[1]));
        
        // Create filter_rule
        sql_query("INSERT INTO filter_rule (filter) VALUES ('{$filterid}')");
        $new_filter_rule = sql_insert_id();
        
        $nodeinsert = array(); // This will contain the SQL value sets to be inserted for this rule
        
        $rulenot = substr($rulefields,-1) == "!";
        $node_condition = RS_FILTER_NODE_IN;
        if($rulenot)
            {
            $rulefields = substr($rulefields,0,-1);
            $node_condition = RS_FILTER_NODE_NOT_IN;
            }
                 
        // Find the fields the rule relates to        
        $rulefieldarr = explode("|",$rulefields);        
        foreach($rulefieldarr as $rulefield)
            {
            $all_fields_index = array_search($rulefield, array_column($all_fields, 'name'));
            $field_ref = $all_fields[$all_fields_index]["ref"];

            echo " --- filter field name: '" . $rulefield. "' , field id #" . $field_ref . "<br />\r\n";
                
            $field_nodes = get_nodes($field_ref,null,true);
            foreach($rulevalues as $rulevalue)
                {
                echo " --- Checking for filter rule value : '" . $rulevalue . "'<br />\r\n";
                $nodeidx = array_search($rulevalue, array_column($field_nodes, 'name'));
                
                // Check for translated option!                
                if($nodeidx !== false)
                // Check for node in field options
                    {
                    $nodeid = $field_nodes[$nodeidx]["ref"];
                    echo " --- field option (node) exists, node id #: " . $field_nodes[$nodeidx]["ref"] . "<br />\r\n";
                    
                    $nodeinsert[] = "('" . $new_filter_rule . "','" . $nodeid . "','" . $node_condition . "')";
                    }
                else
                    {
                    echo " --- Invalid field option, skipping<br />\r\n";
                    }
                }
            } // End of foreach($rulefieldarr as $rulefield)
            
        // Insert associated filter_rules
        $sql = "INSERT INTO filter_rule_node (filter_rule,node,node_condition) VALUES " . implode(',',$nodeinsert);
        sql_query($sql);
        } // End of foreach($filter_rules as $filter_rule)
    
    // Set filter for group
    sql_query("UPDATE usergroup SET search_filter_id='" . $filterid . "' WHERE ref='" . $group["ref"] . "'");
        
    } // End of foreach($groups as $group)
echo "COMPLETE<br />";
    
    