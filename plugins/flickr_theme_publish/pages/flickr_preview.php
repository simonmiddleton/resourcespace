<?php
// grabs preview image to show while publishing
include "../../../include/db.php";

include "../../../include/authenticate.php";

$ref=getvalescaped("ref","");
if($ref!=''){
	$path=get_resource_path($ref,false,"thm",false);
	
	$title=ps_value("select value from resource_data where resource_type_field = ? and resource = ?", array("i",$view_title_field,"i",$ref), "");
	$title=i18n_get_translated($title);
	
	$description=ps_value("select value from resource_data where resource_type_field = ? and resource = ?", array("i",$flickr_caption_field,"i",$ref), "");
	$keywords=ps_value("select value from resource_data where resource_type_field = ? and resource = ?", array("i",$flickr_keywords_field,"i",$ref), "");
	$photoid=ps_value("select flickr_photo_id value from resource where ref = ?", array("i",$ref), "");
	
	$results=array($path,$title,$description,$keywords,$photoid);
	echo json_encode($results);
}
?>
