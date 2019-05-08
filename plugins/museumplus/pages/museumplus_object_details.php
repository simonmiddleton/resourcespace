<?php
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php';
if(!checkperm('t'))
    {
    http_response_code(401);
    exit('Access denied!');
    }
include_once '../../../include/resource_functions.php';
include_once '../include/museumplus_functions.php';


$mpid = getval('mpid', ''); # CAN BE ALPHANUMERIC

$connection_data = mplus_generate_connection_data($museumplus_host, $museumplus_application, $museumplus_api_user, $museumplus_api_pass);
if(empty($connection_data))
    {
    $error = $lang['museumplus_error_bad_conn_data'];
    }
echo "<pre>";print_r(mplus_search($connection_data, 'Object', $mpid));echo "</pre>";die("You died in file " . __FILE__ . " at line " . __LINE__);

$mplus_data = array();
include '../../../include/header.php';
?>
<h2>MuseumPlus details</h2>
<div id="MuseumPlusDetailContainer" class='Listview'>
    <table style='border=1;'>
    <?php
    if(isset($mplus_data[$mpid]))
        {
        foreach($mplus_data[$mpid] as $key => $value)
            {
            ?>
            <tr> 
            <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
            <td><?php echo htmlspecialchars($value); ?></td> 
            </tr>
            <?php
            }
        }
        ?>
    </table>
</div>
<?php
include '../../../include/footer.php';