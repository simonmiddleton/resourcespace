<?php
include dirname(__FILE__) . '/../../include/db.php';
include_once dirname(__FILE__) . '/../../include/general.php';
include dirname(__FILE__) . '/../../include/authenticate.php';
include_once dirname(__FILE__) . '/../../include/render_functions.php';
include_once dirname(__FILE__) . '/../../include/node_functions.php';

// TODO - remove
//include dirname(__FILE__) . '/../../include/header.php';

/*?>
<script src="<?php echo $baseurl_short ?>lib/chosen/chosen.jquery.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo $baseurl_short ?>lib/chosen/chosen.min.css">
<?php
*/
// Provides html for search field 
$fieldref = getvalescaped('field', 0, true);

// Prevent access to fields to which user does not have access to
if(!metadata_field_view_access($fieldref))
    {
    header('HTTP/1.1 401 Unauthorized');
    $return['error'] = array(
        'status' => 401,
        'title'  => 'Unauthorized',
        'detail' => $lang['error-permissiondenied']);

    echo json_encode($return);
    exit();
    }

$nodeoptions = get_nodes($fieldref,NULL,true);

$result["success"] = true;
$result["options"] = array();
foreach($nodeoptions as $nodeoption)
    {
    $result["options"][$nodeoption["ref"]] = $nodeoption["name"];
    //echo "<option value='" . $option["ref"] . "'>" . i18n_get_translated($option["name"]) . "</option>\n";
    }

echo json_encode($result);
