<?php

function meta_get_map()		// returns array of [resource_type][table][attributes], where attributes are (remote_table, remote_ref, required, missing, options[])
{		
	global $mysql_db;
	
	$meta=array();
	
	foreach (sql_query("SELECT ref, upper(title) AS `name`, `type`, title as `nicename`, resource_type, required FROM resource_type_field WHERE name IS NOT NULL AND `name` <> '' AND (resource_type IN (SELECT ref FROM resource_type) OR resource_type = 0)", "schema") as $field)
	{
		# Get translated - support i18n - upload columns must be in user's local language.
		$field['name']=trim(i18n_get_translated($field['name']));
		
		if (!isset($meta[$field['resource_type']])) $meta[$field['resource_type']]=array();		// make meta[<resource_type>] if does not exist			
		$meta[$field['resource_type']][$field['name']]['remote_table']="resource_data";			// add meta[<resource_type>][<field>]=>attributes (remote_table,remote_ref,required,options):
		$meta[$field['resource_type']][$field['name']]['remote_ref']=$field['ref'];
		$meta[$field['resource_type']][$field['name']]['nicename']=$field['nicename'];	
		$meta[$field['resource_type']][$field['name']]['required']=$field['required'];		
		$meta[$field['resource_type']][$field['name']]['type']=$field['type'];
		$meta[$field['resource_type']][$field['name']]['missing']=false;
        $meta[$field['resource_type']][$field['name']]['options'] = extract_node_options(get_nodes($field['ref'], NULL, $field['type'] == FIELD_TYPE_CATEGORY_TREE));

		if($meta[$field['resource_type']][$field['name']]['type']!=7) // Don't do this for category trees, not supported yet
			{
			for ($i=0; $i<count($meta[$field['resource_type']][$field['name']]['options']); $i++) 
				{
				//echo "ref: " . $meta[$field['resource_type']][$field['name']]['remote_ref'] . "<br>";
				//echo "name: " . $meta[$field['resource_type']][$field['name']]['nicename'] . "<br>";
				//echo "type " . $meta[$field['resource_type']][$field['name']]['type'] . "<br>";
				if(isset($meta[$field['resource_type']][$field['name']]['options'][$i]))
					{
					//echo "options: " . $meta[$field['resource_type']][$field['name']]['options'][$i] . "<br>";
					$meta[$field['resource_type']][$field['name']]['options'][$i]=trim ($meta[$field['resource_type']][$field['name']]['options'][$i]);
					}
				}
			}
	}
	$columns=sql_query("select upper(column_name) as name, column_name as nicename from information_schema.columns where table_name='resource' and table_schema='{$mysql_db}'");

	foreach (array_keys($meta) as $resource_type)
	{
		foreach ($columns as $column)	
		{
			if (!isset($meta[$resource_type])) $meta[$resource_type]=array();
			if (isset($meta[$resource_type][$column['name']]) || isset($meta[0][$column['name']])) continue;		// important, we do not want to override an existing meta field defined in resource_field_type
			$meta[$resource_type][$column['name']]=array();
			$meta[$resource_type][$column['name']]['remote_table']="resource";			
			$meta[$resource_type][$column['name']]['remote_ref']=null;		// not required as mapping to resource table
			$meta[$resource_type][$column['name']]['required']=($column=="resource_type");
			$meta[$resource_type][$column['name']]['missing']=false;
			$meta[$resource_type][$column['name']]['options']=array();		// for now leave this empty	
		}
	}
	
	return $meta;	
}
