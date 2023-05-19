<?php
include "../../include/db.php";
include_once "../../include/authenticate.php";
if(!checkperm("a")){exit("Access denied");}

set_time_limit(0);

$restype    = getval("restype",0,true);
$field      = getval("field",0,true);
$dryrun     = getval("dryrun","") != "";
$backurl    = getval("backurl","");

if(getval("submit","") != "")
    {
    $result = cleanup_invalid_nodes([$field],[$restype]);
    }
    
include_once "../../include/header.php";


?>
<div class="BasicsBox">
	<?php 
    if($backurl=="")
        {?>
        <p>
            <a href="<?php echo $backurl ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"]?></a>
        </p>
        <?php
        }
    if(isset($result))
        {
        render_top_page_error_style($result);
        }    
    ?>
	<h1>Cleanup invalid node data</h1>

	<form method="post" class="FormWide" action="<?php echo $baseurl_short ?>pages/tools/cleanup_invalid_nodes.php" onsubmit="return CentralSpacePost(this,true);">
     <?php generateFormToken("cleanup_invalid_nodes"); ?>
    <?php
    render_field_selector_question($lang["field"],"field",[],"medwidth",false,$field);
    render_resource_type_selector_question($lang["property-resource_type"], "restype","medwidth",$restype);
    ?>
	<div class="Question" >
		<label for="dryrun" ><?php echo $lang["admin_resource_type_field_migrate_dry_run"] ?></label>
		<input class="medwidth" type="checkbox" name="dryrun" value="true">
        <div class="clearerleft"> </div>
	</div>
    <div class="Question" >
		<input type="hidden" id="submitinput" name="submit" value="">
		<input type="submit" name="submit" value="<?php echo $lang["action-submit-button-label"] ?>" onclick="document.getElementById('submitinput').value='true';">
        <div class="clearerleft"> </div>
	</div>
    
	<div class="clearerleft"> </div>
    
	</form>
        
</div>
<?php


include_once "../../include/footer.php";


