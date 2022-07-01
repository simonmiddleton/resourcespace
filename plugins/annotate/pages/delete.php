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

$id=getval('id',0,true);
$oldtextnode=ps_value("SELECT node value FROM annotate_notes WHERE ref= ? AND note_id= ?",['i', $ref, 'i', $id],"");
if ($oldtextnode > 0)
    {
    check_delete_nodes([$oldtextnode]);
    }

$notes=ps_query("DELETE FROM annotate_notes WHERE ref= ? AND note_id= ?", ['i', $ref, 'i', $id]);
$notes=ps_query("SELECT COUNT(note_id) FROM annotate_notes WHERE ref= ?", ['i', $ref]);
ps_query("UPDATE resource SET annotation_count= ? WHERE ref= ?", ['i', count($notes), 'i', $ref]);

