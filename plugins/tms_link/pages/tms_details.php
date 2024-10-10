<?php
include '../../../include/boot.php';

include "../../../include/authenticate.php";
if(!checkperm("t")){exit ("Access denied"); }

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
<div class="RecordBox">
    <div class="RecordPanel">
        <div class="RecordHeader">
            <div class="backtoresults">
                <a href="#" onClick="ModalClose();" class="closeLink fa fa-times" title="<?php echo escape($lang["close"]); ?>"></a>
            </div>
            <h1><?php echo escape($lang["tms_link_tms_data"]); ?></h1>
        </div>
    </div>

    <div class="BasicsBox">
    <?php
    if (!is_array($tmsdata)) {
        echo escape($tmsdata);
    } else {?>
        <div class="Listview">
        <table class="ListviewStyle">
            <?php
            foreach ($tmsdata as $module_name => $module_tms_data) {
                ?>
                <tr class="ListviewTitleStyle">
                    <th><?php echo escape($module_name); ?></th>
                    <th><?php echo escape($tmsid); ?></th>
                </tr>
                <?php
                foreach ($module_tms_data as $tms_column => $tms_value) {
                    ?>
                    <tr> 
                        <td><strong><?php echo escape($tms_column); ?></strong></td>
                        <td><?php echo escape($tms_value??""); ?></td>
                    </tr>
                    <?php
                }
            }?>
            </table>
        </div>
        <?php
    }
    ?>
    </div> <!-- End BasicsBox -->
</div> <!-- End RecordBox -->

<?php
include "../../../include/footer.php";