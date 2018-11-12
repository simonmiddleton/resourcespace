<?php
include '../../../include/db.php';
include_once "../../../include/general.php";
include "../../../include/authenticate.php";
if(!checkperm("t")){exit ("Access denied"); }
include_once "../../../include/resource_functions.php";
include_once "../include/tms_link_functions.php";


$ref = getval("ref", 0, true);
$tmsid = getval("tmsid", 0, true);

if($ref == 0 && $tmsid == 0)
    {
    exit($lang["tms_link_no_resource"]);
    }

$tmsdata = tms_link_get_tms_data($ref, $tmsid);

include "../../../include/header.php";
?>
<h2><?php echo $lang["tms_link_tms_data"]; ?></h2>
<?php
if(!is_array($tmsdata))
    {
    echo $tmsdata;
    include "../../../include/footer.php";
    die();
    }
?>
<div class="Listview">
    <table style="border=1;">
<?php
foreach($tmsdata as $module_name => $module_tms_data)
	{
    ?>
    <tr colspan="2"><strong><?php echo htmlspecialchars($module_name); ?></strong></tr>
    <?php
    foreach($module_tms_data as $tms_column => $tms_value)
        {
        ?>
        <tr> 
           <td><strong><?php echo htmlspecialchars($tms_column); ?></strong></td>
           <td><?php echo htmlspecialchars($tms_value); ?></td>
        </tr>
        <?php
        }
	}
    ?>
    </table>
</div>
<?php
include "../../../include/footer.php";