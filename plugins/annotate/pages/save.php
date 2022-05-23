<?php
include_once "../../../include/db.php";
include_once "../include/annotate_functions.php";
include_once "../../../include/authenticate.php";

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

$top=getval('top','');
$left=getval('left','');
$width=getval('width','');
$height=getval('height','');
$text=getval('text','');
$text=str_replace("<br />\n"," ",$text);// remove the breaks added by get.php
$page = getval('page', 1);
$id=getval('id',0,true);
$preview_width=getval('pw','');
$preview_height=getval('ph','');

$curnode=ps_value("SELECT node value FROM annotate_notes WHERE ref= ? AND note_id= ?",['i', $ref, 'i', $id],"");

if (substr($text,0,strlen($username))!=$username)
    {
    $text=$username.": ".$text;
    }

if ($curnode > 0 && get_nodes_use_count([$curnode]) == 1)
    {
    // Reuse same node
    $savenode = set_node($curnode,$annotate_resource_type_field,$text,NULL,0);
    }
else
    {
    // Remove node from resource and create new node
    delete_resource_nodes($ref,[$curnode]);
    $savenode = set_node(NULL,$annotate_resource_type_field,$text,NULL,0);
    add_resource_nodes($ref,[$savenode], true, true);
    }

if ($id > 0)
    {
    ps_query("DELETE FROM annotate_notes WHERE ref= ? AND note_id= ?", ['i', $ref, 'i', $id]);
    }

if (substr($text,0,strlen($username))!=$username)
    {
    $text=$username.": ".$text;
    }

ps_query("INSERT INTO annotate_notes (ref,top_pos,left_pos,width,height,preview_width,preview_height,node,user,`page`) VALUES (?,?,?,?,?,?,?,?,?,?) ",
[
    'i',$ref,
    'i',$top,
    'i',$left,
    'i',$width,
    'i',$height,
    'i',$preview_width,
    'i',$preview_height,
    'i',$savenode,
    'i',$userref,
    'i',$page
]
);

$annotateid = sql_insert_id();
echo $annotateid;

$notes=ps_query("SELECT count(note_id) FROM annotate_notes WHERE ref= ?", ['i', $ref]);
ps_query("UPDATE resource SET annotation_count= ? WHERE ref= ?", ['i', count($notes), 'i', $ref]);
