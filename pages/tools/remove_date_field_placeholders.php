<?php
include "../../include/db.php";
include_once "../../include/authenticate.php";
if(!checkperm("a")){exit("Access denied");}

$datedata = sql_query("SELECT rd.resource, rd.resource_type_field, rd.value FROM resource_data rd LEFT JOIN resource_type_field rtf ON rd.resource_type_field=rtf.ref WHERE rtf.type IN (" . implode(",",$DATE_FIELD_TYPES) . ")");
$datefields = get_resource_type_fields("","ref","asc","",$DATE_FIELD_TYPES);
$datefieldarr = array();
foreach($datefields as $datefield)
    {
    if(!isset($datefieldarr[$datefield["ref"]]))
        {
        $datefieldarr[$datefield["ref"]] = $datefield["name"];
        }
    }

$update = getval("update","") == "true";


// Process each data row
$toupdate   = 0;
$count      = 0;
$log        = "";
foreach($datedata as $date_row)
    {
    $removedates = array("year","month","day"," hh:mm","hh","mm");
    $subdates = array("0000","00","00","","00","00");
    $newval = str_replace($removedates,$subdates,$date_row["value"]);
    $log .= "Resource : " . $date_row["resource"] . ", field '" . (isset($datefieldarr[$date_row["resource_type_field"]]) ?  $datefieldarr[$date_row["resource_type_field"]] : "") . "' (" . $date_row["resource_type_field"] . "). Convert from '" . $date_row["value"] . "' to '" . $newval . "'";
    if($newval != $date_row["value"])
        {
        $toupdate++;
        if($update)
            {
            sql_query("UPDATE resource_data SET value='" . escape_check($newval) . "' WHERE resource='" . $date_row["resource"] . "' AND resource_type_field='" . $date_row["resource_type_field"] . "'");
            $log .= " - UPDATED";
            $count++;
            }
        }
    $log .= "<br/>";
    }


include_once "../../include/header.php";

?>
<h2>Check the output below and if updates are required click on 'UPDATE' to make the changes</h2>
<?php
if($toupdate > 0)
    {?>
    <div class="BasicsBox"><input type="submit" name="Update" onclick="return CentralSpaceLoad('remove_date_field_placeholders.php?update=true',true);" /></div>
    <?php
    }?>
<div class="BasicsBox">
<?php

if($toupdate > 0)
    {
    echo $log;
    }
else
    {
    echo "No resources to update<br/>";
    }

if($update)
    {
    echo $count . " resources updated<br/>";
    }

echo "</div>";

include_once "../../include/footer.php";


