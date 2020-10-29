<?php
include '../include/db.php';
include '../include/authenticate.php';
if(!checkperm('a'))
    {
    error_alert($lang["error-permissiondenied"], false, 401);
    }
$job = getval("job",0,true);

$job_details = job_queue_get_job($job);

if(!is_array($job_details) || count($job_details) == 0)
    {
    exit("Invalid job reference");
    }

?>
<div class="RecordBox">
    <div class="RecordPanel">
        <div class="RecordHeader">

            <div class="backtoresults"> 
                <a href="#" onClick="ModalClose();" class="closeLink fa fa-times" title="<?php echo $lang["close"] ?>"></a>
            </div>
            <h1><?php echo $lang["job_text"] . " #" . $job_details["ref"]; ?></h1>

        </div>
       
    </div>


    <div class="BasicsBox">
        <div class="Listview">
            <table border="0" cellspacing="0" cellpadding="0" class="Listview" style="margin:0;">
                <tr class="ListviewTitleStyle">
                    <th><?php echo $lang["job_data"]?></th>
                    <th><?php echo $lang["job_value"]?></th>
                </tr>
                <?php foreach($job_details as $name => $value)
                    {
                    echo "<tr><td width='50%'>";
                    echo htmlspecialchars($name);
                    echo "</td><td width='50%'>";
                    if($name =="job_data")
                        {
                        $job_data= json_decode($value);
                        render_array_in_table_cells($job_data);
                        }
                    else
                        {
                        echo htmlspecialchars($value);
                        }
                    echo "</td></tr>";
                    }
                ?>
            </table>
        </div>
    </div>
</div>