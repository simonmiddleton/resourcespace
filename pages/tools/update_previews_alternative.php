<?php
#
#
# Quick 'n' dirty script to update all alternative file preview images.
# It's done one at a time via the browser so progress can be monitored.
#
#
include "../../include/db.php";

include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}
include_once "../../include/image_processing.php";

$max=ps_value("select max(ref) value from resource_alt_files",array(), 0);
$ref=getval("ref", 1);
$previewbased=getval("previewbased",false);

$resourceinfo = ps_query("SELECT a.ref, a.resource, a.file_extension FROM resource_alt_files a JOIN resource r ON a.resource = r.ref WHERE a.ref = ? AND length(a.file_extension) > 0", ["i", $ref]);
if (count($resourceinfo)>0)
	{
	create_previews($resourceinfo[0]["resource"],false,($previewbased?"jpg":$resourceinfo[0]["file_extension"]),false,$previewbased,$ref);
	?>
	<img src="<?php echo get_resource_path($resourceinfo[0]["resource"],false,"pre",false,"jpg",-1,1,false,"",$ref)?>">
	<?php
	}
else
	{
	echo "Skipping $ref";
	}

if ($ref<$max && getval("only","")=="")
	{
	?>
	<meta http-equiv="refresh" content="1;url=<?php echo $baseurl?>/pages/tools/update_previews_alternative.php?ref=<?php echo $ref+1?>&previewbased=<?php echo $previewbased?>"/>
	<?php
	}
else
	{
	?>
	Done.	
	<?php
	}
?>
	
