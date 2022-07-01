<?php
include_once "../../../include/db.php";
include_once "../include/annotate_functions.php";

$k=getval("k","");if (($k=="") || (!check_access_key(getval("ref",""),$k))) {include_once "../../../include/authenticate.php";}

$ref=getval("ref", 0, true);
if ($ref==0)
	{
	die($lang["annotate_ref_not_supplied"]);
	}

global $plugins;
if (!in_array("annotate",$plugins))
	{
	header("Status: 403 plugin not activated");
	exit($lang["error-plugin-not-activated"]);
	}

$preview_width  = getval("pw", 0, true);
$preview_height = getval("ph", 0, true);
$page           = getval("page", 1, true);

// Get notes based on page:
$sql_and = '';
$sql_params[] = 'i'; $sql_params[] = $ref;
if($page >= 1)
	{
	$sql_and = ' AND page = ?';
    $sql_params[] = 'i'; $sql_params[] = $page;
	}
$notes=ps_query("SELECT ref, top_pos, left_pos, width, height, preview_width, preview_height, note_id, user, `page`, node FROM annotate_notes WHERE ref= ?" . $sql_and, $sql_params);
$notecount = count($notes);
ps_query("UPDATE resource SET annotation_count= ? WHERE ref= ?", ['i', $notecount, 'i', $ref]);
// check if display size is different from original preview size, and if so, modify coordinates


for($i=0;$i<$notecount;$i++)
    {
    $annotate_node =[];
    get_node($notes[$i]["node"],$annotate_node);
    $notes[$i]["note"] = $annotate_node["name"];
    }

$json="[";
$notes_array=array();
for ($x=0;$x<count($notes);$x++)
	{	
	$ratio=$preview_width/$notes[$x]['preview_width'];
			
	$notes[$x]['width']=$ratio*$notes[$x]['width'];
	$notes[$x]['height']=$ratio*$notes[$x]['height'];
	$notes[$x]['top_pos']=$ratio*$notes[$x]['top_pos'];
	$notes[$x]['left_pos']=$ratio*$notes[$x]['left_pos'];
	$notes[$x]['note'] = str_replace(chr(13). chr(10), '<br />\n', $notes[$x]['note']);
	$notes[$x]['note'] = str_replace(chr(13), '<br />\n', $notes[$x]['note']);
	$notes[$x]['note'] = str_replace(chr(10), '<br />\n', $notes[$x]['note']);

	if (!$annotate_show_author) # Don't display author unless set in config
		{
		$notes[$x]['note'] = substr(strstr($notes[$x]['note'],": "),2);
		}

	if ($x>0){$json.=",";}

	$json.="{";
	$json.='"top":'.round($notes[$x]['top_pos']).', ';
	$json.='"left":'.round($notes[$x]['left_pos']).', ';
	$json.='"width":'.round($notes[$x]['width']).', ';
	$json.='"height":'.round($notes[$x]['height']).', ';
	$json.='"text":"'.config_encode($notes[$x]['note']).'", ';
	$json.='"id":"'.$notes[$x]['note_id'].'", ';

	if (isset($userref) && (($notes[$x]['user']==$userref) || in_array($usergroup,$annotate_admin_edit_access)))
		{
		$json.='"editable":true';
		} 
	else 
		{
		$json.='"editable":false';	
		}

	$json.="}";
	}

$json.="]";
echo $json;

