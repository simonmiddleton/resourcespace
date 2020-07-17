<?php
/**
 * Apply new order to metadata fields
 *
 * @param  array $neworder  Field IDs in new order
 *
 * @return void
 */
function update_resource_type_field_order($neworder)
	{
	global $lang;
	if (!is_array($neworder)) {
		exit ("Error: invalid input to update_resource_type_field_order function.");
	}

	$updatesql= "update resource_type_field set order_by=(case ref ";
	$counter = 10;
	foreach ($neworder as $restype){
		$updatesql.= "when '$restype' then '$counter' ";
		$counter = $counter + 10;
	}
	$updatesql.= "else order_by END)";
	sql_query($updatesql);
	clear_query_cache("schema");
	log_activity($lang['resourcetypefieldreordered'],LOG_CODE_REORDERED,implode(', ',$neworder),'resource_type_field','order_by');
	}
	
/**
 * Apply a new order to resource types
 *
 * @param  array $neworder  Resource type IDs in new order
 *
 * @return void
 */
function update_resource_type_order($neworder)
	{
	global $lang;
	if (!is_array($neworder)) {
		exit ("Error: invalid input to update_resource_type_field_order function.");
	}

	$updatesql= "update resource_type set order_by=(case ref ";
	$counter = 10;
	foreach ($neworder as $restype){
		$updatesql.= "when '$restype' then '$counter' ";
		$counter = $counter + 10;
	}
	$updatesql.= "else order_by END)";
	sql_query($updatesql);
	clear_query_cache("schema");
	log_activity($lang['resourcetypereordered'],LOG_CODE_REORDERED,implode(', ',$neworder),'resource_type','order_by');
	}