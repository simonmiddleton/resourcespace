<?php
#
# Script to update the resource table with values from resource_data when data joins are added or removed.
# Use "add=" to update a specific field or list of fields
# Use "remove=" to drop the column of a specific field or list of fields
# If neither are set the script will use $data_joins to add AND remove fields
#
include "../../include/db.php";
include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}

$add=getval("add","");
$remove=getval("remove","");

$all=(($add=='' && $remove=='')?true:false);

$fields=ps_array("SELECT `COLUMN_NAME` value FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='{$mysql_db}' AND `TABLE_NAME`='resource' AND `COLUMN_NAME` LIKE 'field%'",[]);
echo "fields:";print_r($fields);echo"<br/><br/>";

echo "data_joins:";print_r($data_joins);echo"<br/><br/>";
if($remove!=='' || $all)
    {
    // drop tables first
    if($all)
        {
        // we need to create a list of fields that aren't in $data_joins
        $remove=array();        
        foreach($fields as $field)
            {
            $field_ref=substr($field,(strlen("field")));
            if(!in_array($field_ref,$data_joins))
                {
                $remove[]=(int)$field_ref;
                }
            }
        }
    else
        {
        $remove=explode(",",$remove);
        $r_count=count($remove);
        for($r=0 && $r_count>0;$r<$r_count;$r++)
            {
            if(!in_array("field".$remove[$r],$fields))
                {
                unset($remove[$r]);
                }
            }
        $remove=array_filter(array_values($remove),"is_int_loose");
        }    
    if(count($remove)>0)
        {
        foreach($remove as $column)
            {
            echo "Dropping column $column...";
            $wait=ps_query("ALTER TABLE resource DROP COLUMN field" . $column);
            echo "done!<br/>";
            flush();
            }
        echo "Done dropping columns<br/><br/>";
        }
    else
        {
        echo "No columns to drop.<br/><br/>";
        }
    }
else
    {
    echo "No columns to drop.<br/><br/>";
    }
flush();
if($add!=='' || $all)
    {
    if($all)
        {
        $add=$data_joins;
        }
    else{
        $add=explode(",",$add);
        $a_count=count($add);
        for($a=0;$a<$a_count;$a++)
            {
            if(!in_array($add[$a],$data_joins))
                {
                echo "Field $option is not part of $data_joins...removing from list<br/>";
                unset($add[$a]);
                }
            }
        $add=array_values($add);
        }
    if(count($add)>0)
        {
        foreach($add as $column)
            {
            echo "Updating column field$column...";
            $wait = ps_query("UPDATE resource r LEFT JOIN resource_node rn ON r.ref = rn.resource LEFT JOIN node n ON n.ref=rn.node  SET r.field{$column} = SUBSTR(n.name, 1, ?) WHERE n.resource_type_field = ?",["i",$resource_field_column_limit,"i",$column]);
            echo "done!<br/>";
            flush();
            }
        echo "Done updating columns<br/><br/>";
        }
    else
        {
        echo "No columns to update.<br/><br/>";
        }
    }
else
    {
    echo "No columns to update.<br/><br/>";
    }

echo "Done with updating and removing data_join_columns from the resource table.<br/>";
