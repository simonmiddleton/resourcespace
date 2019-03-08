<?php
include '../../../include/db.php';
include_once "../../../include/general.php";
include "../../../include/authenticate.php";
if(!checkperm("t"))
    {
    exit("Access denied");
    }
include_once "../../../include/resource_functions.php";
include_once "../include/tms_link_functions.php";


$ref = getval("ref", 0, true);

if($ref == 0)
    {
    exit($lang["tms_link_no_resource"]);
    }

$tmsdata = tms_link_get_tms_data($ref);

if(!is_array($tmsdata))
    {
    echo $tmsdata;
    }

include "../../../include/header.php";
?>
<h2><?php echo $lang["tms_link_tms_data"]; ?></h2>
<div class='Listview'>
    <table style="border=1;">
<?php
foreach($tmsdata as $key=>$value)
	{
    ?>
	<tr> 
	<td><strong><?php echo htmlspecialchars($key); ?></strong></td>
	<td><?php echo htmlspecialchars($value); ?></td>
	</tr>
    <?php
	}
    ?>
    </table>
</div>
<?php	
include "../../../include/footer.php";