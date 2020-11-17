<?php
include '../../../include/db.php';
include '../../../include/authenticate.php';
if(!checkperm('t'))
    {
    http_response_code(401);
    exit('Access denied!');
    }
include_once '../../../include/resource_functions.php';
include_once '../include/museumplus_functions.php';


$ref = getval('ref', 0, true);
$mpid = getval('mpid', get_data_by_field($ref, $museumplus_mpid_field)); # CAN BE ALPHANUMERIC
$museumplus_rs_mappings = plugin_decode_complex_configs($museumplus_rs_saved_mappings);

$connection_data = mplus_generate_connection_data($museumplus_host, $museumplus_application, $museumplus_api_user, $museumplus_api_pass);
if(empty($connection_data))
    {
    $error = $lang['museumplus_error_bad_conn_data'];
    }

$mplus_data = mplus_search($connection_data, $museumplus_rs_mappings, 'Object', $mpid, $museumplus_search_mpid_field);
include '../../../include/header.php';
?>
<h2><?php echo htmlspecialchars($lang['museumplus_object_details_title']); ?></h2>
<div id="MuseumPlusDetailContainer" class='Listview'>
    <table>
    <?php
    foreach($mplus_data as $key => $value)
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
include '../../../include/footer.php';