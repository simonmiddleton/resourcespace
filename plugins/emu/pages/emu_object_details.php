<?php
include '../../../include/db.php';
include '../../../include/authenticate.php';
if(!checkperm('t'))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied!');
    }
include_once '../../../include/general.php';
include_once '../../../include/resource_functions.php';
include_once '../include/emu_functions.php';
include_once '../include/emu_api.php';


$ref = getvalescaped('ref', '', true);
$irn = getvalescaped('irn', '', true);

if('' == $ref && '' == $irn)
    {
    exit($lang['emu_no_resource']);
    }

$emu_data        = array();
$emu_rs_mappings = unserialize(base64_decode($emu_rs_saved_mappings));

foreach($emu_rs_mappings as $emu_module => $emu_module_columns)
    {
    $columns_list = array_keys($emu_module_columns);

    $emu_api = new EMuAPI($emu_api_server, $emu_api_server_port, $emu_module);
    $emu_api->setColumns($columns_list);

    $object_data = $emu_api->getObjectByIrn($irn);

    foreach($columns_list as $column)
        {
        if(!array_key_exists($column, $object_data))
            {
            continue;
            }

        $emu_data[$column] = $object_data[$column];
        }
    }

include '../../../include/header.php';
?>
<h2>EMu Object details</h2>
<div class='Listview'>
    <table style='border=1;'>
    <?php
    foreach($emu_data as $key => $value)
        {
        if(is_array($value))
            {
            $value = 'Non-atomic value <=> Array()';
            }
            ?>
        <tr> 
        <td><strong><?php echo $key; ?></strong></td>
        <td><?php echo $value; ?></td> 
        </tr>
        <?php
        }
        ?>
    </table>
</div>
<?php
include '../../../include/footer.php';