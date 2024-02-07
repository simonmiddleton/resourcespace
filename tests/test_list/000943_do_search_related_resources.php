<?php
command_line_only();

// Save state
$related_pushed_order_by_saved = $related_pushed_order_by ?? null;

// Set up related resources

$col_data_type = create_resource_type("Collection data");
$col_image_type = create_resource_type("Collection images");

$test943_resources = [];
$test943_resources[0] = create_resource($col_image_type,0);
$test943_resources[1] = create_resource($col_data_type,0);
$test943_resources[2] = create_resource($col_data_type,0);
$test943_resources[3] = create_resource($col_data_type,0);
relate_all_resources($test943_resources);

// SUBTEST A
$results = do_search('!related' . $test943_resources[0]);
$arr_expected = [$test943_resources[1], $test943_resources[2],$test943_resources[3]];
$arr_result = array_column($results,'ref');
if(count($results) !== 3 || !match_values($arr_result,$arr_expected))
	{
    echo "ERROR - SUBTEST A, expected [" . implode(",",$arr_expected) . "], got [" . implode(",",$arr_result). "]\n";
    return false;
    }

// SUBTEST B - Check relatedpushed and $related_pushed_order_by

// Create new field to order by and add data to the resources
$relatedorderfield = create_resource_type_field("Page943",0,FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,"page943");
$GLOBALS["related_pushed_order_by"] = $relatedorderfield;
$page1node  = set_node(NULL, $relatedorderfield, "1",'',1000);
$page2node  = set_node(NULL, $relatedorderfield, "2",'',1000);
$page3node  = set_node(NULL, $relatedorderfield, "3",'',1000);
add_resource_nodes($test943_resources[2],[$page1node]);
add_resource_nodes($test943_resources[3],[$page2node]);
add_resource_nodes($test943_resources[1],[$page3node]);

$debug_log=true;
$debug_log_location = "/var/log/resourcespace/debug_dev.log";


save_resource_type($col_data_type,["push_metadata"=>true]);
$results=do_search('!relatedpushed' . $test943_resources[0]);
$arr_expected = [$test943_resources[2], $test943_resources[3],$test943_resources[1]];
$arr_result = array_column($results,'ref');

if(count($results) !== 3 || $arr_result !== $arr_expected)
	{
    echo "ERROR - SUBTEST B (\$related_pushed_order_by), expected [" . implode(",",$arr_expected) . "], got [" . implode(",",$arr_result). "]\n";
    return false;
    }

// Restore state
if (!is_null($related_pushed_order_by_saved))
    {
    $GLOBALS["related_pushed_order_by_saved"] = $related_pushed_order_by_saved;
    }

return true;