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
            <table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle" style="margin:0;">
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
                        $job_data= json_decode($value, true);
                        foreach($job_data as $job_data_name => &$job_data_value)
                            {
                            if(is_array($job_data_value) && count($job_data_value) > 100)
                                {
                                $job_data_short = array();
                                $job_data_count = count($job_data_value);
                                $job_data_short[$job_data_name] = array_slice($job_data_value,0,10);
                                $job_data_short["(additional elements)"] = $job_data_count . " total elements";
                                $job_data_value = $job_data_short;
                                }
                            elseif(is_string($job_data_value) && strlen($job_data_value) > 100)
                                {
                                // If a job data element is e.g. a search result set it can be very large
                                $job_data_value = mb_strcut($job_data_value,0,100);
                                }
                            }
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