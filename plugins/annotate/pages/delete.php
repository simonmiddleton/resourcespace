<?php
include_once "../../../include/db.php";
include_once "../include/annotate_functions.php";
include_once "../../../include/authenticate.php";

$ref=getvalescaped("ref", 0, true);
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

$id=getvalescaped('id','');
$oldtext=ps_value("select note value from annotate_notes where ref= ? and note_id= ?",['i', $ref, 'i', $id],"");

if ($oldtext!="")
    {
	remove_keyword_mappings($ref,i18n_get_indexable($oldtext),-1,false,false,"annotation_ref",$id);
	debug("Annotation: deleting keyword: " . i18n_get_indexable($oldtext). " from resource id: " . $ref);
    }

$notes=ps_query("delete from annotate_notes where ref= ? and note_id= ?", ['i', $ref, 'i', $id]);
$notes=ps_query("select * from annotate_notes where ref= ?", ['i', $ref]);
ps_query("update resource set annotation_count= ? where ref= ?", ['i', count($notes), 'i', $ref]);

